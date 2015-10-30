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
    $this->set->filter = "";
    $this->set->path_menu = $this->print_path_menu($dir,true);
    $this->set->form_open = "<form method=\"post\" action=\"?0={dir}\">";
    $this->set->form_close= "<hr style=\"width:50%;clear:both\"/><input type=\"submit\"/></form>";
    $this->set->folder    = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
    $this->set->file      = "<a href=\"{url}\" target=\"{target}\"><div class=\"file {class}\">{file}</div></a>";
    $this->set->image     = "<img src=\"{url}\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
    $this->set->menu = preg_match("/images$/",$dir) ? ' <input id="term" autocomplete="off" name="term" type="text" value="'.utf8_encode(str_replace(["/",".browse","images"]," ",$dir)).'"></input>
      <input type="text" name="count" id="count" style="vertical-align:middle;width:24px;height:32px" value=5></input>
      <input type="submit" value=" " 
        onmouseover="document.forms[\'menu\'].term.style.backgroundColor=\'#000000\';"
        onmouseout="document.forms[\'menu\'].term.style.backgroundColor=\'transparent\';"></input>' : "";

    //brand  layout
    foreach(glob(substr($dir,0,strpos($dir,"/"))."/images/fx_*") as $layout_item){
      $basename = basename($layout_item);
      $key = substr($basename,3,strpos($basename, "_",3)-3);//todo:strlen {fx}
      if($key){
        $this->set->$key = urlencode($layout_item);
        }
      }
    //subpage layout
    foreach(glob("$dir/images/fx_*") as $layout_item){
      $basename = basename($layout_item);
      $key = substr($basename,3,strpos($basename, "_",3)-3);//todo:strlen {fx}
      if($key){
        $this->set->$key = urlencode($layout_item);
        }
      }

    if(is_dir($dir) && isset($key)){
      $this->set->setup_url =  "?0=.browse/".urlencode($dir) ;
      $this->set->setup_click = ""; 
      $this->set->setup_image=".browse/images/setup-button.png";
    }else{
      $this->set->setup_image=".browse/images/search-button.png"; 
      $this->set->setup_url =  "?0=".urlencode($dir)."&research";
      $this->set->setup_click =  "var input=prompt('tags to support search result\nthe more popular the topic the less the need for additional tags');this.parentNode.href+='&tags='+input;";  
      }

    $this->renderer = [
    "JPG" => "image_item",
    "PNG" => "image_item",
    "GIF" => "image_item",
    "BMP" => "image_item"
    ];
    $this->stackoprints = [];
    
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
    if(!is_dir($this->dir)){
      if(!mkdir($this->dir)){
        trace_log("folder_item.mkdir ./browse/".$this->dir);
        return;
        }
      }  
    }
  public function handle_request($path){
    if(array_key_exists($_SERVER["CFG"]["REQUEST"]["SEARCH"],$_GET)){
      search_async($path);
      header("location: ?0=.browse/".urlencode($path)."/".$_SERVER["CFG"]["FOLDER"]["IMAGES"]);
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
    $url  = urlencode(substr($path,strlen($_SERVER["ROOT"])));
    $label= utf8_encode(str_replace(["_",".","/"]," ",substr($path,0,-4)));
    $ext = strtolower(substr($path,-3));
    if($ext=="php") return "";
    if(array_key_exists($ext,$this->renderer)){
      $fx = "\\render\\".$this->renderer[$ext];
      $item = $fx($url,$label,true);    
      }
    else{
      $item =  "<a href=\"?0=$url\" target=\"bypass\"><div class=\"file\">$label</div></a>";    
      }
    //plugin FOLDER_FIRST
    $this->stackoprints[] = $item;
    return;
    // /plugin
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_folder($path,$return = false){
    $url  = urlencode(substr($path,strlen($_SERVER["ROOT"])));
    $label= utf8_encode(str_replace(["_","."]," ",basename($path)));
    $CFG  = &$_SERVER["CFG"];
    $I    = DIRECTORY_SEPARATOR;

    $logo = @array_pop(glob(urldecode($url).$I.$CFG["FOLDER"]["IMAGES"].$I.$CFG["FOLDER"]["FX_COVER"]."*"));
    $item = $logo 
          ? "<img src=\"".urlencode($logo)."\" width=\"100%\" height=\"100%\" alt=\"$label\" title=\"$label\"/>"
          : $label;
    $item =  "<a href=\"?0=$url\"><div class=\"folder\">$item</div></a>";
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_image($path,$return = false){
    $url = $path;
    $url= (preg_match("/\.browse\//",$url)) ? $url : "/.browse/file.php?0=../".$url;
    $label = "<img src=\"$url\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
    $item =  "<a href=\"$url\"><div class=\"file image\">$label</div></a>";
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
* {
  margin:0;padding:0;
  color:#ffffff;
  text-shadow:  0px 2px #333333
  }
#background {
  background:#000000 url(".browse/images/background.jpg") center bottom;
  overflow:hidden;
  }
#wallpaper {
  margin:auto;
  position: absolute;
  opacity: 0.7;
  filter: alpha(opacity=70); /* For IE8 and earlier */
  width:100%;
  height:100%
  }
#logo {
  max-width:33%;
  max-height:128px;
  border-bottom-right-radius:24px;
  }
#cast {
  position:absolute;
  bottom:-32px;
  left:0;
  max-height:55%;
  opacity: 0.8;
  filter:alpha(opacity=80);/* For IE8 and earlier */
  border-top:2px solid black;
  border-right:6px solid black;
  border-top-right-radius:24px;
  }
