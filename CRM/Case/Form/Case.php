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
 * This class generates form components for case activity
 *
 */
class CRM_Case_Form_Case extends CRM_Core_Form {

  public $_dedupeButtonName;
  /**
   * The context
   *
   * @var string
   */
  public $_context = 'case';

  /**
   * Case Id
   */
  public $_caseId = NULL;

  /**
   * Client Id
   */
  public $_currentlyViewedContactId = NULL;

  /**
   * Activity Type File
   */
  public $_activityTypeFile = NULL;

  /**
   * logged in contact Id
   */
  public $_currentUserId = NULL;

  /**
   * activity type Id
   */
  public $_activityTypeId = NULL;

  /**
   * activity type Id
   */
  public $_activityId = NULL;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  function preProcess() {
    $this->_caseId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($this->_action & CRM_Core_Action::ADD && !$this->_currentlyViewedContactId) {
      // check for add contacts permissions

      if (!CRM_Core_Permission::check('add contacts')) {
        CRM_Utils_System::permissionDenied();
        return;
      }
    }

    //CRM-4418
    if (!CRM_Core_Permission::checkActionPermission('CiviCase', $this->_action)) {
      return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }

    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }

    if (!$this->_caseId) {

      $caseAttributes = ['case_type' => CRM_Case_PseudoConstant::caseType(),
        'case_status' => CRM_Case_PseudoConstant::caseStatus(),
        'encounter_medium' => CRM_Case_PseudoConstant::encounterMedium(),
      ];

      foreach ($caseAttributes as $key => $values) {
        if (empty($values)) {
          return CRM_Core_Error::statusBounce(ts('You do not have any active %1',
              [1 => str_replace('_', ' ', $key)]
            ));
          break;
        }
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {

      $this->_activityTypeId = CRM_Core_OptionGroup::getValue('activity_type',
        'Open Case',
        'name'
      );
    }

    //check for case permissions.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::ADD) &&
      !CRM_Core_Permission::check('access all cases and activities')
    ) {
      return CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    if ($this->_activityTypeFile =
      CRM_Activity_BAO_Activity::getFileForActivityTypeId($this->_activityTypeId,
        'Case'
      )
    ) {

      $this->assign('activityTypeFile', $this->_activityTypeFile);
    }

    $details = CRM_Case_PseudoConstant::caseActivityType(FALSE);

    CRM_Utils_System::setTitle($details[$this->_activityTypeId]['label']);
    $this->assign('activityType', $details[$this->_activityTypeId]['label']);
    $this->assign('activityTypeDescription', $details[$this->_activityTypeId]['description']);

    if (isset($this->_currentlyViewedContactId)) {

      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $this->_currentlyViewedContactId;
      if (!$contact->find(TRUE)) {
        return CRM_Core_Error::statusBounce(ts('Client contact does not exist: %1', [1 => $this->_currentlyViewedContactId]));
      }
      $this->assign('clientName', $contact->display_name);
    }


    $session = CRM_Core_Session::singleton();
    $this->_currentUserId = $session->get('userID');

    //when custom data is included in this page
    CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity');
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $className::preProcess( $this );
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
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $defaults = $className::setDefaultValues($this);
    $defaults = array_merge($defaults, CRM_Custom_Form_CustomData::setDefaultValues($this));
    return $defaults;
  }

  public function buildQuickForm() {


    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      $title = 'Delete';
      if ($this->_action & CRM_Core_Action::RENEW) {
        $title = 'Restore';
      }
      $this->addButtons([
          ['type' => 'next',
            'name' => $title,
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    CRM_Custom_Form_CustomData::buildQuickForm($this);
    // we don't want to show button on top of custom form
    $this->assign('noPreCustomButton', TRUE);

    $s = CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'subject');
    if (!is_array($s)) {
      $s = [];
    }
    $this->add('text', 'activity_subject', ts('Subject'),
      array_merge($s, ['maxlength' => '128']), TRUE
    );


    $tags = CRM_Core_BAO_Tag::getTags('civicrm_case');
    if (!empty($tags)) {
      $this->add('select', 'tag', ts('Select Tags'), $tags, FALSE,
        ['id' => 'tags', 'multiple' => 'multiple', 'title' => ts('- select -')]
      );
    }

    // build tag widget

    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_case');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_case', NULL, FALSE, TRUE);

    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $className::buildQuickForm( $this );
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $this->addFormRule([$className, 'formrule'], $this);
    $this->addFormRule(['CRM_Case_Form_Case', 'formRule'], $this);
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values, $files, $form) {
    return TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // check if dedupe button, if so return.
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_dedupeButtonName) {
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $statusMsg = NULL;

      $caseDelete = CRM_Case_BAO_Case::deleteCase($this->_caseId, TRUE);
      if ($caseDelete) {
        $statusMsg = ts('The selected case has been moved to the Trash. You can view and / or restore deleted cases by checking the "Deleted Cases" option under Find Cases.<br />');
      }
      CRM_Core_Session::setStatus($statusMsg);
      return;
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $statusMsg = NULL;

      $caseRestore = CRM_Case_BAO_Case::restoreCase($this->_caseId);
      if ($caseRestore) {
        $statusMsg = ts('The selected case has been restored.<br />');
      }
      CRM_Core_Session::setStatus($statusMsg);
      return;
    }
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $params['now'] = date("Ymd");



    // 1. call begin post process
    if ($this->_activityTypeFile) {
      $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
      $className::beginPostProcess( $this, $params );
    }

    // 2. create/edit case

    if (CRM_Utils_Array::value('case_type_id', $params)) {

      $caseType = CRM_Case_PseudoConstant::caseType('name');
      $params['case_type'] = $caseType[$params['case_type_id']];
      $params['subject'] = $params['activity_subject'];
      $params['case_type_id'] = CRM_Case_BAO_Case::VALUE_SEPERATOR . $params['case_type_id'] . CRM_Case_BAO_Case::VALUE_SEPERATOR;
    }
    $caseObj = CRM_Case_BAO_Case::create($params);
    $params['case_id'] = $caseObj->id;
    // unset any ids, custom data
    unset($params['id'], $params['custom']);

    // add tags if exists
    $tagParams = [];
    if (!empty($params['tag'])) {

      $tagParams = [];
      foreach ($params['tag'] as $tag) {
        $tagParams[$tag] = 1;
      }
    }

    CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_case', $caseObj->id);

    //save free tags
    if (isset($params['taglist']) && !empty($params['taglist'])) {

      CRM_Core_Form_Tag::postProcess($params['taglist'], $caseObj->id, 'civicrm_case', $this);
    }

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$caseObj->id}"
    );
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);

    // 3. format activity custom data
    if (CRM_Utils_Array::value('hidden_custom', $params)) {
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

    // 4. call end post process
    if ($this->_activityTypeFile) {
      $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
      $className::endPostProcess( $this, $params );
    }

    // 5. auto populate activites

    // 6. set status
    CRM_Core_Session::setStatus("{$params['statusMsg']}");
  }
}

