<?php
date_default_timezone_set('Asia/Taipei');
class CRM_Core_Payment_MyPay extends CRM_Core_Payment {

  const MYPAY_REAL_DOMAIN = 'https://ka.mypay.tw';
  const MYPAY_TEST_DOMAIN = 'https://pay.usecase.cc';
  const MYPAY_URL_API = '/api/init';
  const MYPAY_RECUR_URL_API = '/api/agent';

  public static $_allowRecurUnit = ['month', 'year'];

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

  public static $_hideFields = ['invoice_id', 'trxn_id'];

  private $_contributionId = NULL;

  private $_logId = NULL;

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
  function __construct($mode, &$paymentProcessor)
  {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
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
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL)
  {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_MyPay($mode, $paymentProcessor, $paymentForm);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Provide default payment
   *
   * @param array $defaults   array to be change
   * @param object $paymen dao that will be added to payment when default is empty
   * @return void
   */
  static function buildPaymentDefault(&$default, $payment)
  {
    if ($payment->is_test > 0) {
      $default['url_api'] = CRM_Core_Payment_MyPay::MYPAY_TEST_DOMAIN . CRM_Core_Payment_MyPay::MYPAY_URL_API;
      $default['url_recur'] = CRM_Core_Payment_MyPay::MYPAY_TEST_DOMAIN . CRM_Core_Payment_MyPay::MYPAY_RECUR_URL_API;
    } else {
      $default['url_api'] = CRM_Core_Payment_MyPay::MYPAY_REAL_DOMAIN . CRM_Core_Payment_MyPay::MYPAY_URL_API;
      $default['url_recur'] = CRM_Core_Payment_MyPay::MYPAY_REAL_DOMAIN . CRM_Core_Payment_MyPay::MYPAY_RECUR_URL_API;
    }
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig()
  {
    $config = CRM_Core_Config::singleton();

    $error = [];

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (!empty($error)) {
      return CRM_Utils_Array::implode('<p>', $error);
    } else {
      return NULL;
    }
  }

  function setExpressCheckOut(&$params)
  {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getExpressCheckoutDetails($token)
  {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doExpressCheckout(&$params)
  {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doDirectPayment(&$params)
  {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /*
  function getPaymentFrame() {
    if (!empty($this->_paymentProcessor)) {
      $this->_paymentForm->add('hidden', 'prime', '');
      $this->_paymentForm->assign('button_name', $this->_paymentForm->getButtonName('next'));
      $this->_paymentForm->assign('back_button_name', $this->_paymentForm->getButtonName('back'));
      $this->_paymentForm->assign('payment_processor', $this->_paymentProcessor);
      $className = get_class($this->_paymentForm);
      $qfKey = $this->_paymentForm->get('qfKey');
      // needs payment processor keys
      // we needs these to process payByPrime
      $this->_paymentForm->assign('contribution_id', $payment['contributionID']);
      $this->_paymentForm->assign('class_name', $className);
      $this->_paymentForm->assign('qfKey', $qfKey);

      // get template and render some element
      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
      $config = CRM_Core_Config::singleton();
      $storeKey = $this->_paymentProcessor['password'];
      $storeUID = array(
        'pfn' => '1',
        'store_uid' => $this->_paymentProcessor->user_name,
      );
      $size = openssl_cipher_iv_length('AES-256-CBC');
      $iv   = openssl_random_pseudo_bytes($size);
      $storeUIDEncrypt = openssl_encrypt(json_encode($storeUID), 'AES-256-CBC', $storeKey, OPENSSL_RAW_DATA, $iv);
      $this->_paymentForm->assign('store_uid_encrypt', base64_encode($storeUIDEncrypt));
      // dpm($this->_paymentForm->_params);
      // $this->_paymentForm->assign('total_amount');

      $tplFile = $config->templateDir[0] . 'CRM/Core/Page/Payment/MyPay.tpl';
      $tplContent = 'string:' . file_get_contents($tplFile);
      $smarty = CRM_Core_Smarty::singleton();
      $html = $smarty->fetch($tplContent);
      return $html;
    }
  }
  */

  function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }
    $isTest = $this->_mode == 'test' ? 1 : 0;

    // once they enter here, we will check SESSION
    // to see what instrument for mypay
    $instrumentId = $params['civicrm_instrument_id'];
    $instruments = CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $instrumentName = $instruments[$instrumentId];
    $paymentInstruments = self::getInstruments('code');
    $instrumentCode = $paymentInstruments[$instrumentName];
    $formKey = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$params['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$params['qfKey'];

    $contribParams = ['id' => $params['contributionID']];
    $contribValues = $contribIds = [];
    CRM_Contribute_BAO_Contribution::getValues($contribParams, $contribValues, $contribIds);
    if($instrumentCode == 'Credit'){
      $contribValues['is_pay_later'] = FALSE;
    }

    // now process contribution to save some default value
    if($params['civicrm_instrument_id']){
      $contribValues['payment_instrument_id'] = $params['civicrm_instrument_id'];
    }
    $contribValues['trxn_id'] = self::getContributionTrxnID($params['contributionID'], $isTest, $params['contribution_recur_id']);
    $contribution =& CRM_Contribute_BAO_Contribution::create($contribValues, $contribIds);

    // Inject in quickform sessions
    // Special hacking for display trxn_id after thank you page.
    $_SESSION['CiviCRM'][$formKey]['params']['trxn_id'] = $contribution->trxn_id;
    $_SESSION['CiviCRM'][$formKey]['params']['is_pay_later'] = $contribValues['is_pay_later'];
    $params['trxn_id'] = $contribution->trxn_id;
    $params['contact_id'] = $contribution->contact_id;

    $contributionPageId = $params['contributionPageID'];
    $paramsQuery = [ 1 => [$contributionPageId, 'Positive']];
    if ($component !== 'event') {
      $params['is_internal'] = CRM_Core_DAO::singleValueQuery("SELECT is_internal FROM civicrm_contribution_page WHERE id = %1;", $paramsQuery);
    }

    $arguments = $this->getOrderArgs($params, $component, $instrumentCode, $formKey);

    if ($params['is_recur'] && $params['is_internal']) {
      $encryptedArgs = [
        'agent_uid' => $this->_paymentProcessor['user_name'],
        'service' => self::encryptArgs($arguments['service'], $this->_paymentProcessor['password']),
        'encry_data' => self::encryptArgs($arguments['encry_data'], $this->_paymentProcessor['password']),
      ];
      $actionUrl = $this->_paymentProcessor['url_recur'];
    }
    else {
      $encryptedArgs = [
        'store_uid' => $arguments['store_uid'],
        'service' => self::encryptArgs($arguments['service'], $this->_paymentProcessor['password']),
        'encry_data' => self::encryptArgs($arguments['encry_data'], $this->_paymentProcessor['password']),
      ];
      $actionUrl = $this->_paymentProcessor['url_api'];
    }
    // Record Data
    // 1. Record Log Data.
    $saveData = [
      'contribution_id' => $contribParams['id'],
      'url' => $actionUrl,
      'cmd' => $arguments['service']['cmd'],
      'date' => date('Y-m-d H:i:s'),
      'post_data' => json_encode($arguments['encry_data']),
    ];
    $this->_logId = self::writeLog(NULL, $saveData);
    // 2. Record usable data.
    $data = [
      'create_post_data' => json_encode($arguments['encry_data']),
    ];
    self::doRecordData($contribParams['id'], $data);
    // contribution_id is needed in postData
    $this->_contributionId = $params['contributionID'];
    $result = $this->postData($actionUrl, $encryptedArgs);
    if (isset($result['code']) && $result['code'] == '200') {
      // redirect to payment form
      //TODO: save transaction data to db
      CRM_Utils_System::redirect($result['url']);
    }
    else {
      // something wrong
      $contribution->cancel_date = date('Y-m-d H:i:s', CRM_REQUEST_TIME);
      $contribution->cancel_reason = "Code: {$result['code']}\nMessage: {$result['msg']}";
      $contribution->save();
      $failureQuery = http_build_query([
        '_qf_ThankYou_display' => "1",
        'qfKey' => $params['qfKey'],
        'payment_result_type' => '4',
      ], '', '&');
      $failureRedirectURL = CRM_Utils_System::url(CRM_Utils_System::currentPath(), $failureQuery, TRUE, NULL, FALSE);
      CRM_Utils_System::redirect($failureRedirectURL);
    }
    // move things to CiviCRM cache as needed
    CRM_Utils_System::civiExit();
  }

  /**
   * Get all used instrument.
   *
   * @param string $type The String of return type, as 'normal'(default), 'form_name' and 'code'.
   *
   * @return array The instruments used by mypay.
   */
  static function getInstruments($type = 'normal') {
    $i = [
      'Credit Card' => ['label' => ts('Credit Card'), 'desc' => '', 'code' => 'Credit'],
    ];
    if ($type == 'form_name') {
      foreach ($i as $name => $data) {
        $form_name = preg_replace('/[^0-9a-z]+/i', '_', strtolower($name));
        $instrument[$form_name] = $data;
      }
      return $instrument;
    }
    elseif ($type == 'code') {
      foreach ($i as $name =>  $data) {
        $instrument[$name] = $data['code'];
      }
      return $instrument;
    }
    else {
      return $i;
    }
  }

  /**
   * Generate trxn_id of MyPay
   *
   * @param boolean $is_test Is this id a test contribution or not.
   * @param string $id The contribution Id.
   *
   * @return string If test, return expand string of id.
   */
  static function getContributionTrxnID($contributionId, $is_test = 0, $recurringId = NULL) {
    $rand = base_convert(strval(rand(16, 255)), 10, 16);
    if (empty($recurringId)) {
      $recurringId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    }

    if (!empty($recurringId)) {
      $trxnId = 'r_' . $recurringId . '_' . $contributionId . '_' . $rand;
    } else {
      $trxnId = 'c_' . $contributionId . '_' . $rand;
    }
    if ($is_test) {
      $trxnId = 'test' . substr(str_replace(['.', '-'], '', $_SERVER['HTTP_HOST']), 0, 3).'_'.$trxnId;
    }
    return $trxnId;
  }

  /**
   * Retrieve arguments of order
   *
   * @param array $vars Parameters of the contribution page or session.
   * @param string $component String of payment type as 'contribute' or 'event'.
   * @param array $payment_processor The payment processor parameters.
   * @param string $instrument_code The code of used instrument like 'Credit' or 'ATM'.
   * @param string $form_key The unique from key from the session.
   *
   * @return array Rearrange nessesary arguments for checkout.
   *
   */
  function getOrderArgs(&$vars, $component, $instrumentCode, $formKey) {
    $paymentProcessor = $this->_paymentProcessor;
    // parameter
    if ($component == 'event' && !empty($_SESSION['CiviCRM'][$formKey])) {
      $values = &$_SESSION['CiviCRM'][$formKey]['values']['event'];
    } else {
      $values = &$_SESSION['CiviCRM'][$formKey]['values'];
    }

    // building vars
    $successQuery = http_build_query([
      '_qf_ThankYou_display' => "1",
      'qfKey' => $vars['qfKey'],
      'payment_result_type' => '1',
    ], '', '&');
    $failureQuery = http_build_query([
      '_qf_ThankYou_display' => "1",
      'qfKey' => $vars['qfKey'],
      'payment_result_type' => '4',
    ], '', '&');
    $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'], '.')) : $vars['amount'];

    if ($vars['contributionRecurID']) {
      $params = [
        1 => $vars['contributionRecurID'],
        2 => $vars['installments'] ? ts("%1 Periods", [1 => $vars['installments']]) : ts('no period'),
      ];
      $item = ts("Recur %1-%2", $params);
    }
    else {
      $params = [1 => $vars['contributionID']];
      $item = ts("Contribution %1", $params);
    }

    $args = [
      'store_uid' => $paymentProcessor['user_name'],
      'service' => [
        'service_name' => 'api',
        'cmd' => 'api/orders',
      ],
      'encry_data' => [
        'store_uid' => $paymentProcessor['user_name'],
        'user_id' => $vars['contact_id'],
        'currency' => $vars['currencyID'],
        'order_id' => $vars['trxn_id'],
        'agent_sms_fee_type' => 0,
        'cost' => (string) $amount,
        'pfn' => '',
        'ip' => CRM_Utils_System::ipAddress(),
        'echo_0' => $component,
        'echo_1' => $vars['contributionID'],
        'items' => [ 0 => [
          'id' => $vars['trxn_id'],
          'name' => $item,
          'cost' => (string) $amount,
          'total' => (string) $amount,
          'amount' => 1,
        ]],
        'success_returl' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), $successQuery, TRUE, NULL, FALSE),
        'failure_returl' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), $failureQuery, TRUE, NULL, FALSE),
      ],
    ];

    echo $instrumentCode;
    switch ($instrumentCode) {
      case 'Credit':
        $args['encry_data']['pfn'] = 'CREDITCARD';
        if ($vars['is_recur']) {
          $args['encry_data']['pfn'] = 'DIRECTDEBIT';
          $args['encry_data']['regular'] = '';
          $args['encry_data']['group_id'] = $vars['contributionRecurID'];
          if ($vars['is_internal']) {
            $args['encry_data']['store_uid'] = $paymentProcessor['signature'];
            $args['service']['cmd'] = 'api/batchdebitcreator';
            $args['encry_data']['project_name'] = 'Recurring_'.$vars['trxn_id'];
          }
          switch($vars['frequency_unit']) {
            case 'month':
              $args['encry_data']['regular'] = 'M';
              break;
            case 'year':
              $args['encry_data']['regular'] = 'A';
              break;
            case 'week':
              $args['encry_data']['regular'] = 'W';
              break;
          }


          if (!empty($vars['installments']) && $vars['installments'] > 0) {
            if ($vars['frequency_unit'] == 'year' ) {
              $args['encry_data']['regular_total'] = $vars['installments'] >= 9 ? 9 : $vars['installments'];
            }
            else {
              $args['encry_data']['regular_total'] = $vars['installments'] >= 99 ? 99 : $vars['installments'];
            }
          }
          else {
            // no limit
            $args['encry_data']['regular_total'] = 0;
          }
        }
        // is_recur end
        break;
    }
    CRM_Utils_Hook::alterPaymentProcessorParams($paymentProcessor, $vars, $data);
    return $args;
  }

  /**
   * Print redirect form HTML
   *
   * @param array $redirect_vars Variables of form elements which is name to value.
   * @param array $payment_processor The payment processor parameters.
   *
   * @return void
   */
  function postData($url, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($ch);
    
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    // Record all data
    // 1. Record log data.
    $resultArray = json_decode($result, TRUE);
    $saveData = [
      'uid' => $resultArray['uid'],
      'return_data' => $result,
    ];
    self::writeLog($this->_logId, $saveData);
    // 2. Record usable data.
    if ($this->_contributionId) {
      $transationData = [
        'uid' => $resultArray['uid'], // serial number of transaction of MyPay
        'uid_key' => $resultArray['key'],
        'create_result_data' => $result,
      ];
      self::doRecordData($this->_contributionId, $transationData);
    }

    if (!empty($errno)) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      CRM_Core_Error::debug_log_message("MyPay postData: Contribution ID-{$this->_contributionId} :: httpstatus-$status :: error-$errno :: $err");
      return [];
    }
    if ($result === FALSE) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      CRM_Core_Error::debug_log_message("MyPay postData: Contribution ID-{$this->_contributionId} :: httpstatus-$status :: error-$errno :: $err");
      return [];
    }
    curl_close($ch);
    if (!empty($result)) {
      $response = json_decode($result, TRUE);
      return $response;
    }
    return [];
  }

  /**
   * Print redirect form HTML
   *
   * @param array $redirect_vars Variables of form elements which is name to value.
   * @param array $payment_processor The payment processor parameters.
   *
   * @return void
   */
  function outputRedirectForm($redirectVars){
    $paymentProcessor = $this->_paymentProcessor;
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Expires: 0');

    $actionUrl = $paymentProcessor['url_api'];

    if (CRM_Utils_System::getUFLocale() == 'en') {
      $actionUrl .= '?locale=en';
    }
    $o = '<form action="'.$actionUrl.'" name="redirect" method="post" id="redirect-form">';
    foreach($redirectVars as $k=>$p){
      if($k[0] != '#'){
        $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
      }
    }
    $o .= '</form>';
    $html = <<<EOT
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
      {$o}
      <script type="text/javascript">
        document.forms.redirect.submit();
      </script>
    </body>
    <html>
    EOT;
    return $html;
  }

  public static function encryptArgs($fields, $key){
    $data = json_encode($fields);
    $size = openssl_cipher_iv_length('AES-256-CBC');
    $iv   = openssl_random_pseudo_bytes($size);
    $data = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $data = base64_encode($iv . $data);
    return $data;
  }

  /**
   * Execute ipn as called from mypay transaction.
   *
   * @param array $arguments Default params in CiviCRM Router, Must be array('civicrm', 'mypay', 'ipn')
   * @param string $instrument The code of used instrument like 'Credit' or 'ATM'.
   * @param array $post Bring post variables if you need test.
   * @param array $get Bring get variables if you need test.
   * @param boolean $print Does server echo the result, or just return that. Default is TRUE.
   *
   * @return string|void If $print is FALSE, function will return the result as Array.
   */
  public static function doIPN($arguments, $instrument = NULL, $post = NULL, $get = NULL, $print = TRUE) {
    // detect variables
    $post = !empty($post) ? $post : $_POST;
    $get = !empty($get) ? $get : $_GET;
    if (!empty($arguments)) {
      if (is_array($arguments)) {
        $instrument = end($arguments);
      }
      else {
        $instrument = $arguments;
      }
    }
    if (empty($instrument)) {
      $qArray = explode('/', $get['q']);
      $instrument = end($qArray);
    }

    if (!empty($post['uid']) && !empty($post['key']) && !empty($post['prc'])) {
      // Save Data to Log.
      $saveData = [
        'uid' => $post['uid'],
        'date' => date('Y-m-d H:i:s'),
        'post_data' => json_encode($post),
      ];
      $logId = self::writeLog(NULL, $saveData);
      if ($post['order_id']) {
        $contributionID = $post['echo_1'];
        if (empty($contributionID)) {
          $trxn_id = $post['order_id'];
          $contributionID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", [1 => [$trxn_id, 'String']]);
        }
        $requestURL = CRM_Utils_System::isSSL() ? 'https://' : 'http://';
        $requestURL .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $saveData = [
          'contribution_id' => $contributionID,
          'url' => CRM_Core_DAO::escapeString($requestURL),
        ];
        self::writeLog($logId, $saveData);
      }
    }
    else {
      CRM_Core_Error::debug_log_message( "civicrm_mypay: Don't have necessary params: uid, key, prc.", TRUE);
      CRM_Utils_System::civiExit();
    }

    // Give $instrument
    if (empty($instrument)) {
      switch ($post['result_content_type']) {
        case 'CREDITCARD':
          $instrument = 'Credit';
          break;
        case 'BARCODE':
          $instrument = 'BARCODE';
          break;
        case 'E_COLLECTION':
          $instrument = 'ATM';
          break;
        case 'WEBATM':
          $instrument = 'WebATM';
          break;
        default:
          CRM_Core_Error::debug_log_message( "MyPay: The instrument doesn't use, type is '{$post['result_content_type']}'", TRUE);
          CRM_Utils_System::civiExit();
          break;
      }
    }

    // detect variables
    if(empty($post)){
      CRM_Core_Error::debug_log_message( "civicrm_mypay: Could not find POST data from payment server", TRUE);
      CRM_Utils_System::civiExit();
    }
    else{
      $component = $post['echo_0'];
      if(!empty($component)){
        $ipn = new CRM_Core_Payment_MyPayIPN($post, $get);
        $result = $ipn->main($instrument);
        if(!empty($result) && $print){
          echo $result;
        }
        else{
          return $result;
        }
      }
      else{
        CRM_Core_Error::debug_log_message( "civicrm_mypay: Could not get module name from request url", TRUE);
      }
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Write data into table `civicrm_contrbution_mypay_log`
   * @param number|NULL $logId The field `id` in `civicrm_contrbution_mypay_log`. Use NULL to create new row.
   * @param Array $data Insert fields of the row. The value must be String type and keys must match field name.
   * 
   * @return number $id The `id` of the row.
   */
  public static function writeLog($logId, $data = []) {
    $recordType = ['contribution_id', 'uid', 'url', 'cmd', 'date', 'post_data', 'return_data'];

    $record = new CRM_Contribute_DAO_MyPayLog();
    if(!empty($logId)) {
      $record->id = $logId;
      $record->find(TRUE);
    }

    foreach ($recordType as $key) {
      if (!empty($data[$key])) {
        $record->$key = $data[$key];
      }
    }
    $record->save();
    return $record->id;
  }

  /**
   * 
   */
  static public function doRecordData($contributionId, $data, $apiType = '') {
    $recordType = [
      'uid',
      'uid_key',
      'expired_date',
      'create_post_data',
      'create_result_data',
      'ipn_result_data'
    ];
    $mypay = new CRM_Contribute_DAO_MyPay();
    $mypay->contribution_id = $contributionId;
    $mypay->find(TRUE);
    $contributionRecurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    if (empty($mypay->contribution_recur_id) && !empty($contributionRecurId)) {
      $mypay->contribution_recur_id = $contributionRecurId;
    }
    foreach ($recordType as $key) {
      if (!empty($data[$key])) {
        $mypay->$key = $data[$key];
      }
    }
    $mypay->save();
  }

  /**
   * 
   */
  static public function getKey($contributionId) {
    $mypay = new CRM_Contribute_DAO_MyPay();
    $mypay->contribution_id = $contributionId;
    $mypay->find(TRUE);
    $key = $mypay->uid_key;
    return $key;
  }

  /**
   * 
   */
  static public function getTrxnIdByPost($input) {
    $trxnId = NULL;
    if ($input['order_id']) {
      if ($input['uid'] && !empty($input['nois']) && $input['nois'] > 1) {
        $trxnId = $input['order_id'].'-'.$input['nois'].'-'.$input['uid'];
      }
      else {
        $trxnId = $input['order_id'];
      }
    }
    return $trxnId;
  }
}


