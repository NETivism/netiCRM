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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Contribute/Form/ContributionType.tpl"}
{else}
    <div id="help">
        <p>{ts}Contribution types are used to categorize contributions for reporting and accounting purposes. These are also referred to as <strong>Funds</strong>. You may set up as many types as needed. Each type can carry an accounting code which can be used to map contributions to codes in your accounting system. Commonly used contribution types are: Donation, Campaign Contribution, Membership Dues...{/ts}</p>
    </div>

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <table class="display" cellpadding="0" cellspacing="0" border="0">
           <thead class="sticky">
            <th>{ts}ID{/ts}</th>
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Acctg Code{/ts}</th>
            <th>{ts}Tax Receipt?{/ts}</th>
            <th>{ts}Deductible?{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Used for{/ts}</th>
            <th></th>
          </thead>
         {foreach from=$rows item=row}
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
	        <td>{$row.id}</td>
	        <td>{$row.name}</td>
	        <td>{$row.description}</td>
            	<td>{$row.accounting_code}</td>
	        <td>{if $row.is_taxreceipt eq 1}
            {ts}Normal tax or zero tax{/ts} ({ts}Tax Rate{/ts}: {$row.tax_rate}%)
            {elseif $row.is_taxreceipt eq -1}
            {ts}Tax free{/ts} ({ts}Tax Rate{/ts}: {$row.tax_rate}%)
            {else}
            {ts}No{/ts}
            {/if}</td>
	        <td>{if $row.is_deductible eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td>{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	        <td class="ct-usage-cell">
	          {if $row.usedPages}
	            {assign var="pageCount" value=$row.usedPages|@count}
	            <a href="javascript:void(0)" class="ct-usage-toggle" data-id="{$row.id}">
	              {$pageCount} {ts}page(s){/ts} <span class="ct-arrow">&#9658;</span>
	            </a>
	            <div id="ct-usage-{$row.id}" class="ct-usage-list" style="display:none;">
	              {foreach from=$row.usedPages item=page}
	                <div{if not $page.is_active} class="disabled ct-usage-disabled"{/if}>
	                  <a href="{$page.url}"{if not $page.is_active} class="disabled"{/if}>{$page.title}({ts}ID{/ts}:{$page.id})</a>
	                </div>
	              {/foreach}
	            </div>
	          {else}
	            —
	          {/if}
	        </td>
	        <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
         </table>
<style>
.ct-usage-toggle { cursor: pointer; white-space: nowrap; }
.ct-usage-list { margin-top: 4px; }
.ct-usage-list .ct-usage-disabled a,
.ct-usage-list .ct-usage-disabled a.disabled {
  color: #c00;
  text-decoration: line-through;
}
</style>
<script>{literal}
cj(document).ready(function($) {
  $('.ct-usage-toggle').on('click', function() {
    var id = $(this).data('id');
    var list = $('#ct-usage-' + id);
    var arrow = $(this).find('.ct-arrow');
    list.toggle();
    arrow.html(list.is(':visible') ? '&#9660;' : '&#9658;');
  });
});
{/literal}</script>
        {/strip}

        {if $action ne 1 and $action ne 2}
	    <div class="action-link-button">
    	<a href="{crmURL q="action=add&reset=1"}" id="newContributionType" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Contribution Type{/ts}</span></a>
        </div>
        {/if}
    </div>
</div>
{else}
    <div class="messages status">
        
        {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Contribution Types entered. You can <a href='%1'>add one</a>.{/ts}
    </div>    
{/if}
{/if}
