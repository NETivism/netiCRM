<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<head>
  <title>{if $pageTitle}{$pageTitle|strip_tags}{/if}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base target="_parent">
  <style type="text/css" media="screen, print">@import url({$config->resourceBase}css/civicrm.css);</style>
  {if $config->customCSSURL}
  <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" media="all"/>
  {/if}
</head>
<body>
<div class="crm-container">
{$embedBody}
</div>
</body>
</html>
