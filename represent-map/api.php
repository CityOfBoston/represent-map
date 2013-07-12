<?php
include_once "header.php";
?>

<?php

  $_escape = function ($str){
     return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
  };

  $types = Array(
    Array('#e418ac', 'Innovation Spaces'),
    Array('#bb25e2','Tech'),
    Array('#6831e0', 'Creative'), 
    Array('#3d57de', 'Life Science'),
    Array('#49a8dd', 'Professional Services'),
    Array('#54dbcb', 'Cultural and Educational'),
    Array('#60d991', 'Showroom'),
    Array('#73d76b', 'Institutional and Non-Profit'),
    Array('#abd576', 'Industrial'),
    Array('#d4d181', 'Food and Retail'),
    Array('#d49779', 'Other')
  );
  $marker_id = 0;
  $places = mysql_query("SELECT * FROM places WHERE approved='1' ORDER BY title");
  $places_total = mysql_num_rows($places);
  
  echo '{ places: [';
  
  while($place = mysql_fetch_assoc($places)) {
    $newplace = Array( );
    $newplace["title"] = $_escape( $place[title] );
    $newplace["description"] = $_escape( $place[description] );
    $newplace["uri"] = $_escape( $place[uri] );
    $newplace["address"] = $_escape( $place[address] );

    if( $marker_id > 0 ){
      echo ',';
    }
    echo json_encode( $newplace );
    
    $marker_id++;
  }
  
  echo '] }';
  
?>