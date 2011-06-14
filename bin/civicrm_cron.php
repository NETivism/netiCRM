<?php
// civimail run
function run_civimail() {
  $cmspath = $_SERVER['DOCUMENT_ROOT'];
  chdir($cmspath);
  require_once 'includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  civicrm_initialize();
  require_once 'CRM/Mailing/BAO/Job.php';
  $config =& CRM_Core_Config::singleton(); 
    
  $user = user_load(array('uid' => 1));
  $originalpass = $user->pass;
  $newpass = 'password';
  $newhash  = md5($newpass);

  // SQL query to set the user's password to the Temporary one
  $update = "UPDATE {users} u SET pass = '%s' WHERE u.uid = 1";
  db_query($update, $newhash);

  $_REQUEST['key'] = CIVICRM_SITE_KEY;
  $_REQUEST['name'] = $user->name;
  $_REQUEST['pass'] = $newpass;
  CRM_Utils_System::authenticateScript(true);

  // load bootstrap to call hooks
  require_once 'CRM/Utils/System.php';
  CRM_Utils_System::loadBootStrap(  );

  // restore original password
  $reset = "UPDATE {users} u SET pass = '%s' WHERE u.uid = 1";
  $updated = db_query($update, $originalpass);

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
