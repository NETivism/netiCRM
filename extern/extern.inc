<?php
require_once __DIR__.'/../civicrm.config.php';

// wrap function to prevent variale become global
function _forcePHPIDS() {
  $ids = new CRM_Core_IDS();
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $args = [ltrim($path, '/')];
  $ids->check($args);
}


_forcePHPIDS();