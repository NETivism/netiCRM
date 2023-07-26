"use strict";

(function($) {
	/**
	 * ============================
	 * Private static constants
	 * ============================
	 */
  const NSP_CONTAINER = "nsp-container",
        NSP_INNER = "nsp-inner",
        NSP_CONTENT = "nsp-content",
        NSP_HEADER = "nsp-header",
        NSP_FOOTER = "nsp-footer",
        NSP_TRIGGER = "nsp-trigger",
        INNER_CLASS = "inner",
        ACTIVE_CLASS = "is-active",
        OPEN_CLASS = "is-opened",
        CLOSE_CLASS = "is-closed",
        FULLSCREEN_CLASS = "is-fullscreen",
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
    _nspSelector,
    _nspContentSelector,
    _nspHeaderSelector,
    _nspFooterSelector,
    _nspContent,
    _nspHeader,
    _nspFooter,
    _nspWidth,
    _nspOpened,
    _nspFullscreen,
    _nspContainerClass,
		_nspAPI = window.location.origin + "/api/",
		_container,
		_content = "." + NSP_CONTENT,
		_header = "." + NSP_HEADER,
		_footer = "." + NSP_FOOTER,
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

  var _cssVariablesUpdate = function(name, value) {
    if (typeof name !== "undefined" && typeof value !== "undefined") {
      document.documentElement.style.setProperty(name, value);
      console.log(getComputedStyle(document.documentElement).getPropertyValue(name));
    }
  }

	/**
	 * Main
	 */
	var _nspMain = {
		render: function() {
      $(_container).attr("data-type", _nspType);

			if ($(_content).length == 0) {
				$(_container).find("." + NSP_INNER).append("<div class='" + NSP_CONTENT + "'><div class='" + INNER_CLASS + "'></div></div>");
			}

			if ($(_header).length == 0 && _nspHeaderSelector) {
				$(_container).find("." + NSP_INNER).prepend("<div class='" + NSP_HEADER + "'><div class='" + INNER_CLASS + "'></div></div>");
			}

			if ($(_footer).length == 0 && _nspFooterSelector) {
				$(_container).find("." + NSP_INNER).append("<div class='" + NSP_FOOTER + "'><div class='" + INNER_CLASS + "'></div></div>");
			}

      switch (_nspType) {
        case "inline":
          if ($(_nspSelector).length) {
            _nspContent = $(_nspSelector).html();
          }
          else {
            if ($(_nspContentSelector).length) {
              _nspContent = $(_nspContentSelector).html();
            }

            if ($(_nspHeaderSelector).length) {
              _nspHeader = $(_nspHeaderSelector).html();
            }

            if ($(_nspFooterSelector).length) {
              _nspFooter = $(_nspFooterSelector).html();
            }
          }
          break;

        case "iframe":
          if (_isValidHttpUrl(_nspSrc)) {
            _nspContent = "<iframe src='" + _nspSrc + "' class='nsp-iframe' frameborder='0' allowfullscreen></iframe>";
          }
          break;

        case "remote_dom":
          if (_isValidHttpUrl(_nspSrc)) {
            $.get(_nspSrc, function(data) {
              if ($(data).find(".node__content .field--name-body").length) {
                _nspContent = $(data).find(".node__content .field--name-body").html();
              }

              if ($(data).find(".field-name-body").length) {
                _nspContent = $(data).find(".field-name-body").html();
                $(_container).find(_content).find(_inner).html(_nspContent);
              }
            });
          }
          break;
      }

      $(_container).find(_content).find(_inner).html(_nspContent);
      $(_container).find(_header).find(_inner).html(_nspHeader);
      $(_container).find(_footer).find(_inner).html(_nspFooter);

      $(_container).on("click", _trigger, function(event) {
        event.preventDefault();

        if ($(_container).hasClass(OPEN_CLASS)) {
          _nspMain.close();
        }
        else {
          _nspMain.open();
        }
      });

      if (_nspFullscreen) {
        $(_container).on("click", ".nsp-fullscreen-trigger", function(event) {
          event.preventDefault();

          if ($(_container).hasClass(FULLSCREEN_CLASS)) {
            _nspMain.side();
          }
          else {
            _nspMain.fullscreen();
          }
        });
      }

      if (_nspContainerClass) {
        $(_container).addClass(_nspContainerClass);
        $(_container).find("." + NSP_INNER).addClass(_nspContainerClass + "-innner");
        $(_content).addClass(_nspContainerClass + "-content");
        $(_header).addClass(_nspContainerClass + "-header");
        $(_footer).addClass(_nspContainerClass + "-footer");

        if ($("." + _nspContainerClass).length) {
          $("." + _nspContainerClass).each(function() {
            if (!$(this).hasClass(NSP_CONTAINER)) {
              $(this).remove();
            }
          });
        }
      }

      _tooltip();

      if (_nspWidth) {
        _cssVariablesUpdate("--nsp-width", _nspWidth);
      }

      if (_nspOpened) {
        _nspMain.open();
      }
		},
    open: function() {
      $(_container).addClass(OPEN_CLASS);
      $(_container).removeClass(CLOSE_CLASS);
      $("body").addClass("nsp-" + OPEN_CLASS);
    },
    close: function() {
      $(_container).removeClass(OPEN_CLASS);
      $(_container).addClass(CLOSE_CLASS);
      $("body").removeClass("nsp-" + OPEN_CLASS);
    },
    fullscreen: function() {
      _nspMain.open();
      $(_container).addClass(FULLSCREEN_CLASS);
      $("body").addClass("nsp-" + FULLSCREEN_CLASS);
    },
    side: function() {
      $(_container).removeClass(FULLSCREEN_CLASS);
      $("body").removeClass("nsp-" + FULLSCREEN_CLASS);
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

  var _tooltip = function() {
    var jq = $.fn.powerTip ? $ : jQuery.fn.powerTip ? jQuery : null;

    if (jq) {
      if ($("[data-tooltip]").length) {
        $("[data-tooltip]:not(.tooltip-initialized)").each(function() {
          let options = {};

          if ($(this).is("[data-tooltip-placement]")) {
            options.placement = $(this).data("tooltip-placement");
          }

          if ($(this).is("[data-tooltip-fadeouttime]")) {
            options.fadeOutTime = $(this).data("tooltip-fadeouttime");
          }

          jq(this).powerTip(options);
          $(this).addClass("tooltip-initialized");
        });
      }
    }
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
		},
    setPanelWidth: function(width) {
      _nspWidth = width;
      _cssVariablesUpdate("--nsp-width", _nspWidth);
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
      _nspSelector = _nspOptions.selector;
      _nspContentSelector = _nspOptions.contentSelector;
      _nspHeaderSelector = _nspOptions.headerSelector;
      _nspFooterSelector = _nspOptions.footerSelector;
      _nspContainerClass = _nspOptions.containerClass;
      _nspWidth = _nspOptions.width;
      _nspOpened = _nspOptions.opened;
      _nspFullscreen = _nspOptions.fullscreen;

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