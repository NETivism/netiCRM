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
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Mobile($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Setter for the payment form that wants to use the processor
   *
   * @param obj $paymentForm
   *
   */
  function setForm(&$paymentForm) {
    parent::setForm($paymentForm);
    // event registration doesn't use this...
  }

  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name']) && strlen($this->_paymentProcessor['user_name']) == 0) {
      $error[] = ts('UserID is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (!empty($error)) {
      return CRM_Utils_Array::implode('<p>', $error);
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
    $instrument_id = $params['civicrm_instrument_id'];
    if(!empty($instrument_id)){
      // civicrm_instrument_by_id($params['civicrm_instrument_id'], 'name');
      $options = array(1 => array( $instrument_id, 'Integer'));
      $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);
      $this->_instrumentType = strtolower($instrument_name);
    }

    $cid = $params['contributionID'];
    $iid = $params['civicrm_instrument_id'];
    if($cid && $iid){
      $options = array(
        1 => array($iid, 'Integer'),
        2 => array($cid, 'Integer'),
      );
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution SET payment_instrument_id = %1 WHERE id = %2", $options);
    }
    CRM_Core_Error::debug_var('mobile_payment_params', $params);

    if($this->_instrumentType == 'linepay'){
      CRM_Core_Error::debug_var('mobile_payment_linepay', $cid);
      $this->_mobilePayment = new CRM_Core_Payment_LinePay($params['payment_processor']);
      $this->_mobilePayment->doRequest($params);
      return;
    }

    CRM_Core_Error::debug_var('mobile_payment_others', $cid);
    // If not use linepay, We need another payment processor.
    $qfKey = $params['qfKey'];
    $paymentProcessor = $this->_paymentProcessor;

    $provider_name = $paymentProcessor['password'];

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

    if($this->_mode == 'test'){
      $is_test = 1;
    }else{
      $is_test = 0;
    }

    if($this->_instrumentType == 'applepay'){
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
      if (!empty($params['membershipID'])) {
        $payment_params['mid'] = $params['membershipID'];
      }
      $smarty->assign('params', $payment_params );
      $page = $smarty->fetch('CRM/Core/Payment/ApplePay.tpl');
      print($page);
      CRM_Utils_System::civiExit();
    }
    else if ($this->_instrumentType == 'googlepay' && $provider_name == 'spgateway') {
      $mode = $is_test ? 'test':'';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($this->_paymentProcessor['user_name'], $mode);
      $payment = new CRM_Core_Payment_SPGATEWAY($mode, $paymentProcessor);
      $payment->doTransferCheckout($params, $component);
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
      return CRM_Utils_System::theme($page);
    }
  }

  static function validate(){
    $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $validationUrl = CRM_Utils_Request::retrieve('validationURL', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $domainName = CRM_Utils_Request::retrieve('domain_name', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $isTest = CRM_Utils_Request::retrieve('is_test', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, FALSE, 'REQUEST');
    $path = CRM_Utils_Request::retrieve('q', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $arg = explode('/', $path);

    $mobile_paymentProcessor_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'payment_processor_id');
    $merchantIdentifier = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $mobile_paymentProcessor_id, 'signature');


    if($arg[2] == 'applepay'){
      // Refs: Document: [Requesting an Apple Pay Payment Session] https://goo.gl/CJAe4M
      $data = array(
        'merchantIdentifier' => $merchantIdentifier,
        'displayName' => 'test',
        'initiative' => 'web',
        'initiativeContext' => $domainName,
      );
      $host = '';
      if (!self::doCheckValidationUrl($validationUrl, $isTest, $host)) {
        $note = ts('URL: %1 is not accessable, Where ip is %2', array(
          1 => $validationUrl,
          2 => $host,
        ));
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->id = $contributionId;
        $contribution->find(TRUE);
        self::addNote($note, $contribution);
        exit;
      }
      $file_name = 'applepaycert_'.$mobile_paymentProcessor_id.'.inc';
      $file_path = CRM_Utils_System::cmsRootPath() . '/' . CRM_Utils_System::confPath().'/' . $file_name;
      global $civicrm_root;
      $cafile_path = $civicrm_root.'cert/cacert.pem';

      $ch = curl_init($validationUrl);
      $opt = array();
      $opt[CURLOPT_RETURNTRANSFER] = TRUE;
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_HTTPHEADER] = array("Content-Type: application/json");
      $opt[CURLOPT_POSTFIELDS] = json_encode($data);
      $opt[CURLOPT_SSLCERT] = $file_path;
      $opt[CURLOPT_CAINFO] = $cafile_path;
      curl_setopt_array($ch, $opt);

      $cmd = 'curl --request POST --url "'.$validationUrl.'" --cacert '.$cafile_path.' --cert '.$file_path.' -H "Content-Type: application/json" --data "'. json_encode($data).'"';
      CRM_Core_Error::debug('cmd', $cmd);

      $result = curl_exec($ch);
      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($result === FALSE) {
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $curlError = array($errno => $err);
      }
      else{
        $curlError = array();
      }

      CRM_Core_Error::debug('applepay_filepath', $file_path);
      CRM_Core_Error::debug('applepay_validate_post', $_POST);
      CRM_Core_Error::debug('applepay_validate_get', $_GET);
      CRM_Core_Error::debug('applepay_validate_curl_result', $result);
      CRM_Core_Error::debug('applepay_validate_curl_status', $status);
      CRM_Core_Error::debug('applepay_validate_curl_error', $curlError);

      curl_close($ch);
    }
    echo $result;
    CRM_Utils_System::civiExit();
  }

  static function transact(){
    $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $ppProvider = CRM_Utils_Request::retrieve('provider', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $event_id = CRM_Utils_Request::retrieve('eid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $membership_id = CRM_Utils_Request::retrieve('mid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $post = $_POST;

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);

    if ($contribution->contribution_status_id == 1) {
      // The contribution is solved, avoid solve twice.
      CRM_Core_Error::debug('applepay_transact_post_duplicated_condition', $_POST);
      CRM_Core_Error::debug('applepay_transact_get_duplicated_condition', $_GET);
      CRM_Core_Error::debug_log_message('This contribution has already been processed.');
      CRM_Utils_System::civiExit();
    }

    // Prepare objects to put in checkout function.
    $originPaymentProcessorId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $contribution->payment_processor_id, 'user_name');
    $merchantPaymentProcessor = new CRM_Core_DAO_PaymentProcessor();
    $merchantPaymentProcessor->id = $originPaymentProcessorId;
    $merchantPaymentProcessor->find(TRUE);
    $objects = array(
      'contribution' => $contribution,
      'payment_processor' => $merchantPaymentProcessor,
    );

    if(strstr($_GET['q'], 'applepay')){
      $type = 'applepay';
    }
    // call mobile checkout function
    $paymentProviderClass = 'CRM_Core_Payment_'.strtoupper($ppProvider);
    if (!is_callable(array($paymentProviderClass, 'mobileCheckout'))) {
      return CRM_Core_Error::fatal('Function '.$paymentProviderClass.'::mobileCheckout doesn\'t exists.');
    }
    $return = call_user_func(array($paymentProviderClass, 'mobileCheckout'), $type, $post, $objects);

    if(!empty($return)){

      CRM_Core_Error::debug('applepay_transact_post', $_POST);
      CRM_Core_Error::debug('applepay_transact_get', $_GET);
      CRM_Core_Error::debug('applepay_transact_checkout_result', $return);

      // execute ipn transact
      $ipn = new CRM_Core_Payment_BaseIPN();
      $input = $ids = $objects = array();
      if(!empty($participant_id) && !empty($event_id)){
        $input['component'] = 'event';
        $ids['participant'] = $participant_id;
        $ids['event'] = $event_id;
      }
      else {
        if (!empty($membership_id)) {
          $ids['membership'] = $membership_id;
        }
        $input['component'] = 'contribute';
      }
      $ids['contribution'] = $contribution->id;
      $ids['contact'] = $contribution->contact_id;
      $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
      if($validate_result){
        $transaction = new CRM_Core_Transaction();
        if($return['is_success']){
          $input['payment_instrument_id'] = $contribution->payment_instrument_id;
          $input['amount'] = $contribution->amount;
          $objects['contribution']->receive_date = date('YmdHis');
          $objects['contribution']->trxn_id = $ids['contribution']; // Workaround, should use MerchantOrderNo from transact result
          $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction);

          $result = array('is_success' => 1);
        }else{
          $ipn->failed($objects, $transaction, $error);
          $note = $error . $return['message'];
          self::addNote($note, $contribution);
          $result = array('is_success' => 0);
        }
      }
    }

    echo json_encode($return);
    CRM_Utils_System::civiExit();
  }

  static function addNote($note, &$contribution){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s "). ts("Transaction record").": \n\n".$note."\n===============================\n";
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

  static function getAdminFields($ppDAO){
    $text = ts('If the provider needs server IP address, the IP address of this website is %1', array(1 => gethostbyname($_SERVER['HTTP_HOST'])));
    CRM_Core_Session::setStatus($text);
    return array(
      array('name' => 'user_name',
        'label' => $ppDAO->user_name_label,
      ),
      array('name' => 'password',
        'label' => $ppDAO->password_label,
      ),
      array('name' => 'signature',
        'label' => $ppDAO->signature_label,
      ),
      array('name' => 'subject',
        'label' => $ppDAO->subject_label,
      ),
      array('name' => 'url_site',
        'label' => ts('LinePay Channel ID'),
      ),
      array('name' => 'url_api',
        'label' => ts('LinePay Channel Secret Key'),
      ),
    );
  }

  static function doCheckValidationUrl($url, $isTest = FALSE, &$host = NULL) {
    $isPass = false;

    if (!preg_match('/^https:\/\//', $url)) {
      return $isPass;
    }
    $validateUrl = str_replace("https://", "", $url);
    $validateUrl = preg_replace("/\/.+$/", "", $validateUrl);

    if ($isTest) {
      $accessList = array(
        "apple-pay-gateway-cert.apple.com" => "17.171.85.7",
        "cn-apple-pay-gateway-cert.apple.com" => "101.230.204.235",
      );
    }
    else {
      $accessList = array(
        "apple-pay-gateway-nc-pod1.apple.com" => "17.171.78.7",
        "apple-pay-gateway-nc-pod2.apple.com" => "17.171.78.71",
        "apple-pay-gateway-nc-pod3.apple.com" => "17.171.78.135",
        "apple-pay-gateway-nc-pod4.apple.com" => "17.171.78.199",
        "apple-pay-gateway-nc-pod5.apple.com" => "17.171.79.12",
        "apple-pay-gateway-pr-pod1.apple.com" => "17.141.128.7",
        "apple-pay-gateway-pr-pod2.apple.com" => "17.141.128.71",
        "apple-pay-gateway-pr-pod3.apple.com" => "17.141.128.135",
        "apple-pay-gateway-pr-pod4.apple.com" => "17.141.128.199",
        "apple-pay-gateway-pr-pod5.apple.com" => "17.141.129.12",
        "apple-pay-gateway-nc-pod1-dr.apple.com" => "17.171.78.9",
        "apple-pay-gateway-nc-pod2-dr.apple.com" => "17.171.78.73",
        "apple-pay-gateway-nc-pod3-dr.apple.com" => "17.171.78.137",
        "apple-pay-gateway-nc-pod4-dr.apple.com" => "17.171.78.201",
        "apple-pay-gateway-nc-pod5-dr.apple.com" => "17.171.79.13",
        "apple-pay-gateway-pr-pod1-dr.apple.com" => "17.141.128.9",
        "apple-pay-gateway-pr-pod2-dr.apple.com" => "17.141.128.73",
        "apple-pay-gateway-pr-pod3-dr.apple.com" => "17.141.128.137",
        "apple-pay-gateway-pr-pod4-dr.apple.com" => "17.141.128.201",
        "apple-pay-gateway-pr-pod5-dr.apple.com" => "17.141.129.13",
        "cn-apple-pay-gateway-sh-pod1.apple.com" => "101.230.204.232",
        "cn-apple-pay-gateway-sh-pod1-dr.apple.com" => "101.230.204.233",
        "cn-apple-pay-gateway-sh-pod2.apple.com" => "101.230.204.242",
        "cn-apple-pay-gateway-sh-pod2-dr.apple.com" => "101.230.204.243",
        "cn-apple-pay-gateway-sh-pod3.apple.com" => "101.230.204.240",
        "cn-apple-pay-gateway-sh-pod3-dr.apple.com" => "101.230.204.241",
        "cn-apple-pay-gateway-tj-pod1.apple.com" => "60.29.205.104",
        "cn-apple-pay-gateway-tj-pod1-dr.apple.com" => "60.29.205.105",
        "cn-apple-pay-gateway-tj-pod2.apple.com" => "60.29.205.106",
        "cn-apple-pay-gateway-tj-pod2-dr.apple.com" => "60.29.205.107",
        "cn-apple-pay-gateway-tj-pod3.apple.com" => "60.29.205.108",
        "cn-apple-pay-gateway-tj-pod3-dr.apple.com" => "60.29.205.109",
      );
    }

    $host = gethostbyname($validateUrl);
    if ($accessList[$validateUrl] == $host) {
      $isPass = TRUE;
    }
    else if ($validateUrl == 'apple-pay-gateway.apple.com' && in_array($host, $accessList)) {
      $isPass = TRUE;
    }

    return $isPass;
  }

  static function getSyncDataUrl ($contributionId) {
    $payment_instrument_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'payment_instrument_id');
    $instrument_options = CRM_Core_OptionGroup::values('payment_instrument', FALSE);
    $instrument = $instrument_options[$payment_instrument_id];
    if (strtolower($instrument) == 'line pay') {
      $get = $_GET;
      unset($get['q']);
      $query = http_build_query($get);
      $sync_url = CRM_Utils_System::url("civicrm/linepay/query", $query);
    }
    else {
      $sync_url = NULL;
    }
    return $sync_url;
  }
}

