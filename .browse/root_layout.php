<?php
$exclude = ["\$RECYCLE.BIN"];

function folder_item($url,$label,$previewurl=null){
  $item = $previewurl 
        ? "<img src=\"$previewurl\" width=\"100%\" height=\"100%\" alt=\"$label\" title=\"$label\"/>"
        : $label;
  return "<div class=\"folder\"><a target=\"_self\" href=\"?0=$url\">$item</a></div>";  
  }
function file_item($url,$label,$previewurl=null){
  $item = $previewurl 
        ? "<img src=\"$previewurl\" width=\"75%\" height=\"100%\" alt=\"$label\" title=\"$label\"/>"
        : $label;
  return "<div class=\"file\"><a target=\"bypass\" href=\"?0=$url\">$item</a></div>";  
  }
    
/*
?>
*/
$html= <<<PAGE
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'/>
<style>
*{
  margin:0;
  padding:0;
  background:#556677;
  color:white;
  text-shadow: 2 2 0.5
  }
div{
  float:left;
  text-align:center;
  width:13%;
  height:98px;
  border-radius:8px;
  border-top:2px solid silver;
  border-left:1px solid silver;
  border-right:5px solid grey;
  border-bottom:6px solid black;
  padding:4px
  }
.file{
  background:url(".browse/images/file_background.png")
  }
  </style>
</head>
<body><iframe id="bypass" name="bypass" style="display:none" src=""></iframe>
{content}
</body>
</html>
PAGE;
?>