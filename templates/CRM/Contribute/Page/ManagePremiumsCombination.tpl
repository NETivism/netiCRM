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
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 1024}
   {include file="CRM/Contribute/Form/ManagePremiumsCombination.tpl"}
{else}
    

{if $action ne 2}
{if $action ne 1 or $action ne 8}
<div id="help">
{capture assign=contribURL}{crmURL p='civicrm/admin/contribute' q="reset=1"}{/capture}
<p>{ts}The premium combination feature allows you to create bundled sets of multiple items as rewards or thank-you gifts for donors. Each combination can include multiple different items, and you can specify the quantity for each item.{/ts}</p>
<p>{ts 1=$contribURL}Use this feature to create and update all the premium combinations you want to offer on any online contribution page. Then, from <a href='%1'>Configure Online Contribution Page</a> <strong>&raquo; Configure &raquo; Premiums</strong>, you can assign one or more combinations to a specific contribution page.{/ts}</p>

</div>

{/if}
{if $rows}
<div id="ltype">
<p></p>
    {strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
	{include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
          <thead>
           <tr>
            <th id="sortable">{ts}Premium Combination{/ts}</th>
            <th>{ts}SKU{/ts}</th>
            <th>{ts}Min Contribution{/ts}</th>
            <th>{ts}Active?{/ts}</th>
            <th></th>
           </tr>
          </thead>
        {foreach from=$rows item=row}
	      <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
	        <td class="crm-contribution-form-block-combination_name">{$row.combination_name}</td>	
	        <td class="crm-contribution-form-block-sku">{$row.sku}</td>
            <td class="crm-contribution-form-block-product_count">{$row.product_count}</td>
	        <td class="crm-contribution-form-block-min_contribution">{$row.min_contribution}</td>
            <td class="crm-contribution-form-block-weight">{$row.weight}</td>
	        <td id="row_{$row.id}_status" >{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td id={$row.id}>{$row.action|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
        </table>
    {/strip}
    {if $action ne 1 and $action ne 2}
	    <div class="action-link-button">
        <a href="{crmURL q="action=add&reset=1"}" id="newManagePremiumCombination" class="button"><i class="zmdi zmdi-plus-circle-o"></i>{ts}New Premium Combination{/ts}</a>
      </div>
    {/if}
</div>
{else}
    {if $action ne 1 and $action ne 2}
    <div class="action-link-button">
      <a href="{crmURL q="action=add&reset=1"}" id="newManagePremiumCombination" class="button"><i class="zmdi zmdi-plus-circle-o"></i>{ts}New Premium Combination{/ts}</a>
    </div>
    {/if}	  
{/if}
{/if}
{/if}