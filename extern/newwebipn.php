<?php
/*
function lsg($input){
  ob_start();
  print_r($input);
  $content = ob_get_contents();
  ob_end_clean();
  file_put_contents("/tmp/log-newweb.txt", $content, FILE_APPEND);
}

file_put_contents("/tmp/log-newweb.txt", '');
 */
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/BAO/PaymentProcessor.php';
require_once "CRM/Core/DAO.php";
require_once 'CRM/Contribute/DAO/Contribution.php';
$config =& CRM_Core_Config::singleton();

//lsg($_POST);
if($_GET['contributionID'] && is_numeric($_GET['contributionID']) && !empty($_POST)){
  $cid = $_GET['contributionID'];
  if($cid == $_POST['OrderNumber']){
    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution_newweb WHERE order_num = $cid");
    $dao->fetch();
    if(!$dao->order_num){
      $contribution =& new CRM_Contribute_DAO_Contribution( );
      $contribution->id = $cid;
      if ( $contribution->find( true ) ) {
        /*
        $paymentProcessorID = CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_ContributionPage',$contribution->contribution_page_id,'payment_processor_id' );
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $paymentProcessorID, $contribution->is_test ? 'test' : 'live' );
        $signature = $paymentProcessor['signature'];
        $checksum = md5($_POST['MerchantNumber'].$_POST['OrderNumber'].$_POST['PRC'].$_POST['SRC'].$signature.$_POST['Amount']);
        if($checksum == $_POST['CheckSum']){
         */
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_newweb (order_num,prc,src,bankrc,approvalcode) VALUES ($cid,{$_POST['PRC']},{$_POST['SRC']},'{$_POST['BankResponseCode']}','{$_POST['ApprovalCode']}')");
        /*
        }
         */
      }
    }
  }
}

require_once 'CRM/Utils/Array.php';
$value = CRM_Utils_Array::value( 'module', $_GET );
require_once 'CRM/Core/Payment/NewwebIPN.php';
$NewwebIPN = new CRM_Core_Payment_NewwebIPN( );

switch ( $value ) {
 case 'contribute':
     $NewwebIPN->main( 'contribute' );
     break;
 case 'event':
     $NewwebIPN->main( 'event' );
     break;
 default:
     require_once 'CRM/Core/Error.php';
     CRM_Core_Error::debug_log_message( "Could not get module name from request url" );
     echo "Could not get module name from request url<p>";
     break;
 }

