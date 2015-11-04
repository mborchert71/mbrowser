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
function loading_screen($location=null){
  if(!$location){
    ob_start();
    echo "<html><body style=\"text-align:center\">";
    echo "<img src=\"data:image/gif;base64,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\"/>";
    echo str_repeat(' ',1024*64);
    echo "</body></html>";
    flush();
    ob_flush();
    ob_end_clean();
  }else{
    echo "<script>document.location.href=\"$location\";</script>";
    exit;
    }
  }
function handle_request($path){
  if(array_key_exists($_SERVER["CFG"]["SEARCH"],$_GET)){
    loading_screen($location=null);
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
      copy(".browse/images/dummy_cast.jpg",$imgdir."/dummy_cast.jpg");
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
    loading_screen($location="/#".md5($term));
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