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
class CRM_Campaign_BAO_Query {
  //since normal activity clause clause get collides.
  CONST civicrm_activity = 'civicrm_survey_activity', civicrm_activity_target = 'civicrm_survey_activity_target';

  /**
   * static field for all the campaign fields
   *
   * @var array
   * @static
   */
  static $_campaignFields = NULL;

  static $_applySurveyClause;

  /**
   * Function get the fields for campaign.
   *
   * @return array self::$_campaignFields  an associative array of campaign fields
   * @static
   */
  static function &getFields() {
    if (!isset(self::$_campaignFields)) {
      self::$_campaignFields = [];
    }

    return self::$_campaignFields;
  }

  /**
   * if survey, campaign are involved, add the specific fields.
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    self::$_applySurveyClause = FALSE;
    if (is_array($query->_params)) {
      foreach ($query->_params as $values) {
        list($name, $op, $value, $grouping, $wildcard) = $values;
        if ($name == 'campaign_survey_id') {
          self::$_applySurveyClause = TRUE;
          break;
        }
      }
    }
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    //all below tables are require to fetch  result.

    //1. get survey activity target table in.
    $query->_select['survey_activity_target_contact_id'] = 'civicrm_activity_target.target_contact_id as survey_activity_target_contact_id';
    $query->_select['survey_activity_target_id'] = 'civicrm_activity_target.id as survey_activity_target_id';
    $query->_element['survey_activity_target_id'] = 1;
    $query->_element['survey_activity_target_contact_id'] = 1;
    $query->_tables[self::civicrm_activity_target] = 1;
    $query->_whereTables[self::civicrm_activity_target] = 1;

    //2. get survey activity table in.
    $query->_select['survey_activity_id'] = 'civicrm_activity.id as survey_activity_id';
    $query->_element['survey_activity_id'] = 1;
    $query->_tables[self::civicrm_activity] = 1;
    $query->_whereTables[self::civicrm_activity] = 1;

    //3. get survey table.
    $query->_select['campaign_survey_id'] = 'civicrm_survey.id as campaign_survey_id';
    $query->_element['campaign_survey_id'] = 1;
    $query->_tables['civicrm_survey'] = 1;
    $query->_whereTables['civicrm_survey'] = 1;

    //4. get campaign table.
    $query->_select['campaign_id'] = 'civicrm_campaign.id as campaign_id';
    $query->_element['campaign_id'] = 1;
    $query->_tables['civicrm_campaign'] = 1;
    $query->_whereTables['civicrm_campaign'] = 1;
  }

  static function where(&$query) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
        $query->_useDistinct = TRUE;
      }

      self::whereClauseSingle($query->_params[$id], $query);
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    list($name, $op, $value, $grouping, $wildcard) = $values;

    $fields = [];
    $fields = self::getFields();
    if (!empty($value)) {
      $quoteValue = "\"$value\"";
    }

    switch ($name) {
      case 'campaign_survey_id':
        $aType = $value;
        $query->_qill[$grouping][] = ts('Survey - %1', [1 => CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'title')]);

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity.source_record_id',
          $op, $value, "Integer"
        );
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_survey.id',
          $op, $value, "Integer"
        );
        return;

      case 'survey_status_id':

        $activityStatus = CRM_Core_PseudoConstant::activityStatus();

        $query->_qill[$grouping][] = ts('Survey Status - %1', [1 => $activityStatus[$value]]);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity.status_id',
          $op, $value, "Integer"
        );
        return;

      case 'campaign_search_voter_for':
        if (in_array($value, ['release', 'interview'])) {
          $query->_where[$grouping][] = '(civicrm_activity.is_deleted = 0 OR civicrm_activity.is_deleted IS NULL)';
        }
        return;

      case 'survey_interviewer_id':
        $surveyInterviewerName = NULL;
        foreach ($query->_params as $paramValues) {
          if (CRM_Utils_Array::value(0, $paramValues) == 'survey_interviewer_name') {
            $surveyInterviewerName = CRM_Utils_Array::value(2, $paramValues);
            break;
          }
        }
        $query->_qill[$grouping][] = ts('Survey Interviewer - %1', [1 => $surveyInterviewerName]);
        $query->_tables['civicrm_activity_assignment'] = $query->_whereTables['civicrm_activity_assignment'] = 1;
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity_assignment.assignee_contact_id',
          $op, $value, "Integer"
        );
        return;
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return $from;
    }

    switch ($name) {
      case self::civicrm_activity_target:
        $from = " INNER JOIN civicrm_activity_target ON civicrm_activity_target.target_contact_id = contact_a.id ";
        break;

      case self::civicrm_activity:

        $surveyActivityTypes = CRM_Campaign_PseudoConstant::activityType();
        $surveyKeys = "(" . CRM_Utils_Array::implode(',', array_keys($surveyActivityTypes)) . ")";
        $from = " 
INNER JOIN civicrm_activity ON ( civicrm_activity.id = civicrm_activity_target.activity_id AND 
                                 civicrm_activity.activity_type_id IN $surveyKeys )
INNER JOIN civicrm_activity_assignment ON ( civicrm_activity.id = civicrm_activity_assignment.activity_id )
";
        break;

      case 'civicrm_survey':
        $from = " INNER JOIN civicrm_survey ON civicrm_survey.id = civicrm_activity.source_record_id ";
        break;

      case 'civicrm_campaign':
        $from = " $side JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_survey.campaign_id ";
        break;
    }

    return $from;
  }

  static function defaultReturnProperties($mode) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_CAMPAIGN) {
      $properties = [
        'contact_id' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'street_unit' => 1,
        'street_name' => 1,
        'street_number' => 1,
        'street_address' => 1,
        'city' => 1,
        'postal_code' => 1,
        'state_province' => 1,
        'country' => 1,
        'email' => 1,
        'phone' => 1,
        'survey_activity_target_id' => 1,
        'survey_activity_id' => 1,
        'survey_status_id' => 1,
        'campaign_survey_id' => 1,
        'campaign_id' => 1,
        'survey_activity_target_contact_id' => 1,
      ];
    }

    return $properties;
  }

  static function tableNames(&$tables) {}
  static function searchAction(&$row, $id) {}

  static function info(&$tables) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    $weight = end($tables);
    $tables[self::civicrm_activity_target] = ++$weight;
    $tables[self::civicrm_activity] = ++$weight;
    $tables['civicrm_survey'] = ++$weight;
    $tables['civicrm_campaign'] = ++$weight;
  }

  /**
   * add all the elements shared between,
   * normal voter search and voter listing (GOTV form)
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');
    $className = CRM_Utils_System::getClassName($form);

    $form->add('text', 'sort_name', ts('Contact Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );
    $form->add('text', 'street_name', ts('Street Name'), $attributes['street_name']);
    $form->add('text', 'street_number', ts('Street Number'), $attributes['street_number']);
    $form->add('text', 'street_unit', ts('Street Unit'), $attributes['street_unit']);
    $form->add('text', 'street_address', ts('Street Address'), $attributes['street_address']);
    $form->add('text', 'city', ts('City'), $attributes['city']);

    $showInterviewer = FALSE;
    if (CRM_Core_Permission::check('administer CiviCampaign')) {
      $showInterviewer = TRUE;
    }
    $form->assign('showInterviewer', $showInterviewer);

    if ($showInterviewer ||
      $className == 'CRM_Campaign_Form_Gotv'
    ) {
      //autocomplete url
      $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
        'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&reset=1',
        FALSE, NULL, FALSE
      );

      $form->assign('dataUrl', $dataUrl);
      $form->add('text', 'survey_interviewer_name', ts('Select Interviewer'));
      $form->add('hidden', 'survey_interviewer_id', '', ['id' => 'survey_interviewer_id']);

      $userId = NULL;
      if (isset($form->_interviewerId) && $form->_interviewerId) {
        $userId = $form->_interviewerId;
      }
      if (!$userId) {
        $session = CRM_Core_Session::singleton();
        $userId = $session->get('userID');
      }
      if ($userId) {
        $defaults = [];
        $defaults['survey_interviewer_id'] = $userId;
        $defaults['survey_interviewer_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $userId,
          'sort_name',
          'id'
        );
        $form->setDefaults($defaults);
      }
    }

    //build ward and precinct custom fields.
    $query = '
    SELECT  fld.id, fld.label 
      FROM  civicrm_custom_field fld 
INNER JOIN  civicrm_custom_group grp on fld.custom_group_id = grp.id
     WHERE  grp.name = %1';
    $dao = CRM_Core_DAO::executeQuery($query, [1 => ['Voter_Info', 'String']]);
    $customSearchFields = [];

    while ($dao->fetch()) {
      foreach (['ward', 'precinct'] as $name) {
        if (stripos($name, $dao->label) !== FALSE) {
          $fieldId = $dao->id;
          $fieldName = 'custom_' . $dao->id;
          $customSearchFields[$name] = $fieldName;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $fieldName, $fieldId, FALSE, FALSE);
          break;
        }
      }
    }
    $form->assign('customSearchFields', $customSearchFields);

    $surveys = CRM_Campaign_BAO_Survey::getSurveyList();

    if (empty($surveys) &&
      ($className == 'CRM_Campaign_Form_Search')
    ) {
      return CRM_Core_Error::statusBounce(ts('Could not find survey for %1 respondents.',
          [1 => $form->get('op')]
        ),
        CRM_Utils_System::url('civicrm/survey/add',
          'reset=1&action=add'
        )
      );
    }

    $form->add('select', 'campaign_survey_id', ts('Survey'), $surveys, TRUE);
  }

  /*
     * Retrieve all valid voter ids,
     * and build respective clause to restrict search.
     *
     * @param  array  $criteria an array 
     * @return $voterClause as a string  
     * @static
     */
  static function voterClause($params) {
    $voterClause = NULL;
    if (!is_array($params) || empty($params)) {
      return $voterClause;
    }
    $surveyId = CRM_Utils_Array::value('campaign_survey_id', $params);
    $surveyId = CRM_Utils_Type::escape($surveyId, 'Integer');
    $interviewerId = CRM_Utils_Array::value('survey_interviewer_id', $params);
    $searchVoterFor = CRM_Utils_Array::value('campaign_search_voter_for', $params);

    //get the survey activities.

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $status = ['Scheduled'];
    if ($searchVoterFor == 'reserve') {
      $status[] = 'Completed';
    }

    $completedStatusId = NULL;
    foreach ($status as $name) {
      if ($statusId = array_search($name, $activityStatus)) {
        $statusIds[] = $statusId;
        if ($name == 'Completed') {
          $completedStatusId = $statusId;
        }
      }
    }


    $voterActValues = CRM_Campaign_BAO_Survey::getSurveyVoterInfo($surveyId, NULL, $statusIds);

    if (!empty($voterActValues)) {
      $operator = 'IN';
      $voterIds = array_keys($voterActValues);
      if ($searchVoterFor == 'reserve') {
        $operator = 'NOT IN';
        //filter out recontact survey contacts.
        $recontactInterval = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
          $surveyId, 'recontact_interval'
        );
        $recontactInterval = unserialize($recontactInterval);
        if ($surveyId &&
          is_array($recontactInterval) &&
          !empty($recontactInterval)
        ) {
          $voterIds = [];
          foreach ($voterActValues as $values) {
            $numOfDays = CRM_Utils_Array::value($values['result'], $recontactInterval);
            if ($numOfDays &&
              $values['status_id'] == $completedStatusId
            ) {
              $recontactIntSeconds = $numOfDays * 24 * 3600;
              $actDateTimeSeconds = CRM_Utils_Date::unixTime($values['activity_date_time']);
              $totalSeconds = $recontactIntSeconds + $actDateTimeSeconds;
              //don't consider completed survey activity
              //unless it fulfill recontact interval criteria.
              if ($totalSeconds <= time()) {
                continue;
              }
            }
            $voterIds[$values['voter_id']] = $values['voter_id'];
          }
        }
      }

      if (!empty($voterIds)) {
        $voterClause = "( contact_a.id $operator ( " . CRM_Utils_Array::implode(', ', $voterIds) . ' ) )';
      }
    }

    return $voterClause;
  }
}

