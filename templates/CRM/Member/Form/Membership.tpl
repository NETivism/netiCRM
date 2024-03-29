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
    
        <p>{ts}You will not be able to send an automatic email receipt for this Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the Membership.{/ts}</p>
</div>
{/if}
{if $context NEQ 'standalone'}
   <h3>{if $action eq 1}{ts}New Membership{/ts}{elseif $action eq 2}{ts}Edit Membership{/ts}{else}{ts}Delete Membership{/ts}{/if}</h3>
{/if}
{if $membershipMode}
    <div id="help">
        {ts 1=$displayName 2=$registerMode}Use this form to submit Membership Record on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    </div>
{/if}
<div class="crm-block crm-form-block crm-membership-form-block">
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {if $action eq 8}
      <div class="messages status">
          {ts}Are you sure you want to delete the selected memberships?{/ts}
      </div>
    {else}
    <table class="form-layout-compressed">
        {if $context neq 'standalone'}
            <tr>
                <td class="font-size12pt label"><strong>{ts}Member{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
            </tr>
        {else}
            {include file="CRM/Contact/Form/NewContact.tpl"}
        {/if}
    {if $membershipMode}
	    <tr><td class="label">{$form.payment_processor_id.label}</td><td>{$form.payment_processor_id.html}</td></tr>
	{/if}
 	<tr class="crm-membership-form-block-membership_type_id"><td class="label">{$form.membership_type_id.label}</td><td>{$form.membership_type_id.html}
    {if $member_is_test} {ts}(test){/ts}{/if}<br />
        <span class="description">{ts}Select Membership Organization and then Membership Type.{/ts}</span></td></tr>	
    <tr class="crm-membership-form-block-source"><td class="label">{$form.source.label}</td><td>&nbsp;{$form.source.html}<br />
        <span class="description">{ts}Source of this membership. This value is searchable.{/ts}</span></td></tr>
	<tr class="crm-membership-form-block-join_date"><td class="label">{$form.join_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=join_date}
		<br />
        <span class="description">{ts}When did this contact first become a member?{/ts}</span></td></tr>
 	<tr class="crm-membership-form-block-start_date"><td class="label">{$form.start_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}
		<br />
        <span class="description">{ts}First day of current continuous membership period. Start Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
 	<tr class="crm-membership-form-block-end_date"><td class="label">{$form.end_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}
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
        {if $form.skip_status_cal.label}
        <tr id="skipStatusCal">
          <td class="label">{$form.skip_status_cal.label}</td><td class="view-value">{$form.skip_status_cal.html}<br />
          <span class="description">{ts}Check this will skip membership status automatic calculate by start and end date.{/ts}{ts}It's default when membership status is pending{/ts}</span>
          </td>
        </tr>
        {/if}
	{elseif $membershipMode}
        <tr class="crm-membership-form-block-billing"><td colspan="2">
        {include file='CRM/Core/BillingBlock.tpl'}
        </td></tr>
 	{/if}

        {if $accessContribution and ! $membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}
        <tr id="contri">
            <td class="label">{if $onlinePendingContributionId}{ts}Update Payment Status{/ts}{else}{$form.record_contribution.label}{/if}</td>
            <td>{$form.record_contribution.html}<br />
                <span class="description">{ts}Check this box to enter or update payment information. You will also be able to generate a customized receipt.{/ts}</span></td>
            </tr>
        <tr class="crm-membership-form-block-record_contribution"><td colspan="2">    
          <fieldset id="recordContribution"><legend>{ts}Membership Payment and Receipt{/ts}</legend>
              <table>
                  <tr class="crm-membership-form-block-contribution_type_id">
                      <td class="label">{$form.contribution_type_id.label}</td>
                      <td>{$form.contribution_type_id.html}<br />
                      <span class="description">{ts}Select the appropriate contribution type for this payment.{/ts}</span></td>
                  </tr>
                  <tr class="crm-membership-form-block-total_amount">
                      <td class="label">{$form.total_amount.label}</td>
                      <td>{$form.total_amount.html}<br />
                	  <span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span></td>
                  </tr>
                  <tr class="crm-membership-form-block-receive_date">
                      <td class="label">{$form.receive_date.label}</td>
                      <td>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</td>  
                  </tr>
                  <tr class="crm-membership-form-block-payment_instrument_id">
                      <td class="label">{$form.payment_instrument_id.label}</td>
                      <td>{$form.payment_instrument_id.html}</td>
                  </tr>
		          <tr id="checkNumber" class="crm-membership-form-block-check_number">
                      <td class="label">{$form.check_number.label}</td>
                      <td>{$form.check_number.html|crmReplace:class:six}</td>
                  </tr>
	   	       {if $action neq 2 }	
                  <tr class="crm-membership-form-block-trxn_id">
	    	          <td class="label">{$form.trxn_id.label}</td>
                      <td>{$form.trxn_id.html}</td>
                  </tr>
	   	       {/if}
                  <tr class="crm-membership-form-block-contribution_status_id">		
		              <td class="label">{$form.contribution_status_id.label}</td>
                      <td>{$form.contribution_status_id.html}</td>
                  </tr>
                  <tr id="receipt" class="crm-contribution-form-block-receipt">
                    <td class="label"><label>{ts}Receipt{/ts}</label></td>
                    <td>
                      <div class="have-receipt"><input value="1" class="form-checkbox" type="checkbox" name="have_receipt" id="have_receipt" /> <span class="description">{ts}Have receipt?{/ts}</span></div>
                      <div id="receipt-option">
                    {if $emailExists and $outBound_option != 2 }
                        <div class="crm-receipt-option crm-membership-form-block-send_receipt">
                            <div class="label">{$form.send_receipt.label}</div><div>{$form.send_receipt.html}
                            <span class="description">{ts 1=$emailExists}Automatically email a membership confirmation and receipt to %1?{/ts}</span></div>
                        </div>
                    {elseif $context eq 'standalone' and $outBound_option != 2 }
                        <div id="email-receipt" style="display:none;">
                            <div class="label">{$form.send_receipt.label}</div><div>{$form.send_receipt.html}<br />
                            <span class="description">{ts}Automatically email a membership confirmation and receipt to {/ts}<span id="email-address"></span>?</span></div>
                        </div>
                    {/if}    
                        <div id='notice' style="display:none;">
                            <div class="label">{$form.receipt_text_signup.label}</div>
                            <div class="html-adjust"><span class="description">{ts}If you need to include a special message for this member, enter it here. Otherwise, the confirmation email will include the standard receipt message configured under System Message Templates.{/ts}</span>
                                 {$form.receipt_text_signup.html}</div>
                        </div>
                        <div class="crm-receipt-option">
                          <div class="label">{$form.receipt_date.label}</div>
                          <div>{include file="CRM/common/jcalendar.tpl" elementName=receipt_date}<br />
                              <span class="description">{ts}Date that a receipt was sent to the contributor.{/ts}</span>
                          </div>
                        </div>
                        <div class="crm-receipt-option">
                          <div class="label">{$form.receipt_id.label}</div>
                          <div>{$form.receipt_id.html}<br />
                          <span class="description">{ts 1=$receipt_id_setting}Receipt ID will generate automatically based on receive date and <a href="%1" target="_blank">prefix settings</a>.{/ts}</span></div>
                        </div>
                      </div>
                    </td>
                  </tr>
              </table>
          </fieldset>
        </td></tr>
    {else}
        <div class="spacer"></div>
	{/if}

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
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div> <!-- end form-block -->

{if $action neq 8} {* Jscript additions not need for Delete action *} 
{if $accessContribution and !$membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}

{literal}
<script type="text/javascript">
cj(document).ready(function(){
   if(cj('#receipt_date').val()){
     cj('#have_receipt').attr('checked', 'checked');
     cj('#have_receipt').attr('disabled', 'disabled');
   }
   else{
     cj('#receipt-option').hide();
   }
   cj('#have_receipt').on('click', function(){
     if(cj(this).attr('checked') == 'checked'){
       cj('#send_receipt').attr("checked", "checked");{/literal}
{if ($emailExists and $outBound_option != 2) OR $context eq 'standalone' }
       cj('#notice').show();
{/if}{literal}
       var d = new Date();
       if(cj("#receive_date").length){
         cj("#receipt_date").datepicker('setDate', cj("#receive_date").val());

         if(cj("#receive_date_time").val()){
           cj("#receipt_date_time").val(cj("#receive_date_time").val());
         }
         else{
           cj("#receipt_date_time").val(d.getHours()+':'+d.getMinutes());
         }
       }
       else{
         cj("#receipt_date").datepicker('setDate', d);
         cj("#receipt_date_time").val(d.getHours()+':'+d.getMinutes());
       }
       cj('#receipt-option').show();
     }
     else{
       cj('#receipt-option').hide();
       cj('#send_receipt').removeAttr("checked");
       cj('#notice').hide();
       clearDateTime('receipt_date');
     }
   });
});
cj( function( ) {
    cj('#record_contribution').click( function( ) {
        if ( cj(this).attr('checked') ) {
            cj('#recordContribution').show( );
            setPaymentBlock( );
        } else {
            cj('#recordContribution').hide( );
        }
    });
    
    cj('#membership_type_id\\[1\\]').change( function( ) {
        setPaymentBlock( );
    });
});
</script>
{/literal}

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
{literal}
<script type="text/javascript">
{/literal}
{if !$membershipMode}
{literal}
showHideMemberStatus();

function showHideMemberStatus() {
	if (document.getElementsByName("is_override")[0].checked == true) {
    cj("#skipStatusCal").hide();
	  cj('#memberStatus').show( );
    cj('#memberStatus_show').hide( );
	} else {
    cj("#skipStatusCal").show();
	  cj('#memberStatus').hide( );
    cj('#memberStatus_show').show( );
	}
}
{/literal}
{/if}
{literal}
function setPaymentBlock( ) {
    var memType = cj('#membership_type_id\\[1\\]').val( );
    
    if ( !memType ) {
        return;
    }
    
    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/memType' h=0}"{literal};
    
    cj.post( dataUrl, {mtype: memType}, function( data ) {
        cj("#contribution_type_id").val( data.contribution_type_id );
        cj("#total_amount").val( data.total_amount );
    }, 'json');    
}

{/literal}
{if $context eq 'standalone' and $outBound_option != 2 }
{literal}
cj(document).ready(function( ) {
  cj("#contact_1").blur( function( ) {
    checkEmail( );
  });
  checkEmail( );
});
function checkEmail( ) {
    var contactID = cj("input[name='contact_select_id[1]']").val();
    if ( contactID ) {
        var postUrl = "{/literal}{crmURL p='civicrm/ajax/getemail' h=0}{literal}";
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
