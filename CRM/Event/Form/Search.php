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
 * Files required
 */





/**
 * This file is for civievent search
 */
class CRM_Event_Form_Search extends CRM_Core_Form {

  public $_exportButtonName;
  /**
   * @var never[]
   */
  public $defaults;
  /**
   * @var string
   */
  public $_reset;
  public $_eventId;
  public $_defaultValues;
  /**
   * Are we forced to run a search
   *
   * @var int
   * @access protected
   */
  protected $_force;

  /**
   * name of search button
   *
   * @var string
   * @access protected
   */
  protected $_searchButtonName;

  /**
   * name of print button
   *
   * @var string
   * @access protected
   */
  protected $_printButtonName;

  /**
   * name of action button
   *
   * @var string
   * @access protected
   */
  protected $_actionButtonName;

  /**
   * form values that we will be using
   *
   * @var array
   * @access protected
   */
  protected $_formValues;

  /**
   * the params that are sent to the query
   *
   * @var array
   * @access protected
   */
  protected $_queryParams;

  /**
   * have we already done this search
   *
   * @access protected
   * @var boolean
   */
  protected $_done;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * prefix for the controller
   *
   */
  protected $_prefix = "event_";

  protected $_defaults;

  /**
   * the saved search ID retrieved from the GET vars
   *
   * @var int
   * @access protected
   */
  protected $_ssID;

  /**
   * event infomation from saved event
   *
   * @var int
   * @access protected
   */
  protected $_event;

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->set('searchFormName', 'Search');

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_printButtonName = $this->getButtonName('next', 'print');
    $this->_actionButtonName = $this->getButtonName('next', 'action');
    $this->_exportButtonName = $this->getButtonName('next', 'task_3');

    $this->_done = FALSE;
    $this->defaults = [];

    /* 
         * we allow the controller to set force/reset externally, useful when we are being 
         * driven by the wizard framework 
         */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    $this->_eventId = CRM_Utils_Request::retrieve('event', 'Positive', CRM_Core_DAO::$_nullObject);
    if (empty($this->_eventId) && !empty($this->_formValues['event_id']) && is_numeric($this->_formValues['event_id'])) {
      $this->_eventId = $this->_formValues['event_id']; 
    }
    if ($this->_eventId) {
      $params = ['id' => $this->_eventId];
      CRM_Event_BAO_Event::retrieve($params, $this->_event);

      if ($this->_event['id']) {
        // participant count
        $max_participants = $this->_event['max_participants'];
        $status_summary = CRM_Event_PseudoConstant::participantStatus('', NULL, 'label');
        $status_summary = array_flip($status_summary);
        $summary = CRM_Event_BAO_Participant::statusEventSeats($this->_eventId);
        $summary['status'] = $status_summary;
        $summary['space'] = $max_participants ? $max_participants : 0;
        $this->assign('participantSummary', $summary);

        // prepopulate form
        $prePopulate = [
          ['id' => $event, 'name' => $this->_event['title']],
        ];
        $this->assign('eventPrepopulate', json_encode($prePopulate));

        // online registration links
        $this->assign('isOnlineRegistration', $this->_event['is_online_registration']);
        $participantListingID = CRM_Utils_Array::value('participant_listing_id', $eventInfo);
        if ($this->_event['participant_listing_id']) {
          $participantListingURL = CRM_Utils_System::url('civicrm/event/participant', "reset=1&id={$event}", TRUE, NULL, TRUE, TRUE);
          $this->assign('participantListingURL', $participantListingURL);
        }
      }
    }
    $this->assign("event_id", $this->_eventId);

    if (empty($this->_formValues)) {
      if (isset($this->_ssID)) {
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
      }
    }

    if ($this->_force) {
      $this->postProcess();
      $this->set('force', 0);
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }


    $this->_queryParams = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Event_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate();

