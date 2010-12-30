<?php
// bootstrap the environment and run the processor
session_start();
print '<pre>';
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();
require_once 'CRM/Core/Payment.php'; 
require_once 'CRM/Core/BAO/PaymentProcessor.php';
$p = new CRM_Core_BAO_PaymentProcessor();
$paymentProcessor = $p->getPayment(1,'live');
require_once 'CRM/Core/Payment/Newweb.php';
$newweb =& new CRM_Core_Payment_Newweb('live', $paymentProcessor);
$newweb->cron();

/*
CRM_Utils_System::authenticateScript(true);

require_once 'CRM/Core/Lock.php';
$lock = new CRM_Core_Lock('CiviContributeProcessor');

if ($lock->isAcquired()) {
    // try to unset any time limits
    if (!ini_get('safe_mode')) set_time_limit(0);

    CiviContributeProcessor::process( );
} else {
    throw new Exception('Could not acquire lock, another CiviMailProcessor process is running');
}

$lock->release();

echo "Done processing<p>";
*/
