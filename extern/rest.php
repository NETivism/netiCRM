<?php
require_once '../civicrm.config.php';
CRM_Core_Config::singleton();
$rest = new CRM_Utils_REST();

// Json-appropriate header will be set by CRM_Utils_Rest
// But we need to set header here for non-json
if (empty(CRM_Utils_Array::value('xml', $_REQUEST))) {
  header('Content-Type: application/json; charset=utf-8');
}
echo $rest->bootAndRun();
