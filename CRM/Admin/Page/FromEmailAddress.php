<?php

class CRM_Admin_Page_FromEmailAddress extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * The option group id of from_email_address
   * 
   * @var int
   * @static
   */
  static $_optionGroupId = NULL;

  /**
   * The edit form controller
   *
   * @var CRM_Admin_Controller_FromEmailAddress
   */
  private $_controller = NULL;

  /**
   * Obtains the group name from url and sets the title.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    self::$_optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'from_email_address', 'id', 'name');
  }

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_OptionValue';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/from_email_address',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', [1 => ts('From Email Address')]),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/from_email_address',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete %1', [1 => ts('From Email Address')]),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   *
   * @return void
   */
  function run() {
    $this->preProcess();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    if ($id) {
      if (!$this->checkPermission($id, NULL)) {
        return CRM_Core_Error::statusBounce(ts('You do not have permission to make changes to the record'));
      }
    }
    self::$_template->assign('mode', $this->_mode);

    // handling edit form here
    if ($this->_action & (CRM_Core_Action::VIEW | CRM_Core_Action::ADD | CRM_Core_Action::UPDATE)) {
      $this->edit($this->_action, $id);
      $form = $this->_controller->getCurrentPage();
      self::$_template->assign('tplFile', $form->getHookedTemplateFileName());
    }
    // handling delete form here
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->delete($id);
      $form = $this->_controller->getCurrentPage();
      self::$_template->assign('tplFile', $form->getHookedTemplateFileName());
    }
    // handling email confirmation link
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      CRM_Admin_Form_FromEmailAddress::verifyEmail($id);
    }
    // if no action or browse
    else {
      $this->browse();
      $pageTemplateFile = $this->getHookedTemplateFileName();
      self::$_template->assign('tplFile', $pageTemplateFile);
    }

    if ($this->_embedded) {
      return;
    }

    CRM_Utils_Hook::pageRun($this);

    $config = CRM_Core_Config::singleton();
    $content = self::$_template->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');
    CRM_Utils_Hook::alterContent($content, 'page', $pageTemplateFile, $this);
    CRM_Utils_System::theme($content);
  }

  /**
   * Browse all options
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    $groupParams = ['name' => 'from_email_address'];
    $optionValues = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'component_id,weight');
    $returnURL = CRM_Utils_System::url("civicrm/admin/from_email_address", "reset=1");
    $filter = "option_group_id = " . self::$_optionGroupId;
    CRM_Utils_Weight::addOrder($optionValues, 'CRM_Core_DAO_OptionValue', 'id', $returnURL, $filter);
    foreach($optionValues as $idx => $val) {
      $email = CRM_Utils_Mail::pluckEmailFromHeader($val['name']);
      $pageCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_contribution_page WHERE receipt_from_email LIKE %1", [
        1 => [$email, 'String'],
      ]);
      $eventCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_event WHERE confirm_from_email LIKE %1", [
        1 => [$email, 'String'],
      ]);
      $optionValues[$idx]['used_for_page'] = $pageCount;
      $optionValues[$idx]['used_for_event'] = $eventCount;

      // remove delete link
      if (($pageCount || $eventCount) && !$val['is_reserved']) {
        $this->links();
        $action = CRM_Core_Action::UPDATE;
        $optionValues[$idx]['action'] = CRM_Core_Action::formLink($this->links(), $action, [
          'id' => $val['id'],
          'gid' => $val['option_group_id'],
          'value' => $val['value'],
        ]);
      }
    }
    $this->assign('rows', $optionValues);
  }

  /**
   * Edit this entity.
   *
   * @param int $mode - what mode for the form ?
   * @param int $id - id of the entity (for update, view operations)
   *
   * @return $controller
   */
  function edit($mode, $id = NULL, $imageUpload = FALSE, $pushUserContext = TRUE) {
    $controllerName = $this->editForm();
    $this->_controller = new $controllerName();

    // set the userContext stack
    if ($pushUserContext) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url($this->userContext($mode), $this->userContextParams($mode)));
    }
    if ($id !== NULL) {
      $this->_controller->set('id', $id);
    }
    $this->_controller->set('BAOName', $this->getBAOName());
    $this->_controller->setEmbedded(TRUE);
    $this->_controller->process();
    $this->_controller->run();
  }

  function delete($id) {
    $this->_controller = new CRM_Core_Controller_Simple('CRM_Admin_Form_FromEmailAddress', $this->editName(), $this->_action, FALSE);
    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url($this->userContext($this->_action), $this->userContextParams($this->_action)));
    if ($id !== NULL) {
      $this->_controller->set('id', $id);
    }
    $this->_controller->set('BAOName', $this->getBAOName());
    $this->_controller->setEmbedded(TRUE);
    $this->_controller->process();
    $this->_controller->run();
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Controller_FromEmailAddress';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'from_email_address';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/from_email_address';
  }

  /**
   * function to get userContext params
   *
   * @param int $mode mode that we are in
   *
   * @return string
   * @access public
   */
  function userContextParams($mode = NULL) {
    return '&reset=1&action=browse';
  }
}