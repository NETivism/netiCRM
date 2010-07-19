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
<fieldset><legend>{ts}View Contribution{/ts}</legend>
<table class="view-layout">
    <tr>
        <td class="label">{ts}From{/ts}</td>
        <td class="bold">{$displayName}</td>
    </tr>
    <tr>
        <td class="label">{ts}Contribution Type{/ts}</td>
    	<td>{$contribution_type}&nbsp; {if $is_test} {ts}(test){/ts} {/if}</td>
    </tr>
    {if $lineItem}
    <tr>
        <td class="label">{ts}Contribution Amount{/ts}</td>
        <td>{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}</td>
        </tr>
    {else}
    <tr>
        <td class="label">{ts}Total Amount{/ts}</td>
        <td><strong>{$total_amount|crmMoney:$currency}</strong>&nbsp; 
            {if $contribution_recur_id}
              <strong>{ts}Recurring Contribution{/ts}</strong> <br/>
              {ts}Installments{/ts}: {$recur_installments}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
            {/if}
        </td>
    </tr>
    {/if}
    {if $non_deductible_amount}
        <tr>
	        <td class="label">{ts}Non-deductible Amount{/ts}</td>
	        <td>{$non_deductible_amount|crmMoney:$currency}</td>
	    </tr>
	{/if}
	{if $fee_amount}
	    <tr>
	        <td class="label">{ts}Fee Amount{/ts}</td>
	        <td>{$fee_amount|crmMoney:$currency}</td>
	    </tr>
	{/if}
	{if $net_amount}
	    <tr>
	        <td class="label">{ts}Net Amount{/ts}</td>
	        <td>{$net_amount|crmMoney:$currency}</td>
	    </tr>    
	{/if}

	<tr>
	    <td class="label">{ts}Received{/ts}</td>
    	<td>{if $receive_date}{$receive_date|truncate:10:''|crmDate}{else}({ts}pending{/ts}){/if}</td>
	</tr>
	<tr>
	    <td class="label">{ts}Contribution Status{/ts}</td>
	    <td {if $contribution_status_id eq 3} class="font-red bold"{/if}>{$contribution_status}
	    {if $contribution_status_id eq 2} {if $is_pay_later}: {ts}Pay Later{/ts} {else} : {ts}Incomplete Transaction{/ts} {/if}{/if}</td>
	</tr>

	{if $cancel_date}
        <tr>
	        <td class="label">{ts}Cancelled Date{/ts}</td>
	        <td>{$cancel_date|truncate:10:''|crmDate}</td>
        </tr>
	    {if $cancel_reason}
	        <tr>
	            <td class="label">{ts}Cancellation Reason{/ts}</td>
	            <td>{$cancel_reason}</td>
	        </tr>
	    {/if} 
	{/if}
	<tr>
	    <td class="label">{ts}Paid By{/ts}</td>
    	<td>{$payment_instrument}</td>
	</tr>
	
	{if $payment_instrument eq 'Check'}
        <tr>
            <td class="label">{ts}Check Number{/ts}</td>
            <td>{$check_number}</td>
        </tr>
	{/if}
	<tr>
	    <td class="label">{ts}Source{/ts}</td>
    	<td>{$source}</td>
	</tr>
	{if $receipt_date}
    	<tr>
    	    <td class="label">{ts}Receipt Sent{/ts}</td>
        	<td>{$receipt_date|truncate:10:''|crmDate}</td>
    	</tr>
	{/if}	
	{foreach from=$note item="rec"} 
		{if $rec }
		    <tr>
		        <td class="label">{ts}Note{/ts}</td><td>{$rec}</td>
		    </tr>
		{/if} 
	{/foreach} 

	{if $trxn_id}
        <tr>
	        <td class="label">{ts}Transaction ID{/ts}</td>
	        <td>{$trxn_id}</td>
	    </tr>
	{/if} 

	{if $invoice_id}
	    <tr>
	        <td class="label">{ts}Invoice ID{/ts}</td>
	        <td>{$invoice_id}&nbsp;</td>
	    </tr
	{/if} 

	{if $honor_display}
	    <tr>
	        <td class="label">{$honor_type}</td>
	        <td>{$honor_display}&nbsp;</td>
	    </tr>
	{/if} 

	{if $thankyou_date}
	    <tr>
	        <td class="label">{ts}Thank-you Sent{/ts}</td>
	        <td>{$thankyou_date|truncate:10:''|crmDate}</td>
	    </tr>
	{/if}
	
	{if $softCreditToName}
    <tr>
    	<td class="label">{ts}Soft Credit To{/ts}</td>
        <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$soft_credit_to`"}" id="view_contact" title="{ts}View contact record{/ts}">{$softCreditToName}</a></td>
    </tr>
    {/if}	
</table>

{if $premium}
<fieldset><legend>{ts}Premium Information{/ts}</legend>
<table class="view-layout">
	<td class="label">{ts}Premium{/ts}</td><td>{$premium}</td>
	<td class="label">{ts}Option{/ts}</td><td>{$option}</td>
	<td class="label">{ts}Fulfilled{/ts}</td><td>{$fulfilled|truncate:10:''|crmDate}</td>
</table>
</fieldset>
{/if}

{if $pcp_id}
<fieldset><legend>{ts}Personal Campaign Page Contribution Information{/ts}</legend>
<table class="view-layout">
    <tr>
	    <td class="label">{ts}Campaign Page{/ts}</td>
        <td><a href="{crmURL p="civicrm/contribute/pcp/info" q="reset=1&id=`$pcp_id`"}">{$pcp}</a><br />
            <span class="description">{ts}Contribution was made through this personal campaign page.{/ts}</span>
        </td>
    </tr>
    <tr><td class="label">{ts}In Public Honor Roll?{/ts}</td><td>{if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>
    {if $pcp_roll_nickname}
        <tr><td class="label">{ts}Honor Roll Name{/ts}</td><td>{$pcp_roll_nickname}</td></tr>
    {/if}
    {if $pcp_personal_note}
        <tr><td class="label">{ts}Personal Note{/ts}</td><td>{$pcp_personal_note}</td></tr>
    {/if}
</table>
</fieldset>
{/if}

{include file="CRM/Custom/Page/CustomDataView.tpl"}

{if $billing_address}
<fieldset><legend>{ts}Billing Address{/ts}</legend>
	<div class="form-item">
		{$billing_address|nl2br}
	</div>
</fieldset>
{/if}

<table class="form-layout buttons">
    <tr>
	    <td>&nbsp;</td>
        <td>
            {$form.buttons.html}
            {if call_user_func(array('CRM_Core_Permission','check'), 'edit contributions')}
                &nbsp;|&nbsp;<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=$id&cid=$contact_id&action=update&context=$context"}" accesskey="e">Edit</a>
            {/if}
            {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute')}
                &nbsp;|&nbsp;<a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=$id&cid=$contact_id&action=delete&context=$context"}">Delete</a>
            {/if}
        </td>
    </tr>    
</table>

</fieldset>
