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
{* CiviContribute DashBoard (launch page) *}
{if $isAdmin}
    {capture assign=newPageURL}{crmURL p="civicrm/admin/contribute" q="action=add&reset=1"}{/capture}
    {capture assign=configPagesURL}{crmURL p="civicrm/admin/contribute" q="reset=1"}{/capture}
<div class="float-right">
<table class="form-layout-compressed">
<tr>
    <td><a href="{$configPagesURL}" class="button"><span>&raquo; {ts}Manage Contribution Pages{/ts}</span></a></td>
    <td><a href="{$newPageURL}" class="button"><span>&raquo; {ts}New Contribution Page{/ts}</span></a></td>
</tr>
</table>
</div>
{/if}
{capture assign=chartURL}{crmURL p="civicrm/contribute/chart" q="reset=1"}{/capture}

<h3>{ts}Contribution Summary{/ts} {help id="id-contribute-intro"}</h3>
<table class="report">
<tr class="columnheader-dark">
    <th scope="col">{ts}Period{/ts}</th>
    <th scope="col">{ts}Total Amount{/ts}</th>
    <th scope="col" title="Contribution Count"><strong>#</strong></th><th></th></tr>
<tr>
    <td><strong>{ts}Current Month-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $monthToDate.Valid.amount}{ts}(n/a){/ts}{else}{$monthToDate.Valid.amount}{/if}</td>
    <td class="label">{$monthToDate.Valid.count}</td>
    <td><a href="{$monthToDate.Valid.url}">{ts}view details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Current Fiscal Year-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $yearToDate.Valid.amount}{ts}(n/a){/ts}{else}{$yearToDate.Valid.amount}{/if}</td>
    <td class="label">{$yearToDate.Valid.count}</td>
    <td><a href="{$yearToDate.Valid.url}">{ts}view details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Cumulative{/ts}</strong><br />{ts}(since inception){/ts}</td>
    <td class="label">{if NOT $startToDate.Valid.amount}{ts}(n/a){/ts}{else}{$startToDate.Valid.amount}{/if}</td>
    <td class="label">{$startToDate.Valid.count}</td>
    <td><a href="{$startToDate.Valid.url}">{ts}view details{/ts}...</a></td>
<tr><td colspan="4" class="right" style="padding: 8px 10px 0px 4px; vertical-align: middle;"><a href="{$chartURL}" title="{ts}View contribution summary as bar or pie chart using the Google Chart API{/ts}"><img src="{$config->resourceBase}/i/BarGraph.png" alt="{ts}View contribution summary as bar or pie chart using the Google Chart API{/ts}" /></a> <a href="{$chartURL}" title="{ts}View contribution summary as bar or pie chart{/ts}">{ts}Bar or Pie Chart...{/ts}</a></td></tr>
</tr>
</table>

<div class="spacer"></div>

{if $pager->_totalItems}
    <h3>{ts}Recent Contributions{/ts}</h3>
    <div>
        {include file="CRM/Contribute/Form/Selector.tpl" context="dashboard"}
    </div>
{/if}
