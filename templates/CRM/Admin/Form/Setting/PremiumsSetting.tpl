<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
  <h3>{ts}Settings{/ts} - {ts}Premium Settings{/ts}</h3>
  <div class="crm-section">
    <div class="label">{$form.premiumIRCreditCardDays.label}</div>
    <div class="content">
      {$form.premiumIRCreditCardDays.html} 
      <div class="description">{ts}Credit card transaction inventory replenishment days (1-3 days). Default: 1 day.{/ts}</div>
    </div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.premiumIRNonCreditCardDays.label}</div>
    <div class="content">
      {$form.premiumIRNonCreditCardDays.html} 
      <div class="description">{ts}Non-credit card inventory replenishment days after payment deadline (1-7 days). Default: 3 days.{/ts}</div>
    </div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.premiumIRConvenienceStoreDays.label}</div>
    <div class="content">
      {$form.premiumIRConvenienceStoreDays.html} 
      <div class="description">{ts}Convenience store barcode inventory replenishment days after payment deadline (3-7 days). Default: 3 days.{/ts}</div>
    </div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.premiumIRCheckStatuses.label}</div>
    <div class="content">
      {$form.premiumIRCheckStatuses.html} 
      <div class="description">{ts}Select which contribution statuses should be checked for inventory replenishment.{/ts}</div>
    </div>
    {include file="CRM/common/chosen.tpl" selector='select[name^=premiumIRCheckStatuses]'}
  </div>
  <div class="crm-section">
    <div class="label">{$form.premiumIRStatusChange.label}</div>
    <div class="content">
      {$form.premiumIRStatusChange.html} 
      <div class="description">{ts}Choose how to handle contribution status after inventory replenishment.{/ts}</div>
    </div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.premiumIRManualCancel.label}</div>
    <div class="content">
      {$form.premiumIRManualCancel.html} 
      <div class="description">{ts}Enable inventory replenishment for manual cancellations based on the specified statuses above.{/ts}</div>
    </div>
  </div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     