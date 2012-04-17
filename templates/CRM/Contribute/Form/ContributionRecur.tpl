{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
<div class="view-content">
{if $action eq 4}{* when action is view  *}
    {if $recur}
        <h3>{ts}View Recurring Payment{/ts}</h3>
        <div class="crm-block crm-content-block crm-recurcontrib-view-block">
          <table class="crm-info-panel">
            <tr><td class="label">{ts}Amount:{/ts}</td><td>{$recur.amount} {$recur.currency}</td></tr>
            <tr><td class="label">{ts}Frequency:{/ts}</td><td>every {$recur.frequency_interval} {$recur.frequency_unit}</td></tr>
            <tr><td class="label">{ts}Installments:{/ts}</td><td>{$recur.installments}</td></tr>
            <tr><td class="label">{ts}Start date:{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Creation date:{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
            {if $recur.modified_date}<tr><td class="label">{ts}Modified date:{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
            {if $recur.end_date}<tr><td class="label">{ts}End date:{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}End date:{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>{/if}
            {if $recur.processor_id}<tr><td class="label">{ts}Processor ID:{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
            <tr><td class="label">{ts}Transaction ID:{/ts}</td><td>{$recur.trxn_id}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID:{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
            <tr><td class="label">{ts}Cycle day:{/ts}</td><td>{$recur.cycle_day}</td></tr>
            {if $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next contribution:{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Failure count:{/ts}</td><td>{$recur.failure_count}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Failure retry date:{/ts}</td><td>{$recur.next_sched_contribution|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Auto renew:{/ts}</td><td>{if $recur.auto_renew}true{else}false{/if}</td></tr>
            {if $recur.payment_processor}<tr><td class="label">{ts}Payment processor:{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
          </table>
          <div class="crm-submit-buttons"><input type="button" name='cancel' value="{ts}Done{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=contribute'}';"/></div>
        </div>
    {/if}
{elseif $action eq 1 or $action eq 2} {* action is add or update *}
    <h3>
      {if $action eq 1}{ts}New Recurring Payment{/ts}{else}{ts}Edit Recurring Payment{/ts}{/if}
    </h3>
    <div class="crm-block crm-form-block crm-recurcontrib-form-block">
    <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      <table class="form-layout">
        <tr>
          <td class="label">{$form.amount.label}</td>
            <td>
              {$form.amount.html}
            </td>
        </tr>
        <tr>
          <td class="label">{$form.currency.label}</td>
            <td>
              {$form.currency.html}
            </td>
        </tr>
        <tr>
          <td class="label">{$form.frequency_interval.label}</td>
            <td>
              {$form.frequency_interval.html}<br />
              <span class="description">{ts}Number of time units for recurrence of payment.{/ts}</span>
            </td>
        </tr>
        <tr>
          <td class="label">{$form.frequency_unit.label}</td>
            <td>
              {$form.frequency_unit.html}<br />
              <span class="description">{ts}Time unit for recurrence of payment. For example, "month".{/ts}</span>
            </td>
        </tr>
        <tr>
          <td class="label">{$form.cycle_day.label}</td>
            <td>
              {$form.cycle_day.html}<br />
              <span class="description">{ts}Day in the period when the payment should be charged.{/ts}</span>
            </td>
        </tr>
      </table>

      <div class="crm-section recurcontrib-buttons-section no-label">
        <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
        <div class="clear"></div> 
      </div>
    </div>
    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}
{/if}

</div>
