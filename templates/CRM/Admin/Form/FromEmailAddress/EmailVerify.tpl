{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div>
    {if $email_status}
    <div>
      <i class="zmdi zmdi-shield-check ok"></i> {ts 1=$values.from 2=$values.email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    </div>
    {else}
    <div>
      <div>
        <i class="zmdi zmdi-alert-triangle warning"></i> {ts 1=$values.email}Your email address '%1' is not verified.{/ts} <span class="crm-submit-buttons">{$form._qf_EmailVerify_refresh.html}</span>
      </div>
      <div class="description">{ts}In order to verify you are the owner of this email address. Please click confirmation link we sent of your email.{/ts}</div>
    </div>
    {/if}
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>