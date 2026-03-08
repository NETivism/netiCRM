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

/**
 * Page for displaying list of location types
 */
class CRM_Admin_Page_PreferencesDate extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  public static $_links = NULL;

  /**
   * Gets the BAO name.
   *
   * @return string Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_PreferencesDate';
  }

  /**
   * Gets the action links.
   *
   * @return array (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/setting/preferences/date',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Date Type'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Runs the page.
   *
   * @return void
   */
  public function run() {
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - Date Preferences'));
    return parent::run();
  }

  /**
   * Gets the name of the edit form.
   *
   * @return string Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_PreferencesDate';
  }

  /**
   * Gets the edit form name.
   *
   * @return string name of this page.
   */
  public function editName() {
    return 'Date Preferences';
  }

  /**
   * Gets user context.
   *
   * @param string|null $mode
   *
   * @return string
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/setting/preferences/date';
  }
}
