{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
{if $cftype eq 'ContributionRecur'}
    {include file="CRM/Custom/Form/CustomData.tpl"}
{else}

{include file="CRM/common/enableDisable.tpl"}

{if $action eq 4} {* when action is view *}
    {if $recur}
        <h3>{ts}Recurring contributions{/ts} {if $ach}(ACH){/if}</h3>
        <div class="crm-block crm-content-block crm-recurcontrib-view-block">
          <table class="crm-info-panel">
            <tr><td class="label">{ts}From{/ts}</td><td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contactId`" h=0 a=1 fe=1}">{$displayName}</a></td></tr>
            {if $donateAgain}
            <tr>
              <td class="label">{ts}Donate Again URL{/ts}</td>
              <td>
                <div class="flex-general">
                  <textarea name="url_to_copy" class="url_to_copy" cols="50" rows="1" onclick="this.select(); document.execCommand('copy');" data-url-original="{$donateAgain}">{$donateAgain}</textarea>
                  <span>
                    <a href="#" class="button url-copy" onclick="document.querySelector('textarea[name=url_to_copy]').select(); document.execCommand('copy'); return false;"><i class="zmdi zmdi-link"></i>{ts}Copy{/ts}</a>
                  </span>
                  <span>
                    <a href="#" class="button url-shorten" data-url-shorten="url_to_copy" data-page-id="" data-page-type=""><i class="zmdi zmdi-share"></i> {ts}Shorten URL{/ts}</a>
                  </span>
                </div>
                <div class="description font-red"><i class="zmdi zmdi-alert-triangle font-red"></i>{ts}URL will extract personal data from this contact to URL visitor. Make sure send this link to their personal device / email address.{/ts}</div>
                <div>
                <div class="description">{ts 1=7}This URL will be expired on %1 days later.{/ts}</div>
                <div>
                  <span><i class="zmdi zmdi-email"></i><a href="{crmURL p='civicrm/contact/view/activity' q="action=add&reset=1&cid=`$contactId`&selectedChild=activity&atype=3"}" target="_blank">{ts}Send an Email{/ts}</a></span>
                  {if $sendSMS}
                  <span><i class="zmdi zmdi-smartphone-android"></i><a href="{crmURL p='civicrm/contact/view/activity' q="action=add&reset=1&cid=`$contactId`&selectedChild=activity&atype=4"}" target="_blank">{ts}Send SMS{/ts}</a></span>
                  {/if}
                </div>
                {include file="CRM/common/ShortenURL.tpl"}
              </td>
            </tr>
            {/if}
            <tr><td class="label">{ts}Recurring Contribution ID{/ts}</td><td>
              {$recur.id}
              {if $recur.is_test}
                <span class="font-red">{ts}(test){/ts}</span>
              {/if}
            </td></tr>
            <tr><td class="label">{ts}Amount{/ts}</td><td>{$recur.amount} {$recur.currency}</td></tr>
            <tr><td class="label">{ts}Frequency{/ts}</td><td>{ts}every{/ts} {$recur.frequency_interval} {ts}{$recur.frequency_unit}{/ts}</td></tr>
            <tr><td class="label">{ts}Cycle Day{/ts}</td><td>{$recur.cycle_day} {if $recur.frequency_unit == 'week'}{else}{ts}day{/ts}{/if}</td></tr>
            <tr><td class="label">{ts}Installments{/ts}</td><td>{if $recur.installments}{$recur.installments}{else}{ts}no limit{/ts}{/if}</td></tr>
            <tr><td></td><td></td></tr>
            <tr><td class="label">{ts}Create date{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Start date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
            {if $recur.modified_date}<tr><td class="label">{ts}Last Modified Date{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}Cancel Date{/ts}</td><td>{$recur.cancel_date|crmDate}</td></tr>{/if}
            {if $recur.end_date}<tr><td class="label">{ts}End Date{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>
            {/if}
            <tr><td></td><td></td></tr>
            {if $recur.processor_id}<tr><td class="label">{ts}Processor ID{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
            {if $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next Sched Contribution{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}

            {* trxn_id Used in spgateway. refs #16960 - flr. 65 *}
            {if $hide_fields}{if 'trxn_id'|in_array:$hide_fields}<!--{/if}{/if}
            <tr><td class="label">{ts}Transaction ID{/ts}</td><td>{$recur.trxn_id}</td></tr>
            {if $hide_fields}{if 'trxn_id'|in_array:$hide_fields}-->{/if}{/if}
            {if $hide_fields}{if 'invoice_id'|in_array:$hide_fields}<!--{/if}{/if}
            {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
            {if $hide_fields}{if 'invoice_id'|in_array:$hide_fields}-->{/if}{/if}
            <!--
            {if $recur.failure_count}<tr><td class="label">{ts}Failure Count{/ts}</td><td>{$recur.failure_count}</td></tr>{/if}
            {if $recur.next_sched_contribution}<tr><td class="label">{ts}Failure Retry Date{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}
            {if $recur.auto_renew}<tr><td class="label">{ts}Auto Renew{/ts}</td><td>{if $recur.auto_renew}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>{/if}
            {if $recur.payment_processor}<tr><td class="label">{ts}Payment processor:{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
            -->
            <tr><td class="label">{ts}Recurring Status{/ts}</td><td>{$recur.contribution_status}</td></tr>
            {if $record_detail}
            {capture assign="card_expiry_date"}{ts}Card Expiry Date{/ts}{/capture}
            {include file="CRM/common/clickToShow.tpl"}
            <tr><td></td><td></td></tr>
              {foreach from=$record_detail key=label item=value}
                <tr>
                    <td class="label">{$label}</td>
                    <td>
                      {if $label eq $card_expiry_date}
                        <div class="click-to-show"><a href="#" class="click-to-show-trigger">{ts}Please pay attention to protect credit card information.{/ts} {ts}Click to show details{/ts} *******</a><span class="click-to-show-info">{$value}</span></div>
                      {else}
                        {$value}
                      {/if}
                    </td>
                </tr>
              {/foreach}
            {/if}

          </table>

          {if $ach}
          <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
            <div class="crm-accordion-header">
              <div class="zmdi crm-accordion-pointer"></div> 
              {ts}ACH{/ts}
            </div>
            <div class="crm-accordion-body">
              <table class="crm-info-panel">
              <tr><td class="label">{ts}Stamp Verification Status{/ts}</td><td>{$ach.stamp_verification_label}</td></tr>
              {if $ach.stamp_verification == 0}
                <tr><td class="label">{ts}Stamp Verification Date{/ts}</td><td>{ts}None{/ts}</td></tr>
              {elseif $ach.stamp_verification == 1}
                <tr><td class="label">{ts}Stamp Verification Date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
              {elseif $ach.stamp_verification == 2}
                <tr><td class="label">{ts}Stamp Verification Result{/ts}</td><td>{$ach.stamp_verification_reason}</td></tr>
              {/if}
              <tr><td class="label">{ts}Payment Instrument{/ts}</td><td>{ts}{$ach.payment_type}{/ts}</td></tr>
              {if $ach.payment_type == 'ACH Bank'}
                <tr><td class="label">{ts}Bank Identification Number{/ts}</td><td>{ts}{$ach.bank_code}{/ts}</td></tr>
                <tr><td class="label">{ts}Bank Branch{/ts}</td><td>{ts}{$ach.bank_branch}{/ts}</td></tr>
              {else}
                <tr><td class="label">{ts}Post Office Account Type{/ts}</td><td>{ts}{$ach.postoffice_acc_typ}{/ts}</td></tr>
              {/if}
              <tr><td class="label">{ts}Bank Account Number{/ts}</td><td>{ts}{$ach.bank_account}{/ts}</td></tr>
              <tr><td class="label">{ts}Legal Identifier{/ts}</td><td>{ts}{$ach.identifier_number}{/ts}</td></tr>
              </table>
            </div>
          </div>
          {/if}

          {include file="CRM/Custom/Page/CustomDataView.tpl"}

          {if $logs}
          <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
            <div class="crm-accordion-header">
              <div class="zmdi crm-accordion-pointer"></div> 
              {ts}Change Log:{/ts}
            </div>
            <div class="crm-accordion-body">
              <table class="crm-info-panel">
                <thead>
                  <tr>
                    <th>{ts}Modified Date{/ts}</th>
                    <th>{ts}Note Title{/ts}</th>
                    <th>{ts}Note Text{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th>{ts}Amount{/ts}</th>
                    <th>{ts}Other{/ts}</th>
                    <th>{ts}Modified By{/ts}</th>
                  </tr>
                </thead>
                {foreach  from=$logs key=key item=log}
                  <tr>
                    <td>{$log.modified_date|crmDate}</td>
                    <td>
                      {if $log.note_subject}
                        {$log.note_subject}
                      {/if}
                    </td>
                    <td>
                      {if $log.note}
                        {$log.note|nl2br}
                      {/if}
                    </td>
                    <td>
                      {if $log.contribution_status}
                        {$log.contribution_status}
                      {elseif $log.before_contribution_status AND $log.after_contribution_status}
                        <span class="disabled">{$log.before_contribution_status}</span> → {$log.after_contribution_status}
                      {/if}
                    </td>
                    <td>
                      {if $log.amount}
                        {$log.amount}
                      {elseif $log.before_amount AND $log.after_amount}
                        <span class="disabled">{$log.before_amount}</span> → {$log.after_amount}
                      {/if}
                    </td>
                    <td>
                      {if $log.other_diff}
                        {$log.other_diff}
                      {/if}
                    </td>
                    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$log.modified_id`" h=0 a=1 fe=1}">{$log.modified_name}</a></td>
                  </tr>
                {/foreach}
              </table>
            </div>
          </div>
          {/if}

          {literal}
          <script type="text/javascript">
          cj(function() {
            cj().crmaccordions(); 
          });
          </script>
          {/literal}

          {* Recurring Contribution *}
          {if $rows}
            {include file="CRM/Contribute/Form/Selector.tpl"}
          {else}
            <div class="messages status">
                    {ts}No contributions have been recorded from this contact.{/ts}
            </div>
          {/if}
          <div class="crm-submit-buttons">
            <input type="button" name='cancel' value="{ts}Back to Listings{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q="action=browse&selectedChild=contribute&cid=`$contactId`"}';"/>
            {if $is_editable}
              <a class="button" href="{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=`$contributionRecurId`&cid=`$contactId`&action=update"}" accesskey="e"><i class="zmdi zmdi-edit"></i>{ts}edit{/ts}</a>

              {if $ach}
                <a class="button" href="{crmURL p='civicrm/contribute/taiwanach' q="reset=1&id=`$ach.id`&cid=`$contactId`&action=update"}" accesskey="e"><i class="zmdi zmdi-edit"></i>{ts}edit ACH{/ts}</a>
              {/if}
            {/if}
            {$form.$submit_name.html}
          </div>
        </div>
    {/if}
{/if}
{if $recurRows}
    {strip}
    <table class="selector">
        <tr class="columnheader">
            <th scope="col">#</th>
            <th scope="col">{ts}Payment Processor{/ts}</th>
            <th scope="col">{ts}Amount{/ts}</th>
            <th scope="col">{ts}Frequency{/ts}</th>
            <th scope="col">{ts}Start Date{/ts}</th>
            <th scope="col">{ts}End Date{/ts}</th>
            <th scope="col">{ts}Cancel Date{/ts}</th>
            <th scope="col">{ts}Installments{/ts}</th>
            <th scope="col">{ts}Status{/ts}</th>
            <th scope="col">{ts}Cycle Day{/ts}</th>
            <th scope="col">&nbsp;</th>
        </tr>

        {foreach from=$recurRows item=row}
            {assign var=id value=$row.id}
            <tr id="row_{$row.id}" class="{cycle values="even-row,odd-row"}{if NOT $row.is_active} disabled{/if}">
                <td>{$row.id}</td>
                <td>{$row.payment_processor}</td>
                <td>{$row.amount|crmMoney}</td>
                <td>{ts}every{/ts} {$row.frequency_interval} {$row.frequency_unit} </td>
                <td>{$row.start_date|crmDate}</td>
                <td>{$row.end_date|crmDate}</td>
                <td>{$row.cancel_date|crmDate}</td>
                <td>{$row.installments}</td>
                <td>{$row.contribution_status}</td>
                <td>{$row.cycle_day} {ts}day{/ts}</td>
                <td>
                    {$row.action|replace:'xx':$row.recurId}
                </td>
            </tr>
        {/foreach}
    </table>
    {/strip}
{/if}

