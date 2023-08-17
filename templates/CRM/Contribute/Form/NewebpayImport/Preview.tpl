{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="成功捐款" tableHeader=$tableHeader tableContent=$successedContribution}

    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="錯誤捐款" tableHeader=$tableHeader tableContent=$errorContribution}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>