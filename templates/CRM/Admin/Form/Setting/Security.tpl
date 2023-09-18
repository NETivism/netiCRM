<div class="crm-block crm-form-block crm-security-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $form.decryptExcelOption}
  <fieldset>
    <legend>{ts}Export excel file encryption settings{/ts}</legend>
    <table class="form-layout">
        <tr class="crm-miscellaneous-form-block-decryptExcelOption">
            <td class="label">{$form.decryptExcelOption.label}</td>
            <td>{$form.decryptExcelOption.html}</td>
        </tr>
        <tr class="crm-miscellaneous-form-block-decryptExcelPwd">
            <td class="label">{$form.decryptExcelPwd.label}</td>
            <td>{$form.decryptExcelPwd.html}</td>
        </tr>
    </table>
  </fieldset>
  {/if}
  <fieldset>
    <legend>{ts}Trusted Host{/ts}</legend>
    <table class="form-layout">
      <tr class="crm-miscellaneous-form-block-trustedHosts">
        <td class="label">{$form.trustedHostsPatterns.label}</td>
        <td>
          <div class="description">
            <a id="use-default-host" href="#">{ts 1=$current_host}Use default: %1{/ts}</a>
          </div>
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
          <div class="description">
            <a id="use-default-csp" href="#">{ts 1=CSP}Use default: %1{/ts}</a>
          </div>
          {$form.cspRules.html|crmReplace:class:'huge40'}
          <div class="description">
            {ts}Content Security Policy will restrict embed media on your CRM system.{/ts}
          </div>
        </td>
      </tr>
      <!--
      <tr class="crm-csp-form-block-cspExcludePath">
        <td class="label">{$form.cspExcludePath.label}</td>
        <td>{$form.cspExcludePath.html|crmReplace:class:'huge40'}
          <span class="description">{ts}You can enter one path per line, and use "*" as a wildcard character.{/ts}</span>
        </td>
      </tr>
      -->
    </table>
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script>{literal}
cj(document).ready(function($){
  // excel
  var decryptSelectExcelOption = ".crm-miscellaneous-form-block-decryptExcelOption input[type=radio]:checked";
  var decryptExcelOption = ".crm-miscellaneous-form-block-decryptExcelOption input[type=radio]";
  var decryptExcelPwd = ".crm-miscellaneous-form-block-decryptExcelPwd";
  if ($(decryptSelectExcelOption).val() != 2) {
    $(decryptExcelPwd).hide();
    $("#decryptExcelPwd").prop('required', false);
  }
  else {
    $(decryptExcelPwd).show();
    $("#decryptExcelPwd").prop('required', true);
  }
  $(decryptExcelOption).click( function() {
    if ( $(this).val() == "2" ) {
      $(decryptExcelPwd).show();
      $("#decryptExcelPwd").prop('required', true);
    } else {
      $(decryptExcelPwd).hide();
      $("#decryptExcelPwd").prop('required', false);
    }
  });

  // csp
  $('#use-default-csp').click(function(){
    var defaultCSP = "{/literal}{$defaultCSP}{literal}";
    $('#cspRules').val(defaultCSP);
  });
  // host
  $('#use-default-host').click(function(){
    let host = $('#trustedHostsPatterns').data('host');
    $('#trustedHostsPatterns').val(host);
  });

  // lock
  $("#use-default-csp, #use-default-host").hide();
  $('#cspRules, #trustedHostsPatterns').attr("readonly", true);
  $('#cspRules')[0].style.setProperty("cursor", "pointer","important");
  $('#trustedHostsPatterns')[0].style.setProperty("cursor", "pointer","important");
  $('#cspRules, #trustedHostsPatterns').click(function(){
    if ($(this).prop("readonly")) {
      let confirmed = confirm('{/literal}{ts}To ensure security, it is recommended that you submit a request through the customer service system before changing this field. If you are sure you want to modify this setting, please click Confirm button.{/ts}{literal}');
      if (confirmed) {
        $(this).prop("readonly", false);
        $(this).removeAttr("style");
        if ($(this).attr('id') == 'cspRules' && $(this).val() == "") {
          $("#use-default-csp").show();
        }
        if ($(this).attr('id') == 'trustedHostsPatterns' && $(this).val() == "") {
          $("#use-default-host").show();
        }
      }
    }
  });
});
{/literal}</script>