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
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8  and !$usedBy}
    {include file="CRM/Price/Form/Option.tpl"}
{/if}

{if $usedBy}
    <div class='spacer'></div>
    <div id="price_set_used_by" class="messages status">
    
        {if $action eq 8}
            {ts 1=$usedPriceSetTitle}Unable to delete the '%1' Price Field Option - it is currently in use by one or more active events  or contribution pages or contributions.{/ts}
       	{/if}
        
	{if $usedBy.civicrm_event or $usedBy.civicrm_contribution_page} 
            {include file="CRM/Price/Page/table.tpl"} 
        {/if}

    </div>
    {/if}



{if $customOption}
    
    <div id="field_page">
     <p></p>
        {strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
 	{include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
        <thead>
         <tr>
            <th>{ts}Option Label{/ts}</th>
            <th>{ts}Option Amount{/ts}</th>
    	    <th>{ts}Default{/ts}</th>
            <th id="nosort" class="sortable">{ts}Order{/ts}</th>
	        <th>{ts}Enabled?{/ts}</th>
            <th></th>
            <th class="hiddenElement"></th>
         </tr>
        </thead>
        {foreach from=$customOption item=row}
    	<tr id="row_{$row.id}"class=" crm-price-option crm-price-option_{$row.id} {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-price-option-label">{$row.label}</td>
            <td class="crm-price-option-value">{$row.amount|crmMoney}</td>
	    <td class="crm-price-option-is_default">{if $row.is_default}<img src="{$config->resourceBase}/i/check.gif" alt="{ts}Default{/ts}" />{/if}</td>
            <td class="nowrap crm-price-options-order">{$row.weight}</td>
            <td id="row_{$row.id}_status" class="crm-price-option-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|replace:'xx':$row.id}</td>
            <td class="order hiddenElement">{$row.weight}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
        {/strip}
        {if $addMoreFields}
        <div class="action-link-button">
            <a href="{crmURL q="reset=1&action=add&fid=$fid"}" class="button"><span>&raquo; {ts 1=$fieldTitle}New Option for '%1'{/ts}</span></a>
        </div>
	{/if}
    </div>

{else}
    {if $action eq 16}
        <div class="messages status">
           {capture assign=crmURL}{crmURL p='civicrm/admin/price/field/option' q="action=add&fid=$fid"}{/capture}{ts 1=$fieldTitle 2=$crmURL}There are no options for the price field '%1', <a href='%2'>add one</a>.{/ts}
        </div>
    {/if}
{/if}
