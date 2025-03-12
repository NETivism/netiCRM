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
 * This class contains the funtions for Component export
 *
 */
class CRM_Export_BAO_Export {
  CONST EXPORT_ROW_COUNT = 2000;
  CONST EXPORT_BATCH_THRESHOLD = 10000;
  CONST EXPORT_BATCH_CSV_THRESHOLD = 100000;
  CONST VALUE_SEPARATOR = CRM_Core_DAO::VALUE_SEPARATOR;
  CONST DISPLAY_SEPARATOR = '|';

  /**
   * Function to get the list the export fields
   *
   * @param int    $selectAll user preference while export
   * @param array  $ids  contact ids
   * @param array  $params associated array of fields
   * @param string $order order by clause
   * @param array  $fields associated array of fields
   * @param array  $moreReturnProperties additional return fields
   * @param int    $exportMode export mode
   * @param string $componentClause component clause
   * @param string $componentTable component table
   * @param bool   $mergeSameAddress merge records if they have same address
   * @param bool   $mergeSameHousehold merge records if they belong to the same household
   *
   * @static
   * @access public
   */
  static function exportComponents($selectAll,
    $ids,
    $params,
    $order = NULL,
    $fields = NULL,
    $moreReturnProperties = NULL,
    $exportMode = CRM_Export_Form_Select::CONTACT_EXPORT,
    $componentClause = NULL,
    $componentTable = NULL,
    $mergeSameAddress = FALSE,
    $mergeSameHousehold = FALSE,
    $mappingId = NULL,
    $separateMode = FALSE, 
    $exportCustomVars = array()
  ) {
    global $civicrm_batch;
    $allArgs = func_get_args();

    set_time_limit(1800);
    $headerRows = $returnProperties = array();
    $primary = $paymentFields = FALSE;
    $origFields = $fields;
    $queryMode = NULL;

    $phoneTypes = CRM_Core_PseudoConstant::phoneType();
    $imProviders = CRM_Core_PseudoConstant::IMProvider();
    $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      'label',
      FALSE
    );
    $queryMode = CRM_Contact_BAO_Query::MODE_CONTACTS;

    switch ($exportMode) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_EVENT;
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_MEMBER;
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_PLEDGE;
        break;

