<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */



class CRM_Mailing_Form_Subscribe extends CRM_Core_Form {
  protected $_groupID = NULL;
  function preProcess() {
    parent::preProcess();
    $this->_groupID = CRM_Utils_Request::retrieve('gid', 'Integer', $this,
      FALSE, NULL, 'REQUEST'
    );

    // ensure that there is a destination, if not set the destination to the
    // referrer string
    if (!$this->controller->getDestination()) {
      $this->controller->setDestination(NULL, TRUE);
    }



    if ($this->_groupID) {
      $groupTypeCondition = CRM_Contact_BAO_Group::groupTypeCondition('Mailing');

      // make sure requested qroup is accessible and exists
      $query = "
SELECT   title, description
  FROM   civicrm_group
 WHERE   id={$this->_groupID}  
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition";

      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      if ($dao->fetch()) {
        $this->assign('groupName', $dao->title);
        CRM_Utils_System::setTitle(ts('Subscribe to Mailing List - %1', array(1 => $dao->title)));
      }
      else {
         return CRM_Core_Error::statusBounce("The specified group is not configured for this action OR The group doesn't exist.");
      }

      $this->assign('single', TRUE);
    }
    else {
      $this->assign('single', FALSE);
      CRM_Utils_System::setTitle(ts('Mailing List Subscription'));
    }
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */

  public function buildQuickForm() {
    // add the email address
    $this->add('text', 'email', ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email' ), TRUE);
    $this->addRule('email', ts("Please enter a valid email address (e.g. 'yourname@example.com')."), 'email');
    $this->add('text', 'last_name', ts('Last Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'last_name'), TRUE);
    $this->add('text', 'first_name', ts('First Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'first_name'), TRUE);

    if (!$this->_groupID) {
      // create a selector box of all public groups
      $groupTypeCondition = CRM_Contact_BAO_Group::groupTypeCondition('Mailing');

      $query = "
SELECT   id, title, description
  FROM   civicrm_group
 WHERE   ( saved_search_id = 0
    OR     saved_search_id IS NULL )
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition
   AND   is_active = 1
ORDER BY title";
      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      $rows = array();
      while ($dao->fetch()) {
        $row = array();
        $row['id'] = $dao->id;
        $row['title'] = $dao->title;
        $row['description'] = $dao->description;
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $row['id'];
        $this->addElement('checkbox', $row['checkbox'], NULL, NULL);
        $rows[] = $row;
      }
      if (empty($rows)) {
         return CRM_Core_Error::statusBounce(ts('There are no public mailing list groups to display.'));
      }
      if (count($rows) == 1) {
        $row = reset($rows);
        $default[CRM_Core_Form::CB_PREFIX.$row['id']] = 1; 
        $this->setDefaults($default);
      }
      $this->assign('rows', $rows);
      $this->addFormRule(array('CRM_Mailing_Form_Subscribe', 'formRule'));
    }

    $addCaptcha = TRUE;

    // if recaptcha is not set, then dont add it
    $config = CRM_Core_Config::singleton();
    if (empty($config->recaptchaPublicKey) ||
      empty($config->recaptchaPrivateKey)
    ) {
      $addCaptcha = FALSE;
    }

    if ($addCaptcha) {
      // add captcha

      $captcha = CRM_Utils_ReCAPTCHA::singleton();
      $captcha->add($this);
    }

    $this->assign('browserPrint', TRUE);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Subscribe'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  static function formRule($fields) {
    foreach ($fields as $name => $dontCare) {
      if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
        return TRUE;
      }
    }
    return array('_qf_default' => ts('%1 is a required field.', array(1 => ts('Subscribe'))));
  }

  /**
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $groups = array();
    if ($this->_groupID) {
      $groups[] = $this->_groupID;
    }
    else {
      foreach ($params as $name => $dontCare) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($name);
        if (!empty($contactID)) {
          $groups[] = $contactID;
        }
      }
    }
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Strict');
    if (!empty($ids)) {
      sort($ids);
      $contactId = reset($ids);
    }
    else {
      // create contact by params

      $formatted = array(
        'contact_type' => 'Individual',
        'version' => 3,
        'last_name' => $params['last_name'],
        'first_name' => $params['first_name'],
      );
      $locationType = CRM_Core_BAO_LocationType::getDefault();
      $value = array(
        'email' => $params['email'],
        'location_type_id' => $locationType->id,
        'is_bulkmail' => 1,
      );
      _civicrm_api3_deprecated_add_formatted_param($value, $formatted);

      $formatted['onDuplicate'] = CRM_Import_Parser::DUPLICATE_SKIP;
      $formatted['fixAddress'] = TRUE;
      $formatted['log_data'] = ts("Mailing List Subscription");
      $contact = civicrm_api('contact', 'create', $formatted);
      if (!civicrm_error($contact)) {
        $contactId = $contact['id'];
      }
      else{
        $contactId = NULL;
      }
    }

    $config = CRM_Core_Config::singleton();
    if ($config->profileDoubleOptIn) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($groups, $params, $contactId);
    } else {
      foreach ($groups as $groupID) {
        $se = CRM_Mailing_Event_BAO_Subscribe::subscribe($groupID, $params['email'], $contactId);
        $confirm = CRM_Mailing_Event_BAO_Confirm::confirm($contactId, $se->id, $se->hash);
      }
    }
    CRM_Core_Session::setStatus(ts('Thank you. Your information has been saved.'));
  }
}

