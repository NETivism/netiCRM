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

class CRM_Core_BAO_Persistent extends CRM_Core_DAO_Persistent {

  /**
   * Retrieve a persistent record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_DAO_Persistent|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Core_DAO_Persistent();
    $dao->copyValues($params);

    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      if (CRM_Utils_Array::value('is_config', $defaults) == 1) {
        $defaults['data'] = unserialize($defaults['data']);
      }
      return $dao;
    }
    return NULL;
  }

  /**
   * Add or update a persistent record.
   *
   * @param array $params associative array of persistent data
   * @param array $ids associative array containing 'persistent' ID if updating
   *
   * @return CRM_Core_DAO_Persistent the created/updated persistent object
   */
  public static function add(&$params, &$ids) {
    if (CRM_Utils_Array::value('is_config', $params) == 1) {
      $params['data'] = serialize(explode(',', $params['data']));
    }
    $persistentDAO = new CRM_Core_DAO_Persistent();
    $persistentDAO->copyValues($params);

    $persistentDAO->id = CRM_Utils_Array::value('persistent', $ids);
    $persistentDAO->save();
    return $persistentDAO;
  }

  /**
   * Retrieve persistent values associated with a specific context.
   *
   * @param string $context the context name
   * @param string|null $name optional specific name within the context
   *
   * @return mixed the persistent data (string, array, or associative array of all context data)
   */
  public static function getContext($context, $name = NULL) {
    static $contextNameData = [];

    if (!CRM_Utils_Array::arrayKeyExists($context, $contextNameData)) {
      $contextNameData[$context] = [];
      $persisntentDAO = new CRM_Core_DAO_Persistent();
      $persisntentDAO->context = $context;
      $persisntentDAO->find();

      while ($persisntentDAO->fetch()) {
        $contextNameData[$context][$persisntentDAO->name] = $persisntentDAO->is_config == 1 ? unserialize($persisntentDAO->data) : $persisntentDAO->data;
      }
    }
    if (empty($name)) {
      return $contextNameData[$context];
    }
    else {
      return CRM_Utils_Array::value($name, $contextNameData[$context]);
    }
  }
}
