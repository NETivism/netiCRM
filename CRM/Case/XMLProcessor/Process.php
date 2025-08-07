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



class CRM_Case_XMLProcessor_Process extends CRM_Case_XMLProcessor {
  public $_isMultiClient;
  function run($caseType,
    &$params
  ) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {

      $docLink = CRM_Utils_System::docURL2("CiviCase Configuration");
      CRM_Core_Error::fatal(ts("Configuration file could not be retrieved for case type = '%1' %2.",
          [1 => $caseType, 2 => $docLink]
        ));
      return FALSE;
    }


    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $this->_isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();

    $this->process($xml, $params);
  }

  function get($caseType,
    $fieldSet, $isLabel = FALSE, $maskAction = FALSE
  ) {
    $xml = $this->retrieve($caseType);
    if ($xml === FALSE) {

      $docLink = CRM_Utils_System::docURL2("CiviCase Configuration");
      CRM_Core_Error::fatal(ts("Unable to load configuration file for the referenced case type: '%1' %2.",
          [1 => $caseType, 2 => $docLink]
        ));
      return FALSE;
    }

    switch ($fieldSet) {
      case 'CaseRoles':
        return $this->caseRoles($xml->CaseRoles);

      case 'ActivitySets':
        return $this->activitySets($xml->ActivitySets);

      case 'ActivityTypes':
        return $this->activityTypes($xml->ActivityTypes, FALSE, $isLabel, $maskAction);
    }
  }

  function process($xml,
    &$params
  ) {

    $standardTimeline = CRM_Utils_Array::value('standardTimeline', $params);
    $activitySetName = CRM_Utils_Array::value('activitySetName', $params);
    $activityTypeName = CRM_Utils_Array::value('activityTypeName', $params);

    if ('Open Case' ==
      CRM_Utils_Array::value('activityTypeName', $params)
    ) {
      // create relationships for the ones that are required
      foreach ($xml->CaseRoles as $caseRoleXML) {
        foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
          if ((int ) $relationshipTypeXML->creator == 1) {
            if (!$this->createRelationships((string ) $relationshipTypeXML->name,
                $params
              )) {
              CRM_Core_Error::fatal();
              return FALSE;
            }
          }
        }
      }
    }

    if ('Change Case Start Date' ==
      CRM_Utils_Array::value('activityTypeName', $params)
    ) {
      // delete all existing activities which are non-empty
      $this->deleteEmptyActivity($params);
    }

    foreach ($xml->ActivitySets as $activitySetsXML) {
      foreach ($activitySetsXML->ActivitySet as $activitySetXML) {
        if ($standardTimeline) {
          if ((bool ) $activitySetXML->timeline) {
            return $this->processStandardTimeline($activitySetXML,
              $params
            );
          }
        }
        elseif ($activitySetName) {
          $name = (string ) $activitySetXML->name;
          if ($name == $activitySetName) {
            return $this->processActivitySet($activitySetXML,
              $params
            );
          }
        }
      }
    }
  }

  function processStandardTimeline($activitySetXML,
    &$params
  ) {
    if ('Change Case Type' ==
      CRM_Utils_Array::value('activityTypeName', $params)
    ) {
      // delete all existing activities which are non-empty
      $this->deleteEmptyActivity($params);
    }

    foreach ($activitySetXML->ActivityTypes as $activityTypesXML) {
      foreach ($activityTypesXML as $activityTypeXML) {
        $this->createActivity($activityTypeXML, $params);
      }
    }
  }

  function processActivitySet($activitySetXML, &$params) {
    foreach ($activitySetXML->ActivityTypes as $activityTypesXML) {
      foreach ($activityTypesXML as $activityTypeXML) {
        $this->createActivity($activityTypeXML, $params);
      }
    }
  }

  function &caseRoles($caseRolesXML, $isCaseManager = FALSE) {
    $relationshipTypes = &$this->allRelationshipTypes();

    $result = [];
    foreach ($caseRolesXML as $caseRoleXML) {
      foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
        $relationshipTypeName = (string ) $relationshipTypeXML->name;
        $relationshipTypeID = array_search($relationshipTypeName,
          $relationshipTypes
        );
        if ($relationshipTypeID === FALSE) {
          continue;
        }

        if (!$isCaseManager) {
          $result[$relationshipTypeID] = $relationshipTypeName;
        }
        elseif ($relationshipTypeXML->manager) {
          return $relationshipTypeID;
        }
      }
    }
    return $result;
  }

  function createRelationships($relationshipTypeName,
    &$params
  ) {
    $relationshipTypes = &$this->allRelationshipTypes();

    // get the relationship id
    $relationshipTypeID = array_search($relationshipTypeName,
      $relationshipTypes
    );
    if ($relationshipTypeID === FALSE) {
      CRM_Core_Error::fatal();
      return FALSE;
    }

    $client = $params['clientID'];
    if (!is_array($client)) {
      $client = [$client];
    }

    foreach ($client as $key => $clientId) {
      $relationshipParams = ['relationship_type_id' => $relationshipTypeID,
        'contact_id_a' => $clientId,
        'contact_id_b' => $params['creatorID'],
        'is_active' => 1,
        'case_id' => $params['caseID'],
        'start_date' => date("Ymd"),
      ];

      if (!$this->createRelationship($relationshipParams)) {
        CRM_Core_Error::fatal();
        return FALSE;
      }
    }
    return TRUE;
  }

  function createRelationship(&$params) {


    $dao = new CRM_Contact_DAO_Relationship();
    $dao->copyValues($params);
    // only create a relationship if it does not exist
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    return TRUE;
  }

  function activityTypes($activityTypesXML, $maxInst = FALSE, $isLabel = FALSE, $maskAction = FALSE) {
    $activityTypes = &$this->allActivityTypes(TRUE, TRUE);
    $result = [];
    foreach ($activityTypesXML as $activityTypeXML) {
      foreach ($activityTypeXML as $recordXML) {
        $activityTypeName = (string ) $recordXML->name;
        $maxInstances = (string ) $recordXML->max_instances;
        $activityTypeInfo = CRM_Utils_Array::value($activityTypeName, $activityTypes);

        if ($activityTypeInfo['id']) {
          if ($maskAction) {
            if ($maskAction == 'edit' &&
              '0' === (string ) $recordXML->editable
            ) {
              $result[$maskAction][] = $activityTypeInfo['id'];
            }
          }
          else {
            if (!$maxInst) {
              //if we want,labels of activities should be returned.
              if ($isLabel) {
                $result[$activityTypeInfo['id']] = $activityTypeInfo['label'];
              }
              else {
                $result[$activityTypeInfo['id']] = $activityTypeName;
              }
            }
            else {
              if ($maxInstances) {
                $result[$activityTypeName] = $maxInstances;
              }
            }
          }
        }
      }
    }

    // call option value hook

    CRM_Utils_Hook::optionValues($result, 'case_activity_type');

    return $result;
  }

  function deleteEmptyActivity(&$params) {
    $query = "
DELETE a
FROM   civicrm_activity a
INNER JOIN civicrm_activity_target t ON t.activity_id = a.id
WHERE  t.target_contact_id = %1
AND    a.is_auto = 1
AND    a.is_current_revision = 1
";
    $sqlParams = [1 => [$params['clientID'], 'Integer']];
    CRM_Core_DAO::executeQuery($query, $sqlParams);
  }

  function isActivityPresent(&$params) {
    $query = "
SELECT     count(a.id)
FROM       civicrm_activity a
INNER JOIN civicrm_case_activity ca on ca.activity_id = a.id
WHERE      a.activity_type_id  = %1
AND        ca.case_id = %2
AND        a.is_deleted = 0
";

    $sqlParams = [1 => [$params['activityTypeID'], 'Integer'],
      2 => [$params['caseID'], 'Integer'],
    ];
    $count = CRM_Core_DAO::singleValueQuery($query, $sqlParams);

    // check for max instance
    $caseType = CRM_Case_BAO_Case::getCaseType($params['caseID']);
    $process = new CRM_Case_XMLProcessor_Process();
    $maxInstance = $process->getMaxInstance($caseType, $params['activityTypeName']);

    return $maxInstance ? ($count < $maxInstance ? FALSE : TRUE) : FALSE;
  }

  function createActivity($activityTypeXML,
    &$params
  ) {

    $activityTypeName = (string) $activityTypeXML->name;
    $activityTypes = &$this->allActivityTypes(TRUE, TRUE);
    $activityTypeInfo = CRM_Utils_Array::value($activityTypeName, $activityTypes);

    if (!$activityTypeInfo) {

      $docLink = CRM_Utils_System::docURL2("CiviCase Configuration");
      CRM_Core_Error::fatal(ts('Activity type %1, found in case configuration file, is not present in the database %2',
          [1 => $activityTypeName, 2 => $docLink]
        ));
      return FALSE;
    }
    $activityTypeID = $activityTypeInfo['id'];

    if (isset($activityTypeXML->status)) {
      $statusName = (string) $activityTypeXML->status;
    }
    else {
      $statusName = 'Scheduled';
    }

    if ($this->_isMultiClient) {
      $client = $params['clientID'];
    }
    else {
      $client = [1 => $params['clientID']];
    }


    if ($activityTypeName == 'Open Case') {
      $activityParams = ['activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => FALSE,
        'is_current_revision' => 1,
        'subject' => CRM_Utils_Array::value('subject', $params) ? $params['subject'] : $activityTypeName,
        'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
          $statusName,
          'name'
        ),
        'target_contact_id' => $client,
        'medium_id' => CRM_Utils_Array::value('medium_id', $params),
        'location' => CRM_Utils_Array::value('location', $params),
        'details' => CRM_Utils_Array::value('details', $params),
        'duration' => CRM_Utils_Array::value('duration', $params),
      ];
    }
    else {
      $activityParams = ['activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => TRUE,
        'is_current_revision' => 1,
        'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
          $statusName,
          'name'
        ),
        'target_contact_id' => $client,
      ];
    }

    //parsing date to default preference format
    $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time']);

    if ($activityTypeName == 'Open Case') {
      // we don't set activity_date_time for auto generated
      // activities, but we want it to be set for open case.
      $activityParams['activity_date_time'] = $params['activity_date_time'];
      if (CRM_Utils_Array::arrayKeyExists('custom', $params) && is_array($params['custom'])) {
        $activityParams['custom'] = $params['custom'];
      }
    }
    else {
      $activityDate = NULL;
      //get date of reference activity if set.
      if ($referenceActivityName = (string) $activityTypeXML->reference_activity) {

        //we skip open case as reference activity.CRM-4374.
        if (CRM_Utils_Array::value('resetTimeline', $params) && $referenceActivityName == 'Open Case') {
          $activityDate = $params['activity_date_time'];
        }
        else {
          $referenceActivityInfo = CRM_Utils_Array::value($referenceActivityName, $activityTypes);
          if ($referenceActivityInfo['id']) {
            $caseActivityParams = ['activity_type_id' => $referenceActivityInfo['id']];

            //if reference_select is set take according activity.
            if ($referenceSelect = (string) $activityTypeXML->reference_select) {
              $caseActivityParams[$referenceSelect] = 1;
            }


            $referenceActivity = CRM_Case_BAO_Case::getCaseActivityDates($params['caseID'], $caseActivityParams, TRUE);

            if (is_array($referenceActivity)) {
              foreach ($referenceActivity as $aId => $details) {
                $activityDate = CRM_Utils_Array::value('activity_date', $details);
                break;
              }
            }
          }
        }
      }
      if (!$activityDate) {
        $activityDate = $params['activity_date_time'];
      }
      list($activity_date, $activity_time) = CRM_Utils_Date::setDateDefaults($activityDate);
      $activityDateTime = CRM_Utils_Date::processDate($activity_date, $activity_time);
      //add reference offset to date.
      if ((int) $activityTypeXML->reference_offset) {
        $activityDateTime = CRM_Utils_Date::intervalAdd('day', (int) $activityTypeXML->reference_offset,
          $activityDateTime
        );
      }

      $activityParams['activity_date_time'] = CRM_Utils_Date::format($activityDateTime);
    }

    // if same activity is already there, skip and dont touch
    $params['activityTypeID'] = $activityTypeID;
    $params['activityTypeName'] = $activityTypeName;
    if ($this->isActivityPresent($params)) {
      return TRUE;
    }
    $activityParams['case_id'] = $params['caseID'];
    if (CRM_Utils_Array::value('is_auto', $activityParams)) {
      $activityParams['skipRecentView'] = TRUE;
    }


    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    if (!$activity) {
      CRM_Core_Error::fatal();
      return FALSE;
    }

    // create case activity record
    $caseParams = ['activity_id' => $activity->id,
      'case_id' => $params['caseID'],
    ];

    CRM_Case_BAO_Case::processCaseActivity($caseParams);
    return TRUE;
  }

  static function activitySets($activitySetsXML) {
    $result = [];
    foreach ($activitySetsXML as $activitySetXML) {
      foreach ($activitySetXML as $recordXML) {
        $activitySetName = (string ) $recordXML->name;
        $activitySetLabel = (string ) $recordXML->label;
        $result[$activitySetName] = $activitySetLabel;
      }
    }

    return $result;
  }

  function getMaxInstance($caseType, $activityTypeName = NULL) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {
      CRM_Core_Error::fatal();
      return FALSE;
    }

    $activityInstances = $this->activityTypes($xml->ActivityTypes, TRUE);
    return $activityTypeName ? $activityInstances[$activityTypeName] : $activityInstances;
  }

  function getCaseManagerRoleId($caseType) {
    $xml = $this->retrieve($caseType);
    return $this->caseRoles($xml->CaseRoles, TRUE);
  }

  function getRedactActivityEmail() {
    $xml = $this->retrieve("Settings");
    return ( string ) $xml->RedactActivityEmail ? 1 : 0;
  }

  /**
   * Retrieves AllowMultipleCaseClients setting
   *
   * @return string 1 if allowed, 0 if not
   */
  function getAllowMultipleCaseClients() {
    $xml = $this->retrieve("Settings");
    return ( string ) $xml->AllowMultipleCaseClients ? 1 : 0;
  }
}

