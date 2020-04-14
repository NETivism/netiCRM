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
							<div class="nme-setting-panel-content"></div>
						</div>
					</div>
				</div>
				<div class="nme-setting-panels-footer">
					<div class="preview-mode switch-toggle-container">
						<label class="switch-toggle">
							<input class="switch-toggle-input" type="checkbox">
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