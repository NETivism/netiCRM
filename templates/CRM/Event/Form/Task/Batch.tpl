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
<div class="batch-update crm-block crm-form-block crm-event-batch-form-block">
<fieldset>
<div id="help">
    {if $context EQ 'statusChange'} {* Update Participant Status task *}
        {ts}Update the status for each participant individually, OR change all statuses to:{/ts}
        {$form.status_change.html}  {help id="id-status_change"}
        {if $notifyingStatuses}
        <p>{ts 1=$notifyingStatuses}Participants whose status is changed TO any of the following will be automatically notified via email: %1.{/ts}</p>
        {/if}
    {else}
        {ts}Update field values for each participant as needed. To set a field to the same value for ALL rows, enter that value for the first participation and then click the <strong>Copy icon</strong> (next to the column title).{/ts}
    {/if}
    <p>{ts}Click <strong>Update Participant(s)</strong> below to save all your changes.{/ts}</p>
</div>
    <legend>{$profileTitle}</legend>
         <table>
	  <thead class="sticky">
            <tr class="columnheader">
             {foreach from=$readOnlyFields item=fTitle key=fName}
	        <th>{$fTitle}</th>
	     {/foreach}

             <th>{ts}Event{/ts}</th>
             {foreach from=$fields item=field key=fieldName}
                {if strpos( $field.name, '_date' ) !== false ||
                    (substr( $field.name, 0, 7 ) == 'custom_' && $field.data_type == 'Date')}   
                  <th><span class="zmdi zmdi-copy action-icon" title="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" onclick="copyValues('{$field.name}')"></span>{$field.title}</th>
                {else}
                  <th><span class="zmdi zmdi-copy action-icon" title="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" onclick="copyValues('{$field.name}')"></span>{$field.title}</th>
                {/if}
             {/foreach}
            </tr>
          </thead>
            {foreach from=$componentIds item=pid}
            <tr class="{cycle values="odd-row,even-row"}">
	      {foreach from=$readOnlyFields item=fTitle key=fName}
	         <td>{$contactDetails.$pid.$fName}</td>
	      {/foreach}

              <td class="crm-event-title">{$details.$pid.title}</td>   
              {foreach from=$fields item=field key=fieldName}
                {assign var=n value=$field.name}
                {if ( $fields.$n.data_type eq 'Date') or ( $n eq 'participant_register_date' ) }
                   <td class="compressed">{include file="CRM/common/jcalendar.tpl" elementName=$n elementIndex=$pid batchUpdate=1}</td>
                {else}
                	<td class="compressed">
                    {$form.field.$pid.$n.html}
                    {if $field.name eq 'participant_status_id' and $contactDetails.$pid.do_not_notify eq '1'}
                      <span class="do-not-notify hide-block">
                        <i class="font-red zmdi zmdi-notifications-off" title="{ts}Contact labelled as do not notification.{/ts}"></i>
                      </span>
                    {/if}
                  </td>
                {/if}
              {/foreach}
            </tr>
            {/foreach}
          </tr>
          <tr class="do-not-notify hide-row">
            <td colspan="5">
              <div>
                <i class="zmdi zmdi-notifications-active"></i>
                {ts 1=$notifyingStatuses}Participants whose status is changed TO any of the following will be automatically notified via email: %1.{/ts}</div>
              {if $suppress_email_count}
              <div class="description font-red">
                <i class="font-red zmdi zmdi-notifications-off"></i>
                {ts count=$suppress_email_count plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT NOTIFY, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT NOTIFY, or contact is deceased).{/ts}
              </div>
              {/if}
            </td>
          </tr>
        </table>

        <div class="crm-submit-buttons">
          {if $fields}{$form._qf_Batch_refresh.html}{/if}{include file="CRM/common/formButtons.tpl"}
        </div>
</fieldset>
</div>
<script>{literal}
cj(document).ready(function($) {
  let notify_status = [{/literal}{$notify_status}{literal}];
  $('select[name*="participant_status_id"]').change(function(){
    $('.batch-update table .do-not-notify.hide-row .description').hide();
    let selected = parseInt($(this).val());
    if (notify_status.indexOf(selected) != -1) {
      $(this).closest('.crm-form-select-single').next('.do-not-notify').show();
    }
    else {
      $(this).closest('.crm-form-select-single').next('.do-not-notify').hide();
    }
  });
  $('select[name=status_change]').change(function(){
    let selected = parseInt($(this).val());
    $('td.compressed').find('.do-not-notify').hide();
    if (notify_status.indexOf(selected) != -1) {
      $('.batch-update table .do-not-notify.hide-row').show();
      $('.batch-update table .do-not-notify.hide-row .description').show();
    }
    else {
      $('.batch-update table .do-not-notify.hide-row').hide();
    }
  });
});
{/literal}</script>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
