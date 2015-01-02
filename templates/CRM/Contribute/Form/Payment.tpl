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
  <div class="crm-section payment_processor-section">
    <div class="label">{$form.payment_processor.label}</div>
    <div class="content">{$form.payment_processor.html}</div>
    <div class="clear"></div>
  </div>
  <div id="billing-payment-block"></div>
  {include file="CRM/common/paymentBlock.tpl'}
  <div id="crm-submit-buttons" class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
<script type="text/javascript">
{literal}
cj( function() {
  cj("input[name=payment_processor]").click(function(){
    if(cj(this).val() == 0){
      cj("#billing-payment-block").html('<div class="crm-section payment-description"><div class="label"></div><div class="content">{/literal}{$pay_later_receipt|nl2br|regex_replace:"/[\r\n]/":""}{literal}</div><div class="clear"></div></div>');
    }
  });
  var processorTypeObj = cj('input[name="payment_processor"]');

  if ( processorTypeObj.attr('type') == 'hidden' ) {
    var processorTypeValue = processorTypeObj.val( );
  }
  else {
    var processorTypeValue = processorTypeObj.filter(':checked').val();
  }

  if ( processorTypeValue ) {
    buildPaymentBlock( processorTypeValue );
  }

  cj('input[name="payment_processor"]').change( function() {
    buildPaymentBlock( cj(this).val() );    
  });
});

{/literal}
</script>
{/if}{* end ppType *}
