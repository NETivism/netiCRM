<div id="help">
    {ts}{/ts}
</div>
<div class="crm-block crm-form-block crm-security-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $admin}
    <legend></legend>
  <table class="form-layout">
    <tr class="crm-miscellaneous-form-block-trustedHosts">
      <td class="label">{$form.trustedHostsPatterns.label}</td>
      <td>
        {$form.trustedHostsPatterns.html}
        <div class="description">
          {ts}To enable the trusted host mechanism, you enable your allowable hosts in 'Trusted Host Settings' field. You can input as many values as needed, but only one value is allowed per row. You can use '*' as a wildcard character.{/ts}
        </div>
        <div class="description font-red">{ts}NOTE: Once this option is set, security rules will be applied to recognize access to your website only from domains that match those listed above, in order to provide page services. Please make sure that you have set the "trusted hosts" correctly to avoid blocking visitors due to your security settings.{/ts}</div>
      </td>
    </tr>
  </table>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>