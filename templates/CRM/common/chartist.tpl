<div class="chartist-wrapper">
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/dist/chartist.min.js"></script>
  <script type="text/javascript" src="{$config->resourceBase}packages/chartist/plugin/chartist-plugin-axistitle.js"></script>
  <link rel="stylesheet" href="{$config->resourceBase}packages/chartist/dist/chartist.min.css">
{if $chartist.labels && $chartist.series}
  {if $chartist.title}<h3>{$chartist.title}</h3>{/if}
  <div class="chartist-chart ct-major-twelfth"></div>
<script>{literal}
(function(){
  var data = {
    // A labels array that can contain any sort of values
    "labels": {/literal}{$chartist.labels}{literal},
    // Our series array that contains series objects or in this case series data arrays
    "series": {/literal}{$chartist.series}{literal}
  };
  // Create a new line chart object where as first parameter we pass in a selector
  // that is resolving to our chart container element. The Second parameter
  // is the actual data object.
  var chartType = "{/literal}{$chartist.type|capitalize|default:'Line'}{literal}";
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
    var sum = function(a, b) { return a + b };
    options = {
      labelInterpolationFnc: function(value) {
        return value;
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
{/literal}{/if}{literal}
  new Chartist.{/literal}{$chartist.type|capitalize|default:'Line'}{literal}('.chartist-chart', data, options);
})();
{/literal}</script>
{/if}
</div>
