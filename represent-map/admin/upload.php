<?php
include "header.php";

if($task == "doupload") {

  $name = $_FILES['csvfile']['name'];

  $_escape = function ($str){
     $str = str_replace( '&', '&amp;', $str );
     $str = str_replace( '\'', '&#039;', $str );
     return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
  };

  if ($_FILES['csvfile']['error'] == UPLOAD_ERR_OK  //checks for errors
      && is_uploaded_file($_FILES['csvfile']['tmp_name'])) { //checks that file is uploaded
    if (($handle = fopen( $_FILES['csvfile']['tmp_name'] , "r")) !== FALSE) {
      $row = 0;
      $fields = Array( );
      $namefield = 0;
      $addressfield = 0;
      $typefield = 0;
      $descriptionfield = 0;
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        //$num = count($data);
        //echo "<p> $num fields in line $row: <br /></p>\n";
        if($row == 0){
          $fields = $data;
          $num = count($fields);
          for ($c=0; $c < $num; $c++) {
            if( trim($fields[$c]) == "Company Name" ){
              $namefield = $c;
            }
            else if( trim($fields[$c]) == "Address" ){
              $addressfield = $c;
            }
            else if( trim($fields[$c]) == "Industry" ){
              $typefield = $c;
            }
            else if( trim($fields[$c]) == "Description" ){
              $descriptionfield = $c;
            }
          }
          echo $namefield . "<br/>";
          echo $addressfield . "<br/>";
        }
        else{
          $placename = trim( $_escape($data[$namefield]) );
          $address = trim( str_replace( 'floor Boston', 'floor, Boston', $_escape($data[$addressfield]) ) );
          $address = explode( 'Boston, MA', $address );
          if( count($address) > 1 ){
            $address = $address[0] . 'Boston, MA';
          }
          else{
            $address = $address[0];
          }
          if($placename == '' || $address == ''){
            continue;
          }
          
          $place_query = mysql_query("SELECT * FROM places WHERE title='" . $placename . "' LIMIT 1");
          if(mysql_num_rows($place_query) != 1) {
            //$place_query = mysql_query("SELECT * FROM places WHERE address='" . $_escape($data[$addressfield]) . "' LIMIT 1");
            //if(mysql_num_rows($place_query) != 1) {
            echo 'new: ';
            $num = count($data);
            for ($c=0; $c < $num; $c++) {
              echo $fields[$c] . " = " . $data[$c];
            } 
            echo '<br/>';           
            //}

            $type = $data[$typefield];
            $description = explode( '\r', $data[$descriptionfield] );
            $description = explode( '\n', $description[0] );
            $description = $_escape($description[0]);

            $uri = '';
            $owner_name = '';
            $owner_email = '';

            $insert = mysql_query("INSERT INTO places (approved, title, type, address, uri, description, owner_name, owner_email) VALUES (1, '$placename', '$type', '$address', '$uri', '$description', '$owner_name', '$owner_email')") or die(mysql_error());

            // geocode new submission
            $hide_geocode_output = false;
            include "../geocode.php";
            
            continue;
          }
          $place = mysql_fetch_assoc($place_query);

          if( $place['address'] != $data[$addressfield] ){
            echo 'update address: ';
            $num = count($data);
            for ($c=0; $c < $num; $c++) {
              echo $fields[$c] . " = " . $data[$c];
            } 
            echo '<br/>';   
          }
          else{
            echo 'match<br/>';
          }
        }
        $row++;
      }
      fclose($handle);
    }
  }
  else{
   header("Location: index.php?found=" . $name );
  }
  exit;
}
?>

<? echo $admin_head; ?>

<form id="upload" class="form-horizontal" enctype="multipart/form-data" action="upload.php" method="post">
  <h1>
    Update places
  </h1>
  <fieldset>
    <input type="hidden" name="task" value="doupload"/>
    <input type="hidden" name="MAX_FILE_SIZE" value="300000000" />
    <div class="control-group">
      <label class="control-label" for="">CSV File</label>
      <br/>
      <div class="controls">
        <input type="file" name="csvfile"/>
      </div>
    </div>
    <div class="control-group">
      <div class="controls">
        <input type="submit" class="btn btn-primary" value="&uarr; Upload"/>
      </div>
    </div>
  </fieldset>
</form>



<? echo $admin_foot; ?>
