<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * File for the CiviCRM APIv3 activity functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Activity.php 30486 2010-11-02 16:12:09Z shot $
 *
 */

require_once 'CRM/Activity/BAO/Activity.php';
require_once 'CRM/Core/DAO/OptionGroup.php';

/**
 * Creates or updates an Activity. See the example for usage
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs for the activity.
 * {@getfields activity_create}
 *
 * @return array Array containing 'is_error' to denote success or failure and details of the created activity
 *
 * @example ActivityCreate.php Standard create example
 * @example Activity/ContactRefCustomField.php Create example including setting a contact reference custom field
 * {@example ActivityCreate.php 0}
 *
 */
function civicrm_api3_activity_create($params) {

  if (!CRM_Utils_Array::value('id', $params)) {
    // an update does not require any mandatory parameters
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      array(
        'activity_name', 'activity_type_id', 'activity_label',
      )
    );
  }

  $errors = array();

  // check for various error and required conditions
  $errors = _civicrm_api3_activity_check_params($params);

  if (!empty($errors)) {
    return $errors;
  }


  // processing for custom data
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Activity');

  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  $params['skipRecentView'] = TRUE;

  // If this is a case activity, see if there is an existing activity
  // and set it as an old revision. Also retrieve details we'll need.
  $case_id           = '';
  $createRevision    = FALSE;
  $oldActivityValues = array();
  if (CRM_Utils_Array::value('case_id', $params)) {
    $case_id = $params['case_id'];
    if (CRM_Utils_Array::value('id', $params)) {
      $oldActivityParams = array('id' => $params['id']);
      if (!$oldActivityValues) {
        CRM_Activity_BAO_Activity::retrieve($oldActivityParams, $oldActivityValues);
      }
      if (empty($oldActivityValues)) {
        return civicrm_api3_create_error(ts("Unable to locate existing activity."), NULL, CRM_Core_DAO::$_nullObject);
      }
      else {
        require_once 'CRM/Activity/DAO/Activity.php';
        $activityDAO = new CRM_Activity_DAO_Activity();
        $activityDAO->id = $params['id'];
        $activityDAO->is_current_revision = 0;
        if (!$activityDAO->save()) {
          return civicrm_api3_create_error(ts("Unable to revision existing case activity."), NULL, $activityDAO);
        }
        $createRevision = TRUE;
      }
    }
  }

  $deleteActivityAssignment = FALSE;
  if (isset($params['assignee_contact_id'])) {
    $deleteActivityAssignment = TRUE;
  }

  $deleteActivityTarget = FALSE;
  if (isset($params['target_contact_id'])) {
    $deleteActivityTarget = TRUE;
  }

  $params['deleteActivityAssignment'] = CRM_Utils_Array::value('deleteActivityAssignment', $params, $deleteActivityAssignment);
  $params['deleteActivityTarget'] = CRM_Utils_Array::value('deleteActivityTarget', $params, $deleteActivityTarget);

  if ($case_id && $createRevision) {
    // This is very similar to the copy-to-case action.
    if (!CRM_Utils_Array::isEmpty($oldActivityValues['target_contact'])) {
      $oldActivityValues['targetContactIds'] = CRM_Utils_Array::implode(',', array_unique($oldActivityValues['target_contact']));
    }
    if (!CRM_Utils_Array::isEmpty($oldActivityValues['assignee_contact'])) {
      $oldActivityValues['assigneeContactIds'] = CRM_Utils_Array::implode(',', array_unique($oldActivityValues['assignee_contact']));
    }
    $oldActivityValues['mode'] = 'copy';
    $oldActivityValues['caseID'] = $case_id;
    $oldActivityValues['activityID'] = $oldActivityValues['id'];
    $oldActivityValues['contactID'] = $oldActivityValues['source_contact_id'];

    require_once 'CRM/Activity/Page/AJAX.php';
    $copyToCase = CRM_Activity_Page_AJAX::_convertToCaseActivity($oldActivityValues);
    if (empty($copyToCase['error_msg'])) {
      // now fix some things that are different from copy-to-case
      // then fall through to the create below to update with the passed in params
      $params['id'] = $copyToCase['newId'];
      $params['is_auto'] = 0;
      $params['original_id'] = empty($oldActivityValues['original_id']) ? $oldActivityValues['id'] : $oldActivityValues['original_id'];
    }
    else {
      return civicrm_api3_create_error(ts("Unable to create new revision of case activity."), NULL, CRM_Core_DAO::$_nullObject);
    }
  }
  elseif (!empty($params['id'])) {
    // prefill activity value when update exists activity
    $oldActivityValues = array();
    $oldActivityParams = array('id' => $params['id']);
    CRM_Activity_BAO_Activity::retrieve($oldActivityParams, $oldActivityValues);
    if (!empty($oldActivityValues['status_id']) && empty($params['status_id'])) {
      $params['status_id'] = $oldActivityValues['status_id'];
    }
    if (!empty($oldActivityValues['priority_id']) && empty($params['priority_id'])) {
      $params['priority_id'] = $oldActivityValues['priority_id'];
    }
  }

  // create activity
  $activityBAO = CRM_Activity_BAO_Activity::create($params);

  if (isset($activityBAO->id)) {
    if ($case_id && !$createRevision) {
      // If this is a brand new case activity we need to add this
      $caseActivityParams = array('activity_id' => $activityBAO->id, 'case_id' => $case_id);
      require_once 'CRM/Case/BAO/Case.php';
      CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);
    }

    _civicrm_api3_object_to_array($activityBAO, $activityArray[$activityBAO->id]);
    return civicrm_api3_create_success($activityArray, $params, 'activity', 'get', $activityBAO);
  }
}
/*
 * Specify Meta data for create. Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 * @param array $params (reference) array of parameters determined by getfields
 */
