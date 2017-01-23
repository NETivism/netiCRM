{if $smarty.get.snippet eq 2}{* print *}
  {include file="CRM/common/TaxReceipt.tpl"}
{elseif $smarty.get.snippet eq 3}{* pdf *}
  {include file="CRM/common/TaxReceipt.tpl"}
{else}{* normal html *}
<div class="crm-block crm-content-block crm-contribution-tax-invoice-form-block">
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
<h3>{ts}Tax Receipt Fields{/ts}</h3>
  <table class="crm-info-panel taxreceipt-fields">
  {foreach from=$taxReceiptFields key=fieldName item=fieldInfo}
    <tr>
      <td class="label">{$fieldInfo.label}</td>
      <td>{$fieldInfo.value_label}</td>
    </tr>
  {/foreach}
  </table>
{if $taxReceiptPrint}
<h3>{ts}Print Tax Receipt{/ts}</h3>
{/if}
<h3>{ts}Tax Receipt Information{/ts}</h3>
  <table class="crm-info-panel taxreceipt-info">
  {foreach from=$taxReceiptInfo key=fieldName item=fieldInfo}
    <tr>
      <td class="label">{$fieldName}</td>
      <td>{$fieldInfo}</td>
    </tr>
  {/foreach}
  </table>

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div><!-- crm-block -->
<script>{literal}
cj(document).ready(function($){
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
