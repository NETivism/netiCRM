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
 * This class generates form components for processing a contribution
 *
 */
class CRM_Contribute_Form_Contribution extends CRM_Core_Form {
  public $_priceSetId;
  public $_submitValues;
  public $userDisplayName;
  public $userEmail;
  public $_multiContribComponent;
  public $_priceSet;
  /**
   * @var mixed
   */
  public $_params;
  public $_mode;

  public $_action;

  public $_bltID;

  public $_fields;

  public $_paymentProcessor;

  public $_processors;

  public $_participantId;

  public $_membershipId;

  /**
   * the id of the contribution that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the id of the premium that we are proceessing
   *
   * @var int
   * @public
   */
  public $_premiumID = NULL;
  public $_productDAO = NULL;

  /**
   * the id of the note
   *
   * @var int
   * @public
   */
  public $_noteID;

  /**
   * the id of the contact associated with this contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  /**
   * the id of the pledge payment that we are processing
   *
   * @var int
   * @public
   */
  public $_ppID;

  /**
   * the id of the pledge that we are processing
   *
   * @var int
   * @public
   */
  public $_pledgeID;

  /**
   * is this contribution associated with an online
   * financial transaction
   *
   * @var boolean
   * @public
   */
  public $_online = FALSE;

  /**
   * Stores all product option
   *
   * @var array
   * @public
   */
  public $_options;

  /**
   * stores the honor id
   *
   * @var int
   * @public
   */
  public $_honorID = NULL;

  /**
   * Store the contribution Type ID
   *
   * @var array
   */
  public $_contributionType;

  /**
   * The contribution values if an existing contribution
   */
  public $_values;

  /**
   * The pledge values if this contribution is associated with pledge
   */
  public $_pledgeValues;

  public $_contributeMode = 'direct';

  public $_context;

  /*
     * Store the line items if price set used.
     */

  public $_lineItems;

