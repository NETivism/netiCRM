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






abstract class CRM_Contribute_Import_Parser {
  /**
   * @var mixed[]
   */
  public $_contributionPages;
  public $_tableName;
  public $_primaryKeyName;
  public $_statusFieldName;
  CONST MAX_ERRORS = 250, MAX_WARNINGS = 25;
  CONST PENDING = 0, VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256, SOFT_CREDIT_ERROR = 512, PLEDGE_PAYMENT_ERROR = 1024, PCP_ERROR = 2048;
  CONST SOFT_CREDIT = 65536, PLEDGE_PAYMENT = 131072, PCP = 262144; 

  /**
   * import contact when import contribution
   */
  CONST CONTACT_NOIDCREATE = 100, CONTACT_AUTOCREATE = 101, CONTACT_DONTCREATE = 102;

  /**
   * various parser modes
   */
  CONST
    MODE_MAPFIELD = CRM_Import_Parser::MODE_MAPFIELD,
    MODE_PREVIEW = CRM_Import_Parser::MODE_PREVIEW,
    MODE_SUMMARY = CRM_Import_Parser::MODE_SUMMARY,
    MODE_IMPORT = CRM_Import_Parser::MODE_IMPORT;

  /**
   * codes for duplicate record handling
   */
  CONST
    DUPLICATE_SKIP = CRM_Import_Parser::DUPLICATE_SKIP,
    DUPLICATE_REPLACE = CRM_Import_Parser::DUPLICATE_REPLACE,
    DUPLICATE_UPDATE = CRM_Import_Parser::DUPLICATE_UPDATE,
    DUPLICATE_FILL = CRM_Import_Parser::DUPLICATE_FILL,
    DUPLICATE_NOCHECK = CRM_Import_Parser::DUPLICATE_NOCHECK;

  /**
   * various Contact types
   */
  CONST
    CONTACT_INDIVIDUAL = CRM_Import_Parser::CONTACT_INDIVIDUAL,
    CONTACT_HOUSEHOLD = CRM_Import_Parser::CONTACT_HOUSEHOLD,
    CONTACT_ORGANIZATION = CRM_Import_Parser::CONTACT_ORGANIZATION;

  const ERROR_FILE_PREFIX = 'contribution';

  protected $_fileName;

  /**#@+
   * @access protected
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
   * running total number of valid soft credit rows
   */
  protected $_validSoftCreditRowCount;

  /**
   * running total number of invalid soft credit rows
   */
  protected $_invalidSoftCreditRowCount;

  /**
   * running total number of valid pcp rows
   */
  protected $_validPCPRowCount;

  /**
   * running total number of invalid pcp rows
   */
  protected $_invalidPCPRowCount;

  /**
   * running total number of valid pledge payment rows
   */
  protected $_validPledgePaymentRowCount;

  /**
   * running total number of invalid pledge payment rows
   */
  protected $_invalidPledgePaymentRowCount;

  /**
   * maximum number of invalid rows to store
   */
  protected $_maxErrorCount;

  /**
   * array of error lines, bounded by MAX_ERROR
   */
  protected $_errors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_pledgePaymentErrors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_softCreditErrors;

  /**
   * array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_pcpErrors;

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
   * filename of pledge payment error data
   *
   * @var string
   */
  protected $_pledgePaymentErrorsFileName;

  /**
   * filename of soft credit error data
   *
   * @var string
   */
  protected $_softCreditErrorsFileName;

  /**
   * filename of pcp error data
   *
   * @var string
   */
  protected $_pcpErrorsFileName;

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
   * Dedupe group id for contact matching
   *
   * @var integer 
   */
  public $_dedupeRuleGroupId;

  /**
   * Create contact mode
   *
   * @var integer
   */
  protected $_createContactOption;

  /**
   * import source have column header or not.
   */
  public $_skipColumnHeader;

  /**
   * Status Name for import records
   */
  public static $_statusNames;

  /**
   * contact type
   *
   * @var int
   */

