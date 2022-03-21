{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-taiwanach-import">
  <h3>{ts}Procecss Info{/ts}</h3>
  <div><label>{ts}Payment Instrument{/ts}</label>: {ts}{$parseResult.payment_type}{/ts}</div>
  <div><label>{ts}Import Type{/ts}</label>: {ts}{if $parseResult.import_type == 'verification'}Stamp Verification{else}{$parseResult.import_type}{/if}{/ts}</div>
  <div>
    {if $parseResult.process_id}
      <label>{ts}Process ID{/ts}</label>: {$parseResult.process_id}
    {/if}
  </div>
</div>

<div class="crm-block crm-form-block crm-form-block-taiwanach-import">
<div id="new-group" class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div>
      {ts}Preview{/ts}
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
      {foreach from=$parseResult.processed_data item=line}
        <tr>
          {foreach from=$parseResult.columns item=columnHeader key=column}
            {if $column|strstr:"amount"}
              <td>{$line.$column|crmMoney}</td>
            {elseif $column|strstr:"created_date"}
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

  <table class="form-layout-compressed">
  {if $form.custom_process_id}
    <tr class="crm-block crm-form-block crm-form-block-taiwanach-custom_process_id">
      <td class="label">{$form.custom_process_id.label}</td>
      <td class="content">
        {$form.custom_process_id.html}
        {if !$parseResult.process_id}
        <div class="description">
          {ts}Missing transaction file ID. Please copy and paste first line of ACH transaction file or enter the 6 digit number.{/ts}
        </div>
        {/if}
      </td>
    </tr>
  {/if}
    <tr class="crm-block crm-form-block crm-form-block-taiwanach-receive_date">
      <td class="label">{$form.receive_date.label}</td>
      <td class="content">{include file="CRM/common/jcalendar.tpl" elementName="receive_date"}</td>
    </tr>
  </table>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>