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
 * Page for displaying list of Option Groups
 */
class CRM_Admin_Page_OptionGroup extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_OptionGroup';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::BROWSE => array(
          'name' => ts('Multiple Choice Options'),
          'url' => 'civicrm/admin/optionValue',
          'qs' => 'reset=1&action=browse&gid=%%id%%',
          'title' => ts('View and Edit Multiple Choice Options'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit Group'),
          'url' => 'civicrm/admin/optionGroup',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Option'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionGroup' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Option'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionGroup' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Option'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/optionGroup',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Option'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_OptionGroup';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Options';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/optionGroup';
  }

  /**
   * browse all entities.
   *
   * @param int $action
   *
   * @return void
   * @access public
   */
  function browse() {
    $thisArgs = func_get_args();
    $action = isset($thisArgs[0]) ? $thisArgs[0] : NULL;
    $sort = isset($thisArgs[1]) ? $thisArgs[1] : NULL;
    $links = &$this->links();
    if ($action == NULL) {
      if (!empty($links)) {
        $action = array_sum(array_keys($links));
      }
    }
    if (!CRM_Core_Permission::check('administer neticrm')) {
      $action = CRM_Core_Action::BROWSE;
      $action -= CRM_Core_Action::DISABLE;
    }
    else {
      $this->assign('show_add_link', TRUE);
      if ($action & CRM_Core_Action::DISABLE) {
        $action -= CRM_Core_Action::DISABLE;
      }
      if ($action & CRM_Core_Action::ENABLE) {
        $action -= CRM_Core_Action::ENABLE;
      }
    }

    $baoString = $this->getBAOName();
    $object = new $baoString();

    $values = array();

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
          $values[$object->id] = array();
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
}

