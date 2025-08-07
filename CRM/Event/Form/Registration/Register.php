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
class CRM_Event_Form_Registration_Register extends CRM_Event_Form_Registration {

  public $_paymentProcessors;
  public $_feeBlock;
  public $_contactId;
  /**
   * @var int|null
   */
  public $_discountId;
  public $_elementIndex;
  public $_isOnWaitlist;
  public $_usedOptionsDiscount;
  public $_totalDiscount;
  public $_coupon;
  public $_expressButtonName;
  /**
   * The fields involved in this page
   *
   */
  public $_fields;

  /**
   * The defaults involved in this page
   *
   */
  public $_defaults;

  /**
   * The status message that user view.
   *
   */
  protected $_requireApprovalMsg = NULL;

  protected $_ppType;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $this->_ppType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('ppType', FALSE);
    if ($this->_ppType) {
      $this->assign('ppType', TRUE);
      return CRM_Core_Payment_ProcessorForm::preProcess($this);
    }

    parent::isEventFull();

    //get payPal express id and make it available to template
    $paymentProcessors = $this->get('paymentProcessors');
    $this->assign('payPalExpressId', 0);
    if (!empty($paymentProcessors)) {
      foreach ($paymentProcessors as $ppId => $values) {
        $payPalExpressId = ($values['payment_processor_type'] == 'PayPal_Express') ? $values['id'] : 0;
        $this->assign('payPalExpressId', $payPalExpressId);
        if ($payPalExpressId) {
          break;
        }
      }
    }

    //To check if the user is already registered for the event(CRM-2426)
    self::checkRegistration(NULL, $this);

    $this->assign('availableRegistrations', $this->_availableRegistrations);

    // get the participant values from EventFees.php, CRM-4320
    if ($this->_allowConfirmation) {

      CRM_Event_Form_EventFees::preProcess($this);
    }

    // Assign pageTitle
    $this->assign('pageTitle', ts('Event Registration'));

    if (CRM_Utils_Array::value('hidden_processor', $_POST)) {
      $this->set('type', CRM_Utils_Array::value('payment_processor', $_POST));
      $this->set('mode', $this->_mode);
      $this->set('paymentProcessor', $this->_paymentProcessor);

      CRM_Core_Payment_ProcessorForm::preProcess($this);
      CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
    }


    // Prepare params used for meta.
    $params = [];
    $siteName = CRM_Utils_System::siteName();
    $params['site'] = $siteName;
    $params['title'] = $this->_values['event']['title'] . ' - ' . $siteName;

    $description = $this->_values['event']['description'];
    $description = preg_replace("/ *<(?<tag>(style|script))( [^=]+=['\"][^'\"]*['\"])*>(.*?(\n))+.*?<\/\k<tag>>/", "", $description);
    $description = strip_tags($description);
    $description = preg_replace("/(?:(?:&nbsp;)|\n|\r)+/", ' ', $description);
    $description = trim(mb_substr($description, 0, 150));
    $params['description'] = $description;