function _civicrm_api3_activity_create_spec(&$params) {

  //default for source_contact_id = currently logged in user
  $params['source_contact_id']['api.default'] = 'user_contact_id';

  $params['assignee_contact_id'] = array(
    'name' => 'assignee_id',
    'title' => 'assigned to',
    'type' => 1,
    'FKClassName' => 'CRM_Activity_DAO_ActivityAssignment',
  );
  $params['target_contact_id'] = array(
    'name' => 'target_id',
    'title' => 'Activity Target',
    'type' => 1,
    'FKClassName' => 'CRM_Activity_DAO_ActivityTarget',
  );
  $params['activity_status_id'] = array(
    'name' => 'status_id',
    'title' => 'Status Id',
    'type' => 1,
  );
}

/**
 * Gets a CiviCRM activity according to parameters
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs for the activity.
 *
 * @return array
 *
 * {@getfields activity_get}
 * @example ActivityGet.php Basic example
 * @example Activity/DateTimeHigh.php Example get with date filtering
 * {@example ActivityGet.php 0}
 */
function civicrm_api3_activity_get($params) {
  if (!empty($params['contact_id'])) {
    $activities = CRM_Activity_BAO_Activity::getContactActivity($params['contact_id']);
    //BAO function doesn't actually return a contact ID - hack api for now & add to test so when api re-write happens it won't get missed
    foreach ($activities as $key => $activityArray) {
      $activities[$key]['id'] = $key;
    }
  }
  else {
    $activities = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE);
  }
  if (CRM_Utils_Array::value('return.assignee_contact_id', $params)) {
    foreach ($activities as $key => $activityArray) {
      $activities[$key]['assignee_contact_id'] = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId($activityArray['id']);
    }
  }
  if (CRM_Utils_Array::value('return.target_contact_id', $params)) {
    foreach ($activities as $key => $activityArray) {
      $activities[$key]['target_contact_id'] = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($activityArray['id']);
    }
  }
  foreach ($params as $n => $v) {
    // handle the format return.sort_name=1,return.display_name=1
    if (substr($n, 0, 13) == 'return.custom') {
      $returnProperties[substr($n, 7)] = $v;
    }
  }
  if (!empty($activities) && (!empty($returnProperties) || !empty($params['contact_id']))) {
    foreach ($activities as $activityId => $values) {

      _civicrm_api3_custom_data_get($activities[$activityId], 'Activity', $activityId, NULL, $values['activity_type_id']);
    }
  }
  //legacy custom data get - so previous formatted response is still returned too
  return civicrm_api3_create_success($activities, $params, 'activity', 'get');
}

/**
 * Delete a specified Activity.
 *
 * @param array $params array holding 'id' of activity to be deleted
 * {@getfields activity_delete}
 *
 * @return void|CRM_Core_Error  An error if 'activityName or ID' is invalid,
 *                         permissions are insufficient, etc. or CiviCRM success array
 *
 *
 *
 * @example ActivityDelete.php Standard Delete Example
 *
 *
 */
