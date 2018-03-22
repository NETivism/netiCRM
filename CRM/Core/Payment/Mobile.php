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

    dd(date('Y-m-d H:i:s'));
    dd('Hello');

    $qfKey = $_POST['qfKey'];
    $paymentProcessor = $this->_paymentProcessor;
    $form_params = $this->_paymentForm->_params;

    $provider_name = $paymentProcessor['password'];
    $module_name = 'civicrm_'.strtolower($provider_name);
    if (module_load_include('inc', $module_name, $module_name.'.checkout') === FALSE) {
      CRM_Core_Error::fatal('Module '.$module_name.' doesn\'t exists.');
    }

    $options = array(1 => array( $params['civicrm_instrument_id'], 'Integer'));
    $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);

    if($instrument_name == 'ApplePay'){
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
      exit;
    }
  }

  static function checkout(){
    dd('HelloTwo');
    dd($_POST, 'POST_checkout');

    if($_POST['instrument'] == 'ApplePay'){
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 1);
      foreach ($_POST as $key => $value) {
        $smarty->assign($key, $value );
      }
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      exit;
    }
  }

  static function validate(){
    dd(date('Y-m-d H:i:s'));
    dd($_POST, 'POST_validate');

    $qfKey = $_POST['qfKey'];
    $controller = $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$_POST['qfKey']];
    $paymentProcessor = $controller['paymentProcessor'];


    if(strtolower($paymentProcessor['password']) == 'neweb'){
      $data = array(
        "merchantnumber" => $paymentProcessor['user_name'],
        "domain_name" => $_POST['domain_name'],
        "display_name" => $controller['params']['item_name'],
        "validation_url" => $_POST['validationURL'],
      );

      dd($data,'data');

      module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
      if(function_exists('_civicrm_neweb_get_mobile_params')){
        $payment_params = __civicrm_neweb_get_mobile_params();
      }
      $url = $payment_params['session_url'];
      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';
      dd($cmd, 'cmd');
      $result = exec($cmd);

      dd($result,'result');

      echo $result;
      exit;
      
    }


  }

  static function transact(){
    dd(date('Y-m-d H:i:s'));
    dd($_POST, 'POST_transact');

    $qfKey = $_POST['qfKey'];
    $controller = $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$_POST['qfKey']];
    $paymentProcessor = $controller['paymentProcessor'];

    if(strtolower($paymentProcessor['password']) == 'neweb'){
      $data = array(
        'userid' => $paymentProcessor['signature'],
        'passwd' => $paymentProcessor['subject'],
        'merchantnumber' => $paymentProcessor['user_name'],
        'ordernumber' => $controller['params']['contribution_id'],
        'applepay_token' => $controller['params']['ip_address'],
        'depositflag' => 0,
        'consumerip' => $_SERVER['HTTP_CLIENT_IP'],
      );

      // dd(date('Y-m-d H:i:s'));
      // dd($_POST, 'POST');
      // dd($data,'data');

      module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
      if(function_exists('_civicrm_neweb_get_mobile_params')){
        $payment_params = _civicrm_neweb_get_mobile_params();
      }
      $url = $payment_params['transact_url'];
      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';
      dd($cmd, 'cmd');
      $result = exec($cmd);

      dd($result,'result');

      echo $result;
      exit;
      
    }
  }
}

