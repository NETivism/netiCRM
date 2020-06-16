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
<link rel="stylesheet" href="{$config->resourceBase}packages/mailingEditor/mailingEditor.css">
<script type="text/javascript" src="{$config->resourceBase}packages/mailingEditor/mailingEditor.js"></script>
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		$(".nme-container").nmEditor({
			debugMode: true
		});
	});
})(jQuery);
</script>
{/literal}
{include file="../packages/mailingEditor/templates/mailingEditorTemplates.tpl"}
<!-- mailingEditor files end -->

<!-- mailingEditor HTML start -->
{* TODO: Replace DEMO with framework content with correct version (generated after loading json) *}
<div class="demo">
	<div class="nme-container">
 		<div class="nme-setting-panels">
			<div class="nme-setting-panels-inner">
				<div class="nme-setting-panels-header">
					<ul data-target-contents="nme-setting-panel" class="nme-setting-panels-tabs">
						<li><a class="is-active" data-target-id="nme-tpl-select" href="#nme-tpl-select">選擇範本</a></li>
						<li><a data-target-id="nme-add-block" href="#nme-add-block">新增區塊</a></li>
						<li><a data-target-id="nme-global-setting" href="#nme-global-setting">整體設定</a></li>
					</ul>
				</div>
				<div class="nme-setting-panels-content">
					<div id="nme-tpl-select" class="nme-tpl-select nme-setting-panel is-active">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">範本</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-tpl-list nme-block-list">
									<li>單欄+三欄</li>
									<li>單欄+三欄+左右左</li>
									<li>全單欄</li>
									<li>單欄+4則左圖文</li>
									<li>單欄+5則圖文</li>
								</ul>
							</div>
						</div>
					</div>
					<div id="nme-add-block" class="nme-add-block nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">區塊</h3>
							<div class="nme-setting-panel-content">
								<ul class="nme-block-list">
									<li>標題</li>
									<li>文字</li>
									<li>圖片</li>
									<li>按鈕</li>
									<li>單欄圖文</li>
									<li>兩欄圖文</li>
									<li>三欄圖文</li>
								</ul>
							</div>
						</div>
					</div>
					<div id="nme-global-setting" class="nme-global-setting nme-setting-panel">
						<div class="nme-setting-panel-inner">
							<h3 class="nme-setting-panel-title">整體設定</h3>
							<div class="nme-setting-panel-content">
							<div class="nme-page-setting nme-setting-section" data-setting-group="theme">
									<div class="nme-setting-section-content">
									<div id="nme-theme-setting" class="nme-setting-field">
											<div class="nme-setting-field-label">主題配色</div>
											<div class="nme-setting-field-content">
												<div class="crm-form-elem crm-form-select crm-form-select-single">
													<select id="nme-theme-setting-select" class="nme-setting-select form-select">
														<option value="default">預設</option>
														<option value="green">綠色森林</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="nme-page-setting nme-setting-section" data-setting-group="page">
									<h3 class="nme-setting-section-title">頁面</h3>
									<div class="nme-setting-section-content">
										<div id="nme-page-setting-bgcolor" class="nme-setting-field" data-field-type="background-color">
											<div class="nme-setting-field-label">背景色</div>
											<div class="nme-setting-field-content"><div id="nme-page-setting-bgcolor-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-title-setting nme-setting-section" data-setting-group="title">
									<h3 class="nme-setting-section-title">標題</h3>
									<div class="nme-setting-section-content">
									<div id="nme-title-setting-fontsize" class="nme-setting-field" data-field-type="font-size">
											<div class="nme-setting-field-label">文字大小</div>
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
											<div class="nme-setting-field-label">文字顏色</div>
											<div class="nme-setting-field-content"><div id="nme-title-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
									</div>
								</div>
								<div class="nme-button-setting nme-setting-section" data-setting-group="button">
									<h3 class="nme-setting-section-title">按鈕</h3>
									<div class="nme-setting-section-content">
										<div id="nme-button-setting-color" class="nme-setting-field" data-field-type="color">
											<div class="nme-setting-field-label">文字顏色</div>
											<div class="nme-setting-field-content"><div id="nme-button-setting-color-picker" class="nme-setting-picker"></div></div>
										</div>
										<div id="nme-button-setting-bgcolor" class="nme-setting-field" data-field-type="background-color">
											<div class="nme-setting-field-label">背景色</div>
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
						<div class="switch-toggle-label">預覽模式</div>
					</div>

				</div>
			</div> <!-- setting panels inner -->
			<div class="nme-setting-panels-trigger"><i class="zmdi zmdi-email"></i><div class="trigger-label">電子報進階設定</div></div>
		</div> <!-- setting panels -->
	</div>
</div>
<!-- mailingEditor HTML end -->