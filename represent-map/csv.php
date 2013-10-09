<?php
  include_once "header.php";

  header('Content-type: application/csv');

  $_escape = function ($str){
     return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
  };

  $marker_id = 0;
  $places = mysql_query("SELECT * FROM places WHERE approved='1' ORDER BY title");

  echo '"id","title","description","uri","address","type","longitude","latitude"' . "\n";

  while($place = mysql_fetch_assoc($places)) {
    echo "$marker_id" . ',"' . $_escape( $place[title] ) . '","' . $_escape( $place[description] ) . '","' . $_escape( $place[uri] ) . '","' . $_escape( $place[address] ) . '","' . $_escape( $place[type] ) . '",' . "$place[lng]" . ',' . "$place[lat]" . "\n";

    echo json_encode( $newplace );
    
    $marker_id++;
  }
    
?>