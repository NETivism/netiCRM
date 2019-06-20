<?php
session_start();
require_once '../civicrm.config.php';
$config = CRM_Core_Config::singleton();
$rest = new CRM_Utils_REST();

// Json-appropriate header will be set by CRM_Utils_Rest
// But we need to set header here for non-json
if (empty($_GET['json'])) {
  header('Content-Type: text/xml');
}
echo $rest->bootAndRun();
