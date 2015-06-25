<?php
$drupal_root = getenv("DRUPAL_ROOT");

if($drupal_root && is_dir($drupal_root)){
  define('DRUPAL_ROOT', $drupal_root);
  chdir($drupal_root);
  $_SERVER['HTTP_HOST'] = 'mysite.com';
  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
  require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

  // fixes base_path problem
  $GLOBALS['base_path'] = '/';
}

if(!defined('CIVICRM_DSN')&&!empty($GLOBALS['mysql_user'])){
  $dbName = !empty($GLOBALS['mysql_db']) ? $GLOBALS['mysql_db'] : 'civicrm_tests_dev';
  define('CIVICRM_DSN', "mysql://{$GLOBALS['mysql_user']}:{$GLOBALS['mysql_pass']}@{$GLOBALS['mysql_host']}/{$dbName}?new_link=true");
}


if(!defined("CIVICRM_DSN")) {
  $dsn = getenv("CIVICRM_TEST_DSN");
  if (!empty ($dsn)) {
    define("CIVICRM_DSN",$dsn);
  }
  else {
    echo "\nFATAL: no DB connection configured (CIVICRM_DSN). \nYou can either create/edit " . __DIR__ . "/civicrm.settings.local.php\n";
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
      echo "OR set it in your shell:\n \$export CIVICRM_TEST_DSN=mysql://db_username:db_password@localhost/civicrm_tests_dev \n";
    }
    else {
      echo "OR set it in your shell:\n SETX CIVICRM_TEST_DSN mysql://db_username:db_password@localhost/civicrm_tests_dev \n
      (you will need to open a new command shell before it takes effect)";
    }
    echo "\n\n
If you haven't done so already, you need to create (once) a database dedicated to the unit tests:
mysql -uroot -p
create database civicrm_tests_dev;
grant ALL on civicrm_tests_dev.* to db_username@localhost identified by 'db_password';
grant SUPER on *.* to db_username@localhost identified by 'db_password';\n";
    die ("");
  }
}

global $civicrm_root;
$civicrm_root = dirname(dirname(dirname(dirname( __FILE__ ))));

$include_path = '.' . PATH_SEPARATOR .
              $civicrm_root . PATH_SEPARATOR .
              $civicrm_root . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR .
              $civicrm_root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'phpunit'. DIRECTORY_SEPARATOR . 'dbunit' . PATH_SEPARATOR .
              get_include_path();
if ( set_include_path( $include_path ) === false ) {
   echo "Could not set the include path<p>";
   exit();
}

require_once "DB.php";
$dsninfo = DB::parseDSN(CIVICRM_DSN);

$GLOBALS['mysql_host'] = $dsninfo['hostspec'];
$GLOBALS['mysql_user'] = $dsninfo['username'];
$GLOBALS['mysql_pass'] = $dsninfo['password'];
$GLOBALS['mysql_db'] = $dsninfo['database'];


/**
 * Content Management System (CMS) Host:
 *
 * CiviCRM can be hosted in either Drupal, Joomla or WordPress.
*/
define('CIVICRM_UF', 'UnitTests');



// set this to a temporary directory. it defaults to /tmp/civi on linux
//define( 'CIVICRM_TEMPLATE_COMPILEDIR', 'the/absolute/path/' );

if (!defined("CIVICRM_TEMPLATE_COMPILEDIR")) {
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define( 'CIVICRM_TEMPLATE_COMPILEDIR', getenv ('TMP') . DIRECTORY_SEPARATOR . 'civi' . DIRECTORY_SEPARATOR );
  } else {
    define( 'CIVICRM_TEMPLATE_COMPILEDIR', '/tmp/civi/' );
  }
}

define( 'CIVICRM_SITE_KEY', 'phpunittestfakekey' );
define( 'CIVICRM_UF_BASEURL' , 'http://FIX ME' );


if ( function_exists( 'variable_get' ) && variable_get('clean_url', '0') != '0' ) {
  define( 'CIVICRM_CLEANURL', 1 );
}
else {
  define( 'CIVICRM_CLEANURL', 0 );
}

// force PHP to auto-detect Mac line endings
ini_set('auto_detect_line_endings', '1');

// make sure the memory_limit is at least 64 MB
$memLimitString = trim(ini_get('memory_limit'));
$memLimitUnit   = strtolower(substr($memLimitString, -1));
$memLimit       = (int) $memLimitString;
switch ($memLimitUnit) {
  case 'g': $memLimit *= 1024;
  case 'm': $memLimit *= 1024;
  case 'k': $memLimit *= 1024;
}
if($memLimit >= 0 and $memLimit < 67108864) {
  ini_set('memory_limit', '64M');
}

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

