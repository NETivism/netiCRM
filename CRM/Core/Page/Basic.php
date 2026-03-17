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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

abstract class CRM_Core_Page_Basic extends CRM_Core_Page {

  protected $_action;

  /**
   * define all the abstract functions here
   */

  /**
   * Get the name of the BAO associated with this page.
   *
   * @return string BAO class name
   */
  abstract public function getBAOName();

  /**
   * Get an array of action links.
   *
   * @return array associative array of links
   */
  abstract public function &links();

  /**
   * Get the class name of the edit form.
   *
   * @return string form class name
   */
  abstract public function editForm();

  /**
   * Get the name of the form.
   *
   * @return string form name
   */
  abstract public function editName();

  /**
   * Get the user context URL to return to after processing.
   *
   * @param int|null $mode the current mode
   *
   * @return string return URL
   */
  abstract public function userContext($mode = NULL);

  /**
   * Get the user context parameters.
   *
   * @param int|null $mode the current mode
   *
   * @return string URL query parameters
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * Check if the user has permission to access or modify a specific record.
   *
   * @param int $id record ID
   * @param string|null $name record name or title
   *
   * @return int|null permission constant (e.g., CRM_Core_Permission::EDIT) or NULL if denied
   */
  public function checkPermission($id, $name) {
    return CRM_Core_Permission::EDIT;
  }

  /**
   * Add extra values to the controller before processing.
   *
   * @param CRM_Core_Controller $controller the controller object
   *
   * @return void
   */
  public function addValues($controller) {
  }

