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

class CRM_Report_BAO_Instance extends CRM_Report_DAO_Instance {

  /**
   * Delete a report instance by ID.
   *
   * @param int|null $id The ID of the report instance to delete.
   *
   * @return bool TRUE on success, FALSE otherwise.
   */
  public static function deleteInstance($id = NULL) {
    $dao = new CRM_Report_DAO_Instance();
    $dao->id = $id;
    return $dao->delete();
  }

  /**
   * Retrieve a report instance and populate defaults array.
   *
   * @param array $params Key-value pairs used to find the instance (e.g. ['id' => 1]).
   * @param array &$defaults Output array populated with the instance field values.
   *
   * @return CRM_Report_DAO_Instance|null The DAO object if found, NULL otherwise.
   */
  public static function retrieve($params, &$defaults) {
    $instance = new CRM_Report_DAO_Instance();
    $instance->copyValues($params);

    if ($instance->find(TRUE)) {
      CRM_Core_DAO::storeValues($instance, $defaults);
      $instance->free();
      return $instance;
    }
    return NULL;
  }
}
