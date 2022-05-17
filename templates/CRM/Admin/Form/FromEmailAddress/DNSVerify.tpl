{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="spf">
    {if $spf_status}
    <div>
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=SPF 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    </div>
    {else}
    <div>
      <div>
        {capture assign=spf_doc}{docURL page="Configure SPF Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=SPF 2=$values.email}%1 for your email %2 is not verified.{/ts} {$spf_doc}
      </div>
    </div>
    {/if}
  </div>

  <!--
  <div class="dkim">
    {if $dkim_status}
    <div>
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=DKIM 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    </div>
    {else}
    <div>
      <div>
        {capture assign=dkim_doc}{docURL page="Configure DKIM Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=DKIM 2=$values.email}%1 for your email %2 is not verified.{/ts} {$dkim_doc}
      </div>
    </div>
    {/if}
  </div>
  -->
  <div class="description">
    <i class="zmdi zmdi-info-outline"></i>{ts}Your domain name records may need up to 48 hours to update base on what DNS provider you use.{/ts}<br>
    {ts}After update your records on your DNS provider, you are safe to leave this page and procceed this verify later.{/ts}
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>