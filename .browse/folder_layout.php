<?php
$render_function=[
"jpg" => "image_item",
"jpeg"=> "image_item",
"png"=> "image_item",
"gif"=> "image_item",
"mp4"=> "file_item",
"mkv"=> "file_item",
"avi"=> "file_item",
"flv"=> "file_item",
"swf"=> "file_item",
"default"  => "file_item"
];
//
$default = new stdClass;
$default->folder    = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
$default->file      = "<a href=\"{url}\" target=\"{target}\"><div class=\"file {class}\">{file}</div></a>";
$default->image     = "<img src=\"{url}\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
$default->wallpaper = ".browse/images/fx_wallpaper.png";
$default->cast      = ".browse/images/fx_cast.jpg";
$default->logo      = ".browse/images/fx_logo.png";
$default->path      = "./";
$default->setup_url = "";
$default->setup_click="";
$default->setup_image=".browse/images/setup-button.png";
//set default
if(!is_object($page)){
  $page = &$default;
  trace_log(basename(__FILE)." no defined pagelayout \$page");
  }
else{
  foreach($default as $key => $val){
    if(!property_exists($page,$key)){
      $page->$key = $val;
      }
  }
  if(preg_match("/research/",$page->setup_url)){
    $page->setup_image=".browse/images/search-button.png"; 
    }
}
//
function folder_item($url,$label){
  return "<a href=\"?0=$url\"><div class=\"folder\">$label</div></a>";
  }
function file_item($url,$label){
  return "<a href=\"?0=$url\" target=\"bypass\"><div class=\"file\">$label</div></a>";
  }
function browser_item($url,$label){
  $url= (preg_match("/\.browse\//",$url)) ? $url : "/.browse/file.php?0=../".$url;
  return "<a href=\"$url\"><div class=\"file\">$label</div></a>";
  }
function image_item($url,$label){
  $url= (preg_match("/\.browse\//",$url)) ? $url : "/.browse/file.php?0=../".$url;
  $label = "<img src=\"$url\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
  return "<a href=\"$url\"><div class=\"file image\">$label</div></a>";
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
//
$page->path_menu = path_menu($page->path);
/*
?>
*/
$html= <<<PAGE
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
</style>
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