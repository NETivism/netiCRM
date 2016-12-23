<div class="chartist-wrapper">
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/dist/chartist.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-axistitle.js"></script>
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/dist/chartist.min.css">
{if $chartist.labels && $chartist.series}
  {if $chartist.title}<h3>{$chartist.title}</h3>{/if}
  
  {php}
    $chartClasses = array('chartist-chart','ct-major-twelfth');
    $chartist = $this->get_template_vars('chartist');
    
    if (count($chartist['classes']) > 0) {
      $chartClasses = array_merge($chartClasses, $chartist['classes']); 
    }

    $chartClasses = implode(' ', $chartClasses);
    $this->assign('chartClasses', $chartClasses);
  {/php}
  
  {if $chartist.id}
    <div id="{$chartist.id}" class="{$chartClasses}"></div>
  {else}
    <div class="{$chartClasses}"></div>
  {/if}

<script>{literal}
(function(){
  var chartSelector = "{/literal}{$chartist.selector|default:'.chartist-chart'}{literal}";
  var chartType = "{/literal}{$chartist.type|capitalize|default:'Line'}{literal}";
  var labelType = "{/literal}{$chartist.labelType|default:'label'}{literal}";

  var renderChartLegend = function(elem, data) {
    var label, desc, val, percent;
    var sum = function(a, b) { return Number(a) + Number(b) };
    var total = data.series.reduce(sum);
    var ul = cj("<ul class='chart-legend' />");
    for (var i = 0; i < data.series.length; i++) {
      val = data.series[i];
      percent = Math.round(Number(val) / total * 100);
      label = data.labels[i];
      desc = label + " (" + percent + "%)";
      var li = cj("<li/>").attr("title", desc).text(label).appendTo(ul);
    }

    var chartLoaded = setInterval(function() {
      if (cj(elem + " > svg > g").length > 0) {
        cj(elem).after(ul);
        clearInterval(chartLoaded);
      }
    }, 100);
  }

  var data = {
    // A labels array that can contain any sort of values
    "labels": {/literal}{$chartist.labels}{literal},
    // Our series array that contains series objects or in this case series data arrays
    "series": {/literal}{$chartist.series}{literal}
  };
  // Create a new line chart object where as first parameter we pass in a selector
  // that is resolving to our chart container element. The Second parameter
  // is the actual data object.

  var options = {};
  if(chartType == 'Line' || chartType == 'Bar') {
    options = {
      showPoint: true,
      showArea: true,
      lineSmooth: Chartist.Interpolation.simple({
        divisor: 3
      }),
      fullWidth: true,
      chartPadding: {
        top: 50,
        right: 50,
        bottom: 0,
        left: 0
      },
      axisY: {
        offset: 80,
        labelInterpolationFnc: function(value) {
          return '$ ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
      }
    };
  }
  else{
    var sum = function(a, b) { return Number(a) + Number(b) };
    var i = 0;
    options = {
      labelInterpolationFnc: function(value) {
        switch (labelType) {
          case 'percent':
            var series = data.series[i];
            i++;
            return Math.round(Number(series) / data.series.reduce(sum) * 100) + '%';
            break;

          default:
            return value;
            break;
        } 
      } 
    }; 
  }
  options.plugins = [];
{/literal}{if $chartist.axisx || $chartist.axisy}{literal}
  var axis = Chartist.plugins.ctAxisTitle({
    axisX: {
      axisTitle: '{/literal}{$chartist.axisx}{literal}',
      axisClass: 'ct-axis-title ct-axis-x',
      textAnchor: 'end',
      position: 'end',
      offset: { x: 40, y: 15 }
    },
    axisY: {
      axisTitle: '{/literal}{$chartist.axisy}{literal}',
      axisClass: 'ct-axis-title ct-axis-y',
      textAnchor: 'end',
      position: 'end',
      offset: { x: 70, y: -30 }
    }
  });
  options.plugins.push(axis);
{/literal}{/if}

{if $chartist.withLegend}{literal}
  options.labelOffset = 65;
  cj(chartSelector).closest('.chartist-wrapper').addClass('chart-with-legend');
  renderChartLegend(chartSelector, data);
{/literal}{/if}

{if $chartist.labelOffset}{literal}
  options.labelOffset = {/literal}{$chartist.labelOffset}{literal};
{/literal}{/if}{literal}

  new Chartist.{/literal}{$chartist.type|capitalize|default:'Line'}{literal}(chartSelector, data, options);
})();
{/literal}
</script>
{/if}
</div>
