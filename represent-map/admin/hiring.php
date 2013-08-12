<?php
$page = "hiring";
include "header.php";


// hide hiring on map
if($task == "delist") {
  $place_id = htmlspecialchars($_GET['place_id']);
  mysql_query("UPDATE places SET hiring=0 WHERE id='$place_id'") or die(mysql_error());
  header("Location: index.php?view=$view&search=$search&p=$p");
  exit;
}

// show marker on map
if($task == "approve") {
  $place_id = htmlspecialchars($_GET['place_id']);
  mysql_query("UPDATE places SET hiring=2 WHERE id='$place_id'") or die(mysql_error());
  header("Location: index.php?view=$view&search=$search&p=$p");
  exit;
}

// paginate
$items_per_page = 15;
$page_start = ($p-1) * $items_per_page;
$page_end = $page_start + $items_per_page;

// get results
if($view == "hiring") {
  $places = mysql_query("SELECT * FROM places WHERE hiring=2 ORDER BY title LIMIT $page_start, $items_per_page");
  $total = $total_hiring;
}
else if($view == "pendinghiring") {
  $places = mysql_query("SELECT * FROM places WHERE hiring=1 ORDER BY title LIMIT $page_start, $items_per_page");
  $total = $total_hiringpending;
}

echo $admin_head;
?>


<div id="admin">
  <h3>
    <? if($total > $items_per_page) { ?>
      <?=$page_start+1?>-<? if($page_end > $total) { echo $total; } else { echo $page_end; } ?>
      of <?=$total?> markers
    <? } else { ?>
      <?=$total?> markers
    <? } ?>
  </h3>
  <ul>
    <?
      while($place = mysql_fetch_assoc($places)) {
        $place[uri] = str_replace("http://", "", $place[uri]);
        $place[uri] = str_replace("https://", "", $place[uri]);
        $place[uri] = str_replace("www.", "", $place[uri]);
        echo "
          <li>
            <div class='options'>
              ";
              if($place[hiring] == 2) {
                echo "
                  <a class='btn btn-small btn-success disabled'>Approve</a>
                  <a class='btn btn-small btn-inverse' href='hiring.php?task=delist&place_id=$place[id]&view=$view&search=$search&p=$p'>Delist</a>
                ";
              } else {
                echo "
                  <a class='btn btn-small btn-success' href='hiring.php?task=approve&place_id=$place[id]&view=$view&search=$search&p=$p'>Approve</a>
                  <a class='btn btn-small btn-inverse' href='hiring.php?task=delist&place_id=$place[id]&view=$view&search=$search&p=$p'>Delist</a>
                ";
              }
              echo "
            </div>
            <div class='place_info'>
              <a href='$place[hirelink]'>$place[title]</a>
            </div>
          </li>
        ";
      }
    ?>
  </ul>
  
  <? if($p > 1 || $total >= $items_per_page) { ?>
    <ul class="pager">
      <? if($p > 1) { ?>
        <li class="previous">
          <a href="index.php?view=<?=$view?>&search=<?=$search?>&p=<? echo $p-1; ?>">&larr; Previous</a>
        </li>
      <? } ?>
      <? if($total >= $items_per_page * $p) { ?>
        <li class="next">
          <a href="index.php?view=<?=$view?>&search=<?=$search?>&p=<? echo $p+1; ?>">Next &rarr;</a>
        </li>
      <? } ?>
    </ul>
  <? } ?>

</div>


<? echo $admin_foot ?>