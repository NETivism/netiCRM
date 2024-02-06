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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/Session.php';
require_once 'CRM/Core/PseudoConstant.php';
require_once 'CRM/Core/BAO/Tag.php';

require_once 'CRM/Utils/PagerAToZ.php';

require_once 'CRM/Contact/Selector/Controller.php';
require_once 'CRM/Contact/Selector.php';
require_once 'CRM/Contact/Task.php';
require_once 'CRM/Contact/BAO/SavedSearch.php';

/**
 * Base Search / View form for *all* listing of multiple
 * contacts
 */
class CRM_Contact_Form_Search extends CRM_Core_Form {
  /*
     * list of valid contexts
     *
     * @var array
     * @static
     */

  static $_validContext = NULL;

  /**
   * list of values used when we want to display other objects
   *
   * @var array
   * @static
   */
  static $_modeValues = NULL;

  /**
   * The context that we are working on
   *
   * @var string
   * @access protected
   */
  protected $_context;

  /**
   * The contextMenu
   *
   * @var array
   * @access protected
   */
  protected $_contextMenu;

  /**
   * the groupId retrieved from the GET vars
   *
   * @var int
   * @access public
   */
  public $_groupID;

  /**
   * the Group ID belonging to Add Member to group ID
   * retrieved from the GET vars
   *
   * @var int
   * @access protected
   */
  protected $_amtgID;

  /**
   * the saved search ID retrieved from the GET vars
   *
   * @var int
   * @access protected
   */
  protected $_ssID;

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
   * the group elements
   *
   * @var array
   * @access public
   */
  public $_group;
  public $_groupElement;
  public $_groupIterator;

  /**
   * the tag elements
   *
   * @var array
   * @access protected
   */
  public $_tag;
  public $_tagElement;

  /**
   * form values that we will be using
   *
   * @var array
   * @access protected
   */
  public $_formValues;

  /**
   * The params used for search
   *
   * @var array
   * @access protected
   */
  protected $_params;

  /**
   * The return properties used for search
   *
   * @var array
   * @access protected
   */
  protected $_returnProperties;

  /**
   * The sort by character
   *
   * @var string
   * @access protected
   */
  protected $_sortByCharacter;

  /**
   * The profile group id used for display
   *
   * @var integer
   * @access protected
   */
  protected $_ufGroupID;

  /*
     * csv - common search values
     *
     * @var array
     * @access protected
     * @static
     */

  static $csv = array('contact_type', 'group', 'tag');

  protected $_componentMode;
  protected $_modeValue;

  /**
   * have we already done this search
   *
   * @access protected
   * @var boolean
   */
  protected $_done;

  /**
   * name of the selector to use
   */
  protected $_selectorName = 'CRM_Contact_Selector';
  protected $_customSearchID = NULL;
  protected $_customSearchClass = NULL;

  /**
   * define the set of valid contexts that the search form operates on
   *
   * @return array the valid context set and the titles
   * @access protected
   * @static
   */
  static function &validContext() {
    if (!(self::$_validContext)) {
      self::$_validContext = array(
        'smog' => 'Show members of group',
        'amtg' => 'Add members to group',
        'basic' => 'Basic Search',
        'search' => 'Search',
        'builder' => 'Search Builder',
        'advanced' => 'Advanced Search',
        'custom' => 'Custom Search',
      );
    }
    return self::$_validContext;
  }

  static function isSearchContext($context) {
    $searchContext = CRM_Utils_Array::value($context, self::validContext());
    return $searchContext ? TRUE : FALSE;
  }

  function setModeValues() {
    if (!self::$_modeValues) {
      $selectorName = (property_exists($this, '_selectorName') && $this->_selectorName) ? $this->_selectorName : 'CRM_Contact_Selector';
      self::setModeValuesCommon($selectorName);
    }
  }

  function getModeValue($mode = 1) {
    $this->setModeValues();

    if (!CRM_Utils_Array::arrayKeyExists($mode, self::$_modeValues)) {
      $mode = 1;
    }

    return self::$_modeValues[$mode];
  }

  public static function getModeValueCommon($mode) {
    if (!self::$_modeValues) {
      self::setModeValuesCommon('CRM_Contact_Selector');
    }

    if (!CRM_Utils_Array::arrayKeyExists($mode, self::$_modeValues)) {
      $mode = 1;
    }

    return self::$_modeValues[$mode];
  }

