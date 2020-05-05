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
        <th>{ts}Contribution ID{/ts}</th>
        <th>{ts}Transaction ID{/ts}</th>
        <th>{ts}Invoice ID{/ts}</th>
        <th>{ts}Payment Instrument{/ts}</th>
        <th>{ts}Amount{/ts}</th>
        <th>{ts}Type{/ts}</th>
        <th>{ts}Source{/ts}</th>
        <th>{ts}Created Date{/ts}</th>
        <th>{ts}Received{/ts}</th>
        <th>{ts}Status{/ts}</th>
      </thead>
      <tbody>
      {foreach from=$parseResult.lines item=line}
        <tr>
          <td>{$line.id}</td>
          <td>{$line.trxn_id}</td>
          <td>{$line.invoice_id}</td>
          <td>{$line.payment_instrument}</td>
          <td>{$line.total_amount|crmMoney}</td>
          <td>{$line.contribution_type}</td>
          <td>{$line.source}</td>
          <td>{$line.created_date|crmDate}</td>
          <td>{$line.receive_date|crmDate}</td>
          <td>{$line.contribution_status}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>