<?php

class page extends SimpleXMLElement{
  public $dir;
  public $path;
  public $docroot_path;
  public $url;
  public $urlq;
  public $label;
  public $set;
  public $renderer= [];
  public $stackoprints=[];
  public $content_hook;
  public function __get($key){
    if(isset($this->set,$key)){
      return $this->set->$key;
      }
    }
  public function __set($key,$val){
    if(isset($this->set,$key)){
      $this->set->$key=$val;
      return true;
      }
      return false;
    }
  public function check_mirror(){
    if($this->path && !is_dir($this->path)){
      if(!mkdir($this->path)){
        trace_log("folder_item.mkdir ./browse/".$this->path);
        return;
        }
      }    
    }
  public function handle_request($path){
    $dir = $path;
    $imgdir = $_SERVER["CFG"]["SETUP"]["IMAGES"];
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
  public function setBasics($dir){
    $this->dir    = $dir;
    $this->path   = substr($dir,strlen(".browse/"));
    $this->docroot_path = $_SERVER["ROOT"].$dir;
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
      
    $this->set = new \stdClass;
    $this->set->wallpaper = ".browse/images/fx_wallpaper.png";
    $this->set->cast      = ".browse/images/fx_cast.jpg";
    $this->set->logo      = ".browse/images/fx_logo.png";

    $this->set->filter = preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    $this->set->excludes = explode(";",$_SERVER["CFG"]["SETUP"]["EXCLUDE"]);

    foreach(glob(".browse/images/fx_*") as $layout_item){
      $basename = basename($layout_item);
      $key = substr($basename,3,strpos($basename, "_",3)-3);
      if($key){
        $this->set->$key = urlencode($layout_item);
        }
      }
    $this->renderer = [
    "JPG" => "addImage",
    "PNG" => "addImage",
    "GIF" => "addImage",
    "BMP" => "addImage"
    ];
    $this->stackoprints = [];
    
    $this->check_mirror();

    $title  = $this->xpath("/html/head/title");
    $title[0][0] = "Media Browser";

    $style       = $this->xpath("/html/head/style");
    $style[0][0] = file_get_contents('.browse/setup/style.css');

    $design   = $this->xpath("/html/body/img");
    $design[0]->attributes()["src"] = $this->set->wallpaper;
    $design[1]->attributes()["src"] = $this->set->cast;

    $cell    = $this->xpath("//td");
    //0
    $logo = $cell[0]->addChild('img');
    $logo->addAttribute("id","logo");
    $logo->addAttribute("src",$this->set->logo);
    //1
    $menu = $cell[1]->addChild('form');
    
    $menu->addAttribute("id","menu");
    $menu->addAttribute("name","menu");
    $menu->addAttribute("method","post");
    $menu->addAttribute("action",$this->urlq);
    $menu->addAttribute("target","_self");
    $menu->addAttribute("accept-charset","utf-8");
    $mirror_switch = $menu->addChild("a");
    $mirror_switch->addAttribute("href","?0=".urlencode(str_replace([".browse/","/images",".browse"],"",$dir)));
    $mirror_switch->addChild('img');
    $mirror_switch->addAttribute("id","mirror_switch");
    $mirror_switch->addAttribute("src",".browse/images/menu-button.png");
    
    $this->addMenu($menu,$this->path);
    //2
    //3
    $content = $cell[4]->addChild("div");
    $content->addAttribute("id","content");
    $this->content_hook = &$content;
    //5
    //6
    $navi = $cell[7]->addChild("h3");
    $this->addNavi($navi,$dir);
    //8
    }
  public function addNavi(SimpleXMLElement &$e,$path){
    $e->addChild("a","&lArr;");
    $e->addAttribute("href","/");
    $c = "";
    foreach(explode("/",$path) as $f){
      $e->addChild("a",utf8_encode($f));
      $e->addAttribute("href","?0=".urlencode($c.$f));
      $c .= $f."/";
      }
    }
  public function addMenu(SimpleXMLElement &$e,$path){
    $e->addChild("input");
    $e->addAttribute("id","term");
    $e->addAttribute("name","term");
    $e->addAttribute("autocomplete","off");
    $e->addAttribute("type","text");
    $e->addAttribute("value",utf8_encode(str_replace(["/",$_SERVER["CFG"]["SETUP"]["IMAGES"]]," ",$path)));
    
    $e->addChild("input");
    $e->addAttribute("id","count");
    $e->addAttribute("name","count");
    $e->addAttribute("min","1");
    $e->addAttribute("type","number");
    $e->addAttribute("value","5");

    $e->addChild("input");
    $e->addAttribute("type","submit");
    $e->addAttribute("value","");
    $e->addAttribute("mouseover","document.forms['menu'].term.style.backgroundColor='#000000';");
    $e->addAttribute("mouseout" ,"document.forms['menu'].term.style.backgroundColor='transparent';");
    }
  public function addGlob(SimpleXMLElement &$e,$path_expression,$excludes=array()){
    $router = ["addFile","addFolder"];
    foreach(glob($path_expression) as $fifo){
      if(!in_array($fifo,$excludes)){
        $this->$router[intval(is_dir($fifo))]($e,$fifo);
        }
      } 
    }
  public function addFile(SimpleXMLElement &$e,$path){
    $path = substr($path,strlen($_SERVER["ROOT"].".browse/"));
    $ext = strtoupper(substr($path,-3));
    if(array_key_exists($ext,$this->renderer)){
      $render = $this->renderer[$ext];
      $render($e,$path);    
      }
    elseif($ext!="php"){
      $link = $e->addChild("a");
      $link->addAttribute("href",".browse/file.php?0=".urlencode($path));
      $div = $link->addChild("div",utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4))));
      $div->addAttribute("class","file");
      }   
    }
  public function addFolder(SimpleXMLElement &$e,$path){
    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".urlencode(substr($path,strlen($_SERVER["ROOT"]))));
    $div = $link->addChild("div",utf8_encode(str_replace(["_","."]," ",basename($path))));
    $div->addAttribute("class","folder");
    }
  public function addImage(SimpleXMLElement &$e,$path){

    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",urlencode($path));
        
    if(!preg_match("/images$/",dirname($path))){
      $div = $box->addChild("div","&nbsp;");
      $div->addAttribute("class","item-bar");      
      }
    else{
      $filename = basename($path);
      
      $div = $box->addChild("div");
      $div->addAttribute("class","item-bar");
      $trash = $div->addChild("div");
      $trash->addAttribute("title","delete");
      $trash->addAttribute("class","trash-can");
      $trash_icon = $trash->addChild("img");
      $trash_icon->addAttribute("src",".browse/images/delete-button.png");
      $trash_mark = $trash->addChild("input");
      $trash_mark->addChild("type","checkbox");
      $trash_mark->addChild("name","delete[]");
      $trash_mark->addChild("value",$filename);

      if(preg_match("/^fx_(cast|wallpaper|logo)/",$filename,$type)){
        if($type[1]){
          $div->addChild("span",$type[1]);
          }
        }
      elseif(!preg_match("/^fx_cover/",$filename,$m)){
        $customfile = $box->addChild("input");
        $customfile->addAttribute("type","hidden");
        $customfile->addAttribute("name","customfile[]");
        $customfile->addAttribute("value",$filename);
        $select = $box->addChild("div");
        $select->addAttribute("name","customtype");
        $select->addChild("option","");
        $select->addChild("option","logo");
        $select->addChild("option","cast");
        $select->addChild("option","wallpaper");
        }
      }
    }
  public function full_print(){
    $this->setBasics($this->dir);
    $this->addGlob($this->content_hook,$this->dir.$this->filter."/*");
    print $this->asXML();
    }
  }
//addA(href,label,class,labelIsElement)
//addInput(type,name,value,class,attributes)
//addDiv(class,id)
//addImg(src,class)

(new page(".browse/setup/page.htm",null,true))->full_print();