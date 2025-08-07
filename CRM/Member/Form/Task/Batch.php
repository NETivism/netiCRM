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
 * This class provides the functionality for batch profile update for members
 */
class CRM_Member_Form_Task_Batch extends CRM_Member_Form_Task {

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

    $readOnlyFields = [
      'contact_id' => ts('Contact ID'),
      'sort_name' => ts('Name'),
      'membership_id' => ts('Membership ID'),
    ];
    $config = CRM_Core_Config::singleton();

    // For external membership ID custom value field condition.
    if (!empty($config->externalMembershipIdFieldId)) {
      $label = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $config->externalMembershipIdFieldId, 'label');
      if (!empty($config->externalMembershipIdFieldId)) {
        $readOnlyFields['external_membership_id'] = $label;
      }
    }
    //get the read only field data.$returnProperties = array('sort_name' => 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_memberIds, 'CiviMember', $returnProperties);
    $membershipDAO = new CRM_Member_DAO_Membership();
    $membershipDAO->whereAdd("id IN (".CRM_Utils_Array::implode(',', $this->_memberIds).")");
    $membershipDAO->selectAdd(); // clear *
    $membershipDAO->selectAdd('id as membership_id');
    $membershipDAO->find();
    if (!empty($config->externalMembershipIdFieldId)) {
      $externalMembershipIds = CRM_Core_BAO_CustomValueTable::getEntitiesValues($this->_memberIds, 'Membership', [$config->externalMembershipIdFieldId]);
    }
    while($membershipDAO->fetch()) {
      $contactDetails[$membershipDAO->membership_id]['membership_id'] = $membershipDAO->membership_id;
      if (!empty($externalMembershipIds)) {
        $contactDetails[$membershipDAO->membership_id]['external_membership_id'] = $externalMembershipIds[$membershipDAO->membership_id]['custom_'.$config->externalMembershipIdFieldId];
      }
    }
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


    $this->_title = ts('Batch Update for Members') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
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
          'name' => ts('Update Members(s)'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );


    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_memberIds);
    $fileFieldExists = FALSE;


    $customFields = CRM_Core_BAO_CustomField::getFields('Membership');
    foreach ($this->_memberIds as $memberId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $memberId, 'membership_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          if (CRM_Utils_Array::value('extends_entity_column_value', $customValue)) {
            $entityColumnValue = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if ((CRM_Utils_Array::value($typeId, $entityColumnValue)) ||
            CRM_Utils_System::isNull($entityColumnValue[$typeId])
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
          }
        }
        else {
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Batch_next') {
      CRM_Core_Session::setStatus("FILE or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update and have been excluded.");
    }

    $this->addDefaultButtons(ts('Update Memberships'));
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
    foreach ($this->_memberIds as $memberId) {
      $details[$memberId] = [];
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $memberId, 'Membership');
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
      $customFields = [];

      foreach ($params['field'] as $key => $value) {
        $ids['membership'] = $key;
        if ($value['membership_source']) {
          $value['source'] = $value['membership_source'];
        }

        unset($value['membership_source']);

        //Get the membership status
        $membership = new CRM_Member_BAO_Membership();
        $membership->id = CRM_Utils_Array::value('membership', $ids);
        $membership->find(TRUE);
        $membership->free();
        $value['status_id'] = $membership->status_id;

        if (empty($customFields)) {
          // membership type custom data
          $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE, $membership->membership_type_id);

          $customFields = CRM_Utils_Array::arrayMerge($customFields,
            CRM_Core_BAO_CustomField::getFields('Membership',
              FALSE, FALSE, NULL, NULL, TRUE
            )
          );
        }
        //check for custom data
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($params['field'][$key],
          $customFields,
          $key,
          'Membership',
          $membership->membership_type_id
        );

        $membership = CRM_Member_BAO_Membership::add($value, $ids);

        // add custom field values
        if (CRM_Utils_Array::value('custom', $value) &&
          is_array($value['custom'])
        ) {

          CRM_Core_BAO_CustomValueTable::store($value['custom'], 'civicrm_membership', $membership->id);
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

