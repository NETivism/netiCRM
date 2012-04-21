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
    
  // Split up the parent jobs into multiple child jobs
  CRM_Mailing_BAO_Job::runJobs_pre($config->mailerJobSize);
  CRM_Mailing_BAO_Job::runJobs();
  CRM_Mailing_BAO_Job::runJobs_post();
}
