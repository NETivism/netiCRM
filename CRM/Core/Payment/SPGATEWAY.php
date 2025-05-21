<?php
date_default_timezone_set('Asia/Taipei');

class CRM_Core_Payment_SPGATEWAY extends CRM_Core_Payment {
  const EXPIRE_DAY = 7;
  const MAX_EXPIRE_DAY = 180;
  const RESPONSE_TYPE = 'JSON';
  const MPG_VERSION = '1.2';
  const AGREEMENT_VERSION = '1.5';
  const RECUR_VERSION = '1.0';
  const QUERY_VERSION = '1.1';
  const REAL_DOMAIN = 'https://core.newebpay.com';
  const TEST_DOMAIN = 'https://ccore.newebpay.com';
  const URL_SITE = '/MPG/mpg_gateway';
  const URL_QUERY = '/API/QueryTradeInfo';
  const URL_RECUR = '/MPG/period';
  const URL_CREDITBG = "/API/CreditCard";
  const QUEUE_NAME = 'spgateway_batch_all_recur';

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

  static function getAdminFields($ppDAO, $form){
    $fields = array(
      array(
        'name' => 'user_name',
        'label' => $ppDAO->user_name_label,
      ),
      array(
        'name' => 'password',
        'label' => $ppDAO->password_label,
      ),
      array(
        'name' => 'signature',
        'label' => $ppDAO->signature_label,
      ),
      array(
        'name' => 'subject',
        'label' => ts('Order Comment'),
      ),
      array(
        'name' => 'url_recur',
        'label' => ts('Enable Neweb Recurring API'),
      ),
      array(
        'name' => 'url_api',
        'label' => ts('Credit Card Agreement'),
      ),
    );
    $nullObj = NULL;
    $ppid = CRM_Utils_Request::retrieve('id', 'Positive', $nullObj);
    if ($ppid) {
      $params = array(
        1 => array($ppid, 'Positive'),
        2 => array(0, 'Integer'),
      );
      $paramsTest = array(
        1 => array($ppid+1, 'Positive'),
        2 => array(1, 'Integer'),
      );
      $sql = 'SELECT count(id) FROM civicrm_contribution WHERE payment_processor_id = %1 AND is_test = %2';
      $isHavingContribution = CRM_Core_DAO::singleValueQuery($sql, $params);
      $isHavingContributionTest = CRM_Core_DAO::singleValueQuery($sql, $paramsTest);
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('having_contribution', $isHavingContribution);
      $smarty->assign('having_contribution_test', $isHavingContributionTest);
    }

    // remove form rules
    $noRuleElement = array('url_recur', 'url_api', 'test_url_recur', 'test_url_api');
    foreach($noRuleElement as $ele) {
      foreach ($form->_rules[$ele] as $key => $rule) {
        if ($rule['type'] == 'url') {
          unset($form->_rules['url_recur'][$key]);
        }
      }
    }
    return $fields;
  }

