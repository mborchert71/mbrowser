<?php 

//config
$_SERVER["RTCFG"] = parse_ini_file(".browse/global.ini",true);
if(preg_match("/Windows NT/si",$_SERVER["HTTP_USER_AGENT"])){
  $_SERVER["OS"] = "windows-nt";
  $_SERVER["SHELL"] = "bat";
  }
else{}

//main
function trace_log($msg){
  error_log(@date("Y-m-d_H-i-s")."\t"
  .basename(__FILE__)."\t"
  .$msg."\n",3,
  sys_get_temp_dir()."/{$_SERVER['SERVER_NAME']}_{$_SERVER['SERVER_PORT']}.log");
}
function main(){
  $root = "../";
  $fifo = array_key_exists("0",$_GET) ? $_GET[0] : $root;  
  $rooter = $fifo == $root ? "rootpage" : ( is_dir($root.$fifo) ? "foldrooter" : "filerooter" );  
  print $rooter($root,$fifo);
}

//render
function filerooter($root,$fifo){  
  $name = substr(basename($fifo),0,strlen(basename($fifo))-4);
  //
  if(array_key_exists("research",$_REQUEST)){    
    $logo = search_engine_single("yahoo",$name,"poster");
    header("location:/");
    }
  else{
    $file = $root.$fifo;
    if(!preg_match("/^\.browse/",$fifo)){
      $dir = preg_match("/\//",$fifo) ? substr($fifo,0,strpos($fifo,"/")) : ".file";
      file_put_contents($dir."/watch.log",$fifo);
      }
    exec('"'.realpath("./.browse/system/{$_SERVER["OS"]}/launch.{$_SERVER["SHELL"]}").'" "'.$file.'"');
    //
    if(!@array_pop(glob(".file/fx_$name*")) && !strpos($fifo,"/")){ //feature @thismoment unfoldered files only
      if(!search_engine_single("yahoo",$name,"poster")){
        trace_log("search_engine_single $name");
        }
      }
    }
}
function rootpage($root){
  //
  $allfiles = glob($root."*");
  //
  if(array_key_exists("research",$_REQUEST)){
    include_once(".browse/search.php");
    foreach($allfiles as $fifo){
      $path = basename($fifo);
      if(is_file($fifo)){
        $name = substr($path,0,strlen($path)-4);          
        if(!@array_pop(glob(".file/fx_$name*"))){
          if(!search_engine_single("yahoo",$name,"poster")){
            trace_log("search_engine_single $name");
            }
          }
        }
      elseif(is_dir($fifo)){
        if(!is_dir($path)){
          search_async($path);
          }         
        }
      }
    }
  //
  include(".browse/root_layout.php");
  //
  foreach($allfiles as $fifo){    
    $item = "";
    $path = substr($fifo,3);
    $label= str_replace ([".","_"]," ",$path);
    $url  = urlencode($path);
    if(is_dir($fifo)){
      $logo = @array_pop(@glob($path."/images/fx_cover_*"));
      if(is_file($logo)){
        $item = folder_item($url,$label,urlencode($logo));
        }
      else{
        $item = folder_item($url,$label);  
        }
      }
    elseif(is_file($fifo)){
      $name = substr($path,0,strlen($path)-4);
      $logo = @array_pop(glob(".file/fx_$name*"));
      if(is_file($logo)){
        $item = file_item($url,$name,urlencode($logo));
        }
      else{
        $item = file_item($url,$label);  
        }
      }
    //
    if(!in_array(utf8_encode($path),$exclude)){
      $html= str_replace("{content}",$item."{content}",$html);
      }
    }
  $html= str_replace("{content}","",$html);
  return $html;
  }  
function foldrooter($root,$dir){
  //
  $dirs  = explode("/",$dir);
  $renderer = ["foldpage","foldset"];
  $idx = intval(preg_match("/^\.browse/",$dir));
  //
  if(array_key_exists("research",$_GET)){
    search_async($dirs[$idx]);
    header("location: ?0=.browse/".urlencode($dirs[$idx])."/images");
    }
  //
  return $renderer[$idx]($root,$dir,$dirs[$idx]);
  }
function foldpage($root,$dir,$brand){
  $page = new stdClass();
  $page->path = $dir;
  //
  foreach(glob("$brand/images/fx_*") as $layout_item){//restricted to firstlevel : glob("$dir/images/fx_*")
    $basename = basename($layout_item);
    $key = substr($basename,3,strpos($basename, "_",3)-3);
    if($key){
      $page->$key = urlencode($layout_item);
      }
    }
  //
  if(is_dir("$brand") && isset($key)){
    $page->setup_url =  "?0=.browse/".urlencode($brand) ;
    $page->setup_click = "";   
  }else{
    $page->setup_url =  "?research&0=".urlencode($brand)."\" target=\"bypass\"";//workaround 
    $page->setup_click =  "var input=prompt('tags:less is more');this.parentNode.href+='&tags='+input;";  
    }
  //
  include("folder_layout.php");
  //
  foreach(glob($root.$dir."/*") as $fifo ){
    $url = urlencode(substr($fifo,3));
    $label =  utf8_encode(str_replace(["_","."]," ",basename($fifo)));
    $item = "";    
    if(is_dir($fifo)){
      $item = folder_item($url,$label);
      }
    elseif(is_file($fifo)){
      $label = substr($label,0,strlen($label)-4);
      try{
        $item = $render_function[substr($fifo,strrpos($fifo,".")+1)]($url,$label);
        }
      catch(Exception $e){
        $item = $render_function["default"]($url,$label);
        }      
    }
    $html = str_replace("{content}",$item."{content}",$html); 
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
  //include("folder_layout.php");
  
  $html = str_replace(
    ["path.placeholder","search.placeholder","setup.placeholder","setup-title.placeholder","setup-button.placeholder","wallpaper.placeholder","logo.placeholder","cast.placeholder",],
    [$pathlinks,"?0=".urlencode($brand)."&research","?0=".urlencode($brand),"","menu-button",$cfg->wallpaper,$cfg->logo,$cfg->cast],
    $html);
 
  $form = false;
  if(preg_match("/".preg_quote($brand)."\/images$/",$dir)){
    $keys = ["cast","wallpaper","logo"];
    if(array_key_exists("customtype",$_POST)){
      foreach($_POST["customtype"] as $idx => $ctype){
        if(in_array($ctype,$keys)){
            foreach(glob($brand."/images/fx_{$ctype}_*") as $oldfx){
              unlink($oldfx);
              }
            copy($brand."/images/".$_POST["customfile"][$idx],$brand."/images/fx_".$ctype."_".$_POST["customfile"][$idx]);
            if($ctype=="logo"){ 
                foreach(glob($brand."/images/fx_cover*") as $oldcover){
                  unlink($oldcover);
                }
                create_preview($brand."/images/".$_POST["customfile"][$idx],$brand."/images/fx_cover_".$_POST["customfile"][$idx],256,256);
              }
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
      $mime_regex = "/\.(jpg|jpeg|png|gif|bmp)$/si";
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
            if(preg_match("/^fx_(cast|wallpaper|logo)/",basename($fifo),$m)){
              $type    = $m[1] ;
              if($type)
                $input .= $type;
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

main();
clearstatcache();