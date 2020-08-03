"use strict";
(function($) {
	/**
	 * ============================
	 * Private static constants
	 * ============================
	 */
  const JCP_CONTAINER = "jcp-container",
        JCP_MAIN = "jcp-main",
        INNER_CLASS = "inner",
        ACTIVE_CLASS = "is-active",
        INIT_CLASS = "is-initialized",
        OVERLAY_CLASS = "is-overlay";

	/**
	 * ============================
	 * Private variables
	 * ============================
	 */

	/**
	 * Global
	 */
	var jqueryCustomPlugin = function() {},
		_resizeTimer,
		_protocol = window.location.protocol,
		_path = window.location.pathname.substring(1),
		_pathArr = _path.split("/"),
		_hash = window.location.hash.substring(1),
		_query = window.location.search.substring(1),
		_qs,
		_viewport = {
			width: window.innerWidth,
			height: window.innerHeight
    },
    _debugMode = false,
		_data = {},
		_dataLoadMode = "api",
    _jcp, // plugin object
		_jcpOptions = {},
		_jcpAPI = window.location.origin + "/api/",
		_container,
		_main = "." + JCP_MAIN;

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
	var _jcpMain = {
		render: function() {
			if ($(_main).length == 0) {
				$(_container).append("<div class='" + JCP_MAIN + "'><div class='" + INNER_CLASS + "'></div></div>");
			}
		}
	};

	/* Main Help function */
	var _checkSnaInstance = function() {
		if(!$.jqueryCustomPlugin.instance) {
			_jcp = new jqueryCustomPlugin();
			_jcp.init();
			$.jqueryCustomPlugin.instance = _jcp;
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

	var _jqueryCustomPluginInit = function() {
    _debug("===== jqueryCustomPlugin Init =====");

		if (!$(_container).hasClass(JCP_CONTAINER)) {
			$(_container).addClass(JCP_CONTAINER);
		}

		_jcp.render();

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
	jqueryCustomPlugin.prototype = {
		constructor: jqueryCustomPlugin,
		init: function() {
      _jqueryCustomPluginInit();
      
      /*
      if (_dataLoadMode == "api") {
        var dataURL =  _jcpAPI + "/xxx";			    
        $.ajax({
          url: dataURL,
          method: "GET",
          async: true,
          dataType: "JSON",
          success: function(response) {
            _debug(response, "get data");
            _data = response;
            _jqueryCustomPluginInit();
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
				_jqueryCustomPluginInit();
			}
			*/
		},
		render: function() {
			_jcpMain.render();
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
 	$.jqueryCustomPlugin = {
		instance: null
 	};

	// Plugin definition
	$.fn.jqueryCustomPlugin = function(options) {
    // Extend our default options with those provided
    _jcpOptions = $.extend({}, $.fn.jqueryCustomPlugin.defaults, options);

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
	$.fn.jqueryCustomPlugin.defaults = {
		// zoom: zoomProps.default
	};
}(jQuery));