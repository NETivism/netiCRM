{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$successedHeaderText tableHeader=$successedTableHeader tableContent=$successedContribution}

    <p>{ts 1=$modifyFields}The above contribution will update the following information: %1{/ts}</p>

    {if $modifyStatusHeader}
      {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$modifyStatusBlockHeaderText tableHeader=$modifyStatusHeader tableContent=$modifyStatusContribution}
      <p>{ts 1=$modifyFields}The above contribution will update the following information: %1{/ts}<br>
      {ts}The status will be changed to 'Completed.' No contribution completion notification email will be sent.{/ts}<br/>
      {ts 1=$downloadStatusUrl}You can click <a href="%1">here</a> to download the Excel result file for this batch of contributions.{/ts}</p>
    {/if}


  {if $errorTableHeader}
    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText=$errorBlockHeaderText tableHeader=$errorTableHeader tableContent=$errorContribution}

    <p>{ts}No changes will be made to the above contributions.{/ts}{ts 1=$downloadErrorUrl}You can click <a href="%1">here</a> to download the Excel result file for this batch of contributions.{/ts}
  {/if}



  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>