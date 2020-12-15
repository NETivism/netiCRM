{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-taiwanach-import">
  <h3>{ts}Procecss Info{/ts}</h3>
  <div>{ts}Process ID{/ts}: {$parseResult.process_id}</div>
  <div>{ts}Payment Instrument{/ts}: {ts}{$parseResult.payment_type}{/ts}</div>
  <div>{ts}Import Type{/ts}: {ts}{$parseResult.import_type}{/ts}</div>
</div>

<div class="crm-block crm-form-block crm-form-block-taiwanach-import">
<div id="new-group" class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div>
      {ts}Report{/ts}
    </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <div class="flex-general">
      {foreach from=$parseResult.counter item=count key=name}
        <span>{$name}: {$count}</span>
      {/foreach}
    </div>
    <table class="form-layout-compressed">
      <thead>
        <tr>
          {foreach from=$parseResult.columns item=columnHeader}
            <th>{$columnHeader}</th>
          {/foreach}
        </tr>
      </thead>
      <tbody>
      {foreach from=$processResult item=line}
        <tr>
          {foreach from=$parseResult.columns item=columnHeader key=column}
            {if $column|strstr:"amount"}
              <td>{$line.$column|crmMoney}</td>
            {elseif $column|strstr:"date"}
              <td>{$line.$column|crmDate}</td>
            {elseif $column == 'id'}
              {if $importType == 'verification'}
                <td><a href="{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=`$line.id`&cid=`$line.contact_id`" h=0 a=1 fe=1}" target="_blank">{$line.id}</a></td>
              {else}
                <td><a href="{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=`$line.id`&cid=`$line.contact_id`&action=view&context=recur&selectedChild=contribute" h=0 a=1 fe=1}" target="_blank">{$line.id}</a></td>
              {/if}
            {else}
              <td>{$line.$column}</td>
            {/if}
          {/foreach}
        </tr>
      {/foreach}
      </tbody>
    </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>