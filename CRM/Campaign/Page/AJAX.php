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
 *
 */



/**
 * This class contains all campaign related functions that are called using AJAX (jQuery)
 */
class CRM_Campaign_Page_AJAX {

  static function registerInterview() {
    $voterId = CRM_Utils_Array::value('voter_id', $_POST);
    $activityId = CRM_Utils_Array::value('activity_id', $_POST);
    $params = ['voter_id' => $voterId,
      'activity_id' => $activityId,
      'details' => CRM_Utils_Array::value('note', $_POST),
      'result' => CRM_Utils_Array::value('result', $_POST),
      'interviewer_id' => CRM_Utils_Array::value('interviewer_id', $_POST),
      'activity_type_id' => CRM_Utils_Array::value('activity_type_id', $_POST),
      'surveyTitle' => CRM_Utils_Array::value('surveyTitle', $_POST),
    ];

    $customKey = "field_{$voterId}_custom";
    foreach ($_POST as $key => $value) {
      if (strpos($key, $customKey) !== FALSE) {
        $customFieldKey = str_replace(str_replace(substr($customKey, -6), '', $customKey), '', $key);
        $params[$customFieldKey] = $value;
      }
    }

    if (isset($_POST['field']) &&
      CRM_Utils_Array::value($voterId, $_POST['field'])
    ) {
      foreach ($_POST['field'][$voterId] as $fieldKey => $value) {
        if (!empty($value)) {
          $params[$fieldKey] = $value;
        }
      }
    }


    $activityId = CRM_Campaign_Form_Task_Interview::registerInterview($params);
    $result = ['status' => ($activityId) ? 'success' : 'fail',
      'voter_id' => $voterId,
      'activity_id' => $params['interviewer_id'],
    ];


    echo json_encode($result);

    CRM_Utils_System::civiExit();
  }

  static function loadOptionGroupDetails() {

    $id = CRM_Utils_Array::value('option_group_id', $_POST);
    $status = 'fail';
    $opValues = [];

    if ($id) {

      $groupParams['id'] = $id;
      CRM_Core_OptionValue::getValues($groupParams, $opValues);
    }

    $surveyId = CRM_Utils_Array::value('survey_id', $_POST);
    if ($surveyId) {

      $survey = new CRM_Campaign_DAO_Survey();
      $survey->id = $surveyId;
      $survey->result_id = $id;
      if ($survey->find(TRUE)) {
        if ($survey->recontact_interval) {
          $recontactInterval = unserialize($survey->recontact_interval);
          foreach ($opValues as $opValId => $opVal) {
            if (CRM_Utils_Array::value($opVal['label'], $recontactInterval)) {
              $opValues[$opValId]['interval'] = $recontactInterval[$opVal['label']];
            }
          }
        }
      }
    }

    if (!empty($opValues)) {
      $status = 'success';
    }

    $result = ['status' => $status,
      'result' => $opValues,
    ];

    echo json_encode($result);
    CRM_Utils_System::civiExit();
  }

