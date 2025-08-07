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
 * This class provides the functionality to record voter's interview.
 */
class CRM_Campaign_Form_Task_Interview extends CRM_Campaign_Form_Task {

  public $_reserveToInterview;
  public $_surveyId;
  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * variable to store redirect path
   *
   */
  private $_userContext;

  private $_groupTree;

  private $_surveyFields;

  private $_surveyTypeId;

  private $_interviewerId;

  private $_ufGroupId;

  private $_surveyActivityIds;

  private $_votingTab = FALSE;

  private $_surveyValues;

  private $_resultOptions;

  private $_allowAjaxReleaseButton;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_votingTab = $this->get('votingTab');
    $this->_reserveToInterview = $this->get('reserveToInterview');
    if ($this->_reserveToInterview || $this->_votingTab) {
      //user came from voting tab / reserve form.
      foreach (['surveyId', 'contactIds', 'interviewerId'] as $fld) {
        $this->{"_$fld"} = $this->get($fld);
      }
      //get the target voter ids.
      if ($this->_votingTab) {
        $this->getVoterIds();
      }
    }
    else {
      parent::preProcess();
      //get the survey id from user submitted values.
      $this->_surveyId = CRM_Utils_Array::value('campaign_survey_id', $this->get('formValues'));
      $this->_interviewerId = CRM_Utils_Array::value('survey_interviewer_id', $this->get('formValues'));
    }

    //get the contact read only fields to display.

    $readOnlyFields = array_merge(['contact_type' => '',
        'sort_name' => ts('Name'),
      ],
      CRM_Core_BAO_Preferences::valueOptions('contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );

    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $returnProperties['contact_sub_type'] = TRUE;

    //get the profile id.

    $ufJoinParams = ['entity_id' => $this->_surveyId,
      'entity_table' => 'civicrm_survey',
      'module' => 'CiviCampaign',
    ];
    $this->_ufGroupId = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams);

    //validate all voters for required activity.
    //get the survey activities for given voters.

    $this->_surveyActivityIds = CRM_Campaign_BAO_Survey::voterActivityDetails($this->_surveyId,
      $this->_contactIds,
      $this->_interviewerId
    );

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $scheduledStatusId = array_search('Scheduled', $activityStatus);

    $activityIds = [];
    foreach ($this->_contactIds as $key => $voterId) {
      $actVals = CRM_Utils_Array::value($voterId, $this->_surveyActivityIds);
      $statusId = CRM_Utils_Array::value('status_id', $actVals);
      $activityId = CRM_Utils_Array::value('activity_id', $actVals);
      if ($activityId &&
        $statusId &&
        $scheduledStatusId == $statusId
      ) {
        $activityIds["activity_id_{$voterId}"] = $activityId;
      }
      else {
        unset($this->_contactIds[$key]);
      }
    }

    //retrieve the contact details.
    $voterDetails = CRM_Campaign_BAO_Survey::voterDetails($this->_contactIds, $returnProperties);

    $this->_allowAjaxReleaseButton = FALSE;
    if ($this->_votingTab &&
      (CRM_Core_Permission::check('manage campaign') ||
        CRM_Core_Permission::check('administer CiviCampaign') ||
        CRM_Core_Permission::check('release campaign contacts')
      )
    ) {
      $this->_allowAjaxReleaseButton = TRUE;
    }

    $this->assign('votingTab', $this->_votingTab);
    $this->assign('componentIds', $this->_contactIds);
    $this->assign('voterDetails', $voterDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
    $this->assign('interviewerId', $this->_interviewerId);
    $this->assign('surveyActivityIds', json_encode($activityIds));
    $this->assign('allowAjaxReleaseButton', $this->_allowAjaxReleaseButton);

    //get the survey values.
    $this->_surveyValues = $this->get('surveyValues');
    if (!is_array($this->_surveyValues)) {
      $this->_surveyValues = [];
      if ($this->_surveyId) {

        $surveyParams = ['id' => $this->_surveyId];
        CRM_Campaign_BAO_Survey::retrieve($surveyParams, $this->_surveyValues);
      }
      $this->set('surveyValues', $this->_surveyValues);
    }
    $this->assign('surveyValues', $this->_surveyValues);

    //get the survey result options.
    $this->_resultOptions = $this->get('resultOptions');
    if (!is_array($this->_resultOptions)) {
      $this->_resultOptions = [];
      if ($resultOptionId = CRM_Utils_Array::value('result_id', $this->_surveyValues)) {

        $this->_resultOptions = CRM_Core_OptionGroup::valuesByID($resultOptionId);
      }
      $this->set('resultOptions', $this->_resultOptions);
    }

    //validate the required ids.
    $this->validateIds();

    //append breadcrumb to survey dashboard.

    if (CRM_Campaign_BAO_Campaign::accessCampaignDashboard()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey(s)'), 'url' => $url]]);
    }

    //set the title.

    $activityTypes = CRM_Core_PseudoConstant::activityType(FALSE, TRUE, FALSE, 'label', TRUE);
    $this->_surveyTypeId = CRM_Utils_Array::value('activity_type_id', $this->_surveyValues);
    CRM_Utils_System::setTitle(ts('Record %1 Responses', [1 => $activityTypes[$this->_surveyTypeId]]));
  }

  function validateIds() {
    $required = ['surveyId' => ts('Could not find Survey.'),
      'interviewerId' => ts('Could not find Interviewer.'),
      'contactIds' => ts('No respondents are currently reserved for you to interview.'),
      'resultOptions' => ts('Oops. It looks like there is no response option configured.'),
    ];

    $errorMessages = [];
    foreach ($required as $fld => $msg) {
      if (empty($this->{"_$fld"})) {
        if (!$this->_votingTab) {
          return CRM_Core_Error::statusBounce($msg);
          break;
        }
        $errorMessages[] = $msg;
      }
    }

    $this->assign('errorMessages', empty($errorMessages) ? FALSE : $errorMessages);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $this->assign('surveyTypeId', $this->_surveyTypeId);

    //pickup the uf fields.
    $this->_surveyFields = [];
    if ($this->_ufGroupId) {

      $this->_surveyFields = CRM_Core_BAO_UFGroup::getFields($this->_ufGroupId,
        FALSE, CRM_Core_Action::VIEW
      );
    }

    //build all fields.
    $exposedSurveyFields = [];
    foreach ($this->_contactIds as $contactId) {
      //build the profile fields.
      foreach ($this->_surveyFields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          // allow custom fields from profile which are having
          // the activty type same of that selected survey.
          $valueType = CRM_Utils_Array::value('extends_entity_column_value', $customValue);
          if (!$valueType || ($valueType == $this->_surveyTypeId)) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contactId);
            $exposedSurveyFields[$name] = $field;
          }
        }
      }

