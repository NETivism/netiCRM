
{php}
global $crmChartistAdded;
if (!$crmChartistAdded) {
{/php}
  <script type="text/javascript" src="{$config->resourceBase}packages/moment/moment.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/dist/chartist.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-axistitle.js"></script>
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/dist/chartist.min.css">
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-tooltip/chartist-plugin-tooltip.css">
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-verticalhint/chartist-plugin-verticalhint.css">
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-legend/chartist-plugin-legend-custom.css">
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-tooltip/chartist-plugin-tooltip.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-verticalhint/chartist-plugin-verticalhint.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-fill-donut/chartist-plugin-fill-donut.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-legend/chartist-plugin-legend.js"></script>
{php}
  $crmChartistAdded = TRUE;
}
{/php}
<div class="chartist-wrapper">
{if $chartist.series}
  {if $chartist.title}<h3>{$chartist.title}</h3>{/if}
  
  {php}
    $chartClasses = array('chartist-chart');
    $chartist = $this->get_template_vars('chartist');
    $chartClasses[] = !empty($chartist['ratioClass']) ? $chartist['ratioClass'] : 'ct-major-twelfth';
    
    $chartClasses[] = 'ct-chart-' . strtolower($chartist['type']);

    if (array_key_exists('isDonut', $chartist)) {
      $chartClasses[] = 'ct-chart-donut';
    }

    if (array_key_exists('isFillDonut', $chartist)) {
      $chartClasses[] = 'ct-chart-fill-donut';
    }
    if (!is_array($chartist['classes'])) {
      $chartist['classes'] = array();
    }
    if (is_array($chartist['classes']) && count($chartist['classes']) > 0) {
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
  var chartId = "{/literal}{$chartist.id|default:""}{literal}";
  var chartLabels = {/literal}{$chartist.labels|default:"[]"}{literal};
  var chartSeries = {/literal}{$chartist.series|default:"[]"}{literal};
  var chartLegends = {/literal}{$chartist.legends|default:"[]"}{literal};
  var withOldLegend = {/literal}{$chartist.withOldLegend|default:0}{literal};
  var withLegend = {/literal}{$chartist.withLegend|default:0}{literal};
  var withToolTip = {/literal}{$chartist.withToolTip|default:0}{literal};
  var withVerticalHint = {/literal}{$chartist.withVerticalHint|default:0}{literal};
  var isDonut = {/literal}{$chartist.isDonut|default:0}{literal};
  var isFillDonut = {/literal}{$chartist.isFillDonut|default:0}{literal};
  var animation = {/literal}{$chartist.animation|default:0}{literal};
  var stackBars = {/literal}{$chartist.stackBars|default:0}{literal};
  var stackLines = {/literal}{$chartist.stackLines|default:0}{literal};
  var onlyIntegerY = {/literal}{$chartist.onlyIntegerY|default:0}{literal};
  var chartSelector = "{/literal}{$chartist.selector|default:'.chartist-chart'}{literal}";
  var chartType = "{/literal}{$chartist.type|capitalize|default:'Line'}{literal}";
  var labelType = "{/literal}{$chartist.labelType|default:'label'}{literal}";
  var seriesUnit = "{/literal}{$chartist.seriesUnit|default:''}{literal}";
  var seriesUnitPosition = "{/literal}{$chartist.seriesUnitPosition|default:'suffix'}{literal}";
  var autoDateLabel = {/literal}{$chartist.autoDateLabel|default:0}{literal};
  var seriesLength = 0;
  if (typeof chartSeries[0] === "object") {
    seriesLength = chartSeries[0].length; 
  }
  else
  if(typeof chartSeries=== "object"){
    seriesLength = chartSeries.length; 
  }
  autoDateLabel = seriesLength > 35 && autoDateLabel ? true : false; 

  var floorDecimal = function (val, precision) {
    return Math.floor(Math.floor(val * Math.pow(10, (precision || 0) + 1)) / 10) / Math.pow(10, (precision || 0));
  }

  var getPercent = function(val, total, precision) { 
    var percent = Number(val) / Number(total) * 100;
    return floorDecimal(percent, precision); 
  }
  var getSum = function(a, b) { return Number(a) + Number(b); }
  var getDesc = function(label, series, type, unit, percent) {
    var result = '';

    if (String(label).replace(/\s/g, "").length > 0) {
      result += label + '：';
    }
    
    if (String(unit).replace(/\s/g, "").length > 0) {
      result += renderUnitLabel(series, seriesUnit, seriesUnitPosition, 'desc');
    } 
    else {
      result += series;
    }

    if (percent) {
      result += '（' + percent + '%）';
    }

    return result;
  }

  var getNotEmptySeries = function(data) {
    var series, newSeries = [];

    for (var i = 0; i < data.series.length; i++) {
      series = data.series[i];

      if (series != 0) {
        newSeries.push(series);
      }
    }

    return newSeries;
  }

  var addAttrToSeries = function(elem, series) {
    var total = series.reduce(getSum);
    var seriesLength = series.length;
    var addData = function(item, i) {
      var percent = getPercent(series[i], total, 1);
      item.attr({"data-chart-series": series[i], "data-chart-percent": percent});  
    }

    var chartLoaded = setInterval(function() {
      if (cj(elem + " > svg > g:not(.ct-series) > text").length == seriesLength) {
        series.reverse();
        cj(elem + " > svg > g.ct-series").each(function(i) {
          addData(cj(this), i);
        });

        series.reverse();
        cj(elem + " > svg > g:not(.ct-series) > text").each(function(i) {
          addData(cj(this), i);
        });

        clearInterval(chartLoaded);
      }
    }, 100);
  }

  var renderUnitLabel = function(value, unit, position) {
    var result = '';

    if (unit.replace(/\s/g, "").length > 0) {
      if (unit.trim() == '$') {
        value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }

      if (position == 'prefix') {
        result = unit + value;
      }
      else {
        result = value + unit;
      }
    }
    else {
      result = value;
    }

    return result;
  }

  var renderChartLegend = function(elem, data, unit) {
    var label, series, percent, desc;
    var total = data.series.reduce(getSum);
    var type = chartType;
    var ul = cj("<ul class='chart-legend' />");

    for (var i = 0; i < data.series.length; i++) {
      series = data.series[i];

      if (series != 0) {
        label = typeof data.labels !== 'undefined' ? data.labels[i] : '';
        percent = getPercent(series, total, 1);

        desc = getDesc(label, series, type, unit, percent);
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

  var renderToolTipData = function(data, type, unit) {
    var label, series, desc, descData = {};
    var unit = typeof unit !== 'undefined' ? unit : '';

    if (type == 'Line' || type == 'Bar') {
      for (var i = 0; i < data.series.length; i++) {
        for (var j = 0; j < data.series[i].length; j++) {
          label = typeof data.labels !== 'undefined' ? data.labels[j] : '';
          series = data.series[i][j] !== null ? typeof data.series[i][j]["y"] !== "undefined" ? data.series[i][j]["y"] : data.series[i][j] : null;
          desc = getDesc(label, series, type, unit);

          // Added desc to "meta" property if data.series[i][j] not null and have "y" property.
          // refs #28542. Fixed issue of the chart cannot render. Have to check the value of the data is not null, because js have 'Uncaught TypeError: Cannot read property' error.
          if (data.series[i][j] !== null && typeof data.series[i][j]["y"] !== "undefined") {
            data.series[i][j]["meta"] = desc;
          }
          else {
            data.series[i][j] = {"meta": desc, "value": series};
          }
        }  
      }
    }

    if (type == 'Pie') {
      var percent = 0;
      var total = data.series.reduce(getSum);
      
      for (var i = 0; i < data.series.length; i++) {
        label = typeof data.labels !== 'undefined' ? data.labels[i] : '';
        series = data.series[i];
        percent = getPercent(series, total, 1);
        desc = getDesc(label, series, type, unit, percent);
        data.series[i] = {"meta": desc, "value": series};
      }
    }

    return data;
  }

  var renderStackLinesSeries = function(series) {
    for (var i = 0; i < series.length; i++) {
      if (i > 0) {
        for (var j = 0; j < series[i].length; j++) {
          series[i][j] = series[i-1][j] + series[i][j];
        }
      }
    }

    return series;
  }

  var renderSeriesWithLabels = function() {
    if (chartSeries.length > 0 && chartLegends.length > 0) {
      for (var i = 0; i < chartSeries.length; i++) {
        if (typeof chartLegends[i] !== 'undefined') {
          chartSeries[i] = {"name": chartLegends[i], "data": chartSeries[i]};
        }
      }
    }
  }

  var removeEmptySeriesAndLabels = function() {
    if (chartType == 'Pie') {
      for (var i = 0; i < chartSeries.length; i++) {
        if (chartSeries[i] == 0) {
          chartSeries.splice(i, 1);
          chartLabels.splice(i, 1);
        }
      }
    }
  }


  if (chartType == 'Line' && stackLines) {
    chartSeries = renderStackLinesSeries(chartSeries);
  }

  if (withOldLegend) {
    if (chartType == 'Pie') {
      removeEmptySeriesAndLabels();
    }
  }

  if (withLegend) {
    if (chartType == 'Pie') {
      removeEmptySeriesAndLabels();
    }
  }

  var data = {
    // Our series array that contains series objects or in this case series data arrays
    "series": chartSeries
  };

  if (typeof chartLabels !== 'undefined' && chartLabels.length > 0) {    
    data['labels'] = chartLabels;

    // for time serial horizontal label
    // check http://gionkunz.github.io/chartist-js/examples.html#example-timeseries-moment
    if (autoDateLabel && (typeof chartSeries === "object")) {
      data.series = [];
      // rebuild data object
      var stamp = [];
      for(var idx in chartLabels) {
        if (typeof autoDateLabel === "string" && autoDateLabel.length > 1) {
          stamp[idx] = moment(chartLabels[idx], autoDateLabel).format("x");
        }
        else {
          stamp[idx] = moment(chartLabels[idx]).format("x");
        }
      }
      for(var i in chartSeries) {
        if (typeof chartSeries[i] === "object") {
          data.series[i] = [];
          for(var j in chartSeries[i]) {
            data.series[i].push({ "x":stamp[j], "y":chartSeries[i][j]});
          }
        }
        else {
          data.series[i] = [];
          data.series[i].push({ "x":stamp[i], "y":chartSeries[i]});
        }
      }

      var axisX = {
        type: Chartist.FixedScaleAxis,
        divisor: chartLabels.length > 12 ? 12 : chartLabels.length,
        labelInterpolationFnc: function(value) {
          return moment(value).format('YYYY-MM-DD');
        }
      };
    }
  }

  // Create a new line chart object where as first parameter we pass in a selector
  // that is resolving to our chart container element. The Second parameter
  // is the actual data object.

  var options = {};
  if(chartType == 'Line' || chartType == 'Bar') {
    options = {
      classNames: {"chart": ""},
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
          var label = seriesUnit ? renderUnitLabel(value, seriesUnit, seriesUnitPosition, 'axis') : value;
          return label;
        },
        onlyInteger: onlyIntegerY
      }
    };
  }
  // pie, donut
  else {
    options = {
      classNames: {"chart": ""},
      ignoreEmptyValues: true,
      labelInterpolationFnc: function(value, index) {
        switch (labelType) {
          case 'percent':
            var label, series = 0, percent = 0, total = 0;

            if (withToolTip) {
              series = data.series[index].value;

              for (var i = 0; i < data.series.length; i++) {
                total += Number(data.series[i].value);
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
  if (typeof axisX === "object") {
    options["axisX"] = axisX;
  }

  if (seriesLength > 60) {
    options.classNames["chart"] += ' series-large ';
  }
  else
  if (seriesLength > 14) {
    options.classNames["chart"] += ' series-medium ';
  }
  else {
    options.classNames["chart"] += ' series-normal ';
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

{if $chartist.withOldLegend}{literal}
  options.labelOffset = 65;
  cj(chartSelector).closest('.chartist-wrapper').addClass('chart-with-old-legend');
  renderChartLegend(chartSelector, data, seriesUnit);
{/literal}{/if}

{if $chartist.withLegend}{literal}
  cj(chartSelector).closest('.chartist-wrapper').addClass('chart-with-legend');
  var legend = chartType == 'Pie' ? Chartist.plugins.legend() : Chartist.plugins.legend({legendNames: chartLegends});
  options.plugins.push(legend);

  if (chartType == 'Pie') {
    options.labelOffset = 65;
  }
{/literal}{/if}

{if $chartist.type eq 'Line' && $chartist.stackLines}{literal}
  options.showArea = true;
  options.showPoint = true;
  options.showLine = false;
  options.lineSmooth = Chartist.Interpolation.none();
  options.low = 0;
  options.classNames["chart"] += ' ct-chart-line ct-chart-line-stacked ';
{/literal}{/if}

{if $chartist.type eq 'Bar' && $chartist.stackBars}{literal}
  options.stackBars = {/literal}{$chartist.stackBars}{literal};
{/literal}{/if}

{if $chartist.labelOffset}{literal}
  options.labelOffset = {/literal}{$chartist.labelOffset}{literal};
{/literal}{/if}{literal}

  if (chartType == 'Pie') {
    var notEmptySeries = getNotEmptySeries(data);
    addAttrToSeries(chartSelector, notEmptySeries);
  }
    
  if (withToolTip) {
    data = renderToolTipData(data, chartType, seriesUnit);
    var tooltip = Chartist.plugins.tooltip();
    options.plugins.push(tooltip);   
  }
  if (withVerticalHint && !autoDateLabel && chartLegends.length > 0) {
    options.plugins.push(
      Chartist.plugins.verticalhint({
				seriesLabel:chartLegends
      })
    ); 
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
