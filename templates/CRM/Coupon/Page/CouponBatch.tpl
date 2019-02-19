{if $rows}
  {include file="CRM/common/jsortable.tpl hasPager=1}
  {strip}
  <table id="coupon" class="display crm-coupon-batch-listing">
  <thead>
    <tr>
      <th id="coupon-code">{ts}Coupon Code{/ts}</th>
      <th>{ts}Used{/ts} / {ts}Max{/ts}</th>
      <th>{ts}Description{/ts}</th>
      <th></th>
    </tr>
  </thead>
  {foreach from=$rows item=row}
    <tr class=" crm-coupon-batch {cycle values="odd-row,even-row"}">
      <td class="coupon-code"><a href="{crmURL p="civicrm/admin/coupon" q="code=`$row.batch_prefix`"}" target="_blank">{$row.batch_prefix}</a></td>
      <td class="coupon-count-max">{$row.used_max}</td>
      <td class="coupon-description">{$row.description}</td>
      <td class="coupon-download"><a href="{crmURL p='civicrm/admin/coupon' q="action=export&code=`$row.batch_prefix`"}"><i class="zmdi zmdi-download"></i>{ts}Export{/ts}</a></td>
    </tr>
  {/foreach}
  </table>
  {/strip}

{else}
  <div class="messages status">
    {ts}No coupon found.{/ts}
  </div>
{/if}

