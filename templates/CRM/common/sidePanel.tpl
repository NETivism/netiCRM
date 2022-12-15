<!-- sidePanel files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/sidePanel/sidePanel.css?v{$config->ver}">
{js src=packages/sidePanel/sidePanel.js group=999 weight=998 library=civicrm/civicrm-js-sidepanel}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		let neticrmSidePanelOpts = {};

		neticrmSidePanelOpts.debugMode = "{/literal}{$config->debug}{literal}";
    neticrmSidePanelOpts.src = "#nsp-test";
    //neticrmSidePanelOpts.src = "http://local.dev7.neticrm.tw/";
    //neticrmSidePanelOpts.type = "iframe";
    window.neticrmSidePanelInstance = $(".nsp-container").neticrmSidePanel(".nsp-container", neticrmSidePanelOpts);
	});
})(cj);
</script>
{/literal}
<!-- sidePanel files end -->
<!-- sidePanel HTML start -->
<div class="nsp-container">
  <div class="nsp-inner">
    <div class="nsp-content">
      <div class="inner"></div>
    </div>
  <div class="nsp-trigger" title="{ts}Open & Close Panel{/ts}" data-tooltip data-tooltip-placement="w"><i class="zmdi zmdi-settings"></i></div>
</div>
<!-- sidePanel HTML end -->

<!-- nsp inline test start -->
<div id="nsp-test" class="nsp-hide">
  <div class="nsp-test-content">
    <h3>Test Title</h3>
    <p>This HTML will put in nsp !!</p>
  </div>
</div>
<!-- nsp inline test end -->

