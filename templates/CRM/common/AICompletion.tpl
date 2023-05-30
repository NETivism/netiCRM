<!-- AICompletion files start -->
<link rel="stylesheet" href="{$config->resourceBase}packages/AICompletion/AICompletion.css?v{$config->ver}">
{js src=packages/AICompletion/AICompletion.js group=999 weight=998 library=civicrm/civicrm-js-aicompletion}{/js}
{literal}
<script type="text/javascript">
(function ($) {
	$(function() {
    $(".netiaic-container").AICompletion();
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
        <!-- TODO: replace with real content -->
        <p>netiCRM AICompletion Content</p>
      </div>
    </div>
  </div>
</div>
<!-- AICompletion HTML end -->
