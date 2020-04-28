<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<head>
  <title>{if $pageTitle}{$pageTitle|strip_tags}{/if}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base target="{if $sameOrigin}_parent{else}_blank{/if}">
  <style type="text/css" media="screen, print">@import url({$config->resourceBase}css/civicrm.css);</style>
  {if $config->customCSSURL}
  <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" media="all"/>
  {/if}
  {literal}<style>
    body { margin:0; padding: 0; }
  </style>{/literal}
</head>
<body class="crm-embed">
<script type="text/javascript" src="{$config->userFrameworkResourceURL}js/iframeresizer.contentwindow.js"></script>
<div class="crm-container">
{$embedBody}
</div>

{if !$sameOrigin}
<script>{literal}
// safari doesn't support 3rd cookie
if (typeof cj !== "undefined") {
cj(document).ready(function($){

var userAgent = navigator.userAgent.toLowerCase();
var referrer = document.referrer;
if (userAgent && referrer) {
  var isSafari = userAgent.indexOf("safari") != -1 && userAgent.indexOf("chrome") == -1;
  var hasCookiePermission = document.cookie.indexOf("hasCookiePermission=1") != -1;
  var hasForm = $("form").length;
  if (hasForm && (isSafari || !hasCookiePermission)) {
    $('<div id="safari-overlay" align="center"><i class="zmdi zmdi-refresh zmdi-hc-spin"></i></div>').insertAfter("form:eq(0)");
    $("form").hide();
    window.setTimeout(function(){
      $("div#safari-overlay").remove();
      var frameLink = location.href.replace('&embed=1&', '&');
      var $safariSupportMessage = $('<div id="safari-message" align="center"><div class="description">'+"{/literal}{ts}Your browser doesn't support embed form. Click button below to finish online form.{/ts}{literal}"+'</div><a href="'+frameLink+'" class="button">{/literal}{ts}Visit Online Form{/ts}{literal}</div></div>');
      $safariSupportMessage.insertAfter("form:eq(0)");
    }, 1500);
  }
}

});
}
{/literal}</script>
{/if}
</body>
</html>
