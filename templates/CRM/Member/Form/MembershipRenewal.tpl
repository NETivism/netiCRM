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
{* this template is used for renewing memberships for a contact  *}
{if $membershipMode == 'test' }
    {assign var=registerMode value="TEST"}
{elseif $membershipMode == 'live'}
    {assign var=registerMode value="LIVE"}
{/if}
{if !$email}
<div class="messages status">
  <dl>
    <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
    <dd>
        <p>{ts}You will not be able to send an automatic email receipt for this Renew Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before Renewal the Membership.{/ts}</p>
    </dd>
  </dl>
</div>
{/if}
{if $membershipMode}
<div id="help">
    {ts 1=$displayName 2=$registerMode}Use this form to Renew Membership Record on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
</div>
{/if}
<fieldset><legend>{if $action eq 32768}{ts}Renew Membership{/ts}{/if}</legend>
    <div class="form-item">
    <div id="help" class="description">
        {ts}Renewing will add the normal membership period to the End Date of the previous period for members whose status is Current or Grace. For Expired memberships, renewing will create a membership period commencing from the 'Date Renewal Entered'. This date can be adjusted including being set to the day after the previous End Date - if continuous membership is required.{/ts}
    </div>
    <dl>
        <dt>{$form.payment_processor_id.label}</dt>
        <dd class="html-adjust">{$form.payment_processor_id.html}</dd><br />
        
        <dt>{ts}Membership Organization and Type{/ts}</dt>
        <dd class="html-adjust">{$orgName}&nbsp;&nbsp;-&nbsp;&nbsp;{$memType}
            {if $member_is_test} {ts}(test){/ts}{/if}</dd>
        
        <dt>{ts}Membership Status{/ts}</dt>
        <dd class="html-adjust">&nbsp;{$membershipStatus}<br />
            <span class="description">{ts}Status of this membership.{/ts}</span></dd>

        <dt>{ts}Membership End Date{/ts}</dt>
        <dd class="html-adjust">&nbsp;{$endDate}</dd>
        
        <dt>{$form.renewal_date.label}</dt>
        <dd>{include file="CRM/common/jcalendar.tpl" elementName=renewal_date}</dd>
    </dl>

    {if $accessContribution and ! $membershipMode}
        <div id="contri">
            <dl>
            <dt>{$form.record_contribution.label}</dt><dd class="html-adjust">{$form.record_contribution.html}<br />
            <span class="description">{ts}Check this box to enter payment information. You will also be able to generate a customized receipt.{/ts}</span></dd>
            </dl>
        <div>
        <div class="spacer"></div>
        <fieldset id="recordContribution"><legend>{ts}Renewal Payment and Receipt{/ts}</legend>
        <dl>	
            <dt class="label">{$form.contribution_type_id.label}</dt><dd>{$form.contribution_type_id.html}<br />
                <span class="description">{ts}Select the appropriate contribution type for this payment.{/ts}</span></dd>
            <dt class="label">{$form.total_amount.label}</dt><dd>{$form.total_amount.html}<br />
                <span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span></dd>
            <dt class="label">{$form.payment_instrument_id.label}</dt><dd>{$form.payment_instrument_id.html}</dd>
            <div id="checkNumber"><dt class="label">{$form.check_number.label}</dt><dd>{$form.check_number.html|crmReplace:class:six}</dd></div>
	    <dt class="label">{$form.trxn_id.label}</dt><dd>{$form.trxn_id.html}</dd>
            <dt class="label">{$form.contribution_status_id.label}</dt><dd>{$form.contribution_status_id.html}</dd>
	</dl>
        </fieldset>
    {/if}
    {if $membershipMode}
     	<div class="spacer"></div>
     	{include file='CRM/Core/BillingBlock.tpl'}
     {/if}
     {if $email and $outBound_option != 2}	
	 <dl>
	    <dt>{$form.send_receipt.label}</dt><dd class="html-adjust">{$form.send_receipt.html}<br />
	    <span class="description">{ts 1=$email}Automatically email a membership confirmation and receipt to %1?{/ts}</span></dd>
	 </dl> 
	 <div id='notice'>
	    <dl>		
    	    <dt>{$form.receipt_text_renewal.label}</dt>
            <dd class="html-adjust"><span class="description">{ts}Enter a message you want included at the beginning of the emailed receipt. EXAMPLE: 'Thanks for supporting our organization with your membership.'{/ts}</span>
            {$form.receipt_text_renewal.html|crmReplace:class:huge}</dd> 
	    </dl>
	 </div>
     {/if}
         
   <dl>
     <dt></dt><dd class="html-adjust">{$form.buttons.html}</dd>
   </dl>
   <div class="spacer"></div>
   </div>
</fieldset>
{if $accessContribution and ! $membershipMode}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="record_contribution"
    trigger_value       =""
    target_element_id   ="recordContribution" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
{if $email and $outBound_option != 2}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="notice" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
{if !$membershipMode}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="payment_instrument_id"
    trigger_value       = '4'
    target_element_id   ="checkNumber" 
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}
{/if}
{literal}
<script type="text/javascript">
function checkPayment()
{
    showHideByValue('record_contribution','','recordContribution','table-row','radio',false);
    {/literal}{if $email and $outBound_option != 2}{literal}
    var record_contribution = document.getElementsByName('record_contribution');
    if ( record_contribution[0].checked ) {
        document.getElementsByName('send_receipt')[0].checked = true;
    } else {
        document.getElementsByName('send_receipt')[0].checked = false;
    }
    showHideByValue('send_receipt','','notice','table-row','radio',false);  
    {/literal}{/if}{literal}
}        
</script>
{/literal}
