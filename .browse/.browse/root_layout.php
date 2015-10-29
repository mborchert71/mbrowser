<?php namespace render;
const form_open = "<form method=\"post\" action=\"?0={dir}\">";
const form_close= "<hr style=\"width:50%;clear:both\"/><input type=\"submit\"/></form>";

$_SERVER["RESPONSE"]["STACK"] = [];

function handle_request($dir="",$brand=""){
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
function folder_item($path,$return = false){
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
  return $return ? $item : (print $item);
  }
function file_item($path,$return = false){
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
  $_SERVER["RESPONSE"]["STACK"][] = $item;
  return;
  // /plugin
  return $return ? $item : (print $item);
  }
function item(){}
function start($dir,$return = false){
  $html  = $_SERVER["CFG"]["PAGE"];
  $print = substr($html,0,strpos($html,"{content}"));
  return $return ? $print : (print $print);
  }
function end($dir,$return = false){
  $html  = $_SERVER["CFG"]["PAGE"];
  $print = "";
  foreach($_SERVER["RESPONSE"]["STACK"] as $item){
    $print.= $item;
    }
  $print .= substr($html,strpos($html,"{content}")+strlen("{content}"));
  return $return ? $print : (print $print);
  }
/*
?>
*/
$_SERVER["CFG"]["PAGE"] = <<<PAGE
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'/>
<style>
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
  text-align:center;
  background:#8596A5 url('.browse/images/background_root.png') repeat-y;
  }
#content{
  margin-left:auto;
  margin-right:auto;
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
  </style>
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