<?php

class CRM_Core_Payment_Backer extends CRM_Core_Payment {

  protected $_mode = NULL;
  protected $_signature = NULL;
  protected $_delivery = NULL;

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

  static function getAdminFields($ppDAO){
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    foreach($pages as $id => $page) {
      $pages[$id] .= " ($id)";
    }
    return array(
      array(
        'name' => 'user_name',
        'label' => $ppDAO->user_name_label,
        'type' => 'select',
        'options' => array('' => ts('-- select --')) + $pages,
      ),
      array('name' => 'password',
        'label' => $ppDAO->password_label,
      ),
    );
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
      $this->_signature = $signature;
      if (!empty($_SERVER['HTTP_X_BACKME_DELIVERY'])) {
        $this->_delivery = $_SERVER['HTTP_X_BACKME_DELIVERY'];
      }
      return TRUE;
    }
    return FALSE;
  }

  function processContribution($jsonString) {
    $params = self::formatParams($jsonString);
    $locationType = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    $config = CRM_Core_Config::singleton();
    if (empty($params)) {
      return;
    }
    if (empty($params['contribution']['trxn_id'])) {
      return;
    }
    if (empty($params['contribution']['contribution_status_id'])) {
      return;
    }

    $contributionPageId = $this->_paymentProcessor['user_name'];
    if (empty($contributionPageId)) {
      return;
    }

    // first, check if contribution exists
    $currentContributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution']['trxn_id'], 'id', 'trxn_id');
    if ($currentContributionId) {
      // update status and payment only
      $ids = CRM_Contribute_BAO_Contribution::buildIds($currentContributionId, 'ipn');
      $this->processIPN($ids, $params['contribution']);
      return $currentContributionId;
    }

    // not exists contribution, check contact first
    $contactId = 0;
    // get contact by external identifier
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
      sort($foundDupes);
      $contactId = reset($foundDupes);
    }
    else {
      // get contact by email and sort name
      $dedupeParams = array(
        'email' => $params['contact']['email'],
        'last_name' => $params['contact']['last_name'],
        'first_name' => $params['contact']['first_name'],
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
        ),
        20
      );
      if (count($foundDupes)) {
        sort($foundDupes);
        $contactId = reset($foundDupes);
      }
      else {
        // get contact by email, last name, and phone
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
            array('table' => 'civicrm_contact', 'field' => 'last_name', 'weight' => 2),
            array('table' => 'civicrm_contact', 'field' => 'first_name', 'weight' => 8),
            array('table' => 'civicrm_email', 'field' => 'email', 'weight' => 10),
            array('table' => 'civicrm_phone', 'field' => 'phone', 'weight' => 7),
          ),
          20
        );
        if (count($foundDupes)) {
          sort($foundDupes);
          $contactId = reset($foundDupes);
        }
      }
    }
    if (empty($contactId)) {
      // create contact
      $contact = $params['contact'];
      $contact['log_data'] = ts('Updated contact').'-'.ts('Backer Auto Import');
      $contact['version'] = 3;
      $result = civicrm_api('contact', 'create', $contact);
      $contactId = $result['id'];
    }
    else {
      // add email, phone, address into contact
      $contact = $params['contact'];
      $contact['id'] = $contactId;
      $blocks = array('email', 'phone', 'address'); 
      foreach($blocks as $blockName) {
        $blockValue = reset($params[$blockName]);
        $blockValue['contact_id'] = $contactId;
        if ($blockName == 'address') {
          CRM_Core_BAO_Address::valueExists($blockValue);
        }
        else {
          CRM_Core_BAO_Block::blockValueExists($blockName, $blockValue);
        }

        // do not touch contact exists value, only add new value
        if (empty($blockValue['id'])) {
          $contact[$blockName] = $params[$blockName];
        }
      }

      // move exists billing address to other
      $otherLocationTypeId = array_search('Other', $locationType);
      $billingLocationTypeId = array_search('Billing', $locationType);
      if (count($contact['address']) > 0 && $otherLocationTypeId && $billingLocationTypeId) {
        $existsBillingAddress = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_address WHERE location_type_id = '$billingLocationTypeId' AND contact_id = %1", array(
          1 => array($contact['id'], 'Integer')
        ));
        if ($existsBillingAddress) {
          CRM_Core_DAO::executeQuery("UPDATE civicrm_address SET location_type_id = '$otherLocationTypeId' WHERE id = %1", array(
            1 => array($existsBillingAddress, 'Integer')
          ));
        }
      }

      // do not change crm contact name
      unset($contact['first_name']);
      unset($contact['last_name']);

      // log exists
      $contact['log_data'] = ts('Updated contact').'-'.ts('Backer Auto Import');
      $contact['version'] = 3;
      civicrm_api('contact', 'create', $contact);
    }

    // process contribution
    if ($contactId) {
      // create additional contact if needed
      $backerRelationTypeId = $config->backerFounderRelationship;
      if (!empty($params['additional']['first_name']) && !empty($params['additional']['address']) && !empty($backerRelationTypeId)) {
        $dedupeParams = array(
          'email' => $params['additional']['email'][0],
          'last_name' => $params['additional']['last_name'],
          'first_name' => $params['additional']['first_name'],
        );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
        $foundDupes = CRM_Dedupe_Finder::dupesByRules(
          $dedupeParams,
          'Individual',
          'Strict',
          array(),
          array(
            array('table' => 'civicrm_contact', 'field' => 'last_name', 'weight' => 8),
            array('table' => 'civicrm_contact', 'field' => 'first_name', 'weight' => 3),
            array('table' => 'civicrm_email', 'field' => 'email', 'weight' => 10),
          ),
          20
        );
        if (count($foundDupes)) {
          sort($foundDupes);
          $additionalContactId = reset($foundDupes);
        }
        else {
          $dedupeParams = array(
            'last_name' => $params['additional']['last_name'],
            'first_name' => $params['additional']['first_name'],
          );
          $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
          $foundDupes = CRM_Dedupe_Finder::dupesByRules(
            $dedupeParams,
            'Individual',
            'Strict',
            array(),
            array(
              array('table' => 'civicrm_contact', 'field' => 'sort_name', 'weight' => 10),
            ),
            10
          );
        }
        if ($additionalContactId) {
          $params['additional']['id'] = $additionalContactId;
          // only process address
          $blockValue = $params['additional']['address'][0];
          $blockValue['contact_id'] = $additionalContactId;
          CRM_Core_BAO_Address::valueExists($blockValue);
          if (empty($blockValue['id'])) {
            $otherLocationTypeId = array_search('Other', $locationType);
            $billingLocationTypeId = array_search('Billing', $locationType);
            if ($otherLocationTypeId && $billingLocationTypeId) {
              $existsBillingAddr = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_address WHERE location_type_id = '$billingLocationTypeId' AND contact_id = %1", array(
                1 => array($additionalContactId, 'Integer')
              ));
              if ($existsBillingAddr) {
                CRM_Core_DAO::executeQuery("UPDATE civicrm_address SET location_type_id = '$otherLocationTypeId' WHERE id = %1", array(
                  1 => array($existsBillingAddr, 'Integer')
                ));
              }
            }
            $addContact = array(
              'version' => 3,
              'id' => $additionalContactId,
              'address' => $params['additional']['address'],
              'log_data' => ts('Updated contact').'-'.ts('Backer Auto Import'),
            ); 
            // log exists
            civicrm_api('contact', 'create', $addContact);
          }
        }
        else {
          // create new contact
          $addContact = $params['additional'];
          $addContact['version'] = 3;
          $result = civicrm_api('contact', 'create', $addContact);
          $additionalContactId = $result['id'];
        }

        if ($additionalContactId) {
          $params = array(
            'version' => 3,
            'contact_id_a' => $contactId,
            'contact_id_b' => $additionalContactId,
            'relationship_type_id' => $backerRelationTypeId,
            'is_active' => 1,
          );
          civicrm_api('Relationship', 'create', $params);
        }
      }
      // create a pending contribution      
      $params['contribution']['contact_id'] = $contactId;
      $contrib = $params['contribution'];
      $page = array();
      CRM_Contribute_BAO_ContributionPage::setValues($contributionPageId, $page);
      if ($page['id']) {
        $contrib['contribution_status_id'] = 2; // pending
        $contrib['contribution_page_id'] = $page['id'];
        $contrib['contribution_type_id'] = $page['contribution_type_id'];
        $contrib['payment_processor_id'] = $this->_paymentProcessor['id'];
        $contrib['is_test'] = $this->_paymentProcessor['is_test'];
        $contrib['invoice_id'] = !empty($this->_delivery) ? $this->_delivery : md5(uniqid(rand(), TRUE));
        $contrib['version'] = 3;
        $result = civicrm_api('contribution', 'create', $contrib);
        if ($result['id']) {
          $currentContributionId = $result['id'];
          $params['contribution']['id'] = $result['id'];
          $contrib['id'] = $result['id'];
          $ids = CRM_Contribute_BAO_Contribution::buildIds($contrib['id'], 'ipn');
          $this->processIPN($ids, $params['contribution']);
          return $currentContributionId;
        }
      }
    }
  }

  function processIPN($ids, $contrib) {
    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();
    $input = $objects = array();
    $validateResult = $ipn->validateData($input, $ids, $objects, FALSE);
    if ($validateResult){
      $transaction = new CRM_Core_Transaction();
      $exists = $objects['contribution'];
      $input['component'] = 'contribute';
      $input['amount'] = $contrib['total_amount'];

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
    $params['email'][0] = array(
      'email' => $json['user']['email'],
      'location_type_id' => array_search('Billing', $locationType),
      'is_primary' => 1,
      'append' => TRUE,
    );
    $phone = self::validateMobilePhone($json['user']['cellphone']);
    $params['phone'][0] = array(
      'phone' => $phone ? $phone : $json['user']['cellphone'],
      'phone_type_id' => $phone ? 2 : 5, // mobile
      'location_type_id' => array_search('Billing', $locationType),
      'is_primary' => 1,
      'append' => TRUE,
    );

    // address
    $stateAbbr = CRM_Core_PseudoConstant::stateProvinceAbbreviation();
    // backer special abbr convert to CRM
    if ($json['recipient']['recipient_subdivision']) {
      if ($json['recipient']['recipient_subdivision'] == 'KIN') $json['recipient']['recipient_subdivision'] = 'KMN';
      elseif ($json['recipient']['recipient_subdivision'] == 'LIE') $json['recipient']['recipient_subdivision'] = 'LCI';
      elseif ($json['recipient']['recipient_subdivision'] == 'NWT') $json['recipient']['recipient_subdivision'] = 'TPO';

      $countryId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_country WHERE name = 'Taiwan'");
      $stateProvinceId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_state_province WHERE abbreviation = %1 AND country_id = %2", array(
        1 => array($json['recipient']['recipient_subdivision'], 'String'),
        2 => array($countryId, 'Integer'),
      ));
    }
    $address = array(
      'country' => ($json['recipient']['recipient_country'] == 'TW') ? 'Taiwan' : '',
      'postal_code' => $json['recipient']['recipient_postal_code'] ? $json['recipient']['recipient_postal_code'] : '',
      'state_province_id' => $stateProvinceId ? $stateProvinceId : '',
      'city' => $json['recipient']['recipient_cityarea'] ? $json['recipient']['recipient_cityarea'] : '',
      'street_address' => $json['recipient']['recipient_address'] ? $json['recipient']['recipient_address'] : '',
      'location_type_id' => array_search('Billing', $locationType),
    );
    if ($json['recipient']['recipient_name'] == $json['user']['name']) {
      $params['address'][0] = $address;
    }
    else {
      $params['additional'] = array();
      $addName = self::explodeName($json['recipient']['recipient_name']);
      if ($addName === FALSE) {
        $addName= array(
          '',    // sure name
          $json['recipient']['recipient_name'], // given name
        );
      }
      $params['additional'] = array(
        'contact_type' => 'Individual',
        'last_name' => $addName[0],
        'first_name' => $addName[1],
      );
      if ($json['recipient']['recipient_contact_email'] && CRM_Utils_Rule::email($json['recipient']['recipient_contact_email'])) {
        $params['additional']['email'][0] = array(
          'email' => $json['recipient']['recipient_contact_email'],
          'location_type_id' => array_search('Billing', $locationType),
          'is_primary' => 1,
          'append' => TRUE,
        );
      }
      if (!empty($json['recipient']['recipient_cellphone'])) {
        $params['additional']['phone'][0] = array(
          'phone' => $json['recipient']['recipient_cellphone'],
          'phone_type_id' => 5, // other 
          'location_type_id' => array_search('Billing', $locationType),
          'is_primary' => 1,
          'append' => TRUE,
        );
        $addPhone = self::validateMobilePhone($json['recipient']['recipient_cellphone']);
        if ($addPhone) {
          $params['additional']['phone'][0]['phone_type_id'] = 2;
          $params['additional']['phone'][0]['phone'] = $addPhone;
        }
      }
      $params['additional']['address'][0] = $address;
    }

    // contribution
    $instruments =  CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $instrumentMap = array(
      'credit' => 'Credit Card',
      'atm' => 'ATM',
      'cvs' => 'Convenient Store (Code)',
    );
    $statusMap = array(
      'refund_applying' => 0,
      'partial_refund' => 0,
      'success' => 1,
      'wait_code' => 2,
      'wait' => 2,
      'refund' => 3,
      'cancel' => 3,
      'failed' => 4,
      'failed_code' => 4,
      // recurring contribution status
      'suspend' => 7,
      'recurring' => 5,
      'failed_cancel' => 3,
    );

    $params['contribution'] = array(
      'trxn_id' => $json['transaction']['trade_no'],
      'currency' => 'TWD',
      'total_amount' => (int) $json['transaction']['money'],
      'payment_instrument_id' => $instrumentMap[$json['payment']['type']] ? array_search($instrumentMap[$json['payment']['type']], $instruments) : '',
      'contribution_status_id' => $statusMap[$json['transaction']['render_status']],
      'updated_at' => date('YmdHis', strtotime($json['transaction']['updated_at'])),
    );
    switch($statusMap[$json['transaction']['render_status']]) {
      case 1: // success
        $params['contribution']['receive_date'] = date('YmdHis', strtotime($json['payment']['paid_at']));
        break;
      case 2: // pending
        $params['contribution']['receive_date'] = NULL;
        if ($json['payment'] == 'atm' || $json['payment'] == 'cvs') {
          $params['is_pay_later'] = 1;
        }
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

    $amountLevel = $customFields = array();
    $itemMoney = (int) $json['transaction']['items']['money'];
    $amountLevel[] = "{$json['transaction']['items']['reward_name']}({$json['transaction']['items']['reward_id']})x1:{$itemMoney}";
    if (!empty(trim($json['transaction']['items']['note']))) {
      $amountLevel[] = ts('Note').'=>'.$json['transaction']['items']['note'];
    }

    // process custom options
    // complex logic to values of custom
    if (!empty($json['transaction']['items']['custom_fields'])) {
      $items = $matches = array();
      foreach($json['transaction']['items']['custom_fields'] as $key => $item) {
        $items[$item['name']] = $item['value'];
      }
      CRM_Core_BAO_CustomGroup::matchFieldValues('Contribution', $items, $matches);
      if (!empty($matches[1])) {
        $params['contribution'] += $matches[1];
        $leftItems = array_diff_key($items, $matches[0]);
        foreach($leftItems as $label => $value) {
          $amountLevel[] = $label.'=>'.$value;
        }
      }
      if (!empty($amountLevel)) {
        $params['contribution']['amount_level'] = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR.implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $amountLevel).CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
      }
    }
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
  public static function validateMobilePhone($str) {
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
    $phone = preg_replace('/[^0-9]/', '', $number);

    // taiwan mobile phone
    if (!preg_match('/^(09[0-9]{2})([0-9]{6})$/', $phone, $matches)) {
      return FALSE;
    }
    else {
      $phone = $matches[1].'-'.$matches[2];
    }
    return $phone;
  }

}
