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
        {ts 1=$values.email}SPF for your email %1 is not verified.{/ts} {$spf_doc}
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
        {ts 1=$values.email}DKIM for your email %1 is not verified.{/ts} {$dkim_doc}
      </div>
    </div>
    {/if}
  </div>
  -->
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>