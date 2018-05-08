"use strict";
function loadScript(url, callback){
	var script = document.createElement("script")
	script.type = "text/javascript";

	if (script.readyState){  //IE
			script.onreadystatechange = function(){
					if (script.readyState == "loaded" ||
									script.readyState == "complete"){
							script.onreadystatechange = null;
							callback();
					}
			};
	} else {  //Others
			script.onload = function(){
					callback();
			};
	}

	script.src = url;
	document.getElementsByTagName("body")[0].appendChild(script);
}

function getHostNameFromUrl(url) {
  // <summary>Parses the domain/host from a given url.</summary>
  var a = document.createElement("a");
  a.href = url;
  // Handle chrome which will default to domain where script is called from if invalid
  return url.indexOf(a.hostname) != -1 ? a.hostname : '';
}

function referrerInfo() {
	if (typeof navigator.doNotTrack === 'object') {
		var doNotTrack = navigator.doNotTrack;
	}
	else {
		var doNotTrack = null;
	}
	if (!doNotTrack) {
    var referrerInfo = sessionStorage.getItem('referrerInfo');
    if (referrerInfo) {
      referrerInfo = JSON.parse(referrerInfo);
      trackVisit(referrerInfo);
    }
    else {
      var url = window.location.href;
      var referrer = document.referrer;
      inbound.referrer.parse(url, referrer, function (err, visitInfo) {
        // set to sessionStorage because we need to make sure same browser different session have diffrent result
        visitInfo.landing = location.href.replace(location.origin, '');
        sessionStorage.setItem('referrerInfo', JSON.stringify(visitInfo));
        trackVisit(visitInfo);
      });
    }
	}
}

function trackVisit(visitInfo) {
  if (typeof cj === 'undefined') {
    return;
  }

  if (cj("input[type=hidden][name=qfKey]").val()) {
    // required session key
    var object = {};
    object['session_key'] = cj("input[type=hidden][name=qfKey]").val();
    object['landing'] = visitInfo.landing ? visitInfo.landing : '';

    // prepare 
    object['referrer_type'] = visitInfo.referrer.type;
    object['referrer_network'] = '';
    object['referrer_url'] = '';
    switch(object['referrer_type']){
      case 'ad':
        object['referrer_network'] = visitInfo.referrer.network;
        break;
      case 'direct':
        object['referrer_network'] = '';
        break;
      case 'email':
        object['referrer_network'] = visitInfo.referrer.client;
        break;
      case 'internal':
        object['referrer_network'] = '';
        break;
      case 'link':
        object['referrer_network'] = getHostNameFromUrl(visitInfo.referrer.from);
        if (typeof visitInfo.referrer.from !== 'undefined') {
          object['referrer_url'] = visitInfo.referrer.from;
        }
        break;
      case 'local':
        object['referrer_network'] = visitInfo.referrer.site;
        break;
      case 'search':
        object['referrer_network'] = visitInfo.referrer.engine;
        break;
      case 'social':
        object['referrer_network'] = visitInfo.referrer.network;
        break;
      default:
        object['type'] = 'unknown';
        object['referrer_network'] = '';
        break;
    }
    if (typeof visitInfo.campaign !== 'undefined') {
      for(var utmKey in visitInfo.campaign) {
        object[utmKey] = visitInfo.campaign[utmKey];
      }
    }

    // prepare url
    console.log(object);
  }
}

var currentScriptSrc = document.currentScript.src;
currentScriptSrc = currentScriptSrc.replace(/insights\.js.*$/, 'inbound.js');
loadScript(currentScriptSrc, function(){
  referrerInfo();
});

