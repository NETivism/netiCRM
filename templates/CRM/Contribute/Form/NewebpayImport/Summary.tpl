{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
  <h3>{ts}Procecss Info{/ts}</h3>
</div>

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">

{include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$successedHeaderText tableHeader=$successedTableHeader tableContent=$successedContribution}

    {if $modifyStatusHeader}
      {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$modifyStatusBlockHeaderText tableHeader=$modifyStatusHeader tableContent=$modifyStatusContribution}
      <p>{ts 1=$downloadStatusUrl}You can click <a href="%1">here</a> to download the Excel result file for this batch of contributions.{/ts}</p>
    {/if}


  {if $errorTableHeader}
    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$errorBlockHeaderText tableHeader=$errorTableHeader tableContent=$errorContribution}

    <p>{ts 1=$downloadErrorUrl}You can click <a href="%1">here</a> to download the Excel result file for this batch of contributions.{/ts}
  {/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>