{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
  <h3>{ts}Procecss Info{/ts}</h3>
</div>

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">

{include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="成功捐款" tableHeader=$successedTableHeader tableContent=$successedContribution}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>