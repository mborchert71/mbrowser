<?php

$_SERVER["RTCFG"] = parse_ini_file(".browse/global.ini",true);

if(preg_match("/Windows NT/si",$_SERVER["HTTP_USER_AGENT"])){
  $_SERVER["OS"] = "windows-nt";
  $_SERVER["SHELL"] = "bat";}
else{}

function trace_log($msg){
  error_log(@date("Y-m-d_H-i-s")."\t$msg\n",3,trim(`echo %TMP%`)."/{$_SERVER['SERVER_NAME']}_{$_SERVER['SERVER_PORT']}.log");//todo:OS-Switch
}
function main(){
  $root = "../";
  $fifo = array_key_exists("0",$_GET) ? preg_replace("/\.\./","",$_GET[0]) : $root;  
  $rooter = $fifo == $root ? "rootpage" : ( is_dir($root.$fifo) ? "foldrooter" : "filerooter" );  
  print $rooter($root,$fifo);
}

function filerooter($root,$fifo){
  
  $name = substr(basename($fifo),0,strlen(basename($fifo))-4);
  
  if(array_key_exists("research",$_REQUEST)){    
    $logo = search_engine_single("yahoo",$name,"poster");
    header("location:/");
  }else{
    $file = $root.$fifo;
  exec('"'.realpath("./.browse/system/{$_SERVER["OS"]}/launch.{$_SERVER["SHELL"]}").'" "'.$file.'"');
    if(!@array_pop(glob(".file/fx_$name*")) && !preg_match("/\//",$fifo)){ //feature @thismoment unfoldered files only
      if(!search_engine_single("yahoo",$name,"poster")){
        trace_log("search_engine_single $name");
        }
      }
    }
}
function rootpage($root){
    
    $allfiles = glob($root."*");
    
    if(array_key_exists("research",$_REQUEST)){
      $checkfirstrun = false;
      foreach($allfiles as $fifo){        
        if(is_file($fifo)){
          $name = substr(basename($fifo),0,strlen(basename($fifo))-4);          
          if(!@array_pop(glob(".file/fx_$name*"))){
            if(!search_engine_single("yahoo",$name,"poster")){
              trace_log("search_engine_single $name");
              }
            }
          }
        elseif(is_dir($fifo)){
          $name = basename($fifo);
          if(!is_dir($name)){
            if(!search_engine_start($name)){
              trace_log("search_engine_start $name");
              }
            }         
          }
      }
    }

    $print= "<!DOCTYPE html><html><head><meta charset='utf-8'/>
    <style>
    *{margin:0;padding:0;background:#556677;color:white;text-shadow: 2 2 0.5}
    div{float:left;text-align:center;width:13%;height:98px;border-radius:8px;border-top:2px solid silver;border-left:1px solid silver;border-right:5px solid grey;border-bottom:6px solid black;padding:4px}
    .file{background:url(\".browse/images/file_background.png\")}
    </style>
    </head><body><iframe id=\"bypass\" name=\"bypass\" style=\"display:none\" src=\"\"></iframe>";
    
    foreach($allfiles as $file){
      
      $item = utf8_encode(basename($file));
      $label= str_replace ([".","_"]," ",$item);
      $class = "";
      $dir = str_replace($root,"",$file);
      
      if(is_dir($file))
      {
        $target = "_self";
        $logo = @array_pop(@glob("$dir/images/fx_cover_*"));
        if(is_file($logo)){ $label = "<img src='".urlencode($logo)."' width='100%' height='100%' alt=\"$label\" title=\"$label\"/>";}
      }elseif(is_file($file))
      {
        $class = 'class="file"';
        $name = substr($dir,0,strlen($dir)-4);
        $logo = @array_pop(glob(".file/fx_$name*"));
        $target = "bypass";
        if(is_file($logo)){          
          $label = "<img src='".urlencode($logo)."' width='75%' height='100%' alt=\"$label\" title=\"$label\"/>";}
      }
      if($item!="\$RECYCLE.BIN"){
      $print.= "<div $class><a target=\"$target\" href='http://localhost?0=".urlencode(basename($file))."'>$label</a></div>";}
    }
    $print.= "</body></html>";
    return $print;
  }  
function foldrooter($root,$dir){
  $brand = str_replace("\\","/",$dir);
  $dirs  = explode("/",$dir);
  if($dirs[0] ==".browse"){
    $pager = "foldset";
    $brand =  @$dirs[1];
  }else{
    $pager = "foldpage";
    $brand = $dirs[0];
  }
  return $pager($root,$dir,$brand);
}
function foldpage($root,$dir,$brand){

  $p = explode("/",$dir);
  $C = "";
  $pathlinks="<a href=\"/\"><b>&nbsp;&lArr;&nbsp;</b></a>";
  foreach($p as $folder){
    $pathlinks .= "<a href=\"?0=".urlencode($C.$folder)."\">&nbsp;".utf8_encode($folder)."&nbsp;</a>";
    $C .= $folder."/";
    }
  
  if(array_key_exists("research",$_GET)){
    if(!search_engine_start($dir)){
      trace_log("display.search_engine_start 1 $dir");
      }else{
        header("location: ?0=".urlencode($dir));
      }
    }

  $files = glob("$brand/images/fx_*");//brand restricts layout to parentfolder
  
  if(!count($files)){
    $files = glob(".browse/images/fx_*");    
    }

  $cfg = new stdClass();
  $cfg->folder = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
  $cfg->file   = "<a href=\"{url}\" target=\"{target}\"><div class=\"file {class}\">{file}</div></a>";
  $cfg->wallpaper = "";
  $cfg->cast = "";
  $cfg->logo = "";
  
  if(is_dir("$brand")){
    $cfg->menu_url =  "?0=.browse/".urlencode($brand) ;
    $cfg->menu_url_title = $brand ;
    $cfg->menu_button =  "menu-button" ;    
  }else{
    $cfg->menu_url =  "?research&0=".urlencode($brand);
    $cfg->menu_url_title =  "SEARCH Layout (be patient...)";
    $cfg->menu_button =  "setup-button";    
    }
  
  foreach($files as $layout_item){
    $basename = basename($layout_item);
    $key = substr($basename,3,strpos($basename, "_",3)-3);
    if($key){
    $cfg->$key = urlencode($layout_item);}
  }

  $html = file_get_contents(".browse/template-001.xml");
  $html = str_replace(
    ["path.placeholder","setup.placeholder","setup-title.placeholder","setup-button.placeholder","wallpaper.placeholder","logo.placeholder","cast.placeholder",],
    [$pathlinks,$cfg->menu_url,$cfg->menu_url_title,$cfg->menu_button,$cfg->wallpaper,$cfg->logo,$cfg->cast],
    $html);
  
  foreach(glob("../$dir/*") as $fifo ){
    if(is_dir($fifo)){
      $content = "<p>".basename($fifo)."</p>";
      $url = "?0=".urlencode("$dir/".basename($fifo));
      $key = "folder";
      $class = "folder";
      $target = "";
    }elseif(is_file($fifo)){
      $mime_regex = "/\.(jpg|jpeg|png|gif|mp4)$/si";
      $key = "file";
      $class = "file";
      $target  ="";
      preg_match($mime_regex ,basename($fifo),$match);
      $ext = count($match) ? strtolower($match[1]) : "default";
      switch($ext){
        case "jpg" : case "jpeg" : case "png" : case "gif" : case  "bmp" : 
          $url = (preg_match("/\.browse\//",$dir)) 
               ? $fifo
               : ".browse/file.php?0=".urlencode($fifo);
          $content = "<img src=\"$url\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
          $class = "image";
          $target="";
          break;
        default : 
          $target="bypass";
          $content = str_replace(["_","."]," ","<p>".preg_replace($mime_regex ,"",basename($fifo))."</p>");
          $url = "?0=".urlencode($fifo);
      }              
    }
    $snippet = str_replace(["{url}","{class}","{".$key."}","{target}"],[$url,$class,$content,$target],$cfg->$key);
    $html = str_replace("{content}",$snippet."{content}",$html); 
  }
  $html = str_replace("{content}","",$html);
  
  return $html;
}
function foldset($root,$dir,$brand){

  $p = explode("/",$dir);
  $C = "";
  $pathlinks="<a href=\"/\"><b>&nbsp;&lArr;&nbsp;</b></a>";
  
  foreach($p as $folder){
    $pathlinks .= "<a href=\"?0=".urlencode($C.$folder)."\">&nbsp;".utf8_encode($folder)."&nbsp;</a>";
    $C .= $folder."/";
    }
  $files = glob(".browse/images/fx_*"); 

  $cfg = new stdClass();
  $cfg->folder = "<a href=\"{url}\"><div class=\"folder {class}\">{folder}</div></a>";
  $cfg->file   = "<div class=\"file {class}\"><a href=\"{url}\">{file}</a>{input}</div>";
  $cfg->wallpaper = "";
  $cfg->cast = "";
  $cfg->logo = "";

  foreach($files as $layout_item){
    $basename = basename($layout_item);
    $key = substr($basename,3,strpos($basename, "_",3)-3);
    if($key){
    $cfg->$key = urlencode($layout_item);}
  }

  $html = file_get_contents(".browse/template-001.xml");
  $html = str_replace(
    ["path.placeholder","setup.placeholder","setup-title.placeholder","setup-button.placeholder","wallpaper.placeholder","logo.placeholder","cast.placeholder",],
    [$pathlinks,"?0=".urlencode($brand),"","menu-button",$cfg->wallpaper,$cfg->logo,$cfg->cast],
    $html);

  $form = false;
  if(preg_match("/".preg_quote($brand)."\/images$/",$dir)){
    $keys = ["cast","wallpaper","logo"];
    foreach($keys as $key){
      if(array_key_exists($key,$_POST) && !preg_match("/fx_{$key}/",$_POST[$key])){
        set_layout($brand,$key,$_POST[$key]);
      }      
    }
    if(array_key_exists("customtype",$_POST)){
      foreach($_POST["customtype"] as $idx => $ctype){
        if(in_array($ctype,$keys)){
            rename($brand."/images/".$_POST["customfile"][$idx],$brand."/images/".$ctype."_".$_POST["customfile"][$idx]);
          }
        }
      }
    if(array_key_exists("delete",$_POST)){
      foreach($_POST["delete"] as $idx => $name){
        if(is_file($brand."/images/".$name)){
          unlink($brand."/images/".$name);
          }
        }
      }
    
    $form = true;
    $html = str_replace("{content}","<form method=\"post\" action=\"?0=".urlencode($dir)."\">{content}",$html);
  }

  foreach(glob("../$dir/*") as $fifo ){
    $input = "";
    if(is_dir($fifo)){
      $content = "<p>".basename($fifo)."</p>";
      $url = "?0=".urlencode("$dir/".basename($fifo));
      $key = "folder";
      $class = "folder";
    }elseif(is_file($fifo) && !preg_match("/php$/",$fifo)){
      $mime_regex = "/\.(jpg|jpeg|png|gif|mp4)$/si";
      $key = "file";
      $class = "image";
      preg_match($mime_regex ,basename($fifo),$match);
      $ext = count($match) ? strtolower($match[1]) : "default";
      switch($ext){
        case "jpg" : case "jpeg" : case "png" : case "gif" : case  "bmp" :
          $input = "<div  style=\"position:absolute;margin-top:-24px;width:100%;z-index:1;padding-top:-32px;background:grey\">&nbsp;</div>";
          $url = preg_replace("/\.browse\//","",$dir)."/".basename($fifo);
          $content = "<img src=\"".urlencode($url)."\" width=\"100%\" height=\"100%\" style=\"position:relative;opacity:1\"/>";
          if($form){
            $input = "<div  style=\"position:absolute;margin-top:-24px;width:100%;z-index:1;padding-top:-32px;background:grey\">
            <div title=\"delete\" style=\"float:right;height:24px;width:32px;position:relative;text-align:center\">
            <img src = '.browse/images/delete-button.png' width=\"24px\" height=\"100%\" style=\"float:left\"/>
            <input type=\"checkbox\" name=\"delete[]\" value=\"".basename($fifo)."\" style=\"position:absolute;left:16px;width:24px;height:24px;background:transparent;border:0\"/>
            </div>";
            if(preg_match("/^(fx_)?(cast|wallpaper|logo)/",basename($fifo),$m)){
              $checked = $m[1] ? "checked" : "";
              $type    = $m[2] ;
              if($type)
                $input .= "<input type=\"radio\" name=\"$type\" value=\"".basename($fifo)."\" $checked> $type";
            }elseif(!preg_match("/^fx_cover/",basename($fifo),$m)){
                $input .= "<input type=\"hidden\" name=\"customfile[]\" value=\"".basename($fifo)."\"/><select name=\"customtype[]\"><option></option><option>logo</option><option>cast</option><option>wallpaper</option></select>";              
              }else{
                $input .= "";
              }
              $input.="</div>";
            }
          break;
        default : 
          $content = str_replace(["_","."]," ","<p>".preg_replace($mime_regex ,"",basename($fifo))."</p>");
          $url = "?0=".urlencode("$dir/".basename($fifo));
      }              
    }
    $snippet = str_replace(["{url}","{class}","{".$key."}","{input}"],[$url,$class,$content,$input],$cfg->$key);
    $html = str_replace("{content}",$snippet."{content}",$html); 
  }
  
  if($form){
    $html = str_replace("{content}","<hr style=\"width:50%;clear:both\"/><input type=\"submit\"/></form>",$html );   
  }else{
  $html = str_replace("{content}","",$html);}
  
  return $html; 
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
function set_layout($dir,$type,$newset){
  
  foreach(glob($dir."/images/fx_$type*") as $oldset){
    rename($oldset,str_replace("/images/fx_","/images/",$oldset));
  }
  rename($dir."/images/".$newset,$dir."/images/fx_".$newset);
  
  if($type=="logo"){
 
    foreach(glob($dir."/images/fx_cover*") as $oldset){
      unlink($oldset);
    }
    create_preview($dir."/images/fx_".$newset,str_replace("logo_","fx_cover_",$dir."/images/".$newset),256,256);
  }  
}

/*
  yahoo : search images video
*/
/*
image search $_REQUEST["tags","size","color","type","licence"]
*/

function search_engine_start($term,$fetch=["wallpaper","cast","logo"]){

  if(array_key_exists("fetch",$_GET)){ $fetch = $_GET["fetch"]; }
    
  if(!is_dir($term)){
    if(!@mkdir($term)){
      trace_log("search_engine_start.mkdir $term");
      return false;
    }
  }
  if(!is_dir("$term/images"))if(!mkdir("$term/images")){
      trace_log("search_engine_start.mkdir $term/images");
      return false;
    }
    
  foreach($fetch as $type){
    $saved=0;
    foreach(glob("$term/images/*{$type}_*") as $f){
      if(preg_match("/[\d]{1,3}/",basename($f),$a)){
        if( $a[0]*1 > $saved ) { $saved = $a[0]*1; } 
      }
    }
    $count = $saved ? 3 : 1;
    $files = search_engine_fetch("yahoo",$term,$type,$saved,$count);
    if(!count($files)){ trace_log("main.search_engine_start found zero $term/$type"); }
    elseif($type=="logo" && !count(glob("$term/images/fx_cover_*"))){
      create_preview($files[0],str_replace("fx_logo_","fx_cover_",$files[0]),256,256);
      }
  }
   
  return true;
}
function search_engine_fetch($server,$term,$type,$index,$count=1){

  $c    = array_key_exists("count",$_REQUEST)? intval($_REQUEST["count"]) : $count;
  $tags = "(".str_replace(" "," OR ",trim($type." ".$_SERVER["RTCFG"]["root"]["tags"]." ".(array_key_exists("tags",$_REQUEST) ? $_REQUEST["tags"] : ""))).")";
  
  set_time_limit ( 3*$count+10 );
 
  $files = [];
  $saved = $index;

  $m = search_engine_request($server,$term,$tags);

  for($i=0;$i<@count($m);$i++){
    $img = "http://".htmlspecialchars(urldecode($m[$i]));
    $ext = substr($img,strlen($img)-3);
    if(!preg_match("/jpg|png|gif/",$ext)){$ext.=".jpg";}
    $file = @file_get_contents($img);
    if(!$file){
      trace_log("search_engine_fetch.file_get_contents $img");
      }
    else{
      $prefix = (count($files) || $saved) ? "" : "fx_";
      $saved++;
      $filename = "{$term}/images/{$prefix}{$type}_{$saved}.{$ext}";
      if(!file_put_contents($filename,$file)){
      trace_log("search_engine_fetch.file_put_contents $img");
      $saved--;
      }else{
        $files[] = $filename;
        @file_put_contents("$term/.fetch.log","{$type}_{$saved}.{$ext}\t{$img}\n",FILE_APPEND );
        if(count($files)==$c){ $i = count($m); }
      }
    }  
  }
  return $files;
}
function search_engine_request($server,$string,$options){

  $server = "yahoo";

  $term    = "\"".urlencode($string)."\"".urlencode($options);
  $size    = array_key_exists("size",$_REQUEST)    ? $_REQUEST["size"]    : "large";
  $color   = array_key_exists("color",$_REQUEST)   ? $_REQUEST["color"]   : "";
  $type    = array_key_exists("type",$_REQUEST)    ? $_REQUEST["type"]    : "";
  $licence = array_key_exists("licence",$_REQUEST) ? $_REQUEST["licence"] : "";

  $fx = "search_".$server."_image";

  return $fx($term,$size,$color,$type,$licence);
}
function search_engine_single($server,$term,$type){
  $m = search_engine_request($server,$term,$type);
  if(!count($m))return "";
  for($i=0;$i<count($m);$i++){
    $img = "http://".htmlspecialchars(urldecode($m[$i]));
    $ext = strtolower(substr($img,strlen($img)-3));
    try{
      $file = file_get_contents($img);
      $i = count($m);
      break;}
    catch(Exception $e){
      trace_log("search_engine_fetch.file_get_contents $img");
    } 
  }
  if(!$file){
    trace_log("search_engine_fetch.file_get_contents $img");
    }
  else{
    if(!is_dir(".file")){mkdir(".file");}
    
    $newImage=".file/{$term}.{$ext}";
    
    if(!file_put_contents($newImage,$file)){
      trace_log("search_engine_single.file_put_contents $img");
      return "";
    }else{
      @file_put_contents(".file/.fetch.log","{$term}.{$ext}\t{$img}\n",FILE_APPEND );
      create_preview($newImage,str_replace(".file/",".file/fx_",$newImage),256,256);
    }
  }
  return "{$term}.{$ext}";
}
function search_yahoo_image($term,$size="",$color="",$type="",$licence=""){
/*
imgsz=small
imgsz=medium
imgsz=large
imgsz=square
imgsz=wide
imgsz=tall
*/
/*
imgc= bw black&white
imgc= red,green,blue,...
*/
/*
imgty=photo
imgty=graphics
imgty=gif
imgty=face
imgty=portrait
imgty=nonportrait
imgty=clipart
imgty=linedrawing
*/
/*
imgl=pd>Public Domain
imgl=fsu>Free to share and use
imgl=fsuc>Free to share and use commercially
imgl=fmsu>Free to modify, share and use
imgl=fmsuc>Free to modify, share, and use commercially
*/
/*censored content   save=1*/
  $engineUrl = "https://images.search.yahoo.com/search/images?p=<find><options>&save=0";
  $options="";
  
  if($size)$options="&imgsz=$size";
  if($color)$options="&imgc=$color";
  if($type)$options.="&imgty=$type";
  if($licence)$options.="&imgl=$licence";
trace_log($term);
  $url = str_replace(["<find>","<options>"],[$term,$options],$engineUrl);
  $results = [];
  $response = file_get_contents($url); 
  preg_match_all("/imgurl=(.*?)&/",$response,$m);

  return $m[1];
}

main();
clearstatcache();