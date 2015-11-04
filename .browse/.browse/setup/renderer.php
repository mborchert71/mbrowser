<?php
//
class page{
  public $docroot;          //relative_path
  public $dir;              //as requested
  public $path;             //relative_path
  public $url;              //encoded
  public $urlq;             //standard query ?0=docroot/path
  public $label;            //utf8_encoded
  public $my;               //global.ini SETUP section
  public $cfg;              //global.ini
  public $set;              //maybe changing varname vars
  public $stackoprints=[];  //stack of content
  public $content_hook;     //SimpleXMLElement
  public $xml;              //htm template
  public $css;              //css style
  public $exclude;
  public function __construct($dir){
    //
    $this->docroot= $_SERVER["ROOT"];
    $this->cfg    = &$_SERVER["CFG"];
    $this->my     = &$_SERVER["CFG"]["SETUP"];
    $this->dir    = $dir;
    $this->path   = substr($dir,strlen(MIRROR.J));
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    $this->exclude= explode(" ",$this->my["EXCLUDE"]);
    //
    $this->set = new stdClass;
    $this->set->site_title  = $this->my["SITE_TITLE"];
    $this->set->wallpaper   = $this->my["FX_WPAPER"];
    $this->set->cast        = $this->my["FX_CAST"];
    $this->set->logo        = $this->my["FX_LOGO"];
    $this->set->mirror_fifo = glob($this->docroot.str_replace(["/".$this->my["IMAGES"],MIRROR],"",$this->path)."/*");
    $this->set->renderer = $this->cfg["RENDERER"];
    $this->xml = new SimpleXMLElement($this->my["SITE"],null,true);
    $this->css = file_get_contents($this->my["STYLE"]);
    //
    $this->check_mirror();
    }
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
    if(!$this->path){
      return;
      }
    $imagespath = $this->path.DIRECTORY_SEPARATOR.$this->my["IMAGES"];
    if(!is_dir($this->path)){
      if(!mkdir($this->path)){
        trace_log("check_mirror.mkdir <mirror>".$this->path);
        return;
        }
      if(basename($this->path)!=$this->my["IMAGES"]){
        @mkdir($imagespath);
        }
      return;
      }
    if(basename($this->path)!=$this->my["IMAGES"] && !is_dir($imagespath)){
      if(!mkdir($imagespath)){
        trace_log("check_mirror.mkdir <mirror>".$imagespath);
        return;
        }
      }
    }
  public function handle_request(){
    //
    $this->set->filter      = ""; //preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    //
    if(preg_match("/".preg_quote($this->my["IMAGES"])."$/",$this->path)){
      if(array_key_exists($this->cfg["LAYOUT_E"],$_POST)){             //selection on image        
        foreach($_POST[$this->cfg["LAYOUT_E"]] as $idx => $ctype){     //the chosen option
          $cname = $_POST[$this->cfg["LAYOUT_F"]][$idx];               //the image
          $cfile = $this->path.I.$cname;
          $fxpath= $this->path.I.FX;
          if(in_array($ctype,[LOGO,CAST,WPAPER])){           
            foreach(glob($fxpath.$ctype."*") as $oldfx){
              unlink($oldfx);                                          //delete files with having option already
              }
            copy($cfile,$fxpath.$ctype."_".$cname);                    //make file to chosen option
            if($ctype==LOGO){                                          //(folder)cover
              foreach(glob($fxpath.COVER."*") as $oldcover){
                unlink($oldcover);
                }
              create_preview($cfile,$fxpath.COVER."_".$cname,
                                    $this->my["PREVIEW_MAX_WIDTH"],$this->my["PREVIEW_MAX_HEIGHT"]); 
              }
            }
          elseif(trim($ctype)){                                         //(file)cover
            create_preview($cfile,$fxpath.$ctype.$cname,
                                  $this->my["PREVIEW_MAX_WIDTH"],$this->my["PREVIEW_MAX_HEIGHT"]); 
            }
          }
        }
      if(array_key_exists($this->cfg["DELETE"],$_POST)){ 
        foreach($_POST[$this->cfg["DELETE"]] as $idx => $name){
          if(is_file($this->path."/".$name)){
            if(!unlink($this->path."/".$name)){
              trace_log("handle_request.delete $name");
              }
            }
          }
        }
      if(array_key_exists($this->cfg["TERM"],$_POST)){
        loading_screen($location=null);
        include_once($this->cfg["UTIL"]["SEARCH_FILE"]);
        search_engine_find($this->path,$this->cfg["UTIL"]["SEARCH_SERVER"],
                                $_POST[$this->cfg["TERM"]],$_POST[$this->cfg["COUNT"]]);
        loading_screen($location="?0=".$this->dir);
        }
      }
    }
  public function addBasics(){
    //todo: if i load the htm_template, i should load the paths to predefined standard-hooks as well
    //OR define a set of standard hooks to be a fixed path so no cfg is needed (like "title". xpath is pretty determined)
$hooks = <<<HOOKS
<htm:hooks>
  <title>/html/head/title</title>
  <style>/html/head/style</style>
  <script:head>/html/head/script</script:head>
  <script:body>/html/body/script</script:body>
  <images class="wallpaper cast">/html/body/img</images>
  <cells grid="3x3">//td</cells>
</htm:hooks>
HOOKS;
    $title  = $this->xml->xpath("/html/head/title");
    $title[0][0] = $this->set->site_title;
    $style       = $this->xml->xpath("/html/head/style");
    $style[0][0] = $this->css;
    $design   = $this->xml->xpath("/html/body/img");
    $design[0]->attributes()["src"] = $this->set->wallpaper;
    $design[1]->attributes()["src"] = $this->set->cast;
    //
    
    $cell    = $this->xml->xpath("//td");
    $this->set->cell = &$cell;
    //0
    $logo = $cell[0]->addChild('img');
    $logo->addAttribute("id",LOGO);
    $logo->addAttribute("src",$this->set->logo);
    //1
    $menu = $cell[1]->addChild('form');
    $menu->addAttribute("id","menu");
    $menu->addAttribute("name","menu");
    $menu->addAttribute("method","post");
    $menu->addAttribute("action",$this->urlq);
    $menu->addAttribute("accept-charset","utf-8");
    //
    if(preg_match("/".preg_quote($this->my["IMAGES"])."/",$this->dir)){
      $this->addMenu($menu,$this->path);
      }
    elseif($this->dir==MIRROR.I){
      $button = $menu->addChild("a");
      $button->addAttribute("href",MIRROR."/global.php");
      $img = $button->addChild('img');
      $img->addAttribute("class","switch");
      $img->addAttribute("src",MIRROR."/images/setup-button.png");
      }
    //
    $mirror_switch = $menu->addChild("a");
    $mirror_switch->addAttribute("href","?0=".urlencode(str_replace(["/".$this->my["IMAGES"],MIRROR],"",$this->path)));
    $img = $mirror_switch->addChild('img');
    $img->addAttribute("id","mirror_switch");
    $img->addAttribute("src",MIRROR."/images/menu-button.png");
    //2
    //3
    $content = $cell[4]->addChild("div");
    $content->addAttribute("id","content");
    $this->content_hook = &$content;
    //5
    //6
    //7
    $navi = $cell[7]->addChild("h3");
    $navi->addAttribute("class","navi");
    $this->addNavi($navi,$this->dir);
    //8
    }
  public function addContentByCondition(SimpleXMLElement &$e,$content=null,$condition=false){
    //todo:elaborate
    //condition true function preg_match arguments regey path , content c:form a:method->post ,a:action->url ,return element true
    //
    $condition = preg_match("/".preg_quote($this->my["IMAGES"])."$/",$this->path);
    if($condition){
      if($content){
        $e = $this->content_hook->addChild("form");
        $e->addAttribute("method","post");
        $e->addAttribute("action","?0=".urlencode($this->dir));
        $e->addAttribute("name","images");
        $div = $e->addChild("div");
        foreach(glob($this->path.I.FX."*") as $active_layout_image){
          $this->addImage($div,$active_layout_image);
          $this->exclude[] = realpath($active_layout_image);
          }
        }
      else{
        $submit = $this->set->cell[7]->addChild("input");
        $submit->addAttribute("type","submit");
        $submit->addAttribute("onclick","document.forms['images'].submit();return false;");
        $submit->addAttribute("id","submit_images");
        }
      }
      return $e;
    }
  public function addNavi(SimpleXMLElement &$e,$path){
    $m = $e->addChild("a","&lArr;");
    $m->addAttribute("href","/");
    $c = "";
    foreach(explode("/",$path) as $f){
      $m = $e->addChild("a",utf8_encode($f));
      $m->addAttribute("href","?0=".urlencode($c.$f));
      $c .= $f."/";
      }
    }
  public function addMenu(SimpleXMLElement &$e,$path){
    $m = $e->addChild("input");
    $m->addAttribute("id","term");
    $m->addAttribute("name","term");
    $m->addAttribute("autocomplete","off");
    $m->addAttribute("type","text");
    $m->addAttribute("value",utf8_encode(str_replace(["/",$this->my["IMAGES"]]," ",$path)));
    //
    $m = $e->addChild("input");
    $m->addAttribute("id","count");
    $m->addAttribute("name","count");
    $m->addAttribute("min","1");
    $m->addAttribute("type","number");
    $m->addAttribute("value","5");
    //
    $m = $e->addChild("input");
    $m->addAttribute("type","submit");
    $m->addAttribute("value","");
    $m->addAttribute("onmouseover","document.forms['menu'].term.style.backgroundColor='#000000';");
    $m->addAttribute("onmouseout" ,"document.forms['menu'].term.style.backgroundColor='transparent';");
    }
  public function addGlob(SimpleXMLElement &$e,$path_expression,$exclude=array()){
    $func = ["deferXML","addFolder"];//todo:cfg
    $args = ["addFile",null];//todo:cfg
    //
    foreach(glob($path_expression) as $fifo){
      if(!in_array(realpath($fifo),$exclude)){
        $idx = intval(is_dir($fifo));
        $this->{$func[$idx]}($e,$fifo,$args[$idx]);
        }
      }
    foreach($this->stackoprints as $fx){ //todo:own function print_stack
      $this->$fx["args"]($fx["hook"],$fx["path"]);//todo:clean args handling
      }
    return $e;
    }
  public function addFile(SimpleXMLElement &$e,$path){
    $path = substr($path,strlen($this->docroot.".browse/"));
    $ext = strtoupper(substr($path,-3));
    if(array_key_exists($ext,$this->renderer)){
      $render = $this->renderer[$ext];
      $this->$render($e,$path);    
      }
    elseif($ext!="php"){
      $link = $e->addChild("a");
      $link->addAttribute("href",MIRROR."/file.php?0=".urlencode($path));
      $div = $link->addChild("div"," ".utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4))));/*empty tag if path renders empy*/
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
    //
    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",urlencode($path));
    //
    if(!preg_match("/".preg_quote($this->my["IMAGES"])."$/",dirname($path))){
      $div = $box->addChild("div","&nbsp;");
      $div->addAttribute("class","item-bar");      
      }
    else{
      $filename = basename($path);
      //
      $div = $box->addChild("div");
      $div->addAttribute("class","item-bar");
      $trash = $div->addChild("div");
      $trash->addAttribute("title","delete");
      $trash->addAttribute("class","trash-can");
      $trash_icon = $trash->addChild("img");
      $trash_icon->addAttribute("src",MIRROR."/images/delete-button.png");
      $trash_mark = $trash->addChild("input");
      $trash_mark->addAttribute("type","checkbox");
      $trash_mark->addAttribute("name","delete[]");
      $trash_mark->addAttribute("value",$filename);
      //
      $types = implode("|",[preg_quote(LOGO),preg_quote(WPAPER),preg_quote(CAST),preg_quote(COVER)]);
      //
      if(preg_match("/^".preg_quote(FX)."($types|.*)/",$filename,$type)){
        if($type[1] && preg_match("/^($types)$/",$type[1])){
          $div->addChild("span",$type[1]);
          }
        }
      else{
        $customfile = $div->addChild("input");
        $customfile->addAttribute("type","hidden");
        $customfile->addAttribute("name",$this->cfg["LAYOUT_F"]."[]");
        $customfile->addAttribute("value",$filename);
        $select = $div->addChild("select");
        $select->addAttribute("name",$this->cfg["LAYOUT_E"]."[]");
        $select->addChild("option"," ");
        $select->addChild("option",LOGO);
        $select->addChild("option",CAST);
        $select->addChild("option",WPAPER);
        foreach($this->mirror_fifo as $fifo){
          if(is_file($fifo)){
            $select->addChild("option",basename($fifo));
            }
          }
        }
      }
    }
  public function deferXml(SimpleXMLElement &$e,$path,$args){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$path,"args"=>$args];
  }
  public function full_print(){
    $this->handle_request();
    $this->addBasics();
    $e = $this->addContentByCondition($this->content_hook,true);
    $e = $this->addGlob($e,$this->docroot.$this->dir.$this->filter."/*",$this->exclude);
    $this->addContentByCondition($e);
    print $this->xml->asXML();
    }
  }
