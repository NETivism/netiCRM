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

require_once 'CRM/Import/DataSource.php';
class CRM_Import_DataSource_CSV extends CRM_Import_DataSource {
  CONST NUM_ROWS_TO_INSERT = 100;
  function getInfo() {
    return array('title' => ts('Comma-Separated Values (CSV)'));
  }

  public static function preProcess(&$form) {}

  public static function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_CSV');

    $config = CRM_Core_Config::singleton();

    // FIXME: why do we limit the file size to 8 MiB if it's larger in config?
    $uploadFileSize = $config->maxImportFileSize >= 8388608 ? 8388608 : $config->maxImportFileSize;
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
    $form->assign('uploadSize', $uploadSize);
    $form->add('file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=60', TRUE);

    $form->setMaxFileSize($uploadFileSize);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize);
    $form->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');
    $form->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');

    $form->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));
  }

  public static function postProcess(&$form, &$params, &$db) {
    $file = $params['uploadFile']['name'];

    $result = self::_CsvToTable($db, $file, $params['skipColumnHeader'],
      CRM_Utils_Array::value('import_table_name', $params)
    );

    $form->set('originalColHeader', CRM_Utils_Array::value('original_col_header', $result));

    $table = $result['import_table_name'];
    $importJob = new CRM_Import_ImportJob($table);
    $tableName = $importJob->getTableName();
    $form->set('importTableName', $tableName);

    $fields = parent::prepareImportTable($tableName);
    $form->set('primaryKeyName', $fields['primaryKeyName']);
    $form->set('statusFieldName', $fields['statusFieldName']);
  }

  /**
   * Create a table that matches the CSV file and populate it with the file's contents
   *
   * @param object $db     handle to the database connection
   * @param string $file   file name to load
   * @param bool   $headers  whether the first row contains headers
   * @param string $table  Name of table from which data imported.
   *
   * @return string  name of the created table
   */
  private static function _CsvToTable(&$db, $file, $headers = FALSE, $table = NULL) {
    $result = array();
    $fd = fopen($file, 'r');
    if (!$fd) {
      CRM_Core_Error::fatal("Could not read $file");
    }

    $config = CRM_Core_Config::singleton();
    $firstrow = fgetcsv($fd, 0, $config->fieldSeparator);

    // create the column names from the CSV header or as col_0, col_1, etc.
    if ($headers) {
      //need to get original headers.
      $result['original_col_header'] = $firstrow;

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $columns = array_map($strtolower, $firstrow);
      $columns = str_replace(' ', '_', $columns);
      $columns = preg_replace('/[^a-z_]/', '', $columns);

      // need to take care of null as well as duplicate col names.
      $duplicateColName = FALSE;
      if (count($columns) != count(array_unique($columns))) {
        $duplicateColName = TRUE;
      }

      if (in_array('', $columns) || $duplicateColName) {
        foreach ($columns as $colKey => & $colName) {
          if (!$colName) {
            $colName = "col_$colKey";
          }
          elseif ($duplicateColName) {
            $colName .= "_$colKey";
          }
        }
      }

      // CRM-4881: we need to quote column names, as they may be MySQL reserved words
      foreach ($columns as & $column) $column = "`$column`";
    }
    else {
      $columns = array();
      foreach ($firstrow as $i => $_) $columns[] = "col_$i";
    }

    // FIXME: we should regen this table's name if it exists rather than drop it
    if (!$table) {
      $tableName = str_replace('.', '_', microtime(TRUE));
      $table = CRM_Import_ImportJob::TABLE_PREFIX.'_' . $tableName;
    }
    elseif (strpos($table, CRM_Import_ImportJob::TABLE_PREFIX.'_') === 0) {
      $db->query("DROP TABLE IF EXISTS $table");
    }

    $numColumns = count($columns);
    $create = "CREATE TABLE $table (" . CRM_Utils_Array::implode(' text, ', $columns) . " text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $db->query($create);

    // the proper approach, but some MySQL installs do not have this enabled
    // $load = "LOAD DATA LOCAL INFILE '$file' INTO TABLE $table FIELDS TERMINATED BY '$config->fieldSeparator' OPTIONALLY ENCLOSED BY '\"'";
    // if ($headers) {   $load .= ' IGNORE 1 LINES'; }
    // $db->query($load);

    // parse the CSV line by line and build one big INSERT (while MySQL-escaping the CSV contents)
    if (!$headers) {
      rewind($fd);
    }

    $sql = NULL;
    $first = TRUE;
    $count = 0;
    while ($row = fgetcsv($fd, 0, $config->fieldSeparator)) {
      // skip rows that dont match column count, else we get a sql error
      if (count($row) != $numColumns) {
        continue;
      }
      if (count(array_filter($row)) === 0) {
        continue;
      }

      if (!$first) {
        $sql .= ', ';
      }

      $first = FALSE;
      $row = array_map(array('CRM_Core_DAO', 'escapeString'), $row);
      $sql .= "('" . CRM_Utils_Array::implode("', '", $row) . "')";
      $count++;

      if ($count >= self::NUM_ROWS_TO_INSERT && !empty($sql)) {
        $sql = "INSERT IGNORE INTO $table VALUES $sql";
        $db->query($sql);

        $sql = NULL;
        $first = TRUE;
        $count = 0;
      }
    }

    if (!empty($sql)) {
      $sql = "INSERT IGNORE INTO $table VALUES $sql";
      $db->query($sql);
    }

    fclose($fd);

    //get the import tmp table name.
    $result['import_table_name'] = $table;

    return $result;
  }
}
