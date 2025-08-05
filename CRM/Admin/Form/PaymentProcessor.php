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
 * $Id: PaymentProcessor.php 9702 2007-05-29 23:57:16Z lobo $
 *
 */



/**
 * This class generates form components for Location Type
 *
 */
class CRM_Admin_Form_PaymentProcessor extends CRM_Admin_Form {
  protected $_ppType = NULL;

  protected $_id = NULL;

  protected $_testID = NULL;

  protected $_fields = NULL;

  protected $_isFreezed = NULL;

  protected $_isTestFreezed = NULL;

  protected $_isTypeFreezed = NULL;
  
  protected $_ppDAO;
  function preProcess() {
    parent::preProcess();

    CRM_Utils_System::setTitle(ts('Settings - Payment Processor'));

    // get the payment processor meta information
    
    if ($this->_id) {
      $this->_ppType = CRM_Utils_Request::retrieve('pp', 'String', $this, FALSE, NULL);
      if (!$this->_ppType) {
        $this->_ppType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor',
          $this->_id,
          'payment_processor_type'
        );
      }
      $this->set('pp', $this->_ppType);
    }
    else {
      $this->_ppType = CRM_Utils_Request::retrieve('pp', 'String', $this, FALSE, NULL);
    }

    $this->assign('ppType', $this->_ppType);

    $this->_ppDAO = new CRM_Core_DAO_PaymentProcessorType();
    $this->_ppDAO->name = $this->_ppType;

