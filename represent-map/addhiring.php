<?php
include_once "header.php";

// This is used to submit new jobs for review.
// Jobs won't appear on the map until they are approved.

$byid = parseInput($_POST['id']) * 1;
$hirelink = parseInput($_POST['hirelink']);
$hiredate = parseInput($_POST['hiredate']) * 1;

// insert into db, wait for approval
$update = mysql_query("UPDATE places SET hiring=1,hirelink='$hirelink',hiredate='$hiredate' WHERE id=$byid") or die(mysql_error());

// remove old hirings
$passed = time() * 1000;
$update = mysql_query("UPDATE places SET hiring=0,hirelink='',hiredate='' WHERE hiredate>$passed") or die(mysql_error());

echo "success";
exit;


?>
