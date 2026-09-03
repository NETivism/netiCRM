{* handle enable/disable actions*}
{include file="CRM/common/enableDisable.tpl"}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
{if $mode == 'booster'}
<div class="crm-custom-search-description">
  <p>{ts}You should trying to invite them again when donors at the end of recurring contribution.{/ts}</p>
  <p>{ts}When you reach them, don't forget to present how much thanks you have received their donation. If you can send some project report to them, they may want to join you again after reading your needs.{/ts}</p>
</div>
{/if}
<div class="crm-accordion-wrapper crm-custom_search_form-accordion crm-accordion-{if !$rows}open{else}closed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
            {if $form.start_date_from}
                <tr class="crm-contact-custom-search-form-row-start_date">
                    <td class="label">{$form.start_date_from.label}</td>
                    <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date_from} <span>{$form.start_date_to.label}</span>
                        {include file="CRM/common/jcalendar.tpl" elementName=start_date_to}
                    </td>
                </tr>
            {/if}
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-form-row-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    {capture assign=is_date}{$element|substr:-5}{/capture}
                    {if $is_date eq '_date'}
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=$element}</td>
                    {else}
                        <td>{$form.$element.html}</td>
                    {/if}
                </tr>
            {/foreach}
        </table>
        {include file="CRM/common/chosen.tpl" selector="select#contribution_page_id, select#processor_id"}
        {if $recurAuditEnabled}
          <div class="crm-accordion-wrapper crm-recur-audit-accordion crm-accordion-{if $auditRangeComplete}open{else}closed{/if}">
            <div class="crm-accordion-header">
              <div class="zmdi crm-accordion-pointer"></div>
              {ts}Monthly Recurring Debit Audit{/ts}
            </div>
            <div class="crm-accordion-body">
              <table class="form-layout-compressed">
                <tr class="crm-contact-custom-search-form-row-audit_date">
                  <td class="label">{$form.audit_date_from.label}</td>
                  <td>
                    {include file="CRM/common/jcalendar.tpl" elementName=audit_date_from}
                    <span>{$form.audit_date_to.label}</span>
                    {include file="CRM/common/jcalendar.tpl" elementName=audit_date_to}
                    <div class="description">{ts}Audits can go back to the first day of the previous month. Select a future date to forecast scheduled debits.{/ts} {ts}Select the start date first. The end date can be at most one month after the start date.{/ts}</div>
                  </td>
                </tr>
                <tr class="crm-contact-custom-search-form-row-audit_status_id crm-audit-status-controls{if !$auditRangeComplete} hiddenElement{/if}">
                  <td class="label">{$form.audit_status_id.label}</td>
                  <td>
                    {$form.audit_status_id.html}
                    <div class="description">{ts}Results are matched by the status of contribution records within the audit date range.{/ts} {ts}If a recurring contribution has multiple contribution records within the audit date range, it is included in the results whenever any of them matches a checked status.{/ts}</div>
                  </td>
                </tr>
                <tr class="crm-contact-custom-search-form-row-audit_not_executed crm-audit-status-controls{if !$auditRangeComplete} hiddenElement{/if}">
                  <td class="label">{$form.audit_not_executed.label}</td>
                  <td>
                    {$form.audit_not_executed.html}
                    <div class="description">{ts}For payment processors that trigger debits on their own schedule, a debit that has already been charged but whose result has not been returned will still be shown as having no record on the agreed debit date.{/ts}</div>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        {/if}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
  {if isset($summary.search_criteria)}
    <div class="crm-recur-search-criteria" aria-labelledby="crm-recur-search-criteria-title">
      <div id="crm-recur-search-criteria-title"><strong>{$summary.search_criteria.label|escape}</strong></div>
      <ul>
        {foreach from=$summary.search_criteria.items item=criterion}
          <li><strong>{$criterion.label|escape}</strong>: {$criterion.value|escape}</li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if isset($summary.audit_contribution_status)}
    {if isset($summary.audit_criteria)}
      <div class="crm-recur-audit-criteria" aria-labelledby="crm-recur-audit-criteria-title">
        <div id="crm-recur-audit-criteria-title"><strong>{$summary.audit_criteria.label|escape}</strong></div>
        <ul>
          {foreach from=$summary.audit_criteria.items item=criterion}
            <li><strong>{$criterion.label|escape}</strong>: {$criterion.value|escape}</li>
          {/foreach}
        </ul>
      </div>
    {/if}
    <div class="crm-recur-audit-summary" aria-label="{ts}Recurring debit audit summary.{/ts}">
      {if isset($summary.audit_scheduled)}
        <section class="crm-recur-audit-summary-section crm-recur-audit-summary-scheduled" aria-labelledby="crm-recur-audit-summary-scheduled-title">
          <div class="crm-recur-audit-summary-title" id="crm-recur-audit-summary-scheduled-title">{$summary.audit_scheduled.label}</div>
          <div class="crm-recur-audit-summary-items">
            <div class="crm-recur-audit-summary-item">
              <strong class="crm-recur-audit-summary-value crm-recur-audit-summary-value--{$summary.audit_scheduled.status_class}">{$summary.audit_scheduled.value}</strong>
            </div>
          </div>
          <div class="crm-recur-audit-summary-note">{$summary.audit_scheduled.description}</div>
        </section>
      {/if}
      <section class="crm-recur-audit-summary-section crm-recur-audit-summary-contributions" aria-labelledby="crm-recur-audit-summary-contribution-title">
        <div class="crm-recur-audit-summary-title" id="crm-recur-audit-summary-contribution-title">{$summary.audit_contribution_status.label}</div>
        <div class="crm-recur-audit-summary-items">
          {foreach from=$summary.audit_contribution_status.items item=item}
            <div class="crm-recur-audit-summary-item">
              <span>{$item.label}</span>
              <strong class="crm-recur-audit-summary-value crm-recur-audit-summary-value--{$item.status_class}">{$item.value}</strong>
            </div>
          {/foreach}
        </div>
        <div class="crm-recur-audit-summary-note">{$summary.audit_contribution_status.description}</div>
      </section>
    </div>
  {/if}
  <div><label>{$summary.search_results.label}</label>: {$summary.search_results.value}</div>
{/if}

