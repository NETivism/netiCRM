<?php
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'civicrm_cron.inc';

global $config;
$config = CRM_Core_Config::singleton();

$schedule_file = "/tmp/schedule_".$_SERVER['HTTP_HOST'].".json";
$now = time();
if(file_exists($schedule_file)){
  $schedule = json_decode(file_get_contents($schedule_file), TRUE);
}
else{
  $schedule = array(
    // process aborting mail
    'run_civimail_process' => array(
      'frequency' => '3600', // strtotime format
      'last' => 0, // unix timestamp
    ),

    // process greeting update
    'run_contact_greeting_update' => array(
      'frequency' => '7200', // strtotime format
      'last' => 0, // unix timestamp
    ),

    // mass malling
    // place this in last to prevent through exception
    'run_civimail' => 0,

  );
}

// main functions
if($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR']){
  foreach($schedule as $function => $s){
    if(is_array($s)){
      if($now - $s['last'] > $s['frequency']){
        call_user_func($function);
        $schedule[$function]['last'] = $now;
      }
    }
    else{
      call_user_func($function);
      $schedule[$function] = $now;
    }
  }
  file_put_contents($schedule_file, json_encode($schedule));
}
else{
  echo 'Permission denied for civimail. You can only run the script at the same server.';
}


