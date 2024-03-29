<div id="new-group-{$headerText}" class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div>
      {ts}{$headerText}{/ts}
    </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <div class="flex-general">
      {foreach from=$result.counter item=count key=name}
        <span>{$name}: {$count}</span>
      {/foreach}
    </div>
    <table class="form-layout-compressed">
      <thead>
        <tr>
          {foreach from=$tableHeader item=columnHeader}
            <th>{$columnHeader}</th>
          {/foreach}
        </tr>
      </thead>
      <tbody>
      {foreach from=$tableContent item=line}
        <tr>
          {foreach from=$line item=value key=column}
          {if $column|in_array:$tableHeader}
              {if $column|strstr:"金額"}
                <td>{$value|crmMoney}</td>
              {elseif $column|strstr:"手續費"}
                <td>{$value|crmMoney}</td>
              {elseif $column|strstr:"撥款日期"}
                <td>{$value|crmDate}</td>
              {elseif $column == '商店訂單編號'}
                <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$line.id`&cid=`$line.contact_id`&action=view&context=recur&selectedChild=contribute" h=0 a=1 fe=1}" target="_blank">{$line.trxn_id}</a></td>
              {else}
                <td>{$value}</td>
              {/if}
            {/if}
          {/foreach}
        </tr>
      {/foreach}
      </tbody>
    </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