    if(empty($this->_ppDAO->name)){
      $this->_ppDAO->is_active = 1;
    }
    if (!$this->_ppDAO->find(TRUE)) {
      return CRM_Core_Error::statusBounce(ts('Could not find payment processor meta information'));
    }

    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor',
        "reset=1&action=add",
        FALSE, NULL, FALSE
      );
    }

    //CRM-4129
    $destination = CRM_Utils_Request::retrieve('destination', 'String', $this);
    if ($destination) {
      $destination = urlencode($destination);
      $refreshURL .= "&destination=$destination";
    }

    $this->assign('refreshURL', $refreshURL);

    $this->assign('is_recur', $this->_ppDAO->is_recur);


    $class = $this->_ppDAO->class_name;
    $class = 'CRM_Core_'.$class;
    if (method_exists($class, 'getAdminFields')) {
      $this->_fields = $class::getAdminFields($this->_ppDAO, $this);
    }
    else{
      $this->_fields = [
        ['name' => 'user_name',
          'label' => $this->_ppDAO->user_name_label,
        ],
        ['name' => 'password',
          'label' => $this->_ppDAO->password_label,
        ],
        ['name' => 'signature',
          'label' => $this->_ppDAO->signature_label,
        ],
        ['name' => 'subject',
          'label' => $this->_ppDAO->subject_label,
        ],
        ['name' => 'url_site',
          'label' => ts('Site URL'),
          'rule' => 'url',
          'msg' => ts('Enter a valid URL'),
        ],
      ];

      if ($this->_ppDAO->is_recur) {
        $this->_fields[] = ['name' => 'url_recur',
          'label' => ts('Recurring Payments URL'),
          'rule' => 'url',
          'msg' => ts('Enter a valid URL'),
        ];
      }

      if (!empty($this->_ppDAO->url_button_default)) {
        $this->_fields[] = ['name' => 'url_button',
          'label' => ts('Button URL'),
          'rule' => 'url',
          'msg' => ts('Enter a valid URL'),
        ];
      }

      if (!empty($this->_ppDAO->url_api_default)) {
        $this->_fields[] = ['name' => 'url_api',
          'label' => ts('API URL'),
          'rule' => 'url',
          'msg' => ts('Enter a valid URL'),
        ];
      }
    }

    if ($this->_id) {
      $haveActiveRecur = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_recur WHERE processor_id = %1 AND is_test = 0 AND contribution_status_id = 5", [ 1 => [ $this->_id, 'Positive']]);
      if ($haveActiveRecur) {
        $this->_isFreezed = TRUE;
      }
      $haveActiveRecur = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_recur WHERE processor_id = %1 AND is_test = 1 AND contribution_status_id = 5", [ 1 => [ $this->_id+1 , 'Positive']]);
      if ($haveActiveRecur) {
        $this->_isTestFreezed = TRUE;
      }
      $processorUsedInContribution = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_contribution WHERE payment_processor_id = %1 AND is_test = 0", [ 1 => [ $this->_id, 'Positive']]);
      $processorUsedInRecur = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_contribution_recur WHERE processor_id = %1 AND is_test = 0", [ 1 => [ $this->_id, 'Positive']]);
      if ($processorUsedInContribution || $processorUsedInRecur) {
        $this->_isTypeFreezed = TRUE;
      }
    }
    if ($this->_isFreezed || $this->_isTestFreezed) {
      CRM_Core_Session::setStatus(ts('Some recurring contributions that belong to this Payment Processor are in progress, so the fields are freeze.'), TRUE, 'warning');
    }

    $hostIP = CRM_Utils_System::getHostIPAddress();
    if (!$hostIP) {
      $hostIP = ts('None');
    }
    $this->assign('hostIP', $hostIP);

    $enableSPGatewayAgreement = defined('CIVICRM_SPGATEWAY_ENABLE_AGREEMENT') && CIVICRM_SPGATEWAY_ENABLE_AGREEMENT == 1;
    $this->assign('enableSPGatewayAgreement', $enableSPGatewayAgreement);
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm($check = FALSE) {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_PaymentProcessor');

    $this->add('text', 'name', ts('Name'),
      $attributes['name'], TRUE
    );

    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', ['CRM_Core_DAO_PaymentProcessor', $this->_id]);

    $this->add('text', 'description', ts('Description'),
      $attributes['description']
    );

    $types = CRM_Core_PseudoConstant::paymentProcessorType();
    // Refs #28304, Remove neweb selection on payment processor edit page.
    $id = $this->get('id');
    $currentType = NULL;
    if ($id) {
      $currentType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessor', $id, 'payment_processor_type');
    }
    if (empty($id) || $currentType !== 'Neweb') {
      unset($types['Neweb']);
    }
    $processorTypeEle = $this->add('select', 'payment_processor_type', ts('Payment Processor Type'), $types, TRUE,
      ['onchange' => "reload(true)"]
    );
    if ($this->_isTypeFreezed) {
      $processorTypeEle->freeze();
    }

    // is this processor active ?
    $this->add('checkbox', 'is_active', ts('Is this Payment Processor active?'));
    $this->add('checkbox', 'is_default', ts('Is this Payment Processor the default?'));
    if (!empty($this->_id)) {
      $isActivePaymentProcessor = CRM_Core_DAO::singleValueQuery("SELECT is_active FROM civicrm_payment_processor WHERE id = %1", [ 1 => [ $this->_id, 'Positive']]);
      if ($this->_isFreezed && $isActivePaymentProcessor) {
        $this->freeze('is_active');
      }
    }

    foreach ($this->_fields as $field) {
      if (empty($field['label'])) {
        continue;
      }
      if ($this->_isFreezed) {
        if ($field['name'] == 'user_name') {
          if($currentType !== 'TapPay') {
            $fieldAttributes = $attributes[$field['name']] + ['readonly' => 'readonly'];
          }
        }
        else {
          $fieldAttributes = $attributes[$field['name']] + ['readonly' => 'readonly'];
        }
      }
      else {
        $fieldAttributes = $attributes[$field['name']];
      }
      if ($this->_isTestFreezed) {
        $testFieldAttributes = $attributes[$field['name']] + ['readonly' => 'readonly'];
      }
      else {
        $testFieldAttributes = $attributes[$field['name']];
      }
      if (!empty($field['type'])) {

        switch($field['type']) {
          case 'select':
            $this->addSelect($field['name'], $field['label'], $field['options']);
            $this->addSelect('test_'.$field['name'], $field['label'], $field['options']);
            break;
          case 'text':
            $this->add('text', $field['name'], $field['label'], $fieldAttributes);
            $this->add('text', "test_{$field['name']}", $field['label'], $testFieldAttributes);
            break;
        }
      }
      else {
        $this->add('text', $field['name'], $field['label'], $fieldAttributes);
        $this->add('text', "test_{$field['name']}", $field['label'], $testFieldAttributes);
      }
      if (CRM_Utils_Array::value('rule', $field)) {
        $this->addRule($field['name'], $field['msg'], $field['rule']);
        $this->addRule("test_{$field['name']}", $field['msg'], $field['rule']);
      }
    }

    $this->addFormRule(['CRM_Admin_Form_PaymentProcessor', 'formRule']);
  }

  static function formRule($fields) {

    // make sure that at least one of live or test is present
    // and we have at least name and url_site
    // would be good to make this processor specific
    $errors = $liveErrors = $testErrors = [];
    $ppType = $fields['payment_processor_type'];
    $class = 'CRM_Core_Payment_'.$ppType;
    if (method_exists($class, 'checkSection')) {
      $isLiveEmpty = $class::checkSection($fields, $liveErrors);
      $isTestEmpty = $class::checkSection($fields, $testErrors, 'test');
    }
    else {
      $isLiveEmpty = self::checkSection($fields, $liveErrors);
      $isTestEmpty = self::checkSection($fields, $testErrors, 'test');
    }
    if (!empty($liveErrors) || !empty($testErrors)) {
      if ($isLiveEmpty && $isTestEmpty) {
        $errors['_qf_default'] = ts('You must have at least the test or live section filled');
      }
      if (!empty($liveErrors) && !$isLiveEmpty) {
        $errors = array_merge($errors, $liveErrors);
      }
      if (!empty($testErrors) && !$isTestEmpty) {
        $errors = array_merge($errors, $testErrors);
      }
    }

    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }

  static function checkSection(&$fields, &$errors, $section = NULL) {
    if (!empty($fields['payment_processor_type'])) {
      $processorType = CRM_Core_DAO::executeQuery("SELECT user_name_label, password_label, signature_label, subject_label FROM civicrm_payment_processor_type WHERE name LIKE %1", [1 => [$fields['payment_processor_type'], 'String']]);
      $processorType->fetch();
    }
    if (!empty($processorType) && $fields['payment_processor_type'] !== 'Mobile') {
      $present = FALSE;
      $allPresent = TRUE;
      $isAllEmpty = FALSE;
      $requiredFieldsCount = 0;
      foreach(['user_name', 'password', 'signature', 'subject'] as $name) {
        $label = $name.'_label';
        if ($section) {
          $name = "{$section}_$name";
        }
        if (!empty($processorType->$label)) {
          $requiredFieldsCount++;
          if (!empty($fields[$name]) || $fields[$name] == '0') {
            $present = TRUE;
          }
          else {
            $errors[$name] = ts('%1 is a required field.', [1 => $processorType->$label]);
          }
        }
      }
      if (count($errors) == $requiredFieldsCount) {
        $isAllEmpty = TRUE;
      }
    }
    else {
      $names = ['user_name'];

      $present = FALSE;
      $allPresent = TRUE;
      foreach ($names as $name) {
        if ($section) {
          $name = "{$section}_$name";
        }
        if (!empty($fields[$name]) || $fields[$name] == '0') {
          $present = TRUE;
        }
        else {
          $allPresent = FALSE;
        }
      }

      if ($present) {
        if (!$allPresent) {
          $errors['_qf_default'] = ts('You must have at least the user_name specified');
          $isAllEmpty = TRUE;
        }
      }
    }
    return $isAllEmpty;
  }

  function setDefaultValues() {
    $defaults = [];

    $defaults['payment_processor_type'] = $this->_ppType;

    if (!$this->_id) {
      $defaults['is_active'] = 1;
      $defaults['is_default'] = 0;
      $defaults['url_site'] = $this->_ppDAO->url_site_default;
      $defaults['url_api'] = $this->_ppDAO->url_api_default;
      $defaults['url_recur'] = $this->_ppDAO->url_recur_default;
      $defaults['url_button'] = $this->_ppDAO->url_button_default;
      $defaults['test_url_site'] = $this->_ppDAO->url_site_test_default;
      $defaults['test_url_api'] = $this->_ppDAO->url_api_test_default;
      $defaults['test_url_recur'] = $this->_ppDAO->url_recur_test_default;
      $defaults['test_url_button'] = $this->_ppDAO->url_button_test_default;
      return $defaults;
    }
    $domainID = CRM_Core_Config::domainID();

    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->id = $this->_id;
    $dao->domain_id = $domainID;
    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);

    // now get testID
    $testDAO = new CRM_Core_DAO_PaymentProcessor();
    $testDAO->name = $dao->name;
    $testDAO->is_test = 1;
    $testDAO->domain_id = $domainID;
    if ($testDAO->find(TRUE)) {
      $this->_testID = $testDAO->id;

      foreach ($this->_fields as $field) {
        $testName = "test_{$field['name']}";
        $defaults[$testName] = $testDAO->{$field['name']};
      }
    }

    if ($this->_ppType) {
      $defaults['payment_processor_type'] = $this->_ppType;
    }

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('*CRM_Core_DAO_PaymentProcessor*');

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_PaymentProcessor::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Payment Processor has been deleted.'));
      return;
    }

    $values = $this->controller->exportValues($this->_name);
    $domainID = CRM_Core_Config::domainID();

    if (CRM_Utils_Array::value('is_default', $values)) {
      $query = "UPDATE civicrm_payment_processor SET is_default = 0 WHERE domain_id = $domainID";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    $this->updatePaymentProcessor($values, $domainID, FALSE);
    $this->updatePaymentProcessor($values, $domainID, TRUE);
  }
  //end of function
  function updatePaymentProcessor(&$values, $domainID, $test) {
    $dao = new CRM_Core_DAO_PaymentProcessor();

    $dao->id = $test ? $this->_testID : $this->_id;
    $dao->domain_id = $domainID;
    $dao->is_test = $test;
    if (!$test) {
      $dao->is_default = CRM_Utils_Array::value('is_default', $values, 0);
    }
    else {
      $dao->is_default = 0;
    }
    $dao->is_active = CRM_Utils_Array::value('is_active', $values, 0);

    $dao->name = $values['name'];
    $dao->description = $values['description'];
    $dao->payment_processor_type = $values['payment_processor_type'];

    foreach ($this->_fields as $field) {
      $fieldName = $test ? "test_{$field['name']}" : $field['name'];
      $dao->{$field['name']} = trim(CRM_Utils_Array::value($fieldName, $values));
      if (empty($dao->{$field['name']})) {
        $dao->{$field['name']} = 'null';
      }
    }

    // also copy meta fields from the info DAO
    $dao->is_recur = $this->_ppDAO->is_recur;
    $dao->billing_mode = $this->_ppDAO->billing_mode;
    $dao->class_name = $this->_ppDAO->class_name;
    $dao->payment_type = $this->_ppDAO->payment_type;

    $dao->save();
  }
}
