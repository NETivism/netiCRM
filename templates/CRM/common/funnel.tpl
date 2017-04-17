{if $funnel.series}
  <script type="text/javascript" src="{$config->resourceBase}packages/ncfunnel/ncfunnel.js"></script>
  <link rel="stylesheet" href="{$config->resourceBase}packages/ncfunnel/ncfunnel.css">

  {php}
    $chart_classes = array('ncfunnel-chart');
    $funnel = $this->get_template_vars('funnel');
    
    if (count($funnel['classes']) > 0) {
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
  jQuery(document).ready(function() {
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

    jQuery(chartSelector).ncfunnel(options);
  });
  {/literal}</script>
{/if}