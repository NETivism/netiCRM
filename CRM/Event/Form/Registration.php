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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */



/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration extends CRM_Core_Form {

  public $_totalParticipantCount;
  public $_usedOptionsDiscount;
  public $_totalDiscount;
  public $_coupon;
  public $_paymentProcessors;
  public $_isOnWaitlist;
  public $_waitlistMsg;
  /**
   * how many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  CONST LOCATION_BLOCKS = 1;

  /**
   * the id of the event we are proceessing
   *
   * @var int
   * @protected
   */
  public $_eventId;

  /**
   * the array of ids of all the participant we are proceessing
   *
   * @var array
   * @protected
   */
  protected $_participantIDS;

  /**
   * the id of the participant we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_participantId;

  /**
   * is participant able to walk registration wizard.
   *
   * @var Boolean
   * @protected
   */
  public $_allowConfirmation;

  /**
   * is participant requires approval
   *
   * @var Boolean
   * @public
   */
  public $_requireApproval;

  /**
   * is event configured for waitlist.
   *
   * @var Boolean
   * @public
   */
  public $_allowWaitlist;

  /**
   * store additional participant ids
   * when there are pre-registered.
   *
   * @var array
   * @public
   */
  public $_additionalParticipantIds;

  /**
   * the mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * the values for the contribution db object
   *
   * @var array
   * @protected
   */
  public $_values;

  /**
   * the paymentProcessor attributes for this page
   *
   * @var array
   * @protected
   */
  public $_paymentProcessor;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   * @public
   */
  public $_params;

  /**
   * The fields involved in this contribution page
   *
   * @var array
   * @protected
   */
  public $_fields;

  /**
   * The billing location id for this contribiution page
   *
   * @var int
   * @protected
   */
  public $_bltID;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   * @protected
   */
  public $_priceSetId = NULL;

  /**
   * Array of fields for the price set
   *
   * @var array
   * @protected
   */
  public $_priceSet;

  /**
   * The contribution mode.
   *
   * @var string
   * @protected
   */
  public $_contributeMode;

  /**
   * The infomations of participants.
   *
   * @var array
   * @protected
   */
  public $_participantInfo;


  public $_action;

  /* Is event already full.
     *
     * @var boolean
     * @protected
     */

  public $_isEventFull;

  public $_lineItem;
  public $_lineItemParticipantsCount;
  public $_availableRegistrations;
  protected $_uploadedFiles;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_eventId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);

    //CRM-4320
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->_bltID = $this->get('bltID');
    $this->_paymentProcessor = $this->get('paymentProcessor');
    $this->_priceSetId = $this->get('priceSetId');
    $this->_priceSet = $this->get('priceSet');
    $this->_lineItem = $this->get('lineItem');
    $this->_isEventFull = $this->get('isEventFull');
    $this->_lineItemParticipantsCount = $this->get('lineItemParticipants');
    if (!is_array($this->_lineItem)) {
      $this->_lineItem = [];
    }
    if (!is_array($this->_lineItemParticipantsCount)) {
      $this->_lineItemParticipantsCount = [];
    }
    $this->_availableRegistrations = $this->get('availableRegistrations');
    $this->_totalParticipantCount = $this->get('totalParticipantcount');

    //check if participant allow to walk registration wizard.
    $this->_allowConfirmation = $this->get('allowConfirmation');

    // check for Approval
    $this->_requireApproval = $this->get('requireApproval');

    // check for waitlisting.
    $this->_allowWaitlist = $this->get('allowWaitlist');

    //get the additional participant ids.
    $this->_additionalParticipantIds = $this->get('additionalParticipantIds');

    // For coupon
    $this->_usedOptionsDiscount = $this->get('usedOptionsDiscount');
    $this->_totalDiscount = $this->get('totalDiscount');
    $this->_coupon = $this->get('coupon');

    $config = CRM_Core_Config::singleton();

    if (!$this->_values) {
      // create redirect URL to send folks back to event info page is registration not available
      $infoUrl = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}",
        FALSE, NULL, FALSE, TRUE
      );

      // this is the first time we are hitting this, so check for permissions here
      if (!CRM_Core_Permission::event(CRM_Core_Permission::EDIT,
          $this->_eventId
        )) {
         return CRM_Core_Error::statusBounce(ts('You do not have permission to register for this event'), $infoUrl);
      }

      // get all the values from the dao object
      $this->_values = [];
      $this->_fields = [];

      // get the participant values, CRM-4320
      $this->_allowConfirmation = FALSE;
      if ($this->_participantId) {

        $ids = $participantValues = [];
        $participantParams = ['id' => $this->_participantId];

        CRM_Event_BAO_Participant::getValues($participantParams, $participantValues, $ids);
        $this->_values['participant'] = $participantValues[$this->_participantId];

        //allow pending status class walk registration wizard.

        if (CRM_Utils_Array::arrayKeyExists($participantValues[$this->_participantId]['status_id'],
            CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'")
          )) {
          $this->_allowConfirmation = TRUE;
          $this->set('allowConfirmation', TRUE);
        }
      }

      //retrieve event information

      $params = ['id' => $this->_eventId];
      CRM_Event_BAO_Event::retrieve($params, $this->_values['event']);
      $this->_values['event']['event_type'] = CRM_Event_PseudoConstant::eventType($this->_values['event']['event_type_id']);


      //check for additional participants.
      if ($this->_allowConfirmation && $this->_values['event']['is_multiple_registrations']) {
        $additionalParticipantIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_participantId);
        $cnt = 1;
        foreach ($additionalParticipantIds as $additionalParticipantId) {
          $this->_additionalParticipantIds[$cnt] = $additionalParticipantId;
          $cnt++;
        }
        $this->set('additionalParticipantIds', $this->_additionalParticipantIds);
      }

      $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId);
      $this->_allowWaitlist = FALSE;
      $this->_isEventFull = FALSE;
      if ($eventFull && !$this->_allowConfirmation) {
        $this->_isEventFull = TRUE;
        //lets redirecting to info only when to waiting list.
        $this->_allowWaitlist = CRM_Utils_Array::value('has_waitlist', $this->_values['event']);
        if (!$this->_allowWaitlist) {
          CRM_Utils_System::redirect($infoUrl);
        }
      }
      $this->set('isEventFull', $this->_isEventFull);
      $this->set('allowWaitlist', $this->_allowWaitlist);

      //check for require requires approval.
      $this->_requireApproval = FALSE;
      if (CRM_Utils_Array::value('requires_approval', $this->_values['event']) && !$this->_allowConfirmation) {
        $this->_requireApproval = TRUE;
      }
      $this->set('requireApproval', $this->_requireApproval);

      // also get the accounting code
      if (CRM_Utils_Array::value('contribution_type_id', $this->_values['event'])) {
        $this->_values['event']['accountingCode'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
          $this->_values['event']['contribution_type_id'],
          'accounting_code'
        );
      }

      if (isset($this->_values['event']['default_role_id'])) {

        $participant_role = CRM_Core_OptionGroup::values('participant_role');
        $this->_values['event']['participant_role'] = $participant_role["{$this->_values['event']['default_role_id']}"];
      }

      // is the event active (enabled)?
      $is_test = $this->_action & CRM_Core_Action::PREVIEW;
      if (!$this->_values['event']['is_active'] && !$is_test) {
        // form is inactive, die a fatal death
         return CRM_Core_Error::statusBounce(ts('The event you requested is currently unavailable (contact the site administrator for assistance).'));
      }

      // is online registration is enabled?
      if (!$this->_values['event']['is_online_registration'] && !$is_test) {
         return CRM_Core_Error::statusBounce(ts('Online registration is not currently available for this event (contact the site administrator for assistance).'), $infoUrl);
      }

      // is this an event template ?
      if (CRM_Utils_Array::value('is_template', $this->_values['event'])) {
         return CRM_Core_Error::statusBounce(ts('Event templates are not meant to be registered.'), $infoUrl);
      }

      $now = date('YmdHis');
      $startDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('registration_start_date', $this->_values['event']));

      if ($startDate &&  $startDate >= $now && !$is_test) {
         return CRM_Core_Error::statusBounce(ts('Registration for this event begins on %1', [1 => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('registration_start_date', $this->_values['event']))]), $infoUrl);
      }

      $endDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('registration_end_date', $this->_values['event']));
      if ($endDate && $endDate < $now && !$is_test) {
         return CRM_Core_Error::statusBounce(ts('Registration for this event ended on %1', [1 => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('registration_end_date', $this->_values['event']))]), $infoUrl);
      }


      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values['event']);

      //retrieve custom information
      $eventID = $this->_eventId;

      $isPayLater = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'is_pay_later');
      //check for variour combination for paylater, payment
      //process with paid event.
      if ($isMonetary &&
        (!$isPayLater || CRM_Utils_Array::value('payment_processor', $this->_values['event']))
      ) {
        $ppID = CRM_Utils_Array::value('payment_processor',
          $this->_values['event']
        );
        if (!$ppID) {
           return CRM_Core_Error::statusBounce(ts('A payment processor must be selected for this event registration page, or the event must be configured to give users the option to pay later.'), $infoUrl);
        }
        $ppIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ppID);


        $this->_paymentProcessors = CRM_Core_BAO_PaymentProcessor::getPayments($ppIds, $this->_mode);
        $this->set('paymentProcessors', $this->_paymentProcessors);

        //set default payment processor
        if (!empty($this->_paymentProcessors) && empty($this->_paymentProcessor)) {
          foreach ($this->_paymentProcessors as $ppId => $values) {
            if ($values['is_default'] == 1 || (count($this->_paymentProcessors) == 1)) {
              $defaultProcessorId = $ppId;
              break;
            }
          }
        }

        if (isset($defaultProcessorId)) {
          $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($defaultProcessorId, $this->_mode);
          $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);
        }

        // make sure we have a valid payment class, else abort
        if ($this->_values['event']['is_monetary']) {
          if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
            foreach ($this->_paymentProcessors as $eachPaymentProcessor) {

              // check selected payment processor is active
              if (!$eachPaymentProcessor) {
                 return CRM_Core_Error::statusBounce(ts('The site administrator must set a Payment Processor for this event in order to use online registration.'));
              }

              // ensure that processor has a valid config
              $payment = CRM_Core_Payment::singleton($this->_mode, $eachPaymentProcessor, $this);
              $error = $payment->checkConfig();
              if (!empty($error)) {
                CRM_Core_Error::fatal($error);
              }
            }
          }
        }
      }

      //init event fee.
      self::initEventFee($this, $eventID);

      // get the profile ids

      $ufJoinParams = ['entity_table' => 'civicrm_event',
        // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
        'module' => 'CiviEvent',
        'entity_id' => $this->_eventId,
      ];
      list($this->_values['custom_pre_id'],
        $this->_values['custom_post_id']
      ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      // set profiles for additional participants
      if ($this->_values['event']['is_multiple_registrations']) {

        $ufJoinParams = ['entity_table' => 'civicrm_event',
          // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
          'module' => 'CiviEvent_Additional',
          'entity_id' => $this->_eventId,
        ];
        list($this->_values['additional_custom_pre_id'],
          $this->_values['additional_custom_post_id'], $preActive, $postActive
        ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

        // CRM-4377: we need to maintain backward compatibility, hence if there is profile for main contact
        // set same profile for additional contacts.
        if ($this->_values['custom_pre_id'] && !$this->_values['additional_custom_pre_id']) {
          $this->_values['additional_custom_pre_id'] = $this->_values['custom_pre_id'];
        }

        if ($this->_values['custom_post_id'] && !$this->_values['additional_custom_post_id']) {
          $this->_values['additional_custom_post_id'] = $this->_values['custom_post_id'];
        }

        // now check for no profile condition, in that case is_active = 0
        if (isset($preActive) && !$preActive) {
          unset($this->_values['additional_custom_pre_id']);
        }

        if (isset($postActive) && !$postActive) {
          unset($this->_values['additional_custom_post_id']);
        }
      }

      $params = ['id' => $this->_eventId];

      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
      $this->_bltID = array_search('Billing', $locationTypes);
      if (!$this->_bltID) {
         return CRM_Core_Error::statusBounce(ts('Please set a location type of %1', [1 => 'Billing']));
      }
      $this->set('bltID', $this->_bltID);

      if ($this->_values['event']['is_monetary'] &&
        ($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_FORM)
      ) {

        CRM_Core_Payment_Form::setCreditCardFields($this);
      }

      $params = ['entity_id' => $this->_eventId, 'entity_table' => 'civicrm_event'];

      $this->_values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);

      $this->_availableRegistrations = CRM_Event_BAO_Participant::eventFull($this->_values['event']['id'], TRUE);
      $this->set('availableRegistrations', $this->_availableRegistrations);
    }

    $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);

    // check if this is a paypal auto return and redirect accordingly
    if (CRM_Core_Payment::paypalRedirect($this->_paymentProcessor)) {
      $url = CRM_Utils_System::url('civicrm/event/register',
        "_qf_ThankYou_display=1&qfKey={$this->controller->_key}"
      );
      CRM_Utils_System::redirect($url);
    }

    $this->_contributeMode = $this->get('contributeMode');
    $this->assign('contributeMode', $this->_contributeMode);

    // setting CMS page title
    CRM_Utils_System::setTitle($this->_values['event']['title']);
    $this->assign('title', $this->_values['event']['title']);

    $this->assign('paidEvent', $this->_values['event']['is_monetary']);

    // we do not want to display recently viewed items on Registration pages
    $this->assign('displayRecent', FALSE);
    // Registration page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    // assign all event properties so wizard templates can display event info.
    $this->assign('event', $this->_values['event']);
    $this->assign('location', $this->_values['location']);
    $this->assign('bltID', $this->_bltID);
    $isShowLocation = CRM_Utils_Array::value('is_show_location', $this->_values['event']);
    $this->assign('isShowLocation', $isShowLocation);

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values['event'],
      $config->defaultCurrency
    );

    $this->track();
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
    //process only primary participant params
    $this->_params = $this->get('params');
    if (isset($this->_params[0])) {
      $params = $this->_params[0];
    }
    $name = '';
    if (CRM_Utils_Array::value('billing_first_name', $params)) {
      $name = $params['billing_first_name'];
    }

    if (CRM_Utils_Array::value('billing_middle_name', $params)) {
      $name .= " {$params['billing_middle_name']}";
    }

    if (CRM_Utils_Array::value('billing_last_name', $params)) {
      $name .= " {$params['billing_last_name']}";
    }
    $this->assign('billingName', $name);
    $this->set('name', $name);

    $vars = ['amount', 'currencyID', 'credit_card_type',
      'trxn_id', 'amount_level', 'receive_date',
    ];

    foreach ($vars as $v) {
      if (CRM_Utils_Array::value($v, $params)) {
        if ($v == 'receive_date') {
          $this->assign($v, CRM_Utils_Date::mysqlToIso($params[$v]));
        }
        else {
          $this->assign($v, $params[$v]);
        }
      }
      elseif (CRM_Utils_Array::value('amount', $params) == 0) {
        $this->assign($v, CRM_Utils_Array::value($v, $params));
      }
    }

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
      if (isset($params['billing_' . $part])) {
        $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $params);
      }
    }


    $this->assign('address', CRM_Utils_Address::format($addressFields));

    if ($this->_contributeMode == 'direct' &&
      !CRM_Utils_Array::value('is_pay_later', $params)
    ) {
      $date = CRM_Utils_Date::format(CRM_Utils_Array::value('credit_card_exp_date', $params));
      $date = CRM_Utils_Date::mysqlToIso($date);
      $this->assign('credit_card_exp_date', $date);
      $this->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $params))
      );
    }

    $this->assign('email', $this->controller->exportValue('Register', "email-{$this->_bltID}"));

    // assign is_email_confirm to templates
    if (isset($this->_values['event']['is_email_confirm'])) {
      $this->assign('is_email_confirm', $this->_values['event']['is_email_confirm']);
    }

    // assign pay later stuff
    $params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
    $this->assign('is_pay_later', $params['is_pay_later']);
    if ($params['is_pay_later']) {
      $this->assign('pay_later_text', $this->_values['event']['pay_later_text']);
      $this->assign('pay_later_receipt', $this->_values['event']['pay_later_receipt']);
    }
  }

  /**
   * Function to add the custom fields
   *
   * @return None
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE) {
    $stateCountryMap = $fields = [];

    if ($id) {
      $button = substr($this->controller->getButtonName(), -4);


      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');

      // we don't allow conflicting fields to be
      // configured via profile
      $fieldsToIgnore = [
        'participant_fee_amount' => 1,
        'participant_fee_level' => 1,
        'participant_status_id' => 1,
        'participant_register_date' => 1,
        'participant_registered_by_id' => 1,
        'participant_fee_currency' => 1,
        'participant_status' => 1,
        'participant_role' => 1,
        'event_type' => 1,
      ];
      if ($contactID) {
        if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
      }

      if (is_array($fields)) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-') {
            if(!$this->isAssigned('moveEmail')){
              $this->assign('moveEmail', 'profile-group-'.$fields[$fieldName]['group_id']);
            }
            unset($fields[$fieldName]);
          }
        }
      }

      if (array_intersect_key($fields, $fieldsToIgnore)) {
        $fields = array_diff_key($fields, $fieldsToIgnore);
        if (CRM_Core_Permission::check('access CiviEvent')) {
          CRM_Core_Session::setStatus(ts("Some of the profile fields cannot be configured for this page."));
        }
      }

      foreach ($fields as $key => $field) {
        if(strstr($key, 'custom_')){
          $field_id = explode('custom_', $key);
          $field_id = $field_id[1];

          $sql = "SELECT f.custom_group_id FROM civicrm_custom_field f WHERE f.id =$field_id";
          $custom_group_id = CRM_Core_DAO::singleValueQuery($sql);
          $group_id = $custom_group_id ;
          $group = new CRM_Core_DAO_CustomGroup();
          $group->id = $group_id;
          $group->find(TRUE);
          if($group->extends == 'Participant' && !empty($group->extends_entity_column_value )){

            $extends_entity_column_values = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,$group->extends_entity_column_value);
            if($group->extends_entity_column_id == 1){
              // for participant_role

              $sql = "SELECT v.value FROM civicrm_option_value v INNER JOIN civicrm_option_group g ON v.option_group_id = g.id WHERE g.name = 'participant_role' AND v.label = '{$this->_values['event']['participant_role']}'";
              $participant_role = CRM_Core_DAO::singleValueQuery($sql);
              if( !in_array($participant_role, $extends_entity_column_values)){
                unset($fields[$key]);
              }
            }else if($group->extends_entity_column_id == 2
              && !in_array($this->_values['event']['id'], $extends_entity_column_values) ){
              // for event_id
              unset($fields[$key]);
            }else if($group->extends_entity_column_id == 3
              && !in_array($this->_values['event']['event_type_id'], $extends_entity_column_values) ){
              // for event_type
              unset($fields[$key]);
            }
          }
        }
      }

      $addCaptcha = FALSE;
      $fields = array_diff_assoc($fields, $this->_fields);
      $this->assign($name, $fields);
      if (is_array($fields)) {
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // change file upload description
            $this->_uploadedFiles[$key] = $field['name'];
          }
          if ($contactID && $field['data_type'] == 'File' && $field['is_required'] && $this->_allowConfirmation) {
            $field['is_required'] = FALSE;
          }
          //make the field optional if primary participant
          //have been skip the additional participant.
          if ($button == 'skip') {
            $field['is_required'] = FALSE;
          }
          elseif ($field['add_captcha']) {
            // only add captcha for first page
            $addCaptcha = TRUE;
          }

          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($prefixName == 'state_province' || $prefixName == 'country') {
            if (!CRM_Utils_Array::arrayKeyExists($index, $stateCountryMap)) {
              $stateCountryMap[$index] = [];
            }
            $stateCountryMap[$index][$prefixName] = $key;
          }

          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

          $this->_fields[$key] = $field;
        }
      }

      CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

      if ($addCaptcha &&
        !$viewOnly
      ) {

        $captcha = &CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign("isCaptcha", TRUE);
      }
    }
  }

  static function initEventFee(&$form, $eventID) {
    // get price info

    $price = CRM_Price_BAO_Set::initSet($form, $eventID, 'civicrm_event');

    if ($price == FALSE) {

      CRM_Core_OptionGroup::getAssoc("civicrm_event.amount.{$eventID}", $form->_values['fee'], TRUE);


      $discountedEvent = CRM_Core_BAO_Discount::getOptionGroup($eventID, "civicrm_event");
      if (is_array($discountedEvent)) {
        foreach ($discountedEvent as $key => $optionGroupId) {
          $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupId);
          CRM_Core_OptionGroup::getAssoc($name, $form->_values['discount'][$key], TRUE);
          $form->_values['discount'][$key]["name"] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupId, 'label');;
        }
      }
    }

    $eventFee = CRM_Utils_Array::value('fee', $form->_values);
    if (!is_array($eventFee) || empty($eventFee)) {
      $form->_values['fee'] = [];
    }

    //fix for non-upgraded price sets.CRM-4256.
    if (isset($form->_isPaidEvent)) {
      $isPaidEvent = $form->_isPaidEvent;
    }
    else {
      $isPaidEvent = CRM_Utils_Array::value('is_monetary', $form->_values['event']);
    }
    if ($isPaidEvent && empty($form->_values['fee'])) {
       return CRM_Core_Error::statusBounce(ts('No Fee Level(s) or Price Set is configured for this event.<br />Click <a href=\'%1\'>CiviEvent >> Manage Event >> Configure >> Event Fees</a> to configure the Fee Level(s) or Price Set for this event.', [1 => CRM_Utils_System::url('civicrm/event/manage/fee', 'reset=1&action=update&id=' . $form->_eventId)]));
    }
  }

  /**
   * Function to handle  process after the confirmation of payment by User
   *
   * @return None
   * @access public
   */
  function confirmPostProcess($contactID = NULL, $contribution = NULL, $payment = NULL) {
    // add/update contact information
    $fields = [];
    unset($this->_params['note']);

    //to avoid conflict overwrite $this->_params
    $this->_params = $this->get('value');

    // create CMS user
    if (CRM_Utils_Array::value('cms_create_account', $this->_params)) {
      $this->_params['contactID'] = $contactID;

      $mail = 'email-'.$this->_bltID;

      // we should use primary email for
      // 1. free event registration.
      // 2. pay later participant.
      // 3. waiting list participant.
      // 4. require approval participant.
      if (!CRM_Core_BAO_CMSUser::create($this->_params, $mail)) {
         return CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
    //get the amount of primary participant
    if (CRM_Utils_Array::value('is_primary', $this->_params)) {
      $this->_params['fee_amount'] = $this->get('primaryParticipantAmount');
    }

    if(!empty($contribution)){
      $dao = CRM_Coupon_BAO_Coupon::getCouponUsedBy([$contribution->id], 'contribution_id');
      $dao->fetch();
      if ($dao->N > 0) {
        $coupon = [];
        foreach($dao as $idx => $value) {
          if ($idx[0] != '_') {
            $coupon[$idx] = $value;
          }
        }
        $this->_params['coupon'] = $coupon;
      }
    }

    // add participant record
    $participant = $this->addParticipant($this->_params, $contactID);
    $this->_participantIDS[] = $participant->id;

    //setting register_by_id field and primaryContactId
    if (CRM_Utils_Array::value('is_primary', $this->_params)) {
      $this->set('registerByID', $participant->id);
      $this->set('primaryContactId', $contactID);
      $this->track('payment');
    }

    CRM_Core_BAO_CustomValueTable::postProcess($this->_params,
      CRM_Core_DAO::$_nullArray,
      'civicrm_participant',
      $participant->id,
      'Participant'
    );

    $createPayment = ($this->_params['amount'] != 0) ? TRUE : FALSE;
    // force to create zero amount payment, CRM-5095
    if (!$createPayment && $contribution->id
      && ($this->_params['amount'] == 0)
      && $this->_priceSetId && $this->_lineItem
    ) {
      $createPayment = TRUE;
    }
    
    $coupon = $this->get('coupon');
    if (!$createPayment && $contribution->id
    && ($this->_params['amount'] == 0)
    && !empty($coupon)) {
      $createPayment = TRUE;
    }

    if (!empty($contribution->id) && $this->_values['event']['is_monetary'] &&
      CRM_Utils_Array::value('contributionID', $this->_params)
    ) {

      $paymentParams = ['participant_id' => $participant->id,
        'contribution_id' => $contribution->id,
      ];
      $ids = [];
      $paymentPartcipant = CRM_Event_BAO_ParticipantPayment::create($paymentParams, $ids);
    }

    //set only primary participant's params for transfer checkout.
    if (($this->_contributeMode == 'checkout' || $this->_contributeMode == 'notify' || $this->_contributeMode == 'iframe')
      && CRM_Utils_Array::value('is_primary', $this->_params)
    ) {
      if (!empty($contribution->payment_processor_id)) {
        $this->_params['payment_processor_id'] = $contribution->payment_processor_id;
      }
      $this->_params['participantID'] = $participant->id;
      $this->set('primaryParticipant', $this->_params);
    }
    $this->assign('action', $this->_action);
  }

  /**
    *Function to process Registration of free event
    *
    *@param  array $param Form valuess 
    *@param  int contactID
    *
    *@return None
    *access public
    *
    */

  public function processRegistration($params, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    $this->_participantInfo = [];

    // CRM-4320, lets build array of cancelled additional participant ids
    // those are drop or skip by primary at the time of confirmation.
    // get all in and then unset those are confirmed.
    $cancelledIds = $this->_additionalParticipantIds;

    $participantCount = [];
    foreach ($params as $participantNum => $record) {
      if ($record == 'skip') {
        $participantCount[$participantNum] = 'skip';
      }
      elseif ($participantNum) {
        $participantCount[$participantNum] = 'participant';
      }
    }

    foreach ($params as $key => $value) {
      if ($value != 'skip') {
        $fields = NULL;

        // setting register by Id and unset contactId.
        if (!CRM_Utils_Array::value('is_primary', $value)) {
          $contactID = NULL;
          $registerByID = $this->get('registerByID');
          if ($registerByID) {
            $value['registered_by_id'] = $registerByID;
          }
          if (CRM_Utils_Array::value("email-{$this->_bltID}", $value)) {
            $this->_participantInfo[] = $value["email-{$this->_bltID}"];
          }
          else {
            $this->_participantInfo[] = $value['first_name'] . ' ' . $value['last_name'];
          }
        }


        $this->fixLocationFields($value, $fields);

        $contactID = $this->updateContactFields($contactID, $value, $fields);
        if (CRM_Utils_Array::value('is_primary', $value)) {
          $this->set('participantContactID', $contactID);
        }

        // lets store the contactID in the session
        // we dont store in userID in case the user is doing multiple
        // transactions etc
        // for things like tell a friend
        if (!$this->getContactID() && CRM_Utils_Array::value('is_primary', $value)) {
          $session->set('transaction.userID', $contactID);
        }

        //lets get the status if require approval or waiting.
        $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
        if ($this->_isOnWaitlist && !$this->_allowConfirmation) {
          $value['participant_status_id'] = array_search('On waitlist', $waitingStatuses);
        }
        elseif ($this->_requireApproval && !$this->_allowConfirmation) {
          $value['participant_status_id'] = array_search('Awaiting approval', $waitingStatuses);
        }

        $this->set('value', $value);
        $this->confirmPostProcess($contactID, NULL, NULL);

        //lets get additional participant id to cancel.
        if ($this->_allowConfirmation && is_array($cancelledIds)) {
          $additonalId = CRM_Utils_Array::value('participant_id', $value);
          if ($additonalId && $key = array_search($additonalId, $cancelledIds)) {
            unset($cancelledIds[$key]);
          }
        }
      }
    }

    // update status and send mail to cancelled additonal participants, CRM-4320
    if ($this->_allowConfirmation && is_array($cancelledIds) && !empty($cancelledIds)) {


      $cancelledId = array_search('Cancelled',
        CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'")
      );
      CRM_Event_BAO_Participant::transitionParticipants($cancelledIds, $cancelledId);
    }

    //set information about additional participants if exists
    if (count($this->_participantInfo)) {
      $this->set('participantInfo', $this->_participantInfo);
    }

    //send mail Confirmation/Receipt

    if ($this->_contributeMode != 'checkout' ||
      $this->_contributeMode != 'notify'
    ) {
      $isTest = FALSE;
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $isTest = TRUE;
      }

      //handle if no additional participant.
      if (!$registerByID) {
        $registerByID = $this->get('registerByID');
      }
      $primaryContactId = $this->get('primaryContactId');

      //build an array of custom profile and assigning it to template.
      $additionalIDs = CRM_Event_BAO_Event::buildCustomProfile($registerByID, NULL,
        $primaryContactId, $isTest, TRUE
      );

      //lets carry all paticipant params w/ values.
      foreach ($additionalIDs as $participantID => $contactId) {
        $participantNum = NULL;
        if ($participantID == $registerByID) {
          $participantNum = 0;
        }
        else {
          if ($participantNum = array_search('participant', $participantCount)) {
            unset($participantCount[$participantNum]);
          }
        }
        if ($participantNum === NULL)
        break;

        //carry the participant submitted values.
        $this->_values['params'][$participantID] = $params[$participantNum];
      }

      //lets send  mails to all with meanigful text, CRM-4320.
      $this->assign('isOnWaitlist', $this->_isOnWaitlist);
      $this->assign('isRequireApproval', $this->_requireApproval);

      foreach ($additionalIDs as $participantID => $contactId) {
        if ($participantID == $registerByID) {
          //set as Primary Participant
          $this->assign('isPrimary', 1);

          $customProfile = CRM_Event_BAO_Event::buildCustomProfile($participantID, $this->_values, NULL, $isTest);

          if (count($customProfile)) {
            $this->assign('customProfile', $customProfile);
            $this->set('customProfile', $customProfile);
          }
        }
        else {
          $this->assign('isPrimary', 0);
          $this->assign('customProfile', NULL);
        }

        // Add variable for generate cancel link
        if(!$this->_values['event']['is_monetary'] && $this->_values['event']['allow_cancel_by_link']){
          $checksumLife = 'inf';
          if ($endDate = CRM_Utils_Array::value('end_date', $this->_values['event'])) {
            $checksumLife = (CRM_Utils_Date::unixTime($endDate, true) - time()) / (60 * 60);
          }
          $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, $checksumLife);
          $this->assign('hasCancelLink', TRUE);
          $this->assign('checksumValue', $checksumValue);
          $this->assign('participantID', $participantID);
        }

        //send Confirmation mail to Primary & additional Participants if exists
        CRM_Event_BAO_Event::sendMail($contactId, $this->_values, $participantID, $isTest);
      }
    }
  }

  /**
   * Process the participant
   *
   * @return object
   * @access public
   */
  public function addParticipant($params, $contactID) {


    $transaction = new CRM_Core_Transaction();

    $groupName = "participant_role";
    $query = "
SELECT  v.label as label ,v.value as value
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1 
  AND  v.is_active       = 1  
  AND  g.is_active       = 1  
";
    $p = [1 => [$groupName, 'String']];

    $dao = &CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      $roleID = $dao->value;
    }

    // handle register date CRM-4320
    $registerDate = NULL;
    if ($this->_allowConfirmation && $this->_participantId) {
      $registerDate = $params['participant_register_date'];
    }
    elseif (is_array($params['participant_register_date']) && !empty($params['participant_register_date'])) {
      $registerDate = CRM_Utils_Date::format($params['participant_register_date']);
    }

    if(!empty($params['coupon'])){
      $coupon = $params['coupon'];
      $couponDescription = ts('Coupon').'-'.$coupon['code'].'-'.$coupon['description'].': -'.$coupon['discount_amount'];
      $params['amount_level'] .= $couponDescription.CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
    }

    $participantParams = ['id' => CRM_Utils_Array::value('participant_id', $params),
      'contact_id' => $contactID,
      'event_id' => $this->_eventId ? $this->_eventId : $params['event_id'],
      'status_id' => CRM_Utils_Array::value('participant_status_id',
        $params, 1
      ),
      'role_id' => CRM_Utils_Array::value('participant_role_id',
        $params, $roleID
      ),
      'register_date' => ($registerDate) ? $registerDate : date('YmdHis'),
      'source' => $params['participant_source'] ?? $params['description'],
      'fee_level' => $params['amount_level'],
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      'fee_amount' => CRM_Utils_Array::value('fee_amount', $params),
      'registered_by_id' => CRM_Utils_Array::value('registered_by_id', $params),
      'discount_id' => CRM_Utils_Array::value('discount_id', $params),
      'fee_currency' => CRM_Utils_Array::value('currencyID', $params),
    ];

    if ($this->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value('mode', $params) == 'test') {
      $participantParams['is_test'] = 1;
    }
    else {
      $participantParams['is_test'] = 0;
    }

    // refs #34079, participant_note has greater priority than note
    if (CRM_Utils_Array::value('participant_note', $this->_params)) {
      $participantParams['note'] = $this->_params['participant_note'];
    }
    elseif (CRM_Utils_Array::value('note', $this->_params)) {
      $participantParams['note'] = $this->_params['note'];
    }

    // reuse id if one already exists for this one (can happen
    // with back button being hit etc)
    if (!$participantParams['id'] &&
      CRM_Utils_Array::value('contributionID', $params)
    ) {
      $pID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $params['contributionID'],
        'participant_id',
        'contribution_id'
      );
      $participantParams['id'] = $pID;
    }

    $participantParams['discount_id'] = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');

    if (!$participantParams['discount_id']) {
      $participantParams['discount_id'] = "null";
    }


    $participant = CRM_Event_BAO_Participant::create($participantParams);

    $transaction->commit();

    return $participant;
  }

  /* Calculate the total participant count as per params. 
     * 
     * @param  array $params user params.
     *
     * @return $totalCount total participant count.
     * @access public 
     */

  public static function getParticipantCount(&$form, $params, $skipCurrent = FALSE) {
    $totalCount = 0;
    if (!is_array($params) || empty($params)) {
      return $totalCount;
    }

    $priceSetId = $form->get('priceSetId');
    $addParticipantNum = substr($form->_name, 12);
    $priceSetFields = $priceSetDetails = [];
    $hasPriceFieldsCount = FALSE;
    if ($priceSetId) {
      $priceSetDetails = $form->get('priceSet');
      if (isset($priceSetDetails['optionsCountTotal'])
        && $priceSetDetails['optionsCountTotal']
      ) {
        $hasPriceFieldsCount = TRUE;
        $priceSetFields = $priceSetDetails['optionsCountDetails']['fields'];
      }
    }

    $singleFormParams = FALSE;
    foreach ($params as $key => $val) {
      if (!is_numeric($key)) {
        $singleFormParams = TRUE;
        break;
      }
    }

    //first format the params.
    if ($singleFormParams) {
      $params = self::formatPriceSetParams($form, $params);
      $params = [$params];
    }

    foreach ($params as $key => $values) {
      if (!is_numeric($key) ||
        $values == 'skip' ||
        ($skipCurrent && ($addParticipantNum == $key))
      ) {
        continue;
      }
      $count = 1;

      $usedCache = FALSE;
      $cacheCount = CRM_Utils_Array::value($key, $form->_lineItemParticipantsCount);
      if ($cacheCount && is_numeric($cacheCount)) {
        $count = $cacheCount;
        $usedCache = TRUE;
      }

      if (!$usedCache && $hasPriceFieldsCount) {
        $count = 0;
        foreach ($values as $valKey => $value) {
          if (strpos($valKey, 'price_') === FALSE) {
            continue;
          }
          $priceFieldId = substr($valKey, 6);
          if (!$priceFieldId ||
            !is_array($value) ||
            !CRM_Utils_Array::arrayKeyExists($priceFieldId, $priceSetFields)
          ) {
            continue;
          }
          foreach ($value as $optId => $optVal) {
            $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
            if ($currentCount) {
              $count += $currentCount;
            }
          }
        }
        if (!$count) {
          $count = 1;
        }
      }
      $totalCount += $count;
    }
    if (!$totalCount) {
      $totalCount = 1;
    }

    return $totalCount;
  }

  /* Format user submitted price set params.
     * Convert price set each param as an array. 
     * 
     * @param $params an array of user submitted params.
     *
     *
     * @return array $formatted, formatted price set params.
     * @access public 
     */

  public static function formatPriceSetParams(&$form, $params) {
    if (!is_array($params) || empty($params)) {
      return $params;
    }

    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId) {
      return $params;
    }
    $priceSetDetails = $form->get('priceSet');

    foreach ($params as $key => & $value) {
      $vals = [];
      if (strpos($key, 'price_') !== FALSE) {
        $fieldId = substr($key, 6);
        if (!CRM_Utils_Array::arrayKeyExists($fieldId, $priceSetDetails['fields']) ||
          is_array($value) ||
          !$value
        ) {
          continue;
        }
        $field = $priceSetDetails['fields'][$fieldId];
        if ($field['html_type'] == 'Text') {
          $fieldOption = current($field['options']);
          $value = [$fieldOption['id'] => $value];
        }
        else {
          $value = [$value => TRUE];
        }
      }
    }

    return $params;
  }

  /* Calculate total count for each price set options.
     * those are currently selected by user.
     * 
     * @param $form form object.
     *
     *
     * @return array $optionsCount, array of each option w/ count total.
     * @access public 
     */
  static function getPriceSetOptionCount(&$form) {
    $params = $form->get('params');
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');

    $optionsCount = [];
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) ||
      !is_array($params) ||
      empty($params)
    ) {
      return $optionsCount;
    }

    $priceSetFields = [];
    if (isset($priceSet['optionsCountTotal'])
      && $priceSet['optionsCountTotal']
    ) {
      $priceSetFields = $priceSet['optionsCountDetails']['fields'];
    }

    $addParticipantNum = substr($form->_name, 12);
    foreach ($params as $pCnt => $values) {
      if ($values == 'skip' ||
        $pCnt == $addParticipantNum
      ) {
        continue;
      }

      foreach ($values as $valKey => $value) {
        if (strpos($valKey, 'price_') === FALSE) {
          continue;
        }

        $priceFieldId = substr($valKey, 6);
        if (!$priceFieldId ||
          !is_array($value) ||
          !CRM_Utils_Array::arrayKeyExists($priceFieldId, $priceSetFields)
        ) {
          continue;
        }

        foreach ($value as $optId => $optVal) {
          $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
          $optionsCount[$optId] = $currentCount + CRM_Utils_Array::value($optId, $optionsCount);
        }
      }
    }

    return $optionsCount;
  }

  function getTemplateFileName() {
    if ($this->_eventId) {
      $templateName = $this->_name;
      if (substr($templateName, 0, 12) == 'Participant_') {
        $templateName = 'AdditionalParticipant';
      }

      $templateFile = "CRM/Event/Form/Registration/{$this->_eventId}/{$templateName}.tpl";
      $template = &CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }

  function getContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // force to ignore the authenticated user
    if ($tempID === '0') {
      return;
    }

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.

      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }

    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  /* Validate price set submitted params for price option limit,
     * as well as user should select at least one price field option.
     *
     */
  static function validatePriceSet(&$form, $params) {
    $errors = [];
    if (!is_array($params) || empty($params)) {
      return $errors;
    }

    $currentParticipantNum = substr($form->_name, 12);
    if (!$currentParticipantNum) {
      $currentParticipantNum = 0;
    }

    $priceSetId = $form->get('priceSetId');
    $priceSetDetails = $form->get('priceSet');
    if (!$priceSetId ||
      !is_array($priceSetDetails) ||
      empty($priceSetDetails)
    ) {
      return $errors;
    }

    $optionsCountDetails = $optionsMaxValueDetails = [];
    if (isset($priceSetDetails['optionsMaxValueTotal'])
      && $priceSetDetails['optionsMaxValueTotal']
    ) {
      $hasOptMaxValue = TRUE;
      $optionsMaxValueDetails = $priceSetDetails['optionsMaxValueDetails']['fields'];
    }
    if (isset($priceSetDetails['optionsCountTotal'])
      && $priceSetDetails['optionsCountTotal']
    ) {
      $hasOptCount = TRUE;
      $optionsCountDetails = $priceSetDetails['optionsCountDetails']['fields'];
    }
    $feeBlock = $form->_feeBlock;
    if (empty($feeBlock)) {
      $feeBlock = $priceSetDetails['fields'];
    }

    $optionMaxValues = $fieldSelected = [];
    foreach ($params as $pNum => $values) {
      // participant_number => AllFields
      // price field is an array, skip when field is not array.
      if (!is_array($values) || $values == 'skip') {
        continue;
      }
      foreach ($values as $valKey => $value) {
        // AllFields as field_key => EachField
        if (strpos($valKey, 'price_') === FALSE) {
          continue;
        }
        $priceFieldId = substr($valKey, 6);
        if (!$priceFieldId ||
          !is_array($value)
        ) {
          continue;
        }
        $fieldSelected[$pNum] = TRUE;
        if (!$hasOptMaxValue) {
          continue;
        }
        $options = $optionsCountDetails[$priceFieldId]['options'];
        // calculate Each field total, to compare each field limit.
        foreach ($options as $optId => $optCount) {
          if (!empty($value[$optId]) && $value[$optId] == TRUE) {
            $optVal = $value[$optId];
            $fieldCountName = $valKey.'_'.$optId.'_count';
            $optCount = CRM_Utils_Array::arrayKeyExists($fieldCountName, $values) ? $values[$fieldCountName] : $optVal;
            $currentMaxValue = $options[$optId] * $optCount;
            $optionMaxValues[$priceFieldId][$optId] = $currentMaxValue + CRM_Utils_Array::value($optId, $optionMaxValues[$priceFieldId], 0);
          }
          else if(empty($optionMaxValues[$priceFieldId][$optId])) {
            $optionMaxValues[$priceFieldId][$optId] = 0;
          }
        }
      }
    }

    //validate for each option max value.
    foreach ($optionMaxValues as $fieldId => $values) {
      $fieldMax = $optionsMaxValueDetails[$fieldId]['max_value'];
      $options = CRM_Utils_Array::value('options', $feeBlock[$fieldId], []);
      $fieldTotal = 0;
      foreach ($values as $optId => $isSelected) {
        $optMax = $optionsMaxValueDetails[$fieldId]['options'][$optId];
        $total = $isSelected + CRM_Utils_Array::value('db_total_count', $options[$optId], 0);
        if (empty($fieldMax) && $isSelected && $optMax && $total > $optMax) {
          $errors[$currentParticipantNum]["price_{$fieldId}"] = ts('It looks like this field participant count extending its maximum limit.');
        }
        $fieldTotal += $total;
      }
      if(!empty($fieldMax) && $fieldTotal > $fieldMax){
        $errors[$currentParticipantNum]["price_{$fieldId}"] = ts('It looks like this field participant count extending its maximum limit.');
      }
    }

    //validate for price field selection.
    foreach ($params as $pNum => $values) {
      if (!is_array($values) || $values == 'skip') {
        continue;
      }
      if (!CRM_Utils_Array::value($pNum, $fieldSelected)) {
        $errors[$pNum]['_qf_default'] = ts('Select at least one option from Event Fee(s).');
      }
    }

    return $errors;
  }

  function isEventFull() {
    // count with waitlist
    $this->_availableRegistrations = CRM_Event_BAO_Participant::eventFull($this->_values['event']['id'], TRUE);
    $this->_allowWaitlist = CRM_Utils_Array::value('has_waitlist', $this->_values['event']);
    $eventFull = is_numeric($this->_availableRegistrations) ? FALSE : $this->_availableRegistrations;

    $this->_isEventFull = FALSE;
    if ($eventFull && !$this->_allowConfirmation) {
      $this->_isEventFull = TRUE;

      if ($this->_allowWaitlist) {
        $wait_list_msg = CRM_Utils_Array::value('waitlist_text', $this->_values['event']) ? CRM_Utils_Array::value('waitlist_text', $this->_values['event']) : ts('This event is currently full. However you can register now and get added to a waiting list. You will be notified if spaces become available.');
        $this->_waitlistMsg = $wait_list_msg;
        $this->set('waitlistMsg', $this->_waitlistMsg);
        $this->assign('allowWaitlist', $this->_allowWaitlist);
      }
      else {
        $event_full_text = $eventFull ? $eventFull : ts('This event is currently full.');
        $this->set('eventFullText', $event_full_text);
        $this->assign('eventFullText', $event_full_text);
      }
    }
    elseif($this->_allowConfirmation){
      $this->assign('allowConfirmation', TRUE);
    }
    $this->set('isEventFull', $this->_isEventFull);
    $this->set('allowWaitlist', $this->_allowWaitlist);
    $this->set('availableRegistrations', $this->_availableRegistrations);
  }

  /**
   * function to update contact fields
   *
   * @return void
   * @access public
   */
  public function updateContactFields($contactID, $params, $fields) {
    //add the contact to group, if add to group is selected for a
    //particular uf group

    // get the add to groups
    $addToGroups = [];

    if (!empty($this->_fields)) {
      foreach ($this->_fields as $key => $value) {
        if (CRM_Utils_Array::value('add_to_group_id', $value)) {
          $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
        }
      }
    }

    // check for profile double opt-in and get groups to be subscribed

    $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

    foreach ($addToGroups as $k) {
      if (CRM_Utils_Array::arrayKeyExists($k, $subscribeGroupIds)) {
        unset($addToGroups[$k]);
      }
    }

    // since we are directly adding contact to group lets unset it from mailing
    if (!empty($addToGroups)) {
      foreach ($addToGroups as $groupId) {
        if (isset($subscribeGroupIds[$groupId])) {
          unset($subscribeGroupIds[$groupId]);
        }
      }
    }


    $params['log_data'] = !empty($params['log_data']) ? $params['log_data'] : ts('Event').' - '.$this->_eventId;
    if ($contactID) {
      $ctype = CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact",
        $contactID,
        "contact_type"
      );
      $contactID = &CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $contactID,
        $addToGroups,
        NULL,
        $ctype
      );
    }
    else {
      // when we have allow_same_participant_emails = 1
      // don't take email address in dedupe params - CRM-4886
      // here we are making dedupe weak - so to make dedupe
      // more effective please update individual 'Strict' rule.
      $allowSameEmailAddress = CRM_Utils_Array::value('allow_same_participant_emails', $this->_values['event']);

      //suppress "email-Primary" when allow_same_participant_emails = 1
      if ($allowSameEmailAddress &&
        ($email = CRM_Utils_Array::value('email-Primary', $params)) &&
        (CRM_Utils_Array::value('registered_by_id', $params))
      ) {
        //skip dedupe check only for additional participants
        unset($params['email-Primary']);
      }
      $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
      // disable permission based on cache since event registration is public page/feature.
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
      if (empty($ids) && defined('CIVICRM_ENABLE_DEDUPE_DEBUG') && CIVICRM_ENABLE_DEDUPE_DEBUG > 0) {
        CRM_Core_Error::debug_var('registeration_dedupe', $dedupeParams);
      }

      // if we find more than one contact, use the first one
      $contact_id = $ids[0];
      if (isset($email)) {
        $params['email-Primary'] = $email;
      }

      $contactID = &CRM_Contact_BAO_Contact::createProfileContact($params, $fields, $contact_id, $addToGroups);
      $this->set('contactID', $contactID);
    }

    //get email primary first if exist
    $subscribtionEmail = ['email' => CRM_Utils_Array::value('email-Primary', $params)];
    if (!$subscribtionEmail['email']) {
      $subscribtionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {

      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }

    return $contactID;
  }

  function setParticipantCustomDefault($participantId, $fields, &$defaults){
    $participantDefault = [];
    CRM_Core_BAO_UFGroup::setComponentDefaults($fields, $participantId, 'Event', $participantDefault);
    foreach($participantDefault as $cfKey => $value) {
      if($cfKey == 'field' && is_array($value) && is_array($value[$participantId])) {
        foreach($value[$participantId] as $key => $opt){
          if(is_array($opt)) {
            $defaults[$key] = $opt;
            foreach($opt as $optlabel => $tmp){
              $defaults[$key."[$optlabel]"] = 1;
            }
          }
        }
      }
      elseif(preg_match('/^field\[(\d+)\]\[([^\]]+)\]/i', $cfKey, $matches)) {
        if(!empty($matches[2]) && !empty($value)) {
          $defaults[$matches[2]] = $value;
        }
      }
    }
  }

  /**
   * Fix the Location Fields
   *
   * @return void
   * @access public
   */
  public function fixLocationFields(&$params, &$fields) {
    if (!empty($this->_fields)) {
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }
    }

    if (is_array($fields)) {
      if (!CRM_Utils_Array::arrayKeyExists('first_name', $fields)) {
        $nameFields = ['first_name', 'middle_name', 'last_name'];
        foreach ($nameFields as $name) {
          $fields[$name] = 1;
          if (CRM_Utils_Array::arrayKeyExists("billing_$name", $params)) {
            $params[$name] = $params["billing_{$name}"];
            $params['preserveDBName'] = TRUE;
          }
        }
      }
    }

    // also add location name to the array
    if ($this->_values['event']['is_monetary']) {
      $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value("billing_first_name", $params) . ' ' . CRM_Utils_Array::value("billing_middle_name", $params) . ' ' . CRM_Utils_Array::value("billing_last_name", $params);
      $fields["address_name-{$this->_bltID}"] = 1;
    }
    $fields["email-{$this->_bltID}"] = 1;
  }

  public function track($pageName = '') {
    $page_id = $this->_values['event']['id'];
    if (empty($pageName)) {
      $actionName = $this->controller->getActionName();
      list($pageName, $action) = $actionName;
    }
    $pageName = strtolower($pageName);
    $state = [
      'register' => 1,
      'confirm' => 2,
      'payment' => 3,
      'thankyou' => 4
    ];
    $params = [
      'state' => $state[$pageName],
      'page_type' => 'civicrm_event',
      'page_id' => $page_id,
      'visit_date' => date('Y-m-d H:i:s'),
    ];
    $primaryParticipant = $this->get('registerByID');
    if (!empty($primaryParticipant)) {
      $params['entity_table'] = 'civicrm_participant';
      $params['entity_id'] = $primaryParticipant;
    }
    $track = CRM_Core_BAO_Track::add($params);
  }
}

