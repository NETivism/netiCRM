<!-- sidePanel files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/sidePanel/sidePanel.css?v{$config->ver}">
{js src=packages/sidePanel/sidePanel.js group=999 weight=998 library=civicrm/civicrm-js-sidepanel}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		let neticrmSidePanelOpts = {};

		neticrmSidePanelOpts.debugMode = "{/literal}{$config->debug}{literal}";
    console.log("sidePanel tpl hello");
		// window.neticrmSidePanelInstance = $(".nme-container").neticrmSidePanel(".nme-container", neticrmSidePanelOpts);
	});
})(cj);
</script>
{/literal}
<!-- sidePanel files end -->
<!-- sidePanel HTML start -->

<!-- sidePanel HTML end -->