{if $rows}
	<div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
        {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">        
        {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
		</div>
        {* This section displays the rows along and includes the paging controls *}
	    <div class="crm-search-results">

        {include file="CRM/common/pager.tpl" location="top"}

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}
        
        {strip}
        <table class="selector" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <tr>
                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {if $sort->_response.$key}{$sort->_response.$key.link}{else}{$header.name}{/if}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                <th>&nbsp;</th>
                </tr>
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                    {assign var=cbName value=$row.checkbox}
                    <td>{$form.$cbName.html}</td>
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}
        </p>
    {* END Actions/Results section *}
    </div>
    </div>
{/if}

</div>
{/if}
{literal}
<script type="text/javascript">
(function($) {
  'use strict';

  $(function() {
    $().crmaccordions();

    var earliestAuditDate = new Date();
    earliestAuditDate = new Date(earliestAuditDate.getFullYear(), earliestAuditDate.getMonth() - 1, 1);
    $('#audit_date_from').datepicker('option', 'minDate', earliestAuditDate);

    var $auditDateFrom = $('#audit_date_from');
    var $auditDateTo = $('#audit_date_to');
    var $auditDateFields = $auditDateFrom.add($auditDateTo);
    var $startDateTo = $('#start_date_to');
    var $auditControls = $('.crm-audit-status-controls');
    var $auditNotExecuted = $('#audit_not_executed');
    var $auditNotExecutedInitialized = $('#audit_not_executed_initialized');

    var syncAuditControls = function() {
      var rangeComplete = $.trim($auditDateFrom.val()).length > 0 &&
        $.trim($auditDateTo.val()).length > 0;

      if (!rangeComplete) {
        $auditNotExecuted.prop('checked', false);
        $auditNotExecutedInitialized.val('0');
      }
      else if ($auditNotExecutedInitialized.val() !== '1') {
        $auditNotExecuted.prop('checked', true);
        $auditNotExecutedInitialized.val('1');
      }

      $auditControls.toggleClass('hiddenElement', !rangeComplete);
      $auditControls.find(':input')
        .prop('disabled', !rangeComplete)
        .attr('aria-disabled', rangeComplete ? 'false' : 'true');
    };

    // The audit end date is selectable only after the start date is set, and it
    // can be at most one month after the start date (8/5 => 9/4, 9/1 => 9/30).
    var getAuditDateToMax = function(fromDate) {
      var maxDate = new Date(fromDate.getFullYear(), fromDate.getMonth() + 1, fromDate.getDate());
      if (maxDate.getDate() !== fromDate.getDate()) {
        // The next month is shorter, use its last day.
        maxDate = new Date(fromDate.getFullYear(), fromDate.getMonth() + 2, 0);
      }
      else {
        maxDate.setDate(maxDate.getDate() - 1);
      }
      return maxDate;
    };

    var syncAuditDateToRange = function() {
      var fromDate = $.trim($auditDateFrom.val()).length > 0 ? $auditDateFrom.datepicker('getDate') : null;

      if (!fromDate) {
        if ($.trim($auditDateTo.val()).length > 0) {
          $auditDateTo.datepicker('setDate', null);
        }
        $auditDateTo.datepicker('option', {minDate: null, maxDate: null}).datepicker('disable');
        return;
      }

      var maxDate = getAuditDateToMax(fromDate);
      $auditDateTo.datepicker('enable')
        .datepicker('option', {minDate: fromDate, maxDate: maxDate});

      var toDate = $auditDateTo.datepicker('getDate');
      if (toDate && toDate < fromDate) {
        $auditDateTo.datepicker('setDate', fromDate);
      }
      else if (toDate && toDate > maxDate) {
        $auditDateTo.datepicker('setDate', maxDate);
      }
    };

    var syncRecurringStartDateTo = function() {
      var rangeComplete = $.trim($auditDateFrom.val()).length > 0 &&
        $.trim($auditDateTo.val()).length > 0;
      if (!rangeComplete || !$startDateTo.length) {
        return;
      }

      var auditDateTo = $auditDateTo.datepicker('getDate');
      if (auditDateTo && $startDateTo.val() !== $auditDateTo.val()) {
        $startDateTo.datepicker('setDate', auditDateTo).trigger('change');
      }
    };

    syncAuditDateToRange();
    syncAuditControls();
    syncRecurringStartDateTo();
    $auditDateFrom.on('change', syncAuditDateToRange);
    $auditDateFields.on('change input', syncAuditControls);
    $auditDateFields.on('change', syncRecurringStartDateTo);
    $('.crm-contact-custom-search-form-row-audit_date .crm-clear-link a').on('click', function() {
      window.setTimeout(function() {
        syncAuditDateToRange();
        syncAuditControls();
      }, 0);
    });
  });
})(cj);
</script>
{/literal}
