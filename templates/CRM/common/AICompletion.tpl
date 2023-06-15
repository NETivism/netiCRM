<!-- AICompletion files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/AICompletion/AICompletion.css?v{$config->ver}">
{js src=packages/AICompletion/AICompletion.js group=999 weight=998 library=civicrm/civicrm-js-aicompletion}{/js}
<!-- AICompletion files end -->

<!-- Added global variable: AICompletion -->
{literal}
<script type="text/javascript">
window.AICompletion = {
  language: '{/literal}{$tsLocale}{literal}',
  translation: {
    'Copy': '{/literal}{ts}Copy{/ts}{literal}',
    'Submit': '{/literal}{ts}Submit{/ts}{literal}',
    'Try Again': '{/literal}{ts}Try Again{/ts}{literal}',
    'AI-generated Text Templates': '{/literal}{ts}AI-generated Text Templates{/ts}{literal}',
    'Saved Templates': '{/literal}{ts}Saved Templates{/ts}{literal}',
    'Community Recommendations': '{/literal}{ts}Community Recommendations{/ts}{literal}',
    'Warning! Applying this template will clear your current settings. Proceed with the application?': '{/literal}{ts}Warning! Applying this template will clear your current settings. Proceed with the application?{/ts}{literal}'
  }
};
</script>
{/literal}

<!-- AICompletion HTML start -->
<div class="netiaic-container">
  <div class="netiaic-inner">
    <div class="netiaic-content">
      <div class="inner">
        <div class="netiaic-chat">
          <div class="inner"></div>
        </div>
        <div class="netiaic-form-container">
          <div class="inner">
            <div class="netiaic-form-content">
              <ul class="netiaic-use-tpl">
                <li><a href="#" class="use-default-template">{ts}Use default template{/ts}</a></li>
                <li><a href="#" class="use-other-templates">{ts}Use other templates{/ts}</a></li>
              </ul>
              <div class="netiaic-prompt-role-section crm-section form-item">
                <div class="label"><label for="first_name">{ts}Role{/ts}</label></div>
                <div class="edit-value content">
                  <div class="crm-form-elem crm-form-select">
                    <select name="netiaic-prompt-role" class="netiaic-prompt-role-select form-select" data-placeholder="{ts}Please enter or select the role you want AI to represent (e.g., fundraiser).{/ts}"><option></option></select>
                  </div>
                </div>
              </div>
              <div class="netiaic-prompt-tone-section crm-section form-item">
                <div class="label"><label for="first_name">{ts}Tone style{/ts}</label></div>
                <div class="edit-value content">
                  <div class="crm-form-elem crm-form-select">
                    <select name="netiaic-prompt-tone" class="netiaic-prompt-tone-select form-select" data-placeholder="{ts}Please enter or select the desired writing style (e.g., casual).{/ts}"><option></option></select>
                  </div>
                </div>
              </div>
              <div class="netiaic-prompt-content-section crm-section form-item">
                <div class="crm-form-elem crm-form-textarea">
                  <textarea name="netiaic-prompt-content" placeholder="{ts}Please enter the fundraising copy you would like AI to generate.{/ts}" class="netiaic-prompt-content-textarea form-textarea"></textarea>
                  <div class="netiaic-prompt-content-command netiaic-command">
                    <div class="inner">
                      <ul class="netiaic-command-list">
                        <li data-name="org_info" class="netiaic-command-item">
                          <a class="get-org-info" href="#">{ts}Click to insert organization intro.{/ts}</a>
                          <a href="#" target="_blank">({ts}Edit{/ts}<i class="zmdi zmdi-edit"></i>)</a> <!-- TODO: Need to change to correct URL -->
                          <div class="netiaic-command-item-desc"> <!-- TODO: smarty var --> </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="netiaic-form-footer">
              <div class="netiaic-usage-info">
                {ts}Your usage limit is <span class="usage-max">{$maxUsage}</span> times, currently used <span class="usage-current">{$currentUsage}</span> times.{/ts}
              </div>
              <button type="button" class="netiaic-form-submit">
                <i class="zmdi zmdi-mail-send"></i>
                <span class="text">{ts}Submit{/ts}</span>
                <span class="loader"></span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- AICompletion HTML end -->

{literal}
<script type="text/javascript">
(function ($) {
  $(function() {
    // TODO: timeout is workaround
    setTimeout(function() {
      $('.netiaic-container:not(.is-initialized)').AICompletion();
    }, 3000);
  });
})(cj);
</script>
{/literal}