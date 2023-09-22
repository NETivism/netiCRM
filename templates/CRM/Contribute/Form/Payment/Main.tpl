{if $ppType}
  {include file="CRM/Core/BillingBlock.tpl"}
  {if $paymentProcessor.description}
    <div class="crm-section payment-description">
        <div class="label"></div>
        <div class="content">
            {$paymentProcessor.description}
        </div>
        <div class="clear"></div>
    </div>
  {/if}
{else}
  {if $action & 1024}
      {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
  {/if}
<div class="crm-block crm-payment-main-form-block">
  <div class="crm-section payment_processor-section">
    <div class="label">{$form.payment_processor.label}</div>
    <div class="content">{$form.payment_processor.html}</div>
    <div class="clear"></div>
  </div>
  <div id="billing-payment-block"></div>
  {include file="CRM/common/paymentBlock.tpl'}
</div>
  <div id="crm-submit-buttons" class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {literal}
  <script>
    cj("input[name=payment_processor]").click(function(){
      if(cj(this).val() == 0){
        cj("#billing-payment-block").html('<div class="crm-section payment-description"><div class="label"></div><div class="content">{/literal}{$pay_later_receipt|nl2br|regex_replace:"/[\r\n]/":""}{literal}</div><div class="clear"></div></div>');
      }
    });
  </script>
  {/literal}
{/if}
{* end ppType *}
