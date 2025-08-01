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
<div id="help">
    {ts 1=$GName}The existing option choices for %1 group are listed below. You can add, edit or delete them from this screen.{/ts}
</div>
{if $action ne 1 and $action ne 2}
    <div class="action-link-button">
	<a href="{$newReport}"  id="new"|cat:$GName class="button"><span>&raquo; {ts 1=$GName}Register New %1{/ts}</span></a>
    </div>
    <div class="spacer"></div>
{/if}
{if $rows}
    <div id="optionList">
	{strip}
	{* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
 	{include file="CRM/common/jsortable.tpl"}
       <table id="options" class="display">
       <thead>
		<tr>      
		    <th>{ts}Label{/ts}</th>
		    <th>{ts}URL{/ts}</th>   
		    <th id="nosort">{ts}Description{/ts}</th>
		    <th id="order" class="sortable">{ts}Order{/ts}</th>
		    {if $showIsDefault}
		        <th>{ts}Default{/ts}</th>
		    {/if}
		    <th>{ts}Reserved{/ts}</th>
		    <th>{ts}Enabled?{/ts}</th>
		    <th>{ts}Component{/ts}</th>
		    <th></th>
		    <th class="hiddenElement"></th>
		</tr>
        </thead>
		{foreach from=$rows item=row}
		    <tr id="row_{$row.id}" class="crm-report {cycle values="odd-row,even-row"}{$row.class}{if NOT $row.is_active} crm-report-optionList crm-report-optionList-status_disable disabled{else} crm-report-optionList crm-report-optionList-status_enable{/if}">
 		        <td class="crm-report-optionList-label">{$row.label}</td>	
		        <td class="crm-report-optionList-value">{$row.value}</td>
		        <td class="crm-report-optionList-description">{$row.description}</td>	
		        <td class="nowrap weight-order crm-report-optionList-order">{$row.order}</td>
		        {if $showIsDefault}
		            <td class="crm-report-optionList-default_value">{$row.default_value}</td>
		        {/if}
		        <td class="crm-report-optionList-is_reserved">{if $row.is_reserved eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
    			<td class="crm-report-optionList-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	    		<td class="crm-report-optionList-component_name">{$row.component_name}</td>	
		        <td class="crm-report-optionList-action">{$row.action}</td>
                        <td class="order hiddenElement">{$row.weight}</td>
		    </tr>
		{/foreach}
	    </table>
	{/strip}

        {if $action ne 1 and $action ne 2}
            <div class="action-link-button">
		<a href="{$newReport}"  id="new"|cat:$GName class="button"><span>&raquo; {ts 1=$GName}Register New %1{/ts}</span></a>
            </div>
        {/if}
    </div>
{else}
    <div class="messages status">
        {ts 1=$newReport}There are no option values entered. You can <a href="%1">add one</a>.{/ts}
    </div>    
{/if}    
