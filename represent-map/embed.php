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
    <link rel="stylesheet" href="map.css?nocache=289671982568" type="text/css" />
    <link rel="stylesheet" media="only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" />
    <script src="./scripts/jquery-1.7.1.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="./scripts/markerclusterer.js"></script>
    
    <script type="text/javascript">
      var map;
      var infowindow = null;
      var gmarkers = [];
      var markerTitles =[];
      var highestZIndex = 0;  
      var agent = "default";
      var individualMarker = false;
      var markerCluster;

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
          scrollwheel: false,
          styles: mapStyles
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
                markers.push(['".$place[title]."', '".$place[type]."', '".$place[lat]."', '".$place[lng]."', '".$place[description]."', '".$place[uri]."', '".$place[address]."']); 
                markerTitles[".$marker_id."] = '".$place[title]."';
              "; 
              $count[$place[type]]++;
              $marker_id++;
            }
          }
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
              infowindow.setContent(
                "<div class='marker_title'>"+val[0]+"</div>"
                + "<div class='marker_uri'><a target='_blank' href='"+markerURI+"'>"+markerURI_short+"</a></div>"
                + "<div class='marker_desc'>"+val[4]+"</div>"
                + "<div class='marker_address'>"+val[6]+"</div>"
              );
            }
            infowindow.setPosition( marker.getCenter() );
            infowindow.open(map);
          });
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

      google.maps.event.addDomListener(window, 'load', initialize);
    </script>
    
    <? echo $head_html; ?>
  </head>
  <body>
    
    <!-- display error overlay if something went wrong -->
    <?php echo $error; ?>
    
    <!-- google map -->
    <div id="map_canvas"></div>

  </body>
</html>
