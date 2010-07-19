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
{* this template is used for adding event  *}
{capture assign="adminPriceSets"}{crmURL p='civicrm/admin/price' q="reset=1"}{/capture}

<div class="form-item">
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
<fieldset>
    {if !$paymentProcessor}
        {capture assign=ppUrl}{crmURL p='civicrm/admin/paymentProcessor' q="reset=1"}{/capture}
        <div class="status message">
                {ts 1=$ppUrl}No Payment Processor has been configured / enabled for your site. If this is a <strong>paid event</strong> AND you want users to be able to <strong>register and pay online</strong>, you will need to <a href='%1'>configure a Payment Processor</a> first. Then return to this screen and assign the processor to this event.{/ts} {docURL page="CiviContribute Payment Processor Configuration"}
                <p>{ts}NOTE: Alternatively, you can enable the <strong>Pay Later</strong> option below without setting up a payment processor. All users will then be asked to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</p>
        </div>
    {/if}
    <dl>
    <dt>{$form.title.label}</dt><dd>{$form.title.html}</dd>
    <dt>{$form.is_monetary.label}</dt><dd>{$form.is_monetary.html}</dd>
    </dl>

    <div id="event-fees">
        {if $paymentProcessor}
        <div id="paymentProcessor">
            <dl>
              <dt>{$form.payment_processor_id.label}</dt><dd>{$form.payment_processor_id.html}</dd>
              <dt class="">&nbsp;</dt>
              <dd class="description">
                {ts}If this is a paid event and you want users to be able to register and pay online, select a payment processor to use.{/ts}
                {ts}NOTE: Alternatively, you can enable the <strong>Pay Later</strong> feature below without setting up a payment processor. All users will then be asked to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts} {docURL page="CiviContribute Payment Processor Configuration"}
              </dd>
            </dl>
        </div>
        {/if}
           
        <div id="contributionType">
            <dl>
            <dt>{$form.contribution_type_id.label}<span class="marker"> *</span></dt><dd>{$form.contribution_type_id.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This contribution type will be assigned to payments made by participants when they register online.{/ts}
            <dt>{$form.fee_label.label}<span class="marker"> *</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='fee_label' id=$id}{/if}</dt><dd>{$form.fee_label.html}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This label is displayed with the list of event fees.{/ts}
            </dl>
        </div>

        <div id="payLater">
          <dl>
             <dt class="extra-long-fourty">&nbsp;</dt><dd>{$form.is_pay_later.html}&nbsp;{$form.is_pay_later.label}<br />
                <span class="description">{ts}Check this box if you want to give users the option to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</span></dd>
          </dl>
        </div>

        <div id="payLaterOptions">
          <dl>
             <dt>{$form.pay_later_text.label}<span class="marker"> *</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='pay_later_text' id=$id}{/if}</dt><dd>{$form.pay_later_text.html|crmReplace:class:big}</dd>
             <dt>&nbsp;</dt><dd class="description">{ts}Text displayed next to the checkbox for the 'pay later' option on the contribution form.{/ts}</dd>
             <dt>{$form.pay_later_receipt.label}<span class="marker"> *</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_event' field='pay_later_receipt' id=$id}{/if}</dt><dd>{$form.pay_later_receipt.html|crmReplace:class:big}</dd>
             <dt>&nbsp;</dt><dd class="description">{ts}Instructions added to Confirmation and Thank-you pages when the user selects the 'pay later' option (e.g. 'Mail your check to ... within 3 business days.').{/ts}</dd>
          </dl>
        </div>

        <div id="priceSet">
            <dl>
            <dt>{$form.price_set_id.label}</dt>
	    <dd>{if $price eq false}
	    	    <div class="status message">{ts 1=$adminPriceSets}No Price Set has been configured / enabled for your site. Price sets allow you to meet the complex demands of your event registration structure.(e.g. "Pay $15 more for lunch."). Click <a href='%1'>here</a> if you want to configure price sets for your site.{/ts}</div>
	    	{else}
		    {$form.price_set_id.html}</dd><dt>&nbsp;</dt><dd class="description">{ts 1=$adminPriceSets}Select a pre-configured Price Set to offer multiple individually priced options for event registrants. Otherwise, select &quot;-none-&quot; and enter one or more fee levels in the table below. Create or edit Price Sets <a href='%1'>here</a>.{/ts}
	    	{/if}
	    </dd>
            </dl>
        </div>
        <div id="map-field" >
        <fieldset id="map-field"><legend>{ts}Regular Fees{/ts}</legend>
        <p>{ts}Use the table below to enter descriptive labels and amounts for up to ten event fee levels. These will be presented as a list of radio button options. Both the label and dollar amount will be displayed. You can also configure one or more sets of discounted fees by checking "Discounts by Signup Date" below.{/ts}</p>
        <table id="map-field-table">
        <tr class="columnheader"><th scope="column">{ts}Fee Label{/ts}</th><th scope="column">{ts}Amount{/ts}</th><th scope="column">{ts}Default?{/ts}</th></tr>
        {section name=loop start=1 loop=11}
           {assign var=idx value=$smarty.section.loop.index}
           <tr><td class="even-row">{$form.label.$idx.html}</td><td>{$form.value.$idx.html|crmMoney}</td><td class="even-row">{$form.default.$idx.html}</td></tr>
        {/section}
        </table>
        </fieldset>
    
    <div id="isDiscount">
         <dl>
         <dt class="extra-long-fourty">&nbsp;</dt><dd>{$form.is_discount.html}&nbsp;{$form.is_discount.label}<br /><span class="description">{ts}Check this box if you want to offer discounted fees based on registration date (e.g. 'early-registration discounts').{/ts}</span></dd>
         </dl>
    </div>
    <div class="spacer"></div>
    <div class="form-item">
        <fieldset id="discount">
	<table>
	<tr class="columnheader">
        <th>&nbsp;</th>
        <th>{ts}Discount Set{/ts}</th>
        <th>{ts}Start Date{/ts}</th>
        <th>{ts}End Date{/ts}</th>
    </tr>
	
	{section name=rowLoop start=1 loop=6}
	   {assign var=index value=$smarty.section.rowLoop.index}
	   <tr id="discount_{$index}" class="{if $index GT 1 AND empty( $form.discount_name[$index].value) } hiddenElement {/if} form-item {cycle values="odd-row,even-row"}">
           <td>{if $index GT 1} <a onclick="showHideDiscountRow('discount_{$index}', false, {$index}); return false;" name="discount_{$index}" href="javascript:" class="form-link"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}hide field or section{/ts}"/></a>{/if}
           </td>
           <td> {$form.discount_name.$index.html}</td>
           <td> {include file="CRM/common/jcalendar.tpl" elementName='discount_start_date' elementIndex=$index} </td>
           <td> {include file="CRM/common/jcalendar.tpl" elementName='discount_end_date' elementIndex=$index} </td>
	   </tr>
    {/section}
    </table>
        <div id="discountLink" class="add-remove-link">
           <a onclick="showHideDiscountRow( 'discount', true);return false;" id="discountLink" href="#" class="form-link"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}show field or section{/ts}"/>{ts}another discount set{/ts}</a>
        </div>
        {$form._qf_Fee_submit.html}
	
        {if $discountSection}
            <fieldset id="map-field"><legend>{ts}Discounted Fees{/ts}</legend>
            <p>{ts}Use the table below to enter descriptive labels and amounts for up to ten discounted event fees for each discount set. <strong>Don't forget to click 'Save' when you are finished.</strong>{/ts}</p>
	    <table id="map-field-table">
            <tr class="columnheader">
	       <th scope="column">{ts}Fee Label{/ts}</th>
	       {section name=dloop start=1 loop=6}
	          {assign var=i value=$smarty.section.dloop.index}
		  {if $form.discount_name.$i.value}
	          <th scope="column">{$form.discount_name.$i.value}</th>
		  {/if}
	       {/section}
	       <th scope="column">{ts}Default?{/ts}</th>
	    </tr>
            
            {section name=loop start=1 loop=11}
               {assign var=idx value=$smarty.section.loop.index}
               <tr><td class="even-row">{$form.discounted_label.$idx.html}</td>
	          {section name=loop1 start=1 loop=6}
                     {assign var=idy value=$smarty.section.loop1.index}
		      {if $form.discount_name.$idy.value}
	              <td>{$form.discounted_value.$idx.$idy.html|crmMoney}</td>
		      {/if}
	          {/section}
	          <td class="even-row">{$form.discounted_default.$idx.html}</td>
	       </tr>
            {/section}
            </table>
            </fieldset>
        {/if}
        </fieldset>
    </div>
    </div>	
    </div>
