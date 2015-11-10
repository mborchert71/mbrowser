<?php
//
class page{
  public $dir;              //as requested
  public $url;              //encoded
  public $urlq;             //standard query ?0=docroot/path
  public $label;            //utf8_encoded
  public $set;              //maybe changing varname vars
  public $stackoprints=[];  //stack of content
  public $content_hook;     //SimpleXMLElement
  public $xmlraw;           //htm template
  public $xml;              //htm template
  public $css;              //css style
  public $cfg;              //global.ini
  public $my;               //global.ini THIS RENDERERs section
  public $root;             //global.ini ROOT section
  public $setup;            //global.ini SETUP section
  public $folder;           //global.ini FOLDER section
  public $util;             //global.ini UTIL section
  public $excludes;         //global.ini
  public function __construct($dir){
    $this->cfg   = &$_SERVER["CFG"];
    $this->my    = &$_SERVER["CFG"]["FOLDER"];
    $this->root  = &$_SERVER["CFG"]["ROOT"];
    $this->setup = &$_SERVER["CFG"]["SETUP"];
    $this->folder= &$_SERVER["CFG"]["FOLDER"];
    $this->util  = &$_SERVER["CFG"]["UTIL"];
    $this->dir   = $dir;
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    $this->excludes     = explode("\t",$this->my["EXCLUDE"]);
    $this->xmlraw= file_get_contents(__DIR__.I."page.htm");
    $this->xml = new SimpleXMLElement($this->xmlraw);
    $this->css = file_get_contents(__DIR__.I."style.css");
    //
    $this->set = new stdClass;
    $this->set->site_title  = $this->my["SITE_TITLE"];
    $this->set->wallpaper   = CODEBASE.IMAGES.I."wallpaper.png";
    $this->set->cast        = CODEBASE.IMAGES.I."cast.jpg";
    $this->set->logo        = CODEBASE.IMAGES.I."logo.png";
    $this->set->mirror_fifo = glob(MIRROR.I.$this->dir.I."*");
    $this->set->renderer = $this->cfg["RENDERER"];
    $this->set->ui = array();
    $keys = [WPAPER,LOGO,CAST];
    //
    if(strpos($this->dir,I)){
    //brand  layout
      foreach(glob(MIRROR.I.substr($this->dir,0,strpos($this->dir,I)).I.CURRENT.I."*") as $layout_item){
        $key = substr(basename($layout_item),0,-4);
        $this->set->ui[$key] = urlencode($layout_item);
        if(in_array($key,$keys)){
          $this->set->$key = urlencode($layout_item);
          }
        }
      }
    //page layout
    foreach(glob(MIRROR.I.$this->dir.I.CURRENT.I."*") as $layout_item){
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
    $path = MIRROR.I.$this->dir;
    if(!is_dir($path)){
      if(!mkdir($path)){
        trace_log("check_mirror $path");
        }
      else{
        mkdir($path.I.IMAGES);
        mkdir($path.I.CURRENT);
        }
      }
    }
  public function handle_request(){
    $this->set->filter = ""; //preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    }
  public function addBasics(){
    $head = $this->xml->xpath("/html/head");
    $icon = $head[0]->addChild("link");
    $icon->addAttribute("rel","icon");
    $icon->addAttribute("type","image/png");
    $icon->addAttribute("href",MIRROR.I."favicon.ico");
    //
    $this->css .= "
    #background {
      background:#000000 url(\"".CODEBASE.I.IMAGES.I."background.jpg\") center bottom;
      overflow:hidden;
      }
    .folder{
      background:url(\"".CODEBASE.I.IMAGES.I."folder_background.png\") no-repeat top center;
      }
    .file{
      background:url(\"".CODEBASE.I.IMAGES.I."file_background.png\") no-repeat top center;
      }
    ";

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

    $mirror_switch = $menu->addChild("a");
    $mirror_switch->addAttribute("href","?0=".urlencode(MIRROR.I.$this->dir));
    $img = $mirror_switch->addChild('img');
    $img->addAttribute("id","mirror_switch");
    $img->addAttribute("src",CODEBASE.IMAGES.I."setup-button.png");
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
    $navi = $cell[8]->addAttribute("style","width:400px");
    }
  public function addNavi(SimpleXMLElement &$e,$path){
    $m = $e->addChild("a","&lArr;");
    $m->addAttribute("href","?filter=".strtoupper(substr($path,0,1)));
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
    $m->addAttribute("value",utf8_encode(str_replace(["/",IMAGES]," ",$path)));
    
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
  public function addGlob(SimpleXMLElement &$e,$path_expression,$excludes=array()){
    //
    $router = ["deferXml","addFolder"];
    $args   = ["addFile",null];
    foreach(glob($path_expression) as $fifo){
      if(!in_array($fifo,$this->excludes)){
        $idx = intval(is_dir($fifo));
        $this->$router[$idx]($e,$fifo,$args[$idx]);
        }
      }
    foreach($this->stackoprints as $fx){
      $this->$fx["func"]($fx["hook"],$fx["path"]);
      }
    }
  public function addItem(SimpleXMLElement &$e,$path,$target,$class,$id,$label){
    $basename = basename($path);
    $label = utf8_encode(str_replace([".","_"],"",$label));
    $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".urlencode($path));
    $link->addAttribute("target",$target);
    $div = $link->addChild("div");
    $div->addAttribute("class",$class);
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
  public function addFile(SimpleXMLElement &$e,$path){
    $ext = strtoupper(substr($path,-3));
    if(array_key_exists($ext,$this->renderer)){
      $render = $this->renderer[$ext];
      $this->$render($e,$path);    
      }
    elseif($ext!="PHP"){
      $class = $this->style_file($ext);
      $this->addItem($e,$path,"bypass","file $class",null,substr(basename($path),0,-4));
      }   
    }
  public function addFolder(SimpleXMLElement &$e,$path){
    $this->addItem($e,$path,"_self","folder",null,basename($path));
    }
  public function addImage(SimpleXMLElement &$e,$path){
    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",CODEBASE."file.php?0=".urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",CODEBASE."file.php?0=".urlencode($path));
    }
  public function deferXml($e,$fifo,$func){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$fifo,"func"=>$func];
    }
  public function style_file($ext){
    static $extypes;
    if(!is_array($extypes)){ $extypes = array(); }
    if(!in_array($ext,$extypes)){
      if(!is_file(CODEBASE.IMAGES.I.strtolower($ext).".png")){ return ""; }
      $extypes[] = $ext;
      $hook = $this->xml->xpath("/html/head");
      $head = $hook[0];
      $head->addChild("style",".{$ext} {background-image:url(\"".CODEBASE.IMAGES.I.strtolower($ext).".png\");background-repeat:no-repeat}");
      }
    return $ext;
    }
  public function full_print(){
    $this->handle_request();
    $this->addBasics();
    $this->addGlob($this->content_hook,$this->dir.$this->filter."/*");
    print $this->xml->asXML();
    }
  //
  }
