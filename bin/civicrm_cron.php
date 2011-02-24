<?php
// civimail run
function run_civimail() {
  require_once '../../../../../includes/unicode.inc';
  require_once '../civicrm.config.php'; 
  require_once 'CRM/Core/Config.php'; 
  require_once 'CRM/Mailing/BAO/Job.php';
  $config =& CRM_Core_Config::singleton(); 
  //log the execution of script
  CRM_Core_Error::debug_log_message( 'civimail.cronjob.php');
    
  // load bootstrap to call hooks
  require_once 'CRM/Utils/System.php';
  CRM_Utils_System::loadBootStrap(  );

  // Split up the parent jobs into multiple child jobs
  CRM_Mailing_BAO_Job::runJobs_pre($config->mailerJobSize);
  CRM_Mailing_BAO_Job::runJobs();
  CRM_Mailing_BAO_Job::runJobs_post();
}


if($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR']){
  run_civimail( );
}
else{
  echo 'Permission denied for civimail. You can only run the script at the same server.';
}
