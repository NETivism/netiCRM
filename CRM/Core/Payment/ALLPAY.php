<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_ALLPAY extends CRM_Core_Payment {
  const ALLPAY_REAL_DOMAIN = 'https://payment.ecpay.com.tw';
  const ALLPAY_TEST_DOMAIN = 'https://payment-stage.ecpay.com.tw';
  const ALLPAY_URL_SITE = '/Cashier/AioCheckOut';
  const ALLPAY_URL_API = '/Cashier/QueryTradeInfo';
  const ALLPAY_URL_RECUR = '/Cashier/QueryCreditCardPeriodInfo';

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

  public static $_hideFields = array('invoice_id', 'trxn_id');

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
    $this->_processorName = ts('Allpay');
    $config = &CRM_Core_Config::singleton();
    $this->_config = $config;
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
      self::$_singleton[$processorName] = new CRM_Core_Payment_ALLPAY($mode, $paymentProcessor);
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
  static function buildPaymentDefault(&$default, $payment) {
    if ($payment->is_test > 0) {
      $default['url_site'] = CRM_Core_Payment_ALLPAY::ALLPAY_TEST_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_SITE;
      $default['url_api'] = CRM_Core_Payment_ALLPAY::ALLPAY_TEST_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_API;
      $default['url_recur'] = CRM_Core_Payment_ALLPAY::ALLPAY_TEST_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_RECUR;
    }
    else {
      $default['url_site'] = CRM_Core_Payment_ALLPAY::ALLPAY_REAL_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_SITE;
      $default['url_api'] = CRM_Core_Payment_ALLPAY::ALLPAY_REAL_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_API;
      $default['url_recur'] = CRM_Core_Payment_ALLPAY::ALLPAY_REAL_DOMAIN . CRM_Core_Payment_ALLPAY::ALLPAY_URL_RECUR;
    }
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
   * Original _civicrm_allpay_instrument, Get all used instrument.
   *
   * @param string $type The String of return type, as 'normal'(default), 'form_name' and 'code'.
   *
   * @return array The instruments used by AllPay.
   */
  static function getInstruments($type = 'normal'){
    $i = array(
      'Credit Card' => array('label' => ts('Credit Card'), 'desc' => '', 'code' => 'Credit'),
      'ATM' => array('label' => ts('ATM Transfer'), 'desc' => '', 'code' => 'ATM'),
      'Web ATM' => array('label' => ts('Web ATM Transfer'), 'desc' => '', 'code' => 'WebATM'),
      'Convenient Store' => array('label' => ts('Convenient Store Barcode'), 'desc'=>'', 'code' => 'BARCODE'),
      'Convenient Store (Code)' => array('label'=> ts('Convenient Store (Code)'),'desc' => '', 'code' => 'CVS'),
      'Alipay' => array('label'=> ts('AliPay'), 'desc' => '', 'code' => 'Alipay'),
      // 'Tenpay' => array('label'=> ts('Tenpay'), 'desc' => '', 'code' => 'Tenpay'),
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
   * Generate trxn_id of allPay, Original _civicrm_allpay_trxn_id
   *
   * @param boolean $is_test Is this id a test contribution or not.
   * @param string $id The contribution Id.
   *
   * @return string If test, return expand string of id.
   */
  static function generateTrxnId($is_test, $id){
    if($is_test){
      $id = 'test' . substr(str_replace(array('.','-'), '', $_SERVER['HTTP_HOST']), 0, 3) . $id. 'T'. mt_rand(100, 999);
    }
    return $id;
  }
  /**
   * Generate a trxn_id for recurring. Original _civicrm_allpay_recur_trxn
   *
   * @param string $parent Input 'MerchantTradeNo' from allpay return values/
   * @param string $gwsr Input 'gwsr' from allpay return values.
   *
   * @return string implode by $parent and $gwsr.
   */
  static function generateRecurTrxn($parent, $gwsr){
    if(empty($gwsr)){
      return $parent;
    }
    else{
      return $parent . '-' . $gwsr;
    }
  }


  /**
   * Sets appropriate parameters for checking out to google
   *
   * @param array $params  name value pair of contribution data.
   * @param string component String of payment type as 'contribute' or 'event'.
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
    $payment_processor = $this->_paymentProcessor;
    $is_test = $this->_mode == 'test' ? 1 : 0;

    // once they enter here, we will check SESSION
    // to see what instrument for newweb
    $instrument_id = $params['civicrm_instrument_id'];
    $instruments = CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $instrument_name = $instruments[$instrument_id];
    $allpay_instruments = self::getInstruments('code');
    $instrument_code = $allpay_instruments[$instrument_name];
    $form_key = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$params['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$params['qfKey'];

    // Todo: remove this.
    // The first, we insert every contribution into record. After this, we'll use update for the record.
    // $record = array('cid' => $params['contributionID']);
    // drupal_write_record("civicrm_contribution_allpay", $record);

    $_SESSION['allpay']['submitted'] = TRUE;
    $_SESSION['allpay']['instrument'] = $instrument_code;

    if($instrument_code == 'Credit' || $instrument_code == 'WebATM'){
      $is_pay_later = FALSE;
    }
    else{
      $is_pay_later = TRUE;

      // Set participant status to 'Pending from pay later', Accupied the seat.
      if($params['participantID']){
        $pstatus = CRM_Event_PseudoConstant::participantStatus();
        if($new_pstatus = array_search('Pending from pay later', $pstatus)){
          CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $params['participantID'], 'status_id', $new_pstatus, 'id');
          $sql = 'SELECT id FROM civicrm_participant WHERE registered_by_id = %1';
          $paramsRegisteredBy = array(
            1 => array($params['participantID'], 'Integer'),
          );
          $dao = CRM_Core_DAO::executeQuery($sql, $paramsRegisteredBy);
          while($dao->fetch()){
            CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $dao->id, 'status_id', $new_pstatus, 'id');
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
    $contrib_values['is_pay_later'] = $is_pay_later;
    $contrib_values['trxn_id'] = self::generateTrxnId($is_test, $params['contributionID']);
    $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

    // Inject in quickform sessions
    // Special hacking for display trxn_id after thank you page.
    $_SESSION['CiviCRM'][$form_key]['params']['trxn_id'] = $contribution->trxn_id;
    $_SESSION['CiviCRM'][$form_key]['params']['is_pay_later'] = $is_pay_later;
    $params['trxn_id'] = $contribution->trxn_id;

    $arguments = self::getOrderArgs($params, $component, $payment_processor, $instrument_code, $form_key);
    self::generateMacValue($arguments, $payment_processor);
    /*
    $alter = array(
      'module' => 'civicrm_allpay',
      'billing_mode' => $payment_processor['billing_mode'],
      'params' => $arguments,
    );
    drupal_alter('civicrm_checkout_params', $alter);
    */
    print self::outputRedirectForm($arguments, $payment_processor);
    // move things to CiviCRM cache as needed
    CRM_Utils_System::civiExit();
  }


  /**
   * Retrieve arguments of order. Original _civicrm_allpay_order.
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
  static function getOrderArgs(&$vars, $component, &$payment_processor, $instrument_code, $form_key){

    // url 
    $notify_url = self::generateNotifyUrl($vars, 'allpay/ipn/'.$instrument_code, $component);
    $civi_base_url = CRM_Utils_System::currentPath();
    $query = http_build_query(array( "_qf_ThankYou_display" => "1" , "qfKey" => $vars['qfKey']), '', '&');
    $thankyou_url = CRM_Utils_System::url($civi_base_url, $query, TRUE, NULL, FALSE);
  
    // parameter
    if($component == 'event' && !empty($_SESSION['CiviCRM'][$form_key])){
      $values =& $_SESSION['CiviCRM'][$form_key]['values']['event'];
    }
    else{
      $values =& $_SESSION['CiviCRM'][$form_key]['values'];
    }
  
    // building vars
    $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];
  
    $args = array(
      'MerchantID' => $payment_processor['user_name'],
      'MerchantTradeNo' => $vars['trxn_id'],
      'MerchantTradeDate' => date('Y/m/d H:i:s'),
      'PaymentType' => 'aio',
      'TotalAmount' => $amount,
      'TradeDesc' => preg_replace('~[^\p{L}\p{N}]++~u', ' ', $vars['description']),
      'ItemName' => preg_replace('~[^\p{L}\p{N}]++~u', ' ', $vars['item_name']),
      'ReturnURL' => $notify_url,
      'ChoosePayment' => $instrument_code,
      #'CheckMacValue' => '', // add in civicrm_allpay_checkmacvalue
      'ClientBackURL' => $thankyou_url,
      'ItemURL' => '',
      'Remark' => '',
      'ChooseSubPayment' => '',
      'OrderResultURL' => $thankyou_url,
      'NeedExtraPaidInfo' => 'Y',
      'DeviceSource' => '',
    );
  
    // max 7 days of expire
    $baseTime = time() + 86400; // because not include today
    if (!empty($vars['payment_expired_timestamp'])) {
      $hours = ($vars['payment_expired_timestamp'] - $baseTime) / 3600;
    }
    else {
      $hours = (CRM_Core_Payment::calcExpirationDate(0) - $baseTime) / 3600;
    }
    if ($hours < 24) {
      $hours = 24;
    }
  
    switch($instrument_code){
      case 'ATM':
        $args['ExpireDate'] = ceil($hours/24) > 60 ? 60 : ceil($hours/24);
      case 'BARCODE':
        $args['StoreExpireDate'] = ceil($hours/24) > 7 ? 7 : ceil($hours/24);
      case 'CVS':
        if($instrument_code == 'CVS' && !empty($hours)) {
          // hour before 24hr
          $end_of_day_hr = 24 - (int)date('H');
          $end_of_day_min = (int)date('i') + 1;
          $args['StoreExpireDate'] = ceil($hours/24) > 7 ? 7 : ceil($hours/24);
          $args['StoreExpireDate'] = $args['StoreExpireDate']*24*60 + $end_of_day_hr*60 - $end_of_day_min;
        }
        $args['Desc_1'] = '';
        $args['Desc_2'] = '';
        $args['Desc_3'] = '';
        $args['Desc_4'] = '';
  
        #ATM / CVS / BARCODE
        $args['PaymentInfoURL'] = CRM_Utils_System::url('allpay/record/'.$vars['contributionID'], "", TRUE, NULL, FALSE);
        break;
      case 'Alipay':
        $params = array(
          'version' => 3,
          'id' => $vars['contactID'],
          'return.sort_name' => 1,
          'return.phone' => 1,
        );
        $result = civicrm_api('contact', 'get', $params);
        if(!empty($result['count'])){
          $phone = $result['values'][$result['id']]['phone'];
          $name = $result['values'][$result['id']]['sort_name'];
        }
        $args['AlipayItemName'] = $vars['item_name'];
        $args['AlipayItemCounts'] = 1;
        $args['AlipayItemPrice'] = $amount;
        $args['Email'] = $vars['email-5'];
        $args['PhoneNo'] = $phone;
        $args['UserName'] = $name;
        break;
      /*
      case 'Tenpay':
        $args['ExpireTime'] = '';
        break;
       */
      case 'WebATM':
        break;
      case 'Credit':
        if($vars['is_recur']){
          $args['PeriodAmount'] = $amount;
          $period = strtoupper($vars['frequency_unit'][0]);
          $args['PeriodType'] = $vars['frequency_unit'] == 'week' ? 'D' : $period;
  
          if($vars['frequency_unit'] == 'month'){
            $frequency_interval = $vars['frequency_interval'] > 12 ? 12 : $vars['frequency_interval'];
          }
          elseif($vars['frequency_unit'] == 'week'){
            $frequency_interval = (7 * $vars['frequency_interval']) > 365 ? 365 : ($vars['frequency_interval'] * 7);
          }
          elseif($vars['frequency_unit'] == 'day'){
            $frequency_interval = $vars['frequency_interval'] > 365 ? 365 : $vars['frequency_interval'];
          }
          elseif($vars['frequency_unit'] == 'year'){
            $frequency_interval = 1;
          }
          if(empty($frequency_interval)){
            $frequency_interval = 1;
          }
          $args['Frequency'] = $frequency_interval;
          if($vars['frequency_unit'] == 'year'){
            $args['ExecTimes'] = empty($vars['installments']) ? 9 : $vars['installments'];
          }else{
            $args['ExecTimes'] = empty($vars['installments']) ? 99 : $vars['installments']; // support endless
          }
          $args['PeriodReturnURL'] = $notify_url.'&is_recur=1';
        }
        if(CRM_Utils_System::getUFLocale() == 'en_US'){
          $args['Language'] = 'ENG';
        }
        # Recurring
        break;
    }
    return $args ;
  }
  
  /**
   * Print redirect form HTML. Original _civicrm_allpay_form_redirect.
   *
   * @param array $redirect_vars Variables of form elements which is name to value.
   * @param array $payment_processor The payment processor parameters.
   *
   * @return void
   */
  static function outputRedirectForm($redirect_vars, $payment_processor){
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Expires: 0');
  
    $o = "";
  
    $js = 'document.forms.redirect.submit();';
    $o .= '<form action="'.$payment_processor['url_site'].'" name="redirect" method="post" id="redirect-form">';
    foreach($redirect_vars as $k=>$p){
      if($k[0] != '#'){
        $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
      }
    }
    $o .= '</form>';
    return '
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  </head>
  <body>
    '.$o.'
    <script type="text/javascript">
    '.$js.'
    </script>
  </body>
  <html>
  ';
  }
  
  /**
   * Generate notify URL added to checkout request. Original _civicrm_allpay_notify_url
   *
   * @param array $vars Variables used in compose query.
   * @param string $path Notify URL path.
   * @param string $component String of payment type as 'contribute' or 'event'.
   *
   * @return string The full path or notify URL.
   */
  static function generateNotifyUrl(&$vars, $path, $component){
    $query = array();
    $query["contact_id"] = $vars['contactID'];
    $query["cid"] = $vars['contributionID'];
    $query["module"] = $component;
  
    if ( $component == 'event' ) {
      $query["eid"] = $vars['eventID'];
      $query["pid"] = $vars['participantID'];
    }
    else {
      if ( !empty($vars['membershipID']) ) {
        $query["mid"] = $vars['membershipID'];
      }
      if ( !empty($vars['related_contact']) ){
        $query["rid"] = $vars['related_contact'];
        if ( !empty($vars['onbehalf_dupe_alert']) ){
          $query["onbehalf_dupe_alert"] = $vars['onbehalf_dupe_alert'];
        }
      }
    }
  
    // if recurring donations, add a few more items
    if ( !empty( $vars['is_recur']) ) {
      if ($vars['contributionRecurID']) {
        $query["crid"] = $vars['contributionRecurID'];
        $query["cpid"] = $vars['contributionPageID'];
      }
    }

    $query = http_build_query($query, '', '&');
    $url = CRM_Utils_System::url(
      $path,
      $query,
      TRUE,
      NULL,
      FALSE
    );
    if( ( !empty($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] == 'on' ) || ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ){
      return str_replace('http://', 'https://', $url);
    }
    else{
      return $url;
    }
  }

  /**
   * Generate mac value used to for validation. Original _civicrm_allpay_checkmacvalue
   *
   * @param mixed $args Arguments of the order. Default is Array. Will rearrange to Array if type is String.
   * @param array $payment_processor The payment processor parameters.
   *
   * @return string md5 hash of mac values.
   */
  static function generateMacValue(&$args, $payment_processor){
    // remove empty arg
    if(is_array($args)){
      foreach($args as $k => $v){
        if($k == 'CheckMacValue'){
          unset($args[$k]);
        }
      }
    }
    elseif(is_string($args)){
      $tmp = explode('&', $args);
      $args = array();
      foreach($tmp as $v){
        list($key, $value) = explode('=', $v);
        $args[$key] = $value;
      }
    }
    uksort($args, 'strnatcasecmp');
    $a = array(
      'HashKey='.$payment_processor['password'],
    );
    foreach($args as $k => $v){
      $a[] = $k.'='.$v;
    }
    $a[] = 'HashIV='.$payment_processor['signature'];
    $keystr = CRM_Utils_Array::implode('&', $a);
    $keystr = urlencode($keystr);
    $keystr = strtolower($keystr);
  
    $special_char_allpay = array(
      '%2d' => '-',
      '%5f' => '_',
      '%2e' => '.',
      '%21' => '!',
      '%2a' => '*',
      '%28' => '(',
      '%29' => ')',
      '%20' => '+',
    );
    $keystr = str_replace(array_keys($special_char_allpay), $special_char_allpay, $keystr);
    $checkmacvalue = md5($keystr);
    $args['CheckMacValue'] = $checkmacvalue;
    return $checkmacvalue;
  }

  /**
   * Synchronize all recurring of specific day of month.
   * Original civicrm_allpay_recur_sync
   * 
   * @param array $days The array of days need to synchronize recurrings.
   * @return null
   */
  public static function recurSync($days = array()) {
    $allpayEnabled = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_payment_processor WHERE payment_processor_type LIKE 'ALLPAY%' AND is_active > 0");
    if (!$allpayEnabled) {
      return;
    }
    if(empty($days)){
      $days = array(
        date('j'),
        date('j', strtotime('-1 day')),
      );

      // when end of month
      $end_this_month = date('j', strtotime('last day of this month'));
      if (date('j') == $end_this_month) {
        for($i = $end_this_month; $i <= 31; $i++) {
          $days[] = $i;
        }
      }
      $days = array_unique($days);
    }

    $query = "SELECT (SELECT count(c.id) FROM civicrm_contribution c WHERE c.contribution_recur_id = r.id AND c.receive_date >= %2 AND c.receive_date <= %3 ) AS contribution_count, r.* FROM civicrm_contribution_recur r
    WHERE r.contribution_status_id = 5 AND r.frequency_unit = 'month' AND DAY(r.start_date) = %1
    ORDER BY r.create_date ASC";
    foreach($days as $d){
      $d = (string) $d;
      CRM_Core_Error::debug_log_message('CiviCRM AllPay: Start to sync recurring for day '.$d);
      $query_params = array(
        1 => array($d, 'String'),
        2 => array(date('Y-m-').sprintf('%02s', $d).' 00:00:00', 'String'),
        3 => array(date('Y-m-').sprintf('%02s', $d).' 23:59:59', 'String'),
      );
      $result = CRM_Core_DAO::executeQuery($query, $query_params);
      while($result->fetch()){
        if(empty($result->contribution_count)){
          // check if is next day of expect recurring
          self::recurCheck($result->id);
          usleep(300000); // sleep 0.3 second
        }
      }
      $result->free();
      $result = NULL;
    }
  }

  /**
   * Chcek recurring of specific id from AllPay API.
   * Original civicrm_allpay_recur_check
   * 
   * @param integer $rid The recurring id.
   * @param object $order If you want to include object already wrote.
   * @return null
   */
  public static function recurCheck($rid, $order = NULL) {
    $now = time();
    $query = "SELECT c.id as cid, c.contact_id, c.is_test, c.trxn_id, c.payment_processor_id as pid, c.contribution_status_id, r.id as rid, r.contribution_status_id as recurring_status FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1 AND c.payment_processor_id IS NOT NULL ORDER BY c.id ASC";
    $result = CRM_Core_DAO::executeQuery($query, array(1 => array($rid, 'Integer')));

    // fetch first contribution
    $result->fetch();
    if(!empty($result->N)){
      $first_contrib_id = $result->cid;
      $is_test = $result->is_test;
      $payment_processor = CRM_Core_BAO_PaymentProcessor::getPayment($result->pid, $is_test ? 'test' : 'live');
      if($payment_processor['payment_processor_type'] != 'ALLPAY'){
        return;
      }

      if(!empty($payment_processor['url_recur']) && !empty($payment_processor['user_name'])){
        $processor = array(
          'password' => $payment_processor['password'],
          'signature' => $payment_processor['signature'],
        );
        $post_data = array(
          'MerchantID' => $payment_processor['user_name'],
          'MerchantTradeNo' => $result->trxn_id,
          'TimeStamp' => $now,
        );
        self::generateMacValue($post_data, $processor);
        if(empty($order)){
          $order = self::postdata($payment_processor['url_recur'], $post_data);
        }
        if(!empty($order) && $order->MerchantTradeNo == $result->trxn_id && count($order->ExecLog) > 1){
          // update recur status
          if(isset($order->ExecStatus)){
            $recur_param = $null = array();
            if($order->ExecStatus == 0 && $result->recurring_status != 3){
              // cancelled
              $recur_param = array(
                'id' => $rid,
                'modified_date' => date('YmdHis'),
                'cancel_date' => date('YmdHis'),
                'contribution_status_id' => 3, // cancelled
              );
              CRM_Contribute_BAO_ContributionRecur::add($recur_param, $null);
            }
            elseif($order->ExecStatus == 2 && $result->recurring_status != 1){
              // completed
              $recur_param = array(
                'id' => $rid,
                'modified_date' => date('YmdHis'),
                'end_date' => date('YmdHis'),
                'contribution_status_id' => 1, // completed
              );
              CRM_Contribute_BAO_ContributionRecur::add($recur_param, $null);
            }
            elseif($order->ExecStatus == 1){
              // current running, should be 5, do nothing
            }
          }

          $orders = array();
          foreach($order->ExecLog as $o){
            // update exists first contribution if pending
            // otherwise skip
            if($order->gwsr == $o->gwsr){
              if($result->contribution_status_id != 2) {
                continue;
              }
              else {
                $trxn_id = $result->trxn_id;
                $orders[$trxn_id] = $o;
              }
            }

            // skip failed contribution when process_date before last month
            if (!empty($o->process_date) && $o->RtnCode != 1) {
              if (strtotime($o->process_date) < strtotime(date('Y-m-01 00:00:00'))) {
                continue;
              }
            }
            $noid = self::getNoidHash($o, $order->MerchantTradeNo);
            if (!empty($noid)) {
              if($o->RtnCode == 1 && empty($o->gwsr)){
                continue; // skip, not normal
              }
              $trxn_id = self::generateRecurTrxn($order->MerchantTradeNo, $noid);
              $orders[$trxn_id] = $o;
            }
          }
          // remove exists records
          while($result->fetch()){
            unset($orders[$result->trxn_id]);
          }
          // real record to add
          if(!empty($orders)){
            foreach($orders as $trxn_id => $o){
              $get = $post = $ids = array();
              list($main_trxn, $noid) = explode('-', $trxn_id);
              $ids = CRM_Contribute_BAO_Contribution::buildIds($first_contrib_id);
              $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, $return_query = TRUE);
              parse_str($query, $get);
              if($order->gwsr != $o->gwsr){
                $get['is_recur'] = 1;
              }
              $post = array(
                'MerchantID' => $order->MerchantID,
                'MerchantTradeNo' => $order->MerchantTradeNo,
                'RtnCode' => $o->RtnCode,
                'RtnMsg' => !empty($o->RtnMsg) ? ts($o->RtnMsg) : self::getErrorMsg($o->RtnCode),
                'PeriodType' => $order->PeriodType,
                'Frequency' => $order->Frequency,
                'ExecTimes' => $order->ExecTimes,
                'Amount' => !empty($o->amount) ? $o->amount : $order->amount,
                'Gwsr' => $noid,
                'ProcessDate' => $o->process_date,
                'AuthCode' => !empty($o->auth_code) ? $o->auth_code : '',
                'FirstAuthAmount' => $order->PeriodAmount,
                'TotalSuccessTimes' => $order->TotalSuccessTimes,
                //'SimulatePaid' => $order->SimulatePaid,
              );

              // #40509, do not send email notification after transaction overdue
              if (strtotime($o->process_date) < strtotime('today')) {
                $post['do_not_email'] = 1;
                $post['do_not_receipt'] = 1;
              }

              // manually trigger ipn
              self::doIPN(array('allpay', 'ipn', 'Credit'), $post, $get, FALSE);
            }
          }
        }
      }
    }
  }

  /**
   * Check TradeStatus from ALLPAY
   *
   * @param string $orderId
   * @param array $order
   * @return false|string
   */
  public static function tradeCheck($orderId, $order = NULL) {
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->trxn_id = $orderId;
    if ($contribution->find(TRUE)) {
      $paymentProcessorId = $contribution->payment_processor_id;
      if ($contribution->contribution_status_id == 1) {
        $message = ts('There are no any change.');
        return $message;
      }
    }
    if (!empty($paymentProcessorId) && !empty($contribution->id)) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($paymentProcessorId, $contribution->is_test ? 'test': 'live');

      if (strstr($paymentProcessor['payment_processor_type'], 'ALLPAY') && !empty($paymentProcessor['user_name'])) {
        $processor = array(
          'password' => $paymentProcessor['password'],
          'signature' => $paymentProcessor['signature'],
        );
        $postData = array(
          'MerchantID' => $paymentProcessor['user_name'],
          'MerchantTradeNo' => $orderId,
          'TimeStamp' => CRM_REQUEST_TIME,
        );
        self::generateMacValue($postData, $processor);
        if (empty($order)) {
          $order = self::postdata($paymentProcessor['url_api'], $postData, FALSE);
        }

        // Online contribution
        // Only trigger if there are pay time in result;
        if (!empty($order) && is_array($order) && isset($order['TradeStatus'])) {
          // transition status ipn when status is change
          $processIPN = FALSE;
          if ($order['TradeStatus'] == 1 && $contribution->contribution_status_id != 1) {
            $processIPN = TRUE;
          }
          if ($order['TradeStatus'] != 1 && in_array($contribution->contribution_status_id, array(1, 2))) {
            $processIPN = TRUE;
          }
          // can't find trade number
          if ($order['TradeStatus'] == '10200047') {
            $processIPN = FALSE;
          }

          if ($processIPN) {
            $ids = CRM_Contribute_BAO_Contribution::buildIds($contribution->id);
            $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, TRUE);

            parse_str($query, $ipnGet);
            $rtnMsg = self::getErrorMsg($order['TradeStatus']);
            $ipnPost = array(
              'MerchantID' => $order['MerchantID'],
              'MerchantTradeNo' => $order['MerchantTradeNo'],
              'RtnCode' => $order['TradeStatus'],
              'RtnMsg' => $rtnMsg,
              'Amount' => !empty($order['amount']) ? $order['amount'] : $order['TradeAmt'],
              'Gwsr' => $order['gwsr'],
              'ProcessDate' => $order['process_date'],
              'AuthCode' => !empty($order['auth_code']) ? $order['auth_code'] : '',
            );

            // only non-credit card offline payment have this val
            if (isset($order['ExpireDate'])) {
              $ipnPost['ExpireDate'] = $order['ExpireDate'];
            }

            /* TODO: #40509, do not send email notification after transaction overdue
            if (strtotime($order['process_date']) < CRM_REQUEST_TIME - 86400*2) {
              $ipnPost['do_not_email'] = 1;
            }
            */
            $result = self::doIPN(array('allpay', 'ipn', 'Credit'), $ipnPost, $ipnGet, FALSE);
            return $result;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Help function for such function as recurCheck.
   * Posting Data to AllPay server and retrieve data.
   * Original _civicrm_allpay_postdata
   * 
   * @param string $url Post url
   * @param array $post_data Post Data
   * @param boolean $json Is return json format.
   * @return string|array|null
   */
  public static function postdata($url, $post_data, $json = TRUE){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    $field_string = http_build_query($post_data, '', '&');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
    curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
    $receive = curl_exec($ch);
    if(curl_errno($ch)){
      CRM_Core_Error::debug_log_message('AllPay: Fetch recuring error: curl_errno: '.curl_errno($ch).' / '. curl_error($ch));
    }
    else{
      CRM_Core_Error::debug_log_message('AllPay: Request:'.$url."?".$field_string.'; Receive: '.$receive);
    }
    curl_close($ch);
    if(!empty($receive)){
      if($json){
        return json_decode($receive);
      }
      else{
        $return = array();
        parse_str($receive, $return);
        return $return;
      }
    }
    else{
      return FALSE;
    }
  }
  
  /**
   * Get AllPay error msg.
   * Original _civicrm_allpay_error_msg
   *
   * @param string $code Error code from allpay.
   *
   * @return string Translated error message response to the code.
   */
  static function getErrorMsg($code){
    $code = (string) $code;
    // success
    if($code == '1' || $code == '2'){
      return;
    }

    // error
    $msg = array(
      '10100001' => 'IP Access Denied.',
      '10100050' => 'Parameter Error.',
      '10100054' => 'Trading Number Repeated.',
      '10100055' => 'Create Trade Fail.',
      '10100058' => 'Pay Fail.',
      '10100059' => 'Trading Number cannot Be Found.',
      '10200001' => 'Can not use trade service.',
      '10200002' => 'Trade has been updated before.',
      '10200003' => 'Trade Status Error.',
      '10200005' => 'Price Format Error.',
      '10200007' => 'ItemURL Format Error.',
      '10200047' => 'Cant not find the trade data.',
      '10200050' => 'AllPayTradeID Error.',
      '10200051' => 'MerchantID Error.',
      '10200052' => 'MerchantTradeNo Error.',
      '10200073' => 'CheckMacValue Error',
      '10200124' => 'TopUpUsedESUN Trade Error',
      'uncertain' => 'Please login your payment processor system to check problem.',
      '0' => 'Please login your payment processor system to check problem.',
    );
    if(!empty($msg[$code])){
      return ts($msg[$code]);
    }
    else{
      return ts('Error when processing your payment.');
    }
  }

  /**
   * Original _civicrm_allpay_noid_hash
   * 
   * @param object $o Return log object from AllPay.
   * @param string $main_trxn The TradeNo of AllPay transaction.
   * @return string|null get the hash
   */
  static function getNoidHash($o, $main_trxn) {
    // check database for this
    $lookup = array(
      1 => array('%TradeNo":"'.$main_trxn.'"%ProcessDate":"'.str_replace('/', '\\\\\\\\', $o->process_date).'"%', 'String'),
    );
    $cid = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_allpay WHERE data LIKE %1", $lookup);
    if ($cid) {
      $trxn_id = CRM_Core_DAO::singleValueQuery("SELECT trxn_id FROM civicrm_contribution WHERE id = %1", array(1 => array($cid, 'Integer')));
      list($main_trxn, $noid) = explode('-', $trxn_id);
      if ($noid) {
        return $noid;
      }
      else {
        return;
      }
    }
    elseif (!empty($o->process_date)) {
      if (!empty($o->gwsr)) {
        return $o->gwsr;
      }
      else {
        return substr(md5(CRM_Utils_Array::implode('', (array)$o)), 0, 8);
      }
    }
  }

  function cancelRecuringMessage($recurID){
    if (function_exists("_civicrm_allpay_cancel_recuring_message")) {
      return _civicrm_allpay_cancel_recuring_message(); 
    }
    else {
      CRM_Core_Error::fatal('Module civicrm_allpay doesn\'t exists.');
    }
  }

  /**
   * Execute ipn as called from allpay transaction. Original civicrm_allpay_ipn
   *
   * @param array $instrument The code of used instrument like 'Credit' or 'ATM'.
   * @param array $post Bring post variables if you need test.
   * @param array $get Bring get variables if you need test.
   * @param boolean $print Does server echo the result, or just return that. Default is TRUE.
   *
   * @return string|void If $print is FALSE, function will return the result as Array.
   */
  static function doIPN($arguments, $post = NULL, $get = NULL, $print = TRUE) {
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

    // detect variables
    if(empty($post)){
      CRM_Core_Error::debug_log_message( "civicrm_allpay: Could not find POST data from payment server", TRUE);
      CRM_Utils_System::civiExit();
    }
    else{
      $component = $get['module'];
      if(!empty($component)){
        $ipn = new CRM_Core_Payment_ALLPAYIPN($post, $get);
        $result = $ipn->main($component, $instrument);
        if(!empty($result) && $print){
          echo $result;
        }
        else{
          return $result;
        }
      }
      else{
        CRM_Core_Error::debug_log_message( "civicrm_allpay: Could not get module name from request url", TRUE);
      }
    }
    CRM_Utils_System::civiExit();
  }
}

