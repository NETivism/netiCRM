(function($) {
  var floorDecimal = function (val, precision) {
    return Math.floor(Math.floor(val * Math.pow(10, (precision || 0) + 1)) / 10) / Math.pow(10, (precision || 0));
  }

  var getPercent = function(val, sum, precision) { 
    var percent = Number(val) / Number(sum) * 100;
    return floorDecimal(percent, precision); 
  }

  var renderFunnel = function(target, options) {
    var settings = {
      series: null, 
      labels: null, 
      labelsTop: null
    };
    $.extend(settings, options);

    var $funnel     = target;
    var series      = options.series;
    var labels      = options.labels;
    var labelsTop   = options.labelsTop;
    var row         = series.length;
    var column      = series[0].length;
    var barSum      = column;
    var arrowSum    = barSum - 1;
    var itemSum     = barSum + arrowSum;
    var itemWidth   = floorDecimal(100 / itemSum, 3);

    $funnel.addClass("ncf-container ncf-horizontal");

    for (var i = 0; i < itemSum; i++) {
      var colNum = i + 1;
      $funnel.append("<div class='ncf-item' style='width:" + itemWidth + "%;'></div>");
      var itemOutput = "";

      if (Math.abs(colNum % 2) == 1) {
        var bar = {};
        var valSum = 0;
        var k = i / 2;
        bar.items = [];
        
        for (var j = 0; j < row; j++) {
          var val = parseFloat(series[j][k]);
          bar.items[j] = {};
          bar.items[j].value = val;
          valSum += val;
        }
        
        for (var j = 0; j < row; j++) {
          bar.items[j].percent = getPercent(bar.items[j].value, valSum, 2);
        }

        // Render top label
        if (labelsTop && labelsTop.length > 0) {
          bar.labelTop = labelsTop[k];
          itemOutput += "<div class='ncf-chart-label-top'>" + bar.labelTop + "</div>";
        }

        // Render chart bar
        itemOutput += "<div class='ncf-chart-bar'>";
        for (var b in bar.items) {
          var v = bar.items[b].value;
          var p = bar.items[b].percent;

          itemOutput += 
          "<div class='ncf-bar ncf-bar-" + b + "' role='bar' aria-valuenow='" + p + "' aria-valuemin='0' aria-valuemax='100' data-value='" + v + "' data-percent='" + p + "' style='height: "+ p +"%;'>" +
            "<div class='ncf-bar-percent'>" + p + "%</div>" +
            "<div class='ncf-bar-meta'>" + v + "</div>" + 
          "</div>";
        }
        itemOutput += "</div>";

        // Render label
        if (labels && labels.length > 0) {
          bar.label = labels[k];
          itemOutput += "<div class='ncf-chart-label'>" + bar.label + "</div>";
        }
      }
      else {
        itemOutput += "<div class='ncf-arrow ncf-arrow-right'><div class='arrow'></div></div>";
      }
      
      $funnel.find(".ncf-item").eq(i).append(itemOutput);
    }
  }

  $.fn.extend({
    ncfunnel: function(options) {
      return this.each(function() {
        renderFunnel($(this), options);
      });
    }
  });
})(jQuery);