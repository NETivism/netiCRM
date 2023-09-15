{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-form-block-newebpayImport-import">
    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="成功捐款" tableHeader=$successedTableHeader tableContent=$successedContribution}

    <p>上述捐款將更新以下資料：手續費、撥款日期。</p>

    {if $modifyStatusHeader}
      {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="將更新狀態" tableHeader=$modifyStatusHeader tableContent=$modifyStatusContribution}
    {/if}

    <p>上述捐款將更新以下欄位：手續費、撥款日期。狀態將會更改為已完成。不會寄出捐款完成通知信。<br/>可<a href="{$downloadStatusUrl}">按此下載</a>這批捐款的excel結果檔。</p>

    {include file="CRM/Contribute/Form/NewebpayImport/accordianTable.tpl" headerText="錯誤捐款" tableHeader=$errorTableHeader tableContent=$errorContribution}

    <p>上述捐款將不會有任何更動。可<a href="{$downloadErrorUrl}">按此下載</a>這批捐款的excel結果檔。</p>



  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>