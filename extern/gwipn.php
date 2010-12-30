<?php
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();
require_once 'CRM/Core/Error.php';
if(empty($_POST)){
   CRM_Core_Error::debug_log_message( "Could not find POST data from payment server" );
   echo "Could not find POST data from payment server";
}
else{
  require_once 'CRM/Utils/Array.php';
  $value = CRM_Utils_Array::value('module', $_GET);
  require_once 'CRM/Core/Payment/GWIPN.php';
  $ipn = new CRM_Core_Payment_GWIPN();
  switch ( $value ) {
    case 'contribute':
     $ipn->main('contribute');
     break;
    case 'event':
     $ipn->main('event');
     break;
    default:
     CRM_Core_Error::debug_log_message( "Could not get module name from request url" );
     echo "Could not get module name from request url";
     break;
  }
}
