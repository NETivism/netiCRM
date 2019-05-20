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

{include file="CRM/common/enableDisable.tpl"}

{if $action eq 4} {* when action is view *}
    {if $recur}
        <h3>{ts}Recurring contributions{/ts}</h3>
        <div class="crm-block crm-content-block crm-recurcontrib-view-block">
          <table class="crm-info-panel">
            <tr><td class="label">{ts}From{/ts}</td><td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contactId`" h=0 a=1 fe=1}">{$displayName}</a></td></tr>
            <tr><td class="label">{ts}Recurring Contribution ID{/ts}</td><td>{$recur.id}</td></tr>
            <tr><td class="label">{ts}Amount{/ts}</td><td>{$recur.amount} {$recur.currency}</td></tr>
            <tr><td class="label">{ts}Frequency{/ts}</td><td>{ts}every{/ts} {$recur.frequency_interval} {ts}{$recur.frequency_unit}{/ts}</td></tr>
            <tr><td class="label">{ts}Cycle Day{/ts}</td><td>{$recur.cycle_day} {if $recur.frequency_unit == 'week'}{else}{ts}day{/ts}{/if}</td></tr>
            <tr><td class="label">{ts}Installments{/ts}</td><td>{if $recur.installments}{$recur.installments}{else}{ts}no limit{/ts}{/if}</td></tr>
            <tr><td></td><td></td></tr>
            <tr><td class="label">{ts}Create date{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Start date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
            {if $recur.modified_date}<tr><td class="label">{ts}Last Modified Date{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}Cancel Date{/ts}</td><td>{$recur.cancel_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}End Date{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>
            {/if}
            <tr><td></td><td></td></tr>
            {if $recur.processor_id}<tr><td class="label">{ts}Processor ID{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
            {if $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next Sched Contribution{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}

            {* trxn_id Used in spgateway. refs #16960 - flr. 65 *}
            <tr><td class="label">{ts}Transaction ID{/ts}</td><td>{$recur.trxn_id}</td></tr>
            <!--
            {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
            {if $recur.failure_count}<tr><td class="label">{ts}Failure Count{/ts}</td><td>{$recur.failure_count}</td></tr>{/if}
            {if $recur.next_sched_contribution}<tr><td class="label">{ts}Failure Retry Date{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}
            {if $recur.auto_renew}<tr><td class="label">{ts}Auto Renew{/ts}</td><td>{if $recur.auto_renew}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>{/if}
            {if $recur.payment_processor}<tr><td class="label">{ts}Payment processor:{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
            -->
            <tr><td class="label">{ts}Recurring Status{/ts}</td><td>{$recur.contribution_status}</td></tr>
          </table>

          <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
            <div class="crm-accordion-header">
              <div class="zmdi crm-accordion-pointer"></div> 
              {ts}Change Log:{/ts}
            </div>
            <div class="crm-accordion-body">
              <table class="crm-info-panel">
                <thead>
                  <tr>
                    <th>{ts}Modified Date{/ts}</th>
                    <th>{ts}Note{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th>{ts}Amount{/ts}</th>
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
                      {if $log.note}
                        {if $log.note}
                          {help id="recur-note-`$key`" text="`$log.note`"}
                        {/if}
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
                    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$log.modified_id`" h=0 a=1 fe=1}">{$log.modified_name}</a></td>
                  </tr>
                {/foreach}
              </table>
            </div>
            
          </div>

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
          <div class="crm-submit-buttons"><input type="button" name='cancel' value="{ts}Back to Listings{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=contribute'}';"/></div>
        </div>
    {/if}
{/if}
{if $recurRows}
    {strip}
    <table class="selector">
        <tr class="columnheader">
            <th scope="col">#</th>
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
      <tr><td class="label">{$form.frequency_unit.label}</td><td>{ts}every{/ts} {$form.frequency_interval.html} {ts}{$form.frequency_unit.html}{/ts}</td></tr>
      <tr><td class="label">{$form.cycle_day.label}</td><td>{$form.cycle_day.html} {if $form.frequency_unit == 'week'}{else}{ts}day{/ts}{/if}</td></tr>
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
      <tr><td class="label">{$form.trxn_id.label}</td><td>{$form.trxn_id.html}</td></tr>
      {if $form.auto_renew.html}<tr><td class="label">{$form.auto_renew.label}</td><td>{$form.auto_renew.html}</td></tr>{/if}
      <tr><td class="label">{$form.contribution_status_id.label}</td><td>{$form.contribution_status_id.html}</td></tr>
      <tr><td class="label">{$form.note_title.label}</td><td>{$form.note_title.html}</td></tr>
      <tr><td class="label">{$form.note_body.label}</td><td>{$form.note_body.html}</td></tr>
    </table>
    <div class="crm-section recurcontrib-buttons-section no-label">
      <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      <div class="clear"></div> 
    </div>
    {literal}
    <script>
      (function($){
        $(function(){
          $('#contribution_status_id').change(function(){
            var $this = $(this);
            if ($this.val() != $this.attr('data-origin-type')) {
              $('#note_title').val("{/literal}{ts}Change status to %status%{/ts}{literal}".replace("%status%", $this.find(":selected").text()));
            }
            else {
              $('#note_title').val("");
            }
          })
        });
      })(cj);
    </script>
    {/literal}
    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}
</div>
{/if}
