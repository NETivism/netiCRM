<!-- mailingEditor files start -->
<!-- poshytip -->
<link rel="stylesheet" href="{$config->resourceBase}packages/poshytip/src/tip-yellowsimple/tip-yellowsimple.css">
<script type="text/javascript" src="{$config->resourceBase}packages/poshytip/src/jquery.poshytip.js"></script>

<!-- x-editable -->
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/jquery-editable/css/jquery-editable.css">
<script type="text/javascript" src="{$config->resourceBase}packages/x-editable/dist/jquery-editable/js/jquery-editable-poshytip.js"></script>
<link rel="stylesheet" href="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/quill.snow.css">
<script type="text/javascript" src="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/lib/quill.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/x-editable/dist/inputs-ext/quill/quill.js"></script>

<!-- pickr -->
<link rel="stylesheet" href="{$config->resourceBase}packages/pickr/dist/themes/nano.min.css"/>
<script src="{$config->resourceBase}packages/pickr/dist/pickr.min.js"></script>

<!-- Magnific Popup -->
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css">
<script src="{$config->resourceBase}packages/Magnific-Popup/dist/jquery.magnific-popup.js"></script>

<!-- mailingEditor -->
{include file="../packages/mailingEditor/templates/mailingEditorTemplates.tpl"}
<link rel="stylesheet" href="{$config->resourceBase}packages/mailingEditor/mailingEditor.css">
<script type="text/javascript" src="{$config->resourceBase}packages/mailingEditor/mailingEditor.js"></script>
{literal}
<script type="text/javascript">
window.nmEditor = {
	translation: {
		// Editor
		"Add Block Here": "{/literal}{ts}Add Block Here{/ts}{literal}",

		// Panels
		"Mailing Advanced Settings" : "{/literal}{ts}Mailing Advanced Settings{/ts}{literal}",
		"Templates" : "{/literal}{ts}Templates{/ts}{literal}",
		"Blocks" : "{/literal}{ts}Blocks{/ts}{literal}",
		"Setting" : "{/literal}{ts}Setting{/ts}{literal}",
		"Preview" : "{/literal}{ts}Preview{/ts}{literal}",
		"Normal" : "{/literal}{ts}Normal{/ts}{literal}",
		"Mobile Device" : "{/literal}{ts}Mobile Device{/ts}{literal}",

		// Panel: Templates
		"1 Column" : "{/literal}{ts}1 Column{/ts}{literal}",
		"1:2 Column" : "{/literal}{ts}1:2 Column{/ts}{literal}",
		"1 Column + Float" : "{/literal}{ts}1 Column + Float{/ts}{literal}",

		// Panel: Blocks
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
		"Background" : "{/literal}{ts}Background{/ts}{literal}",

		// Confirm
		"Because you have switched to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to save it?" : "{/literal}{ts}Because you have switched to 'Compose On-screen' mode, the content of the traditional editor will be replaced. Are you sure you want to save it?{/ts}{literal}"
	}
};
</script>
{/literal}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		let $oldEditorData = $("#html_message"),
				$newEditorData = $("#mailing_content_data"),
				oldEditorData = $oldEditorData.length ? $.trim($oldEditorData.val()) : "",
				newEditorData = $newEditorData.length ? $.trim($newEditorData.val()) : "";

		if (oldEditorData && !newEditorData) {
			// If the content of the old newsletter was edited by old editor previously, switch from default option 'Traditional Editor' to 'Compose On-screen'.
			$(".form-radio[name='upload_type'][value='2']").click();

			// Initialize the new editor after swtich option.
			$(".nme-container").nmEditor();
		}
		else {
			// If traditional editor has no content, initialize the new editor directly.
			$(".nme-container").nmEditor();
		}
	});
})(jQuery);
</script>
{/literal}
<!-- mailingEditor files end -->

<!-- mailingEditor HTML start -->
{* TODO: Replace DEMO with framework content with correct version (generated after loading json) *}
<div class="demo">
	<div class="nme-container">
 		<div class="nme-setting-panels">
			<div class="nme-setting-panels-inner">
				<div class="nme-setting-panels-header">
					<ul data-target-contents="nme-setting-panel" class="nme-setting-panels-tabs">
						<li><a class="is-active" data-target-id="nme-select-tpl" href="#nme-select-tpl">{ts}Templates{/ts}</a></li>
						<li><a data-target-id="nme-add-block" href="#nme-add-block">{ts}Blocks{/ts}</a></li>
						<li><a data-target-id="nme-global-setting" href="#nme-global-setting">{ts}Settings{/ts}</a></li>
					</ul>
				</div>
				<div class="nme-setting-panels-content">
					<div id="nme-select-tpl" class="nme-select-tpl nme-setting-panel is-active">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}Templates{/ts}</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-select-tpl-list nme-setting-item-list">
									<li><button class="nme-select-tpl-btn" type="button" data-name="col-1-full-width">{ts}1 Column{/ts}</button></li>
								</ul>
							</div>
						</div>
					</div>
					<div id="nme-add-block" class="nme-add-block nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">{ts}Blocks{/ts}</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-add-block-list nme-setting-item-list">
									<li><button class="nme-add-block-btn" type="button" data-type="title">{ts}Title{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="paragraph">{ts}Paragraph{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="image">{ts}Image{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="button">{ts}Button{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="rc-col-1">{ts}Rich Content: 1 Column{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="rc-col-2">{ts}Rich Content: 2 Column{/ts}</button></li>
									<li><button class="nme-add-block-btn" type="button" data-type="rc-float">{ts}Rich Content: Float{/ts}</button></li>
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
											<div class="nme-setting-field-label">{ts}Color{/ts}</div>
											<div class="nme-setting-field-content"><div id="nme-title-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-button-setting nme-setting-section" data-setting-group="button">
									<h3 class="nme-setting-section-title">{ts}Button{/ts}</h3>
									<div class="nme-setting-section-content">
										<div id="nme-button-setting-color" class="nme-setting-field" data-field-type="color">
											<div class="nme-setting-field-label">{ts}Color{/ts}</div>
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
				</div>
				<div class="nme-setting-panels-footer">
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
<!-- mailingEditor HTML end -->