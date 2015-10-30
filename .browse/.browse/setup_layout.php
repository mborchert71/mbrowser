<?php namespace render;

class folder {
  public $dir;
  public $path;
  public $docroot_path;
  public $url;
  public $urlq;
  public $label;
  public $set;
  public $renderer= [];
  public $stackoprints=[];
  public function print_page(){
    $router = ["print_file","print_folder"];
    $this->print_start() ;
    //
    foreach(glob($this->path."/".$this->filter."*") as $fifo){
      if(!in_array($fifo,$this->excludes)){
        $this->$router[intval(is_dir($fifo))]($fifo);
        }
      }
    //
    $this->print_end();
    }
  public function __construct($dir,$set=null){
    $this->dir    = $dir;
    $this->path   = substr($dir,strlen(".browse/"));
    $this->docroot_path = $_SERVER["ROOT"].$dir;
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    
    $this->set=$set;    
    $this->set = new \stdClass;
    $this->set->wallpaper = ".browse/images/fx_wallpaper.png";
    $this->set->cast      = ".browse/images/fx_cast.jpg";
    $this->set->logo      = ".browse/images/fx_logo.png";
    $this->set->setup_image=".browse/images/setup-button.png";
    $this->set->search_url= "?".str_replace("&research","",$_SERVER["QUERY_STRING"])."&research";
    $this->set->switch_url =  "?0=".urlencode(str_replace([".browse/","/images",".browse"],"",$dir));
    $this->set->form_target="_self";
    $this->set->filter = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    $this->set->excludes = explode(";",$_SERVER["CFG"]["SETUP"]["EXCLUDE"]);
    $this->set->path_menu = $this->print_path_menu($dir,true);
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
    if($this->path && !is_dir($this->path)){
      if(!mkdir($this->path)){
        trace_log("folder_item.mkdir ./browse/".$this->path);
        return;
        }
      }    
    }
  public function handle_request($path){
    $dir = $path;
    $imgdir = $_SERVER["CFG"]["FOLDER"]["IMAGES"];
    if(array_key_exists($_SERVER["CFG"]["REQUEST"]["SEARCH"],$_GET)){
      search_async(str_replace([".browse/","/".$imgdir],"",$dir));
      $url = "?0=.browse/".urlencode(preg_replace(["/^.browse\//","/".preg_quote($imgdir)."$/"],"",$dir)).$_SERVER["CFG"]["FOLDER"]["IMAGES"];
      header("location: ./$url");
      return false;
      }
    //
    $dir = preg_replace("/^\.browse\//","",$dir);
    if(preg_match("/\/images$/",$dir)){
      $keys = ["cast","wallpaper","logo"];//todo:cfg auto_search_layout_keys
      if(array_key_exists("customtype",$_POST)){
        foreach($_POST["customtype"] as $idx => $ctype){
          if(in_array($ctype,$keys)){
            foreach(glob("$dir/fx_{$ctype}_*") as $oldfx){
              unlink($oldfx);
              }
            copy($dir."/".$_POST["customfile"][$idx],$dir."/fx_".$ctype."_".$_POST["customfile"][$idx]);
            if($ctype=="logo"){ 
              foreach(glob($dir."/fx_cover*") as $oldcover){
                unlink($oldcover);
                }
              create_preview("$dir/".$_POST["customfile"][$idx],"$dir/fx_cover_".$_POST["customfile"][$idx],256,256);
              }
            }
          }
        }
      if(array_key_exists("delete",$_POST)){
        foreach($_POST["delete"] as $idx => $name){
          if(is_file($dir."/".$name)){
            unlink($dir."/".$name);
            }
          }
        }
      if(array_key_exists("term",$_POST)){
        include_once(".browse/search.php");
        search_engine_find($dir,"yahoo",$_POST["term"],$_POST["count"]);
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
    $url = urlencode(substr($path,strlen($_SERVER["ROOT"].".browse/")));
    $label= utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4)));
    $ext = strtoupper(substr($path,-3));
    if(array_key_exists($ext,$this->renderer)){
      $fx = "\\render\\".$this->renderer[$ext];
      $item = $fx($url,$label,true);    
      }
    elseif($ext!="php"){ 
      $item =  "<a href=\".browse/file.php?0=$url\" target=\"_self\"><div class=\"file\">$label</div></a>";    
      }
    else{
      $item  ="";
      }

    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_folder($path,$return = false){
    $url  = urlencode(substr($path,strlen($_SERVER["ROOT"])));
    $label= utf8_encode(str_replace(["_","."]," ",basename($path)));
    $item =  "<a href=\"?0=$url\"><div class=\"folder\">$label</div></a>";
    return $return ? $item : ((print $item) ? "" : "error:$path\n");
    }
  public function print_image($path,$return = false){
    $url = $path;
    $input = "<div  style=\"position:absolute;margin-top:-24px;width:100%;z-index:1;padding-top:-32px;background:grey\">&nbsp;</div>";
    $label = "<img src=\"".$url."\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
    $filename = basename(urldecode($url));
    if(preg_match("/images$/",dirname(urldecode($url)))){
      $input = "<div  style=\"position:absolute;margin-top:-24px;width:100%;z-index:1;padding-top:-32px;background:grey\">
      <div title=\"delete\" style=\"float:right;height:24px;width:32px;position:relative;text-align:center\">
      <img src = '.browse/images/delete-button.png' width=\"24px\" height=\"100%\" style=\"float:left\"/>
      <input type=\"checkbox\" name=\"delete[]\" value=\"".$filename."\" style=\"position:absolute;left:16px;width:24px;height:24px;background:transparent;border:0\"/>
      </div>";
      if(preg_match("/^fx_(cast|wallpaper|logo)/",$filename,$m)){//todo:CFG
        $type    = $m[1] ;
        if($type)
          $input .= $type;
      }elseif(!preg_match("/^fx_cover/",$filename,$m)){
          $input .= "<input type=\"hidden\" name=\"customfile[]\" value=\"".$filename."\"/><select name=\"customtype[]\"><option></option><option>logo</option><option>cast</option><option>wallpaper</option></select>";              
        }else{
          $input .= "";
        }
        $input.="</div>";
      }
    $item =  "<div class=\"file image\"><a href=\"$url\" target=\"_self\">$label</a>$input</div>";

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
#layout{
  border-spacing:0;
  border-collapse:collapse;
  empty-cells:show;
  width:100%;
  height:100%
  }
#layout tr { height:0}
.menu-panel {
  height:1px;
  text-align:right;
  vertical-align:top;
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
#mirror_switch{
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
#navigation-panel button {
  width: 128px;
  }
#layout .small{
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
  background:transparent;
  filter:alpha(opacity=100);/*      For IE8 and earlier */
  }
#term{
  background:transparent;
  border:0px;
  width:50%;
  font-size:1em;
  height:32px;
  border-radius:8px;
  }
.menu-panel input[type="submit"]{
  background:url(.browse/images/search-button32.png) no-repeat center center transparent;
  border:0;
  width:32px;
  height:32px;  
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
    <tr>
    <td class="menu-panel"><img id="logo" src=".browse/images/fx_logo.png"/>
    </td>
    <td class="menu-panel" colspan="2">
    <form name="menu" method="post" action="$page->urlq" target="$page->form_target" accept-charset="utf-8">
    <div>
      $page->menu
      <a href="$page->switch_url"><img id="mirror_switch" src=".browse/images/menu-button.png"></img></a>
    </div>
    </form>
    </td>
    </tr>
    <tr>
    <td id="content-panel" colspan="3"><div id="auto">{content}</div></td>         
    </tr>
    <tr >
    <td id="navigation-panel" colspan="3"><h3>$page->path_menu</h3></td>
    </tr>
    </tbody>
    </table>
  </div>
  <iframe id="bypass" name="bypass" style="display:none" src=""></iframe>
</body>
</html>
PAGE;
?>