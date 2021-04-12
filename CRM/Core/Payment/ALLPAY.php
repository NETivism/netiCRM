<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_ALLPAY extends CRM_Core_Payment {

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

  // Get all used instrument
  static function _civicrm_allpay_instrument($type = 'normal'){
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

  static function _civicrm_allpay_trxn_id($is_test, $id){
    if($is_test){
      $id = 'test' . substr(str_replace(array('.','-'), '', $_SERVER['HTTP_HOST']), 0, 3) . $id. 'T'. mt_rand(100, 999);
    }
    return $id;
  }
  static function _civicrm_allpay_recur_trxn($parent, $gwsr){
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
    $payment_processor = $this->_paymentProcessor;
    $is_test = $this->_mode == 'test' ? 1 : 0;

    // once they enter here, we will check SESSION
    // to see what instrument for newweb
    $instrument_id = $params['civicrm_instrument_id'];
    $instrument_name = civicrm_instrument_by_id($instrument_id, 'name'); // TODO
    $allpay_instruments = self::_civicrm_allpay_instrument('code');
    $instrument_code = $allpay_instruments[$instrument_name];
    $form_key = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$params['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$params['qfKey'];

    // The first, we insert every contribution into record. After this, we'll use update for the record.
    $record = array('cid' => $params['contributionID']);
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
          $params = array(
            1 => array($params['participantID'], 'Integer'),
          );
          $dao = CRM_Core_DAO::executeQuery($sql, $params);
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
    $contrib_values['trxn_id'] = self::_civicrm_allpay_trxn_id($is_test, $params['contributionID']);
    $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

    // Inject in quickform sessions
    // Special hacking for display trxn_id after thank you page.
    $_SESSION['CiviCRM'][$form_key]['params']['trxn_id'] = $contribution->trxn_id;
    $_SESSION['CiviCRM'][$form_key]['params']['is_pay_later'] = $is_pay_later;
    $params['trxn_id'] = $contribution->trxn_id;

    $arguments = self::_civicrm_allpay_order($params, $component, $payment_processor, $instrument_code, $form_key);
    self::_civicrm_allpay_checkmacvalue($arguments, $payment_processor);
    /*
    $alter = array(
      'module' => 'civicrm_allpay',
      'billing_mode' => $payment_processor['billing_mode'],
      'params' => $arguments,
    );
    drupal_alter('civicrm_checkout_params', $alter);
    */
    print self::_civicrm_allpay_form_redirect($arguments, $payment_processor);
    // move things to CiviCRM cache as needed
    CRM_Utils_System::civiExit();
  }

  static function _civicrm_allpay_order(&$vars, $component, &$payment_processor, $instrument_code, $form_key){

    // url 
    $notify_url = self::_civicrm_allpay_notify_url($vars, 'allpay/ipn/'.$instrument_code, $component);
    $civi_base_url = CRM_Utils_System::currentPath();
    $thankyou_url = CRM_Utils_System::url($civi_base_url, array( "_qf_ThankYou_display" => "1" , "qfKey" => $vars['qfKey'], ), true);
  
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
        $args['PaymentInfoURL'] = CRM_Utils_System::url('allpay/record/'.$vars['contributionID'], array(), true);
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
        if(CRM_Utils_System::getUFLocale() == 'en'){
          $args['Language'] = 'ENG';
        }
        # Recurring
        break;
    }
    return $args ;
  }
  
  static function _civicrm_allpay_form_redirect($redirect_vars, $payment_processor){
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
  
  static function _civicrm_allpay_notify_url(&$vars, $path, $component){
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
  
    $url = CRM_Utils_System::url(
      $path,
      $query,
      true,
    );
    if( ( !empty($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] == 'on' ) || ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ){
      return str_replace('http://', 'https://', $url);
    }
    else{
      return $url;
    }
  }

  static function _civicrm_allpay_checkmacvalue(&$args, $payment_processor){
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
    $keystr = implode('&', $a);
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

  function cancelRecuringMessage($recurID){
    if (function_exists("_civicrm_allpay_cancel_recuring_message")) {
      return _civicrm_allpay_cancel_recuring_message(); 
    }else{
      CRM_Core_Error::fatal('Module civicrm_allpay doesn\'t exists.');
    }
  }
}

