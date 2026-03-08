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

class CRM_Core_BAO_PreferencesDate extends CRM_Core_DAO_PreferencesDate {

  /**
   * static holder for the default LT
   */
  public static $_defaultPreferencesDate = NULL;

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve a date preference record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_DAO_PreferencesDate|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Core_DAO_PreferencesDate();
    $dao->copyValues($params);
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      return $dao;
    }
    return NULL;
  }

  /**
   * Update the is_active flag for a date preference record.
   *
   * Note: This method currently calls fatal() and is not implemented.
   *
   * @param int $id database record ID
   * @param bool $is_active value to set for the is_active field
   *
   * @return void
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::fatal();
  }

  /**
   * Delete a date preference record.
   *
   * Note: This method currently calls fatal() and is not implemented.
   *
   * @param int $id record ID
   *
   * @return void
   */
  public static function del($id) {
    CRM_Core_Error::fatal();
  }
}