  public static function setModeValuesCommon($selectorName) {
    self::$_modeValues = array(
      1 => array(
        'selectorName' => $selectorName,
        'selectorLabel' => ts('Contacts'),
        'taskFile' => "CRM/Contact/Form/Search/ResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Contact/Form/Selector.tpl',
        'resultContext' => NULL,
        'taskClassName' => 'CRM_Contact_Task',
      ),
      2 => array('selectorName' => 'CRM_Contribute_Selector_Search',
        'selectorLabel' => ts('Contributions'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => 'Contribution',
        'resultFile' => 'CRM/Contribute/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Contribute_Task',
      ),
      3 => array('selectorName' => 'CRM_Event_Selector_Search',
        'selectorLabel' => ts('Event Participants'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Event/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Event_Task',
      ),
      4 => array('selectorName' => 'CRM_Activity_Selector_Search',
        'selectorLabel' => ts('Activities'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Activity/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Activity_Task',
      ),
      5 => array('selectorName' => 'CRM_Member_Selector_Search',
        'selectorLabel' => ts('Membership'),
        'taskFile' => "CRM/common/searchResultTasks.tpl",
        'taskContext' => NULL,
        'resultFile' => 'CRM/Member/Form/Selector.tpl',
        'resultContext' => 'Search',
        'taskClassName' => 'CRM_Member_Task',
      ),
    );
  }


  function getModeSelect() {
    $this->setModeValues();

    $select = array();
    foreach (self::$_modeValues as $id => & $value) {
      $select[$id] = $value['selectorLabel'];
    }

    // unset contributions or participants if user does not have
    // permission on them
    if (!CRM_Core_Permission::access('CiviContribute')) {
      unset($select['2']);
    }

    if (!CRM_Core_Permission::access('CiviEvent')) {
      unset($select['3']);
    }

    if (!CRM_Core_Permission::check('view all activities')) {
      unset($select['4']);
    }

    if (!CRM_Core_Permission::access('CiviMember')) {
      unset($select['5']);
    }

    return $select;
  }

  /**
   * Build the common elements between the search/advanced form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $permission = CRM_Core_Permission::getPermission();

    // some tasks.. what do we want to do with the selected contacts ?
    $tasks = array('' => ts('- actions -'));
    if ($this->_componentMode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
      $tasks += CRM_Contact_Task::permissionedTaskTitles($permission,
        CRM_Utils_Array::value('deleted_contacts',
          $this->_formValues
        )
      );
    }
    else {
      $taskClassName = $this->_modeValue['taskClassName'];
      $tasks += $taskClassName::permissionedTaskTitles( $permission );
    }

    if (isset($this->_ssID)) {
      if ($permission == CRM_Core_Permission::EDIT) {
        CRM_Contact_Task::optionalTaskTitle($tasks);
      }

      $savedSearchValues = array('id' => $this->_ssID,
        'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
      );
      $this->assign_by_ref('savedSearch', $savedSearchValues);
      $this->assign('ssID', $this->_ssID);
    }

    if ($this->_context === 'smog') {
      // need to figure out how to freeze a bunch of checkboxes, hack for now
      if ($this->_action != CRM_Core_Action::ADVANCED) {
        //Fix ME
        //$this->_groupElement->freeze( );
      }

      // also set the group title
      $cacheDate = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'cache_date');
      $groupValues = array('id' => $this->_groupID, 'title' => $this->_group[$this->_groupID], 'cache_date' => $cacheDate);
      if (CRM_REQUEST_TIME - CRM_Contact_BAO_GroupContactCache::SMARTGROUP_CACHE_TIMEOUT_MINIMAL > strtotime($cacheDate)) {
        $groupValues['refresh_button'] = TRUE;
      }
      $this->set('gid', $this->_groupID);
      $this->assign_by_ref('group', $groupValues);

      // also set ssID if this is a saved search
      $ssID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id');
      $this->assign('ssID', $ssID);

      //get the saved search mapping id
      if ($ssID) {
        $ssMappingId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $ssID, 'mapping_id');
      }

      if (isset($ssMappingId)) {
        $this->assign('ssMappingID', $ssMappingId);
      }
      $group_contact_status = array();
      foreach (CRM_Core_SelectValues::groupContactStatus() as $k => $v) {
        if (!empty($k)) {
          $group_contact_status[] = $this->createElement('checkbox', $k, NULL, $v);
        }
      }
      $this->addGroup($group_contact_status,
        'group_contact_status', ts('Group Status')
      );

      /* 
             * commented out to fix CRM-4268
             *
             * $this->addGroupRule( 'group_contact_status',
             *                  ts( 'Please select at least Group Status value.' ), 'required', null, 1 );
            */


      // Set dynamic page title for 'Show Members of Group'
      CRM_Utils_System::setTitle(ts('Contacts in Group: %1', array(1 => $this->_group[$this->_groupID])));

      // check if user has permission to edit members of this group
      require_once 'CRM/Contact/BAO/Group.php';
      $permission = CRM_Contact_BAO_Group::checkPermission($this->_groupID, $this->_group[$this->_groupID]);
      if ($permission && in_array(CRM_Core_Permission::EDIT, $permission)) {
        $this->assign('permissionedForGroup', TRUE);
      }
      else {
        $this->assign('permissionedForGroup', FALSE);
      }
    }

    /*
         * add the go button for the action form, note it is of type 'next' rather than of type 'submit'
         *
         */

    if ($this->_context === 'amtg') {
      // Set dynamic page title for 'Add Members Group'
      CRM_Utils_System::setTitle(ts('Add to Group: %1', array(1 => $this->_group[$this->_amtgID])));
      // also set the group title and freeze the action task with Add Members to Group
      $groupValues = array('id' => $this->_amtgID, 'title' => $this->_group[$this->_amtgID]);
      $this->assign_by_ref('group', $groupValues);
      $this->add('submit', $this->_actionButtonName, ts('Add Contacts to %1', array(1 => $this->_group[$this->_amtgID])),
        array('class' => 'form-submit',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 1);",
        )
      );
      $this->add('hidden', 'task', CRM_Contact_Task::GROUP_CONTACTS);
    }
    else {
      $this->add('select', 'task', ts('Actions:') . ' ', $tasks);
      $this->add('submit', $this->_actionButtonName, ts('Go'),
        array('class' => 'form-submit',
          'id' => 'Go',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 0);",
        )
      );
    }

