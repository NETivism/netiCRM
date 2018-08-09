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
  }
  else {  //Others
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
  var dateTime = Date.now();
  var timestamp = Math.floor(dateTime / 1000);
  var referrerInfo = sessionStorage.getItem('referrerInfo');
  if (referrerInfo) {
    referrerInfo = JSON.parse(referrerInfo);
  }

  // if someone visit this site over 30mins, we need to get referrer again
  if (referrerInfo && typeof referrerInfo.timestamp !== 'undefined' && referrerInfo.timestamp - timestamp < 1800) {
    trackVisit(referrerInfo);
  }
  else {
    var url = window.location.href;
    var referrer = document.referrer;
    inbound.referrer.parse(url, referrer, function (err, visitInfo) {
      // set to sessionStorage because we need to make sure same browser different session have diffrent result
      visitInfo.landing = location.href.replace(location.origin, '');
      visitInfo.timestamp = timestamp;
      if (referrerInfo && typeof referrerInfo.referrer !== 'undefined') {
        if (visitInfo.referrer.type !== 'direct' && visitInfo.referrer.type !== 'internal') {
          sessionStorage.setItem('referrerInfo', JSON.stringify(visitInfo));
        }
        else {
          visitInfo = referrerInfo;
        }
      }
      else {
        sessionStorage.setItem('referrerInfo', JSON.stringify(visitInfo));
      }
      trackVisit(visitInfo);
    });
  }
}

function trackVisit(visitInfo) {
  if (typeof cj === 'undefined') {
    return;
  }
  cj(document).ready(function($){
    var object = {};
    if (location.href.match(/civicrm\/event\/(register|info)/)) {
      object['page_type'] = 'civicrm_event';
    }
    else if (location.href.match(/civicrm\/contribute\/transact/)) {
      object['page_type'] = 'civicrm_contribution_page';
    }
    else if (location.href.match(/civicrm\/profile\/create/)) {
      object['page_type'] = 'civicrm_profile';
    }
    var page_id = location.search.match(/id=(\d+)/);
    if (page_id) {
      object['page_id'] = page_id[1];
    }
    if (!object['page_type'] || !object['page_id']) {
      return;
    }

    // prepare 
    object['landing'] = visitInfo.landing ? visitInfo.landing : '';
    object['referrer_type'] = visitInfo.referrer.type;
    object['referrer_network'] = '';
    object['referrer_url'] = '';
    switch(object['referrer_type']){
      case 'ad':
        object['referrer_network'] = visitInfo.referrer.network;
        break;
      case 'direct':
        // detect if from civimail mailing list
        var queue_id = location.search.match(/civimail_x_q=(\d+)/);
        var url_id = location.search.match(/civimail_x_u=(\d+)/);
        if (queue_id && url_id) {
          object['referrer_type'] = 'email';
          object['referrer_network'] = 'civimail';
          object['referrer_url'] = 'external/url.php?qid='+queue_id[1]+'&u='+url_id[1];
        }
        else {
          object['referrer_network'] = '';
        }
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
    if (typeof navigator.doNotTrack === 'object' && !navigator.doNotTrack) {
      object['type'] = 'unknown';
      object['referrer_network'] = '';
      object['referrer_url'] = '';
    }

    $.ajax({
      type: "POST",
      url: '/civicrm/ajax/track',
      data: JSON.stringify(object),
      dataType: 'json'
    });
  });
}

var currentScriptSrc = document.currentScript.src;
var inboundSrc = currentScriptSrc.replace(/insights\.js.*$/, 'inbound.js');
loadScript(inboundSrc, function(){
  referrerInfo();
});

