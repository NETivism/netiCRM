/*!
 * netiCRM jQuery Migrate
 * Copyright OpenJS Foundation and other contributors
 */
( function( factory ) {
	"use strict";

	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( [ "jquery" ], function( jQuery ) {
			return factory( jQuery, window );
		} );
	} else if ( typeof module === "object" && module.exports ) {

		// Node/CommonJS
		// eslint-disable-next-line no-undef
		module.exports = factory( require( "jquery" ), window );
	} else {

		// Browser globals
		factory( jQuery, window );
	}
} )( function( jQuery, window ) {
"use strict";

jQuery.neticrmMigrateVersion = "3";

// Set to true to prevent console output;
jQuery.neticrmMigrateMute = true;

// Returns 0 if v1 == v2, -1 if v1 < v2, 1 if v1 > v2
function compareVersions( v1, v2 ) {
	var i,
		rVersionParts = /^(\d+)\.(\d+)\.(\d+)/,
		v1p = rVersionParts.exec( v1 ) || [ ],
		v2p = rVersionParts.exec( v2 ) || [ ];

	for ( i = 1; i <= 3; i++ ) {
		if ( +v1p[ i ] > +v2p[ i ] ) {
			return 1;
		}
		if ( +v1p[ i ] < +v2p[ i ] ) {
			return -1;
		}
	}
	return 0;
}

function jQueryVersionSince( version ) {
	return compareVersions( jQuery.fn.jquery, version ) >= 0;
}

( function() {

	// Support: IE9 only
	// IE9 only creates console object when dev tools are first opened
	// IE9 console is a host object, callable but doesn't have .apply()
	if ( !window.console || !window.console.log ) {
		return;
	}

	// Need jQuery 3.0.0+ and no older Migrate loaded
	if ( !jQuery || !jQueryVersionSince( "3.0.0" ) ) {
		window.console.log( "netiCRM JQMIGRATE: jQuery 3.0.0+ REQUIRED" );
	}

	if ( jQuery.neticrmMigrateWarnings ) {
		window.console.log( "netiCRM JQMIGRATE: Migrate plugin loaded multiple times" );
	}

	// Show a message on the console so devs know we're active
	if ( !jQuery.neticrmMigrateMute ) {
		window.console.log( "netiCRM JQMIGRATE: Migrate is installed" +
			( jQuery.neticrmMigrateMute ? "" : " with logging active" ) +
			", version " + jQuery.neticrmMigrateVersion );
	}
} )();

var warnedAbout = {};

// By default each warning is only reported once.
jQuery.neticrmMigrateDeduplicateWarnings = true;

// List of warnings already given; public read only
jQuery.neticrmMigrateWarnings = [];

// Set to false to disable traces that appear with warnings
if ( jQuery.neticrmMigrateTrace === undefined ) {
	jQuery.neticrmMigrateTrace = false;
}

// Forget any warnings we've already given; public
jQuery.neticrmMigrateReset = function() {
	warnedAbout = {};
	jQuery.neticrmMigrateWarnings.length = 0;
};

function migrateWarn( msg ) {
  var console = window.console;
	if ( !jQuery.neticrmMigrateDeduplicateWarnings || !warnedAbout[ msg ] ) {
		warnedAbout[ msg ] = true;
		jQuery.neticrmMigrateWarnings.push( msg );
		if ( console && console.warn && !jQuery.neticrmMigrateMute ) {
			console.warn( "netiCRM JQMIGRATE: " + msg );
			if ( jQuery.neticrmMigrateTrace && console.trace ) {
				console.trace();
			}
		}
  }
}

function migrateWarnProp( obj, prop, value, msg ) {
	Object.defineProperty( obj, prop, {
		configurable: true,
		enumerable: true,
		get: function() {
			migrateWarn( msg );
			return value;
		},
		set: function( newValue ) {
			migrateWarn( msg );
			value = newValue;
		}
	} );
}

function migrateWarnFunc( obj, prop, newFunc, msg ) {
	obj[ prop ] = function() {
		migrateWarn( msg );
		return newFunc.apply( this, arguments );
	};
}

if ( window.document.compatMode === "BackCompat" && !jQuery.neticrmMigrateMute ) {
	// JQuery has never supported or tested Quirks Mode
	migrateWarn( "netiCRM JQMIGRATE: jQuery is not compatible with Quirks Mode" );
}


/**
 * Detect 'jQuery.browser'
 * Code source: jquery-migrate-1.4.1.js
 */

var matched, browser;

jQuery.neticrmUaMatch = function( ua ) {
	ua = ua.toLowerCase();

	var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
		/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
		/(msie) ([\w.]+)/.exec( ua ) ||
		ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
		[];

	return {
		browser: match[ 1 ] || "",
		version: match[ 2 ] || "0"
	};
};

// Don't clobber any existing jQuery.browser in case it's different
if ( !jQuery.browser ) {
	matched = jQuery.neticrmUaMatch( navigator.userAgent );
	browser = {};

	if ( matched.browser ) {
		browser[ matched.browser ] = true;
		browser.version = matched.version;
	}

	// Chrome is Webkit, but Webkit is also Safari.
	if ( browser.chrome ) {
		browser.webkit = true;
	} else if ( browser.webkit ) {
		browser.safari = true;
	}

	jQuery.browser = browser;
}

// Warn if the code tries to get jQuery.browser
migrateWarnProp( jQuery, "browser", jQuery.browser, "jQuery.browser is deprecated" );


/**
 * Detect 'jQuery.fn.toggle()'
 * Code source: jquery-migrate-1.4.1.js
 */

var oldToggle = jQuery.fn.toggle;

jQuery.fn.toggle = function( fn, fn2 ) {

	// Don't mess with animation or css toggles
	// jQuery.isFunction is removed in 4.0, so do not depend on it here
	if ( typeof fn !== "function" || typeof fn2 !== "function" ) {
		return oldToggle.apply( this, arguments );
	}
	migrateWarn("jQuery.fn.toggle(handler, handler...) is deprecated");

	// Save reference to arguments for access in closure
	var args = arguments,
		guid = fn.guid || jQuery.guid++,
		i = 0,
		toggler = function( event ) {
			// Figure out which function to execute
			var lastToggle = ( jQuery._data( this, "lastToggle" + fn.guid ) || 0 ) % i;
			jQuery._data( this, "lastToggle" + fn.guid, lastToggle + 1 );

			// Make sure that clicks stop
			event.preventDefault();

			// and execute the function
			return args[ lastToggle ].apply( this, arguments ) || false;
		};

	// link all the functions, so any of them can unbind this click handler
	toggler.guid = guid;
	while ( i < args.length ) {
		args[ i++ ].guid = guid;
	}

	return this.click( toggler );
};


/**
 * Detect 'jQuery.fn.live()'
 * Code source: jquery-migrate-1.4.1.js
 */

var oldLive = jQuery.fn.live;

jQuery.fn.live = function( types, data, fn ) {
	migrateWarn("jQuery.fn.live() is deprecated");
	if ( oldLive ) {
		return oldLive.apply( this, arguments );
	}
	jQuery( this.context ).on( types, this.selector, data, fn );
	return this;
};


/**
 * Detect 'jQuery.fn.attr()'
 * Code source: jquery-migrate-1.4.1.js
 */

var attrFn = jQuery( "<input/>", { size: 1 } ).attr("size") && jQuery.attrFn,
	oldAttr = jQuery.attr,
	valueAttrGet = jQuery.attrHooks.value && jQuery.attrHooks.value.get ||
		function() { return null; },
	valueAttrSet = jQuery.attrHooks.value && jQuery.attrHooks.value.set ||
		function() { return undefined; },
	rnoType = /^(?:input|button)$/i,
	rnoAttrNodeType = /^[238]$/,
	rboolean = /^(?:autofocus|autoplay|async|checked|controls|defer|disabled|hidden|loop|multiple|open|readonly|required|scoped|selected)$/i,
	ruseDefault = /^(?:checked|selected)$/i;

// Named so the jQuery 4.0 block below can install the same hook
var boolHook = {
	get: function( elem, name ) {
		// Align boolean attributes with corresponding properties
		// Fall back to attribute presence where some booleans are not supported
		var attrNode,
			property = jQuery.prop( elem, name );
		return property === true || typeof property !== "boolean" &&
			( attrNode = elem.getAttributeNode(name) ) && attrNode.nodeValue !== false ?

			name.toLowerCase() :
			undefined;
	},
	set: function( elem, value, name ) {
		var propName;
		if ( value === false ) {
			// Remove boolean attributes when set to false
			jQuery.removeAttr( elem, name );
		} else {
			// value is true since we know at this point it's type boolean and not false
			// Set boolean attributes to the same name and set the DOM property
			propName = jQuery.propFix[ name ] || name;
			if ( propName in elem ) {
				// Only set the IDL specifically if it already exists on the element
				elem[ propName ] = true;
			}

			elem.setAttribute( name, name.toLowerCase() );
		}
		return name;
	}
};

// jQuery.attrFn
migrateWarnProp( jQuery, "attrFn", attrFn || {}, "jQuery.attrFn is deprecated" );

jQuery.attr = function( elem, name, value, pass ) {
	var lowerName = name.toLowerCase(),
		nType = elem && elem.nodeType;

	if ( pass ) {
		// Since pass is used internally, we only warn for new jQuery
		// versions where there isn't a pass arg in the formal params
		if ( oldAttr.length < 4 ) {
			migrateWarn("jQuery.fn.attr( props, pass ) is deprecated");
		}
		if ( elem && !rnoAttrNodeType.test( nType ) &&
			(attrFn ? name in attrFn : typeof jQuery.fn[ name ] === "function") ) {
			return jQuery( elem )[ name ]( value );
		}
	}

	// Warn if user tries to set `type`, since it breaks on IE 6/7/8; by checking
	// for disconnected elements we don't warn on $( "<button>", { type: "button" } ).
	if ( name === "type" && value !== undefined && rnoType.test( elem.nodeName ) && elem.parentNode ) {
		migrateWarn("Can't change the 'type' of an input or button in IE 6/7/8");
	}

	// Restore boolHook for boolean property/attribute synchronization
	if ( !jQuery.attrHooks[ lowerName ] && rboolean.test( lowerName ) ) {
		jQuery.attrHooks[ lowerName ] = boolHook;

		// Warn only for attributes that can remain distinct from their properties post-1.9
		if ( ruseDefault.test( lowerName ) ) {
			migrateWarn( "jQuery.fn.attr('" + lowerName + "') might use property instead of attribute" );
		}
	}

	return oldAttr.call( jQuery, elem, name, value );
};


/**
 * Detect 'jQuery.fn.size()'
 * Code source: jquery-migrate-3.3.2.js
 */

// The number of elements contained in the matched element set
migrateWarnFunc( jQuery.fn, "size", function() {
	return this.length;
},
"jQuery.fn.size() is deprecated and removed; use the .length property" );


/**
 * Detect 'jQuery.fn.load()', 'jQuery.fn.unload()', 'jQuery.fn.error()'
 * Code source: jquery-migrate-3.3.2.js
 */

var oldLoad = jQuery.fn.load;

jQuery.each( [ "load", "unload", "error" ], function( _, name ) {
	jQuery.fn[ name ] = function() {
		var args = Array.prototype.slice.call( arguments, 0 );

		// If this is an ajax load() the first arg should be the string URL;
		// technically this could also be the "Anything" arg of the event .load()
		// which just goes to show why this dumb signature has been deprecated!
		// jQuery custom builds that exclude the Ajax module justifiably die here.
		if ( name === "load" && typeof args[ 0 ] === "string" ) {
			return oldLoad.apply( this, args );
		}

		migrateWarn( "jQuery.fn." + name + "() is deprecated" );

		args.splice( 0, 0, name );
		if ( arguments.length ) {
			return this.on.apply( this, args );
		}

		// Use .triggerHandler here because:
		// - load and unload events don't need to bubble, only applied to window or image
		// - error event should not bubble to window, although it does pre-1.7
		// See http://bugs.jquery.com/ticket/11820
		this.triggerHandler.apply( this, args );
		return this;
	};
} );


/**
 * Restore APIs removed in jQuery 4.0
 * Migrate 4 only warns about these. Skip on 3.6, where Migrate 3.3.2 or jQuery itself
 * already provides them; applying twice would e.g. fire the 'ready' event twice.
 */
if ( jQueryVersionSince( "4.0.0" ) ) {

	/**
	 * Detect 'ready' event
	 * Code source: jquery-migrate-3.3.2.js
	 */

	// Trigger "ready" event only once, on document ready
	jQuery( function() {
		jQuery( window.document ).triggerHandler( "ready" );
	} );

	jQuery.event.special.ready = {
		setup: function() {
			if ( this === window.document ) {
				migrateWarn( "'ready' event is deprecated" );
			}
		}
	};


	/**
	 * Detect 'jQuery.event.props', 'jQuery.event.fixHooks'
	 * Code source: jquery-migrate-3.3.2.js
	 */

	var originalFix = jQuery.event.fix;

	// Own objects instead of Migrate 4's empty placeholders, so this does not depend on Migrate 4
	jQuery.event.props = [];
	jQuery.event.fixHooks = {};

	jQuery.event.fix = function( originalEvent ) {
		var event,
			type = originalEvent.type,
			fixHook = this.fixHooks[ type ],
			props = jQuery.event.props;

		if ( props.length ) {
			migrateWarn( "jQuery.event.props are deprecated and removed: " + props.join() );
			while ( props.length ) {
				jQuery.event.addProp( props.pop() );
			}
		}

		if ( fixHook && !fixHook._migrated_ ) {
			fixHook._migrated_ = true;
			migrateWarn( "jQuery.event.fixHooks are deprecated and removed: " + type );
			if ( ( props = fixHook.props ) && props.length ) {
				while ( props.length ) {
					jQuery.event.addProp( props.pop() );
				}
			}
		}

		event = originalFix.call( this, originalEvent );

		return fixHook && fixHook.filter ? fixHook.filter( event, originalEvent ) : event;
	};


	/**
	 * Detect 'jQuery.event.global'
	 * Code source: jquery-3.6.0.js (jQuery.event.add)
	 */

	var oldEventAdd = jQuery.event.add,
		eventGlobal = {},
		rnothtmlwhite = /[^\x20\t\r\n\f]+/g,
		rtypenamespace = /^([^.]*)(?:\.(.+)|)/;

	// Replaces Migrate 4's getter. jQuery.event.add writes to eventGlobal directly,
	// so only reads from other code are recorded as warnings.
	migrateWarnProp( jQuery.event, "global", eventGlobal, "jQuery.event.global is removed" );

	jQuery.event.add = function( elem, types, handler, data, selector ) {
		var t, tmp, type, special,
			ret = oldEventAdd.apply( this, arguments );

		// Same as jQuery's acceptData(): other nodes never get handlers
		if ( !( elem.nodeType === 1 || elem.nodeType === 9 || !( +elem.nodeType ) ) ) {
			return ret;
		}
		if ( handler && handler.handler ) {
			selector = handler.selector;
		}

		types = ( types || "" ).match( rnothtmlwhite ) || [ "" ];
		for ( t = 0; t < types.length; t++ ) {
			tmp = rtypenamespace.exec( types[ t ] ) || [];
			type = tmp[ 1 ];
			if ( !type ) {
				continue;
			}
			special = jQuery.event.special[ type ] || {};
			type = ( selector ? special.delegateType : special.bindType ) || type;
			eventGlobal[ type ] = true;
		}
		return ret;
	};


	/**
	 * Simulate focusin/focusout from captured focus/blur
	 * Code source: jquery-3.6.0.js
	 */

	// 3.6 installs this when window has no onfocusin, which is every current browser.
	// jquery.validate checks special.focusin when it loads and otherwise adds its own shim,
	// which calls the removed jQuery.event.handle, so this must be defined before it loads.
	jQuery.each( { focus: "focusin", blur: "focusout" }, function( orig, fix ) {
		var handler = function( event ) {
			jQuery.event.simulate( fix, event.target, jQuery.event.fix( event ) );
		};

		jQuery.event.special[ fix ] = {
			setup: function() {
				var doc = this.ownerDocument || this.document || this,
					attaches = jQuery._data( doc, fix );

				if ( !attaches ) {
					doc.addEventListener( orig, handler, true );
				}
				jQuery._data( doc, fix, ( attaches || 0 ) + 1 );
			},
			teardown: function() {
				var doc = this.ownerDocument || this.document || this,
					attaches = jQuery._data( doc, fix ) - 1;

				if ( !attaches ) {
					doc.removeEventListener( orig, handler, true );
					jQuery._removeData( doc, fix );

				} else {
					jQuery._data( doc, fix, attaches );
				}
			}
		};
	} );


	/**
	 * Detect boolean attributes in '.attr()'
	 * Code source: jquery-migrate-1.4.1.js (boolHook above)
	 */

	// Migrate 4 registers its own hooks for these at load time, so the lazy install
	// in jQuery.attr above never runs on 4.0. Overriding them also silences its
	// 'boolean-attributes' warnings. Same names as rboolean.
	jQuery.each( ( "autofocus autoplay async checked controls defer disabled hidden " +
		"loop multiple open readonly required scoped selected" ).split( " " ), function( _i, name ) {
		jQuery.attrHooks[ name ] = boolHook;
	} );


	/**
	 * Detect boolean attributes in '.removeAttr()'
	 * Code source: jquery-migrate-3.3.2.js
	 */

	var oldRemoveAttr = jQuery.fn.removeAttr,
		// 3.6 jQuery.expr.match.bool, which 4.0 removed
		rbooleanAll = /^(?:checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped)$/i;

	jQuery.fn.removeAttr = function( name ) {
		var self = this;

		// 4.0 accepts a missing name; unlike 3.3.2, do not throw on it
		jQuery.each( String( name ).match( /\S+/g ) || [], function( _i, attr ) {
			if ( rbooleanAll.test( attr ) ) {
				self.prop( attr, false );
			}
		} );

		return oldRemoveAttr.apply( this, arguments );
	};


	/**
	 * Append "px" to unitless numbers in 'jQuery.style'
	 * Code source: jquery-3.6.0.js (jQuery.cssNumber, jQuery.style)
	 */

	var oldStyle = jQuery.style,
		// 3.6 jQuery.cssNumber. Migrate 4's copy warns on every access and is the 3.7.1 list.
		cssNumber36 = {
			animationIterationCount: true, columnCount: true, fillOpacity: true, flexGrow: true,
			flexShrink: true, fontWeight: true, gridArea: true, gridColumn: true, gridColumnEnd: true,
			gridColumnStart: true, gridRow: true, gridRowEnd: true, gridRowStart: true, lineHeight: true,
			opacity: true, order: true, orphans: true, widows: true, zIndex: true, zoom: true
		},
		rmsPrefix = /^-ms-/,
		rdashAlpha = /-([a-z])/g,
		rrelNum = /^[+-]=[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)$/,

		// 4.0 removed jQuery.camelCase; Migrate 4 restores it but warns on every call
		cssCamelCase = function( string ) {
			return string.replace( rmsPrefix, "ms-" ).replace( rdashAlpha, function( _all, letter ) {
				return letter.toUpperCase();
			} );
		};

	// 3.6 checks cssNumber with the name before cssProps aliasing, so a wrapper that
	// maps aliases must be installed before this one and run inside it.
	jQuery.style = function( elem, name, value, extra ) {
		if ( ( typeof value === "number" && value === value ||
				typeof value === "string" && rrelNum.test( value ) ) &&
			typeof name === "string" && name.indexOf( "--" ) !== 0 &&
			!cssNumber36[ cssCamelCase( name ) ] ) {
			value += "px";
		}

		return oldStyle.call( this, elem, name, value, extra );
	};


	/**
	 * Detect 'jQuery( "#" )' and the 'root' argument of 'jQuery.fn.init'
	 * Code source: jquery-migrate-3.3.2.js, jquery-3.6.0.js (jQuery.fn.init)
	 */

	var oldFnInit = jQuery.fn.init,
		rquickExpr = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;

	jQuery.fn.init = function( selector, context, root ) {
		var args = Array.prototype.slice.call( arguments );

		if ( selector === "#" ) {

			// JQuery( "#" ) is a bogus ID selector, but it returned an empty set before jQuery 3.0
			migrateWarn( "jQuery( '#' ) is not a valid selector" );
			args[ 0 ] = [];

		} else if ( root ) {
			if ( typeof selector === "function" ) {
				migrateWarn( "jQuery.fn.init() root argument is removed" );
				return root.ready !== undefined ? root.ready( selector ) : selector( jQuery );
			}

			// 3.6 never used root for HTML or '#id' (getElementById), so those go to the
			// original init; root.find( "#id" ) would miss elements outside root.
			if ( selector && typeof selector === "string" && !context &&
				!( selector[ 0 ] === "<" && selector[ selector.length - 1 ] === ">" &&
					selector.length >= 3 ) &&
				!rquickExpr.test( selector ) ) {
				migrateWarn( "jQuery.fn.init() root argument is removed" );
				return root.find( selector );
			}
		}

		return oldFnInit.apply( this, args );
	};
	jQuery.fn.init.prototype = jQuery.fn;


	/**
	 * Detect unquoted '#' in attribute selectors
	 * Code source: jquery-migrate-3.3.2.js
	 */

	var findProp,
		oldFind = jQuery.find,
		rattrHashTest = /\[(\s*[-\w]+\s*)([~|^$*]?=)\s*([-\w#]*?#[-\w#]*)\s*\]/,
		rattrHashGlob = /\[(\s*[-\w]+\s*)([~|^$*]?=)\s*([-\w#]*?#[-\w#]*)\s*\]/g;

	jQuery.find = function( selector ) {
		var args = Array.prototype.slice.call( arguments );

		if ( typeof selector === "string" && rattrHashTest.test( selector ) ) {

			// The nonstandard and undocumented unquoted-hash was removed in jQuery 1.12.0
			// First see if qS thinks it's a valid selector, if so avoid a false positive
			try {
				window.document.querySelector( selector );
			} catch ( err1 ) {

				// Didn't *look* valid to qSA, warn and try quoting what we think is the value
				selector = selector.replace( rattrHashGlob, function( _, attr, op, value ) {
					return "[" + attr + op + "\"" + value + "\"]";
				} );

				// If the regexp *may* have created an invalid selector, don't update it
				// Note that there may be false alarms if selector uses jQuery extensions
				try {
					window.document.querySelector( selector );
					migrateWarn( "Attribute selector with '#' must be quoted: " + args[ 0 ] );
					args[ 0 ] = selector;
				} catch ( err2 ) {
					migrateWarn( "Attribute selector with '#' was not fixed: " + args[ 0 ] );
				}
			}
		}

		return oldFind.apply( this, args );
	};

	// Copy properties attached to original jQuery.find method (e.g. .matches, .tokenize)
	for ( findProp in oldFind ) {
		if ( Object.prototype.hasOwnProperty.call( oldFind, findProp ) ) {
			jQuery.find[ findProp ] = oldFind[ findProp ];
		}
	}


	/**
	 * Restore 'jQuery.expr.match.bool'
	 * Code source: jquery-3.6.0.js
	 */

	jQuery.expr.match.bool = rbooleanAll;


	/**
	 * Restore 'jQuery.expr.attrHandle' for '.attr()'
	 * Code source: jquery-3.6.0.js (jQuery.attr, Sizzle.attr)
	 */

	var attrBeforeHandle = jQuery.attr;

	// 3.6 has 16 boolean handles here; boolHook above already gives .attr() the same
	// results for those, so this starts empty.
	jQuery.expr.attrHandle = {};

	// Separate from the shared jQuery.attr wrapper above: there, 3.6 would read its boolean
	// handles before boolHook and change results. Same order as 3.6: attrHooks get first.
	// 4.0 reads attributes in non-qSA selectors through jQuery.attr, so they get handles too.
	jQuery.attr = function( elem, name, value ) {
		var lowerName, handle, hooks, ret;

		if ( value === undefined && elem && elem.nodeType === 1 ) {
			lowerName = name.toLowerCase();
			handle = Object.prototype.hasOwnProperty.call( jQuery.expr.attrHandle, lowerName ) &&
				jQuery.expr.attrHandle[ lowerName ];

			if ( handle ) {

				// 3.6 skips hooks per element (jQuery.attr) but passes isXML per document (Sizzle.attr)
				hooks = jQuery.isXMLDoc( elem ) ? undefined : jQuery.attrHooks[ lowerName ];
				if ( !( hooks && "get" in hooks && hooks.get( elem, name ) !== null ) ) {
					migrateWarn( "jQuery.expr.attrHandle is removed" );
					ret = handle( elem, name, jQuery.isXMLDoc( elem.ownerDocument || elem ) );
					if ( ret !== undefined ) {
						return ret == null ? undefined : ret;
					}
				}
			}
		}

		return attrBeforeHandle.apply( this, arguments );
	};


	/**
	 * Restore Sizzle APIs on 'jQuery.find'
	 * Code source: jquery-3.6.0.js (Sizzle)
	 */

	jQuery.each( {
		contains: jQuery.contains,
		escape: jQuery.escapeSelector,
		getText: jQuery.text,
		isXML: jQuery.isXMLDoc,
		selectors: jQuery.expr,
		uniqueSort: jQuery.uniqueSort,

		error: function( msg ) {
			throw new Error( "Syntax error, unrecognized expression: " + msg );
		},

		// Sizzle.attr does not use attrHooks; booleans follow 3.6's boolean attrHandle entries
		attr: function( elem, name ) {
			var lowerName = name.toLowerCase(),
				isXML = jQuery.isXMLDoc( elem.ownerDocument || elem ),
				handle = Object.prototype.hasOwnProperty.call( jQuery.expr.attrHandle, lowerName ) &&
					jQuery.expr.attrHandle[ lowerName ],
				ret = handle ? handle( elem, name, isXML ) : undefined;

			if ( ret !== undefined ) {
				return ret;
			}
			if ( !isXML && rbooleanAll.test( name ) ) {
				return elem.getAttribute( name ) != null ? lowerName : null;
			}
			return elem.getAttribute( name );
		},

		// Only the flag detection from 3.6 Sizzle (setDocument and the one-time checks),
		// computed once for the current document, in the same key order
		support: ( function() {
			var matches,
				support = {},
				hasDuplicate = false,
				document = window.document,
				docElem = document.documentElement,
				expando = "sizzle" + 1 * new Date(),
				rnative = /^[^{]+\{\s*\[native \w/,
				assert = function( fn ) {
					var el = document.createElement( "fieldset" );

					try {
						return !!fn( el );
					} catch ( e ) {
						return false;
					} finally {
						if ( el.parentNode ) {
							el.parentNode.removeChild( el );
						}
					}
				};

			// detectDuplicates records whether this sort compared equal items
			support.sortStable = expando.split( "" ).sort( function( a, b ) {
				if ( a === b ) {
					hasDuplicate = true;
				}
				return 0;
			} ).join( "" ) === expando;
			support.detectDuplicates = !!hasDuplicate;

			support.scope = assert( function( el ) {
				docElem.appendChild( el ).appendChild( document.createElement( "div" ) );
				return typeof el.querySelectorAll !== "undefined" &&
					!el.querySelectorAll( ":scope fieldset div" ).length;
			} );
			support.attributes = assert( function( el ) {
				el.className = "i";
				return !el.getAttribute( "className" );
			} );
			support.getElementsByTagName = assert( function( el ) {
				el.appendChild( document.createComment( "" ) );
				return !el.getElementsByTagName( "*" ).length;
			} );
			support.getElementsByClassName = rnative.test( document.getElementsByClassName );
			support.getById = assert( function( el ) {
				docElem.appendChild( el ).id = expando;
				return !document.getElementsByName || !document.getElementsByName( expando ).length;
			} );
			support.qsa = rnative.test( document.querySelectorAll );
			if ( ( support.matchesSelector = rnative.test( ( matches = docElem.matches ||
				docElem.webkitMatchesSelector ||
				docElem.mozMatchesSelector ||
				docElem.oMatchesSelector ||
				docElem.msMatchesSelector ) ) ) ) {
				assert( function( el ) {
					support.disconnectedMatch = matches.call( el, "*" );
				} );
			}
			support.sortDetached = assert( function( el ) {
				return el.compareDocumentPosition( document.createElement( "fieldset" ) ) & 1;
			} );

			return support;
		} )()
	}, function( name, value ) {
		migrateWarnProp( jQuery.find, name, value, "jQuery.find." + name + " is removed" );
	} );
}


return jQuery;
} );
