(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define("PlaceholderModule", [], factory);
	else if(typeof exports === 'object')
		exports["PlaceholderModule"] = factory();
	else
		root["PlaceholderModule"] = factory();
})(typeof self !== 'undefined' ? self : this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Object.defineProperty(exports, "__esModule", { value: true });
var placeholder_blot_1 = __webpack_require__(1);
function getPlaceholderModule(Quill, options) {
    var Parchment = Quill.import('parchment');
    var PlaceholderBlot = placeholder_blot_1.default(Quill);
    PlaceholderBlot.className = options && options.className || 'ql-placeholder-content';
    Quill.register(PlaceholderBlot);

    var PlaceholderModule = function PlaceholderModule(quill, options) {
        var _this = this;

        _classCallCheck(this, PlaceholderModule);

        this.quill = quill;
        this.onTextChange = function (_, oldDelta, source) {
            if (source === Quill.sources.USER) {
                var currrentContents = _this.quill.getContents();
                var delta = currrentContents.diff(oldDelta);
                var shouldRevert = delta.ops.filter(function (op) {
                    return op.insert && op.insert.placeholder && op.insert.placeholder.required;
                }).length;
                if (shouldRevert) {
                    _this.quill.updateContents(delta, Quill.sources.SILENT);
                }
            }
        };
        this.onClick = function (ev) {
            var blot = Parchment.find(ev.target.parentNode);
            if (blot instanceof PlaceholderBlot) {
                var index = _this.quill.getIndex(blot);
                _this.quill.setSelection(index, blot.length(), Quill.sources.USER);
            }
        };
        this.toolbarHandler = function (identifier) {
            var selection = _this.quill.getSelection();
            var placeholder = _this.placeholders.filter(function (pl) {
                return pl.id === identifier;
            })[0];
            if (!placeholder) throw new Error("Missing placeholder for " + identifier);
            _this.quill.deleteText(selection.index, selection.length);
            _this.quill.insertEmbed(selection.index, 'placeholder', placeholder, Quill.sources.USER);
            _this.quill.setSelection(selection.index + 1, 0);
        };
        this.placeholders = options.placeholders;
        PlaceholderBlot.delimiters = options.delimiters || ['{', '}'];
        this.quill.getModule('toolbar').addHandler('placeholder', this.toolbarHandler);
        this.quill.root.addEventListener('click', this.onClick);
        this.quill.on('text-change', this.onTextChange);
    };

    return PlaceholderModule;
}
exports.default = getPlaceholderModule;

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

Object.defineProperty(exports, "__esModule", { value: true });
function getPlaceholderBlot(Quill) {
    var Embed = Quill.import('blots/embed');

    var PlaceholderBlot = function (_Embed) {
        _inherits(PlaceholderBlot, _Embed);

        function PlaceholderBlot() {
            _classCallCheck(this, PlaceholderBlot);

            return _possibleConstructorReturn(this, (PlaceholderBlot.__proto__ || Object.getPrototypeOf(PlaceholderBlot)).apply(this, arguments));
        }

        _createClass(PlaceholderBlot, [{
            key: "length",
            value: function length() {
                return 1;
            }
        }, {
            key: "deleteAt",
            value: function deleteAt(index, length) {
                if (!this.domNode.dataset.required) _get(PlaceholderBlot.prototype.__proto__ || Object.getPrototypeOf(PlaceholderBlot.prototype), "deleteAt", this).call(this, index, length);
            }
        }], [{
            key: "create",
            value: function create(value) {
                var node = _get(PlaceholderBlot.__proto__ || Object.getPrototypeOf(PlaceholderBlot), "create", this).call(this, value);
                if (value.required) node.setAttribute('data-required', 'true');
                node.setAttribute('data-id', value.id);
                node.setAttribute('data-label', value.label);
                node.setAttribute('spellcheck', 'false');
                var delimiters = PlaceholderBlot.delimiters;

                var label = typeof delimiters === 'string' ? "" + delimiters + value.label + delimiters : "" + delimiters[0] + value.label + (delimiters[1] || delimiters[0]);
                var labelNode = document.createTextNode(label);
                if (Quill.version < '1.3') {
                    var wrapper = document.createElement('span');
                    wrapper.setAttribute('contenteditable', 'false');
                    wrapper.appendChild(labelNode);
                    node.appendChild(wrapper);
                } else {
                    node.appendChild(labelNode);
                }
                return node;
            }
        }, {
            key: "value",
            value: function value(domNode) {
                return domNode.dataset;
            }
        }]);

        return PlaceholderBlot;
    }(Embed);

    PlaceholderBlot.blotName = 'placeholder';
    PlaceholderBlot.tagName = 'span';
    return PlaceholderBlot;
}
exports.default = getPlaceholderBlot;

/***/ })
/******/ ]);
});
//# sourceMappingURL=placeholder-module.js.map