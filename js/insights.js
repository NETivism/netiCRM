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
	var url = window.location.href;
	var referrer = document.referrer;
	var domainVisit = getHostNameFromUrl(url);
	var domainReferrer = getHostNameFromUrl(referrer);

	if (typeof navigator.doNotTrack === 'object') {
		var doNotTrack = navigator.doNotTrack;
	}
	else {
		var doNotTrack = null;
	}
	if (!doNotTrack && referrer && (domainVisit != domainReferrer)) {
		inbound.referrer.parse(url, referrer, function (err, description) {
			sessionStorage.setItem('referrerInfo', JSON.stringify(description));
		});
	}
}

var currentScriptSrc = document.currentScript.src;
currentScriptSrc = currentScriptSrc.replace(/insights\.js.*$/, 'inbound.js');
loadScript(currentScriptSrc, function(){
  referrerInfo();
});