{if $action eq 1 or $action eq 2} {* action is add or update *}
<div class="view-content">
    <h3>
      {if $action eq 1}{ts}New Recurring Payment{/ts}{else}{ts}Recurring contributions{/ts}{/if}
    </h3>
    <table class="form-layout-compressed">
      <tr><td class="label">{ts}From{/ts}</td><td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contactId`" h=0 a=1 fe=1}">{$displayName}</a></td></tr>
      <tr><td class="label">{$form.id.label}</td><td>{$form.id.html}</td></tr>
      <tr><td class="label">{$form.amount.label}</td><td>{$form.amount.html}</td></tr>
      <tr><td class="label">{$form.currency.label}</td><td>{$form.currency.html}</td></tr>
      <tr><td class="label">{$form.frequency_unit.label}</td><td><span id="frequency_interval_block">{ts}every{/ts} {$form.frequency_interval.html} {ts}{$form.frequency_unit.html}{/ts}</span></td></tr>
      <tr><td class="label">{$form.cycle_day.label}</td><td>
        {$form.cycle_day.html}
        {if $payment_type == 'SPGATEWAY'}<span id="cycle_day_date_block">{include file="CRM/common/jcalendar.tpl" elementName=cycle_day_date}</span>{/if}
        {if $form.frequency_unit == 'week'}{else}{ts}day{/ts}{/if}
      </td></tr>
      <tr><td class="label">{$form.installments.label}</td><td>{$form.installments.html}</td></tr>
      <tr><td></td><td></td></tr>
      <tr><td class="label">{$form.create_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=create_date}</td></tr>
      <tr><td class="label">{$form.start_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td></tr>
      {if $form.modified_date.html}<tr><td class="label">{$form.modified_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=modified_date}</td></tr>{/if}
      {if $form.cancel_date.html}<tr><td class="label">{$form.cancel_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=cancel_date}</td></tr>{/if}
      {if $form.cancel_date.html}<tr><td class="label">{$form.end_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td></tr>
      {/if}
      <tr><td></td><td></td></tr>
      {if $form.processor_id.html}<tr><td class="label">{$form.processor_id.label}</td><td>{$form.processor_id.html}</td></tr>{/if}
      {if $form.contribution_status_id neq 3}<tr><td class="label">{$form.next_sched_contribution.label}</td><td>{$form.next_sched_contribution.html|crmDate}</td></tr>{/if}

      {* trxn_id Used in spgateway. refs #16960 - flr. 65 *}
      {if $hide_fields}{if 'trxn_id'|in_array:$hide_fields}<!--{/if}{/if}
      <tr><td class="label">{$form.trxn_id.label}</td><td>{$form.trxn_id.html}</td></tr>
      {if $hide_fields}{if 'trxn_id'|in_array:$hide_fields}-->{/if}{/if}

      {if $form.auto_renew.html}<tr><td class="label">{$form.auto_renew.label}</td><td>{$form.auto_renew.html}</td></tr>{/if}
      <tr><td class="label">{$form.contribution_status_id.label}</td><td>{$form.contribution_status_id.html}</td></tr>
      {if $form.note_title.html}
      <tr><td class="label">{$form.note_title.label}</td><td>{$form.note_title.html}</td></tr>
      {/if}
      {if $form.note_body.html}
      <tr><td class="label">{$form.note_body.label}</td><td>{$form.note_body.html}</td></tr>
      {/if}
    </table>
    <div id="customData"></div>
      {*include custom data js file*}
      {include file="CRM/common/customData.tpl"}	
      {literal}
        <script type="text/javascript">
          cj(document).ready(function() {
            {/literal}
              buildCustomData( '{$customDataType}' );
            {literal}
          });
        </script>
      {/literal}

    <div class="crm-section recurcontrib-buttons-section no-label">
      <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      <div class="clear"></div> 
    </div>
    {if $payment_type == 'SPGATEWAY'}
    {literal}
    <script>
      (function($){
        $(function(){
          {/literal}{if $set_active_only}{literal}
            var allowStatusId = ["3", "5"];
          {/literal}{else}{literal}
            var allowStatusId = ["1", "5", "7"];
          {/literal}{/if}{literal}
          if ($('select#contribution_status_id').length) {
            if ($('select#contribution_status_id').val() == 2) {
              $('select#contribution_status_id').attr('disabled','disabled');
              $('form#ContributionRecur input').attr('disabled','disabled');
            }
            else if (allowStatusId.includes($('select#contribution_status_id').val())){
              $('select#contribution_status_id option').each(function(i, e){
                if (!allowStatusId.includes(e.value)) {
                  e.remove();
                }
              });
            }
          }

          var freq = $('input#frequency_unit').val();
          $('input#frequency_unit').closest('td').append($('<select id="fake_frequency_unit"><option value="month">{/literal}{ts}monthly{/ts}{literal}</option><option value="year">{/literal}{ts}yearly{/ts}{literal}</option></select>'));
          $('select#fake_frequency_unit [value='+freq+']').attr('selected', 'selected');
          $('#frequency_interval_block').hide();

          // Set default value when type is year.
          if ($('input#frequency_unit').val() == 'year') {
            var monthDayValue = $('input#cycle_day').val();
            var month = ('0' + monthDayValue.replace(/^(\d{1,2})(\d{2})$/, "$1")).slice(-2);
            var day = monthDayValue.replace(/^(\d{1,2})(\d{2})$/, "$2");
            $('input#cycle_day_date').val(month+"-"+day);;
          }

          function updateFormStatusEnable() {
            var $inputs = $('input#amount, input#frequency_unit, input#cycle_day, input#installments');
            if ($('select#contribution_status_id').val() == 5) {
              $inputs.removeAttr('disabled');
              $('#fake_frequency_unit').removeAttr('disabled');
            }
            else {
              $inputs.each(function(i, e) {
                e.value = e.defaultValue;
                e.disabled = true;
              });
              $('#fake_frequency_unit').val($('input#frequency_unit').val()).attr('disabled','disabled');
            }

            $('input#frequency_unit').val($('#fake_frequency_unit').val());

            if ($('input#frequency_unit').val() == 'month') {
              console.log($('input#cycle_day').val());
              $('input#cycle_day').show();
              $('#cycle_day_date_block').hide();
              if (window.origin_type == 'year') {
                var cycle_day_e = document.getElementById('cycle_day');
                cycle_day_e.value = cycle_day_e.defaultValue;
              }
              if ($('input#cycle_day').val() > 28) {
                $('input#cycle_day').val(28);
              }
              if ($('input#cycle_day').val() < 1) {
                $('input#cycle_day').val(1);
              }
            }
            else {
              $('input#cycle_day').hide();
              $('#cycle_day_date_block').show();
              var monthDate = $('#cycle_day_date').val().replace("-", "");
              $('#cycle_day').val(monthDate);
            }
            window.origin_status_id = $('select#contribution_status_id').val();
            window.origin_type = $('input#frequency_unit').val();

          }

          updateFormStatusEnable();
          $('select#contribution_status_id').focus(function(e) {
            window.origin_status_id = e.target.value;
          });
          $('#fake_frequency_unit').focus(function(e) {
            window.origin_type = e.target.value;
          });
          $('#cycle_day_date').on('change', updateFormStatusEnable);
          $('input#cycle_day, select#fake_frequency_unit').change(updateFormStatusEnable);
          $('select#contribution_status_id').change(function(e){
            if (window.confirm("{/literal}{ts}If you set recurring status to 'Pause' or 'Finished'. It will send API request to payment processor provider. And all other change in this page will be recover. Are you sure to change this? {/ts}{literal}")) {
              updateFormStatusEnable();
            }
            else {
              e.target.value = window.origin_status_id;
            }
          })

        });
      })(cj);
    </script>
    {/literal}
    {/if}


    {if $form.note_title.html}
    {literal}
    <script>
      (function($){
        $(function(){
          $('#contribution_status_id').change(function(){
            var $this = $(this);
            if ($this.val() != $this.attr('data-origin-status')) {
              $('#note_title').data('status', $this.find(":selected").text());
            }
            else {
              $('#note_title').data('status', '');
            }
            updateNoteTitle()
          });

          $('#amount').change(function(){
            var $this = $(this);
            if ($this.val() != $this.attr('data-origin-amount')) {
              $('#note_title').data('amount', parseFloat($this.val()));
            }
            else {
              $('#note_title').data('amount', '');
            }
            updateNoteTitle()
          });

          function updateNoteTitle(){
            var textArray = [];
            if ($('#note_title').data('status')) {
              console.log($('#note_title').data('status'));
              textArray.push("{/literal}{ts}Change status to %status%{/ts}{literal}".replace("%status%", $('#note_title').data('status')))
            }
            if ($('#note_title').data('amount')) {
              textArray.push("{/literal}{ts}Change amount{/ts}{literal}");
            }
            $('#note_title').val(textArray.join(','));
          }
        });
      })(cj);
    </script>
    {/literal}
    {/if}
    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}
</div>
{/if}

{/if}