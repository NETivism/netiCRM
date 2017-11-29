{if $smarty.get.snippet eq 2}{* print *}
  {include file="CRM/common/TaxReceipt.tpl"}
{elseif $smarty.get.snippet eq 3}{* pdf *}
  {include file="CRM/common/TaxReceipt.tpl"}
{else}{* normal html *}
<div class="crm-block crm-content-block crm-contribution-tax-invoice-form-block">
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{if $taxReceiptPrint}
<div>{$taxReceiptPrint}</div>
{/if}
{if $taxReceiptInfo}
<div class="crm-accordion-wrapper crm-accordion-open">
  <div class="crm-accordion-header"> <span class="zmdi crm-accordion-pointer"></span> {ts}Tax Receipt Information{/ts}</div>
  <div class="crm-accordion-body">
    <table class="crm-info-panel taxreceipt-info">
    {foreach from=$taxReceiptInfo key=fieldName item=fieldInfo}
      <tr>
        <td class="label">{$fieldName}</td>
        <td>{$fieldInfo}</td>
      </tr>
    {/foreach}
    </table>
  </div>
</div>
{/if}
<div class="crm-accordion-wrapper crm-accordion-open">
  <div class="crm-accordion-header"> <span class="zmdi crm-accordion-pointer"></span> {ts}Tax Receipt Fields{/ts}</div>
  <div class="crm-accordion-body">
  <table class="crm-info-panel taxreceipt-fields">
    <tr>
      <td class="label">{ts}Transaction ID{/ts}</td>
      <td>{$trxn_id}</td>
    </tr>
  {foreach from=$taxReceiptFields key=fieldName item=fieldInfo}
    <tr>
      <td class="label">{$fieldInfo.label}</td>
      <td>{$fieldInfo.value_label}</td>
    </tr>
  {/foreach}
  </table>
  </div>
</div>
{if $form.tax_receipt_paper.html}
<div class="crm-section {$form.tax_receipt_paper.name}-section">
  <div class="label">{$form.tax_receipt_paper.label}</div>
  <div class="content">
    {$form.tax_receipt_paper.html}
    <div class="description">{ts}Use paper receipt. This will cancel user selection even they choose donate receipt or electronic receipt. User need keep receipt on their own and system will not email any further notification.{/ts}</div>
  </div>
</div>
{/if}

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div><!-- crm-block -->
<script>{literal}
cj(document).ready(function($){
  $().crmaccordions();
  $('input[name=_qf_TaxReceipt_next]').click(function(e){
    var confirmed = confirm("{/literal}{ts}Once create tax receipt, you can't withdraw your receipt.{/ts}{literal}");
    if (!confirmed) {
      e.preventDefault();
      return false;
    }
  });
});
{/literal}</script>
{/if}{* print or not *}
