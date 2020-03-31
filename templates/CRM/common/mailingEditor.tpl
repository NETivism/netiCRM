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
<!-- mailingEditor files end -->

<!-- mailingEditor HTML start -->
{* TODO: Replace DEMO with framework content with correct version (generated after loading json) *}
<div class="demo">
	<div class="nme-container">
		<div id="nme-blocks" class="nme-blocks">
			<div id="nme-block-1" data-id="nme-block-1" class="nme-block" data-type="image">
				<div class="nme-block-inner">
					<div class="nme-block-content">
						<div class="nme-image nme-item"><img src="https://unsplash.it/1360/600?image=972" alt=""></div>
					</div>	
					<div class="nme-block-control">
						<div class="nme-block-move">
							<span class="handle-drag handle-btn" data-type="drag"><i class="zmdi zmdi-arrows"></i></span>
							<span class="handle-prev handle-btn" data-type="prev"><i class="zmdi zmdi-long-arrow-up"></i></span>
							<span class="handle-next handle-btn" data-type="next"><i class="zmdi zmdi-long-arrow-down"></i></span>
						</div>
						<div class="nme-block-actions">
							<span class="handle-link handle-btn" data-type="link"><i class="zmdi zmdi-link"></i></span>
							<span class="handle-image handle-btn" data-type="image"><i class="zmdi zmdi-image"></i></span>
							<span class="handle-clone handle-btn" data-type="clone"><i class="zmdi zmdi-collection-plus"></i></span>
							<span class="handle-delete handle-btn" data-type="delete"><i class="zmdi zmdi-delete"></i></span>
						</div>
					</div>	
				</div>
			</div>
			<div id="nme-block-2" data-id="nme-block-2" class="nme-block" data-type="title">
				<div class="nme-block-inner">
					<div class="nme-block-content">
						<h3 class="nme-title nme-item"><div class='nme-editable' id='demo-nme-title' data-type='text'>請輸入標題</div></h3>
					</div>	
					<div class="nme-block-control">
						<div class="nme-block-move">
							<span class="handle-drag handle-btn" data-type="drag"><i class="zmdi zmdi-arrows"></i></span>
							<span class="handle-prev handle-btn" data-type="prev"><i class="zmdi zmdi-long-arrow-up"></i></span>
							<span class="handle-next handle-btn" data-type="next"><i class="zmdi zmdi-long-arrow-down"></i></span>
						</div>
						<div class="nme-block-actions">
							<span class="handle-style handle-btn"><i class="zmdi zmdi-format-color-fill"></i></span>
							<span class="handle-clone handle-btn"><i class="zmdi zmdi-collection-plus"></i></span>
							<span class="handle-delete handle-btn"><i class="zmdi zmdi-delete"></i></span>
						</div>
					</div>
				</div>
			</div>
			<div id="nme-block-3" data-id="nme-block-3" class="nme-block" data-type="text">
				<div class="nme-block-inner">
					<div class="nme-block-content">
						<div class="nme-text nme-item">
							<div class='nme-editable' id='demo-nme-text' data-type='xquill' data-placeholder='請輸入段落文字' data-title='Enter comments'>請輸入段落文字</div>
						</div>
					</div>	
					<div class="nme-block-control">
						<div class="nme-block-move">
							<span class="handle-drag handle-btn" data-type="drag"><i class="zmdi zmdi-arrows"></i></span>
							<span class="handle-prev handle-btn" data-type="prev"><i class="zmdi zmdi-long-arrow-up"></i></span>
							<span class="handle-next handle-btn" data-type="next"><i class="zmdi zmdi-long-arrow-down"></i></span>
						</div>
						<div class="nme-block-actions">
							<span class="handle-style handle-btn"><i class="zmdi zmdi-format-color-fill"></i></span>
							<span class="handle-clone handle-btn"><i class="zmdi zmdi-collection-plus"></i></span>
							<span class="handle-delete handle-btn"><i class="zmdi zmdi-delete"></i></span>
						</div>
					</div>
				</div>
			</div>
			<div id="nme-block-4" data-id="nme-block-4" class="nme-block" data-type="button">
				<div class="nme-block-inner">
					<div class="nme-block-content">
						<div class="nme-button nme-item"><div class='nme-editable btn' id='demo-nme-button' data-type='text'>按鈕</div></div>
					</div>	
					<div class="nme-block-control">
						<div class="nme-block-move">
							<span class="handle-drag handle-btn" data-type="drag"><i class="zmdi zmdi-arrows"></i></span>
							<span class="handle-prev handle-btn" data-type="prev"><i class="zmdi zmdi-long-arrow-up"></i></span>
							<span class="handle-next handle-btn" data-type="next"><i class="zmdi zmdi-long-arrow-down"></i></span>
						</div>
						<div class="nme-block-actions">
							<span class="handle-link handle-btn" data-type="link"><i class="zmdi zmdi-link"></i></span>
							<span class="handle-style handle-btn"><i class="zmdi zmdi-format-color-fill"></i></span>
							<span class="handle-clone handle-btn" data-type="clone"><i class="zmdi zmdi-collection-plus"></i></span>
							<span class="handle-delete handle-btn" data-type="delete"><i class="zmdi zmdi-delete"></i></span>
						</div>
					</div>	
				</div>
			</div>
		</div>
 		<div class="nme-setting-panels">
			<div class="nme-setting-panels-inner">
				<div class="nme-global-setting nme-setting-panel">
					<div class="nme-setting-panel-inner">
						<h3 class="nme-setting-panel-title">整體設定</h3>
						<div class="nme-setting-panel-content"></div>
					</div>
				</div>
				<div class="nme-block-setting nme-setting-panel">
					<div class="nme-setting-panel-inner">
						<h3 class="nme-setting-panel-title">區塊設定</h3>
						<div class="nme-setting-panel-content"></div>
					</div>
				</div>
				<div class="nme-add-block nme-setting-panel">
					<div class="nme-setting-panel-inner">
						<h3 class="nme-setting-panel-title">新增區塊</h3>
						<div class="nme-setting-panel-content">
							<ul class="nme-block-preview-list">
								<li>標題</li>
								<li>文字</li>
								<li>圖片</li>
							</ul>
						</div>
					</div>
				</div>
			<div class="nme-setting-panels-trigger"><i class="zmdi zmdi-plus"></i></div>
			</div>
		</div>
	</div>
</div>
<!-- mailingEditor HTML end -->