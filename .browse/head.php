<?php

//config
$_SERVER["FLAG"]= array();
$_SERVER["CFG"] = parse_ini_file(".browse/global.ini",true);
if(preg_match("/Windows NT/si",$_SERVER["HTTP_USER_AGENT"])){
  $_SERVER["OS"] = "windows-nt";
  $_SERVER["SHELL"] = "bat";
  }
else{}

function trace_log($msg){
  error_log(@date("Y-m-d_H-i-s")."\t"
  .basename(__FILE__)."\t"
  .$msg."\n",3,
  sys_get_temp_dir()."/{$_SERVER['SERVER_NAME']}_{$_SERVER['SERVER_PORT']}.log");
}

//utils
function create_preview($src,$tgt,$maxwidth,$maxheight){

// Set a maximum height and width
$width = $maxwidth;
$height = $maxheight;

$ext = strtolower(substr($src,strlen($src)-3));

list($width_orig, $height_orig) = getimagesize($src);

$ratio_orig = $width_orig/$height_orig;

if ($width/$height > $ratio_orig) {
   $width = $height*$ratio_orig;
} else {
   $height = $width/$ratio_orig;
}

$image_p = imagecreatetruecolor($width, $height);
imagefill($image_p,0,0,imagecolorallocate($image_p, 175, 175, 175));

try{
  switch($ext){
    case "jpg" :
      $image = imagecreatefromjpeg($src);
      imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
      imagejpeg($image_p,$tgt,70);
      break;
    case "gif" :
      $image = imagecreatefromgif($src);

      imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
      imagegif($image_p, $tgt, 100);
      break;
    case "png" :
      $image = imagecreatefrompng($src);
      imagealphablending($image, false);
      imagesavealpha($image, true);
      imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
      imagealphablending($image_p, false);
      imagesavealpha($image_p, true);
      imagepng($image_p, $tgt, 3);
      break;
    default :
      $image = imagecreatefromjpeg($src);
      imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
      imagejpeg($image_p, substr($tgt,0,strlen($tgt)-3)."jpg",70);
    }
}catch(exception $e){
  trace_log($e->getMessage());
}
  imagedestroy($image_p);
}
function search_async($path){
  include_once(".browse/search.php");
  $param=search_engine_start($path);
  //$searchfile = sys_get_temp_dir()."/{$_SERVER['SERVER_NAME']}_{$_SERVER['SERVER_PORT']}_search.prm";
  //file_put_contents($searchfile,serialize($param));
  //$script = realpath("./.browse/system/{$_SERVER["OS"]}/cli-search.{$_SERVER["SHELL"]}");
  //exec('"'.$script.'" "'.$searchfile.'"');
  list($server,$term,$fetch,$tags,$count)=$param;
  search_engine_fetch($server,$term,$fetch,$tags,$count);  
  }
//FILES
function search_single($name){
  include_once(".browse/search.php");
  search_engine_single("yahoo",utf8_encode($name),"poster"); 
  }
  

function file_request_handle($fifo){
  $name = basename(substr($fifo,0,strlen($fifo)-4));//files to root restriction
  if(array_key_exists("research",$_REQUEST)){
    $logo = search_single($name);
    return false;
    }
  return true;
  }
function file_render_handle($fifo,$return=false){
  header("location:./.browse/file.php?0=".urlencode($fifo));
  }
function file_finish_handle($fifo){  
  $name = basename(substr($fifo,0,strlen($fifo)-4));//files to root restriction
  if(!@array_pop(glob($_F["FX_PATH"].DIRECTORY_SEPARATOR.$_F["FX"].$name.".*")) && !strpos($fifo,"/")){
    if(!search_single($name)){
      trace_log("search_single ".utf8_encode($name));
      }
    }
  }

?>