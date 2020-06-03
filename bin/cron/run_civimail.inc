<?php

/**
 * Sending mail
 */
function run_civimail() {
  $cmspath = $_SERVER['DOCUMENT_ROOT'];
  if($cmspath){
    chdir($cmspath);
  }
  require_once 'CRM/Mailing/BAO/Job.php';
  $config =& CRM_Core_Config::singleton(); 
  if(!$config->mailerJobSize){
    $config->mailerJobSize = 200;
  }
    
  // Split up the parent jobs into multiple child jobs
  date_default_timezone_set("Asia/Taipei");
  CRM_Mailing_BAO_Job::runJobs_pre($config->mailerJobSize);
  CRM_Mailing_BAO_Job::runJobs();
  CRM_Mailing_BAO_Job::runJobs_post();
}
