<?php
//
$_SERVER["CFG"] = parse_ini_file(".browse/global.ini",true);
//
const MIRROR = ".browse";
const I      = "/"; //DIRECTORY_SEPARATOR;
const J      = "/";
const LOGO   = "logo";
const COVER  = "cover";
const CAST   = "cast";
const WPAPER = "wallpaper";
const FX     = "fx_";
//
if(preg_match("/Windows NT/si",$_SERVER["HTTP_USER_AGENT"])){
  $_SERVER["OS"] = "windows-nt";
  $_SERVER["SHELL"] = "bat";
  }
else{
  }