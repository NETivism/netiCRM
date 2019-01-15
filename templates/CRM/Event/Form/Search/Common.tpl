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
<tr>
    <td class="crm-event-form-block-event_type"> {$form.event_id.label}  <br />{$form.event_id.html|crmReplace:class:huge} </td>
    <td class="crm-event-form-block-event_type"> {$form.event_type.label}<br />{$form.event_type.html} </td>
</tr>     
 
<tr> 
    <td class="crm-event-form-block-event_start_date_low">  
       {$form.event_start_date_low.label}<br />{include file="CRM/common/jcalendar.tpl" elementName=event_start_date_low}
    </td>
    <td class="crm-event-form-block-event_end_date_high"> 
       {$form.event_end_date_high.label}<br />{include file="CRM/common/jcalendar.tpl" elementName=event_end_date_high}
    </td> 
</tr>

<tr>
    <td class="crm-event-form-block-participant_status"><label>{ts}Participant Status{/ts}</label> 
      <br />
     {$form.participant_status_id.html}
    </td>
    <td class="crm-event-form-block-participant_role_id"><label>{ts}Participant Role{/ts}</label>
      <br />
      {$form.participant_role_id.html}
    </td>
  
</tr> 
<tr>
    <td class="crm-event-form-block-participant_test">{$form.participant_test.html}&nbsp;{$form.participant_test.label}</td> 
    <td class="crm-event-form-block-participant_pay_later">{$form.participant_pay_later.html}&nbsp;{$form.participant_pay_later.label}</td> 
</tr>
<tr>
    <td class="crm-event-form-block-participant_fee_level">{$form.participant_fee_id.label}<br />{$form.participant_fee_id.html}</td>
     <td class="crm-event-form-block-participant_fee_amount"><label>{ts}Fee Amount{/ts}</label><br />
     	{$form.participant_fee_amount_low.label} &nbsp; {$form.participant_fee_amount_low.html} &nbsp;&nbsp; 
	{$form.participant_fee_amount_high.label} &nbsp; {$form.participant_fee_amount_high.html}
     </td> 
</tr>
{if $participantGroupTree }
<tr>
    <td colspan="4">
       {include file="CRM/Custom/Form/Search.tpl" groupTree=$participantGroupTree showHideLinks=false}
    </td>
</tr>
{/if}

{literal}
<script type="text/javascript">
eval( 'tokenClass = { tokenList: "token-input-list-facebook", token: "token-input-token-facebook", tokenDelete: "token-input-delete-token-facebook", selectedToken: "token-input-selected-token-facebook", highlightedToken: "token-input-highlighted-token-facebook", dropdown: "token-input-dropdown-facebook", dropdownItem: "token-input-dropdown-item-facebook", dropdownItem2: "token-input-dropdown-item2-facebook", selectedDropdownItem: "token-input-selected-dropdown-item-facebook", inputToken: "token-input-input-token-facebook" } ');

var hintText = "{/literal}{ts}Type in a partial or complete name of an existing event.{/ts}{literal}";
var eventUrl = "{/literal}{$dataURLEvent}{literal}";
{/literal}
{if $eventPrepopulate}
  var eventPrepopulate = {$eventPrepopulate};
{else}
  var eventPrepopulate = '';
{/if}
{literal}
cj("#event_id").tokenInput( eventUrl, { prePopulate: eventPrepopulate, classes: tokenClass, hintText: hintText });

var typeUrl  = "{/literal}{$dataURLEventType}{literal}";
var feeUrl   = "{/literal}{$dataURLEventFee}{literal}";

cj('#event_type').autocomplete( typeUrl, { width : 180, selectFirst : false, matchContains: true
                               }).result(function(event, data, formatted) { cj( "input#event_type_id" ).val( data[1] );
                               }).bind( 'click', function( ) { cj( "input#event_type_id" ).val(''); });

cj('#participant_fee_level').autocomplete( feeUrl, { width : 180, selectFirst : false, matchContains: true
                                         }).result(function(event, data, formatted) { cj( "input#participant_fee_id" ).val( data[1] );
                                         }).bind( 'click', function( ) { cj( "input#participant_fee_id" ).val(''); });
</script>
{/literal}
{include file="CRM/common/chosen.tpl" selector="select[name^=participant_status_id],select[name^=participant_role_id],select[name^=participant_fee_id]" select_width=400}
