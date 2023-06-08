<!-- AICompletion files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/AICompletion/AICompletion.css?v{$config->ver}">
{js src=packages/AICompletion/AICompletion.js group=999 weight=998 library=civicrm/civicrm-js-aicompletion}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
    setTimeout(function() {
      $(".netiaic-container:not(.is-initialized)").AICompletion();
    }, 3000);
	});
})(cj);
</script>
{/literal}
<!-- AICompletion files end -->
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
                <li><a href="#" class="use-default-template">使用預設範本</a></li>
                <li><a href="#" class="use-other-templates">使用其他範本</a></li>
              </ul>
              <div class="netiaic-prompt-role-section crm-section form-item">
                <div class="label"><label for="first_name">文案角色</label></div>
                <div class="edit-value content">
                  <div class="crm-form-elem crm-form-select">
                    <select name="netiaic-prompt-role" class="netiaic-prompt-role-select form-select" data-placeholder="請輸入或選擇希望AI代表的角色（ex. 募款人員）"><option></option></select>
                  </div>
                </div>
              </div>
              <div class="netiaic-prompt-tone-section crm-section form-item">
                <div class="label"><label for="first_name">語氣風格</label></div>
                <div class="edit-value content">
                  <div class="crm-form-elem crm-form-select">
                    <select name="netiaic-prompt-tone" class="netiaic-prompt-tone-select form-select" data-placeholder="請輸入或選擇希望的文案風格（ex. 輕鬆的）"><option></option></select>
                  </div>
                </div>
              </div>
              <div class="netiaic-prompt-content-section crm-section form-item">
                <div class="crm-form-elem crm-form-textarea">
                  <textarea name="netiaic-prompt-content" placeholder="請輸入想請AI生成的募款文案" class="netiaic-prompt-content-textarea form-textarea"></textarea>
                  <div class="netiaic-prompt-content-command netiaic-command">
                    <ul class="netiaic-command-list">
                      <li data-name="org_info" class="netiaic-command-item">
                        <a class="get-org-info" href="#">點選以帶入組織簡介</a>
                        <a href="#">（編輯簡介<i class="zmdi zmdi-edit"></i>）</a>
                        <div class="netiaic-command-item-desc"></div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="netiaic-form-footer">
              <div class="netiaic-usage-info">您的使用額度為<span class="usage-max">10</span>次，目前已使用<span class="usage-used">0</span>次。</div>
              <button class="netiaic-form-submit">{ts}Submit{/ts}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- AICompletion HTML end -->