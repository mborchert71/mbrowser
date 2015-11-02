<?php

class page{
  public $docroot;          //relative_path
  public $dir;              //as requested
  public $path;             //relative_path
  public $url;              //encoded
  public $urlq;             //standard query ?0=docroot/path
  public $label;            //utf8_encoded
  public $my;               //global.ini SETUP section
  public $cfg;              //global.ini
  public $set;              //maybe changing varname vars
  public $stackoprints=[];  //stack of content
  public $content_hook;     //SimpleXMLElement
  public $xml;              //htm template
  public $css;              //css style
  //
  public function __construct($dir){
    $this->docroot= $_SERVER["ROOT"];
    $this->cfg    = &$_SERVER["CFG"];
    $this->my     = &$_SERVER["CFG"]["ROOT"];
    $this->dir    = $dir;
    $this->path   = $dir;//see setup/renderer
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    //
    $this->set             =  new stdClass;
    $this->set-> site_title= $this->my["SITE_TITLE"];
    $this->set-> filter    = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    $this->set-> excludes  = explode(";",$_SERVER["CFG"]["ROOT"]["EXCLUDE"]);
    $this->set-> renderer  = $this->cfg["RENDERER"];
    //
    $this->xml = new SimpleXMLElement($this->my["SITE"],null,true);
    $this->css = file_get_contents($this->my["STYLE"]);
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
    }
  public function addBasics(){
    //
    $title       = $this->xml->xpath("/html/head/title");
    $title[0][0] = $this->set->site_title;
    //
    $style       = $this->xml->xpath("/html/head/style");
    $style[0][0] = $this->css;
    //
    $content     = $this->xml->xpath("/html/body/div/table/tbody/tr/td/div");
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
    //
    $router = ["defer_addXML","addFolder"];
    $args   = ["addFile",null];
    //
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
      $cover = urlencode(@array_pop(glob($this->cfg["SETUP"]["IMAGES"].I.FX.COVER."_"."$name.*")));
      $label = utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4)));
      $link = $e->addChild("a");
      $link->addAttribute("href","?0=".urlencode($path));
      $link->addAttribute("target","bypass");
      $link->addAttribute("onmouseover","document.getElementById('title_display').innerHTML=\"$label\"");
      $link->addAttribute("onmouseout","document.getElementById('title_display').innerHTML=\"\"");
      $div = $link->addChild("div");
      $div->addAttribute("class","file");
      $div->addAttribute("id",md5($name));
      
      if(!$cover){
        $div->addChild("br");
        $div->addChild("span",$label);
        $div->addChild("br");
        $div->addChild("br");
        $a = $div->addChild("a");
        //$a->addAttribute("target","bypass");
        $a->addAttribute("href","?0=".urlencode($path)."&".$this->cfg["SEARCH"]."&".$this->cfg["TERM"]."=".$name);
        $img = $a->addChild("img");
        $img->addAttribute("title","Find Cover");
        $img->addAttribute("src",MIRROR."/images/search-button.png");
        $img->addAttribute("style","width:32px;height:32px");
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
    //
    $path  = substr($path,strlen($this->docroot));
    $label = utf8_encode(str_replace(["_","."]," ",basename($path)));
    $cover = @array_pop(glob($path."/".$this->cfg["SETUP"]["IMAGES"]."/".FX.COVER."*"));
    //
    $link = $e->addChild("a");
    $link-> addAttribute("href","?0=".urlencode($path));
    $link-> addAttribute("onmouseover","document.getElementById('title_display').innerHTML=\"$label\"");
    $link-> addAttribute("onmouseout","document.getElementById('title_display').innerHTML=\"\"");
    $div  = $link->addChild("div");
    $div->  addAttribute("class","folder");
    $div->addAttribute("id",md5($path));
    //
    if(!$cover){
      $div->addChild("br");
      $div->addChild("span",$label);
      $div->addChild("br");
      $div->addChild("br");
      $a = $div->addChild("a");
      //$a->addAttribute("target","bypass");
      $a->addAttribute("href","?0=".urlencode($path)."&".$this->cfg["SEARCH"]."&".$this->cfg["TERM"]."=".$path);
      $img = $a->addChild("img");
      $img->addAttribute("title","Find Cover");
      $img->addAttribute("src",MIRROR."/images/search-button.png");
      $img->addAttribute("style","width:32px;height:32px");
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
    $link->addAttribute("href",MIRROR."/file.php?0=".$this->docroot.urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",MIRROR."/file.php?0=".$this->docroot.urlencode($path));
    }
  public function full_print(){
    $this->handle_request($this->dir);
    $this->addBasics($this->dir);
    $this->addGlob($this->content_hook,$this->docroot.$this->dir.$this->filter."*",$this->excludes);
    print $this->xml->asXML();
    }
  }
