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
{* this template is used for adding/editing/deleting contribution *}

{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{elseif $priceSetId}
  {include file="CRM/Price/Form/PriceSet.tpl" context="standalone"}
{elseif $showAdditionalInfo and $formType }
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}

{if $contributionMode}
    <h3>{if $ppID}{ts}Credit Card Pledge Payment{/ts}{else}{ts}Credit Card Contribution{/ts}{/if}</h3>
{elseif $context NEQ 'standalone'}
    <h3>{if $action eq 1 or $action eq 1024}{if $ppID}{ts}Pledge Payment{/ts}{else}{ts}New Contribution{/ts}{/if}{elseif $action eq 8}{ts}Delete Contribution{/ts}{else}{ts}Edit Contribution{/ts}{/if}</h3> 
{/if}

<div class="crm-block crm-form-block crm-contribution-form-block"> 

{if $contributionMode == 'test' }
    {assign var=contribMode value="TEST"}
{elseif $contributionMode == 'live'}
    {assign var=contribMode value="LIVE"}
{/if}

{if !$email and $action neq 8 and $context neq 'standalone'}
<div class="messages status">
  &nbsp;{ts}You will not be able to send an automatic email receipt for this contribution because there is no email address recorded for this contact. If you want a receipt to be sent when this contribution is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the contribution.{/ts}
