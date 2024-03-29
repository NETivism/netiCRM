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
{* this div is being used to apply special css *}
    {if !$section }
    <div class="crm-block crm-form-block crm-report-field-form-block">    
        {include file="CRM/Report/Form/Fields.tpl"}
    </div>
    {/if}    
	
<div class="crm-block crm-content-block crm-report-form-block">
{include file="CRM/Report/Form/Actions.tpl"}
{if !$section }
{include file="CRM/Report/Form/Statistics.tpl" top=true}
{/if}
    {if $rows}
        <div class="report-pager">
            {include file="CRM/common/pager.tpl" location="top" noForm=0}
        </div>
        {foreach from=$rows item=row key=number}
                	<table class="report-layout crm-report_contact_civireport">
                            {if !$number}
                            <tr>
                                {foreach from=$columnHeaders item=header key=field}
                                    {if !$skip}
                                        {if $header.colspan}
                                            <th colspan={$header.colspan}>{$header.title}</th>
                                            {assign var=skip value=true}
                                            {assign var=skipCount value=`$header.colspan`}
                                            {assign var=skipMade  value=1}
                                        {else}
                                            <th>{$header.title}</th>
                                            {assign var=skip value=false}
                                        {/if}
                                    {else} {* for skip case *}
                                        {assign var=skipMade value=`$skipMade+1`}
                                        {if $skipMade >= $skipCount}{assign var=skip value=false}{/if}
                                    {/if}
                                {/foreach}
                            </tr>               
                            {/if}
                            <tr class="group-row crm-report">
                                {foreach from=$columnHeaders item=header key=field}
                                    {assign var=fieldLink value=$field|cat:"_link"}
                                    {assign var=fieldHover value=$field|cat:"_hover"}
                                    <td  class="report-contents crm-report_{$field}">
                                        {if $row.$fieldLink}<a title="{$row.$fieldHover}" href="{$row.$fieldLink}">{/if}
                        
                                        {if $row.$field eq 'Subtotal'}
                                            {$row.$field}
                                        {elseif $header.type eq 12}
                                            {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                                                {$row.$field|crmDate:$config->dateformatPartial}
                                            {elseif $header.group_by eq 'YEAR'}	
                                                {$row.$field|crmDate:$config->dateformatYear}
                                            {else}				
                                                {$row.$field|truncate:10:''|crmDate}
                                            {/if}	
                                        {elseif $header.type eq 1024}
                                            {$row.$field|crmMoney}
                                        {else}
                                            {$row.$field}
                                        {/if}
				
                                        {if $row.contactID} {/if}
                                    
                                        {if $row.$fieldLink}</a>{/if}
                                    </td>
                                {/foreach}
                            </tr>
                        </table>

                        {if $columnHeadersComponent}
                            {assign var=componentContactId value=$row.contactID}
                            {foreach from=$columnHeadersComponent item=pheader key=component}
                                {if $componentRows.$componentContactId.$component}
                                    <h3>{ts}{$component|replace:'_civireport':''|capitalize}{/ts}</h3>
                        	<table class="report-layout crm-report_{$component}">
                        	    {*add space before headers*}
                        		<tr>
                        		    {foreach from=$pheader item=header}
                        			<th>{$header.title}</th>
                        		    {/foreach}
                        		</tr>
                             
                        	    {foreach from=$componentRows.$componentContactId.$component item=row key=rowid}
                        		<tr class="{cycle values="odd-row,even-row"} crm-report" id="crm-report_{$rowid}">
                        		    {foreach from=$columnHeadersComponent.$component item=header key=field}
                        			{assign var=fieldLink value=$field|cat:"_link"}
                                                {assign var=fieldHover value=$field|cat:"_hover"}
                        			<td class="report-contents crm-report_{$field}">
                        			    {if $row.$fieldLink}
                        				<a title="{$row.$fieldHover} "href="{$row.$fieldLink}">
                        			    {/if}
                        
                        			    {if $row.$field eq 'Sub Total'}
                        				{$row.$field}
                        			    {elseif $header.type & 4}
                        			        {if $header.group_by eq 'MONTH' or $header.group_by eq 'QUARTER'}
                        				    {$row.$field|crmDate:$config->dateformatPartial}
                        				{elseif $header.group_by eq 'YEAR'}	
                        				    {$row.$field|crmDate:$config->dateformatYear}
                        				{else}				
                        				    {$row.$field|truncate:10:''|crmDate}
                        				{/if}	
                        			    {elseif $header.type eq 1024}
                        				{$row.$field|crmMoney}
                        			    {else}
                        				{$row.$field}
                        			    {/if}
                        
                        			    {if $row.$fieldLink}</a>{/if}
                        			</td>
                        		    {/foreach}
                        		</tr>
                        	    {/foreach}
                        	</table>
                            {/if}	
                            {/foreach}
                        {/if}
        {/foreach}

	<div class="report-pager">
            {include file="CRM/common/pager.tpl" noForm=0}
        </div>
        <br />
        {if $grandStat}
            <table class="report-layout">
                <tr>
                    {foreach from=$columnHeaders item=header key=field}
                        <td>
                            <strong>
                                {if $header.type eq 1024}
                                    {$grandStat.$field|crmMoney}
                                {else}
                                    {$grandStat.$field}
                                {/if}
                            </strong>
                        </td>
                    {/foreach}
                </tr>
            </table>
        {/if}
        
        {if !$section }
            {*Statistics at the bottom of the page*}
            {include file="CRM/Report/Form/Statistics.tpl" bottom=true}
        {/if}
    {/if} 
    {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
