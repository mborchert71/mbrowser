<?php
if(!is_file($argv[1])){
  error_log(@date("Y-m-d_H-i-s")."\t"
    .basename(__FILE__)."\t"
    ."param file missing\n",3,
    ".browse/search_error.log");
  exit;
  }
include(".browse/search.php");
$param = unserialize(file_get_contents($argv[1]));
list($server,$term,$fetch,$tags,$count)=$param;
search_engine_fetch($server,$term,$fetch,$tags,$count);
