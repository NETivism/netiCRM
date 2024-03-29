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
{* This template is used for adding/editing/deleting offline Event Registrations *}
{if $showFeeBlock }

    {if $priceSet}
    <div id='validate_pricefield' class='messages crm-error hiddenElement'></div>
    {literal}
    <script type="text/javascript">

    var fieldOptionsFull = new Array( );
    {/literal}
    {foreach from=$priceSet.fields item=fldElement key=fldId}
    {if $fldElement.options}
      {foreach from=$fldElement.options item=fldOptions key=opId}
      {if $fldOptions.is_full}
        {literal}
  fieldOptionsFull[{/literal}{$fldId}{literal}] = new Array( );
        fieldOptionsFull[{/literal}{$fldId}{literal}][{/literal}{$opId}{literal}] = 1; 
        {/literal}
      {/if} 
      {/foreach}
    {/if}
    {/foreach}
    {literal}

    if ( fieldOptionsFull.length > 0 ) {
    cj(document).ready( function( ) {
      cj("input,#priceset select,#priceset").each(function () {
        if ( cj(this).attr('price') ) {
            switch( cj(this).attr('type') ) { 
              case 'checkbox':
              case 'radio':
                cj(this).click( function() { 
                  validatePriceField(this);    
                });
                break;

        case 'select-one':
          cj(this).change( function() {
            validatePriceField(this); 
          });
          break;
        case 'text':
    cj(this).bind( 'keyup', function() { validatePriceField(this) });
    break;
            }
        }
      });
    });
    
    function validatePriceField( obj ) {
           var namePart =  cj(obj).attr('name').split('_');
     var fldVal  =  cj(obj).val();
     if ( cj(obj).attr('type') == 'checkbox') {
       var eleIdpart = namePart[1].split('[');
       var eleId = eleIdpart[0];
     } else {
       var eleId  = namePart[1];
     }     
     var showError = false;
     
     switch( cj(obj).attr('type') ) {
      case 'text':
           if ( fieldOptionsFull[eleId] && fldVal ) {
               showError = true;
        cj(obj).parent( ).parent( ).children('.label').addClass('crm-error');     
           } else {
              cj(obj).parent( ).parent( ).children('.label').removeClass('crm-error');
        cj('#validate_pricefield').hide( ).html('');         
           }
           break;

      case 'checkbox':  
          var checkBoxValue = eleIdpart[1].split(']');
          if ( cj(obj).attr("checked") == true &&
               fieldOptionsFull[eleId] &&
               fieldOptionsFull[eleId][checkBoxValue[0]]) {
               showError = true;
               cj(obj).parent( ).addClass('crm-error');         
          } else {
         cj(obj).parent( ).removeClass('crm-error');
          }
          break;   
    
      default:
          if ( fieldOptionsFull[eleId] &&
               fieldOptionsFull[eleId][fldVal]  ) {
               showError = true;
               cj(obj).parent( ).addClass('crm-error');         
          } else {
         cj(obj).parent( ).removeClass('crm-error');
          }
     }
     
     if ( showError ) {
         cj('#validate_pricefield').show().html("<span class='icon red-zmdi zmdi-alert-circle'></span>{/literal}{ts}This Option is already full for this event.{/ts}{literal}");
     } else {
       cj('#validate_pricefield').hide( ).html('');
     }
                           
        }
  }
    </script>
    {/literal}
    {/if}
    {include file="CRM/Event/Form/EventFees.tpl"}
    
