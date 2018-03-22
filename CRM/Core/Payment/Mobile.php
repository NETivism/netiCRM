<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 3.3                                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
*/


/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */


require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_Mobile extends CRM_Core_Payment {
  CONST CHARSET = 'iso-8859-1';
  static protected $_mode = NULL;

  static protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('DPS Payment Express');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Mobile($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('UserID is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function setExpressCheckOut(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getExpressCheckoutDetails($token) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doExpressCheckout(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /**
   * Main transaction function
   *
   * @param array $params  name value pair of contribution data
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {

    // dd(date('Y-m-d H:i:s'));
    // dd('Hello');
    $qfKey = $params['qfKey'];
    $paymentProcessor = $this->_paymentProcessor;
    $form_params = $this->_paymentForm->_params;

    $session = CRM_Core_Session::singleton();
    $session->set($qfKey."_params", $params);

    $provider_name = $paymentProcessor['password'];
    $module_name = 'civicrm_'.strtolower($provider_name);
    if (module_load_include('inc', $module_name, $module_name.'.checkout') === FALSE) {
      CRM_Core_Error::fatal('Module '.$module_name.' doesn\'t exists.');
    }

    $options = array(1 => array( $params['civicrm_instrument_id'], 'Integer'));
    $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);

    if(strtolower($instrument_name) == 'applepay'){
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 0);
      $params = array(
        'cid' => $form_params['contributionID'],
        'provider' => $provider_name,
        'description' => $form_params['description'],
        'amount' => $form_params['amount'],
        'qfKey' => $qfKey,
      );
      $smarty->assign('params', $params );
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      CRM_Utils_System::civiExit();
    }
  }

  static function checkout(){
    if($_POST['instrument'] == 'ApplePay'){
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 1);
      foreach ($_POST as $key => $value) {
        $smarty->assign($key, $value );
      }
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      CRM_Utils_System::civiExit();
    }
  }

  static function validate(){
    // dd('hello validate');
    // dd($_POST, 'VALIDATE POST');

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $_POST['cid'];
    $contribution->find(TRUE);

    $paymentProcessor = new CRM_Core_DAO_PaymentProcessor();
    $paymentProcessor->id = $contribution->payment_processor_id;
    $paymentProcessor->find(TRUE);

    if(strtolower($paymentProcessor->password) == 'neweb'){
      $data = array(
        "merchantnumber" => $paymentProcessor->user_name,
        "domain_name" => $_POST['domain_name'],
        "display_name" => $contribution->source,
        "validation_url" => $_POST['validationURL'],
      );

      module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
      if(function_exists('_civicrm_neweb_get_mobile_params')){
        $payment_params = _civicrm_neweb_get_mobile_params();
      }
      $url = $payment_params['session_url'];
      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';
      $result = exec($cmd);

      echo $result;
      CRM_Utils_System::civiExit();
      
    }


  }

  static function transact(){
    // dd('hello transact');
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $_POST['cid'];
    $contribution->find(TRUE);

    $paymentProcessor = new CRM_Core_DAO_PaymentProcessor();
    $paymentProcessor->id = $contribution->payment_processor_id;
    $paymentProcessor->find(TRUE);

    if(strtolower($paymentProcessor->password) == 'neweb'){
      $data = array(
        'userid' => $paymentProcessor->signature,
        'passwd' => $paymentProcessor->subject,
        'merchantnumber' => $paymentProcessor->user_name,
        'ordernumber' => $contribution->id,
        'applepay_token' => $_POST['applepay_token'],
        'depositflag' => 0,
        'consumerip' => CRM_Utils_System::ipAddress(),
      );

      module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
      if(function_exists('_civicrm_neweb_get_mobile_params')){
        $payment_params = _civicrm_neweb_get_mobile_params();
      }else{
        CRM_Core_Error::debug_log_message("Doesn't enable module: civicrm_neweb");
      }
      $url = $payment_params['transact_url'];
      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';
      $result = exec($cmd);
      $result = json_decode($result);
      
      $ipn = new CRM_Core_Payment_BaseIPN();
      $input = $ids = $objects = array();
      // if(!empty($controller['value']['event_id'])){
        // ?? 
        // $input['component'] = 'event';

      // }else{
        $input['component'] = 'contribute';
      // }
      $ids['contribution'] = $contribution->id;
      $ids['contact'] = $contribution->contact_id;
      // $pid = $controller['params']['payment_processor'];
      // $validate_result = $ipn->validateData($input, $ids, $objects, FALSE, $pid);
      $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
      if($validate_result){
        $transaction = new CRM_Core_Transaction();
        if($result->prc == 0 && $result->src == 0){
          // $input['trxn_id'] = $c->trxn_id;
          $input['payment_instrument_id'] = $contribution->payment_instrument_id;
          // $input['check_number'] = $result['writeoffnumber'];
          $input['amount'] = $contribution->amount;
          // if($result['timepaid']){
          //   $objects['contribution']->receive_date = $result['timepaid'];
          // }
          // else{
            $objects['contribution']->receive_date = date('YmdHis');
          // }
          $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction);

          $result = array('is_success' => 1);
        }else{
          $transaction_result = $ipn->failed($objects, $transaction);
          $result = array('is_success' => 0);
        }
      }

      echo json_encode($result);
      // dd($result,'RESULT');
      CRM_Utils_System::civiExit();
      
    }
  }
}

