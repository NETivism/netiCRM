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

    $qfKey = $params['qfKey'];
    $paymentProcessor = $this->_paymentProcessor;

    $provider_name = $paymentProcessor['password'];
    $module_name = 'civicrm_'.strtolower($provider_name);
    if (module_load_include('inc', $module_name, $module_name.'.checkout') === FALSE) {
      CRM_Core_Error::fatal('Module '.$module_name.' doesn\'t exists.');
    }

    $options = array(1 => array( $params['civicrm_instrument_id'], 'Integer'));
    $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);

    if(!empty($params['eventID'])){
      $event = new CRM_Event_DAO_Event();
      $event->id = $params['eventID'];
      $event->find(1);
      $page_title = $event->title;
    }else{
      $contribution_pgae = new CRM_Contribute_DAO_ContributionPage();
      $contribution_pgae->id = $params['contributionPageID'];
      $contribution_pgae->find(1);
      $page_title = $contribution_pgae->title;
    }

    $description = !empty($params['amount_level']) ? $page_title . ' - ' . $params['amount_level'] : $page_title;

    if($this->_paymentForm->_mode == 'test'){
      $is_test = 1;
    }else{
      $is_test = 0;
    }

    if(strtolower($instrument_name) == 'applepay'){
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 0);
      $payment_params = array(
        'cid' => $params['contributionID'],
        'provider' => $provider_name,
        'description' => $description,
        'amount' => $params['amount'],
        'qfKey' => $qfKey,
        'is_test' => $is_test,
      );
      if(!empty($params['participantID'])){
        $payment_params['pid'] = $params['participantID'];
      }
      if(!empty($params['eventID'])){
        $payment_params['eid'] = $params['eventID'];
      }
      $smarty->assign('params', $payment_params );
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      CRM_Utils_System::civiExit();
    }
  }

  static function checkout(){
    if($_POST['instrument'] == 'ApplePay'){
      $domain = CRM_Core_BAO_Domain::getDomain();
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('after_redirect', 1);
      $smarty->assign('organization', $domain->name);
      foreach ($_POST as $key => $value) {
        $smarty->assign($key, $value );
      }
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      CRM_Utils_System::setTitle(ts('Contribute Now'));
      // CRM_Utils_Hook::alterContent($page, 'page', $pageTemplateFile, $this);
      CRM_Utils_System::theme('page', $page);
    }
  }

  static function validate(){

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

      if(empty($paymentProcessor->url_site)){
        module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
        if(function_exists('_civicrm_neweb_get_mobile_params')){
          $payment_params = _civicrm_neweb_get_mobile_params();
        }else{
          $error = "Can't get params from module when validate: civicrm_neweb";
          CRM_Core_Error::debug_log_message($error);
          $note .= $error;
          CRM_Core_Payment_Mobile::addNote($note, $contribution);
        }

        $_test = $_POST['is_test']? '_test' : '';
        $url = $payment_params['session_url'.$_test];
      }else{
        $url = preg_replace('/\/$/', '', trim($paymentProcessor->url_site)).'/sessions';
      }

      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';

      // $sql = "SELECT cid FROM civicrm_contribution_neweb WHERE cid = {$contribution->id}";
      // $cid = CRM_Core_DAO::singleValueQuery($sql);
      // if(!empty($cid)){
      //   $primary_keys = 'cid';
      // }else{
      //   $primary_keys = array();
      // }

      $record = array(
        'cid' => $contribution->id,
        'post_data' => $cmd,
      );
      drupal_write_record('civicrm_contribution_neweb', $record, $primary_keys);

      $result = exec($cmd);

      $record = array(
        'cid' => $contribution->id,
        'data' => $result,
      );
      drupal_write_record('civicrm_contribution_neweb', $record, 'cid');

      $result_object = json_decode($result);
      if($result_object->prc !== 0 || $result_object->src !== 0){
        $note = "Prc: {$result_object->prc}, Src: {$result_object->src}";
        CRM_Core_Error::debug_log_message($error);
        CRM_Core_Payment_Mobile::addNote($note, $contribution);
      }

      echo $result;
      CRM_Utils_System::civiExit();
    }
  }

  static function transact(){
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

      if(empty($paymentProcessor->url_site)){
        module_load_include("inc", 'civicrm_neweb', 'civicrm_neweb.checkout');
        if(function_exists('_civicrm_neweb_get_mobile_params')){
          $payment_params = _civicrm_neweb_get_mobile_params();
        }else{
          $error = "Can't get params from module when transact: civicrm_neweb";
          CRM_Core_Error::debug_log_message($error);
          $note .= $error;
          CRM_Core_Payment_Mobile::addNote($note, $contribution);
        }

        $_test = $_POST['is_test']? '_test' : '';
        $url = $payment_params['transact_url'.$_test];
      }else{
        $url = preg_replace('/\/$/', '', trim($paymentProcessor->url_site)).'/ccaccept';
      }
      $cmd = 'curl --request POST --url "'.$url.'" -H "Content-Type: application/json" --data @- <<END 
      '. json_encode($data).'
      END';

      $record = array(
        'cid' => $contribution->id,
        'post_data_transact' => $cmd,
      );
      drupal_write_record('civicrm_contribution_neweb', $record, 'cid');

      $result = exec($cmd);

      $record = array(
        'cid' => $contribution->id,
        'return_data' => $result,
      );
      $result = json_decode($result);

      $record['created'] = time();
      $record['prc'] = $result->prc;
      $record['src'] = $result->src;
      $record['bankrc'] = $result->bankresponsecode;
      $record['approvalcode'] = $result->approvalcode;
      drupal_write_record('civicrm_contribution_neweb', $record, 'cid');
      
      $ipn = new CRM_Core_Payment_BaseIPN();
      $input = $ids = $objects = array();
      if(!empty($_POST['pid']) && !empty($_POST['eid'])){
        $input['component'] = 'event';
        $ids['participant'] = $_POST['pid'];
        $ids['event'] = $_POST['eid'];
      }else{
        $input['component'] = 'contribute';
      }
      $ids['contribution'] = $contribution->id;
      $ids['contact'] = $contribution->contact_id;
      $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
      if($validate_result){
        $transaction = new CRM_Core_Transaction();
        if($result->prc == 0 && $result->src == 0){
          // $input['trxn_id'] = $c->trxn_id;
          $input['payment_instrument_id'] = $contribution->payment_instrument_id;
          // $input['check_number'] = $result['writeoffnumber'];
          $input['amount'] = $contribution->amount;
          $objects['contribution']->receive_date = date('YmdHis');
          $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction);

          $result = array('is_success' => 1);
        }else{
          $ipn->failed($objects, $transaction, $error);
          $note .= $error . "Prc: {$result_object->prc}, Src: {$result_object->src}";
          CRM_Core_Payment_Mobile::addNote($note, $contribution);
          $result = array('is_success' => 0);
        }
      }

      echo json_encode($result);
      CRM_Utils_System::civiExit();
      
    }
  }

  static function addNote($note, &$contribution){
      require_once 'CRM/Core/BAO/Note.php';
      $note = date("Y/m/d H:i:s"). ts("Transaction record").": \n\nError: ".$note."\n===============================\n";
      $note_exists = CRM_Core_BAO_Note::getNote( $contribution->id, 'civicrm_contribution' );
      if(count($note_exists)){
        $note_id = array( 'id' => reset(array_keys($note_exists)) );
        $note = $note . reset($note_exists);
      }
      else{
        $note_id = NULL;
      }
      $noteParams = array(
        'entity_table'  => 'civicrm_contribution',
        'note'          => $note,
        'entity_id'     => $contribution->id,
        'contact_id'    => $contribution->contact_id,
        'modified_date' => date('Ymd')
      );
      CRM_Core_BAO_Note::add( $noteParams, $note_id );
    }


}

