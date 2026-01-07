{if $funnel.series}
  {js src=packages/ncfunnel/ncfunnel.js group=999 weight=998 library=civicrm/civicrm-js-ncfunnel}{/js}
  <link rel="stylesheet" href="{$config->resourceBase}packages/ncfunnel/ncfunnel.css">

  {php}
    $chart_classes = array('ncfunnel-chart');
    $funnel = $this->get_template_vars('funnel');
    
    if (is_array($funnel['classes']) && count($funnel['classes']) > 0) {
      $chart_classes = array_merge($chart_classes, $funnel['classes']); 
    }

    $chart_classes = implode(' ', $chart_classes);
    $this->assign('chart_classes', $chart_classes);
  {/php}

  {if $funnel.id}
    <div id="{$funnel.id}" class="{$chart_classes}"></div>
  {else}
    <div class="{$chart_classes}"></div>
  {/if}

  <script type="text/javascript">{literal}
  cj(document).ready(function() {
    var chartSeries = {/literal}{$funnel.series|default:"[]"}{literal};
    var chartLabels = {/literal}{$funnel.labels|default:"[]"}{literal};
    var chartLabelsTop = {/literal}{$funnel.labelsTop|default:"[]"}{literal};
    var chartSelector = "{/literal}{$funnel.selector|default:'.ncfunnel-chart'}{literal}";
    var options = {
      "series": chartSeries
    }

    if (typeof chartLabels !== 'undefined' && chartLabels.length > 0) {
      options.labels = chartLabels;
    }

    if (typeof chartLabelsTop !== 'undefined' && chartLabelsTop.length > 0) {
      options.labelsTop = chartLabelsTop;
    }

    cj(chartSelector).ncfunnel(options);
  });
  {/literal}</script>
{/if}