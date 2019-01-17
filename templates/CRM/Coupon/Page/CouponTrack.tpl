  {if $rows}
    {include file="CRM/common/jsortable.tpl hasPager=1}
    <table id="coupon" class="display crm-coupon-listing">
    <thead>
      <tr>
        <th>{ts}Contact{/ts}</th>
        <th id="coupon-code">{ts}Coupon{/ts}</th>
        <th id="coupon-type">{ts}Coupon Type{/ts}</th>
        <th>{ts}Discounted Fees{/ts}</th>
        <th>{ts}Total Amount{/ts}</th>
      </tr>
    </thead>
    {foreach from=$rows item=row}
      <tr id="row_{$row.id}"class=" crm-coupon crm-coupon-{$row.id} {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
        <td class="coupon-contact"><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$row.contact_id`"}" target="_blank">{$row.sort_name}</a></td>
        <td class="coupon-code"><a href="{crmURL p="civicrm/admin/coupon" q="action=browse&code=`$row.code`"}">{$row.code} - {$row.description}</a></td>
        <td class="coupon-type">{ts}{$row.coupon_type|ucfirst}{/ts}</td>
        <td class="coupon-discount">{if $row.coupon_type == 'percentage'}{$row.discount}%{else}${$row.discount}{/if}</td>
        <td class="coupon-total_amount"><a href="{crmURL p="civicrm/contact/view/contribution" q="reset=1&id=`$row.contribution_id`&cid=`$row.contact_id`&action=view"}" target="_blank">{$row.total_amount|crmMoney}</a></td>
      </tr>
    {/foreach}
    </table>
  {else}
    <div class="messages status">
      {ts}No coupon found.{/ts}
    </div>
  {/if}
