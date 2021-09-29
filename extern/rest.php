<?php
if (empty($_GET['xml'])) {
  header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__.'/extern.inc';

// prevent API access by pass
if (!defined('CIVICRM_APIEXPLORER_ENABLED')) {
  http_response_code(404);
  die();
}
if (!CIVICRM_APIEXPLORER_ENABLED) {
  http_response_code(404);
  die();
}

$config = CRM_Core_Config::singleton();
$rest = new CRM_Utils_REST();
echo $rest->bootAndRun();