  protected $_formType;
  protected $_cdType;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }

    $this->_cdType = CRM_Utils_Array::value('type', $_GET);

    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    // get price set id.
    $this->_priceSetId = CRM_Utils_Array::value('priceSetId', $_GET);
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);

    //get the pledge payment id
    $this->_ppID = CRM_Utils_Request::retrieve('ppid', 'Positive', $this);

    //get the contact id
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // refs #31746, #30215, #30417 ... etc.
    // somehow we can't trust contact id from saved session
    // uid from url will be modify by saved session object
    // we use submitted value instead when really submit
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($this->_submitValues) && $this->_submitValues['contact_preset_id']) {
      if (CRM_Utils_Type::validate($this->_submitValues['contact_preset_id'], 'Positive', FALSE, 'contact_preset_id')) {
        $this->_contactID = $this->_submitValues['contact_preset_id'];
      }
    }

    //get the action.
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    //get the contribution id if update
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    // add participant payment links for this contribution
    $pid = CRM_Utils_Request::retrieve( 'participant_id', 'Positive', $this);
    $mid = CRM_Utils_Request::retrieve( 'membership_id', 'Positive', $this);
    if($this->_action & CRM_Core_Action::ADD) {
      if($pid) {
        $pcid = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $pid, 'contact_id');
        // check if participant is the same with this contact
        if($this->_contactID == $pcid){
          $this->_participantId = $pid;
          $this->assign('participantId', $this->_participantId);
        }
      }
      if($mid) {
        $mcid = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $mid, 'contact_id');
        // check if membership is the same with this contact
        if($this->_contactID == $mcid){
          $this->_membershipId = $mid;
          $this->assign('membershipId', $this->_membershipId);
        }
      }
    }

    if ($this->_contactID) {
      $this->_context = 'contribution';
      $this->set('context', 'contribution');
    }
    else {
      $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    }
    $this->assign('context', $this->_context);

    // set the contribution mode.
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
    // check if mode available
    $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, "billing_mode IN ( 1, 3 ) AND payment_processor_type != 'TaiwanACH'");
    if(empty($processors)) {
      $this->_mode = NULL;
    }

    $this->assign('contributionMode', $this->_mode);

    $this->_paymentProcessor = ['billing_mode' => 1];

    $this->assign('showCheckNumber', FALSE);

    //ensure that processor has a valid config
    //only valid processors get display to user
    if ($this->_mode) {
      $validProcessors = [];
      foreach ($processors as $ppID => $label) {


        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppID, $this->_mode);
        if ($paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name']) {
          continue;
        }
        elseif ($paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live') {
          continue;
        }
        else {
          $paymentObject = CRM_Core_Payment::singleton($this->_mode, $paymentProcessor, $this);
          $error = $paymentObject->checkConfig();
          if (empty($error)) {
            $validProcessors[$ppID] = $label;
          }
          $paymentObject = NULL;
        }
      }
      if (empty($validProcessors)) {
         return CRM_Core_Error::statusBounce(ts('You will need to configure the %1 settings for your Payment Processor before you can submit credit card transactions.', [1 => $this->_mode]));
      }
      else {
        $this->_processors = $validProcessors;
      }
    }

    // this required to show billing block
    $this->assign_by_ref('paymentProcessor', $paymentProcessor);
    $this->assign('hidePayPalExpress', TRUE);

    if ($this->_contactID) {

      list($this->userDisplayName,
        $this->userEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      $this->assign('displayName', $this->userDisplayName);
      $this->assign('contactID', $this->_contactID);
    }
    

    // Assign pageTitle to be "Contribution - "+ Contributor name
    //      $pageTitle = 'Contribution - '.$this->userDisplayName;
    //    	$this->assign( 'pageTitle', $pageTitle );

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



    // payment fields are depending on payment type
    if (CRM_Utils_Array::value('payment_type', $this->_processors) & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT) {
      CRM_Core_Payment_Form::setDirectDebitFields($this);
    }
    else {
      CRM_Core_Payment_Form::setCreditCardFields($this);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $config = CRM_Core_Config::singleton();
    if (in_array('CiviPledge', $config->enableComponents) &&
      !$this->_formType
    ) {


      //get the payment values associated with given pledge payment id OR check for payments due.
      $this->_pledgeValues = [];
      if ($this->_ppID) {
        $payParams = ['id' => $this->_ppID];

        CRM_Pledge_BAO_Payment::retrieve($payParams, $this->_pledgeValues['pledgePayment']);
        $this->_pledgeID = CRM_Utils_Array::value('pledge_id', $this->_pledgeValues['pledgePayment']);
        $paymentStatusID = CRM_Utils_Array::value('status_id', $this->_pledgeValues['pledgePayment']);
        $this->_id = CRM_Utils_Array::value('contribution_id', $this->_pledgeValues['pledgePayment']);

        //get all status
        $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
        if (!($paymentStatusID == array_search('Pending', $allStatus) ||
            $paymentStatusID == array_search('Overdue', $allStatus)
          )) {
          CRM_Core_Error::fatal(ts("Pledge payment status should be 'Pending' or  'Overdue'."));
        }

        //get the pledge values associated with given pledge payment.

        $ids = [];
        $pledgeParams = ['id' => $this->_pledgeID];
        CRM_Pledge_BAO_Pledge::getValues($pledgeParams, $this->_pledgeValues, $ids);
        $this->assign('ppID', $this->_ppID);
      }
      else {
        // Not making a pledge payment, so check if pledge payment(s) are due for this contact so we can alert the user. CRM-5206
        if (isset($this->_contactID)) {
          $contactPledges = [];
          $contactPledges = CRM_Pledge_BAO_Pledge::getContactPledges($this->_contactID);

          if (!empty($contactPledges)) {
            $payments = $paymentsDue = NULL;
            $multipleDue = FALSE;
            foreach ($contactPledges as $key => $pledgeId) {
              $payments = CRM_Pledge_BAO_Payment::getOldestPledgePayment($pledgeId);
              if ($payments) {
                if ($paymentsDue) {
                  $multipleDue = TRUE;
                  break;
                }
                else {
                  $paymentsDue = $payments;
                }
              }
            }
            if ($multipleDue) {
              // Show link to pledge tab since more than one pledge has a payment due
              $pledgeTab = CRM_Utils_System::url('civicrm/contact/view',
                "reset=1&force=1&cid={$this->_contactID}&selectedChild=pledge"
              );
              CRM_Core_Session::setStatus(ts('This contact has pending or overdue pledge payments. <a href="%1">Click here to view their Pledges tab</a> and verify whether this contribution should be applied as a pledge payment.', [1 => $pledgeTab]));
            }
            elseif ($paymentsDue) {
              // Show user link to oldest Pending or Overdue pledge payment


              $ppAmountDue = CRM_Utils_Money::format($payments['amount']);
              $ppSchedDate = CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment', $payments['id'], 'scheduled_date'));
              if ($this->_mode) {
                $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                  "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge&mode=live"
                );
              }
              else {
                $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                  "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge"
                );
              }
              CRM_Core_Session::setStatus(ts('This contact has a pending or overdue pledge payment of %2 which is scheduled for %3. <a href="%1">Click here to apply this contribution as a pledge payment</a>.', [1 => $ppUrl, 2 => $ppAmountDue, 3 => $ppSchedDate]));
            }
          }
        }
      }
    }

    $this->_values = [];

    // current contribution id
    if ($this->_id) {

      // check for entity_financial_trxn linked to this contribution to see if it's an online contribution

      $fids = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnIds($this->_id, 'civicrm_contribution');
      $this->_online = $fids['entityFinancialTrxnId'];

      if ($this->_online) {
        $this->assign('isOnline', TRUE);
      }

      //to get Premium id
      $sql = "
SELECT *
FROM   civicrm_contribution_product
WHERE  contribution_id = {$this->_id}
";
      $dao = CRM_Core_DAO::executeQuery($sql,
        CRM_Core_DAO::$_nullArray
      );
      if ($dao->fetch()) {
        $this->_premiumID = $dao->id;
        $this->_productDAO = $dao;
      }
      $dao->free();

      $ids = [];
      $params = ['id' => $this->_id];

      CRM_Contribute_BAO_Contribution::getValues($params, $this->_values, $ids);

      //unset the honor type id:when delete the honor_contact_id
      //and edit the contribution, honoree infomation pane open
      //since honor_type_id is present
      if (!CRM_Utils_Array::value('honor_contact_id', $this->_values)) {
        unset($this->_values['honor_type_id']);
      }
      //to get note id

      $daoNote = new CRM_Core_BAO_Note();
      $daoNote->entity_table = 'civicrm_contribution';
      $daoNote->entity_id = $this->_id;
      if ($daoNote->find(TRUE)) {
        $this->_noteID = $daoNote->id;
        $this->_values['note'] = $daoNote->note;
      }

      $this->_contributionType = $this->_values['contribution_type_id'];

      $csParams = ['contribution_id' => $this->_id];
      $softCredit = CRM_Contribute_BAO_Contribution::getSoftContribution($csParams, TRUE);

      if (CRM_Utils_Array::value('soft_credit_to', $softCredit)) {
        $softCredit['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $softCredit['soft_credit_to'], 'sort_name'
        );
      }
      $this->_values['soft_credit_to'] = CRM_Utils_Array::value('sort_name', $softCredit);
      $this->_values['softID'] = CRM_Utils_Array::value('soft_credit_id', $softCredit);
      $this->_values['soft_contact_id'] = CRM_Utils_Array::value('soft_credit_to', $softCredit);

      $this->_values['pcp_made_through_id'] = CRM_Utils_Array::value('pcp_id', $softCredit);
      $this->_values['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $softCredit);
      $this->_values['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $softCredit);
      $this->_values['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $softCredit);

      //display check number field only if its having value or its offline mode.
      if (CRM_Utils_Array::value('payment_instrument_id',
          $this->_values
        ) == CRM_Core_OptionGroup::getValue('payment_instrument', 'Check', 'name')
        || CRM_Utils_Array::value('check_number', $this->_values)
      ) {
        $this->assign('showCheckNumber', TRUE);
      }

      // fetch current contribution detail
      $details = CRM_Contribute_BAO_Contribution::getComponentDetails([$this->_id]);
      $details = reset($details);
      if ($details['component'] == 'event' && !empty($details['participant'])) {
        $this->_participantId = $details['participant'];
        $this->_multiContribComponent = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_participant_payment WHERE participant_id = %1 AND contribution_id != %2", [
          1 => [$this->_participantId, 'Positive'],
          2 => [$this->_id, 'Positive'],
        ]);
        $this->assign('participantId', $this->_participantId);
      }
      elseif($details['membership']){
        $this->_membershipId = $details['membership'];
        $this->_multiContribComponent = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_membership_payment WHERE membership_id = %1 AND contribution_id != %2", [
          1 => [$this->_membershipId, 'Positive'],
          2 => [$this->_id, 'Positive'],
        ]);
        $this->assign('membershipId', $this->_membershipId);
      }
      $this->set('originalValues', $this->_values);
    }

    // when custom data is included in this page
    if (CRM_Utils_Array::value('hidden_custom', $_POST)) {
      $this->set('type', 'Contribution');
      $this->set('subType', CRM_Utils_Array::value('contribution_type_id', $_POST));
      $this->set('entityId', $this->_id);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }


    $this->_lineItems = [];
    if ($this->_id &&
      $priceSetId = CRM_Price_BAO_Set::getFor('civicrm_contribution', $this->_id)
    ) {
      $this->_priceSetId = $priceSetId;

      $this->_lineItems[] = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution');
    }
    $this->assign('lineItem', empty($this->_lineItems) ? FALSE : $this->_lineItems);
  }

  function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $defaults = $this->_values;
    foreach ($defaults as $k => $v) {
      if ($v === '0000-00-00 00:00:00') {
        unset($defaults[$k]);
      }
    }

    //set defaults for pledge payment.
    if ($this->_ppID) {
      $defaults['total_amount'] = CRM_Utils_Array::value('scheduled_amount', $this->_pledgeValues['pledgePayment']);
      $defaults['honor_type_id'] = CRM_Utils_Array::value('honor_type_id', $this->_pledgeValues);
      $defaults['honor_contact_id'] = CRM_Utils_Array::value('honor_contact_id', $this->_pledgeValues);
      $defaults['contribution_type_id'] = CRM_Utils_Array::value('contribution_type_id', $this->_pledgeValues);
      $defaults['option_type'] = 1;
    }

    if($this->_participantId){
      $defaults['participant_id'] = $this->_participantId;
    }
    if($this->_membershipId){
      $defaults['membership_id'] = $this->_membershipId;
    }

    $fields = [];
    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if ($this->_mode) {
      $billingFields = [];
      foreach ($this->_fields as $name => $dontCare) {
        if (strpos($name, 'billing_') === 0) {
          $name = $idName = substr($name, 8);
          if (in_array($name, ["state_province_id-$this->_bltID", "country_id-$this->_bltID"])) {
            $name = str_replace('_id', '', $name);
          }
          $billingFields[$name] = 'billing_' . $idName;
        }
        $fields[$name] = 1;
      }


      if ($this->_contactID) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactID, $fields, $defaults);
      }
      foreach ($billingFields as $name => $billingName) {
        $defaults[$billingName] = $defaults[$name];
      }

      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (!CRM_Utils_Array::value("billing_country_id-{$this->_bltID}", $defaults)) {
        $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
      }
    }

    if ($this->_id) {
      $this->_contactID = $defaults['contact_id'];
    }


    // fix the display of the monetary value, CRM-4038
    if (isset($defaults['total_amount'])) {
      $defaults['total_amount'] = CRM_Utils_Money::format($defaults['total_amount'], NULL, '%a');
    }

    if (isset($defaults['non_deductible_amount'])) {
      $defaults['non_deductible_amount'] = CRM_Utils_Money::format($defaults['non_deductible_amount'], NULL, '%a');
    }

    if (isset($defaults['fee_amount'])) {
      $defaults['fee_amount'] = CRM_Utils_Money::format($defaults['fee_amount'], NULL, '%a');
    }

    if (isset($defaults['net_amount'])) {
      $defaults['net_amount'] = CRM_Utils_Money::format($defaults['net_amount'], NULL, '%a');
    }

    if ($this->_contributionType) {
      $defaults['contribution_type_id'] = $this->_contributionType;
    }

    if (CRM_Utils_Array::value('is_test', $defaults)) {
      $this->assign('is_test', TRUE);
      $defaults['is_test'] = 1;
    }

    if (isset($defaults['honor_contact_id'])) {
      $honorDefault = $ids = [];
      $this->_honorID = $defaults['honor_contact_id'];
      $honorType = CRM_Core_PseudoConstant::honor();
      $idParams = ['id' => $defaults['honor_contact_id'],
        'contact_id' => $defaults['honor_contact_id'],
      ];
      CRM_Contact_BAO_Contact::retrieve($idParams, $honorDefault, $ids);

      $defaults['honor_prefix_id'] = CRM_Utils_Array::value('prefix_id', $honorDefault);
      $defaults['honor_first_name'] = CRM_Utils_Array::value('first_name', $honorDefault);
      $defaults['honor_last_name'] = CRM_Utils_Array::value('last_name', $honorDefault);
      $defaults['honor_email'] = CRM_Utils_Array::value('email', $honorDefault['email'][1]);
      $defaults['honor_type'] = $honorType[$defaults['honor_type_id']];
    }

    $this->assign('showOption', TRUE);
    // for Premium section
    if ($this->_premiumID) {
      $this->assign('showOption', FALSE);
      $options = $this->_options[$this->_productDAO->product_id] ?? "";
      if (!$options) {
        $this->assign('showOption', TRUE);
      }
      $options_key = CRM_Utils_Array::key($this->_productDAO->product_option, $options);
      if ($options_key) {
        $defaults['product_name'] = [$this->_productDAO->product_id, trim($options_key)];
      }
      else {
        $defaults['product_name'] = [$this->_productDAO->product_id];
      }
      if ($this->_productDAO->fulfilled_date) {
        list($defaults['fulfilled_date']) = CRM_Utils_Date::setDateDefaults($this->_productDAO->fulfilled_date);
      }
    }

    if (isset($this->userEmail)) {
      $this->assign('email', $this->userEmail);
    }

    if (CRM_Utils_Array::value('is_pay_later', $defaults)) {
      $this->assign('is_pay_later', TRUE);
    }
    $this->assign('contribution_status_id', CRM_Utils_Array::value('contribution_status_id', $defaults));

    $dates = ['receive_date', 'receipt_date', 'cancel_date', 'thankyou_date'];
    foreach ($dates as $key) {
      if (CRM_Utils_Array::value($key, $defaults)) {
        list($defaults[$key],
          $defaults[$key . '_time']
        ) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value($key, $defaults),
          'activityDateTime'
        );
      }
    }

    if (!$this->_id && !CRM_Utils_Array::value('receive_date', $defaults)) {
      list($defaults['receive_date'],
        $defaults['receive_date_time']
      ) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    }

    $this->assign('receive_date', CRM_Utils_Date::processDate(CRM_Utils_Array::value('receive_date', $defaults),
        CRM_Utils_Array::value('receive_date_time', $defaults)
      ));
    $this->assign('currency', CRM_Utils_Array::value('currency', $defaults));
    $this->assign('totalAmount', CRM_Utils_Array::value('total_amount', $defaults));

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $defaults['update_related_component'] = 0;
      if ($this->_participantId && !$this->_multiContribComponent) {
        $defaults['update_related_component'] = 1;
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
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    // build price set form.
    $buildPriceSet = FALSE;
    if (empty($this->_lineItems) &&
      ($this->_priceSetId || CRM_Utils_Array::value('price_set_id', $_POST))
    ) {
      $buildPriceSet = TRUE;
      $getOnlyPriceSetElements = TRUE;
      if (!$this->_priceSetId) {
        $this->_priceSetId = $_POST['price_set_id'];
        $getOnlyPriceSetElements = FALSE;
      }

      $this->set('priceSetId', $this->_priceSetId);

      CRM_Price_BAO_Set::buildPriceSet($this);

      // get only price set form elements.
      if ($getOnlyPriceSetElements) {
        return;
      }
    }
    // use to build form during form rule.
    $this->assign('buildPriceSet', $buildPriceSet);

    $showAdditionalInfo = FALSE;



    $defaults = $this->_values;
    $additionalDetailFields = ['note', 'thankyou_date', 'invoice_id', 'non_deductible_amount', 'fee_amount', 'net_amount'];
    foreach ($additionalDetailFields as $key) {
      if (!empty($defaults[$key])) {
        $defaults['hidden_AdditionalDetail'] = 1;
        break;
      }
    }

    $honorFields = ['honor_type_id', 'honor_prefix_id', 'honor_first_name',
      'honor_lastname', 'honor_email',
    ];
    foreach ($honorFields as $key) {
      if (!empty($defaults[$key])) {
        $defaults['hidden_Honoree'] = 1;
        break;
      }
    }

    //check for honoree pane.
    if ($this->_ppID && CRM_Utils_Array::value('honor_contact_id', $this->_pledgeValues)) {
      $defaults['hidden_Honoree'] = 1;
    }

    if ($this->_productDAO) {
      if ($this->_productDAO->product_id) {
        $defaults['hidden_Premium'] = 1;
      }
    }

    if ($this->_noteID &&
      isset($this->_values['note'])
    ) {
      $defaults['hidden_AdditionalDetail'] = 1;
    }

    $paneNames = [ts('Additional Details') => 'AdditionalDetail',
      ts('Honoree Information') => 'Honoree',
    ];

    //Add Premium pane only if Premium is exists.

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;

    if ($dao->find(TRUE)) {
      $paneNames[ts('Premium Information')] = 'Premium';
      $this->assign('havePremium', true);
    }
    else if (!empty($this->_id)) {
      $selectedProductId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionProduct', $this->_id, 'product_id', 'contribution_id' );
      if (!empty($selectedProductId)) {
        $paneNames[ts('Premium Information')] = 'Premium';
        $this->assign('havePremium', true);
      }
    }

    $ccPane = NULL;
    if ($this->_mode) {
      if (CRM_Utils_Array::value('payment_type', $this->_processors) & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
      ) {
        $ccPane = [ts('Direct Debit Information') => 'DirectDebit'];
      }
      else {
        $ccPane = [ts('Credit Card Information') => 'CreditCard'];
      }
    }
    if (is_array($ccPane)) {
      $paneNames = array_merge($ccPane, $paneNames);
    }

    foreach ($paneNames as $name => $type) {
      $urlParams = "snippet=4&formType={$type}";
      if ($this->_mode) {
        $urlParams .= "&mode={$this->_mode}";
      }

      $open = 'false';

      $allPanes[$name] = ['url' => CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams),
        'open' => $open,
        'id' => $type,
      ];

      // see if we need to include this paneName in the current form
      if ($this->_formType == $type ||
        CRM_Utils_Array::value("hidden_{$type}", $_POST) ||
        CRM_Utils_Array::value("hidden_{$type}", $defaults)
      ) {
        $showAdditionalInfo = TRUE;
      }

      if ($type == 'CreditCard') {
        $this->add('hidden', 'hidden_CreditCard', 1);
        CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
      }
      elseif ($type == 'DirectDebit') {
        $this->add('hidden', 'hidden_DirectDebit', 1);
        CRM_Core_Payment_Form::buildDirectDebit($this, TRUE);
      }
      else {
        $buildFunc = 'build' . $type;
        CRM_Contribute_Form_AdditionalInfo::$buildFunc( $this );
      }
    }

    $this->assign('allPanes', $allPanes);
    $this->assign('showAdditionalInfo', $showAdditionalInfo);

    if ($this->_formType) {
      $this->assign('formType', $this->_formType);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

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

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Contribution');
    $this->assign('customDataSubType', $this->_contributionType);
    $this->assign('entityID', $this->_id);

    if ($this->_context == 'standalone') {

      CRM_Contact_Form_NewContact::buildQuickForm($this);
    }
    elseif($this->_contactID) {
      $this->addElement('hidden', 'contact_preset_id', $this->_contactID);
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    if (CRM_Utils_Array::value('is_test', $defaults)) {
      $isTestOption = [0 => ts('No'), 1 => ts('Yes')];
      $isTestElement = $this->addRadio('is_test', ts('Is Test'), $isTestOption);
      $isTestElement->freeze();
    }

    $element = $this->add('select', 'contribution_type_id',
      ts('Contribution Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionType(NULL, NULL, TRUE),
      TRUE, ['onChange' => "buildCustomData( 'Contribution', this.value );"]
    );
    $deductibleType = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_deductible');
    $this->assign('deductible_type_ids', CRM_Utils_Array::implode(',', array_keys($deductibleType)));
    if ($this->_online) {
      // $element->freeze( );
    }
    if (!$this->_mode) {
      $element = $this->add('select', 'payment_instrument_id',
        ts('Paid By'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
      );

      if ($this->_online) {
        $element->freeze();
      }
    }

    $element = $this->add('text', 'trxn_id', ts('Transaction ID'), $attributes['trxn_id']);
    if ($this->_online) {
      $element->freeze();
    }
    else {
      $this->addRule('trxn_id',
        ts('This Transaction ID already exists in the database. Include the account number for checks.'),
        'objectExists',
        ['CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id']
      );
    }


    //add receipt for offline contribution
    $receiptEle = $this->addElement('checkbox', 'is_email_receipt', ts('Send Payment Notification').'?', NULL, [
      'onclick' => "showHideByValue('is_email_receipt',1,'from_email_address','block','radio',false);showHideByValue('is_email_receipt',1,'is_attach_receipt','block','radio',false);",
    ]);
    if (!empty($this->_contactID)) {
      $contactDetail = CRM_Contact_BAO_Contact::getContactDetails($this->_contactID);
      if (!empty($contactDetail[5])) {
        $receiptEle->freeze();
        $this->assign('do_not_notify', 1);
      }
    }

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_attach_receipt', ts('Email Receipt').'?');

    $haveAttachReceiptOption = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if (!empty($haveAttachReceiptOption)) {
      $this->assign('have_attach_receipt_option', 1);
    }

    // add mail from address select box
    $fromEmails = CRM_Contact_BAO_Contact_Utils::fromEmailAddress();
    if (!empty($this->_values['contribution_page_id'])) {
      $page = [];
      CRM_Contribute_BAO_ContributionPage::setValues($this->_values['contribution_page_id'], $page);
      if (!empty($page['receipt_from_email'])) {
        if ($page['receipt_from_name']) {
          $mailAddr = "{$page['receipt_from_name']} <{$page['receipt_from_email']}>";
        }
        else {
          $mailAddr = "{$page['receipt_from_email']}";
        }
        $pageFromAddress = [$mailAddr => htmlspecialchars($mailAddr)];
      }
    }
    $emails = [
      ts('Contribution Page') => $pageFromAddress,
    ];
    if (!empty($fromEmails['system'])) {
      $emails[ts('Default').' '.ts('(built-in)')] = $fromEmails['system'];
    }
    $emails[ts('Default')] = $fromEmails['default'];
    if (!empty($fromEmails['contact'])) {
      $emails[ts('Your Email')] = $fromEmails['contact'];
    }
    if (CRM_Core_Permission::check('access CiviContribute') && !empty($fromEmails['domain'])) {
      $emails[ts('Other')] = $fromEmails['domain'];
    }
    $this->addSelect('from_email_address', ts('FROM Email Address'), $emails);

    // add receipt id text area
    $receipt_attr = array_merge($attributes['receipt_id'], ['readonly' => 'readonly', 'class' => 'readonly']);
    $this->add('text', 'receipt_id', ts('Receipt ID'), $receipt_attr);
    $this->assign('receipt_id_setting', CRM_Utils_System::url("civicrm/admin/receipt", 'reset=1'));

    $status = CRM_Contribute_PseudoConstant::contributionStatus();
    unset($status[5]); // In Progress; For recurring
    unset($status[6]); // Expired; For recurring
    unset($status[7]); // Paused; For recurring
    // supressing contribution statuses that are NOT relevant to pledges (CRM-5169)
    if ($this->_ppID) {
      $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      foreach (['Cancelled', 'Failed', 'In Progress'] as $supress) {
        unset($status[CRM_Utils_Array::key($supress, $statusName)]);
      }
    }

    // if ($this->_action & CRM_Core_Action::UPDATE && $this->_updateRelatedStatus) {
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->addElement('checkbox', 'update_related_component', ts('Update status related to'));
    }
    else{
      $this->addElement('hidden', 'update_related_component', 0);
    }

    $this->add('select', 'contribution_status_id',
      ts('Contribution Status'),
      $status,
      FALSE, [
        'onClick' => "if (this.value != 3 && this.value != 4) {  status();} else return false",
        'onChange' => "return showHideByValue('contribution_status_id','3|4','cancelInfo','table-row','select',false);",
      ]
    );

    // add various dates
    $this->addDateTime('receive_date', ts('Received'), FALSE, ['formatType' => 'activityDateTime']);
    if ($this->_online) {
      // refs #9900
      //$this->assign( 'hideCalender', true );
    }
    $element = $this->add('text', 'check_number', ts('Check Number'), $attributes['check_number']);
    if ($this->_online) {
      $element->freeze();
    }

    $this->addDateTime('receipt_date', ts('Receipt Date'), FALSE, ['formatType' => 'activityDateTime']);
    if (!empty($this->_values['receipt_id'])) {
      $this->assign('receipt_id', $this->_values['receipt_id']);
      /*
      $this->getElement('receipt_date')->freeze();
      $this->getElement('receipt_date_time')->freeze();
      */
    }

    $this->addDateTime('cancel_date', ts('Cancelled or Failed Date'), FALSE, ['formatType' => 'activityDateTime']);

    $this->add('textarea', 'cancel_reason', ts('Cancelled or Failed Reason'), $attributes['cancel_reason']);

    $element = $this->add('select', 'payment_processor_id',
      ts('Payment Processor'),
      $this->_processors
    );
    if ($this->_online) {
      $element->freeze();
    }

    if (empty($this->_lineItems)) {
      $buildPriceSet = FALSE;

      $priceSets = CRM_Price_BAO_Set::getAssoc(FALSE, 'CiviContribute');
      if (!empty($priceSets) && !$this->_ppID) {
        $buildPriceSet = TRUE;
      }

      // don't allow price set for contribution if it is related to participant, or if it is a pledge payment
      // and if we already have line items for that participant. CRM-5095
      if ($buildPriceSet && $this->_id) {
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails([$this->_id]);
        $componentDetails = reset($componentDetails);
        $pledgePaymentId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment',
          $this->_id,
          'id',
          'contribution_id'
        );
        if ($pledgePaymentId) {
          $buildPriceSet = FALSE;
        }
        if ($participantID = CRM_Utils_Array::value('participant', $componentDetails)) {

          $participantLI = CRM_Price_BAO_LineItem::getLineItems($participantID);
          if (!CRM_Utils_System::isNull($participantLI)) {
            $buildPriceSet = FALSE;
          }
        }
      }

      $hasPriceSets = FALSE;
      if ($buildPriceSet) {
        $hasPriceSets = TRUE;
        $element = $this->add('select', 'price_set_id', ts('Choose price set'),
          ['' => ts('Choose price set')] + $priceSets,
          NULL, ['onchange' => "buildAmount( this.value );"]
        );
        if ($this->_online) {
          $element->freeze();
        }
      }
      $this->assign('hasPriceSets', $hasPriceSets);
      if ($this->_online || $this->_ppID) {

        $attributes['total_amount'] = array_merge($attributes['total_amount'], [
            'READONLY' => TRUE,
            'style' => "background-color:#EBECE4",
          ]);
        $optionTypes = ['1' => ts('Adjust Pledge Payment Schedule?'),
          '2' => ts('Adjust Total Pledge Amount?'),
        ];
        $element = $this->addRadio('option_type',
          NULL,
          $optionTypes,
          [], '<br/>'
        );
      }

      $element = $this->addMoney('total_amount',
        ts('Total Amount'),
        ($hasPriceSets) ? FALSE : TRUE,
        $attributes['total_amount'],
        TRUE
      );
    }

    $element = $this->add('text', 'source', ts('Source'), CRM_Utils_Array::value('source', $attributes));
    if ($this->_online) {
      $element->freeze();
    }

    $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
      "className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&reset=1&context=softcredit&id={$this->_id}",
      FALSE, NULL, FALSE
    );
    $this->assign('dataUrl', $dataUrl);
    $this->addElement('text', 'soft_credit_to', ts('Soft Credit To'));
    $this->addElement('hidden', 'soft_contact_id', '', ['id' => 'soft_contact_id']);

    // add form element for participant
    if ($this->_action & CRM_Core_Action::ADD) {
      if(!empty($this->_participantId)){
        $element = $this->add('text', 'participant_id', ts('Participant'));
        $element->freeze();
      }

      // add form element for membership
      if(!empty($this->_membershipId)){
        $element = $this->add('text', 'membership_id', ts('Membership'));
        $element->freeze();
      }
    }
    if (CRM_Utils_Array::value('soft_contact_id', $defaults) &&
      $this->_action & CRM_Core_Action::UPDATE
    ) {
      $ele = $this->addElement('select', 'pcp_made_through_id',
        ts('Personal Campaign Page'),
        ['' => ts('- select -')] +
        CRM_Contribute_PseudoConstant::pcPage()
      );
      $this->addElement('checkbox', 'pcp_display_in_roll', ts('Pcp Display In Roll'), NULL,
        ['onclick' => "return showHideByValue('pcp_display_in_roll','','nameID|nickID|personalNoteID','table-row','radio',false);"]
      );
      $this->addElement('text', 'pcp_roll_nickname', ts('Pcp Roll Nickname'));
      $this->addElement('textarea', 'pcp_personal_note', ts('Pcp Personal Note'));
    }

    $js = NULL;
    if (!$this->_mode) {
      $js = ['onclick' => "return verify( );",'data' => 'click-once'];
    }


    $mailingInfo = CRM_Core_BAO_Preferences::mailingPreferences();
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addButtons([
        ['type' => 'upload',
          'name' => ts('Save'),
          'js' => $js,
          'isDefault' => TRUE,
        ],
        ['type' => 'upload',
          'name' => ts('Save and New'),
          'js' => $js,
          'subName' => 'new',
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    $this->addFormRule(['CRM_Contribute_Form_Contribution', 'formRule'], $this);
    $this->assign('checkReceipt', TRUE);
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = [];

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    if (isset($fields['honor_type_id'])) {
      if (!((CRM_Utils_Array::value('honor_first_name', $fields) &&
            CRM_Utils_Array::value('honor_last_name', $fields)
          ) ||
          CRM_Utils_Array::value('honor_email', $fields)
        )) {
        $errors['honor_first_name'] = ts('Honor First Name and Last Name OR an email should be set.');
      }
    }

    //check for Credit Card Contribution.
    if ($self->_mode) {
      if (empty($fields['payment_processor_id'])) {
        $errors['payment_processor_id'] = ts('Payment Processor is a required field.');
      }
    }

    // do the amount validations.
    if (!CRM_Utils_Array::value('total_amount', $fields) && empty($self->_lineItems)) {
      if ($priceSetId = CRM_Utils_Array::value('price_set_id', $fields)) {

        CRM_Price_BAO_Field::priceSetValidation($priceSetId, $fields, $errors);
      }
    }

    //Check receipt exist or not
    $contributionId = $self->_id;
    if (!empty($fields['receipt_id'])) {
      $object = new CRM_Contribute_DAO_Contribution();
      $object->receipt_id = $fields['receipt_id'];
      if ($object->find(TRUE)) {
        $checkReceiptId = ($contributionId && $object->id == $contributionId) ? TRUE : FALSE;
        //If DB have exist receipt id then checkReceiptId would be FALSE.
        if (!$checkReceiptId) {
          $errors['receipt_id'] = ts('This Receipt ID already exists in the database.');
        }
      }
    }

    // Check receipt field empty or not.
    if (!empty($contributionId)) {
      $receiptId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',$contributionId, 'receipt_id');
      if (!empty($receiptId) && empty($fields['receipt_id'])) {
        if (!empty($fields['receipt_date']) || !empty($fields['receipt_date_time'])) {
          $errors['receipt_id'] = ts('Receipt ID can not be empty. Because Receipt Date Time and Receipt Date not empty.');
        }
      }
    }

    return $errors;
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

      CRM_Contribute_BAO_Contribution::deleteContribution($this->_id);
      return;
    }

    // get the submitted form values.
    $submittedValues = $this->controller->exportValues($this->_name);
    $originalValues = $this->get('originalValues');

    // process price set and get total amount and line items.
    $lineItem = [];
    $priceSetId = NULL;
    if ($priceSetId = CRM_Utils_Array::value('price_set_id', $submittedValues)) {

      CRM_Price_BAO_Set::processAmount($this->_priceSet['fields'],
        $submittedValues, $lineItem[$priceSetId]
      );
      $submittedValues['total_amount'] = $submittedValues['amount'];
    }
    if (!CRM_Utils_Array::value('total_amount', $submittedValues)) {
      $submittedValues['total_amount'] = $this->_values['total_amount'];
    }
    $this->assign('lineItem', !empty($lineItem) ? $lineItem : FALSE);

    if (CRM_Utils_Array::value('soft_credit_to', $submittedValues)) {
      $submittedValues['soft_credit_to'] = $submittedValues['soft_contact_id'];
    }

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $submittedValues)) {
      $this->_contactID = $submittedValues['contact_select_id'][1];
    }
    elseif (CRM_Utils_Array::value('contact_preset_id', $submittedValues)) {
      $this->_contactID = $submittedValues['contact_preset_id'];
    }

    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    //Credit Card Contribution.
    if ($this->_mode) {
      $unsetParams = ['trxn_id', 'payment_instrument_id', 'contribution_status_id',
        'receive_date', 'cancel_date', 'cancel_reason',
      ];
      foreach ($unsetParams as $key) {
        if (isset($submittedValues[$key])) {
          unset($submittedValues[$key]);
        }
      }

      //Get the rquire fields value only.
      $params = $this->_params = $submittedValues;


      $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($this->_params['payment_processor_id'],
        $this->_mode
      );


      $now = date('YmdHis');
      $fields = [];

      // we need to retrieve email address
      if ($this->_context == 'standalone' && CRM_Utils_Array::value('is_email_receipt', $submittedValues)) {

        list($this->userDisplayName,
          $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
        $this->assign('displayName', $this->userDisplayName);
      }

      //set email for primary location.
      $fields['email-Primary'] = 1;
      $params['email-Primary'] = $this->userEmail;

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);
      $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
      $fields["address_name-{$this->_bltID}"] = 1;

      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $this->_contactID,
        'contact_type'
      );

      $nameFields = ['first_name', 'middle_name', 'last_name'];
      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (CRM_Utils_Array::arrayKeyExists("billing_$name", $params)) {
          $params[$name] = $params["billing_{$name}"];
          $params['preserveDBName'] = TRUE;
        }
      }

      if (CRM_Utils_Array::value('source', $params)) {
        unset($params['source']);
      }
      $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, $fields,
        $this->_contactID,
        NULL, NULL,
        $ctype
      );

      // add all the additioanl payment params we need
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

      if ($this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD) {
        $this->_params['year'] = $this->_params['credit_card_exp_date']['Y'];
        $this->_params['month'] = $this->_params['credit_card_exp_date']['M'];
      }
      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $this->_params['total_amount'];
      $this->_params['amount_level'] = 0;
      $this->_params['currencyID'] = CRM_Utils_Array::value('currency',
        $this->_params,
        $config->defaultCurrency
      );
      $this->_params['payment_action'] = 'Sale';

      if (CRM_Utils_Array::value('soft_credit_to', $params)) {
        $this->_params['soft_credit_to'] = $params['soft_credit_to'];
        $this->_params['pcp_made_through_id'] = $params['pcp_made_through_id'];
      }

      $this->_params['pcp_display_in_roll'] = $params['pcp_display_in_roll'];
      $this->_params['pcp_roll_nickname'] = $params['pcp_roll_nickname'];
      $this->_params['pcp_personal_note'] = $params['pcp_personal_note'];

      //Add common data to formatted params
      if ($this->_honorID) {
        $params['honorID'] = $this->_honorID;
      }
      CRM_Contribute_Form_AdditionalInfo::postProcessCommon($params, $this->_params);

      if (empty($this->_params['invoice_id'])) {
        $this->_params['invoiceID'] = md5(uniqid((string)rand(), TRUE));
      }
      else {
        $this->_params['invoiceID'] = $this->_params['invoice_id'];
      }

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      $contributionType = new CRM_Contribute_DAO_ContributionType();
      $contributionType->id = $params['contribution_type_id'];
      if (!$contributionType->find(TRUE)) {
        CRM_Core_Error::fatal('Could not find a system table');
      }

      // add some contribution type details to the params list
      // if folks need to use it
      $paymentParams['contributionType_name'] = $this->_params['contributionType_name'] = $contributionType->name;
      $paymentParams['contributionType_accounting_code'] = $this->_params['contributionType_accounting_code'] = $contributionType->accounting_code;
      $paymentParams['contributionPageID'] = NULL;
      if (CRM_Utils_Array::value('is_email_receipt', $this->_params)) {
        $paymentParams['email'] = $this->userEmail;
      }

      $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);

      $result = $payment->doDirectPayment($paymentParams);

      if (is_a($result, 'CRM_Core_Error')) {
        //set the contribution mode.
        $urlParams = "action=add&cid={$this->_contactID}";
        if ($this->_mode) {
          $urlParams .= "&mode={$this->_mode}";
        }
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
      }

      $this->_params['receive_date'] = $now;

      if (CRM_Utils_Array::value('have_receipt', $this->_params) && empty($this->_params['receipt_date'])) {
        $this->_params['receipt_date'] = $now;
      }
      else {
        $this->_params['receipt_date'] = CRM_Utils_Date::processDate($this->_params['receipt_date'], $params['receipt_date_time'], TRUE);
      }

      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
      $this->assign('receive_date', CRM_Utils_Date::processDate($this->_params['receive_date'],
          $this->_params['receive_date_time']
        ));

      // result has all the stuff we need
      // lets archive it to a financial transaction
      if ($contributionType->is_deductible) {
        $this->assign('is_deductible', TRUE);
        $this->set('is_deductible', TRUE);
      }

      // set source if not set
      if (empty($this->_params['source'])) {
        $userID = $session->get('userID');
        $userSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userID,
          'sort_name'
        );
        $this->_params['source'] = ts('Submit Credit Card Payment by: %1', [1 => $userSortName]);
      }

      // build custom data getFields array
      $customFieldsContributionType = CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE,
        CRM_Utils_Array::value('contribution_type_id',
          $params
        )
      );
      $customFields = CRM_Utils_Array::arrayMerge($customFieldsContributionType,
        CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE, NULL, NULL, TRUE)
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $customFields,
        $this->_id,
        'Contribution'
      );


      $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($this,
        $this->_params,
        $result,
        $this->_contactID,
        $contributionType,
        FALSE, FALSE, FALSE
      );

      // process line items, until no previous line items.
      if (empty($this->_lineItems) && $contribution->id && !empty($lineItem)) {
        CRM_Contribute_Form_AdditionalInfo::processPriceSet($contribution->id, $lineItem);
      }

      //send receipt mail.
      if ($contribution->id &&
        CRM_Utils_Array::value('is_email_receipt', $this->_params)
      ) {
        $this->_params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $result);
        $this->_params['contact_id'] = $this->_contactID;
        $this->_params['contribution_id'] = $contribution->id;
        $this->_params['is_test'] = $contribution->is_test;
        $this->_params['contribution_page_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'contribution_page_id');
        $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $this->_params, TRUE);
      }

      //process the note
      if ($contribution->id && isset($params['note'])) {
        CRM_Contribute_Form_AdditionalInfo::processNote($params, $contactID, $contribution->id, NULL);
      }
      //process premium
      if ($contribution->id && isset($params['product_name'][0])) {
        CRM_Contribute_Form_AdditionalInfo::processPremium($params, $contribution->id, NULL, $this->_options);
        // pending contribution to restock
        if ($config->premiumIRManualCancel && ($submittedValues['contribution_status_id'] == 3 || $submittedValues['contribution_status_id'] == 4) ) {
          try{
            CRM_Contribute_BAO_Premium::restockPremiumInventory($contribution->id, ts('Manually restock by user change contribution status.'));
          }
          catch (Exception $e) {
            $errorMessage = "Failed to restock contribution ID {$contribution->id}: " . $e->getMessage();
            CRM_Core_Error::debug_log_message($errorMessage);
          }
        }
      }

      //update pledge payment status.
      if ($this->_ppID && $contribution->id) {
        //store contribution id in payment record.
        CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_Payment', $this->_ppID, 'contribution_id', $contribution->id);


        CRM_Pledge_BAO_Payment::updatePledgePaymentStatus($this->_pledgeID,
          [$this->_ppID],
          $contribution->contribution_status_id,
          NULL,
          $contribution->total_amount
        );
      }

      if ($contribution->id) {
        $statusMsg = ts('The contribution record has been processed.');
        if (CRM_Utils_Array::value('is_email_receipt', $this->_params) && $sendReceipt) {
          $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
        }
        CRM_Core_Session::setStatus($statusMsg);
      }
      //submit credit card contribution ends.
    }
    else {
      //Offline Contribution.
      $unsetParams = ['payment_processor_id', "email-{$this->_bltID}",
        'hidden_buildCreditCard', 'hidden_buildDirectDebit',
        'billing_first_name', 'billing_middle_name',
        'billing_last_name', 'street_address-5',
        "city-{$this->_bltID}", "state_province_id-{$this->_bltID}",
        "postal_code-{$this->_bltID}",
        "country_id-{$this->_bltID}",
        'credit_card_number', 'cvv2',
        'credit_card_exp_date', 'credit_card_type',
      ];
      foreach ($unsetParams as $key) {
        if (isset($submittedValues[$key])) {
          unset($submittedValues[$key]);
        }
      }

      // get the required field value only.
      $formValues = $submittedValues;
      $params = $ids = [];

      $params['contact_id'] = $this->_contactID;

      // get current currency from DB or use default currency
      $currentCurrency = CRM_Utils_Array::value('currency',
        $this->_values,
        $config->defaultCurrency
      );

      // use submitted currency if present else use current currency
      $params['currency'] = CRM_Utils_Array::value('currency',
        $submittedValues,
        $currentCurrency
      );

      $fields = [
        'contribution_type_id',
        'contribution_status_id',
        'payment_instrument_id',
        'cancel_reason',
        'source',
        'check_number',
        'soft_credit_to',
        'pcp_made_through_id',
        'pcp_display_in_roll',
        'pcp_roll_nickname',
        'pcp_personal_note',
        'receipt_id',
        'is_test',
      ];

      foreach ($fields as $f) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }
      if (!empty($params['pcp_made_through_id'])) {
        $params['pcp_id'] = $params['pcp_made_through_id'];
      }

      if ($softID = CRM_Utils_Array::value('softID', $this->_values)) {
        $params['softID'] = $softID;
      }
      //if priceset is used, no need to cleanup money
      //CRM-5740
      if ($priceSetId) {
        $params['skipCleanMoney'] = 1;
      }

      $dates = ['receive_date',
        'receipt_date',
        'cancel_date',
      ];

      foreach ($dates as $d) {
        $params[$d] = CRM_Utils_Date::processDate($formValues[$d], $formValues[$d . '_time'], TRUE);
      }

      if (CRM_Utils_Array::value('is_email_receipt', $formValues) && empty($params['receipt_date'])) {
        $params['receipt_date'] = date("Y-m-d");
      }

      if ($params['contribution_status_id'] == 3 || $params['contribution_status_id'] == 4) {
        if (CRM_Utils_System::isNull(CRM_Utils_Array::value('cancel_date', $params)) || CRM_Utils_Array::value('cancel_date', $params) == 'null') {
          $params['cancel_date'] = date("Y-m-d H:i:s");
        }
      }
      else {
        $params['cancel_date'] = $params['cancel_reason'] = 'null';
      }

      $ids['contribution'] = $params['id'] = $this->_id;

      //Add Additinal common information  to formatted params
      if ($this->_honorID) {
        $formValues['honorID'] = $this->_honorID;
      }
      CRM_Contribute_Form_AdditionalInfo::postProcessCommon($formValues, $params);

      //create contribution.

      $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);
      if(empty($this->_id)){
        $this->_id = $contribution->id;
      }

      // process line items, until no previous line items.
      if (empty($this->_lineItems) && $contribution->id && !empty($lineItem)) {
        CRM_Contribute_Form_AdditionalInfo::processPriceSet($contribution->id, $lineItem);
      }

      // process associated membership / participant, CRM-4395
      $relatedComponentStatusMsg = NULL;
      if ($submittedValues['update_related_component'] && $contribution->id && $this->_action & CRM_Core_Action::UPDATE) {
        $relatedComponentStatusMsg = $this->updateRelatedComponent($contribution->id,
          $contribution->contribution_status_id,
          CRM_Utils_Array::value('contribution_status_id',
            $this->_values
          )
        );
      }

      // add participant / membership record when create contribution
      if ( $contribution->id && $this->_action & CRM_Core_Action::ADD) {
        if ($submittedValues['participant_id']) {
          $paymentParticipant = [
            'participant_id' => $submittedValues['participant_id'],
            'contribution_id' => $contribution->id,
          ];
          $ids = [];
          CRM_Event_BAO_ParticipantPayment::create($paymentParticipant, $ids);
        }
        if ($submittedValues['membership_id']) {
          $ids = [];
          $mpDAO = new CRM_Member_DAO_MembershipPayment();
          $mpDAO->membership_id = $submittedValues['membership_id'];
          $mpDAO->contribution_id = $contribution->id;
          CRM_Utils_Hook::pre('create', 'MembershipPayment', NULL, $mpDAO);
          $mpDAO->save();
          CRM_Utils_Hook::post('create', 'MembershipPayment', $mpDAO->id, $mpDAO);
        }
      }

      //process  note
      if ($contribution->id && isset($formValues['note'])) {
        CRM_Contribute_Form_AdditionalInfo::processNote($formValues, $this->_contactID, $contribution->id, $this->_noteID);
      }

      //process premium
      if ($contribution->id && (isset($formValues['product_name'][0]) || $this->_premiumID)) {
        CRM_Contribute_Form_AdditionalInfo::processPremium($formValues, $contribution->id,
          $this->_premiumID, $this->_options
        );
      }
      if ($config->premiumIRManualCancel &&
        ($originalValues['contribution_status_id'] == 1 || $originalValues['contribution_status_id'] == 2) &&
        ($submittedValues['contribution_status_id'] == 3 || $submittedValues['contribution_status_id'] == 4)) {
        try{
          CRM_Contribute_BAO_Premium::restockPremiumInventory($contribution->id, ts('Manually restock by user change contribution status.'));
        }
        catch (Exception $e) {
          $errorMessage = "Failed to restock contribution ID {$contribution->id}: " . $e->getMessage();
          CRM_Core_Error::debug_log_message($errorMessage);
        }
      }

      //send receipt mail.
      if ($contribution->id && CRM_Utils_Array::value('is_email_receipt', $formValues)) {
        $formValues['contact_id'] = $this->_contactID;
        $formValues['contribution_id'] = $contribution->id;
        $formValues['contribution_page_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'contribution_page_id');
        $formValues['is_test'] = CRM_Core_DAO::getFieldValue("CRM_Contribute_DAO_Contribution", $contribution->id, 'is_test');
        $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $formValues);
      }

      $pledgePaymentId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment',
        $contribution->id,
        'id',
        'contribution_id'
      );
      //update pledge payment status.
      if ((($this->_ppID && $contribution->id) && $this->_action & CRM_Core_Action::ADD) ||
        (($pledgePaymentId) && $this->_action & CRM_Core_Action::UPDATE)
      ) {

        if ($this->_ppID) {
          //store contribution id in payment record.
          CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_Payment', $this->_ppID, 'contribution_id', $contribution->id);
        }
        else {
          $this->_ppID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment',
            $contribution->id,
            'id',
            'contribution_id'
          );
          $this->_pledgeID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment',
            $contribution->id,
            'pledge_id',
            'contribution_id'
          );
        }

        $adjustTotalAmount = FALSE;
        if (CRM_Utils_Array::value('option_type', $formValues) == 2) {
          $adjustTotalAmount = TRUE;
        }

        CRM_Pledge_BAO_Payment::updatePledgePaymentStatus($this->_pledgeID,
          [$this->_ppID],
          $contribution->contribution_status_id,
          NULL,
          $contribution->total_amount,
          $adjustTotalAmount
        );
      }

      $statusMsg = ts('The contribution record has been saved.');
      if (CRM_Utils_Array::value('is_email_receipt', $formValues) && $sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }

      if ($relatedComponentStatusMsg) {
        $statusMsg .= ' ' . $relatedComponentStatusMsg;
      }

      CRM_Core_Session::setStatus($statusMsg, FALSE);
      //Offline Contribution ends.
    }

    $buttonName = $this->controller->getButtonName();
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/add',
            'reset=1&action=add&context=standalone'
          ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
            "reset=1&cid={$this->_contactID}&selectedChild=contribute"
          ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
          "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}"
        ));
    }
  }

  /**
   * This function process contribution related objects.
   */
  function updateRelatedComponent($contributionId, $statusId, $previousStatusId = NULL) {
    $statusMsg = NULL;
    if (!$contributionId || !$statusId) {
      return $statusMsg;
    }

    $params = [
      'contribution_id' => $contributionId,
      'contribution_status_id' => $statusId,
      'previous_contribution_status_id' => $previousStatusId,
    ];


    $updateResult = CRM_Contribute_BAO_Contribution::transitionComponents($params);

    if (!is_array($updateResult) ||
      !($updatedComponents = CRM_Utils_Array::value('updatedComponents', $updateResult)) ||
      !is_array($updatedComponents) ||
      empty($updatedComponents)
    ) {
      return $statusMsg;
    }

    // get the user display name.
    $sql = "
   SELECT  display_name as displayName 
     FROM  civicrm_contact
LEFT JOIN  civicrm_contribution on (civicrm_contribution.contact_id = civicrm_contact.id )
    WHERE  civicrm_contribution.id = {$contributionId}";
    $userDisplayName = CRM_Core_DAO::singleValueQuery($sql);

    // get the status message for user.
    foreach ($updatedComponents as $componentName => $updatedStatusId) {

      if ($componentName == 'CiviMember') {

        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Member_PseudoConstant::membershipStatus()
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= ts("<br />Membership for %1 has been Cancelled.", [1 => $userDisplayName]);
        }
        elseif ($updatedStatusName == 'Expired') {
          $statusMsg .= ts("<br />Membership for %1 has been Expired.", [1 => $userDisplayName]);
        }
        elseif ($endDate = CRM_Utils_Array::value('membership_end_date', $updateResult)) {
          $statusMsg .= ts("<br />Membership for %1 has been updated. The membership End Date is %2.",
            [1 => $userDisplayName,
              2 => $endDate,
            ]
          );
        }
      }

      if ($componentName == 'CiviEvent') {

        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Event_PseudoConstant::participantStatus()
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= ts("<br />Event Registration for %1 has been Cancelled.", [1 => $userDisplayName]);
        }
        elseif ($updatedStatusName == 'Registered') {
          $statusMsg .= ts("<br />Event Registration for %1 has been updated.", [1 => $userDisplayName]);
        }
      }

      if ($componentName == 'CiviPledge') {

        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= ts("<br />Pledge Payment for %1 has been Cancelled.", [1 => $userDisplayName]);
        }
        elseif ($updatedStatusName == 'Failed') {
          $statusMsg .= ts("<br />Pledge Payment for %1 has been Failed.", [1 => $userDisplayName]);
        }
        elseif ($updatedStatusName == 'Completed') {
          $statusMsg .= ts("<br />Pledge Payment for %1 has been updated.", [1 => $userDisplayName]);
        }
      }
    }

    return $statusMsg;
  }
}

