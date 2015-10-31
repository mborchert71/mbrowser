<?php

class page{
  public $dir;
  public $path;
  public $docroot;
  public $url;
  public $urlq;
  public $label;
  public $set;
  public $renderer= [];
  public $stackoprints=[];
  public $content_hook;
  public $xml;
  public function __construct($dir,$xml,$opt=null){
    $this->dir = $dir;
    $this->xml = new SimpleXMLElement($xml,null,true);
    }
  public function __get($key){
    if(isset($this->set,$key)){
      return $this->set->$key;
      }
    }
  public function __set($key,$val){
    if(isset($this->set,$key)){
      $this->set->$key=$val;
      return true;
      }
      return false;
    }
  public function check_mirror(){
    }
  public function handle_request($path){
    $CFG  = &$_SERVER["CFG"];
    $I    = DIRECTORY_SEPARATOR;
    if(array_key_exists($CFG["REQUEST"]["SEARCH"],$_REQUEST)){
      include_once($_SERVER["UTIL"]["SEARCH"]);
      foreach(glob($_SERVER["ROOT"]."*") as $fifo){
        $path = basename($fifo);
        if(is_file($fifo)){
          $name = substr($path,0,strlen($path)-4);
          $cover= @array_pop( glob($CFG["FILE"]["FX_PATH"].$I.$CFG["FILE"]["FX"].$name."*") );
          if(!count($cover) && !search_engine_single($CFG["UTIL"]["SEARCH_SERVER"],utf8_encode($name),$CFG["FILE"]["TAGS"])){
            trace_log("root_page.search_engine_single $name");
            }
          }
        elseif(is_dir($fifo) && !is_dir($path)){
          search_async($path);
          }
        }
      }
    }
  public function setBasics(){
    $dir = $this->dir ;
    $this->docroot = $_SERVER["ROOT"];
    $this->path   = $dir;
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));

    $this->set = new stdClass;
    $this->set->filter = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    $this->set->excludes = explode(";",$_SERVER["CFG"]["ROOT"]["EXCLUDE"]);

    $this->renderer = [
    "JPG" => "addImage",
    "PNG" => "addImage",
    "GIF" => "addImage",
    "BMP" => "addImage"
    ];
    $this->stackoprints = [];

    $title  = $this->xml->xpath("/html/head/title");
    $title[0][0] = "Media Browser";

    $style       = $this->xml->xpath("/html/head/style");
    $style[0][0] = file_get_contents('.browse/root/style.css');

    $content       = $this->xml->xpath("/html/body/div");
    $this->content_hook = &$content [0][0];

    }
  public function addNavi(SimpleXMLElement &$e,$path){

    }
  public function addMenu(SimpleXMLElement &$e,$path){

    }
  public function defer_addXML(&$e,$fifo,$func){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$fifo,"func"=>$func];
    }
  public function addGlob(SimpleXMLElement &$e,$path_expression,$excludes=array()){

    $router = ["defer_addXML","addFolder"];
    $args   = ["addFile",null];

    foreach(glob($path_expression) as $fifo){
      if(!in_array(basename($fifo),$excludes)){
        $idx = intval(is_dir($fifo));
        $this->$router[$idx]($e,$fifo,$args[$idx]);
        }
      }
    foreach($this->stackoprints as $fx){
      $this->$fx["func"]($fx["hook"],$fx["path"]);
      }
    }
  public function addFile(SimpleXMLElement &$e,$path){
    $path = substr($path,strlen($this->docroot));
    $ext = strtoupper(substr($path,-3));
    if(array_key_exists($ext,$this->renderer)){
      $render = $this->renderer[$ext];
      $this->$render($e,$path);    
      }
    elseif($ext!="php"){
      $name = basename(substr($path,0,strlen($path)-4));
      $cover = urlencode(@array_pop( glob($_SERVER["CFG"]["FILE"]["FX_PATH"]."/".$_SERVER["CFG"]["FILE"]["FX"]."$name.*") ));
      $label = utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4)));
      $link = $e->addChild("a");
      $link->addAttribute("href","?0=".urlencode($path));
      $link->addAttribute("target","bypass");
      $div = $link->addChild("div");
      $div->addAttribute("class","file");
      if(!$cover){
        $div->addChild("span",$label);
        $div->addChild("br",$label);
        $a = $div->addChild("a",$label);
        $a->addAttribute("target","bypass");
        $a->addAttribute("href","?research&0=".urlencode($path));
        $img = $a->addChild("img");
        $img->addAttribute("title","Find Cover");
        $img->addAttribute("src",".browse/images/search-button.png");
        $img->addAttribute("width","32px");
        $img->addAttribute("height","32px");
        }
      else{
        $img = $div->addChild("img");
        $img->addAttribute("src",$cover);
        $img->addAttribute("alt",$label);
        $img->addAttribute("title",$label);
        }
      }   
    }
  public function addFolder(SimpleXMLElement &$e,$path){

    $path = substr($path,strlen($this->docroot));
    $label = utf8_encode(str_replace(["_","."]," ",basename($path)));
    $cover = @array_pop(glob($path."/".$_SERVER["CFG"]["SETUP"]["IMAGES"]."/".$_SERVER["CFG"]["FOLDER"]["FX_COVER"]."*"));

    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".urlencode($path));
    $div = $link->addChild("div");
    $div->addAttribute("class","folder");

    if(!$cover){
      $div->addChild("span",$label);
      }
    else{
      $img = $div->addChild("img");
      $img->addAttribute("src",urlencode($cover));
      $img->addAttribute("alt",$label);
      $img->addAttribute("title",$label);
      }
    }
  public function addImage(SimpleXMLElement &$e,$path){

    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",".browse/file.php?0=".$this->docroot.urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",".browse/file.php?0=".$this->docroot.urlencode($path));

    }
  public function full_print(){
    $this->setBasics($this->dir);
    $this->addGlob($this->content_hook,$this->docroot.$this->dir.$this->filter."*",$this->excludes);
    print $this->xml->asXML();
    }
  }


(new page($dir,".browse/root/page.htm"))->full_print();