  public $_contactType; function __construct() {
    $this->_maxLinesToProcess = 0;
    $this->_maxErrorCount = self::MAX_ERRORS;
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
    $createContactOption = self::CONTACT_NOIDCREATE,
    $dedupeRuleGroupId = 0
  ) {

    $this->_contactType = $contactType;
    $this->_createContactOption = $createContactOption;
    if (!empty($dedupeRuleGroupId)) {
      $this->_dedupeRuleGroupId = $dedupeRuleGroupId;
    }

    $this->init();

    $this->_rowCount = $this->_warningCount = $this->_validSoftCreditRowCount = $this->_validPledgePaymentRowCount = 0;
    $this->_invalidRowCount = $this->_validCount = $this->_invalidSoftCreditRowCount = $this->_invalidPledgePaymentRowCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_conflicts = [];
    $this->_pledgePaymentErrors = [];
    $this->_softCreditErrors = [];
    $this->_pcpErrors = [];
    $this->_contributionPages = CRM_Contribute_PseudoConstant::contributionPage();

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


    // this is for import progress indicator
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
      $query .= " WHERE $statusFieldName = 'NEW'";
    }
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();
    $result = $db->query($query);

    while ($values = $result->fetchRow(DB_FETCHMODE_ORDERED)) {
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
        $returnCode = $this->import($onDuplicate, $values);

        // this is for import progress indicator
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
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode == self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::SOFT_CREDIT) {
        $this->_validSoftCreditRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::PLEDGE_PAYMENT) {
        $this->_validPledgePaymentRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::PCP) {
        $this->_validPCPRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::ERROR) {
        $this->_invalidRowCount++;
        array_unshift($values, $lineNum);
        $this->_errors[] = $values;
      }

      if ($returnCode == self::PLEDGE_PAYMENT_ERROR) {
        $this->_invalidPledgePaymentRowCount++;
        array_unshift($values, $lineNum);
        $this->_pledgePaymentErrors[] = $values;
      }

      if ($returnCode == self::SOFT_CREDIT_ERROR) {
        $this->_invalidSoftCreditRowCount++;
        $recordNumber = $this->_rowCount;
        array_unshift($values, $lineNum);
        $this->_softCreditErrors[] = $values;
      }

      if ($returnCode == self::PCP_ERROR) {
        $this->_invalidPCPRowCount++;
        array_unshift($values, $lineNum);
        $this->_pcpErrors[] = $values;
      }

      if ($returnCode == self::CONFLICT) {
        $this->_conflictCount++;
        array_unshift($values, $lineNum);
        $this->_conflicts[] = $values;
      }

