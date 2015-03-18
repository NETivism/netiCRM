<?php
/**
 * We always locate at sites/all/modules/civicrm/bin/
 */
error_reporting(E_ERROR | E_WARNING);
global $civicrm_root, $db_url, $user;
date_default_timezone_set("Asia/Taipei");

$vars = array();
if(is_array($argv)){
  foreach($argv as $a){
    list($k, $v) = explode("=", $a);
    if($k[0] == '-' && $k[1] == '-' && !$v){
      $v = 1;
    }
    $vars[$k] = $v;
  }
}

if(!$vars['site']){
  cli_error('You don\'t assign site parameter.');
  return;
}
elseif(!$vars['function']){
  cli_error('You don\'t assign function parameter.');
  return;
}
else{
  $_SERVER['HTTP_HOST'] = $vars['site'];
  require_once("/var/aegir/.drush/{$vars['site']}.alias.drushrc.php");
  $alias = current($aliases);
  $conf_dir = $alias['site_path'];
  $civicrm_root = $alias['root'].'/sites/all/modules/civicrm';
  // ch to cms path
  chdir($alias['root']);

  if(!file_exists($conf_dir.'/drushrc.php')){
    cli_error('drushrc.php loading failed.');
  }
  else{
    include_once $conf_dir . '/drushrc.php'; 
  }
}

if(!file_exists($conf_dir.'/civicrm.settings.php')){
  cli_error('Config file loading error of civicrm.settings.php');
  return;
}
else{
  require_once dirname(__FILE__) . '/../CRM/Core/ClassLoader.php';
  CRM_Core_ClassLoader::singleton()->register();

  include_once $conf_dir . '/settings.php'; 
  include_once $conf_dir . '/civicrm.settings.php'; 
  $_REQUEST['key']= CIVICRM_SITE_KEY;
  ini_set('session.save_handler', 'files');
  if(!is_dir('/tmp/php')){
    mkdir('/tmp/php');
  }
  ini_set('session.save_path', '/tmp/php');
}

/**
 * Now start of authentication / key verifying
 */
cli_authenticate(1);

/**
 * The main cron run
 */
$schedule_file = "/tmp/schedule_".$_SERVER['HTTP_HOST'].".json";
$now = time();
$schedule = array(
  // mass malling
  // place this in first to prevent through exception
  'run_civimail' => 0,

  // process aborting mail
  'run_civimail_process' => array(
    'frequency' => '7200', // strtotime format
    'last' => 0, // unix timestamp
  ),

  // process greeting update
  'run_contact_greeting_update' => array(
    'frequency' => '604800', // strtotime format
    'last' => 0, // unix timestamp
  ),

  // process membership status
  'run_membership_status_update' => array(
    'frequency' => '86399',
    'last' => 0, // unix timestamp
  ),
);
if(file_exists($schedule_file)){
  $s = json_decode(file_get_contents($schedule_file), TRUE);
  $schedule = array_merge($schedule, $s);
}

if($function = $vars['function']){
  $s = $schedule[$function];
  if(is_array($s)){
    if(($now - $s['last'] > $s['frequency']) || $vars['--force']){
      $schedule[$function]['last'] = $now;
      require_once(dirname(__FILE__).'/cron/'.$function.'.inc');
      file_put_contents($schedule_file, json_encode($schedule));
      civicrm_initialize();
      call_user_func($function);
    }
  }
  else{
    civicrm_initialize();
    require_once(dirname(__FILE__).'/cron/'.$function.'.inc');
    call_user_func($function);
    $schedule[$function] = $now;
    file_put_contents($schedule_file, json_encode($schedule));
  }
}
else{
  cli_error('No specific function');
  return; 
}


/**
 * Helper Functions Start here
 */

function cli_error($e){
  print "Error: ".$e."\n";
}

function cli_authenticate($uid) {
  session_start();                               
  require_once 'DB.php';
  $error = include_once( 'CRM/Core/Config.php' );
  $config = CRM_Core_Config::singleton(); 

  if(!CRM_Utils_System::authenticateKey( true ) ){
    cli_error('Authenticate Key error');
    return;
  }

  $dbDrupal = DB::connect( $config->userFrameworkDSN );
  if ( DB::isError( $dbDrupal ) ) {
    cli_error("Cannot connect to drupal db via $config->userFrameworkDSN, " . $dbDrupal->getMessage( ));
    return;
  }                                                      

  $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
  $sql = 'SELECT u.* FROM ' . $config->userFrameworkUsersTableName . " u WHERE u.uid = $uid AND u.status = 1";
  $query = $dbDrupal->query( $sql );

  $acconut = null;
  // need to change this to make sure we matched only one row
  require_once 'CRM/Core/BAO/UFMatch.php';
  while ( $row = $query->fetchRow( DB_FETCHMODE_ASSOC ) ) { 
    CRM_Core_BAO_UFMatch::synchronizeUFMatch( $account, $row['uid'], $row['mail'], 'Drupal' );
    $contactID = CRM_Core_BAO_UFMatch::getContactId( $row['uid'] );
    if ( ! $contactID ) {
      cli_error("Doesn't have matching contact_id in CiviCRM");
      return false;
    }
    $contact = array( $contactID, $row['uid'], mt_rand() );
  }

  if (empty($contact) ) {
    cli_error("Invalid username and/or password");
    return false;
  } 
  // lets store contact id and user id in session
  list( $userID, $ufID, $randomNumber ) = $contact;
  if ( $userID && $ufID ) {
    $session = CRM_Core_Session::singleton( );
    $session->set( 'ufID'  , $ufID );
    $session->set( 'userID', $userID );
  }
  else {
    cli_error("ERROR: Unexpected error, could not match userID and contactID");
    return false;
  }

  // bootstrap CMS environment
  global $civicrm_root;
  $_SERVER['SCRIPT_FILENAME'] = "$civicrm_root/bin/cli.php";
  $config = CRM_Core_Config::singleton();                                

  require_once 'includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  global $user;
  $user = user_load(array('uid' => $uid, 'status' => 1));

  return TRUE;
}



