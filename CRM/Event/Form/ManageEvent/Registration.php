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

require_once 'CRM/Event/Form/ManageEvent.php';
require_once 'CRM/Event/BAO/Event.php';

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_ManageEvent_Registration extends CRM_Event_Form_ManageEvent {

  /**
   * what blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
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
  function setDefaultValues() {
    $eventId = $this->_id;

    $defaults = parent::setDefaultValues();

    $this->setShowHide($defaults);
    if (isset($eventId)) {
      $params = array('id' => $eventId);
      CRM_Event_BAO_Event::retrieve($params, $defaults);
      if (!empty($defaults['is_multiple_registrations'])) {
        $defaults['is_multiple_registrations_max'] = $defaults['is_multiple_registrations'];
      }

      require_once 'CRM/Core/BAO/UFJoin.php';
      $ufJoinParams = array('entity_table' => 'civicrm_event',
        'module' => 'CiviEvent',
        'entity_id' => $eventId,
      );

      list($defaults['custom_pre_id'],
        $defaults['custom_post_id']
      ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      if ($defaults['is_multiple_registrations']) {
        // CRM-4377: set additional participants’ profiles – set to ‘none’ if explicitly unset (non-active)
        $ufJoin = new CRM_Core_DAO_UFJoin;
        $ufJoin->module = 'CiviEvent_Additional';
        $ufJoin->entity_table = 'civicrm_event';
        $ufJoin->entity_id = $eventId;
        $ufJoin->orderBy('weight');
        $ufJoin->find();
        $custom = array(1 => 'additional_custom_pre_id',
          2 => 'additional_custom_post_id',
        );
        while ($ufJoin->fetch()) {
          $defaults[$custom[$ufJoin->weight]] = $ufJoin->is_active ? $ufJoin->uf_group_id : 'none';
        }
      }
    }
    else {
      $defaults['is_email_confirm'] = 0;
    }

    // provide defaults for required fields if empty (and as a 'hint' for approval message field)
    $defaults['registration_link_text'] = CRM_Utils_Array::value('registration_link_text', $defaults, ts('Register Now'));
    $defaults['confirm_title'] = CRM_Utils_Array::value('confirm_title', $defaults, ts('Confirm Your Registration Information'));
    $defaults['thankyou_title'] = CRM_Utils_Array::value('thankyou_title', $defaults, ts('Thank You for Registering'));
    $defaults['approval_req_text'] = CRM_Utils_Array::value('approval_req_text', $defaults, ts('Participation in this event requires approval. Submit your registration request here. Once approved, you will receive an email with a link to a web page where you can complete the registration process.'));

    if (!$this->_isTemplate) {
      if (CRM_Utils_Array::value('registration_start_date', $defaults)) {
        list($defaults['registration_start_date'],
          $defaults['registration_start_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['registration_start_date'], 'activityDateTime');
      }

      if (CRM_Utils_Array::value('registration_end_date', $defaults)) {
        list($defaults['registration_end_date'],
          $defaults['registration_end_date_time']
        ) = CRM_Utils_Date::setDateDefaults($defaults['registration_end_date'], 'activityDateTime');
      }
    }
    return $defaults;
  }

  /**
   * Fix what blocks to show/hide based on the default values set
   *
   * @param array   $defaults the array of default values
   * @param boolean $force    should we set show hide based on input defaults
   *
   * @return void
   */
  function setShowHide(&$defaults) {
    require_once 'CRM/Core/ShowHideBlocks.php';
    $this->_showHide = new CRM_Core_ShowHideBlocks(array('registration' => 1), '');
    if (empty($defaults)) {
      $this->_showHide->addShow('registration_screen_show');
      $this->_showHide->addShow('confirm_show');
      $this->_showHide->addShow('mail_show');
      $this->_showHide->addShow('thankyou_show');
      $this->_showHide->addHide('registration');
      $this->_showHide->addHide('registration_screen');
      $this->_showHide->addHide('confirm');
      $this->_showHide->addHide('mail');
      $this->_showHide->addHide('thankyou');
      $this->_showHide->addHide('additional_profile_pre');
      $this->_showHide->addHide('additional_profile_post');
      $this->_showHide->addHide('id-approval-text');
    }
    else {
      $this->_showHide->addShow('confirm');
      $this->_showHide->addShow('mail');
      $this->_showHide->addShow('thankyou');
      $this->_showHide->addHide('registration_screen_show');
      $this->_showHide->addHide('confirm_show');
      $this->_showHide->addHide('mail_show');
      $this->_showHide->addHide('thankyou_show');
      if (!$defaults['is_multiple_registrations']) {
        $this->_showHide->addHide('additional_profile_pre');
        $this->_showHide->addHide('additional_profile_post');
      }
      if (!CRM_Utils_Array::value('requires_approval', $defaults)) {
        $this->_showHide->addHide('id-approval-text');
      }
    }
    $this->_showHide->addToTemplate();
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

    $this->addElement('checkbox',
      'is_online_registration',
      ts('Allow Online Registration?'),
      NULL,
      array('onclick' => "return showHideByValue('is_online_registration', 
                                                                       '', 
                                                                       'registration_blocks', 
                                                                       'block', 
                                                                       'radio', 
                                                                       false );",
      )
    );

    $this->add('text', 'registration_link_text', ts('Registration Link Text'));

    if (!$this->_isTemplate) {
      $this->addDateTime('registration_start_date', ts('Registration Start Date'), FALSE, array('formatType' => 'activityDateTime'));
      $this->addDateTime('registration_end_date', ts('Registration End Date'), FALSE, array('formatType' => 'activityDateTime'));
    }

    $this->addElement('checkbox', 'is_multiple_registrations', ts('Register multiple participants?'), NULL, array('onclick' => "return showHideByValue('is_multiple_registrations', '', 'allow_same_emails|additional_profile_pre|additional_profile_post|is_multiple_registrations_limit', 'table-row', 'radio', false);"));

    for($i = 2; $i <= 10; $i++) {
      $maxLimit[$i]  = $i;
    }
    $this->addSelect('is_multiple_registrations_max', ts('Maximum per registration'), $maxLimit);

    $this->addElement('checkbox', 'allow_same_participant_emails', ts('Allow multiple registrations from the same email address?'));

    require_once 'CRM/Event/PseudoConstant.php';
    $participantStatuses = &CRM_Event_PseudoConstant::participantStatus();
    if (in_array('Awaiting approval', $participantStatuses) &&
        in_array('Pending from approval', $participantStatuses) &&
        in_array('Rejected', $participantStatuses) &&
        !$this->_eventInfo['has_waitlist']) {
      $this->addElement('checkbox',
        'requires_approval',
        ts('Require participant approval?'),
        NULL,
        array('onclick' => "return showHideByValue('requires_approval', '', 'id-approval-text', 'table-row', 'radio', false);")
      );
      $this->add('textarea', 'approval_req_text', ts('Approval message'), $attributes['approval_req_text']);
    }

    $this->add('text', 'expiration_time', ts('Pending participant expiration (hours)'));
    $this->addRule('expiration_time', ts('Please enter the number of hours (as an integer).'), 'integer');

    self::buildRegistrationBlock($this);
    self::buildConfirmationBlock($this);
    self::buildMailBlock($this);
    self::buildThankYouBlock($this);

    parent::buildQuickForm();
  }

  /**
   * Function to build Registration Block
   *
   * @param int $pageId
   * @static
   */
  function buildRegistrationBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->addWysiwyg('intro_text', ts('Introductory Text'), $attributes['intro_text']);
    // FIXME: This hack forces height of editor to 175px. Need to modify QF classes for editors to allow passing
    // explicit height and width.
    $form->addWysiwyg('footer_text', ts('Footer Text'), array('rows' => 2, 'cols' => 40));

    require_once "CRM/Core/BAO/UFGroup.php";
    require_once "CRM/Contact/BAO/ContactType.php";
    $types = array_merge(array('Contact', 'Individual', 'Participant'),
      CRM_Contact_BAO_ContactType::subTypes('Individual')
    );
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    // filter again use uf_join
    $eventProfiles = CRM_Core_BAO_UFGroup::getModuleUFGroup('CiviEvent') + CRM_Core_BAO_UFGroup::getModuleUFGroup('CiviEvent_Additional');
    $profiles = array_intersect_key($profiles, $eventProfiles);

    $mainProfiles = array('' => ts('- select -')) + $profiles;
    $addtProfiles = array('' => ts('- same as for main contact -'), 'none' => ts('- no profile -')) + $profiles;

    $form->add('select', 'custom_pre_id', ts('Include Profile') . '<br />' . ts('(top of page)'), $mainProfiles);
    $form->add('select', 'custom_post_id', ts('Include Profile') . '<br />' . ts('(bottom of page)'), $mainProfiles);

    $form->add('select', 'additional_custom_pre_id', ts('Profile for Additional Participants') . '<br />' . ts('(top of page)'), $addtProfiles);
    $form->add('select', 'additional_custom_post_id', ts('Profile for Additional Participants') . '<br />' . ts('(bottom of page)'), $addtProfiles);
  }

  /**
   * Function to build Confirmation Block
   *
   * @param int $pageId
   * @static
   */
  function buildConfirmationBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->add('text', 'confirm_title', ts('Title'), $attributes['confirm_title']);
    $form->addWysiwyg('confirm_text', ts('Introductory Text'), $attributes['confirm_text']);
    // FIXME: This hack forces height of editor to 175px. Need to modify QF classes for editors to allow passing
    // explicit height and width.
    $form->addWysiwyg('confirm_footer_text', ts('Footer Text'), array('rows' => 2, 'cols' => 40));
  }

  /**
   * Function to build Email Block
   *
   * @param int $pageId
   * @static
   */
  function buildMailBlock(&$form) {
    $form->registerRule('emailList', 'callback', 'emailList', 'CRM_Utils_Rule');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->addYesNo('is_email_confirm', ts('Send Confirmation Email?'), NULL, NULL, array('onclick' => "return showHideByValue('is_email_confirm','','confirmEmail','block','radio',false);"));
    $form->addYesNo('is_qrcode', ts('Check In Code')."?");
    $form->addWysiwyg('confirm_email_text', ts('Text'), $attributes['confirm_email_text']);
    $form->add('text', 'cc_confirm', ts('CC Confirmation To'), CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'cc_confirm'));
    $form->addRule("cc_confirm", ts('Please enter a valid list of comma delimited email addresses'), 'emailList');
    $form->add('text', 'bcc_confirm', ts('BCC Confirmation To'), CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'bcc_confirm'));
    $form->addRule("bcc_confirm", ts('Please enter a valid list of comma delimited email addresses'), 'emailList');
    $form->add('text', 'confirm_from_name', ts('Confirm From Name'));

    $availableFrom = CRM_Core_PseudoConstant::fromEmailAddress(TRUE, TRUE);
    $verifiedFrom = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail();
    $verifiedDomains = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail(
      CRM_Admin_Form_FromEmailAddress::VALID_EMAIL | CRM_Admin_Form_FromEmailAddress::VALID_DKIM | CRM_Admin_Form_FromEmailAddress::VALID_SPF,
      'domain'
    );
    $selectableEmail = array();
    $hasVerified = FALSE;
    foreach($availableFrom as $fromAddr) {
      $email = htmlspecialchars($fromAddr['email']);
      if (array_search($fromAddr['email'], $verifiedFrom) !== FALSE) {
        $email = ts('%1 Verified', array(1 => '🛡️ '.htmlspecialchars($fromAddr['email'])));
        $hasVerified = TRUE;
        $selectableEmail[$fromAddr['email']] = $email;
      }
      elseif (CRM_Utils_Mail::checkMailInDomains($fromAddr['email'], $verifiedDomains)) {
        $selectableEmail[$fromAddr['email']] = $email;
      }
    }
    arsort($selectableEmail);
    if (!$hasVerified) {
      $form->assign('show_spf_dkim_notice', TRUE);
    }
    $form->addSelect('confirm_from_email', ts('Confirm From Email'), $selectableEmail);
    $form->assign('mail_providers', str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS));
    $form->addRule("confirm_from_email", ts('Email is not valid.'), 'email');

    $form->addElement('checkbox',
      'allow_cancel_by_link',
      ts('Attach cancel registration link'),
      NULL);

    $defaultFromMail = CRM_Mailing_BAO_Mailing::defaultFromMail();
    $form->assign('default_from_target', 'confirm_from_email');
    $form->assign('default_from_value', $defaultFromMail);

    // tokens
    $tokens = array();
    $tokens = CRM_Core_SelectValues::contactTokens();
    $form->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    $form->add('select', 'token2', ts('Insert Tokens'),
      $tokens, FALSE,
      array(
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplHtml(this);",
      )
    );
  }

  function buildThankYouBlock(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');
    $form->add('text', 'thankyou_title', ts('Title'), $attributes['thankyou_title']);
    $form->addWysiwyg('thankyou_text', ts('Introductory Text'), $attributes['thankyou_text']);
    // FIXME: This hack forces height of editor to 175px. Need to modify QF classes for editors to allow passing
    // explicit height and width.
    $form->addWysiwyg('thankyou_footer_text', ts('Footer Text'), array('rows' => 2, 'cols' => 40));
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(array('CRM_Event_Form_ManageEvent_Registration', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values) {
    if ($values['is_online_registration']) {
      if (!$values['confirm_title']) {
        $errorMsg['confirm_title'] = ts('Please enter a Title for the registration Confirmation Page');
      }
      if (!$values['thankyou_title']) {
        $errorMsg['thankyou_title'] = ts('Please enter a Title for the registration Thank-you Page');
      }
      if ($values['is_email_confirm']) {
        if (!$values['confirm_from_name']) {
          $errorMsg['confirm_from_name'] = ts('Please enter Confirmation Email FROM Name.');
        }

        $email = trim(CRM_Utils_Array::value('confirm_from_email', $values));
        if (empty($email)) {
          $errorMsg['confirm_from_email'] = ts('Please enter Confirmation Email FROM Email Address.');
        }
        else {
          if(!CRM_Utils_Rule::email($email)) {
            $errorMsg['confirm_from_email'] = ts('Please enter the valid email address.');
          }
          if (!CRM_Utils_Mail::checkMailProviders($email)) {
            $errorMsg['confirm_from_email'] = ts('Do not use free mail address as mail sender. (eg. %1)', array(1 => str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS)));
          }
        }
      }
    }

    if (!empty($errorMsg)) {
      return $errorMsg;
    }

    return TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = array();
    $params = $this->exportValues();

    $params['id'] = $this->_id;

    //format params
    $params['is_online_registration'] = CRM_Utils_Array::value('is_online_registration', $params, FALSE);
    $params['is_multiple_registrations'] = CRM_Utils_Array::value('is_multiple_registrations', $params, FALSE);
    if ($params['is_multiple_registrations']) {
      $params['is_multiple_registrations'] = CRM_Utils_Array::value('is_multiple_registrations_max', $params, FALSE);
    }
    $params['allow_same_participant_emails'] = CRM_Utils_Array::value('allow_same_participant_emails', $params, FALSE);
    $params['requires_approval'] = CRM_Utils_Array::value('requires_approval', $params, FALSE);
    $params['allow_cancel_by_link'] = CRM_Utils_Array::value('allow_cancel_by_link', $params, FALSE);

    // reset is_email confirm if not online reg
    if (!$params['is_online_registration']) {
      $params['is_email_confirm'] = FALSE;
    }

    if (!$this->_isTemplate) {
      $params['registration_start_date'] = CRM_Utils_Date::processDate($params['registration_start_date'],
        $params['registration_start_date_time'],
        TRUE
      );
      $params['registration_end_date'] = CRM_Utils_Date::processDate($params['registration_end_date'],
        $params['registration_end_date_time'],
        TRUE
      );
    }

    require_once 'CRM/Event/BAO/Event.php';
    CRM_Event_BAO_Event::add($params);

    // also update the ProfileModule tables
    $ufJoinParams = array('is_active' => 1,
      'module' => 'CiviEvent',
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->_id,
    );

    require_once 'CRM/Core/BAO/UFJoin.php';

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    if (!empty($params['custom_pre_id'])) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['custom_pre_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    unset($ufJoinParams['id']);

    if (!empty($params['custom_post_id'])) {
      $ufJoinParams['weight'] = 2;
      $ufJoinParams['uf_group_id'] = $params['custom_post_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    // CRM-4377: also update the profiles for additional participants
    $ufJoinParams['module'] = 'CiviEvent_Additional';
    $ufJoinParams['weight'] = 1;
    $ufJoinParams['uf_group_id'] = $params['custom_pre_id'];
    if ($params['additional_custom_pre_id'] == 'none') {
      $ufJoinParams['is_active'] = 0;
    }
    elseif ($params['additional_custom_pre_id']) {
      $ufJoinParams['uf_group_id'] = $params['additional_custom_pre_id'];
    }
    CRM_Core_BAO_UFJoin::create($ufJoinParams);

    $ufJoinParams['weight'] = 2;
    $ufJoinParams['uf_group_id'] = $params['custom_post_id'];
    if ($params['additional_custom_post_id'] == 'none') {
      $ufJoinParams['is_active'] = 0;
    }
    elseif ($params['additional_custom_post_id']) {
      //minor fix for CRM-4377
      $ufJoinParams['is_active'] = 1;
      $ufJoinParams['uf_group_id'] = $params['additional_custom_post_id'];
    }
    CRM_Core_BAO_UFJoin::create($ufJoinParams);

    parent::endPostProcess();
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Online Registration');
  }
}

