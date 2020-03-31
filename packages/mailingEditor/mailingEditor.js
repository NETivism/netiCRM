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
		_dataLoadMode = "field",
		_dataLoadSource = "",
    _nme, // plugin object
		_nmeOptions = {},
		_nmeAPI = window.location.origin + "/api/",
		_container,
		_main = "." + NME_MAIN,
		_controlIconClass = {
			drag: "zmdi-arrows",
			prev: "zmdi-long-arrow-up",
			next: "zmdi-long-arrow-down",
			clone: "zmdi-collection-plus",
			delete: "zmdi-delete",
			link: "zmdi-link",
			image: "zmdi-image",
			style: "zmdi-format-color-fill"
		},
		_editActions = {
			default: ["clone", "delete"],
			extended: {
				image: ["link", "image"],
				title: ["link", "style"],
				paragraph: ["style"],
				button: ["link", "style"]
			}
		},
		_sortables = {},
		_pickrs = [],
		_tpl = {
			mail: {
				"col-1-full-width": ""
			},
			block: {
				"title": "",
				"paragraph": "",
				"image": "",
				"button": "",
				"edit": ""
			},
			elem: {}
		};

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

	var _swapArrayVal = function(arr, a, b) {
    if (typeof arr !== "undefined") {
      var a_index = arr.indexOf(a);
      var b_index = arr.indexOf(b);
      var temp = arr[a_index];
      arr[a_index] = arr[b_index];
      arr[b_index] = temp;
      return arr;
    }
  }

	var _isJsonString = function(str) {
		try {
			var json = JSON.parse(str);
			return (typeof json === "object");
		} catch (e) {
			_debug("===== Source data is not json. =====");
			return false;
		}
	}

	var _objIsEmpty = function(obj) {
		if (typeof obj === "object") {
			for (var key in obj) {
				if (obj.hasOwnProperty(key)) {
					return false;
				}
			}
			return true;
		}
		else {
			return false;
		}
	}

	var _getLength = function(obj) {
		let sum = 0;
		for (let count = 0; count < obj.length; count ++) {
			sum += obj[count].length ? obj[count].getLength() : 1;
		}
		return 6;
		//return sum;
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

  var _renderID = function(str, len) {
    var str = typeof str !== "undefined" ? str : "";
    var len = typeof len !== "undefined" ? len : 10;
    var allow = "abcdefghijklmnopqrstuvwxyz0123456789";
    // var allow = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    var output = "";

    if (str) {
      output = str + "-";
    }

    for (var i = 0; i < len; i++) {
      output += allow.charAt(Math.floor(Math.random() * allow.length));
    }

    if (output) {
      return output;
		}
	}

	var _elemIsOnScreen = function(elem, mode, buffer) {
		let $elem = $(elem),
				screenMode = typeof mode !== "undefined" ? mode : "default",
				scrollBuffer = typeof buffer !== "undefined" ? parseInt(buffer) : 0;

		if ($elem.length) {
			let docViewTop = $(window).scrollTop(),
					docViewBottom = docViewTop + $(window).outerHeight(),
					elemTop = typeof buffer !== "undefined" ? $elem.offset().top + scrollBuffer : $elem.offset().top,
					elemBottom = elemTop + $elem.outerHeight();

			/*
			console.log("elemTop: " + elemTop);
			console.log("elemBottom: " + elemBottom);
			console.log("docViewTop: " + docViewTop);
			console.log("docViewBottom: " + docViewBottom);
			*/

			if (mode == "default") {
				return ((elemTop >= docViewTop && elemTop <= docViewBottom) || (elemBottom >= docViewTop && elemBottom <= docViewBottom) || (docViewTop >= elemTop && docViewTop <= elemBottom));
			}
		}
	};

	var _onScreenCenterElem = function(elem) {
		let $elem = $(elem),
				elemsYaxisRange = {};

		if ($elem.length) {
			let docViewTop = $(window).scrollTop(),
					docViewBottom = docViewTop + $(window).outerHeight();

			$elem.each(function(e) {
				let	$this = $(this),
						elemID = $this.attr("id"),
						elemTop = typeof buffer !== "undefined" ? $this.offset().top : $this.offset().top,
						elemBottom = elemTop + $this.outerHeight(),
						elemYaxisRange = [elemTop, elemBottom];

				elemsYaxisRange[elemID] = elemYaxisRange;
			});

			_debug(elemsYaxisRange, "onScreenCenterElem");

			$(window).scroll(function() {
				let scrollTop = $(window).scrollTop();
				$elem.removeClass("on-screen-center");

				for (let blockID in elemsYaxisRange) {
					let yMin = elemsYaxisRange[blockID][0],
							yMax = elemsYaxisRange[blockID][1];

					if (scrollTop >= yMin && scrollTop <= yMax) {
						_debug(blockID);
						$("#" + blockID).addClass("on-screen-center");
					}
				}
			});
		}
	}

	/**
	 * Main
	 */
	var _nmeData = {
		get: {
			field: function(selector) {
				let $field = $(selector);

				if ($field.length) {
					let dataString = $field.val();

					if (_isJsonString(dataString)) {
						_data = JSON.parse(dataString);
						_debug(_data, "Source data");
					}
				}
			}
		},
		update: function() {
			_debug(_data);
			let data = JSON.stringify(_data, undefined, 4);
			$(_dataLoadSource).val(data);
			$.nmEditor.instance.data = _data;
		},
		sort: function(order, section) {
			if (_data["sections"][section]) {
				var tempSectionBlocksData = _data["sections"][section]["blocks"];
				_data["sections"][section]["blocks"] = {};

				for (let i = 0; i < order.length; i++) {
					let blockID = order[i];
					_data["sections"][section]["blocks"][blockID] = {};
					tempSectionBlocksData[blockID]["weight"] = i;
					_data["sections"][section]["blocks"][blockID] = tempSectionBlocksData[blockID];
				}
			}

			_nmeData.update();

		}
	};

	var _nmeElem = {
		add: function(type, mode, source) {
			let output = "",
					elemType = typeof type !== undefined ? type : "",
					elemMode = typeof mode !== undefined ? mode : "view",
					targetID = typeof source !== undefined ? source : null;

			switch (elemType) {
				case "image":
					if (targetID) {
						output =  "<img src='" + _data.blocks[targetID].data + "' alt=''>";
					}
					else {
						output = "<img src='https://unsplash.it/1360/600?image=972' alt=''>";
					}
					break;

				case "title":
					if (elemMode == "edit") {
						if (targetID) {
							output =  "";
						}
						else {
							output = "";
						}
					}
					else {
						if (targetID) {
							output =  "";
						}
						else {
							output = "";
						}
					}
					break;

				case "paragraph":
					break;

				case "button":
					break;
			}

			return output;
		}
	};

	var _nmeBlock = {
		add: function(data, mode, target, method) {
			let block = !_objIsEmpty(data) ? data : null,
					addMethod = typeof method !== "undefined" ? method : "append";

			if (block && block.type) {
				let output = "",
						blockMode = typeof mode !== "undefined" ? mode : "view",
						blockType = block.type,
						blockSection = block.section,
						blockID = block.id ? block.id : blockType + "-" + _renderID(),
						$target = typeof target !== "undefined" ? $(target) : "",
						disallowSortType = ["header", "footer"];

				// If the mode is 'edit', render nmeBlock control buttons.
				if (blockMode == "edit") {
					//_loadTemplate("block--edit", "block", "default", targetContainer);
					//_nmeBlockControl.render(blockType);
					if ($target.length) {
						let blockContent = _tpl.block[blockType],
								blockEditContent = _tpl.block.edit,
								blockSortable = "true";

						if (disallowSortType.includes(blockType)) {
							blockSortable = "false";
						}

						blockEditContent = blockEditContent.replace(/{nmeBlockID}/g, blockID);
						blockEditContent = blockEditContent.replace("{nmeBlockType}", blockType);
						blockEditContent = blockEditContent.replace("{nmeBlockSection}", blockSection);
						blockEditContent = blockEditContent.replace("{nmeBlockContent}", blockContent);
						blockEditContent = blockEditContent.replace("{nmeBlockSortable}", blockSortable);

						output = blockEditContent;

						switch (addMethod) {
							case "append":
								$target.append(output);
								break;

							case "before":
								$target.before(output);
								break;

							case "after":
								$target.after(output);
								break;
						}

						let $nmeb = $(".nme-block[data-id='" + blockID + "']").find(".nmeb"),
								$nmebInner = $nmeb.find(".nmeb-inner"),
								$nmebContentContainer = $nmeb.find(".nmeb-content-container"),
								$nmebContent = $nmeb.find(".nmeb-content"),
								$nmebElem = $nmeb.find(".nme-elem");

						if ($nmeb.length) {
							$nmeb.attr("data-id", blockID);

							// Set styles
							for (let styleTarget in block.styles) {
								let $styleTarget = $nmeb.find("[data-settings-target='" + styleTarget + "']");

								for (let styleProperty in block.styles[styleTarget]) {
									let styleValue = block.styles[styleTarget][styleProperty];
									$styleTarget.css(styleProperty, styleValue);
								}
							}

							if ($nmebElem.length) {
								$nmebElem.attr({
									"data-id": blockID,
									"data-section": blockSection
								});

								switch (blockType) {
									case "title":
										$nmebElem.addClass("nme-editable");
										$nmebElem.attr({
											"data-type": "text"
										});
										$nmebElem.html(block.data);
										break;

									case "paragraph":
										$nmebElem.addClass("nme-editable");
										$nmebElem.attr({
											"data-type": "xquill",
											"data-placeholder": "請輸入段落文字...",
											"data-title": "Enter comments"
										});
										$nmebElem.html(block.data.html);
										break;

									case "button":
										$nmebElem.html(block.data);
										$nmebElem.replaceWith(function(){
											return this.outerHTML.replace("<a", "<div").replace("</a", "</div");
										});
										$nmebElem = $nmeb.find(".nme-elem");
										$nmebElem.addClass("nme-editable");
										$nmebElem.attr({
											"data-type": "text"
										});
										break;

									case "image":
										$nmebElem.attr({
											"src": block.data.url,
											"width": block.data.width,
											"height": block.data.height,
											"alt": block.data.fileName
										});
										break;

									default:
										$nmebElem.html(block.data);
										break;
								}

								_editable();
								_colorable();
							}
						}
					}
				}
			}
		},
		clone: function(data, target) {
			let cloneData = !_objIsEmpty(data) ? data : null,
					$target = typeof target !== undefined ? $(target) : "";

			if ($target.length) {
				_nmeBlock.add(cloneData, "edit", target, "after");
			}
		},
		delete: function(data) {
			let block = !_objIsEmpty(data) ? data : null,
					blockID = block.id,
					section = block.section,
					$block = $(".nme-block[data-id='" + blockID + "']");

			if ($block.length) {
				// Delete DOM
				$block.remove();

				// Remove block data
				if (_data["sections"][section]["blocks"][blockID]) {
					delete _data["sections"][section]["blocks"][blockID];
					_nmeData.update();
				}
			}
		}
	};

	var _nmeMain = function() {
		/*
		let mailTpl = _loadTemplate('mail--col-1-full-width', 'mail');
		console.log(mailTpl);
		*/

		let tplCurrentLoadItems = 0,
				tplTotal = _getLength(_tpl);

		for (let tplLevel in _tpl) {
			for (let tplName in _tpl[tplLevel]) {
				let tplPath = "/sites/all/modules/civicrm/packages/mailingEditor/templates/" +
				tplLevel + "/" + tplLevel + "--" + tplName + ".html";

				$.ajax({
					url: tplPath,
					method: "GET",
					async: true,
					success: function(response) {
						tplCurrentLoadItems++;
						_tpl[tplLevel][tplName] = response;
						//_debug(_tpl);

						if (tplCurrentLoadItems == tplTotal) {
							if (!$(_main).length) {
								let mailTpl = _tpl["mail"]["col-1-full-width"];

								$(_container).append("<div class='" + NME_MAIN + "'><div class='" + INNER_CLASS + "'></div></div>");
								$(_main).children(".inner").append(mailTpl);

								if (!_objIsEmpty(_data) && _data.sections && _data.settings) {
									for (let section in _data.sections) {
										if (!_sectionIsEmpty(section)) {
											let blocksData = _data.sections[section].blocks,
													sectionID = "nme-mail-" + section,
													sectionInner = "#" + sectionID + " .nme-mail-inner",
													blocksContainer = sectionInner + " .nme-blocks";

											$(sectionInner).append("<div id='" + sectionID + "-blocks' class='nme-blocks' data-section='" + section + "'></div>");
											for (let blockID in _data.sections[section].blocks) {
												let blockData = blocksData[blockID];
												_nmeBlock.add(blockData, "edit", blocksContainer);
												_nmeBlockControl.render(blockID, blockData.type);
											}

											_editable();
											_sortable();
											_colorable();
											_nmeBlockControl.init();
											_nmePanels();
											_onScreenCenterElem(".nme-block");
										}
									}
								}
							}
						}
					},
					error: function(xhr, status, error) {
						_debug("xhr:" + xhr + '\n' + "status:" + status + '\n' + "error:" + error);
						return false;
					}
				});
			};
		}
	};

	/* Main Help function */
	var _checkNmeInstance = function() {
		if(!$.nmEditor.instance) {
			_nme = new nmEditor();
			_nme.init();
			$.nmEditor.instance = _nme;
			$.nmEditor.instance.data = _data;
		}
	};

	var _editable = function() {
		let $editableElems = $(".nme-editable:not(.editable-initialized)");

		if (typeof $.fn.editable !== "undefined" && $editableElems.length) {
			$.fn.editable.defaults.mode = 'inline';

			$editableElems.each(function() {
				let $editableElem = $(this);

				$editableElem.editable();
				/*
				$editableElem.on("shown", function(e, editable) {
					console.log("show");
					console.log(e);
					console.log(editable);
					if (typeof editable != "undefined") {
						// console.log(editable);
						var label = $(this).attr("data-field-label");
						if (editable.value == label && editableDefaultVal.indexOf(label) != -1) {
							editable.input.$input.val("");
						}
					}
				});
				*/

				$editableElem.on("save", function(e, params) {
					console.log("save");
					console.log(params);
					let $this = $(this),
							blockID = $this.data("id"),
							blockType = $this.data("type"),
							section = $this.data("section");

					if (_data["sections"][section]["blocks"][blockID]) {
						console.log(params.newValue);
						if (blockType == "text" || blockType == "button") {
							_data["sections"][section]["blocks"][blockID]["data"] = params.newValue;
						}

						_nmeData.update();
					}

					/*
					if (params.newValue == "") {
						var label = $(this).attr("data-field-label");
						//console.log(label);
						//editable.input.$input.val(label);
						params.newValue = label;
					}
					*/
				});

				$editableElem.addClass("editable-initialized");
			});
		}
	};

	var _sortable = function() {
		$(".nme-blocks").each(function() {
			let nmeBlocksID = $(this).attr("id"),
					nmeBlocksSection = $(this).data("section"),
					nmeBlocks = document.getElementById(nmeBlocksID);

			_sortables[nmeBlocksSection] = {};
			_sortables[nmeBlocksSection]["inst"] = new Sortable(nmeBlocks, {
				animation: 150,
				draggable: ".nme-block[data-sortable='true']",
				dragClass: "handle-drag",
				ghostClass: 'nme-block-dragging',
				onUpdate: function (evt) {
					console.log("sortable onUpdate");
					_sortables[nmeBlocksSection]["order"] = _sortables[nmeBlocksSection]["inst"].toArray();
					_nmeData.sort(_sortables[nmeBlocksSection]["order"], nmeBlocksSection);
					/*
					var $list = $(evt.to);
					var order = sortable.toArray();
					// sortable.sort(order.reverse());
					console.log(order);
					_nmeData.sort(order);
					*/
				}
			});

			_sortables[nmeBlocksSection]["order"] = _sortables[nmeBlocksSection]["inst"].toArray();
		});
	};

	var _colorable = function() {
		var pickrInit = function(elemID) {
			let targetSelector = "#" + elemID,
					$target = $(targetSelector);

			if ($target.length) {
				const pickr = new Pickr({
					el: targetSelector,
					theme: 'nano',
					lockOpacity: true,
					useAsButton: true,
					swatches: [
						'#F44336',
						'#E91E63',
						'#9C27B0',
						'#673AB7',
						'#3F51B5',
						'#2196F3',
						'#03A9F4',
						'#00BCD4',
						'#009688',
						'#4CAF50',
						'#8BC34A',
						'#CDDC39',
						'#FFEB3B',
						'#FFC107',
						'#FF9800',
						'#FF5722',
						'#795548',
						'#9E9E9E',
						'#607D8B',
						'#000000',
						'#FFFFFF'
					],
					components: {
						// Main components
						preview: true,
						opacity: true,
						hue: true,

						// Input / output Options
						interaction: {
							hex: true,
							input: true
						}
					}
				});

				pickr.on("init", function(instance) {
					$(pickr._root.button).addClass("pickr-initialized");
				});

				pickr.on("change", function(color, instance) {
					let $button = $(pickr._root.button),
							$block = $button.closest(".nme-block"),
							blockType = $block.data("type");
					/*
					console.log('change');
					console.log(color);
					console.log(instance);
					*/
					pickr.applyColor();
				});

				pickr.on("save", function(color, instance) {
					let $button = $(pickr._root.button),
							$block = $button.closest(".nme-block"),
							blockID = $block.data("id"),
							section = $block.data("section"),
							blockType = $block.data("type");
					/*
					console.log('save');
					console.log(color);
					console.log(instance);
					*/

					if (blockType == "button") {
						let bgColor = color.toHEXA().toString();
						$block.find("[data-settings-target='elemContainer']").css("background-color", bgColor);
						_data["sections"][section]["blocks"][blockID]["styles"]["elemContainer"]["background-color"] = bgColor;
						_nmeData.update();
					}
				});
			}
		};

		$(".handle-style:not(.pickr-initialized)").each(function() {
			let pickrID = this.id;
			pickrInit(pickrID);
			_pickrs.push(pickrID);
			// console.log(_pickrs); // has a issue
		});
	};

	var _sectionIsEmpty = function(section) {
		if (_data.sections[section] && !_objIsEmpty(_data.sections[section].blocks)) {
			let $section = $("#nme-mail-" + section);
			if ($section.length && $section.find(".nme-mail-inner")) {
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return true;
		}
	}

	var _loadTemplate = function(name, level, target, mode, data) {
		let tplName = typeof name !== undefined ? name : "",
				tplLevel = typeof level !== undefined ? level : "",
				tplTarget = typeof target !== undefined ? target : "",
				tplLoadMode = typeof mode !== undefined && mode ? mode : "storage",
				tplData = !_objIsEmpty(data) ? data : null,
				tplAlreadyStored = false,
				tplOutput = "";

		if (tplName && tplLevel) {
			let $tplTarget = $(tplTarget);

			function loadTemplateFinalTask() {
				tplOutput = _tpl[tplLevel][tplName];

				if ($tplTarget.length) {
					if (tplLoadMode == "direct") {
						$tplTarget.html(tplOutput);
					}
				}
				else {
					if (tplLoadMode == "storage") {
						return tplOutput;
					}
				}
			}

			if (_tpl[tplLevel][tplName]) {
				tplAlreadyStored = true;
			}

			if (tplAlreadyStored) {
				loadTemplateFinalTask();
			}
			else {
				let tplPath = "/sites/all/modules/civicrm/packages/mailingEditor/templates/" +
						tplLevel + "/" + tplLevel + "--" + tplName + ".html";

				$.ajax({
					url: tplPath,
					method: "GET",
					async: true,
					success: function(response) {
						_tpl[tplLevel][tplName] = response;
						loadTemplateFinalTask();
						_debug(_tpl);
					},
					error: function(xhr, status, error) {
						_debug("xhr:" + xhr + '\n' + "status:" + status + '\n' + "error:" + error);
						return false;
					}
				});
			}

			/*	
					if (tplLoadMode == "direct") {
						$tplTarget.load(tplPath, function(response, status, xhr) {
							if (status == "success") {
								_tpl[tplLevel][tplName] = response;

								if (tplLevel == "mail") {
									if (!_objIsEmpty(_data) && _data.sections && _data.settings) {
										for (let section in _data.sections) {
											if (!_sectionIsEmpty(section)) {
												let blocksContainer = "#nme-mail-" + section + " .nme-mail-inner";
												console.log(blocksContainer);
												for (let block in _data.sections[section].blocks) {
													console.log(block);
													//_nmeBlock.add(block, "edit", blocksContainer);
												}
											}
										}						
									}
									else {
									}
								}
								// _debug(_tpl);
							}

							if (status == "error" && _debugMode) {
								let msg = "Sorry but there was an error: \n" + xhr.status + " " + xhr.statusText;
								_debug(msg);
							}
						});
						*/
		}
	};

	var _loadTemplates = function() {

	}

	var _nmeBlockControl = {
		render: function(id, type) {
			let output = "",
					blockID = typeof id !== "undefined" ? id : "",
					blockType = typeof type !== "undefined" ? type : "",
					$block = $(".nme-block[data-id='" + blockID + "']");

			// nmeBlock control - extended actions
			if ($block.length && _editActions.extended[blockType]) {
				let extendedActions = _editActions.extended[blockType];

				for (let k in extendedActions) {
					let action = extendedActions[k];
					//output += "<span class='handle-" + action + " handle-btn' data-type='" + action + "'><i class='zmdi " + _controlIconClass[action] + "'></i></span>";
					output += "<button id='" + blockID + "-handle-" + action + "' type='button' class='handle-" + action + " handle-btn' data-type='" + action + "'><i class='zmdi " + _controlIconClass[action] + "'></i></button>";
				}

				$block.find(".nme-block-actions").prepend(output);
			}
		},
		init: function() {
			$(".nme-block-control").on("click", ".handle-btn", function(event) {
				event.preventDefault();

				let $handle = $(this),
						handleType = $handle.data("type"),
						$block = $handle.closest(".nme-block"),
						blockID = $block.data("id"),
						blockDomID = $block.attr("id"),
						blockType = $block.data("type"),
						section = $block.data("section"),
						sectionID = "nme-mail-" + section,
						sectionInner = "#" + sectionID + " .nme-mail-inner",
						blocksContainer = sectionInner + " .nme-blocks",
						$elem = $block.find(".nme-elem"),
						$elemContainer = $elem.parent(".nmeb-content"),
						blockSortInst = _sortables[section]["inst"],
						blocksSortOrder = _sortables[section]["order"];

				// $block.addClass(ACTIVE_CLASS);
				// Block control: move group
				// prev
				if (handleType == "prev") {
					let $prevBlock = $block.prev(".nme-block"),
							prevBlockID = $prevBlock.length ? $prevBlock.attr("data-id") : "";

					if (prevBlockID && $prevBlock.data("sortable")) {
						blocksSortOrder = _swapArrayVal(blocksSortOrder, blockID, prevBlockID);
						_sortables[section]["order"] = blocksSortOrder;
						blockSortInst.sort(blocksSortOrder);
						_nmeData.sort(blocksSortOrder, section);
					}
				}

				// next
				if (handleType == "next") {
					let $nextBlock = $block.next(".nme-block"),
							nextBlockID = $nextBlock.length ? $nextBlock.attr("data-id") : "";

					if (nextBlockID && $nextBlock.data("sortable")) {
						blocksSortOrder = _swapArrayVal(blocksSortOrder, blockID, nextBlockID);
						_sortables[section]["order"] = blocksSortOrder;
						blockSortInst.sort(blocksSortOrder);
						_nmeData.sort(blocksSortOrder, section);
					}
				}

				// Block control: actions group
				// clone
				if (handleType == "clone") {
					let cloneData = Object.assign({}, _data["sections"][section]["blocks"][blockID]),
							cloneBlockID = blockType + "-" + _renderID();

					cloneData.id = cloneBlockID;
					_data["sections"][section]["blocks"][cloneBlockID] = cloneData;
					_nmeBlock.clone(cloneData, "#" + blockDomID);
					_sortables[section]["order"] = _sortables[section]["inst"].toArray();
					_nmeData.sort(_sortables[section]["order"], section);
				}

				// delete
				if (handleType == "delete") {
					let deleteData = Object.assign({}, _data["sections"][section]["blocks"][blockID]);
					_nmeBlock.delete(deleteData);
				}

				// image
				if (handleType == "image") {
					window.nmeImce.targetID = blockID;
					window.nmeImce.targetSection = section;
					var win = window.open('/imce&app=nme|sendto@nmeImce.afterInsert', 'nme_imce', 'width=640, height=480');
				}

				// link
				if (handleType == "link") {
					let	editBlockID = "nme-edit-" + blockDomID,
					editItemID = "nme-edit-" + handleType + "-item-" + blockDomID;

					if ($elemContainer.length && !$elemContainer.find(".nme-edit").length) {
						let editItemVal = $elem.data("link") ? $elem.data("link") : "";
						let nmeEdit = "<div id='" + editBlockID + "' class='nme-edit' data-target='" + blockDomID + "'>" +
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

						// 將編輯表單放入對應的元件內
						$block.addClass(EDIT_CLASS);
						$elem.addClass(EDIT_CLASS);
						$elemContainer.addClass(EDIT_CLASS).append(nmeEdit);
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
									$elem.attr("data-link", editItemVal);

									if (_data["sections"][section]["blocks"][blockID]) {
										_data["sections"][section]["blocks"][blockID]["link"] = editItemVal;
										_nmeData.update();
									}
								}
							}

							$editBlock.remove();
							$block.removeClass(EDIT_CLASS);
							$elem.removeClass(EDIT_CLASS);
							$elemContainer.removeClass(EDIT_CLASS);
						});
					}
				}
			});
		}
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
          window.console.log("===== " + label + " =====");
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
	window.nmeImce = {
		targetID: "",
		targetSection: "",
		afterInsert: function(file, imceWindow) {
			// _debug(file);
			// _debug(imceWindow); // TypeError: cyclic object value
			// console.log(file);
			// console.log(imceWindow);
			let blockID = window.nmeImce.targetID,
					section = window.nmeImce.targetSection,
					fileURL = file.url,
					fileName = file.name;

			if (blockID) {
				let $target = $(".nme-block[data-id='" + blockID + "']");

				if ($target.length) {
					let $img = $target.find(".nmee-image");

					if ($img.length) {
						// Update dom properties of the target image
						$img.attr({
							"src": file.url,
							"alt": file.name
						});

						// Update json data of the target image
						if (_data["sections"][section]["blocks"][blockID]) {
							_data["sections"][section]["blocks"][blockID]["data"]["url"] = fileURL;
							_data["sections"][section]["blocks"][blockID]["data"]["fileName"] = fileName;
							_nmeData.update();
						}
					}
				}
			}

			imceWindow.close();
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
	nmEditor.prototype = {
		constructor: nmEditor,
		data: {},
		init: function() {
      _nmEditorInit();

			if (_dataLoadMode == "field") {
				_dataLoadSource = _nmeOptions.dataLoadSource;
				_nmeData.get.field(_dataLoadSource);
			}
		},
		render: function() {
			_nmeMain();
			/*
			_editable();
			_sortable();
			_nmeBlockControl.init();
			_nmePanels();
			*/
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
    
    if (_nmeOptions.debugMode && _qs.debug) {
      _debugMode = _qs.debug;
    }

		_container = this.selector;
		_debug(_container);
    _checkNmeInstance();

		return this;
	};
	 
	// Plugin defaults options
	$.fn.nmEditor.defaults = {
		dataLoadMode: "dom",
		dataLoadSource: "#mailing_content_data",
		debugMode: false
	};
}(jQuery));