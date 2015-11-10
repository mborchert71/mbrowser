<?php

class page{
  public $dir;              //as requested
  public $path;             //relative_path
  public $url;              //encoded
  public $urlq;             //standard query ?0=docroot/path
  public $label;            //utf8_encoded
  public $cfg;              //global.ini
  public $my;               //global.ini SETUP section
  public $util;             //global.ini SETUP section
  public $set;              //maybe changing varname vars
  public $stackoprints=[];  //stack of content
  public $content_hook;     //SimpleXMLElement
  public $xmlraw;           //htm template
  public $xml;              //htm template
  public $css;              //css style
  public $exclude;
  public function __construct($dir){
    //
    $this->cfg    = &$_SERVER["CFG"];
    $this->my     = &$_SERVER["CFG"]["SETUP"];
    $this->util   = &$_SERVER["CFG"]["UTIL"];
    $this->dir    = $dir;
    $this->url    = urlencode($dir);
    $this->urlq   = "?0=".$this->url;
    $this->label  = utf8_encode(basename($dir));
    $this->exclude= explode("\t",$this->my["EXCLUDE"]);
    //
    $this->set = new stdClass;
    $this->set->site_title  = $this->my["SITE_TITLE"];
    $this->set->wallpaper   = CODEBASE.IMAGES.I."wallpaper.png";
    $this->set->cast        = CODEBASE.IMAGES.I."cast.jpg";
    $this->set->logo        = CODEBASE.IMAGES.I."logo.png";
    $this->set->mirror_fifo = glob(str_replace([I.IMAGES,MIRROR.I],"",$this->dir)."/*");
    $this->set->renderer = $this->cfg["RENDERER"];
    $this->xmlraw= file_get_contents(__DIR__.I."page.htm");
    $this->xml = new SimpleXMLElement($this->xmlraw);
    $this->css = file_get_contents(__DIR__.I."style.css");
    //
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
  public function handle_request(){
    //
    $this->set->filter      = ""; //preg_replace("/[[:cntrl:]]/","",strval(@$_REQUEST["filter"]));
    //
    if(array_key_exists(KEY_DELETE,$_POST)){ 
      foreach($_POST[KEY_DELETE] as $idx => $file){
        if(is_file($file)){
          if(!unlink($file)){
            trace_log("handle_request.delete $file");
            }
          }
        }
      }
    if(preg_match("/".preg_quote(IMAGES)."$/",$this->dir)){
      if(!is_file($this->dir.I."dummy_cast.jpg")){
        copy(CODEBASE.IMAGES.I."dummy_cast.jpg",$this->dir.I."dummy_cast.jpg");
        }
      if(array_key_exists(KEY_LAYOUT_E,$_POST)){       
        foreach($_POST[KEY_LAYOUT_E] as $idx => $ctype){
          $cname = $_POST[KEY_LAYOUT_F][$idx];
          $cfile = $this->dir.I.$cname;
          $ext   = substr($cname,-4);
          $fxpath= dirname($this->dir).I.CURRENT.I;
          if(in_array($ctype,[LOGO,CAST,WPAPER,COVER])){
            if($ctype==COVER){
              $dir  = basename(dirname($this->dir));
              $cover = dirname(dirname($this->dir)).I.CURRENT.I;
              $width = $this->my["PREVIEW_MAX_WIDTH"];
              $height= $this->my["PREVIEW_MAX_HEIGHT"] ;
              create_preview($cfile,$cover.$dir.$ext,$width,$height); 
              }
            else{
              copy($cfile,$fxpath.$ctype.$ext);  
              }
            }
          elseif(trim($ctype)){
            $width = $this->my["PREVIEW_MAX_WIDTH"];
            $height= $this->my["PREVIEW_MAX_HEIGHT"] ;
            create_preview($cfile,$fxpath.$ctype.$ext,$width,$height); 
            }
          }
        }
      if(array_key_exists(KEY_TERM,$_POST)){
        loading_screen($location=null);
        include_once(CODEBASE."search.php");
        search_engine_find($this->dir,$this->util["SEARCH_SERVER"],$_POST[KEY_TERM],$_POST[KEY_COUNT]);
        loading_screen($location="?0=".$this->dir);
        }
      //
      }
    elseif(@count($_POST)){
      if(array_key_exists(KEY_MKDIR,$_POST)){
        if(!mkdir($_POST[KEY_MKDIR])){
          trace_log("handle_request mkdir ".$_POST[KEY_MKDIR]);
          }
        else{
          mkdir($_POST[KEY_MKDIR].I.IMAGES);
          mkdir($_POST[KEY_MKDIR].I.CURRENT);
          }
        }
      if(array_key_exists(KEY_MR_FILE,$_POST)){ 
        foreach($_POST[KEY_MR_FILE] as $idx => $fifo){
          if(is_file($fifo)){
            $ext = substr($fifo,-4);
            if(basename(substr($fifo,0,-4)) != $_POST[KEY_MR_LABEL][$idx]){
              if(!rename($fifo,dirname($fifo).I.$_POST[KEY_MR_LABEL][$idx].$ext)){
                trace_log("handle_request.rename $fifo => ".$_POST[KEY_MR_LABEL][$idx]);
                }
              else{
                $dir = strpos($fifo,I)===false ? "" : dirname($fifo).I;
                $coverfile = MIRROR.I.$dir.CURRENT.I.basename($fifo);
                $cover = array_pop(@glob($coverfile."*"));
                if($cover){
                  $img = substr($cover,-4);
                  @rename($cover,dirname($cover).I.$_POST[KEY_MR_LABEL][$idx].$ext.$img);
                  }
                }
              }
            }
          else{
            $filename = basename($fifo);
            if($filename != $_POST[KEY_MR_LABEL][$idx]){
              if(!rename($fifo,dirname($fifo).I.$_POST[KEY_MR_LABEL][$idx])){
                trace_log("handle_request.rename $fifo => ".$_POST[KEY_MR_LABEL][$idx]);
                }
              else{
                $setup_dir = MIRROR.I.$fifo;
                if(is_dir($setup_dir)){
                  if(!rename($setup_dir,dirname($setup_dir).I.$_POST[KEY_MR_LABEL][$idx])){
                    trace_log("handle_request.rename mirror $fifo => ".$_POST[KEY_MR_LABEL][$idx]);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  public function addBasics(){
    $head = $this->xml->xpath("/html/head");
    $icon = $head[0]->addChild("link");
    $icon->addAttribute("rel","icon");
    $icon->addAttribute("type","image/png");
    $icon->addAttribute("href",MIRROR.I."favicon.ico");
    //
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
    if(preg_match("/".preg_quote(IMAGES)."/",$this->dir)){
      $this->addMenu($menu,$this->dir);
      }
    elseif($this->dir==MIRROR.I.MIRROR){
      $button = $menu->addChild("a");
      $button->addAttribute("href",CODEBASE."global.php");
      $img = $button->addChild('img');
      $img->addAttribute("class","switch");
      $img->addAttribute("src",CODEBASE.IMAGES.I."setup-button.png");
      }
    //
    $mirror_switch = $menu->addChild("a");
    $mirror_switch->addAttribute("href","?0=".urlencode(str_replace([MIRROR.I,I.CURRENT,I.IMAGES,MIRROR],"",$this->dir)));
    $img = $mirror_switch->addChild('img');
    $img->addAttribute("id","mirror_switch");
    $img->addAttribute("src",CODEBASE.IMAGES.I."menu-button.png");
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
    $m->addAttribute("value",utf8_encode(str_replace(["/",IMAGES,MIRROR]," ",$path)));
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
    $m->addAttribute("value","   ");
    $m->addAttribute("style","width:32px;height:32px;border:0;background:url(\"".CODEBASE.IMAGES.I."search-button.png\") no-repeat center center transparent;");
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
    $ext = strtoupper(substr($path,-3));
    $doc_item = null;
    if(preg_match("/".preg_quote(IMAGES)."$/",dirname($path))){
      return $this->addLayoutItem($e,$path);      
      }
    if(array_key_exists($ext,$this->renderer)){
      $render = $this->renderer[$ext];
      $doc_item = $this->$render($e,$path); 
      }
    elseif($ext!="PHP"){
      $link = $e->addChild("a");
      $link->addAttribute("href",CODEBASE."file.php?0=".urlencode($path));
      $doc_item = $link->addChild("div"," ".utf8_encode(str_replace(["_","."]," ", substr(basename($path),0,-4))));/*empty tag if path renders empy*/
      $doc_item->addAttribute("class","file");
      }
      return $doc_item;
    }
  public function addFolder(SimpleXMLElement &$e,$path){
    $link = $e->addChild("a");
    $link->addAttribute("href","?0=".urlencode($path));
    $div = $link->addChild("div",utf8_encode(str_replace(["_","."]," ",basename($path))));
    $div->addAttribute("class","folder");
    }
  public function addLayoutItem(SimpleXMLElement &$e,$path){
    //
    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",urlencode($path));
    //
    $filename = basename($path);
    //
    $div = $box->addChild("div");
    $div->addAttribute("class","item-bar");
    $trash = $div->addChild("div");
    $trash->addAttribute("title","delete");
    $trash->addAttribute("class","trash-can");
    $trash_icon = $trash->addChild("img");
    $trash_icon->addAttribute("src",CODEBASE.IMAGES.I."delete-button.png");
    $trash_mark = $trash->addChild("input");
    $trash_mark->addAttribute("type","checkbox");
    $trash_mark->addAttribute("name","delete[]");
    $trash_mark->addAttribute("value",$path);
  
    $customfile = $div->addChild("input");
    $customfile->addAttribute("type","hidden");
    $customfile->addAttribute("name",KEY_LAYOUT_F."[]");
    $customfile->addAttribute("value",$filename);
    $select = $div->addChild("select");
    $select->addAttribute("name",KEY_LAYOUT_E."[]");
    $select->addChild("option"," ");
    $select->addChild("option",COVER);
    $select->addChild("option",LOGO);
    $select->addChild("option",CAST);
    $select->addChild("option",WPAPER);

    foreach($this->mirror_fifo as $fifo){
      $select->addChild("option",basename($fifo));
      }
      return $box;
    }
  public function addMirror(SimpleXMLElement &$e,$path){
    //todo:create folder , download here , upload
    $section = $e->addChild("hr");
    $section->addAttribute("style","clear:both");
    $path = str_replace(MIRROR.I,"",$path);
    foreach(glob($path) as $fifo){
      if(is_dir($fifo)){
        if(!is_dir(MIRROR.I.$fifo)){ $this->addMirrorFolder($e,$fifo); }
        }
      else{
        $this->addMirrorFile($e,$fifo);
        }
      }
    return $e;
    }
  public function addMirrorFile(SimpleXMLElement &$e,$fifo){
      $filename = substr(basename($fifo),0,-4);
      
      preg_match("/S[\d]{1,2}[\.-_\s]{0,1}(E[\d]{1,2})/si",$filename,$match);
      $suggested_filename = count($match) ? strtoupper($match[1]) : $filename;
      
      $div = $e->addChild("div");
      $div->addAttribute("style","width:440px;clear:both");
      $hidden = $div->addChild("input");
      $hidden->addAttribute("type","hidden");
      $hidden->addAttribute("value",$fifo);
      $hidden->addAttribute("name",KEY_MR_FILE."[]");
      $input = $div->addChild("input");
      $input->addAttribute("value",$filename);
      $input->addAttribute("name",KEY_MR_LABEL."[]");
      $input->addAttribute("style","background:transparent;width:300px");
      $suggestion = $div->addChild("button","suggest");
      $suggestion->addAttribute("value",$suggested_filename);
      $suggestion->addAttribute("onclick","this.previousSibling.value=this.value;return false;");
      $trash = $div->addChild("div");
      $trash->addAttribute("title","delete");
      $trash->addAttribute("class","trash-can");
      $trash_icon = $trash->addChild("img");
      $trash_icon->addAttribute("src",CODEBASE.IMAGES.I."delete-button.png");
      $trash_mark = $trash->addChild("input");
      $trash_mark->addAttribute("type","checkbox");
      $trash_mark->addAttribute("name","delete[]");
      $trash_mark->addAttribute("value",$fifo);
    }
  public function addMirrorFolder(SimpleXMLElement &$e,$fifo){
      $filename = basename($fifo);     
      $div = $e->addChild("div");
      $div->addAttribute("style","width:440px;clear:both");
      $hidden = $div->addChild("input");
      $hidden->addAttribute("type","hidden");
      $hidden->addAttribute("value",$fifo);
      $hidden->addAttribute("name","mirror_fifo[]");
      $input = $div->addChild("input");
      $input->addAttribute("value",$filename);
      $input->addAttribute("name","mirror_name[]");
      $input->addAttribute("style","background:grey;width:300px");
      $mirror_dir = substr($fifo,strlen(MIRROR.I));
      if(!is_dir($mirror_dir)){
        $setup_mirror = $div->addChild("button","setup");
        $setup_mirror->addAttribute("name",KEY_MKDIR);
        $setup_mirror->addAttribute("value",$mirror_dir);
        }
      }
  public function addImage(SimpleXMLElement &$e,$path){
    $box = $e->addChild("div");
    $box->addAttribute("class","file image");
    $link = $box->addChild("a");
    $link->addAttribute("href",urlencode($path));
    $img = $link->addChild("img");
    $img->addAttribute("src",urlencode($path));
    $div = $box->addChild("div","&nbsp;");
    $div->addAttribute("class","item-bar");
    return $box;
    }
  public function deferXml(SimpleXMLElement &$e,$path,$args){
    $this->stackoprints[] = ["hook"=>$e,"path"=>$path,"args"=>$args];
  }
  public function full_print(){
    $this->handle_request();
    $this->addBasics();
    
    $e = $this->content_hook->addChild("form");
    $e->addAttribute("method","post");
    $e->addAttribute("action","?0=".urlencode($this->dir));
    $e->addAttribute("name","images");
    $div = $e->addChild("div");
    
    $e = $this->addGlob($e,$this->dir.$this->filter."/*",$this->exclude);
    $e = $this->addMirror($e,$this->dir.$this->filter."/*",array());
    
    $submit = $this->set->cell[7]->addChild("input");
    $submit->addAttribute("type","submit");
    $submit->addAttribute("onclick","document.forms['images'].submit();return false;");
    $submit->addAttribute("id","submit_images");

    print $this->xml->asXML();
    }
  //
  }
