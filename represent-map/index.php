<?php
include_once "header.php";
?>

<!DOCTYPE html>
<html>
  <head>
    <!--
    This site was based on the Represent.LA project by:
    - Alex Benzer (@abenzer)
    - Tara Tiger Brown (@tara)
    - Sean Bonner (@seanbonner)
    
    Create a map for your startup community!
    https://github.com/abenzer/represent-map
    -->
    <title>Meet Boston Startups</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta charset="UTF-8">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:700|Open+Sans:400,700' rel='stylesheet' type='text/css'>
    <link href="./bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="map.css" type="text/css" />
    <link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" />
    <link rel="stylesheet" href="datepicker.css" type="text/css" />
    <script src="./scripts/jquery-1.7.1.js" type="text/javascript" charset="utf-8"></script>
    <script src="./bootstrap/js/bootstrap.js" type="text/javascript" charset="utf-8"></script>
    <script src="./bootstrap/js/bootstrap-typeahead.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="./scripts/markerclusterer.js"></script>
    <script type="text/javascript" src="./scripts/bootstrap-datepicker.js"></script>
    
    <script type="text/javascript">
      var map;
      var infowindow = null;
      var gmarkers = [];
      var markerTitles =[];
      var highestZIndex = 0;  
      var agent = "default";
      var zoomControl = true;
      var individualMarker = false;
      var markerCluster;

      // detect browser agent
      $(document).ready(function(){
        if(navigator.userAgent.toLowerCase().indexOf("iphone") > -1 || navigator.userAgent.toLowerCase().indexOf("ipod") > -1) {
          agent = "iphone";
          zoomControl = false;
        }
        if(navigator.userAgent.toLowerCase().indexOf("ipad") > -1) {
          agent = "ipad";
          zoomControl = false;
        }
      }); 
      

      // resize marker list onload/resize
      $(document).ready(function(){
        resizeList() 
      });
      $(window).resize(function() {
        resizeList();
      });
      
      // resize marker list to fit window
      function resizeList() {
        newHeight = $('html').height() - $('#topbar').height();
        $('#list').css('height', newHeight + "px"); 
        $('#menu').css('margin-top', $('#topbar').height()); 
      }


      // initialize map
      function initialize() {
        // set map styles
        var mapStyles = [
         {
            featureType: "road",
            elementType: "geometry",
            stylers: [
              { hue: "#8800ff" },
              { lightness: 100 }
            ]
          },{
            featureType: "road",
            stylers: [
              { visibility: "on" },
              { hue: "#91ff00" },
              { saturation: -62 },
              { gamma: 1.98 },
              { lightness: 45 }
            ]
          },{
            featureType: "water",
            stylers: [
              { hue: "#005eff" },
              { gamma: 0.72 },
              { lightness: 42 }
            ]
          },{
            featureType: "transit.line",
            stylers: [
              { visibility: "off" }
            ]
          },{
            featureType: "administrative.locality",
            stylers: [
              { visibility: "on" }
            ]
          },{
            featureType: "administrative.neighborhood",
            elementType: "geometry",
            stylers: [
              { visibility: "simplified" }
            ]
          },{
            featureType: "landscape",
            stylers: [
              { visibility: "on" },
              { gamma: 0.41 },
              { lightness: 46 }
            ]
          },{
            featureType: "administrative.neighborhood",
            elementType: "labels.text",
            stylers: [
              { visibility: "on" },
              { saturation: 33 },
              { lightness: 20 }
            ]
          },
          { "featureType": "poi", "elementType": "labels", "stylers": [ { "visibility": "off" } ] }
        ];

        // set map options
        var myOptions = {
          zoom: 15,
          minZoom: 10,
          center: new google.maps.LatLng( 42.34933,-71.032469 ),
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          streetViewControl: false,
          mapTypeControl: false,
          panControl: false,
          zoomControl: zoomControl,
          styles: mapStyles,
          zoomControlOptions: {
            style: google.maps.ZoomControlStyle.SMALL,
            position: google.maps.ControlPosition.LEFT_CENTER
          }
        };
        map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
        
        map.fitBounds( new google.maps.LatLngBounds( new google.maps.LatLng(42.343472, -71.05295), new google.maps.LatLng(42.354699, -71.015) ) );
        
        var osmMapLayer = new google.maps.ImageMapType({
          getTileUrl: function(coord, zoom) {
            return "http://tiles1.skobbler.net/osm_tiles2/" + zoom + "/" + coord.x + "/" + coord.y + ".png";
          },
          tileSize: new google.maps.Size(256, 256),
          isPng: true,
          alt: "Skobbler GmbH",
          name: "Skobbler GmbH",
          maxZoom: 19
        });
        map.mapTypes.set('Skobbler',osmMapLayer);
        map.setMapTypeId('Skobbler');

        zoomLevel = map.getZoom();

        // prepare infowindow
        infowindow = new google.maps.InfoWindow();

        // markers array: name, type (icon), lat, long, description, uri, address
        markers = new Array();
        <?php
          $types = Array(
              Array('#e418ac', 'Innovation Spaces'),
              Array('#bb25e2','Tech'),
              Array('#6831e0', 'Creative'), 
              Array('#3d57de', 'Life Science'),
              Array('#49a8dd', 'Professional Services'),
              Array('#54dbcb', 'Cultural and Educational'),
              Array('#60d991', 'Food and Retail'),
              Array('#73d76b', 'Institutional and Non-Profit'),
              Array('#abd576', 'Industrial'),
              /* Array('#d4d181', 'Food and Retail'), */
              Array('#d49779', 'Other')
              );
          $marker_id = 0;
          
          $_linefix = function ($str){
            $str = str_replace( "\r", "<br>", $str );
            $str = str_replace( "\n", "<br>", $str );
            $firstwords = explode( $str, " " );
            if( sizeof( $firstwords ) > 4 ){
              $firstwords = $firstwords[0] . " " . $firstwords[1];
              if(strpos( $str, $firstwords ) < strrpos( $str, $firstwords ) ){
                // assume a repeat
                $str = explode( $str, $firstwords );
                $str = $firstwords . $str[0];
              }
            }
            return $str;
         };
          
          foreach($types as $type) {
            $places = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[1]' ORDER BY title");
            $places_total = mysql_num_rows($places);
            while($place = mysql_fetch_assoc($places)) {
              $place[title] = htmlspecialchars_decode(addslashes(htmlspecialchars($_linefix($place[title]))));
              if($place[lat] == 0 && $place[lng] == 0){
                // bad location
                echo "/* bad location: " . $place[title] . " */";
                continue;
              }
              $place[description] = htmlspecialchars_decode(addslashes(htmlspecialchars($_linefix($place[description]))));
              $place[uri] = addslashes(htmlspecialchars($_linefix($place[uri])));
              $place[address] = htmlspecialchars_decode(addslashes(htmlspecialchars($_linefix($place[address]))));
              echo "
                markers.push(['".$place[title]."', '".$place[type]."', '".$place[lat]."', '".$place[lng]."', '".$place[description]."', '".$place[uri]."', '".$place[address]."', ".$place[id].", '".$place[hiring]."', '".$place[hirelink]."']); 
                markerTitles[".$marker_id."] = '".$place[title]."';
              "; 
              $count[$place[type]]++;
              $marker_id++;
            }
          }
          /* 
          if($show_events == true) {
            $place[type] = "event";
            $events = mysql_query("SELECT * FROM events WHERE start_date > ".time()." AND start_date < ".(time()+4838400)." ORDER BY id DESC");
            $events_total = mysql_num_rows($events);
            while($event = mysql_fetch_assoc($events)) {
              $event[title] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[title])));
              $event[description] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[description])));
              $event[uri] = addslashes(htmlspecialchars($event[uri]));
              $event[address] = htmlspecialchars_decode(addslashes(htmlspecialchars($event[address])));
              $event[start_date] = date("D, M j @ g:ia", $event[start_date]);
              echo "
                markers.push(['".$event[title]."', 'event', '".$event[lat]."', '".$event[lng]."', '".$event[start_date]."', '".$event[uri]."', '".$event[address]."']); 
                markerTitles[".$marker_id."] = '".$event[title]."';
              "; 
              $count[$place[type]]++;
              $marker_id++;
            }
          }
          */
        ?>

        // add markers
        jQuery.each(markers, function(i, val) {

          // offset latlong ever so slightly to prevent marker overlap
          rand_x = Math.random();
          rand_y = Math.random();
          val[2] = parseFloat(val[2]) + parseFloat(parseFloat(rand_x) / 6000);
          val[3] = parseFloat(val[3]) + parseFloat(parseFloat(rand_y) / 6000);

          // show smaller marker icons on mobile
          if(agent == "iphone") {
            var iconSize = new google.maps.Size(16,19);
          } else {
            iconSize = null;
          }

          var markerColor = {
              'Innovation Spaces': '#e418ac',
              'Tech': '#bb25e2',
              'Creative': '#6831e0',
              'Life Science': '#3d57de',
              'Professional Services': '#49a8dd',
              'Cultural and Educational': '#54dbcb',
              'Food and Retail': '#60d991',
              'Institutional and Non-Profit': '#73d76b',
              'Industrial': '#abd576',
              'Other': '#d49779'
          };
          if(val[1] == "other" || typeof markerColor[val[1]] == "undefined"){
            val[1] = "Other";
          }
          var marker = new google.maps.Circle({
            center: new google.maps.LatLng(val[2],val[3]),
            // map: map,
            clickable: true,
            infoWindowHtml: '',
            zIndex: 10 + i,
            fillColor: markerColor[ val[1] ],
            strokeColor: "#fff",
            strokeOpacity: 0,
            strokeWidth: 0,
            fillOpacity: 0.5,
            radius: 50
          });
          marker.type = val[1];
          gmarkers.push(marker);

          // format marker URI for display and linking
          var markerURI = val[5];
          if(markerURI.substr(0,7) != "http://") {
            markerURI = "http://" + markerURI; 
          }
          var markerURI_short = markerURI.replace("http://", "");
          var markerURI_short = markerURI_short.replace("www.", "");

          // add marker click effects (open infowindow)
          google.maps.event.addListener(marker, 'click', function (){
            var manyMarkers=getNearbyMarkers(marker.getCenter());
            if((manyMarkers.length > 1 && !individualMarker) && ( manyMarkers.length != 2 || manyMarkers[0].id != manyMarkers[1].id )){
              var pageViewer="<div style='min-width:280px;'><div style='margin-left:auto;margin-right:auto;'>Many at this location: <a href='#' onclick='map.setOptions({center:new google.maps.LatLng(" + marker.getCenter().lat() + ","+ marker.getCenter().lng() + "),zoom:"+(map.getZoom()+2)+"});infowindow.close();'>Zoom</a><br/>";
              var tablesOn=false;
              if(manyMarkers.length > 10){
                tablesOn=true;
                pageViewer+="<table><tr><td>";
              }
              pageViewer+="<ul>";
              for(var mPt=0;mPt<manyMarkers.length;mPt++){
                if((tablesOn)&&(mPt%10==0)&&(mPt!=0)){
                  if(mPt > 30){break;}
                  pageViewer+='</ul></td><td><ul>';
                }
                pageViewer+='<li><a href="#" onclick="openMarker('+manyMarkers[mPt].id+');return false;">'+markerTitles[ manyMarkers[mPt].id ]+'</a></li>';
              }
              pageViewer+="</ul>";
              if(tablesOn){
                pageViewer+="</td></tr></table>";
              }
              infowindow.setContent(pageViewer+"</div></div>");
            }
            else{
              individualMarker = false;
              var content = "<div class='marker_title'>"+val[0]+"</div>"
                + "<div class='marker_uri'><a target='_blank' href='"+markerURI+"'>"+markerURI_short+"</a></div>"
                + "<div class='marker_desc'>"+val[4]+"</div>";
                
              // check for hiring info
              var dbid = val[7];
              var hiring = val[8];
              var hirelink = val[9];
              if(hiring && hiring.length && hiring == '2'){
                // approved job link
                content += "<div class='hiring'><a href='" + hirelink + "' target='_blank'>Hiring Now!</a></div>";
              }
              
              content += "<div class='marker_address'>"+val[6]+"</div>";
              
              infowindow.setContent(content);
            }
            infowindow.setPosition( marker.getCenter() );
            infowindow.open(map);
          });
        });

        // zoom to marker if selected in search typeahead list
        $('#search').typeahead({
          source: markerTitles, 
          onselect: function(obj, obj2) {
            marker_id = jQuery.inArray(obj, markerTitles);
            if(marker_id > -1) {
              map.panTo(gmarkers[marker_id].getCenter());
              map.setZoom(15);
              individualMarker = true;
              google.maps.event.trigger(gmarkers[marker_id], 'click');
            }
            $("#search").val("");
          }
        });
        $('.searchbar').typeahead({
          source: markerTitles, 
          onselect: function(obj) {
            marker_id = jQuery.inArray(obj, markerTitles);
            if(marker_id > -1){
              $('#searchoutcome').val( markers[marker_id][7] );
              $('#jobsubmit')[0].disabled = false;
              $('#jobsubmit').removeClass("disabled");
            }
          }
        });
        $('#hiredate').datepicker({
          format: 'mm-dd-yyyy',
          startDate: new Date(),
          endDate: new Date( (new Date()) * 1 + 365 * 24 * 60 * 60 * 1000 ),
          todayHighlight: true,
          autoclose: true
        })
        .on('changeDate', function(ev){
          $('#unixhiredate').val( (new Date($('#hiredate').val()) * 1) );
        });
        
        // change circle size on zoom
        google.maps.event.addListener(map, 'zoom_changed', function(){
          if(map.getZoom() > 14){
            for(var i=0;i<gmarkers.length;i++){
              if(typeof gmarkers[i].setRadius == "function"){
                gmarkers[i].setRadius( Math.round( 50 / Math.pow( 2, (map.getZoom() - 14) / 2 ) ) );
              }
            }
          }
        });
        
        markerCluster = new MarkerClusterer(map, gmarkers, { zoomOnClick: false });
      }

      function getNearbyMarkers(latlng){
        var nMarkers=[];
        var zoomFactor = 2.5 * Math.max(1, Math.pow(2,15-map.getZoom()) );
        for(var mPt=0;mPt<gmarkers.length;mPt++){
          if(!gmarkers[mPt].visible){
            continue;
          }
          if( Math.abs(latlng.lat() - gmarkers[mPt].getCenter().lat()) < ( 0.0001 * zoomFactor )){
            if( Math.abs(latlng.lng() - gmarkers[mPt].getCenter().lng()) < ( 0.0001 * zoomFactor )){
              nMarkers.push({
                marker: gmarkers[mPt],
                id: mPt
              });
            }
          }
        }
        return nMarkers;
      }

      // open specific marker
      function openMarker(marker_id) {
        if(marker_id) {
          individualMarker = true;
          google.maps.event.trigger(gmarkers[marker_id], 'click');
        }
      }

      // zoom to specific marker
      function goToMarker(marker_id) {
        if(marker_id) {
          map.panTo(gmarkers[marker_id].getCenter());
          map.setZoom( Math.max(17, map.getZoom()) );
          individualMarker = true;
          google.maps.event.trigger(gmarkers[marker_id], 'click');
        }
      }

      // toggle (hide/show) markers of a given type (on the map)
      function toggle(type) {
        if($('.filter_'+type.split(" ")[0]).is('.inactive')) {
          show(type); 
        } else {
          hide(type); 
        }
      }

      // hide all markers of a given type
      function hide(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(false);
          }
        }
        $(".filter_"+type.split(" ")[0]).addClass("inactive");
        markerCluster.redraw();
      }

      // show all markers of a given type
      function show(type) {
        for (var i=0; i<gmarkers.length; i++) {
          if (gmarkers[i].type == type) {
            gmarkers[i].setVisible(true);
          }
        }
        $(".filter_"+type.split(" ")[0]).removeClass("inactive");
        markerCluster.redraw();
      }
      
      // toggle (hide/show) marker list of a given type
      function toggleList(type) {
        $(".list-"+type.split(" ")[0]).toggle();
      }

      // hover on list item
      function markerListMouseOver(marker_id) {
        $("#marker"+marker_id).css("display", "inline");
      }
      function markerListMouseOut(marker_id) {
        $("#marker"+marker_id).css("display", "none");
      }

      google.maps.event.addDomListener(window, 'load', initialize);
    </script>
    
    <? echo $head_html; ?>
  </head>
  <body>
    
    <!-- display error overlay if something went wrong -->
    <?php echo $error; ?>
    
    <!-- facebook like button code -->
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=421651897866629";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>
    
    <!-- google map -->
    <div id="map_canvas"></div>
    
    <!-- topbar -->
    <div class="topbar" id="topbar">
      <div class="wrapper">
        <div class="right">
          <div class="share">
            <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://bostonstartups.aws.af.cm/" data-text="Meet Boston's startups:" data-via="notifyboston" data-count="none">Tweet</a>
            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
            <div class="fb-like" data-href="http://bostonstartups.aws.af.cm/" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false" data-font="arial"></div>
            <a href="api.php" class="btn btn-inverse"><i class="icon-info-sign icon-white"></i>&nbsp;API</a>
          </div>
        </div>
        <div class="left">
          <div class="logo">
            <a href="./">
              <img src="images/bostonlogo.png" style="height:42px;" alt="City of Boston" title="City of Boston" />
            </a>
          </div>
          <div class="logo logo2">
            <strong>Boston's Innovation District</strong>          
          </div>
          <div class="buttons">
            <a href="#modal_add" class="btn btn-large btn-success" data-toggle="modal"><i class="icon-plus-sign icon-white"></i>Add Company</a>
            <a href="#modal_jobs" class="btn btn-large btn-success" data-toggle="modal"><i class="icon-plus-sign icon-white"></i>Add Job</a>
            <a href="#modal_info" class="btn btn-large btn-info" data-toggle="modal"><i class="icon-info-sign icon-white"></i>About this Map</a>

          </div>
          <div class="search">
            <input type="text" name="search" id="search" placeholder="Search for companies..." data-provide="typeahead" autocomplete="off" />
          </div>
        </div>
      </div>
    </div>
    
    <!-- right-side gutter -->
    <div class="menu" id="menu">
      <ul class="list" id="list">
        <?php
          $types = Array(
              Array('#e418ac', 'Innovation Spaces'),
              Array('#bb25e2','Tech'),
              Array('#6831e0', 'Creative'), 
              Array('#3d57de', 'Life Science'), 
              Array('#49a8dd', 'Professional Services'),
              Array('#54dbcb', 'Cultural and Educational'),
              Array('#60d991', 'Food and Retail'),
              Array('#73d76b', 'Institutional and Non-Profit'),
              Array('#abd576', 'Industrial'),
              Array('#d49779', 'Other')
              );
          /*
          if($show_events == true) {
            $types[] = Array('event', 'Events'); 
          }
          */
          $marker_id = 0;
          foreach($types as $type) {
            if($type[0] != "event") {
              $markers = mysql_query("SELECT * FROM places WHERE approved='1' AND type='$type[1]' ORDER BY title");
            } else {
              $markers = mysql_query("SELECT * FROM events WHERE start_date > ".time()." AND start_date < ".(time()+4838400)." ORDER BY id DESC");
            }
            $markers_total = mysql_num_rows($markers);
            echo "
              <li class='category'>
                <div class='category_item'>
                  <div class='category_toggle filter_$type[1]' onClick=\"toggle('$type[1]')\"></div>
                  <a href='#' onClick=\"toggleList('$type[1]');\" class='category_info'><span style='background-color:$type[0];color:$type[0];margin-right:10px;'>___</span><small>$type[1]</small><span class='total'> ($markers_total)</span></a>
                </div>
                <ul class='list-items list-$type[1]'>
            ";
            while($marker = mysql_fetch_assoc($markers)) {
              echo "
                  <li class='".$marker[type]."'>
                    <a href='#' onMouseOver=\"markerListMouseOver('".$marker_id."')\" onMouseOut=\"markerListMouseOut('".$marker_id."')\" onClick=\"goToMarker('".$marker_id."');\">".$marker[title]."</a>
                  </li>
              ";
              $marker_id++;
            }
            echo "
                </ul>
              </li>
            ";
          }
        ?>
        <li class="blurb">
          This map was made to connect and promote the Boston tech startup community.
        </li>
        <li class="attribution">
          <!-- per our license, you may not remove this line -->
          <?=$attribution?>
        </li>
      </ul>
    </div>
    
    <!-- more info modal -->
    <div class="modal hide" id="modal_info">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>About this Map</h3>
      </div>
      <div class="modal-body">
        <p>Welcome! We built this map to help build connections in Boston’s growing Innovation District. The Innovation District is Mayor Thomas M. Menino's initiative to transform 1,000 acres of the South Boston waterfront into an urban environment that fosters innovation, collaboration, and entrepreneurship. From a tech meet-up at a co-working space to an art exhibition, or the launch of a new start-up or a special chef's event at a local restaurant, the Innovation District is expanding quickly across a variety of sectors.</p>
        <p>We intend this map to be your guide to all of the great resources the Innovation District has to offer. Whether you want to explore an industry as a whole, find a specific company, or just browse for a new lunch spot, this map is for you. Additional layers of data can give you insight into future development projects, new housing options, and other amenities currently in place in our district.</p>
        <p>Want to know where to start? Here’s a few ways you can use this map:
        <ul>
          <li>Explore your surroundings. Discover everything your neighbors and community have to offer. Visit our map here.</li>
          <li>Find a job. Love to collaborate and create? We're always looking for people to join our innovation community.  Use this map to see which companies are currently hiring.</li>
          <li>Add your company. Don’t see your business currently listed? Keep our map fresh and submit your information here.</li>
        </ul>
        </p>
        <p>Questions? Feedback? Connect with us: <a href="http://www.innovationdistrict.org/contact/" target="_blank">http://www.innovationdistrict.org/contact/</a></p>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
      </div>
    </div>
    
    
    <!-- add something modal -->
    <div class="modal hide" id="modal_add">
      <form action="add.php" id="modal_addform" class="form-horizontal">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h3>Add something!</h3>
        </div>
        <div class="modal-body">
          <div id="result"></div>
          <fieldset>
            <div class="control-group">
              <label class="control-label" for="add_owner_name">Your Name</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_name" id="add_owner_name" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_owner_email">Your Email</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_email" id="add_owner_email" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_title">Company Name</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="title" id="add_title" maxlength="100" autocomplete="off">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="input01">Company Type</label>
              <div class="controls">
                <select name="type" id="add_type" class="input-xlarge">
                  <option value="Professional Services">Professional Services</option>
                  <option value="Tech">Tech</option>
                  <option value="Life Science">Life Science</option>
                  <option value="Industrial">Industrial</option>
                  <option value="Creative">Creative</option>
                  <option value="Cultural and Educational">Cultural and Educational</option>
                  <option value="Food and Retail">Food and Retail</option>
                  <option value="Innovation Spaces">Innovation Spaces</option>
                  <option value="Institutional and Non-Profit">Institutional and Non-Profit</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_address">Address</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="address" id="add_address">
                <p class="help-block">
                  Should be your <b>full street address (including city and zip)</b>.
                  If it works on Google Maps, it will work here.
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">Website URL</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_uri" name="uri" placeholder="http://">
                <p class="help-block">
                  Your full URL e.g. "http://www.yoursite.com"
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">Number of Employees</label>
              <div class="controls">
                <input type="text" class="input-large" id="employeenum" name="employeenum"/>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_description">Description</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="add_description" name="description" maxlength="150">
                <p class="help-block">
                  Brief, concise description. Max 150 chars.
                </p>
              </div>
            </div>
          </fieldset>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Submit for Review</button>
          <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
        </div>
      </form>
    </div>
    <script>
      // add modal form submit
      $("#modal_addform").submit(function(event) {
        event.preventDefault(); 
        // get values
        var $form = $( this ),
            owner_name = $form.find( '#add_owner_name' ).val(),
            owner_email = $form.find( '#add_owner_email' ).val(),
            title = $form.find( '#add_title' ).val(),
            type = $form.find( '#add_type' ).val(),
            address = $form.find( '#add_address' ).val(),
            uri = $form.find( '#add_uri' ).val(),
            description = $form.find( '#add_description' ).val(),
            employeenum = $form.find( '#employeenum' ).val(),
            url = $form.attr( 'action' );

        // send data and get results
        $.post( url, { owner_name: owner_name, owner_email: owner_email, title: title, type: type, address: address, uri: uri, description: description, employeenum: employeenum },
          function( data ) {
            var content = $( data ).find( '#content' );
            
            // if submission was successful, show info alert
            if(data == "success") {
              $("#modal_addform #result").html("We've received your submission and will review it shortly. Thanks!"); 
              $("#modal_addform #result").addClass("alert alert-info");
              $("#modal_addform p").css("display", "none");
              $("#modal_addform fieldset").css("display", "none");
              $("#modal_addform .btn-primary").css("display", "none");
              
            // if submission failed, show error
            } else {
              $("#modal_addform #result").html(data); 
              $("#modal_addform #result").addClass("alert alert-danger");
            }
          }
        );
      });
    </script>

    <!-- add job modal -->
    <div class="modal hide" id="modal_jobs">
      <form action="addhiring.php" id="modal_addjob" class="form-horizontal">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h3>You're hiring!</h3>
        </div>
        <div class="modal-body">
          <div id="result"></div>
          <fieldset>
            <div class="control-group">
              <label class="control-label" for="add_title">Company Name</label>
              <div class="controls">
                <div class="search">
                  <input id="searchoutcome" type="hidden" name="id" value=""/>
                  <input type="text" class="searchbar" placeholder="Search for companies..." data-provide="typeahead" autocomplete="off" />
                </div>
                <p class="help-block">
                  Your company must be added to the map first!
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_owner_name">Your Name</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_name" id="add_owner_name" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_owner_email">Your Email</label>
              <div class="controls">
                <input type="text" class="input-xlarge" name="owner_email" id="add_owner_email" maxlength="100">
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">Job URL</label>
              <div class="controls">
                <input type="text" class="input-xlarge" id="hirelink" name="hirelink" placeholder="http://">
                <p class="help-block">
                  Should be a link to your careers page, including http:// "http://www.yoursite.com/careers"
                </p>
              </div>
            </div>
            <div class="control-group">
              <label class="control-label" for="add_uri">Hire date</label>
              <div class="controls">
                <input id="unixhiredate" type="hidden" name="hiredate"/>
                <input type="text" id="hiredate"/>
                <p class="help-block">
                  We'll review the job and list it on the site. When should we stop listing the job?
                </p>
              </div>
            </div>
          </fieldset>
        </div>
        <div class="modal-footer">
          <button id="jobsubmit" type="submit" disabled="disabled" class="btn btn-primary disabled">Submit for Review</button>
          <a href="#" class="btn" data-dismiss="modal" style="float: right;">Close</a>
        </div>
      </form>
    </div>
    <script>
      // add modal form submit
      $("#modal_addjob").submit(function(event) {
        event.preventDefault(); 
        // get values
        var $form = $( this ),
            //owner_name = $form.find( '#job_owner_name' ).val(),
            //owner_email = $form.find( '#job_owner_email' ).val(),
            id = $form.find( '#searchoutcome' ).val(),
            hirelink = $form.find( '#hirelink' ).val(),
            hiredate = $form.find( '#unixhiredate' ).val(),            
            url = $form.attr( 'action' );

        // send data and get results
        $.post( url,
          {
            id: id,
            hirelink: hirelink,
            hiredate: hiredate
          },
          function( data ) {
            console.log( data );
            var content = $( data ).find( '#content' );
            
            // if submission was successful, show info alert
            if(data == "success") {
              $("#modal_addjob #result").html("We've received your submission and will review it shortly. Thanks!"); 
              $("#modal_addjob #result").addClass("alert alert-info");
              $("#modal_addjob p").css("display", "none");
              $("#modal_addjob fieldset").css("display", "none");
              $("#modal_addjob .btn-primary").css("display", "none");
              
            // if submission failed, show error
            } else {
              $("#modal_addjob #result").html(data); 
              $("#modal_addjob #result").addClass("alert alert-danger");
            }
          }
        );
      });
    </script>
    
  </body>
</html>
