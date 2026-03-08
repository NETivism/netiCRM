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
 * SOAP API-specific permission checking implementation
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 *
 */
class CRM_Core_Permission_Soap {

  /**
   * Get the current permission of this user.
   *
   * @return int The permission level.
   */
  public static function getPermission() {
    return CRM_Core_Permission::EDIT;
  }

  /**
   * Get the permissioned where clause for the user.
   *
   * @param int $type The type of permission needed.
   * @param array $tables (reference) The tables that are needed for the select clause.
   * @param array $whereTables (reference) The tables that are needed for the where clause.
   *
   * @return string The group where clause for this user.
   */
  public static function whereClause($type, &$tables, &$whereTables) {
    return '( 1 )';
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user.
   *
   * @param string|null $groupType Type of group.
   *
   * @return array List of all groups.
   */
  public static function &group($groupType = NULL) {
    return CRM_Core_PseudoConstant::allGroup($groupType);
  }

  /**
   * Given a permission string, check for access requirements.
   *
   * @param string $str The permission to check.
   *
   * @return bool True if yes, else false.
   */
  public static function check($str) {
    return TRUE;
  }
}