#content {
  width:100%;
  height:100%
  }
#interface {
  position: absolute;
  top:0;
  left:0;
  width:100%;
  height:100%
  }
#interface #layout{
  border-spacing:0;
  border-collapse:collapse;
  empty-cells:show;
  width:100%;
  height:100%
  }
#interface #layout tr { height:0}
#interface #layout #menu-panel {
  height:1px
  }
#content-panel {
  text-align:center;
  position:relative;
  padding-left:48px;
  padding-right:48px;
  vertical-align:top;
  }
#auto{
  height:100%;
  position:absolute;
  overflow:auto;
  }
#setup_switch{
  position:absolute;
  right:0;
  top:0;
  margin:4px;
  width:32px;
  height:32px
  }
#navigation-panel {
  height: 32px;
  padding:8px;
  text-align:center;
  width:100%;
  }
#navigation-panel a {
  text-decoration:none;
  text-shadow: 0px 3px #333333
  margin:8px}
  }
#interface #layout #navigation-panel button {
  width: 128px;
  }
#interface #layout .small{
  width:32px;
  }
.folder{
  width:128px;
  height:128px;
  float:left;
  background:url(".browse/images/folder_background.png") no-repeat top center;
  text-align:center;
  padding-top:32px;
  }
.file{
  position:relative;
  width:192px;
  height:128px;
  float:left;
  background:url(".browse/images/file_background.png") no-repeat top center;
  text-align:center;
  border-radius:12px;
  opacity: 0.6;
  filter:alpha(opacity=60);/*      For IE8 and earlier */
  }
.image{
  opacity: 1;
  filter:alpha(opacity=100);/*      For IE8 and earlier */
  }
STYLE;

$_SERVER["CFG"]["PAGE"] = <<<PAGE
<!DOCTYPE html>
<html id="background"><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8"/>
<title>Media-Browser</title>
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<style>$style</style>
<script></script>
</head>
<body>
  <img id="wallpaper" src="$page->wallpaper" />
  <img id="cast" src="$page->cast"/>
  <div id="interface">
    <table id="layout">
    <tbody>
    <tr >
      <td id="menu-panel"><img id="logo" src="$page->logo"/></td>
      </tr>
    <tr>
      <td id="content-panel"><div id="auto">{content}</div></td>         
      </tr>
    <tr >
      <td id="navigation-panel"><h3>$page->path_menu</h3></td>
      </tr>
    </tbody>
    </table>
    <a href="$page->setup_url"><img id="setup_switch" onclick="$page->setup_click" src="$page->setup_image"/></a>
  </div>
  <iframe id="bypass" name="bypass" style="display:none" src=""></iframe>
</body>
</html>
PAGE;
?>