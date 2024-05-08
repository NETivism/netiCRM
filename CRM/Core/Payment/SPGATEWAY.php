<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_SPGATEWAY extends CRM_Core_Payment {
  const EXPIRE_DAY = 7;
  const MAX_EXPIRE_DAY = 180;
  const RESPONSE_TYPE = 'JSON';
  const MPG_VERSION = '1.2';
  const RECUR_VERSION = '1.0';
  const QUERY_VERSION = '1.1';
  const REAL_DOMAIN = 'https://core.newebpay.com';
  const TEST_DOMAIN = 'https://ccore.newebpay.com';
  const URL_SITE = '/MPG/mpg_gateway';
  const URL_API = '/API/QueryTradeInfo';
  const URL_RECUR = '/MPG/period';
  const URL_CREDITBG = "/API/CreditCard";

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  protected static $_mode = NULL;

  public static $_hideFields = array('invoice_id');

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = NULL;

  public static $_statusMap = array(
    // 3 => 'terminate',   // Can't undod. Don't Use
    1 => 'suspend',
    5 => 'restart',
    7 => 'suspend',
  );

  public static $_unitMap = array(
    'year' => 'Y',
    'month' => 'M',
  );

  private static $_recurEditAPIVersion = '1.1';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  private $_config = NULL;
  private $_processorName = NULL;

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
    $this->_processorName = ts('Spgateway');
    $config = &CRM_Core_Config::singleton();
    $this->_config = $config;
  }

  public static function getEditableFields($paymentProcessor = NULL, $form = NULL) {
    if (empty($paymentProcessor)) {
      $returnArray = array();
    }
    else {
      if ($paymentProcessor['url_recur'] == 1) {
        // $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
        // Enable Installments field after spgateway update.
        $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
      }
    }
    if (!empty($form)) {
      $recur_id = $form->get('id');
      if ($recur_id) {
        $sql = "SELECT LENGTH(trxn_id) FROM civicrm_contribution_recur WHERE id = %1";
        $params = array( 1 => array($recur_id, 'Positive'));
        $length = CRM_Core_DAO::singleValueQuery($sql, $params);
        if ($length >= 30 || empty($length)) {
          $returnArray[] = 'trxn_id';
        }
        // Refs 35835, recur should switch to in_process as canceled, and no use neweb recur IPN.
        if ($paymentProcessor['url_recur'] != 1) {
          $sql = "SELECT contribution_status_id FROM civicrm_contribution_recur WHERE id = %1";
          $statusId = CRM_Core_DAO::singleValueQuery($sql, $params);
          if ($statusId == 3) {
            $returnArray[] = 'contribution_status_id';
            $form->assign('set_active_only', 1);
          }
        }
      }
    }

    return $returnArray;
  }

  public static function postBuildForm($form) {
    $form->addDate('cycle_day_date', FALSE, FALSE, array('formatType' => 'custom', 'format' => 'mm-dd'));
    $cycleDay = &$form->getElement('cycle_day');
    unset($cycleDay->_attributes['max']);
    unset($cycleDay->_attributes['min']);
    if (!empty($form->get('id'))) {
      $installment = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $form->get('id'), 'installments');
      if (!empty($installment)) {
        $form->set('original_installments', $installment);
        $form->addFormRule(array('CRM_Core_Payment_SPGATEWAY', 'validateInstallments'), $form);
      }
    }
  }

  public static function validateInstallments($fields, $ignore, $form) {
    $errors = array();
    $pass = TRUE;
    $contribution_status_id = $fields['contribution_status_id'];
    $installments = $fields['installments'];
    $original_installments = $form->get('original_installments');
    if ($contribution_status_id == 5 && !empty($original_installments) && $installments <= 0) {
      $pass = FALSE;
    }
    if (!$pass) {
      $errors['installments'] = ts('Installments should be greater than zero.');
    }
    return $errors;
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
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_SPGATEWAY($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
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
   * Sets appropriate parameters for checking out to google
   *
   * @param array $params  name value pair of contribution datat
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }
    $is_test = $this->_mode == 'test' ? 1 : 0;
    if (isset($this->_paymentForm) && get_class($this->_paymentForm) == 'CRM_Contribute_Form_Payment_Main') {
      if (empty($params['email-5'])) {
        // Retrieve email of billing type or primary.
        $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
        $bltID = array_search('Billing', $locationTypes);
        if (!$bltID) {
          return CRM_Core_Error::statusBounce(ts('Please set a location type of %1', array(1 => 'Billing')));
        }
        $fields = array();
        $fields['email-'.$bltID] = 1;
        $fields['email-Primary'] = 1;
        $default = array();

        CRM_Core_BAO_UFGroup::setProfileDefaults($params['contactID'], $fields, $default);
        if (!empty($default['email-'.$bltID])) {
          $params['email-5'] = $default['email-'.$bltID];
        }
        elseif (!empty($default['email-Primary'])) {
          $params['email-5'] = $default['email-Primary'];
        }
      }
      $params['item_name'] = $params['description'];
    }

    $instrumentId = $params['civicrm_instrument_id'];
    $options = array(1 => array( $instrumentId, 'Integer'));
    $instrumentName = CRM_Core_DAO::singleValueQuery("SELECT v.name FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'payment_instrument' AND v.is_active = 1 AND v.value = %1;", $options);
    $spgatewayInstruments = self::instruments('code');
    $instrumentCode = $spgatewayInstruments[$instrumentName];
    if (empty($instrumentCode)) {
      // For google pay
      $instrumentCode = $instrumentName;
    }
    $formKey = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$params['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$params['qfKey'];

    // The first, we insert every contribution into record. After this, we'll use update for the record.
    $exists = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_spgateway WHERE cid = %1", array(
      1 => array($params['contributionID'], 'Integer'),
    ));
    if (!$exists) {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_spgateway (cid) VALUES (%1)", array(
        1 => array($params['contributionID'], 'Integer'),
      ));
    }

    if($instrumentCode == 'Credit' || $instrumentCode == 'WebATM'){
      $isPayLater = FALSE;
    }
    else{
      $isPayLater = TRUE;

      // Set participant status to 'Pending from pay later', Accupied the seat.
      if($params['participantID']){
        $participantStatus = CRM_Event_PseudoConstant::participantStatus();
        if($newStatus = array_search('Pending from pay later', $participantStatus)){
          CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $params['participantID'], 'status_id', $newStatus, 'id');
          $cancelledStatus = array_search('Cancelled', $participantStatus);
          $sql = 'SELECT id FROM civicrm_participant WHERE registered_by_id = %1 AND status_id != %2';
          $paramsRegisteredBy = array(
            1 => array($params['participantID'], 'Integer'),
            2 => array($cancelledStatus, 'Integer'),
          );
          $dao = CRM_Core_DAO::executeQuery($sql, $paramsRegisteredBy);
          while($dao->fetch()){
            CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $dao->id, 'status_id', $newStatus, 'id');
          }
        }
      }
    }

    // now process contribution to save some default value
    $contrib_params = array( 'id' => $params['contributionID'] );
    $contrib_values = $contrib_ids = array();
    CRM_Contribute_BAO_Contribution::getValues($contrib_params, $contrib_values, $contrib_ids);
    if($params['civicrm_instrument_id']){
      $contrib_values['payment_instrument_id'] = $params['civicrm_instrument_id'];
    }
    $contrib_values['is_pay_later'] = $isPayLater;
    $contrib_values['trxn_id'] = self::generateTrxnId($is_test, $params['contributionID']);
    $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

    // Inject in quickform sessions
    // Special hacking for display trxn_id after thank you page.
    $_SESSION['CiviCRM'][$formKey]['params']['trxn_id'] = $contribution->trxn_id;
    $_SESSION['CiviCRM'][$formKey]['params']['is_pay_later'] = $isPayLater;
    $params['trxn_id'] = $contribution->trxn_id;

    $arguments = $this->prepareOrderParams($contribution, $params, $instrumentCode, $formKey);
    if(!$contrib_values['is_recur']){
      CRM_Core_Payment_SPGATEWAYAPI::checkMacValue($arguments, $this->_paymentProcessor);
    }
    CRM_Core_Error::debug_var('spgateway_post_data_', $arguments);
    /* TODO: detect this sh*t
    // making redirect form
    $alter = array(
      'module' => 'civicrm_spgateway',
      'billing_mode' => $this->_paymentProcessor['billing_mode'],
      'params' => $arguments,
    );
    drupal_alter('civicrm_checkout_params', $alter);
    */
    print $this->redirectForm($arguments);
    CRM_Utils_System::civiExit();
  }

  /**
   * Migrate from _civicrm_spgateway_order
   *
   * Prepare order form element
   *
   * @param object $contribution
   * @param array $vars
   * @param object $paymentProcessor
   * @param string $instrumentCode
   * @param string $formKey
   * @return void
   */
  function prepareOrderParams(&$contribution, &$vars, $instrumentCode, $formKey){
    global $tsLocale;

    // url
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
    $notifyURL= CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, 'spgateway/ipn/'.$instrumentCode);
    $baseURL= CRM_Utils_System::currentPath();
    $urlParams = array( "_qf_ThankYou_display" => "1" , "qfKey" => $vars['qfKey'], );
    $thankyouURL = CRM_Utils_System::url($baseURL, http_build_query($urlParams), TRUE);

    $component = !empty($ids['eventID']) ? 'event' : 'contribution';

    // parameter
    if($component == 'event' && !empty($_SESSION['CiviCRM'][$formKey])){
      $values =& $_SESSION['CiviCRM'][$formKey]['values']['event'];
    }
    else{
      $values =& $_SESSION['CiviCRM'][$formKey]['values'];
    }

    // max 180 days of expire
    $baseTime = time() + 86400; // because not include today
    if (!empty($vars['payment_expired_timestamp'])) {
      $hours = ($vars['payment_expired_timestamp'] - $baseTime) / 3600;
    }
    else {
      $hours = (CRM_Core_Payment::calcExpirationDate(0) - $baseTime) / 3600;
    }
    if ($hours < 24) {
      $values['expiration_day'] = 1;
    }
    elseif ($hours > 24 * self::MAX_EXPIRE_DAY ) {
      $values['expiration_day'] = self::MAX_EXPIRE_DAY;
    }
    elseif(!empty($hours)){
      $values['expiration_day'] = ceil($hours/24);
    }

    // building vars
    $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];

    $itemDescription = $vars['description'];
    $itemDescription .= ($vars['description'] == $vars['item_name'])?'':':'.$vars['item_name'];
    $itemDescription .= ':'.floatval($vars['amount']);
    $itemDescription = preg_replace('/[^[:alnum:][:space:]]/u', ' ', $itemDescription);

    if(!$vars['is_recur']){
      $args = array(
        'MerchantID' => $this->_paymentProcessor['user_name'],
        'RespondType' => self::RESPONSE_TYPE,
        'TimeStamp' => time(),
        'Version' => self::MPG_VERSION,
        'Amt' => $amount,
        'NotifyURL' => $notifyURL,
        'Email' => $vars['email-5'],
        'LoginType' => '0',
        'ItemDesc' => $itemDescription,
        'MerchantOrderNo' => $vars['trxn_id'],
      );
      if ($this->_paymentProcessor['is_test']) {
        $args['#url'] = self::TEST_DOMAIN.self::URL_SITE;
      }
      else {
        $args['#url'] = self::REAL_DOMAIN.self::URL_SITE;
      }

      switch($instrumentCode){
        case 'ATM':
          $args['VACC'] = 1;
          $day = !empty($values['expiration_day']) ? $values['expiration_day'] : self::EXPIRE_DAY;
          $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          $args['CustomerURL'] = $thankyouURL;
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          break;
        case 'BARCODE':
          $args['BARCODE'] = 1;
          $day = !empty($values['expiration_day']) ? $values['expiration_day'] : self::EXPIRE_DAY;
          $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          $args['CustomerURL'] = $thankyouURL;
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          break;
        case 'CVS':
          $args['CVS'] = 1;
          if($instrumentCode == 'CVS' && !empty($values['expiration_day'])) {
            $day = !empty($values['expiration_day']) ? $values['expiration_day'] : self::EXPIRE_DAY;
            $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          }
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          // $args['Desc_1'] = '';
          // $args['Desc_2'] = '';
          // $args['Desc_3'] = '';
          // $args['Desc_4'] = '';

          #ATM / CVS / BARCODE
          $args['CustomerURL'] = $thankyouURL;
          break;
        case 'WebATM':
          $args['WEBATM'] = 1;
          $args['ReturnURL'] = $thankyouURL;
          break;
        case 'Credit':
          $args['CREDIT'] = 1;
          $args['ReturnURL'] = $thankyouURL;
          break;
        case 'GooglePay':
          $args['ANDROIDPAY'] = 1;
          $args['ReturnURL'] = $thankyouURL;
          break;
      }

      if($tsLocale == CRM_Core_Config::SYSTEM_LANG){
        $args['LangType'] = 'en';
      }
      // Use hook_civicrm_alterPaymentProcessorParams
      $mode = $this->_paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = CRM_Core_Payment::singleton($mode, $this->_paymentProcessor, CRM_Core_DAO::$_nullObject);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $vars, $args);
    }
    else{
      $data = array(
        'MerchantID' => $this->_paymentProcessor['user_name'],
        'RespondType' => self::RESPONSE_TYPE,
        'TimeStamp' => time(),
        'Version' => self::RECUR_VERSION,
        'Amt' => $amount,
        'NotifyURL' => $notifyURL."&qfKey=".$vars['qfKey'],
        'PayerEmail' => $vars['email-5'],
        'LoginType' => '0',
        'MerOrderNo' => $vars['trxn_id'],
        'ProdDesc' => $itemDescription,
        'PeriodAmt' => $amount,
        'PeriodStartType' => 2,
        'ReturnURL' => $thankyouURL,
        'PaymentInfo' => 'N',
        'OrderInfo' => 'N',
      );
      $period = strtoupper($vars['frequency_unit'][0]);

      if($vars['frequency_unit'] == 'month'){
        $frequency_interval = $vars['frequency_interval'] > 12 ? 12 : $vars['frequency_interval'];
        $data['PeriodType'] = 'M';
        $data['PeriodPoint'] = date('d');
      }
      elseif($vars['frequency_unit'] == 'week'){
        $frequency_interval = (7 * $vars['frequency_interval']) > 365 ? 365 : ($vars['frequency_interval'] * 7);
        $data['PeriodType'] = 'W';
      }
      elseif($vars['frequency_unit'] == 'year'){
        $frequency_interval = 1;
        $data['PeriodType'] = 'Y';
        $data['PeriodPoint'] = date('md');
      }
      if(empty($frequency_interval)){
        $frequency_interval = 1;
      }
      // $data['PeriodTimes'] = $frequency_interval;
      if($vars['frequency_unit'] == 'year'){
        $data['PeriodTimes'] = empty($vars['installments']) ? 9 : $vars['installments'];
      }else{
        $data['PeriodTimes'] = empty($vars['installments']) ? 99 : $vars['installments']; // support endless
      }
      if($tsLocale == CRM_Core_Config::SYSTEM_LANG){
        $data['LangType'] = 'en';
      }
      // Use hook_civicrm_alterPaymentProcessorParams
      $mode = $this->_paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = CRM_Core_Payment::singleton($mode, $this->_paymentProcessor, CRM_Core_DAO::$_nullObject);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $vars, $data);
      // Encrypt Recurring Request.
      $str = http_build_query($data, '', '&');
      $strPost = CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($str, $this->_paymentProcessor);
      $args['PostData_'] = $strPost;
      $args['MerchantID_'] = $this->_paymentProcessor['user_name'];
      if ($this->_paymentProcessor['is_test']) {
        $args['#url'] = self::TEST_DOMAIN.self::URL_RECUR;
      }
      else {
        $args['#url'] = self::REAL_DOMAIN.self::URL_RECUR;
      }
    }


    return $args ;
  }


  private function redirectForm($vars){
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Expires: 0');

    $output = "";

    $js = 'document.forms.redirect.submit();';
    $output .= '<form action="'.$vars['#url'].'" name="redirect" method="post" id="redirect-form">';
    foreach($vars as $k=>$p){
      if($k[0] != '#'){
        $output .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
      }
    }
    $output .= '</form>';
    return <<<EOT
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  </head>
  <body>
    {$output}
    <script type="text/javascript">
    {$js}
    </script>
  </body>
  <html>
EOT;
  }

  public static function mobileCheckout($type, $post, $objects) {
    $contribution = $objects['contribution'];
    $merchantPaymentProcessor = $objects['payment_processor'];

    if($type = 'applepay') {
      $email = new CRM_Core_DAO_Email();
      $email->contact_id = $contribution->contact_id;
      $email->is_primary = true;
      $email->find(TRUE);

      $token = urlencode(json_encode($post['token']));
      $is_test = $contribution->is_test;

      $params = array(
        'TimeStamp' => time(),
        'Version' => '1.0',
        'MerchantOrderNo' => CRM_Core_Payment_SPGATEWAY::generateTrxnId($is_test, $contribution->id),
        'Amt' => $contribution->total_amount,
        'ProdDesc' => $post['description'],
        'PayerEmail' => $email->email,
        'CardNo' => '',
        'Exp' => '',
        'CVC' => '',
        'APPLEPAY' => $token,
        'APPLEPAYTYPE' => '02',
      );
      CRM_Core_Error::debug('applepay_transact_curl_params_before_encrypt', $params);

      $data = CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt(http_build_query($params), get_object_vars($merchantPaymentProcessor));

      $data = array(
        'MerchantID_' => $merchantPaymentProcessor->user_name,
        'PostData_' => $data,
        'Pos_' => 'JSON',
      );
      if($contribution->is_test){
        $url = CRM_Core_Payment_SPGATEWAY::TEST_DOMAIN.CRM_Core_Payment_SPGATEWAY::URL_CREDITBG;
      }else{
        $url = CRM_Core_Payment_SPGATEWAY::REAL_DOMAIN.CRM_Core_Payment_SPGATEWAY::URL_CREDITBG;
      }

      CRM_Core_Error::debug('applepay_transact_curl_data_after_encrypt', $data);

      $ch = curl_init($url);
      $opt = array();
      $opt[CURLOPT_RETURNTRANSFER] = TRUE;
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_POSTFIELDS] = $data;
      $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
      curl_setopt_array($ch, $opt);

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
      curl_close($ch);
      CRM_Core_Error::debug('applepay_transact_curl_error', $curlError);

      $result = json_decode($result);
      CRM_Core_Payment_SPGATEWAYAPI::writeRecord($contribution->id, get_object_vars($result));
      $return = array();
      if($result->Status == 'SUCCESS'){
        $return['is_success'] = true;
      }
      $return['message'] = $result->Message;
    }
    return $return;
  }

  function doUpdateRecur($params, $debug = FALSE) {
    if ($debug) {
      CRM_Core_Error::debug('SPGATEWAY doUpdateRecur $params', $params);
    }
    // For no use neweb recur API condition, return original parameters.
    if ($this->_paymentProcessor['url_recur'] != 1) {
      return $params;
    }
    if (empty($params['contribution_recur_id'])) {
      CRM_Core_Error::fatal('Missing contribution recur ID in params');
    }
    else {
      // Prepare params
      $recurResult = array();

      if (preg_match('/^[a-f0-9]{32}$/', $params['trxn_id']) || empty($params['trxn_id'])) {
        // trxn_id is hash, equal to the situation without trxn_id
        $recurResult['is_error'] = 1;
        $recurResult['msg'] = ts('Transaction ID must equal to the Order serial of NewebPay.');
        $recurResult['msg'] .= ts('There are no any change.');
        return $recurResult;
      }

      $apiConstructParams = array(
        'paymentProcessor' => $this->_paymentProcessor,
        'isTest' => $this->_mode == 'test' ? 1 : 0,
      );

      $sql = "SELECT r.trxn_id AS period_no, c.trxn_id AS merchant_id FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1";
      $sqlParams = array( 1 => array($params['contribution_recur_id'], 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      while ($dao->fetch()) {
        if (substr($dao->merchant_id, 0, 2) == 'r_') {
          // Condition for old neweb transfer to current.
          list($ignore1, $merchantId, $ignore2) = explode('_', $dao->merchant_id);
        }
        else {
          list($merchantId, $ignore) = explode('_', $dao->merchant_id);
        }
      }

      // If status is changed, Send request to alter status API.

      if (!empty($params['contribution_status_id'])) {
        $apiConstructParams['apiType'] = 'alter-status';
        $spgatewayAPI = new CRM_Core_Payment_SPGATEWAYAPI($apiConstructParams);
        $newStatusId = $params['contribution_status_id'];

        /*
        * $requestParams = array(
        *    'AlterStatus'          => Positive(7 => suspend, 3 => terminate, 5 => restart),
        * )
        */
        $requestParams = array(
          'Version' => self::$_recurEditAPIVersion,
          'MerOrderNo' => $merchantId,
          'PeriodNo' => $params['trxn_id'],
          'AlterType' => self::$_statusMap[$newStatusId],
        );
        $apiAlterStatus = clone $spgatewayAPI;
        $recurResult = $apiAlterStatus->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterType'] = $apiAlterStatus;
        }

        if (!empty($recurResult['response_status'])) {
          if (in_array($recurResult['response_status'], array('PER10062', 'PER10064'))) {
            // Neweb is canceled. Set finished if status is setting to finished.
            if ($newStatusId == 1) {
              $recurResult['contribution_status_id'] = $newStatusId;
            }
            else {
              $recurResult['msg'] .=  "\n". ts('The contribution has been canceled.');
              $recurResult['note_body'] = $recurResult['msg'];
              $recurResult['contribution_status_id'] = 3;
            }
          }
          else {
            // Status is 'PER10061', 'PER10063'. Set to which admin is selected.
            $recurResult['contribution_status_id'] = $newStatusId;
          }
        }
        if (!empty($recurResult['is_error'])) {
          // There are error msg in $recurResult['msg']
          $errResult = $recurResult;
          return $errResult;
        }
      }

      // Send alter other property API.

      $apiConstructParams['apiType'] = 'alter-amt';
      $spgatewayAPI = new CRM_Core_Payment_SPGATEWAYAPI($apiConstructParams);
      $isChangeRecur = FALSE;
      $requestParams = array(
        'Version' => self::$_recurEditAPIVersion,
        'MerOrderNo' => $merchantId,
        'PeriodNo' => $params['trxn_id'],
      );

      /*
      * $requestParams = array(
      *    'AlterAmt'             => Positive,
      *    'PeriodType'           => String(D,W,M,Y)
      *    'PeriodPoint'          => Positive(1 - 31, 0101 - 1231)
      *    'PeriodTimes'          => Positive
      * )
      */

      if (!empty($params['frequency_unit'])) {

        $requestParams['PeriodType'] = self::$_unitMap[$params['frequency_unit']];
        $isChangeRecur = TRUE;
      }

      if (!empty($params['cycle_day'])) {
        if (empty($requestParams['PeriodType'])) {
          $unit = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contribution_recur_id'], 'frequency_unit');
          $requestParams['PeriodType'] = self::$_unitMap[$unit];
        }
        $isChangeRecur = TRUE;
      }
      if (!empty($requestParams['PeriodType'])) {
        if ($requestParams['PeriodType'] == 'M') {
          $requestParams['PeriodPoint'] = sprintf('%02d', $params['cycle_day']);
        }
        elseif ($requestParams['PeriodType'] == 'Y') {
          $requestParams['PeriodPoint'] = sprintf('%04d', $params['cycle_day']);
        }
      }
      if (!empty($params['amount'])) {
        $requestParams['AlterAmt'] = $params['amount'];
        $isChangeRecur = TRUE;
      }
      if (!empty($params['installments'])) {
        $requestParams['PeriodTimes'] = $params['installments'];
        $isChangeRecur = TRUE;
      }

      if ($debug) {
        CRM_Core_Error::debug('SPGATEWAY doUpdateRecur $requestParams', $requestParams);
      }

      /**
       * Send Request.
       */
      if ($isChangeRecur) {
        $apiOthers = clone $spgatewayAPI;
        $recurResult2 = $apiOthers->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterMnt'] = $apiOthers;
          CRM_Core_Error::debug('SPGATEWAY doUpdateRecur $apiOthers', $apiOthers);
        }
        if (is_array($recurResult2)) {
          $recurResult += $recurResult2;
        }
      }

      if (!empty($recurResult['is_error'])) {
        // There are error msg in $recurResult['msg']
        $errResult = $recurResult;
        return $errResult;
      }
      CRM_Core_Error::debug('SPGATEWAY doUpdateRecur $recurResult', $recurResult);
      if (!empty($recurResult['installments'] && $recurResult['installments'] != $requestParams['PeriodTimes'])) {
        $recurResult['note_body'] .= ts('Selected installments is %1.', array(1 => $requestParams['PeriodTimes'])).ts('Modify installments by Newebpay data.');
      }
    }

    if ($debug) {
      CRM_Core_Error::debug('Payment Spgateway doUpdateRecur $recurResult', $recurResult);
    }
    return $recurResult;
  }

  function cancelRecuringMessage($recurID){
    $sql = "SELECT p.payment_processor_type, p.url_recur FROM civicrm_payment_processor p INNER JOIN civicrm_contribution_recur r ON p.id = r.processor_id WHERE r.id = %1";
    $params = array( 1 => array($recurID, 'Positive'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      if ($dao->payment_processor_type == 'SPGATEWAY' && $dao->url_recur == 1 ) {
        $msg = '<p>'.ts("You have enable NewebPay recurring API. Please use edit page to cancel recurring contribution.").'</p><script>cj(".ui-dialog-buttonset button").hide();</script>';
        return $msg;
      }
    }
    if (function_exists("_civicrm_spgateway_cancel_recuring_message")) {
      return _civicrm_spgateway_cancel_recuring_message(); 
    }else{
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
  }

  /**
   * Function called from contributionRecur page to show tappay detail information
   * 
   * @param int @contributionId the contribution id
   * 
   * @return array The label as the key to value.
   */
  public static function getRecordDetail($contributionId) {
    require_once 'CRM/Core/Smarty/resources/String.php';
    $smarty = CRM_Core_Smarty::singleton();
    civicrm_smarty_register_string_resource();
    $returnTables[ts("Manually Synchronize")] = $smarty->fetch('string: {$form.$update_notify.html}');
    return $returnTables;
  }

  /**
   * Behavior after pressed "Sync now" button.
   * 
   * @param int $id The contribution recurring ID
   * @param string $idType Means the type of the ID, value as "Contribution" or "recur"
   * @param object $form The MakingTransaction form object
   * @return void
   */
  public static function doRecurUpdate($id, $idType = 'contribution', $form = NULL) {
    if (!empty($form)) {
      $contributionId = $form->get('contributionId');
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contributionId;
      $contribution->find(TRUE);
      $trxn_id = $contribution->trxn_id;
      $explodedTrxnId = explode('_', $trxn_id);
      $isAddedNewContribution = FALSE;
      // If current contribution status is waited, solved current contribution.
      if ($contribution->contribution_status_id == 2) {
        if (count($explodedTrxnId) == 1) {
          $trxn_id .= '_1';
        }
      }
      // If current contribution status is completed, find next contribution.
      if ($contribution->contribution_status_id == 1) {
        $isAddedNewContribution = TRUE;
        if ($explodedTrxnId[0] == 'r' && !empty($explodedTrxnId[2])) {
          // For old neweb contribution trxn id, format: r_123_4
          $trxn_id = $explodedTrxnId[0] . '_' . $explodedTrxnId[1] . '_' . ($explodedTrxnId[2] + 1);
        }
        else {
          // For current spgateway trxn id, format: 1234_5
          $trxn_id = $explodedTrxnId[0] . '_' . ($explodedTrxnId[1] + 1);
        }
      }

      $result = self::recurSyncTransaction($trxn_id, TRUE);
      $session = CRM_Core_Session::singleton();
      if (!empty($result)) {
        if ($isAddedNewContribution) {
          if (!empty($result->Result->OrderNo)) {
            $orderNo = $result->Result->OrderNo;
          }
          $session->setStatus(ts("The contribution with transaction ID: %1 has been created and updated.", array(
            1 => $orderNo,
          )));
        }
        else {
          $session->setStatus(ts("%1 status has been updated to %2.", array(
            1 => ts("Recurring Contribution"),
            2 => ts("In Progress"),
          )));
        }
      }
      else {
        if ($isAddedNewContribution) {
          $session->setStatus(ts("The contribution with transaction ID: %1 can't find from Newebpay API.", array(
            1 => $trxn_id,
          )));
        }
        else {
          $session->setStatus(ts("There are no any change."));
        }
      }
    }
  }

  public static function doSingleQueryRecord($contributionId = NULL, $order = NULL) {
    $get = $_GET;
    unset($get['q']);
    if (!is_numeric($contributionId) || empty($contributionId)) {
      $cid = $get['id'];
    }
    else {
      $cid = $contributionId;
    }
    $origDAO = new CRM_Contribute_DAO_Contribution();
    $origDAO->id = $cid;
    $origDAO->find(TRUE);
    $trxnId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $cid, 'trxn_id');
    if (empty($trxnId)) {
      $resultMessage = ts("The contribution with transaction ID: %1 can't find from Newebpay API.", array(1 => $cid));
    }
    else {
      if (!empty($order)) {
        // this is for ci testing or something we already had response
        // should be object or associated array
        self::syncTransaction($trxnId, $order);
      }
      else {
        self::syncTransaction($trxnId);
      }
      $resultMessage = ts("Synchronizing to %1 server success.", array(1 => ts("NewebPay")));
      $updatedDAO = new CRM_Contribute_DAO_Contribution();
      $updatedDAO->id = $cid;
      $updatedDAO->find(TRUE);
      $diffContribution = array();
      if ($updatedDAO->contribution_status_id != $origDAO->contribution_status_id) {
        $status = CRM_Contribute_PseudoConstant::contributionStatus();
        $diffContribution[ts('Contribution Status')] = array($status[$origDAO->contribution_status_id], $status[$updatedDAO->contribution_status_id]);

        // Check it will send Email.
        $components = CRM_Contribute_BAO_Contribution::getComponentDetails(array($cid));
        $contributeComponent = $components[$cid];
        $componentName = $contributeComponent['component'];
        $pageId = $contributeComponent['page_id'];
        if ($componentName == 'contribute' && !empty($pageId)) {
          $pageParams = array(1 => array( $pageId, 'Positive'));
          $isEmailReceipt = CRM_Core_DAO::singleValueQuery("SELECT is_email_receipt FROM civicrm_contribution_page WHERE id = %1", $pageParams);
          if ($isEmailReceipt) {
            $diffContribution[] = ts('A notification email has been sent to the supporter.');
          }
        }

        // Check if the SMS is sent.
        $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
        $activitySMSParams = array(
          'source_record_id' => $cid,
          'activity_type_id' => CRM_Utils_Array::key('Contribution SMS', $activityType),
        );
        $smsActivity = new CRM_Activity_DAO_Activity();
        $smsActivity->copyValues($activitySMSParams);
        if ($smsActivity->find(TRUE)) {
          $diffContribution[] = ts('SMS Sent');
        }
      }
      if ($updatedDAO->receive_date != $origDAO->receive_date) {
        $diffContribution[ts('Received Date')] = array($origDAO->receive_date, $updatedDAO->receive_date);
      }
      if ($updatedDAO->cancel_date != $origDAO->cancel_date) {
        $diffContribution[ts('Cancel Date')] = array($origDAO->cancel_date, $updatedDAO->cancel_date);
      }
      if ($updatedDAO->cancel_reason != $origDAO->cancel_reason) {
        $diffContribution[ts('Cancel Reason')] = array($origDAO->cancel_reason, $updatedDAO->cancel_reason);
      }
      if ($updatedDAO->receipt_id != $origDAO->receipt_id) {
        $diffContribution[ts('Receipt ID')] = array($origDAO->receipt_id, $updatedDAO->receipt_id);
      }
      if ($updatedDAO->receipt_date != $origDAO->receipt_date) {
        $diffContribution[ts('Receipt Date')] = array($origDAO->receipt_date, $updatedDAO->receipt_date);
      }
      if (empty($diffContribution)) {
        $diffContribution[] = ts("There are no any change.");
      }
    }
    // Redirect to contribution view page.
    $query = http_build_query($get);
    $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
    if (!empty($diffContribution)) {
      $resultMessage."<ul>";
      foreach ($diffContribution as $key => $value) {
        if ($key && is_array($value)) {
          $resultMessage .= "<li><span>{$key}: </span>".CRM_Utils_Array::implode(' ==> ', $value)."</li>";
        }
        else {
          $resultMessage .= "<li>{$value}</li>";
        }
      }
      $resultMessage.="</ul>";
    }
    if (!isset($order)) {
      CRM_Core_Session::setStatus($resultMessage);
      CRM_Utils_System::redirect($redirect);
    }
  }

  public static function getSyncDataUrl($contributionId, &$form = NULL) {
    $get = $_GET;
    unset($get['q']);
    $query = http_build_query($get);
    $sync_url = CRM_Utils_System::url("civicrm/spgateway/query", $query);
    $params = array( 1 => array( $contributionId, 'Positive'));
    $statusId = CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution WHERE id = %1", $params);
    if ($statusId == 2) {
      $updateDataArray = array(ts('Contribution Status'), ts('Receive Date'), );
      $components = CRM_Contribute_BAO_Contribution::getComponentDetails(array($contributionId));
      $contributeComponent = $components[$contributionId];
      $componentName = $contributeComponent['component'];
      $pageId = $contributeComponent['page_id'];
      if ($componentName == 'contribute' && !empty($pageId)) {
        $pageParams = array(1 => array( $pageId, 'Positive'));
        $isEmailReceipt = CRM_Core_DAO::singleValueQuery("SELECT is_email_receipt FROM civicrm_contribution_page WHERE id = %1", $pageParams);
        if ($isEmailReceipt) {
          $updateDataArray[] = ts('Receipt Date');
          $updateDataArray[] = ts('Receipt ID');
          $updateDataArray[] = ts('Payment Notification');
        }
        $isSendSMS = CRM_Core_DAO::singleValueQuery("SELECT is_send_sms FROM civicrm_contribution_page WHERE id = %1", $pageParams);
        if ($isSendSMS) {
          $updateDataArray[] = ts('Send SMS');
        }
      }
      $updateData = CRM_Utils_Array::implode(', ', $updateDataArray);
      $form->set('sync_data_hint', ts('If the transaction is finished, it will update the follow data by this action: %1', array(1 => $updateData)));
    }

    return $sync_url;
  }

  /**
   * The IPN warpping
   *
   * @param array $arguments Router will pass array into this function
   * @param array $post Simulate POST data
   * @param array $get Simulate GET data
   * @param bool $print print result
   * @return void
   */
  public static function doIPN($arguments, $post = NULL, $get = NULL, $print = TRUE) {
    $post = !empty($post) ? $post : $_POST;
    $get = !empty($get) ? $get : $_GET;
    if (!empty($arguments)) {
      if (is_array($arguments)) {
        $instrument = end($arguments);
      }
      elseif (is_string($arguments)){
        $instrument = $arguments;
      }
      else {
        CRM_Core_Error::debug_log_message("Spgateway: IPN Missing require argument.");
        CRM_Utils_System::civiExit();
      }
    }

    // detect variables
    if(empty($post)){
      CRM_Core_Error::debug_log_message("Spgateway: Could not find POST data from payment server.");
      CRM_Utils_System::civiExit();
    }
    else{
      // validate some post
      if (!empty($post['JSONData']) || !empty($post['Period']) || !empty($post['Result'])) {
        $ipn = new CRM_Core_Payment_SPGATEWAYIPN($post, $get);
        $result = $ipn->main($instrument);
        if(is_string($result) && $print){
          echo $result;
        }
        else{
          return $result;
        }
      }
      else {
        CRM_Core_Error::debug_log_message("Spgateway: Invlid POST data.");
        CRM_Core_Error::debug_var("spgateway_ipn_post", $post);
      }
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Migrate from _civicrm_spgateway_instrument
   *
   * @param string $type
   * @return void
   */
  public static function instruments($type = 'normal'){
    $i = array(
      'Credit Card' => array('label' => ts('Credit Card'), 'desc' => '', 'code' => 'Credit'),
      'ATM' => array('label' => ts('ATM Transfer'), 'desc' => '', 'code' => 'ATM'),
      'Web ATM' => array('label' => ts('Web ATM'), 'desc' => '', 'code' => 'WebATM'),
      'Convenient Store' => array('label' => ts('Convenient Store Barcode'), 'desc'=>'', 'code' => 'BARCODE'),
      'Convenient Store (Code)' => array('label'=> ts('Convenient Store (Code)'),'desc' => '', 'code' => 'CVS'),
    );
    if($type == 'form_name'){
      foreach($i as $name => $data){
        $form_name = preg_replace('/[^0-9a-z]+/i', '_', strtolower($name));
        $instrument[$form_name] = $data;
      }
      return $instrument;
    }
    elseif($type == 'code'){
      foreach($i as $name =>  $data){
        $instrument[$name] = $data['code'];
      }
      return $instrument;
    }
    else{
      return $i;
    }
  }

  /**
   * Migrate from _civicrm_spgateway_trxn_id
   *
   * @param int $is_test
   * @param int $id
   * @return string
   */
  public static function generateTrxnId($is_test, $id){
    if($is_test){
      $trxnId = 'test' . substr(str_replace(array('.','-'), '', $_SERVER['HTTP_HOST']), 0, 3) . $id. 'T'. mt_rand(100, 999);
      return $trxnId;
    }
    return (string) $id;
  }

  /**
   * Migrate from civicrm_spgateway_single_contribution_sync
   *
   * Sync non-recurring transaction by specific trxn id
   *
   * @param int $inputTrxnId
   * @return bool|object
   * @param string $order
   */
  public static function syncTransaction($inputTrxnId, $order = NULL) {
    $paymentProcessorId = 0;

    // Check contribution is exists
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->trxn_id = $inputTrxnId;
    if ($contribution->find(TRUE)) {
      $paymentProcessorId = $contribution->payment_processor_id;
      if ($contribution->contribution_status_id == 1) {
        $message = ts('There are no any change.');
        return $message;
      }
    }
    // we can't support single contribution check because lake of payment processor id #TODO - logic to get payment processor id
    if (!empty($paymentProcessorId)) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($paymentProcessorId, $contribution->is_test ? 'test': 'live');

      if (strstr($paymentProcessor['payment_processor_type'], 'SPGATEWAY') && !empty($paymentProcessor['user_name'])) {
        if ($order) {
          // this is for ci testing or something we already had response
          // should be object or associated array
          $result = $order;
        }
        else {
          $amount = $contribution->total_amount;
          if ($contribution->contribution_recur_id && !strstr($inputTrxnId, '_')) {
            $trxnId = $inputTrxnId.'_1';
            $recurring_first_contribution = TRUE;
          }
          else {
            $trxnId = $inputTrxnId;
          }
          $data = array(
            'Amt' => floor($amount),
            'MerchantID' => $paymentProcessor['user_name'],
            'MerchantOrderNo' => $trxnId,
            'RespondType' => self::RESPONSE_TYPE,
            'TimeStamp' => CRM_REQUEST_TIME,
            'Version' => self::QUERY_VERSION,
          );
          $args = array('IV','Amt','MerchantID','MerchantOrderNo', 'Key');
          CRM_Core_Payment_SPGATEWAYAPI::encode($data, $paymentProcessor, $args);
          $urlApi = $contribution->is_test ? self::TEST_DOMAIN.self::URL_API : self::REAL_DOMAIN.self::URL_API;
          $result = CRM_Core_Payment_SPGATEWAYAPI::sendRequest($urlApi, $data);
        }

        // Online contribution
        // Only trigger if there are pay time in result;
        if (!empty($result) && $result->Status == 'SUCCESS' && $result->Result->TradeStatus !== '0') {
          // complex part to simulate spgateway ipn
          $ipnGet = $ipnPost = array();

          // prepare post, complex logic because recurring have different variable names
          $ipnResult = clone $result;
          if ($result->Result->TradeStatus != 1) {
            $ipnResult->Status =$result->Result->RespondCode;
          }
          $ipnResult->Message = $result->Result->RespondMsg;
          // Pass CheckCode.
          unset($ipnResult->Result->CheckCode);
          $ipnPost = (array) $ipnResult;

          if ($contribution->contribution_recur_id) {
            $ipnResult->Result->AuthAmt = $result->Result->Amt;
            unset($ipnResult->Result->Amt);
            $ipnResult->Result->OrderNo = $result->Result->MerchantOrderNo;
            list($first_id, $period_times) = explode('_', $result->Result->MerchantOrderNo);
            if(!empty($period_times) && $period_times != 1){
              $ipnResult->Result->AlreadyTimes = $period_times;
            }
            $ipnResult->Result->MerchantOrderNo = $first_id;
            $ipnResult = json_encode($ipnResult);
            $ipnPost = array('Period' => CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($ipnResult, $paymentProcessor));
          }

          // prepare get
          $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
          $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, TRUE);
          parse_str($query, $ipnGet);

          // create recurring record
          $result->_post = $ipnPost;
          $result->_get = $ipnGet;
          $result->_response = self::doIPN(array('spgateway', 'ipn', 'Credit'), $result->_post, $result->_get, FALSE);

          if ($recurring_first_contribution) {
            $contribution = new CRM_Contribute_DAO_Contribution();
            $contribution->trxn_id = $inputTrxnId;
            if ($contribution->find(TRUE) && strstr($trxnId, '_1')) {
              // The case first contribution trxn_id not append '_1' in the end.
              CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'trxn_id', $trxnId);
            }
          }
          return $result;
        }
      }
    }
    return FALSE;
  }

  /**
   * Always trying to fetch next trxn_id which not appear in CRM
   */
  /**
   * Migrate from civicrm_spgateway_recur_check
   *
   * Trying to get next transaction by given recurring id
   *
   * @param int $recurId
   * @return void
   */
  public static function recurSync($recurId) {
    $query = "SELECT trxn_id, CAST(REGEXP_REPLACE(trxn_id, '^[0-9_r]+_([0-9]+)$', '\\\\1') as UNSIGNED) as number FROM civicrm_contribution WHERE contribution_recur_id = %1 AND CAST(trxn_id as UNSIGNED) < 900000000 ORDER BY number DESC";
    $result = CRM_Core_DAO::executeQuery($query, array(1 => array($recurId, 'Integer')));
    $result->fetch();
    if(!empty($result->N)){
      // when recurring trxn_id have underline, eg oooo_1
      if (strstr($result->trxn_id, '_')) {
        list($parentTrxnId, $recurringInstallment, $oldRecurInstallment) = explode('_', $result->trxn_id);
        if ($parentTrxnId == 'r' && is_numeric($oldRecurInstallment)) {
          // for old recurring. trxn_id like 'r_12_3', $parentTrxnId = 'r', $recurringInstallment = 12, $oldRecurInstallment = 3
          $oldRecurInstallment++;
          self::recurSyncTransaction($parentTrxnId.'_'.$recurringInstallment.'_'.$oldRecurInstallment, $createContribution = TRUE);
        }
        elseif (is_numeric($recurringInstallment)) {
          // for current recurring, for trxn_id like 123_4, $parentTrxnId = 123, $recurringInstallment = 4
          $recurringInstallment++;
          self::recurSyncTransaction($parentTrxnId.'_'.$recurringInstallment, $createContribution = TRUE);
        }
      }
      // when first recurring trxn_id record without underline
      else {
        $parentTrxnId= $result->trxn_id;
        $installment = 2;
        self::recurSyncTransaction($parentTrxnId, TRUE);
      }
    }
  }

  /**
   * Migrate from civicrm_spgateway_single_check
   *
   * Create recurring transacation by specific trxn id
   * Base on spgateway / neweb response
   *
   * @param string $trxnId
   * @param bool $createContribution
   * @param string $order
   * @return object|bool
   */
  public static function recurSyncTransaction($trxnId, $createContribution = FALSE, $order = NULL) {
    $parentTrxnId = 0;
    $paymentProcessorId = 0;
    if (strstr($trxnId, '_')) {
      list($recurId, $installment, $oldInstallment) = explode('_', $trxnId);
      if ($recurId == 'r' && !empty($oldInstallment)) {
        // Old newebpay recurring, format: r_123_4
        $parentTrxnId = $recurId.'_'.$installment;
      }
      else {
        // Current spgateway recurring, format: 1234_5
        $parentTrxnId = $recurId;
      }
    }
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->trxn_id = $trxnId;
    if ($contribution->find(TRUE)) {
      $paymentProcessorId = $contribution->payment_processor_id;
      if ($contribution->contribution_status_id == 1) {
        $createContribution = FALSE; // Found, And contribution is already success.
      }
      elseif (empty($parentTrxnId) && $createContribution) {
        // First recurring or single contribution.
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->id = $trxnId;
        if ($contribution->find(TRUE)) {
          $paymentProcessorId = $contribution->payment_processor_id;
          if (!empty($contribution->contribution_recur_id)) {
            // First recurring contribution
            $trxnId = $trxnId.'_1';
          }
        }
      }
    }
    elseif($createContribution) {
      // recurring contribution
      if ($parentTrxnId) {
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->trxn_id = $parentTrxnId.'_1';
        if ($contribution->find(TRUE)) {
          $paymentProcessorId = $contribution->payment_processor_id;
        }
        else {
          $contribution = new CRM_Contribute_DAO_Contribution();
          $contribution->trxn_id = $parentTrxnId;
          if ($contribution->find(TRUE)) {
            $paymentProcessorId = $contribution->payment_processor_id;
          }
        }
      }
    }

    // we can't support single contribution check because lake of payment processor id #TODO - logic to get payment processor id
    if (!empty($paymentProcessorId)) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($paymentProcessorId, $contribution->is_test ? 'test': 'live');

      if (!empty($paymentProcessor['user_name'])) {
        if ($order) {
          // this is for ci testing or something we already had response
          // should be object or associated array
          $result = $order;
        }
        else {
          $amount = CRM_Core_DAO::singleValueQuery('SELECT amount FROM civicrm_contribution_recur WHERE id = %1', array(1 => array($contribution->contribution_recur_id, 'Positive')));
          $data = array(
            'Amt' => floor($amount),
            'MerchantID' => $paymentProcessor['user_name'],
            'MerchantOrderNo' => $trxnId,
            'RespondType' => self::RESPONSE_TYPE,
            'TimeStamp' => CRM_REQUEST_TIME,
            'Version' => self::QUERY_VERSION,
          );
          $args = array('IV','Amt','MerchantID','MerchantOrderNo', 'Key');
          CRM_Core_Payment_SPGATEWAYAPI::encode($data, $paymentProcessor, $args);
          $urlApi = $contribution->is_test ? self::TEST_DOMAIN.self::URL_API : self::REAL_DOMAIN.self::URL_API;
          $result = CRM_Core_Payment_SPGATEWAYAPI::sendRequest($urlApi, $data);
        }

        // Online contribution
        if (!empty($result) && $result->Status == 'SUCCESS') {
          if ($createContribution && $contribution->id) {
            // complex part to simulate spgateway ipn
            $ipnGet = $ipnPost = array();

            // prepare post, complex logic because recurring have different variable names
            $ipnResult = clone $result;
            if ($result->Result->TradeStatus != 1) {
              $ipnResult->Status =$result->Result->RespondCode;
            }
            $ipnResult->Message = $result->Result->RespondMsg;

            $ipnResult->Result->AuthAmt = $result->Result->Amt;
            unset($ipnResult->Result->Amt);
            unset($ipnResult->Result->CheckCode);
            $ipnResult->Result->OrderNo = $result->Result->MerchantOrderNo;
            list($first_id, $period_times) = explode('_', $result->Result->MerchantOrderNo);
            if(!empty($period_times) && $period_times != 1){
              $ipnResult->Result->AlreadyTimes = $period_times;
            }
            $ipnResult->Result->MerchantOrderNo = $first_id;
            $ipnResult = json_encode($ipnResult);
            $ipnPost = array('Period' => CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($ipnResult, $paymentProcessor));

            // prepare get
            $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
            $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
            parse_str($query, $ipnGet);

            // create recurring record
            $result->_post = $ipnPost;
            $result->_get = $ipnGet;
            $result->_response = self::doIPN(array('spgateway', 'ipn', 'Credit'), $ipnPost, $ipnGet, FALSE);
            $contribution = new CRM_Contribute_DAO_Contribution();
            $contribution->trxn_id = $parentTrxnId;
            if ($contribution->find(TRUE) && strstr($trxnId, '_1')) {
              // The case first contribution trxn_id not append '_1' in the end.
              CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'trxn_id', $trxnId);
            }
            return $result;
          }
          else {
            return $result;
          }
        }
      }
    }
    return FALSE;
  }
}
