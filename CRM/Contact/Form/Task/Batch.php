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
 * This class provides the functionality for batch profile update
 */
class CRM_Contact_Form_Task_Batch extends CRM_Contact_Form_Task {

  public $_fields;
  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * maximum contacts that should be allowed to update
   *
   */
  protected $_maxContacts = 100;

  /**
   * maximum profile fields that will be displayed
   *
   */
  protected $_maxFields = 9;

  /**
   * variable to store redirect path
   *
   */
  protected $_userContext;

  /**
   * when not to reset sort_name
   */
  protected $_preserveDefault = TRUE;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    /*
         * initialize the task and row fields
         */

    parent::preProcess();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      CRM_Core_Error::fatal('ufGroupId is missing');
    }

    $this->_title = ts('Batch Update') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = [];
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = ['File', 'Autocomplete-Select'];
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons([
        ['type' => 'submit',
          'name' => ts('Update Contact(s)'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );


    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_contactIds);

    // if below fields are missing we should not reset sort name / display name
    // CRM-6794
    $preserveDefaultsArray = ['first_name', 'last_name', 'middle_name',
      'organization_name',
      'household_name',
    ];

    foreach ($this->_contactIds as $contactId) {
      foreach ($this->_fields as $name => $field) {
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contactId);

        if (in_array($field['name'], $preserveDefaultsArray)) {
          $this->_preserveDefault = FALSE;
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_BatchUpdateProfile_next') {
      CRM_Core_Session::setStatus("FILE or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update and have been excluded.");
    }

    $this->addDefaultButtons(ts('Update Contacts'));
    $this->addFormRule(['CRM_Contact_Form_Task_Batch', 'formRule']);
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    foreach ($this->_contactIds as $contactId) {
      $details[$contactId] = [];

      //build sortname
      $sortName[$contactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactId,
        'sort_name'
      );

      CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $this->_fields, $defaults, FALSE);
    }

    $this->assign('sortName', $sortName);

    return $defaults;
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields) {
    $errors = [];
    $externalIdentifiers = [];
    foreach ($fields['field'] as $componentId => $field) {
      foreach ($field as $fieldName => $fieldValue) {
        if ($fieldName == 'external_identifier') {
          if (in_array($fieldValue, $externalIdentifiers)) {
            $errors["field[$componentId][external_identifier]"] = ts('Duplicate value for External Identifier.');
          }
          else {
            $externalIdentifiers[$componentId] = $fieldValue;
          }
        }
      }
    }

    return $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->exportValues();

    $ufGroupId = $this->get('ufGroupId');
    $notify = NULL;
    $inValidSubtypeCnt = 0;
    //send profile notification email if 'notify' field is set
    $notify = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $ufGroupId, 'notify');
    foreach ($params['field'] as $key => $value) {

      //CRM-5521
      //validate subtype before updating
      if (CRM_Utils_Array::value('contact_sub_type', $value) && !CRM_Contact_BAO_ContactType::isAllowEdit($key)) {
        unset($value['contact_sub_type']);
        $inValidSubtypeCnt++;
      }

      $value['preserveDBName'] = $this->_preserveDefault;
      CRM_Contact_BAO_Contact::createProfileContact($value, $this->_fields, $key, NULL, $ufGroupId);
      if ($notify) {
        $values = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($ufGroupId, $key, NULL);
        $fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);
        foreach ($fields as $k => $v) {
          if ((CRM_Utils_Array::value('data_type', $v, '') == 'File' || CRM_Utils_Array::value('name', $v, '') == 'image_URL') && !empty($values['values'][$v['title']] )){
            $values['values'][$v['title']] = ts("Uploaded files received");
          }
        }
        CRM_Core_BAO_UFGroup::commonSendMail($key, $values);
      }
    }

    $statusMsg = ts("Your updates have been saved.");

    if ($inValidSubtypeCnt) {
      $statusMsg .= ' ' . ts('Contact SubType field of %1 nunber of contact(s) has not been updated.', [1 => $inValidSubtypeCnt]);
    }
    CRM_Core_Session::setStatus("{$statusMsg}");
  }
  //end of function
}

