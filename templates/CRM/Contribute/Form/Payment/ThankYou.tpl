
{if $action & 1024}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}
<div class="crm-block crm-payment-thankyou-form-block">
  {if $thankyou_text}
    {$thankyou_text}
  {/if}

  <div id="change-payment-detail" class="crm-section">
    <div class="header-dark">
      {ts}Payment Information{/ts}
    </div>
    <div>
      <div class="label"><label>{ts}Source{/ts}: </label></div>
      <div class="content">{$source}</div>
      <div class="clear"></div>
    </div>
    <div>
      <div class="label"><label>{ts}Transaction ID{/ts}: </label></div>
      <div class="content">{$trxn_id}</div>
      <div class="clear"></div>
    </div>
    <div>
      <div class="label"><label>{ts}Amount{/ts}: </label></div>
      <div class="content"><strong>{$amount|crmMoney} {if $amount_level } - {$amount_level} {/if}</strong></div>
      <div class="clear"></div>
    </div>
  </div>
</div>
