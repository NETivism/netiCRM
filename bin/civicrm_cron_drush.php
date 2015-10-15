<?php
// already boot from drush
error_reporting(E_ERROR | E_WARNING);
date_default_timezone_set("Asia/Taipei");

// load civicrm
civicrm_initialize();

// load arguments
$site = drush_get_context('DRUSH_DRUPAL_SITE');
$arguments = drush_get_context('arguments');
$force = FALSE;
foreach($arguments as $k => $v){
  if($v == 'civicrm_cron_drush.php' && !empty($arguments[$k+1])){
    $function = $arguments[$k+1];
    $force = $arguments[$k+2] == '--force' ? TRUE : FALSE;
    break;
  }
}
if(empty($function)){
  drush_log("No specific function", 'error');
  drush_log('Please enter function name: "drush scr civicrm_cron_drush.php run_civimail"', 'warning');
  return;
}
else{
  require_once(dirname(__FILE__).'/cron/'.$function.'.inc');
  if(!function_exists($function)){
    drush_log("Function not exists", 'error');
    drush_log('Please enter function name: "drush scr civicrm_cron_drush.php run_civimail"', 'warning');
  }
}

// load user
global $user;
$user = user_load(array('uid' => 1, 'status' => 1));
if($user->uid){
  $schedule_file = "/tmp/schedule_".$site.".json";
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
      'frequency' => '21600', // strtotime format
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
    foreach($s as $k => $v){
      unset($s[$k]['frequency']);  
    }
    $schedule = array_merge($schedule, $s);
  }

  $s = $schedule[$function];
  if(is_array($s)){
    if(($now - $s['last'] > $s['frequency'])){
      $schedule[$function]['last'] = $now;
      file_put_contents($schedule_file, json_encode($schedule));
      call_user_func($function);
    }
  }
  else{
    call_user_func($function);
    $schedule[$function] = $now;
    file_put_contents($schedule_file, json_encode($schedule));
  }
}