      if ($returnCode == self::DUPLICATE) {
        if ($returnCode == self::MULTIPLE_DUPE) {
          // TODO: multi-dupes should be counted apart from singles on non-skip action
        }
        $this->_duplicateCount++;
        array_unshift($values, $lineNum);
        $this->_duplicates[] = $values;
      }

      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode == self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }

      // clean up memory from dao's
      CRM_Core_DAO::freeResult();
    }

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields('Contribution');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }
      $headers = array_merge([ts('Line Number'), ts('Reason')], $customHeaders);
      $filenamePrefix = str_replace(CRM_Import_ImportJob::TABLE_PREFIX, self::ERROR_FILE_PREFIX, $tableName);

      if ($this->_invalidRowCount) {
        $this->_errorFileName = self::errorFileName(self::ERROR, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }

      if ($this->_invalidPledgePaymentRowCount) {
        $this->_pledgePaymentErrorsFileName = self::errorFileName(self::PLEDGE_PAYMENT_ERROR, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_pledgePaymentErrorsFileName, $headers, $this->_pledgePaymentErrors);
      }

      if ($this->_invalidSoftCreditRowCount) {
        $this->_softCreditErrorsFileName = self::errorFileName(self::SOFT_CREDIT_ERROR, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_softCreditErrorsFileName, $headers, $this->_softCreditErrors);
      }

      if ($this->_invalidPCPRowCount) {
        $this->_pcpErrorsFileName = self::errorFileName(self::PCP_ERROR, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_pcpErrorsFileName, $headers, $this->_pcpErrors);
      }

      if ($this->_conflictCount) {
        $this->_conflictFileName = self::errorFileName(self::CONFLICT, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE, $filenamePrefix);
        CRM_Import_Parser::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
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
        $this->_activeFields[] = new CRM_Contribute_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  function setActiveFieldSoftCredit($elements) {
    if ($elements && is_array($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_softCreditField = $elements[$i];
      }
    }
  }

  function setActiveFieldPCP($elements) {
    if ($elements && is_array($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_pcpField = $elements[$i];
      }
    }
  }

  function setActiveFieldValues($elements, &$erroneousField) {
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

  function setActiveFieldLocationTypes($elements) {
    if ($elements && is_array($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_hasLocationType = $elements[$i];
      }
    }
  }

  function setActiveFieldPhoneTypes($elements) {
    if ($elements && is_array($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_phoneType = $elements[$i];
      }
    }
  }

  function setActiveFieldWebsiteTypes($elements) {
    if ($elements && is_array($elements)) {
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
    if ($elements && is_array($elements)) {
      for ($i = 0; $i < count($elements); $i++) {
        $this->_activeFields[$i]->_imProvider = $elements[$i];
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
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_softCreditField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = [];
          }
          $params[$this->_activeFields[$i]->_name][$this->_activeFields[$i]->_softCreditField] = $this->_activeFields[$i]->_value;
        }
        if (isset($this->_activeFields[$i]->_pcpField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = [];
          }
          $params[$this->_activeFields[$i]->_name][$this->_activeFields[$i]->_pcpField] = $this->_activeFields[$i]->_value;
        }

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
          if (!isset($this->_activeFields[$i]->_softCreditField)) {
            $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
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
      if (isset($field->_hasLocationType)) {
        $values[$name] = $field->_hasLocationType;
      }
    }
    return $values;
  }

  function getHeaderPatterns() {
    $values = [];
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_headerPattern)) {
        $values[$name] = $field->_headerPattern;
      }
    }
    return $values;
  }

  function getDataPatterns() {
    /**
      priority of fields is 'email', 'total amount', 'Each date fields like join_date, start_date', 'phone', 'contribute fields', 'contact fields'
    */
    $values = $contribute_fields = $contact_fields = [];
    $priority_fields = [
      'email' => '',
      'total_amount' => '',
    ];
    $secondary_fields = [
      'phone' => '',
    ];
    foreach ($this->_fields as $name => $field) {
      if(isset($priority_fields[$name])){
        $priority_fields[$name] = $field->_dataPattern;
      }
      elseif(preg_match('/_date$/', $name)){
        $priority_fields[$name] = $field->_dataPattern;
      }
      elseif(isset($secondary_fields[$name])){
        $secondary_fields[$name] = $field->_dataPattern;
      }
      elseif(preg_match('/^'.ts('Contact').'::/', $field->_title)){
        $contact_fields[$name] = $field->_dataPattern;
      }
      else{
        $contribute_fields[$name] = $field->_dataPattern;
      }
    }
    $values = array_merge($priority_fields, $secondary_fields, $contribute_fields, $contact_fields);
    return $values;
  }

  function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//', $hasLocationType = FALSE) {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
    else {
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!CRM_Utils_Array::arrayKeyExists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
      }
      else {
        $this->_fields[$name] = new CRM_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
      }
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
  function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('rowCount', $this->_rowCount);
    $store->set('seperator', $this->_seperator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('invalidSoftCreditRowCount', $this->_invalidSoftCreditRowCount);
    $store->set('validSoftCreditRowCount', $this->_validSoftCreditRowCount);
    $store->set('validPCPRowCount', $this->_validPCPRowCount);
    $store->set('invalidPCPRowCount', $this->_invalidPCPRowCount);
    $store->set('invalidPledgePaymentRowCount', $this->_invalidPledgePaymentRowCount);
    $store->set('validPledgePaymentRowCount', $this->_validPledgePaymentRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);

    switch ($this->_contactType) {
      case 'Individual':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_INDIVIDUAL);
        break;

      case 'Household':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_HOUSEHOLD);
        break;

      case 'Organization':
        $store->set('contactType', CRM_Contribute_Import_Parser::CONTACT_ORGANIZATION);
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

    if ($this->_invalidPledgePaymentRowCount) {
      $store->set('pledgePaymentErrorsFileName', $this->_pledgePaymentErrorsFileName);
    }

    if ($this->_invalidSoftCreditRowCount) {
      $store->set('softCreditErrorsFileName', $this->_softCreditErrorsFileName);
    }

    if ($this->_invalidPCPRowCount) {
      $store->set('pcpErrorsFileName', $this->_pcpErrorsFileName);
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
      $status = $params[$statusFieldName] ?? '';
      $query = "UPDATE {$this->_tableName} SET {$statusFieldName} = %1, {$statusFieldName}Msg = %2 WHERE {$primaryKeyName} = %3";
      if ($status === '') {
        CRM_Core_Error::debug_var('updateImportStatus_id', $id);
        CRM_Core_Error::debug_var('updateImportStatus_params', $params);
        CRM_Core_Error::debug_var('updateImportStatus_statusFieldName', $statusFieldName);
        CRM_Core_Error::debug_var('updateImportStatus_query', $query);
        CRM_Core_Error::debug_var('updateImportStatus_msg', $msg);
      }
      CRM_Core_DAO::executeQuery($query, [
        1 => [$status, 'String'],
        2 => [$msg, 'String'],
        3 => [$id, 'Integer'],
      ]);
    }
  }

  public static function statusName($status = NULL) {
    if (empty(self::$_statusNames)) {
      self::$_statusNames = CRM_Import_Parser::statusName();
      self::$_statusNames[self::SOFT_CREDIT_ERROR] = ts('Error').'-'.ts('Soft Credit');
      self::$_statusNames[self::PLEDGE_PAYMENT_ERROR] = ts('Error').'-'.ts('Pledge Payment');
      self::$_statusNames[self::PCP_ERROR] = ts('Error').'-'.ts('PCP Contributions');
      self::$_statusNames[self::SOFT_CREDIT] = ts('Soft Credit');
      self::$_statusNames[self::PLEDGE_PAYMENT] = ts('Pledge Payment');
      self::$_statusNames[self::PCP] = ts('PCP Contributions');
    }
    if ($status) {
      return self::$_statusNames[$status];
    }
    else {
      return self::$_statusNames;
    }
  }

  public static function errorFileName($type, $prefix) {
    if (empty($prefix)) {
      $prefix = 'contribution';
    }
    switch ($type) {
      case CRM_Contribute_Import_Parser::ERROR:
      case CRM_Contribute_Import_Parser::NO_MATCH:
      case CRM_Contribute_Import_Parser::CONFLICT:
      case CRM_Contribute_Import_Parser::DUPLICATE:
        //here constants get collides.

        if ($type == CRM_Contribute_Import_Parser::ERROR) {
          $type = CRM_Import_Parser::ERROR;
        }
        elseif ($type == CRM_Contribute_Import_Parser::NO_MATCH) {
          $type = CRM_Import_Parser::NO_MATCH;
        }
        elseif ($type == CRM_Contribute_Import_Parser::CONFLICT) {
          $type = CRM_Import_Parser::CONFLICT;
        }
        else {
          $type = CRM_Import_Parser::DUPLICATE;
        }
        $fileName = CRM_Import_Parser::saveFileName($type, $prefix);
        break;

      case CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR:
        $fileName = $prefix.'.softcredit.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR:
        $fileName = $prefix.'.pledge.xlsx';
        break;

      case CRM_Contribute_Import_Parser::PCP_ERROR:
        $fileName = $prefix.'.pcp.xlsx';
        break;
    }

    return $fileName;
  }
}

