<?php
session_start();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
   <title>SAR Planner</title>
   <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA3ZZQ3BeOtUTYd09U6I5ExRTGZfsDOAqaP_s50jDZ_W6ABA8j8RSkFaM_H3oi-RcbukqLWpEQCWIfFw" type="text/javascript"></script>
   <script src="prototype.js" type="text/javascript"></script>
   <script type="text/javascript">
    //<![CDATA[

    function map_start() {
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
        map.setCenter(new GLatLng(41.2605, -96.0130), 4);
      }
    }

    function plotSARroute() {
       $('input').hide();
       var csp     = $F('csp').split(', ');
           csp[0]  = parseFloat(csp[0]);
           csp[1]  = parseFloat(csp[1]);
       var pattern = $F('pattern');
       var major   = parseFloat($F('major'));
       var minor   = parseFloat($F('minor'));
       var track   = parseFloat($F('track'));
       var space   = parseFloat($F('space'));
       var numlegs = parseFloat($F('numlegs'));
       var rearth  = 6371;

       if ( pattern == "Parallel" || pattern == "Creep Line" ) {
         if ( pattern == "Creep Line" ) {
            major = major + minor;   // This just swaps the major and minor axis
            minor = major - minor;
            major = major - minor;
         }

         var waypoint = new Array(2*numlegs);
         for ( var i = 0; i < waypoint.length; i++ ) {
            waypoint[i] = new Array(2);
         }

         waypoint[0] = csp;

         for ( i=1; i < 2*numlegs; i++ ) {
            if ( i%2 == 0 ) { // Start of leg
               bearing = minor;
               distance = space;
            }
            else { // End of leg
               if ( (i-1)%4 == 0 ) { // Down leg
                  bearing = major;
                  distance = track;
               }
               else { // Up leg
                  if ( major >= 180 ) { bearing = major-180; }
                  else { bearing = major+180; }
                  distance = track;
               }
            }
  
            lat1 = waypoint[i-1][0] * Math.PI / 180 ;
            lon1 = waypoint[i-1][1] * Math.PI / 180 ;
            bearing *= Math.PI / 180;
            distance *= 1.852;
  
            lat2 = Math.asin( Math.sin(lat1)*Math.cos(distance/rearth) + Math.cos(lat1)*Math.sin(distance/rearth)*Math.cos(bearing) );
            lon2 = lon1 + Math.atan2(Math.sin(bearing)*Math.sin(distance/rearth)*Math.cos(lat1),
                                     Math.cos(distance/rearth)-Math.sin(lat1)*Math.sin(lat2));
          
            waypoint[i][0] = lat2 * 180 / Math.PI;
            waypoint[i][1] = lon2 * 180 / Math.PI;
         }
       }
       else if ( pattern == "Sector Search" ) {

         var waypoint = new Array(8);
         for ( var i = 0; i < waypoint.length; i++ ) {
            waypoint[i] = new Array(2);
         }

         waypoint[0] = csp;
         waypoint[7] = csp;

         var heading = major;

         for ( i=1; i <= 6; i++ ) {
            if ( i == 3 || i == 5 ) { // Long legs
               distance = 2*track;
            }
            else { // End of leg
               distance = track;
            }
  
            lat1 = waypoint[i-1][0] * Math.PI / 180 ;
            lon1 = waypoint[i-1][1] * Math.PI / 180 ;
            bearing = heading * Math.PI / 180;
            distance *= 1.852;
  
            lat2 = Math.asin( Math.sin(lat1)*Math.cos(distance/rearth) + Math.cos(lat1)*Math.sin(distance/rearth)*Math.cos(bearing) );
            lon2 = lon1 + Math.atan2(Math.sin(bearing)*Math.sin(distance/rearth)*Math.cos(lat1),
                                     Math.cos(distance/rearth)-Math.sin(lat1)*Math.sin(lat2));
          
            waypoint[i][0] = lat2 * 180 / Math.PI;
            waypoint[i][1] = lon2 * 180 / Math.PI;

            heading += 120;
            while ( heading >= 360 ) { heading -= 360; }
         }
       }
       else if ( pattern == "Expanding Square" ) {

        var waypoint = new Array(numlegs+1);
         for ( var i = 0; i < waypoint.length; i++ ) {
            waypoint[i] = new Array(2);
         }

         waypoint[0] = csp;

         var heading = major;

         for ( i=1; i <= numlegs; i++ ) {
            distance = track * Math.ceil(i/2);

            lat1 = waypoint[i-1][0] * Math.PI / 180 ;
            lon1 = waypoint[i-1][1] * Math.PI / 180 ;
            bearing = heading * Math.PI / 180;
            distance *= 1.852;

            lat2 = Math.asin( Math.sin(lat1)*Math.cos(distance/rearth) + Math.cos(lat1)*Math.sin(distance/rearth)*Math.cos(bearing) );
            lon2 = lon1 + Math.atan2(Math.sin(bearing)*Math.sin(distance/rearth)*Math.cos(lat1),
                                     Math.cos(distance/rearth)-Math.sin(lat1)*Math.sin(lat2));

            waypoint[i][0] = lat2 * 180 / Math.PI;
            waypoint[i][1] = lon2 * 180 / Math.PI;

            heading -= 90;
            while ( heading < 0 ) { heading += 360; }
         }



       }
       else if ( pattern == "Single Point" ) {

       }
       else {

       }

       if (GBrowserIsCompatible()) {
         // Find center
         var lat     = csp[0];
         var long    = csp[1];

         // How far to zoom?
         var zoomlevel = 7;

         var map = new GMap2(document.getElementById("map"));
         map.setCenter(new GLatLng(lat, long), zoomlevel);
         map.addControl(new GLargeMapControl());

         map.addOverlay(new GMarker(new GPoint(csp[1],csp[0])));

         for ( i=1; i < waypoint.length; i++ ) {  // alert(waypoint[i][0]+', '+waypoint[i][1]);
//            map.addOverlay(new GMarker(new GPoint(waypoint[i][1],waypoint[i][0])));
            map.addOverlay(new GPolyline([new GLatLng(waypoint[i-1][0],waypoint[i-1][1]), new GLatLng(waypoint[i][0],waypoint[i][1])], "#ff0000", 10));
         }
       }
    }

    function select_pattern() {
       var pattern = $F('pattern');
       if ( pattern == "Parallel" || pattern == "Creep Line" ) {
         $('pattern_details').show();
         $('plotbutton').disabled=false;
         $('minor').disabled=false;
         $('space').disabled=false;
         $('numlegs').disabled=false;
       }
       else if ( pattern == "Sector Search" ) {
         $('pattern_details').show();
         $('plotbutton').disabled=false;
         $('minor').disabled=true;
         $('space').disabled=true;
         $('numlegs').disabled=true;
       }
       else if ( pattern == "Expanding Square" ) {
         $('pattern_details').show();
         $('plotbutton').disabled=false;
         $('minor').disabled=true;
         $('space').disabled=true;
         $('numlegs').disabled=false;
       }
       else if ( pattern == "Single Point" ) {
         $('pattern_details').hide();
         $('plotbutton').disabled=false;
       }
       else {
         $('pattern_details').hide();
         $('plotbutton').disabled=true;
       }
    }

    //]]>
   </script>
   <style rel="stylesheet" type="text/css">
    #title {
       width:  900px;
       margin: auto;
    }
    #input_holder {
       width:  900px;
       margin: auto;
    }
    #input {
       opacity: 0.9;
       width:  375px;
       position: absolute;
       z-index: 100;
       background: #ffffff;
       border: thin solid #000000;
    }
    #map {
       width:  900px;
       height: 500px;
       margin: auto;
       border: thin solid #000000;
    }
    #patterndetail {
       width:  900px;
       height: 200px;
       margin: auto;
       overflow:auto;
    }
    #patterndetail td {
       text-align: center;
    }
    #copyright {
       font-size: x-small;
       width:  900px;
       margin: auto;
    }
    h1 {
       font-size: x-large;
       margin: 0px;
    }
    h2 {
       font-size: large;
       text-align: center;
       margin: 0px;
    }
   </style>
  </head>
  <body onload="map_start()" onunload="GUnload()">
     <div id="title">
      <h1>SAR Pattern Plotter</h1>
     </div>
     <div id="input_holder">
      <div id="input" style="display:none">
       <h2>Plot Search Pattern</h2>
       <form>
       <table style="margin-left:auto;margin-right:auto;">
        <tr>
         <td>Commence Search Point (CSP)</td>
         <td><input name="csp" id="csp" value="41.2605, -96.0130" /></td>
        </tr>
        <tr>
         <td>Pattern Type</td>
         <td><select name="pattern" id="pattern" onChange="select_pattern();">
             <option selected>&nbsp;</option>
             <option>Single Point</option>
             <option>Parallel</option>
             <option>Creep Line</option>
             <option>Sector Search</option>
             <option>Expanding Square</option>
             </select></td>
        </tr>
        <tr>
         <td colspan=2>
          <div id="pattern_details" style="display:none">
           <hr>
           <table>
            <tr>
             <td>Major Axis</td>
             <td><input name="major" id="major" value="180" /></td>
            </tr>
            <tr>
             <td>Minor Axis</td>
             <td><input name="minor" id="minor" value="90" /></td>
            </tr>
            <tr>
             <td>Track (nm)</td>
             <td><input name="track" id="track" /></td>
            </tr>
            <tr>
             <td>Space (nm)</td>
             <td><input name="space" id="space" /></td>
            </tr>
            <tr>
             <td>Number of Search Legs</td>
             <td><select name="numlegs" id="numlegs">
