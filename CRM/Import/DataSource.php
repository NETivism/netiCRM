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
 | Version 3, 19 November 2009.                                       |
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

require_once 'CRM/Core/Form.php';
#require_once 'CRM/Import/Parser/Contact.php';

/**
 * This class defines the DataSource interface but must be subclassed to be
 * useful.
 */
abstract class CRM_Import_DataSource {

  /**
   * Provides information about the data source
   *
   * @return array collection of info about this data source
   *
   * @access public
   *
   */
  abstract public function getInfo();

  /**
   * Function to set variables up before form is built
   *
   * @access public
   */
  abstract public static function preProcess(&$form);

  /**
   * This is function is called by the form object to get the DataSource's
   * form snippet. It should add all fields necesarry to get the data
   * uploaded to the temporary table in the DB.
   *
   * @return None (operates directly on form argument)
   * @access public
   */
  abstract public static function buildQuickForm(&$form);

  /**
   * Function to process the form
   *
   * @access public
   */
  abstract public static function postProcess(&$form, &$params, &$db);

  public function checkPermission() {
    $info = $this->getInfo();
    return empty($info['permissions']) || CRM_Core_Permission::check($info['permissions']);
  }

  public static function prepareImportTable($tableName, $statusFieldName = '_status', $primaryKeyName = '_id') {
    $alterQuery = "ALTER TABLE $tableName
      ADD COLUMN $statusFieldName INT DEFAULT 0 NOT NULL,
      ADD COLUMN ${statusFieldName}Msg TEXT,
      ADD COLUMN $primaryKeyName INT PRIMARY KEY NOT NULL
      AUTO_INCREMENT";
    CRM_Core_DAO::executeQuery($alterQuery);

    return array('statusFieldName' => $statusFieldName, 'primaryKeyName' => $primaryKeyName);
  }

}

