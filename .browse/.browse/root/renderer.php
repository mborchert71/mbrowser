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
    $this->set-> excludes  = explode("\t",$this->my["EXCLUDE"]);
    $this->set-> renderer  = $this->cfg["RENDERER"];
    $this->set->ui_current  = $this->cfg["SETUP"]["CURRENT"];
    $this->set->ui = array();
    foreach(glob($this->ui_current.I."*") as $layout_item){
      $key = substr(basename($layout_item),0,-4);
      $this->set->ui[$key] = $layout_item;
      }
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
  public function check_mirror($path){
    }
  public function handle_request($path){
    if(array_key_exists($_SERVER["CFG"]["SEARCH"],$_GET)){
      loading_screen($location=null);
      $path = $_GET[1];
      $term = $_GET[$_SERVER["CFG"]["TERM"]];
      $imgdir = $_SERVER["CFG"]["SETUP"]["IMAGES"];
      if(is_file("../$path")){
        $tags   = $_SERVER["CFG"]["ROOT"]["TAGS_FILE"];
        }
      else{
        $tags   = $_SERVER["CFG"]["ROOT"]["TAGS_FOLDER"];
        }
      include_once($_SERVER["CFG"]["UTIL"]["SEARCH_FILE"]);
      $s = $term."(".str_replace(" "," OR ",$_SERVER["CFG"]["ROOT"]["TAGS"]).") ".$tags;
      $m = search_engine_request($_SERVER["CFG"]["UTIL"]["SEARCH_SERVER"],urlencode($s));
      $c = @count($m);
      if(!count($m)){
        trace_log("search_engine_find found zero $term");
        return "";
        }
      for($i=0;$i<$c;$i++){
        $img = "http://".htmlspecialchars(urldecode($m[$i]));
        $ext = strtolower(substr($img,-3));
        $file = @file_get_contents($img);
        if($file){ $i = $c;}
        }
      if(!$file){
        trace_log("search_engine_fetch none");
        }
      else{
        $newImage= $imgdir.I.$term.".".$ext;
        if(!file_put_contents($newImage,$file)){
          trace_log("search_engine_single.file_put_contents $img");
          return "";
        }
        else{
          @file_put_contents($_SERVER["CFG"]["SETUP"]["FETCH_LOG"],"$term.$ext\t{$img}\n",FILE_APPEND );
          $fifo = basename($path);
          $cover  = $_SERVER["CFG"]["SETUP"]["CURRENT"].I.$fifo.".".$ext;
          $width  = $_SERVER["CFG"]["SETUP"]["PREVIEW_MAX_WIDTH"];
          $height = $_SERVER["CFG"]["SETUP"]["PREVIEW_MAX_HEIGHT"];
          create_preview($newImage,$cover,$width,$height);
          }
        }
      loading_screen($location="/#".md5($term));
      }
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
      $basename = basename($path);
      $name = substr($basename,0,-4);
      $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
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
        $a->addAttribute("href","?1=".urlencode($path)."&".$this->cfg["SEARCH"]."&".$this->cfg["TERM"]."=".$name);
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
    $basename = basename($path);
    $path  = substr($path,strlen($this->docroot));
    $label = utf8_encode(str_replace(["_","."]," ",$basename));
    //
    $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
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
      $a->addAttribute("href","?1=".urlencode($path)."&".$this->cfg["SEARCH"]."&".$this->cfg["TERM"]."=".$path);
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
