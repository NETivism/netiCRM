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
  public static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL) {
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
    $error = [];

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }


    if (!empty($error)) {
      return CRM_Utils_Array::implode('<br>', $error);
    }
    else {
      return NULL;
    }
  }

  static function getAdminFields($ppDAO, $form){
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    foreach($pages as $id => $page) {
      $pages[$id] .= " ($id)";
    }
    return [
      [
        'name' => 'user_name',
        'label' => $ppDAO->user_name_label,
        'type' => 'select',
        'options' => ['' => ts('-- select --')] + $pages,
      ],
      ['name' => 'password',
        'label' => $ppDAO->password_label,
      ],
      [
        'name' => 'url_api',
        'label' => ts('API URL'),
      ],
      ['name' => 'url_button',
        'label' => ts('Link Label'),
      ],
      ['name' => 'url_site',
        'label' => ts('Link URL'),
      ],
    ];
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

  function processContribution($jsonString, &$contributionResult) {
    $params = self::formatParams($jsonString);
    $locationType = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    $config = CRM_Core_Config::singleton();
    if (empty($params)) {
      $contributionResult['status'] = "params is empty";
      // return;
    }
    if (empty($params['contribution']['trxn_id'])) {
      $contributionResult['status'] = "Contribution trxn_id is empty.";
      // return;
    }
    if (empty($params['contribution']['contribution_status_id'])) {
      $contributionResult['status'] = "Contribution contribution_status_id is empty.";
      // return;
    }

    $contributionPageId = $this->_paymentProcessor['user_name'];
    if (empty($contributionPageId)) {
      $contributionResult['status'] = "Contribution page id is empty.";
      // return;
    }

    // first, check if contribution exists
    if ($params['contribution']) {
      $currentContributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution']['trxn_id'], 'id', 'trxn_id');
      if ($currentContributionId) {
        // update status and payment only
        $ids = CRM_Contribute_BAO_Contribution::buildIds($currentContributionId, 'ipn');
        $this->processIPN($ids, $params['contribution']);
        $contributionResult['status'] = "Contribution exists, retrun currentContributionId.";
        $contributionResult['contributionId'] = $currentContributionId;
        return $currentContributionId;
      }
    }

    // not exists contribution, check contact first
    $contactId = 0;
    // get contact by external identifier
    $dedupeParams = [
      'external_identifier' => $params['contact']['external_identifier'],
    ];
    $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
    $foundDupes = CRM_Dedupe_Finder::dupesByRules(
      $dedupeParams,
      'Individual',
      'Strict',
      [],
      [
        ['table' => 'civicrm_contact', 'field' => 'external_identifier', 'weight' => 10],
      ]
    );
    if (count($foundDupes)) {
      sort($foundDupes);
      $contactId = reset($foundDupes);
    }
    else {
      // get contact by email and sort name
      $dedupeParams = [
        'email' => $params['contact']['email'],
        'last_name' => $params['contact']['last_name'],
        'first_name' => $params['contact']['first_name'],
      ];
      $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
      $foundDupes = CRM_Dedupe_Finder::dupesByRules(
        $dedupeParams,
        'Individual',
        'Strict',
        [],
        [
          ['table' => 'civicrm_contact', 'field' => 'sort_name', 'weight' => 10],
          ['table' => 'civicrm_email', 'field' => 'email', 'weight' => 10],
        ],
        20
      );
      if (count($foundDupes)) {
        sort($foundDupes);
        $contactId = reset($foundDupes);
      }
      else {
        // get contact by email, last name, and phone
        $dedupeParams = [
          'email' => $params['contact']['email'],
          'last_name' => $params['contact']['last_name'],
          'first_name' => $params['contact']['first_name'],
          'phone' => $params['phone']['phone'],
        ];
        $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
        $foundDupes = CRM_Dedupe_Finder::dupesByRules(
          $dedupeParams,
          'Individual',
          'Strict',
          [],
          [
            ['table' => 'civicrm_contact', 'field' => 'last_name', 'weight' => 2],
            ['table' => 'civicrm_contact', 'field' => 'first_name', 'weight' => 8],
            ['table' => 'civicrm_email', 'field' => 'email', 'weight' => 10],
            ['table' => 'civicrm_phone', 'field' => 'phone', 'weight' => 7],
          ],
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
      $blocks = ['email', 'phone', 'address']; 
      foreach($blocks as $blockName) {
        $contact[$blockName] = $params[$blockName];
      }
      $contact['log_data'] = ts('Updated contact').'-'.ts('Backer Auto Import');
      $contact['version'] = 3;
      $result = civicrm_api('contact', 'create', $contact);
      $contactId = $result['id'];
    }
    else {
      // add email, phone, address into contact
      $contact = $params['contact'];
      $contact['id'] = $contactId;
      $blocks = ['email', 'phone', 'address']; 
      foreach($blocks as $blockName) {
        if (isset($params[$blockName]) && is_array($params[$blockName])) {
          $blockValue = reset($params[$blockName]);
          $blockValue['contact_id'] = $contactId;
          if ($blockName == 'address') {
            CRM_Core_BAO_Address::valueExists($blockValue);
          }
          elseif ($blockName == 'email') {
            if (count($params['email']) == 1) {
              CRM_Core_BAO_Block::blockValueExists($blockName, $blockValue);
            }
            else {
              foreach($params[$blockName] as $emailKey => $emailValue) {
                CRM_Core_BAO_Block::blockValueExists($blockName, $emailValue);
              }
            }
          }
          else {
            CRM_Core_BAO_Block::blockValueExists($blockName, $blockValue);
          }

          // do not touch contact exists value, only add new value
          if (empty($blockValue['id'])) {
            $contact[$blockName] = $params[$blockName];
          }
        }
      }

      // move exists billing address to other
      $otherLocationTypeId = array_search('Other', $locationType);
      $billingLocationTypeId = array_search('Billing', $locationType);
      if (count((array)$contact['address']) > 0 && $otherLocationTypeId && $billingLocationTypeId) {
        $existsBillingAddress = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_address WHERE location_type_id = '$billingLocationTypeId' AND contact_id = %1", [
          1 => [$contact['id'], 'Integer']
        ]);
        if ($existsBillingAddress) {
          CRM_Core_DAO::executeQuery("UPDATE civicrm_address SET location_type_id = '$otherLocationTypeId' WHERE id = %1", [
            1 => [$existsBillingAddress, 'Integer']
          ]);
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

    if ($contactId) {
      $contributionResult['contactId']  = $contactId;
      // create additional contact if needed
      $backerRelationTypeId = $config->backerFounderRelationship;
      if (!empty($params['additional']['first_name']) && !empty($params['additional']['address']) && !empty($backerRelationTypeId)) {
        $dedupeParams = [
          'last_name' => $params['additional']['last_name'],
          'first_name' => $params['additional']['first_name'],
          'email' => $params['additional']['email'][0]['email'],
          'phone' => $params['additional']['phone'][0]['phone'],
        ];
        $dedupeParams = CRM_Dedupe_Finder::formatParams($dedupeParams, 'Individual');
        $foundDupes = CRM_Dedupe_Finder::dupesByRules(
          $dedupeParams,
          'Individual',
          'Strict',
          [],
          [
            ['table' => 'civicrm_contact', 'field' => 'last_name', 'weight' => 2],
            ['table' => 'civicrm_contact', 'field' => 'first_name', 'weight' => 9],
            ['table' => 'civicrm_email', 'field' => 'email', 'weight' => 9],
            ['table' => 'civicrm_phone', 'field' => 'phone', 'weight' => 9],
          ],
          20
        );
        if (count($foundDupes)) {
          sort($foundDupes);
          $additionalContactId = reset($foundDupes);
        }
        if ($additionalContactId) {
          $params['additional']['id'] = $additionalContactId;
          $otherLocationTypeId = array_search('Other', $locationType);
          $addContact = [];

          // check addr exists
          $blockValue = $params['additional']['address'][0];
          $blockValue['contact_id'] = $additionalContactId;
          CRM_Core_BAO_Address::valueExists($blockValue);
          if (empty($blockValue['id'])) {
            if ($otherLocationTypeId) {
              $existsAddr = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_address WHERE contact_id = %1", [
                1 => [$additionalContactId, 'Integer']
              ]);
              if ($existsAddr) {
                $params['additional']['address'][0]['location_type_id'] = $otherLocationTypeId;
              }
            }
            $addContact['address'] = $params['additional']['address'];
          }

          // check phone exists
          $blockValue = $params['additional']['phone'][0];
          $blockValue['contact_id'] = $additionalContactId;
          CRM_Core_BAO_Phone::valueExists($blockValue);
          if (empty($blockValue['id'])) {
            if ($otherLocationTypeId) {
              $existsPhone = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_phone WHERE contact_id = %1", [
                1 => [$additionalContactId, 'Integer']
              ]);
              if ($existsPhone) {
                $params['additional']['phone'][0]['location_type_id'] = $otherLocationTypeId;
                $params['additional']['phone'][0]['is_primary'] = 0;
              }
            }
            $addContact['phone'] = $params['additional']['phone'];
          }

          // check email exists
          $blockValue = $params['additional']['email'][0];
          $blockValue['contact_id'] = $additionalContactId;
          CRM_Core_BAO_Email::valueExists($blockValue);
          if (empty($blockValue['id'])) {
            if ($otherLocationTypeId) {
              $existsEmail = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_email WHERE contact_id = %1", [
                1 => [$additionalContactId, 'Integer']
              ]);
              if ($existsEmail) {
                $params['additional']['email'][0]['location_type_id'] = $otherLocationTypeId;
                $params['additional']['email'][0]['is_primary'] = 0;
              }
            }
            $addContact['email'] = $params['additional']['email'];
          }

          // only update contact when value different
          if (!empty($addContact)) {
            $addContact['id'] = $additionalContactId;
            $addContact['version'] = 3;
            $addContact['log_data'] = ts('Updated contact').'-'.ts('Backer Auto Import');
            civicrm_api('contact', 'create', $addContact);
          }
        }
        else {
          // create new contact
          $addContact = $params['additional'];
          $addContact['version'] = 3;
          $addContact['log_data'] = ts('Updated contact').'-'.ts('Backer Auto Import');
          $result = civicrm_api('contact', 'create', $addContact);
          $additionalContactId = $result['id'];
        }

        if ($additionalContactId && $additionalContactId != $contactId) {
          $addContactParams = [
            'version' => 3,
            'contact_id_a' => $contactId,
            'contact_id_b' => $additionalContactId,
            'relationship_type_id' => $backerRelationTypeId,
            'is_active' => 1,
          ];
          civicrm_api('Relationship', 'create', $addContactParams);
        }
      }

      // create recurring when available
      if (!empty($params['recurring'])) {
        $params['recurring']['contact_id'] = $contactId;
        $params['recurring']['processor_id'] = $this->_paymentProcessor['id'];
        $params['recurring']['is_test'] = $this->_paymentProcessor['is_test'] ? 1 : 0;
        $recur = $params['recurring'];
        $recurFind = [
          'version' => 3,
          'trxn_id' => $recur['trxn_id'],
        ];
        $result = civicrm_api('ContributionRecur', 'get', $recurFind);
        if ($result['id']) {
          $recur['id'] = $result['id'];
          $recur['invoice_id'] = $result['values'][$result['id']]['invoice_id'];
        }
        else {
          $recur['invoice_id'] = md5(uniqid('', TRUE));
        }
        $recur['version'] = "3";
        $recurResult = civicrm_api('ContributionRecur', 'create', $recur);
        $contributionResult['recur_contribution_id']  = $recurResult['id'];
        if (!empty($recur['trxn_id'])) {
          self::updateContributionRecurId($recur['trxn_id']);
        }
        $contributionResult['status'] = "ContributionRecur ID already created.";
      }

      // create a pending contribution then trigger ipn
      if (!empty($params['contribution'])) {
        $params['contribution']['contact_id'] = $contactId;
        $contrib = $params['contribution'];
        $page = [];
        CRM_Contribute_BAO_ContributionPage::setValues($contributionPageId, $page);
        if ($page['id']) {
          $contrib['contribution_status_id'] = 2; // pending
          $contrib['contribution_page_id'] = $page['id'];
          $contrib['contribution_type_id'] = $page['contribution_type_id'];
          $contrib['payment_processor_id'] = $this->_paymentProcessor['id'];
          $contrib['is_test'] = $this->_paymentProcessor['is_test'] ? 1 : 0;
          if (!empty($this->_delivery)) {
            $contrib['invoice_id'] = $contrib['invoice_id'].'_'.$this->_delivery;
          }
          $contrib['version'] = 3;
          $result = civicrm_api('contribution', 'create', $contrib);
          if ($result['id']) {
            $currentContributionId = $result['id'];
            $params['contribution']['id'] = $result['id'];
            $contrib['id'] = $result['id'];
            $ids = CRM_Contribute_BAO_Contribution::buildIds($contrib['id'], 'ipn');
            $this->processIPN($ids, $params['contribution']);
            $contributionResult['status'] = "Get currentContributionId.";
            $contributionResult['contributionId'] = $currentContributionId;
            // return $currentContributionId;
          }
        }
      }
    }
  }

  function processIPN($ids, $contrib) {
    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();
    $input = $objects = [];
    $input['component'] = 'contribute';
    $validateResult = $ipn->validateData($input, $ids, $objects, FALSE);
    if ($validateResult){
      $transaction = new CRM_Core_Transaction();
      $exists = $objects['contribution'];
      $input['amount'] = $contrib['total_amount'];

      // success: 2->1
      if ($contrib['contribution_status_id'] == 1 && $exists->contribution_status_id == 2){
        $objects['contribution']->receive_date = $contrib['receive_date'];
        $ipn->completeTransaction($input, $ids, $objects, $transaction);
      }
      else {
        // multiple scenario will happen

        // failed: 1 or 2 ->4
        if ($contrib['contribution_status_id'] == 4 && in_array($exists->contribution_status_id, [1,2])) {
          $objects['contribution']->cancel_date = $contrib['cancel_date'];
          $cancelReason = $contrib['updated_at'].' '.ts("Update").":\n".$contrib['cancel_reason'];
          $ipn->failed($objects, $transaction, $cancelReason);
        }
        // cancel: 1 or 2->3
        elseif ($contrib['contribution_status_id'] == 3 && in_array($exists->contribution_status_id, [1,2])) {
          $ipn->cancelled($objects, $transaction);
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
    $params = [];
    $json = json_decode($string, TRUE);
    if (!$json) {
      return $params;
    }

    // contact
    self::formatContact($json, $params);

    // recurring
    if ($json['transaction']['type'] == 'parent') {
      self::formatRecurring($json, $params);
    }

    // contribution
    if ($json['transaction']['type'] == 'normal' || $json['transaction']['type'] == 'child') {
      self::formatContribution($json, $params);
    }
    return $params;
  }

  public static function formatContact($json, &$params) {
    $name = self::explodeName($json['user']['name']);
    $locationType = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    if ($name === FALSE) {
      $name = [
        '',    // sure name
        $name, // given name
      ];
    }
    $params['contact'] = [
      'contact_type' => 'Individual',
      'external_identifier' => 'backer-'.$json['user']['id'],
      'last_name' => $name[0],
      'first_name' => $name[1],
      'email' => $json['user']['email'],
    ];
    $params['email'][] = [
      'email' => $json['user']['email'],
      'location_type_id' => array_search('Home', $locationType),
      'is_primary' => 1,
      'append' => TRUE,
    ];
     $params['email'][] = [
      'email' => $json['receipt']['email'],
      'location_type_id' => array_search('Billing', $locationType),
      'is_primary' => 1,
      'append' => TRUE,
    ];
    $phone = self::validateMobilePhone($json['user']['cellphone']);
    $params['phone'][] = [
      'phone' => $phone ? $phone : $json['user']['cellphone'],
      'phone_type_id' => $phone ? 2 : 5, // mobile
      'location_type_id' => array_search('Home', $locationType),
      'is_primary' => 1,
      'append' => TRUE,
    ];

    // address
    // backer special abbr convert to CRM
    if ($json['recipient']['recipient_subdivision']) {
      if ($json['recipient']['recipient_subdivision'] == 'KIN') $json['recipient']['recipient_subdivision'] = 'KMN';
      elseif ($json['recipient']['recipient_subdivision'] == 'LIE') $json['recipient']['recipient_subdivision'] = 'LCI';
      elseif ($json['recipient']['recipient_subdivision'] == 'NWT') $json['recipient']['recipient_subdivision'] = 'TPQ';

      $countryId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_country WHERE name = 'Taiwan'");
      $stateProvinceId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_state_province WHERE abbreviation = %1 AND country_id = %2", [
        1 => [$json['recipient']['recipient_subdivision'], 'String'],
        2 => [$countryId, 'Integer'],
      ]);
    }

    // #40812#note-10 , do not remove recipient data in form data stage
    $params['additional'] = [];
    $address = [
      'country' => ($json['recipient']['recipient_country'] == 'TW') ? 'Taiwan' : '',
      'postal_code' => $json['recipient']['recipient_postal_code'] ? $json['recipient']['recipient_postal_code'] : '',
      'state_province_id' => $stateProvinceId ? $stateProvinceId : '',
      'city' => $json['recipient']['recipient_cityarea'] ? $json['recipient']['recipient_cityarea'] : '',
      'street_address' => $json['recipient']['recipient_address'] ? $json['recipient']['recipient_address'] : '',
      'location_type_id' => array_search('Home', $locationType),
    ];
    $addName = self::explodeName(trim($json['recipient']['recipient_name']));
    if ($addName === FALSE) {
      $addName= [
        '',    // sure name
        $json['recipient']['recipient_name'], // given name
      ];
    }
    $params['additional'] = [
      'contact_type' => 'Individual',
      'last_name' => $addName[0],
      'first_name' => $addName[1],
    ];
    if ($json['recipient']['recipient_contact_email'] && CRM_Utils_Rule::email(trim($json['recipient']['recipient_contact_email']))) {
      $params['additional']['email'][0] = [
        'email' => trim($json['recipient']['recipient_contact_email']),
        'location_type_id' => array_search('Home', $locationType),
        'is_primary' => 1,
        'append' => TRUE,
      ];
    }
    if (!empty($json['recipient']['recipient_cellphone'])) {
      $params['additional']['phone'][0] = [
        'phone' => trim($json['recipient']['recipient_cellphone']),
        'phone_type_id' => 5, // other
        'location_type_id' => array_search('Home', $locationType),
        'is_primary' => 1,
        'append' => TRUE,
      ];
      $addPhone = self::validateMobilePhone(trim($json['recipient']['recipient_cellphone']));
      if ($addPhone) {
        $params['additional']['phone'][0]['phone_type_id'] = 2;
        $params['additional']['phone'][0]['phone'] = $addPhone;
      }
    }
    $params['additional']['address'][0] = $address;
  }

  public static function formatRecurring($json, &$params) {
    $statusMap = [
      // recurring contribution status
      'success' => 1,
      'suspend' => 7,
      'recurring' => 5,
      'failed_cancel' => 3,
    ];

    $recurring = [];
    $recurring['trxn_id'] = $json['transaction']['trade_no'];
    $recurring['amount'] = $json['transaction']['money'];
    $recurring['frequency_unit'] = 'month';
    $recurring['frequency_interval'] = 1;
    $recurring['installments'] = NULL;
    $recurring['create_date'] = date('YmdHis', strtotime($json['transaction']['created_at']));

    // status
    $recurring['contribution_status_id'] = $statusMap[$json['transaction']['render_status']];
    if ($json['transaction']['render_status'] == 'success') {
      $recurring['end_date'] = date('YmdHis');
      unset($recurring['amount']);
    }
    elseif ($json['transaction']['render_status'] == 'recurring') {
      $recurring['start_date'] = date('YmdHis', strtotime($json['transaction']['created_at']));
    }
    elseif ($json['transaction']['render_status'] == 'suspend') {
    }
    elseif ($json['transaction']['render_status'] == 'failed_cancel') {
    }

    if (!empty($json['transaction']['updated_at'])) {
      $recurring['modified_date'] = date('YmdHis', strtotime($json['transaction']['updated_at']));
    }
    else {
      $recurring['modified_date'] = date('YmdHis');
    }
    $recurring['cycle_day'] = date('j');
    $recurring['processor_id'] = NULL;
    $recurring["is_test"] = NULL;
    $recurring['contact_id'] = NULL;
    $params['recurring'] = $recurring;
  }

  public static function formatContribution($json, &$params) {
    $config = CRM_Core_Config::singleton();
    $locationType = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    $instruments =  CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $instrumentMap = [
      'credit' => 'Credit Card',
      'atm' => 'ATM',
      'cvs' => 'Convenient Store (Code)',
    ];
    $statusMap = [
      'refund_applying' => 0,
      'partial_refund' => 0,
      'success' => 1,
      'wait_code' => 2,
      'wait' => 2,
      'refund' => 3,
      'cancel' => 3,
      'failed' => 4,
      'failed_code' => 4,
    ];

    $params['contribution'] = [
      'trxn_id' => $json['transaction']['trade_no'],
      'currency' => 'TWD',
      'total_amount' => (int) $json['transaction']['money'],
      'payment_instrument_id' => $instrumentMap[$json['payment']['type']] ? array_search($instrumentMap[$json['payment']['type']], $instruments) : '',
      'contribution_status_id' => $statusMap[$json['transaction']['render_status']],
      'updated_at' => date('YmdHis', strtotime($json['transaction']['updated_at'])),
    ];

    // invoice_id, recurring
    if (!empty($json['transaction']['parent_trade_no'])) {
      // invoice id is uniq, will append additional info
      $params['contribution']['invoice_id'] = $json['transaction']['parent_trade_no'].'_'.substr(md5(uniqid((string)rand(), TRUE)), 0, 10);
      $contributionRecurId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_recur WHERE trxn_id = %1" , [1 => [$json['transaction']['parent_trade_no'], 'String']]);
      if (!empty($contributionRecurId)) {
        $params['contribution']['contribution_recur_id'] = $contributionRecurId;
      } else {
        $contributionResult['status'] = "No recur id.";
      }
    }
    else {
      $params['contribution']['invoice_id'] = md5(uniqid((string)rand(), TRUE));
    }

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

    $amountLevel = $customFields = [];
    $itemMoney = (int) $json['transaction']['items']['money'];
    $amountLevel[] = "{$json['transaction']['items']['reward_name']}({$json['transaction']['items']['reward_id']})x1:{$itemMoney}";
    if (!empty(trim($json['transaction']['items']['note']))) {
      $amountLevel[] = ts('Note').'=>'.$json['transaction']['items']['note'];
    }

    // process custom options
    // complex logic to values of custom
    if (!empty($json['transaction']['items']['custom_fields'])) {
      $items = $matches = [];
      foreach($json['transaction']['items']['custom_fields'] as $key => $item) {
        $items[$item['name']] = $item['value'];
      }
      CRM_Core_BAO_CustomGroup::matchFieldValues('Contribution', $items, $matches);
      if (!empty($matches[1])) {
        $params['contribution'] += $matches[1];
        $leftItems = array_diff_key($items, $matches[0]);
        foreach($leftItems as $label => $value) {
          $amountLevel[] = $label.'→'.$value;
        }
      }
      if (!empty($amountLevel)) {
        $params['contribution']['amount_level'] = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR.CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $amountLevel).CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
      }
    }
    else {
      if (!empty($amountLevel)) {
        $params['contribution']['amount_level'] = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR.implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $amountLevel).CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
      }
    }

    // handling receipt
    if (!empty($json['receipt']) && !empty($json['receipt']['receipt_type'])) {
      $receiptFieldsMap = [
        'choice' => 'custom_'.$config->receiptYesNo,
        'receipt_type' => 'custom_'.$config->receiptYesNo,
        'corporate_name' => 'custom_'.$config->receiptTitle,
        'contact_name' => 'custom_'.$config->receiptTitle,
        'tax_number' => 'custom_'.$config->receiptSerial,
        'identity_card_number' => 'custom_'.$config->receiptSerial,
      ];
      $choice = trim($json['receipt']['choice']);
      $receiptType = trim($json['receipt']['receipt_type']);
      if ($choice === '不需要收據') {
        $params['contribution'][$receiptFieldsMap['choice']] = '0';
        $needReceipt = FALSE;
      }
      elseif ($receiptType === '稅捐收據' && $choice === '單次寄送紙本收據') {
        $params['contribution'][$receiptFieldsMap['receipt_type']] = '1';
        $needReceipt = TRUE;
      }
      elseif ($receiptType === '稅捐收據' && $choice === '需要收據但不需要寄送') {
        $params['contribution'][$receiptFieldsMap['receipt_type']] = '2';
        $needReceipt = TRUE;
      }
      elseif ($receiptType === '稅捐收據' && $choice === '年度寄送紙本收據') {
        // special case
        $params['contribution'][$receiptFieldsMap['receipt_type']] = 'annual_paper_receipt';
        $needReceipt = TRUE;
      }
      elseif ($receiptType === '稅捐收據' && $choice === '單次寄送電子收據') {
        // special case
        $params['contribution'][$receiptFieldsMap['receipt_type']] = 'single_e_receipt';
        $needReceipt = TRUE;
      }
      elseif ($receiptType === '稅捐收據' && $choice === '年度寄送電子收據') {
        // special case
        $params['contribution'][$receiptFieldsMap['receipt_type']] = 'annual_e_receipt';
        $needReceipt = TRUE;
      }
      if ($needReceipt) {
        if (!empty($json['receipt']['corporate_name'])) {
          $params['contribution'][$receiptFieldsMap['corporate_name']] = $json['receipt']['corporate_name'];
        }
        elseif (!empty($json['receipt']['contact_name'])) {
          $params['contribution'][$receiptFieldsMap['contact_name']] = $json['receipt']['contact_name'];
        }

        if (!empty($json['receipt']['tax_number'])) {
          $params['contribution'][$receiptFieldsMap['tax_number']] = $json['receipt']['tax_number'];
        }
        elseif (!empty($json['receipt']['identity_card_number'])) {
          $params['contribution'][$receiptFieldsMap['identity_card_number']] = $json['receipt']['identity_card_number'];
        }

        // billing address and email
        if ($json['receipt']['address']) {
          if ($json['receipt']['subdivision'] == 'KIN') $json['receipt']['subdivision'] = 'KMN';
          elseif ($json['receipt']['subdivision'] == 'LIE') $json['receipt']['subdivision'] = 'LCI';
          elseif ($json['receipt']['subdivision'] == 'NWT') $json['receipt']['subdivision'] = 'TPQ';

          $countryId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_country WHERE name = 'Taiwan'");
          $stateProvinceId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_state_province WHERE abbreviation = %1 AND country_id = %2", [
            1 => [$json['receipt']['subdivision'], 'String'],
            2 => [$countryId, 'Integer'],
          ]);
          $address = [
            'country' => ($json['receipt']['country'] == 'TW') ? 'Taiwan' : '',
            'postal_code' => $json['receipt']['postal_code'] ? $json['receipt']['postal_code'] : '',
            'state_province_id' => $stateProvinceId ? $stateProvinceId : '',
            'city' => $json['receipt']['city_area'] ? $json['receipt']['city_area'] : '',
            'street_address' => $json['receipt']['address'] ? $json['receipt']['address'] : '',
            'location_type_id' => array_search('Billing', $locationType),
          ];
          $params['address'][] = $address;
        }
        if ($json['receipt']['email']) {
          if (!empty($params['email'])) {
            foreach($params['email'] as &$mail) {
              if (isset($mail['is_primary'])) {
                $mail['is_primary'] = 0;
              }
            }
          }
          $params['email'][] = [
            'email' => $json['receipt']['email'],
            'location_type_id' => array_search('Billing', $locationType),
            'is_primary' => 1,
            'append' => TRUE,
          ];
        }
      }
    }
  }

  /**
   * Explode name string to sure name - given name
   *
   * @param string $str
   * @return array|false
   */
  public static function explodeName($str) {
    $str = trim($str);
    $str = str_replace(["\r","\n"],'',$str);
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
        $name = [
          mb_substr($str, 0, 1, 'UTF-8'),
          mb_substr($str, 1, 1, 'UTF-8'),
        ];
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
    $str = str_replace(["\r","\n"],'',$str);
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

  /**
   * Attempts to backfill the missing contribution_recur_id on a contribution
   * by matching the beginning of the invoice_id with the given trxn_id.
   */
  public static function updateContributionRecurId($trxn_id) {
    $sql = "
            SELECT c.id, r.id AS recur_id
            FROM civicrm_contribution_recur r
            JOIN civicrm_contribution c
              ON c.invoice_id LIKE CONCAT(r.trxn_id, '%')
            WHERE c.contribution_recur_id IS NULL
              AND r.trxn_id = %1
            LIMIT 1
            ";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$trxn_id, 'String']]);
    while ($dao->fetch()) {
      $updateQuery = "UPDATE civicrm_contribution SET contribution_recur_id = %1 WHERE id = %2";
      $params = [
        1 => [$dao->recur_id, 'Integer'],
        2 => [$dao->id, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($updateQuery, $params);
      CRM_Core_Error::debug_log_message("Recur id: {$dao->recur_id} already update for contribution  {$dao->id}");
    }
  }
}
