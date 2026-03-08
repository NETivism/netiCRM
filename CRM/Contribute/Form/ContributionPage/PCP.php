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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * form to process actions on the Personal Campaign Page (PCP) section of Contribution Page
 */
class CRM_Contribute_Form_ContributionPage_PCP extends CRM_Contribute_Form_ContributionPage {

  /**
   * Set up variables before the form is built.
   *
   * Calls the parent preProcess method.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Set default values for the form.
   *
   * Retrieves existing PCP block settings for the current contribution page
   * from the database. Sets initial values for approval, link text, and notification email.
   *
   * @return array the array of default values for form elements
   */
  public function setDefaultValues() {
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('Personal Campaign Page Settings (%1)', [1 => $title]));
    $defaults = [];
    if (isset($this->_id)) {
      $params = ['entity_id' => $this->_id, 'entity_table' => 'civicrm_contribution_page'];
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCPBlock', $params, $defaults);
      // Assign contribution page ID to pageId for referencing in PCP.hlp - since $id is overwritten there. dgg
      $this->assign('pageId', $this->_id);
    }

    if (!CRM_Utils_Array::value('id', $defaults)) {
      $defaults['is_approval_needed'] = 1;
      $defaults['link_text'] = ts('Create your own fundraising page');

      if ($ccReceipt = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'cc_receipt')) {
        $defaults['notify_email'] = $ccReceipt;
      }
    }
    return $defaults;
  }

  /**
   * Actually build the form components.
   *
   * Adds fields for enabling PCP, approval requirements, supporter profiles,
   * link text, and notification emails.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addElement('checkbox', 'is_active', ts('Enable Personal Campaign Pages (for this contribution page)?'), NULL, ['onclick' => "return showHideByValue('is_active',true,'pcpFields','table-row','radio',false);"]);

    $this->addElement('checkbox', 'is_approval_needed', ts('Approval required'));

    $profile = [];
    $isUserRequired = NULL;
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework != 'Standalone') {
      $isUserRequired = 2;
    }
    CRM_Core_DAO::commonRetrieveAll('CRM_Core_DAO_UFGroup', 'is_cms_user', $isUserRequired, $profiles, ['title', 'is_active']);
    if (!empty($profiles)) {
      foreach ($profiles as $key => $value) {
        if ($value['is_active']) {
          $profile[$key] = $value['title'];
        }
      }
      $this->assign('profile', $profile);
    }

    $this->add('select', 'supporter_profile_id', ts('Campaign owner profile'), ['' => ts('- select -')] + $profile);
    if (count($profile)) {
      $defaultProfile = key($profile);
      $this->setDefaults(['supporter_profile_id' => $defaultProfile]);
    }

    $this->add(
      'text',
      'link_text',
      ts("'Create Personal Campaign Page' link text"),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PCPBlock', 'pcp_link_text')
    );

    $notifyAttr = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PCPBlock', 'notify_email');
    $notifyAttr['placeholder'] = 'jane@example.org,paula@example.org';
    $this->add('text', 'notify_email', ts('Notify Email'), $notifyAttr);

    parent::buildQuickForm();
    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_PCP', 'formRule'], $this);
  }

  /**
   * Global form rule for validation.
   *
   * Validates that a supporter profile is selected if PCP is enabled,
   * ensuring the profile contains an email field, and validates notification emails.
   *
   * @param array $params an assoc array of name/value pairs submitted by the form
   * @param array $files the uploaded files array
   * @param CRM_Core_Form $self the form object
   *
   * @return mixed true if no errors, or an array of error messages
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    if (CRM_Utils_Array::value('is_active', $params)) {

      if (!CRM_Utils_Array::value('supporter_profile_id', $params)) {
        $errors['supporter_profile_id'] = ts('Supporter profile is a required field.');
      }
      else {

        if (CRM_Contribute_BAO_PCP::checkEmailProfile($params['supporter_profile_id'])) {
          $errors['supporter_profile_id'] = ts('Profile is not configured with Email address.');
        }
      }

      if ($emails = CRM_Utils_Array::value('notify_email', $params)) {
        $emailArray = explode(',', $emails);
        foreach ($emailArray as $email) {
          if ($email && !CRM_Utils_Rule::email(trim($email))) {
            $errors['notify_email'] = ts('A valid Notify Email address must be specified');
          }
        }
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   * Creates or updates the `civicrm_pcp_block` record associated with the
   * current contribution page.
   *
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $params['entity_id'] = $this->_id;
    $params['entity_table'] = 'civicrm_contribution_page';

    $dao = new CRM_Contribute_DAO_PCPBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $this->_id;
    $dao->find(TRUE);
    $params['id'] = $dao->id;
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_approval_needed'] = CRM_Utils_Array::value('is_approval_needed', $params, FALSE);

    $dao = CRM_Contribute_BAO_PCP::add($params);
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string the descriptive page title
   */
  public function getTitle() {
    return ts('Enable Personal Campaign Pages');
  }
}
