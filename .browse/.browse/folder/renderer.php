<?php

class page{
  public $docroot;          //relative_path
  public $dir;              //as requested
  public $path;             //relative_path
  public $url;              //encoded
  public $urlq;             //standard query ?0=docroot/path
  public $label;            //utf8_encoded
  public $my;               //global.ini own section
  public $cfg;              //global.ini
  public $set;              //maybe changing varname vars
  public $stackoprints=[];  //stack of content
  public $content_hook;     //SimpleXMLElement
  public $xml;              //htm template
  public $css;              //css style
  public function __construct($dir){
    //
    $this->docroot= $_SERVER["ROOT"];
    $this->cfg    = &$_SERVER["CFG"];
    $this->my     = &$_SERVER["CFG"]["FOLDER"];
    $this->dir    = $dir;
    $this->path   = $dir;//see setup/renderer
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    //
    $this->set = new stdClass;
    $this->set->site_title  = $this->my["SITE_TITLE"];
    $this->set->wallpaper   = $this->my["FX_WPAPER"];
    $this->set->cast        = $this->my["FX_CAST"];
    $this->set->logo        = $this->my["FX_LOGO"];
    $this->set->exclude     = explode("\t",$this->my["EXCLUDE"]);
    $this->set->mr_images   = $this->cfg["SETUP"]["IMAGES"];
    $this->set->ui_current  = $this->cfg["SETUP"]["CURRENT"];
    $this->set->mirror_fifo = glob($this->path."/*");
    $this->set->renderer = $this->cfg["RENDERER"];
    $this->set->ui = array();
    $keys = [WPAPER,LOGO,CAST];
    //
    $this->xml = new SimpleXMLElement($this->my["SITE"],null,true);
    $this->css = file_get_contents($this->my["STYLE"]);
    //brand  layout
    foreach(glob(substr($this->path,0,strpos($this->path,I)).I.$this->ui_current.I."*") as $layout_item){
      $key = substr(basename($layout_item),0,-4);
      $this->set->ui[$key] = urlencode($layout_item);
      if(in_array($key,$keys)){
        $this->set->$key = urlencode($layout_item);
        }
      }
    //subpage layout
    foreach(glob($this->dir.I.$this->ui_current.I."*") as $layout_item){
      $key = substr(basename($layout_item),0,-4);
      $this->set->ui[$key] = urlencode($layout_item);
      if(in_array($key,$keys)){
        $this->set->$key = urlencode($layout_item);
        }
      }
    //
    $this->check_mirror();
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
    $path = $this->path;
    if(!is_dir($path)){
      if(!mkdir($path)){
        trace_log("check_mirror $path");
        }
      else{
        mkdir($path.I.$this->cfg["SETUP"]["IMAGES"]);
        mkdir($path.I.$this->cfg["SETUP"]["CURRENT"]);
        }
      }
    }
  public function handle_request(){
    $this->set->filter = ""; //preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    }
  public function addBasics(){

    $title  = $this->xml->xpath("/html/head/title");
    $title[0][0] = $this->set->site_title;

    $style       = $this->xml->xpath("/html/head/style");
    $style[0][0] = $this->css;

    $design   = $this->xml->xpath("/html/body/img");
    $design[0]->attributes()["src"] = $this->set->wallpaper;
    $design[1]->attributes()["src"] = $this->set->cast;

    $cell    = $this->xml->xpath("//td");
    //0
    $logo = $cell[0]->addChild('img');
    $logo->addAttribute("id","logo");
    $logo->addAttribute("src",$this->set->logo);
    //1
    $menu = $cell[1]->addChild('form');
    $menu->addAttribute("id","menu");
    $menu->addAttribute("name","menu");
    $menu->addAttribute("method","post");
    $menu->addAttribute("action",$this->urlq);
    $menu->addAttribute("target","_self");
    $menu->addAttribute("accept-charset","utf-8");
    
    if(preg_match("/".preg_quote($this->mr_images)."/",$this->url)){
      $this->addMenu($menu,$this->path);
      }

    $mirror_switch = $menu->addChild("a");
    $mirror_switch->addAttribute("href","?0=".MIRROR."/".urlencode($this->dir));
    $img = $mirror_switch->addChild('img');
    $img->addAttribute("id","mirror_switch");
    $img->addAttribute("src",MIRROR."/images/setup-button.png");
    //2
    //3
    $content = $cell[4]->addChild("div");
    $content->addAttribute("id","content");
    $this->content_hook = &$content;
    //5
    //6
    $navi = $cell[7]->addChild("h3");
    $navi->addAttribute("class","navi");
    $this->addNavi($navi,$this->dir);
    //8
    }
  public function addNavi(SimpleXMLElement &$e,$path){
    $m = $e->addChild("a","&lArr;");
    $m->addAttribute("href","/");
    $c = "";
    foreach(explode("/",$path) as $f){
      $m = $e->addChild("a",utf8_encode($f));
      $m->addAttribute("href","?0=".urlencode($c.$f));
      $c .= $f."/";
      }
    }
  public function addMenu(SimpleXMLElement &$e,$path){
    $m = $e->addChild("input");
    $m->addAttribute("id","term");
    $m->addAttribute("name","term");
    $m->addAttribute("autocomplete","off");
    $m->addAttribute("type","text");
    $m->addAttribute("value",utf8_encode(str_replace(["/",$this->mr_images]," ",$path)));
    
    $m = $e->addChild("input");
    $m->addAttribute("id","count");
    $m->addAttribute("name","count");
    $m->addAttribute("min","1");
    $m->addAttribute("type","number");
    $m->addAttribute("value","5");

    $m = $e->addChild("input");
    $m->addAttribute("type","submit");
    $m->addAttribute("value","");
    $m->addAttribute("onmouseover","document.forms['menu'].term.style.backgroundColor='#000000';");
    $m->addAttribute("onmouseout" ,"document.forms['menu'].term.style.backgroundColor='transparent';");
    }
  public function defer_addXML($e,$fifo,$func){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$fifo,"func"=>$func];
    }
  public function addGlob(SimpleXMLElement &$e,$path_expression,$excludes=array()){
    
    $router = ["defer_addXML","addFolder"];
    $args   = ["addFile",null];

    foreach(glob($path_expression) as $fifo){
      if(!in_array($fifo,$this->exclude)){
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
    elseif($ext!="PHP"){
      $basename = basename($path);
      $class = $this->style_file($ext);
      $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
      $label = utf8_encode(str_replace(["_","."]," ", substr($basename,0,-4)));
      $link = $e->addChild("a");
      $link->addAttribute("href","?0=".urlencode($path));
      $link->addAttribute("target","bypass");
      $div = $link->addChild("div");
      $div->addAttribute("class","file $class");
      
      if(!$cover){
        $div->addChild("span",$label);
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
    $basename = basename($path);
    $path = substr($path,strlen($this->docroot));
    $label = utf8_encode(str_replace(["_","."]," ",$basename));
    $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".urlencode($path));
    $div = $link->addChild("div");
    $div->addAttribute("class","folder");

    if(!$cover){
      $div->addChild("span",$label);
      }
    else{
      $img = $div->addChild("img");
      $img->addAttribute("src",$cover);
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
    $this->handle_request();
    $this->addBasics();
    $this->addGlob($this->content_hook,$this->docroot.$this->dir.$this->filter."/*");
    print $this->xml->asXML();
    }
  public function style_file($ext){
    static $extypes;
    if(!is_file(".browse/images/".strtolower($ext).".png")){ return ""; }
    if(!is_array($extypes)){ $extypes = array(); }
    if(!in_array($ext,$extypes)){
      $extypes[] = $ext;
      $hook = $this->xml->xpath("/html/head");
      $head = $hook[0];
      $head->addChild("style",".{$ext} {background-image:url(\".browse/images/".strtolower($ext).".png\");}");
      }
    return $ext;
    }
  }
