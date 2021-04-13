<?php
define('SPGATEWAY_EXPIRE_DAY', 7);
define('SPGATEWAY_MAX_EXPIRE_DAY', 180);
define('SPGATEWAY_RESPONSE_TYPE', 'JSON');
define('SPGATEWAY_MPG_VERSION','1.2');
define('SPGATEWAY_RECUR_VERSION','1.0');
define('SPGATEWAY_QUERY_VERSION','1.1');
define('SPGATEWAY_REAL_DOMAIN', 'https://core.newebpay.com');
define('SPGATEWAY_TEST_DOMAIN', 'https://ccore.newebpay.com');
define('SPGATEWAY_URL_SITE', '/MPG/mpg_gateway');
define('SPGATEWAY_URL_API', '/API/QueryTradeInfo');
define('SPGATEWAY_URL_RECUR', '/MPG/period');
define('SPGATEWAY_URL_CREDITBG', "/API/CreditCard");

date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_SPGATEWAY extends CRM_Core_Payment {

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

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
    $this->_processorName = ts('Spgateway');
    $config = &CRM_Core_Config::singleton();
    $this->_config = $config;
  }

  static function getEditableFields($paymentProcessor = NULL) {
    if (empty($paymentProcessor)) {
      $returnArray = array();
    }
    else {
      if ($paymentProcessor['url_recur'] == 1) {
        // $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
        // Enable Installments field after spgateway update.
        $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'note_title', 'note_body');
      }
    }
    return $returnArray;
  }

  static function postBuildForm($form) {
    $form->addDate('cycle_day_date', FALSE, FALSE, array('formatType' => 'custom', 'format' => 'mm-dd'));
    $cycleDay = &$form->getElement('cycle_day');
    unset($cycleDay->_attributes['max']);
    unset($cycleDay->_attributes['min']);
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

  function _civicrm_spgateway_instrument($type = 'normal'){
    $i = array(
      'Credit Card' => array('label' => ts('Credit Card'), 'desc' => '', 'code' => 'Credit'),
      'ATM' => array('label' => ts('ATM Transfer'), 'desc' => '', 'code' => 'ATM'),
      'Web ATM' => array('label' => ts('Web ATM Transfer'), 'desc' => '', 'code' => 'WebATM'),
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

    // echo  date('Y-m-d H:i:s', $params['payment_expired_timestamp']);
    // once they enter here, we will check SESSION
    // to see what instrument for newweb
    $instrument_id = $params['civicrm_instrument_id'];
    $gid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name = 'payment_instrument'");
    $instrument_name = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_option_value WHERE value = %1 AND option_group_id = {$gid}", array(1 => array($instrument_id, 'Positive')));
    $spgateway_instruments = _civicrm_spgateway_instrument('code');
    $instrument_code = $spgateway_instruments[$instrument_name];
    if (empty($instrument_code)) {
      // For google pay
      $instrument_code = $instrument_name;
    }
    $form_key = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$params['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$params['qfKey'];

    // The first, we insert every contribution into record. After this, we'll use update for the record.
    $record = array('cid' => $params['contributionID']);
    // drupal_write_record("civicrm_contribution_spgateway", $record);

    $_SESSION['spgateway']['submitted'] = TRUE;
    $_SESSION['spgateway']['instrument'] = $instrument_code;

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
    $contrib_values['trxn_id'] = self::_civicrm_spgateway_trxn_id($is_test, $params['contributionID']);
    $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

    // Inject in quickform sessions
    // Special hacking for display trxn_id after thank you page.
    $_SESSION['CiviCRM'][$form_key]['params']['trxn_id'] = $contribution->trxn_id;
    $_SESSION['CiviCRM'][$form_key]['params']['is_pay_later'] = $is_pay_later;
    $params['trxn_id'] = $contribution->trxn_id;

    $arguments = self::_civicrm_spgateway_order($params, $component, $payment_processor, $instrument_code, $form_key);
    if(!$contrib_values['is_recur']){
      self::_civicrm_spgateway_checkmacvalue($arguments, $payment_processor);
    }
    CRM_Core_Error::debug_var('spgateway_post_data', $arguments);
    // making redirect form
    $alter = array(
      'module' => 'civicrm_spgateway',
      'billing_mode' => $payment_processor['billing_mode'],
      'params' => $arguments,
    );
    // drupal_alter('civicrm_checkout_params', $alter);
    print self::_civicrm_spgateway_form_redirect($alter['params'], $payment_processor);
    // move things to CiviCRM cache as needed
    CRM_Utils_System::civiExit();
  }
  
  static function _civicrm_spgateway_order(&$vars, $component, &$payment_processor, $instrument_code, $form_key){

    // url 
    $notify_url = self::_civicrm_spgateway_notify_url($vars, 'spgateway/ipn/'.$instrument_code, $component);
    $civi_base_url = CRM_Utils_System::currentPath();
    $thankyou_url = CRM_Utils_System::url($civi_base_url, array( "_qf_ThankYou_display" => "1" , "qfKey" => $vars['qfKey'], ), true);
  
    // parameter
    if($component == 'event' && !empty($_SESSION['CiviCRM'][$form_key])){
      $values =& $_SESSION['CiviCRM'][$form_key]['values']['event'];
    }
    else{
      $values =& $_SESSION['CiviCRM'][$form_key]['values'];
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
    elseif ($hours > 24 * SPGATEWAY_MAX_EXPIRE_DAY ) {
      $values['expiration_day'] = SPGATEWAY_MAX_EXPIRE_DAY;
    }
    elseif(!empty($hours)){
      $values['expiration_day'] = ceil($hours/24);
    }
  
    // building vars
    $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];
  
    $itemDescription = $vars['description'];
    $itemDescription .= ($vars['description'] == $vars['item_name'])?'':':'.$vars['item_name'];
    $itemDescription .= ':'.$vars['amount'];
    $itemDescription = preg_replace('/[^[:alnum:][:space:]]/u', ' ', $itemDescription);
  
    if(!$vars['is_recur']){
      $args = array(
        'MerchantID' => $payment_processor['user_name'],
        'RespondType' => SPGATEWAY_RESPONSE_TYPE,
        'TimeStamp' => time(),
        'Version' => SPGATEWAY_MPG_VERSION,
        'Amt' => $amount,
        'NotifyURL' => $notify_url,
        'Email' => $vars['email-5'],
        'LoginType' => '0',
        'ItemDesc' => $itemDescription,
        'MerchantOrderNo' => $vars['trxn_id'],
      );
      if ($payment_processor['is_test']) {
        $args['#url'] = SPGATEWAY_TEST_DOMAIN.SPGATEWAY_URL_SITE;
      }
      else {
        $args['#url'] = SPGATEWAY_REAL_DOMAIN.SPGATEWAY_URL_SITE;
      }
  
      switch($instrument_code){
        case 'ATM':
          $args['VACC'] = 1;
          $day = !empty($values['expiration_day']) ? $values['expiration_day'] : SPGATEWAY_EXPIRE_DAY;
          $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          $args['CustomerURL'] = $thankyou_url;
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          break;
        case 'BARCODE':
          $args['BARCODE'] = 1;
          $day = !empty($values['expiration_day']) ? $values['expiration_day'] : SPGATEWAY_EXPIRE_DAY;
          $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          $args['CustomerURL'] = $thankyou_url;
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          break;
        case 'CVS':
          $args['CVS'] = 1;
          if($instrument_code == 'CVS' && !empty($values['expiration_day'])) {
            $day = !empty($values['expiration_day']) ? $values['expiration_day'] : SPGATEWAY_EXPIRE_DAY;
            $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          }
          // $args['ReturnURL'] = url('spgateway/record/'.$vars['contributionID'], array('absolute' => true));
          // $args['Desc_1'] = '';
          // $args['Desc_2'] = '';
          // $args['Desc_3'] = '';
          // $args['Desc_4'] = '';
  
          #ATM / CVS / BARCODE
          $args['CustomerURL'] = $thankyou_url;
          break;
        case 'WebATM':
          $args['WEBATM'] = 1;
          $args['ReturnURL'] = $thankyou_url;
          break;
        case 'Credit':
          $args['CREDIT'] = 1;
          $args['ReturnURL'] = $thankyou_url;
          break;
        case 'GooglePay':
          $args['ANDROIDPAY'] = 1;
          $args['ReturnURL'] = $thankyou_url;
          break;
      }
  
      if(CRM_Utils_System::getUFLocale() == 'en'){
        $args['LangType'] = 'en';
      }
    }else{
      $data = array(
        'MerchantID' => $payment_processor['user_name'],
        'RespondType' => SPGATEWAY_RESPONSE_TYPE,
        'TimeStamp' => time(),
        'Version' => SPGATEWAY_RECUR_VERSION,
        'Amt' => $amount,
        'NotifyURL' => $notify_url."&qfKey=".$vars['qfKey'],
        'PayerEmail' => $vars['email-5'],
        'LoginType' => '0',
        'MerOrderNo' => $vars['trxn_id'],
        'ProdDesc' => $itemDescription,
        'PeriodAmt' => $amount,
        'PeriodStartType' => 2,
        'ReturnURL' => $thankyou_url,
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
      if(CRM_Utils_System::getUFLocale() == 'en'){
        $data['LangType'] = 'en';
      }
      $str = http_build_query($data, '', '&');
      $strPost = _civicrm_spgateway_recur_encrypt($str, $payment_processor);
      $args['PostData_'] = $strPost;
      $args['MerchantID_'] = $payment_processor['user_name'];
      if ($payment_processor['is_test']) {
        $args['#url'] = SPGATEWAY_TEST_DOMAIN.SPGATEWAY_URL_RECUR;
      }
      else {
        $args['#url'] = SPGATEWAY_REAL_DOMAIN.SPGATEWAY_URL_RECUR;
      }
    }
  
    
    return $args ;
  }
  
  static function _civicrm_spgateway_form_redirect($redirect_vars, $payment_processor){
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Expires: 0');
  
    $o = "";
  
    $js = 'document.forms.redirect.submit();';
    $o .= '<form action="'.$redirect_vars['#url'].'" name="redirect" method="post" id="redirect-form">';
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
  
  static function _civicrm_spgateway_notify_url(&$vars, $path, $component){
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
         $query["ppid"] = $vars['payment_processor'];
       }
    }
  
    $url = CRM_Utils_System::url(
      $path,
      $query,
      TRUE,
    );
    if( ( !empty($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] == 'on' ) || ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ){
      return str_replace('http://', 'https://', $url);
    }
    else{
      return $url;
    }
  }

  static function _civicrm_spgateway_trxn_id($is_test, $id){
    if($is_test){
      $id = 'test' . substr(str_replace(array('.','-'), '', $_SERVER['HTTP_HOST']), 0, 3) . $id. 'T'. mt_rand(100, 999);
    }
    return $id;
  }
  
  /**
   * $objects should be an array of the dao of contribution, email, merchant_payment_processor
   */
  static function _civicrm_spgateway_mobile_checkout($type, $post, $objects) {
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
        'MerchantOrderNo' => self::_civicrm_spgateway_trxn_id($is_test, $contribution->id),
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
  
      $data = _civicrm_spgateway_recur_encrypt(http_build_query($params), get_object_vars($merchantPaymentProcessor));
  
      $data = array(
        'MerchantID_' => $merchantPaymentProcessor->user_name,
        'PostData_' => $data,
        'Pos_' => 'JSON',
      );
      if($contribution->is_test){
        $url = SPGATEWAY_TEST_DOMAIN.SPGATEWAY_URL_CREDITBG;
      }else{
        $url = SPGATEWAY_REAL_DOMAIN.SPGATEWAY_URL_CREDITBG;
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
      // civicrm_spgateway_record($contribution->id, get_object_vars($result));
      $return = array();
      if($result->Status == 'SUCCESS'){
        $return['is_success'] = true;
      }
      $return['message'] = $result->Message;
    }
    return $return;
  }

  
  static function _civicrm_spgateway_checkmacvalue(&$args, $payment_processor){
    $used_args = array('HashKey','Amt','MerchantID','MerchantOrderNo','TimeStamp','Version','HashIV');
    return self::_civicrm_spgateway_encode($args, $payment_processor, $used_args);
  }

  static function _civicrm_spgateway_checkcode(&$args, $payment_processor){
    $used_args = array('HashIV','Amt','MerchantID','MerchantOrderNo','TradeNo','HashKey');
    return self::_civicrm_spgateway_encode($args, $payment_processor, $used_args);
  }

  static function _civicrm_spgateway_recur_encrypt($str, $payment_processor){
    $key = $payment_processor['password'];
    $iv = $payment_processor['signature'];
    self::_civicrm_spgateway_checkKeyIV($key);
    self::_civicrm_spgateway_checkKeyIV($iv);
    $str = trim(bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, self::_civicrm_spgateway_addpadding($str), MCRYPT_MODE_CBC, $iv)));
    return $str;
  }
  
  static function _civicrm_spgateway_recur_decrypt($str, $payment_processor){
    $key = $payment_processor['password'];
    $iv = $payment_processor['signature'];
    self::_civicrm_spgateway_checkKeyIV($key);
    self::_civicrm_spgateway_checkKeyIV($iv);
    $str = self::_civicrm_spgateway_strippadding(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, hex2bin($str), MCRYPT_MODE_CBC, $iv));
    return $str;
  }
  
  static function _civicrm_spgateway_addpadding($string, $blocksize = 32) {
    $len = strlen($string);
    $pad = $blocksize - ($len % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    return $string;
  }
  
  static function _civicrm_spgateway_strippadding($string) {
      $slast = ord(substr($string, -1));
      $slastc = chr($slast);
      if (preg_match("/$slastc{" . $slast . "}/", $string)) {
          $string = substr($string, 0, strlen($string) - $slast);
          return $string;
      } else {
          return false;
      }
  }
  
  static function _civicrm_spgateway_encode(&$args, $payment_processor, $checkArgs = array()){
    // remove empty arg
    if(is_array($args)){
      foreach($args as $k => $v){
        if($k == 'CheckValue'){
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
    if(count($checkArgs) == 0){
      $checkArgs = array('HashKey','Amt','MerchantID','MerchantOrderNo','TimeStamp','Version','HashIV');
    }
    foreach($checkArgs as $k){
      switch ($k) {
        case 'HashIV':
        case 'IV':
          $v = $payment_processor['signature'];
          self::_civicrm_spgateway_checkKeyIV($v);
          break;
        case 'HashKey':
        case 'Key':
          $v = $payment_processor['password'];
          self::_civicrm_spgateway_checkKeyIV($v);
          break;
        default:
          $v = $args[$k];
          break;
      }
      $a[] = $k.'='.$v;
    }
    $keystr = implode('&', $a);
  
    $checkvalue = strtoupper(hash("sha256", $keystr));
    $args['CheckValue'] = $checkvalue;
    return $checkvalue;
  }

  static function _civicrm_spgateway_checkKeyIV($v){
    if(empty($v)){
      CRM_Core_Error::fatal(ts('KEY and IV should have value.'));
    }
  }


  /*
      * $params = array(
      *    'contribution_recur_id   => Positive,
      *    'contribution_status_id' => Positive(7 => suspend, 3 => terminate, 5 => restart),
      *    'amount'                 => Positive,
      *    'frequency_unit'         => String('year', 'month')
      *    'cycle_day'              => Positive(1 - 31, 101 - 1231)
      *    'end_date'               => Date
      * )
      */
  function doUpdateRecur($params, $debug = FALSE) {
    if ($debug) {
      CRM_Core_error::debug('SPGATEWAY doUpdateRecur $params', $params);
    }
    if (module_load_include('inc', 'civicrm_spgateway', 'civicrm_spgateway.api') === FALSE) {
      CRM_Core_Error::fatal('Module civicrm_spgateway doesn\'t exists.');
    }
    else if (empty($params['contribution_recur_id'])) {
      CRM_Core_Error::fatal('Missing contribution recur ID in params');
    }
    else {
      // Prepare params
      $recurResult = array();

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
        $spgatewayAPI = new spgateway_spgateway_api($apiConstructParams);
        $newStatusId = $params['contribution_status_id'];
        
        /*
        * $requestParams = array(
        *    'AlterStatus'          => Positive(7 => suspend, 3 => terminate, 5 => restart),
        * )
        */
        $requestParams = array(
          'MerOrderNo' => $merchantId,
          'PeriodNo' => $dao->period_no,
          'AlterType' => self::$_statusMap[$newStatusId],
        );
        $apiAlterStatus = clone $spgatewayAPI;
        $recurResult = $apiAlterStatus->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterType'] = $apiAlterStatus;
        }

        if (!empty($recurResult['is_error'])) {
          // There are error msg in $recurResult['msg']
          $errResult = $recurResult;
          return $errResult;
        }
        else {
          // for status 'suspend', result status id could be 1 or 7, depends on input status id.
          $recurResult['contribution_status_id'] = $params['contribution_status_id'];
        }
      }

      // Send alter other property API.

      $apiConstructParams['apiType'] = 'alter-amt';
      $spgatewayAPI = new spgateway_spgateway_api($apiConstructParams);
      $isChangeRecur = FALSE;
      $requestParams = array(
        'MerOrderNo' => $merchantId,
        'PeriodNo' => $dao->period_no,
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
        CRM_Core_error::debug('SPGATEWAY doUpdateRecur $requestParams', $requestParams);
      }

      /**
       * Send Request.
       */
      if ($isChangeRecur) {
        $apiOthers = clone $spgatewayAPI;
        $recurResult2 = $apiOthers->request($requestParams);
        if ($debug) {
          $recurResult['API']['AlterMnt'] = $apiOthers;
          CRM_Core_error::debug('SPGATEWAY doUpdateRecur $apiOthers', $apiOthers);
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
   * return array(
   *   // All instrument:
   *   'status' => contribuion_status
   *   'msg' => return message
   * 
   *   // Not Credit Card:
   *   'payment_instrument' => civicrm_spgateway_notify_display() return value
   * )
   */
  function doGetResultFromIPNNotify($contributionId, $submitValues = array()) {
    // First, check if it is redirect payment.
    $instruments = CRM_Contribute_PseudoConstant::paymentInstrument('Name');
    $cDao = new CRM_Contribute_DAO_Contribution();
    $cDao->id = $contributionId;
    $cDao->fetch(TRUE);
    if (strstr($instruments[$cDao->payment_instrument_id], 'Credit')) {
      // If contribution status id == 2, wait 3 second for IPN trigger
      if ($cDao->contribution_status_id == 2) {
        sleep(3);
        $contribution_status_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_status_id');
        if ($contribution_status_id == 2) {
          $ids = CRM_Contribute_BAO_Contribution::buildIds($contributionId);
          $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, TRUE);
          parse_str($query, $get);
          $result = civicrm_spgateway_ipn('Credit', $submitValues, $get, FALSE);
          if(strstr($result, 'OK')){
            $status = 1;
          }
          else{
            $status = 2;
          }
        }
      }
      else {
        $status = $cDao->contribution_status_id;
        if (!empty($submitValues['JSONData'])) {
          $return_params = _civicrm_spgateway_post_decode($submitValues['JSONData']);
        }
        if(!empty($submitValues['Period']) && empty($return_params)){
          $payment_processors = CRM_Core_BAO_PaymentProcessor::getPayment($cDao->payment_processor_id, $cDao->is_test?'test':'live');
          $return_params = _civicrm_spgateway_post_decode(_civicrm_spgateway_recur_decrypt($submitValues['Period'], $payment_processors));
        }
        $msg = _civicrm_spgateway_error_msg($return_params['RtnCode']);
      }
    }
    else {

    }

  }
}

