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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */






abstract class CRM_Import_Parser {
  public $_contactSubType;
  public $_unparsedAddresses;
  CONST MAX_ERRORS = 5000, MAX_WARNINGS = 25;
  CONST PENDING = 0, VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256;

  /**
   * various parser modes
   */
  CONST MODE_MAPFIELD = 1, MODE_PREVIEW = 2, MODE_SUMMARY = 4, MODE_IMPORT = 8;

  /**
   * codes for duplicate record handling
   */
  CONST DUPLICATE_SKIP = 1, DUPLICATE_REPLACE = 2, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * various Contact types
   */
  CONST CONTACT_INDIVIDUAL = 'Individual', CONTACT_HOUSEHOLD = 'Household', CONTACT_ORGANIZATION = 'Organization';

  /**
   * Error file name prefix
   */
  CONST ERROR_FILE_PREFIX = 'contact';

  protected $_tableName;

  /**#@+
   * @access protected
   * @var integer
   */

  /**
   * total number of lines in file
   */
  protected $_rowCount;

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
   * array of error lines
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
   * running total number of un matched Conact
   */
  protected $_unMatchCount;

  /**
   * array of unmatched lines
   */
  protected $_unMatch;

  /**
   * total number of contacts with unparsed addresses
   */
  protected $_unparsedAddressCount;

  /**
   * array of warning lines
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
   * filename of mismatch data
   *
   * @var string
   */
  protected $_misMatchFileName;

  /**
   * filename of unparsed adderss
   *
   * @var string
   */
  protected $_unparsedAddressFileName;

  protected $_primaryKeyName;
  protected $_statusFieldName;

  /**
   * contact type
   *
   * @var int
   */

  public $_contactType;

  /**
   * import job object
   *
   * @var object
   */
  public $_job;

  /**
   * on duplicate
   *
   * @var int
   */
  public $_onDuplicate;

  /**
   * on duplicate check rule group id
   *
   * @var int
   */
  public $_dedupeRuleGroupId;

  /**
   * contact log register message
   *
   * @var int
   */
  public $_contactLog;

  /**
   * import source have column header or not.
   */
  public $_skipColumnHeader;

  /**
   * Status Name for import records
   */
  public static $_statusNames;

  function __construct() {
    $this->_maxLinesToProcess = 0;
  }

