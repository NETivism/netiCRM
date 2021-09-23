<?php
// we will bootstrap drupal when require civicrm.config.php
// just like extern/*.php doing
require_once __DIR__.'/../../../civicrm.config.php';

// we should already have fully drupal bootstrp and database connection here
// we init config for achive that
CRM_Core_Config::singleton();
CRM_Core_DAO::freeResult();
