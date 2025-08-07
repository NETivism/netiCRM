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
 * This class provides the functionality for batch profile update for Activities
 */
class CRM_Activity_Form_Task_Batch extends CRM_Activity_Form_Task {

  public $_fields;
  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

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

    //get the contact read only fields to display.

    $readOnlyFields = array_merge(['sort_name' => ts('Name')],
      CRM_Core_BAO_Preferences::valueOptions('contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );

    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);

    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_activityHolderIds,
      'Activity', $returnProperties
    );
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
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


    $this->_title = ts('Batch Update for Activities') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
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

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons([
        ['type' => 'submit',
          'name' => ts('Update Activities'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );


    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_activityHolderIds);
    $fileFieldExists = FALSE;



    $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

    foreach ($this->_activityHolderIds as $activityId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Activity_DAO_Activity", $activityId, 'activity_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          if (CRM_Utils_Array::value('extends_entity_column_value', $customValue)) {
            $entityColumnValue = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if (CRM_Utils_Array::value($typeId, $entityColumnValue) ||
            CRM_Utils_System::isNull($entityColumnValue[$typeId])
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
          }
        }
        else {
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    // $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Batch_next') {
      CRM_Core_Session::setStatus("FILE or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update and have been excluded.");
    }

    $this->addDefaultButtons(ts('Update Activities'));
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

    $defaults = [];
    foreach ($this->_activityHolderIds as $activityId) {
      $details[$activityId] = [];
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $activityId, 'Activity');
    }

    return $defaults;
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

    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {

        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          CRM_Core_DAO::$_nullObject,
          $key, 'Activity'
        );
        $value['id'] = $key;

        if ($value['activity_date_time']) {
          $value['activity_date_time'] = CRM_Utils_Date::processDate($value['activity_date_time'], $value['activity_date_time_time']);
        }

        if ($value['activity_status_id']) {
          $value['status_id'] = $value['activity_status_id'];
        }

        if ($value['activity_details']) {
          $value['details'] = $value['activity_details'];
        }

        if ($value['activity_duration']) {
          $value['duration'] = $value['activity_duration'];
        }

        if ($value['activity_location']) {
          $value['location'] = $value['activity_location'];
        }

        if ($value['activity_subject']) {
          $value['subject'] = $value['activity_subject'];
        }

        $query = "
SELECT activity_type_id , source_contact_id 
FROM   civicrm_activity 
WHERE  id = %1";
        $params = [1 => [$key, 'Integer']];
        $dao = CRM_Core_DAO::executeQuery($query, $params);
        $dao->fetch();

        // Get Activity Type ID
        $value['activity_type_id'] = $dao->activity_type_id;

        // Get Conatct ID
        $value['source_contact_id'] = $dao->source_contact_id;


        $activityId = civicrm_activity_update($value);

        // add custom field values
        if (CRM_Utils_Array::value('custom', $value) &&
          is_array($value['custom'])
        ) {

          CRM_Core_BAO_CustomValueTable::store($value['custom'], 'civicrm_activity', $activityId->id);
        }
      }
      CRM_Core_Session::setStatus(ts("Your updates have been saved."));
    }
    else {
      CRM_Core_Session::setStatus(ts("No updates have been saved."));
    }
  }
  //end of function
}

