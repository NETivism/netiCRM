<?php

/**
 * This function has been copied from DRUPAL_ROOT/includes/bootstrap.inc
 */

/**
 * Locate the appropriate configuration file.
 *
 * Try finding a matching configuration directory by stripping the
 * website's hostname from left to right and pathname from right to
 * left.  The first configuration file found will be used, the
 * remaining will ignored.  If no configuration file is found,
 * return a default value '$confdir/default'.
 *
 * Example for a fictitious site installed at
 * http://www.drupal.org/mysite/test/ the 'settings.php' is
 * searched in the following directories:
 *
 *  1. $confdir/www.drupal.org.mysite
 *  2. $confdir/drupal.org.mysite
 *  3. $confdir/org.mysite
 *
 *  4. $confdir/www.drupal.org
 *  5. $confdir/drupal.org
 *  6. $confdir/org
 *
 *  7. $confdir/default
 *
 */

function civicrm_conf_init() {
    global $skipConfigError, $civicrm_root, $civicrm_conf_path, $civicrm_drupal_root;

    static $conf = '';

    if ($conf) {
      return $conf;
    }

    /**
     * We are within the civicrm module, the drupal root is 2 links
     * above us, so use that
     */
    if(php_sapi_name() == 'cli' && !empty($_SERVER['PWD'])){
      $sfile = $_SERVER['PWD'].'/'.$_SERVER['SCRIPT_FILENAME'];
    }
    else{
      $sfile = $_SERVER['SCRIPT_FILENAME'];
    }
    // drupal 6-7
    if (preg_match('@sites/([^/]+)/modules/civicrm/.*$@', $sfile)) {
      preg_match('@(.*)(sites/([^/]+)/modules/civicrm)/.*$@', $sfile, $matches);
    }
    // drupal 9
    elseif (preg_match('@/modules/civicrm/.*$@', $sfile)) {
      preg_match('@(.*)(/modules/civicrm)/.*$@', $sfile, $matches);
    }

    $drupal_root = $civicrm_root = $site_dir = '';
    if(!empty($matches[1])) {
      $drupal_root = rtrim($matches[1], '/');
    }
    if(!empty($matches[1]) && !empty($matches[2])){
      $civicrm_root = rtrim($matches[1].$matches[2], '/');
    }
    if(!empty($matches[3])) {
      $site_dir = $matches[3];
    }

    $possibleConf = [];
    if (defined('CIVICRM_CONFDIR')) {
      $possibleConf[] = CIVICRM_CONFDIR;
    }

    // detection dirs by
    $phpSelf  = array_key_exists( 'PHP_SELF' , $_SERVER ) ? $_SERVER['PHP_SELF' ] : '';
    $httpHost = array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : '';
    $httpHost = preg_replace('/[^0-9a-z.\-]+/i', '', $httpHost);

    if ($phpSelf && $httpHost && $drupal_root) {
      $uri = explode('/', $phpSelf, 3); // only support 1st sub-dir
      $server = explode('.', implode('.', array_reverse(explode(':', rtrim($httpHost, '.')))));
      for ($i = count($uri) - 1; $i > 0; $i--) {
        for ($j = count($server); $j > 0; $j--) {
          $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
          $possibleConf[] = $drupal_root.'/sites/'.$dir;
        }
      }
    }

    // fallback dirs
    if ($site_dir && $site_dir !== 'all') {
      $possibleConf[] = $drupal_root.'/sites/'.$site_dir;
    }
    $possibleConf[] = $drupal_root.'/sites/default';

    foreach($possibleConf as $pdir) {
      if (file_exists($pdir. DIRECTORY_SEPARATOR . 'civicrm.settings.php')) {
        $conf = $pdir;
        $civicrm_drupal_root = $drupal_root;
        $civicrm_conf_path = $conf;
        return $conf;
      }
    }

    echo "403 Forbidden";
    http_response_code(403);
    exit();
}

civicrm_conf_init();
global $civicrm_root, $civicrm_conf_path, $civicrm_drupal_root;
if ($civicrm_drupal_root) {
  chdir($civicrm_drupal_root);
}

if( file_exists($civicrm_conf_path . '/settings.php')){
  $error = include_once $civicrm_conf_path . '/settings.php';
}

$settingsFile = $civicrm_conf_path . '/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
$error = @include_once( $settingsFile );
if ( $error === false ) {
  echo "403 Forbidden";
  http_response_code(403);
  exit();
}

// Load class loader
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();
