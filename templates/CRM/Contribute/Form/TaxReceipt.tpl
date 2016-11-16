<div class="crm-block crm-content-block crm-contribution-tax-invoice-form-block">

<h3>{ts}Tax Receipt Information{/ts}</h3>
  <table class="crm-info-panel">
  {foreach from=$taxReceiptFields key=fieldName item=fieldInfo}
    <tr>
      <td class="label">{$fieldInfo.label}</td>
      <td>{$fieldInfo.value_label}</td>
    </tr>
  {/foreach}
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
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