  function voterList() {
    $searchParams = ['city',
      'sort_name',
      'street_unit',
      'street_name',
      'street_number',
      'street_address',
      'survey_interviewer_id',
      'campaign_survey_id',
      'campaign_search_voter_for',
    ];

    $params = $searchRows = [];
    foreach ($searchParams as $param) {
      if (CRM_Utils_Array::value($param, $_POST)) {
        $params[$param] = $_POST[$param];
      }
    }

    $voterClauseParams = [];
    foreach (['campaign_survey_id', 'survey_interviewer_id', 'campaign_search_voter_for'] as $fld) {
      $voterClauseParams[$fld] = CRM_Utils_Array::value($fld, $params);
    }

    $interviewerId = $surveyTypeId = $surveyId = NULL;
    $searchVoterFor = $params['campaign_search_voter_for'];
    if ($searchVoterFor == 'reserve') {
      if (CRM_Utils_Array::value('campaign_survey_id', $params)) {

        $survey = new CRM_Campaign_DAO_Survey();
        $survey->id = $surveyId = $params['campaign_survey_id'];
        $survey->selectAdd('campaign_id, activity_type_id');
        $survey->find(TRUE);
        $campaignId = $survey->campaign_id;
        $surveyTypeId = $survey->activity_type_id;
        if ($campaignId) {

          $campaignGroups = CRM_Campaign_BAO_Campaign::getCampaignGroups($campaignId);
          foreach ($campaignGroups as $id => $group) {
            if ($group['entity_table'] == 'civicrm_group') {
              $params['group'][$group['entity_id']] = 1;
            }
          }
        }
        unset($params['campaign_survey_id']);
      }
      unset($params['survey_interviewer_id']);
    }
    else {
      //get the survey status in where clause.

      $scheduledStatusId = array_search('Scheduled', CRM_Core_PseudoConstant::activityStatus('name'));
      if ($scheduledStatusId) {
        $params['survey_status_id'] = $scheduledStatusId;
      }
      //BAO/Query knows reserve/release/interview processes.
      if ($params['campaign_search_voter_for'] == 'gotv') {
        $params['campaign_search_voter_for'] = 'release';
      }
    }

    $selectorCols = ['sort_name',
      'street_address',
      'street_name',
      'street_number',
      'street_unit',
    ];

    // get the data table params.
    $dataTableParams = ['sEcho' => ['name' => 'sEcho',
        'type' => 'Integer',
        'default' => 0,
      ],
      'offset' => ['name' => 'iDisplayStart',
        'type' => 'Integer',
        'default' => 0,
      ],
      'rowCount' => ['name' => 'iDisplayLength',
        'type' => 'Integer',
        'default' => 25,
      ],
      'sort' => ['name' => 'iSortCol_0',
        'type' => 'Integer',
        'default' => 'sort_name',
      ],
      'sortOrder' => ['name' => 'sSortDir_0',
        'type' => 'String',
        'default' => 'asc',
      ],
    ];
    foreach ($dataTableParams as $pName => $pValues) {
      $$pName = $pValues['default'];
      if (CRM_Utils_Array::value($pValues['name'], $_POST)) {
        $$pName = CRM_Utils_Type::escape($_POST[$pValues['name']], $pValues['type']);
        if ($pName == 'sort')$$pName = $selectorCols[$$pName];
      }
    }


    $queryParams = CRM_Contact_BAO_Query::convertFormValues($params);
    $query = new CRM_Contact_BAO_Query($queryParams,
      NULL, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CAMPAIGN
    );

    //get the voter clause to restrict and validate search.

    $voterClause = CRM_Campaign_BAO_Query::voterClause($voterClauseParams);

    $searchCount = $query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $voterClause
    );

    $iTotal = $searchCount;

    $selectorCols = ['contact_type', 'sort_name', 'street_address',
      'street_name', 'street_number', 'street_unit',
    ];

    $extraVoterColName = 'is_interview_conducted';
    if ($params['campaign_search_voter_for'] = 'reserve') {
      $extraVoterColName = 'reserve_voter';
    }

