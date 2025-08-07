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
 * advanced search, extends basic search
 */
class CRM_Contribute_Form_Search extends CRM_Core_Form {

  public $_exportButtonName;
  /**
   * @var never[]
   */
  public $defaults;
  /**
   * @var string
   */
  public $_reset;
  public $_pageId;
  /**
   * Are we forced to run a search
   *
   * @var int
   * @access protected
   */
  protected $_force;

  /**
   * Are we search test contribution
   *
   * @var int
   * @access protected
   */
  protected $_test;

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
  protected $_compContext = NULL;

  protected $_defaults;

  /**
   * prefix for the controller
   *
   */
  protected $_prefix = "contribute_";

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
    $this->_exportButtonName = $this->getButtonName('next', 'task_4');

    $this->_done = FALSE;
    $this->defaults = [];

    /* 
     * we allow the controller to set force/reset externally, useful when we are being 
     * driven by the wizard framework 
     */
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_pageId = CRM_Utils_Request::retrieve('pid', 'Positive', CRM_Core_DAO::$_nullObject);
    $this->_test = CRM_Utils_Request::retrieve('test', 'Boolean', $this);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = empty($this->get('context')) ? CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search') : $this->get('context');
    $this->_compContext = $this->get('compContext');

    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    //membership ID
    $memberShipId = CRM_Utils_Request::retrieve('memberId', 'Positive', $this);
    if (isset($memberShipId)) {
      $this->_formValues['contribution_membership_id'] = $memberShipId;
    }
    $participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);
    if (isset($participantId)) {
      $this->_formValues['contribution_participant_id'] = $participantId;
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
    $selector = new CRM_Contribute_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context,
      $this->_compContext
    );
    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);
    $this->assign("page_id", $this->_pageId);

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

    $this->assign('contributionSummary', $this->get('summary'));
  }

  function setDefaultValues() {
    if (!CRM_Utils_Array::value('contribution_status',
        $this->_defaults
      )) {
      $this->_defaults['contribution_status'][1] = 1;
    }
    return $this->_defaults;
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    // text for sort_name
    $this->addElement('text', 'sort_name', ts('Contributor Name, Phone or Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));


    CRM_Contribute_BAO_Query::buildSearchForm($this);

    /* 
     * add form checkboxes for each row. This is needed out here to conform to QF protocol 
     * of all elements being declared in builQuickForm 
     */


    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addElement('checkbox', 'toggleSelect');
        foreach ($rows as $row) {
          $this->addElement('checkbox', $row['checkbox']);
        }
      }

      $total = $cancel = 0;


      $permission = CRM_Core_Permission::getPermission();


      $tasks = ['' => ts('- actions -')] + CRM_Contribute_Task::permissionedTaskTitles($permission);
      $this->add('select', 'task', ts('Actions:') . ' ', $tasks);
      $this->add('submit', $this->_actionButtonName, ts('Go'),
        ['class' => 'form-submit',
          'id' => 'Go',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 0);",
        ]
      );

      $this->add('submit', $this->_printButtonName, ts('Print'),
        ['class' => 'form-submit',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 1);",
        ]
      );

      // override default task
      $this->addElement('hidden', 'task_force', 4);
      $this->add('submit', $this->_exportButtonName, ts('Export Contributions'),
        ['class' => 'form-submit',
          'id' => 'export',
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
      ]
    );
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

    $this->fixFormValues();

    // we don't show test contributions in Contact Summary / User Dashboard
    // in Search mode by default we hide test contributions
    if (!CRM_Utils_Array::value('contribution_test',
        $this->_formValues
      )) {
      $this->_formValues["contribution_test"] = 0;
    }

    foreach (['contribution_amount_low', 'contribution_amount_high'] as $f) {
      if (isset($this->_formValues[$f])) {
        $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
      }
    }


    CRM_Core_BAO_CustomValue::fixFieldValueOfTypeMemo($this->_formValues);


    $this->_queryParams = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName || $buttonName == $this->_printButtonName || $buttonName == $this->_exportButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // refs #18784, take sortOrder to CRM_Contribute_Form_Task
      $this->_queryParams = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
      $selector = new CRM_Contribute_Selector_Search($this->_queryParams,
        $this->_action,
        NULL,
        $this->_single,
        $this->_limit,
        $this->_context,
        $this->_compContext
      );
      $sortOrder = $selector->getSortOrder(CRM_Core_Action::VIEW);
      $this->controller->set('sortOrder',$sortOrder);

      // hack, make sure we reset the task values
      $stateMachine = &$this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);

      if ($buttonName == $this->_exportButtonName) {
        $this->controller->set('force', 1);
        $this->controller->set('entityTable', 'civicrm_contribution_page');
        $this->controller->set('entityId', $this->_formValues['contribution_page_id']);
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
    $selector = new CRM_Contribute_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context,
      $this->_compContext
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic' || $this->_context == 'user') {
      $prefix = $this->_prefix;
    }

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
    $summary = &$query->summaryContribution($this->_context);
    $this->set('summary', $summary);
    $this->assign('contributionSummary', $summary);
    $controller->run();
  }

  function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)

    if (!$this->_force) {
      return;
    }

    $status = CRM_Utils_Request::retrieve('status', 'String', CRM_Core_DAO::$_nullObject);
    if ($status) {
      $this->_formValues['contribution_status_id'] = [$status => 1];
      $this->_defaults['contribution_status_id'] = [$status => 1];
    }

    $type = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject);
    if ($type) {
      $type = explode(',', $type);
      foreach ($type as $k => $t) {
        $types[$t] = $t;
      }
      $ctypes = CRM_Contribute_PseudoConstant::contributionType();
      $types = array_intersect_key($types, $ctypes);

      $this->_formValues['contribution_type_id'] = $types;
      $this->_defaults['contribution_type_id'] = $types;
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {

        $this->_formValues['contact_id'] = $cid;
        list($display, $image) = CRM_Contact_BAO_Contact::getDisplayAndImage($cid);
        $this->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid,
          'sort_name'
        );
        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }

    $lowDate = CRM_Utils_Request::retrieve('start', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($lowDate) {
      $lowDate = CRM_Utils_Type::escape($lowDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($lowDate);
      $this->_formValues['contribution_date_low'] = $this->_defaults['contribution_date_low'] = $date[0];
    }

    $highDate = CRM_Utils_Request::retrieve('end', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($highDate) {
      $highDate = CRM_Utils_Type::escape($highDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($highDate);
      $this->_formValues['contribution_date_high'] = $this->_defaults['contribution_date_high'] = $date[0];
    }

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive',
      $this
    );

    $test = CRM_Utils_Request::retrieve('test', 'Boolean', $this);
    if (isset($test)) {
      $test = CRM_Utils_Type::escape($test, 'Boolean');
      $this->_formValues['contribution_test'] = $test;
    }
    //Recurring id
    $recur = CRM_Utils_Request::retrieve('recur', 'Positive', $this, FALSE);
    if ($recur) {
      $this->_formValues['contribution_recur_id'] = $recur;
      $this->_formValues['contribution_recurring'] = 1;
    }else if($recur === '0'){
      $this->_formValues['contribution_recurring'] = 2;
    }

    //check for contribution page id.
    $contribPageId = CRM_Utils_Request::retrieve('pid', 'Positive', $this);
    if ($contribPageId) {
      $this->_formValues['contribution_page_id'] = $contribPageId;
    }

    //give values to default.
    $this->_defaults = $this->_formValues;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Find Contributions');
  }
}

