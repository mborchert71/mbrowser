<?php
$markup = <<<HTML
<html>
 <head>
  <meta charset="utf-8"/>
  <style>
  #ini{width:100%;margin-left_auto;margin-right:auto}
  #ini #panel{position:fixed;top:0;left:0;z-index:1;background:#ffffff;width:100%}
  #ini section{margin-top:48px;margin-left_auto;margin-right:auto}
  #ini button{width:20%}
  #ini input{width:30%}
  /*.keyval{float:left;width:50%}*/
  </style>
 </head>
 <body>
  <form id="ini" name="ini" method="post"><div id="panel"><input type="submit"/></div>
    <section></section>
  </form>
 </body>
</html>
HTML;

$law = parse_ini_file("global.schema.ini",true);
$cfg = parse_ini_file("global.ini",true);
$xml = new SimpleXMLElement($markup);
$hook = $xml->xpath("/html/body/form/section");

if(@count($_POST)){
  $cfg = writeIni("global.ini",$cfg,$law);
  }
addIniForm($hook[0],$cfg,$law);

print $xml->asXML();

function addIniComment(SimpleXMLElement $e,$string){
  $e->addChild("p",$string);
  }
function addIniKeyVal(SimpleXMLElement $e,$key,$value){
  $group = $e->attributes()["id"];
  $div = $e->addChild("div");
  $div->addChild("button",$key);
  $div->addAttribute("class","keyval");
  $i = $div->addChild("input");
  $i->addAttribute("type","text");
  $i->addAttribute("value",$value);
  $i->addAttribute("name",$group ? "{$group}[{$key}]" : $key );
  }
function addIniGroup(SimpleXMLElement $e,$name){
  $grp = $e->addChild("fieldset");
  $grp->addAttribute("id",$name);
  $grp->addChild("legend",$name);
  return $grp;
}
function addIniForm(SimpleXMLElement $e,$ini,$law){
  foreach($ini as $gkey => $value){
    if(is_array($value)){
      $grp = addIniGroup($e,$gkey);
      foreach($value as $key =>$val){
        if(preg_match("/^1\s/",$law[$gkey][$key])){
          addIniKeyVal($grp,$key,$val);
          }
        }
      }
    else{
      if(preg_match("/^1\s/",$law[$gkey])){
        addIniKeyVal($e,$gkey,$value);
        }
      }
    }
  }
function check_value($value){
  //todo:really validate
  if(!is_array($value)){ return $value;}
  else{ return array_pop($value); } 
  }
function writeIni($path,array $ini,array $law){
  $new_ini = array_merge_recursive($ini,$_POST);
  copy($path,$path.".bkp");
  $iniString = ""; 
  foreach($new_ini as $gkey => $value){
    if(is_array($value)){
      $iniString .= "[$gkey]\n";
      foreach($value as $key =>$val){
      $new_ini[$gkey][$key] = check_value($val,$law[$gkey][$key]);
      $iniString .= "$key = \"".str_replace('"','\"',$new_ini[$gkey][$key])."\"\n";
        }
      }
    else{
      $new_ini[$gkey] = check_value($value,$law[$gkey]);
      $iniString .= "$gkey = \"".str_replace('"','\"',$new_ini[$gkey])."\"\n";
      }
    }
  file_put_contents($path,$iniString);
  return $new_ini;
  }