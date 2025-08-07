<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */




/**
 * This class generates form components for Membership Renewal
 *
 */
class CRM_Member_Form_MembershipRenewal extends CRM_Member_Form {

  public $_contactID;
  public $_memType;
  public $_memTypeDetails;
  public $_endDate;
  public $_mode;
  public $_paymentProcessor;
  public $_processors;
  public $_bltID;
  public $_fields;
  public $_defaults;
  public $_values;
  public $_contributorDisplayName;
  public $_contributorEmail;
  /**
   * @var mixed
   */
  public $_params;
  public $_membershipId;
  public $_groupTree;
  public function preProcess() {
    // check for edit permission
    if (!CRM_Core_Permission::check('edit memberships')) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this
    );
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this
    );
    if ($this->_id) {
      $this->_memType = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $this->_id, "membership_type_id");
      $this->_memTypeDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($this->_memType);
    }

    
    $this->_endDate = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $this->_id, "end_date");
    $this->assign("endDate", $this->_endDate);
    $this->assign("membershipStatus",
      CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipStatus",
        CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership",
          $this->_id, "status_id"
        ),
        "name"
      )
    );

    $orgId = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipType", $this->_memType, "member_of_contact_id");

    $this->assign("memType", CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipType", $this->_memType, "name"));
    $this->assign("orgName", CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $orgId, "display_name"));

    //using credit card :: CRM-2759
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
    if ($this->_mode) {
      $membershipFee = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipType", $this->_memType, 'minimum_fee');
      if (!$membershipFee) {
        $statusMsg = ts('Membership Renewal using credit card required Membership fee, since this memebrship type have no fee, you can use normal renew mode');
        CRM_Core_Session::setStatus($statusMsg);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership"
          ));
      }
      $this->assign('membershipMode', $this->_mode);

      $this->_paymentProcessor = ['billing_mode' => 1];
      $validProcessors = [];
      $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, "billing_mode IN ( 1, 3 ) AND payment_processor_type != 'TaiwanACH'");

      foreach ($processors as $ppID => $label) {


        $paymentProcessor = &CRM_Core_BAO_PaymentProcessor::getPayment($ppID, $this->_mode);
        if ($paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name']) {
          continue;
        }
        elseif ($paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live') {
          continue;
        }
        else {
          $paymentObject = &CRM_Core_Payment::singleton($this->_mode, $paymentProcessor, $this);
          $error = $paymentObject->checkConfig();
          if (empty($error)) {
            $validProcessors[$ppID] = $label;
          }
          $paymentObject = NULL;
        }
      }
      if (empty($validProcessors)) {
         return CRM_Core_Error::statusBounce(ts('Could not find valid payment processor for this page'));
      }
      else {
        $this->_processors = $validProcessors;
      }
      // also check for billing information
      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
      $this->_bltID = array_search('Billing', $locationTypes);
      if (!$this->_bltID) {
         return CRM_Core_Error::statusBounce(ts('Please set a location type of %1', [1 => 'Billing']));
      }
      $this->set('bltID', $this->_bltID);
      $this->assign('bltID', $this->_bltID);

      $this->_fields = [];


      CRM_Core_Payment_Form::setCreditCardFields($this);

      // this required to show billing block
      $this->assign_by_ref('paymentProcessor', $paymentProcessor);
      $this->assign('hidePayPalExpress', TRUE);
    }
    else {
      $this->assign('membershipMode', FALSE);
    }
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults = &parent::setDefaultValues();
    $this->_memType = $defaults["membership_type_id"];
    $renewalDate = date('Y-m-d', strtotime($this->_endDate) + 86400);
    $defaults['renewal_date'] = $renewalDate;

    if ($defaults['id']) {
      $defaults['record_contribution'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment',
        $defaults['id'],
        'contribution_id',
        'membership_id'
      );
    }

    $defaults['contribution_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $this->_memType,
      'contribution_type_id'
    );

    $defaults['total_amount'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $this->_memType,
      'minimum_fee'
    );

    $defaults['record_contribution'] = 0;
    if ($defaults['record_contribution']) {
      $contributionParams = ['id' => $defaults['record_contribution']];
      $contributionIds = [];


      CRM_Contribute_BAO_Contribution::getValues($contributionParams, $defaults, $contributionIds);
    }

    $defaults['send_receipt'] = 0;

    if ($defaults['membership_type_id']) {
      $defaults['receipt_text_renewal'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $defaults['membership_type_id'],
        'receipt_text_renewal'
      );
    }

    $this->assign("member_is_test", CRM_Utils_Array::value('member_is_test', $defaults));

    if ($this->_mode) {
      $fields = [];

      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      $names = ["first_name", "middle_name", "last_name", "street_address-{$this->_bltID}",
        "city-{$this->_bltID}", "postal_code-{$this->_bltID}", "country_id-{$this->_bltID}",
        "state_province_id-{$this->_bltID}",
      ];
      foreach ($names as $name) {
        $fields[$name] = 1;
      }

      $fields["state_province-{$this->_bltID}"] = 1;
      $fields["country-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $fields["email-Primary"] = 1;


      CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactID, $fields, $this->_defaults);

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
        !empty($this->_defaults["email-Primary"])
      ) {
        $defaults["email-{$this->_bltID}"] = $this->_defaults["email-Primary"];
      }

      foreach ($names as $name) {
        if (!empty($this->_defaults[$name])) {
          $defaults["billing_" . $name] = $this->_defaults[$name];
        }
      }
    }
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->applyFilter('__ALL__', 'trim');

    $this->addDate('renewal_date', ts('Date Renewal Entered'), FALSE, ['formatType' => 'activityDate']);
    if ($this->_memTypeDetails['period_type'] == 'fixed') {
      $this->getElement('renewal_date')->freeze();
    }
    if (!$this->_mode) {
      $this->addElement('checkbox', 'record_contribution', ts('Record Renewal Payment?'), NULL);

      $this->add('select', 'contribution_type_id', ts('Contribution Type'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionType()
      );

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->add('select', 'payment_instrument_id', ts('Paid By'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
      );

      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', ['CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id']
      );

      $this->add('select', 'contribution_status_id', ts('Payment Status'),
        CRM_Contribute_PseudoConstant::contributionStatus()
      );

      $this->add('text', 'check_number', ts('Check Number'),
        CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
      );
    }
    // receipt
    $receipt_attr = ['readonly' => 'readonly'];
    $this->add('text', 'receipt_id', ts('Receipt ID'), $receipt_attr);
    $this->addRule('receipt_id', ts('This Receipt ID already exists in the database.'), 'objectExists', ['CRM_Contribute_DAO_Contribution', $this->_id, 'receipt_id']);
    $this->assign('receipt_id_setting', CRM_Utils_System::url("civicrm/admin/receipt", 'reset=1'));
    $this->addDateTime('receipt_date', ts('Receipt Date'), FALSE, ['formatType' => 'activityDateTime']);
    if ($this->_values['receipt_id']) {
      $this->assign('receipt_id', $this->_values['receipt_id']);
      $this->getElement('receipt_date')->freeze();
      $this->getElement('receipt_date_time')->freeze();
    }
    $sendReceiptEle = $this->addElement('checkbox', 'send_receipt', ts('Send Confirmation and Receipt?'), NULL,
      ['onclick' => "return showHideByValue('send_receipt','','notice','table-row','radio',false);"]
    );

      // do_not_notify check
    if (!empty($this->_contactID)) {
      $contactDetail = CRM_Contact_BAO_Contact::getContactDetails($this->_contactID);
      if (!empty($contactDetail[5])) {
        $sendReceiptEle->freeze();
        $this->assign('do_not_notify', TRUE);
      }
    }

    $this->add('textarea', 'receipt_text_renewal', ts('Renewal Message'));

    if ($this->_mode) {
      $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, TRUE);

      CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
    }


    // Retrieve the name and email of the contact - this will be the TO for receipt email
    list($this->_contributorDisplayName,
      $this->_contributorEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
    $this->assign('email', $this->_contributorEmail);


    $mailingInfo = &CRM_Core_BAO_Preferences::mailingPreferences();
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addFormRule(['CRM_Member_Form_MembershipRenewal', 'formRule']);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params) {
    $errors = [];
    //total amount condition arise when membership type having no
    //minimum fee
    if (isset($params['record_contribution'])) {
      if (!$params['contribution_type_id']) {
        $errors['contribution_type_id'] = ts('Please enter the contribution Type.');
      }
      if (!$params['total_amount']) {
        $errors['total_amount'] = ts('Please enter the contribution.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the renewal form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {




    // get the submitted form values.
    $this->_params = $formValues = $this->controller->exportValues($this->_name);

    $params = [];
    $ids = [];
    $config = CRM_Core_Config::singleton();
    $params['contact_id'] = $this->_contactID;
    if ($this->_mode) {
      $formValues['total_amount'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $this->_memType, 'minimum_fee'
      );
      $formValues['contribution_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $this->_memType, 'contribution_type_id'
      );

      $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($formValues['payment_processor_id'],
        $this->_mode
      );

      $now = CRM_Utils_Date::getToday($now, 'YmdHis');
      $fields = [];

      // set email for primary location.
      $fields["email-Primary"] = 1;
      $formValues["email-5"] = $formValues["email-Primary"] = $this->_contributorEmail;
      $formValues['register_date'] = $now;

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $formValues["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_middle_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_last_name', $formValues);

      $formValues["address_name-{$this->_bltID}"] = trim($formValues["address_name-{$this->_bltID}"]);

      $fields["address_name-{$this->_bltID}"] = 1;

      $fields["email-{$this->_bltID}"] = 1;

      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type');

      $nameFields = ['first_name', 'middle_name', 'last_name'];

      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (CRM_Utils_Array::arrayKeyExists("billing_$name", $formValues)) {
          $formValues[$name] = $formValues["billing_{$name}"];
          $formValues['preserveDBName'] = TRUE;
        }
      }

      $contactID = CRM_Contact_BAO_Contact::createProfileContact($formValues, $fields, $this->_contactID, NULL, NULL, $ctype);

      // add all the additioanl payment params we need
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

      $this->_params['year'] = $this->_params['credit_card_exp_date']['Y'];
      $this->_params['month'] = $this->_params['credit_card_exp_date']['M'];
      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $formValues['total_amount'];
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['payment_action'] = 'Sale';
      $this->_params['invoiceID'] = md5(uniqid((string)rand(), TRUE));

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (CRM_Utils_Array::value('send_receipt', $this->_params)) {
        $paymentParams['email'] = $this->_contributorEmail;
      }


      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      $payment = &CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);

      $result = &$payment->doDirectPayment($paymentParams);

      if (is_a($result, 'CRM_Core_Error')) {
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership&mode={$this->_mode}"
          ));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
      }
      $formValues['contribution_status_id'] = 1;
      $formValues['receive_date'] = $now;
      $formValues['invoice_id'] = $this->_params['invoiceID'];
      $formValues['trxn_id'] = $result['trxn_id'];
      $formValues['payment_instrument_id'] = 1;
      $formValues['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      if (CRM_Utils_Array::value('send_receipt', $this->_params) && empty($formValues['receipt_date'])) {
        $formValues['receipt_date'] = $now;
      }
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
      $this->assign('receive_date', CRM_Utils_Date::mysqlToIso($formValues['receive_date']));
    }

    $renewalDate = NULL;

    if ($formValues['renewal_date']) {
      $this->set('renewDate', CRM_Utils_Date::processDate($formValues['renewal_date']));
    }
    else {
      $renewalDate = date('Y-m-d', strtotime($this->_endDate) + 86400);
      $this->set('renewDate', CRM_Utils_Date::processDate($renewalDate));
    }
    $this->_membershipId = $this->_id;

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    // check for test membership.
    $isTestMembership = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_membershipId, 'is_test');
    $renewMembership = CRM_Member_BAO_Membership::renewMembership(
      $this->_contactID,
      $this->_memType,
      $isTestMembership,
      $this,
      NULL,
      $userID
    );

    $endDate = CRM_Utils_Date::processDate($renewMembership->end_date);


    // Retrieve the name and email of the current user - this will be the FROM for the receipt email
    list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);

    $memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $renewMembership->membership_type_id, 'name');

    if (CRM_Utils_Array::value('record_contribution', $formValues) || $this->_mode) {
      //building contribution params
      $contributionParams = [];
      $config = CRM_Core_Config::singleton();
      $contributionParams['currency'] = $config->defaultCurrency;
      $contributionParams['contact_id'] = $params['contact_id'];
      $contributionParams['source'] = "{$memType} " . ts("Membership: Offline membership renewal (by %1)", [1 => $userName]);
      $contributionParams['non_deductible_amount'] = 'null';
      $contributionParams['receive_date'] = date('Y-m-d H:i:s');
      $contributionParams['receipt_date'] = CRM_Utils_Array::value('send_receipt', $formValues) ? $contributionParams['receive_date'] : NULL;
      $tdates = ['receive_date', 'receipt_date'];
      foreach ($tdates as $d) {
        if (!empty($formValues[$d])) {
          $contributionParams[$d] = CRM_Utils_Date::processDate($formValues[$d], $formValues[$d . '_time'], TRUE);
        }
      }

      $recordContribution = ['total_amount', 'contribution_type_id', 'payment_instrument_id', 'trxn_id', 'contribution_status_id', 'invoice_id', 'check_number', 'is_test'];
      foreach ($recordContribution as $f) {
        $contributionParams[$f] = CRM_Utils_Array::value($f, $formValues);
      }


      $contribution = &CRM_Contribute_BAO_Contribution::create($contributionParams, $ids);



      $mpDAO = new CRM_Member_DAO_MembershipPayment();
      $mpDAO->membership_id = $renewMembership->id;
      $mpDAO->contribution_id = $contribution->id;

      CRM_Utils_Hook::pre('create', 'MembershipPayment', NULL, $mpDAO);
      $mpDAO->save();
      CRM_Utils_Hook::post('create', 'MembershipPayment', $mpDAO->id, $mpDAO);

      if ($this->_mode) {
        $trxnParams = [
          'contribution_id' => $contribution->id,
          'trxn_date' => $now,
          'trxn_type' => 'Debit',
          'total_amount' => $formValues['total_amount'],
          'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
          'net_amount' => CRM_Utils_Array::value('net_amount', $result, $formValues['total_amount']),
          'currency' => $config->defaultCurrency,
          'payment_processor' => $this->_paymentProcessor['payment_processor_type'],
          'trxn_id' => $result['trxn_id'],
        ];

        CRM_Core_BAO_FinancialTrxn::create($trxnParams);
      }
    }

    if (CRM_Utils_Array::value('send_receipt', $formValues)) {

      CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipType',
        CRM_Utils_Array::value('membership_type_id', $params),
        'receipt_text_renewal',
        $formValues['receipt_text_renewal']
      );
    }

    $receiptSend = FALSE;
    if (CRM_Utils_Array::value('send_receipt', $formValues)) {
      $receiptSend = TRUE;
      // Retrieve the name and email of the contact - this will be the TO for receipt email
      list($this->_contributorDisplayName,
        $this->_contributorEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      $fromEmail = CRM_Core_PseudoConstant::fromEmailAddress();
      if(!empty($fromEmail['default'])){
        $receiptFrom = reset($fromEmail);
      }
      else{
        $receiptFrom = '"' . $userName . '" <' . $userEmail . '>';
      }

      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];

      //get the group Tree
      $this->_groupTree = &CRM_Core_BAO_CustomGroup::getTree('Membership', $this, $this->_id, FALSE, $this->_memType);

      // retrieve custom data

      $customFields = $customValues = $fo = [];
      foreach ($this->_groupTree as $groupID => $group) {
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }
      }

      CRM_Core_BAO_UFGroup::getValues($this->_contactID, $customFields, $customValues, FALSE,
        [['member_id', '=', $renewMembership->id, 0, 0]]
      , CRM_Core_BAO_UFGroup::MASK_ALL);

      $this->assign_by_ref('formValues', $formValues);
      $this->assign('receive_date', $formValues['receive_date']);
      $this->assign('module', 'Membership');
      $this->assign('receiptType', 'membership renewal');
      $this->assign('mem_start_date', CRM_Utils_Date::customFormat($renewMembership->start_date));
      $this->assign('mem_end_date', CRM_Utils_Date::customFormat($renewMembership->end_date));
      $this->assign('membership_name', CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $renewMembership->membership_type_id
        ));
      $this->assign('customValues', $customValues);
      if ($this->_mode) {
        if (CRM_Utils_Array::value('billing_first_name', $this->_params)) {
          $name = $this->_params['billing_first_name'];
        }

        if (CRM_Utils_Array::value('billing_middle_name', $this->_params)) {
          $name .= " {$this->_params['billing_middle_name']}";
        }

        if (CRM_Utils_Array::value('billing_last_name', $this->_params)) {
          $name .= " {$this->_params['billing_last_name']}";
        }
        $this->assign('billingName', $name);

        // assign the address formatted up for display
        $addressParts = ["street_address-{$this->_bltID}",
          "city-{$this->_bltID}",
          "postal_code-{$this->_bltID}",
          "state_province-{$this->_bltID}",
          "country-{$this->_bltID}",
        ];
        $addressFields = [];
        foreach ($addressParts as $part) {
          list($n, $id) = explode('-', $part);
          if (isset($this->_params['billing_' . $part])) {
            $addressFields[$n] = $this->_params['billing_' . $part];
          }
        }

        $this->assign('address', CRM_Utils_Address::format($addressFields));
        $date = CRM_Utils_Date::format($this->_params['credit_card_exp_date']);
        $date = CRM_Utils_Date::mysqlToIso($date);
        $this->assign('credit_card_exp_date', $date);
        $this->assign('credit_card_number',
          CRM_Utils_System::mungeCreditCard($this->_params['credit_card_number'])
        );
        $this->assign('credit_card_type', $this->_params['credit_card_type']);
        $this->assign('contributeMode', 'direct');
        $this->assign('isAmountzero', 0);
        $this->assign('is_pay_later', 0);
        $this->assign('isPrimary', 1);
        if ($this->_mode == 'test') {
          $this->assign('action', '1024');
        }
      }

      #14664, remove un-notice strange design to send member notice
      $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow('msg_tpl_workflow_membership', 'membership_offline_receipt');
      $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($renewMembership, 'Membership Notification Email', $workflow['msg_title']);
      list($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
        [
          'activityId' => $activityId,
          'groupName' => 'msg_tpl_workflow_membership',
          'valueName' => 'membership_offline_receipt',
          'contactId' => $this->_contactID,
          'from' => $receiptFrom,
          'toName' => $this->_contributorDisplayName,
          'toEmail' => $this->_contributorEmail,
          'isTest' => $this->_mode == 'test',
        ],
        CRM_Core_DAO::$_nullObject,
        [
          0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
          1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
        ]
      );
    }

    $statusMsg = ts('%1 membership for %2 has been renewed.', [1 => $memType, 2 => $this->_contributorDisplayName]);

    $endDate = $endDate ?  CRM_Utils_Date::customFormat($endDate) : CRM_Utils_Date::customFormat($renewMembership->end_date);
    if ($endDate) {
      $statusMsg .= ' ' . ts('The new membership End Date is %1.', [1 => $endDate]);
    }

    if ($receiptSend && $mailSend) {
      $statusMsg .= ' ' . ts('A renewal confirmation and receipt has been sent to %1.', [1 => $this->_contributorEmail]);
    }

    CRM_Core_Session::setStatus($statusMsg);
  }
}

