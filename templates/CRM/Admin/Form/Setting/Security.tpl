<div class="crm-block crm-form-block crm-security-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <fieldset>
    <legend>{ts}Trusted Host{/ts}</legend>
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
  </fieldset>
  <fieldset>
    <legend>{ts}Content Security Policy{/ts}</legend>
    <table class="form-layout">
      <tr class="crm-csp-form-block-csp">
        <td class="label">{$form.cspRules.label}</td>
        <td>
          {$form.cspRules.html|crmReplace:class:'huge40'}
          <div class="description">
            {ts}Content Security Policy will restrict embed media on your CRM system.{/ts}
            <a id="use-default-csp" href="#">{ts 1=CSP}Use default: %1{/ts}</a>
          </div>
        </td>
      </tr>
      <tr class="crm-csp-form-block-cspExcludePath">
        <td class="label">{$form.cspExcludePath.label}</td>
        <td>{$form.cspExcludePath.html|crmReplace:class:'huge40'}
          <span class="description">{ts}You can enter one path per line, and use "*" as a wildcard character.{/ts}</span>
        </td>
      </tr>
    </table>
    {literal}
    <script>
      cj('#use-default-csp').click(function(){
        var cspRules = textarea = document.getElementById('cspRules');
        var defaultCSP = {/literal}"{$defaultCSP}";
        {literal}
        cspRules.value = defaultCSP;
      });
    </script>
    {/literal}
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
