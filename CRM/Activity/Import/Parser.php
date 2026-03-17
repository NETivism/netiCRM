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
 * Base parser for validating and processing Activity import data
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

abstract class CRM_Activity_Import_Parser {
  public const MAX_ERRORS = 250, MAX_WARNINGS = 25, VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128;

  /**
   * various parser modes
   */
  public const MODE_MAPFIELD = 1, MODE_PREVIEW = 2, MODE_SUMMARY = 4, MODE_IMPORT = 8;

  /**
   * codes for duplicate record handling
   */
  public const DUPLICATE_SKIP = 1, DUPLICATE_REPLACE = 2, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  public const ERROR_FILE_PREFIX = 'activity';

  /**#@+
   * @var integer
   */

  /**
   * imported file size
   */
  protected $_fileSize;

  /**
   * seperator being used
   */
  protected $_seperator;

  /**
   * total number of lines in file
   */
  protected $_lineCount;

  /**
   * total number of non empty lines
   */
  protected $_totalCount;

  /**
   * running total number of valid lines
   */
  protected $_validCount;

  /**
   * running total number of invalid rows
   */
  protected $_invalidRowCount;

  /**
   * maximum number of invalid rows to store
   */
  protected $_maxErrorCount;

  /**
   * array of error lines, bounded by MAX_ERROR
   */
  protected $_errors;

  /**
   * total number of conflict lines
   */
  protected $_conflictCount;

  /**
   * array of conflict lines
   */
  protected $_conflicts;

  /**
   * total number of duplicate (from database) lines
   */
  protected $_duplicateCount;

  /**
   * array of duplicate lines
   */
  protected $_duplicates;

  /**
   * running total number of warnings
   */
  protected $_warningCount;

  /**
   * maximum number of warnings to store
   */
  protected $_maxWarningCount = self::MAX_WARNINGS;

  /**
   * array of warning lines, bounded by MAX_WARNING
   */
  protected $_warnings;

  /**
   * array of all the fields that could potentially be part
   * of this import process
   * @var array
   */
  protected $_fields;

  /**
   * array of the fields that are actually part of the import process
   * the position in the array also dictates their position in the import
   * file
   * @var array
   */
  protected $_activeFields;

  /**
   * cache the count of active fields
   *
   * @var int
   */
  protected $_activeFieldCount;

  /**
   * maximum number of non-empty/comment lines to process
   *
   * @var int
   */
  protected $_maxLinesToProcess;

  /**
   * cache of preview rows
   *
   * @var array
   */
  protected $_rows;

  /**
   * filename of error data
   *
   * @var string
   */
  protected $_errorFileName;

  /**
   * filename of conflict data
   *
   * @var string
   */
  protected $_conflictFileName;

  /**
   * filename of duplicate data
   *
   * @var string
   */
  protected $_duplicateFileName;

  /**
   * whether the file has a column header or not
   *
   * @var boolean
   */
  protected $_haveColumnHeader;
  /**
   * class constructor
   */
  public function __construct() {
    $this->_maxLinesToProcess = 0;
    $this->_maxErrorCount = self::MAX_ERRORS;
  }

  abstract public function init();