  /**
   * Class constructor.
   *
   * @param string|null $title title of the page
   * @param int|null $mode mode of the page
   */
  public function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);
  }

  /**
   * Run the basic page execution flow.
   *
   * @return void
   */
  public function run() {
    // what action do we want to perform ? (store it for smarty too.. :)
    $thisArgs = func_get_args();
    $args = $thisArgs[0] ?? NULL;
    $pageArgs = $thisArgs[1] ?? NULL;
    $sort = $thisArgs[2] ?? NULL;

    $this->_action = CRM_Utils_Request::retrieve(
      'action',
      'String',
      $this,
      FALSE,
      'browse'
    );
    $this->assign('action', $this->_action);

    // get 'id' if present
    $id = CRM_Utils_Request::retrieve(
      'id',
      'Positive',
      $this,
      FALSE,
      0
    );

    require_once(str_replace('_', DIRECTORY_SEPARATOR, $this->getBAOName()) . ".php");

    if ($id) {
      if (!$this->checkPermission($id, NULL)) {
        return CRM_Core_Error::statusBounce(ts('You do not have permission to make changes to the record'));
      }
    }

    if ($this->_action &
      (
        CRM_Core_Action::VIEW |
        CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      // use edit form for view, add or update or delete
      $this->edit($this->_action, $id);
    }
    else {
      // if no action or browse
      $this->browse(NULL, $sort);
    }

    return parent::run();
  }

  /**
   * Alias for the parent run method.
   *
   * @return void
   */
  public function superRun() {
    return parent::run();
  }

  /**
   * Browse all entities and display them in a list.
   *
   * @return void
   */
  public function browse() {
    $thisArgs = func_get_args();
    $action = $thisArgs[0] ?? NULL;
    $sort = $thisArgs[1] ?? NULL;
    $links = &$this->links();
    if ($action == NULL) {
      if (!empty($links)) {
        $action = array_sum(array_keys($links));
      }
    }
    if ($action & CRM_Core_Action::DISABLE) {
      $action -= CRM_Core_Action::DISABLE;
    }
    if ($action & CRM_Core_Action::ENABLE) {
      $action -= CRM_Core_Action::ENABLE;
    }

    $baoString = $this->getBAOName();
    $object = new $baoString();

    $values = [];

    /*
         * lets make sure we get the stuff sorted by name if it exists
         */

    $fields = &$object->fields();
    $key = '';
    if (CRM_Utils_Array::value('title', $fields)) {
      $key = 'title';
    }
    elseif (CRM_Utils_Array::value('label', $fields)) {
      $key = 'label';
    }
    elseif (CRM_Utils_Array::value('name', $fields)) {
      $key = 'name';
    }

    if (trim($sort)) {
      $object->orderBy($sort);
    }
    elseif ($key) {
      $object->orderBy($key . ' asc');
    }

    // find all objects
    $object->find();
    while ($object->fetch()) {
      if (!isset($object->mapping_type_id) ||
        // "1 for Search Builder"
        $object->mapping_type_id != 1
      ) {
        $permission = CRM_Core_Permission::EDIT;
        if ($key) {
          $permission = $this->checkPermission($object->id, $object->$key);
        }
        if ($permission) {
          $values[$object->id] = [];
          CRM_Core_DAO::storeValues($object, $values[$object->id]);

          CRM_Contact_DAO_RelationshipType::addDisplayEnums($values[$object->id]);

          // populate action links
          $this->action($object, $action, $values[$object->id], $links, $permission);

          if (isset($object->mapping_type_id)) {

            $mappintTypes = CRM_Core_PseudoConstant::mappingTypes();
            $values[$object->id]['mapping_type'] = $mappintTypes[$object->mapping_type_id];
          }
        }
      }
    }
    $this->assign('rows', $values);
  }

  /**
   * Populate action links for a given record.
   *
   * @param CRM_Core_DAO &$object the data object
   * @param int $action the base set of actions
   * @param array &$values array to store link data for Smarty
   * @param array &$links the array of link definitions
   * @param int $permission permission level
   * @param bool $forceAction whether to bypass some eligibility checks
   *
   * @return void
   */
  public function action(&$object, $action, &$values, &$links, $permission, $forceAction = FALSE) {
    $values['class'] = '';
    $newAction = $action;
    $hasDelete = $hasDisable = TRUE;

    if (!empty($values['name']) && in_array($values['name'], ['encounter_medium', 'case_type', 'case_status'])) {
      static $caseCount = NULL;

      if (!isset($caseCount)) {
        $caseCount = CRM_Case_BAO_Case::caseCount(NULL, FALSE);
      }
      if ($caseCount > 0) {
        $hasDelete = $hasDisable = FALSE;
      }
    }

    if (!$forceAction) {
      if (property_exists($object, 'is_reserved') && $object->is_reserved) {
        $values['class'] = 'reserved';
        // check if object is relationship type
        if (get_class($object) == 'CRM_Contact_BAO_RelationshipType') {
          $newAction = CRM_Core_Action::VIEW + CRM_Core_Action::UPDATE;
        }
        else {
          $newAction = 0;
          $values['action'] = '';
          return;
        }
      }
      else {
        if (property_exists($object, 'is_active')) {
          if ($object->is_active) {
            if ($hasDisable) {
              $newAction += CRM_Core_Action::DISABLE;
            }
          }
          else {
            $newAction += CRM_Core_Action::ENABLE;
          }
        }
      }
    }

    //CRM-4418, handling edit and delete separately.
    $permissions = [$permission];
    if ($hasDelete && ($permission == CRM_Core_Permission::EDIT)) {
      //previously delete was subset of edit
      //so for consistency lets grant delete also.
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    // make sure we only allow those actions that the user is permissioned for
    $newAction = $newAction & CRM_Core_Action::mask($permissions);

    $values['action'] = CRM_Core_Action::formLink($links, $newAction, ['id' => $object->id]);
  }

  /**
   * Delegate to the edit form for this entity.
   *
   * @param int $mode form mode (ADD, UPDATE, DELETE, VIEW)
   * @param int|null $id record ID
   * @param bool $imageUpload whether the form handles image uploads
   * @param bool $pushUserContext whether to push to the user context stack
   *
   * @return void
   */
  public function edit($mode, $id = NULL, $imageUpload = FALSE, $pushUserContext = TRUE) {
    $controller = new CRM_Core_Controller_Simple($this->editForm(), $this->editName(), $mode, $imageUpload);

    // set the userContext stack
    if ($pushUserContext) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url($this->userContext($mode), $this->userContextParams($mode)));
    }
    if ($id !== NULL) {
      $controller->set('id', $id);
    }
    $controller->set('BAOName', $this->getBAOName());
    $this->addValues($controller);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }
}
