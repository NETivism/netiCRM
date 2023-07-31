{if !isset($triggerText)}
  {assign var="triggerText" value="Open & Close Panel"}
{/if}

{if !isset($triggerIcon)}
  {assign var="triggerIcon" value="zmdi-settings"}
{/if}

<!-- sidePanel files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/sidePanel/sidePanel.css?v{$config->ver}">
{js src=packages/sidePanel/sidePanel.js group=999 weight=998 library=civicrm/civicrm-js-sidepanel}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
		let neticrmSidePanelOpts = {};
    neticrmSidePanelOpts.type = "{/literal}{$type}{literal}";
    neticrmSidePanelOpts.src = "{/literal}{$src}{literal}";
    neticrmSidePanelOpts.selector = "{/literal}{$selector}{literal}";
    neticrmSidePanelOpts.contentSelector = "{/literal}{$contentSelector}{literal}";
    neticrmSidePanelOpts.headerSelector = "{/literal}{$headerSelector}{literal}";
    neticrmSidePanelOpts.footerSelector = "{/literal}{$footerSelector}{literal}";
    neticrmSidePanelOpts.containerClass = "{/literal}{$containerClass}{literal}";
    neticrmSidePanelOpts.width = "{/literal}{$width}{literal}";
    neticrmSidePanelOpts.opened = "{/literal}{$opened}{literal}";
    neticrmSidePanelOpts.fullscreen = "{/literal}{$fullscreen}{literal}";
		neticrmSidePanelOpts.debugMode = "{/literal}{$config->debug}{literal}";
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
  <div class="nsp-tools">
    <div class="inner">
      {if isset($fullscreen) && $fullscreen eq 'true'}
        <div class="nsp-fullscreen-trigger nsp-tool-btn" title="{ts}Expand{/ts}" data-tooltip data-tooltip-placement="w"><i class="zmdi zmdi-fullscreen"></i></div>
      {/if}
      {if $triggerIcon|strstr:"zmdi-"}
        <div class="nsp-trigger nsp-tool-btn" title="{ts}{$triggerText}{/ts}" data-tooltip data-tooltip-placement="w">
          <i class="zmdi {$triggerIcon}"></i>
        </div>
      {else}
        <div class="nsp-trigger nsp-tool-btn" title="{ts}{$triggerText}{/ts}" data-tooltip data-tooltip-placement="w">
          <img src="{$config->resourceBase}/{$triggerIcon}" >
        </div>
      {/if}
    </div>
  </div>
</div>
<!-- sidePanel HTML end -->