    // need to perform tasks on all or selected items ? using radio_ts(task selection) for it
    $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel');
    $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);

    $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all', array('checked' => 'checked'));
    $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);

    /*
     * add form checkboxes for each row. This is needed out here to conform to QF protocol
     * of all elements being declared in builQuickForm
     */

    $rows = $this->get('rows');
    if (is_array($rows)) {
      $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('onclick' => "toggleTaskAction( true ); return toggleCheckboxVals('mark_x_',this);"));
      foreach ($rows as $row) {
        $this->addElement('checkbox', $row['checkbox'], NULL, NULL);
      }
    }

    // add buttons
    $this->addButtons(array(
        array('type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      )
    );

    $this->add('submit', $this->_printButtonName, ts('Print'),
      array('class' => 'form-submit',
        'id' => 'Print',
        'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 1);",
      )
    );

    $this->setDefaultAction('refresh');
  }

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */
  function preProcess() {

    /**
     * set the varios class variables
     */
    $this->_group = &CRM_Core_PseudoConstant::group();
    $this->_groupIterator = &CRM_Core_PseudoConstant::groupIterator();
    $this->_tag = CRM_Core_BAO_Tag::getTags();
    $this->_done = FALSE;

    /*
         * we allow the controller to set force/reset externally, useful when we are being
         * driven by the wizard framework
         */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean',
      CRM_Core_DAO::$_nullObject
    );

    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean',
      CRM_Core_DAO::$_nullObject
    );

    $this->_groupID = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    $this->_amtgID = CRM_Utils_Request::retrieve('amtgID', 'Positive', $this);
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter', 'String', $this);
    $this->_ufGroupID = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_componentMode = CRM_Utils_Request::retrieve('component_mode', 'Positive', $this, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS, $_REQUEST);
    $this->_tagID = CRM_Utils_Request::retrieve( 'tid' , 'Positive', $this);
    
    if (!empty($this->_ssID) && !CRM_Core_Permission::check('edit groups')) {
      return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_printButtonName = $this->getButtonName('next', 'print');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->assign('printButtonName', $this->_printButtonName);
    $this->assign('actionButtonName', $this->_actionButtonName);

    // reset from session, CRM-3526
    $session = CRM_Core_Session::singleton();
    if ($this->_force && $session->get('selectedSearchContactIds')) {
      $session->resetScope('selectedSearchContactIds');
    }

    // if we dont get this from the url, use default if one exsts
    $config = CRM_Core_Config::singleton();
    if ($this->_ufGroupID == NULL &&
      $config->defaultSearchProfileID != NULL
    ) {
      $this->_ufGroupID = $config->defaultSearchProfileID;
    }

    /*
         * assign context to drive the template display, make sure context is valid
         */

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    if (!CRM_Utils_Array::value($this->_context, self::validContext())) {
      $this->_context = 'search';
    }
    $this->set('context', $this->_context);
    $this->assign('context', $this->_context);

    $this->_modeValue = $this->getModeValue($this->_componentMode);
    $this->assign($this->_modeValue);

    $this->set('selectorName', $this->_selectorName);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    // $this->controller->isModal( ) returns true if page is
    // valid, i.e all the validations are true

    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $this->normalizeFormValues();
      $this->_params = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
      $this->_returnProperties = &$this->returnProperties();

      // also get the uf group id directly from the post value
      $this->_ufGroupID = CRM_Utils_Array::value('uf_group_id', $_POST, $this->_ufGroupID);
      $this->_formValues['uf_group_id'] = $this->_ufGroupID;
      $this->set('id', $this->_ufGroupID);

      // also get the object mode directly from the post value
      $this->_componentMode = CRM_Utils_Array::value('component_mode', $_POST, $this->_componentMode);
    }
    else {
      $this->_formValues = $this->get('formValues');
      $this->_params = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
      $this->_returnProperties = &$this->returnProperties();
    }

    if (empty($this->_formValues)) {
      //check if group is a smart group (fix for CRM-1255)
      if ($this->_groupID) {
        if ($ssId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id')) {
          $this->_ssID = $ssId;
        }
      }

      // fix for CRM-1907
      if (isset($this->_ssID) && $this->_context != 'smog') {
        // we only retrieve the saved search values if out current values are null
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);

        //fix for CRM-1505
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id')) {
          $this->_params = &CRM_Contact_BAO_SavedSearch::getSearchParams($this->_ssID);
        }
        else {
          $this->_params = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
        }
        $this->_returnProperties = &$this->returnProperties();
      }
      else {
        if (isset($this->_ufGroupID)) {
          // also set the uf group id if not already present
          $this->_formValues['uf_group_id'] = $this->_ufGroupID;
        }
        if (isset($this->_componentMode)) {
          $this->_formValues['component_mode'] = $this->_componentMode;
        }
      }
    }
    $this->assign('id', CRM_Utils_Array::value('uf_group_id', $this->_formValues));

    // show the context menu only when we’re not searching for deleted contacts; CRM-5673
    if (!CRM_Utils_Array::value('deleted_contacts', $this->_formValues)) {
      require_once 'CRM/Contact/BAO/Contact.php';
      $menuItems = CRM_Contact_BAO_Contact::contextMenu();
      $primaryActions = CRM_Utils_Array::value('primaryActions', $menuItems, array());
      $this->_contextMenu = CRM_Utils_Array::value('moreActions', $menuItems, array());
      $this->assign('contextMenu', $primaryActions + $this->_contextMenu);
    }

    // CRM_Core_Error::debug( 'f', $this->_formValues );
    // CRM_Core_Error::debug( 'p', $this->_params );
    if (!isset($this->_componentMode)) {
      $this->_componentMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
    $modeValues = $this->getModeValue($this->_componentMode);

    require_once (str_replace('_', DIRECTORY_SEPARATOR, $this->_modeValue['selectorName']) . '.php');
    $this->_selectorName = $this->_modeValue['selectorName'];

    if (strpos($this->_selectorName, 'CRM_Contact_Selector') !== FALSE) {
      if (!empty($this->_customSearchClass)) {
        $this->controller->set('customSearchClass', $this->_customSearchClass);
      }
      $selectorName = $this->_selectorName;
      $selector = new $selectorName( $this->_customSearchClass,
                     $this->_formValues,
                     $this->_params,
                     $this->_returnProperties,
                     $this->_action,
                     false, true,
                     $this->_context );
    }
    else {
      $selectorName = $this->_selectorName;
      $selector = new $selectorName( $this->_params,
                     $this->_action,
                     null, false, null,
                     "search", "advanced" );
    }
    $controller = new CRM_Contact_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->get(CRM_Utils_Sort::SORT_ID),
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER
    );
    $controller->setEmbedded(TRUE);
    $this->selector = $selector;

    if ($this->_force) {
      // using default value as custom search force rule
      if ($this->_customSearchID) {
        if (method_exists($this, 'setDefaultValues')) {
          $defaults = $this->setDefaultValues();
          if (!empty($defaults)) {
            $this->_formValues = array_merge($this->_formValues, $defaults);
          }
        }
      }
      $this->postProcess();
      /*
             * Note that we repeat this, since the search creates and stores
             * values that potentially change the controller behavior. i.e. things
             * like totalCount etc
             */

      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
          $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $sortID,
        CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::TRANSFER
      );
      $controller->setEmbedded(TRUE);
    }

    $controller->moveFromSessionToTemplate();
  }

  function &getFormValues() {
    return $this->_formValues;
  }

  /**
   * Common post processing
   *
   * @return void
   * @access public
   */
  function postProcess() {
    /*
         * sometime we do a postProcess early on, so we dont need to repeat it
         * this will most likely introduce some more bugs :(
         */

    if ($this->_done) {
      return;
    }
    $this->_done = TRUE;

    //get the button name
    $buttonName = $this->controller->getButtonName();

    if (isset($this->_ufGroupID) &&
      !CRM_Utils_Array::value('uf_group_id', $this->_formValues)
    ) {
      $this->_formValues['uf_group_id'] = $this->_ufGroupID;
    }

    if (isset($this->_componentMode) &&
      !CRM_Utils_Array::value('component_mode', $this->_formValues)
    ) {
      $this->_formValues['component_mode'] = $this->_componentMode;
    }

    if (!CRM_Utils_Array::value('qfKey', $this->_formValues)) {
      $this->_formValues['qfKey'] = $this->controller->_key;
    }

    if (!CRM_Core_Permission::check('access deleted contacts')) {
      unset($this->_formValues['deleted_contacts']);
    }

    $this->set('type', $this->_action);
    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_params);
    $this->set('returnProperties', $this->_returnProperties);

    if ($buttonName == $this->_actionButtonName || $buttonName == $this->_printButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = &$this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }
    else {
      $output = CRM_Core_Selector_Controller::SESSION;

      // create the selector, controller and run - store results in session
      $searchChildGroups = TRUE;
      if ($this->get('isAdvanced')) {
        $searchChildGroups = FALSE;
      }

      if (strpos($this->_selectorName, 'CRM_Contact_Selector') !== FALSE) {
        $selectorName = $this->_selectorName;
        $selector = new $selectorName(
          $this->_customSearchClass,
          $this->_formValues,
          $this->_params,
          $this->_returnProperties,
          $this->_action,
          false,
          $searchChildGroups,
          $this->_context,
          $this->_contextMenu
        );
      }
      else {
        $selectorName = $this->_selectorName;
        $selector = new $selectorName($this->_params, $this->_action, NULL, FALSE, NULL, "search", "advanced");
      }

      $selector->setKey($this->controller->_key);

      // added the sorting  character to the form array
      // lets recompute the aToZ bar without the sortByCharacter
      // we need this in most cases except when just pager or sort values change, which
      // we'll ignore for now
      $config = CRM_Core_Config::singleton();
      if ($config->includeAlphabeticalPager) {
        if ($this->_reset || !$this->_sortByCharacter) {
          $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($selector, $this->_sortByCharacter);
          $this->set('AToZBar', $aToZBar);
        }
      }

      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
          $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $sortID,
        CRM_Core_Action::VIEW,
        $this,
        $output
      );
      $controller->setEmbedded(TRUE);
      $controller->run();
    }
  }

  function &returnProperties() {
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  function getTitle() {
    return ts('Search');
  }
}

