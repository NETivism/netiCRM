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
{include file="CRM/Event/Form/Registration/Progress.tpl"}
{if $skipCountMessage}
    <h3>{$skipCountMessage}</h3>
{/if}
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}

{*CRM-4320*}
{if $statusMessage}
    <div class="messages status">
        <p>{$statusMessage}</p>
    </div>
{/if}

{if $priceSet && $allowGroupOnWaitlist}   
    {include file="CRM/Price/Form/ParticipantCount.tpl"}
    <div id="waiting-status" style="display:none;" class="messages status"></div>
    <div class="messages status" style="width:25%"><span id="event_participant_status"></span></div> 
{/if}

<div class="crm-block crm-event-additionalparticipant-form-block">
{if $priceSet}
     <fieldset id="priceset" class="crm-group priceset-group"><legend>{$event.fee_label}</legend>
     	 {include file="CRM/Price/Form/PriceSet.tpl"}
    </fieldset>
{else}
    {if $paidEvent}
        <table class="form-layout-compressed">
            <tr class="crm-event-additionalparticipant-form-block-amount">
                <td class="label nowrap">{$event.fee_label} <span class="marker">*</span></td>
                <td>&nbsp;</td>
                <td>{$form.amount.html}</td>
            </tr>
        </table>
    {/if}
{/if}

{assign var=n value=email-$bltID}
  <div class="crm-section email-section">
    <div class="label">{$form.$n.label}</div>
    <div class="content">{$form.$n.html}</div>
    <div class="clear"></div>
  </div>

{include file="CRM/UF/Form/Block.tpl" fields=$additionalCustomPre} 
{include file="CRM/UF/Form/Block.tpl" fields=$additionalCustomPost} 
{include file="CRM/common/moveEmail.tpl"}

{if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

<div id="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
</div>
</div>

{if $priceSet && $allowGroupOnWaitlist}
{literal} 
<script type="text/javascript">

function allowGroupOnWaitlist( participantCount, currentCount ) 
{
  var formId          = {/literal}'{$formId}'{literal};
  var waitingMsg      = {/literal}'{$waitingMsg}'{literal};
  var confirmedMsg    = {/literal}'{$confirmedMsg}'{literal};
  var paymentBypassed = {/literal}'{$paymentBypassed}'{literal};

  var availableRegistrations = {/literal}{$availableRegistrations}{literal}; 
  if ( !participantCount ) participantCount = {/literal}'{$currentParticipantCount}'{literal};	 
  var totalParticipants = parseInt(participantCount) + parseInt(currentCount);

  var seatStatistics = '{/literal}{ts 1="' + totalParticipants + '"}Total Participants : %1{/ts}{literal}' + '<br />' + '{/literal}{ts 1="' + availableRegistrations + '"}Event Availability : %1{/ts}{literal}';
  cj("#event_participant_status").html( seatStatistics );
  
  if ( !{/literal}'{$lastParticipant}'{literal} ) return; 

  if ( totalParticipants > availableRegistrations ) {

    cj('#waiting-status').show( ).html(waitingMsg);

    if ( paymentBypassed ) {
      cj('input[name=_qf_Participant_'+ formId +'_next]').parent( ).show( );
      cj('input[name=_qf_Participant_'+ formId +'_next_skip]').parent( ).show( );
    }  
  } else {
    if ( paymentBypassed ) {
      confirmedMsg += '<br /> ' + paymentBypassed;
    }	
    cj('#waiting-status').show( ).html(confirmedMsg);

    if ( paymentBypassed ) {
      cj('input[name=_qf_Participant_'+ formId +'_next]').parent( ).hide( );
      cj('input[name=_qf_Participant_'+ formId +'_next_skip]').parent( ).hide( );
    }  
  }
}

</script>
{/literal} 	
{/if}