{elseif $cdType }
    {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
    {if $participantMode == 'test' }
        {assign var=registerMode value="TEST"}
        {else if $participantMode == 'live'}
        {assign var=registerMode value="LIVE"}
    {/if}
    {if $id}
      {include file="CRM/Event/Form/ManageEvent/Navigator.tpl"}
    {/if}
    <h3>{if $action eq 1}{elseif $action eq 8}{ts}Delete Event Registration{/ts}{else}{ts}Edit Event Registration{/ts}{/if}</h3>
    <div class="crm-block crm-form-block crm-participant-form-block">
    <div class="view-content">
    {if $participantMode}
        <div id="help">
          {ts 1=$displayName 2=$registerMode}Use this form to submit an event registration on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
        </div>
    {/if}
    <div id="eventFullMsg" class="messages status" style="display:none;"></div>
  

    {if $action eq 1 AND $paid}
      <div id="help">
        {ts}If you are accepting offline payment from this participant, check <strong>Record Payment</strong>. You will be able to fill in the payment information, and optionally send a receipt.{/ts}
      </div>  
    {/if}

        {if $action eq 8} {* If action is Delete *}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <div class="crm-participant-form-block-delete messages status">
                <div class="crm-content">
                    {ts}Are you sure you want to delete the selected participations?{/ts}
                </div>
          {if $additionalParticipant}
                    <div class="crm-content">
                        {ts 1=$additionalParticipant} There are %1 more Participant(s) registered by this participant.{/ts}
                    </div>
          {/if}
            </div>
      {if $additionalParticipant}
              {$form.delete_participant.html}
            {/if}
        {else} {* If action is other than Delete *}
            <table class="form-layout-compressed">
            <tr class="crm-participant-form-block-event_id">
              <td class="label">{$form.event_id.label}</td><td class="view-value bold">{$form.event_id.html}&nbsp;        
              {if $action eq 1 && !$past }<br /><a href='javascript:buildSelect( "event_id" );' id='past-event'>&raquo; {ts}Include past event(s) in this select list.{/ts}</a>{/if}    
              {if $is_test}
                {ts}(test){/ts}
              {/if}
              </td>
            </tr> 
            {if $single and $context neq 'standalone'}
          <tr class="crm-participant-form-block-displayName">
              <td class="label font-size12pt"><label>{ts}Participant{/ts}</label></td>
              <td class="font-size12pt view-value">{$displayName}&nbsp;</td>
          </tr>
          {else}
                {include file="CRM/Contact/Form/NewContact.tpl"}
            {/if}
            {if $action EQ 2}
              {if $additionalParticipants} {* Display others registered by this participant *}
                    <tr class="crm-participant-form-block-additionalParticipants">
                        <td class="label"><label>{ts}Also Registered by this Participant{/ts}</label></td>
                        <td>
                            {foreach from=$additionalParticipants key=apName item=apURL}
                                <a href="{$apURL}" title="{ts}view additional participant{/ts}">{$apName}</a><br />
                            {/foreach}
                        </td>
                    </tr>
              {/if}
              {if $registered_by_contact_id}
                    <tr class="crm-participant-form-block-registered-by">
                        <td class="label"><label>{ts}Registered By{/ts}</label></td>
                        <td class="view-value">
                          <a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=$participant_registered_by_id&cid=$registered_by_contact_id&action=view"}" title="{ts}view primary participant{/ts}">{$registered_by_display_name}</a>
                        </td>
                    </tr>
              {/if}
            {/if}
            {if $participantMode}
                <tr class="crm-participant-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}</td><td>{$form.payment_processor_id.html}</td></tr>
            {/if}
            <tr class="crm-participant-form-block-role_id"><td class="label">{$form.role_id.label}</td><td>{$form.role_id.html}</td></tr>
            <tr class="crm-participant-form-block-register_date">
                <td class="label">{$form.register_date.label}</td>
                <td>
                    {if $hideCalendar neq true}
                        {include file="CRM/common/jcalendar.tpl" elementName=register_date}
                    {else}
                        {$form.register_date.html|crmDate}
                    {/if}
          </td>
        </tr>
        <tr class="crm-participant-form-block-status_id">
          <td class="label">{$form.status_id.label}</td>
      <td>{$form.status_id.html}{if $event_is_test} {ts}(test){/ts}{/if}
              <div id="notify">{$form.is_notify.html}{$form.is_notify.label}</div>
      </td>
        </tr>
        <tr class="crm-participant-form-block-source">
            <td class="label">{$form.source.label}</td><td>{$form.source.html|crmReplace:class:huge}<br />
                <span class="description">{ts}Source for this registration (if applicable).{/ts}</span></td>
            </tr>
            </table>

            {* Fee block (EventFees.tpl) is injected here when an event is selected. *}
            <div id="feeBlock"></div>

            <fieldset>
            <table class="form-layout">
                <tr class="crm-participant-form-block-note">
                    <td class="label">{$form.note.label}</td><td>{$form.note.html}</td>
                </tr>
            </table>
            </fieldset>

            <div class="crm-participant-form-block-customData">
                <div id="customData" class="crm-customData-block"></div>  {* Participant Custom data *}
                <div id="customData{$eventNameCustomDataTypeID}" class="crm-customData-block"></div> {* Event Custom Data *}
                <div id="customData{$roleCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}  
                <div id="customData{$eventTypeCustomDataTypeID}" class="crm-customData-block"></div> {* Role Custom Data *}  
            </div>
      {/if}
     
        {if $accessContribution and $action eq 2 and $rows.0.contribution_id}
            {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
        {/if}

        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
</div>
{if $action eq 1 or $action eq 2}
{literal}
<script type="text/javascript">
    // event select
    function buildSelect( selectID ) {
        var elementID = '#' + selectID;
        cj( elementID ).html('');
        var postUrl = "{/literal}{crmURL p='civicrm/ajax/eventlist' h=0}{literal}";
        cj.post( postUrl, null,
            function ( response ) {
                response = eval( response );
                for (i = 0; i < response.length; i++) {
                    cj( elementID ).get(0).add(new Option(response[i].name, response[i].value), document.all ? i : null);
                }
                cj('#past-event').hide( );
                cj('input[name=past_event]').val(1);
                cj("#feeBlock").html( '' );
            }
        );
    }
    {/literal}

    {if $preloadJSSnippet}
       {$preloadJSSnippet}
    {else}
      //build fee block
      buildFeeBlock( );
   {/if}

   {literal}  
    //build discount block
    if ( document.getElementById('discount_id') ) {
      var discountId  = document.getElementById('discount_id').value;
      if ( discountId ) {
  var eventId  = document.getElementById('event_id').value;
  buildFeeBlock( eventId, discountId );    
      }
    }

  function buildFeeBlock( eventId, discountId )
  {
    var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q='snippet=4'}";
                dataUrl = dataUrl + '&qfKey=' + '{$qfKey}'
 
    {if $urlPathVar}
    dataUrl = dataUrl + '&' + '{$urlPathVar}'
    {/if}

    {literal}

    if ( !eventId ) {
      var eventId  = document.getElementById('event_id').value;
    }

    if ( eventId) {
      dataUrl = dataUrl + '&eventId=' + eventId;  
    } else {
                        cj('#eventFullMsg').hide( );
      cj('#feeBlock').html('');
      return;
    }

    var participantId  = "{/literal}{$participantId}{literal}";

    if ( participantId ) {
      dataUrl = dataUrl + '&participantId=' + participantId;  
    }

    if ( discountId ) {
      dataUrl = dataUrl + '&discountId=' + discountId;  
    }

    window.setTimeout(function(){
      cj.ajax({
        url: dataUrl,
        global: false
      })
      .done(function(data){
        cj("#feeBlock").html(data);
      });
    }, 1000);
              
        cj("#feeBlock").ajaxStart(function(){
            cj(".disable-buttons input").attr('disabled', true);
        });
        
        cj("#feeBlock").ajaxStop(function(){
            cj(".disable-buttons input").attr('disabled', false);
        });

        //show event real full as well as waiting list message. 
        if ( cj("#hidden_eventFullMsg").val( ) ) {
          cj( "#eventFullMsg" ).show( ).html( cj("#hidden_eventFullMsg" ).val( ) );
        } else {
          cj( "#eventFullMsg" ).hide( );
        }
  }    

</script>
{/literal}
{*include custom data js file*}
{include file="CRM/common/customData.tpl"}
{literal}
<script type="text/javascript">
    var roleGroupMapper = new Array( );
    {/literal}{foreach from=$participantRoleIds item="grps" key="rlId"}{literal}
      roleGroupMapper[{/literal}{$rlId}{literal}] = '{/literal}{$grps}{literal}';
    {/literal}{/foreach}{literal}

    function buildParticipantRole( eventID )
    {
         var dataUrl = "{/literal}{crmURL p='civicrm/ajax/rest' q='className=CRM_Event_Page_AJAX&fnName=participantRole&json=1&context=participant' h=0 }"{literal};
         
         if ( !eventId ) {
           var eventId  = document.getElementById( 'event_id' ).value;
     }
         if ( eventId ) {
           dataUrl = dataUrl + '&eventId=' + eventID;  
         }
      window.setTimeout(function(){
      cj.ajax({
        url: dataUrl,
        global: false,
        dataType: "json"
      })
      .done(function(response){
        if ( response.role ) {
          for ( var i in roleGroupMapper ) {
            if ( i != 0 ) {
              if ( i == response.role ) {
                document.getElementById("role_id[" +i+ "]"  ).checked = true;
              }
              else {
                document.getElementById("role_id[" +i+ "]"  ).checked = false;
              }  
              showCustomData( 'Participant', i, {/literal} {$roleCustomDataTypeID} {literal} );
            }
          }
        }
      });
      
      }, 1000);
    }

  
    function showCustomData( type, subType, subName )
    {
        var dataUrl = {/literal}"{crmURL p=$urlPath h=0 q='snippet=4&type='}"{literal} + type;
         
        var roleid = "role_id["+subType+"]";
               
        var loadData = false;
     
        if ( document.getElementById( roleid ).checked == true ) {
            if ( roleGroupMapper[subType] ) {
                var splitGroup = roleGroupMapper[subType].split(",");
                for ( i = 0; i < splitGroup.length; i++ ) {
                    var roleCustomGroupId = splitGroup[i];
                    if ( cj( '#'+roleCustomGroupId ).length > 0 ) {
                        cj( '#'+roleCustomGroupId ).remove( );
                    }
                } 
                loadData = true;  
            }
           
            if ( loadData && roleGroupMapper[0] ) {
                var splitGroup = roleGroupMapper[0].split(",");
                for ( i = 0; i < splitGroup.length; i++ ) {
                    var roleCustomGroupId = splitGroup[i];
                    if ( cj( '#'+roleCustomGroupId ).length > 0 ) {
                        cj( '#'+roleCustomGroupId ).remove( );
                    }
                } 
            }
        } else {
            var groupUnload = new Array( );
            var x = 0;
                
            if ( roleGroupMapper[0] ) {
                var splitGroup = roleGroupMapper[0].split(",");
                for ( x = 0; x < splitGroup.length; x++ ) {
                    groupUnload[x] = splitGroup[x];
                }
            }

            for ( var i in roleGroupMapper ) {
                if ( ( i != 0 ) && document.getElementById( "role_id["+i+"]" ) && document.getElementById( "role_id["+i+"]" ).checked == true ) {
                    var splitGroup = roleGroupMapper[i].split(",");
                    for ( j = 0; j < splitGroup.length; j++ ) {
                        groupUnload[x+j+1] = splitGroup[j];
                    }
                }
            } 

            if ( roleGroupMapper[subType] ) {
                var splitGroup = roleGroupMapper[subType].split(",");
                for ( i = 0; i < splitGroup.length; i++ ) {
                    var roleCustomGroupId = splitGroup[i];
                    if ( cj( '#'+roleCustomGroupId ).length > 0 ) {
                        if ( cj.inArray( roleCustomGroupId, groupUnload ) == -1  ) {
                            cj( '#'+roleCustomGroupId ).remove( );
                        }
                    }
                } 
            }        
        }

        if ( !( loadData ) ) {
           return false;
        }       
 
        if ( subType ) {
            dataUrl = dataUrl + '&subType=' + subType;
        }
  
        if ( subName ) {
            dataUrl = dataUrl + '&subName=' + subName;
            cj( '#customData' + subName ).show( );
        } else {
            cj( '#customData' ).show( );    
        }
  
       {/literal}
       {if $urlPathVar}
           dataUrl = dataUrl + '&' + '{$urlPathVar}'
       {/if}
       {if $groupID}
           dataUrl = dataUrl + '&groupID=' + '{$groupID}'
       {/if}
       {if $qfKey}
           dataUrl = dataUrl + '&qfKey=' + '{$qfKey}'
       {/if}
       {if $entityID}
           dataUrl = dataUrl + '&entityID=' + '{$entityID}'
       {/if}
 
       {literal}
       
           if ( subName && subName != 'null' ) {    
               var fname = '#customData' + subName;
           } else {
               var fname = '#customData';
           }    
  
       window.setTimeout(function(){
         cj.ajax({
            url: dataUrl
         })
         .done(function(response){
           if ( subType != 'null' ) {
             if ( document.getElementById(roleid).checked == true ) {
               var response_text = '<div style="display:block;" id = '+subType+'_chk >'+response+'</div>';
               cj( fname ).append(response_text);
             }
             else {
               cj('#'+subType+'_chk').remove();
             }
           }                 
         });
       }, 1000); 
   }
  cj(function() {        
    {/literal}
    buildCustomData( '{$customDataType}', 'null', 'null' );
    {literal}
        for ( var i in roleGroupMapper ) {
          if (roleGroupMapper[i].length > 0) {
            if ( ( i != 0 ) && document.getElementById( "role_id["+i+"]" ) && document.getElementById( "role_id["+i+"]" ).checked == true ) {
               {/literal}
               showCustomData( '{$customDataType}', i, {$roleCustomDataTypeID} );
               {literal}  
            }
            else if(i == 0) {
              // do nothing here
            }
          }
        }
        {/literal}
    {if $eventID}
        buildCustomData( '{$customDataType}', {$eventID}, {$eventNameCustomDataTypeID} );
    {/if}
    {if $eventTypeID}
        buildCustomData( '{$customDataType}', {$eventTypeID}, {$eventTypeCustomDataTypeID} );
    {/if}
    
    //call pane js
    cj().crmaccordions();
    {literal}
  });
</script>
{/literal}  

{/if}

    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}

