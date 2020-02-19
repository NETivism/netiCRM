"use strict";
(function($) {
	/**
	 * ============================
	 * Private static constants
	 * ============================
	 */
  const NME_CONTAINER = "nme-container",
        NME_MAIN = "nme-main",
        INNER_CLASS = "inner",
        INIT_CLASS = "is-initialized",
        EDIT_CLASS = "is-edit",
        ACTIVE_CLASS = "is-active",
        OVERLAY_CLASS = "is-overlay";

	/**
	 * ============================
	 * Private variables
	 * ============================
	 */

	/**
	 * Global
	 */
	var nmEditor = function() {},
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
    _nme, // plugin object
		_nmeOptions = {},
		_nmeAPI = window.location.origin + "/api/",
		_container,
		_main = "." + NME_MAIN;

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
	var _nmeMain = {
		render: function() {
			if ($(_main).length == 0) {
				$(_container).append("<div class='" + NME_MAIN + "'><div class='" + INNER_CLASS + "'></div></div>");
			}
		}
	};

	/* Main Help function */
	var _checkSnaInstance = function() {
		if(!$.nmEditor.instance) {
			_nme = new nmEditor();
			_nme.init();
			$.nmEditor.instance = _nme;
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

	var _editable = function() {
		if (typeof $.fn.editable !== "undefined") {
			$.fn.editable.defaults.mode = 'inline';
			$(".nme-editable").editable();
		}
	};

	var _sortable = function() {
		var nmeBlocks = document.getElementById('nme-blocks');
		new Sortable(nmeBlocks, {
			animation: 150,
			draggable: ".nme-block",
			dragClass: "handle-drag",
			ghostClass: 'nme-block-dragging'
		});
	};

	var _nmeBlockControl = function() {
		$(".nme-block-control").on("click", ".handle-btn", function() {
			let $handle = $(this),
					handleType = $handle.data("type"),
					$block = $handle.closest(".nme-block"),
					blockID = $block.attr("id"),
					blockType = $block.data("type"),
					$target = $block.find(".nme-" + blockType);

			// $block.addClass(ACTIVE_CLASS);
			// move
			
			// actions
			// image
			if (handleType == "image") {
				nmeImceTargetID = blockID;
				var win = window.open('/imce&app=nme|sendto@nmeImce', 'nme_imce', 'width=640, height=480');
			}

			// link
			if (handleType == "link") {
				let	editBlockID = "nme-edit-" + blockID,
						editItemID = "nme-edit-" + handleType + "-item-" + blockID;
						
				if ($target.length && !$target.find(".nme-edit").length) {
					let editItemVal = $target.data("link") ? $target.data("link") : "";
					let nmeEdit = "<div id='" + editBlockID + "' class='nme-edit' data-target='" + blockID + "'>" +
						"<div class='" + INNER_CLASS + "'>" +
						"<div class='nme-edit-link-item nme-edit-item'>" +
						"<div class='nme-edit-item-label'>" +
						"<label for='" + editItemID + "'>請輸入網址</label>" +
						"</div>" + // .nme-edit-item-label
						"<div class='nme-edit-item-content'>" +
						"<input id='" + editItemID + "' name='" + editItemID + "' type='text' placeholder='http://' value='" + editItemVal + "'>" +
						"</div>" + // .nme-edit-item-content
						"</div>" + // .nme-edit-item
						"<div class='nme-edit-actions'>" +
						"<a href='#' class='nme-edit-submit nme-edit-action btn' data-type='submit'>儲存</a>" +
						"<a href='#' class='nme-edit-cancel nme-edit-action btn' data-type='cancel'>取消</a>" +
						"</div>" + // .nme-edit-actions
						"</div>" + // .inner
						"</div>"; // .nme-edit


					$block.addClass(EDIT_CLASS);

					// 將編輯表單放入對應的元件內
					$target.addClass(EDIT_CLASS).append(nmeEdit);
					let $editBlock = $("#" + editBlockID),
							$editItem = $("#" + editItemID);

					// 編輯表單的按鈕事件：送出與取消
					$editBlock.on("click", ".nme-edit-action", function(event) {
						event.preventDefault();

						let $action = $(this),
								actionType = $action.data("type");

						if (actionType == "submit") {
							let editItemVal = $editItem.val();

							if ($.trim(editItemVal)) {
								$target.attr("data-link", editItemVal);
							}
						}

						$editBlock.remove();
						$block.removeClass(EDIT_CLASS);
						$target.removeClass(EDIT_CLASS);
 					});
				}
			}
		});
	};

	var _nmePanels = function() {
		$(".nme-setting-panels").on("click", ".nme-setting-panels-trigger", function() {
			var $panels = $(".nme-setting-panels");
			if ($panels.hasClass("is-opened")) {
				$panels.removeClass("is-opened");
				$("body").removeClass("nme-panel-is-opened");
			}
			else {
				$panels.addClass("is-opened");
				$("body").addClass("nme-panel-is-opened");
			}
		});
	};

	var _nmEditorInit = function() {
    _debug("===== nmEditor Init =====");

		if (!$(_container).hasClass(NME_CONTAINER)) {
			$(_container).addClass(NME_CONTAINER);
		}

		_nme.render();

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
	 * Public variables
	 * ============================
	 */

	// IMCE客製公用函式，用來介接圖片上傳功能
	window.nmeImce = function(file, imceWindow) {
		// _debug(file);
		// _debug(imceWindow); // TypeError: cyclic object value
		// console.log(file);
		// console.log(imceWindow);
		if (nmeImceTargetID) {
			let $target = $("#" + nmeImceTargetID);

			if ($target.length) {
				let $imgContainer = $target.find(".nme-image"),
						img = "<img class='nc-img' src='" + file.url + "' alt='" + file.name + "' />";
				
				$imgContainer.html(img);
			}
		}

		imceWindow.close();
	}

	window.nmeImceTargetID = "";

	/**
	 * ============================
	 * Public functions
	 * ============================
	 */

	/**
	 * Main
	 */
	nmEditor.prototype = {
		constructor: nmEditor,
		init: function() {
      _nmEditorInit();
      
      /*
      if (_dataLoadMode == "api") {
        var dataURL =  _nmeAPI + "/xxx";			    
        $.ajax({
          url: dataURL,
          method: "GET",
          async: true,
          dataType: "JSON",
          success: function(response) {
            _debug(response, "get data");
            _data = response;
            _nmEditorInit();
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
				_nmEditorInit();
			}
			*/
		},
		render: function() {
			_nmeMain.render();
			_editable();
			_sortable();
			_nmeBlockControl();
			_nmePanels();
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
 	$.nmEditor = {
		instance: null
 	};

	// Plugin definition
	$.fn.nmEditor = function(options) {
    // Extend our default options with those provided
    _nmeOptions = $.extend({}, $.fn.nmEditor.defaults, options);

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
	$.fn.nmEditor.defaults = {
		// zoom: zoomProps.default
	};
}(jQuery));