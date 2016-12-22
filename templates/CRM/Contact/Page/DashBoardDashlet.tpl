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
{include file="CRM/common/dashboard.tpl"}
{include file="CRM/common/openFlashChart.tpl"}
<div class="crm-submit-buttons">
<a href="javascript:addDashlet( );" class="button show-add">
	<span><div class="zmdi zmdi-settings"></div>{ts}Configure Your Dashboard{/ts}</span></a>

<a style="display:none;" href="{crmURL p="civicrm/dashboard" q="reset=1"}" class="button show-done" style="margin-left: 6px;">
	<span><div class="zmdi zmdi-check-square"></div>{ts}Done{/ts}</span></a>

<a style="float:right;" href="{crmURL p="civicrm/dashboard" q="reset=1&resetCache=1"}" class="button show-refresh" style="margin-left: 6px;">
	<span> <div class="zmdi zmdi-refresh-alt"></div>{ts}Refresh Dashboard Data{/ts}</span></a>

</div>
<div class="crm-block crm-content-block">
{* Welcome message appears when there are no active dashlets for the current user. *}
<div id="empty-message" class='hiddenElement'>
    <div class="status">
        <div class="font-size12pt bold">{ts}Welcome to your Home Dashboard{/ts}</div>
        <div class="display-block">
            {ts}Your dashboard provides a one-screen view of the data that's most important to you. Graphical or tabular data is pulled from the reports you select, and is displayed in 'dashlets' (sections of the dashboard).{/ts} {help id="id-dash_welcome" file="CRM/Contact/Page/Dashboard.hlp"}
        </div>
    </div>
</div>

<div id="configure-dashlet" class='hiddenElement'></div>
<div id="civicrm-dashboard">
  <!-- You can put anything you like here.  jQuery.dashboard() will remove it. -->
  {ts}Javascript must be enabled in your browser in order to use the dashboard features.{/ts}
</div>
<div class="clear"></div>

<div class="chartist-test">
{php}
  /*
  id：設定此元素的 id
  classes：設定此元素的 class，資料格式為陣列，可多值，例如 array('ct-chart-pie', 'ct-chart-pie-medium')
  type：chartist 圖表的類型，預設為「Line」，可使用的類型：Line、Bar、Pie
  labels：chartist 圖表的標籤，資料格式為陣列，可多值，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  series：chartist 圖表的值，資料格式為陣列，可多值，PHP 丟資料時記得加上 json_encode，讓 js 能夠讀取
  labelType：圖表標籤的類型，預設為「label」，可使用的類型：label、percent 
  labelOffset：圖表標籤的位置，預設為 0，如果有圖例，預設值為 65
  withLegend：是否有圖例，資料格式為布林值，預設為「false」
  */

  $chart = array(
    'id' => 'chart-pie-with-legend-demo',
    'classes' => array('ct-chart-pie'),
    'type' => 'Pie',
    'labels' => json_encode(array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O')),
    'series' => json_encode(array(10, 15, 20, 4, 19, 40, 29, 14, 34, 20, 49, 10, 23, 10, 5)),
    'labelType' => 'percent', 
    'withLegend' => true
  );
  $this->assign('chart', $chart);
{/php}
{include file="CRM/common/chartist.tpl" chartist=$chart}
</div>

{literal}
<script type="text/javascript">
  function addDashlet(  ) {
      var dataURL = {/literal}"{crmURL p='civicrm/dashlet' q='reset=1&snippet=1' h=0 }"{literal};

      cj.ajax({
         url: dataURL,
         success: function( content ) {
             cj("#civicrm-dashboard").hide( );
             cj('.show-add').hide( );
             cj('.show-refresh').hide( );
             cj('.show-done').show( );
             cj("#empty-message").hide( );
             cj("#configure-dashlet").show( ).html( content );
         }
      });
  }
        
</script>
{/literal}
</div>
