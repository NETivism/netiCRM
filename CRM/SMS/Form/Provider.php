<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * SMS Form.
 */
class CRM_SMS_Form_Provider extends CRM_Core_Form {
  protected $_id = NULL;

  public function preProcess() {

    $this->_id = $this->get('id');

    // $this->setPageTitle(ts('SMS Provider'));

    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/sms/provider',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/sms/provider',
        "reset=1&action=add",
        FALSE, NULL, FALSE
      );
    }

    $this->assign('refreshURL', $refreshURL);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_SMS_DAO_Provider');

    $providerNames = CRM_Core_OptionGroup::values('sms_provider_name', FALSE, FALSE, FALSE, NULL, 'label');
    $apiTypes = CRM_Core_OptionGroup::values('sms_api_type', FALSE, FALSE, FALSE, NULL, 'label');

    $eleProviders = $this->addSelect('name', ts('Name'), array('' => ts('-- Select --')) + $providerNames, TRUE);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $eleProviders->freeze();
    }

    $this->add('text', 'title', ts('Title'),
      $attributes['title'], TRUE
    );

    $this->addRule('title', ts('This Title already exists in Database.'), 'objectExists', array(
      'CRM_SMS_DAO_Provider',
      $this->_id,
    ));

    $this->add('text', 'username', ts('Username'), $attributes['username']);

    $this->add('password', 'password', ts('Password'), $attributes['password']);

    $this->add('select', 'api_type', ts('API Type'), $apiTypes, TRUE);

    $this->add('text', 'api_url', ts('API URL'), $attributes['api_url'], TRUE);

    $this->add('textarea', 'api_params', ts('API Parameters'), "cols=50 rows=6");

    $this->add('checkbox', 'is_active', ts('Is this provider active?'));

    $this->add('checkbox', 'is_default', ts('Is this a default provider?'));

    $this->addFormRule(array('CRM_SMS_Form_Provider', 'formRule'));
  }

  /**
   * Form rule
   *
   * @param array $fields
   * @return array
   */
  public static function formRule($fields) {
    $errors = array();
    if ($fields['name'] == 'CRM_SMS_Provider_Mitake') {
      // check requirement of these fields
      $checkFields = array(
        'username' => ts('Username'),
        'password' => ts('Password'),
      );
      foreach(array_keys($checkFields) as $field) {
        if (empty($fields[$field])) {
          $errors[$field] = ts('%1 is a required field.', array(1 => $checkFields[$field]));
        }
      }
    }
    if ($fields['name'] === 'CRM_SMS_Provider_Flydove' && !empty($fields['flydove_api_token'])) {
      foreach($fields['flydove_api_token'] as $token) {
        if (!preg_match('/^[0-9a-zA-Z]*$/', $token)) {
          $errors['qfKey'] = ts('Invalid value for field(s)');
        }
      }
    }
    return $errors;
  }

  /**
   * Set the default values of various form elements.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();

    $name = CRM_Utils_Request::retrieve('key', 'String', $this, FALSE, NULL);
    if ($name) {
      $defaults['name'] = $name;
      $provider = CRM_SMS_Provider::singleton(array('provider' => $name));
      $defaults['api_url'] = $provider->_apiURL;
    }

    if (!$this->_id) {
      $defaults['is_active'] = 1;
      $providers = CRM_SMS_BAO_Provider::getProviders();
      if (empty($providers)) {
        $defaults['is_default'] = 1;
      }
      return $defaults;
    }

    $dao = new CRM_SMS_DAO_Provider();
    $dao->id = $this->_id;

    if ($name) {
      $dao->name = $name;
    }

    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);

    if ($defaults['name'] == 'CRM_SMS_Provider_Flydove' && !empty($defaults['api_params'])) {
      $apiParams = json_decode($defaults['api_params'], TRUE);
      if (!empty($apiParams) && is_array($apiParams['tokens'])) {
        $this->assign('flydove_api_token', $apiParams['tokens']);
      }
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {

    CRM_Utils_System::flushCache('CRM_SMS_DAO_Provider');

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_SMS_BAO_Provider::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Provider has been deleted.'), ts('Deleted'), 'success');
      return;
    }

    $recData = $values = $this->controller->exportValues($this->_name);
    if ($recData['name'] === 'CRM_SMS_Provider_Flydove' && !empty($this->_submitValues['flydove_api_token'])) {
      $apiParams = array(
        'tokens' => $this->_submitValues['flydove_api_token'],
      );
      $recData['api_params'] = json_encode($apiParams);
    }
    $recData['is_active'] = CRM_Utils_Array::value('is_active', $recData, 0);
    $recData['is_default'] = CRM_Utils_Array::value('is_default', $recData, 0);
    $domainID = CRM_Core_Config::domainID();
    if ($recData['is_default']) {
      $query = "UPDATE civicrm_sms_provider SET is_default = 0 WHERE domain_id = $domainID";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    if ($this->_action && (CRM_Core_Action::UPDATE || CRM_Core_Action::ADD)) {
      if ($this->_id) {
        $recData['id'] = $this->_id;
      }
      $dao = new CRM_SMS_DAO_Provider();
      $dao->name = $recData['name'];
      $dao->title = $recData['title'];
      $dao->username = $recData['username'];
      $dao->password = $recData['password'];
      $dao->api_type = $recData['api_type'];
      $dao->api_url = $recData['api_url'];
      $dao->api_params = $recData['api_params'];
      $dao->is_active = CRM_Utils_Array::value('is_active', $recData, 0);
      $dao->is_default = CRM_Utils_Array::value('is_default', $recData, 0);
      $dao->domain_id = $domainID;
      $dao->id = $this->_id;
      $dao->save();
      // civicrm_api3('SmsProvider', 'create', $recData);
    }
  }

}
