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
 * This class provides the functionality to add contacts for
 * voter reservation.
 */
class CRM_Campaign_Form_Task_Reserve extends CRM_Campaign_Form_Task {

  /**
   * survet id`
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * interviewer id
   *
   * @var int
   */
  protected $_interviewerId;

  /**
   * survey details
   *
   * @var object
   */
  protected $_surveyDetails;

  protected $_surveyActivities;

  /**
   * number of voters
   *
   * @var int
   */
  protected $_numVoters;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    //get the survey id from user submitted values.
    $this->_surveyId = $this->get('surveyId');
    $this->_interviewerId = $this->get('interviewerId');
    if (!$this->_surveyId) {
      return CRM_Core_Error::statusBounce(ts("Could not find Survey Id."));
    }
    if (!$this->_interviewerId) {
      return CRM_Core_Error::statusBounce(ts("Missing Interviewer contact."));
    }
    if (!is_array($this->_contactIds) || empty($this->_contactIds)) {
      return CRM_Core_Error::statusBounce(ts("Could not find contacts for reservation."));
    }

    $params = ['id' => $this->_surveyId];
    CRM_Campaign_BAO_Survey::retrieve($params, $this->_surveyDetails);

    //get the survey activities.

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $statusIds = [];
    foreach (['Scheduled'] as $name) {
      if ($statusId = array_search($name, $activityStatus)) {
        $statusIds[] = $statusId;
      }
    }
    $this->_surveyActivities = CRM_Campaign_BAO_Survey::getSurveyActivities($this->_surveyId,
      $this->_interviewerId,
      $statusIds
    );
    $this->_numVoters = count($this->_surveyActivities);

    //validate the selected survey.
    $this->validateSurvey();
    $this->assign('surveyTitle', $this->_surveyDetails['title']);

    //append breadcrumb to survey dashboard.

    if (CRM_Campaign_BAO_Campaign::accessCampaignDashboard()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey(s)'), 'url' => $url]]);
    }

    //set the title.
    CRM_Utils_System::setTitle(ts('Reserve Respondents'));
  }

  function validateSurvey() {
    $errorMsg = NULL;
    $maxVoters = CRM_Utils_Array::value('max_number_of_contacts', $this->_surveyDetails);
    if ($maxVoters) {
      if ($maxVoters <= $this->_numVoters) {
        $errorMsg = ts('The maximum number of contacts is already reserved for this interviewer.');
      }
      elseif (count($this->_contactIds) > ($maxVoters - $this->_numVoters)) {
        $errorMsg = ts('You can reserve a maximum of %1 contact(s) at a time for this survey.',
          [1 => $maxVoters - $this->_numVoters]
        );
      }
    }
    $defaultNum = CRM_Utils_Array::value('default_number_of_contacts', $this->_surveyDetails);
    if (!$errorMsg && $defaultNum && (count($this->_contactIds) > $defaultNum)) {
      $errorMsg = ts('You can reserve a maximum of %1 contact(s) at a time for this survey.',
        [1 => $defaultNum]
      );
    }
    if ($errorMsg) {
      return CRM_Core_Error::statusBounce($errorMsg);
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $buttons = [['type' => 'done',
        'name' => ts('Reserve'),
        'subName' => 'reserve',
        'isDefault' => TRUE,
      ]];

    if (CRM_Core_Permission::check('manage campaign') ||
      CRM_Core_Permission::check('administer CiviCampaign') ||
      CRM_Core_Permission::check('interview campaign contacts')
    ) {
      $buttons[] = ['type' => 'next',
        'name' => ts('Reserve and Interview'),
        'subName' => 'reserveToInterview',
      ];
    }
    $buttons[] = ['type' => 'back',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $existingVoterIds = $campGrpContacts = $reservedVoterIds = [];
    foreach ($this->_surveyActivities as $actId => $actVals) {
      $voterId = $actVals['voter_id'];
      $existingVoterIds[$voterId] = $voterId;
    }

    $campaignId = CRM_Utils_Array::value('campaign_id', $this->_surveyDetails);



    $campGrps = CRM_Campaign_BAO_Campaign::getCampaignGroups($campaignId);
    foreach ($campGrps as $grpId => $grpVals) {
      $group = new CRM_Contact_DAO_Group();
      $group->id = $grpVals['entity_id'];
      $contacts = CRM_Contact_BAO_GroupContact::getGroupContacts($group);
      foreach ($contacts as $contact) {
        $campContacts[$contact->contact_id] = $contact->contact_id;
      }
    }

    //add reservation.

    $countVoters = 0;
    $maxVoters = $surveyDetails['max_number_of_contacts'];
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $statusHeld = array_search('Scheduled', $activityStatus);

    foreach ($this->_contactIds as $cid) {
      //apply filter for existing voters
      //and do check across campaign contacts.
      if (in_array($cid, $existingVoterIds) ||
        (!empty($campContacts) && !in_array($cid, $campContacts))
      ) {
        continue;
      }
      $subject = ts('%1', [1 => $this->_surveyDetails['title']]) . ' - ' . ts('Respondent Reservation');
      $session = &CRM_Core_Session::singleton();
      $activityParams = ['source_contact_id' => $session->get('userID'),
        'assignee_contact_id' => [$this->_interviewerId],
        'target_contact_id' => [$cid],
        'source_record_id' => $this->_surveyId,
        'activity_type_id' => $this->_surveyDetails['activity_type_id'],
        'subject' => $subject,
        'activity_date_time' => date('YmdHis'),
        'status_id' => $statusHeld,
        'skipRecentView' => 1,
      ];
      $activity = CRM_Activity_BAO_Activity::create($activityParams);
      if ($activity->id) {
        $countVoters++;
        $reservedVoterIds[$cid] = $cid;
      }
      if ($maxVoters && ($maxVoters <= ($this->_numVoters + $countVoters))) {
        break;
      }
    }

    $status = [];
    if ($countVoters > 0) {
      $status[] = ts('Reservation has been added for %1 Contact(s).', [1 => $countVoters]);
    }
    if (count($this->_contactIds) > $countVoters) {
      $status[] = ts('Reservation did not add for %1 Contact(s).',
        [1 => (count($this->_contactIds) - $countVoters)]
      );
    }
    if (!empty($status)) {
      CRM_Core_Session::setStatus(CRM_Utils_Array::implode('&nbsp;&nbsp;', $status));
    }

    //get ready to jump to voter interview form.
    $buttonName = $this->controller->getButtonName();
    if (!empty($reservedVoterIds) &&
      $buttonName == '_qf_Reserve_next_reserveToInterview'
    ) {
      $this->controller->set('surveyId', $this->_surveyId);
      $this->controller->set('contactIds', $reservedVoterIds);
      $this->controller->set('interviewerId', $this->_interviewerId);
      $this->controller->set('reserveToInterview', TRUE);
    }
  }
}