</div>
{/if}
{if $contributionMode}
    <div id="help">
        {ts 1=$displayName 2=$contribMode}Use this form to submit a new contribution on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    </div>
{/if}
   {if $action eq 8} 
      <div class="messages status"> 
           
          {ts}WARNING: Deleting this contribution will result in the loss of the associated financial transactions (if any).{/ts} {ts}Do you want to continue?{/ts}
      </div> 
   {else}
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
      {if $isOnline}{assign var=valueStyle value=" class='view-value'"}{else}{assign var=valueStyle value=""}{/if}
      <table class="form-layout-compressed">
        {if $is_test}
            <tr>
                <td class="label">{$form.is_test.label}</td><td>{$form.is_test.html}</td>
            </tr>
        {/if}
        {if $context neq 'standalone'}
            <tr>
                <td class="font-size12pt label"><strong>{ts}Contributor{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
            </tr>
        {else}
            {include file="CRM/Contact/Form/NewContact.tpl"}
        {/if}
        {if $form.participant_id.label}
          <tr class="crm-contribution-form-block-participant_id"><td class="label nowrap">{$form.participant_id.label}</td><td><a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&action=view&id=$participantId&cid=$contactID"}" target="_blank">{$participantId}</a></td></tr>
        {/if}
        {if $form.membership_id.label}
          <tr class="crm-contribution-form-block-membership_id"><td class="label nowrap">{$form.membership_id.label}</td><td><a href="{crmURL p='civicrm/contact/view/membership' q="reset=1&action=view&id=$membershipId&cid=$contactID"}" target="_blank">{$membershipId}</a></td></tr>
        {/if}
        {if $contributionMode}
           <tr class="crm-contribution-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
        {/if}
        <tr class="crm-contribution-form-block-contribution_type_id"><td class="label">{$form.contribution_type_id.label}</td><td{$valueStyle}>{$form.contribution_type_id.html}&nbsp;
        {if $is_test}
        {ts}(test){/ts}
        {/if} {help id="id-contribution_type"}
        </td></tr>
	
	{if $action eq 2 and $lineItem}
	    <tr>
            <td class="label">{ts}Amount{/ts}</td>
            <td>{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}</td>
        </tr>
	{else}
        <tr  class="crm-contribution-form-block-total_amount">
            <td class="label">{$form.total_amount.label}</td>
    	    <td {$valueStyle}>
        	    <span id='totalAmount'>{$form.currency.html} {$form.total_amount.html|crmReplace:class:eight}</span> 
        	    {if $hasPriceSets}
        	        <span id='totalAmountORPriceSet'> {ts}OR{/ts}</span>
        	        <span id='selectPriceSet'>{$form.price_set_id.html}</span>
                    <div id="priceset" class="hiddenElement"></div>	    
        	    {/if}
        	    
            	{if $ppID}{ts}<a href='#' onclick='adjustPayment();'>adjust payment amount</a>{/ts}{help id="adjust-payment-amount"}{/if}
	            <br /><span class="description">{ts}Actual amount given by contributor.{/ts}</span>
	    </td>
        </tr>
	    <tr id="adjust-option-type" class="crm-contribution-form-block-option_type">
            <td class="label"></td><td {$valueStyle}>{$form.option_type.html}</td> 
	    </tr>
    {/if}

        <tr  class="crm-contribution-form-block-source"><td class="label">{$form.source.label}</td><td{$valueStyle}>{$form.source.html} {help id="id-contrib_source"}</td></tr>

        {if $contributionMode}
            {if $email and $outBound_option != 2}
                <tr class="crm-contribution-form-block-is_email_receipt"><td class="label">{$form.is_email_receipt.label}</td><td>{$form.is_email_receipt.html}</td></tr>
                <tr><td class="label">&nbsp;</td><td class="description">{if $do_not_notify}<span class="font-red">{ts}Contact labelled as do not notification.{/ts}</span>{else}{ts 1=$email}Automatically email a payment notification for this contribution to %1?{/ts}{/if}</td></tr>
            {elseif $context eq 'standalone' and $outBound_option != 2 }
                <tr id="email-receipt" style="display:none;" class="crm-contribution-form-block-is_email_receipt"><td class="label">{$form.is_email_receipt.label}</td><td>{$form.is_email_receipt.html} <span class="description">{if $do_not_notify}<span class="font-red">{ts}Contact labelled as do not notification.{/ts}</span>{else}{ts}Automatically email a payment notification for this contribution to {/ts}<span id="email-address"></span>?{/if}</span></td></tr>
            {/if}
            <tr id="from_email_address" class="crm-contribution-form-block-from_email_address">
                <td class="label">{$form.from_email_address.label}</td>
                <td>{$form.from_email_address.html}</td>
            </tr>
            {if $have_attach_receipt_option}
            <tr id="is_attach_receipt" class="crm-contribution-form-block-is_attach_receipt">
                <td class="label">{$form.is_attach_receipt.label}</td>
                <td>{$form.is_attach_receipt.html}</td>
            </tr>
            {/if}
            <tr id="receiptDate" class="crm-contribution-form-block-receipt_date">
                <td class="label">{$form.receipt_date.label}</td>
                <td>{include file="CRM/common/jcalendar.tpl" elementName=receipt_date}<br />
                <span class="description">{ts}Date that a receipt was sent to the contributor.{/ts}</span></td></tr>
            <tr id="receiptId" class="crm-contribution-form-block-receipt_id">
                <td class="label">{$form.receipt_id.label}</td>
                <td>{$form.receipt_id.html} <a href="#receipt" id="manual-receipt-id">Change receipt number</a><br />
                <span class="description">{ts 1=$receipt_id_setting}Receipt Id will generate automatically based on <a href="%1" target="_blank">settings</a>.{/ts}</span></td></tr>
        {/if}
        {if !$contributionMode}
            <tr class="crm-contribution-form-block-receive_date">
                <td class="label">{$form.receive_date.label}</td>
                <td{$valueStyle}>{if $hideCalender neq true}{include file="CRM/common/jcalendar.tpl" elementName=receive_date}{else}{$receive_date|crmDate}{/if}<br />
                    <span class="description">{ts}The date this contribution was received.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-contribution-form-block-payment_instrument_id">
                <td class="label">{$form.payment_instrument_id.label}</td><td{$valueStyle}>{$form.payment_instrument_id.html}<br />
                    <span class="description">{ts}Leave blank for non-monetary contributions.{/ts}</span>
                </td>
            </tr>
            {if $showCheckNumber || !$isOnline}  
                <tr id="checkNumber" class="crm-contribution-form-block-check_number"><td class="label">{$form.check_number.label}</td><td>{$form.check_number.html|crmReplace:class:six}</td></tr>
            {/if}
            <tr class="crm-contribution-form-block-trxn_id"><td class="label">{$form.trxn_id.label}</td><td{$valueStyle}>{$form.trxn_id.html|crmReplace:class:twelve} {help id="id-trans_id"}</td></tr>
            <tr id="receipt" class="crm-contribution-form-block-receipt">
              <td class="label"><label>{ts}Receipt{/ts}</label></td>
              <td>
                <div class="have-receipt"><input value="1" class="form-checkbox" type="checkbox" name="have_receipt" id="have_receipt" /> <span class="description">{ts}Have receipt?{/ts}</span></div>
                <div id="dialog-confirm-receipt" title="{ts}Procceed Receipt Generation?{/ts}" style="display:none;">
                  <p><span class="zmdi zmdi-alert-circle" style="margin: 0 7px 0 0;"></span>{ts}This contribution type is not deductible. Are you sure you want to generate receipt date and receipt ID?{/ts}</p>
                  <p>{ts}Are you sure you want to continue?{/ts}</p>
                </div>
                <div id="receipt-option">
                  {if $email and $outBound_option != 2}
                    <div class="crm-receipt-option crm-contribution-form-block-is_email_receipt">
                      <div class="label">{$form.is_email_receipt.label}</div>
                      <div>{$form.is_email_receipt.html} <span class="description">{if $do_not_notify}<span class="font-red">{ts}Contact labelled as do not notification.{/ts}</span>{else}{ts 1=$email}Automatically email a payment notification for this contribution to %1?{/ts}{/if}</span></div>
                    </div>
                  {elseif $context eq 'standalone' and $outBound_option != 2}
                    <div id="email-receipt" style="display:none;" class="crm-contribution-form-block-is_email_receipt">
                      <div class="label">{$form.is_email_receipt.label}</div>
                      <div>{$form.is_email_receipt.html} <span class="description">{if $do_not_notify}<span class="font-red">{ts}Contact labelled as do not notification.{/ts}</span>{else}{ts}Automatically email a payment notification for this contribution to {/ts}<span id="email-address"></span>?{/if}</span></div>
                    </div>
                  {/if}
                  <div id="from_email_address" class="crm-contribution-form-block-from_email_address">
                    <div class="label">{$form.from_email_address.label}</div>
                    <div>
                      {$form.from_email_address.html}
                      <span class="description font-red">
                        {ts}Only verified domain of email can be set as sender.{/ts} {ts}Otherwise, the email will be hidden on above select list.{/ts}<br>
                        {capture assign="from_email_admin_path"}{crmURL p="civicrm/admin/from_email" q="reset=1"}{/capture}
                        {ts 1=$from_email_admin_path}Make sure at least one of your email domain verified in <a href="%1">FROM email address</a> list.{/ts}
                      </span>
                    </div>
                  </div>
                  {if $have_attach_receipt_option}
                  <div id="is_attach_receipt" class="crm-contribution-form-block-is_attach_receipt">
                    <div class="label">{$form.is_attach_receipt.label}</div>
                    <div>{$form.is_attach_receipt.html}<span class="description">{ts}Add receipt as attachment in email.{/ts}</span></div>
                  </div>
                  {/if}
                  <div class="crm-receipt-option">
                    <div class="label">{$form.receipt_date.label}</div>
                    <div>{include file="CRM/common/jcalendar.tpl" elementName=receipt_date}<br />
                        <span class="description">{ts}Date that a receipt was sent to the contributor.{/ts}</span>
                    </div>
                  </div>
                  <div class="crm-receipt-option">
                    <div class="label">{$form.receipt_id.label}</div>
                    <div>{$form.receipt_id.html} <a href="#receipt" id="manual-receipt-id">{ts}Change receipt number{/ts}</a><br />
                    <span class="description">{ts 1=$receipt_id_setting}Receipt ID will generate automatically based on receive date and <a href="%1" target="_blank">prefix settings</a>.{/ts}</span></div>
                  </div>
                </div>
              </td>
            </tr>
            {if ($participantId || $membershipId) && $form.update_related_component.label}
            <tr class="crm-contribution-form-block-update_related_component"><td class="label">{$form.update_related_component.label}</td><td>
              {if $membershipId}<a href="{crmURL p='civicrm/contact/view/membership' q="reset=1&action=view&id=$membershipId&cid=$contactID"}" target="_blank">{ts}Membership{/ts}: {$membershipId}</a>
              {elseif $participantId}<a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&action=view&id=$participantId&cid=$contactID"}" target="_blank">{ts}Participant{/ts}: {$participantId}</a>
              {/if}
              {$form.update_related_component.html}
              <span class="description">{ts}Check this will also update related component status.{/ts}</span></div>
            </td></tr>
            {/if}
            <tr class="crm-contribution-form-block-contribution_status_id"><td class="label">{$form.contribution_status_id.label}</td><td>{$form.contribution_status_id.html}
            {if $contribution_status_id eq 2}{if $is_pay_later }: {ts}Pay Later{/ts} {else}: {ts}Incomplete Transaction{/ts}{/if}{/if}
              <div class="description"></div>
            </td></tr>
            {* Cancellation fields are hidden unless contribution status is set to Cancelled *}
            <tr id="cancelInfo" class="crm-contribution-form-block-cancelInfo"> 
                <td>&nbsp;</td> 
                <td><fieldset><legend>{ts}Cancellation or Failure Information{/ts}</legend>
                <table class="form-layout-compressed">
                  <tr id="cancelDate" class="crm-contribution-form-block-cancel_date">
                    <td class="label">{$form.cancel_date.label}</td>
                    <td>
                        {if $hideCalendar neq true}
                            {include file="CRM/common/jcalendar.tpl" elementName=cancel_date}
                        {else}
                            {$form.cancel_date.html|crmDate}
                        {/if}
                   </td>
                  </tr>
                  <tr id="cancelDescription" class="crm-contribution-form-block-cancel_reason"><td class="label">&nbsp;</td><td class="description">{ts}Enter the cancellation date, or you can skip this field and the cancellation date will be automatically set to TODAY.{/ts}</td></tr>
                  <tr id="cancelReason"><td class="label" style="vertical-align: top;">{$form.cancel_reason.label}</td><td>{$form.cancel_reason.html}</td></tr>
               </table>
               </fieldset>
               </td>
            </tr>
        {/if}

        <tr class="crm-contribution-form-block-soft_credit_to"><td class="label">{$form.soft_credit_to.label}</td>
            <td>{$form.soft_credit_to.html} {help id="id-soft_credit"}</td>
        </tr>
	    {if $action eq 2 and $form.soft_credit_to.value} {* Include PCP honor roll fields if contrib came from PCP page *}
          <tr class="crm-contribution-form-block-pcp_made_through_id"><td class="label">{$form.pcp_made_through_id.label}</td>
            <td>{$form.pcp_made_through_id.html}</td>
          </tr>
    	    <tr class="crm-contribution-form-block-pcp_display_in_roll"><td class="label">{$form.pcp_display_in_roll.label}</td>
    	        <td>{$form.pcp_display_in_roll.html}</td>
    	    </tr>
    	    <tr id="nameID" class="crm-contribution-form-block-pcp_is_anonymous">
    	        <td></td>
    	        <td>{$form.pcp_is_anonymous.html}</td>
    	    </tr>
    	    <tr id="nickID" class="crm-contribution-form-block-pcp_roll_nickname">
    	        <td class="label">{$form.pcp_roll_nickname.label}</td>
    	        <td>{$form.pcp_roll_nickname.html}<br />
    		    <span class="description">{ts}Name displayed in the Honor Roll.{/ts}</span></td>
    	    </tr>
    	    <tr id="personalNoteID" class="crm-contribution-form-block-pcp_personal_note">
    	        <td class="label" style="vertical-align: top">{$form.pcp_personal_note.label}</td>
    	        <td>{$form.pcp_personal_note.html}
                    <span class="description">{ts}Personal message submitted by contributor for display in the Honor Roll.{/ts}</span>
    		    </td>
    	    </tr>
        {/if}	
      </table>

    <div id="customData" class="crm-contribution-form-block-customData"></div>

    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}
{include file="CRM/common/chosen.tpl" selector="select#payment_instrument_id, select#pcp_made_through_id"}
{literal}
<script type="text/javascript">
    cj( function( ) {
        {/literal}
        buildCustomData( '{$customDataType}' );
        {if $customDataSubType}
        buildCustomData( '{$customDataType}', {$customDataSubType} );
        {/if}
        {literal}
    });

