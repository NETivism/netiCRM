"use strict";

(function($) {
	/**
	 * ============================
	 * Private static constants
	 * ============================
	 */
  const NSP_CONTAINER = "nsp-container",
        NSP_CONTENT = "nsp-content",
        NSP_TRIGGER = "nsp-trigger",
        INNER_CLASS = "inner",
        ACTIVE_CLASS = "is-active",
        OPEN_CLASS = "is-opened",
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
    _nspType,
    _nspSrc,
    _nspContent,
    _nspOpened,
		_nspAPI = window.location.origin + "/api/",
		_container,
		_content = "." + NSP_CONTENT,
		_trigger = "." + NSP_TRIGGER,
    _inner = "." + INNER_CLASS;

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

  var _isValidHttpUrl = function(string) {
    try {
      const url = new URL(string);
      return url.protocol === "http:" || url.protocol === "https:";
    }
    catch (err) {
      return false;
    }
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
			if ($(_content).length == 0) {
				$(_container).append("<div class='" + NSP_CONTENT + "'><div class='" + INNER_CLASS + "'></div></div>");
			}

      switch (_nspType) {
        case "inline":
          if ($(_nspSrc).length) {
            _nspContent = $(_nspSrc).html();
          }
          break;

        case "iframe":
          if (_isValidHttpUrl(_nspSrc)) {
            _nspContent = "<iframe src='" + _nspSrc + "' class='nsp-iframe' frameborder='0' allowfullscreen></iframe>";
          }
          break;
      }

      $(_container).find(_content).find(_inner).html(_nspContent);

      $(_container).on("click", _trigger, function(event) {
        event.preventDefault();

        if ($(_container).hasClass(OPEN_CLASS)) {
          _nspMain.close();
        }
        else {
          _nspMain.open();
        }
      });

      if (_nspOpened) {
        _nspMain.open();
      }
		},
    open: function() {
      $(_container).addClass(OPEN_CLASS);
      $("body").addClass("nsp-" + OPEN_CLASS);
    },
    close: function() {
      $(_container).removeClass(OPEN_CLASS);
      $("body").removeClass("nsp-" + OPEN_CLASS);
    }
	};

	/* Main Help function */
	var _checkNspInstance = function() {
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
		},
		render: function() {
			_nspMain.render();
		},
		open: function(elem) {
      _nspMain.open();
		},
		close: function(elem) {
      _nspMain.close();
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
	$.fn.neticrmSidePanel = function(selector, options) {
    if (typeof selector === "string" && $(selector).length) {
      // Extend our default options with those provided
      _nspOptions = $.extend({}, $.fn.neticrmSidePanel.defaults, options);

      // Plugin implementation
      _qs = _parseQueryString(_query);
      _debugMode = _nspOptions.debugMode === "1" ? true : false;
      _nspType = _nspOptions.type;
      _nspSrc = _nspOptions.src;
      _nspOpened = _nspOptions.opened;

      if (_debugMode) {
        $("html").addClass("is-debug");
      }

      _container = selector;
      _checkNspInstance();

      return _nsp;
    }
    else {
      if (window.console || window.console.error) {
        console.error(".selector API has been removed in jQuery 3.0. jQuery Plugin that need to use a selector string within their plugin can require it as a parameter of the method.");
      }
    }
	};

	// Plugin defaults options
	$.fn.neticrmSidePanel.defaults = {
    type: "inline",
    opened: false,
		debugMode: false
	};
}(jQuery));