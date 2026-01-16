{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-content-block">
  <div class="help">
    <p>{ts 1=$productName}Stock transaction history for premium: <strong>%1</strong>{/ts}</p>
  </div>

  {if $rows}
    {include file="CRM/common/jsortable.tpl"}
    <table id="stock-log-table" class="display">
      <thead>
        <tr>
          <th id="sortable">{ts}Modified Date{/ts}</th>
          <th>{ts}Stock Change{/ts}</th>
          <th id="sortable">{ts}Contribution ID{/ts}</th>
          <th>{ts}Reason{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$rows item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td class="crm-stock-log-modified_date">{$row.modified_date}</td>
            <td class="crm-stock-log-stock_change">{$row.stock_change}</td>
            <td class="crm-stock-log-contribution_id">
              {if $row.contribution_id}
                <a href="{$row.contribution_url}" target="_blank">{$row.contribution_id}</a>
              {else}
                -
              {/if}
            </td>
            <td class="crm-stock-log-reason">{$row.reason}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <div class="messages status no-popup">
      {ts}No stock log records found for this premium product.{/ts}
    </div>
  {/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/contribute/managePremiums' q='reset=1'}" class="button">
      <span><i class="zmdi zmdi-arrow-left"></i> {ts}Back to Manage Premiums{/ts}</span>
    </a>
  </div>
</div>
