<!-- mailingEditor files start -->
<!-- Added the translated string to the token list of quill -->
{literal}
<style>
{/literal}
{foreach from=$tokensArray key=k item=v}
{literal}
.ql-picker.ql-placeholder > span.ql-picker-options > span.ql-picker-item[data-value="{/literal}{$k}{literal}"]::before {
	content: "{/literal}{$v}{literal}";
}
{/literal}
{/foreach}
{literal}
</style>
{/literal}
{literal}

<!-- Added global variable: nmEditor -->
<script type="text/javascript">
window.nmEditor = {
	mailingID: "{/literal}{$mailingID}{literal}",
	qfKey: "{/literal}{$qfKey}{literal}",
	crmPath: "{/literal}{$config->resourceBase}{literal}",
	language: "{/literal}{$tsLocale}{literal}",
	translation: {
		// Golbal
		"OK" : "{/literal}{ts}OK{/ts}{literal}",
		"Cancel" : "{/literal}{ts}Cancel{/ts}{literal}",

		// Panels
		"Mailing Advanced Settings" : "{/literal}{ts}Mailing Advanced Settings{/ts}{literal}",
		"Templates" : "{/literal}{ts}Templates{/ts}{literal}",
		"Blocks" : "{/literal}{ts}Blocks{/ts}{literal}",
		"Settings" : "{/literal}{ts}Settings{/ts}{literal}",
		"Preview" : "{/literal}{ts}Preview{/ts}{literal}",
		"Normal" : "{/literal}{ts}Normal{/ts}{literal}",
		"Mobile Device" : "{/literal}{ts}Mobile Device{/ts}{literal}",

		// Panel: Templates
		"1 Column" : "{/literal}{ts}1 Column{/ts}{literal}",
		"1:2 Column" : "{/literal}{ts}1:2 Column{/ts}{literal}",
		"1 Column + Float" : "{/literal}{ts}1 Column + Float{/ts}{literal}",

		// Panel: Blocks
		"Header": "{/literal}{ts}Header{/ts}{literal}",
		"Footer": "{/literal}{ts}Footer{/ts}{literal}",
		"Title" : "{/literal}{ts}Title{/ts}{literal}",
		"Paragraph" : "{/literal}{ts}Paragraph{/ts}{literal}",
		"Image" : "{/literal}{ts}Image{/ts}{literal}",
		"Button" : "{/literal}{ts}Button{/ts}{literal}",
		"Rich Content: 1 Column" : "{/literal}{ts}Rich Content: 1 Column{/ts}{literal}",
		"Rich Content: 2 Column" : "{/literal}{ts}Rich Content: 2 Column{/ts}{literal}",
		"Rich Content: Float" : "{/literal}{ts}Rich Content: Float{/ts}{literal}",

		// Panel: Setting
		"Theme" : "{/literal}{ts}Theme{/ts}{literal}",
		"Page" : "{/literal}{ts}Page{/ts}{literal}",
		"Block" : "{/literal}{ts}Block{/ts}{literal}",
		"Size" : "{/literal}{ts}Size{/ts}{literal}",
		"Color" : "{/literal}{ts}Color{/ts}{literal}",
		"Text Color" : "{/literal}{ts}Text Color{/ts}{literal}",
		"Background" : "{/literal}{ts}Background{/ts}{literal}",

		// Block
		"Edit Link": "{/literal}{ts}Edit Link{/ts}{literal}",
		"Edit Image": "{/literal}{ts}Edit Image{/ts}{literal}",
		"Edit Background": "{/literal}{ts}Edit Background{/ts}{literal}",
		"Edit Background of Block": "{/literal}{ts}Edit Background of Block{/ts}{literal}",
		"Edit Background of Button": "{/literal}{ts}Edit Background of Button{/ts}{literal}",

		// Confirm
		"You are switching to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to switch to 'Compose On-screen' mode?" : "{/literal}{ts}You are switching to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to switch to 'Compose On-screen' mode?{/ts}{literal}",
		"Because you have switched to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to save it?" : "{/literal}{ts}Because you have switched to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to save it?{/ts}{literal}",
		"Are your sure to use template to replace your work? You will lose any customizations you have made." : "{/literal}{ts}Are your sure to use template to replace your work? You will lose any customizations you have made.{/ts}{literal}"
  },
  tokenTrigger: "#token2"
};
{/literal}
</script>
<!-- poshytip -->
<link rel="stylesheet" href="{$config->resourceBase}packages/poshytip/src/tip-yellowsimple/tip-yellowsimple.css?v{$config->ver}">
{js src=packages/poshytip/src/jquery.poshytip.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

<!-- x-editable -->
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/jquery-editable/css/jquery-editable.css?v{$config->ver}">
{js src=packages/x-editable/dist/jquery-editable/js/jquery-editable-poshytip.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/quill.snow.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/quill.bubble.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/mailingEditor/quill.override.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/placeholder/quill.placeholder.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/emoji/quill-emoji.min.css?v{$config->ver}">
{if $tsLocale == 'zh_TW'}
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/l10n.zh_TW.css?v{$config->ver}">
{/if}
{js src=packages/x-editable/dist/inputs-ext/quill/lib/quill.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}
{js src=packages/x-editable/dist/inputs-ext/quill/lib/placeholder/quill.placeholder.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}
{js src=packages/x-editable/dist/inputs-ext/quill/lib/emoji/quill-emoji.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}
{js src=packages/x-editable/dist/inputs-ext/quill/quill.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}


<!-- pickr -->
<link rel="stylesheet" href="{$config->resourceBase}packages/pickr/dist/themes/nano.min.css?v{$config->ver}"/>
{js src=packages/pickr/dist/pickr.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

<!-- Magnific Popup -->
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css?v{$config->ver}">
{js src=packages/Magnific-Popup/dist/jquery.magnific-popup.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

<!-- mailingEditor -->
<link rel="stylesheet" href="{$config->resourceBase}packages/mailingEditor/mailingEditor.css?v{$config->ver}">
{include file="../packages/mailingEditor/templates/mailingEditorTemplates.tpl"}
{literal}
<style>
	.nme-block.on-screen-center:after {
		content: "{/literal}{ts}Add Block Here{/ts}{literal}";
	}
</style>
{/literal}
<!-- mailingEditor files end -->

<!-- mailingEditor HTML start -->
{* TODO: Replace DEMO with framework content with correct version (generated after loading json) *}
<div class="demo">
	<div class="nme-container">
    <div>
 		<div class="nme-setting-panels">
			<div class="nme-setting-panels-inner">
				<div class="nme-setting-panels-header" id="nme-setting-panels-header">
					<ul data-target-contents="nme-setting-panel" class="nme-setting-panels-tabs">
						<li><a title="{ts}Switch Templates{/ts}" href="#nme-select-tpl" class="is-active" data-target-id="nme-select-tpl" data-tooltip>{ts}Templates{/ts}</a></li>
						<li><a title="{ts}Add Blocks{/ts}" href="#nme-add-block" data-target-id="nme-add-block" data-tooltip>{ts}Blocks{/ts}</a></li>
						<li><a title="{ts}Global Settings{/ts}" href="#nme-global-setting" data-target-id="nme-global-setting" data-tooltip>{ts}Settings{/ts}</a></li>
						{if $config->nextEnabled}<li><a title="{ts}AI Copywriter{/ts}" href="#nme-aicompletion" data-target-id="nme-aicompletion" data-tooltip>{ts}AI Copywriter{/ts}</a></li>{/if}
					</ul>
				</div>
				<div class="nme-setting-panels-content" id="nme-setting-panels-content">
					<div id="nme-select-tpl" class="nme-select-tpl nme-setting-panel is-active">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}Templates{/ts}</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-select-tpl-list nme-setting-item-list">
									<li><button class="nme-select-tpl-btn" type="button" title="{ts}Click to Switch Template{/ts}" data-name="col-1-full-width" data-tooltip>{ts}1 Column{/ts}</button></li>
									<li><button class="nme-select-tpl-btn" type="button" title="{ts}Click to Switch Template{/ts}" data-name="col-1-col-2" data-tooltip>{ts}1:2 Column{/ts}</button></li>
									<li><button class="nme-select-tpl-btn" type="button" title="{ts}Click to Switch Template{/ts}" data-name="col-1-float" data-tooltip>{ts}1 Column + Float{/ts}</button></li>
								</ul>
							</div>
						</div>
					</div>
					<div id="nme-add-block" class="nme-add-block nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}Blocks{/ts}</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-add-block-list nme-setting-item-list">
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="title" data-tooltip>{ts}Title{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="paragraph" data-tooltip>{ts}Paragraph{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="image" data-tooltip>{ts}Image{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="button" data-tooltip>{ts}Button{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="rc-col-1" data-tooltip>{ts}Rich Content: 1 Column{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="rc-col-2" data-tooltip>{ts}Rich Content: 2 Column{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" title="{ts}Click to Add Block{/ts}" data-type="rc-float" data-tooltip>{ts}Rich Content: Float{/ts}</button></li>
								</ul>
							</div>
						</div>
					</div>
					<div id="nme-global-setting" class="nme-global-setting nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}Settings{/ts}</h3>
							<div class="nme-setting-panel-content">
							<div class="nme-page-setting nme-setting-section" data-setting-group="theme">
									<div class="nme-setting-section-content">
									<div id="nme-theme-setting" class="nme-setting-field">
											<div class="nme-setting-field-label">{ts}Theme{/ts}</div>
											<div class="nme-setting-field-content">
												<div class="nme-theme-setting-items"></div>
											</div>
										</div>
									</div>
								</div>
								<div class="nme-page-setting nme-setting-section" data-setting-group="page">
									<h3 class="nme-setting-section-title">{ts}Page{/ts}</h3>
									<div class="nme-setting-section-content">
										<div id="nme-page-setting-bgcolor" class="nme-setting-field" data-field-type="background-color">
											<div class="nme-setting-field-label">{ts}Background{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-page-setting-bgcolor-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-block-setting nme-setting-section" data-setting-group="block">
									<h3 class="nme-setting-section-title">{ts}Block{/ts}</h3>
									<div class="nme-setting-section-content">
										<div id="nme-block-setting-bgcolor" class="nme-setting-field" data-field-type="background-color">
											<div class="nme-setting-field-label">{ts}Background{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-block-setting-bgcolor-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-title-setting nme-setting-section" data-setting-group="title">
									<h3 class="nme-setting-section-title">{ts}Title{/ts}</h3>
									<div class="nme-setting-section-content">
									<div id="nme-title-setting-fontsize" class="nme-setting-field" data-field-type="font-size">
											<div class="nme-setting-field-label">{ts}Size{/ts}</div>
											<div class="nme-setting-field-content">
												<div class="crm-form-elem crm-form-select crm-form-select-single">
													<select id="nme-title-setting-fontsize-select" class="nme-setting-select form-select">
														<option value="16px">16px</option>
														<option value="18px">18px</option>
														<option value="20px">20px</option>
														<option value="24px">24px</option>
														<option value="26px">26px</option>
														<option value="28px">28px</option>
														<option value="30px">30px</option>
														<option value="32px">32px</option>
														<option value="36px">36px</option>
														<option value="40px">40px</option>
														<option value="48px">48px</option>
														<option value="60px">60px</option>
													</select>
												</div>
											</div>
										</div>
										<div id="nme-title-setting-color" class="nme-setting-field" data-field-type="color">
											<div class="nme-setting-field-label">{ts}Text Color{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-title-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-paragraph-setting nme-setting-section" data-setting-group="paragraph">
									<h3 class="nme-setting-section-title">{ts}Paragraph{/ts}</h3>
									<div class="nme-setting-section-content">
										<div id="nme-paragraph-setting-color" class="nme-setting-field" data-field-type="color">
											<div class="nme-setting-field-label">{ts}Text Color{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-paragraph-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-button-setting nme-setting-section" data-setting-group="button">
									<h3 class="nme-setting-section-title">{ts}Button{/ts}</h3>
									<div class="nme-setting-section-content">
										<div id="nme-button-setting-color" class="nme-setting-field" data-field-type="color">
											<div class="nme-setting-field-label">{ts}Text Color{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-button-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
										<div id="nme-button-setting-bgcolor" class="nme-setting-field" data-field-type="background-color">
											<div class="nme-setting-field-label">{ts}Background{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-button-setting-bgcolor-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
          {if $config->nextEnabled}
					<div id="nme-aicompletion" class="nme-aicompletion nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}AI Copywriter{/ts}</h3>
							<div class="nme-setting-panel-content">
                {capture assign=component_locale}{ts}Mailing{/ts}{/capture}
                {include file="CRM/AI/AICompletion.tpl" component=$component_locale}
							</div>
						</div>
					</div>
          {/if}
				</div>
				<div class="nme-setting-panels-footer" id="nme-setting-panels-footer">
					<div class="preview-mode switch-toggle-container">
						<label class="switch-toggle">
							<input class="switch-toggle-input nme-preview-mode-switch" type="checkbox" value="1">
							<span class="switch-toggle-slider"></span>
						</label>
						<div class="switch-toggle-label">{ts}Preview{/ts}</div>
					</div>

				</div>
			</div> <!-- setting panels inner -->
			<div class="nme-setting-panels-trigger" title="{ts}Mailing Advanced Settings{/ts}" data-tooltip data-tooltip-placement="w"><i class="zmdi zmdi-settings"></i></div>
		</div> <!-- setting panels -->
    </div>
	</div>
</div>
<!-- mailingEditor HTML end -->

{include file="CRM/common/sidePanel.tpl" type="inline" headerSelector="#nme-setting-panels-header" contentSelector="#nme-setting-panels-content" footerSelector="#nme-setting-panels-footer" containerClass="nme-setting-panels" opened="true" userPreference="true" triggerText="Mailing Advanced Settings" width="500px" fullscreen="true"}
{js src=packages/mailingEditor/mailingEditor.js group=999 weight=998 library=civicrm/civicrm-js-mailingeditor}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		let $oldEditorData = $("#html_message"),
				$newEditorData = $("#body_json"),
				oldEditorData = $oldEditorData.length ? $.trim($oldEditorData.val()) : "",
				newEditorData = $newEditorData.length ? $.trim($newEditorData.val()) : "",
				nmEditorOpts = {};

		nmEditorOpts.debugMode = "{/literal}{$config->debug}{literal}";
		window.nmEditorInstance = $(".nme-container").nmEditor(".nme-container", nmEditorOpts);
	});
})(cj);
</script>
{/literal}
