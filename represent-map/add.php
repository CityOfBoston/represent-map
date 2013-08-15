<?php
include_once "header.php";

// This is used to submit new markers for review.
// Markers won't appear on the map until they are approved.

$owner_name = parseInput($_POST['owner_name']);
$owner_email = parseInput($_POST['owner_email']);
$title = parseInput($_POST['title']);
$type = parseInput($_POST['type']);
$address = parseInput($_POST['address']);
$uri = parseInput($_POST['uri']);
$numemployees = parseInput($_POST['numemployees']) * 1;
$description = parseInput($_POST['description']);

// validate fields
if(empty($title) || empty($type) || empty($address) || empty($description)) {
  echo "All fields are required - please try again.";
  exit;
  
} else {
  
  
  
  // if startup genome mode enabled, post new data to API
  if($sg_enabled) {
    
    try {
      @$r = $http->doPost("/organization", $_POST);
      $response = json_decode($r, 1);
      if ($response['response'] == 'success') {
        include_once("startupgenome_get.php");
        echo "success"; 
        exit;
      }
    } catch (Exception $e) {
      echo "<pre>";
      print_r($e);
    }

  } else if( array_key_exists("lat", $_POST) ) {    
    // geocoded point being posted
    $lat = parseInput($_POST['lat']);
    $lng = parseInput($_POST['lng']);
    $insert = mysql_query("INSERT INTO places (lat, lng, approved, title, type, address, uri, description, owner_name, owner_email) VALUES ($lat, $lng, 1, '$title', '$type', '$address', '$uri', '$description', '$owner_name', '$owner_email')") or die(mysql_error());
    echo "posted";
    exit;
  
  } else {
    // normal mode enabled, save new data to local db
    
    // insert into db, wait for approval
    $insert = mysql_query("INSERT INTO places (approved, title, type, address, uri, description, owner_name, owner_email, numemployees) VALUES (null, '$title', '$type', '$address', '$uri', '$description', '$owner_name', '$owner_email', $numemployees)") or die(mysql_error());

    // geocode new submission
    $hide_geocode_output = true;
    include "geocode.php";
    
    echo "success";
    exit;
  
  }

  
}


?>
