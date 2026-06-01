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
 * This is a part of CiviCRM extension management functionality.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * This page displays the list of extensions registered in the system.
 */
class CRM_Admin_Page_Extensions extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  public static $_links = NULL;

  public static $_extInstalled = NULL;

  public static $_extNotInstalled = NULL;

  /**
   * Obtains the group name from URL and sets the title.
   *
   * @return void
   */
  public function preProcess() {

    $ext = new CRM_Core_Extensions();
    if ($ext->enabled === TRUE) {
      self::$_extInstalled = $ext->getInstalled(TRUE);
      self::$_extNotInstalled = $ext->getNotInstalled();
    }
    CRM_Utils_System::setTitle(ts('CiviCRM Extensions'));
  }

  /**
   * Gets the BAO name.
   *
   * @return string Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_OptionValue';
  }

  /**
   * Gets the action links.
   *
   * @return array (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::ADD => [
          'name' => ts('Install'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=add&id=%%id%%&key=%%key%%',
          'title' => ts('Install'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( \'%%id%%\',\'' . 'CRM_Core_Extensions' . '\',\'' . 'disable-enable' . '\',\'' . 'true' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( \'%%id%%\',\'' . 'CRM_Core_Extensions' . '\',\'' . 'enable-disable' . '\',\'' . 'true' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Uninstall'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=delete&id=%%id%%&key=%%key%%',
          'title' => ts('Uninstall Extension'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Runs the basic page.
   *
   * @return void
   */
  public function run() {
    $this->preProcess();
    parent::run();
  }

  /**
   * Browses all extensions.
   *
   * @return void
   */
  public function browse() {

    $this->assign('extEnabled', FALSE);
    if (self::$_extInstalled) {
      $this->assign('extEnabled', TRUE);

      // convert objects to arrays for handling in the template
      $rows = [];
      foreach (self::$_extInstalled as $id => $obj) {
        $rows[$id] = (array) $obj;
        if ($obj->is_active) {
          $action = CRM_Core_Action::DISABLE;
        }
        else {
          $action = array_sum(array_keys($this->links()));
          $action -= CRM_Core_Action::DISABLE;
          $action -= CRM_Core_Action::ADD;
        }
        $rows[$id]['action'] = CRM_Core_Action::formLink(
          self::links(),
          $action,
          ['id' => $id,
            'key' => $obj->key,
          ]
        );
      }
      $this->assign('rows', $rows);
    }

    if (self::$_extNotInstalled) {
      $this->assign('extEnabled', TRUE);
      $rowsUpl = [];
      foreach (self::$_extNotInstalled as $id => $obj) {
        $rowsUpl[$id] = (array) $obj;
        $action = array_sum(array_keys($this->links()));
        $action -= CRM_Core_Action::DISABLE;
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::DELETE;
        $rowsUpl[$id]['action'] = CRM_Core_Action::formLink(
          self::links(),
          $action,
          ['id' => $id,
            'key' => $obj->key,
          ]
        );
      }
      $this->assign('rowsUploaded', $rowsUpl);
    }
  }

  /**
   * Gets the name of the edit form.
   *
   * @return string Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Gets the edit form name.
   *
   * @return string name of this page.
   */
  public function editName() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Gets user context.
   *
   * @param string|null $mode
   *
   * @return string
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/extensions';
  }

  /**
   * Gets user context params.
   *
   * @param string|null $mode
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }
}
