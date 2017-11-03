{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if $showDirectly}
  {assign var=height value="450px"}
  {assign var=width  value="100%"}
{else}	
  {assign var=height value="600px"}
  {assign var=width  value="100%"}
{/if}
<div id="civicrm-google-map" class="event-map media_embed" style="width: {$width}; height: {$height}"></div>
<script src="{$config->resourceBase}packages/markerclusterer/markerclusterer.js"></script>
{literal}
<script type="text/javascript">
(function($){

// this is free embed map api key
var googleMapEmbed = 'https://www.google.com/maps/embed/v1/<MODE>?';
var googleMapKey = '{/literal}{if $mapKey}{$mapKey}{/if}{literal}';
var googleMapJS = 'https://maps.googleapis.com/maps/api/js?';
var mapWidth = '{/literal}{$width}{literal}';
var mapHeight = '{/literal}{$height}{literal}';
var mapId = '#civicrm-google-map';
var locations = [];
{/literal}{if $locationsJson}
locations = {$locationsJson};
{/if}{literal}

if (!locations.length) {
  return;
}
// embed api
else if (locations.length == 1) {
  // prepare parameters
  var location = locations[0];
  var parameters = [];
  var mode = 'place'; // currently only support place mode
  var src = '';
  if (typeof location.address) {
    q = location.address;
  }
  else if (typeof location.lat === 'string' && typeof location.lng === 'string') {
    q = location.lat + ',' + location.lng;
  }
  if (q) {
    q = encodeURIComponent(q);
    parameters.push('q='+q);
    if (typeof location.locationName !== 'undefined') {
      var source = encodeURIComponent(location.locationName);
      parameters.push('attribution_source='+source);
      parameters.push('attribution_web_url='+ encodeURIComponent(window.location.href));
      parameters.push('attribution_ios_deep_link_id=comgooglemaps://?daddr='+q); // for iphone
    }
    parameters.push('key='+googleMapKey);
    src = googleMapEmbed.replace('<MODE>', mode);
    src += parameters.join('&');

    $(mapId).html('<iframe src="'+src+'" style="border:0; width:'+mapWidth+'; height:'+mapHeight+';" frameborder="0" allowfullscreen></iframe>');
  }
}
// paid api, needs specify key at /civicrm/admin/setting/mapping
else {
  if (googleMapKey.length <= 0) {
    $(mapId).html('{/literal}<a href="{crmURL p='civicrm/admin/setting/mapping' q='reset=1'}">{ts}Enter your Google API Key OR your Yahoo Application ID.{/ts}</a>{literal}');
    return;
  }
  else {
    var lat, lng;
    var center = {};
    {/literal}{if $center}
    lat = Number({$center.lat});
    lng = Number({$center.lng});
    {/if}{literal}
    if (lat && lng) {
      center = {"lat": lat, "lng": lng};
    }
    var info = function(loc) {
      var output = '';
      if (loc.photo) {
        output += '<div><a href="'+loc.url+'" target="_blank"><img src="'+loc.photo+'" style="max-width:150px;"></a></div>';
      }
      if (loc.displayName) {
        output += '<div><strong><a href="'+loc.url+'" target="_blank">'+loc.displayName+'</a></strong></div>';
      }
      if (loc.displayAddress) {
        output += '<div>'+loc.displayAddress+'</div>';
      }
      if (loc.country) {
        output += '<div>'+loc.country+'</div>';
      }
      return output;
    }

    // load clusterer js
    window.crmGoogleMapCallback = function() {
      var infowindow = null;
      var markerAnimtion = null;
      var map = new google.maps.Map(document.getElementById('civicrm-google-map'), {
        zoom: 8,
        center: center
      });
      var markers = locations.map(function(location, i){
        var infoHtml = info(location);
        if (location.lat && location.lng) {
          var icon = '{/literal}{$config->resourceBase}{literal}packages/markerclusterer/'+location.marker_class.toLowerCase()+'.svg';
          var marker = new google.maps.Marker({
            position: {"lat": Number(location.lat), "lng": Number(location.lng)},
            title: location.displayName,
            icon: icon
          });
          marker.addListener('click', function() {
            if (infowindow) {
              infowindow.close();
            }
            infowindow = new google.maps.InfoWindow({
              content: infoHtml 
            });
            infowindow.open(map, marker);
          });
          return marker;
        }
      });
      var markerCluster = new MarkerClusterer(map, markers, {
        maxZoom: 16, // we need set prevent never zoom enough
        imagePath: '{/literal}{$config->resourceBase}{literal}packages/markerclusterer/m'
      });
    }

		// add google map script
    var script = document.createElement('script');
    script.src = googleMapJS + 'key=' + googleMapKey + '&callback=crmGoogleMapCallback';
    $('body').append(script);
  }
}


})(cj);
</script>
{/literal}
