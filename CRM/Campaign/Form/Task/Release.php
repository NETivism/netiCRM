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
class CRM_Campaign_Form_Task_Release extends CRM_Campaign_Form_Task {

  public $_interviewToRelease;
  /**
   * survet id
   *
   * @var int
   */
  protected $_surveyId;

  /**
   * number of voters
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
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_interviewToRelease = $this->get('interviewToRelease');
    if ($this->_interviewToRelease) {
      //user came from interview form.
      foreach (['surveyId', 'contactIds', 'interviewerId'] as $fld) {
        $this->{"_$fld"} = $this->get($fld);
      }

      if (!empty($this->_contactIds)) {
        $this->assign('totalSelectedContacts', count($this->_contactIds));
      }
    }
    else {
      parent::preProcess();
      //get the survey id from user submitted values.
      $this->_surveyId = CRM_Utils_Array::value('campaign_survey_id', $this->get('formValues'));
      $this->_interviewerId = CRM_Utils_Array::value('survey_interviewer_id', $this->get('formValues'));
    }

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $surveyActType = CRM_Campaign_BAO_Survey::getSurveyActivityType();

    if (!$this->_surveyId) {
      return CRM_Core_Error::statusBounce(ts("Please search with 'Survey', to apply this action."));
    }
    if (!$this->_interviewerId) {
      return CRM_Core_Error::statusBounce(ts('Missing Interviewer contact.'));
    }
    if (!is_array($this->_contactIds) || empty($this->_contactIds)) {
      return CRM_Core_Error::statusBounce(ts('Could not find respondents to release.'));
    }

    $surveyDetails = [];
    $params = ['id' => $this->_surveyId];
    $this->_surveyDetails = CRM_Campaign_BAO_Survey::retrieve($params, $surveyDetails);


    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $statusIds = [];
    foreach (['Scheduled'] as $name) {
      if ($statusId = array_search($name, $activityStatus)) {
        $statusIds[] = $statusId;
      }
    }
    //fetch the target survey activities.
    $this->_surveyActivities = CRM_Campaign_BAO_Survey::voterActivityDetails($this->_surveyId,
      $this->_contactIds,
      $this->_interviewerId,
      $statusIds
    );
    if (count($this->_surveyActivities) < 1) {
      return CRM_Core_Error::statusBounce(ts('We could not found respondent for this survey to release.'));
    }

    $this->assign('surveyTitle', $surveyDetails['title']);

    //append breadcrumb to survey dashboard.

    if (CRM_Campaign_BAO_Campaign::accessCampaignDashboard()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey(s)'), 'url' => $url]]);
    }

    //set the title.
    CRM_Utils_System::setTitle(ts('Release Respondents'));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {

    $this->addDefaultButtons(ts('Release Respondents'), 'done');
  }

  function postProcess() {
    $deleteActivityIds = [];
    foreach ($this->_contactIds as $cid) {
      if (CRM_Utils_Array::arrayKeyExists($cid, $this->_surveyActivities)) {
        $deleteActivityIds[] = $this->_surveyActivities[$cid]['activity_id'];
      }
    }

    //set survey activites as deleted = true.
    if (!empty($deleteActivityIds)) {
      $query = 'UPDATE civicrm_activity SET is_deleted = 1 WHERE id IN ( ' . CRM_Utils_Array::implode(', ', $deleteActivityIds) . ' )';
      CRM_Core_DAO::executeQuery($query);

      $status = [ts("%1 respondent(s) have been released.", [1 => count($deleteActivityIds)])];
      if (count($this->_contactIds) > count($deleteActivityIds)) {
        $status[] = ts("%1 respondents did not release.",
          [1 => (count($this->_contactIds) - count($deleteActivityIds))]
        );
      }
      CRM_Core_Session::setStatus(CRM_Utils_Array::implode('&nbsp;', $status));
    }
  }
}

