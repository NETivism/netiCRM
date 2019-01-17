{if $action eq 1 or $action eq 2 or $action eq 4}
  {* action = add / update / view *}
  {include file="CRM/Coupon/Form/Coupon.tpl"}
{else}
  {* action = browse *}

	{if NOT ($action eq 1 or $action eq 2) }
    <form>
      <label>{ts}Coupon Code{/ts}: <input name="code" type="text" value="{$smarty.get.code}"></label>
      {if $clear_filter}
			<a href="{crmURL p='civicrm/admin/coupon' q="reset=1"}" class="button"><span><i class="zmdi zmdi-close"></i> {ts}Reset{/ts}</span></a>
      {/if}
    </form>
		<div class="action-link-button">
			<a href="{crmURL p='civicrm/admin/coupon' q="action=add&reset=1"}" id="newCoupon" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Coupon{/ts}</span></a>
		</div>
	{/if}
  {if $rows}
    {include file="CRM/common/jsortable.tpl hasPager=1}
    {strip}
    <table id="coupon" class="display crm-coupon-listing">
    <thead>
      <tr>
        <th id="coupon-id">#</th>
        <th id="coupon-code">{ts}Coupon Code{/ts}</th>
        <th id="coupon-start-date">{ts}Start Date{/ts}</th>
        <th id="coupon-ent-date">{ts}End Date{/ts}</th>
        <th id="coupon-type">{ts}Coupon Type{/ts}</th>
        <th>{ts}Discounted Fees{/ts}</th>
        <th>{ts}Minimum Amount{/ts}</th>
        <th>{ts}Used{/ts} / {ts}Max{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Used For{/ts}</th>
        <th>{ts}Enabled?{/ts}</th>
        <th></th>
      </tr>
    </thead>
    {foreach from=$rows item=row}
      <tr id="row_{$row.id}"class=" crm-coupon crm-coupon-{$row.id} {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
        <td class="coupon-id">{$row.id}</td>
        <td class="coupon-code">{$row.code}</td>
        <td class="coupon-start-date">{$row.start_date}</td>
        <td class="coupon-end-date">{$row.end_date}</td>
        <td class="coupon-type">{ts}{$row.coupon_type|ucfirst}{/ts}</td>
        <td class="coupon-discount">{if $row.coupon_type == 'percentage'}{$row.discount}%{else}${$row.discount}{/if}</td>
        <td class="coupon-minimum">{$row.minimal_amount}</td>
        <td class="coupon-count-max"><a href="{crmURL p="civicrm/admin/coupon/track" q="reset=1&coupon_id=`$row.id`"}" target="_blank">{$row.count_max}</a></td>
        <td class="coupon-description">{$row.description}</td>
        <td class="coupon-extends">{', '|implode:$row.used_for}</td>
        <td id="row_{$row.id}_status" class="crm-coupon-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
      </tr>
    {/foreach}
    </table>
    {/strip}

    {if NOT ($action eq 1 or $action eq 2) }
    <div class="action-link-button">
      <a href="{crmURL p='civicrm/admin/coupon' q="action=add&reset=1"}" id="newCoupon" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Coupon{/ts}</span></a>
    </div>
    {/if}
  {else}
    <div class="messages status">
      {ts}No coupon found.{/ts}
    </div>
  {/if}
{/if}

