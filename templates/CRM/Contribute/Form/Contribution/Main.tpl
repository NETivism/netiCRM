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
  {if $is_monetary}
  {* Put PayPal Express button after customPost block since it's the submit button in this case. *}
  {if $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    <div id="paypalExpress">
     {assign var=expressButtonName value='_qf_Main_upload_express'}
      <fieldset class="crm-group paypal_checkout-group">
        <legend>{ts}Checkout with PayPal{/ts}</legend>
        <div class="section">
        <div class="crm-section paypalButtonInfo-section">
          <div class="content">
              <span class="description">{ts}Click the PayPal button to continue.{/ts}</span>
          </div>
          <div class="clear"></div>
        </div>
        <div class="crm-section {$expressButtonName}-section">
            <div class="content">
              {$form.$expressButtonName.html} <span class="description">Checkout securely. Pay without sharing your financial information. </span>
            </div>
            <div class="clear"></div>
        </div>
        </div>
      </fieldset>
   </div>
  {/if}
  {/if}
{elseif $onbehalf}
   {include file=CRM/Contribute/Form/Contribution/OnBehalfOf.tpl}
{else}{* being main template *}

{if $action & 1024}
  {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker" title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-contribution-main-form-block">
  {if $sharethis}<div class="sharethis">
    {$sharethis}
  </div>{/if}

  {if $achievement.goal}
    {include file="CRM/common/progressbar.tpl" achievement=$achievement}
  {/if}

  <div id="intro_text" class="crm-section intro_text-section">
    {$intro_text}
  </div>
<!--intro-separator-->
  {* User account registration option. Displays if enabled for one of the profiles on this page. *}
  {include file="CRM/common/CMSUser.tpl"}
  {include file="CRM/Contribute/Form/Contribution/MembershipBlock.tpl" context="makeContribution"}

{if $is_monetary}
  <fieldset class="crm-group payment_options-group">
    <legend>{ts}Payment Options{/ts}</legend>
  {if $form.is_recur}
    <div class="crm-section {$form.is_recur.name}-section">
      <div class="content">
        {$form.is_recur.html}
        {if $form.frequency_unit.html}
        <div class="recur-element" id="recur-options-interval">
          {$form.frequency_unit.label}: {$form.frequency_unit.html}
        </div>
        {/if}
        <div class="recur-element" id="recur-options-installmnts">
          {$form.installments.label}: {$form.installments.html}
        </div>
        <p>
          <span class="description">{ts}Your recurring contribution will be processed automatically for the number of installments you specify. You can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time.{/ts}
            {if $is_email_receipt}
                {ts}You will receive an email receipt for each recurring contribution.{/ts} 
                {if $receipt_from_email}
                {ts 1=$receipt_from_email}To modify or cancel future contributions please contact us at %1.{/ts}
                {else}
                {ts}To modify or cancel future contributions please contact us{/ts}
                {/if}
            {/if}
        </p>
      </div>
    </div>{*recur section*}
  {/if}{*is_recur*}
{/if}{*is_monetary*}

{if $priceSet}
  <div id="priceset">
      {include file="CRM/Price/Form/PriceSet.tpl"}
  </div>
{else}
  {if $form.amount}
      <div class="crm-section {$form.amount.name}-section">
      <div class="label">{$form.amount.label}</div>
      <div class="content">{$form.amount.html}</div>
      <div class="clear"></div>
      </div>
  {/if}
  {if $is_allow_other_amount}
      <div class="crm-section {$form.amount_other.name}-section">
      <div class="label">{$form.amount_other.label}</div>
      <div class="content">{$form.amount_other.html|crmMoney}</div>
      <div class="clear"></div>
      </div>
  {/if}
  {if $pledgeBlock}
      {if $is_pledge_payment}
      <div class="crm-section {$form.pledge_amount.name}-section">
      <div class="label">{$form.pledge_amount.label}&nbsp;<span class="marker">*</span></div>
      <div class="content">{$form.pledge_amount.html}</div>
      <div class="clear"></div>
      </div>
      {else}
      <div class="crm-section {$form.is_pledge.name}-section">
      <div class="content">
        {$form.is_pledge.html}&nbsp;
        {if $is_pledge_interval}
          {$form.pledge_frequency_interval.html}&nbsp;
        {/if}
        {$form.pledge_frequency_unit.html}&nbsp;{ts}for{/ts}&nbsp;{$form.pledge_installments.html}&nbsp;{ts}installments.{/ts}
      </div>
      </div>
      {/if}
  {/if}
{/if}{*priceset*}

{if $is_monetary}
  {if $form.payment_processor.label}
    <div class="crm-section payment_processor-section">
      <div class="label">{$form.payment_processor.label}</div>
      <div class="content">{$form.payment_processor.html}</div>
      <div class="clear"></div>
    </div>
    <div id="billing-payment-block"></div>
    {include file="CRM/common/paymentBlock.tpl'}
  {elseif $is_pay_later}
    <div class="crm-section pay_later_receipt-section">
      <div class="label">{ts}Payment Method{/ts}</div>
      <div class="content">
        <input type="checkbox" checked="checked" disabled="disabled"/>{$pay_later_text|nl2br}<br />
        <span class="description">{$pay_later_receipt|nl2br}</span>
      </div>
      <div class="clear"></div>
    </div>
  {/if}
  </fieldset>
{/if}{*is_monetary*}

  {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="makeContribution"}

  {if $form.is_for_organization}
    <div class="crm-section {$form.is_for_organization.name}-section">
        <div class="content">
          {$form.is_for_organization.html}&nbsp;{$form.is_for_organization.label}
        </div>
      </div>
  {/if}


  {if $form.is_for_organization}
     {include file=CRM/Contact/Form/OnBehalfOf.tpl}
  {/if}


  {if $honor_block_is_active}
  <fieldset class="crm-group honor_block-group">
    <legend>{$honor_block_title}</legend>
        <div class="crm-section honor_block_text-section">
          {$honor_block_text}
        </div>
    {if $form.honor_type_id.html}
        <div class="crm-section {$form.honor_type_id.name}-section">
        <div class="content" >
          {$form.honor_type_id.html}
          <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('honor_type_id', '{$form.formName}');enableHonorType(); return false;">{ts}clear{/ts}</a>)</span>
          <div class="description">{ts}Please include the name, and / or email address of the person you are honoring.{/ts}</div>
        </div>
        </div>
    {/if}
    <div id="honorType" class="honoree-name-email-section">
      <div class="crm-section {$form.honor_prefix_id.name}-section">
          <div class="content">{$form.honor_prefix_id.html}</div>
      </div>
      <div class="crm-section {$form.honor_first_name.name}-section">
        <div class="label">{$form.honor_first_name.label}</div>
          <div class="content">
              {$form.honor_first_name.html}
        </div>
        <div class="clear"></div>
      </div>
      <div class="crm-section {$form.honor_last_name.name}-section">
          <div class="label">{$form.honor_last_name.label}</div>
          <div class="content">
              {$form.honor_last_name.html}
        </div>
        <div class="clear"></div>
      </div>
      <div id="honorTypeEmail" class="crm-section {$form.honor_email.name}-section">
        <div class="label">{$form.honor_email.label}</div>
          <div class="content">
            {$form.honor_email.html}
        </div>
        <div class="clear"></div>
      </div>
    </div>
  </fieldset>
  {/if}

    {assign var=n value=email-$bltID}
    <div class="crm-section email-section {$form.$n.name}-section">
      <div class="label">{$form.$n.label}</div>
      <div class="content">
        {$form.$n.html}
      </div>
      <div class="clear"></div>
    </div>
    <div class="crm-group custom_pre_profile-group">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
    </div>

    {if $pcp}
    <fieldset class="crm-group pcp-group">
      <div class="crm-section pcp-section">
      <div class="crm-section display_in_roll-section">
        <div class="content">
              {$form.pcp_display_in_roll.html} &nbsp;
              {$form.pcp_display_in_roll.label}
          </div>
          <div class="clear"></div>
      </div>
      <div id="nameID" class="crm-section is_anonymous-section">
          <div class="content">
              {$form.pcp_is_anonymous.html}
          </div>
          <div class="clear"></div>
      </div>
      <div id="nickID" class="crm-section pcp_roll_nickname-section">
          <div class="label">{$form.pcp_roll_nickname.label}</div>
          <div class="content">{$form.pcp_roll_nickname.html}
        <div class="description">{ts}Enter the name you want listed with this contribution. You can use a nick name like 'The Jones Family' or 'Sarah and Sam'.{/ts}</div>
          </div>
          <div class="clear"></div>
      </div>
      <div id="personalNoteID" class="crm-section pcp_personal_note-section">
          <div class="label">{$form.pcp_personal_note.label}</div>
          <div class="content">
            {$form.pcp_personal_note.html}
                <div class="description">{ts}Enter a message to accompany this contribution.{/ts}</div>
          </div>
          <div class="clear"></div>
      </div>
      </div>
    </fieldset>
    {/if}
  {include file="CRM/Custom/Page/CustomDataView.tpl"}
  <div class="crm-group custom_post_profile-group">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
  </div>
  {include file="CRM/common/moveEmail.tpl"}
<!--ufform-separator-->

    {if $is_monetary and $form.bank_account_number}
    <div id="payment_notice">
      <fieldset class="crm-group payment_notice-group">
          <legend>{ts}Agreement{/ts}</legend>
          {ts}Your account data will be used to charge your bank account via direct debit. While submitting this form you agree to the charging of your bank account via direct debit.{/ts}
      </fieldset>
    </div>
    {/if}
<!--payment-separator-->

  {if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}
  {if $is_monetary}
  {if $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    <div id="paypalExpress">
    {* Put PayPal Express button after customPost block since it's the submit button in this case. *}
      {assign var=expressButtonName value='_qf_Main_upload_express'}
      <fieldset class="crm-group paypal_checkout-group">
        <legend>{ts}Checkout with PayPal{/ts}</legend>
        <div class="section">
        <div class="crm-section paypalButtonInfo-section">
          <div class="content">
              <span class="description">{ts}Click the PayPal button to continue.{/ts}</span>
          </div>
          <div class="clear"></div>
        </div>
        <div class="crm-section {$expressButtonName}-section">
            <div class="content">
              {$form.$expressButtonName.html} <span class="description">Checkout securely. Pay without sharing your financial information. </span>
            </div>
            <div class="clear"></div>
        </div>
        </div>
      </fieldset>
    </div>
  {/if}
  {/if}
  <div id="crm-submit-buttons" class="crm-submit-buttons">
     {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {if $footer_text}
    <div id="footer_text" class="crm-section contribution_footer_text-section">
      <p>{$footer_text}</p>
    </div>
  {/if}
</div><!-- crm-contribution-main-form-block -->

{* Hide Credit Card Block and Billing information if contribution is pay later. *}
{if $form.is_pay_later and $hidePaymentInformation}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_pay_later"
    trigger_value       =""
    target_element_id   ="billing-payment-block"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 1
}
{/if}

<script type="text/javascript">
{literal}
function useAmountOther() {
  for( i=0; i < document.Main.elements.length; i++ ) {
    element = document.Main.elements[i];
    if ( element.type == 'radio' && element.name == 'amount' ) {
      if (element.value == 'amount_other_radio' ) {
        element.checked = true;
      }
      else {
        element.checked = false;
      }
    }
  }
}

function clearAmountOther() {
  if (document.Main.amount_other == null) return; // other_amt field not present; do nothing
  document.Main.amount_other.value = "";
}
{/literal}
{if $pcp}{literal}
pcpAnonymous();
function pcpAnonymous( ) {
  // clear nickname field if anonymous is true
  if ( document.getElementsByName("pcp_is_anonymous")[1].checked ) {
    document.getElementById('pcp_roll_nickname').value = '{/literal}{ts}Anonymity{/ts}{literal}';
  }
  else {
    document.getElementById('pcp_roll_nickname').value = '';
  }
  if ( ! document.getElementsByName("pcp_display_in_roll")[0].checked ) {
    hide('nickID', 'block');
    hide('nameID', 'block');
    hide('personalNoteID', 'block');
  }
  else {
    if ( document.getElementsByName("pcp_is_anonymous")[0].checked ) {
      show('nameID', 'block');
      show('nickID', 'block');
      show('personalNoteID', 'block');
    }
  }
}
{/literal}{/if}
{if $honor_block_is_active AND $form.honor_type_id.html}{literal}
enableHonorType();
function enableHonorType( ) {
  var element = document.getElementsByName("honor_type_id");
  for (var i = 0; i < element.length; i++ ) {
    var isHonor = false;
    if ( element[i].checked == true ) {
      var isHonor = true;
      break;
    }
  }
  if ( isHonor ) {
    show('honorType', 'block');
    show('honorTypeEmail', 'block');
  }
  else {
    document.getElementById('honor_first_name').value = '';
    document.getElementById('honor_last_name').value  = '';
    document.getElementById('honor_email').value      = '';
    document.getElementById('honor_prefix_id').value  = '';
    hide('honorType', 'block');
    hide('honorTypeEmail', 'block');
  }
}
{/literal}{/if}{literal}

{/literal}{if $form.is_pay_later and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}{literal}
  function showHidePayPalExpressOption() {
    if (document.getElementsByName("is_pay_later")[0].checked) {
      show("crm-submit-buttons");
      hide("paypalExpress");
    }
    else {
      show("paypalExpress");
      hide("crm-submit-buttons");
    }
  }
  showHidePayPalExpressOption();
{/literal}{/if}
  var recur_support = {$recur_support};{literal}
  var check_recur_support = function(pid){
    var payment_processor_id = parseInt(pid);
    if(recur_support.indexOf(payment_processor_id) === -1){
      cj("input[name=is_recur][value=0]").attr("checked", 1);
    }
  }
  cj("input[name=payment_processor]").click(function(){
    if(cj("input[name=is_recur][value=1]").attr("checked")){
      check_recur_support(cj(this).val());
    }
    if(cj(this).val() == 0){
      cj("#billing-payment-block").html('<div class="crm-section payment-description"><div class="label"></div><div class="content">{/literal}{$pay_later_receipt|nl2br|regex_replace:"/[\r\n]/":""}{literal}</div><div class="clear"></div></div>');
    }
  });

  cj(document).ready(function($){
    var amountGrouping = function(init){
      var amountFilter = function() {
        $("input[name=amount]").each(function() {
          var $label = $(this).closest('label.crm-form-elem');
          if (isRecur == '1') {
            if ($(this).data('grouping') == 'non-recurring') {
              $label.hide();
            }
            else {
              $label.show();
            }
          }
          else {
            if ($(this).data('grouping') == 'recurring') {
              $label.hide();
            }
            else {
              $label.show();
            }
          }
        });
      }
      $('.crm-section.amount-section label').css("display", "block");
      $('.crm-section.amount-section br').remove();
      var isRecur = $("input[name=is_recur]:checked").val();
      if (init) {
        amountFilter();
        // whatever, show current selected option
        $("input[name=amount]:checked").closest("label.crm-form-elem").show();
      }
      else {
        if (isRecur == 1) {
          var dataFilter = 'recurring';
        }
        else {
          var dataFilter = 'non-recurring';
        }
        $("input[name=amount]").removeProp("checked");
        amountFilter();
        var $default = $("input[name=amount][data-default=1]:visible");
        $default.prop("checked", true);
      }
    }
    var enablePeriod = function($isRecur){
      var $installments = cj('#installments');
      var $frequencyUnits = cj('#frequency_unit');
      if(parseInt($isRecur.val())){
        $installments.attr('disabled', false);
        $frequencyUnits.attr('disabled', false);
        cj('.is_recur-section .recur-element').show();
      }
      else{
        $installments.attr('disabled', true);
        $frequencyUnits.attr('disabled', true);
        cj('.is_recur-section .recur-element').hide();
      }
    }
    if (cj('input[name=is_recur]').length > 1) {
      cj('input[name=is_recur]').click(function(){
        amountGrouping();
        enablePeriod(cj(this));
      });
      enablePeriod(cj('input[name=is_recur]:checked'));
    }

    // don't submit at input
    cj('#crm-container form input:not([type="submit"])').keydown(function (e) {
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

    // email position
    if(cj('#crm-container .custom_pre_profile-group fieldset legend').length){
      cj('#crm-container .email-5-section').insertAfter('#crm-container .custom_pre_profile-group fieldset legend');
    }


    // prevent overwrite others contact info
    var lockfield = function($obj){
      $obj.attr('title', '{/literal}{ts}To change your personal info, go My Account page for further setting.{/ts}{literal}');
      $obj.attr("readonly", "readonly").addClass("readonly");
      if($obj.parent('.crm-form-elem').length){
        $obj.parent('.crm-form-elem').addClass('crm-form-readonly');
      }
    }
    {/literal}
    {if $contact_id}
      lockfield(cj("input#last_name"));
      lockfield(cj("input#first_name"));
      lockfield(cj("input#email-5"));
      cj(".first_name-section .content").append('<div class="description">{ts}To prevent overwrite personal info, we locked some field above for logged user. Please logout before you help other people to complete this form.{/ts}{ts 1="/user"}To change your personal info, go <a href="%1">My Account page</a> for further setting.{/ts}</div>');
    {/if}
    {literal}
    amountGrouping(true);
  });
{/literal}
</script>
{include file="CRM/common/betterContributionForm.tpl"}
{/if}{*ppType*}
{$special_style}