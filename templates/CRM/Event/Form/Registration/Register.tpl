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
{if $ppType}
  {include file="CRM/Core/BillingBlock.tpl"}
  {if $paymentProcessor.description}
    <div class="crm-section payment-description">
        <div class="label"></div>
        <div class="content">
            {$paymentProcessor.description}
        </div>
        <div class="clear"></div>
    </div>
  {/if}

{else}{*ppType if else*}

<div id="paypalExpress">
{* Put PayPal Express button after customPost block since it's the submit button in this case. *}
{if $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    {assign var=expressButtonName value='_qf_Register_upload_express'}
    <fieldset class="crm-group payPalExpress-group"><legend>{ts}Checkout with PayPal{/ts}</legend>
    <div class="description">{ts}Click the PayPal button to continue.{/ts}</div>
	<div>{$form.$expressButtonName.html} <span style="font-size:11px; font-family: Arial, Verdana;">Checkout securely.  Pay without sharing your financial information. </span>
    </div>
    </fieldset>
{/if}
</div>
{include file="CRM/Event/Form/Registration/Progress.tpl"}
<!--progress-separator-->
{if $action & 1024}
  {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-event-register-form-block">

{if $event.intro_text}
  <div id="intro_text" class="crm-section intro_text-section">
      <p>{$event.intro_text}</p>
  </div>
{/if}
<!--intro-separator-->
{if $contact_id and $smarty.get.id and !$allowConfirmation}
  <div id="register-who">
    <a href="#register-now" id="register-me" class="button"><span><i class="zmdi zmdi-sign-in"></i>{ts 1=$display_name}Registering Yourself (%1){/ts}</span></a>
    <a href="{crmURL p='civicrm/event/register' q="cid=0&reset=1&id=`$event.id`"}" title="{ts}Click here to register a different person for this event.{/ts}" class="button"><span><i class="zmdi zmdi-account-add"></i>{ts}Registering Others{/ts}</span></a>
  </div>
  <div class="clear"></div>
{/if}
<div id="register-now">
  {if $form.additional_participants.html}
    <div class="crm-section additional_participants-section" id="noOfparticipants">
      <div class="label">{$form.additional_participants.label}</div>
      <div class="content">
        {$form.additional_participants.html}<br />
        <span class="description">{ts}Fill in your registration information on this page. If you are registering additional people, you will be able to enter their registration information after you complete this page and click &quot;Continue&quot;.{/ts}</span>
      </div>
    </div>
    <div class="clear"></div>
  {/if}

  {assign var=n value=email-$bltID}
  <div class="crm-section email-section">
    <div class="label">{$form.$n.label}</div>
    <div class="content">{$form.$n.html}</div>
    <div class="clear"></div>
  </div>


  {* User account registration option. Displays if enabled for one of the profiles on this page. *}
  {include file="CRM/common/CMSUser.tpl"}
  {include file="CRM/UF/Form/Block.tpl" fields=$customPre} 
  {include file="CRM/UF/Form/Block.tpl" fields=$customPost}   
  {include file="CRM/common/moveEmail.tpl"}
<!--ufform-separator-->

  {if $priceSet}
    <fieldset id="priceset" class="crm-group priceset-group"><legend>{$event.fee_label}</legend>
      {include file="CRM/Price/Form/PriceSet.tpl"}
      {include file="CRM/Price/Form/ParticipantCount.tpl"}
    </fieldset>
    {if $event.is_pay_later && $show_payment_processors}
      <div class="crm-section pay_later-section">
        <div class="label">{ts}Payment Method{/ts}</div>
        <div class="content">
          <input type="checkbox" checked="checked" disabled="disabled"/> {$event.pay_later_text|nl2br}<br />
          <span class="description">{$event.pay_later_receipt|nl2br}</span>
        </div>
        <div class="clear"></div>
      </div>
    {/if}
  {else}
    {if $paidEvent}
      <fieldset id="priceset" class="crm-group priceset-group"><legend>{$event.fee_label}</legend></fieldset>
      <div class="crm-section paid_event-section">
        <div class="label">&nbsp;<span class="marker">*</span></div>
        <div class="content">{$form.amount.html}</div>
        <div class="clear"></div>
      </div>
      {if $event.is_pay_later && $show_payment_processors}
        <div class="crm-section pay_later-section">
          <div class="label">{ts}Payment Method{/ts}</div>
          <div class="content"><input type="checkbox" checked="checked" disabled="disabled"/>{$event.pay_later_text}<br />
            <span class="description">{$event.pay_later_receipt}</span>
          </div>
          <div class="clear"></div>
        </div>
      {/if}
    {/if}
  {/if}

  <div class="crm-section payment_processor-section">
    <div class="label">{$form.payment_processor.label}</div>
    <div class="content">{$form.payment_processor.html}</div>
    <div class="clear"></div>
  </div>
  <div id="billing-payment-block"></div>
  {include file="CRM/common/paymentBlock.tpl"}
<!--payment-separator-->

  {if $isCaptcha}
    {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}

  <div id="crm-submit-buttons" class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {if $event.footer_text}
  <div id="footer_text" class="crm-section event_footer_text-section">
    <p>{$event.footer_text}</p>
  </div>
  {/if}
</div><!-- end register-me -->

</div><!-- end crm-event-register-form-block -->
{literal} 
<script type="text/javascript">

    function allowParticipant( ) { 		
	{/literal}{if $allowGroupOnWaitlist}{literal}
	    var additionalParticipants = cj('#additional_participants').val();
	    var pricesetParticipantCount = 0;
	    {/literal}{if $priceSet}{literal}
	      pricesetParticipantCount = pPartiCount;
	    {/literal}{/if}{literal}
	
	    allowGroupOnWaitlist( additionalParticipants, pricesetParticipantCount );
	{/literal}{/if}{literal}
    }

    {/literal}{if ($form.is_pay_later or $bypassPayment) and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    {literal} 
       showHidePayPalExpressOption( );
    {/literal}{/if}{literal}

    function showHidePayPalExpressOption( )
    {
	var payLaterElement = {/literal}{if $form.is_pay_later}true{else}false{/if}{literal};
	if ( ( cj("#bypass_payment").val( ) == 1 ) ||
	     ( payLaterElement && document.getElementsByName('is_pay_later')[0].checked ) ) {
		show("crm-submit-buttons");
		hide("paypalExpress");
	} else {
		show("paypalExpress");
		hide("crm-submit-buttons");
	}
    }

    {/literal}{if ($form.is_pay_later or $bypassPayment) and $showHidePaymentInformation}{literal} 
       showHidePaymentInfo( );
    {/literal} {/if}{literal}

    function showHidePaymentInfo( )
    {	
	var payLater = {/literal}{if $form.is_pay_later}true{else}false{/if}{literal};

	if ( ( cj("#bypass_payment").val( ) == 1 ) ||
	     ( payLater && document.getElementsByName('is_pay_later')[0].checked ) ) {
	     hide( 'billing-payment-block' );		
	} else {
             show( 'billing-payment-block' );
	}
    }
    
    {/literal}{if $allowGroupOnWaitlist}{literal}
       allowGroupOnWaitlist( 0, 0 );
    {/literal}{/if}{literal}
    
    function allowGroupOnWaitlist( additionalParticipants, pricesetParticipantCount )
    {	
      {/literal}{if $isAdditionalParticipants}{literal}
      if ( !additionalParticipants ) {
      	additionalParticipants = cj('#additional_participants').val();
      }
      {/literal}{else}{literal}
        additionalParticipants = 0;
      {/literal}{/if}{literal}

      additionalParticipants = parseInt( additionalParticipants );
      if ( ! additionalParticipants ) {
      	 additionalParticipants = 0;
      }     

      var availableRegistrations = {/literal}'{$availableRegistrations}'{literal};
      var totalParticipants = parseInt( additionalParticipants ) + 1;
      
      if ( pricesetParticipantCount ) {
      	// add priceset count if any 
      	totalParticipants += parseInt(pricesetParticipantCount) - 1;
      }
      var isrequireApproval = {/literal}'{$requireApprovalMsg}'{literal};
 
      if ( totalParticipants > availableRegistrations ) {
         cj( "#id-waitlist-msg" ).show( );
         cj( "#id-waitlist-approval-msg" ).show( );

         //set the value for hidden bypass payment. 
         cj( "#bypass_payment").val( 1 );

         //hide pay later.
         {/literal}{if $form.is_pay_later}{literal} 
	    cj("#is-pay-later").hide( );
         {/literal} {/if}{literal}
 
      }	else {
         if ( isrequireApproval ) {
            cj( "#id-waitlist-approval-msg" ).show( );
            cj( "#id-waitlist-msg" ).hide( );
         } else {
            cj( "#id-waitlist-approval-msg" ).hide( );
         }
         //reset value since user don't want or not eligible for waitlist 
         cj( "#bypass_payment").val( 0 );

         //need to show paylater if exists.
         {/literal}{if $form.is_pay_later}{literal} 
	    cj("#is-pay-later").show( );
         {/literal} {/if}{literal}
      }

      //now call showhide payment info.
      {/literal}
      {if ($form.is_pay_later or $bypassPayment) and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}{literal} 
         showHidePayPalExpressOption( );
      {/literal}{/if}
      {literal}
  
      {/literal}{if ($form.is_pay_later or $bypassPayment) and $showHidePaymentInformation}{literal} 
         showHidePaymentInfo( );
      {/literal}{/if}{literal}
    }

  cj('form input:not([type="submit"])').keydown(function (e) {
    if (e.keyCode == 13) {
      if(cj(this).attr('id') == 'neticrm_sort_name_navigation'){
        return true;
      }
      var inputs = cj(this).parents("form").eq(0).find(':input:visible');
      if (inputs[inputs.index(this) + 1] != null && ( inputs.index(this) + 1 ) < inputs.length) {                    
          inputs[inputs.index(this) + 1].focus();
      }
      cj(this).blur();
      e.preventDefault();
      return false;
    }
  });
  cj("input[name=payment_processor]").click(function(){
    if(cj(this).val() == 0){
      cj("#billing-payment-block").html('<div class="crm-section payment-description"><div class="label"></div><div class="content">{/literal}{$event.pay_later_receipt|nl2br|regex_replace:"/[\r\n]/":""}{literal}</div><div class="clear"></div></div>');
    }
  });
  var lockfield = function($obj){
    $obj.attr('title', '{/literal}{ts}To change your personal info, go My Account page for further setting.{/ts}{literal}');
    $obj.attr("readonly", "readonly").addClass("readonly");
    if($obj.parent('.crm-form-elem').length){
      $obj.parent('.crm-form-elem').addClass('crm-form-readonly');
    }
  }
  // prevent overwrite others contact info
  {/literal}
  {if $contact_id}
    {if isset($form.last_name.value) and $form.last_name.value and isset($form.first_name.value) and $form.first_name.value}
      lockfield(cj("input#last_name"));
      lockfield(cj("input#first_name"));
    {/if}
    {assign var="email_f" value="email-5"}
    {if isset($form.$email_f.value) and $form.$email_f.value}
      lockfield(cj("input#email-5"));
    {/if}
  {/if}
  {if $is_contact_admin}
    cj(".first_name-section .content .description").html('{ts}To prevent overwrite personal info, we locked some field above for logged user. Please logout before you help other people to complete this form.{/ts}');
  {/if}
  {literal}
  if(cj("#register-who").length && location.hash != '#register-now'){
    cj("#register-now").hide();
    cj("#register-me").click(function(){
      cj("#register-now").slideDown();
    });
  }
</script>
{/literal} 

{/if}{*end pptype else*}
