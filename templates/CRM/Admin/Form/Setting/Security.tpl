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
  <fieldset><legend>{ts}Content Security Policy{/ts}</legend>
    <div class="description">
    {ts}Default values will be supplied for these Content Security Policy when you access CiviCRM.{/ts}
    </div>
    <table class="form-layout">
      <tr class="crm-csp-form-block-csp">
        <td class="label">{$form.cspRules.label}</td>
        <td>{$form.cspRules.html|crmReplace:class:'huge40'}
          <a id="use-default-csp" href="#">{ts}Click here to replace the current input values with CSP default values.{/ts}</a>
        </td>
      </tr>
      <tr class="crm-csp-form-block-cspExcludePath">
        <td class="label">{$form.cspExcludePath.label}</td>
        <td>{$form.cspExcludePath.html|crmReplace:class:'huge40'}
          <span class="description">{ts}Specify pages by using their paths. Enter one path per line.{/ts}</span>
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
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
