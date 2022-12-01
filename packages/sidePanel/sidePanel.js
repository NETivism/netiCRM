"use strict";
console.log("nsp hello !");
(function($) {
	/**
	 * ============================
	 * Private static constants
	 * ============================
	 */
  const NSP_CONTAINER = "nsp-container",
        NSP_MAIN = "nsp-main",
        INNER_CLASS = "inner",
        ACTIVE_CLASS = "is-active",
        INIT_CLASS = "is-initialized";

	/**
	 * ============================
	 * Private variables
	 * ============================
	 */

	/**
	 * Global
	 */
	var neticrmSidePanel = function() {},
		_resizeTimer,
		_query = window.location.search.substring(1),
		_qs,
		_viewport = {
			width: window.innerWidth,
			height: window.innerHeight
    },
    _debugMode = false,
		_data = {},
		_dataLoadMode = "api",
    _nsp, // plugin object
		_nspOptions = {},
		_nspAPI = window.location.origin + "/api/",
		_container,
		_main = "." + NSP_MAIN;

	/**
	 * ============================
	 * Private functions
	 * ============================
	 */

	/**
	 * General
	 */
	var _getUrlContent = function(url) {
    return $.get(url);
	}

  var _isNumeric = function(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
  }

	var _objIsEmpty = function(obj) {
    for (var key in obj) {
      if (obj.hasOwnProperty(key)) {
        return false;
      }
    }
    return true;
	}

	var _parseQueryString = function(query) {
	  var vars = query.split("&");
	  var queryString = {};
	  for (var i = 0; i < vars.length; i++) {
	    var pair = vars[i].split("=");
	    var key = decodeURIComponent(pair[0]);
	    var value = decodeURIComponent(pair[1]);
	    // If first entry with this name
	    if (typeof queryString[key] === "undefined") {
	      queryString[key] = decodeURIComponent(value);
      }
      // If second entry with this name
      else if (typeof queryString[key] === "string") {
	      var arr = [queryString[key], decodeURIComponent(value)];
	      queryString[key] = arr;
      }
      // If third or later entry with this name
      else {
	      queryString[key].push(decodeURIComponent(value));
	    }
	  }
	  return queryString;
	}

  var _getViewport = function() {
    _viewport = {
      width  : window.innerWidth,
      height : window.innerHeight,
    };
    _debug(_viewport, "viewport");
  };

  var _updateUrlHash = function(hash) {
    var hash = typeof hash !== "undefined" ? "#" + hash : "";

    if (hash) {
      if (history.pushState) {
        history.pushState(null, null, hash);
      }
      else {
        location.hash = hash;
      }
    }
  }

	/**
	 * Main
	 */
	var _nspMain = {
		render: function() {
			if ($(_main).length == 0) {
				$(_container).append("<div class='" + NSP_MAIN + "'><div class='" + INNER_CLASS + "'></div></div>");
			}
		}
	};

	/* Main Help function */
	var _checkSnaInstance = function() {
		if(!$.neticrmSidePanel.instance) {
			_nsp = new neticrmSidePanel();
			_nsp.init();
			$.neticrmSidePanel.instance = _nsp;
		}
	};

	var _getData = {
		local: function(data) {
			if (typeof data !== "undefined") {
				return data;
			}
		},
		api: function(url) {
			return _getJSON(url);
		}
	};

	var _neticrmSidePanelInit = function() {
    _debug("===== neticrmSidePanel Init =====");

		if (!$(_container).hasClass(NSP_CONTAINER)) {
			$(_container).addClass(NSP_CONTAINER);
		}

		_nsp.render();

		// Window resize
		$(window).resize(function() {
			clearTimeout(_resizeTimer);
			_resizeTimer = setTimeout(_windowResize, 250);
		});

		$(_container).addClass(INIT_CLASS);
	};

	var _rwdEvents = function() {
	};

	var _windowResize = function() {
		_getViewport();
	};

	/**
	 * Debug
	 */
	var _ln = function() {
	  var e = new Error();
	  if (!e.stack) try {
	    // IE requires the Error to actually be throw or else the Error's 'stack'
	    // property is undefined.
	    throw e;
	  } catch (e) {
	    if (!e.stack) {
	      return 0; // IE < 10, likely
	    }
	  }
	  var stack = e.stack.toString().split(/\r\n|\n/);
	  // We want our caller's frame. It's index into |stack| depends on the
	  // browser and browser version, so we need to search for the second frame:
	  var frameRE = /:(\d+):(?:\d+)[^\d]*$/;
	  do {
	    var frame = stack.shift();
	  } while (!frameRE.exec(frame) && stack.length);
	  return frameRE.exec(stack.shift())[1];
	}

	var _debug = function(item, label, mode) {
    var item = typeof item !== "undefined" ? item : "",
        mode = typeof mode !== "undefined" ? mode : "default",
        label = typeof label !== "undefined" ? label : "",
        allow = false;

    if (window.console && window.console.log) {
      if (mode == "force") {
        allow = true;
      }
      else {
        if (_debugMode) {
          allow = true;
        }
      }

      if (allow) {
        if (label) {
          window.console.log("===== " + label + "=====");
        }

        if (typeof item === "object") {
          window.console.log(JSON.parse(JSON.stringify(item)));
        }
        else {
          window.console.log(item);
        }
      }
    }
	};

	/**
	 * ============================
	 * Public functions
	 * ============================
	 */

	/**
	 * Main
	 */
	neticrmSidePanel.prototype = {
		constructor: neticrmSidePanel,
		init: function() {
      _neticrmSidePanelInit();

      /*
      if (_dataLoadMode == "api") {
        var dataURL =  _nspAPI + "/xxx";
        $.ajax({
          url: dataURL,
          method: "GET",
          async: true,
          dataType: "JSON",
          success: function(response) {
            _debug(response, "get data");
            _data = response;
            _neticrmSidePanelInit();
          },
          error: function(xhr, status, error) {
            _debug("xhr:" + xhr + '\n' + "status:" + status + '\n' + "error:" + error);
            return false;
          }
        });
      }
      */
			/*
			if (_dataLoadMode == "local") {
				_data = _getData.local();
				_neticrmSidePanelInit();
			}
			*/
		},
		render: function() {
			_nspMain.render();
		},
		open: function(elem) {
			var $elem = $(elem);
			if ($elem.length) {
			}
			else {
				_debug("\"" + elem + "\" can not open, because this element has not existed yet.");
			}
		},
		close: function(elem) {
			var $elem = $(elem);
			if ($elem.length) {
			}
			else {
				_debug("\"" + elem + "\" can not close, because this element has not existed yet.");
			}
		}
	};

	/**
	 * Extend
	 */

	/**
	 *
	 * ============================
	 * Public static functions
	 * ============================
	 *
	 */
 	$.neticrmSidePanel = {
		instance: null
 	};

	// Plugin definition
	$.fn.neticrmSidePanel = function(options) {
    // Extend our default options with those provided
    _nspOptions = $.extend({}, $.fn.neticrmSidePanel.defaults, options);

    // Plugin implementation
    _qs = _parseQueryString(_query);

		if (_qs.dataLoadMode) {
      _dataLoadMode = _qs.dataLoadMode;
    }

    if (_qs.debug) {
      _debugMode = _qs.debug;
    }

    _container = this.selector;
    _checkSnaInstance();

		return this;
	};

	// Plugin defaults options
	$.fn.neticrmSidePanel.defaults = {
		// zoom: zoomProps.default
	};
}(jQuery));