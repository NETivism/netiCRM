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
 * This class create activities for a case
 *
 */
class CRM_Case_Form_Activity extends CRM_Activity_Form_Activity {

  public $_encounterMedium;
  /**
   * The default variable defined
   *
   * @var int
   */
  public $_caseId;

  /**
   * The default case type variable defined
   *
   * @var int
   */
  public $_caseType;

  /**
   * The default values of an activity
   *
   * @var array
   */
  public $_defaults = [];

  /**
   * The array of releted contact info
   *
   * @var array
   */
  public $_relatedContacts;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  function preProcess() {
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    if (!$this->_context) {
      $this->_context = 'caseActivity';
    }
    $this->_crmDir = 'Case';
    $this->assign('context', $this->_context);

    $result = parent::preProcess();

    $scheduleStatusId = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $this->assign('scheduleStatusId', $scheduleStatusId);

    if ($this->_cdType || $this->_addAssigneeContact || $this->_addTargetContact) {
      return $result;
    }

    if (!$this->_caseId && $this->_activityId) {
      $this->_caseId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseActivity', $this->_activityId,
        'case_id', 'activity_id'
      );
    }
    if ($this->_caseId) {
      $this->assign('caseId', $this->_caseId);
    }

    if (!$this->_caseId ||
      (!$this->_activityId && !$this->_activityTypeId)
    ) {
      CRM_Core_Error::fatal('required params missing.');
    }

    //check for case activity access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }
    //validate case id.
    if ($this->_caseId &&
      !CRM_Core_Permission::check('access all cases and activities')
    ) {
      $session = CRM_Core_Session::singleton();
      $allCases = CRM_Case_BAO_Case::getCases(TRUE, $session->get('userID'));
      if (!CRM_Utils_Array::arrayKeyExists($this->_caseId, $allCases)) {
        return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
      }
    }

    //validate case activity id.
    if ($this->_activityId &&
      ($this->_action & CRM_Core_Action::UPDATE)
    ) {
      $valid = CRM_Case_BAO_Case::checkPermission($this->_activityId, 'edit',
        $this->_activityTypeId
      );
      if (!$valid) {
        return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
      }
    }

