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
{* this template is used for adding/editing/deleting memberships for a contact  *}
<div class="spacer"></div>
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
{if $membershipMode == 'test' }
    {assign var=registerMode value="TEST"}
{elseif $membershipMode == 'live'}
    {assign var=registerMode value="LIVE"}
{/if}
{if !$emailExists and $action neq 8 and $context neq 'standalone'}
<div class="messages status">
  <dl>
    <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
    <dd>
        <p>{ts}You will not be able to send an automatic email receipt for this Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the Membership.{/ts}</p>
    </dd>
  </dl>
</div>
{/if}

{if $membershipMode}
    <div id="help">
        {ts 1=$displayName 2=$registerMode}Use this form to submit Membership Record on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    </div>
{/if}
<div class="html-adjust">{$form.buttons.html}</div>
<fieldset><legend>{if $action eq 1}{ts}New Membership{/ts}{elseif $action eq 2}{ts}Edit Membership{/ts}{else}{ts}Delete Membership{/ts}{/if}</legend> 
    <div class="form-item">
    {if $action eq 8}
      <div class="messages status">
        <dl>
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
          <dd>    
          {ts}WARNING: Deleting this membership will also delete related membership log and payment records. This action cannot be undone. Consider modifying the membership status instead if you want to maintain a record of this membership.{/ts}
          {ts}Do you want to continue?{/ts}
          </dd>
       </dl>
      </div>
    {else}
    <table class="form-layout-compressed">
        {if $context neq 'standalone'}
            <tr>
                <td class="font-size12pt right"><strong>{ts}Member{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
            </tr>
        {else}
            {include file="CRM/Contact/Form/NewContact.tpl"}
        {/if}
    {if $membershipMode}
	    <tr><td class="label">{$form.payment_processor_id.label}</td><td>{$form.payment_processor_id.html}</td></tr>
	{/if}
 	<tr><td class="label">{$form.membership_type_id.label}</td><td>{$form.membership_type_id.html}
    {if $member_is_test} {ts}(test){/ts}{/if}<br />
        <span class="description">{ts}Select Membership Organization and then Membership Type.{/ts}</span></td></tr>	
    <tr><td class="label">{$form.source.label}</td><td>&nbsp;{$form.source.html}<br />
        <span class="description">{ts}Source of this membership. This value is searchable.{/ts}</span></td></tr>
	<tr><td class="label">{$form.join_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=join_date}
		<br />
        <span class="description">{ts}When did this contact first become a member?{/ts}</span></td></tr>
 	<tr><td class="label">{$form.start_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}
		<br />
        <span class="description">{ts}First day of current continuous membership period. Start Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
 	<tr><td class="label">{$form.end_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}
		<br />
        <span class="description">{ts}Latest membership period expiration date. End Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
    {if ! $membershipMode}
        <tr><td class="label">{$form.is_override.label}</td><td>{$form.is_override.html}&nbsp;&nbsp;{help id="id-status-override"}</td></tr>
    {/if}

    {if ! $membershipMode}
    {* Show read-only Status block - when action is UPDATE and is_override is FALSE *}
        <tr id="memberStatus_show">
        {if $action eq 2}
        <td class="label">{$form.status_id.label}</td><td class="view-value">{$membershipStatus}</td>
        {/if}
        </tr>

    {* Show editable status field when is_override is TRUE *}
        <tr id="memberStatus"><td class="label">{$form.status_id.label}</td><td>{$form.status_id.html}<br />
            <span class="description">{ts}If <strong>Status Override</strong> is checked, the selected status will remain in force (it will NOT be modified by the automated status update script).{/ts}</span></td></tr>
	{elseif $membershipMode}
        <tr><td colspan="2">
        {include file='CRM/Core/BillingBlock.tpl'}
        </td></tr>
 	{/if}

        {if $accessContribution and ! $membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}
        <tr id="contri">
            <td class="label">{if $onlinePendingContributionId}{ts}Update Payment Status{/ts}{else}{$form.record_contribution.label}{/if}</td>
            <td>{$form.record_contribution.html}<br />
                <span class="description">{ts}Check this box to enter or update payment information. You will also be able to generate a customized receipt.{/ts}</span></td>
            </tr>
        <tr><td colspan="2">    
        <fieldset id="recordContribution"><legend>{ts}Membership Payment and Receipt{/ts}</legend>
        <dl>
		<dt class="label">{$form.contribution_type_id.label}</dt><dd>{$form.contribution_type_id.html}<br />
                	<span class="description">{ts}Select the appropriate contribution type for this payment.{/ts}</span></dd>
		<dt class="label">{$form.total_amount.label}</dt><dd>{$form.total_amount.html}<br />
                	<span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span></dd>
        <dt class="label" >{$form.receive_date.label}</dt><dd>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</dd>  
          
        <dt class="label">{$form.payment_instrument_id.label}</dt><dd>{$form.payment_instrument_id.html}</dd>
		<div id="checkNumber"><dt class="label">{$form.check_number.label}</dt><dd>{$form.check_number.html|crmReplace:class:six}</dd></div>
	   	{if $action neq 2 }	
	    	<dt class="label">{$form.trxn_id.label}</dt><dd>{$form.trxn_id.html}</dd>
	   	{/if}		
		<dt class="label">{$form.contribution_status_id.label}</dt><dd>{$form.contribution_status_id.html}</dd>
	
       	</fieldset>
       	</td></tr>
    {else}
        <div class="spacer"></div>
	{/if}

    {if $emailExists and $outBound_option != 2 }
        <tr>
            <td class="label">{$form.send_receipt.label}</td><td>{$form.send_receipt.html}<br />
            <span class="description">{ts 1=$emailExists}Automatically email a membership confirmation and receipt to %1?{/ts}</span></td>
        </tr>
    {elseif $context eq 'standalone' and $outBound_option != 2 }
        <tr id="email-receipt" style="display:none;">
            <td class="label">{$form.send_receipt.label}</td><td>{$form.send_receipt.html}<br />
            <span class="description">{ts}Automatically email a membership confirmation and receipt to {/ts}<span id="email-address"></span>?</span></td>
        </tr>
    {/if}    
        <tr id='notice' style="display:none;">
            <td class="label">{$form.receipt_text_signup.label}</td>
            <td class="html-adjust"><span class="description">{ts}Enter a message you want included at the beginning of the emailed receipt. EXAMPLE: 'Thanks for supporting our organization with your membership.'{/ts}</span>
                 {$form.receipt_text_signup.html|crmReplace:class:huge}</td>
        </tr>
    </table>
    
    <div id="customData"></div>
    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}
	{literal}
		<script type="text/javascript">
			cj(document).ready(function() {
				{/literal}
				buildCustomData( '{$customDataType}' );
				{if $customDataSubType}
					buildCustomData( '{$customDataType}', {$customDataSubType} );
				{/if}
				{literal}
			});
		</script>
	{/literal}
	{if $accessContribution and $action eq 2 and $rows.0.contribution_id}
        <fieldset>	 
            {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
        </fieldset>
	{/if}
   {/if}
    
    <div class="spacer"></div>
    </div>
</fieldset>
<div class="html-adjust">{$form.buttons.html}</div>
{if $action neq 8} {* Jscript additions not need for Delete action *} 
{if $accessContribution and !$membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="record_contribution"
    trigger_value       =""
    target_element_id   ="recordContribution" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="payment_instrument_id"
    trigger_value       = '4'
    target_element_id   ="checkNumber" 
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}
{/if}
{if ($emailExists and $outBound_option != 2) OR $context eq 'standalone' }
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="notice" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
{literal}
<script type="text/javascript">
{/literal}
{if !$membershipMode}
{literal}
showHideMemberStatus();

function showHideMemberStatus() {
	if (document.getElementsByName("is_override")[0].checked == true) {
	   cj('#memberStatus').show( );
       cj('#memberStatus_show').hide( );
	} else {
	   cj('#memberStatus').hide( );
       cj('#memberStatus_show').show( );
	}
}
{/literal}
{/if}
{literal}
function setPaymentBlock( memType ) 
{
    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/memType' h=0}"{literal};
    
    cj.post( dataUrl, {mtype: memType}, function( data ) {
        cj("#contribution_type_id").val( data.contribution_type_id );
        cj("#total_amount").val( data.total_amount );
    }, 'json');    
}

{/literal}
{if $context eq 'standalone' and $outBound_option != 2 }
{literal}
cj( function( ) {
    cj("#contact").blur( function( ) {
        checkEmail( );
    } );
    checkEmail( );
});
function checkEmail( ) {
    var contactID = cj("input[name=contact_select_id]").val();
    if ( contactID ) {
        var postUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' h=0}{literal}";
        cj.post( postUrl, {contact_id: contactID},
            function ( response ) {
                if ( response ) {
                    cj("#email-receipt").show( );
                    if ( cj("#send_receipt").is(':checked') ) {
                        cj("#notice").show( );
                    }
                
                    cj("#email-address").html( response );
                } else {
                    cj("#email-receipt").hide( );
                    cj("#notice").hide( );
                }
            }
        );
    }
}
{/literal}
{/if}
</script>
{/if} {* closing of delete check if *} 
{/if}{* closing of custom data if *}
