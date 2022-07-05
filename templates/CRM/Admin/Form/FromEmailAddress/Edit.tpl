
{include file="CRM/common/WizardHeader.tpl"}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>		      
  <table class="form-layout-compressed">
    <tr class="crm-admin-from_email_address-form-block-from">
      <td class="label">{$form.from.label}</td>
      <td>
        {$form.from.html}
      </td>
    </tr>
    <tr class="crm-admin-from_email_address-form-block-email">
      <td class="label">{$form.email.label}</td>
      <td>
        {$form.email.html}
        {if $email_status}
        <div class="description">
          {ts}You can't change your email address after verified the ownership. However, you can create another from email address anytime.{/ts}
        </div>
        {else}
				{include file="CRM/common/defaultFrom.tpl"}
        <div class="description">
          {ts}Most of mail providers apply DMARC, that means if you use free email address as mail sender, the mail will be blocked by destination inbox.{/ts}<br>
          {ts 1=`$mail_providers`}Do not use free mail address as mail sender. (eg. %1){/ts}
        </div>
        {/if}
      </td>
    </tr>
    <tr class="crm-admin-from_email_address-form-block-desciption">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}<br />
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>