// bind first click of accordion header to load crm-accordion-body with snippet
// everything else taken care of by cj().crm-accordions()
cj(document).ready( function() {
    cj('#adjust-option-type').hide();	
    cj('.crm-ajax-accordion .crm-accordion-header').one('click', function() { 
    	loadPanes(cj(this).attr('id')); 
    });
    cj('.crm-ajax-accordion.crm-accordion-open .crm-accordion-header').each(function(idx) {
      var paneID = cj(this).attr('id');
      window.setTimeout(function(){
        loadPanes(paneID);
      }, 800*(idx+1));
    });
});
// load panes function calls for snippet based on id of crm-accordion-header
function loadPanes( id ) {
  var url = "{/literal}{crmURL p='civicrm/contact/view/contribution' q="qfKey=`$qfKey`&pageKey=`$pageKey`&snippet=4&formType=" h=0}{literal}" + id;
  {/literal}{if $contributionMode}
  url = url + "&mode={$contributionMode}";
  {/if}{literal}
  if ( ! cj('div.'+id).html() ) {
    var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts}loading{/ts}{literal}" />&nbsp;{/literal}{ts}Loading{/ts}{literal}...';
    cj('div.'+id).html(loading);
    cj.ajax({
      url : url
    })
    .done(function(data){
      cj('div.'+id).html(data);
    });
  }
}

    var url = "{/literal}{$dataUrl}{literal}";

    cj('#soft_credit_to').autocomplete( url, { width : 180, selectFirst : false, matchContains: true
        }).result( function(event, data, formatted) { cj( "#soft_contact_id" ).val( data[1] );
    });
    {/literal}
    {if $context eq 'standalone' and $outBound_option != 2 }
    {literal}
    cj( function( ) {
        cj("#contact_1").blur( function( ) {
            checkEmail( );
        });
        checkEmail( );
    });
    function checkEmail( ) {
        var contactID = cj("input[name='contact_select_id[1]']").val();
        if ( contactID ) {
            var postUrl = "{/literal}{crmURL p='civicrm/ajax/getemail' h=0}{literal}";
            cj.post( postUrl, {contact_id: contactID, check_can_notify: true},
                function ( response ) {
                    if ( response ) {
                        cj("#email-receipt").show( );
                        cj("#email-address").html( response );
                    } else {
                        cj("#is_email_receipt").prop('checked', false);
                        cj("#from_email_address").hide();
                        cj("#email-receipt").hide( );
                    }
                }
            );
        }
    }
    {/literal}
    {/if}