      //build the result field.
      if (!empty($this->_resultOptions)) {
        $this->add('select', "field[$contactId][result]", ts('Result'),
          ['' => ts('- select -')] +
          array_combine($this->_resultOptions, $this->_resultOptions)
        );
      }

      $this->add('text', "field[{$contactId}][note]", ts('Note'));

      //need to keep control for release/reserve.
      if ($this->_allowAjaxReleaseButton) {
        $this->addElement('hidden',
          "field[{$contactId}][is_release_or_reserve]", 0,
          ['id' => "field_{$contactId}_is_release_or_reserve"]
        );
      }
    }
    $this->assign('surveyFields', empty($exposedSurveyFields) ? FALSE : $exposedSurveyFields);

    //no need to get qf buttons.
    if ($this->_votingTab) {
      return;
    }

    $buttons = [['type' => 'cancel',
        'name' => ts('Done'),
        'subName' => 'interview',
        'isDefault' => TRUE,
      ]];

    $manageCampaign = CRM_Core_Permission::check('manage campaign');
    $adminCampaign = CRM_Core_Permission::check('administer CiviCampaign');
    if ($manageCampaign ||
      $adminCampaign ||
      CRM_Core_Permission::check('release campaign contacts')
    ) {
      $buttons[] = ['type' => 'next',
        'name' => ts('Release Respondents >>'),
        'subName' => 'interviewToRelease',
      ];
    }
    if ($manageCampaign ||
      $adminCampaign ||
      CRM_Core_Permission::check('reserve campaign contacts')
    ) {
      $buttons[] = ['type' => 'done',
        'name' => ts('Reserve More Respondents >>'),
        'subName' => 'interviewToReserve',
      ];
    }

    $this->addButtons($buttons);
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    return $defaults = [];
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == '_qf_Interview_done_interviewToReserve') {
      //hey its time to stop cycle.
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/survey/search', 'reset=1&op=reserve'));
    }
    elseif ($buttonName == '_qf_Interview_next_interviewToRelease') {
      //get ready to jump to release form.
      foreach (['surveyId', 'contactIds', 'interviewerId'] as $fld) {
        $this->controller->set($fld, $this->{"_$fld"});
      }
      $this->controller->set('interviewToRelease', TRUE);
    }

    // vote is done through ajax
    return;

    $params = $this->controller->exportValues($this->_name);

    //process survey.

    foreach ($params['field'] as $voterId => & $values) {
      $values['voter_id'] = $voterId;
      $values['interviewer_id'] = $this->_interviewerId;
      $values['activity_type_id'] = $this->_surveyTypeId;
      $values['activity_id'] = CRM_Utils_Array::value('activity_id', $this->_surveyActivityIds[$voterId]);
      self::registerInterview($values);
    }
  }

  static function registerInterview($params) {
    $activityId = CRM_Utils_Array::value('activity_id', $params);
    $surveyTypeId = CRM_Utils_Array::value('activity_type_id', $params);
    if (!is_array($params) || !$surveyTypeId || !$activityId) {
      return FALSE;
    }

    static $surveyFields;
    if (!is_array($surveyFields)) {

      $surveyFields = CRM_Core_BAO_CustomField::getFields('Activity',
        FALSE,
        FALSE,
        $surveyTypeId,
        NULL,
        FALSE,
        TRUE
      );
    }

    static $statusId;
    if (!$statusId) {

      $statusId = array_search('Completed', CRM_Core_PseudoConstant::activityStatus('name'));
    }

    //format custom fields.
    $customParams = CRM_Core_BAO_CustomField::postProcess($params,
      $surveyFields,
      $activityId,
      'Activity'
    );

    CRM_Core_BAO_CustomValueTable::store($customParams, 'civicrm_activity', $activityId);

    //update activity record.

    $activity = new CRM_Activity_DAO_Activity();
    $activity->id = $activityId;

    $activity->selectAdd();
    $activity->selectAdd('activity_date_time, status_id, result, subject');
    $activity->find(TRUE);
    $activity->activity_date_time = date('Ymdhis');
    $activity->status_id = $statusId;
    if (CRM_Utils_Array::value('details', $params)) {
      $activity->details = $params['details'];
    }
    if ($result = CRM_Utils_Array::value('result', $params)) {
      $activity->result = $result;
    }

    $subject = '';
    $surveyTitle = CRM_Utils_Array::value('surveyTitle', $params);
    if ($surveyTitle) {
      $subject = ts('%1', [1 => $surveyTitle]);
      $subject .= ' - ';
    }
    $subject .= ts('Respondent Interview');

    $activity->subject = $subject;
    $activity->save();
    $activity->free();

    return $activityId;
  }

  function getVoterIds() {
    if (!$this->_interviewerId) {
      $session = CRM_Core_Session::singleton();
      $this->_interviewerId = $session->get('userID');
    }
    if (!$this->_surveyId) {
      // use default survey id

      $dao = new CRM_Campaign_DAO_Survey();
      $dao->is_active = 1;
      $dao->is_default = 1;
      $dao->find(TRUE);
      $this->_surveyId = $dao->id;
    }

    $this->_contactIds = $this->get('contactIds');
    if (!is_array($this->_contactIds)) {
      //get the survey activities.

      $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
      $statusIds = [];
      if ($statusId = array_search('Scheduled', $activityStatus)) {
        $statusIds[] = $statusId;
      }

      $surveyActivities = CRM_Campaign_BAO_Survey::getSurveyVoterInfo($this->_surveyId,
        $this->_interviewerId,
        $statusIds
      );
      $this->_contactIds = [];
      foreach ($surveyActivities as $val) $this->_contactIds[$val['voter_id']] = $val['voter_id'];
      $this->set('contactIds', $this->_contactIds);
    }
  }
}