    if ($searchCount > 0) {
      if ($searchCount < $offset) {
        $offset = 0;
      }


      $config = CRM_Core_Config::singleton();

      // get the result of the search
      $result = $query->searchQuery($offset, $rowCount, $sort,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE,
        $voterClause,
        $sortOrder
      );
      while ($result->fetch()) {
        $contactID = $result->contact_id;
        $typeImage = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
          $result->contact_sub_type : $result->contact_type,
          FALSE,
          $result->contact_id
        );

        $searchRows[$contactID] = ['id' => $contactID];
        foreach ($selectorCols as $col) {
          $val = $result->$col;
          if ($col == 'contact_type') {
            $val = $typeImage;
          }
          $searchRows[$contactID][$col] = $val;
        }
        if ($searchVoterFor == 'reserve') {
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $contactID . ']" name="survey_activity[' . $contactID . ']" value=' . $contactID . ' onClick="processVoterData( this, \'reserve\' );" />';
          $msg = ts('Respondent Reserved.');
          $voterExtraColHtml .= "&nbsp;<span id='success_msg_{$contactID}' class='ok' style='display:none;'>$msg</span>";
        }
        elseif ($searchVoterFor == 'gotv') {
          $surveyActId = $result->survey_activity_id;
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $surveyActId . ']" name="survey_activity[' . $surveyActId . ']" value=' . $surveyActId . ' onClick="processVoterData( this, \'gotv\' );" />';
          $msg = ts('Vote Recorded');
          $voterExtraColHtml .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id='success_msg_{$surveyActId}' class='ok' style='display:none;'>$msg</span>";
        }
        else {
          $surveyActId = $result->survey_activity_id;
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $surveyActId . ']" name="survey_activity[' . $surveyActId . ']" value=' . $surveyActId . ' onClick="processVoterData( this, \'release\' );" />';
          $msg = ts('Vote Recorded');
          $voterExtraColHtml .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id='success_msg_{$surveyActId}' class='ok' style='display:none;'>$msg</span>";
        }
        $searchRows[$contactID][$extraVoterColName] = $voterExtraColHtml;
      }
    }


    $selectorElements = array_merge($selectorCols, [$extraVoterColName]);

    $iFilteredTotal = $iTotal;

    echo CRM_Utils_JSON::encodeDataTableSelector($searchRows, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  function processVoterData() {
    $status = NULL;
    $operation = CRM_Utils_Type::escape($_POST['operation'], 'String');
    if ($operation == 'release') {

      $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
      $isDelete = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['isDelete'], 'String'));
      if ($activityId &&
        CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
          $activityId,
          'is_deleted',
          $isDelete
        )
      ) {
        $status = 'success';
      }
    }
    elseif ($operation == 'reserve') {
      $activityId = NULL;
      $createActivity = TRUE;
      if (CRM_Utils_Array::value('activity_id', $_POST)) {
        $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
        if ($activityId) {
          $createActivity = FALSE;
          $activityUpdated = CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
            $activityId,
            'is_deleted',
            0
          );
          if ($activityUpdated) {
            $status = 'success';
          }
        }
      }
      if ($createActivity) {
        $ids = ['source_record_id',
          'source_contact_id',
          'target_contact_id',
          'assignee_contact_id',
        ];
        $activityParams = [];
        foreach ($ids as $id) {
          $val = CRM_Utils_Array::value($id, $_POST);
          if (!$val) {
            $createActivity = FALSE;
            break;
          }
          $activityParams[$id] = CRM_Utils_Type::escape($val, 'Integer');
        }
      }
      if ($createActivity) {
        $isReserved = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['isReserved'], 'String'));

        $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
        $scheduledStatusId = array_search('Scheduled', $activityStatus);
        if ($isReserved) {
          $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
            $activityParams['source_record_id'],
            'activity_type_id'
          );
          $surveytitle = CRM_Utils_Array::value('surveyTitle', $_POST);
          if (!$surveytitle) {
            $surveytitle = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
              $activityParams['source_record_id'], 'title'
            );
          }
          $subject = ts('%1', [1 => $surveytitle]) . ' - ' . ts('Respondent Reservation');
          $activityParams['subject'] = $subject;
          $activityParams['status_id'] = $scheduledStatusId;
          $activityParams['skipRecentView'] = 1;
          $activityParams['activity_date_time'] = date('YmdHis');
          $activityParams['activity_type_id'] = $activityTypeId;


          $activity = CRM_Activity_BAO_Activity::create($activityParams);
          if ($activity->id) {
            $status = 'success';
          }
        }
        else {
          //delete reserved activity for given voter.

          $voterIds = [$activityParams['target_contact_id']];
          $activities = CRM_Campaign_BAO_Survey::voterActivityDetails($activityParams['source_record_id'],
            $voterIds,
            $activityParams['source_contact_id'],
            [$scheduledStatusId]
          );
          foreach ($activities as $voterId => $values) {
            $activityId = CRM_Utils_Array::value('activity_id', $values);
            if ($activityId && ($values['status_id'] == $scheduledStatusId)) {
              CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
                $activityId,
                'is_deleted',
                TRUE
              );
              $status = 'success';
              break;
            }
          }
        }
      }
    }
    elseif ($operation == 'gotv') {

      $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
      $hasVoted = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['hasVoted'], 'String'));
      if ($activityId) {
        if ($hasVoted) {
          $statusValue = 2;
        }
        else {
          $statusValue = 1;
        }
        CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
          $activityId,
          'status_id',
          $statusValue
        );
        $status = 'success';
      }
    }

    echo json_encode(['status' => $status]);
    CRM_Utils_System::civiExit();
  }
}

