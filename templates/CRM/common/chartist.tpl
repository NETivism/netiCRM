<div class="chartist-wrapper">
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/dist/chartist.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-axistitle.js"></script>
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/dist/chartist.min.css">
  
  {if $chartist.withToolTip}
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-tooltip/chartist-plugin-tooltip.css">
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-tooltip/chartist-plugin-tooltip.min.js"></script>
  {/if}

  {if $chartist.type eq 'Pie' and $chartist.isFillDonut}
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-fill-donut/chartist-plugin-fill-donut.min.js"></script>
  {/if}

{if $chartist.series}
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
  var chartLabels = {/literal}{$chartist.labels|default:"[]"}{literal};
  var chartSeries = {/literal}{$chartist.series|default:"[]"}{literal};
  var withToolTip = {/literal}{$chartist.withToolTip|default:0}{literal};
  var isDonut = {/literal}{$chartist.isDonut|default:0}{literal};
  var isFillDonut = {/literal}{$chartist.isFillDonut|default:0}{literal};
  var animation = {/literal}{$chartist.animation|default:0}{literal};
  var chartSelector = "{/literal}{$chartist.selector|default:'.chartist-chart'}{literal}";
  var chartType = "{/literal}{$chartist.type|capitalize|default:'Line'}{literal}";
  var labelType = "{/literal}{$chartist.labelType|default:'label'}{literal}";

  var getSum = function(a, b) { return Number(a) + Number(b); }
  var getPercent = function(val, total) { return Math.round(Number(val) / total * 100); }
  var getDesc = function(label, series, percent) { return label + '：' + series + '筆（' + percent + '%）'; }

  var renderChartLegend = function(elem, data) {
    var label, series, percent, desc;
    var total = data.series.reduce(getSum);
    var ul = cj("<ul class='chart-legend' />");

    for (var i = 0; i < data.series.length; i++) {
      series = data.series[i];

      if (series != 0) {
        label = data.labels[i];
        percent = getPercent(series, total);
        desc = getDesc(label, series, percent);
        var li = cj("<li/>").attr({"title": desc, "data-chart-series": series, "data-chart-percent": percent}).text(label).appendTo(ul);
      }
    }

    var chartLoaded = setInterval(function() {
      if (cj(elem + " > svg > g").length > 0) {
        cj(elem).after(ul);
        clearInterval(chartLoaded);
      }
    }, 100);
  }

  var renderToolTipData = function(data) {
    var label, series, percent, desc;
    var total = data.series.reduce(getSum);
    
    for (var i = 0; i < data.series.length; i++) {
      label = data.labels[i];
      series = data.series[i];
      percent = getPercent(series, total);
      desc = getDesc(label, series, percent);
      data.series[i] = {meta: desc, value: series}
    }

    return data;
  }

  var data = {
    // Our series array that contains series objects or in this case series data arrays
    "series": {/literal}{$chartist.series}{literal}
  };

  if (typeof chartLabels !== 'undefined' && chartLabels.length > 0) {    
    // A labels array that can contain any sort of values
    data['labels'] = chartLabels;
  }

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
  else {
    options = {
      ignoreEmptyValues: true,
      labelInterpolationFnc: function(value, index) {
        switch (labelType) {
          case 'percent':
            var label, series = 0, percent = 0, total = 0;

            if (withToolTip) {
              series = data.series[index].value;

              for (var i = 0; i < data.series.length; i++) {
                total += data.series[i].value;
              }
            } 
            else {
              series = data.series[index];
              total = data.series.reduce(getSum);
            }

            percent = getPercent(series ,total);
            label = percent + '%';

            return label;
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


  if (withToolTip) {
    if (chartType == 'Pie') {
      data = renderToolTipData(data);
      var tooltip = Chartist.plugins.tooltip();
      options.plugins.push(tooltip);
    }
  }

  if (chartType == 'Pie') {
    if (isDonut) {
      options.donut = true;
    }

    if (isFillDonut) {
      var percent = getPercent(data.series[0], data.series[1]);
      var difference = 100 - percent;
      data.series[0] = percent;
      data.series[1] = difference;

      options.donut = true;
      options.donutWidth = 20;
      options.startAngle = 0;
      options.showLabel = false;

      var fillDonut = Chartist.plugins.fillDonut({
          items: [{
              content: '',
              position: 'bottom',
              offsetY : 10,
              offsetX: -2
          }, {
              content: '<div class="chart-percent">' + data.series[0] + '%</div>'
          }]
      });

      options.plugins.push(fillDonut);
    }
  }

  var chart = new Chartist.{/literal}{$chartist.type|capitalize|default:'Line'}{literal}(chartSelector, data, options);

  if (animation) {
    if (chartType == 'Pie') {
      if (isFillDonut) {
        chart.on('draw', function(data) {
          if(data.type === 'slice' && data.index == 0) {
            // Get the total path length in order to use for dash array animation
            var pathLength = data.element._node.getTotalLength();

            // Set a dasharray that matches the path length as prerequisite to animate dashoffset
            data.element.attr({
                'stroke-dasharray': pathLength + 'px ' + pathLength + 'px'
            });

            // Create animation definition while also assigning an ID to the animation for later sync usage
            var animationDefinition = {
                'stroke-dashoffset': {
                    id: 'anim' + data.index,
                    dur: 1200,
                    from: -pathLength + 'px',
                    to:  '0px',
                    easing: Chartist.Svg.Easing.easeOutQuint,
                    fill: 'freeze'
                }
            };

            // We need to set an initial value before the animation starts as we are not in guided mode which would do that for us
            data.element.attr({
                'stroke-dashoffset': -pathLength + 'px'
            });

            // We can't use guided mode as the animations need to rely on setting begin manually
            // See http://gionkunz.github.io/chartist-js/api-documentation.html#chartistsvg-function-animate
            data.element.animate(animationDefinition, true);
          }
        });
      }
    }
  }
})();
{/literal}
</script>
{/if}
</div>