  /**
   * Run the parser
   *
   * @param array $fileName
   * @param string $seperator
   * @param array $mapper
   * @param bool $skipColumnHeader
   * @param int $mode
   * @param int $onDuplicate
   * @param string $filenamePrefix
   *
   * @return mixed
   */
  public function run(
    $fileName,
    $seperator,
    &$mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW,
    $onDuplicate = self::DUPLICATE_SKIP,
    $filenamePrefix = self::ERROR_FILE_PREFIX
  ) {
    if (!is_array($fileName)) {
      CRM_Core_Error::fatal('Empty file array');
    }
    $fileName = $fileName['name'];

    $this->init();

    $this->_haveColumnHeader = $skipColumnHeader;

    $this->_seperator = $seperator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = $this->_warningCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_conflicts = [];

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = [];
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    while (!feof($fd)) {
      $this->_lineCount++;

      $values = fgetcsv($fd, 8192, $seperator);
      if (!$values) {
        continue;
      }
      if (count(array_filter($values)) === 0) {
        continue;
      }

      self::encloseScrub($values);

      // skip column header if we're not in mapfield mode
      if ($mode != self::MODE_MAPFIELD && $skipColumnHeader) {
        $skipColumnHeader = FALSE;
        continue;
      }

      /* trim whitespace around the values */

      $empty = TRUE;
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }

      if (CRM_Utils_System::isNull($values)) {
        continue;
      }

      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = $this->mapField($values);
      }
      elseif ($mode == self::MODE_PREVIEW) {
        $returnCode = $this->preview($values);
      }
      elseif ($mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        $returnCode = $this->import($onDuplicate, $values);
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode & self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode & self::WARNING) {
        $this->_warningCount++;
        if ($this->_warningCount < $this->_maxWarningCount) {
          $this->_warningCount[] = $line;
        }
      }

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        if ($this->_invalidRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        if ($returnCode & self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
                     * on non-skip action */
        }
        $this->_duplicateCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode & self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }
    }

    fclose($fd);

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;
      $filenamePrefix = self::ERROR_FILE_PREFIX.'_'.date('YmdHis', CRM_REQUEST_TIME);

      $customfields = &CRM_Core_BAO_CustomField::getFields('Activity');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }
      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(
          [ts('Line Number'),
            ts('Reason'),
          ],
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR, $filenamePrefix);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }
      if ($this->_conflictCount) {
        $headers = array_merge(
          [ts('Line Number'),
            ts('Reason'),
          ],
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT, $filenamePrefix);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(
          [ts('Line Number'),
            ts('View Activity History URL'),
          ],
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE, $filenamePrefix);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
    return $this->fini();
  }

  abstract public function mapField(&$values);
  abstract public function preview(&$values);
  abstract public function summary(&$values);
  abstract public function import($onDuplicate, &$values);

  abstract public function fini();

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array $fieldKeys mapped array of values
   *
   * @return void
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Activity_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /*function setActiveFieldLocationTypes( $elements )
    {
        for ($i = 0; $i < count( $elements ); $i++) {
            $this->_activeFields[$i]->_hasLocationType = $elements[$i];
        }
    }

    function setActiveFieldPhoneTypes( $elements )
    {
        for ($i = 0; $i < count( $elements ); $i++) {
            $this->_activeFields[$i]->_phoneType = $elements[$i];
        }
    }*/

  /**
   * Set active field values
   *
   * @param array $elements
   * @param int $erroneousField
   *
   * @return int
   */
  public function setActiveFieldValues($elements, &$erroneousField) {
    $maxCount = count($elements) < $this->_activeFieldCount ? count($elements) : $this->_activeFieldCount;
    for ($i = 0; $i < $maxCount; $i++) {
      $this->_activeFields[$i]->setValue($elements[$i]);
    }

    // reset all the values that we did not have an equivalent import element
    for (; $i < $this->_activeFieldCount; $i++) {
      $this->_activeFields[$i]->resetValue();
    }

    // now validate the fields and return false if error
    $valid = self::VALID;
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (!$this->_activeFields[$i]->validate()) {
        // no need to do any more validation
        $erroneousField = $i;
        $valid = self::ERROR;
        break;
      }
    }
    return $valid;
  }

  /**
   * function to format the field values for input to the api
   *
   * @return array (reference ) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = [];
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)
        && !isset($params[$this->_activeFields[$i]->_name])
        && !isset($this->_activeFields[$i]->_related)
      ) {

        $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
      }
    }
    return $params;
  }

  /**
   * Get select values
   *
   * @return array
   */
  public function getSelectValues() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_title;
    }
    return $values;
  }

  /**
   * Get select types
   *
   * @return array
   */
  public function getSelectTypes() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_hasLocationType;
    }
    return $values;
  }

  /**
   * Get header patterns
   *
   * @return array
   */
  public function getHeaderPatterns() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_headerPattern;
    }
    return $values;
  }

  /**
   * Get data patterns
   *
   * @return array
   */
  public function getDataPatterns() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_dataPattern;
    }
    return $values;
  }

  /**
   * Add field
   *
   * @param string $name
   * @param string $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   *
   * @return void
   */
  public function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Activity_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {

      $tempField = CRM_Contact_BAO_Contact::importableFields('Individual', NULL);
      if (!CRM_Utils_Array::arrayKeyExists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Activity_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
      }
      else {

        $this->_fields[$name] = new CRM_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $tempField[$name]['hasLocationType']);
      }
    }
  }

  /**
   * setter function
   *
   * @param int $max
   *
   * @return void
   */
  public function setMaxLinesToProcess($max) {
    $this->_maxLinesToProcess = $max;
  }

  /**
   * Store parser values
   *
   * @param object $store
   * @param int $mode
   *
   * @return void
   */
  public function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('lineCount', $this->_lineCount);
    $store->set('seperator', $this->_seperator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
    }
    if ($this->_conflictCount) {
      $store->set('conflictsFileName', $this->_conflictFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
  }

  /**
   * Export data to a CSV file
   *
   * @param string $fileName
   * @param array $header
   * @param array $data
   *
   * @return void
   */
  public static function exportCSV($fileName, $header, $data) {
    CRM_Core_Report_Excel::writeExcelFile($fileName, $header, $data, $download = FALSE);
  }

  /**
   * Remove single-quote enclosures from a value array (row)
   *
   * @param array $values
   * @param string $enclosure
   *
   * @return void
   */
  public static function encloseScrub(&$values, $enclosure = "'") {
    if (empty($values)) {
      return;
    }

    foreach ($values as $k => $v) {
      $values[$k] = preg_replace("/^$enclosure(.*) $enclosure$/", '$1', $v);
    }
  }

  /**
   * Get error filename
   *
   * @param int $type
   * @param string $prefix
   *
   * @return string
   */
  public function errorFileName($type, $prefix) {
    return CRM_Import_Parser::saveFileName($type, $prefix);
  }
}