</script>

<div class="accordion ui-accordion ui-widget ui-helper-reset">
    {* Additional Detail / Honoree Information / Premium Information  Fieldset *}
    {foreach from=$allPanes key=paneName item=paneValue}
            
<div class="crm-accordion-wrapper crm-ajax-accordion crm-{$paneValue.id}-accordion {if $paneValue.open eq 'true'}crm-accordion-open{else}crm-accordion-closed{/if}">
 <div class="crm-accordion-header" id="{$paneValue.id}">
  <div class="zmdi crm-accordion-pointer"></div> 

        {$paneName}
  </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">

        <div class="{$paneValue.id}"></div>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

    {/foreach}
</div>

{/if}
<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
    {literal}
    <script type="text/javascript">
     function verify( ) {
       var element = document.getElementsByName("is_email_receipt");
        if ( element[0].checked ) {
         var ok = confirm( '{/literal}{ts}Click OK to save this contribution record AND send a receipt to the contributor now{/ts}{literal}.' );    
          if (!ok ) {
            return false;
          }
        }
     }
     function status() {
       cj("#cancel_date").val('');
       cj("#cancel_reason").val('');
     }

    </script>
    {/literal}


{if $action neq 8}  
{if !$contributionMode} 
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="contribution_status_id"
    trigger_value       = '3|4'
    target_element_id   ="cancelInfo" 
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}
{if $pcp}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="pcp_display_in_roll"
    trigger_value       =""
    target_element_id   ="nameID|nickID" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="payment_instrument_id"
    trigger_value       = '4'
    target_element_id   ="checkNumber" 
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_email_receipt"
    trigger_value       = 1
    target_element_id   ="from_email_address"
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_email_receipt"
    trigger_value       = 1
    target_element_id   ="is_attach_receipt"
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}
{/if} 
{/if} {* not delete mode if*}      

    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}

