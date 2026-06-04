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
 * This class generates form components for Option Value
 *
 */
class CRM_Admin_Form_OptionValue extends CRM_Admin_Form {
  public static $_gid = NULL;

  /**
   * The option group name
   *
   * @var string
   * @static
   */
  public static $_gName = NULL;

  /**
   * Pre-processes the form.
   *
   * @return void Pre-processes the form.
   */
  public function preProcess() {
    parent::preProcess();
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0);
    //get optionGroup name in case of email/postal greeting or addressee, CRM-4575
    if (!empty($this->_gid)) {
      $this->_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gid, 'name');
    }
    else {
      $this->_gName = CRM_Utils_Request::retrieve('group', 'String', $this, FALSE, 0);
      if (!empty($this->_gName)) {
        $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gName, 'id', 'name');
      }
    }
    if (empty($this->_gid)) {
      CRM_Core_Error::fatal(ts('Missing required fields').': '.ts('Option Group Name'));
    }
    // get id from value
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (empty($this->_id) || !is_numeric($this->_id)) {
        $value = CRM_Utils_Request::retrieve('value', 'String', $this, TRUE);
        $optionId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_value WHERE option_group_id = %1 AND value = %2", [
          1 => [$this->_gid, 'Integer'],
          2 => [$value, 'String'],
        ]);
        if (!empty($optionId)) {
          $this->_id = $optionId;
        }
        else {
          CRM_Core_Error::fatal(ts('Missing required fields').': '.ts('Option Value'));
        }
      }
      $label = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'label');
      if (ts($label) !== $label) {
        $label = ts($label)."($label)";
      }
      CRM_Utils_System::setTitle(ts('Edit %1 Option', [1 => $label]));
    }
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/optionValue', 'reset=1&action=browse&gid=' . $this->_gid);
    $session->pushUserContext($url);
    $this->assign('id', $this->_id);
    $this->assign('gid', $this->_gid);

    if ($this->_id && in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domainID) {
        return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
      }
    }
  }

  /**
   * Sets the default values for the form.
   *
   * @return array The default values.
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults = parent::setDefaultValues();
    if (!CRM_Utils_Array::value('weight', $defaults)) {
      $query = "SELECT max( `weight` ) as weight FROM `civicrm_option_value` where option_group_id=" . $this->_gid;
      $dao = new CRM_Core_DAO();
      $dao->query($query);
      $dao->fetch();
      $defaults['weight'] = ($dao->weight + 1);
    }
    //setDefault of contact types for email greeting, postal greeting, addressee, CRM-4575
    if (in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee'])) {
      $defaults['contactOptions'] = CRM_Utils_Array::value('filter', $defaults);
    }
    return $defaults;
  }

  /**
   * Builds the form.
   *
   * @return void Builds the form.
   */
  public function buildQuickForm() {
    //CRM-4575
    $isReserved = FALSE;
    if ($this->_id) {
      $isReserved = (bool) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'is_reserved');
    }
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);
    $this->add('text', 'value', ts('Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'), TRUE);
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'name'));
    if ($this->_gName == 'custom_search') {
      $this->add(
        'text',
        'description',
        ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
      );
    }
    else {
      $this->addWysiwyg(
        'description',
        ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
      );
    }

    if ($this->_gName == 'case_status') {
      $grouping = $this->add('select', 'grouping', ts('Option Grouping Name'), ['Opened' => ts('Opened'),
          'Closed' => ts('Closed'),
        ]);
      if ($isReserved) {
        $grouping->freeze();
      }
    }
    else {
      $this->add('text', 'grouping', ts('Option Grouping Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'grouping'));
    }

    $this->add('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_default', ts('Default Option?'));
    $this->add('checkbox', 'is_optgroup', ts('Option Group?'));
    $ele = $this->add('checkbox', 'filter', ts('Filter'));

    if (CRM_Core_Permission::check('administer neticrm')) {
      $this->assign('show_details', TRUE);
    }
    else {
      $this->getElement('label')->freeze();
      $this->getElement('value')->freeze();
      $this->getElement('name')->freeze();
      $this->getElement('grouping')->freeze();
      $this->getElement('description')->freeze();
      $this->getElement('is_default')->freeze();
      $this->getElement('is_optgroup')->freeze();
      $this->getElement('weight')->freeze();
    }

    if ($this->_gName = 'contact_edit_options') {
      $ele = $ele->freeze();
    }

    if ($this->_action & CRM_Core_Action::UPDATE && $isReserved) {
      $this->freeze(['name', 'description', 'is_active']);
    }
    //get contact type for which user want to create a new greeting/addressee type, CRM-4575
    if (in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee']) && !$isReserved) {
      $values = [1 => ts('Individual'), 2 => ts('Household')];
      if ($this->_gName == 'addressee') {
        $values[] = ts('Organization');
      }
      $this->add('select', 'contactOptions', ts('Contact Type'), ['' => '-select-'] + $values, TRUE);
    }

    $this->addFormRule(['CRM_Admin_Form_OptionValue', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields The input form values.
   * @param array $files The uploaded files.
   * @param CRM_Core_Form $self The form object.
   *
   * @return bool|array True if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    //don't allow duplicate value within group.
    $optionValues = [];

    CRM_Core_OptionValue::getValues(['id' => $self->_gid], $optionValues);
    foreach ($optionValues as $values) {
      if ($values['id'] != $self->_id) {
        if ($fields['value'] == $values['value']) {
          $errors['value'] = ts('Value already exist in database.');
          break;
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Processes the submitted form values.
   *
   * @return void Processes the submitted form values.
   */
  public function postProcess() {
    $params = $this->exportValues();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_OptionValue::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected option value has been deleted.'));
    }
    else {

      $params = $ids = [];
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['option_group_id'] = $this->_gid;

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['optionValue'] = $this->_id;
      }

      //set defaultGreeting option in params to save default value as per contactOption-defaultValue mapping
      if (CRM_Utils_Array::value('contactOptions', $params)) {
        $params['filter'] = CRM_Utils_Array::value('contactOptions', $params);
        $params['defaultGreeting'] = 1;
      }

      $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The Option Value \'%1\' has been saved.', [1 => $optionValue->label]));
    }
  }
}
