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
 * This class generates form components for processing a ontribution
 *
 */
class CRM_Contribute_Form_ContributionBase extends CRM_Core_Form {

  public $_mid;
  /**
   * @var int
   */
  public $_defaultMemTypeId;
  public $_paymentProcessors;
  public $_contributeMode;
  public $_defaultFromRequest;
  /**
   * the id of the contribution page that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * Prevent multiple submission
   *
   * @var Boolean
   * @protected
   */
  protected $_preventMultipleSubmission;

  /**
   * the contact id related to a membership
   *
   * @var int
   * @public
   */
  public $_membershipContactID;

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
  protected $_paymentObject = NULL;

  /**
   * The membership block for this page
   *
   * @var array
   * @protected
   */
  public $_membershipBlock = NULL;

  /**
   * the default values for the form
   *
   * @var array
   * @protected
   */
  protected $_defaults;

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
   * @public
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
   * Cache the amount to make things easier
   *
   * @var float
   * @public
   */
  public $_amount;

  /**
   * pcp id
   *
   * @var integer
   * @public
   */
  public $_pcpId;

  /**
   * pcp block
   *
   * @var array
   * @public
   */
  public $_pcpBlock;

  /**
   * pcp info
   *
   * @var array
   * @public
   */
  public $_pcpInfo;

  protected $_userID;

  protected $_originalId;

  public $_originalValues;

  /**
   * the Membership ID for membership renewal
   *
   * @var int
   * @public
   */
  public $_membershipId;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   * @protected
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set
   *
   * @var array
   * @protected
   */
  public $_priceSet;

  public $_action;
  protected $_uploadedFiles;