function civicrm_api3_activity_delete($params) {

  if (CRM_Activity_BAO_Activity::deleteActivity($params)) {
    return civicrm_api3_create_success(1, $params, 'activity', 'delete');
  }
  else {
    return civicrm_api3_create_error('Could not delete activity');
  }
}

/**
 * Function to check for required params
 *
 * @param array   $params  associated array of fields
 * @param boolean $addMode true for add mode
 *
 * @return array $error array with errors
 */
function _civicrm_api3_activity_check_params(&$params) {

  $contactIDFields = array_intersect_key($params,
    array(
      'source_contact_id' => 1,
      'assignee_contact_id' => 1,
      'target_contact_id' => 1,
    )
  );

  if (!empty($contactIDFields)) {
    $contactIds = array();
    foreach ($contactIDFields as $fieldname => $contactfield) {
      if (empty($contactfield)) {
        continue;
      }
      if (is_array($contactfield)) {
        foreach ($contactfield as $contactkey => $contactvalue) {
          $contactIds[$contactvalue] = $contactvalue;
        }
      }
      else {
        $contactIds[$contactfield] = $contactfield;
      }
    }


    $sql = '
SELECT  count(*)
  FROM  civicrm_contact
 WHERE  id IN (' . CRM_Utils_Array::implode(', ', $contactIds) . ' )';
    if (count($contactIds) != CRM_Core_DAO::singleValueQuery($sql)) {
      return civicrm_api3_create_error('Invalid ' .  ' Contact Id');
    }
  }


  $activityIds = array('activity' => CRM_Utils_Array::value('id', $params),
    'parent' => CRM_Utils_Array::value('parent_id', $params),
    'original' => CRM_Utils_Array::value('original_id', $params),
  );

  foreach ($activityIds as $id => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'id')
    ) {
      return civicrm_api3_create_error('Invalid ' . ucfirst($id) . ' Id');
    }
  }


  require_once 'CRM/Core/PseudoConstant.php';
  $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE);
  $activityName  = CRM_Utils_Array::value('activity_name', $params);
  $activityName  = ucfirst($activityName);
  $activityLabel = CRM_Utils_Array::value('activity_label', $params);
  if ($activityLabel) {
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
  }

  $activityTypeId = CRM_Utils_Array::value('activity_type_id', $params);

  if ($activityName || $activityLabel) {
    $activityTypeIdInList = array_search(($activityName ? $activityName : $activityLabel), $activityTypes);

    if (!$activityTypeIdInList) {
      $errorString = $activityName ? "Invalid Activity Name" : "Invalid Activity Type Label";
      return civicrm_api3_create_error($errorString);
    }
    elseif ($activityTypeId && ($activityTypeId != $activityTypeIdInList)) {
      return civicrm_api3_create_error('Mismatch in Activity');
    }
    $params['activity_type_id'] = $activityTypeIdInList;
  }
  elseif ($activityTypeId &&
    !CRM_Utils_Array::arrayKeyExists($activityTypeId, $activityTypes)
  ) {
    return civicrm_api3_create_error('Invalid Activity Type ID');
  }

  // check for activity status is passed in
  if (isset($params['activity_status_id'])) {
    require_once "CRM/Core/PseudoConstant.php";
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();

    if (is_numeric($params['activity_status_id']) && !CRM_Utils_Array::arrayKeyExists($params['activity_status_id'], $activityStatus)) {
      return civicrm_api3_create_error('Invalid Activity Status');
    }
    elseif (!is_numeric($params['activity_status_id'])) {
      $statusId = array_search($params['activity_status_id'], $activityStatus);

      if (!is_numeric($statusId)) {
        return civicrm_api3_create_error('Invalid Activity Status');
      }
    }
  }



  // check for activity duration minutes
  if (isset($params['duration_minutes']) && !is_numeric($params['duration_minutes'])) {
    return civicrm_api3_create_error('Invalid Activity Duration (in minutes)');
  }


  //if adding a new activity & date_time not set make it now
  if (!CRM_Utils_Array::value('id', $params) &&
    !CRM_Utils_Array::value('activity_date_time', $params)
  ) {
    $params['activity_date_time'] = CRM_Utils_Date::processDate(date('Y-m-d H:i:s'));
  }

  return NULL;
}

