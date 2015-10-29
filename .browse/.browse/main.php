<?php
//
function route_file($file,$return=false){
  //
  if(!file_request_handle($file)){ return false; }
  if(!preg_match("/^\.browse/",$file)){
    $dir = preg_match("/\//",$file) ? substr($file,0,strpos($file,"/")) : ".";
    file_put_contents($dir.DIRECTORY_SEPARATOR.$_SERVER["CFG"]["FILE"]["WATCH_LOG"],$file);
    $url = urlencode($file);
    }
  if(preg_match("/".$_SERVER["CFG"]["FILE"]["LAUNCH"]."/",$file)){
    exec('"'.realpath("./.browse/system/{$_SERVER["OS"]}/launch.{$_SERVER["SHELL"]}").'" "'.$_SERVER["ROOT"].$file.'"');
    }
  else{
    file_render_handle($file,$return);
    }
  //
  file_finish_handle($file);
  }
//
function render_folder($dir,$return=false){
  $CONTENT = ["FOLDER","SETUP","ROOT"][ !$dir ? 2 : intval(preg_match("/^\.browse/",$dir))];
  $RENDER  = ["\\render\\file_item","\\render\\folder_item"];
  $FILTER  = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
  $EXCLUDE = explode(";",$_SERVER["CFG"][$CONTENT]["EXCLUDE"]);
  //
  include($_SERVER["CFG"][$CONTENT]["RENDERER"]);
  $print  = render\handle_request($dir,$return);
  $print .= render\start($dir,$return) ;
  //
  foreach(glob($_SERVER["ROOT"].$dir."/".$FILTER."*") as $fifo){
    if(!in_array(utf8_encode(basename($fifo)),$EXCLUDE)){
      $print .= $RENDER[intval(is_dir($fifo))]($fifo,$return);
      }
    }
  //
  $print .= render\end($dir,$return);
  return $return ? $print : true;
  }
//
function run(){
  include(".browse/head.php");
  $r=($_SERVER["ROOT"]    ="../");
  $p=($_REQUEST["PATH"]   =[(array_key_exists("0",$_GET) ? $_GET[0] : "")]);
  $q=($_SERVER["RESPONSE"]= is_dir($r.$p[0]) ?render_folder($p[0]) :route_file($p[0]));
  include(".browse/feet.php");
  }