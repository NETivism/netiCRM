{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
{* Display monthly and yearly contributions using Google charts (Bar and Pie) *} 
{if $hasContributions}
<table class="chart">
  <tr>
     <td>
         {if $hasByMonthChart}
      	     {* display monthly chart *}
             <div id="open_flash_chart_by_month"></div>
         {else}
	     {ts}There were no contributions during the selected year.{/ts}  
         {/if}	
     </td> 
     <td>
       	 {* display yearly chart *}
         <div id="open_flash_chart_by_year"></div>
     </td>
  </tr>
</table>

<table  class="form-layout-compressed" >
      <td class="label">{$form.select_year.label}</td><td>{$form.select_year.html}</td> 
      <td class="label">{$form.chart_type.label}</td><td>{$form.chart_type.html}</td> 
      <td class="html-adjust">
        {$form.buttons.html}<br />
        <span class="add-remove-link"><a href="{crmURL p="civicrm/contribute" q="reset=1"}">{ts}Table View{/ts}...</a></span>
      </td> 
</table>
{else}
 <div class="messages status"> 
      <dl> 
        <dd>{ts}There are no live contribution records to display.{/ts}</dd> 
      </dl> 
 </div>
{/if}

{if $hasOpenFlashChart}
{include file="CRM/common/openFlashChart.tpl"}

{literal}
<script type="text/javascript">

  cj( function( ) {
      buildChart( );
  });

  function buildChart( ) {
     var chartData = {/literal}{$openFlashChartData}{literal};	
     cj.each( chartData, function( chartID, chartValues ) {

	 var xSize   = eval( "chartValues.size.xSize" );
	 var ySize   = eval( "chartValues.size.ySize" );
	 var divName = eval( "chartValues.divName" );

	 createSWFObject( chartID, divName, xSize, ySize, 'loadData' );  
     });
  }
  
  function loadData( chartID ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var data    = eval( "allData." + chartID + ".object" );
     return JSON.stringify( data );
  }
 
  function byMonthOnClick( barIndex ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var url     = eval( "allData.by_month.on_click_urls.url_" + barIndex );
     if ( url ) window.location = url;
  }

  function byYearOnClick( barIndex ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var url     = eval( "allData.by_year.on_click_urls.url_" + barIndex );
     if ( url ) window.location = url;
  }

</script>
{/literal}
{/if}
