<?php
//
if(preg_match("/Windows NT/si",$_SERVER["HTTP_USER_AGENT"])){
  define("OS","windows-nt");
  define("SHELL_EXT","bat");
  }
else{
  }
//
const I      = "/"; //DIRECTORY_SEPARATOR;
const J      = "/";
define("MIRROR", basename(__DIR__) );
const CODEBASE = MIRROR.I.MIRROR.I;
//
$_SERVER["CFG"] = parse_ini_file(CODEBASE."global.ini",true);
//
define("IMAGES" ,$_SERVER["CFG"]["SETUP"]["IMAGES"]);
define("CURRENT",$_SERVER["CFG"]["SETUP"]["CURRENT"]);
define("LOGO"   ,$_SERVER["CFG"]["SETUP"]["LOGO"]);
define("COVER"  ,$_SERVER["CFG"]["SETUP"]["COVER"]);
define("CAST"   ,$_SERVER["CFG"]["SETUP"]["CAST"]);
define("WPAPER" ,$_SERVER["CFG"]["SETUP"]["WPAPER"]); //yeah yeah twix is now called raider :  BACKDROP
//
define("KEY_SEARCH" ,$_SERVER["CFG"]["SEARCH"]);
define("KEY_TAGS" ,$_SERVER["CFG"]["TAGS"]);
define("KEY_TITLE" ,$_SERVER["CFG"]["TITLE"]);
define("KEY_FETCH" ,$_SERVER["CFG"]["FETCH"]);
define("KEY_COUNT" ,$_SERVER["CFG"]["COUNT"]);
define("KEY_TERM" ,$_SERVER["CFG"]["TERM"]);
define("KEY_DELETE" ,$_SERVER["CFG"]["DELETE"]);
define("KEY_LAYOUT_E" ,$_SERVER["CFG"]["LAYOUT_E"]);
define("KEY_LAYOUT_F" ,$_SERVER["CFG"]["LAYOUT_F"]);
define("KEY_MR_LABEL" ,$_SERVER["CFG"]["MR_LABEL"]);
define("KEY_MR_FILE" ,$_SERVER["CFG"]["MR_FILE"]);
define("KEY_MKDIR" ,$_SERVER["CFG"]["MKDIR"]);
