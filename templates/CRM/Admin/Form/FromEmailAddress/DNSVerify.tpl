{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="spf">
    <h2>{ts 1=SPF}Verify %1{/ts}</h2>
    {if $spf_status}
    <div>
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=SPF 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    </div>
    {else}
    <div>
      <div>
        {capture assign=spf_doc}{docURL page="Configure SPF Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=SPF 2=$values.email}%1 for your email %2 is not verified.{/ts} <span class="crm-submit-buttons">{$form._qf_DNSVerify_refresh.html}</span> {$spf_doc}
      </div>
    </div>
    {/if}
  </div>

  <!--
  <div class="dkim">
    <h2>{ts 1=DKIM}Verify %1{/ts}</h2>
    {if $dkim_status}
    <div>
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=DKIM 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    </div>
    {else}
    <div>
      <div>
        {capture assign=dkim_doc}{docURL page="Configure DKIM Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=DKIM 2=$values.email}%1 for your email %2 is not verified.{/ts} <span class="crm-submit-buttons">{$form._qf_DNSVerify_refresh.html}</span> {$dkim_doc}
      </div>
    </div>
    {/if}
  </div>
  -->
  {if !$spf_status or !$dkim_status}
  <div class="messages warning">
    {ts}Your domain name records may need up to 48 hours to update base on what DNS provider you use.{/ts}<br>
    {ts}After update your records on your DNS provider, you are safe to leave this page and procceed this verify later.{/ts}
  </div>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>