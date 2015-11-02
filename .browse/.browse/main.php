<?php
function trace_log($msg){
  error_log(@date("Y-m-d_H-i-s")."\t"
  .basename(__FILE__)."\t"
  .$msg."\n",3,
  sys_get_temp_dir()."/{$_SERVER['SERVER_NAME']}_{$_SERVER['SERVER_PORT']}.log");
}
function scan_layout(){
  include_once($_SERVER["UTIL"]["SEARCH_FILE"]);
  foreach(glob($_SERVER["ROOT"]."*") as $fifo){
    $path = basename($fifo);
    if(is_file($fifo)){
      $name = substr($path,0,strlen($path)-4);
      $cover= @array_pop( glob($_SERVER["CFG"]["FILE"]["FX_PATH"].I.FX.$name."*") );
      if(!count($cover) && 
         !search_engine_single($this->cfg["UTIL"]["SEARCH_SERVER"],
                              utf8_encode($name),$_SERVER["CFG"]["FILE"]["TAGS"])){
        trace_log("root_page.search_engine_single $name");
        }
      }
    elseif(is_dir($fifo) && !is_dir($path)){
      search_async($path);
      }
    }  
  }
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
@list($r,$g,$b) = explode(" ",@$_SERVER["CFG"]["SETUP"]["PREVIEW_BG_COLOR"]);
if(is_null($b)){ 
  trace_log("create_preview bgColor not in global.ini");
  $r = 175 ; $g=175;$b = 175;
  }
$image_p = imagecreatetruecolor($width, $height);
imagefill($image_p,0,0,imagecolorallocate($image_p, $r, $g, $b));

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
function handle_request($path){
  if(array_key_exists($_SERVER["CFG"]["SEARCH"],$_GET)){
    $term = $_GET[$_SERVER["CFG"]["TERM"]];
    //
    if(is_file("../$path")){
      $imgdir = $_SERVER["CFG"]["SETUP"]["IMAGES"];
      $tags   = $_SERVER["CFG"]["ROOT"]["TAGS_FILE"];
      }
    elseif($path && !is_dir($path)){
      if(!mkdir($path)){
        trace_log("search_engine_start.mkdir $path");
        return false;
        }
      else{
        $imgdir = $path.I.$_SERVER["CFG"]["SETUP"]["IMAGES"];
        $tags   = $_SERVER["CFG"]["ROOT"]["TAGS_FOLDER"];
        }
      }
    else{
      $imgdir = $path.I.$_SERVER["CFG"]["SETUP"]["IMAGES"];
      $tags   = $_SERVER["CFG"]["ROOT"]["TAGS_FOLDER"];
      }
    include_once($_SERVER["CFG"]["UTIL"]["SEARCH_FILE"]);
    if(!is_dir($imgdir)){
      if(!mkdir($imgdir)){
        trace_log("search_engine_start.mkdir $imgdir");
        return false;
        }
      }
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
      }else{
        @file_put_contents(
        dirname($imgdir).I.$_SERVER["CFG"]["SETUP"]["FETCH_LOG"],"$term.$ext\t{$img}\n",FILE_APPEND );
        $preview =$imgdir.I.FX.COVER."_".$term.".".$ext;
        $width   =$_SERVER["CFG"]["SETUP"]["PREVIEW_MAX_WIDTH"];
        $height  =$_SERVER["CFG"]["SETUP"]["PREVIEW_MAX_HEIGHT"];
        create_preview($newImage,$preview,$width,$height);
        }
      }
    header("location: ./#".md5($term));
    }
  }
function route_file($file,$return=false){
  if(!preg_match("/^".preg_quote(MIRROR)."/",$file)){
    $dir = preg_match("/\//",$file) ? substr($file,0,strpos($file,"/")) : ".";
    file_put_contents($dir.I.$_SERVER["CFG"]["FILE"]["WATCH_LOG"],$file);
    $url = urlencode($file);
    }
  if(preg_match("/".$_SERVER["CFG"]["FILE"]["LAUNCH"]."/",$file)){
    exec('"'.realpath(".browse".I."system".I.$_SERVER["OS"].I."launch.".$_SERVER["SHELL"]).'" "'.$_SERVER["ROOT"].$file.'"');
    }
  else{
    header("location: .browse/file.php?0=".urlencode($fifo));
    }
  }
function route_folder($dir,$return=false){
  include($_SERVER["CFG"][["FOLDER","SETUP","ROOT"][ !$dir ? 2 : intval(preg_match("/^".preg_quote(MIRROR)."/",$dir))]]["RENDERER"]);
  (new page($dir))->full_print();
  }
function run(){
  include(".browse/head.php");
  $r=($_SERVER["ROOT"]    ="../");
  $p=($_REQUEST["PATH"]   =[(array_key_exists("0",$_GET) ? $_GET[0] : "")]);
  handle_request($p[0]);
  $q=($_SERVER["RESPONSE"]= is_dir($r.$p[0]) ?route_folder($p[0]) :route_file($p[0]));
  clearstatcache();
  }