    $event = new stdClass();
    $event->_id = $this->_eventId;
    $values = $this->_values;
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("Event", $event, $event->_id, 0, $values['event']['event_type_id']);
    $config = CRM_Core_Config::singleton();
    foreach ($groupTree as $ufg_inner) {
      if (is_array($ufg_inner['fields'])) {
        foreach ($ufg_inner['fields'] as $uffield) {
          if (is_array($uffield)) {
            if ($uffield['data_type'] == 'File') {
              if (!empty($uffield['customValue'][1]) && preg_match('/\.(jpg|png|jpeg)$/',$uffield['customValue'][1]['data'])) {
                $image = $config->customFileUploadURL . $uffield['customValue'][1]['data'];
                break;
                break;
                break;
              }
            }
          }
        }
      }
    }
    if (empty($image)) {
      preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $values['event']['description'], $matches);
      if (count($matches) >= 2) {
        $image = $matches[1];
      }
    }
    $params['image'] = $image;
    CRM_Utils_System::setPageMetaInfo($params);


  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if ($this->_ppType) {
      return;
    }
    $contactID = parent::getContactID();
    if ($contactID) {
      $options = [];
      $fields = [];


      if (!empty($this->_fields)) {
        $removeCustomFieldTypes = ['Participant'];
        foreach ($this->_fields as $name => $dontCare) {
          if (substr($name, 0, 7) == 'custom_') {
            $id = substr($name, 7);
            if (!$this->_allowConfirmation &&
              !CRM_Core_BAO_CustomGroup::checkCustomField($id, $removeCustomFieldTypes)
            ) {
              continue;
            }
            // ignore component fields
          }
          elseif ((substr($name, 0, 12) == 'participant_')) {
            continue;
          }
          $fields[$name] = 1;
        }
      }

      $names = ["first_name", "middle_name", "last_name", "street_address-{$this->_bltID}", "city-{$this->_bltID}",
        "postal_code-{$this->_bltID}", "country_id-{$this->_bltID}", "state_province_id-{$this->_bltID}",
      ];

      foreach ($names as $name) {
        $fields[$name] = 1;
      }
      $fields["state_province-{$this->_bltID}"] = 1;
      $fields["country-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $fields["email-Primary"] = 1;


      CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);

      if (!empty($this->_participantId)) {
        parent::setParticipantCustomDefault($this->_participantId, $fields, $this->_defaults);
      }

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
        !empty($this->_defaults["email-Primary"])
      ) {
        $this->_defaults["email-{$this->_bltID}"] = $this->_defaults["email-Primary"];
      }

      foreach ($names as $name) {
        if (isset($this->_defaults[$name])) {
          $this->_defaults["billing_" . $name] = $this->_defaults[$name];
        }
      }
    }
    else {
      if (isset($this->_fields['group'])) {
        CRM_Contact_BAO_Group::publicDefaultGroups($this->_defaults);
      }
    }
    //if event is monetary and pay later is enabled and payment
    //processor is not available then freeze the pay later checkbox with
    //default check
    if (CRM_Utils_Array::value('is_pay_later', $this->_values['event']) && !is_array($this->_paymentProcessors)) {
      $this->_defaults['is_pay_later'] = 1;
    }
    if(isset($this->_paymentProcessors) && count($this->_paymentProcessors) == 1){
      $pid = key($this->_paymentProcessors);
      $this->_defaults['payment_processor'] = $pid;
    }

    //set custom field defaults
    if (!empty($this->_fields)) {

      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          // fix for CRM-1743
          if (!isset($this->_defaults[$name])) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
              NULL, CRM_Profile_Form::MODE_REGISTER
            );
          }
        }
      }
    }

    //fix for CRM-3088, default value for discount set.
    $discountId = NULL;
    if (!empty($this->_values['discount'])) {

      $participantId = $this->get('participantId');
      $timestamp = self::getRegistrationTimestamp($participantId);
      $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event', $timestamp);
      if ($discountId) {
        if (isset($this->_values['event']['default_discount_fee_id'])) {
          $discountKey = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_OptionValue",
            $this->_values['event']['default_discount_fee_id'],
            'weight', 'id'
          );

          $this->_defaults['amount'] = key(array_slice($this->_values['discount'][$discountId], $discountKey - 1, $discountKey, TRUE));
        }
      }
    }

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
    if (!CRM_Utils_Array::value("billing_country_id-{$this->_bltID}", $this->_defaults)) {
      $this->_defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
    }

    // now fix all state country selectors

    CRM_Core_BAO_Address::fixAllStateSelects($this, $this->_defaults);

    // add this event's default participant role to defaults array (for cases where participant_role field is included in form via profile)
    if ($this->_values['event']['default_role_id']) {
      $this->_defaults['participant_role_id'] = $this->_values['event']['default_role_id'];
    }
    if ($this->_priceSetId) {
      foreach ($this->_feeBlock as $key => $val) {
        foreach ($val['options'] as $keys => $values) {
          if ($values['is_default'] &&
            !CRM_Utils_Array::value('is_full', $values)
          ) {

            if ($val['html_type'] == 'CheckBox') {
              $this->_defaults["price_{$key}"][$keys] = 1;
            }
            else {
              $this->_defaults["price_{$key}"] = $keys;
            }
          }
        }
      }
    }

    //set default participant fields, CRM-4320.
    $hasAdditionalParticipants = FALSE;
    if ($this->_allowConfirmation) {

      $this->_contactId = $contactID;
      $this->_discountId = $discountId;
      $forcePayLater = CRM_Utils_Array::value('is_pay_later', $this->_defaults, FALSE);
      $this->_defaults = array_merge($this->_defaults, CRM_Event_Form_EventFees::setDefaultValues($this));
      $this->_defaults['is_pay_later'] = $forcePayLater;

      if ($this->_additionalParticipantIds) {
        $hasAdditionalParticipants = TRUE;
        $this->_defaults['additional_participants'] = count($this->_additionalParticipantIds);
      }
    }
    $this->assign('hasAdditionalParticipants', $hasAdditionalParticipants);

    //         //hack to simplify credit card entry for testing
    //         $this->_defaults['credit_card_type']     = 'Visa';
    //         $this->_defaults['credit_card_number']   = '4807731747657838';
    //         $this->_defaults['cvv2']                 = '000';
    //         $this->_defaults['credit_card_exp_date'] = array( 'Y' => '2010', 'M' => '05' );

    // to process Custom data that are appended to URL

    $getDefaults = CRM_Core_BAO_CustomGroup::extractGetParams($this, "'Contact', 'Individual', 'Contribution', 'Participant'");
    if (!empty($getDefaults)) {
      $this->_defaults = array_merge($this->_defaults, $getDefaults);
    }

    // readonly for specify field
    if ($contactID) {
      $readonlyFields = ['last_name', 'first_name', "email-{$this->_bltID}"];
      foreach($readonlyFields as $fld) {
        if (!empty($this->_elementIndex[$fld]) && !empty($this->_defaults[$fld])) {
          $element = $this->getElement($fld);
          $element->updateAttributes(['readonly' => 'readonly']);
        }
      }
    }

    return $this->_defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */

  public function buildQuickForm() {
    if ($this->_ppType) {
      return CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
    }

    $contactID = parent::getContactID();
    if ($contactID) {

      $name = CRM_Contact_BAO_Contact::displayName($contactID);
      $this->assign('display_name', $name);
      $this->assign('contact_id', $contactID);
      if(CRM_Core_Permission::check('edit all contacts')){
        $this->assign('is_contact_admin', 1);
      }
      else{
        $this->assign('is_contact_admin', 0);
      }
    }

    $config = CRM_Core_Config::singleton();
    $this->add('hidden', 'scriptFee', NULL);
    $this->add('hidden', 'scriptArray', NULL);
    $this->add('text',
      "email-{$this->_bltID}",
      ts('Email Address'),
      ['size' => 30, 'maxlength' => 60], TRUE
    );
    $this->addRule("email-{$this->_bltID}", ts('Email is not valid.'), 'email');

    $bypassPayment = $allowGroupOnWaitlist = $isAdditionalParticipants = FALSE;
    if ($this->_values['event']['is_multiple_registrations']) {
      // don't allow to add additional during confirmation if not preregistered.
      if (!$this->_allowConfirmation || $this->_additionalParticipantIds) {
        $additionalOptions = ['' => ts('1')];
        if ($this->_values['event']['is_multiple_registrations'] > 1) {
          $maxAdditionalParticipant = $this->_values['event']['is_multiple_registrations'];
        }
        else {
          $maxAdditionalParticipant = 10;
        }
        for($i = 2; $i <= $maxAdditionalParticipant; $i++) {
          $additionalOptions[$i-1] = $i;
        }
        $element = $this->add('select', 'additional_participants',
          ts('How many people are you registering?'),
          $additionalOptions,
          NULL,
          ['onChange' => "allowParticipant()"]
        );
        $isAdditionalParticipants = TRUE;
      }
    }

    //hack to allow group to register w/ waiting
    if ((CRM_Utils_Array::value('is_multiple_registrations', $this->_values['event']) ||
        $this->_priceSetId
      ) &&
      !$this->_allowConfirmation &&
      is_numeric($this->_availableRegistrations)
      && CRM_Utils_Array::value('has_waitlist', $this->_values['event'])
    ) {
      $bypassPayment = TRUE;
      //case might be group become as a part of waitlist.
      //If not waitlist then they require admin approve.
      $allowGroupOnWaitlist = TRUE;
      $this->_waitlistMsg = ts("This event has only %1 space(s) left. If you continue and register more than %1 people (including yourself ), the whole group will be wait listed. Or, you can reduce the number of people you are registering to %1 to avoid being put on the waiting list.", [1 => $this->_availableRegistrations]);

      if ($this->_requireApproval) {
        $this->_requireApprovalMsg = CRM_Utils_Array::value('approval_req_text', $this->_values['event'],
          ts('Registration for this event requires approval. Once your registration(s) have been reviewed, you will receive an email with a link to a web page where you can complete the registration process.')
        );
      }
    }

    //case where only approval needed - no waitlist.
    if ($this->_requireApproval &&
      !$this->_allowWaitlist && !$bypassPayment
    ) {
      $this->_requireApprovalMsg = CRM_Utils_Array::value('approval_req_text', $this->_values['event'],
        ts('Registration for this event requires approval. Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.')
      );
    }

    //lets display status to primary page only.
    $this->assign('waitlistMsg', $this->_waitlistMsg);
    $this->assign('requireApprovalMsg', $this->_requireApprovalMsg);
    $this->assign('allowGroupOnWaitlist', $allowGroupOnWaitlist);
    $this->assign('isAdditionalParticipants', $isAdditionalParticipants);

    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');

    //lets get js on two different qf elements.
    $buildExpressPayBlock = FALSE;
    $showHidePayfieldName = NULL;
    $showHidePaymentInformation = FALSE;
    if ($this->_values['event']['is_monetary']) {
      self::buildAmount($this);

      $attributes = NULL;
      $freezePayLater = TRUE;
      if (is_array($this->_paymentProcessor)) {
        $freezePayLater = FALSE;
        if (!in_array($this->_paymentProcessor['billing_mode'], [2, 4])) {
          $showHidePayfieldName = 'payment_information';
          $attributes = ['onclick' => "showHidePaymentInfo( );"];
        }

        if ($this->_paymentProcessor['payment_processor_type'] == 'PayPal_Express') {
          $showHidePayfieldName = 'PayPalExpress';
          $attributes = ['onclick' => "showHidePayPalExpressOption();"];
        }
      }

      //lets build only when there is no waiting and no required approval.
      /*
            if ( $this->_allowConfirmation || ( !$this->_requireApproval && !$this->_allowWaitlist ) ) {
                if ( $this->_values['event']['is_pay_later'] ) {
                    $element = $this->addElement( 'checkbox', 'is_pay_later', 
                                                  $this->_values['event']['pay_later_text'], null, $attributes );
                    
                    //if payment processor is not available then freeze
                    //the paylater checkbox with default checked.
                    if ( $freezePayLater ) {
                        $element->freeze( );
                    }
                }
                

                CRM_Core_Payment_Form::buildCreditCard( $this );
                if ( $showHidePayfieldName == 'payment_information' ) {
                    $showHidePaymentInformation = true;
                }
                if ( $showHidePayfieldName == 'PayPalExpress' ) {
                    $buildExpressPayBlock = true; 
                }
            }
*/
      if(!empty($this->_paymentProcessors) && count($this->_paymentProcessors) == 1){
        $pid = key($this->_paymentProcessors);
        $this->_defaults['payment_processor'] = $pid;
      }
      $pps = NULL;
      $this->_paymentProcessors = $this->get('paymentProcessors');
      if (!empty($this->_paymentProcessors)) {
        $pps = $this->_paymentProcessors;
        foreach ($pps as $key => & $name) {
          $pps[$key] = $name['name'];
        }
      }

      if (($this->_requireApproval || $this->_isOnWaitlist) && !$this->_allowConfirmation) {
        $this->assign('show_payment_processors', 0);
      }
      else{
        $this->assign('show_payment_processors', 1);
        if (is_array($pps) && count($pps)) {
          if (CRM_Utils_Array::value('is_pay_later', $this->_values['event']) && ($this->_allowConfirmation || (!$this->_requireApproval && !$this->_isOnWaitlist))) {
            $pps[0] = $this->_values['event']['pay_later_text'];
          }
          $this->addRadio('payment_processor', ts('Payment Method'), $pps, NULL, "&nbsp;", TRUE);
        }
        else {
          $this->assign('is_pay_later', $this->_values['is_pay_later']);
          $this->assign('pay_later_text', $this->_values['pay_later_text']);
          $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
          if(!empty($pps)){
            $this->addElement('hidden', 'payment_processor', array_pop(array_keys($pps)));
          }
        }
      }
    }

    //lets add some qf element to bypass payment validations, CRM-4320
    if ($bypassPayment) {
      $attributes = NULL;
      if ($showHidePayfieldName == 'payment_information' && $showHidePaymentInformation) {
        $attributes = ['onclick' => "showHidePaymentInfo();"];
      }
      if ($showHidePayfieldName == 'PayPalExpress') {
        $attributes = ['onclick' => "showHidePayPalExpressOption();"];
      }
      $this->addElement('hidden', 'bypass_payment', NULL, ['id' => 'bypass_payment']);
    }
    $this->assign('bypassPayment', $bypassPayment);
    $this->assign('buildExpressPayBlock', $buildExpressPayBlock);
    $this->assign('showHidePaymentInformation', $showHidePaymentInformation);

    $userID = parent::getContactID();

    if (!$userID) {
      $createCMSUser = FALSE;
      if ($this->_values['custom_pre_id']) {
        $profileID = $this->_values['custom_pre_id'];
        $createCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_cms_user');
      }
      if (!$createCMSUser &&
        $this->_values['custom_post_id']
      ) {
        $profileID = $this->_values['custom_post_id'];
        $createCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_cms_user');
      }
      if ($createCMSUser) {

        CRM_Core_BAO_CMSUser::buildForm($this, $profileID, TRUE);
      }
    }

    //we have to load confirm contribution button in template
    //when multiple payment processor as the user
    //can toggle with payment processor selection
    $billingModePaymentProcessors = 0;
    if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $key => $values) {
        if ($values['billing_mode'] == CRM_Core_Payment::BILLING_MODE_BUTTON) {
          $billingModePaymentProcessors++;
        }
      }
    }

    if ($billingModePaymentProcessors && count($this->_paymentProcessors) == $billingModePaymentProcessors) {
      $allAreBillingModeProcessors = TRUE;
    }
    else {
      $allAreBillingModeProcessors = FALSE;
    }

    if (!$allAreBillingModeProcessors ||
      CRM_Utils_Array::value('is_pay_later', $this->_values['event']) || $bypassPayment
    ) {

      //freeze button to avoid multiple calls.
      $js = NULL;

      if (!CRM_Utils_Array::value('is_monetary', $this->_values['event']) && !CRM_Utils_Array::value('is_multiple_registrations',$this->_values['event'])) {
        $js = ['data' => 'submit-once'];
      }

      if (!$this->_isEventFull || $this->_allowWaitlist) {
        $this->addButtons([
            [
              'type' => 'upload',
              'name' => ts('Continue >>'),
              'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
              'isDefault' => TRUE,
              'js' => $js,
            ],
          ]
        );
      }
    }

    $this->addFormRule(['CRM_Event_Form_Registration_Register', 'formRule'], $this);
  }

  /**
   * build the radio/text form elements for the amount field
   *
   * @param object   $form form object
   * @param boolean  $required  true if you want to add formRule
   * @param int      $discountId discount id for the event
   *
   * @return void
   * @access public
   * @static
   */
  static public function buildAmount(&$form, $required = TRUE, $discountId = NULL) {
    //if payment done, no need to build the fee block.
    if (isset($form->_paymentId) && $form->_paymentId && $form->_online) {
      //fix to diaplay line item in update mode.
      $form->assign('priceSet', $form->_priceSet ?? NULL);
      return;
    }

    $feeFields = CRM_Utils_Array::value('fee', $form->_values);

    if (is_array($feeFields)) {
      $form->_feeBlock = &$form->_values['fee'];
    }

    //check for discount.
    $discountedFee = CRM_Utils_Array::value('discount', $form->_values);
    if (is_array($discountedFee) && !empty($discountedFee)) {
      if (!$discountId) {
        $participantId = $form->get('participantId');
        $timestamp = self::getRegistrationTimestamp($participantId);
        $form->_discountId = $discountId = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event', $timestamp);
      }
      if ($discountId) {
        $form->_feeBlock = &$form->_values['discount'][$discountId];
      }
    }
    if (!is_array($form->_feeBlock)) {
      $form->_feeBlock = [];
    }

    // add coupon field in feeBlock for hook buildAmount
    $params = [
      'date' => date('Y-m-d H:i:s'),
      'is_active' => 1,
      'entity_table' => 'civicrm_event',
      'entity_id' => $form->_eventId,
    ];
    $couponDAO = CRM_Coupon_BAO_Coupon::getCouponList($params);
    if (!empty($couponDAO->N)) {
      $form->_feeBlock['coupon'] = 1;
    }
    $form->assign('eventId', $form->_eventId);

    //its time to call the hook.
    CRM_Utils_Hook::buildAmount('event', $form, $form->_feeBlock);

    //reset required if participant is skipped.
    $button = substr($form->controller->getButtonName(), -4);
    if ($required && $button == 'skip') {
      $required = FALSE;
    }

    $className = CRM_Utils_System::getClassName($form);

    //build the priceset fields.
    if (isset($form->_priceSetId) && $form->_priceSetId) {

      //format price set fields across option full.
      self::formatFieldsForOptionFull($form);

      // add coupon field in feeBlock for hook buildAmount
      $activeOptionIds = $form->get('activePriceOptionIds'); // we wont have this price option when  formatFieldsForOptionFull not triggered
      if (empty($activeOptionIds)) {
         $activeOptionIds = [];
         $priceSet = $form->get('priceSet'); 
         foreach($priceSet['fields'] as $priceField) {
           foreach($priceField['options'] as $priceOption) {
             if ($priceOption['is_active']) {
               $activeOptionIds[] = $priceOption['id'];
             }
           }
         }
      }
      $params = [
        'date' => date('Y-m-d H:i:s'),
        'is_active' => 1,
        'entity_table' => 'civicrm_price_field_value',
        'entity_id' => $activeOptionIds,
      ];
      $couponDAO = CRM_Coupon_BAO_Coupon::getCouponList($params);
      if (!empty($couponDAO->N)) {
        $form->_feeBlock['coupon'] = 1;
        $form->assign('activePriceOptionIds', CRM_Utils_Array::implode(',', $activeOptionIds));
      }

      $form->addGroup($elements, 'amount', ts('Event Fee(s)'), '<br />');
      $form->add('hidden', 'priceSetId', $form->_priceSetId);

      foreach ($form->_feeBlock as $idx => $field) {
        if ($idx == 'coupon') {
          CRM_Coupon_BAO_Coupon::addQuickFormElement($form);
          continue;
        }
        if (CRM_Utils_Array::value('visibility', $field) == 'public' ||
          $className == 'CRM_Event_Form_Participant'
        ) {
          $fieldId = $field['id'];
          $elementName = 'price_' . $fieldId;

          $isRequire = CRM_Utils_Array::value('is_required', $field);
          if ($button == 'skip') {
            $isRequire = FALSE;
          }

          //user might modified w/ hook.
          $options = CRM_Utils_Array::value('options', $field);
          if (!is_array($options)) {
            continue;
          }

          $optionFullIds = CRM_Utils_Array::value('option_full_ids', $field, []);

          //soft suppress required rule when option is full.
          if (!empty($optionFullIds) && (count($options) == count($optionFullIds))) {
            $isRequire = FALSE;
          }

          //build the element.
          CRM_Price_BAO_Field::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE,
            $isRequire,
            NULL,
            $options,
            $optionFullIds
          );
        }
      }
      $form->assign('priceSet', $form->_priceSet);
    }
    else {
      $eventFeeBlockValues = [];
      foreach ($form->_feeBlock as $idx => $fee) {
        if ($idx == 'coupon') {
          CRM_Coupon_BAO_Coupon::addQuickFormElement($form);
          continue;
        }
        if (is_array($fee)) {
          $eventFeeBlockValues['amount_id_' . $fee['amount_id']] = $fee['value'];
          $elements[] = &$form->createElement('radio', NULL, '',
            CRM_Utils_Money::format($fee['value']) . ' ' .
            $fee['label'],
            $fee['amount_id'],
            ['onClick' => "fillTotalAmount(" . $fee['value'] . ")"]
          );
        }
      }
      $form->assign('eventFeeBlockValues', json_encode($eventFeeBlockValues));

      $form->_defaults['amount'] = CRM_Utils_Array::value('default_fee_id', $form->_values['event']);
      $element = &$form->addGroup($elements, 'amount', ts('Event Fee(s)'), '<br />');
      if (isset($form->_online) && $form->_online) {
        $element->freeze();
      }
      if ($required) {
        $form->addRule('amount', ts('Fee Level is a required field.'), 'required');
      }
    }
  }

  public static function formatFieldsForOptionFull(&$form) {
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) ||
      !CRM_Utils_Array::value('optionsMaxValueTotal', $priceSet)
    ) {
      return;
    }




    $skipParticipants = $formattedPriceSetDefaults = [];
    if ($form->_allowConfirmation && (isset($form->_pId) || isset($form->_additionalParticipantId))) {

      $participantId = $form->_pId ?? $form->_additionalParticipantId;
      $pricesetDefaults = CRM_Event_Form_EventFees::setDefaultPriceSet($participantId,
        $form->_eventId
      );
      // modify options full to respect the selected fields
      // options on confirmation.
      $formattedPriceSetDefaults = self::formatPriceSetParams($form, $pricesetDefaultOptions);

      // to skip current registered participants fields option count on confirmation.
      $skipParticipants[] = $form->_participantId;
      if (!empty($form->_additionalParticipantIds)) {
        $skipParticipants = array_merge($skipParticipants, $form->_additionalParticipantIds);
      }
    }

    $className = CRM_Utils_System::getClassName($form);

    //get the current price event price set options count.
    $currentOptionsCount = self::getPriceSetOptionCount($form);
    $recordedOptionsCount = CRM_Event_BAO_Participant::priceSetOptionsCount($form->_eventId, $skipParticipants);

    $activeOptionIds = [];
    $allOptions = [];
    foreach ($form->_feeBlock as & $field) {
      $optionFullIds = [];
      $fieldId = $field['id'];
      if (!is_array($field['options'])) {
        continue;
      }
      $sumCount = 0;
      foreach ($field['options'] as & $option) {
        $optId = $option['id'];
        $activeOptionIds[$optId] = $optId;
        $allOptions[$optId] = $option;
        $count = CRM_Utils_Array::value('count', $option, 0);
        $maxValue = CRM_Utils_Array::value('max_value', $option, 0);
        $dbTotalCount = CRM_Utils_Array::value($optId, $recordedOptionsCount, 0);
        $currentTotalCount = CRM_Utils_Array::value($optId, $currentOptionsCount, 0);
        $totalCount = $currentTotalCount + $dbTotalCount;
        $sumCount += $dbTotalCount;

        $isFull = FALSE;
        if (empty($field['max_value']) && $maxValue &&
          (($totalCount >= $maxValue) || ($totalCount + $count > $maxValue))
        ) {
          $isFull = TRUE;
          $optionFullIds[$optId] = $optId;
          unset($activeOptionIds[$optId]);
        }

        //here option is not full,
        //but we don't want to allow participant to increase
        //seats at the time of re-walking registration.
        if (empty($field['max_value']) && $count &&
          $form->_allowConfirmation &&
          !empty($formattedPriceSetDefaults)
        ) {
          if (!CRM_Utils_Array::value("price_{$field}", $formattedPriceSetDefaults) ||
            !CRM_Utils_Array::value($opId, $formattedPriceSetDefaults["price_{$fieldId}"])
          ) {
            $optionFullIds[$optId] = $optId;
            unset($activeOptionIds[$optId]);
            $isFull = TRUE;
          }
        }
        $option['is_full'] = $isFull;
        $option['db_total_count'] = $dbTotalCount;
        $option['total_option_count'] = $totalCount;
      }

      if(!empty($field['max_value']) && $field['max_value'] <= $sumCount){
        foreach ($field['options'] as & $option) {
          $optId = $option['id'];
          $optionFullIds[$optId] = $optId;
          unset($activeOptionIds[$optId]);
          $option['is_full'] = TRUE;
        }
      }

      //ignore option full for offline registration.
      if ($className == 'CRM_Event_Form_Participant') {
        $optionFullIds = [];
      }

      //finally get option ids in.
      $field['option_full_ids'] = $optionFullIds;
    }
    $form->set('activePriceOptionIds', $activeOptionIds);
    $form->set('allPriceOption', $allOptions);
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
    $self->isEventFull();

    //To check if the user is already registered for the event(CRM-2426)
    $checked = $self->checkRegistration($fields, $self);
    if (is_array($checked)) {
      $errors += $checked;
    }

    // check full
    if($self->_isEventFull){
      if (!$self->_allowWaitlist && !$self->_allowConfirmation) {
        $errors['qfKey'] = $self->_values['event']['event_full_text'] ? $self->_values['event']['event_full_text'] : ts('This event is currently full.');
        return $errors;
      }
    }

    //check for availability of registrations.
    if (!$self->_allowConfirmation &&
      !CRM_Utils_Array::value('bypass_payment', $fields) &&
      is_numeric($self->_availableRegistrations) &&
      CRM_Utils_Array::value('additional_participants', $fields) >= $self->_availableRegistrations
    ) {
      $errors['additional_participants'] = ts("There is only enough space left on this event for %1 participant(s).", [1 => $self->_availableRegistrations]);
    }

    // during confirmation don't allow to increase additional participants, CRM-4320
    if ($self->_allowConfirmation &&
      CRM_Utils_Array::value('additional_participants', $fields) &&
      is_array($self->_additionalParticipantIds) &&
      $fields['additional_participants'] > count($self->_additionalParticipantIds)
    ) {
      $errors['additional_participants'] = ts("Oops. It looks like you are trying to increase the number of additional people you are registering for. You can confirm registration for a maximum of %1 additional people.", [1 => count($self->_additionalParticipantIds)]);
    }

    //don't allow to register w/ waiting if enough spaces available.
    if (CRM_Utils_Array::value('bypass_payment', $fields)) {
      if (!is_numeric($self->_availableRegistrations) ||
        (!CRM_Utils_Array::value('priceSetId', $fields) && CRM_Utils_Array::value('additional_participants', $fields) < $self->_availableRegistrations)
      ) {
        $errors['bypass_payment'] = ts("Oops. There are enough available spaces in this event. You can not add yourself to the waiting list.");
      }
    }

    if (CRM_Utils_Array::value('additional_participants', $fields) &&
      !CRM_Utils_Rule::positiveInteger($fields['additional_participants'])
    ) {
      $errors['additional_participants'] = ts('Please enter a whole number for Number of additional people.');
    }

    // priceset validations
    if (CRM_Utils_Array::value('priceSetId', $fields)) {
      //format params.
      $formatted = self::formatPriceSetParams($self, $fields);
      $ppParams = [$formatted];
      $priceSetErrors = self::validatePriceSet($self, $ppParams);
      $primaryParticipantCount = self::getParticipantCount($self, $ppParams);

      //get price set fields errors in.
      $errors = array_merge($errors, CRM_Utils_Array::value(0, $priceSetErrors, []));

      $totalParticipants = $primaryParticipantCount;
      if (CRM_Utils_Array::value('additional_participants', $fields)) {
        $totalParticipants += $fields['additional_participants'];
      }

      if (!CRM_Utils_Array::value('bypass_payment', $fields) &&
        !$self->_allowConfirmation &&
        is_numeric($self->_availableRegistrations) &&
        $self->_availableRegistrations < $totalParticipants
      ) {
        $errors['_qf_default'] = ts("Only %1 Registrations available.", [1 => $self->_availableRegistrations]);
      }

      $lineItem = [];

      CRM_Price_BAO_Set::processAmount($self->_values['fee'], $fields, $lineItem);
      if ($fields['amount'] < 0) {
        $errors['_qf_default'] = ts("Event Fee(s) can not be less than zero. Please select the options accordingly");
      }
    }

    if ($self->_values['event']['is_monetary']) {
      // validate coupon
      $couponErrors = CRM_Coupon_BAO_Coupon::checkError($self, $fields);
      if(!empty($couponErrors)){
        foreach ($couponErrors as $key => $value) {
          $errors[$key] = $value;
        }
      }

      if (is_array($self->_paymentProcessor)) {
        $payment = &CRM_Core_Payment::singleton($self->_mode, $self->_paymentProcessor, $self);
        $error = $payment->checkConfig($self->_mode);
        if ($error) {
          $errors['_qf_default'] = $error;
        }
      }
      // return if this is express mode
      $config = CRM_Core_Config::singleton();
      if ($self->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON) {
        if (CRM_Utils_Array::value($self->_expressButtonName . '_x', $fields) ||
          CRM_Utils_Array::value($self->_expressButtonName . '_y', $fields) ||
          CRM_Utils_Array::value($self->_expressButtonName, $fields)
        ) {
          return empty($errors) ? TRUE : $errors;
        }
      }

      // also return if paylater mode or zero fees for valid members
      if (CRM_Utils_Array::value('is_pay_later', $fields) ||
        CRM_Utils_Array::value('bypass_payment', $fields) ||
        (CRM_Utils_Array::value('priceSetId', $fields) && $fields['amount'] == '0') ||
        (!$self->_allowConfirmation && ($self->_requireApproval || $self->_allowWaitlist))
      ) {
        return empty($errors) ? TRUE : $errors;
      }
    }
    $self->addFieldRequiredRule($errors, $fields ,$files);

    // make sure that credit card number and cvv are valid

    if (CRM_Utils_Array::value('credit_card_type', $fields)) {
      if (CRM_Utils_Array::value('credit_card_number', $fields) &&
        !CRM_Utils_Rule::creditCardNumber($fields['credit_card_number'], $fields['credit_card_type'])
      ) {
        $errors['credit_card_number'] = ts("Please enter a valid Credit Card Number");
      }

      if (CRM_Utils_Array::value('cvv2', $fields) &&
        !CRM_Utils_Rule::cvv($fields['cvv2'], $fields['credit_card_type'])
      ) {
        $errors['cvv2'] = ts("Please enter a valid Credit Card Verification Number");
      }
    }

    $elements = ['email_greeting' => 'email_greeting_custom',
      'postal_greeting' => 'postal_greeting_custom',
      'addressee' => 'addressee_custom',
    ];
    foreach ($elements as $greeting => $customizedGreeting) {
      if ($greetingType = CRM_Utils_Array::value($greeting, $fields)) {
        $customizedValue = CRM_Core_OptionGroup::getValue($greeting, 'Customized', 'name');
        if ($customizedValue == $greetingType &&
          !CRM_Utils_Array::value($customizedGreeting, $fields)
        ) {
          $errors[$customizedGreeting] = ts('Custom %1 is a required field if %1 is of type Customized.',
            [1 => ucwords(str_replace('_', " ", $greeting))]
          );
        }
      }
    }

    // prevent double submission when free event
    if (empty($fields['additional_participants']) && !$self->_values['event']['is_monetary']) {
      $self->_preventMultipleSubmission = TRUE;
    }

    // Check discount priceset is correct.
    if (!empty($self->_values['discount'])) {
      $participantId = $self->get('participantId');
      $timestamp = self::getRegistrationTimestamp($participantId);
      $discountId = CRM_Core_BAO_Discount::findSet($self->_eventId, 'civicrm_event', $timestamp);
      if (!empty($discountId)) {
        if (!empty($self->_values['discount'][$discountId])) {
          if (!isset($self->_values['discount'][$discountId][$fields['amount']])) {
            $errors['amount'] = ts('The fee you selected has expired before submission. Please reselect the fee.');
          }
        }
      }
      else {
        if (!isset($self->_values['fee'][$fields['amount']])) {
          $errors['amount'] = ts('The fee you selected has expired before submission. Please reselect the fee.');
        }
      }
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

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    //set as Primary participant
    $params['is_primary'] = 1;
    if (!$this->_allowConfirmation) {
      // check if the participant is already registered
      $params['contact_id'] = self::getRegistrationContactID($params, $this, FALSE);
    }

    if (CRM_Utils_Array::value('image_URL', $params)) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    //hack to allow group to register w/ waiting
    $primaryParticipantCount = self::getParticipantCount($this, $params);

    $totalParticipants = $primaryParticipantCount;
    if (CRM_Utils_Array::value('additional_participants', $params)) {
      $totalParticipants += $params['additional_participants'];
    }

    if (!$this->_allowConfirmation && CRM_Utils_Array::value('bypass_payment', $params) &&
      is_numeric($this->_availableRegistrations) &&
      $totalParticipants > $this->_availableRegistrations
    ) {
      $this->_isOnWaitlist = TRUE;
      $this->set('isOnWaitlist', TRUE);
    }

    // skip pre-registered participant confirmtion to mark as waitlist
    if ($this->_allowWaitlist && empty($this->_participantId)) {
      if(($totalParticipants > $this->_availableRegistrations) || !is_numeric($this->_availableRegistrations)){
        $this->_isOnWaitlist = TRUE;
        $this->set('isOnWaitlist', TRUE);
      }
    }

    //carry participant id if pre-registered.
    if ($this->_allowConfirmation && $this->_participantId) {
      $params['participant_id'] = $this->_participantId;
    }

    $params['defaultRole'] = 1;
    if (CRM_Utils_Array::arrayKeyExists('participant_role_id', $params)) {
      $params['defaultRole'] = 0;
    }
    if (!CRM_Utils_Array::value('participant_role_id', $params) && $this->_values['event']['default_role_id']) {
      $params['participant_role_id'] = $this->_values['event']['default_role_id'];
    }

    $config = CRM_Core_Config::singleton();
    $params['currencyID'] = $config->defaultCurrency;

    if ($this->_values['event']['is_monetary']) {
      // we first reset the confirm page so it accepts new values
      $this->controller->resetPage('Confirm');

      //added for discount

      $participantId = $this->get('participantId');
      $timestamp = self::getRegistrationTimestamp($participantId);
      $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event', $timestamp);

      if (!empty($this->_values['discount'][$discountId])) {
        $params['discount_id'] = $discountId;
        $params['amount_level'] = $this->_values['discount'][$discountId][$params['amount']]['label'];

        $params['amount'] = $this->_values['discount'][$discountId][$params['amount']]['value'];
      }
      elseif (empty($params['priceSetId'])) {
        $params['amount_level'] = $this->_values['fee'][$params['amount']]['label'];
        $params['amount'] = $this->_values['fee'][$params['amount']]['value'];
      }
      else {
        $lineItem = [];

        CRM_Price_BAO_Set::processAmount($this->_values['fee'], $params, $lineItem);
        $this->set('lineItem', [$lineItem]);
        $this->set('lineItemParticipantsCount', [$primaryParticipantCount]);
      }

      $this->set('amount', $params['amount']);
      $this->set('amount_level', $params['amount_level']);

      // generate and set an invoiceID for this transaction
      $invoiceID = md5(uniqid((string)rand(), TRUE));
      $this->set('invoiceID', $invoiceID);

      if (is_array($this->_paymentProcessor)) {
        $payment = &CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
      }
      // default mode is notify
      $this->set('contributeMode', 'notify');

      if (isset($params["state_province_id-{$this->_bltID}"]) && $params["state_province_id-{$this->_bltID}"]) {
        $params["state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params["state_province_id-{$this->_bltID}"]);
      }

      if (isset($params["country_id-{$this->_bltID}"]) && $params["country_id-{$this->_bltID}"]) {
        $params["country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($params["country_id-{$this->_bltID}"]);
      }
      if (isset($params['credit_card_exp_date'])) {
        $params['year'] = $params['credit_card_exp_date']['Y'];
        $params['month'] = $params['credit_card_exp_date']['M'];
      }
      if ($this->_values['event']['is_monetary']) {
        $params['ip_address'] = CRM_Utils_System::ipAddress();
        $params['currencyID'] = $config->defaultCurrency;
        $params['payment_action'] = 'Sale';
        $params['invoiceID'] = $invoiceID;
      }
      if (($this->_values['is_pay_later'] && empty($this->_paymentProcessor) && !CRM_Utils_Array::arrayKeyExists('hidden_processor', $params)) || CRM_Utils_Array::value('payment_processor', $params) == 0) {
        $params['is_pay_later'] = 1;
      }
      else {
        $params['is_pay_later'] = 0;
      }

      $this->_params = [];
      $this->_params[] = $params;
      $this->set('params', $this->_params);


      CRM_Coupon_BAO_Coupon::countAmount($this, $params);
      if(!empty($this->_usedOptionsDiscount)){
        foreach ($this->_usedOptionsDiscount as $key => $value) {
          $this->_lineItem[0][$key]['discount'] = $value;
        }
        $this->set('usedOptionsDiscount', $this->_usedOptionsDiscount);
      }
      $this->set('totalDiscount', $this->_totalDiscount);
      $this->set('couponDescription', $this->_coupon['description']);


      if ($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON) {
        //get the button name
        $buttonName = $this->controller->getButtonName();
        if (in_array($buttonName,
            [$this->_expressButtonName, $this->_expressButtonName . '_x', $this->_expressButtonName . '_y']
          ) &&
          !isset($params['is_pay_later']) &&
          !$this->_allowWaitlist && !$this->_requireApproval
        ) {
          $this->set('contributeMode', 'express');

          // Send Event Name & Id in Params
          $params['eventName'] = $this->_values['event']['title'];
          $params['eventId'] = $this->_values['event']['id'];

          $params['cancelURL'] = CRM_Utils_System::url('civicrm/event/register',
            "_qf_Register_display=1&qfKey={$this->controller->_key}",
            TRUE, NULL, FALSE
          );
          if (CRM_Utils_Array::value('additional_participants', $params, FALSE)) {
            $urlArgs = "_qf_Participant_1_display=1&rfp=1&qfKey={$this->controller->_key}";
          }
          else {
            $urlArgs = "_qf_Confirm_display=1&rfp=1&qfKey={$this->controller->_key}";
          }
          $params['returnURL'] = CRM_Utils_System::url('civicrm/event/register',
            $urlArgs,
            TRUE, NULL, FALSE
          );
          $params['invoiceID'] = $invoiceID;

          //default action is Sale
          $params['payment_action'] = 'Sale';

          $token = $payment->setExpressCheckout($params);
          if (is_a($token, 'CRM_Core_Error')) {
            CRM_Core_Error::displaySessionError($token);
            CRM_Utils_System::redirect($params['cancelURL']);
          }

          $this->set('token', $token);

          $paymentURL = $this->_paymentProcessor['url_site'] . "/cgi-bin/webscr?cmd=_express-checkout&token=$token";

          CRM_Utils_System::redirect($paymentURL);
        }
      }
      elseif ($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_NOTIFY) {
        $this->set('contributeMode', 'notify');
      }
      elseif ($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_IFRAME) {
        $this->set('contributeMode', 'iframe');
      }
    }
    else {
      $session = CRM_Core_Session::singleton();
      $params['description'] = ts('Online Event Registration') . ' ' . $this->_values['event']['title'];

      $this->_params = [];
      $this->_params[] = $params;
      $this->set('params', $this->_params);

      if (!CRM_Utils_Array::value('additional_participants', $params)) {
        $this->processRegistration($this->_params, $contactID);
      }
    }

    // If registering > 1 participant, give status message
    if (CRM_Utils_Array::value('additional_participants', $params, FALSE)) {

      $statusMsg = ts('Registration information for participant 1 has been saved.');
      CRM_Core_Session::setStatus("{$statusMsg}");
    }
  }
  //end of function

  /**
   * Method to check if the user is already registered for the event
   * and if result found redirect to the event info page
   *
   * @param array $fields  the input form values(anonymous user)
   * @param array $self    event data
   * @param boolean $isAdditional  if it's additional participant
   *
   * @return void
   * @access public
   */
  static function checkRegistration($fields, &$self, $isAdditional = FALSE) {
    if ($self->_mode == 'test') {
      return FALSE;
    }

    $contactID = NULL;
    $contactID = self::getRegistrationContactID($fields, $self, $isAdditional);

    // implement hook for change registrion check
    CRM_Utils_Hook::checkRegistration($contactID, $fields, $self, $isAdditional, $forceAllowedRegister);
    if (!empty($forceAllowedRegister)) {
      return $forceAllowedRegister;
    }

    // skip check when confirm by mail link
    if ($self->_allowConfirmation) {
      if ($isAdditional) {
        // refs #32662,return false means additional participant already registered
        return TRUE;
      }
      return FALSE;
    }
    if ($contactID) {
      $session = CRM_Core_Session::singleton();

      // check if contact exists but email not the same
      if (isset($fields['_qf_default']) && $fields['cms_create_account']) {
        $dao = new CRM_Core_DAO_UFMatch();
        $dao->contact_id = $contactID;
        $dao->find(TRUE);
        if (!empty($dao->uf_name) && ($dao->uf_name !== $fields['email-5'])) {
          // errors because uf_name(email) will update to new value
          // then the drupal duplicate email check may failed
          // we should validate and stop here before confirm stage
          $url = CRM_Utils_System::url('user', "destination=" . urlencode("civicrm/event/register?reset=1&id={$self->_values['event']['id']}"));
          return ['email-5' => ts('Accroding your profile, you are one of our registered user. Please <a href="%1">login</a> to proceed.', [1 => $url])];
        }
      }


      $participant = new CRM_Event_BAO_Participant();
      $participant->contact_id = $contactID;
      $participant->event_id = $self->_values['event']['id'];
      $participant->role_id = $self->_values['event']['default_role_id'];
      $participant->is_test = 0;

      $participant->find();

      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 1");
      while ($participant->fetch()) {
        if (CRM_Utils_Array::arrayKeyExists($participant->status_id, $statusTypes)) {
          if (!$isAdditional) {
            $registerUrl = CRM_Utils_System::url('civicrm/event/register',
              "reset=1&id={$self->_values['event']['id']}&cid=0"
            );
            $status = ts("Oops. It looks like you are already registered for this event. If you want to change your registration, or you feel that you've gotten this message in error, please contact the site administrator.") . ' ' . ts('You can also <a href="%1">register another participant</a>.', [1 => $registerUrl]);
            $session->setStatus($status);
            $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$self->_values['event']['id']}&noFullMsg=true");
            if ($self->_action & CRM_Core_Action::PREVIEW) {
              $url .= '&action=preview';
            }
            CRM_Utils_System::redirect($url);
          }

          if ($isAdditional) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  public static function getRegistrationContactID($fields, $self, $isAdditional){
    $contactID = NULL;
    if (!$isAdditional) {
      $contactID = $self->getContactID();
    }
    if (!$contactID && is_array($fields) && !empty($fields)) {
      //CRM-6996
      //as we are allowing w/ same email address,
      //lets check w/ other contact params.
      $params = $fields;
      $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');

      // disable permission based on cache since event registration is public page/feature.
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
      $contactID = CRM_Utils_Array::value(0, $ids);
    }
    return $contactID;
  }

  public function getTitle() {
    return ts('Register for Event');
  }

  static function getRegistrationTimestamp($participantId) {
    if (!empty($participantId)) {
      $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
      $activityId = CRM_Utils_Array::key('Event Registration', $activityTypes);
      $registerDate = CRM_Core_DAO::singleValueQuery('SELECT activity_date_time FROM civicrm_activity WHERE source_record_id = %1 AND activity_type_id = %2 ORDER BY activity_date_time ASC LIMIT 1', [
        1 => [$participantId, 'Positive'],
        2 => [$activityId, 'Positive'],
      ]);
      $timestamp = strtotime($registerDate);
    }
    else {
      $timestamp = CRM_REQUEST_TIME;
    }
    return $timestamp;
  }
}

