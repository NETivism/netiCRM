<?php

class CRM_Core_Payment_Backer extends CRM_Core_Payment {

  protected $_mode = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  function __construct($mode, &$paymentProcessor) {
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
  public static function &singleton($mode = 'live', &$paymentProcessor, $paymentForm = NULL) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Backer($mode, $paymentProcessor);
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
    $error = array();

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }


    if (!empty($error)) {
      return implode('<br>', $error);
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

  function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function cancelRecuringMessage($recurID) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function checkSignature($string, $signature = NULL) {
    if (empty($signature)) {
      $headers = CRM_Utils_System::getAllHeaders();
      $signature = $_SERVER['HTTP_X_BACKME_SIGNATURE'];
    }
    if (empty($signature)) {
      return FALSE;
    }
    $secret = $this->_paymentProcessor['password'];
    if (empty($secret)) {
      return FALSE;
    }
    $hash = hash_hmac('sha1', $string, $secret);
    if ($hash === $signature) {
      return TRUE;
    }
    return FALSE;
  }

  function processContribution($jsonString) {
    $params = self::formatParams($jsonString);
    if (empty($params)) {
      return;
    }
    if (empty($params['contribution']['trxn_id'])) {
      return;
    }

    // first, check if contribution exists
    $currentContributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution']['trxn_id'], 'id', 'trxn_id');
    if ($currentContributionId) {
      // update status and payment only
      $ids = CRM_Contribute_BAO_Contribution::buildIds($ids);
      $this->processIPN($ids, $params['contribution']);
      return;
    }

    // not exists contribution, check contact first
    $contactId = 0;
    $dedupeParams = array(
      'external_identifier' => $params['contact']['external_identifier'],
    );
    $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
    $foundDupes = CRM_Dedupe_Finder::dupesByRules(
      $dedupeParams,
      'Individual',
      'Strict',
      array(),
      array(
        array('table' => 'civicrm_contact', 'field' => 'external_identifier', 'weight' => 10),
      )
    );
    if (count($foundDupes)) {
      // first match contact
      sort($foundDupes);
      $contactId = reset($foundDupes);
    }
    else {
      $dedupeParams = array(
        'email' => $params['contact']['email'],
        'last_name' => $params['contact']['last_name'],
        'first_name' => $params['contact']['first_name'],
        'phone' => $params['phone']['phone'],
      );
      $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
      $foundDupes = CRM_Dedupe_Finder::dupesByRules(
        $dedupeParams,
        'Individual',
        'Strict',
        array(),
        array(
          array('table' => 'civicrm_contact', 'field' => 'sort_name', 'weight' => 10),
          array('table' => 'civicrm_email', 'field' => 'email', 'weight' => 10),
          array('table' => 'civicrm_phone', 'field' => 'phone', 'weight' => 10),
        ),
        20
      );
      if (count($foundDupes)) {
        // first match contact
        sort($foundDupes);
        $contactId = reset($foundDupes);
      }
    }
    if (empty($contactId)) {
      // create contact
    }
    else {
      // add email, phone, address into contact
    }
  }

  function processIPN($ids, $contrib) {
    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();
    $input = $objects = array();
    $input['component'] = 'contribute';
    $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
    if ($validate_result){
      $transaction = new CRM_Core_Transaction();
      $exists = $objects['contribution'];

      // success: 2->1
      if ($contrib['contribution_status_id'] == 1 && $exists->contribution_status_id == 2){
        $objects['contribution']->receive_date = $contrib['receive_date'];
        $ipn->completeTransaction($input, $ids, $objects, $transaction);
      }
      else {
        // multiple scenario will happen

        // failed: 1->3,4 or 2->3,4
        if (in_array($contrib['contribution_status_id'], array(3,4)) && in_array($exists->contribution_status_id, array(1,2))) {
          $objects['contribution']->cancel_date = $contrib['cancel_date'];
          $cancelReason = $contrib['updated_at'].' '.ts("Update").":\n".$contrib['cancel_reason'];
          $ipn->failed($objects, $transaction, $cancelReason);
        }
        // pending: nothing
        elseif ($contrib['contribution_status_id'] == 2) {
          // do nothing
          // default contribution status is pending
        }
      }
    }
    else{
      // error log here
    }
  }

  /**
   * Format params from backer
   *
   * @param string $string
   * @return array
   */
  public static function formatParams($string) {
    $params = array();
    $json = json_decode($string, TRUE);
    if (!$json) {
      return $params;
    }

    $name = self::explodeName($json['user']['name']);
    $locationType = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    if ($name === FALSE) {
      $name = array(
        '',    // sure name
        $name, // given name
      );
    }
    $params['contact'] = array(
      'contact_type' => 'Individual',
      'external_identifier' => 'backer-'.$json['user']['id'],
      'last_name' => $name[0],
      'first_name' => $name[1],
      'email' => $json['user']['email'],
    );
    $phone = self::validatePhone($json['user']['cellphone']);
    $params['phone'] = array(
      'phone' => $phone ? $phone : $json['user']['cellphone'],
      'phone_type_id' => $phone ? 2 : 5, // mobile
      'location_type_id' => array_search('Billing', $locationType),
    );

    // address
    if ($json['recipient']['recipient_name'] == $json['user']['name']) {
      $stateAbbr = CRM_Core_PseudoConstant::stateProvinceAbbreviation();

      $params['address']['country'] = $json['recipient']['recipient_country'] == 'TW' ? 'Taiwan' : '';
      $params['address']['postal_code'] = $json['recipient']['recipient_postal_code'] ? $json['recipient']['recipient_postal_code'] : '';
      $params['address']['state_province_id'] = $json['recipient']['recipient_subdivision'] ? array_search($json['recipient']['recipient_subdivision'], $stateAbbr) : '';
      $params['address']['city'] = $json['recipient']['recipient_cityarea'] ? $json['recipient']['recipient_cityarea'] : '';
      $params['address']['street_address'] = $json['recipient']['recipient_address'] ? $json['recipient']['recipient_address'] : '';
      $params['address']['location_type_id'] = array_search('Billing', $locationType);
    }

    // contribution
    $instruments =  CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $instrumentMap = array(
      'credit' => 'Credit Card',
      'atm' => 'ATM',
      'cvs' => 'Convenient Store (Code)',
    );
    $statusMap = array(
      'success' => 1,
      'wait' => 2,
      'failed' => 4,
      'refund' => 3,
      'suspend' => 7,  // recurring
      'recurring' => 5,// recurring
      'cancel' => 3,   // both
      'wait_code' => 2,
      'refund_applying' => 2,
      'failed_code' => 4,
      'failed_cancel' => 3,
      'all_wait' => 2,
      'all_failed' => 4,
    );

    $params['contribution'] = array(
      'trxn_id' => $json['transaction']['trade_no'],
      'currency' => 'TWD',
      'amount' => (int) $json['transaction']['money'],
      'paymnet_instrument_id' => $instrumentMap[$json['payment']['type']] ? array_search($instrumentMap[$json['payment']['type']], $instruments) : '',
      'contribution_status_id' => $statusMap[$json['transaction']['render_status']],
      'updated_at' => date('YmdHis', strtotime($json['transaction']['updated_at'])),
    );
    switch($statusMap[$json['transaction']['render_status']]) {
      case 1: // success
        $params['contribution']['receive_date'] = date('YmdHis', strtotime($json['payment']['paid_at']));
        break;
      case 2: // pending
        $params['contribution']['receive_date'] = NULL;
        break;
      case 3: // cancel
        $params['contribution']['cancel_date'] = date('YmdHis', strtotime($json['payment']['paid_at']));
        $params['contribution']['cancel_reason'] = $json['transaction']['render_status'].':'.$json['payment']['log'];
        if ($json['payment']['refund_at']) {
          $params['contribution']['cancel_reason'] .= ts('The transaction has already been refunded.').'('.$json['payment'].')'['refund_at'];
        }
        break;
      case 4: // failed
        $params['contribution']['cancel_date'] = date('YmdHis', strtotime($json['payment']['paid_at']));
        $params['contribution']['cancel_reason'] = $json['transaction']['render_status'].': '.$json['payment']['log'];
        break;
      case 5: // processing
        break;
      case 7: // suspend
        break;
    }

    $amountLevel = array();
    $itemMoney = (int) $json['transaction']['items']['money'];
    $amountLevel[] = "{$json['transaction']['items']['reward_name']}({$json['transaction']['items']['reward_id']})x1:{$itemMoney}";
    if (!empty(trim($json['transaction']['items']['note']))) {
      $amountLevel[] = ts('Note').'=>'.$json['transaction']['items']['note'];
    }
    foreach($json['transaction']['items']['custom_fields'] as $key => $item) {
      if ($item['value'] !== '') {
        $amountLevel[] = $item['name'].'=>'.$item['value'];
      }
    }
    $params['contribution']['amount_level'] = implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $amountLevel);
    return $params;
  }