<?php for ($i=1;$i<=20;$i++) echo "                 <option>$i</option>\n"; ?>
                 </select>
             </td>
            </tr>
           </table>
          <hr>
         </div>
        </td>
       </tr>
       <tr>
        <td colspan=2 style="text-align:right;"><input id="plotbutton" type="button" value="Plot SAR Route" onClick="plotSARroute();" disabled><input type="button" value="Cancel" onClick="$('input').hide();">
        </td>
       </tr>
      </table>
      </form>
     </div>
    </div>
    <div id="map"></div>
<?php

if ( 1 == 1 ) {

?>
    <div id="patterndetail" style="display:none;">
     <table width="100%">
      <tr>
       <th>ID</th>
       <th>Pattern Type</th>
       <th>Major Axis</th>
       <th>Minor Axis</th>
       <th>Track</th>
       <th>Spacing</th>
       <th>Legs</th>
       <th>Display On Map</th>
      </tr>
      <tr>
       <td>1</td>
       <td>Parallel</td>
       <td>180</td>
       <td>90</td>
       <td>20</td>
       <td>5</td>
       <td>10</td>
       <td><input type="checkbox"></td>
      </tr>
     </table>
    </div>
<?php } ?>
    <div id="copyright">
     <input type="button" value="Plot Search Pattern" onClick="$('input').toggle();" style="float:right;">
     <a href="http://www.gnu.org/copyleft/gpl.html"><img src="http://www.gnu.org/graphics/gplv3-88x31.png" style="float:left" border=0></a>
     Copyright &copy; 2009 <a href="http://nharmon.multics.org/">Nathan Harmon</a><br>
     Released under the <a href="http://www.gnu.org/copyleft/gpl.html">GNU General Public License</a>
    </div>
  </body>
</html>
