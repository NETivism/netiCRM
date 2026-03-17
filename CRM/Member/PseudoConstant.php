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
 * This class holds all the Pseudo constants that are specific to the civimember component. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Member_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * membership types
   * @var array
   */
  private static $membershipType;

  /**
   * membership types
   * @var array
   */
  private static $membershipStatus;

  /**
   * Get all the membership types.
   *
   * @param int|null $id
   * @param bool $force
   *
   * @return array|string|null - array reference of all membership types if any
   */
  public static function &membershipType($id = NULL, $force = FALSE) {
    if (!self::$membershipType || $force) {
      CRM_Core_PseudoConstant::populate(
        self::$membershipType,
        'CRM_Member_DAO_MembershipType',
        FALSE,
        'name',
        'is_active',
        NULL,
        'weight'
      );
    }
    if ($id) {
      if (CRM_Utils_Array::arrayKeyExists($id, self::$membershipType)) {
        return self::$membershipType[$id];
      }
      else {
        $result = NULL;
        return $result;
      }
    }
    return self::$membershipType;
  }

  /**
   * Get all the membership status.
   *
   * @param int|null $id
   * @param string|null $cond
   * @param string $column
   *
   * @return array|string|null - array reference of all membership statuss if any
   */
  public static function &membershipStatus($id = NULL, $cond = NULL, $column = 'name') {
    if (self::$membershipStatus === NULL) {
      self::$membershipStatus = [];
    }

    $cacheKey = $column;
    if ($cond) {
      $cacheKey .= "_{$cond}";
    }
    if (!isset(self::$membershipStatus[$cacheKey])) {
      CRM_Core_PseudoConstant::populate(
        self::$membershipStatus[$cacheKey],
        'CRM_Member_DAO_MembershipStatus',
        FALSE,
        $column,
        'is_active',
        $cond,
        'weight'
      );
    }

    $value = NULL;
    if ($id) {
      $value = CRM_Utils_Array::value($id, self::$membershipStatus[$cacheKey]);
    }
    else {
      $value = self::$membershipStatus[$cacheKey];
    }

    return $value;
  }

  /**
   * Alias of membershipStatus for getoptions API.
   *
   * @param int|null $id
   * @param string|null $cond
   * @param string $column
   *
   * @return array|string|null - array reference of all membership statuss if any
   */
  public static function &status($id = NULL, $cond = NULL, $column = 'label') {
    return self::membershipStatus($id, $cond, $column);
  }
}