      case CRM_Export_Form_Select::CASE_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_CASE;
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_GRANT;
        break;

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $queryMode = CRM_Contact_BAO_Query::MODE_ACTIVITY;
        break;
    }
    require_once 'CRM/Core/BAO/CustomField.php';
    if ($fields) {
      //construct return properties
      $locationTypes = CRM_Core_PseudoConstant::locationType();
      $locationTypeFields = array('street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'city',
        'postal_code',
        'postal_code_suffix',
        'geo_code_1',
        'geo_code_2',
        'state_province',
        'country',
        'phone',
        'email',
        'im',
      );

      $index = 2;

      $needsProviderId = FALSE;
      foreach ($fields as $key => $value) {
        $phoneTypeId = $imProviderId = $relationField = NULL;
        $relationshipTypes = $fieldName = CRM_Utils_Array::value(1, $value);
        if (!$fieldName) {
          continue;
        }
        // get phoneType id and IM service provider id seperately
        if ($fieldName == 'phone') {
          $phoneTypeId = CRM_Utils_Array::value(3, $value);
        }
        elseif ($fieldName == 'im') {
          $imProviderId = CRM_Utils_Array::value(3, $value);
          if (empty($imProviderId)) {
            $needsProviderId = TRUE;
          }
        }

        if (CRM_Utils_Array::arrayKeyExists($relationshipTypes, $contactRelationshipTypes)) {
          if (CRM_Utils_Array::value(2, $value)) {
            $relationField = CRM_Utils_Array::value(2, $value);
            if (trim(CRM_Utils_Array::value(3, $value))) {
              $relLocTypeId = CRM_Utils_Array::value(3, $value);
            }
            else {
              $relLocTypeId = 'Primary';
            }

            if ($relationField == 'phone') {
              $relPhoneTypeId = CRM_Utils_Array::value(4, $value);
            }
            elseif ($relationField == 'im') {
              $relIMProviderId = CRM_Utils_Array::value(4, $value);
            }
          }
          elseif (CRM_Utils_Array::value(4, $value)) {
            $relationField = CRM_Utils_Array::value(4, $value);
            $relLocTypeId = CRM_Utils_Array::value(5, $value);
            if ($relationField == 'phone') {
              $relPhoneTypeId = CRM_Utils_Array::value(6, $value);
            }
            elseif ($relationField == 'im') {
              $relIMProviderId = CRM_Utils_Array::value(6, $value);
            }
          }
        }

        $contactType = CRM_Utils_Array::value(0, $value);
        $locTypeId = CRM_Utils_Array::value(2, $value);

        if ($relationField) {
          if (in_array($relationField, $locationTypeFields) && is_numeric($relLocTypeId)) {
            if ($relPhoneTypeId) {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['phone-' . $relPhoneTypeId] = $index++;
            }
            elseif ($relIMProviderId) {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['im-' . $relIMProviderId] = $index++;
            }
            else {
              $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]][$relationField] = $index++;
            }
            $relPhoneTypeId = $relIMProviderId = NULL;
          }
          else {
            $returnProperties[$relationshipTypes][$relationField] = $index++;
          }
        }
        elseif (is_numeric($locTypeId)) {
          if ($phoneTypeId) {
            $returnProperties['location'][$locationTypes[$locTypeId]]['phone-' . $phoneTypeId] = $index++;
          }
          elseif (isset($imProviderId)) {
            //build returnProperties for IM service provider
            $returnProperties['location'][$locationTypes[$locTypeId]]['im-' . $imProviderId] = $index++;
          }
          else {
            $returnProperties['location'][$locationTypes[$locTypeId]][$fieldName] = $index++;
          }
        }
        else {
          //hack to fix component fields
          if ($fieldName == 'event_id') {
            $returnProperties['event_title'] = $index++;
          }
          else {
            $returnProperties[$fieldName] = $index++;
            if ($fieldName == 'im' && $needsProviderId) {
              $returnProperties['provider_id'] = $index++;
            }
          }
        }
      }

      // hack to add default returnproperty based on export mode
      if ($exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT && empty($returnProperties['contribution_id'])) {
        $returnProperties['contribution_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
        if(empty($returnProperties['participant_id'])){
          $returnProperties['participant_id'] = $index++;
        }
        if ($returnProperties['participant_role']) {
          $returnProperties['participant_role_id'] = $returnProperties['participant_role'];
          unset($returnProperties['participant_role']);
        }
      }
      elseif ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT && empty($returnProperties['membership_id'])) {
        $returnProperties['membership_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT && empty($returnProperties['pledge_id'])) {
        $returnProperties['pledge_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::CASE_EXPORT && empty($returnProperties['case_id'])) {
        $returnProperties['case_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::GRANT_EXPORT && empty($returnProperties['grant_id'])) {
        $returnProperties['grant_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT && empty($returnProperties['activity_id'])) {
        $returnProperties['activity_id'] = $index++;
      }
      elseif ($exportMode == CRM_Export_Form_Select::CONTACT_EXPORT && empty($returnProperties['contact_id']) && !empty($exportCustomVars)) {
        // Refs #35465, Add contact_id field for join.
        $returnProperties['contact_id'] = $index++;
      }
    }
    else {
      $primary = TRUE;
      $fields = CRM_Contact_BAO_Contact::exportableFields('All', TRUE, TRUE);
      foreach ($fields as $key => $var) {
        if ($key && (substr($key, 0, 6) != 'custom')) {
          //for CRM=952
          $returnProperties[$key] = $index++;
        }
      }

      if ($primary) {
        $returnProperties['location_type'] = $index++;
        $returnProperties['im_provider'] = $index++;
        $returnProperties['phone_type_id'] = $index++;
        $returnProperties['provider_id'] = $index++;
        $returnProperties['current_employer'] = $index++;
      }

      $extraReturnProperties = array();
      $paymentFields = FALSE;

      switch ($queryMode) {
        case CRM_Contact_BAO_Query::MODE_EVENT:
          $paymentFields = TRUE;
          $paymentTableId = "participant_id";
          break;

        case CRM_Contact_BAO_Query::MODE_MEMBER:
          $paymentFields = TRUE;
          $paymentTableId = "membership_id";
          break;

        case CRM_Contact_BAO_Query::MODE_PLEDGE:
          require_once 'CRM/Pledge/BAO/Query.php';
          $extraReturnProperties = CRM_Pledge_BAO_Query::extraReturnProperties($queryMode);
          $paymentFields = TRUE;
          $paymentTableId = "pledge_payment_id";
          break;

        case CRM_Contact_BAO_Query::MODE_CASE:
          require_once 'CRM/Case/BAO/Query.php';
          $extraReturnProperties = CRM_Case_BAO_Query::extraReturnProperties($queryMode);
          break;
      }
      foreach ($extraReturnProperties as $key => $value) {
        $extraReturnProperties[$key] = $index++;
      }

      if ($queryMode != CRM_Contact_BAO_Query::MODE_CONTACTS) {
        $componentReturnProperties = CRM_Contact_BAO_Query::defaultReturnProperties($queryMode);
        foreach ($componentReturnProperties as $key => $value) {
          $componentReturnProperties[$key] = $index++;
        }
        $returnProperties = array_merge($returnProperties, $componentReturnProperties);

        if (!empty($extraReturnProperties)) {
          $returnProperties = array_merge($returnProperties, $extraReturnProperties);
        }

        // unset non exportable fields for components
        $nonExpoFields = array('contribution_status_id',
          'pledge_status_id', 'pledge_payment_status_id',
        );
        foreach ($nonExpoFields as $value) {
          unset($returnProperties[$value]);
        }
      }
    }

    if ($mergeSameAddress) {
      $drop = FALSE;

      //make sure the addressee fields are selected
      //while using merge same address feature
      $returnProperties['addressee'] = $index++;
      $returnProperties['street_name'] = $index++;
      if (!CRM_Utils_Array::value('last_name', $returnProperties)) {
        $returnProperties['last_name'] = $index++;
        $drop = 'last_name';
      }
      $returnProperties['household_name'] = $index++;
      $returnProperties['street_address'] = $index++;
    }

    if ($moreReturnProperties) {
      // fix for CRM-7066
      if (CRM_Utils_Array::value('group', $moreReturnProperties)) {
        unset($moreReturnProperties['group']);
        $moreReturnProperties['groups'] = $index++;
      }
      $returnProperties_keys = array_keys($returnProperties);
      $moreReturnProperties_keys = array_keys($moreReturnProperties);
      $diff_moreReturnProperties = array_diff($moreReturnProperties_keys, $returnProperties_keys);
      foreach ($diff_moreReturnProperties as $field) {
        $returnProperties[$field] = $index++;
      }
    }

    $query = new CRM_Contact_BAO_Query(0, $returnProperties, NULL, FALSE, FALSE, $queryMode);
    list($select, $from, $where) = $query->query();

    if ($mergeSameHousehold == 1) {
      if (!$returnProperties['id']) {
        $returnProperties['id'] = $index++;
        $setId = TRUE;
      }
      else {
        $setId = FALSE;
      }

      $relationKey = CRM_Utils_Array::key('Household Member of', $contactRelationshipTypes);
      foreach ($returnProperties as $key => $value) {
        if (!CRM_Utils_Array::arrayKeyExists($key, $contactRelationshipTypes)) {
          $returnProperties[$relationKey][$key] = $value;
        }
      }

      unset($returnProperties[$relationKey]['location_type']);
      unset($returnProperties[$relationKey]['im_provider']);
    }

    $allRelContactArray = $relationQuery = array();

    foreach ($contactRelationshipTypes as $rel => $dnt) {
      if ($relationReturnProperties = CRM_Utils_Array::value($rel, $returnProperties)) {
        $allRelContactArray[$rel] = array();
        // build Query for each relationship
        $relationQuery[$rel] = new CRM_Contact_BAO_Query(0, $relationReturnProperties,
          NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS
        );
        list($relationSelect, $relationFrom, $relationWhere) = $relationQuery[$rel]->query();
        $relationSelect = str_replace('civicrm_state_province.abbreviation', 'civicrm_state_province.name', $relationSelect);

        list($id, $direction) = explode('_', $rel, 2);
        // identify the relationship direction
        $contactA = 'contact_id_a';
        $contactB = 'contact_id_b';
        if ($direction == 'b_a') {
          $contactA = 'contact_id_b';
          $contactB = 'contact_id_a';
        }
        if ($exportMode == CRM_Export_Form_Select::CONTACT_EXPORT) {
          $relIDs = $ids;
        }
        elseif ($exportMode == CRM_Export_Form_Select::ACTIVITY_EXPORT) {
          $query = "SELECT source_contact_id FROM civicrm_activity
                              WHERE id IN ( " . CRM_Utils_Array::implode(',', $ids) . ")";
          $dao = CRM_Core_DAO::executeQuery($query);
          while ($dao->fetch()) {
            $relIDs[] = $dao->source_contact_id;
          }
        }
        else {
          switch ($exportMode) {
            case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
              $component = 'civicrm_contribution';
              break;

            case CRM_Export_Form_Select::EVENT_EXPORT:
              $component = 'civicrm_participant';
              break;

            case CRM_Export_Form_Select::MEMBER_EXPORT:
              $component = 'civicrm_membership';
              break;

            case CRM_Export_Form_Select::PLEDGE_EXPORT:
              $component = 'civicrm_pledge';
              break;

            case CRM_Export_Form_Select::CASE_EXPORT:
              $component = 'civicrm_case';
              break;

            case CRM_Export_Form_Select::GRANT_EXPORT:
              $component = 'civicrm_grant';
              break;
          }
          $relIDs = CRM_Core_DAO::getContactIDsFromComponent($ids, $component);
        }

        $relationshipJoin = $relationshipClause = '';
        if ($componentTable) {
          $relationshipJoin = " INNER JOIN $componentTable ctTable ON ctTable.contact_id = {$contactA}";
        }
        else {
          $relID = CRM_Utils_Array::implode(',', $relIDs);
          $relationshipClause = " AND crel.{$contactA} IN ( {$relID} )";
        }
        $relTempName = CRM_Core_DAO::createTempTableName('civicrm_relationship_temp', FALSE);
        $sqlTempTable = "CREATE TEMPORARY TABLE IF NOT EXISTS $relTempName AS (SELECT * FROM civicrm_relationship ORDER BY is_active DESC, start_date DESC )";
        CRM_Core_DAO::executeQuery($sqlTempTable);
        $relationFrom = " {$relationFrom}
                INNER JOIN {$relTempName} crel ON crel.{$contactB} = contact_a.id AND crel.relationship_type_id = {$id}
                {$relationshipJoin} ";
        $relationWhere = " WHERE contact_a.is_deleted = 0 {$relationshipClause}";
        $relationGroupBy = " GROUP BY crel.{$contactA}";
        $relationSelect = "{$relationSelect}, {$contactA} as refContact ";
        $relationQueryString = "$relationSelect $relationFrom $relationWhere $relationGroupBy";

        $allRelContactDAO = CRM_Core_DAO::executeQuery($relationQueryString);
        while ($allRelContactDAO->fetch()) {
          //FIX Me: Migrate this to table rather than array
          // build the array of all related contacts
          $allRelContactArray[$rel][$allRelContactDAO->refContact] = clone($allRelContactDAO);
        }
        $allRelContactDAO->free();
      }
    }

    // make sure the groups stuff is included only if specifically specified
    // by the fields param (CRM-1969), else we limit the contacts outputted to only
    // ones that are part of a group
    if (CRM_Utils_Array::value('groups', $returnProperties)) {
      $oldClause = "contact_a.id = civicrm_group_contact.contact_id";
      $newClause = " ( $oldClause AND civicrm_group_contact.status = 'Added' OR civicrm_group_contact.status IS NULL ) ";
      // total hack for export, CRM-3618
      $from = str_replace($oldClause,
        $newClause,
        $from
      );
    }

    // Privacy in notes should be public.
    if (CRM_Utils_Array::value('notes', $returnProperties)) {
      $oldClause = "contact_a.id = civicrm_note.entity_id";
      $newClause = " ( $oldClause AND (civicrm_note.privacy = 0 OR civicrm_note.privacy IS NULL )) ";
      $from = str_replace($oldClause,
        $newClause,
        $from
      );
    }

    if ($componentTable) {
      $from .= " INNER JOIN $componentTable ctTable ON ctTable.contact_id = contact_a.id ";
      if ($order) {
        list($field, $dir) = explode(' ', $order, 2);
        $field = trim($field);
        if (CRM_Utils_Array::value($field, $returnProperties)) {
          $orderBy = " ORDER BY $order ";
        }
      }
    }
    elseif ($componentClause) {
      if (empty($where)) {
        $where = "WHERE $componentClause";
      }
      else {
        $where .= " AND $componentClause";
      }
      if (count($ids) < 1000) {
        $field = preg_replace('/ IN.+$/', '', $componentClause);
        $orderBy = " ORDER BY FIELD($field,".CRM_Utils_Array::implode(',', $ids).") ";
      }
    }

    $queryString = "$select $from $where";
    $countQuery = "SELECT contact_a.id $from $where";

    $groupBy = "";
    if (CRM_Utils_Array::value('tags', $returnProperties) ||
      CRM_Utils_Array::value('groups', $returnProperties) ||
      CRM_Utils_Array::value('notes', $returnProperties) ||
      ($queryMode & CRM_Contact_BAO_Query::MODE_CONTACTS && $query->_useGroupBy)
    ) {
      $groupBy = " GROUP BY contact_a.id";
    }
    switch ($exportMode) {
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $groupBy = 'GROUP BY civicrm_contribution.id';
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $groupBy = 'GROUP BY civicrm_participant.id';
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $groupBy = " GROUP BY civicrm_membership.id";
        break;
    }
    if ($queryMode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
      $groupBy = " GROUP BY civicrm_activity.id ";
    }
    $queryString .= $groupBy . $orderBy;
    $countQuery .=  $groupBy;

    //hack for student data
    require_once 'CRM/Core/OptionGroup.php';
    $multipleSelectFields = array('preferred_communication_method' => 1);

    if (CRM_Core_Permission::access('Quest')) {
      require_once 'CRM/Quest/BAO/Student.php';
      $studentFields = array();
      $studentFields = CRM_Quest_BAO_Student::$multipleSelectFields;
      $multipleSelectFields = array_merge($multipleSelectFields, $studentFields);
    }

    $header = $addPaymentHeader = FALSE;

    if ($paymentFields) {
      //special return properties for event and members
      $paymentHeaders = array('total_amount' => ts('Total Amount'),
        'contribution_status' => ts('Contribution Status'),
        'received_date' => ts('Received Date'),
        'payment_instrument' => ts('Payment Instrument'),
        'transaction_id' => ts('Transaction ID'),
      );

      // get payment related in for event and members
      require_once 'CRM/Contribute/BAO/Contribution.php';
      $paymentDetails = CRM_Contribute_BAO_Contribution::getContributionDetails($exportMode, $ids);
      if (!empty($paymentDetails)) {
        $addPaymentHeader = TRUE;
      }
      $nullContributionDetails = array_fill_keys($paymentHeaders, NULL);
    }

    $componentDetails = $headerRows = $sqlColumns = array();
    $setHeader = TRUE;
    $fieldOrder = array();
    $offset = 0;

    // for CRM-3157 purposes
    $i18n = CRM_Core_I18n::singleton();

    $rowCount = self::EXPORT_ROW_COUNT;
    $count = 0;

    if (empty($civicrm_batch)) {
      $countDAO = CRM_Core_DAO::executeQuery($countQuery);
      $totalNumRows = $countDAO->N;
      $fileName = self::getExportFileName($exportMode);
      if ($totalNumRows > self::EXPORT_BATCH_THRESHOLD) {
        // start batch
        $config = CRM_Core_Config::singleton();
        $file = $config->uploadDir.$fileName;
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Export').': '.$fileName,
          'startCallback' => NULL,
          'startCallbackArgs' => NULL,
          'processCallback' => array(__CLASS__, __FUNCTION__),
          'processCallbackArgs' => $allArgs,
          'finishCallback' => array(__CLASS__, 'batchFinish'),
          'finishCallbackArgs' => NULL,
          'exportFile' => $file,
          'download' => array(
            'header' => array(
              'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              'Content-Disposition: attachment;filename="'.$fileName.'"',
            ),
            'file' => $file,
          ),
          'actionPermission' => '',
          'total' => $totalNumRows,
          'processed' => 0,
        );
        if ($totalNumRows > self::EXPORT_BATCH_CSV_THRESHOLD) {
          $fileName = str_replace('.xlsx', '.csv', $fileName);
          $file = $config->uploadDir.$fileName;
          $batchParams['label'] = ts('Export').': '.$fileName;
          $batchParams['exportFile'] = $file;
          $batchParams['download'] = array(
            'header' => array(
              'Content-Type: text/csv',
              'Content-Disposition: attachment;filename="'.$fileName.'"',
            ),
            'file' => $batchParams['exportFile'],
          );
        }
        $batch->start($batchParams);

        // refs #32446, 
        self::audit($exportMode, $fileName, $totalNumRows, $returnProperties);

        // redirect to notice page
        CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
      }
      else {
        self::audit($exportMode, $fileName, $totalNumRows, $returnProperties);
      }
    }
    else {
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'] ;
      }
    }

    while (1) {
      $limitQuery = "{$queryString} LIMIT {$offset}, {$rowCount}";
      $dao = CRM_Core_DAO::executeQuery($limitQuery);

      if ($dao->N <= 0) {
        break;
      }

      while ($dao->fetch()) {
        $row = array();

        $activityTargetNames = NULL;
        $activityAssignNames = NULL;

        //first loop through returnproperties so that we return what is required, and in same order.
        $relationshipField = 0;
        foreach ($returnProperties as $field => $value) {
          //we should set header only once
          if ($setHeader) {
            $sqlDone = FALSE;
            if (isset($query->_fields[$field]['title'])) {
              $headerRows[$value] = $query->_fields[$field]['title'];
              $fieldOrder[] = $value;
            }
            elseif ($field == 'phone_type_id') {
              $headerRows[$value] = 'Phone Type';
              $fieldOrder[] = $value;
            }
            elseif ($field == 'provider_id') {
              $headerRows[$value] = ts('Instant Messenger Services');
              $fieldOrder[] = $value;
            }
            elseif (is_array($value) && $field == 'location') {
              // fix header for location type case
              foreach ($value as $ltype => $val) {
                foreach (array_keys($val) as $fld) {
                  $type = explode('-', $fld);
                  $hdr = "{$ltype}-" . $query->_fields[$type[0]]['title'];

                  if (CRM_Utils_Array::value(1, $type)) {
                    if (CRM_Utils_Array::value(0, $type) == 'phone') {
                      $hdr .= "-" . CRM_Utils_Array::value($type[1], $phoneTypes);
                    }
                    elseif (CRM_Utils_Array::value(0, $type) == 'im') {
                      $hdr .= "-" . CRM_Utils_Array::value($type[1], $imProviders);
                    }
                  }
                  $headerRows[$val[$fld]] = $hdr;
                  $fieldOrder[] = $val[$fld];
                  self::sqlColumnDefn($query, $sqlColumns, $hdr, $val[$fld]);
                }
                $sqlDone = TRUE;
              }
            }
            elseif (substr($field, 0, 5) == 'case_') {
              if ($query->_fields['case'][$field]['title']) {
                $headerRows[$value] = $query->_fields['case'][$field]['title'];
                $fieldOrder[] = $value;
              }
              elseif ($query->_fields['activity'][$field]['title']) {
                $headerRows[$value] = $query->_fields['activity'][$field]['title'];
                $fieldOrder[] = $value;
              }
            }
            elseif (CRM_Utils_Array::arrayKeyExists($field, $contactRelationshipTypes)) {
              $relName = $field;
              foreach ($value as $relationField => $relationValue) {
                // below block is same as primary block (duplicate)
                if (isset($relationQuery[$field]->_fields[$relationField]['title'])) {
                  $headerName = $field . '-' . $relationQuery[$field]->_fields[$relationField]['title'];
                  $headerRows[$relationValue] = $headerName;
                  $fieldOrder[] = $relationValue;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName, $relationValue);
                }
                elseif ($relationField == 'phone_type_id') {
                  $headerName = $field . '-' . 'Phone Type';
                  $headerRows[$relationValue] = $headerName;
                  $fieldOrder[] = $relationValue;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName, $relationValue);
                }
                elseif ($relationField == 'provider_id') {
                  $headerName = $field . '-' . 'Im Service Provider';
                  $headerRows[$relationValue] = $headerName;
                  $fieldOrder[] = $relationValue;
                  self::sqlColumnDefn($query, $sqlColumns, $headerName, $relationValue);
                }
                elseif (is_array($relationValue) && $relationField == 'location') {
                  // fix header for location type case
                  foreach ($relationValue as $ltype => $val) {
                    foreach (array_keys($val) as $fld) {
                      $type = explode('-', $fld);
                      $hdr = "{$ltype}-" . $relationQuery[$field]->_fields[$type[0]]['title'];

                      if (CRM_Utils_Array::value(1, $type)) {
                        if (CRM_Utils_Array::value(0, $type) == 'phone') {
                          $hdr .= "-" . CRM_Utils_Array::value($type[1], $phoneTypes);
                        }
                        elseif (CRM_Utils_Array::value(0, $type) == 'im') {
                          $hdr .= "-" . CRM_Utils_Array::value($type[1], $imProviders);
                        }
                      }
                      $headerName = $field . '-' . $hdr;
                      $headerRows[$val[$fld]] = $headerName;
                      $fieldOrder[] = $val[$fld];
                      self::sqlColumnDefn($query, $sqlColumns, $headerName, $val[$fld]);
                    }
                  }
                }
              }
            }
            else {
              $headerRows[$value] = $field;
              $fieldOrder[] = $value;
            }

            if (!$sqlDone) {
              self::sqlColumnDefn($query, $sqlColumns, $field, $value);
            }
          }
          if ($field == 'state_province') {
            $field = 'state_province_name';
          }

          //build row values (data)
          if (property_exists($dao, $field)) {
            $fieldValue = $dao->$field;
            // to get phone type from phone type id
            if ($field == 'phone_type_id') {
              $fieldValue = $phoneTypes[$fieldValue];
            }
            elseif ($field == 'provider_id') {
              $fieldValue = CRM_Utils_Array::value($fieldValue, $imProviders);
            }
            elseif ($field == 'participant_role_id') {
              $participantRoles = CRM_Event_PseudoConstant::participantRole();
              $viewRoles = array();
              foreach (explode(self::VALUE_SEPARATOR, $dao->$field) as $k => $v) {
                $viewRoles[] = $participantRoles[$v];
              }
              $fieldValue = CRM_Utils_Array::implode(self::DISPLAY_SEPARATOR, $viewRoles);
            }
          }
          elseif ($field == 'master_address_belongs_to') {
            $masterAddressId = NULL;
            if (isset($dao->master_id)) {
              $masterAddressId = $dao->master_id;
            }
            // get display name of contact that address is shared.
            $fieldValue = CRM_Contact_BAO_Contact::getMasterDisplayName($masterAddressId, $dao->contact_id);
          }
          else {
            $fieldValue = '';
          }

          if (strstr($field, 'target_contact')) {
            if (empty($activityTargetNames)) {
              $activityTargetNames = CRM_Activity_BAO_ActivityTarget::getTargetNames($dao->activity_id);
            }
            if (strstr($field, '_name')) {
              $fieldValue = reset($activityTargetNames);
            }
            if (strstr($field, '_id')) {
              $fieldValue = reset(array_keys($activityTargetNames));
            }
          }
          if (strstr($field, 'assign_contact')) {
            if (empty($activityAssignNames)) {
              $activityAssignNames = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($dao->activity_id);
            }
            if (strstr($field, '_name')) {
              $fieldValue = reset($activityAssignNames);
            }
            if (strstr($field, '_id')) {
              $fieldValue = reset(array_keys($activityAssignNames));
            }
          }

          if ($field == 'id') {
            $row[$field] = $dao->contact_id;
            // special case for calculated field
          }
          elseif ($field == 'pledge_balance_amount') {
            $row[$field] = $dao->pledge_amount - $dao->pledge_total_paid;
            // special case for calculated field
          }
          elseif ($field == 'pledge_next_pay_amount') {
            $row[$field] = $dao->pledge_next_pay_amount + $dao->pledge_outstanding_amount;
          }
          elseif (is_array($value) && $field == 'location') {
            // fix header for location type case
            foreach ($value as $ltype => $val) {
              foreach (array_keys($val) as $fld) {
                $type = explode('-', $fld);
                $fldValue = "{$ltype}-" . $type[0];

                if (CRM_Utils_Array::value(1, $type)) {
                  $fldValue .= "-" . $type[1];
                }

                // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
                switch ($fld) {
                  case 'country':
                  case 'world_region':
                    $row[$fldValue] = $dao->$fldValue ? $i18n->crm_translate($dao->$fldValue, array('context' => 'country')) : '';
                    break;

                  case 'state_province_name':
                  case 'state_province':
                    $row[$fldValue] = $dao->$fldValue ? $i18n->crm_translate($dao->$fldValue, array('context' => 'province')) : '';
                    break;

                  default:
                    $row[$fldValue] = $dao->$fldValue;
                    break;
                }
              }
            }
          }
          elseif (CRM_Utils_Array::arrayKeyExists($field, $contactRelationshipTypes)) {
            $relDAO = $allRelContactArray[$field][$dao->contact_id];

            foreach ($value as $relationField => $relationValue) {
              if (is_object($relDAO) && property_exists($relDAO, $relationField)) {
                $fieldValue = $relDAO->$relationField;
                if ($relationField == 'phone_type_id') {
                  $fieldValue = $phoneTypes[$relationValue];
                }
                elseif ($relationField == 'provider_id') {
                  $fieldValue = CRM_Utils_Array::value($relationValue, $imProviders);
                }
              }
              else {
                $fieldValue = '';
              }
              if ($relationField == 'id') {
                $row[$field . $relationField] = $relDAO->contact_id;
              }
              elseif (is_array($relationValue) && $relationField == 'location') {
                foreach ($relationValue as $ltype => $val) {
                  foreach (array_keys($val) as $fld) {
                    $type = explode('-', $fld);
                    $fldValue = "{$ltype}-" . $type[0];
                    if (CRM_Utils_Array::value(1, $type)) {
                      $fldValue .= "-" . $type[1];
                    }
                    // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
                    switch (TRUE) {
                      case in_array('country', $type):
                      case in_array('world_region', $type):
                        $row[$field . $fldValue] = $relDAO->$fldValue ? $i18n->crm_translate($relDAO->$fldValue, array('context' => 'country')) : $relDAO->$fldValue;
                        break;

                      case in_array('state_province_name', $type):
                      case in_array('state_province', $type):
                        $row[$field . $fldValue] = $relDAO->$fldValue ? $i18n->crm_translate($relDAO->$fldValue, array('context' => 'province')) : $relDAO->$fldValue;
                        break;

                      default:
                        $row[$field . $fldValue] = $relDAO->$fldValue;
                        break;
                    }
                  }
                }
              }
              elseif (isset($fieldValue) && $fieldValue != '') {
                //check for custom data
                if ($cfID = CRM_Core_BAO_CustomField::getKeyID($relationField)) {
                  if($relationQuery[$field]->_fields[$relationField]['data_type'] == 'File'){
                    list($url, $ignore1, $ignore2) = CRM_Core_BAO_File::url($fieldValue, NULL);
                    $row[$field . $relationField] = $url;
                  }else{
                    $row[$field . $relationField] = CRM_Core_BAO_CustomField::getDisplayValue($fieldValue, $cfID, $relationQuery[$field]->_options, NULL, $separateMode);
                  }
                }
                elseif (in_array($relationField, array('email_greeting', 'postal_greeting', 'addressee'))) {
                  //special case for greeting replacement
                  $fldValue = "{$relationField}_display";
                  $row[$field . $relationField] = $relDAO->$fldValue;
                }
                else {
                  //normal relationship fields
                  // CRM-3157: localise country, region (both have ‘country’ context) and state_province (‘province’ context)
                  switch ($relationField) {
                    case 'country':
                    case 'world_region':
                      $row[$field . $relationField] = $fieldValue ? $i18n->crm_translate($fieldValue, array('context' => 'country')) : $fieldValue;
                      break;

                    case 'state_province_name':
                    case 'state_province':
                      $row[$field . $relationField] = $fieldValue ? $i18n->crm_translate($fieldValue, array('context' => 'province')) : $fieldValue;
                      break;

                    default:
                      $row[$field . $relationField] = $fieldValue;
                      break;
                  }
                }
              }
              else {
                // if relation field is empty or null
                $row[$field . $relationField] = '';
              }
            }
          }
          elseif (isset($fieldValue) && $fieldValue != '') {
            //check for custom data
            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($field)) {
              if($query->_fields[$field]['data_type'] == 'File' && !empty($dao->$field)){
                list($url, $ignore1, $ignore2) = CRM_Core_BAO_File::url($dao->$field, NULL);
                $row[$field] = $url;
              }else{
                $row[$field] = CRM_Core_BAO_CustomField::getDisplayValue($fieldValue, $cfID, $query->_options, NULL, $separateMode);
              }
            }
            elseif (CRM_Utils_Array::arrayKeyExists($field, $multipleSelectFields)) {
              //option group fixes
              $paramsNew = array($field => $fieldValue);
              if ($field == 'test_tutoring') {
                $name = array($field => array('newName' => $field, 'groupName' => 'test'));
                // for  readers group
              }
              elseif (substr($field, 0, 4) == 'cmr_') {
                $name = array($field => array('newName' => $field, 'groupName' => substr($field, 0, -3)));
              }
              else {
                $name = array($field => array('newName' => $field, 'groupName' => $field));
              }
              CRM_Core_OptionGroup::lookupValues($paramsNew, $name, FALSE);
              $row[$field] = $paramsNew[$field];
            }
            elseif (in_array($field, array('email_greeting', 'postal_greeting', 'addressee'))) {
              //special case for greeting replacement
              $fldValue = "{$field}_display";
              $row[$field] = $dao->$fldValue;
            }
            else {
              //normal fields with a touch of CRM-3157
              switch ($field) {
                case 'country':
                case 'world_region':
                  $row[$field] = $i18n->crm_translate($fieldValue, array('context' => 'country'));
                  break;

                case 'state_province_name':
                  $row[$field] = $i18n->crm_translate($fieldValue, array('context' => 'province'));
                  break;

                case 'gender':
                case 'preferred_communication_method':
                case 'preferred_mail_format':
                  $row[$field] = $i18n->crm_translate($fieldValue);
                  break;
                case 'age':
                  $age = CRM_Utils_Date::calculateAge($fieldValue);
                  $row[$field] = CRM_Utils_Array::value('years', $age);
                  break;

                default:
                  $row[$field] = $fieldValue;
                  break;
              }
            }
          }
          else {
            // if field is empty or null
            $row[$field] = '';
          }
        }
        $newRow = array();
        $rowIndex = 0;
        foreach ($row as $value) {
          $newRow[$fieldOrder[$rowIndex]] = $value;
          $rowIndex++;
        }
        $row = $newRow;

        if ($setHeader) {
          ksort($headerRows);
          ksort($sqlColumns);
        }

        ksort($row);

        // add payment headers if required
        if ($addPaymentHeader && $paymentFields) {
          $headerRows = array_merge($headerRows, $paymentHeaders);
          foreach ($paymentHeaders as $paymentHdr) {
            self::sqlColumnDefn($query, $sqlColumns, $paymentHdr);
          }
          $addPaymentHeader = FALSE;
        }

        if ($setHeader) {
          $exportTempTable = self::createTempTable($sqlColumns);
        }

        //build header only once
        $setHeader = FALSE;

        // add payment related information
        if ($paymentFields && isset($paymentDetails[$row[$paymentTableId]])) {
          $row = array_merge($row, $paymentDetails[$row[$paymentTableId]]);
        }
        elseif ($paymentDetails) {
          $row = array_merge($row, $nullContributionDetails);
        }

        //remove organization name for individuals if it is set for current employer
        if (CRM_Utils_Array::value('contact_type', $row) && $row['contact_type'] == 'Individual' && CRM_Utils_Array::arrayKeyExists('organization_name', $row)) {
          $row['organization_name'] = '';
        }

        // add component info
        // write the row to a file
        $componentDetails[] = $row;
        $count++;
      }
      if ($civicrm_batch && count(reset($componentDetails)) <= 3) {
        $recordlog = TRUE;
      }
      self::writeDetailsToTable($exportTempTable, $componentDetails, $sqlColumns);
      $componentDetails = array();
      $dao->free();

      // continue process next run
      $offset += $rowCount;

      // every batch only process threshold
      if (!empty($civicrm_batch)) {
        if ($count >= self::EXPORT_BATCH_THRESHOLD) {
          break;
        }
      }
    }
    if (!empty($recordlog)) {
      CRM_Core_Error::debug_log_message("debug #31515 - start from ".$civicrm_batch->data['processed']." - query - ".$limitQuery);
    }

    // do merge same address and merge same household processing
    if ($mergeSameAddress) {
      self::mergeSameAddress($exportTempTable, $headerRows, $sqlColumns, $drop);
    }

    // merge the records if they have corresponding households
    if ($mergeSameHousehold) {
      self::mergeSameHousehold($exportTempTable, $headerRows, $sqlColumns, $relationKey);
    }

    // fix the headers for rows with relationship type
    if ($relName) {
      self::manipulateHeaderRows($headerRows, $contactRelationshipTypes);
    }

    if (!empty($exportCustomVars)) {

      // prepare data for custom search export.
      $exportCustomResult = CRM_Export_BAO_Export::exportCustom(
        $exportCustomVars['customSearchClass'],
        $exportCustomVars['formValues'],
        $exportCustomVars['order'],
        $exportCustomVars['pirmaryIDName']
      );

      $customHeader = $exportCustomResult['header'];
      $customRows = $exportCustomResult['rows'];
      $primaryIDName = empty($exportCustomVars['pirmaryIDName']) ? 'contact_id' : $exportCustomVars['pirmaryIDName'];
      $csResultTempTable = CRM_Core_DAO::createTempTableName('civicrm_task_action', FALSE);
      foreach ($customHeader as $columnName => $val) {
        if ($primaryIDName == 'contact_id' && $columnName == 'contact_id') {
          // If primary field of custom search result is 'contact_id', it will write as $primaryIDName in the former $sql. So skip it.
          continue;
        }
        $customColumns .= ", $columnName varchar(255)";
      }

      // Create custom search custom table.
      $sql = "CREATE TEMPORARY TABLE {$csResultTempTable} ( $primaryIDName int primary key $customColumns) ENGINE=MyISAM DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      CRM_Core_DAO::executeQuery($sql);

      // Write data into Table
      $customColumnsNames = str_replace('varchar(255)', '', $customColumns);
      foreach ($customRows as $key => $rows) {
        if ($primaryIDName == 'contact_id') {
          unset($rows[$primaryIDName]);
        }
        $params = array();
        $i = 1;
        $values = '';
        foreach ($rows as $value) {
          if (is_null($value)) {
            $value = '';
          }
          $values .= ", %{$i}";
          $params[$i] = array(
            $value, 
            'String'
          );
          $i++;
        }
        $sql = "REPLACE INTO {$csResultTempTable} ( $primaryIDName $customColumnsNames) VALUES ( {$key} $values)";
        CRM_Core_DAO::executeQuery($sql, $params);
      }

      // Join custom search table and selected field table.
      foreach ($customHeader as $fieldName => $dontcard) {
        if (strstr($fieldName, 'column')) {
          $componentColumns .= ", csResultTable.{$fieldName}";
        }
      }

      $exportTempTableSelectFields = array();
      foreach ($sqlColumns as $value) {
        if (!strstr($value, 'contact_id')) {
          // grep field name from string like 'field_name varchar(16)';
          $fieldName = preg_replace('/^([a-z0-9_]+) .+$/i', '$1', $value);
          $exportTempTableSelectFields[] = "{$exportTempTable}.{$fieldName}";
        }
      }
      $exportTempTableSelectColumns = CRM_Utils_Array::implode(', ', $exportTempTableSelectFields);
      $tempTableName = 'new_export_temp_table';
      $sql = "CREATE TEMPORARY TABLE $tempTableName SELECT $exportTempTableSelectColumns $componentColumns FROM $csResultTempTable csResultTable INNER JOIN $exportTempTable ON csResultTable.contact_id = $exportTempTable.contact_id";
      CRM_Core_DAO::executeQuery($sql);
      $exportTempTable = $tempTableName;

      // unset 'contact_id' in header
      $key = array_search('contact_id', $headerRows);
      unset($headerRows[$key]);
      $headerRows = $customHeader + $headerRows;
      foreach (array_reverse($customHeader) as $key => $ignore) {
        array_unshift($sqlColumns, "$key varchar(255)");
      }
    }

    // call export hook
    require_once 'CRM/Utils/Hook.php';
    CRM_Utils_Hook::export($exportTempTable, $headerRows, $sqlColumns, $exportMode, $mappingId);

    // now write the CSV file
    if ($civicrm_batch) {
      $fileUri = $civicrm_batch->data['exportFile']; 
      $query = "SELECT * FROM $exportTempTable";
      $dao = CRM_Core_DAO::executeQuery($query);
      CRM_Core_Error::debug_log_message("expect $count rows, temp table rows $dao->N");
      if (!empty($count) && $count == $dao->N) {
        self::writeBatchFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode, $fileUri);
        $civicrm_batch->data['processed'] += $count;
      }
      return;
    }
    else {
      self::writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode, $fileName);
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * name of the export file based on mode
   *
   * @param string  $output type of output
   * @param int     $mode export mode
   *
   * @return string name of the file
   */
  static function getExportFileName($mode = NULL) {
    $rand = substr(md5(microtime(TRUE)), 0, 4);
    $name = self::getExportName($mode);
    return date('Ymd_').str_replace(array(' ', '.', '/', '-') , '_', $name) . "_" . $rand . '.xlsx';
  }

  /**
   * Name of the export mode
   *
   * @param int     $mode export mode
   *
   * @return string name of export mode
   */
  public static function getExportName($mode = NULL) {
    switch ($mode) {
      case CRM_Export_Form_Select::CONTACT_EXPORT:
        $name = ts('CiviCRM Contact Search');
        break;
      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $name = ts('CiviCRM Contribution Search');
        break;
      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $name = ts('CiviCRM Member Search');
        break;
      case CRM_Export_Form_Select::EVENT_EXPORT:
        $name = ts('CiviCRM Participant Search');
        break;
      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $name = ts('CiviCRM Pledge Search');
        break;
      case CRM_Export_Form_Select::CASE_EXPORT:
        $name = ts('CiviCRM Case Search');
        break;
      case CRM_Export_Form_Select::GRANT_EXPORT:
        $name = ts('CiviCRM Grant Search');
        break;
      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $name = ts('CiviCRM Activity Search');
        break;
      default:
        $name = 'civicrm_export';
        break;
    }
    return $name;
  }

  /**
   * Alias of importError to support old menu
   *
   * @deprecated
   * @return void
   */
  public static function invoke() {
    self::importError();
  }

  /**
   * Function to handle import error file creation.
   *
   **/
  public static function importError() {
    $type = CRM_Utils_Request::retrieve('type', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $parserName = CRM_Utils_Request::retrieve('parser', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    if (empty($type) || empty($parserName) || empty($qfKey)) {
      CRM_Core_Error::fatal('Invalid parameters');
    }

    // clean and ensure parserName is a valid string
    $parserName = CRM_Utils_String::munge($parserName);
    $filename = CRM_Import_Parser::getImportErrorFilename($qfKey, $type, $parserName);
    if (!empty($filename)) {
      $config = CRM_Core_Config::singleton();
      $errorFileName = $config->uploadDir . $filename;
      if (!empty($errorFileName) && is_file($errorFileName)) {
        $buffer = '';
        CRM_Utils_System::download(
          $filename,
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          $buffer,
          NULL,
          FALSE
        );
        readfile($errorFileName);
      }
    }
    else {
      CRM_Core_Error::debug_log_message('Import error file fetch error - unknown file: '."$qfKey $type $parserName");
      CRM_Core_Error::fatal('Invalid parameters');
    }

    CRM_Utils_System::civiExit();
  }

  static function exportCustom($customSearchClass, $formValues, $order, $primaryIDName = FALSE, $returnRows = TRUE, $exportFile = FALSE) {
    require_once "CRM/Core/Extensions.php";
    $ext = new CRM_Core_Extensions();
    if (!$ext->isExtensionClass($customSearchClass)) {
      if(!class_exists($customSearchClass)){
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $customSearchClass) . '.php');
      }
    }
    else {
      require_once ($ext->classToPath($customSearchClass));
    }
    $search = new $customSearchClass($formValues);
    $search->_isExport = TRUE;

    $includeContactIDs = FALSE;
    if ($formValues['radio_ts'] == 'ts_sel') {
      $includeContactIDs = TRUE;
    }

    $sql = $search->all(0, 0, $order, $includeContactIDs);

    $columns = $search->columns();

    if (!empty($primaryIDName)) {
      $keyContactIDName = array_search('contact_id', $columns);
      unset($columns[$keyContactIDName]);
      $header = array_keys($columns);
      $header[] = ts('CiviCRM Contact ID');
      $fields = array_values($columns);
      $fields[] = 'contact_id';
    }
    else {
      $header = array_keys($columns);
      $fields = array_values($columns);
    }

    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    $alterRow = FALSE;
    if (method_exists($search, 'alterRow')) {
      $alterRow = TRUE;
    }
    while ($dao->fetch()) {
      $row = array();

      foreach ($fields as $field) {
        // Avoid "too many $dao->$field doesn't exist" error messages.
        if (isset($dao->$field)) {
          $row[$field] = CRM_Utils_String::toNumber($dao->$field);
        }
        else {
          $row[$field] = NULL;
        }
      }
      if ($alterRow) {
        $search->alterRow($row);
      }
      if (!empty($primaryIDName)) {
        unset($row['contact_id']);
        $row['contact_id'] = $dao->contact_id;
      }
      unset($row['action']);
      if (!empty($primaryIDName)) {
        $rows[$dao->$primaryIDName] = $row;
      }
      elseif (isset($dao->id)) {
        $rows[$dao->id] = $row;
      }
      elseif (isset($dao->contact_id)) {
        $rows[$dao->contact_id] = $row;
      }
      else {
        $rows[] = $row;
      }
      // If only return Header, just run once.
      if (!$returnRows) {
        break;
      }
    }

    // remove the fields which key is numeric. refs #19235
    foreach ($header as $key => $value) {
      $header[$key] = strip_tags($value);
      if(is_numeric($value)){
        unset($header[$key]);
        foreach ($rows as &$row) {
          unset($row[$fields[$key]]);
        }
        unset($fields[$key]);
      }
      else {
        if ($value == ts('CiviCRM Contact ID')) {
          $customHeader['contact_id'] = $value;  
        }
        elseif ($key == 0 && $fields[0] == 'contact_id') {
          // If primary field is 'contact_id', than don't use column_0.
          $customHeader['contact_id'] = $value;
        }
        else {
          $customHeader["column_{$key}"] = $value;
        }
      }
    }

    if ($exportFile) {
      CRM_Core_Report_Excel::writeExcelFile(self::getExportFileName(), $header, $rows);
      CRM_Utils_System::civiExit();
    }
    else {
      if ($returnRows) {
        $returnArray = array('header' => $customHeader, 'rows' => $rows);
      }
      else {
        $returnArray = array('header' => $customHeader);
      }
      return $returnArray;
    }
  }

  static function sqlColumnDefn(&$query, &$sqlColumns, $field, $index = 1) {
    if (substr($field, -4) == '_a_b' ||
      substr($field, -4) == '_b_a'
    ) {
      return;
    }

    $fieldName = CRM_Utils_String::munge(strtolower($field), '_', 64);
    if ($fieldName == 'id') {
      $fieldName = 'civicrm_primary_id';
    }

    // set the sql columns
    $newColumn = '';
    if (isset($query->_fields[$field]['type'])) {
      switch ($query->_fields[$field]['type']) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_BOOL:
        case CRM_Utils_Type::T_BOOLEAN:
          $newColumn = "$fieldName varchar(16)";
          break;

        case CRM_Utils_Type::T_STRING:
          if (isset($query->_fields[$field]['maxlength'])) {
            $newColumn = "$fieldName varchar({$query->_fields[$field]['maxlength']})";
          }
          else {
            $newColumn = "$fieldName varchar(64)";
          }
          break;

        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
          $newColumn = "$fieldName longtext";
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_ENUM:
        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIME:
        case CRM_Utils_Type::T_TIMESTAMP:
        case CRM_Utils_Type::T_MONEY:
        case CRM_Utils_Type::T_EMAIL:
        case CRM_Utils_Type::T_URL:
        case CRM_Utils_Type::T_CCNUM:
        default:
          $newColumn = "$fieldName varchar(32)";
          break;
      }
    }
    else {
      if (substr($fieldName, -3, 3) == '_id') {
        $newColumn = "$fieldName varchar(16)";
      }
      else {
        $changeFields = array('groups', 'tags', 'notes', 'contribution_note');
        if (in_array($fieldName, $changeFields)) {
          $newColumn = "$fieldName text";
        }
        else {
          // set the sql columns for custom data
          if (isset($query->_fields[$field]['data_type'])) {

            switch ($query->_fields[$field]['data_type']) {
              case 'Country':
              case 'StateProvince':
              case 'Link':
              case 'String':
                $newColumn = "$fieldName text";
                break;

              case 'Memo':
                $newColumn = "$fieldName text";
                break;

              default:
                $newColumn = "$fieldName varchar(255)";
                break;
            }
          }
          else {
            $newColumn = "$fieldName varchar(255)";
          }
        }
      }
    }

    if($index == 1){
      $sqlColumns[] = $newColumn;
    }else{
      $sqlColumns[$index] = $newColumn;
    }
  }

  static function writeDetailsToTable($tableName, &$details, &$sqlColumns) {
    if (empty($details)) {
      return;
    }

    $sql = "
SELECT max(id)
FROM   $tableName
";

    $sqlColumnsKeys = array();
    foreach ($sqlColumns as $value) {
      $arr = explode(' ', $value);
      $sqlColumnsKeys[] = $arr[0];
    }

    $id = CRM_Core_DAO::singleValueQuery($sql);
    if (!$id) {
      $id = 0;
    }

    $sqlClause = array();

    foreach ($details as $dontCare => $row) {
      $id++;
      $valueString = array($id);
      foreach ($row as $dontCare => $value) {
        if (empty($value)) {
          $valueString[] = "''";
        }
        else {
          $valueString[] = "'" . CRM_Core_DAO::escapeString($value) . "'";
        }
      }
      $sqlClause[] = '(' . CRM_Utils_Array::implode(',', $valueString) . ')';
    }

    $sqlColumnString = '(id, ' . CRM_Utils_Array::implode(',', $sqlColumnsKeys) . ')';

    $sqlValueString = CRM_Utils_Array::implode(",\n", $sqlClause);

    $sql = "
INSERT INTO $tableName $sqlColumnString
VALUES $sqlValueString
";

    CRM_Core_DAO::executeQuery($sql);
  }

  static function createTempTable(&$sqlColumns) {
    //creating a temporary table for the search result that need be exported
    $exportTempTable = CRM_Core_DAO::createTempTableName('civicrm_export', FALSE);

    // also create the sql table
    $sql = "DROP TABLE IF EXISTS {$exportTempTable}";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
CREATE TABLE {$exportTempTable} ( 
     id int unsigned NOT NULL AUTO_INCREMENT,
";
    $sql .= CRM_Utils_Array::implode(",\n", array_values($sqlColumns));

    $sql .= ",
  PRIMARY KEY ( id )
";
    // add indexes for street_address and household_name if present
    $addIndices = array('street_address', 'household_name', 'civicrm_primary_id');
    foreach ($addIndices as $index) {
      foreach($sqlColumns as $column){
        if($column == $index){
          $sql .= ",
  INDEX index_{$index}( $index )
";
          break;
        }
      }
    }

    $sql .= "
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
";

    CRM_Core_DAO::executeQuery($sql);
    return $exportTempTable;
  }

  static function mergeSameAddress($tableName, &$headerRows, &$sqlColumns, $drop = FALSE) {
    // find all the records that have the same street address BUT not in a household
    $sql = "
SELECT    r1.id as master_id,
          r1.last_name as last_name,
          r1.addressee as master_addressee,
          r2.id as copy_id,
          r2.last_name as copy_last_name,
          r2.addressee as copy_addressee
FROM      $tableName r1
LEFT JOIN $tableName r2 ON r1.street_address = r2.street_address
WHERE     ( r1.household_name IS NULL OR r1.household_name = '' )
AND       ( r2.household_name IS NULL OR r2.household_name = '' )
AND       ( r1.street_address != '' )
AND       r2.id > r1.id
ORDER BY  r1.id
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $mergeLastName = TRUE;
    $merge = $parents = $masterAddressee = array();
    while ($dao->fetch()) {
      $masterID = $dao->master_id;
      $copyID = $dao->copy_id;
      $lastName = $dao->last_name;
      $copyLastName = $dao->copy_last_name;

      // merge last names only when same
      if ($lastName != $copyLastName) {
        $mergeLastName = FALSE;
      }

      if (!isset($merge[$masterID])) {
        // check if this is an intermediate child
        // this happens if there are 3 or more matches a,b, c
        // the above query will return a, b / a, c / b, c
        // we might be doing a bit more work, but for now its ok, unless someone
        // knows how to fix the query above
        if (isset($parents[$masterID])) {
          $masterID = $parents[$masterID];
        }
        else {
          $merge[$masterID] = array('addressee' => $dao->master_addressee,
            'copy' => array(),
          );
        }
      }
      $parents[$copyID] = $masterID;
      $merge[$masterID]['copy'][$copyID] = $dao->copy_addressee;
    }

    $processed = array();
    foreach ($merge as $masterID => $values) {
      if (isset($processed[$masterID])) {
        CRM_Core_Error::fatal();
      }
      $processed[$masterID] = 1;
      if ($values['addressee']) {
        $masterAddressee = array(trim($values['addressee']));
      }
      $deleteIDs = array();
      foreach ($values['copy'] as $copyID => $copyAddressee) {
        if (isset($processed[$copyID])) {
          CRM_Core_Error::fatal();
        }
        $processed[$copyID] = 1;
        if ($copyAddressee) {
          $masterAddressee[] = trim($copyAddressee);
        }
        $deleteIDs[] = $copyID;
      }

      $addresseeString = CRM_Utils_Array::implode(', ', $masterAddressee);
      if ($mergeLastName) {
        $addresseeString = str_replace(" " . $lastName . ",", ",", $addresseeString);
      }

      $sql = "
UPDATE $tableName
SET    addressee = %1
WHERE  id = %2
";
      $params = array(1 => array($addresseeString, 'String'),
        2 => array($masterID, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);

      // delete all copies
      $deleteIDString = CRM_Utils_Array::implode(',', $deleteIDs);
      $sql = "
DELETE FROM $tableName
WHERE  id IN ( $deleteIDString )
";
      CRM_Core_DAO::executeQuery($sql);
    }

    // drop the table columns for last name
    // if added for addressee calculation
    if ($drop) {
      $dropQuery = "
ALTER TABLE $tableName
DROP  $drop";

      CRM_Core_DAO::executeQuery($dropQuery);

      $allKeys = array();
      foreach ($sqlColumns as $value) {
        $arr = explode(' ', $value);
        $allKeys[] = $arr[0];
      }

      if ($key = CRM_Utils_Array::key($drop, $allKeys)) {
        unset($headerRows[$key]);
      }
      unset($sqlColumns[$drop]);
    }
  }

  /**
   * Function to merge household record into the individual record
   * if exists
   *
   * @param string $exportTempTable temporary temp table that stores the records
   * @param array  $headerRows array of headers for the export file
   * @param array  $sqlColumns array of names of the table columns of the temp table
   * @param string $prefix name of the relationship type that is prefixed to the table columns
   */
  static function mergeSameHousehold($exportTempTable, &$headerRows, &$sqlColumns, $prefix) {
    $prefixColumn = $prefix . '_';
    $allKeys = array();
    foreach ($sqlColumns as $value) {
      $arr = explode(' ', $value);
      $allKeys[] = $arr[0];
    }
    $replaced = array();

    // name map of the non standard fields in header rows & sql columns
    $mappingFields = array(
      'civicrm_primary_id' => 'internal contact id',
      'url' => 'website',
      'contact_sub_type' => 'contact_subtype',
      'is_opt_out' => 'no_bulk_emails__user_opt_out_',
      'external_identifier' => 'external_identifier__match_to_contact_',
      'contact_source' => 'source_of_contact_data',
      'user_unique_id' => 'unique_id__openid_',
      'contact_source' => 'source_of_contact_data',
      'state_province' => 'state',
      'is_bulkmail' => 'use_for_bulk_mail',
      'im' => 'im_screen_name',
      'groups' => 'group_s_',
      'tags' => 'tag_s_',
      'notes' => 'note_s_',
      'provider_id' => 'im_service_provider',
      'phone_type_id' => 'phone_type',
    );

    //figure out which columns are to be replaced by which ones
    $index = 0;
    foreach ($sqlColumns as $dontCare) {
      $columnNames = $allKeys[$index];
      if ($rep = CRM_Utils_Array::value($columnNames, $mappingFields)) {
        $replaced[$columnNames] = CRM_Utils_String::munge($prefixColumn . $rep, '_', 64);
      }
      else {
        $householdColName = CRM_Utils_String::munge($prefixColumn . $columnNames, '_', 64);

        if (CRM_Utils_Array::value($householdColName, $sqlColumns)) {
          $replaced[$columnNames] = $householdColName;
        }
      }
      $index++;
    }
    $query = "UPDATE $exportTempTable SET ";

    foreach ($replaced as $from => $to) {
      $clause[] = "$from = $to ";
      unset($sqlColumns[$to]);
      if ($key = CRM_Utils_Array::key($to, $allKeys)) {
        unset($headerRows[$key]);
      }
    }
    $query .= CRM_Utils_Array::implode(",\n", $clause);
    $query .= " WHERE {$replaced['civicrm_primary_id']} != ''";

    CRM_Core_DAO::executeQuery($query);

    //drop the table columns that store redundant household info
    $dropQuery = "ALTER TABLE $exportTempTable ";
    foreach ($replaced as $householdColumns) {
      $dropClause[] = " DROP $householdColumns ";
    }
    $dropQuery .= CRM_Utils_Array::implode(",\n", $dropClause);

    CRM_Core_DAO::executeQuery($dropQuery);

    // also drop the temp table if exists
    $sql = "DROP TABLE IF EXISTS {$exportTempTable}_temp";
    CRM_Core_DAO::executeQuery($sql);

    // clean up duplicate records
    $query = "
CREATE TABLE {$exportTempTable}_temp SELECT *
FROM {$exportTempTable}
GROUP BY civicrm_primary_id ";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "DROP TABLE $exportTempTable";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "ALTER TABLE {$exportTempTable}_temp RENAME TO {$exportTempTable}";
    $dao = CRM_Core_DAO::executeQuery($query);
  }

  static function writeCSVFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode, $fileName) {

    $query = "SELECT * FROM $exportTempTable";
    $componentDetails = array();
    $writer = CRM_Core_Report_Excel::singleton('excel');
    $config = CRM_Core_Config::singleton();
    if ($config->decryptExcelOption == 0) {
      $writer->openToBrowser($fileName);
    }
    else {
      $filePath = $config->uploadDir.$fileName;
      $writer->openToFile($filePath);
    }
    $writer->addRow($headerRows);

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $row = array();
      foreach ($sqlColumns as $column => $sqlColumn) {
        $arr = explode(' ', $sqlColumn);
        $column = $arr[0];
        $fieldValue = $dao->$column;
        if (strstr($fieldValue, self::VALUE_SEPARATOR)){
          $fieldValue = trim($dao->$column, self::VALUE_SEPARATOR);
          $fieldValue = explode(self::VALUE_SEPARATOR, $fieldValue);
          $fieldValue = CRM_Utils_Array::implode(self::DISPLAY_SEPARATOR, $fieldValue);
        }
        if(strlen($fieldValue) < 15 && !preg_match('/[eE]/', $fieldValue)){
          $row[$column] = CRM_Utils_String::toNumber($fieldValue);
        }
        else{
          $row[$column] = $fieldValue;
        }
      }
      $writer->addRow($row);
    }
    $writer->close();
    if ($config->decryptExcelOption != 0) {
      CRM_Utils_File::encryptXlsxFile($filePath);
      header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename=' . $fileName);
      header('Pragma: no-cache');
      echo file_get_contents($filePath);
    }
  }

  static function writeBatchFromTable($exportTempTable, $headerRows, $sqlColumns, $exportMode, $fileName) {
    if (strstr($fileName, '.csv')) {
      // export csv. use Spout to add header row and BOM
      if (!is_file($fileName)){
        $writer = CRM_Core_Report_Excel::singleton('csv');
        $writer->openToFile($fileName);
        $writer->addRow($headerRows);
        $writer->close();
      }

      // Spout can't append line at the end of csv
      // use native instead
      $handle = fopen($fileName, 'a');
      $query = "SELECT * FROM $exportTempTable";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $row = array();
        foreach ($sqlColumns as $column => $sqlColumn) {
          $arr = explode(' ', $sqlColumn);
          $column = $arr[0];
          $fieldValue = $dao->$column;
          if (strstr($fieldValue, self::VALUE_SEPARATOR)){
            $fieldValue = trim($dao->$column, self::VALUE_SEPARATOR);
            $fieldValue = explode(self::VALUE_SEPARATOR, $fieldValue);
            $fieldValue = CRM_Utils_Array::implode(self::DISPLAY_SEPARATOR, $fieldValue);
          }
          if(strlen($fieldValue) < 15){
            $row[$column] = CRM_Utils_String::toNumber($fieldValue);
          }
          else{
            $row[$column] = $fieldValue;
          }
        }
        fputcsv($handle, $row);
      }
      fclose($handle);
    }
    else {
      $new = $fileName.'.new';
      $sleepCounter = 0;
      while(file_exists($new)) {
        $sleepCounter++;
        sleep(2);
        // timeout
        if ($sleepCounter > 90) {
          $query = "SELECT * FROM $exportTempTable";
          $dao = CRM_Core_DAO::executeQuery($query);
          $dao->fetch();
          $error = '';
          foreach ($sqlColumns as $column => $sqlColumn) {
            $error .= $column." => ".$dao->$column."\n";
          }
          CRM_Core_Error::fatal("Batch exporting error on previous exporting still writing to file (wait over 180 seconds). Rows from $error doesn't write correctly.");
          break;
        }
      }
      $writer = CRM_Core_Report_Excel::singleton('excel');
      $writer->openToFile($fileName.'.new');

      if (!is_file($fileName)){
        $writer->addRow($headerRows);
      }
      else{
        $tmpDir = rtrim(CRM_Utils_System::cmsDir('temp'), '/').'/';
        $reader = CRM_Core_Report_Excel::reader('excel');
        $reader->setTempFolder($tmpDir);
        $reader->open($fileName);
        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
          // Add sheets in the new file, as we read new sheets in the existing one
          if ($sheetIndex !== 1) {
            $writer->addNewSheetAndMakeItCurrent();
          }

          foreach ($sheet->getRowIterator() as $row) {
            // ... and copy each row into the new spreadsheet
            $writer->addRow($row);
          }
        }
      }

      $query = "SELECT * FROM $exportTempTable";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $row = array();
        foreach ($sqlColumns as $column => $sqlColumn) {
          $arr = explode(' ', $sqlColumn);
          $column = $arr[0];
          $fieldValue = $dao->$column;
          if (strstr($fieldValue, self::VALUE_SEPARATOR)){
            $fieldValue = trim($dao->$column, self::VALUE_SEPARATOR);
            $fieldValue = explode(self::VALUE_SEPARATOR, $fieldValue);
            $fieldValue = CRM_Utils_Array::implode(self::DISPLAY_SEPARATOR, $fieldValue);
          }
          if(strlen($fieldValue) < 15){
            $row[$column] = CRM_Utils_String::toNumber($fieldValue);
          }
          else{
            $row[$column] = $fieldValue;
          }
        }
        $writer->addRow($row);
      }
      $writer->close();
      if (is_file($fileName)){
        unlink($fileName);
      }
      rename($fileName.'.new', $fileName);
    }
  }

  /**
   * Function to manipulate header rows for relationship fields
   *
   */
  static function manipulateHeaderRows(&$headerRows, $contactRelationshipTypes) {
    foreach ($headerRows as & $header) {
      $split = explode('-', $header);
      if ($relationTypeName = CRM_Utils_Array::value($split[0], $contactRelationshipTypes)) {
        $split[0] = $relationTypeName;
        $header = CRM_Utils_Array::implode('-', $split);
      }
    }
  }

  function batchFinish() {
    global $civicrm_batch;
    $batchData = $civicrm_batch->data;
    $fileFullPath = $batchData['download']['file'];
    $fileType = $batchData['download']['header'][0];
    if (strstr($fileType, 'vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
      $config = CRM_Core_Config::singleton();
      if ($config->decryptExcelOption != 0) {
        CRM_Utils_File::encryptXlsxFile($fileFullPath);
      }
    }
  }

  public static function audit($exportMode, $fileName, $totalNumRow, $fields) {
    $serial = CRM_REQUEST_TIME;
    $flatten = array();
    CRM_Utils_Array::flatten($fields, $flatten);
    $data = array(
      'Type' => $exportMode,
      'File' => $fileName,
      'Data' => $totalNumRow,
      'Fields' => array_keys($flatten),
    );
    CRM_Core_BAO_Log::audit($serial, 'civicrm.export', json_encode($data));
  }
}