</fieldset>
<div class="crm-submit-buttons">
    {$form.buttons.html}
</div>
</div>

{include file="CRM/common/showHide.tpl"}

<script type="text/javascript">
    var showRows   = new Array({$showBlocks});
    var hideBlocks = new Array({$hideBlocks});
    var rowcounter = 0;
    {literal}
    if (navigator.appName == "Microsoft Internet Explorer") {    
	for ( var count = 0; count < hideBlocks.length; count++ ) {
	    var r = document.getElementById(hideBlocks[count]);
            r.style.display = 'none';
        }
    }

    //hide and display the appropriate blocks as directed by the php code
    on_load_init_blocks( showRows, hideBlocks, '' );

    {/literal} 
    {if $price}
    {literal}
    // Re-show Fee Level grid if Price Set select has been set to none.
    if ( document.getElementById('price_set_id').options[document.getElementById('price_set_id').selectedIndex].value == '' ) {
       show( 'map-field' );
    }
    {/literal} 
    {/if}
    {literal}
    
    if ( document.getElementsByName('is_monetary')[0].checked ) {
        show( 'event-fees', 'block' );
    }
    
    function warnDiscountDel( ) {
        if ( ! document.getElementsByName('is_discount')[0].checked ) {
            alert('{/literal}{ts}If you uncheck "Discounts by Signup Date" and Save this form, any existing discount sets will be deleted. This action cannot be undone. If this is NOT what you want to do, you can check "Discounts by Signup Date" again{/ts}{literal}.');
        }
    }
    
    /**
     * Function used to show /hide discount and set defaults
     */
    function showHideDiscountRow( rowName, show, index ) {
        if ( show ) {
            // show first hidden element and set date default
            var counter = 0;
            cj('tr[id^=' + rowName + ']').each( function( i ) {
                counter++;
                if ( cj(this).css('display') == 'none' ) {
                    cj(this).show( );

                    // set default
                    var currentRowId = cj(this).attr('id');
                    var temp = currentRowId.split('_');
                    var currentElementID = temp[1];
                    var lastElementID    = currentElementID - 1 ;

                    var lastEndDate = cj( '#discount_end_date_' + lastElementID ).datepicker( 'getDate' );
                    if ( lastEndDate ) {
                        var discountDate = new Date( Date.parse( lastEndDate ) );
                        discountDate.setDate( discountDate.getDate() + 1 );
                        var newDate = discountDate.toDateString();
                        newDate = new Date( Date.parse( newDate ) );
                        cj( '#discount_start_date_' + currentElementID ).datepicker('setDate', newDate );
                    }

                    if ( counter == 5 ) {
                        cj('#discountLink').hide( );
                    }
                    return false;
                }
            });
        } else {
            // hide tr and clear dates
            cj( '#discount_end_date_' + index ).val('');
            cj( '#discount_name_' + index ).val('');
            cj( '#discount_start_date_' + index ).val('');
            cj( '#' + rowName ).hide( );
            cj('#discountLink').show( );
        }
    }
{/literal}
</script>


{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_pay_later"
    trigger_value       =""
    target_element_id   ="payLaterOptions" 
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}
{if $price }
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="price_set_id"
    trigger_value       =""
    target_element_id   ="map-field" 
    target_element_type ="block"
    field_type          ="select"
    invert              = 0
}
{/if}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_discount"
    trigger_value       =""
    target_element_id   ="discount" 
    target_element_type ="block"
    field_type          ="radio"
    invert              = 0
}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

