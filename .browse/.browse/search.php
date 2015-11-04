<?php
/*
  yahoo : search images video
*/
/*
image search $_REQUEST["tags","size","color","type","licence"]
*/
ignore_user_abort();

function search_engine_find($dir,$server,$term,$count=5){
  if(basename($dir)!=$_SERVER["CFG"]["SETUP"]["IMAGES"]){
    trace_log("search_engine_find called from non images folder $dir");
    return false;
    }
  $logfile = dirname($dir).I.$_SERVER["CFG"]["SETUP"]["FETCH_LOG"];
  $src = [];
  $handle = fopen($logfile, "a+");
  if($handle){
    while(($data = fgetcsv($handle, 1024, "\t")) !== FALSE) {
      $src[] = $data[1];
      }
    }
  fclose($handle);
  //
  set_time_limit ( $count*30 );
  $saved = 0;
  $m = search_engine_request($server,urlencode($term));
  $c = @count($m);
  //
  for($i=0;$i<$c;$i++){
    $img = "http://".htmlspecialchars(urldecode($m[$i]));
    if(!in_array($img,$src)){
      $src[] = $img;
      @file_put_contents($logfile,"$server:$term\t$img\n",FILE_APPEND );
      $file = @file_get_contents($img);
      $img = preg_replace("/\?.*$/","",$img);
      $ext = strtolower(substr($img,-3));
      if(!preg_match("/jpg|png|gif|bmp|svg|tif/",$ext)){
        trace_log("search_engine_find.invalid extension $ext");
        $ext.=".jpg";
        }
      if(!$file){
        trace_log("search_engine_find.file_get_contents $img");
        //todo: truncate file strlen("{$type}\t{$img}\n");
        @file_put_contents($logfile,"dead_link\t{$img}\n",FILE_APPEND );
        }
      else{
        $newname  = basename(substr($img,0,strlen($img)-4)).".".$ext;
        if(!file_put_contents($dir."/".$newname,$file)){
          trace_log("search_engine_find.file_put_contents $img");
          }
        else{
          $saved++;
          if($saved==$count){ 
            $i = count($m);
            }
          }
        }  
      }
    }    
  if(!$saved){ trace_log("search_engine_find\tfound zero\t$dir $term"); }
  return $saved;
}

function search_engine_request($server,$term,$size="large",$color="",$type="",$licence=""){

  $size    = array_key_exists("size",$_REQUEST)    ? $_REQUEST["size"]    : $size;
  $color   = array_key_exists("color",$_REQUEST)   ? $_REQUEST["color"]   : $color;
  $type    = array_key_exists("type",$_REQUEST)    ? $_REQUEST["type"]    : $type;
  $licence = array_key_exists("licence",$_REQUEST) ? $_REQUEST["licence"] : $licence;

  $fx = "search_".$server."_image";

  return $fx($term,$size,$color,$type,$licence);
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

  $url = str_replace(["<find>","<options>"],[$term,$options],$engineUrl);
  $results = [];
  $response = file_get_contents($url); 
  preg_match_all("/imgurl=(.*?)&/",$response,$m);

  return $m[1];
  }

function search_engine_fetch($server,$term,$fetch,$tags,$count=1){
  $imgdir = "$term/".$_SERVER["CFG"]["SETUP"]["IMAGES"];
  $src = [];
  $handle = fopen("$term/fetch.log", "a+");
  if($handle)while (($data = fgetcsv($handle, 1024, "\t")) !== FALSE) {
      $src[] = $data[1];
    }  
  fclose($handle);
  //
  foreach($fetch as $type){
    $files = [];
    $tags = str_replace("{type}",$type,$tags);
    $saved = count(glob($imgdir."/"."fx_{$type}*"));
    $count = $count ? $count : ( $saved ? 3 : 1 );
    set_time_limit ( $count*30 );
    //
    $m = search_engine_request($server,"\"".urlencode(str_replace("/","\" \"",$term))."\"".urlencode($tags));
    //
    for($i=0;$i<@count($m);$i++){
      $img = "http://".htmlspecialchars(urldecode($m[$i]));
      if(!in_array($img,$src)){
        $ext = substr($img,strlen($img)-3);
        if(!preg_match("/jpg|png|gif|bmp|svg|tif/",$ext)){$ext.=".jpg";}
        $src[] = $img;
        @file_put_contents("$term/fetch.log","{$type}\t{$img}\n",FILE_APPEND );
        $file = @file_get_contents($img);
        if(!$file){
          trace_log("search_engine_fetch.file_get_contents $img");
          @file_put_contents("$term/fetch.log","dead_link\t{$img}\n",FILE_APPEND );
          }
        else{
          $newname  = basename(substr($img,0,strlen($img)-4)).".".$ext;
          if(!file_put_contents($imgdir."/".$newname,$file)){
            trace_log("search_engine_fetch.file_put_contents $img");
            }
          else{
            if (!count($files) && !$saved){ 
              copy($imgdir."/".$newname,"$imgdir/fx_{$type}_".$newname);
              if($type=="logo"){
                create_preview($imgdir."/".$newname,$imgdir."/"."fx_cover_".$newname,256,256);
                }
              }
            $files[] = $newname;
            $saved++;
            if(count($files)==$count){ 
              $i = count($m);
              }
            }
          }  
        }
      }    
    if(!count($files)){ trace_log("main.search_engine_start\tfound zero\t$term $type $tags"); }
    }
  return count($files);
  }