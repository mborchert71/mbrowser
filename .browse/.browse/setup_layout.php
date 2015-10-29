<?php namespace render;

$mirrorpath= substr($dir,strlen(".browse/")); //$dir comes from includer!
if($mirrorpath && !is_dir($mirrorpath)){
  if(!mkdir($mirrorpath)){
    trace_log("folder_item.mkdir ./browse/$mirrorpath");
    return;
    }
  }

$_SERVER["RESPONSE"]["STACK"] = [];

$_SERVER["CFG"]["RENDERER"] = [ //todo:cfg
"jpg" => "image_item",
"jpeg"=> "image_item",
"png"=> "image_item",
"gif"=> "image_item",
"bmp"=> "image_item"
];
//
$default = new \stdClass;
const form_open = "<form method=\"post\" action=\"?0={dir}\">";
const form_close= "<hr style=\"width:50%;clear:both\"/><input type=\"submit\"/></form>";
$default->folder    = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
$default->file      = "<div class=\"file {class}\"><a href=\"{url}\" target=\"{target}\">{file}</a>{input}</div>";
$default->image     = "<img src=\"{url}\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
$default->wallpaper = ".browse/images/fx_wallpaper.png";
$default->cast      = ".browse/images/fx_cast.jpg";
$default->logo      = ".browse/images/fx_logo.png";
$default->path      = "./";
$default->setup_url = "";
$default->setup_click="";
$default->setup_image=".browse/images/setup-button.png";
$default->search_url= "?".str_replace("&research","",$_SERVER["QUERY_STRING"])."&research";
//
function handle_request($dir){

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
//
$page = new \stdClass();
$page->form_target="_self";
$page->path = $dir; //$dir kommt vom includer !!!
$page->path_menu = path_menu($page->path);
$page->menu = preg_match("/images$/",$dir) ? ' <input id="term" autocomplete="off" name="term" type="text" value="'.utf8_encode(str_replace(["/",".browse","images"]," ",$dir)).'"></input>
  <input type="text" name="count" id="count" style="vertical-align:middle;width:24px;height:32px" value=5></input>
  <input type="submit" value=" " 
    onmouseover="document.forms[\'menu\'].term.style.backgroundColor=\'#000000\';"
    onmouseout="document.forms[\'menu\'].term.style.backgroundColor=\'transparent\';"></input>' : "";
$page->switch_url =  "?0=".urlencode(str_replace([".browse/","/images",".browse"],"",$dir));
$page->url =  "?0=".urlencode($dir) ;
//
foreach(glob(".browse/images/fx_*") as $layout_item){
  $basename = basename($layout_item);
  $key = substr($basename,3,strpos($basename, "_",3)-3);//todo:getridoff fx_ cause images get saved in their srcfilename so id is <fetchtype>_ ... conflict is ok...
  if($key){
    $page->$key = urlencode($layout_item);
    }
  }
//
//set default
if(!is_object($page)){
  $page = &$default;
  trace_log(basename(__FILE__)." no defined pagelayout \$page");
  }
else{
  foreach($default as $key => $val){
    if(!property_exists($page,$key)){
      $page->$key = $val;
      }
  }
  if(preg_match("/".preg_quote($_SERVER["CFG"]["REQUEST"]["SEARCH"])."/",$page->setup_url)){
    $page->setup_image=".browse/images/search-button.png"; 
    }
  }
function start($dir,$return = false){
  $html  = $_SERVER["CFG"]["PAGE"];
  $print = substr($html,0,strpos($html,"{content}"));
  if(preg_match("/images$/",$dir))$print.= str_replace("{dir}",urlencode($dir),form_open);
  return $return ? $print : (print $print);
  }
function end($dir,$return = false){
  $html  = $_SERVER["CFG"]["PAGE"];
  $print = "";
  foreach($_SERVER["RESPONSE"]["STACK"] as $item){
    $print.= $item;
    }
  if(preg_match("/images$/",$dir))$print .= form_close;
  $print .= substr($html,strpos($html,"{content}")+strlen("{content}"));
  return $return ? $print : (print $print);
  }
function item($innerHtml,$return=false){
  return $return ? $innerHtml : (print $innerHtml);
  }
//
function folder_item($path,$return=false){
  $url  = urlencode(substr($path,strlen($_SERVER["ROOT"].".browse/")));
  $label= utf8_encode(str_replace(["_","."]," ",basename($path)));
  $item =  "<a href=\"?0=.browse/$url\"><div class=\"folder\">$label</div></a>";
  return $return ? $item : (print $item);
  }
function file_item($path,$return=false){ 
  $url = urlencode(substr($path,strlen($_SERVER["ROOT"].".browse/")));
  $label= utf8_encode(str_replace(["_","."]," ",basename($path)));
  $label = substr($label,0,strlen($label)-4);
  $ext = strtolower(substr($url,-3));
  if(array_key_exists($ext,$_SERVER["CFG"]["RENDERER"])){
    $fx = "\\render\\".$_SERVER["CFG"]["RENDERER"][$ext];
    $item = $fx($url,$label,true);    
    }
  elseif($ext!="php"){ 
    $item =  "<a href=\".browse/file.php?0=$url\" target=\"_self\"><div class=\"file\">$label</div></a>";    
    }
  else{
    $item  ="";
    }

  return $return ? $item : (print $item);
  }
function image_item($url,$label,$return=false){
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

  return $return ? $item : (print $item);
  }
function path_menu($dir=""){
  $html="<a href=\"/\"><b>&nbsp;&lArr;&nbsp;</b></a>";
  $c = "";
  foreach(explode("/",$dir) as $f){
    $html .= "<a href=\"?0=".urlencode($c.$f)."\">&nbsp;".utf8_encode($f)."&nbsp;</a>";
    $c .= $f."/";
    }
  return $html;
  }

/*
?>
*/
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
<style>
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
</style>
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
      <form name="menu" method="post" action="$page->url" target="$page->form_target" accept-charset="utf-8"><div>
        $page->menu
        <a href="$page->switch_url"><img src=".browse/images/menu-button.png" style="margin:8px;width:32px;height:32px"></img></a>
      </div></form>
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