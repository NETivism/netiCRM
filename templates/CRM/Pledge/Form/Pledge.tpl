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
{* this template is used for adding/editing/deleting pledge *} 
{if $cdType}
  {include file="CRM/Custom/Form/CustomData.tpl"}
{elseif $showAdditionalInfo and $formType }
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}
{if !$email and $action neq 8 and $context neq 'standalone'}
<div class="messages status">
  <dl>
    <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
    <dd>
        <p>{ts}You will not be able to send an acknowledgment for this pledge because there is no email address recorded for this contact. If you want a acknowledgment to be sent when this pledge is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the pledge.{/ts}</p>
    </dd>
  </dl>
</div>
{/if}
<div class="form-item">
<div class=""crm-submit-buttons">{$form.buttons.html}</div>
<fieldset><legend>{if $action eq 1 or $action eq 1024}{ts}New Pledge{/ts}{elseif $action eq 8}{ts}Delete Pledge{/ts}{else}{ts}Edit Pledge{/ts}{/if}</legend> 
   {if $action eq 8} 
      <div class="messages status"> 
        <dl> 
          <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt> 
          <dd> 
          {ts}WARNING: Deleting this pledge will result in the loss of the associated financial transactions (if any).{/ts} {ts}Do you want to continue?{/ts} 
          </dd> 
       </dl> 
      </div> 
   {else}
      <table class="form-layout-compressed">
        {if $context eq 'standalone'}
            {include file="CRM/Contact/Form/NewContact.tpl"}
        {else}
          <tr>
              <td class="font-size12pt right"><strong>{ts}Pledge by{/ts}</strong></td>
              <td class="font-size12pt"><strong>{$displayName}</strong></td>
          </tr>
        {/if}
	<tr><td class="font-size12pt right">{$form.amount.label}</td><td class="font-size12pt">{$form.amount.html|crmMoney}</td></tr>
        <tr><td class="label">{$form.installments.label}</td><td>{$form.installments.html} {ts}installments of{/ts} {if $action eq 1 or $isPending}{$form.eachPaymentAmount.html|crmMoney}{elseif $action eq 2 and !$isPending}{$eachPaymentAmount|crmMoney}{/if}&nbsp;{ts}every{/ts}&nbsp;{$form.frequency_interval.html}&nbsp;{$form.frequency_unit.html}</td></tr>
        <tr><td class="label nowrap">{$form.frequency_day.label}</td><td>{$form.frequency_day.html} {ts}day of the period{/ts}<br />
            <span class="description">{ts}This applies to weekly, monthly and yearly payments.{/ts}</td></tr>
        {if $form.create_date}	
        <tr>
            <td class="label">{$form.create_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=create_date}<br />
        {/if}
        {if $create_date}
            <tr><td class="label">Pledge Made</td><td class="view-value">{$create_date|truncate:10:''|crmDate}
        {/if}<br />
            <span class="description">{ts}Date when pledge was made by the contributor.{/ts}</span></td></tr>
       
        {if $form.start_date}	
            <tr>
                <td class="label">{$form.start_date.label}</td>
                <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}<br />
        {/if}
        {if $start_date}
            <tr><td class="label">Payments Start</td><td class="view-value">{$start_date|truncate:10:''|crmDate}
        {/if}<br />
            <span class="description">{ts}Date of first pledge payment.{/ts}</span></td></tr>
       
        {if $email and $outBound_option != 2}
            {if $form.is_acknowledge }
                <tr><td class="label">{$form.is_acknowledge.label}</td><td>{$form.is_acknowledge.html}<br />
                <span class="description">{ts 1=$email}Automatically email an acknowledgment of this pledge to %1?{/ts}</span></td></tr>
            {/if}
	    {elseif $context eq 'standalone' and $outBound_option != 2 }
                <tr id="acknowledgment-receipt" style="display:none;"><td class="label">{$form.is_acknowledge.label}</td><td>{$form.is_acknowledge.html} <span class="description">{ts}Automatically email an acknowledgment of this pledge to {/ts}<span id="email-address"></span>?</span></td></tr>
        {/if}
        <tr id="acknowledgeDate"><td class="label">{$form.acknowledge_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=acknowledge_date}<br />
            <span class="description">{ts}Date when an acknowledgment of the pledge was sent.{/ts}</span></td></tr>
            <tr><td class="label">{$form.contribution_type_id.label}</td><td>{$form.contribution_type_id.html}<br />
            <span class="description">{ts}Sets the default contribution type for payments against this pledge.{/ts}</span></td></tr>
	    <tr><td class="label">{$form.contribution_page_id.label}</td><td>{$form.contribution_page_id.html}<br />
            <span class="description">{ts}Select an Online Contribution page that the user can access to make self-service pledge payments. (Only Online Contribution pages configured to include the Pledge option are listed.){/ts}</span></td></tr>
        
	    <tr><td class="label">{ts}Pledge Status{/ts}</td><td class="view-value">{$status}<br />
            <span class="description">{ts}Pledges are "Pending" until the first payment is received. Once a payment is received, status is "In Progress" until all scheduled payments are completed. Overdue pledges are ones with payment(s) past due.{/ts}</span></td></tr>
		<tr><td colspan=2>{include file="CRM/Custom/Form/CustomData.tpl"}</td></tr>
       </table>