{/if} {* end of eventshow condition*}

<script type="text/javascript">
{literal}
  sendNotification(false);
  function sendNotification(checked) {
    cj("#notify").hide();
    cj('#send_confirmation_receipt').hide();
    cj("#is_notify").attr('checked', false);

    var status_id = cj("select#status_id option:selected").val();
    {/literal} 
    var participant_status = {$participantStatus};
    var participant_status_counted = {$participantStatusCounted};
    var status = participant_status[status_id];
    var is_counted = participant_status_counted[status_id] ? 1 : 0;
    if ( status == 'Cancelled' || 
         status == 'Pending from waitlist' || 
         status == 'Pending from approval' || 
         status == 'Expired' ) {literal}{
      cj("#notify").show();
      if(checked === false || checked === true) {
        cj("#is_notify").attr('checked', checked);
      }
      else{
        if(status == 'Cancelled' || status == 'Expired') {
          cj("#is_notify").attr('checked', false);
        }
        else{
          cj("#is_notify").attr('checked', true);
        }
      }
      cj('#send_confirmation_receipt').hide();
      cj('#send_receipt').attr('checked', false);
    }
    else {
      if(is_counted){
        cj('#send_confirmation_receipt').show();
      }
      else {
        cj('#send_receipt').attr('checked', false);
        cj('#send_confirmation_receipt').hide();
      }
    }
  }

  function buildEventTypeCustomData( eventID, eventTypeCustomDataTypeID, eventAndTypeMapping ) {
    var mapping = eval('(' + eventAndTypeMapping + ')');
    buildCustomData( 'Participant', mapping[eventID], eventTypeCustomDataTypeID );
  }
{/literal}
</script>
{literal}
<script type="text/javascript">
cj(function() {
   cj().crmaccordions(); 
});
</script>
{/literal}
{include file="CRM/common/chosen.tpl" selector="#event_id"}

