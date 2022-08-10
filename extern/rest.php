<?php
require_once __DIR__.'/extern.inc';
CRM_Core_Config::singleton();
$rest = new CRM_Utils_REST();

// Json-appropriate header will be set by CRM_Utils_Rest
// But we need to set header here for non-json
if (empty(CRM_Utils_Array::value('xml', $_REQUEST))) {
  header('Content-Type: application/json; charset=utf-8');
}

// prevent API access by pass
if (!defined('CIVICRM_APIEXPLORER_ENABLED')) {
  http_response_code(404);
  exit;
}
if (!CIVICRM_APIEXPLORER_ENABLED) {
  http_response_code(404);
  exit;
}

$config = CRM_Core_Config::singleton();
$rest = new CRM_Utils_REST();
echo $rest->bootAndRun();
