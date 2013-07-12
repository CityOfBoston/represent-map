<?php
  include_once "header.php";

  header('Content-type: application/json');

  $_escape = function ($str){
     return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
  };

  $marker_id = 0;
  $places = mysql_query("SELECT * FROM places WHERE approved='1' ORDER BY title");
  $places_total = mysql_num_rows($places);
  
  echo '{ "places": [';
  
  while($place = mysql_fetch_assoc($places)) {
    $newplace = Array( );
    $newplace["title"] = $_escape( $place[title] );
    $newplace["description"] = $_escape( $place[description] );
    $newplace["uri"] = $_escape( $place[uri] );
    $newplace["address"] = $_escape( $place[address] );
    $newplace["type"] = $_escape( $place[type] );

    if( $marker_id > 0 ){
      echo ',';
    }
    echo json_encode( $newplace );
    
    $marker_id++;
  }
  
  echo '] }';
  
?>