    $this->_caseType = CRM_Case_BAO_Case::getCaseType($this->_caseId, 'name');
    $this->assign('caseType', $this->_caseType);


    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    if ($isMultiClient) {
      $clients = CRM_Case_BAO_Case::getContactNames($this->_caseId);
      $this->assign('client_names', $clients);
    }
    else {
      $clientName = $this->_getDisplayNameById($this->_currentlyViewedContactId);
      $this->assign('client_name', $clientName);
    }
    // set context for pushUserContext and for statusBounce
    if ($this->_context == 'fulltext') {
      if ($this->_action == CRM_Core_Action::UPDATE || $this->_action == CRM_Core_Action::DELETE) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$this->_caseId}&show=1&context={$this->_context}"
        );
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/search/custom', 'force=1');
      }
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view/case',
        "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$this->_caseId}&show=1"
      );
    }
    if (!$this->_activityId) {

      $caseTypes = CRM_Case_PseudoConstant::caseType();

      if (empty($caseTypes) && ($this->_activityTypeName == 'Change Case Type') && !$this->_caseId) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$this->_caseId}&show=1"
        );
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
        return CRM_Core_Error::statusBounce(ts("You do not have any active Case Types"));
      }

      // check if activity count is within the limit
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
      $activityInst = $xmlProcessor->getMaxInstance($this->_caseType);

      // If not bounce back and also provide activity edit link
      if (isset($activityInst[$this->_activityTypeName])) {
        $activityCount = CRM_Case_BAO_Case::getCaseActivityCount($this->_caseId, $this->_activityTypeId);
        if ($activityCount >= $activityInst[$this->_activityTypeName]) {
          if ($activityInst[$this->_activityTypeName] == 1) {
            $atArray = ['activity_type_id' =>
              $this->_activityTypeId,
            ];
            $activities = CRM_Case_BAO_Case::getCaseActivity($this->_caseId,
              $atArray,
              $this->_currentUserId
            );
            $activities = array_keys($activities);
            $activities = $activities[0];
            $editUrl = CRM_Utils_System::url('civicrm/case/activity',
              "reset=1&cid={$this->_currentlyViewedContactId}&caseid={$this->_caseId}&action=update&id={$activities}"
            );
          }
          return CRM_Core_Error::statusBounce(ts("You can not add another '%1' activity to this case. %2",
              [1 => $this->_activityTypeName,
                2 => "Do you want to <a href='$editUrl'>edit the existing activity</a> ?",
              ]
            ),
            $url
          );
        }
      }
    }

    CRM_Utils_System::setTitle($this->_activityTypeName);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $this->_defaults = parent::setDefaultValues();
    $targetContactValues = [];

    //get all clients.
    $clients = CRM_Case_BAO_Case::getContactNames($this->_caseId);
    if (isset($this->_activityId) && empty($_POST)) {
      if (!CRM_Utils_Array::isEmpty($this->_defaults['target_contact'])) {
        $targetContactValues = array_combine(array_unique($this->_defaults['target_contact']),
          explode(';', trim($this->_defaults['target_contact_value']))
        );

        //exclude all clients.
        foreach ($clients as $clientId => $vals) {
          if (CRM_Utils_Array::arrayKeyExists($clientId, $targetContactValues)) {
            unset($targetContactValues[$clientId]);
          }
        }
      }
    }
    $this->assign('targetContactValues', empty($targetContactValues) ? FALSE : $targetContactValues);

    //return form for ajax
    if ($this->_cdType || $this->_addAssigneeContact || $this->_addTargetContact) {
      return $this->_defaults;
    }

    if ($this->_encounterMedium) {
      $this->_defaults['medium_id'] = $this->_encounterMedium;
    }
    elseif (empty($this->_defaults['medium_id'])) {
      // set default encounter medium CRM-4816

      $medium = CRM_Core_OptionGroup::values('encounter_medium', FALSE, FALSE, FALSE, 'AND is_default = 1');
      if (count($medium) == 1) {
        $this->_defaults['medium_id'] = key($medium);
      }
    }

    return $this->_defaults;
  }

  public function buildQuickForm() {
    // modify core Activity fields
    $this->add('text', 'target_contact_id', ts('target'));

    //FIXME : ideally hidden element should work.
    $this->addElement('advcheckbox', "hidden_target_contact");

    $this->_fields['source_contact_id']['label'] = ts('Reported By');
    $this->_fields['status_id']['attributes'] = ['' => ts('- select -')] + CRM_Core_PseudoConstant::activityStatus();

    if ($this->_caseType) {
      $xmlProcessor = new CRM_Case_XMLProcessor_Process();
      $aTypes = $xmlProcessor->get($this->_caseType, 'ActivityTypes', TRUE);

      // remove Open Case activity type since we're inside an existing case
      $openCaseID = CRM_Core_OptionGroup::getValue('activity_type', 'Open Case', 'name');
      unset($aTypes[$openCaseID]);
      asort($aTypes);
      $this->_fields['followup_activity_type_id']['attributes'] = ['' => '- select activity type -'] + $aTypes;
    }

    $result = parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::DETACH | CRM_Core_Action::RENEW)) {
      return;
    }

    if ($this->_cdType || $this->_addAssigneeContact || $this->_addTargetContact) {
      return $result;
    }

    $this->assign('urlPath', 'civicrm/case/activity');

    $encounterMediums = CRM_Case_PseudoConstant::encounterMedium();
    if ($this->_activityTypeFile == 'OpenCase') {
      $this->_encounterMedium = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $this->_activityId,
        'medium_id'
      );
      if (!CRM_Utils_Array::arrayKeyExists($this->_encounterMedium, $encounterMediums)) {
        $encounterMediums[$this->_encounterMedium] = CRM_Core_OptionGroup::getLabel('encounter_medium',
          $this->_encounterMedium,
          FALSE
        );
      }
    }

    $this->add('select', 'medium_id', ts('Medium'), $encounterMediums, TRUE);

    $this->_relatedContacts = CRM_Case_BAO_Case::getRelatedAndGlobalContacts($this->_caseId);
    //add case client in send a copy selector.CRM-4438.
    $relatedContacts = CRM_Case_BAO_Case::getContactNames($this->_caseId);
    if (!empty($relatedContacts)) {
      foreach ($relatedContacts as $relatedContact) {
        $this->_relatedContacts[] = $relatedContact;
      }
    }

    if (!empty($this->_relatedContacts)) {
      $checkBoxes = [];
      foreach ($this->_relatedContacts as $id => $row) {
        $checkBoxes[$id] = $this->addElement('checkbox', $id, NULL, '');
      }

      $this->addGroup($checkBoxes, 'contact_check');
      $this->addElement('checkbox', 'toggleSelect', NULL, NULL,
        ['onclick' => "return toggleCheckboxVals('contact_check',this);"]
      );
      $this->assign('searchRows', $this->_relatedContacts);
    }

    $this->addFormRule(['CRM_Case_Form_Activity', 'formRule'], $this);
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    // skip form rule if deleting
    if (CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Delete' || CRM_Utils_Array::value('_qf_Activity_next_', $fields) == 'Restore') {
      return TRUE;
    }

    return parent::formrule($fields, $files, $self);
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess($params = null) {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $statusMsg = NULL;

      //block deleting activities which affects
      //case attributes.CRM-4543
      $activityCondition = " AND v.name IN ('Open Case', 'Change Case Type', 'Change Case Status', 'Change Case Start Date')";
      $caseAttributeActivities = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $activityCondition);

      if (!CRM_Utils_Array::arrayKeyExists($this->_activityTypeId, $caseAttributeActivities)) {
        $params = ['id' => $this->_activityId];
        $activityDelete = CRM_Activity_BAO_Activity::deleteActivity($params, TRUE);
        if ($activityDelete) {
          $statusMsg = ts('The selected activity has been moved to the Trash. You can view and / or restore deleted activities by checking "Deleted Activities" from the Case Activities search filter (under Manage Case).<br />');
        }
      }
      else {
        $statusMsg = ts("Selected Activity cannot be deleted.");
      }


      $tagParams = ['entity_table' => 'civicrm_activity',
        'entity_id' => $this->_activityId,
      ];
      CRM_Core_BAO_EntityTag::del($tagParams);

      CRM_Core_Session::setStatus($statusMsg);
      return;
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $statusMsg = NULL;
      $params = ['id' => $this->_activityId];
      $activityRestore = CRM_Activity_BAO_Activity::restoreActivity($params);
      if ($activityRestore) {
        $statusMsg = ts('The selected activity has been restored.<br />');
      }
      CRM_Core_Session::setStatus($statusMsg);
      return;
    }

    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    if ($params['source_contact_id']) {
      $params['source_contact_id'] = $params['source_contact_qid'];
    }

    //set parent id if its edit mode
    if ($parentId = CRM_Utils_Array::value('parent_id', $this->_defaults)) {
      $params['parent_id'] = $parentId;
    }

    // required for status msg
    $recordStatus = 'created';

    // store the dates with proper format
    $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time'], $params['activity_date_time_time']);
    $params['activity_type_id'] = $this->_activityTypeId;


    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    $targetContacts = [$this->_currentlyViewedContactId];
    if (CRM_Utils_Array::value('hidden_target_contact', $params) &&
      CRM_Utils_Array::value('target_contact_id', $params)
    ) {
      $targetContacts = array_unique(explode(',', $params['target_contact_id']));
    }
    $params['target_contact_id'] = $targetContacts;

    // format activity custom data
    if (CRM_Utils_Array::value('hidden_custom', $params)) {
      if ($this->_activityId) {
        // unset custom fields-id from params since we want custom
        // fields to be saved for new activity.
        foreach ($params as $key => $value) {
          $match = [];
          if (preg_match('/^(custom_\d+_)(\d+)$/', $key, $match)) {
            $params[$match[1] . '-1'] = $params[$key];

            // for autocomplete transfer hidden value instead of label
            if ($params[$key] && isset($params[$key . '_id'])) {
              $params[$match[1] . '-1_id'] = $params[$key . '_id'];
              unset($params[$key . '_id']);
            }
            unset($params[$key]);
          }
        }
      }

      // build custom data getFields array
      $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, $this->_activityTypeId);
      $customFields = CRM_Utils_Array::arrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $customFields,
        $this->_activityId,
        'Activity'
      );
    }

    if (CRM_Utils_Array::value('assignee_contact_id', $params)) {
      $assineeContacts = explode(',', $params['assignee_contact_id']);
      $assineeContacts = array_unique($assineeContacts);
      unset($params['assignee_contact_id']);
    }
    else {
      $params['assignee_contact_id'] = $assineeContacts = [];
    }

    if (isset($this->_activityId)) {

      // activity which hasn't been modified by a user yet
      if ($this->_defaults['is_auto'] == 1) {
        $params['is_auto'] = 0;
      }

      // always create a revision of an case activity. CRM-4533
      $newActParams = $params;

      // add target contact values in update mode
      if (empty($params['target_contact_id']) && !empty($this->_defaults['target_contact'])) {
        $newActParams['target_contact_id'] = $this->_defaults['target_contact'];
      }

      // record status for status msg
      $recordStatus = 'updated';
    }

    if (!isset($newActParams)) {
      // add more attachments if needed for old activity
      CRM_Core_BAO_File::formatAttachment($params,
        $params,
        'civicrm_activity'
      );

      // call begin post process, before the activity is created/updated.
      $this->beginPostProcess($params);
      $params['case_id'] = $this->_caseId;
      // activity create/update
      $activity = CRM_Activity_BAO_Activity::create($params);

      // call end post process, after the activity has been created/updated.
      $this->endPostProcess($params, $activity);
    }
    else {
      // since the params we need to set are very few, and we don't want rest of the
      // work done by bao create method , lets use dao object to make the changes
      $params = ['id' => $this->_activityId];
      $params['is_current_revision'] = 0;
      $activity = new CRM_Activity_DAO_Activity();
      $activity->copyValues($params);
      $activity->save();
    }

    // create a new version of activity if activity was found to
    // have been modified/created by user
    if (isset($newActParams)) {

      // set proper original_id
      if (CRM_Utils_Array::value('original_id', $this->_defaults)) {
        $newActParams['original_id'] = $this->_defaults['original_id'];
      }
      else {
        $newActParams['original_id'] = $activity->id;
      }
      //is_current_revision will be set to 1 by default.

      // add attachments if any
      CRM_Core_BAO_File::formatAttachment($newActParams,
        $newActParams,
        'civicrm_activity'
      );

      // call begin post process, before the activity is created/updated.
      $this->beginPostProcess($newActParams);
      $newActParams['case_id'] = $this->_caseId;

      $activity = CRM_Activity_BAO_Activity::create($newActParams);

      // call end post process, after the activity has been created/updated.
      $this->endPostProcess($newActParams, $activity);

      // copy files attached to old activity if any, to new one,
      // as long as users have not selected the 'delete attachment' option.
      if (!CRM_Utils_Array::value('is_delete_attachment', $newActParams)) {
        CRM_Core_BAO_File::copyEntityFile('civicrm_activity', $this->_activityId,
          'civicrm_activity', $activity->id
        );
      }

      // copy back params to original var
      $params = $newActParams;
    }

    if ($activity->id) {
      // add tags if exists
      $tagParams = [];
      if (!empty($params['tag'])) {
        foreach ($params['tag'] as $tag) {
          $tagParams[$tag] = 1;
        }
      }

      //save static tags

      CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_activity', $activity->id);

      //save free tags
      if (isset($params['taglist']) && !empty($params['taglist'])) {

        CRM_Core_Form_Tag::postProcess($params['taglist'], $activity->id, 'civicrm_activity', $this);
      }
    }

    $params['assignee_contact_id'] = $assineeContacts;
    // update existing case record if needed
    $caseParams = $params;
    $caseParams['id'] = $this->_caseId;

    if (CRM_Utils_Array::value('case_type_id', $caseParams)) {
      $caseParams['case_type_id'] = CRM_Case_BAO_Case::VALUE_SEPERATOR . $caseParams['case_type_id'] . CRM_Case_BAO_Case::VALUE_SEPERATOR;
    }
    if (CRM_Utils_Array::value('case_status_id', $caseParams)) {
      $caseParams['status_id'] = $caseParams['case_status_id'];
    }

    // unset params intended for activities only
    unset($caseParams['subject'], $caseParams['details'],
      $caseParams['status_id'], $caseParams['custom']
    );
    $case = CRM_Case_BAO_Case::create($caseParams);

    // create case activity record
    $caseParams = ['activity_id' => $activity->id,
      'case_id' => $this->_caseId,
    ];
    CRM_Case_BAO_Case::processCaseActivity($caseParams);


    // create activity assignee records
    $assigneeParams = ['activity_id' => $activity->id];

    if (!CRM_Utils_Array::isEmpty($params['assignee_contact_id'])) {
      //skip those assignee contacts which are already assigned
      //while sending a copy.CRM-4509.
      $activityAssigned = array_flip($params['assignee_contact_id']);
      $activityId = $this->_activityId ?? $activity->id;
      $assigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($activityId);
      $activityAssigned = array_diff_key($activityAssigned, $assigneeContacts);

      foreach ($params['assignee_contact_id'] as $key => $id) {
        $assigneeParams['assignee_contact_id'] = $id;
        CRM_Activity_BAO_Activity::createActivityAssignment($assigneeParams);
      }
      //modify assigne_contact as per newly assigned contact before sending copy. CRM-4509.
      $params['assignee_contact_id'] = $activityAssigned;
    }

    // Insert civicrm_log record for the activity (e.g. store the
    // created / edited by contact id and date for the activity)
    // Note - civicrm_log is already created by CRM_Activity_BAO_Activity::create()


    // send copy to selected contacts.
    $mailStatus = '';
    $mailToContacts = [];

    //CRM-5695
    //check for notification settings for assignee contacts
    $selectedContacts = ['contact_check'];

    $config = &CRM_Core_Config::singleton();
    if ($config->activityAssigneeNotification) {
      $selectedContacts[] = 'assignee_contact_id';
    }

    foreach ($selectedContacts as $dnt => $val) {
      if (CRM_Utils_Array::arrayKeyExists($val, $params) && !CRM_Utils_array::crmIsEmptyArray($params[$val])) {
        if ($val == 'contact_check') {
          $mailStatus = ts("A copy of the activity has also been sent to selected contacts(s).");
        }
        else {
          $this->_relatedContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($activity->id, TRUE, FALSE);
          $mailStatus .= ' ' . ts("A copy of the activity has also been sent to assignee contacts(s).");
        }
        //build an associative array with unique email addresses.
        foreach ($params[$val] as $id => $dnc) {
          if (isset($id) && CRM_Utils_Array::arrayKeyExists($id, $this->_relatedContacts)) {
            //if email already exists in array then append with ', ' another role only otherwise add it to array.
            if ($contactDetails = CRM_Utils_Array::value($this->_relatedContacts[$id]['email'], $mailToContacts)) {
              $caseRole = CRM_Utils_Array::value('role', $this->_relatedContacts[$id]);
              $mailToContacts[$this->_relatedContacts[$id]['email']]['role'] = $contactDetails['role'] . ', ' . $caseRole;
            }
            else {
              $mailToContacts[$this->_relatedContacts[$id]['email']] = $this->_relatedContacts[$id];
            }
          }
        }
      }
    }

    if (!CRM_Utils_array::crmIsEmptyArray($mailToContacts)) {
      //include attachments while sendig a copy of activity.
      $attachments = &CRM_Core_BAO_File::getEntityFile('civicrm_activity',
        $activity->id
      );

      $result = CRM_Case_BAO_Case::sendActivityCopy($this->_currentlyViewedContactId,
        $activity->id, $mailToContacts, $attachments, $this->_caseId
      );

      if (empty($result)) {
        $mailStatus = '';
      }
    }
    else {
      $mailStatus = '';
    }

    // create follow up activity if needed
    $followupStatus = '';
    if (CRM_Utils_Array::value('followup_activity_type_id', $params)) {
      $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($activity->id, $params);

      if ($followupActivity) {
        $caseParams = ['activity_id' => $followupActivity->id,
          'case_id' => $this->_caseId,
        ];
        CRM_Case_BAO_Case::processCaseActivity($caseParams);
        $followupStatus = ts("A followup activity has been scheduled.");
      }
    }

    CRM_Core_Session::setStatus(ts("'%1' activity has been %2. %3 %4",
        [1 => $this->_activityTypeName,
          2 => $recordStatus,
          3 => $followupStatus,
          4 => $mailStatus,
        ]
      ));
  }
}

