{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <fieldset class="spf">
    <legend>{ts 1=SPF}Verify %1{/ts}</legend>
    <div>
    {if $spf_status}
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=SPF 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    {else}
      <div>
        {capture assign=spf_doc}{docURL page="Configure SPF Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=SPF 2=$values.email}%1 for your email %2 is not verified.{/ts} <span class="crm-submit-buttons">{$form._qf_DNSVerify_refresh.html}</span> {$spf_doc}
      </div>
    {/if}
    {if $spf_record}
      <div>
        {ts}Your current DNS record is listed below.{/ts}
        <pre>{$spf_record}</pre>
      </div>
    {/if}
    </div>
  </fieldset>

  <fieldset class="dkim">
    <legend>{ts 1=DKIM}Verify %1{/ts}</legend>
    <div>
    {if $dkim_status}
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=DKIM 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    {else}
      <div>
        {capture assign=dkim_doc}{docURL page="Configure DKIM Record" text="Learn how to config settings"}{/capture}
        <i class="zmdi zmdi-alert-triangle warning"></i>
        {ts 1=DKIM 2=$values.email}%1 for your email %2 is not verified.{/ts} <span class="crm-submit-buttons">{$form._qf_DNSVerify_refresh.html}</span> {$dkim_doc}
      </div>
    {/if}
    {if $dkim_record}
      <div>
        {ts}Your current DNS record is listed below.{/ts}
        <pre>{$dkim_record}</pre>
      </div>
    {/if}
    </div>
  </fieldset>

  {if !$spf_status or !$dkim_status}
  <div class="messages warning">
    {ts}Your domain name records may need up to 48 hours to update base on what DNS provider you use.{/ts}<br>
    {ts}After update your records on your DNS provider, you are safe to leave this page and procceed this verify later.{/ts}
  </div>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>