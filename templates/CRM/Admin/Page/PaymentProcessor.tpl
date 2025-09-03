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
{capture assign=docLink}{docURL page="CiviContribute Payment Processor Configuration"}{/capture}
<div id="help">
    {ts}You can configure one or more Payment Processors for your CiviCRM installation. You must then assign an active Payment Processor to each <strong>Online Contribution Page</strong> and each paid <strong>Event</strong>.{/ts} {$docLink}
</div>

<div id="help">
    {ts 1=$hostIP}If the provider needs server IP address, the IP address of this website is %1{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PaymentProcessor.tpl"}
{else}

{if $rows}
<div id="ltype">
        {strip}
        {* handle enable/disable actions*}
 	{include file="CRM/common/enableDisable.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <table class="selector display">
          <thead>
          <tr class="columnheader">
            <th id="sortable">{ts}ID{/ts}</th>
            <th >{ts}Name{/ts}</th>
            <th >{ts}Processor Type{/ts}</th>
            <th >{ts}Description{/ts}</th>
            <th >{ts}Enabled?{/ts}</th>
	    <th >{ts}Default?{/ts}</th>
            <th ></th>
          </tr>
          </thead>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="crm-payment_processor {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-payment_processor-id">{$row.id}</td>
            <td class="crm-payment_processor-name">{$row.name}</td>
            <td class="crm-payment_processor-payment_processor_type">{$row.payment_processor_type}</td>
            <td class="crm-payment_processor-description">{$row.description}</td>
            <td id="row_{$row.id}_status" class="crm-payment_processor-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-payment_processor-is_default">{if $row.is_default eq 1}<img src="{$config->resourceBase}/i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
	        <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
	    <div class="action-link-button">
      {if $availablePaymentProcessor}
    	<a href="{crmURL q="action=add&reset=1&pp=`$availablePaymentProcessor`"}" id="newPaymentProcessor" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Payment Processor{/ts}</span></a>
      {/if}
        </div>
        {/if}
</div>
{elseif $action ne 1}
    <div class="messages status">
      
      {if $availablePaymentProcessor}
        {capture assign=crmURL}{crmURL p='civicrm/admin/paymentProcessor' q="action=add&reset=1&pp=`$availablePaymentProcessor`"}{/capture}
      {ts 1=$crmURL}There are no Payment Processors entered. You can <a href='%1'>add one</a>.{/ts}
      {/if}
     </div>    
{/if}
{/if}
