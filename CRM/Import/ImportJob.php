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



/**
 * This class acts like a psuedo-BAO for transient import job tables
 */
class CRM_Import_ImportJob {
  /**
   * default segementation of import job
   */
  CONST BATCH_THRESHOLD = 2000, BATCH_LIMIT = 2000;

  CONST TABLE_PREFIX = 'civicrm_import_job';

  protected $_tableName;
  protected $_primaryKeyName;
  protected $_statusFieldName;

  protected $_invalidRowCount;
  protected $_conflictRowCount;
  protected $_onDuplicate;

  protected $_mapper;
  protected $_mapperKeys;
  protected $_mapperLocTypes;
  protected $_mapperPhoneTypes;
  protected $_mapperImProviders;
  protected $_mapperWebsiteTypes;

  protected $_mapFields;

  protected $_parser;

  public function __construct($tableName = NULL, $createSql = NULL, $createTable = FALSE) {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    if ($createTable) {
      if (!$createSql) {
        CRM_Core_Error::fatal('Either an existing table name or an SQL query to build one are required');
      }

      // FIXME: we should regen this table's name if it exists rather than drop it
      if (!$tableName) {
        $tableName = str_replace('.', '_', microtime(TRUE));
        $tableName = self::TABLE_PREFIX.'_' . $tableName;
      }
      $db->query("DROP TABLE IF EXISTS $tableName");
      $db->query("CREATE TABLE $tableName ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci $createSql");
    }

    if (!$tableName) {
      CRM_Core_Error::fatal('Import Table is required.');
    }

    $this->_tableName = $tableName;
  }

  public function getTableName() {
    return $this->_tableName;
  }

  public function isComplete($dropIfComplete = FALSE) {
    if (!$this->_statusFieldName) {
      CRM_Core_Error::fatal("Could not get name of the import status field");
    }
    $query = "SELECT * FROM $this->_tableName WHERE $this->_statusFieldName = %1 LIMIT 1";
    $result = CRM_Core_DAO::executeQuery($query, array(
      1 => array(CRM_Import_Parser::PENDING, 'Integer'),
    ));
    if ($result->fetch()) {
      return FALSE;
    }
    if ($dropIfComplete) {
      $query = "DROP TABLE $this->_tableName";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  public function setJobParams(&$params) {
    foreach ($params as $param => $value) {
      $index = "_".$param;
      $this->$index = $value;
    }
  }

  public function setFormVariables($form) {
    $this->_parser->set($form, CRM_Import_Parser::MODE_IMPORT);
  }

  public static function getIncompleteImportTables() {
    $dao = new CRM_Core_DAO();
    $database = $dao->database();
    $tablePrefix = CRM_Import_ImportJob::TABLE_PREFIX;
    $query = "SELECT   TABLE_NAME FROM INFORMATION_SCHEMA
                  WHERE    TABLE_SCHEMA = ? AND
                           TABLE_NAME LIKE '{$tablePrefix}_%'
                  ORDER BY TABLE_NAME";
    $result = CRM_Core_DAO::executeQuery($query, array($database));
    $incompleteImportTables = array();
    /* #24589
    // Very confuse code here
    // this will trigger "Using $this when not in object context error"
    // and never get incomplete import table because lack of status field
    while ($importTable = $result->fetch()) {
      if (!$this->isComplete($importTable)) {
        $incompleteImportTables[] = $importTable;
      }
    }
    */
    return $incompleteImportTables;
  }
}