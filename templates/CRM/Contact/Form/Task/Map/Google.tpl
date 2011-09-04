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
  {assign var=height value="350px"}
  {assign var=width  value="425px"}
{else}	
  {assign var=height value="600px"}
  {assign var=width  value="100%"}
{/if}
{assign var=defaultZoom value=12}  
{literal}
<script src="http://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>
<script type="text/javascript">
    function initMap() {
        var latlng = new google.maps.LatLng({/literal}{$center.lat},{$center.lng}{literal});
        var map = new google.maps.Map(document.getElementById("google_map"));
        map.setCenter(latlng);
        map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
        setMapOptions(map);
    }
    
    function setMapOptions(map) {
        bounds = new google.maps.LatLngBounds( );
	{/literal}
	{foreach from=$locations item=location}
	    {if $location.url and ! $profileGID}
		{literal}
		var data = "{/literal}<div class=\"map-content\"><h3><a href='{$location.url}'>{$location.displayName}</a></h3><p>{if !$skipLocationType}[{$location.location_type}] {/if}{$location.address}</p><div>{ts}Get Directions From{/ts}:</div><div style=\"width:400px;\">{ts}From{/ts}:<input type=text id=from size=\"30\" /><br />{ts}To{/ts}:{$location.displayAddress}<input type=hidden id=to value='{$location.displayAddress}'> <a class=\"button silver\" href=\"javascript:gpopUp();\">{ts}&raquo; Go{/ts}</a></div></div>";
	    {else}
		{capture assign="profileURL"}{crmURL p='civicrm/profile/view' q="reset=1&id=`$location.contactID`&gid=$profileGID"}{/capture}
		{literal}
		var data = "{/literal}<div class=\"map-content\"><p><a href='{$profileURL}'>{$location.displayName}</a></h3><p>{if !$skipLocationType}[{$location.location_type}] {/if}{$location.address}</p><div>{ts}Get Directions From{/ts}:</div><div style=\"width:400px;\">{ts}From{/ts}:<input type=text id=from size=\"30\" /><br />{ts}To{/ts}:{$location.displayAddress}<input type=hidden id=to value='{$location.displayAddress}'> <a class=\"button silver\" href=\"javascript:gpopUp();\">{ts}&raquo; Go{/ts}</a></div></div>";
	    {/if}
	    {literal}
	    var address = "{/literal}{$location.address}{literal}";
	    {/literal}
	    {if $location.lat}
		var point  = new google.maps.LatLng({$location.lat},{$location.lng});
		{if $location.image && ( $location.marker_class neq 'Event' ) }
 		  var image = '{$location.image}';
		{else}
                 {if $location.marker_class eq 'Individual'}
 		      var image = "{$config->resourceBase}i/contact_ind.gif";
 		  {/if}
 		  {if $location.marker_class eq 'Household'}
 		      var image = "{$config->resourceBase}i/contact_house.png";
 		  {/if}
 		  {if $location.marker_class eq 'Organization' || $location.marker_class eq 'Event'}
  		      var image = "{$config->resourceBase}i/contact_org.gif";
 		  {/if}
                {/if}
 	        {literal}
                createMarker(map, point, data, image);
                bounds.extend(point);
                {/literal}
	    {/if}
	{/foreach}
        map.setCenter(bounds.getCenter());
        {if count($locations) gt 1}  
            map.fitBounds(bounds);
            map.setMapTypeId(google.maps.MapTypeId.TERRAIN);
        {elseif $location.marker_class eq 'Event' || $location.marker_class eq 'Individual'|| $location.marker_class eq 'Household' || $location.marker_class eq 'Organization' }
            map.setZoom({$defaultZoom});
        {else} 
            map.setZoom({$defaultZoom}); 
        {/if}
	{literal}	
    }

    function createMarker(map, point, data, image) {
        var marker = new google.maps.Marker({ position: point,
                                              map: map,
                                              icon: image
                                            });
        var infowindow = new google.maps.InfoWindow({
            content: data,
            maxWidth: 500
        });
        google.maps.event.addListener(marker, 'click', function() {
          infowindow.open(map,marker);
        });
 
    }

    function gpopUp() {
	var from   = document.getElementById('from').value;
	var to     = document.getElementById('to').value;	
	var URL    = "http://maps.google.com.tw/maps?saddr=" + from + "&daddr=" + to;
	day = new Date();
	id  = day.getTime();
	eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=780,height=640,left = 202,top = 100');");
    }

    if (window.addEventListener) {
        window.addEventListener("load", initMap, false);
    } else if (window.attachEvent) {
        document.attachEvent("onreadystatechange", initMap);
    }
</script>
{/literal}
<div id="google_map" style="width: {$width}; height: {$height}"></div>
