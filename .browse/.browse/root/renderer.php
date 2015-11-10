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
    $this->my    = &$_SERVER["CFG"]["ROOT"];
    $this->root  = &$_SERVER["CFG"]["ROOT"];
    $this->setup = &$_SERVER["CFG"]["SETUP"];
    $this->folder= &$_SERVER["CFG"]["FOLDER"];
    $this->util  = &$_SERVER["CFG"]["UTIL"];
    $this->dir   = $dir;
    $this->url   = urlencode($dir);
    $this->urlq  = "?0=".$this->url;
    $this->label = utf8_encode(basename($dir));
    $this->xmlraw= file_get_contents(__DIR__.I."page.htm");
    $this->xml = new SimpleXMLElement($this->xmlraw);
    $this->css = file_get_contents(__DIR__.I."style.css");
    $this->excludes  = explode("\t",$this->my["EXCLUDE"]);
    //
    $this->set             =  new stdClass;
    $this->set-> site_title= $this->my["SITE_TITLE"];
    $this->set-> filter    = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    $this->set-> renderer  = $this->cfg["RENDERER"];
    //
    $this->set->ui = array();
    foreach(glob(MIRROR.I.CURRENT.I."*") as $layout_item){
      $key = substr(basename($layout_item),0,-4);
      $this->set->ui[$key] = $layout_item;
      }
    //
    $this->set->cells = $this->xml->xpath("/html/body/div/table/tbody/tr/td");
    $this->set->cells["top_left"]     = &$this->set->cells[0];
    $this->set->cells["top_center"]   = &$this->set->cells[1];
    $this->set->cells["top_right"]    = &$this->set->cells[2];
    $this->set->cells["middle_left"]  = &$this->set->cells[3];
    $this->set->cells["middle_center"]= &$this->set->cells[4];
    $this->set->cells["middle_right"] = &$this->set->cells[5];
    $this->set->cells["bottom_left"]  = &$this->set->cells[6];
    $this->set->cells["bottom_center"]= &$this->set->cells[7];
    $this->set->cells["bottom_right"] = &$this->set->cells[8];
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
  public function handle_request($path){
    if(array_key_exists(KEY_SEARCH,$_GET)){
      loading_screen($location=null);
      include_once(CODEBASE."search.php");
      $path = $_GET[1];
      $basename = basename($path);
      $term = $_GET[KEY_TERM];
      $tags = ["TAGS_FOLDER","TAGS_FILE"][intval(is_file($path))];
      $s = $term."(".str_replace(" "," OR ",$this->my["TAGS"]).") ".$this->my[$tags];
      $m = search_engine_request($this->util["SEARCH_SERVER"],urlencode($s));
      $c = @count($m);
      if(!count($m)){
        trace_log("search_engine_find found zero $term");
        return;
        }
      for($i=0;$i<$c;$i++){
        $img = "http://".htmlspecialchars(urldecode($m[$i]));
        $ext = strtolower(substr($img,-4));
        $file = @file_get_contents($img);
        if($file){ $i = $c;}
        }
      if(!$file){
        trace_log("search_engine_fetch none");
        }
      else{
        $newImage= MIRROR.I.IMAGES.I.$basename.$ext;
        if(!file_put_contents($newImage,$file)){
          trace_log("search_engine_single.file_put_contents $img");
          return;
        }
        else{
          @file_put_contents(MIRROR.I.$this->setup["FETCH_LOG"],$term.$ext."\t{$img}\n",FILE_APPEND );
          $cover  = MIRROR.I.CURRENT.I.$basename.$ext;
          $width  = $this->setup["PREVIEW_MAX_WIDTH"];
          $height = $this->setup["PREVIEW_MAX_HEIGHT"];
          create_preview($newImage,$cover,$width,$height);
          }
        }
      loading_screen($location="/?filter=".strtoupper(substr($basename,0,1))."#".md5($basename));
      }
    }
  public function addBasics(){
    $head = $this->xml->xpath("/html/head");
    $icon = $head[0]->addChild("link");
    $icon->addAttribute("rel","icon");
    $icon->addAttribute("type","image/png");
    $icon->addAttribute("href",MIRROR.I."favicon.ico");
    //
    $title       = $this->xml->xpath("/html/head/title");
    $title[0][0] = $this->set->site_title;
    //
    $style       = $this->xml->xpath("/html/head/style");
    $style[0][0] = $this->css;
    //
    $content     = $this->xml->xpath("/html/body/div/table/tbody/tr/td/div");
    $this->content_hook = &$content [0][0];
    //
    $label_display = $this->set->cells[7]->addChild("h2");
    $label_display->addAttribute("id","label_display");
    $this->set->cells[7]->addChild("br");
    }
  public function addNavi(SimpleXMLElement &$e,$path){
    }
  public function addMenu(SimpleXMLElement &$e,$path){

    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".MIRROR);
    $img = $link->addChild("img");
    $img->addAttribute("src",CODEBASE.IMAGES.I."setup-button.png");
    $img->addAttribute("id","setup_switch");

    $form = $this->set->cells[5]->addChild("form");
    $form->addAttribute("onchange","this-submit();");
    $form->addAttribute("method","get");
    $btn= $form->addChild("button","*");
    $btn->addAttribute("name","filter");
    $btn->addAttribute("value","");
    $btn->addAttribute("class","navi");
    $i = 65;
    while($i<91){
      $btn= $form->addChild("button",chr($i));
      $btn->addAttribute("name","filter");
      $btn->addAttribute("value",chr($i));
      $btn->addAttribute("class","navi");
      $i++;
      }
    }
  public function addGlob(SimpleXMLElement &$e,$path_expression,$excludes=array()){
    //
    $router = ["deferXml","addFolder"];
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
  public function addItem(SimpleXMLElement &$e,$path,$target,$class,$id,$label){
    $label = utf8_encode(str_replace(["_","."]," ",$label));
    $basename = basename($path);
    $cover = array_key_exists($basename,$this->set->ui) ? $this->set->ui[$basename]: "";
    $link = $e->addChild("a");
    $link-> addAttribute("href","?0=".urlencode($path));    
    $link->addAttribute("target",$target);
    $link->addAttribute("onmouseover","document.getElementById('label_display').innerHTML=\"$label\"");
    $link->addAttribute("onmouseout","document.getElementById('label_display').innerHTML=\"\"");
    $div = $link->addChild("div");
    $div->addAttribute("class",$class);
    $div->addAttribute("id",$id);
    //
    if(!$cover){
      $div->addChild("br");
      $div->addChild("span",$label);
      $div->addChild("br");
      $div->addChild("br");
      $a = $div->addChild("a");
      $a->addAttribute("href","?1=".urlencode($path)."&".$this->cfg["SEARCH"]."&".$this->cfg["TERM"]."=".$label);
      $img = $a->addChild("img");
      $img->addAttribute("title","Find Cover");
      $img->addAttribute("src",CODEBASE.IMAGES.I."search-button.png");
      $img->addAttribute("style","width:32px;height:32px");
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
      $this->addItem($e,$path,$target="bypass","file",md5($path),substr($path,0,-4));
      }   
    }
  public function addFolder(SimpleXMLElement &$e,$path){
    $this->addItem($e,$path,$target="_self","folder",md5($path),$path);
    }
  public function addImage(SimpleXMLElement &$e,$path){
    $url = CODEBASE."file.php?0=".urlencode($path);
    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",$url);
    $img = $link->addChild("img");
    $img->addAttribute("src",$url);
    }
  public function deferXml(&$e,$fifo,$func){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$fifo,"func"=>$func];
    }
  public function full_print(){
    $this->handle_request($this->dir);
    $this->addBasics($this->dir);
    $this->addMenu($this->xml->xpath("/html/body")[0],$this->dir);
    $this->addGlob($this->content_hook,$this->dir.$this->filter."*",$this->excludes);
    print $this->xml->asXML();
    }
  //
  }