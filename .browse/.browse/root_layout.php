<?php namespace render;

class folder {
  public $dir;
  public $path;
  public $url;
  public $urlq;
  public $label;
  public $set;
  public $renderer= [];
  public $stackoprints=[];
  public function print_page(){
    foreach(glob($this->path."/".$this->filter."*") as $fifo){
      $router = ["print_file","print_folder"];
      if(!in_array($fifo,$this->excludes)){
        $this->$router[is_dir($fifo)]($fifo);
        }
      }
    }
  public function __construct($dir,$set=null){
    $this->dir    = $dir;
    $this->path   = substr($dir,strlen(".browse/"));
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    $this->set=$set;    
    $this->set = new \stdClass;
    $this->set->wallpaper = ".browse/images/fx_wallpaper.png";
    $this->set->cast      = ".browse/images/fx_cast.jpg";
    $this->set->logo      = ".browse/images/fx_logo.png";
    $this->set->setup_image=".browse/images/setup-button.png";

    $this->set->search_url= "?".str_replace("&research","",@$_SERVER["QUERY_STRING"])."&research";
    $this->set->switch_url =  "?0=".urlencode(str_replace([".browse/","/images",".browse"],"",$dir));

    $this->set->form_target="_self";

    $this->set->form_open = "<form method=\"post\" action=\"?0={dir}\">";
    $this->set->form_close= "<hr style=\"width:50%;clear:both\"/><input type=\"submit\"/></form>";
    $this->set->folder    = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
    $this->set->file      = "<div class=\"file {class}\"><a href=\"{url}\" target=\"{target}\">{file}</a>{input}</div>";
    $this->set->image     = "<img src=\"{url}\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
    $this->set->menu = preg_match("/images$/",$dir) ? ' <input id="term" autocomplete="off" name="term" type="text" value="'.utf8_encode(str_replace(["/",".browse","images"]," ",$dir)).'"></input>
      <input type="text" name="count" id="count" style="vertical-align:middle;width:24px;height:32px" value=5></input>
      <input type="submit" value=" " 
        onmouseover="document.forms[\'menu\'].term.style.backgroundColor=\'#000000\';"
        onmouseout="document.forms[\'menu\'].term.style.backgroundColor=\'transparent\';"></input>' : "";
    $this->set->filter = "";
    foreach(glob(".browse/images/fx_*") as $layout_item){
      $basename = basename($layout_item);
      $key = substr($basename,3,strpos($basename, "_",3)-3);
      if($key){
        $this->set->$key = urlencode($layout_item);
        }
      }
    $this->renderer = [
    "JPG" => "image_item",
    "PNG" => "image_item",
    "GIF" => "image_item",
    "BMP" => "image_item"
    ];
    
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
    if($this->path && !is_dir($this->path)){
      if(!mkdir($this->path)){
        trace_log("folder_item.mkdir ./browse/".$this->path);
        return;
        }
      }    
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
  public function print_start(){
    $dir = $this->dir;$return=false;
    $html  = $_SERVER["CFG"]["PAGE"];
    $print = substr($html,0,strpos($html,"{content}"));
    if(preg_match("/images$/",$dir))$print.= str_replace("{dir}",urlencode($dir),$this->form_open);
    return $return ? $print : ((print $print) ? "" : "error:$path\n");
    }
  public function print_end(){
    $dir = $this->dir;$return = false;
    $html  = $_SERVER["CFG"]["PAGE"];
    $print = "";
    foreach($this->stackoprints as $item){
      $print.= $item;
      }
    if(preg_match("/images$/",$dir))$print .= $this->form_close;
    $print .= substr($html,strpos($html,"{content}")+strlen("{content}"));
    return $return ? $print : ((print $print) ? "" : "error:$path\n");
    }
  public function print_path_menu($path,$return=false){
    $item="<a href=\"/\"><b>&nbsp;&lArr;&nbsp;</b></a>";
    $c = "";
    foreach(explode("/",$path) as $f){
      $item .= "<a href=\"?0=".urlencode($c.$f)."\">&nbsp;".utf8_encode($f)."&nbsp;</a>";
      $c .= $f."/";
      }
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_file($path,$return = false){
    $I    = DIRECTORY_SEPARATOR;
    $path = substr($path,strlen($_SERVER["ROOT"])+1);
    $label= utf8_encode(str_replace ([".","_","/"]," ",substr($path,0,strlen($path)-4)));
    $url  = urlencode($path);
    $name = basename(substr($path,0,strlen($path)-4));
    $logo = urlencode(@array_pop( glob($_SERVER["CFG"]["FILE"]["FX_PATH"].$I.$_SERVER["CFG"]["FILE"]["FX"]."$name.*") ));
    if($logo){  
      $item = "<div class=\"file\"><a target=\"bypass\" href=\"?0=$url\"><img src=\"$logo\" width=\"75%\" height=\"100%\" alt=\"$label\" title=\"$label\"/></a></div>";
      }
    else{
      $item = "<div class=\"file\"><a target=\"bypass\" href=\"?0=$url\"><br/><br/>$label</a><br/><a href=\"?0=$url&research\" target=\"bypass\"><img title=\"Find Cover\" src=\".browse/images/search-button.png\" width=\"32px\" height=\"32px\"/></a></div>";
      }
    //plugin FOLDER_FIRST  $item = render\plugins(render\file_item_plugin,$path);
    $this->stackoprints[] = $item;
    return;
    // /plugin
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_folder($path,$return = false){
    $path = substr($path,strlen($_SERVER["ROOT"])+1);
    $label= utf8_encode(str_replace ([".","_","/"]," ",$path));

    $CFG  = &$_SERVER["CFG"];
    $I    = DIRECTORY_SEPARATOR;
    $url  = urlencode($path);
    $logo = urlencode(@array_pop(glob($path.$I.$CFG["FOLDER"]["IMAGES"].$I.$CFG["FOLDER"]["FX_COVER"]."*")));
    $item = $logo 
          ? "<img src=\"$logo\" width=\"100%\" height=\"100%\" alt=\"$label\" title=\"$label\"/>"
          : "<p><br/><br/>$label</p>";
    $item = "<div class=\"folder\"><a target=\"_self\" href=\"?0=$url\">$item</a></div>";  
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_image($path,$return = false){
    $item = "";
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  }

$page = new folder($dir);
//$page->print_page();
//-------------------------------------------------------
$GLOBALS["PAGE"] = &$page;

function handle_request($dir){
  return $GLOBALS["PAGE"]->handle_request($dir);
  }
function start(){
  return $GLOBALS["PAGE"]->print_start();
  }
function end(){
  return $GLOBALS["PAGE"]->print_end();
  }
function file_item($dir){
   return $GLOBALS["PAGE"]->print_file($dir); 
  }
function folder_item($dir){
   return $GLOBALS["PAGE"]->print_folder($dir); 
  }
function image_item($dir){
   return $GLOBALS["PAGE"]->print_image($dir); 
  }
//-------------------------------------------------------
$style = <<<STYLE
*{
  margin:0;
  padding:0;
  color:white;
  text-shadow: 2 2 0.5
  }
html{
  width:100%;
  height:100%
  }
body{
  width:100%;
  height:100%;
  background:#8596A5 url('.browse/images/background_root.png') repeat-y;
  }
#content{
  margin-left:1%;
  width:99%;
  }
#setup_switch{
  z-index:1;
  position:fixed;
  right:0;
  top:0;
  margin:4px;
  width:32px;
  height:32px
  }
.folder, .file{
  background:#556677;
  position:relative;
  float:left;
  text-align:center;
  width:18.1%;
  height:176px;
  margin:1px;
  border-radius:8px;
  border-top:2px solid silver;
  border-left:1px solid silver;
  border-right:5px solid grey;
  border-bottom:6px solid black;
  padding:4px;
  }
.file{
  background:url(".browse/images/file_background.png");
  }
STYLE;
$_SERVER["CFG"]["PAGE"] = <<<PAGE
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'/>
<style>$style</style>
</head>
<body><iframe id="bypass" name="bypass" style="display:none" src="" onload=""></iframe>
<div id="content">{content}</div>
<div id="foreground_top" style="z-index:1;background:url('.browse/images/foreground_top.png') repeat-x;width:100%;height:53px;position:fixed;top:0;left:0"></div>
<div id="foreground_toe" style="z-index:1;background:url('.browse/images/foreground_toe.png') repeat-x;width:100%;height:53px;position:fixed;bottom:0;left:0"></div>
<a href="?0=.browse"><img id="setup_switch" src=".browse/images/setup-button.png"/></a>
</body>
</html>
PAGE;
?>