  abstract function init();
  function run($tableName,
    &$mapper,
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $primaryKeyName = '_id',
    $statusFieldName = '_status',
    $onDuplicate = self::DUPLICATE_SKIP,
    $statusID = NULL,
    $totalRowCount = NULL,
    $doGeocodeAddress = FALSE,
    $offset = NULL,
    $contactSubType = NULL
  ) {

    $this->_onDuplicate = $onDuplicate;

    switch ($contactType) {
      case CRM_Import_Parser::CONTACT_INDIVIDUAL:
        $this->_contactType = 'Individual';
        break;

      case CRM_Import_Parser::CONTACT_HOUSEHOLD:
        $this->_contactType = 'Household';
        break;

      case CRM_Import_Parser::CONTACT_ORGANIZATION:
        $this->_contactType = 'Organization';
    }

    $this->_contactSubType = $contactSubType;

    $this->init();

    $this->_rowCount = $this->_warningCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_conflicts = [];
    $this->_unparsedAddresses = [];

    $status = '';

    $this->_tableName = $tableName;
    $this->_primaryKeyName = $primaryKeyName;
    $this->_statusFieldName = $statusFieldName;

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = [];
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    if ($mode == self::MODE_IMPORT) {
      //get the key of email field
      foreach ($mapper as $key => $value) {
        if (strtolower($value) == 'email') {
          $emailKey = $key;
          break;
        }
      }
    }

    if ($statusID) {
      $skip = 50;
      // $skip = 1;
      $config = CRM_Core_Config::singleton();
      $statusFile = "{$config->uploadDir}status_{$statusID}.txt";
      $status = "<div class='description'>&nbsp; " . ts('No processing status reported yet.') . "</div>";
      $contents = json_encode([0, $status]);

      file_put_contents($statusFile, $contents);

      $startTimestamp = $currTimestamp = $prevTimestamp = time();
    }

    // get the contents of the temp. import table
    $query = "SELECT * FROM $tableName";
    if ($mode == self::MODE_IMPORT) {
      $query .= " WHERE $statusFieldName = '".self::PENDING."'";
    }
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();
    $result = $db->query($query);

    while ($values = $result->fetchRow(DB_FETCHMODE_ORDERED)) {
      /* trim whitespace around the values */
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }
      if (CRM_Utils_System::isNull($values)) {
        continue;
      }

      $this->_rowCount++;
      $this->_totalCount++;
      $lineNum = $values[count($values) - 1];
      if ($this->_skipColumnHeader) {
        $lineNum++;
      }

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
        //print "Running parser in import mode<br/>\n";
        $returnCode = $this->import($onDuplicate, $values);
        if ($statusID && (($this->_rowCount % $skip) == 0)) {
          $currTimestamp = time();
          $totalTime = ($currTimestamp - $startTimestamp);
          $time = ($currTimestamp - $prevTimestamp);
          $recordsLeft = $totalRowCount - $this->_rowCount;
          if ($recordsLeft < 0) {
            $recordsLeft = 0;
          }
          $estimatedTime = ($recordsLeft / $skip) * $time;
          $estMinutes = floor($estimatedTime / 60);
          $timeFormatted = '';
          if ($estMinutes > 1) {
            $timeFormatted = $estMinutes . ' ' . ts('minutes') . ' ';
            $estimatedTime = $estimatedTime - ($estMinutes * 60);
          }
          $timeFormatted .= round($estimatedTime) . ' ' . ts('seconds');
          $processedPercent = (int )(($this->_rowCount * 100) / $totalRowCount);
          $statusMsg = ts('%1 of %2 records - %3 remaining',
            [1 => $this->_rowCount, 2 => $totalRowCount, 3 => $timeFormatted]
          );
          $status = "
<div class=\"description\">
&nbsp; <strong>{$statusMsg}</strong>
</div>
";

          $contents = json_encode([$processedPercent, $status]);

          file_put_contents($statusFile, $contents);

          $prevTimestamp = $currTimestamp;
        }
        // sleep(1);
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

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        array_unshift($values, $lineNum);
        $this->_errors[] = $values;
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        array_unshift($values, $lineNum);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::NO_MATCH) {
        $this->_unMatchCount++;
        array_unshift($values, $lineNum);
        $this->_unMatch[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        if ($returnCode & self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
                     * on non-skip action */
        }
        $this->_duplicateCount++;
        array_unshift($values, $lineNum);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      if ($returnCode & self::UNPARSED_ADDRESS_WARNING) {
        $this->_unparsedAddressCount++;
        array_unshift($values, $lineNum);
        $this->_unparsedAddresses[] = $values;
      }
      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode & self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_rowCount >= $this->_maxLinesToProcess) {
        break;
      }

      // clean up memory from dao's
      CRM_Core_DAO::freeResult();
    }

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields($this->_contactType);
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }

      $filenamePrefix = str_replace(CRM_Import_ImportJob::TABLE_PREFIX, self::ERROR_FILE_PREFIX, $this->_tableName);
      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(
          [ts('Line Number'), ts('Reason')],
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR, $filenamePrefix);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }
      if ($this->_conflictCount) {
        $headers = array_merge(
          [ts('Line Number'), ts('Reason')],
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT, $filenamePrefix);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(
          [ts('Line Number'), ts('View Contact URL')],
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE, $filenamePrefix);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
      if ($this->_unMatchCount) {
        $headers = array_merge(
          [ts('Line Number'), ts('Reason')],
          $customHeaders
        );

        $this->_misMatchFileName = self::errorFileName(self::NO_MATCH, $filenamePrefix);
        self::exportCSV($this->_misMatchFileName, $headers, $this->_unMatch);
      }
      if ($this->_unparsedAddressCount) {
        $headers = array_merge(
          [ts('Line Number'), ts('Contact Edit URL')],
          $customHeaders
        );
        $this->_unparsedAddressFileName = self::errorFileName(self::UNPARSED_ADDRESS_WARNING, $filenamePrefix);
        self::exportCSV($this->_unparsedAddressFileName, $headers, $this->_unparsedAddresses);
      }
    }
    return $this->fini();
  }

  abstract function mapField(&$values);
  abstract function preview(&$values);
  abstract function summary(&$values);
  abstract function import($onDuplicate, &$values);

  abstract function fini();

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array mapped array of values
   *
   * @return void
   * @access public
   */
  function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  function setActiveFieldValues($elements) {
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
        $valid = self::ERROR;
        break;
      }
    }
    return $valid;
  }

  function setActiveFieldLocationTypes($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_hasLocationType = $elements[$i];
      }
    }
  }

  function setActiveFieldPhoneTypes($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_phoneType = $elements[$i];
      }
    }
  }

  function setActiveFieldWebsiteTypes($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_websiteType = $elements[$i];
      }
    }
  }

  /**
   * Function to set IM Service Provider type fields
   *
   * @param array $elements IM service provider type ids
   *
   * @return void
   * @access public
   */
  function setActiveFieldImProviders($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_imProvider = $elements[$i];
      }
    }
  }

  function setActiveFieldRelated($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_related = $elements[$i];
      }
    }
  }

  function setActiveFieldRelatedContactType($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactType = $elements[$i];
      }
    }
  }

  function setActiveFieldRelatedContactDetails($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactDetails = $elements[$i];
      }
    }
  }

  function setActiveFieldRelatedContactLocType($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactLocType = $elements[$i];
      }
    }
  }

  function setActiveFieldRelatedContactPhoneType($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactPhoneType = $elements[$i];
      }
    }
  }

  function setActiveFieldRelatedContactWebsiteType($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactWebsiteType = $elements[$i];
      }
    }
  }

  /**
   * Function to set IM Service Provider type fields for related contacts
   *
   * @param array $elements IM service provider type ids of related contact
   *
   * @return void
   * @access public
   */
  function setActiveFieldRelatedContactImProvider($elements) {
    if (!empty($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_relatedContactImProvider = $elements[$i];
      }
    }
  }

  /**
   * function to format the field values for input to the api
   *
   * @return array (reference ) associative array of name/value pairs
   * @access public
   */
  function &getActiveFieldParams() {
    $params = [];

    //CRM_Core_Error::debug( 'Count', $this->_activeFieldCount );
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if ($this->_activeFields[$i]->_name == 'do_not_import') {
        continue;
      }

      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_hasLocationType)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = [];
          }

          $value = [
            $this->_activeFields[$i]->_name =>
            $this->_activeFields[$i]->_value,
            'location_type_id' =>
            $this->_activeFields[$i]->_hasLocationType,
          ];

          if (isset($this->_activeFields[$i]->_phoneType)) {
            $value['phone_type_id'] = $this->_activeFields[$i]->_phoneType;
          }

          // get IM service Provider type id
          if (isset($this->_activeFields[$i]->_imProvider)) {
            $value['provider_id'] = $this->_activeFields[$i]->_imProvider;
          }

          $params[$this->_activeFields[$i]->_name][] = $value;
        }
        elseif (isset($this->_activeFields[$i]->_websiteType)) {
          $value = [$this->_activeFields[$i]->_name => $this->_activeFields[$i]->_value,
            'website_type_id' => $this->_activeFields[$i]->_websiteType,
          ];

          $params[$this->_activeFields[$i]->_name][] = $value;
        }

        if (!isset($params[$this->_activeFields[$i]->_name])) {
          if (!isset($this->_activeFields[$i]->_related)) {
            $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
          }
        }

        //minor fix for CRM-4062
        if (isset($this->_activeFields[$i]->_related)) {
          if (!isset($params[$this->_activeFields[$i]->_related])) {
            $params[$this->_activeFields[$i]->_related] = [];
          }

          if (!isset($params[$this->_activeFields[$i]->_related]['contact_type']) && !empty($this->_activeFields[$i]->_relatedContactType)) {
            $params[$this->_activeFields[$i]->_related]['contact_type'] = $this->_activeFields[$i]->_relatedContactType;
          }

          if (isset($this->_activeFields[$i]->_relatedContactLocType) && !empty($this->_activeFields[$i]->_value)) {
            if (!is_array($params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails])) {
              $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails] = [];
            }
            $value = [$this->_activeFields[$i]->_relatedContactDetails => $this->_activeFields[$i]->_value,
              'location_type_id' => $this->_activeFields[$i]->_relatedContactLocType,
            ];

            if (isset($this->_activeFields[$i]->_relatedContactPhoneType)) {
              $value['phone_type_id'] = $this->_activeFields[$i]->_relatedContactPhoneType;
            }

            // get IM service Provider type id for related contact
            if (isset($this->_activeFields[$i]->_relatedContactImProvider)) {
              $value['provider_id'] = $this->_activeFields[$i]->_relatedContactImProvider;
            }

            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails][] = $value;
          }
          elseif (isset($this->_activeFields[$i]->_relatedContactWebsiteType)) {
            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails][] = ['url' => $this->_activeFields[$i]->_value,
              'website_type_id' => $this->_activeFields[$i]->_relatedContactWebsiteType,
            ];
          }
          else {
            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails] = $this->_activeFields[$i]->_value;
          }
        }
      }
    }

    return $params;
  }

  function getSelectValues() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_title;
    }
    return $values;
  }

  function getSelectTypes() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_hasLocationType;
    }
    return $values;
  }

  function getColumnPatterns() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_columnPattern;
    }
    return $values;
  }

  function getDataPatterns() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_dataPattern;
    }
    return $values;
  }

  function addField($name, $title, $type = CRM_Utils_Type::T_INT,
    $headerPattern = '//', $dataPattern = '//',
    $hasLocationType = FALSE
  ) {
    $this->_fields[$name] = new CRM_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
  }

  /**
   * setter function
   *
   * @param int $max
   *
   * @return void
   * @access public
   */
  function setMaxLinesToProcess($max) {
    $this->_maxLinesToProcess = $max;
  }

  /**
   * Store parser values
   *
   * @param CRM_Core_Session $store
   *
   * @return void
   * @access public
   */
  function set(&$store, $mode = self::MODE_SUMMARY) {
    $store->set('rowCount', $this->_rowCount);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('columnPatterns', $this->getColumnPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);
    $store->set('unMatchCount', $this->_unMatchCount);

    switch ($this->_contactType) {
      case 'Individual':
        $store->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
        break;

      case 'Household':
        $store->set('contactType', CRM_Import_Parser::CONTACT_HOUSEHOLD);
        break;

      case 'Organization':
        $store->set('contactType', CRM_Import_Parser::CONTACT_ORGANIZATION);
    }

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
    }
    if ($this->_conflictCount) {
      $store->set('conflictsFileName', $this->_conflictFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($this->_unMatchCount) {
      $store->set('mismatchFileName', $this->_misMatchFileName);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      $store->set('unparsedAddressCount', $this->_unparsedAddressCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
      if ($this->_unparsedAddressCount) {
        $store->set('unparsedAddressFileName', $this->_unparsedAddressFileName);
      }
    }
  }

  /**
   * Export data to a CSV file
   *
   * @param string $filename
   * @param array $header
   * @param data $data
   *
   * @return void
   * @access public
   */
  static function exportCSV($fileName, $header, $data) {
    // remove '_status', '_statusMsg' and '_id' from error file
    $errorValues = [];
    $firstRow = reset($data);
    $colNum = count($firstRow);
    foreach ($data as $rowCount => $values) {
      for($i = 0; $i < $colNum - 3; $i++) {
        $errorValues[$rowCount][$i] = $values[$i];
      }
    }
    $data = $errorValues;

    global $civicrm_batch;
    if (!empty($civicrm_batch)) {
      CRM_Core_Report_Excel::writeExcelFile($fileName, $header, $data, $download = FALSE, $append = TRUE);
    }
    else {
      CRM_Core_Report_Excel::writeExcelFile($fileName, $header, $data, $download = FALSE, $append = FALSE);
    }
  }

  /**
   * Update the record with PK $id in the import database table
   *
   * @param int $id
   * @param array $params
   *
   * @return void
   * @access public
   */
  public function updateImportStatus($id, $params) {
    $statusFieldName = $this->_statusFieldName;
    $primaryKeyName = $this->_primaryKeyName;

    if ($statusFieldName && $primaryKeyName && is_numeric($id)) {
      $msg = !empty($params["{$statusFieldName}Msg"]) ? $params["{$statusFieldName}Msg"] : '';
      $query = "UPDATE {$this->_tableName} SET {$statusFieldName} = %1, {$statusFieldName}Msg = %2 WHERE {$primaryKeyName} = %3";
      CRM_Core_DAO::executeQuery($query, [
        1 => [$params[$statusFieldName], 'String'],
        2 => [$msg, 'String'],
        3 => [$id, 'Integer'],
      ]);
    }
  }

  public static function statusName($status = NULL) {
    if (empty(self::$_statusNames)) {
      self::$_statusNames = [
        self::PENDING => ts('Pending'),
        self::VALID => ts('Records Imported'),
        self::WARNING => ts('Warning'),
        self::ERROR => ts('Invalid Rows (skipped)'),
        self::CONFLICT => ts('Conflicting Rows (skipped)'),
        self::STOP => ts('Stop'),
        self::DUPLICATE => ts('Duplicate Rows'),
        self::MULTIPLE_DUPE => ts('Mutiple Duplicate'),
        self::NO_MATCH => ts('Mismatched Rows (skipped)'),
        self::UNPARSED_ADDRESS_WARNING => ts('Unparsed Address'),
      ];
    }
    if ($status) {
      return self::$_statusNames[$status];
    }
    else {
      return self::$_statusNames;
    }
  }

  /**
   * For internal call
   *
   * @param [type] $type
   * @param [type] $prefix
   * @return void
   */
  public static function errorFileName($type, $prefix) {
    $fileName = self::saveFileName($type, $prefix);
    return $fileName;
  }

  /**
   * Contact, contribution, member, activity, event will call this
   *
   * @param [type] $type
   * @param [type] $prefix
   * @return void
   */
  public static function saveFileName($type, $prefix) {
    if (empty($prefix)) {
      $prefix = 'import_'.date('YmdHis', CRM_REQUEST_TIME);
    }
    $fileName = $prefix;
    switch ($type) {
      case CRM_Import_Parser::ERROR:
        $fileName .= '.errors';
        break;

      case CRM_Import_Parser::CONFLICT:
        $fileName .= '.conflicts';
        break;

      case CRM_Import_Parser::DUPLICATE:
        $fileName .= '.duplicates';
        break;

      case CRM_Import_Parser::NO_MATCH:
        $fileName .= '.mismatch';
        break;

      case CRM_Import_Parser::UNPARSED_ADDRESS_WARNING:
        $fileName .= '.unparsedAddress';
        break;
    }

    return $fileName . '.xlsx';
  }

  /**
   * For download endpoint to retrieve filename
   *
   * @param string $qfKey
   * @param int $type
   * @param string $parserClass
   * @return string
   */
  public static function getImportErrorFilename($qfKey, $type, $parserClass){
    $session = CRM_Core_Session::singleton();
    $scope = 'import-'.$qfKey;
    $name = $parserClass.'-'.$type;
    $filename = $session->get($name, $scope);
    return $filename;
  }

  /**
   * For import form to set error filename to session and return url
   *
   * @param string $qfKey
   * @param int $type
   * @param string $parserClass
   * @param string $filename
   * @return void
   */
  public static function setImportErrorFilenames($qfKey, $urlMap, $parserClass, $prefix, $form){
    $defaultUrlMap = [
      // defaults
      self::ERROR => 'downloadErrorRecordsUrl',
      self::CONFLICT => 'downloadConflictRecordsUrl',
      self::DUPLICATE => 'downloadDuplicateRecordsUrl',
      self::NO_MATCH => 'downloadMismatchRecordsUrl',
      // special in contact
      self::UNPARSED_ADDRESS_WARNING => 'downloadAddressRecordsUrl',
      // special in contribution
      CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR => 'downloadSoftCreditErrorRecordsUrl',
      CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR => 'downloadPledgePaymentErrorRecordsUrl',
      CRM_Contribute_Import_Parser::PCP_ERROR => 'downloadPCPErrorRecordsUrl',
    ];
    $session = CRM_Core_Session::singleton();
    $scope = 'import-'.$qfKey;

    foreach($urlMap as $idx => $type) {
      $type = strtoupper($type);
      if (is_callable([$parserClass, 'errorFileName']) && defined($parserClass.'::'.$type)) {
        $constType = constant($parserClass.'::'.$type);
        if (is_numeric($constType)) {
          $name = $parserClass.'-'.$constType;
          $filename = call_user_func([$parserClass, 'errorFileName'], $constType, $prefix);
          if (!empty($filename)) {
            $session->set($name, $filename, $scope);
          }
          $urlParams = http_build_query([
            'type' => $constType,
            'parser' => $parserClass,
            'qfKey' => $qfKey,
          ], '', '&');
          $tplVarName = is_numeric($idx) ? $defaultUrlMap[$constType] : $idx;
          $form->set($tplVarName, CRM_Utils_System::url('civicrm/export', $urlParams));
        }
      }
    }
  }
}