  public $_contributionID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    // current contribution page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this
    );
    if (!$this->_id) {
      $pastContributionID = $session->get('pastContributionID');
      if (!$pastContributionID) {
        return CRM_Core_Error::statusBounce(ts('We can\'t load the requested web page due to an incomplete link. This can be caused by using your browser\'s Back button or by using an incomplete or invalid link.'));
      }
      else {
        return CRM_Core_Error::statusBounce(ts('This contribution has already been submitted. Click <a href=\'%1\'>here</a> if you want to make another contribution.', [1 => CRM_Utils_System::url('civicrm/contribute/transact', 'reset=1&id=' . $pastContributionID)]));
      } 
    }
    else {
      $session->set('pastContributionID', $this->_id);
    }

    // #25950, do not force userID to current logged user. 
    // this will cause user data being overwrite by some admin
    // instead use session based data storage to check main state contact id
    $this->_userID = $this->get('csContactID') ? $this->get('csContactID') : $this->get('userID');
    $this->_mid = NULL;
    if ($this->_userID) {
      $this->assign('contact_id', $this->_userID);
      if(CRM_Core_Permission::check('edit all contacts')){
        $this->assign('is_contact_admin', 1);
      }
      else{
        $this->assign('is_contact_admin', 0);
      }
      if ($this->get('originalId')) {
        $this->_originalId = $this->get('originalId');
        $this->assign('originalId', $this->_originalId);
      }
      $this->_mid = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
      if ($this->_mid) {

        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $this->_mid;

        if ($membership->find(TRUE)) {
          $this->_defaultMemTypeId = $membership->membership_type_id;
          if ($membership->contact_id != $this->_userID) {

            $employers = CRM_Contact_BAO_Relationship::getPermissionedEmployer($this->_userID);
            if (CRM_Utils_Array::arrayKeyExists($membership->contact_id, $employers)) {
              $this->_membershipContactID = $membership->contact_id;
              $this->assign('membershipContactID', $this->_membershipContactID);
              $this->assign('membershipContactName', $employers[$this->_membershipContactID]['name']);
            }
            else {
              CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."));
            }
          }
        }
        else {
          CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."));
        }
        unset($membership);
      }
    }

    // assign id
    $this->assign('id', $this->_id);

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
    // Contribution page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->assign('action', $this->_action);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';
    $this->assign('browserPrint', TRUE);

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->_bltID = $this->get('bltID');
    $this->_paymentProcessor = $this->get('paymentProcessor');
    $this->_priceSetId = $this->get('priceSetId');
    $this->_priceSet = $this->get('priceSet');
    $this->_contributionID = $this->get('contributionID');

    if (!$this->_values) {
      // get all the values from the dao object
      $this->_values = [];
      $this->_fields = [];


      CRM_Contribute_BAO_ContributionPage::setValues($this->_id, $this->_values);

      $premiumParams = [
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      ];
      $premiumDefault = [];
      CRM_Contribute_BAO_Premium::commonRetrieve('CRM_Contribute_DAO_Premium', $premiumParams, $premiumDefault);
      if (!empty($premiumDefault['premiums_active'])) {
        $this->_values['premiums_active'] = $premiumDefault['premiums_active'];
        $this->_values['premiums_intro_title'] = $premiumDefault['premiums_intro_title'];
        $this->_values['premiums_display_min_contribution'] = $premiumDefault['premiums_display_min_contribution'];
      }

      // check if form is active
      if (!$this->_values['is_active']) {
        if ($this->_action != CRM_Core_Action::PREVIEW || !CRM_Core_Permission::check('access CiviContribute')) {
          // form is inactive, die a fatal death
          $config = CRM_Core_Config::singleton();
          $pageId = $config->defaultRenewalPageId;
          if ($pageId) {
            // Handle utm params
            $utmParams = ['utm_source', 'utm_medium', 'utm_term', 'utm_content', 'utm_campaign'];
            $queryParts = [];
            foreach ($utmParams as $param) {
              if (!empty($_GET[$param])) {
                $queryParts[] = $param . '=' . urlencode($_GET[$param]);
              }
            }
            $queryParts[] = 'reset=1';
            $queryParts[] = 'id=' . $pageId;
            $queryString = implode('&', $queryParts);
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', $queryString));
          } else {
            return CRM_Core_Error::statusBounce(ts('The page you requested is currently unavailable.'));
          }
        }
      }
      else {
        if ($this->_values['is_internal'] > 0 && !CRM_Core_Permission::check('access CiviContribute')) {
          $config = CRM_Core_Config::singleton();
          $pageId = $config->defaultRenewalPageId;
          $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
          if ($pageId && $contactId) {
            $oid = 0;
            $contactTypeSql = "SELECT contact_type FROM civicrm_contact WHERE id = %1";
            $contactTypeParams = [1 => [$contactId, 'Integer']];
            $contactType = CRM_Core_DAO::singleValueQuery($contactTypeSql, $contactTypeParams);
            if ($contactType == 'Individual') {
              $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId);
              CRM_Utils_System::redirect( CRM_Utils_System::url('civicrm/contribute/transact', "reset=1&id=$pageId&cid=$contactId&oid=$oid&cs=$cs",true));
            }
          }
          return CRM_Core_Error::statusBounce(ts('The page you requested is currently unavailable.'));
        }
      }

      if (($this->_values['is_active'] & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL ) && empty($this->_values['custom_post_id']) && empty($this->_values['custom_pre_id'])) {
        return CRM_Core_Error::statusBounce(ts("You may want to collect information from contributors beyond what is required to make a contribution. For example, you may want to inquire about volunteer availability and skills. Add any number of fields to your contribution form by selecting CiviCRM Profiles (collections of fields) to include at the beginning of the page, and/or at the bottom."));
      }

      // also check for billing informatin
      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
      $this->_bltID = array_search('Billing', $locationTypes);
      if (!$this->_bltID) {
        return CRM_Core_Error::statusBounce(ts('Please set a location type of %1', [1 => 'Billing']));
      }
      $this->set('bltID', $this->_bltID);

      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values);
      $isPayLater = CRM_Utils_Array::value('is_pay_later', $this->_values);

      if ($isMonetary && (!$isPayLater || CRM_Utils_Array::value('payment_processor', $this->_values))) {
        $ppID = CRM_Utils_Array::value('payment_processor', $this->_values);
        if (!$ppID) {
           return CRM_Core_Error::statusBounce(ts('A payment processor must be selected for this contribution page or must be configured to give users the option to pay later.'));
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

        if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
          foreach ($this->_paymentProcessors as $eachPaymentProcessor) {
            // check selected payment processor is active
            if (empty($eachPaymentProcessor)) {
               return CRM_Core_Error::statusBounce(ts('A payment processor configured for this page might be disabled (contact the site administrator for assistance).'));
            }

            // ensure that processor has a valid config
            $this->_paymentObject = &CRM_Core_Payment::singleton($this->_mode, $eachPaymentProcessor, $this);
            $error = $this->_paymentObject->checkConfig();
            if (!empty($error)) {
               return CRM_Core_Error::statusBounce($error);
            }
          }
        }
      }

      // get price info
      // CRM-5095

      CRM_Price_BAO_Set::initSet($this, $this->_id, 'civicrm_contribution_page');


      // this avoids getting E_NOTICE errors in php
      $setNullFields = ['amount_block_is_active',
        'honor_block_is_active',
        'is_allow_other_amount',
        'footer_text',
      ];
      foreach ($setNullFields as $f) {
        if (!isset($this->_values[$f])) {
          $this->_values[$f] = NULL;
        }
      }

      //check if Membership Block is enabled, if Membership Fields are included in profile
      //get membership section for this contribution page

      $this->_membershipBlock = CRM_Member_BAO_Membership::getMembershipBlock($this->_id);
      $this->set('membershipBlock', $this->_membershipBlock);


      if ($this->_values['custom_pre_id']) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if ($this->_values['custom_post_id']) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);
      }
      // also set cancel subscription url
      if (CRM_Utils_Array::value('is_recur', $this->_paymentProcessor) &&
        CRM_Utils_Array::value('is_recur', $this->_values)
      ) {
        $this->_values['cancelSubscriptionUrl'] = $this->_paymentObject->cancelSubscriptionURL();
      }
      if (((isset($postProfileType) && $postProfileType == 'Membership') ||
          (isset($preProfileType) && $preProfileType == 'Membership')
        ) &&
        !$this->_membershipBlock['is_active']
      ) {
         return CRM_Core_Error::statusBounce(ts('This page includes a Profile with Membership fields - but the Membership Block is NOT enabled. Please notify the site administrator.'));
      }


      $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($this->_id);

      if ($pledgeBlock) {
        $this->_values['pledge_block_id'] = CRM_Utils_Array::value('id', $pledgeBlock);
        $this->_values['max_reminders'] = CRM_Utils_Array::value('max_reminders', $pledgeBlock);
        $this->_values['initial_reminder_day'] = CRM_Utils_Array::value('initial_reminder_day', $pledgeBlock);
        $this->_values['additional_reminder_day'] = CRM_Utils_Array::value('additional_reminder_day', $pledgeBlock);

        //set pledge id in values
        $pledgeId = CRM_Utils_Request::retrieve('pledgeId', 'Positive', $this);

        //authenticate pledge user for pledge payment.
        if ($pledgeId) {
          $this->_values['pledge_id'] = $pledgeId;
          $this->authenticatePledgeUser();
        }
      }
      //retrieve custom field information
      $groupTree = &CRM_Core_BAO_CustomGroup::getTree("ContributionPage", $this, $this->_id, 0, $this->_values['contribution_type_id']);
      $this->_values['custom_data_view'] = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);
      $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_id, 'ContributionPage');
      if (!empty($customValues)) {
        foreach($customValues as $customFieldId => $val) {
          $this->_values['custom_'.$customFieldId] = $val;
        }
      }

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }
    else{
      // assign tempalte 
      $this->assign_by_ref("viewCustomData", $this->_values['custom_data_view']);
    }


    $pcpId = CRM_Utils_Request::retrieve('pcpId', 'Positive', $this);
    if ($pcpId) {

      $approvedId = CRM_Core_OptionGroup::getValue('pcp_status', 'Approved', 'name');

      $prms = ['entity_id' => $this->_values['id'],
        'entity_table' => 'civicrm_contribution_page',
      ];

      $pcpStatus = CRM_Contribute_PseudoConstant::pcpStatus();
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCPBlock',
        $prms,
        $pcpBlock
      );
      $prms = ['id' => $pcpId];
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCP', $prms, $pcpInfo);

      //start and end date of the contribution page
      $startDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('start_date', $this->_values));
      $endDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('end_date', $this->_values), true);
      $now = time();

      if ($pcpInfo['contribution_page_id'] != $this->_values['id']) {
        $statusMessage = ts('This contribution page is not related to the Personal Campaign Page you have just visited. However you can still make a contribution here.');
         return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
            "reset=1&id={$this->_values['id']}",
            FALSE, NULL, FALSE, TRUE
          ));
      }
      elseif ($pcpInfo['status_id'] != $approvedId) {
        $statusMessage = ts('The Personal Campaign Page you have just visited is currently %1. However you can still support the campaign by making a contribution here.', [1 => $pcpStatus[$pcpInfo['status_id']]]);
         return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
            "reset=1&id={$pcpInfo['contribution_page_id']}",
            FALSE, NULL, FALSE, TRUE
          ));
      }
      elseif (!CRM_Utils_Array::value('is_active', $pcpBlock)) {
        $statusMessage = ts('Personal Campaign Pages are currently not enabled for this contribution page. However you can still support the campaign by making a contribution here.');
         return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
            "reset=1&id={$pcpInfo['contribution_page_id']}",
            FALSE, NULL, FALSE, TRUE
          ));
      }
      elseif (!CRM_Utils_Array::value('is_active', $pcpInfo)) {
        $statusMessage = ts('The Personal Campaign Page you have just visited is current inactive. However you can still make a contribution here.');
         return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
            "reset=1&id={$pcpInfo['contribution_page_id']}",
            FALSE, NULL, FALSE, TRUE
          ));
      }
      elseif (($startDate && $startDate > $now) || ($endDate && $endDate < $now)) {
        $customStartDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('start_date', $this->_values));
        $customEndDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('end_date', $this->_values));
        if ($startDate && $endDate) {
          $statusMessage = ts('The Personal Campaign Page you have just visited is only active between %1 to %2. However you can still support the campaign by making a contribution here.',
            [1 => $customStartDate, 2 => $customEndDate]
          );
           return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
              "reset=1&id={$pcpInfo['contribution_page_id']}",
              FALSE, NULL, FALSE, TRUE
            ));
        }
        elseif ($startDate) {
          $statusMessage = ts('The Personal Campaign Page you have just visited will be active beginning on %1. However you can still support the campaign by making a contribution here.', [1 => $customStartDate]);
           return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
              "reset=1&id={$pcpInfo['contribution_page_id']}",
              FALSE, NULL, FALSE, TRUE
            ));
        }
        elseif ($endDate) {
          $statusMessage = ts('The Personal Campaign Page you have just visited is not longer active (as of %1). However you can still support the campaign by making a contribution here.', [1 => $customEndDate]);
           return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
              "reset=1&id={$pcpInfo['contribution_page_id']}",
              FALSE, NULL, FALSE, TRUE
            ));
        }
      }

      $this->_pcpId = $pcpId;
      $this->_pcpBlock = $pcpBlock;
      $this->_pcpInfo = $pcpInfo;
    }

    // Link (button) for users to create their own Personal Campaign page
    if ($linkText = CRM_Contribute_BAO_PCP::getPcpBlockStatus($this->_id)) {
      $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
        "action=add&reset=1&pageId={$this->_id}",
        FALSE, NULL, TRUE
      );
      $this->assign('linkTextUrl', $linkTextUrl);
      $this->assign('linkText', $linkText);
    }

    //set pledge block if block id is set
    if (CRM_Utils_Array::value('pledge_block_id', $this->_values)) {
      $this->assign('pledgeBlock', TRUE);
    }

    $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);

    // check if one of the (amount , membership)  bloks is active or not

    $this->_membershipBlock = $this->get('membershipBlock');

    if (!$this->_values['amount_block_is_active'] &&
      !$this->_membershipBlock['is_active'] &&
      !$this->_priceSetId
    ) {
       return CRM_Core_Error::statusBounce(ts('The requested online contribution page is missing a required Contribution Amount section or Membership section or Price Set. Please check with the site administrator for assistance.'));
    }

    if ($this->_values['amount_block_is_active']) {
      $this->set('amount_block_is_active', $this->_values['amount_block_is_active']);
    }

    if (!empty($this->_membershipBlock) &&
      CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock) &&
      (!($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_FORM))
    ) {
       return CRM_Core_Error::statusBounce(ts('This contribution page is configured to support separate contribution and membership payments. This %1 plugin does not currently support multiple simultaneous payments. Please contact the site administrator and notify them of this error',
          [1 => $this->_paymentProcessor['payment_processor_type']]
        ));
    }

    $this->_contributeMode = $this->get('contributeMode');
    $this->assign('contributeMode', $this->_contributeMode);

    //assigning is_monetary and is_email_receipt to template
    $this->assign('is_monetary', $this->_values['is_monetary']);
    $this->assign('is_email_receipt', $this->_values['is_email_receipt']);
    $this->assign('receipt_from_email', $this->_values['receipt_from_email']);
    $this->assign('bltID', $this->_bltID);

    //assign cancelSubscription URL to templates
    $this->assign('cancelSubscriptionUrl',
      CRM_Utils_Array::value('cancelSubscriptionUrl', $this->_values)
    );

    // assigning title to template in case someone wants to use it, also setting CMS page title
    if ($this->_pcpId) {
      $this->assign('title', $pcpInfo['title']);
      CRM_Utils_System::setTitle($pcpInfo['title']);
    }
    else {
      $this->assign('title', $this->_values['title']);
      CRM_Utils_System::setTitle($this->_values['title']);
    }
    $this->_defaults = [];

    $this->_amount = $this->get('amount');

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );

    //do check for cancel recurring and clean db, CRM-7696
    if (CRM_Utils_Request::retrieve('cancel', 'Boolean', CRM_Core_DAO::$_nullObject)) {
      self::cancelRecurring();
    }

    if($_GET['style'] == 'origin'){
      $this->set('style', 'origin');
    }
    if($this->_values['is_active'] & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL && $_GET['snippet'] != 4 && $this->get('style') != 'origin'){
      $bgFile = basename($this->_values['background_URL']);
      $bgFileMobile = basename($this->_values['mobile_background_URL']);
      $this->assign('intro_text', $this->_values['intro_text']);
      $this->assign('backgroundImageUrl', str_replace($bgFile, urlencode($bgFile), $this->_values['background_URL']));
      $this->assign('mobileBackgroundImageUrl', str_replace($bgFileMobile, urlencode($bgFileMobile), $this->_values['mobile_background_URL']));
      $this->assign('special_style', 1);
      $this->assign('min_amount', (float) $this->_values['min_amount']);
      $this->assign('max_amount', (float) $this->_values['max_amount']);
      $object = [
        'tag' => 'link',
        'attributes' =>  [
          'rel' => 'stylesheet',
          'href' => $config->resourceBase.'css/contribution_page.css?v'.$config->ver,
        ],
      ];
      CRM_Utils_System::addHTMLHead($object);
    }


    // tracking click
    $this->_ppType = CRM_Utils_Array::value('type', $_GET);
    if (!$this->_ppType) {
      if (empty($this->_values['is_internal'])) {
        $this->track();
      }
    }
  }

  /**
   * set the default values
   *
   * @return void
   * @access public
   */
  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
    $name = CRM_Utils_Array::value('billing_first_name', $this->_params);
    if (CRM_Utils_Array::value('billing_middle_name', $this->_params)) {
      $name .= " {$this->_params['billing_middle_name']}";
    }
    $name .= ' ' . CRM_Utils_Array::value('billing_last_name', $this->_params);
    $name = trim($name);
    $this->assign('billingName', $name);
    $this->set('name', $name);

    $this->assign('paymentProcessor', $this->_paymentProcessor);

    $vars = ['amount', 'currencyID',
      'credit_card_type', 'trxn_id', 'amount_level',
    ];

    $config = CRM_Core_Config::singleton();
    if (isset($this->_values['is_recur']) &&
      $this->_paymentProcessor['is_recur']
    ) {
      $this->assign('is_recur_enabled', 1);
      $vars = array_merge($vars, ['is_recur', 'frequency_interval', 'frequency_unit',
          'installments',
        ]);
    }

    if (in_array('CiviPledge', $config->enableComponents) &&
      CRM_Utils_Array::value('is_pledge', $this->_params) == 1
    ) {
      $this->assign('pledge_enabled', 1);

      $vars = array_merge($vars, ['is_pledge',
          'pledge_frequency_interval',
          'pledge_frequency_unit',
          'pledge_installments',
        ]);
    }

    if (!empty($this->_params['amount_other']) || isset($this->_params['selectMembership'])) {
      $this->_params['amount_level'] = '';
    }

    foreach ($vars as $v) {
      if (CRM_Utils_Array::value($v, $this->_params)) {
        if ($v == 'frequency_unit' || $v == 'pledge_frequency_unit') {
          $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
          if (CRM_Utils_Array::arrayKeyExists($this->_params[$v], $frequencyUnits)) {
            // This is a bug for recurring unit translations. refs #4670
            $this->assign($v, ts($this->_params[$v]));
          }
        }
        else {
          $this->assign($v, $this->_params[$v]);
        }
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
      $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $this->_params);
    }


    $this->assign('address', CRM_Utils_Address::format($addressFields));

    if (CRM_Utils_Array::value('is_for_organization', $this->_params)) {
      $onBehalfParams = [
        ts("Organization Name") => $this->_params['organization_name'],
        ts('SIC Code') => $this->_params['sic_code'],
        ts("Email") => $this->_params['onbehalf_location']['email'][1]['email'],
        ts("Phone Number") => $this->_params['onbehalf_location']['phone'][1]['phone'],
        ts("Address") => CRM_Utils_Address::format($this->_params['onbehalf_location']['address'][1], NULL, FALSE, TRUE),
      ];
      $this->assign('onBehalfParams', $onBehalfParams);
    }

    //fix for CRM-3767
    $assignCCInfo = FALSE;
    if ($this->_amount > 0.0) {
      $assignCCInfo = TRUE;
    }
    elseif (CRM_Utils_array::value('selectMembership', $this->_params)) {
      $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
      if ($memFee > 0.0) {
        $assignCCInfo = TRUE;
      }
    }

    if ($this->_contributeMode == 'direct' && $assignCCInfo) {
      if ($this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT) {
        $this->assign('payment_type', $this->_paymentProcessor['payment_type']);
        $this->assign('account_holder', $this->_params['account_holder']);
        $this->assign('bank_identification_number', $this->_params['bank_identification_number']);
        $this->assign('bank_name', $this->_params['bank_name']);
        $this->assign('bank_account_number', $this->_params['bank_account_number']);
      }
      else {
        $date = CRM_Utils_Date::format($this->_params['credit_card_exp_date']);
        $date = CRM_Utils_Date::mysqlToIso($date);
        $this->assign('credit_card_exp_date', $date);
        $this->assign('credit_card_number',
          CRM_Utils_System::mungeCreditCard($this->_params['credit_card_number'])
        );
      }
    }

    $this->assign('email',
      $this->controller->exportValue('Main', "email-{$this->_bltID}")
    );

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }

    // assign pay later stuff
    $this->_params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $this->_params, FALSE);
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_text', $this->_values['pay_later_text']);
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
  }

  /**
   * Function to add the custom fields
   *
   * @return None
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE) {
    $stateCountryMap = [];

    if ($id) {


      $contactID = $this->_userID;

      // we don't allow conflicting fields to be
      // configured via profile - CRM 2100
      $fieldsToIgnore = ['receive_date' => 1,
        'trxn_id' => 1,
        'invoice_id' => 1,
        'net_amount' => 1,
        'fee_amount' => 1,
        'non_deductible_amount' => 1,
        'total_amount' => 1,
        'amount_level' => 1,
        'contribution_status_id' => 1,
        'payment_instrument' => 1,
        'check_number' => 1,
        'contribution_type' => 1,
      ];

      $fields = NULL;
      if ($contactID) {

        if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD);
      }

      if ($fields) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-') {
            if(!$this->isAssigned('moveEmail')){
              $this->assign('moveEmail', 'profile-group-'.$fields[$fieldName]['group_id']);
            }
            unset($fields[$fieldName]);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          if (CRM_Core_Permission::check('access CiviContribute')) {
            CRM_Core_Session::setStatus(ts("Some of the profile fields cannot be configured for this page."));
          }
        }

        $fields = array_diff_assoc($fields, $this->_fields);
        $this->assign($name, $fields);

        $addCaptcha = FALSE;
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // change file upload description
            $this->_uploadedFiles[$key] = $field['name'];
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
          if ($field['add_captcha']) {
            $addCaptcha = TRUE;
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
  }

  function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/Contribute/Form/Contribution/{$this->_id}/{$this->_name}.tpl";
      $template = &CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }

  /**
   * Function to authenticate pledge user during online payment.
   *
   * @access public
   *
   * @return None
   */
  public function authenticatePledgeUser() {
    //get the userChecksum and contact id
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //get pledge status and contact id
    $pledgeValues = [];
    $pledgeParams = ['id' => $this->_values['pledge_id']];
    $returnProperties = ['contact_id', 'status_id'];
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_DAO_Pledge', $pledgeParams, $pledgeValues, $returnProperties);

    //get all status

    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $validStatus = [array_search('Pending', $allStatus),
      array_search('In Progress', $allStatus),
      array_search('Overdue', $allStatus),
    ];

    $validUser = FALSE;
    if ($this->_userID &&
      $this->_userID == $pledgeValues['contact_id']
    ) {
      //check for authenticated  user.
      $validUser = TRUE;
    }
    elseif ($userChecksum && $pledgeValues['contact_id']) {
      //check for anonymous user.

      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($pledgeValues['contact_id'], $userChecksum);

      //make sure cid is same as pledge contact id
      if ($validUser && ($pledgeValues['contact_id'] != $contactID)) {
        $validUser = FALSE;
      }
    }

    if (!$validUser) {
      CRM_Core_Error::fatal(ts("Oops. It looks like you have an incorrect or incomplete link (URL). Please make sure you've copied the entire link, and try again. Contact the site administrator if this error persists."));
    }

    //check for valid pledge status.
    if (!in_array($pledgeValues['status_id'], $validStatus)) {
      CRM_Core_Error::fatal(ts('Oops. You cannot make a payment for this pledge - pledge status is %1.', [1 => CRM_Utils_Array::value($pledgeValues['status_id'], $allStatus)]));
    }
  }

  /**
   * In case user cancel recurring contribution,
   * When we get the control back from payment gate way
   * lets delete the recurring and related contribution.
   *
   **/
  public function cancelRecurring() {
    $isCancel = CRM_Utils_Request::retrieve('cancel', 'Boolean', CRM_Core_DAO::$_nullObject);
    if ($isCancel) {
      $isRecur = CRM_Utils_Request::retrieve('isRecur', 'Boolean', CRM_Core_DAO::$_nullObject);
      $recurId = CRM_Utils_Request::retrieve('recurId', 'Positive', CRM_Core_DAO::$_nullObject);
      //clean db for recurring contribution.
      if ($isRecur && $recurId) {

        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($recurId);
      }
      $contribId = CRM_Utils_Request::retrieve('contribId', 'Positive', CRM_Core_DAO::$_nullObject);
      if ($contribId) {

        CRM_Contribute_BAO_Contribution::deleteContribution($contribId);
      }
    }
  }

  public function track($pageName = '') {
    $page_id = $this->_values['id'];
    if (empty($pageName)) {
      $actionName = $this->controller->getActionName();
      list($pageName, $action) = $actionName;
    }
    $pageName = strtolower($pageName);
    $state = [
      'main' => 1,
      'confirm' => 2,
      'payment' => 3,
      'thankyou' => 4
    ];
    $params = [
      'state' => $state[$pageName],
      'page_type' => 'civicrm_contribution_page',
      'page_id' => $page_id,
      'visit_date' => date('Y-m-d H:i:s'),
    ];
    if (!empty($this->_contributionID)) {
      $params['entity_table'] = 'civicrm_contribution';
      $params['entity_id'] = $this->_contributionID;
    }
    $track = CRM_Core_BAO_Track::add($params);
  }

  /**
   * Load custom field default value from original contribution id
   * 
   * @id  int
   *   original contribution id 
   */
  public function loadDefaultFromOriginalId($id = NULL) {
    $this->_originalId = NULL;
    if (empty($id)) {
      // original id, it's previous contribution
      $id = CRM_Utils_Request::retrieve('oid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    }
    if ($id) {
      $original = new CRM_Contribute_DAO_Contribution();
      $original->id = $id;
      if ($original->find(TRUE)) {
        // double check oid is from same contact
        if($original->contact_id == $this->_userID) {
          $this->set('originalId', $id);
          $this->assign('originalId', $id);
          $this->_originalId = $id;
          $selectedProductId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionProduct', $id, 'product_id', 'contribution_id' );
          $defaultFromRequest = $this->get('defaultFromRequest');
          if (!isset($defaultFromRequest['amt'])) $defaultFromRequest['amt'] = (int) $original->total_amount;
          if (!isset($defaultFromRequest['grouping'])) $defaultFromRequest['grouping'] = $original->contribution_recur_id ? 'recurring' : 'non-recurring';
          if (!isset($defaultFromRequest['instrument'])) $defaultFromRequest['instrument'] = $original->payment_instrument_id;
          if (!isset($defaultFromRequest['ppid'])) $defaultFromRequest['ppid'] = $original->payment_processor_id;
          if (!isset($defaultFromRequest['gift']) && !empty($selectedProductId)) $defaultFromRequest['gift'] = $selectedProductId;
          if (!isset($defaultFromRequest['membership'])) {
            $memberId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment', $id, 'membership_id', 'contribution_id');
            if ($memberId) {
              $memberTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $memberId, 'membership_type_id');
              if ($memberTypeId) {
                $defaultFromRequest['membership'] = $memberTypeId;
              }
            }
          }
          $this->_defaultFromRequest = $defaultFromRequest;
          $this->set('defaultFromRequest', $this->_defaultFromRequest);

          // set custom default values
          $originalCustom = CRM_Core_BAO_CustomValueTable::getEntityValues($id, 'Contribution');
          foreach($originalCustom as $customId => $customValue) {
            if (isset($customValue)) {
              $this->_defaults['custom_'.$customId] = $customValue;
            }
          }
        }
      }
    }
  }
}

