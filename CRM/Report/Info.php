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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Report_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'report';

  /**
   * Returns component metadata for CiviReport.
   *
   * @return array Associative array with keys: name, translatedName, title, search, showActivitiesInCore.
   */
  public function getInfo() {
    return ['name' => 'CiviReport',
      'translatedName' => ts('CiviReport'),
      'title' => 'CiviCRM Report Engine',
      'search' => 0,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * Returns the list of permissions defined by the CiviReport component.
   *
   * @return string[] Array of permission string identifiers.
   */
  public function getPermissions() {
    return ['access CiviReport', 'access Report Criteria', 'administer Reports'];
  }

  /**
   * Returns dashboard element definition. CiviReport has no dashboard element.
   *
   * @return null
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * Returns dashboard object. CiviReport has no dashboard object.
   *
   * @return null
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * Returns contact record tab definition. CiviReport does not use contact tabs.
   *
   * @return null
   */
  public function registerTab() {
    // this component doesn't use contact record tabs
    return NULL;
  }

  /**
   * Returns advanced search pane definition. CiviReport does not use advanced search panes.
   *
   * @return null
   */
  public function registerAdvancedSearchPane() {
    // this component doesn't use advanced search
    return NULL;
  }

  /**
   * Returns activity type definitions. CiviReport does not define activity types.
   *
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * Adds shortcut links to the Create New navigation menu. CiviReport has no shortcuts.
   *
   * @param array &$shortCuts Reference to the array of shortcut definitions to populate.
   *
   * @return void
   */
  public function creatNewShortcut(&$shortCuts) {
  }
}
