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
 * This class generates form components for Membership Type
 *
 */
class CRM_Member_Form_Membership extends CRM_Member_Form {
  public $_cdType;
  public $_contactID;
  public $_context;
  public $_paymentProcessor;
  public $_processors;
  public $_bltID;
  public $_fields;
  public $_defaults;
  public $_values;
  public $_memberDisplayName;
  public $_memberEmail;
  /**
   * @var mixed
   */
  public $_params;
  public $_groupTree;
  protected $_memType = NULL;

  protected $_onlinePendingContributionId;

  protected $_mode;

  public $_membershipID;

  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
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


    // check for edit permission
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', $this->_action)) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $this->_context);

    if ($this->_id) {
      $this->_memType = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $this->_id,
        "membership_type_id"
      );
    }
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    if ($this->_mode) {
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

    //check whether membership status present or not
    if ($this->_action & CRM_Core_Action::ADD) {
      CRM_Member_BAO_Membership::statusAvilability($this->_contactID);
    }

    // when custom data is included in this page
    if (CRM_Utils_Array::value("hidden_custom", $_POST)) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    // CRM-4395, get the online pending contribution id.
    $this->_onlinePendingContributionId = NULL;
    if (!$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {

      $this->_onlinePendingContributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution($this->_id, 'Membership');
    }
    $this->assign('onlinePendingContributionId', $this->_onlinePendingContributionId);

    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $defaults = [];
    $defaults = &parent::setDefaultValues();

    //setting default join date and receive date
    list($now) = CRM_Utils_Date::setDateDefaults();
    if ($this->_action == CRM_Core_Action::ADD) {
      list($defaults['receive_date'], $defaults['receive_date_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    }

    if (is_numeric($this->_memType)) {
      $defaults["membership_type_id"] = [];
      $defaults["membership_type_id"][0] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $this->_memType,
        'member_of_contact_id',
        'id'
      );
      $defaults["membership_type_id"][1] = $this->_memType;
    }
    else {
      $defaults["membership_type_id"] = $this->_memType;
    }

    if (CRM_Utils_Array::value('id', $defaults)) {
      if ($this->_onlinePendingContributionId) {
        $defaults['record_contribution'] = $this->_onlinePendingContributionId;
      }
      else {
        $contributionId = CRM_Core_DAO::singleValueQuery("
  SELECT contribution_id 
  FROM civicrm_membership_payment 
  WHERE membership_id = $this->_id 
  ORDER BY contribution_id 
  DESC limit 1");

        if ($contributionId) {
          $defaults['record_contribution'] = $contributionId;
        }
      }
    }

    if (CRM_Utils_Array::value('record_contribution', $defaults) && !$this->_mode) {
      $contributionParams = ['id' => $defaults['record_contribution']];
      $contributionIds = [];


      CRM_Contribute_BAO_Contribution::getValues($contributionParams, $defaults, $contributionIds);

      list($defaults['receive_date']) = CRM_Utils_Date::setDateDefaults($defaults['receive_date']);

      // Contribution::getValues() over-writes the membership record's source field value - so we need to restore it.
      if (CRM_Utils_Array::value('membership_source', $defaults)) {
        $defaults['source'] = $defaults['membership_source'];
      }
    }

    // User must explicitly choose to send a receipt in both add and update mode.
    $defaults['send_receipt'] = 0;

    if ($this->_action & CRM_Core_Action::UPDATE) {
      // in this mode by default uncheck this checkbox
      unset($defaults['record_contribution']);
    }

    $this->assign("member_is_test", CRM_Utils_Array::value('member_is_test', $defaults));

    $this->assign('membership_status_id', CRM_Utils_Array::value('status_id', $defaults));

    if (CRM_Utils_Array::value('is_pay_later', $defaults)) {
      $this->assign('is_pay_later', TRUE);
    }
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

      if ($this->_contactID) {

        CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactID, $fields, $this->_defaults);
      }

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

    $dates = ['join_date', 'start_date', 'end_date'];
    foreach ($dates as $key) {
      if (CRM_Utils_Array::value($key, $defaults)) {
        list($defaults[$key]) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value($key, $defaults));
      }
    }

    //setting default join date if there is no join date
    if (!CRM_Utils_Array::value('join_date', $defaults)) {
      $defaults['join_date'] = $now;
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
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
    $this->assign('entityID', $this->_id);

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    if ($this->_context == 'standalone') {

      CRM_Contact_Form_NewContact::buildQuickForm($this);
    }

    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $dao = new CRM_Member_DAO_MembershipType();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->find();
    while ($dao->fetch()) {
      if ($dao->is_active) {
        if ($this->_mode && !$dao->minimum_fee) {
          continue;
        }
        else {
          if (!CRM_Utils_Array::value($dao->member_of_contact_id, $selMemTypeOrg)) {
            $selMemTypeOrg[$dao->member_of_contact_id] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $dao->member_of_contact_id,
              'display_name',
              'id'
            );

            $selOrgMemType[$dao->member_of_contact_id][0] = ts('- select -');
          }
          if (!CRM_Utils_Array::value($dao->id, $selOrgMemType[$dao->member_of_contact_id])) {
            $selOrgMemType[$dao->member_of_contact_id][$dao->id] = $dao->name;
          }
        }
      }
    }

    // show organization by default, if only one organization in
    // the list
    if (count($selMemTypeOrg) == 2) {
      unset($selMemTypeOrg[0], $selOrgMemType[0][0]);
    }
    //sort membership organization and type, CRM-6099
    natcasesort($selMemTypeOrg);
    foreach ($selOrgMemType as $index => $orgMembershipType) {
      natcasesort($orgMembershipType);
      $selOrgMemType[$index] = $orgMembershipType;
    }
    $sel = &$this->addElement('hierselect',
      'membership_type_id',
      ts('Membership Organization and Type'),
      ['onChange' => "buildCustomData( 'Membership', this.value );"]
    );

    $sel->setOptions([$selMemTypeOrg, $selOrgMemType]);

    $this->applyFilter('__ALL__', 'trim');

    $this->addDate('join_date', ts('Join Date'), FALSE, ['formatType' => 'activityDate']);
    $this->addDate('start_date', ts('Start Date'), FALSE, ['formatType' => 'activityDate']);
    $this->addDate('end_date', ts('End Date'), FALSE, ['formatType' => 'activityDate']);

    $this->add('text', 'source', ts('Source'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'source')
    );

    if (!$this->_mode) {
      $this->add('select', 'status_id', ts('Membership Status'),
        ['' => ts('- select -')] + CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label')
      );
      $this->addElement('checkbox', 'is_override',
        ts('Status Override?'), NULL,
        ['onClick' => 'showHideMemberStatus()']
      );

      $membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();
      $skipStatusCalStatus = array_intersect($membershipStatuses, ['Pending', 'Expired', 'Cancelled']);
      if (!empty($skipStatusCalStatus[$this->_defaults['status_id']])) {
        $this->addElement('checkbox', 'skip_status_cal', ts('Skip status calculate'));
        $defaults = ['skip_status_cal' => 1];
        $this->setDefaults($defaults);
      }

      $this->addElement('checkbox', 'record_contribution', ts('Record Membership Payment?'));


      $this->add('select', 'contribution_type_id',
        ts('Contribution Type'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionType()
      );

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->addDateTime('receive_date', ts('Receive Date'), FALSE, ['formatType' => 'activityDateTime']);

      $this->add('select', 'payment_instrument_id',
        ts('Paid By'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
      );
      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', ['CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id']
      );

      $allowStatuses = [];
      $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
      if ($this->_onlinePendingContributionId) {
        $statusNames = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
        foreach ($statusNames as $val => $name) {
          if (in_array($name, ['In Progress', 'Overdue'])) {
            continue;
          }
          $allowStatuses[$val] = $statuses[$val];
        }
      }
      else {
        $allowStatuses = $statuses;
      }
      $this->add('select', 'contribution_status_id',
        ts('Payment Status'), $allowStatuses
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
    if (!empty($this->_values['receipt_id'])) {
      $this->assign('receipt_id', $this->_values['receipt_id']);
      $this->getElement('receipt_date')->freeze();
      $this->getElement('receipt_date_time')->freeze();
    }

    $sendReceiptEle = $this->addElement('checkbox', 'send_receipt', ts('Send Confirmation and Receipt?'), NULL, ['onclick' => "return showHideByValue('send_receipt','','notice','table-row','radio',false);"]);

    $this->add('textarea', 'receipt_text_signup', ts('Receipt Message'));
    if ($this->_mode) {

      $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, TRUE);

      CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
    }

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    if ($this->_contactID) {

      list($this->_memberDisplayName,
        $this->_memberEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

      $this->assign('emailExists', $this->_memberEmail);
      $this->assign('displayName', $this->_memberDisplayName);

      // do_not_notify check
      $contactDetail = CRM_Contact_BAO_Contact::getContactDetails($this->_contactID);
      if (!empty($contactDetail[5])) {
        $sendReceiptEle->freeze();
        $this->assign('do_not_notify', TRUE);
      }
    }

    $this->addFormRule(['CRM_Member_Form_Membership', 'formRule'], $this);


    $mailingInfo = &CRM_Core_BAO_Preferences::mailingPreferences();
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    parent::buildQuickForm();
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
  public static function formRule($params, $files, $self) {
    $errors = [];

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    if (!CRM_Utils_Array::value(1, $params['membership_type_id'])) {
      $errors['membership_type_id'] = ts('Please select a membership type.');
    }
    if (CRM_Utils_Array::value(1, $params['membership_type_id']) &&
      CRM_Utils_Array::value('payment_processor_id', $params)
    ) {
      // make sure that credit card number and cvv are valid

      if (CRM_Utils_Array::value('credit_card_type', $params)) {
        if (CRM_Utils_Array::value('credit_card_number', $params) &&
          !CRM_Utils_Rule::creditCardNumber($params['credit_card_number'], $params['credit_card_type'])
        ) {
          $errors['credit_card_number'] = ts("Please enter a valid Credit Card Number");
        }

        if (CRM_Utils_Array::value('cvv2', $params) &&
          !CRM_Utils_Rule::cvv($params['cvv2'], $params['credit_card_type'])
        ) {
          $errors['cvv2'] = ts("Please enter a valid Credit Card Verification Number");
        }
      }
    }

    $joinDate = NULL;
    if (!CRM_Utils_Array::value('skip_status_cal', $params) && empty($params['start_date'])) {
      $errors['start_date'] = ts('Start date must be the same or later than join date.');
    }

    if (CRM_Utils_Array::value('join_date', $params)) {
      $joinDate = CRM_Utils_Date::processDate($params['join_date']);

      $membershipDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($params['membership_type_id'][1]);

      $startDate = NULL;
      if (CRM_Utils_Array::value('start_date', $params)) {
        $startDate = CRM_Utils_Date::processDate($params['start_date']);
      }
      if ($startDate && CRM_Utils_Array::value('period_type', $membershipDetails) == 'rolling') {
        if ($startDate < $joinDate) {
          $errors['start_date'] = ts('Start date must be the same or later than join date.');
        }
      }

      // if end date is set, ensure that start date is also set
      // and that end date is later than start date
      // If selected membership type has duration unit as 'lifetime'
      // and end date is set, then give error
      $endDate = NULL;
      if (CRM_Utils_Array::value('end_date', $params)) {
        $endDate = CRM_Utils_Date::processDate($params['end_date']);
      }
      if ($endDate) {
        if ($membershipDetails['duration_unit'] == 'lifetime') {
          $errors['end_date'] = ts('The selected Membership Type has a lifetime duration. You cannot specify an End Date for lifetime memberships. Please clear the End Date OR select a different Membership Type.');
        }
        else {
          if (!$startDate) {
            $errors['start_date'] = ts('Start date must be set if end date is set.');
          }
          if ($endDate < $startDate) {
            $errors['end_date'] = ts('End date must be the same or later than start date.');
          }
        }
      }

      //  Default values for start and end dates if not supplied
      //  on the form
      $defaultDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType(
        $params['membership_type_id'][1], $joinDate,
        $startDate, $endDate
      );
      if (!$startDate) {
        $startDate = CRM_Utils_Array::value('start_date',
          $defaultDates
        );
      }
      if (!$endDate) {
        $endDate = CRM_Utils_Array::value('end_date',
          $defaultDates
        );
      }

      //CRM-3724, check for availability of valid membership status.
      if (!CRM_Utils_Array::value('is_override', $params)) {

        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
          $endDate,
          $joinDate,
          'today',
          TRUE
        );
        if (empty($calcStatus)) {
          $url = CRM_Utils_System::url('civicrm/admin/member/membershipStatus', 'reset=1&action=browse');
          $errors['_qf_default'] = ts('There is no valid Membership Status available for selected membership dates.');
          $status = ts('Oops, it looks like there is no valid membership status available for the given membership dates. You can <a href="%1">Configure Membership Status Rules</a>.', [1 => $url]);
          if (!$self->_mode) {
            $status .= ' ' . ts('OR You can sign up by setting Status Override? to true.');
          }
          CRM_Core_Session::setStatus($status);
        }
      }
    }
    else {
      $errors['join_date'] = ts('Please enter the join date.');
    }

    if (isset($params['is_override']) &&
      $params['is_override'] &&
      !CRM_Utils_Array::value('status_id', $params)
    ) {
      $errors['status_id'] = ts('Please enter the status.');
    }

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

    // validate contribution status for 'Failed'.
    if ($self->_onlinePendingContributionId &&
      CRM_Utils_Array::value('record_contribution', $params) &&
      (CRM_Utils_Array::value('contribution_status_id', $params) ==
        array_search('Failed', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))
      )
    ) {
      $errors['contribution_status_id'] = ts("Please select a valid payment status before updating.");
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {




    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Member_BAO_Membership::deleteRelatedMemberships($this->_id);
      CRM_Member_BAO_Membership::deleteMembership($this->_id);
      return;
    }
    $config = CRM_Core_Config::singleton();
    $membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();

    // get the submitted form values.
    $this->_params = $formValues = $this->controller->exportValues($this->_name);

    $params = [];
    $ids = [];

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $formValues)) {
      $this->_contactID = $formValues['contact_select_id'][1];
    }

    $params['contact_id'] = $this->_contactID;

    $fields = [
      'status_id',
      'source',
      'is_override',
    ];

    foreach ($fields as $f) {
      $params[$f] = CRM_Utils_Array::value($f, $formValues);
    }

    // fix for CRM-3724
    // when is_override false ignore is_admin statuses during membership
    // status calculation. similarly we did fix for import in CRM-3570.
    if (!CRM_Utils_Array::value('is_override', $params)) {
      $params['exclude_is_admin'] = TRUE;
    }
    $params['membership_type_id'] = $formValues['membership_type_id'][1];

    // process date params to mysql date format.
    $dateTypes = ['join_date' => 'joinDate',
      'start_date' => 'startDate',
      'end_date' => 'endDate',
    ];
    foreach ($dateTypes as $dateField => $dateVariable) {
      $$dateVariable = CRM_Utils_Date::processDate($formValues[$dateField]);
    }
    $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($params['membership_type_id'],
      $joinDate, $startDate, $endDate
    );
    $excludeIsAdmin = CRM_Utils_Array::value('exclude_is_admin', $params, FALSE);
    if (!$excludeIsAdmin && !CRM_Utils_Array::value('is_override', $params)) {
      $excludeIsAdmin = TRUE;
    }
    $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate, $endDate, $joinDate, 'today', $excludeIsAdmin);

    $dates = ['join_date',
      'start_date',
      'end_date',
      'reminder_date',
    ];
    $currentTime = getDate();
    $pendingId = array_search('Pending', $membershipStatuses);
    foreach ($dates as $d) {
      //first give priority to form values then calDates.
      $date = CRM_Utils_Array::value($d, $formValues);
      if (!$date) {
        if ($formValues['status_id'] == $pendingId && !$formValues['skip_status_cal'] && $calcStatus['id'] != $pendingId) {
          $date = CRM_Utils_Array::value($d, $calcDates);
        }
        elseif($formValues['status_id'] != $pendingId) {
          $date = CRM_Utils_Array::value($d, $calcDates);
        }
      }
      if ($date) {
        $params[$d] = CRM_Utils_Date::processDate($date, NULL, TRUE);
      }
      else {
        $params[$d] = '';
      }
    }
    if ($formValues['skip_status_cal'] == 1) {
      $params['skipStatusCal'] = 1;
    }

    $tdates = ['receive_date', 'receipt_date'];
    foreach ($tdates as $d) {
      $params[$d] = CRM_Utils_Date::processDate($formValues[$d], $formValues[$d . '_time'], TRUE);
    }

    if ($this->_id) {
      $ids['membership'] = $params['id'] = $this->_id;
    }

    $session = CRM_Core_Session::singleton();
    $ids['userId'] = $session->get('userID');

    // membership type custom data
    $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE,
      CRM_Utils_Array::value('membership_type_id', $params)
    );

    $customFields = CRM_Utils_Array::arrayMerge($customFields,
      CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE, NULL, NULL, TRUE)
    );

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($formValues,
      $customFields,
      $this->_id,
      'Membership'
    );

    // Retrieve the name and email of the current user - this will be the FROM for the receipt email

    list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($ids['userId']);

    if (CRM_Utils_Array::value('record_contribution', $formValues)) {
      $recordContribution = ['total_amount', 'contribution_type_id', 'payment_instrument_id',
        'trxn_id', 'contribution_status_id', 'check_number',
      ];

      foreach ($recordContribution as $f) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }

      $membershipType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $formValues['membership_type_id'][1]
      );
      if (!$this->_onlinePendingContributionId) {
        $params['contribution_source'] = "{$membershipType} " . ts("Membership: Offline membership signup (by %1)", [1 => $userName]);
      }

      if (CRM_Utils_Array::value('send_receipt', $formValues) && empty($formValues['receipt_date'])) {
        $params['receipt_date'] = $params['receive_date'];
      }

      //insert contribution type name in receipt.
      $formValues['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
        $formValues['contribution_type_id']
      );
    }

    if ($this->_mode) {
      $params['total_amount'] = $formValues['total_amount'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $params['membership_type_id'], 'minimum_fee'
      );
      $params['contribution_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $params['membership_type_id'], 'contribution_type_id'
      );

      $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($formValues['payment_processor_id'],
        $this->_mode
      );



      $now = date('YmdHis');
      $fields = [];

      // set email for primary location.
      $fields["email-Primary"] = 1;
      $formValues["email-5"] = $formValues["email-Primary"] = $this->_memberEmail;
      $params['register_date'] = $now;

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
      $this->_params['amount'] = $params['total_amount'];
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['payment_action'] = 'Sale';
      $this->_params['invoiceID'] = md5(uniqid((string)rand(), TRUE));

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (CRM_Utils_Array::value('send_receipt', $this->_params)) {
        $paymentParams['email'] = $this->_memberEmail;
      }


      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      $payment = &CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);

      $result = &$payment->doDirectPayment($paymentParams);

      if (is_a($result, 'CRM_Core_Error')) {
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=add&cid={$this->_contactID}&context=&mode={$this->_mode}"
          ));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
      }
      $params['contribution_status_id'] = 1;
      $params['receive_date'] = $now;
      $params['invoice_id'] = $this->_params['invoiceID'];
      $params['contribution_source'] = ts('Online Membership: Admin Interface');
      $params['source'] = $formValues['source'] ? $formValues['source'] : $params['contribution_source'];
      $params['trxn_id'] = $result['trxn_id'];
      $params['payment_instrument_id'] = 1;
      $params['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      if (CRM_Utils_Array::value('send_receipt', $this->_params) && empty($this->_params['receipt_date'])) {
        $params['receipt_date'] = $now;
      }

      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
      $this->assign('receive_date',
        CRM_Utils_Date::mysqlToIso($params['receive_date'])
      );

      // required for creating membership for related contacts
      $params['action'] = $this->_action;

      $membership = &CRM_Member_BAO_Membership::create($params, $ids);
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->trxn_id = $result['trxn_id'];
      if ($contribution->find(TRUE)) {
        // next create the transaction record
        $trxnParams = [
          'contribution_id' => $contribution->id,
          'trxn_date' => $now,
          'trxn_type' => 'Debit',
          'total_amount' => $params['total_amount'],
          'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
          'net_amount' => CRM_Utils_Array::value('net_amount', $result, $params['total_amount']),
          'currency' => $config->defaultCurrency,
          'payment_processor' => $this->_paymentProcessor['payment_processor_type'],
          'trxn_id' => $result['trxn_id'],
        ];

        CRM_Core_BAO_FinancialTrxn::create($trxnParams);
      }
    }
    else {
      $params['action'] = $this->_action;
      if ($this->_onlinePendingContributionId && CRM_Utils_Array::value('record_contribution', $formValues)) {

        // update membership as well as contribution object, CRM-4395

        $params['contribution_id'] = $this->_onlinePendingContributionId;
        $params['componentId'] = $params['id'];
        $params['componentName'] = 'contribute';
        $result = CRM_Contribute_BAO_Contribution::transitionComponents($params, TRUE);

        //carry updated membership object.
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $this->_id;
        $membership->find(TRUE);

        $cancelled = TRUE;
        if ($membership->end_date) {
          //display end date w/ status message.
          $endDate = $membership->end_date;


          if (!in_array($membership->status_id, [array_search('Cancelled', $membershipStatuses),
                array_search('Expired', $membershipStatuses),
              ])) {
            $cancelled = FALSE;
          }
        }
        // suppress form values in template.
        $this->assign('cancelled', $cancelled);

        // here we might updated dates, so get from object.
        foreach ($calcDates as $date => & $val) {
          if ($membership->$date) {
            $val = $membership->$date;
          }
        }
      }
      else {
        $membership = &CRM_Member_BAO_Membership::create($params, $ids);
      }
    }
    $this->_membershipID = $membership->id;

    $receiptSend = FALSE;
    if (CRM_Utils_Array::value('send_receipt', $formValues)) {
      $receiptSend = TRUE;
      $fromEmail = CRM_Core_PseudoConstant::fromEmailAddress();
      if(!empty($fromEmail['default'])){
        $receiptFrom = reset($fromEmail);
      }
      else{
        $receiptFrom = '"' . $userName . '" <' . $userEmail . '>';
      }

      if (CRM_Utils_Array::value('payment_instrument_id', $formValues)) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];
      }

      // retrieve custom data

      $customFields = $customValues = [];
      foreach ($this->_groupTree as $groupID => $group) {
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }
      }
      $members = [['member_id', '=', $membership->id, 0, 0]];
      // check whether its a test drive
      if ($this->_mode) {
        $members[] = ['member_test', '=', 1, 0, 0];
      }
      CRM_Core_BAO_UFGroup::getValues($this->_contactID, $customFields, $customValues, FALSE, $members, CRM_Core_BAO_UFGroup::MASK_ALL);
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
      }
      $this->assign('module', 'Membership');
      $this->assign('contactID', $this->_contactID);
      $this->assign('membershipID', $params['membership_id']);
      $this->assign('receiptType', 'membership signup');
      $this->assign('receive_date', $params['receive_date']);
      $this->assign('formValues', $formValues);
      $this->assign('mem_start_date', CRM_Utils_Date::customFormat($calcDates['start_date']));
      $this->assign('mem_end_date', CRM_Utils_Date::customFormat($calcDates['end_date']));
      $this->assign('membership_name', CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $formValues['membership_type_id'][1]
        ));
      $this->assign('customValues', $customValues);

      $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow('msg_tpl_workflow_membership', 'membership_offline_receipt');
      $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($membership, 'Membership Notification Email', $workflow['msg_title']);
      list($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
        [
          'activityId' => $activityId,
          'groupName' => 'msg_tpl_workflow_membership',
          'valueName' => 'membership_offline_receipt',
          'contactId' => $this->_contactID,
          'from' => $receiptFrom,
          'toName' => $this->_memberDisplayName,
          'toEmail' => $this->_memberEmail,
          'isTest' => (bool)($this->_action & CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_DAO::$_nullObject,
        [
          0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
          1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
        ]
      );
    }

    //end date can be modified by hooks, so if end date is set then use it.
    $endDate = ($membership->end_date) ? $membership->end_date : $endDate;
    if (($this->_action & CRM_Core_Action::UPDATE)) {
      $statusMsg = ts('Membership for %1 has been updated.', [1 => $this->_memberDisplayName]);
      if ($endDate) {
        $endDate = CRM_Utils_Date::customFormat($endDate);
        $statusMsg .= ' ' . ts('The membership End Date is %1.', [1 => $endDate]);
      }
      if ($receiptSend) {
        $statusMsg .= ' ' . ts('A confirmation and receipt has been sent to %1.', [1 => $this->_memberEmail]);
      }
    }
    elseif (($this->_action & CRM_Core_Action::ADD)) {

      $memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $params['membership_type_id']
      );
      $statusMsg = ts('%1 membership for %2 has been added.', [1 => $memType, 2 => $this->_memberDisplayName]);

      //get the end date from calculated dates.
      $endDate = ($endDate) ? $endDate : CRM_Utils_Array::value('end_date', $calcDates);

      if ($endDate) {
        $endDate = CRM_Utils_Date::customFormat($endDate);
        $statusMsg .= ' ' . ts('The new membership End Date is %1.', [1 => $endDate]);
      }
      if ($receiptSend && $mailSend) {
        $statusMsg .= ' ' . ts('A membership confirmation and receipt has been sent to %1.', [1 => $this->_memberEmail]);
      }
    }
    CRM_Core_Session::setStatus($statusMsg);

    $buttonName = $this->controller->getButtonName();
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/member/add',
            'reset=1&action=add&context=standalone'
          ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
            "reset=1&cid={$this->_contactID}&selectedChild=member"
          ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action=add&context=membership&cid={$this->_contactID}"
        ));
    }
  }
}