  /**
   * Explode name string to sure name - given name
   *
   * @param string $str
   * @return array|false
   */
  public static function explodeName($str) {
    $str = trim($str);
    $str = str_replace(array("\r","\n"),'',$str);
    if (empty($str)) {
      return FALSE;
    }
    if (preg_match("/[a-zA-Z]/", $str)) { // check for english name
      if (preg_match("/[,]/", $str)) { // has comma will be reverse
        $name = explode(',', $str);
      }
      else { // has space
        $name = array_reverse(preg_split("/[\s,]+/", $str));
      }
    }
    else { // check for chinese name
      $str = str_replace(' ', '', $str);
      $str = str_replace('　', '', $str);
      $len = mb_strlen($str, 'UTF-8');

      if ($len == 2) {
        $name = array(
          mb_substr($str, 0, 1, 'UTF-8'),
          mb_substr($str, 1, 1, 'UTF-8'),
        );
      }
      else if ($len == 3 || $len == 4) {
        $given_name = mb_substr($str, -2, 2, 'UTF-8');
        $sure_name = str_replace($given_name, '', $str);
        $name[] = $sure_name;
        $name[] = $given_name;
      }
      else {
        return FALSE;
      }
    }

    return $name;
  }

  /**
   * Validating mobile number.
   */
  public static function validatePhone($str) {
    $str = trim($str);
    $str = str_replace(array("\r","\n"),'',$str);
    if (empty($str)) {
      return FALSE;
    }
    $number = $str;
    if (preg_match("/^\+/", $number)) {
      if (preg_match("/^\+886-?/", $number)) {
        $number = str_replace('+886', '0', $number);
        $phone = $number;
      }
      else {
        $phone = $number;
      }
    }
    else {
      return FALSE;
    }
    if (!preg_match('/^[0+][0-9-]*(#.*)?$/u', $phone)) {
      return FALSE;
    }
    return $phone;
  }

}