{/if} {* closing of main custom data if *} 


{literal}
<script type="text/javascript">

Number.prototype.pad = function (len) {
  return (new Array(len+1).join("0") + this).slice(-len);
}
cj(document).ready(function(){

  {/literal}{if !$smarty.get.snippet}{literal}
   if(cj('#receipt_date').val()){
     cj('#have_receipt').attr('checked', 'checked');
     cj('#have_receipt').attr('disabled', 'disabled');
   }
   else{
     cj('#receipt-option').hide();
   }
   let havePremium = {/literal}{if $havePremium}1{else}0{/if}{literal};
   
   // Define dialog behavior.
  cj("#dialog-confirm-receipt").dialog({
    autoOpen: false,
    resizable: false,
    width:450,
    height:250,
    modal: true,
    buttons: {
      "{/literal}{ts}OK{/ts}{literal}": function() {
        isPassChekcedDeductible = true;
        cj(this).dialog("close");
        cj('#have_receipt')[0].click();
        return true;
      },
      "{/literal}{ts}Cancel{/ts}{literal}": function() {
        cj( this ).dialog( "close" );
        return false;
      }
    }
  });
  isPassChekcedDeductible = false;

  cj("#contribution_type_id").change(function(){
    let contributionTypeId = parseInt(cj(this).val());
    let notifySpan = cj('#have_receipt').next('.description');
    notifySpan.find('span.font-red').remove();
    if (cj('#have_receipt').attr('checked') == 'checked'){
      if (!([{/literal}{$deductible_type_ids}{literal}].includes(contributionTypeId))){
        notifySpan.append('<span class="font-red">{/literal}{ts}This contribution type is not deductible. Are you sure you want to generate receipt date and receipt ID?{/ts}{literal}</span>');
      }
    }
  });

  // Track initial contribution status value
  let initialStatusId = parseInt(cj("#contribution_status_id").val());
  
  cj("#contribution_status_id").change(function(){
    let currentStatusId = parseInt(cj(this).val());
    let statusSpan = cj('#contribution_status_id').closest('.crm-form-select').next('.description');
    statusSpan.find('span.font-red.restock-warning').remove();
    
    // Check if status changed from 2 (Pending) to 3 (Cancelled)
    if (havePremium && initialStatusId === 2 && (currentStatusId === 3 || currentStatusId === 4)) {
      statusSpan.append('<span class="font-red restock-warning">{/literal}{ts}Premium inventory linked to this donation will be restocked when saved as cancelled or failed status{/ts}{literal}</span>');
    }
  });

   cj('#have_receipt').on('click', function(){
     if(cj(this).attr('checked') == 'checked'){
      let contributionTypeId = parseInt(cj('#contribution_type_id').val());
      if (!([{/literal}{$deductible_type_ids}{literal}].includes(contributionTypeId)) && !isPassChekcedDeductible){
        cj("#dialog-confirm-receipt").dialog('open');
        return false;
      }
       var d = new Date();
       cj("#receipt_date").datepicker('setDate', d);
       cj("#receipt_date_time").val(d.getHours().pad(2)+':'+d.getMinutes().pad(2));
       cj('#receipt-option').show();
     }
     else{
       isPassChekcedDeductible = false;
       cj('#receipt-option').hide();
       clearDateTime('receipt_date');
     }
   });
   {/literal}{/if}{literal}
   cj("#manual-receipt-id").click(function(e){
     var okok = confirm("{/literal}{ts}This action will break auto serial number. Please confirm you really want to change receipt number manually.{/ts}{literal}");
     if(okok){
       cj("#receipt_id").removeAttr("readonly").removeClass("readonly").focus();
       cj("#manual-receipt-id").remove();
     }
     unbind("#manual-receipt-id", 'click');
   });
});
cj(function() {
   cj().crmaccordions(); 
});
</script>
{/literal}


