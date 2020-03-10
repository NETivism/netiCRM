{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 8 or $action eq 2}
  {include file="CRM/Batch/Form/Batch.tpl"}
{else}
  {include file="CRM/Batch/Form/Search.tpl"}

  {if $rows}
  <div id="ltype">
  {include file="CRM/common/jsortable.tpl hasPager=1}
  {strip}
  <table id="batch" class="crm-batch-selector">
    <thead>
    <tr>
      <th class="crm-batch-id">#</th>
      <th class="crm-batch-name">{ts}Label{/ts}</th>
      <th class="crm-batch-created_by">{ts}Created By{/ts}</th>
      <th class="crm-batch-created_date">{ts}Created Date{/ts}</th>
      <th class="crm-batch-modified_date">{ts}Modified Date{/ts}</th>
      <th class="crm-batch-type">{ts}Batch Type{/ts}</th>
      <th class="crm-batch-processed">{ts}Completed{/ts}/{ts}Total{/ts}</th>
      <th class="crm-batch-status">{ts}Batch Status{/ts}</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} {$row.class}">
      <td class="crm-batch-name">{$row.id}</td>
      <td class="crm-batch-name">{$row.label}</td>
      <td class="crm-batch-created_by">{$row.created_by}</td>
      <td class="crm-batch-created_date">{$row.created_date|crmDate}</td>
      <td class="crm-batch-modified_date">{$row.modified_date|crmDate}</td>
      <td class="crm-batch-type">{$row.batch_type}</td>
      <td class="crm-batch-processed">{$row.processed}</td>
      <td class="crm-batch-status">{$row.batch_status}</td>
      <td class="action">{$row.action}</td>
    </tr>
    {/foreach}
    </tbody>
  </table>
  {/strip}
  </div>
  {else}
  <div class="messages status">
    {ts}Sorry. No results found.{/ts}
  </div>
  {/if}{*end rows*}

{/if}{*end action*}