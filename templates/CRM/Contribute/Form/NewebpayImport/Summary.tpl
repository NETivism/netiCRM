{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
  <h3>{ts}Procecss Info{/ts}</h3>
</div>

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">

{include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="成功捐款" tableHeader=$successedTableHeader tableContent=$successedContribution}

{if $modifyStatusHeader}
  {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="將更新狀態" tableHeader=$modifyStatusHeader tableContent=$modifyStatusContribution}
  <p>>可<a href="{$downloadStatusUrl}">按此下載</a>這批捐款的excel結果檔。</p>
{/if}

{if $errorTableHeader}
  {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="錯誤捐款" tableHeader=$errorTableHeader tableContent=$errorContribution}
  <p>可<a href="{$downloadErrorUrl}">按此下載</a>這批捐款的excel結果檔。</p>
{/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>