{if $action eq 1 or $action eq 2 or $action eq 4}
  {* action = add / update / view *}
  {include file="CRM/Coupon/Form/Coupon.tpl"}
{elseif $action eq 8}
  {include file="CRM/Coupon/Form/DeleteCoupon.tpl"}
{else}
  {* action = browse *}

	{if NOT ($action eq 1 or $action eq 2) }
    <div class="crm-block crm-form-block crm-form-block-search crm-event-search-form-block">
      <div class="crm-accordion-wrapper crm-coupon_search_form-accordion crm-accordion-open">
        <div class="crm-accordion-header crm-master-accordion-header">
          <div class="zmdi crm-accordion-pointer"></div> {ts}Edit Search Criteria{/ts}
        </div>
        <div class="crm-accordion-body">
          <form method="get">
            <label>{ts}Coupon Code{/ts}&nbsp;&nbsp;<input name="code" type="text" value="{$smarty.get.code}"></label>&nbsp;&nbsp;&nbsp;{ts}and{/ts}&nbsp;&nbsp;&nbsp;
            <label>{ts}Description{/ts}&nbsp;&nbsp;<input name="description" type="text" value="{$smarty.get.description}"></label>&nbsp;&nbsp;&nbsp;
            <button type="submit">{ts}Search{/ts}</button>
            {if $clear_filter}
            <a href="{crmURL p='civicrm/admin/coupon' q="reset=1"}" class="button"><span><i class="zmdi zmdi-close"></i> {ts}Reset{/ts}</span></a>
            {/if}
            <div class="description">{ts}Enter partial words of code to search (such as prefix words of code).{/ts}</div>
          </form>
        </div>
      </div>
    </div>
    {literal}
    <script type="text/javascript">
    cj(function() {
       cj().crmaccordions(); 
    });
    </script>
    {/literal}

		<div class="action-link-button">
			<a href="{crmURL p='civicrm/admin/coupon' q="action=add&reset=1&prefix=`$default_prefix`"}" id="newCoupon" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Coupon{/ts}</span></a>
      <a href="{crmURL p='civicrm/admin/coupon' q="action=export&code=`$smarty.get.code`"}" class="button"><span><i class="zmdi zmdi-download"></i>{ts}Export{/ts}</span></a>
		</div>
	{/if}
  {if $rows}
    {* handle enable/disable actions*}
    {include file="CRM/common/pager.tpl" location="top"}
    {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl}
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
        <th>{ts}Used for{/ts}</th>
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
        <td class="coupon-extends">
          {foreach from=$row.used_for key=entity_type item=entities}
            <div>
              {$usedForName.$entity_type}:&nbsp;
              {if $entity_type eq 'civicrm_event'}
                {assign var=events value=null}
                {foreach from=$entities key=id item=name}
                  {capture append=events}
                    <a href="{crmURL p='civicrm/event/manage/eventInfo' q="reset=1&action=update&id=`$id`" h=0 a=1 fe=1}" target="_blank">{$name}</a>
                  {/capture}
                {/foreach}
                {', '|implode:$events}
              {elseif $entity_type eq 'civicrm_price_field_value'}
                {', '|implode:$entities}
              {/if}
            </div>
          {/foreach}
        </td>
        <td id="row_{$row.id}_status" class="crm-coupon-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
      </tr>
    {/foreach}
    </table>
    {include file="CRM/common/pager.tpl" location="bottom"}
    {/strip}

    {if NOT ($action eq 1 or $action eq 2) }
    <div class="action-link-button">
      <a href="{crmURL p='civicrm/admin/coupon' q="action=add&reset=1&prefix=`$default_prefix`"}" id="newCoupon" class="button"><span><i class="zmdi zmdi-plus-circle-o"></i>{ts}Add Coupon{/ts}</span></a>
      <a href="{crmURL p='civicrm/admin/coupon' q="action=export&code=`$smarty.get.code`"}" class="button"><span><i class="zmdi zmdi-download"></i>{ts}Export{/ts}</span></a>
    </div>
    {/if}
  {else}
    <div class="messages status">
      {ts}No coupon found.{/ts}
    </div>
  {/if}
{/if}

