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

class CRM_Core_BAO_LocationType extends CRM_Core_DAO_LocationType {

  /**
   * static holder for the default LT
   */
  public static $_defaultLocationType = NULL;

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve a location type record based on the provided parameters.
   *
   * @param array $params associative array of name/value pairs
   * @param array $defaults associative array to hold the flattened values
   *
   * @return CRM_Core_DAO_LocationType|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->copyValues($params);
    if ($locationType->find(TRUE)) {
      CRM_Core_DAO::storeValues($locationType, $defaults);
      return $locationType;
    }
    return NULL;
  }

  /**
   * Update the is_active flag for a location type in the database.
   *
   * @param int $id ID of the database record
   * @param bool $is_active value to set for the is_active field
   *
   * @return CRM_Core_DAO_LocationType|null the updated DAO object
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_LocationType', $id, 'is_active', $is_active);
  }

  /**
   * Retrieve the default location type.
   *
   * @return CRM_Core_DAO_LocationType the default location type object
   */
  public static function &getDefault() {
    if (self::$_defaultLocationType == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultLocationType = self::retrieve($params, $defaults);
    }
    return self::$_defaultLocationType;
  }

  /**
   * Delete a location type and its dependent records (address, phone, email, im).
   *
   * @param int $locationTypeId ID of the location type to be deleted
   *
   * @return void
   */
  public static function del($locationTypeId) {
    $entity = ['address', 'phone', 'email', 'im'];
    //check dependencies
    foreach ($entity as $key) {
      if ($key == 'im') {
        $name = strtoupper($key);
      }
      else {
        $name = ucfirst($key);
      }
      $baoString = 'CRM_Core_BAO_' . $name;
      $object = new $baoString();
      $object->location_type_id = $locationTypeId;
      $object->delete();
    }

    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->id = $locationTypeId;
    $locationType->delete();
  }
}