    $this->assign('summary', $this->get('summary'));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $this->addElement('text', 'sort_name', ts('Participant Name, Phone or Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));


    CRM_Event_BAO_Query::buildSearchForm($this);

    /* 
         * add form checkboxes for each row. This is needed out here to conform to QF protocol 
         * of all elements being declared in builQuickForm 
         */

    $rows = $this->get('rows');
    if (is_array($rows)) {
      $lineItems = $participantIds = [];


      if (!$this->_single) {
        $this->addElement('checkbox', 'toggleSelect');
      }
      foreach ($rows as $row) {
        $participantIds[] = $row['participant_id'];
        if (!$this->_single) {
          $this->addElement('checkbox', $row['checkbox']);
        }
        if (CRM_Event_BAO_Event::usesPriceSet($row['event_id'])) {
          // add line item details if applicable

          $lineItems[$row['participant_id']] = CRM_Price_BAO_LineItem::getLineItems($row['participant_id']);
        }
      }

      $participantCount = CRM_Event_BAO_Participant::totalEventSeats($participantIds);
      $this->assign('participantCount', $participantCount);
      $this->assign('lineItems', $lineItems);

      $total = $cancel = 0;


      $permission = CRM_Core_Permission::getPermission();


      $tasks = ['' => ts('- actions -')] + CRM_Event_Task::permissionedTaskTitles($permission);
      if (isset($this->_ssID)) {
        if ($permission == CRM_Core_Permission::EDIT) {

          $tasks = $tasks + CRM_Event_Task::optionalTaskTitle();
        }

        $savedSearchValues = ['id' => $this->_ssID,
          'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        ];
        $this->assign_by_ref('savedSearch', $savedSearchValues);
        $this->assign('ssID', $this->_ssID);
      }

      $this->add('select', 'task', ts('Actions:') . ' ', $tasks);
      $this->add('submit', $this->_actionButtonName, ts('Go'),
        ['class' => 'form-submit',
          'id' => 'Go',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 0);",
        ]
      );

      // override default task
      $this->addElement('hidden', 'task_force', 3);
      $this->add('submit', $this->_exportButtonName, ts('Export Participants'),
        ['class' => 'form-submit',
          'id' => 'export',
        ]
      );

      $this->add('submit', $this->_printButtonName, ts('Print'),
        ['class' => 'form-submit',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 1);",
        ]
      );

      // need to perform tasks on all or selected items ? using radio_ts(task selection) for it
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', ['checked' => 'checked']);
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);

      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);
    }

    // add buttons
    $this->addButtons([
        ['type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]);
  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post procesing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submissing is
   *        done.
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   *
   * @param
   *
   * @return void
   * @access public
   */
  function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    if (empty($this->_formValues)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    // we don't show test registrations in Contact Summary / User Dashboard
    // in Search mode by default we hide test registrations
    if (!CRM_Utils_Array::value('participant_test',
        $this->_formValues
      )) {
      $this->_formValues["participant_test"] = 0;
    }


    CRM_Core_BAO_CustomValue::fixFieldValueOfTypeMemo($this->_formValues);


    $this->_queryParams = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName || $buttonName == $this->_printButtonName || $buttonName == $this->_exportButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = &$this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);

      if ($buttonName == $this->_exportButtonName) {
        $this->controller->set('force', 1);
        $this->controller->set('entityTable', 'civicrm_event');
        $this->controller->set('entityId', $this->_eventId);
      }
      return;
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }


    $this->_queryParams = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $selector = new CRM_Event_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );

    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);

    $query = &$selector->getQuery();
    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return None
   * @access public
   * @see valid_date
   */
  function addRules() {
    $this->addFormRule(['CRM_Event_Form_Search', 'formRule']);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   * @param array $errors list of errors to be posted back to the form
   *
   * @return void
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = [];

    if (!empty($fields['event_id']) && !is_numeric($fields['event_id'])) {
      if (strstr($fields['event_id'], ',')) {
        $ids = explode(',', $fields['event_id']);
        foreach($ids as $id) {
          if (!is_numeric($id)) {
            $errors['event_id'] = ts('Please select valid event.');
            break;
          }
        }
      }
      else {
        $errors['event_id'] = ts('Please select valid event.');
      }
    }

    if (!empty($fields['event_type_id']) && !is_numeric($fields['event_type_id'])) {
      if (strstr($fields['event_type_id'], ',')) {
        $ids = explode(',', $fields['event_type_id']);
        foreach($ids as $id) {
          if (!is_numeric($id)) {
            $errors['event_id'] = ts('Please select valid event type.');
            break;
          }
        }
      }
      else {
        $errors['event_type_id'] = ts('Please select valid event type.');
      }
    }
    if (!empty($errors)) {
      return $errors;
    }

    return TRUE;
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function &setDefaultValues() {
    $defaults = [];
    $defaults = $this->_formValues;
    self::fixEventIdDefaultValues($defaults);
    self::fixEventTypeIdDefaultValues($defaults);

    return $defaults;
  }

  function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)
    if (($this->_event['id'] && $this->_force) ||
        ($this->_event['id'] == $this->_formValues['event_id'])
    ) {
      $this->_formValues['event_id'] = $this->_event['id'];
      $this->_formValues['event_name'] = $this->_event['title'];
      CRM_Utils_System::setTitle($this->_event['title']);
    }
    else {
      if (isset($this->_defaultValues['event_id']) && !empty($this->_defaultValues['event_id'])) {
        if (is_numeric($this->_defaultValues['event_id'])) {
          $this->assign('event_id', $this->_defaultValues['event_id']);
        }
      }
    }

    $status = CRM_Utils_Request::retrieve('status', 'String', CRM_Core_DAO::$_nullObject);

    if (isset($status)) {

      if ($status === 'true') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 1");
      }
      elseif ($status === 'false') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 0");
      }
      elseif (is_numeric($status)) {
        $status = (int) $status;
        $statusTypes = [$status => CRM_Event_PseudoConstant::participantStatus($status)];
      }
      $status = [];
      foreach ($statusTypes as $key => $value) {
        $status[$key] = $key;
      }
      $this->_formValues['participant_status_id'] = $status;
    }

    $role = CRM_Utils_Request::retrieve('role', 'String', CRM_Core_DAO::$_nullObject);

    if (isset($role)) {

      if ($role === 'true') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 1");
      }
      elseif ($role === 'false') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 0");
      }
      elseif (is_numeric($role)) {
        $role = (int) $role;
        $roleTypes = [$role => CRM_Event_PseudoConstant::participantRole($role)];
      }
      $role = [];
      foreach ($roleTypes as $key => $value) {
        $role[$key] = $key;
      }
      $this->_formValues['participant_role_id'] = $role;
    }

    if (is_array($_REQUEST['participant_status_id'])) {
      $this->_formValues['participant_status_id'] = [];
      foreach ($_REQUEST['participant_status_id'] as $key => $value) {
        $this->_formValues['participant_status_id'][$value] = $value;
      }
    }

    if (is_array($_REQUEST['participant_role_id'])) {
      $this->_formValues['participant_role_id'] = [];
      foreach ($_REQUEST['participant_role_id'] as $key => $value) {
        $this->_formValues['participant_role_id'][$value] = $value;
      }
    }

    $type = CRM_Utils_Request::retrieve('type', 'Positive',
      CRM_Core_DAO::$_nullObject
    );
    if ($type) {
      $this->_formValues['event_type_id'] = $type;
    }

    $feeLevel = CRM_Utils_Request::retrieve('fee_level', 'String', CRM_Core_DAO::$_nullObject);

    if ($feeLevel && $this->_event['id']) {
      if (strstr($feeLevel, '|')) {
        $this->_formValues['participant_fee_id'] = explode('|', $feeLevel);
        foreach($this->_formValues['participant_fee_id'] as $key => $value) {
          $this->_formValues['participant_fee_id'][$key] = $value;
        }
      }
      else {
        if (strstr($feeLevel, 'priceset:') && strstr($feeLevel, ',')) {
          list($pricesetLabel, $fees) = explode(":", $feeLevel);
          $fees = explode(",", $fees);
          foreach($fees as $fee) {
            $this->_formValues['participant_fee_id'][] = 'priceset:'.$fee;
          }
        }
        else {
          $this->_formValues['participant_fee_id'] = [$feeLevel];
        }
      }
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;

        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }
  }

  function getFormValues() {
    return NULL;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Find Participants');
  }

  public static function fixEventIdDefaultValues($defaults) {
    if (!empty($defaults['event_id'])) {
      $prePopulate = [];
      if (is_numeric($defaults['event_id'])) {
        $eventTitle = CRM_Event_BAO_Event::retrieveField($defaults['event_id'], 'title');
        $prePopulate[] = ['id' => trim($defaults['event_id']), 'name' => $eventTitle];
      }
      else {
        $ids = explode(',', $defaults['event_id']);
        foreach($ids as $id) {
          $eventTitle = CRM_Event_BAO_Event::retrieveField($id, 'title');
          $prePopulate[] = ['id' => trim($id), 'name' => $eventTitle];
        }
      }
      $template = CRM_Core_Smarty::singleton();
      $template->assign('eventPrepopulate', json_encode($prePopulate));
    }
  }

  public static function fixEventTypeIdDefaultValues($defaults) {
    if (!empty($defaults['event_type_id'])) {
      $prePopulate = [];
      $types = CRM_Event_PseudoConstant::eventType();
      if (is_numeric($defaults['event_type_id'])) {
        $prePopulate[] = ['id' => trim($defaults['event_type_id']), 'name' => $types[$defaults['event_type_id']]];
      }
      else {
        $ids = explode(',', $defaults['event_type_id']);
        foreach($ids as $id) {
          $prePopulate[] = ['id' => trim($id), 'name' => $types[$id]];
        }
      }
      $template = CRM_Core_Smarty::singleton();
      $template->assign('eventTypePrepopulate', json_encode($prePopulate));
    }
  }
}