</fieldset>
{literal}
<script type="text/javascript">
var showPane = "";
cj(function() {
  cj('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ");

  cj('.accordion .head').hover( function() { cj(this).addClass( "ui-state-hover");
                             }, function() { cj(this).removeClass( "ui-state-hover");
               }).bind('click', function() { 
		                             var checkClass = cj(this).find('span').attr( 'class' );
					     var len        = checkClass.length;
					     if( checkClass.substring( len - 1, len ) == 's' ) {
					       cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-e');
					     } else {
					       cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
					     }
					     cj(this).next().toggle(); return false; }).next().hide();
  if( showPane.length > 1 ) {
    eval("showPane =[ '" + showPane.substring( 0,showPane.length - 2 ) +"]");
    cj.each( showPane, function( index, value ) {
       cj('span#'+value).removeClass().addClass('ui-icon ui-icon-triangle-1-s');
       loadPanes( value )  ;
       cj("div."+value).show();
    });
  }
});


cj(document).ready( function() {
    cj('.head').one( 'click', function() { loadPanes( cj(this).children().attr('id') );  });
});

function loadPanes( id ) {
    var url = "{/literal}{crmURL p='civicrm/contact/view/pledge' q='snippet=4&formType=' h=0}{literal}" + id;
    if ( ! cj('div.'+id).html() ) {
  var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts}loading{/ts}{literal}" />&nbsp;{/literal}{ts}Loading{/ts}{literal}...';
    cj('div.'+id).html(loading);
    }
    cj.ajax({
        url    : url,
        success: function(data) { 
                    cj('div.'+id).html(data);
                 }
         });
}
</script>
{/literal}
{* jQuery pane *}
<div class="accordion ui-accordion ui-widget ui-helper-reset">
{foreach from=$allPanes key=paneName item=paneValue}
<h3 class="head"><span class="ui-icon ui-icon-triangle-1-e" id="{$paneValue.id}"></span><a href="#">{$paneName}</a></h3>
<div class={$paneValue.id}></div>
{if $paneValue.open eq 'true'}
{literal}<script type="text/javascript"> showPane += "{/literal}{$paneValue.id}{literal}"+"','";</script>{/literal}
{/if}
{/foreach}
{/if} {* not delete mode if*}   
</div> 
<br />
<div class="crm-submit-buttons">{$form.buttons.html}</div>
     {literal}
     <script type="text/javascript">

     function verify( ) {
       var element = document.getElementsByName("is_acknowledge");
        if ( element[0].checked ) {
            var message = '{/literal}{ts 1=$email}Click OK to save this Pledge record AND send an acknowledgment to %1 now{/ts}{literal}.';
            if (!confirm( message) ) {
                return false;
            }
        }
     }
     
     function calculatedPaymentAmount( ) {
       var thousandMarker = '{/literal}{$config->monetaryThousandSeparator}{literal}';
       var seperator      = '{/literal}{$config->monetaryDecimalPoint}{literal}';
       var amount = document.getElementById("amount").value;
       var installments = document.getElementById("installments").value;
       if ( installments != '' && installments != NaN) {
            amount =  amount/installments;
            var installmentAmount = formatMoney( amount, 2, seperator, thousandMarker );
            document.getElementById("eachPaymentAmount").value = installmentAmount;
       }   
     }
     
     function formatMoney (amount, c, d, t){
       var n = amount, 
       c = isNaN(c = Math.abs(c)) ? 2 : c, 
       d = d == undefined ? "," : d, 
       t = t == undefined ? "." : t, s = n < 0 ? "-" : "", 
       i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
       j = (j = i.length) > 3 ? j % 3 : 0;
	   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
     };

    {/literal}
    {if $context eq 'standalone' and $outBound_option != 2 }
    {literal}
    cj( function( ) {
        cj("#contact").blur( function( ) {
            checkEmail( );
        });
        checkEmail( );
    });
    function checkEmail( ) {
        var contactID = cj("input[name=contact_select_id]").val();
        if ( contactID ) {
            var postUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' h=0}{literal}";
            cj.post( postUrl, {contact_id: contactID},
                function ( response ) {
                    if ( response ) {
                        cj("#acknowledgment-receipt").show( );
                        cj("#email-address").html( response );
                    } else {
                        cj("#acknowledgment-receipt").hide( );
                    }
                }
            );
        }
    }
    {/literal}
    {/if}
</script>

{if $email and $outBound_option != 2}
{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_acknowledge"
    trigger_value       =""
    target_element_id   ="acknowledgeDate" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 1
}
{/if}

   {* include jscript to warn if unsaved form field changes *}
   {include file="CRM/common/formNavigate.tpl"}

{/if}
{* closing of main custom data if *}
