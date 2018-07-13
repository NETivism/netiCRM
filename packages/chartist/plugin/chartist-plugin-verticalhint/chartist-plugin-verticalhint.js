(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define(["chartist"], function (Chartist) {
      return (root.returnExportsGlobal = factory(Chartist));
    });
  } else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like enviroments that support module.exports,
    // like Node.
    module.exports = factory(require("chartist"));
  } else {
    root['Chartist.plugins.verticalhint'] = factory(Chartist);
  }
}(this, function (Chartist) {

  /**
  * Chartist.js plugin to display a data label on top of the points in a line chart.
  *
  */
  /* global Chartist */
  (function (window, document, Chartist) {
    'use strict';

    var defaultOptions = {
      valuePrefix: '',
      valueSuffix: '',
      customSelector: '',
      verticalhintOffset: {
        x: 0,
        y: 0
      }
    };

    Chartist.plugins = Chartist.plugins || {};
    Chartist.plugins.verticalhint = function (options) {
      options = Chartist.extend({}, defaultOptions, options);

      return function verticalhint(chart) {
        if (options.customSelector) {
          var verticalhintSelector = options.customSelector;
        }
        else {
          var verticalhintSelector = 'ct-horizontal';
        }
        var $chart = chart.container;

        // this apply chartist default class ct-series-[alphabetical] based on index number
        var classes = [];
        for(var i = 0; i < 26; i++) {
          classes[i] = String.fromCharCode('a'.charCodeAt(0) + i);
        }

        // chart grid labels
        var horizontalLabels = chart.data.labels.map(function(x){ return x.toString();});
        
        // custom labels for series, each line should have one
        var seriesLabel = options.seriesLabel;

        // series data
        var series = chart.data.series;

        var $verticalhint = $chart.querySelector('.chartist-verticalhint');
        if (!$verticalhint) {
          $verticalhint = document.createElement('div');
          $verticalhint.className = (!options.class) ? 'chartist-verticalhint' : 'chartist-verticalhint ' + options.class;
          $chart.appendChild($verticalhint);
        }
        var $verticalhintHighlight = $chart.querySelector('.chartist-verticalhint-highlight');
        if (!$verticalhintHighlight) {
          $verticalhintHighlight = document.createElement('div');
          $verticalhintHighlight.className = (!options.class) ? 'chartist-verticalhint-highlight' : 'chartist-verticalhint-highlight ' + options.class;
          $chart.appendChild($verticalhintHighlight);
          $verticalhintHighlight.style.height = '100px';
        }
        var height = $verticalhint.offsetHeight;
        var width = $verticalhint.offsetWidth;

        hide($verticalhint);
        hide($verticalhintHighlight);

        function on(event, selector, callback) {
          $chart.addEventListener(event, function (e) {
            if (!selector || hasClass(e.target, selector))
            callback(e);
          });
        }

        on('mouseover', verticalhintSelector, function (event) {
          var pointText = event.target.innerHTML;
          var $grid = chart.container.querySelector('.ct-grids');
          var index = horizontalLabels.indexOf(pointText);
          if (index === -1) {
            return;
          }
          var verticalhintText = '';
          var verticalhintText = addContent(index);

          if(verticalhintText) {
            var wid, hei, position, padding;

            if (chart instanceof Chartist.Bar) {
              wid = event.target.offsetParent.attributes.width.value;
            }
            else {
              wid = (event.target.offsetParent.attributes.width.value / 3);
            }
            hei = $grid.getBBox().height;
            position = setPosition(event, wid, hei+5);
            $verticalhintHighlight.style.width = wid + 'px';
            $verticalhintHighlight.style.height = hei + 'px';
            $verticalhintHighlight.style.left = position.x + 'px';
            $verticalhintHighlight.style.top = position.y + 'px';

            $verticalhint.innerHTML = verticalhintText;
            padding = 10;
            position = setPosition(event, width, height+20);
            if (chart instanceof Chartist.Bar) {
              $verticalhint.style.left = position.x+wid/2 + padding + 'px';
            }
            else{
              $verticalhint.style.left = position.x+width/2+wid/2 + padding + 'px';
            }
            $verticalhint.style.top = position.y + 'px';

            show($verticalhint);
            show($verticalhintHighlight);

            // Remember height and width to avoid wrong position in IE
            height = $verticalhint.offsetHeight;
            width = $verticalhint.offsetWidth;
          }
        });

        on('mouseout', verticalhintSelector, function () {
          hide($verticalhint);
          hide($verticalhintHighlight);
        });

        function addContent(idx) {
          if (typeof series !== 'undefined') {
            var seriesValue = [];
            if (series[0].constructor === Array) { // multidimensional
              for (var key in series) {
                var val = series[key];
                if (typeof val[idx] !== 'undefined') {
                  if (typeof val[idx].value !== 'undefined') {
                    seriesValue[key] = val[idx].value;
                    //while zoom plugin is enabled
                  }else if(typeof val[idx] === "object"  && val[idx]['y'] !== undefined){
                    seriesValue[key]=val[idx]['y'];
                  }
                  else {
                    seriesValue[key] = val[idx];
                  }
                }
              }
            }
            else {
              if (typeof series[idx].value !== 'undefined') {
                seriesValue[0] = series[idx].value;
              }
              else {
                seriesValue[0] = series[idx];
              }
            }
            if (seriesValue.length > 0 && seriesValue.length === seriesLabel.length) {
              if (options.contentCallback && typeof options.contentCallback === 'function') {
                return options.contentCallback(seriesLabel, seriesValue, chart);
              }
              else {
                var content = '';
                for (var key in seriesLabel) {
                  content += '<div class="hint-item ct-series ct-series-'+classes[key]+'">';
                  content += '<svg style="width:15px;height:10px;" class="ct-series-'+classes[key]+'"><line x1="5" y1="5" x2="5" y2="5" class="ct-point"></line></svg>';
                  content += '<label>'+seriesLabel[key]+':</label> ';
                  content += '<span> <span class="value-prefix">'+options.valuePrefix+'</span>';
                  content += '<span class="value-number">'+seriesValue[key]+'</span>';
                  content += '<span class="value-suffix">'+options.valueSuffix+'</span> </span>';
                  content += '</div>';
                }
                return content;
              }
            }
          }
          return '';
        }

        function setPosition(event, width, height) {
          if (chart instanceof Chartist.Bar) {
            var offsetX = options.verticalhintOffset.x
          }
          else {
            var offsetX = - width / 2 + options.verticalhintOffset.x
          }
          var offsetY = - height + options.verticalhintOffset.y;
          var x = parseInt(event.target.offsetParent.attributes.x.value);
          var y = parseInt(event.target.offsetParent.attributes.y.value);

          return {
            x: x + offsetX,
            y: y + offsetY
          };
        }
      }
    };

    function show(element) {
      if(!hasClass(element, 'verticalhint-show')) {
        element.className = element.className + ' verticalhint-show';
      }
    }

    function hide(element) {
      var regex = new RegExp('verticalhint-show' + '\\s*', 'gi');
      element.className = element.className.replace(regex, '').trim();
    }

    function hasClass(element, className) {
      return (' ' + element.getAttribute('class') + ' ').indexOf(' ' + className + ' ') > -1;
    }

    function next(element, className) {
      do {
        element = element.nextSibling;
      } while (element && !hasClass(element, className));
      return element;
    }

    function text(element) {
      return element.innerText || element.textContent;
    }

  } (window, document, Chartist));

  return Chartist.plugins.verticalhint;

}));
