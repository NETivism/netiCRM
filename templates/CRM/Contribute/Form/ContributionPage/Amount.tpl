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
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
{capture assign="adminPriceSets"}{crmURL p='civicrm/admin/price' q="reset=1"}{/capture}
<div class="crm-block crm-form-block crm-contribution-contributionpage-amount-form-block">
<div id="help">
    {ts}Use this form to configure Contribution Amount options. You can give contributors the ability to enter their own contribution amounts - and/or provide a fixed list of amounts. For fixed amounts, you can enter a label for each 'level' of contribution (e.g. Friend, Sustainer, etc.). If you allow people to enter their own dollar amounts, you can also set minimum and maximum values. Depending on your choice of Payment Processor, you may be able to offer a recurring contribution option.{/ts} {docURL page="PayPal Website Payments Standard and Recurring Contributions"}
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {if !$paymentProcessor}
        {capture assign=ppUrl}{crmURL p='civicrm/admin/paymentProcessor' q="reset=1"}{/capture}
        <div class="status message">
            {ts 1=$ppUrl}No Payment Processor has been configured / enabled for your site. Unless you are only using CiviContribute to solicit non-monetary / in-kind contributions, you will need to <a href='%1'>configure a Payment Processor</a>. Then return to this screen and assign the processor to this Contribution Page.{/ts} {docURL page="CiviContribute Payment Processor Configuration"}
            <p>{ts}NOTE: Alternatively, you can enable the <strong>Pay Later</strong> option below without setting up a payment processor. All users will then be asked to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</p>
        </div>
    {/if}
    <table class="form-layout-compressed">
        <tr class="crm-contribution-contributionpage-amount-form-block-is_monetary"><th scope="row" class="label" width="20%">{$form.is_monetary.label}</th>
            <td>{$form.is_monetary.html}<br />
            <span class="description">{ts}Uncheck this box if you are using this contribution page for free membership signup ONLY, or to solicit in-kind / non-monetary donations such as furniture, equipment.. etc.{/ts}</span></td>
        </tr>
        </table>
        <table class="form-layout-compressed" id="is_monetary_child_table">
        {if $paymentProcessor}
        <tr class="crm-contribution-contributionpage-amount-form-block-payment_processor"><th scope="row" class="label" width="20%">{$form.payment_processor.label}</th>
            <td>{$form.payment_processor.html}<br />
            <span class="description">{ts}Select the payment processor to be used for contributions submitted from this contribution page (unless you are soliciting non-monetary / in-kind contributions only).{/ts} {docURL page="CiviContribute Payment Processor Configuration"}
                {* This feature is temporarily disable *}
                {*{if $is_special}
                    <br />
                    {ts}Cause you use special style. If you only choose 'Credit Card', the payment processor and instrument form will be hidden on the contribution page. Contributor don't need to choose these selections.{/ts}
                {/if}*}
            </span></td>
        </tr>
          {if $form.payment_instrument}
          <tr class="crm-contribution-contributionpage-amount-form-block-payment_instrument"><td></td><td>
            <table><tr>
                <th scope="row" class="label" width="20%">{$form.payment_instrument.label}</th>
                <td>{$form.payment_instrument.html}</td>
            </tr></table>
          </tr>
          {/if}
        {/if}

        <!-- is_pay_later BEGIN -->
            <tr class="crm-contribution-contributionpage-amount-form-block-is_pay_later"><th scope="row" class="label" width="20%">{$form.is_pay_later.label}</th>
            <td>{$form.is_pay_later.html}<br />
            <span class="description">{ts}Check this box if you want to give users the option to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</span></td></tr>
        <tr id="payLaterFields" class="crm-contribution-form-block-payLaterFields"><td>&nbsp;</td>
            <td>
            <table class="form-layout">
                <tr class="crm-contribution-contributionpage-amount-form-block-pay_later_text"><th scope="row" class="label">{$form.pay_later_text.label} <span class="marker" title="This field is required.">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='pay_later_text' id=$id}{/if}</th>
                <td>{$form.pay_later_text.html|crmReplace:class:big}<br />
                    <span class="description">{ts}Text displayed next to the checkbox for the 'pay later' option on the contribution form.{/ts}</span></td></tr> 
                <tr class="crm-contribution-contributionpage-amount-form-block-pay_later_receipt"><th scope="row" class="label">{$form.pay_later_receipt.label} <span class="marker" title="This field is required.">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='pay_later_receipt' id=$id}{/if}</th>
                <td>{$form.pay_later_receipt.html|crmReplace:class:big}<br />
                    <span class="description">{ts}Instructions added to Confirmation and Thank-you pages, as well as the confirmation email, when the user selects the 'pay later' option (e.g. 'Mail your check to ... within 3 business days.').{/ts}</span></td></tr>
            </table>
            </td>
        </tr>
        <!-- is_pay_later END -->


        {if $recurringPaymentProcessor}
            <tr id="recurringContribution" class="crm-contribution-form-block-is_recur"><th scope="row" class="label" width="20%">{$form.is_recur.label}</th>
               <td>{$form.is_recur.html}<br />
                  <span class="description">{ts}Check this box if you want to give users the option to make recurring contributions. (This feature requires that you use a payment processor with this functionality built in - Paypal std or Pro, Pay2Cash or Payjunction at the time of writing.){/ts}</span>
               </td>
            </tr>
            <tr id="recurFields" class="crm-contribution-form-block-recurFields"><td>&nbsp;</td>
               <td>
                  <table class="form-layout-compressed">
                    <tr class="crm-contribution-form-block-is_recur_only"><th scope="row" class="label">{$form.is_recur_only.label}</th>
                        <td>{$form.is_recur_only.html}<br />
                        <span class="description">{ts}Check this box will turn off single transaction of this page.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-recur_frequency_unit"><th scope="row" class="label">{$form.recur_frequency_unit.label}<span class="marker" title="This field is required.">*</span></th>
                        <td>{$form.recur_frequency_unit.html}<br />
                        <span class="description">{ts}Select recurring units supported for recurring payments.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-show_installments_option"><th scope="row" class="label">{$form.show_installments_option.label}<span class="marker" title="This field is required.">*</span></th>
                        <td>{$form.show_installments_option.html}<br />
                        <span class="description">{ts}Select 'Yes' if you want to give users to choose the installments of recurring contribution.{/ts}</span></td>
                    </tr>
                  </table>
                </td>
            </tr>
        {/if}

        </table>
        <table class="form-layout-compressed">
        <tr class="crm-contribution-contributionpage-amount-form-block-currency"><th scope="row" class="label" width="20%">{$form.currency.label}</th>
            <td>{$form.currency.html}<br />
            <span class="description">{ts}Select the currency to be used for contributions submitted from this contribution page.{/ts}</span></td>
        </tr>
        <tr class="crm-contribution-contributionpage-amount-form-block-amount_block_is_active"><th scope="row" class="label">{$form.amount_block_is_active.label}</th>
            <td>{$form.amount_block_is_active.html}<br />
            <span class="description">{ts}Uncheck this box if you are using this contribution page for membership signup and renewal only - and you do NOT want users to select or enter any additional contribution amounts.{/ts}</span></td>
        </tr>

	<tr id="priceSet" class="crm-contribution-contributionpage-amount-form-block-priceSet">
	     <th scope="row" class="label">{$form.price_set_id.label}</th>
	     {if $price eq true}
	     	 <td>{$form.price_set_id.html}<br /><span class="description">{ts 1=$adminPriceSets}Select a pre-configured Price Set to offer multiple individually priced options for contributions. Otherwise, select &quot;-none-&quot; and enter one or more fee levels in the table below. Create or edit Price Sets <a href='%1'>here</a>.{/ts}
                {if $is_special}
                    <br />
                    {ts}Cause you use special style. You can't use price set mode.{/ts}
                {/if}
             </span></td>
	     {else}
		<td><div class="status message">{ts 1=$adminPriceSets}No Price Set has been configured / enabled for your site. Price sets allow you to configure more complex contribution options (e.g. "Contribute $25 more to receive our monthly magazine."). Click <a href='%1'>here</a> if you want to configure price sets for your site.{/ts}</div></td>
	     {/if}
        </tr>
    </table>
   
    <div id="amountFields">
        <table class="form-layout-compressed">
            {* handle CiviPledge fields *} 
            {if $civiPledge}
            <tr class="crm-contribution-form-block-is_pledge_active"><th scope="row" class="label" width="20%">{$form.is_pledge_active.label}</th>
                <td>{$form.is_pledge_active.html}<br />
                    <span class="description">{ts}Check this box if you want to give users the option to make a Pledge (a commitment to contribute a fixed amount on a recurring basis).{/ts}</span>
                </td>
            </tr>
            <tr id="pledgeFields" class="crm-contribution-form-block-pledgeFields"><td></td><td>
                <table class="form-layout-compressed">
                    <tr class="crm-contribution-form-block-pledge_frequency_unit"><th scope="row" class="label">{$form.pledge_frequency_unit.label}<span class="marker"> *</span></th>
                        <td>{$form.pledge_frequency_unit.html}<br />
                            <span class="description">{ts}Which frequencies can the user pick from (e.g. every 'week', every 'month', every 'year')?{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-is_pledge_interval"><th scope="row" class="label">{$form.is_pledge_interval.label}</th>
                        <td>{$form.is_pledge_interval.html}<br />
                            <span class="description">{ts}Can they also set an interval (e.g. every '3' months)?{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-initial_reminder_day"><th scope="row" class="label">{$form.initial_reminder_day.label}</th>
                        <td>{$form.initial_reminder_day.html}
                            <span class="label">{ts}days prior to each scheduled payment due date.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-max_reminders"><th scope="row" class="label">{$form.max_reminders.label}</th>
                        <td>{$form.max_reminders.html}
                            <span class="label">{ts}reminders for each scheduled payment.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-additional_reminder_day"><th scope="row" class="label">{$form.additional_reminder_day.label}</th>
                        <td>{$form.additional_reminder_day.html}	
                            <span class="label">{ts}days after the last one sent, up to the maximum number of reminders.{/ts}</span></td>
                    </tr>
                </table>
                </td>
            </tr>
            {/if}
	
            <tr class="crm-contribution-form-block-is_allow_other_amount"><th scope="row" class="label" width="20%">{$form.is_allow_other_amount.label}</th>
            <td>{$form.is_allow_other_amount.html}<br />
            <span class="description">{ts}Check this box if you want to give users the option to enter their own contribution amount. Your page will then include a text field labeled <strong>Other Amount</strong>.{/ts}</span></td></tr>

            <tr id="minMaxFields" class="crm-contribution-form-block-minMaxFields"><td>&nbsp;</td><td>
               <table class="form-layout-compressed">
                <tr class="crm-contribution-form-block-min_amount"><th scope="row" class="label">{$form.min_amount.label}</th>
                <td>{$form.min_amount.html}</td></tr>
                <tr class="crm-contribution-form-block-max_amount"><th scope="row" class="label">{$form.max_amount.label}</th>
                <td>{$form.max_amount.html}<br />
                <span class="description">{ts 1=5|crmMoney}If you have chosen to <strong>Allow Other Amounts</strong>, you can use the fields above to control minimum and/or maximum acceptable values (e.g. don't allow contribution amounts less than %1).{/ts}</span></td></tr>
               </table>
            </td></tr>
            <tr><td colspan="2">
                <fieldset><legend>{ts}Fixed Contribution Options{/ts}</legend>
                    {ts}Use the table below to enter up to ten fixed contribution amounts. These will be presented as a list of radio button options. Both the label and dollar amount will be displayed.{/ts}<br />
                    <div id="map-field">
                    <table id="map-field-table">
                        <tr class="columnheader" >
                          <td scope="column">{ts}Used for{/ts}</td>
                          <td scope="column">{ts}Contribution Label{/ts}</td>
                          <td scope="column">{ts}Amount{/ts}</td>
                          <td scope="column">{ts}Default{/ts}</td>
                        </tr>
                        {section name=loop start=1 loop=11}
                            {assign var=idx value=$smarty.section.loop.index}
                            <tr>
                              <td class="even-row">{$form.default.$idx.html} {$form.grouping.$idx.html}</td>
                              <td>{$form.label.$idx.html}</td>
                              <td class="even-row">{$form.value.$idx.html}</td>
                              <td class="even-row">{$form.filter.$idx.html}</td>
                            </tr>
                        {/section}
                    </table>
                    </div>
              </fieldset>
            </td></tr>
        </table>
      </div>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
  cj(document).ready(function($){
    var varifyCheckbox = function($checkbox) {
      var $tr = $checkbox.closest("tr");
      var $table = $checkbox.closest("table");
      var groupCurrent = $tr.find("select[name^=grouping]").val() ? $tr.find("select[name^=grouping]").val() : 'all';

      var $trAll = $table.find("select[name^=grouping]").filter(function(){ return !$(this).val(); }).closest("tr");
      var $trCurrent = $table.find("select[name^=grouping]").filter(function(){ return $(this).val() == groupCurrent; }).closest("tr");

      if($checkbox.is(":checked")) {
        // clear all same level checkbox, use that for default
        switch(groupCurrent) {
          case "all":
            $("input[type=checkbox][name^=filter]:checked").removeProp('checked');
            $checkbox.prop("checked", true);
            break;
          default:
            $trAll.find("input[type=checkbox][name^=filter]:checked").removeProp('checked');
            $trCurrent.find("input[type=checkbox][name^=filter]:checked").removeProp('checked');
            $checkbox.prop("checked", true);
            break;
        }
      }
    }
    $("input[type=checkbox][name^=filter]").change(function(e){
      varifyCheckbox($(this));
      return false;
    });
    $("select[name^=grouping]").change(function(e){
      var $tr = $(this).closest("tr");
      var $checkbox = $tr.find("input[type=checkbox][name^=filter]:checked");
      varifyCheckbox($checkbox);
    });
  });
   var paymentProcessorMapper = new Array( );
     {/literal}
       {if $recurringPaymentProcessor}
           {foreach from=$recurringPaymentProcessor item="paymentProcessor" key="index"}{literal}
               paymentProcessorMapper[{/literal}{$index}{literal}] = '{/literal}{$paymentProcessor}{literal}';
           {/literal}{/foreach}
       {/if}
     {literal}
	var element_other_amount = document.getElementsByName('is_allow_other_amount');
  	if (! element_other_amount[0].checked) {
	   hide('minMaxFields', 'table-row');
	}
	var amount_block = document.getElementsByName('amount_block_is_active');
	var priceSetID = {/literal}'{$priceSetID}'{literal};
  	
	if ( ! amount_block[0].checked || priceSetID ) {
	   if ( !priceSetID ) hide('priceSet', 'table-row' ); 
	   hide('amountFields', 'block');
        }
	var pay_later = document.getElementsByName('is_pay_later');
  	if ( ! pay_later[0].checked) {
	    hide('payLaterFields', 'table-row');
        }
	
	function minMax(chkbox) {
           if (chkbox.checked) {
	     show('minMaxFields', 'table-row');
 	   } else {
		 hide('minMaxFields');
		 document.getElementById("min_amount").value = '';
		 document.getElementById("max_amount").value = '';
	  }
	}
		
	function payLater(chkbox) {
           if (chkbox.checked) {
	       show('payLaterFields',  'table-row');
	   } else {
	       hide('payLaterFields',  'table-row');
	   }
        }

	function showHideAmountBlock( element, elementName ) 
        {
	   // show / hide when amount section is active check/uncheck.

	   var priceSetID = {/literal}'{$priceSetID}'{literal};

	   switch ( elementName ) {
          case 'is_monetary':
              if ( element.checked ) {
                cj('#is_monetary_child_table').show();
              }else{
                cj('#is_monetary_child_table').hide();
              }
          break;
		  case 'price_set_id':
		       if ( element ) {
		       	  hide('amountFields', 'block');
		       } else {
		       	  show('amountFields', 'block');
		       }
		       cj("#amount_block_is_active").attr( 'checked', true );
		  break;   	
	   	  
		  case 'is_pledge_active' :
		  case 'is_allow_other_amount' :
		       if ( element.checked ) {
		       	  if ( priceSetID ) cj( "#price_set_id" ).val( '' );
		     	  show('amountFields', 'block');
	               }
		       cj("#amount_block_is_active").attr( 'checked', true );
		  break;			
	   	  
	   	  case 'amount_block_is_active' :
		       if ( element.checked ) {
		       	  if ( priceSetID ) {
			     hide('amountFields', 'block' );
			     cj( "#price_set_id" ).val( priceSetID );  
			  } else {
			     show('amountFields', 'block');
			     cj( "#price_set_id" ).val( '' ); 
			  }
			  show('priceSet', 'table-row' );
		       } else {
		    	  cj( "#price_set_id" ).val( '' );
		    	  hide('amountFields', 'block' );
			  hide('priceSet', 'table-row' );
		       }
		  break;
	   }
 	}

	function showRecurInterval( ) {
		 if ( cj("#is_recur").attr( 'checked' ) ) {
		 } 
     else {
		     cj("#is_recur_interval").attr( 'checked', false ); 
		 }
	}
</script>
{/literal}
{if $form.is_recur}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_recur"
    trigger_value       ="true"
    target_element_id   ="recurFields" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = "false"
}
{/if}
{if $civiPledge}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    = "is_pledge_active"
    trigger_value       = "true"
    target_element_id   = "pledgeFields" 
    target_element_type = "table-row"
    field_type          = "radio"
    invert              = "false"
}
{/if}
{if $paymentProcessor}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_monetary"
    trigger_value       ="true"
    target_element_id   ="is_monetary_child_table"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = "false"
}
{/if}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