{literal}
<script type="text/javascript" >
{/literal}

 {if $pcp}{literal}pcpAnonymous();{/literal}{/if}
 {if $checkReceipt}{literal}checkReceipt();{/literal}{/if}
 // load form during form rule.
 {if $buildPriceSet}{literal}buildAmount( );{/literal}
 {/if}
 {literal}

function pcpAnonymous( ) {
    // clear nickname field if anonymous is true
    if ( document.getElementsByName("pcp_is_anonymous")[1].checked ) { 
        document.getElementById('pcp_roll_nickname').value = '';
	document.getElementById('pcp_personal_note').value = '';
    }
    if ( ! document.getElementsByName("pcp_display_in_roll")[0].checked ) { 
        hide('nickID', 'table-row');
        hide('nameID', 'table-row');
	hide('personalNoteID', 'table-row');
    } else {
        if ( document.getElementsByName("pcp_is_anonymous")[0].checked ) {
            show('nameID', 'table-row');
            show('nickID', 'table-row');
	    show('personalNoteID', 'table-row');
        } else {
            show('nameID', 'table-row');
            hide('nickID', 'table-row');
	    hide('personalNoteID', 'table-row');
        }
    }
}

function buildAmount( priceSetId ) {

  if ( !priceSetId ) priceSetId = cj("#price_set_id").val( );

  var fname = '#priceset';
  if ( !priceSetId ) {
      // hide price set fields.
      cj( fname ).hide( ); 

      // show/hide price set amount and total amount.
      cj( "#totalAmountORPriceSet" ).show( );
      cj( "#totalAmount").show( );

      return;
  }

  var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&priceSetId=' + priceSetId + '&pageKey={/literal}{$pageKey}{literal}';

  cj.ajax({
		url: dataUrl,
  })
  .done(function(data){
    cj(fname).html(data).show();

    // freeze total amount text field.
    cj( "#total_amount").val('');
    cj( "#totalAmountORPriceSet" ).hide();
    cj( "#totalAmount").hide();
  });
}
function adjustPayment( ) {
cj('#adjust-option-type').show();		    	    
cj("#total_amount").removeAttr("READONLY");
cj("#total_amount").css('background-color', '#ffffff');
}
function checkReceipt( ) {
  cj("#receipt_id")
  .change(function() {
    var receiptId = cj("#receipt_id").val();
    var receiptDate = cj("#receipt_date").val();
    var receiptTime = cj("#receipt_date_time").val();
    if (receiptId.length == 0) {
      if (receiptDate.length != 0 && receiptTime.length != 0) {
        cj("#receipt_id").after( "<span class='alter' style='color:red'>{/literal}{ts}Receipt ID can not be empty. Because Receipt Date Time and Receipt Date not empty.{/ts}{literal}</span>");
      }
    } else {
      cj(".alter").remove();
    }
  });
  cj(".crm-clear-link a")
  .click(function() {
    var receiptId = cj("#receipt_id").val();
    if (receiptId.length == 0) {
      cj(".alter").remove();
    }
  });
}
</script>
{/literal}