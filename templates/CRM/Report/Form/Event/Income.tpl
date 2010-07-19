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
{* this div is being used to apply special css *}
    {if !$section }
        {include file="CRM/Report/Form/Fields.tpl"}
        {*Statistics at the Top of the page*}
        {include file="CRM/Report/Form/Statistics.tpl" top=true}    
    {/if}
    
    {if $events}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top" noForm=0}
        </div>
        {foreach from=$events item=eventID}
            <br />
            <table class="report-layout">
                <tr>
                    <td>    
                	<table class="report-layout" >
                	    {foreach from=$summary.$eventID item=values key=keys}
                	        {if $keys == 'Title'}
                        	    <tr>
                                        <th>{$keys}</th>
                                        <th colspan="3">{$values}</th>
                                    </tr>
                                {else}  
                                    <tr>
                                        <td class="report-contents">{$keys}</td>
                                        <td class="report-contents" colspan="3">{$values}</td>
                                    </tr>
                                {/if}
                            {/foreach}
                        </table>
                        {foreach from=$rows item=row key=keys}
                            {if $row.$eventID}
                            <table class="report-layout">
                        	{if $row}
                        	    <tr>
                        	        <th width="34%">{ts 1=$keys}%1 Breakdown{/ts}</th>
                                	<th class="reports-header-right">{ts}Total{/ts}</th>
                                        <th class="reports-header-right">{ts}% of Total{/ts}</th>
                                        <th class="reports-header-right">{ts}Revenue{/ts}</th>
                                    </tr>
                                    {foreach from=$row.$eventID item=row key=role}
                                        <tr>
                                            <td class="report-contents" width="34%">{$role}</td>
                                            <td class="report-contents-right">{$row.0}</td>
                                            <td class="report-contents-right">{$row.1}</td>
                                            <td class="report-contents-right">{$row.2|crmMoney}</td>	        
                                        </tr>
                                    {/foreach}
                                {/if}
                            </table>
                            {/if}
                        {/foreach} 
                    </td>
                </tr>
            </table>       
        {/foreach}
         
	<div class="report-pager">
            {include file="CRM/common/pager.tpl" noForm=0}
        </div>
        {if !$section }
            {*Statistics at the bottom of the page*}
            {include file="CRM/Report/Form/Statistics.tpl" bottom=true}
        {/if}    
    {/if}
    
{include file="CRM/Report/Form/ErrorMessage.tpl"}
