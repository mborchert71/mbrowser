<?php
$markup = <<<HTML
<html>
 <head>
  <meta charset="utf-8"/>
  <style>
  #ini{width:100%;margin-left_auto;margin-right:auto}
  #ini #panel{position:fixed;top:0;left:0;z-index:1;background:#ffffff;width:100%}
  #ini section{margin-top:48px;margin-left_auto;margin-right:auto}
  #ini button{width:25%}
  #ini input{width:70%}
  </style>
 </head>
 <body>
  <form id="ini" name="ini" method="post"><div id="panel"><input type="submit"/></div>
    <section></section>
  </form>
 </body>
</html>
HTML;

$cfg = parse_ini_file("global.ini",true);
$xml = new SimpleXMLElement($markup);
$hook = $xml->xpath("/html/body/form/section");
addIniForm($hook[0],$cfg);
print $xml->asXML();

function addIniComment(SimpleXMLElement $e,$string){
  $e->addChild("p",$string);
  }
function addIniKeyVal(SimpleXMLElement $e,$key,$value){
  $group = $e->attributes()["id"];
  $div = $e->addChild("div");
  $div->addChild("button",$key);
  $i = $div->addChild("input");
  $i->addAttribute("type","text");
  $i->addAttribute("value",$value);
  $i->addAttribute("name",$group ? "$group[$key]" : $key );
  }
function addIniGroup(SimpleXMLElement $e,$name){
  $grp = $e->addChild("fieldset");
  $grp->addAttribute("id",$name);
  $grp->addChild("legend",$name);
  return $grp;
}
function addIniForm(SimpleXMLElement $e,$ini){
  foreach($ini as $gkey => $value){
    if(is_array($value)){
      $grp = addIniGroup($e,$gkey);
      foreach($value as $key =>$val){
        addIniKeyVal($grp,$key,$val);
        }
      }
    else{
      addIniKeyVal($e,$gkey,$value);
      }
    }
  }
function writeIni($path,array $ini){
  //if is file compare bind in comments
}