  public static function getEditableFields($paymentProcessor = NULL, $form = NULL) {
    if (empty($paymentProcessor)) {
      $returnArray = array();
    }
    elseif (!empty($paymentProcessor['url_recur'])) {
      $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'frequency_unit', 'recurring', 'installments', 'note_title', 'note_body');
    }
    elseif (!empty($paymentProcessor['url_api'])) {
      $returnArray = array('contribution_status_id', 'amount', 'cycle_day', 'recurring', 'installments', 'note_title', 'note_body', 'end_date');
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
        if (empty($paymentProcessor['url_recur']) && empty($paymentProcessor['url_api'])) {
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
      if (!empty($params['contributionRecurID'])) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_spgateway (cid, contribution_recur_id) VALUES (%1, %2)", array(
          1 => array($params['contributionID'], 'Integer'),
          2 => array($params['contributionRecurID'], 'Integer'),
        ));
      }
      else {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_spgateway (cid) VALUES (%1)", array(
          1 => array($params['contributionID'], 'Integer'),
        ));
      }
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

    if(!empty($this->_paymentProcessor['url_api']) && $this->_paymentProcessor['url_api'] > 0) {
      $tradeInfo = array(
        'MerchantID' => $this->_paymentProcessor['user_name'],
        'RespondType' => self::RESPONSE_TYPE,
        'TimeStamp' => time(),
        'Version' => self::AGREEMENT_VERSION,
        'Amt' => $amount,
        'ItemDesc' => $itemDescription,
        'MerchantOrderNo' => $vars['trxn_id'],
        'ReturnURL' => $thankyouURL,
        'NotifyURL' => $notifyURL,
        // 'ClientBackURL' => $thankyouURL, // For cancellation
        'Email' => $vars['email-5'],
        'EmailModify' => 0, // Default to not allowing email modification
        'LoginType' => 0,
        'LangType' => ($tsLocale == CRM_Core_Config::SYSTEM_LANG) ? 'en' : 'zh-tw',
        'P3D' => '1',  // Default to non-3D transaction
        'CREDITAGREEMENT' => 1, // For credit card token payment
        'OrderComment' => !empty($this->_paymentProcessor['subject']) ? $this->_paymentProcessor['subject'] : '',
        'TokenTerm' => isset($vars['is_recur']) && !empty($vars['contributionRecurID']) ? $vars['contributionRecurID'] : $vars['contactID'],
        'TokenLife' => '', // Default empty to the card expire date
      );

      // Use hook_civicrm_alterPaymentProcessorParams
      $mode = $this->_paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = CRM_Core_Payment::singleton($mode, $this->_paymentProcessor, CRM_Core_DAO::$_nullObject);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $vars, $tradeInfo);

      // Encrypt Recurring Request.
      CRM_Core_Error::debug_var('spgateway_agreement_args', $tradeInfo);
      $tradeInfoStr = http_build_query($tradeInfo, '', '&');
      $tradeInfoEncrypted  = CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($tradeInfoStr, $this->_paymentProcessor);
      $tradeSha = CRM_Core_Payment_SPGATEWAYAPI::tradeSha($tradeInfoEncrypted, $this->_paymentProcessor);

      // Create final args
      $args = array(
        'MerchantID' => $this->_paymentProcessor['user_name'],
        'TradeInfo' => $tradeInfoEncrypted,
        'TradeSha' => $tradeSha,
        'Version' => self::AGREEMENT_VERSION,
      );
      // $args['PostData_'] = $tradeInfoEncrypted;
      // $args['MerchantID_'] = $this->_paymentProcessor['user_name'];
      if ($this->_paymentProcessor['is_test']) {
        $args['#url'] = self::TEST_DOMAIN.self::URL_SITE;
      }
      else {
        $args['#url'] = self::REAL_DOMAIN.self::URL_SITE;
      }
    }
    elseif(!$vars['is_recur']){
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
          break;
        case 'BARCODE':
          $args['BARCODE'] = 1;
          $day = !empty($values['expiration_day']) ? $values['expiration_day'] : self::EXPIRE_DAY;
          $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          $args['CustomerURL'] = $thankyouURL;
          break;
        case 'CVS':
          $args['CVS'] = 1;
          if($instrumentCode == 'CVS' && !empty($values['expiration_day'])) {
            $day = !empty($values['expiration_day']) ? $values['expiration_day'] : self::EXPIRE_DAY;
            $args['ExpireDate'] = date('Ymd',strtotime("+$day day"));
          }
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

    return $args;
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
        'Pos_' => self::RESPONSE_TYPE,
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
    if (empty($this->_paymentProcessor['url_recur'])) {
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
      if ($dao->payment_processor_type == 'SPGATEWAY' && $dao->url_recur > 0) {
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
    $table = array();
    $syncMsg = self::getSyncNowMessage($contributionId);
    if (!empty($syncMsg)) {
      require_once 'CRM/Core/Smarty/resources/String.php';
      $smarty = CRM_Core_Smarty::singleton();
      civicrm_smarty_register_string_resource();
      $table[ts("Manually Synchronize")] = $smarty->fetch('string: {$form.$update_notify.html}');
    }
    return $table;
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
      $updateString = ts('If the transaction is finished, it will update the follow data by this action: %1', array(1 => $updateData));
      $form->set('sync_data_hint', $updateString);
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
      if (!empty($post['JSONData']) || !empty($post['Period']) || !empty($post['Result']) ||
        (!empty($post['Version']) && $post['Version'] === self::AGREEMENT_VERSION && !empty($post['TradeInfo']))
      ) {
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
    if (empty($id)) {
      $id = 'ag_' . substr(md5(uniqid(mt_rand(), true)), 0, 6).'_'.mt_rand(100, 999);
    }
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
          $queryTrxnId = preg_replace('/^r_/', '', $trxnId);
          $data = array(
            'Amt' => floor($amount),
            'MerchantID' => $paymentProcessor['user_name'],
            'MerchantOrderNo' => $queryTrxnId,
            'RespondType' => self::RESPONSE_TYPE,
            'TimeStamp' => CRM_REQUEST_TIME,
            'Version' => self::QUERY_VERSION,
          );
          $args = array('IV','Amt','MerchantID','MerchantOrderNo', 'Key');
          CRM_Core_Payment_SPGATEWAYAPI::encode($data, $paymentProcessor, $args);
          $urlApi = $contribution->is_test ? self::TEST_DOMAIN.self::URL_QUERY : self::REAL_DOMAIN.self::URL_QUERY;
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

          $queryTrxnId = preg_replace('/^r_/', '', $trxnId);
          $data = array(
            'Amt' => floor($amount),
            'MerchantID' => $paymentProcessor['user_name'],
            'MerchantOrderNo' => $queryTrxnId,
            'RespondType' => self::RESPONSE_TYPE,
            'TimeStamp' => CRM_REQUEST_TIME,
            'Version' => self::QUERY_VERSION,
          );
          $args = array('IV','Amt','MerchantID','MerchantOrderNo', 'Key');
          CRM_Core_Payment_SPGATEWAYAPI::encode($data, $paymentProcessor, $args);
          $urlApi = $contribution->is_test ? self::TEST_DOMAIN.self::URL_QUERY : self::REAL_DOMAIN.self::URL_QUERY;
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
            if (preg_match('/^r_/', $trxnId)) {
              $ipnResult->Result->OrderNo = $trxnId;
            }
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
            if ($contribution->find(TRUE) && preg_match('/_1$/', $trxnId)) {
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

  /**
   * Using weblog post data to sync
   *
   * @param string $processOnlyDate string that format YYYYmmddHH indicate only process date in that hour
   * @param array $lines custom provided lines for test o process
   * @return void
   */
  public static function syncTransactionWebLog($filterDatetime = '', $lines = NULL) {
    $paymentProcessors = array();
    if (isset($lines) && is_array($lines)) {
      $logLines = $lines;
    }
    else {
      $logLines = array();
      $logFile = CRM_Utils_System::cmsRootPath().'/'.CRM_Core_Config::singleton()->webLogDir.'/ipn_post.log';
      $logContent = '';
      if (strpos($logFile, '/') === 0 && is_file($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = explode("\n", $logContent);
      }
    }

    if (!empty($logLines)) {
      $ordersByMerchant = array();
      foreach ($logLines as $idx => $logLine) {
        $getParams = $ipnResult = $postParams = array();
        $logLine = trim($logLine);
        if (empty($logLine)) {
          continue;
        }
        // separate by space
        preg_match('/^([^ ]+)\s\[([^\s]+)\]\s([^\s]+)\s(.*)$/', $logLine, $logMatches);
        if (count($logMatches) >= 4) {
          $isoDate = $logMatches[2];
          // skip ipn when filter
          if ($filterDatetime && strpos(date('YmdH', strtotime($isoDate)), $filterDatetime) === FALSE) {
            continue;
          }

          $getString = $logMatches[3];
          $postString = isset($logMatches[4]) ? $logMatches[4] : '';
          $postString = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function ($strMatches) {
            return chr(hexdec($strMatches[1]));
          }, $postString);

          $parsedUrl = parse_url($getString);
          parse_str($parsedUrl['query'], $getParams);

          // analysis POST
          if (!empty($postString)) {
            if (strpos($postString, 'JSONData=') === 0) {
              $postString = substr($postString, 9);
              $postParams = json_decode($postString, true);
              if (!empty($postParams['Result'])) {
                $ipnResult = json_decode($postParams['Result'], TRUE);
                if (!empty($ipnResult['MerchantID'])) {
                  $ordersByMerchant[$ipnResult['MerchantID']][$idx] = array(
                    'recurring' => FALSE,
                    'contribution_id' => !empty($getParams['cid']) ? $getParams['cid'] : '',
                    'contact_id' => !empty($getParams['contact_id']) ? $getParams['contact_id'] : '',
                    'success' => (isset($postParams['Status']) && $postParams['Status'] === 'SUCCESS') ? TRUE : FALSE,
                    'message' => isset($postParams['Message']) ? $postParams['Message'] : '',
                    'total_amount' => isset($ipnResult['Amt']) ? (float)$ipnResult['Amt'] : 0,
                    'trxn_id' => isset($ipnResult['MerchantOrderNo']) ? $ipnResult['MerchantOrderNo'] : '',
                    'receive_date' => isset($ipnResult['PayTime']) ? date('c', strtotime($ipnResult['PayTime'])) : '',
                    'ipn_date' => $isoDate,
                  );
                }
              }
            }
            elseif (strpos($postString, 'Content-Disposition: form-data') !== false) {
              $rawPostData = preg_replace('/\r\n--------------------------\w+--\r\n$/', '', $postString);
              $postParts = preg_split('/\r\n--------------------------\w+\r\n/', $rawPostData);
              $postParams = array();
              foreach ($postParts as $part) {
                if (preg_match('/Content-Disposition: form-data; name="(.+?)"\r\n\r\n(.*)/s', $part, $strMatches)) {
                  $key = $strMatches[1];
                  $value = $strMatches[2];
                  $postParams[$key] = $value;
                }
              }
              if (!empty($postParams['Period'])) {
                // decode
                if(is_numeric($getParams['cid'])) {
                  $dao = CRM_Core_DAO::executeQuery("SELECT payment_processor_id, is_test FROM civicrm_contribution WHERE id = %1", array(
                    1 => array($getParams['cid'], 'Integer'),
                  ));
                  $dao->fetch();
                  if (!empty($paymentProcessors[$dao->payment_processor_id])) {
                    $paymentProcessor = $paymentProcessors[$dao->payment_processor_id];
                  }
                  elseif (!empty($dao->payment_processor_id)) {
                    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test' : 'live');
                  }
                  $postDecode = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($postParams['Period'], $paymentProcessor);
                  if (!empty($postDecode) && json_decode($postDecode)) {
                    $postParams = json_decode($postDecode, TRUE);
                    $ipnResult = $postParams['Result'];
                    if (!empty($ipnResult['OrderNo'])) {
                      $orderNumber = $ipnResult['OrderNo'];
                      $firstRecurring = FALSE;
                    }
                    else {
                      $orderNumber = $ipnResult['MerchantOrderNo'];
                      $firstRecurring = TRUE;
                    }
                    $amount = isset($ipnResult['AuthAmt']) ? $ipnResult['AuthAmt'] : $ipnResult['PeriodAmt'];
                    $receiveDate = '';
                    if (!empty($ipnResult['AuthDate'])) {
                      $receiveDate = date('c', strtotime($ipnResult['AuthDate']));
                    }
                    elseif (!empty($ipnResult['AuthTime'])) {
                      $receiveDate = date('c', strtotime($ipnResult['AuthTime']));
                    }
                    $ordersByMerchant[$ipnResult['MerchantID']][$idx] = array(
                      'recurring' => TRUE,
                      'first_recurring' => $firstRecurring,
                      'contribution_id' => !empty($getParams['cid']) ? $getParams['cid'] : '',
                      'contact_id' => !empty($getParams['contact_id']) ? $getParams['contact_id'] : '',
                      'success' => (isset($postParams['Status']) && $postParams['Status'] === 'SUCCESS') ? TRUE : FALSE,
                      'message' => isset($postParams['Message']) ? $postParams['Message'] : '',
                      'total_amount' => $amount,
                      'trxn_id' => $orderNumber,
                      'receive_date' => !empty($receiveDate) ? $receiveDate : '',
                      'ipn_date' => $isoDate,
                      'original_request' => [
                        'get' => $getParams,
                        'post' => $postParams,
                      ],
                    );
                  }
                }
              }
            }
          }
        }
      }
      // process complete, start to call sync when necessery
      // only trigger sync when contribution status is pending
      if (!empty($ordersByMerchant)) {
        foreach($ordersByMerchant as $merchantId => $orders) {
          foreach($orders as $idx => $order) {
            if (!empty($order['trxn_id'])) {
              if ($order['first_recurring']) {
                $current_status_id = CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id IN (%1, %2)", array(
                  1 => array($order['trxn_id'], 'String'),
                  2 => array($order['trxn_id'].'_1', 'String'),
                ));
              }
              else {
                $current_status_id = CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1", array(
                  1 => array($order['trxn_id'], 'String'),
                ));
              }
              if ($order['recurring']) {
                if (!empty($order['original_request']['post'])) {
                  CRM_Core_Error::debug_var("spgateway_weblog_sync", $order['original_request']);
                }
                if (empty($current_status_id)) {
                  if (!$order['first_recurring'] && !$order['success']) {
                    CRM_Core_Error::debug_log_message("spgateway: weblog sync trxn_id {$order['trxn_id']} failed, skipped. Reason: ".$order['message']);
                  }
                  else {
                    CRM_Core_Error::debug_log_message("spgateway: weblog sync recur create for trxn_id {$order['trxn_id']}");
                    self::recurSyncTransaction($order['trxn_id'], TRUE);
                  }
                }
                elseif($current_status_id == 2) {
                  CRM_Core_Error::debug_log_message("spgateway: weblog sync recur status for trxn_id {$order['trxn_id']}");
                  if ($order['first_recurring']) {
                    $ids = explode('_', $order['trxn_id']);
                    self::syncTransaction($ids[0]);
                  }
                  else {
                    self::syncTransaction($order['trxn_id']);
                  }
                }
              }
              else {
                if ($current_status_id == 2) {
                  // only sync status
                  CRM_Core_Error::debug_log_message("spgateway: weblog sync non-recur status for trxn_id {$order['trxn_id']}");
                  self::syncTransaction($order['trxn_id']);
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * doExecuteAllRecur for spgateway agreement payment
   *
   * @param  int $time
   * @return void
   */
  public static function doExecuteAllRecur($time = NULL) {
    // Check sequence;
    $seq = new CRM_Core_DAO_Sequence();
    $seq->name = self::QUEUE_NAME;

    if ($seq->find(TRUE)) {
      if ( $seq->value && (CRM_REQUEST_TIME - $seq->timestamp) < 1800) {
        // last process is executing.
        $error = "Last process is still executing. Interupt now.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        return $error;
      }
      else {
        // no last process or last process is overdue.
        // delete last sequence if it exist
        $error = "There are a overdue process in DB, delete it.";
        CRM_Core_Error::debug_log_message($error, TRUE);
        $seq->delete();
      }
    }
    // insert new sequence
    $seq->value = date('YmdHis');
    $seq->timestamp = microtime(TRUE);
    $seq->insert();

    if (empty($time)) {
      $time = time();
    }
    $thisMonth = date('m', $time);
    $theMonthNextDay = date('m', $time + 86400);
    $today = date('j', $time);
    if ($thisMonth == $theMonthNextDay) {
      $cycleDayFilter = 'r.cycle_day = '.$today.' ';
    }
    else {
      for($i = $today; $i <= 31 ; $i++) {
        $days[] = $i;
      }
      $cycleDayFilter = 'r.cycle_day IN ('.CRM_Utils_Array::implode(',', $days).')';
    }

    $currentDate = date('Y-m-01 00:00:00', $time);

    // only trigger when current month doesn't have any contribution yet
    $sql = <<<EOT
SELECT
  r.id recur_id,
  r.last_execute_date last_execute_date,
  c.payment_processor_id payment_processor_id,
  c.is_test is_test,
  (SELECT MAX(created_date) FROM civicrm_contribution WHERE contribution_recur_id = r.id GROUP BY r.id) AS last_created_date,
  '$currentDate' as current_month_start
FROM
  civicrm_contribution_recur r
INNER JOIN
  civicrm_contribution c
ON
  r.id = c.contribution_recur_id
INNER JOIN
  civicrm_payment_processor p
ON
  c.payment_processor_id = p.id
WHERE
  $cycleDayFilter AND
  (SELECT MAX(created_date) FROM civicrm_contribution WHERE contribution_recur_id = r.id GROUP BY r.id) < '$currentDate'
AND r.contribution_status_id = 5
AND r.frequency_unit = 'month'
AND p.payment_processor_type = 'SPGateway'
AND COALESCE(p.url_api, '') != ''
GROUP BY r.id
ORDER BY r.id
LIMIT 0, 100
EOT;
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // Check last execute date.
      $currentDayTime = strtotime(date('Y-m-d', $time));
      $lastExecuteDayTime = strtotime(date('Y-m-d', strtotime($dao->last_execute_date)));
      if (!empty($dao->last_execute_date) && $currentDayTime <= $lastExecuteDayTime) {
        CRM_Core_Error::debug_log_message(ts("Last execute date of recur is over the date."));
        continue;
      }

      $command = 'drush neticrm-process-recurring --payment-processor=spgateway --time='.$time.' --contribution-recur-id='.$dao->recur_id.'&';
      popen($command, 'w');
      // wait for 1 second.
      usleep(1000000);
    }

    // Delete the sequence data of this process.
    $checkSeq = new CRM_Core_DAO_Sequence();
    unset($seq->timestamp);
    $seqArray = (array) $seq;
    $checkSeq->copyValues($seqArray);
    if ($checkSeq->find(TRUE)) {
      $checkSeq->delete();
    }
  }

  /**
   * doCheckRecur
   *
   * @param  int $recurId
   * @param  int $time
   * @return string
   */
  public static function doCheckRecur($recurId, $time = NULL) {
    CRM_Core_Error::debug_log_message("SPGateway synchronize execute: ".$recurId);
    if (empty($time)) {
      $time = time();
    }
    // Update last_execute_date
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'last_execute_date', date('Y-m-d H:i:s'));
    // Get same cycle_day recur.
    $sql = "SELECT c.id contribution_id, r.id recur_id, r.contribution_status_id recur_status_id, r.end_date end_date, r.installments, r.frequency_unit, c.is_test FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.contribution_recur_id = %1 ORDER BY c.id ASC LIMIT 1";
    $params = array(
      1 => array($recurId, 'Positive'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    $resultNote = "Syncing recurring $recurId ";
    $changeStatus = FALSE;
    $goPayment = $donePayment = FALSE;
    $sqlContribution = "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1 AND is_test = %2";
    $paramsContribution = array(
      1 => array($dao->recur_id, 'Positive'),
      2 => array($dao->is_test, 'Integer'),
    );
    $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);

    if (!empty($dao->end_date)) {
      if ($time <= strtotime($dao->end_date)) {
        $goPayment = TRUE;
        $reason = 'by end_date not due ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the end_date was dued.";
      }
    }
    elseif (!empty($dao->installments)) {
      if ($successCount < $dao->installments) {
        $goPayment = TRUE;
        $reason = 'by installments not full ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the installments was full.";
      }
    }
    else {
      // Obviously, the condition is empty($dao->installments) && empty($dao->end_date)
      $goPayment = TRUE;
      $reason = 'by no end_date and installments set ...';
    }

    $spgateway = new CRM_Contribute_DAO_SPGATEWAY();
    $spgateway->contribution_recur_id = $recurId;
    $spgateway->orderBy("expiry_date DESC");
    $spgateway->find(TRUE);
    $expiry_date = $spgateway->expiry_date;
    if (!empty($spgateway) && empty($spgateway->token_value)) {
      $resultNote .= "Payment doesn't be executed because token_value is empty.";
    }
    elseif ($goPayment) {
      // Check if Credit card over date.
      if ($time <= strtotime($expiry_date)) {
        $resultNote .= $reason;
        $resultNote .= ts("Finish synchronizing recurring.");
        self::payByToken($dao->recur_id);
        $donePayment = TRUE;
        // Count again for new contribution.
        $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);
      }
      else {
        $resultNote .= $reason;
        $resultNote .= ', but card expiry date due.';
      }
    }

    // check recurring status change and reason
    // no else for make sure every rule checked
    // and get latest spgateway check
    $spgateway->free();
    $spgateway = new CRM_Contribute_DAO_SPGateway();
    $spgateway->contribution_recur_id = $recurId;
    $spgateway->orderBy("expiry_date DESC");
    $spgateway->find(TRUE);
    $new_expiry_date = $spgateway->expiry_date;
    if ($donePayment && $dao->frequency_unit == 'month' && !empty($dao->end_date) && date('Ym', $time) == date('Ym', strtotime($dao->end_date))) {
      $statusNote = ts("This is lastest contribution of this recurring (end date is %1).", array(1 => date('Y-m-d', strtotime($dao->end_date))));
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif ($donePayment && $dao->frequency_unit == 'month' && !empty($new_expiry_date) && date('Ym', $time) == date('Ym', strtotime($new_expiry_date))) {
      $statusNote = ts("This is lastest contribution of this recurring (expiry date is %1).", array(1 => date('Y/m',strtotime($new_expiry_date))));
      $resultNote .= "\n" . $statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->end_date) && $time > strtotime($dao->end_date)) {
      $statusNote = ts("End date is due.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($dao->installments) && $successCount >= $dao->installments) {
      $statusNote = ts("Installments is full.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }
    elseif (!empty($new_expiry_date) && $time > strtotime($new_expiry_date)) {
      $statusNote = ts("Card expiry date is due.");
      $resultNote .= "\n".$statusNote;
      $changeStatus = TRUE;
    }

    if ( $changeStatus ) {
      $statusNoteTitle = ts("Change status to %1", array(1 => CRM_Contribute_PseudoConstant::contributionStatus(1)));
      $statusNote .= ' '.ts("Auto renews status");
      $resultNote .= "\n".$statusNoteTitle;
      $recurParams = array();
      $recurParams['id'] = $dao->recur_id;
      $recurParams['contribution_status_id'] = 1;
      $recurParams['message'] = $resultNote;
      CRM_Contribute_BAO_ContributionRecur::add($recurParams, CRM_Core_DAO::$_nullObject);
      CRM_Contribute_BAO_ContributionRecur::addNote($dao->recur_id, $statusNoteTitle, $statusNote);
    }

    CRM_Core_Error::debug_log_message($resultNote);
    CRM_Core_Error::debug_log_message("SPGateway synchronize finished: ".$recurId);
    return $resultNote;
  }

  /**
   * payByToken
   *
   * @param  int $recurringId
   * @param  int $referContributionId
   * @param  bool $sendMail
   * @return array
   */
  public static function payByToken($recurringId = NULL, $referContributionId = NULL, $sendMail = TRUE) {
    $response = array();
    if(empty($recurringId)){
      $recurringId = CRM_Utils_Request::retrieve('crid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, $recurringId, 'REQUEST');
    }
    if (empty($recurringId)) {
      $msg = 'Require recurringId from url crid or function';
      CRM_Core_Error::debug_log_message('spgateway_agreement: '.$msg);
      $response['status'] = 0;
      $response['msg'] = $msg;
      return $response;
    }

    $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
    $contributionRecur->id = $recurringId;
    $contributionRecur->find(TRUE);

    $spgateway = new CRM_Contribute_DAO_SPGATEWAY();
    $spgateway->contribution_recur_id = $recurringId;
    $spgateway->orderBy("cid ASC");
    $spgateway->find(TRUE);

    if (empty($spgateway->token_value)) {
      CRM_Core_Error::debug_log_message('spgateway_agreement: ');
      $msg = 'No token_value found for rid:'.$recurringId;
      CRM_Core_Error::debug_log_message('spgateway_agreement: '.$msg);
      $response['status'] = 0;
      $response['msg'] = $msg;
      return $response;
    }

    // $contribution -> first contribution
    // $c -> current editable contribution
    // Find the contribution
    $config = CRM_Core_Config::singleton();
    if (!empty($config->recurringCopySetting) && $config->recurringCopySetting == 'latest') {
      $order = 'DESC';
    }
    else {
      $order = 'ASC';
    }
    $sql = "SELECT id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY created_date $order";
    $params = array(1 => array($recurringId, 'Positive'));
    $findContributionId = CRM_Core_DAO::singleValueQuery($sql, $params);

    // Find FirstContribution
    $findContribution = new CRM_Contribute_DAO_Contribution();
    $findContribution->id = $findContributionId;
    $findContribution->find(TRUE);

    $ppid = $findContribution->payment_processor_id;
    $mode = $findContribution->is_test ? 'test' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    $response = array('status' => 0, 'msg' => 'Unknown error');
    if ($paymentProcessor && !empty($paymentProcessor['url_api'])) {
      $trxnId = self::generateTrxnId($findContribution->is_test, 0);

      if (empty($referContributionId)) {
        $c = CRM_Core_Payment_BaseIPN::copyContribution($findContribution, $recurringId, $trxnId);
        $c->total_amount = $contributionRecur->amount;
      }
      else {
        $c = new CRM_Contribute_DAO_Contribution();
        $c->id = $referContributionId;
        if (!$c->find(TRUE)) {
          $response = array('status' => 0, 'msg' => 'Error on finding referConitributionId');
          return $response;
        }
      }

      // Sync Recurring Custom fields.
      if ($c->contribution_recur_id == $recurringId) {
        CRM_Contribute_BAO_ContributionRecur::syncContribute($recurringId, $c->id);
      }

      $contribution = $ids = array();
      $params = array('id' => $c->id);
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      if (empty($details)) {
        $details = (string) $c->total_amount;
      }

      // prepare firing payment
      $prepareParams = array(
        'TimeStamp' => time(),
        'Version' => self::AGREEMENT_VERSION,
        'MerchantOrderNo' => $c->trxn_id,
        'Amt' => $c->total_amount,
        'ProdDesc' => $details,
        'PayerEmail' => $email,
        'TokenValue' => $spgateway->token_value,
        'TokenTerm' => $recurringId,
        'TokenSwitch' => 'on',
      );
      // Allow further manipulation of the arguments via custom hooks ..
      $paymentClass = self::singleton($mode, $paymentProcessor, CRM_Core_DAO::$_nullObject);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $prepareParams);
      $postData = http_build_query($prepareParams, '', '&');
      $postData_ = CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($postData, $paymentProcessor);
      $data = array(
        'MerchantID_' => $paymentProcessor['user_name'],
        'PostData_' => $postData_,
        'Pos_' => self::RESPONSE_TYPE,
      );
      CRM_Core_Payment_SPGATEWAYAPI::encode($data, $paymentProcessor);
      $urlApi = $c->is_test ? self::TEST_DOMAIN.self::URL_CREDITBG : self::REAL_DOMAIN.self::URL_CREDITBG;
      $tradeResult = CRM_Core_Payment_SPGATEWAYAPI::sendRequest($urlApi, $data);

      if (!empty($tradeResult) && is_object($tradeResult) && isset($tradeResult->Status)) {
        // save token_value on each payment anyway
        $tradeResult->Result->TokenValue = $spgateway->token_value;
        // call IPN
        $ids = CRM_Contribute_BAO_Contribution::buildIds($c->id);
        $query = CRM_Contribute_BAO_Contribution::makeNotifyUrl($ids, NULL, TRUE);
        parse_str($query, $ipnGet);

        // create recurring record
        $tradeInfoStr = json_encode($tradeResult);
        $tradeInfoEncrypted  = CRM_Core_Payment_SPGATEWAYAPI::recurEncrypt($tradeInfoStr, $paymentProcessor);
        $ipnPost = array(
          'Status' => 'SUCCESS', // call status, not transaction status
          'MerchantID' => $paymentProcessor['user_name'],
          'TradeInfo' => $tradeInfoEncrypted,
          'Version' => self::AGREEMENT_VERSION,
        );
        $ipnResult = self::doIPN(array('spgateway', 'ipn', 'Credit'), $ipnPost, $ipnGet, FALSE);
        if (!empty($ipnResult)) {
          $response = array('status' => $tradeResult->status, 'msg' => $tradeResult->msg);
        }
        else {
          if ($tradeResult->Status === 'SUCCESS') {
            $response = array('status' => 0, 'msg' => 'Trade complete but IPN result error');
            CRM_Core_Error::debug_log_message('spgateway_agreement: '.$response['msg']."on contribution $c->id and $recurringId");
          }
          else {
            $response = array('status' => 0, 'msg' => 'Trade not complete and IPN result error');
          }
        }
      }
      else {
        $response = array('status' => 0, 'msg' => 'Error when trying to call spgateway API');
      }
    }
    return $response;
  }

  public static function checkProceedRecur($recurId) {
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    if ($recur->find(TRUE) && !empty($recur->processor_id)) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($recur->processor_id, $recur->is_test ? 'test': 'live');
      if (!empty($paymentProcessor['url_api'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Trigger when click transaction button.
   */
  public static function doRecurTransact($recurId = NULL, $sendMail = FALSE) {
    if (empty($recurId) || !CRM_Utils_Type::validate($recurId, 'Positive')) {
      CRM_Core_Error::statusBounce(ts('Missing required field: %1', array(1 => 'Recurring Id')));
      return;
    }

    $resultNote = '';
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    if ($recur->find(TRUE) && !empty($recur->processor_id)) {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($recur->processor_id, $recur->is_test ? 'test': 'live');
      if (!empty($paymentProcessor['url_api'])) {
        // Get current user
        $session = CRM_Core_Session::singleton();
        $contactId = $session->get('userID');

        $resultNote = self::payByToken($recurId, NULL, $sendMail);

        $sql = "SELECT id FROM civicrm_contribution_spgateway WHERE contribution_recur_id = %1 ORDER BY id DESC LIMIT 1";
        $params = array(1 => array($recurId, 'Positive'));
        $spgatewayId = CRM_Core_DAO::singleValueQuery($sql, $params);
        if ($spgatewayId) {
          $spgatewayData = new CRM_Contribute_DAO_SPGATEWAY();
          $spgatewayData->id = $spgatewayId;
          $spgatewayData->find(TRUE);
          $spgatewayData->created_id = $contactId;
          $spgatewayData->save();
        }
      }
    }

    return $resultNote;
  }

  /**
   * Get the message as pressing "Sync Now" button.
   * Called by MakingTransaction form.
   *
   * @param int $contributionId The contribution id of the page.
   * @param int $recurId The recurring id of the page.
   * @return string The message
   */
  public static function getSyncNowMessage($contributionId, $recurId = NULL) {
    // check payment processor to see if we can show this button
    if (!empty($recurId)) {
      $recur = new CRM_Contribute_DAO_ContributionRecur();
      $recur->id = $recurId;
      if ($recur->find(TRUE) && !empty($recur->processor_id)) {
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($recur->processor_id, $recur->is_test);
        if (!empty($paymentProcessor['url_api'])) {
          return '';
        }
      }
    }
    elseif (!empty($contributionId)) {
      $contrib = new CRM_Contribute_DAO_Contribution();
      $contrib->id = $contributionId;
      if ($contrib->find(TRUE) && !empty($contrib->payment_processor_id)) {
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($contrib->payment_processor_id, $contrib->is_test);
        if (!empty($paymentProcessor['url_api'])) {
          return '';
        }
      }
    }
    return ts("Are you sure you want to sync the recurring status and check the contributions?");
  }
}
