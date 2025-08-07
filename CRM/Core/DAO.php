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
 * Our base DAO class. All DAO classes should inherit from this class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */







class CRM_Core_DAO extends DB_DataObject {

  /**
   * a null object so we can pass it as reference if / when needed
   */
  static $_nullObject = NULL;
  static $_nullArray = [];
  static $_dbColumnValueCache = NULL;
  CONST 
  NOT_NULL = 1,
  IS_NULL = 2, 
  DB_DAO_NOTNULL = 128, 
  VALUE_SEPARATOR = "", // equal to SQL Query: SELECT CHAR(1) or PHP chr(1)
  BULK_INSERT_COUNT = 200, 
  BULK_INSERT_HIGH_COUNT = 200,
  BULK_MAIL_INSERT_COUNT = 10,
  MAX_KEYS_PER_TABLE = 64; // mariadb - innodb max keys per table

  const PROFILE_RESULT_COLUMNS = 'QUERY_ID,SEQ,STATE,DURATION,CPU_USER,CPU_SYSTEM,CONTEXT_VOLUNTARY,CONTEXT_INVOLUNTARY,BLOCK_OPS_IN,BLOCK_OPS_OUT,MESSAGES_SENT,MESSAGES_RECEIVED,PAGE_FAULTS_MAJOR,PAGE_FAULTS_MINOR,SWAPS,SOURCE_FUNCTION,SOURCE_FILE,SOURCE_LINE';

  /**
   * the factory class for this application
   * @var object
   */
  static $_factory = NULL;

  /**
   * Class constructor
   *
   * @return object
   * @access public
   */
  function __construct() {
    $this->initialize();
    $this->__table = $this->getTableName();
  }

  /**
   * empty definition for virtual function
   */
  static function getTableName() {
    return NULL;
  }

  /**
   * initialize the DAO object
   *
   * @param string $dsn   the database connection string
   *
   * @return void
   * @access private
   */
  static function init($dsn) {
    $options = &PEAR::getStaticProperty('DB_DataObject', 'options');
    $options['database'] = $dsn;
    if (defined('CIVICRM_DAO_DEBUG')) {
      self::DebugLevel(CIVICRM_DAO_DEBUG);
    }
  }

  /**
   * reset the DAO object. DAO is kinda crappy in that there is an unwritten
   * rule of one query per DAO. We attempt to get around this crappy restricrion
   * by resetting some of DAO's internal fields. Use this with caution
   *
   * @return void
   * @access public
   *
   */
  function reset() {

    foreach (array_keys($this->table()) as $field) {
      unset($this->$field);
    }

    /**
     * reset the various DB_DAO structures manually
     */
    $this->_query = [];
    $this->whereAdd();
    $this->selectAdd();
    $this->joinAdd();
  }

  /**
   * Execute a query by the current DAO, localizing it along the way (if needed).
   *
   * @param string $query        the SQL query for execution
   * @param bool   $i18nRewrite  whether to rewrite the query
   *
   * @return object              the current DAO object after the query execution
   */
  function query($query, $i18nRewrite = TRUE) {
    // rewrite queries that should use $dbLocale-based views for multi-language installs
    global $dbLocale;
    if ($i18nRewrite and $dbLocale) {

      $query = CRM_Core_I18n_Schema::rewriteQuery($query);
    }

    return parent::query($query);
  }

  /**
   * Static function to set the factory instance for this class.
   *
   * @param object $factory  the factory application object
   *
   * @return void
   * @access public
   */
  static function setFactory(&$factory) {
    self::$_factory = &$factory;
  }

  /**
   * Factory method to instantiate a new object from a table name.
   *
   * @return void
   * @access public
   */
  function factory($table = '') {
    if (!isset(self::$_factory)) {
      return parent::factory($table);
    }

    return self::$_factory->create($table);
  }

  /**
   * Initialization for all DAO objects. Since we access DB_DO programatically
   * we need to set the links manually.
   *
   * @return void
   * @access protected
   */
  function initialize() {
    $links = $this->links();
    if (empty($links)) {
      return;
    }

    $this->_connect();

    if (!isset($GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database])) {
      $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database] = [];
    }

    if (!CRM_Utils_Array::arrayKeyExists($this->__table, $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database])) {
      $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database][$this->__table] = $links;
    }
  }

  /**
   * Defines the default key as 'id'.
   *
   * @access protected
   *
   * @return array
   */
  function keys() {
    static $keys;
    if (!empty($this->_primaryKey)) {
      return [$this->_primaryKey];
    }
    elseif (!isset($keys)) {
      $keys = ['id'];
    }
    return $keys;
  }

  /**
   * Tells DB_DataObject which keys use autoincrement.
   * 'id' is autoincrementing by default.
   *
   * @access protected
   *
   * @return array
   */
  function sequenceKey() {
    static $sequenceKeys;
    if (!empty($this->_primaryKey)) {
      return [FALSE, FALSE, $this->_primaryKey];
    }
    elseif (!isset($sequenceKeys)) {
      $sequenceKeys = ['id', TRUE];
    }
    return $sequenceKeys;
  }

  /**
   * returns list of FK relationships
   *
   * @access public
   *
   * @return array
   */
  function links() {
    return NULL;
  }

  /**
   * Returns list of FK relationships.
   *
   *
   * @return array
   *   Array of CRM_Core_Reference_Interface
   */
  public static function getReferenceColumns() {
    return [];
  }

  /**
   * returns all the column names of this table
   *
   * @access public
   *
   * @return array
   */
  static function &fields() {
    $result = NULL;
    return $result;
  }

  function table() {
    $fields = &$this->fields();

    $table = [];
    if ($fields) {
      foreach ($fields as $name => $value) {
        $table[$value['name']] = $value['type'];
        if (CRM_Utils_Array::value('required', $value)) {
          $table[$value['name']] += self::DB_DAO_NOTNULL;
        }
      }
    }

    // set the links
    $this->links();

    return $table;
  }

  /**
   * find result from dao
   * 
   * We need this because we need trigger hook to alter data when we found something
   * 
   * @param   boolean $n Whether to fetch first result
   */
  function find($n = false)
  {
    $ret = parent::find($n);
    CRM_Utils_Hook::get($n, $this, $ret);
    return $ret;
  }

  function save() {
    if (!empty($this->_primaryKey)) {
      $key = $this->_primaryKey;
    }
    else {
      $key = 'id';
    }
    if ($this->$key) {
      $this->update();
    }
    else {
      $this->insert();
    }
    $this->free();
    return $this;
  }

  function log($created = FALSE) {
    static $cid = NULL;

    if (!$this->getLog()) {
      return;
    }

    if (!$cid) {
      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');
    }

    // return is we dont have handle to FK
    if (!$cid) {
      return;
    }


    $dao = new CRM_Core_DAO_Log();
    $dao->entity_table = $this->getTableName();
    $dao->entity_id = $this->id;
    $dao->modified_id = $cid;
    $dao->modified_date = date("YmdHis");
    $dao->insert();
  }

  /**
   * Given an associative array of name/value pairs, extract all the values
   * that belong to this object and initialize the object with said values
   *
   * @param array $params (reference ) associative array of name/value pairs
   *
   * @return boolean      did we copy all null values into the object
   * @access public
   */
  function copyValues(&$params) {
    $fields = &$this->fields();
    $allNull = TRUE;
    foreach ($fields as $name => $value) {
      $dbName = $value['name'];
      if (CRM_Utils_Array::arrayKeyExists($dbName, $params)) {
        $pValue = $params[$dbName];
        $exists = TRUE;
      }
      elseif (CRM_Utils_Array::arrayKeyExists($name, $params)) {
        $pValue = $params[$name];
        $exists = TRUE;
      }
      else {
        $exists = FALSE;
      }

      // if there is no value then make the variable NULL
      if ($exists) {
        if ($pValue === '') {
          $this->$dbName = 'null';
        }
        else {
          $this->$dbName = $pValue;
          $allNull = FALSE;
        }
      }
    }
    return $allNull;
  }

  /**
   * Store all the values from this object in an associative array
   * this is a destructive store, calling function is responsible
   * for keeping sanity of id's.
   *
   * @param object $object the object that we are extracting data from
   * @param array  $values (reference ) associative array of name/value pairs
   *
   * @return void
   * @access public
   */
  static function storeValues(&$object, &$values) {
    $fields = &$object->fields();
    foreach ($fields as $name => $value) {
      $dbName = $value['name'];
      if (isset($object->$dbName) && $object->$dbName !== 'null') {
        $values[$dbName] = $object->$dbName;
        if ($name != $dbName) {
          $values[$name] = $object->$dbName;
        }
      }
    }
  }

  /**
   * create an attribute for this specific field. We only do this for strings and text
   *
   * @param array $field the field under task
   *
   * @return array|null the attributes for the object
   * @access public
   * @static
   */
  static function makeAttribute($field) {
    if ($field) {
      if (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_STRING) {
        $maxLength = CRM_Utils_Array::value('maxlength', $field);
        $size = CRM_Utils_Array::value('size', $field);
        if ($maxLength || $size) {
          $attributes = [];
          if ($maxLength) {
            $attributes['maxlength'] = $maxLength;
          }
          if ($size) {
            $attributes['size'] = $size;
          }
          return $attributes;
        }
      }
      elseif (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_TEXT) {
        $rows = CRM_Utils_Array::value('rows', $field);
        if (!isset($rows)) {
          $rows = 2;
        }
        $cols = CRM_Utils_Array::value('cols', $field);
        if (!isset($cols)) {
          $cols = 80;
        }

        $attributes = [];
        $attributes['rows'] = $rows;
        $attributes['cols'] = $cols;
        return $attributes;
      }
      elseif (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_INT || CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_FLOAT || CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_MONEY) {
        $attributes['size'] = 6;
        $attributes['maxlength'] = 14;
        return $attributes;
      }
    }
    return NULL;
  }

  /**
   * Get the size and maxLength attributes for this text field
   * (or for all text fields) in the DAO object.
   *
   * @param string $class     name of DAO class
   * @param string $fieldName field that i'm interested in or null if
   *                          you want the attributes for all DAO text fields
   *
   * @return array assoc array of name => attribute pairs
   * @access public
   * @static
   */
  static function getAttribute($class, $fieldName = NULL) {
    $object = new $class( );
    $fields = &$object->fields();
    if ($fieldName != NULL) {
      $field = CRM_Utils_Array::value($fieldName, $fields);
      return self::makeAttribute($field);
    }
    else {
      $attributes = [];
      foreach ($fields as $name => $field) {
        $attribute = self::makeAttribute($field);
        if ($attribute) {
          $attributes[$name] = $attribute;
        }
      }
      if (!empty($attributes)) {
        return $attributes;
      }
    }
    return NULL;
  }

  static function transaction($type) {
    CRM_Core_Error::fatal('This function is obsolete, please use CRM_Core_Transaction');
  }

  /**
   * Check if there is a record with the same name in the db
   *
   * @param string $value     the value of the field we are checking
   * @param string $daoName   the dao object name
   * @param string $daoID     the id of the object being updated. u can change your name
   *                          as long as there is no conflict
   * @param string $fieldName the name of the field in the DAO
   *
   * @return boolean     true if object exists
   * @access public
   * @static
   */
  static function objectExists($value, $daoName, $daoID, $fieldName = 'name') {
    $object = new $daoName( );
    $object->$fieldName = $value;

    $config = CRM_Core_Config::singleton();

    if ($object->find(TRUE)) {
      return ($daoID && $object->id == $daoID) ? TRUE : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Check if there is a given column in a specific table
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return boolean true if exists, else false
   * @static
   */
  static function checkFieldExists($tableName, $columnName) {
    $query = "
SHOW COLUMNS
FROM $tableName
LIKE %1
";
    $params = [1 => [$columnName, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = $dao->fetch() ? TRUE : FALSE;
    $dao->free();
    return $result;
  }

  /**
   * Returns the storage engine used by given table-name(optional).
   * Otherwise scans all the tables and return an array of all the
   * distinct storage engines being used.
   *
   * @param string $tableName
   *
   * @return array
   * @static
   */
  function getStorageValues($tableName = NULL, $maxTablesToCheck = 10, $fieldName = 'Engine') {
    $values = [];
    $query = "SHOW TABLE STATUS LIKE %1";

    $params = [];

    if (isset($tableName)) {
      $params = [1 => [$tableName, 'String']];
    }
    else {
      $params = [1 => ['civicrm_%', 'String']];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $count = 0;
    while ($dao->fetch()) {
      if (!isset($values[$dao->$fieldName])) {
        $values[$dao->$fieldName] = 1;
      }
      $count++;
      if ($maxTablesToCheck &&
        $count >= $maxTablesToCheck
      ) {
        break;
      }
    }
    $dao->free();

    return $values;
  }

  static function isDBMyISAM($maxTablesToCheck = 10) {
    // show error if any of the tables, use 'MyISAM' storage engine.
    $engines = self::getStorageValues(NULL, $maxTablesToCheck);
    if (CRM_Utils_Array::arrayKeyExists('MyISAM', $engines)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if a constraint exists for a specified table.
   *
   * @param string $tableName
   * @param string $constraint
   *
   * @return boolean true if constraint exists, false otherwise
   * @static
   */
  function checkConstraintExists($tableName, $constraint) {
    static $show = [];

    if (!CRM_Utils_Array::arrayKeyExists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

      $dao->free();
      $show[$tableName] = $dao->Create_Table;
    }

    return preg_match("/$constraint/i", $show[$tableName]) ? TRUE : FALSE;
  }

  /**
   * Checks if the FK constraint name is in the format 'FK_tableName_columnName'
   * for a specified column of a table.
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return boolean true if in format, false otherwise
   * @static
   */
  function checkFKConstraintInFormat($tableName, $columnName) {
    static $show = [];

    if (!CRM_Utils_Array::arrayKeyExists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

      $dao->free();
      $show[$tableName] = $dao->Create_Table;
    }

    return preg_match('/CONSTRAINT [`\']?' . "FK_{$tableName}_{$columnName}" . '/i', $show[$tableName]) ? TRUE : FALSE;
  }

  /**
   * Check whether a specific column in a specific table has always the same value
   *
   * @param string $tableName
   * @param string $columnName
   * @param string $columnValue
   *
   * @return boolean true if the value is always $columnValue, false otherwise
   * @static
   */
  function checkFieldHasAlwaysValue($tableName, $columnName, $columnValue) {
    $query = "SELECT * FROM $tableName WHERE $columnName != '$columnValue'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    $dao->free();
    return $result;
  }

  /**
   * Check whether a specific column in a specific table is always NULL
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return boolean true if if the value is always NULL, false otherwise
   * @static
   */
  function checkFieldIsAlwaysNull($tableName, $columnName) {
    $query = "SELECT * FROM $tableName WHERE $columnName IS NOT NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    $dao->free();
    return $result;
  }

  /**
   * Check if there is a given table in the database
   *
   * @param string $tableName
   *
   * @return boolean true if exists, else false
   * @static
   */
  function checkTableExists($tableName) {
    $query = "
SHOW TABLES
LIKE %1
";
    $params = [1 => [$tableName, 'String']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = $dao->fetch() ? TRUE : FALSE;
    $dao->free();
    return $result;
  }

  function checkVersion($version) {
    $query = "
SELECT version
FROM   civicrm_domain
";
    $dbVersion = CRM_Core_DAO::singleValueQuery($query);
    return trim($version) == trim($dbVersion) ? TRUE : FALSE;
  }

  /**
   * Given a DAO name, a column name and a column value, find the record and GET the value of another column in that record
   *
   * @param string $daoName       Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact)
   * @param int    $searchValue   Value of the column you want to search by
   * @param string $returnColumn  Name of the column you want to GET the value of
   * @param string $searchColumn  Name of the column you want to search by
   *
   * @return string|null          Value of $returnColumn in the retrieved record
   * @static
   * @access public
   */
  static function getFieldValue($daoName, $searchValue, $returnColumn = 'name', $searchColumn = 'id', $force = FALSE) {
    if (empty($searchValue)) {
      // adding this year since developers forget to check for an id
      // and hence we get the first value in the db
      CRM_Core_Error::fatal("Search Value cannot be empty when using getFieldValue of $daoName");
      return NULL;
    }

    $cacheKey = "{$daoName}:{$searchValue}:{$returnColumn}:{$searchColumn}";
    if (self::$_dbColumnValueCache === NULL) {
      self::$_dbColumnValueCache = [];
    }

    if (!CRM_Utils_Array::arrayKeyExists($cacheKey, self::$_dbColumnValueCache) || $force) {
      $object = new $daoName();
      $object->$searchColumn = $searchValue;
      $object->selectAdd();
      if ($returnColumn == 'id') {
        $object->selectAdd('id');
      }
      else {
        $object->selectAdd("id, $returnColumn");
      }
      $result = NULL;
      if ($object->find(TRUE)) {
        $result = $object->$returnColumn;
      }
      $object->free();
      self::$_dbColumnValueCache[$cacheKey] = $result;
    }
    return self::$_dbColumnValueCache[$cacheKey];
  }

  /**
   * Given a DAO name, a column name and a column value, find the record and SET the value of another column in that record
   *
   * @param string $daoName       Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact)
   * @param int    $searchValue   Value of the column you want to search by
   * @param string $setColumn     Name of the column you want to SET the value of
   * @param string $setValue      SET the setColumn to this value
   * @param string $searchColumn  Name of the column you want to search by
   *
   * @return boolean          true if we found and updated the object, else false
   * @static
   * @access public
   */
  static function setFieldValue($daoName, $searchValue, $setColumn, $setValue, $searchColumn = 'id') {
    $object = new $daoName();
    $object->selectAdd();
    $object->selectAdd("$searchColumn, $setColumn");
    $object->$searchColumn = $searchValue;
    $result = FALSE;
    if ($object->find(TRUE)) {
      $object->$setColumn = $setValue;
      if ($object->save()) {
        $result = TRUE;
      }
    }
    $object->free();
    return $result;
  }

  /**
   * Get sort string
   *
   * @param array|object $sort either array or CRM_Utils_Sort
   * @param string $default - default sort value
   *
   * @return string - sortString
   * @access public
   * @static
   */
  static function getSortString($sort, $default = NULL) {
    // check if sort is of type CRM_Utils_Sort
    if (is_a($sort, 'CRM_Utils_Sort')) {
      return $sort->orderBy();
    }

    // is it an array specified as $field => $sortDirection ?
    if ($sort) {
      foreach ($sort as $k => $v) {
        $sortString .= "$k $v,";
      }
      return rtrim($sortString, ',');
    }
    return $default;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param string $daoName  name of the dao object
   * @param array  $params   (reference ) an assoc array of name/value pairs
   * @param array  $defaults (reference ) an assoc array to hold the flattened values
   * @param array  $returnProperities     an assoc array of fields that need to be returned, eg array( 'first_name', 'last_name')
   *
   * @return object an object of type referenced by daoName
   * @access public
   * @static
   */
  static function commonRetrieve($daoName, &$params, &$defaults, $returnProperities = NULL) {
    $object = new $daoName();
    $object->copyValues($params);

    // return only specific fields if returnproperties are sent
    if (!empty($returnProperities)) {
      $object->selectAdd();
      $object->selectAdd(CRM_Utils_Array::implode(',', $returnProperities));
    }

    if ($object->find(TRUE)) {
      self::storeValues($object, $defaults);
      return $object;
    }
    return NULL;
  }

  /**
   * Delete the object records that are associated with this contact
   *
   * @param string $daoName  name of the dao object
   * @param  int  $contactId id of the contact to delete
   *
   * @return void
   * @access public
   * @static
   */
  static function deleteEntityContact($daoName, $contactId) {
    $object = new $daoName();

    $object->entity_table = 'civicrm_contact';
    $object->entity_id = $contactId;
    $object->delete();
  }

  /**
   * execute a query
   *
   * @param string $query query to be executed
   *
   * @return Object CRM_Core_DAO object that holds the results of the query
   * @static
   * @access public
   */
  static function &executeQuery($query,
    $params = [],
    $abort = TRUE,
    $daoName = NULL,
    $freeDAO = FALSE,
    $i18nRewrite = TRUE
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);
    //CRM_Core_Error::debug( 'q', $queryStr );

    if (!$daoName) {
      $dao = new CRM_Core_DAO();
    }
    else {
      $dao = new $daoName();
    }
    $dao->query($queryStr, $i18nRewrite);

    if ($freeDAO ||
      preg_match('/^(insert|update|delete|create|drop)/i', $queryStr)
    ) {
      // we typically do this for insert/update/delete stataments OR if explicitly asked to
      // free the dao
      $dao->free();
    }
    return $dao;
  }

  /**
   * execute a query and get the single result
   *
   * @param string $query query to be executed
   *
   * @return string the result of the query
   * @static
   * @access public
   */
  static function &singleValueQuery($query,
    $params = [],
    $abort = TRUE,
    $i18nRewrite = TRUE
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);

    static $_dao = NULL;

    if (!$_dao) {
      $_dao = new CRM_Core_DAO();
    }

    $_dao->query($queryStr, $i18nRewrite);

    $result = $_dao->getDatabaseResult();
    $ret = NULL;
    if ($result) {
      $row = $result->fetchRow();
      if ($row) {
        $ret = $row[0];
      }
    }
    $_dao->free();
    return $ret;
  }

  static function composeQuery($query, &$params, $abort) {


    $tr = [];
    foreach ($params as $key => $item) {
      if (is_numeric($key)) {
        if (CRM_Utils_Type::validate($item[0], $item[1]) !== NULL) {
          $item[0] = self::escapeString($item[0]);
          if (strtolower($item[0]) === '[null]') {
            $item[0] = "null";
            $item[1] = "NULL";
          }

          if ($item[1] == 'String' ||
            $item[1] == 'Memo' ||
            $item[1] == 'Link'
          ) {
            if (isset($item[2]) &&
              $item[2]
            ) {
              $item[0] = "'%{$item[0]}%'";
            }
            else {
              $item[0] = "'{$item[0]}'";
            }
          }

          if (($item[1] == 'Date' || $item[1] == 'Timestamp') &&
            strlen($item[0]) == 0
          ) {
            $item[0] = 'null';
          }

          $tr['%' . $key] = $item[0];
        }
        elseif ($abort) {
          CRM_Core_Error::fatal("{$item[0]} is not of type {$item[1]}");
        }
      }
    }
    return strtr($query, $tr);
  }

  static function freeResult($ids = NULL) {
    global $_DB_DATAOBJECT;

    /***
     $q = array( );
     foreach ( array_keys( $_DB_DATAOBJECT['RESULTS'] ) as $id ) {
     $q[] = $_DB_DATAOBJECT['RESULTS'][$id]->query;
     }
     CRM_Core_Error::debug( 'k', $q );
     return;
     ***/

    if (!$ids) {
      if (!$_DB_DATAOBJECT ||
        !isset($_DB_DATAOBJECT['RESULTS'])
      ) {
        return;
      }
      $ids = array_keys($_DB_DATAOBJECT['RESULTS']);
    }

    foreach ($ids as $id) {
      if (isset($_DB_DATAOBJECT['RESULTS'][$id])) {
        $_DB_DATAOBJECT['RESULTS'][$id]->free();
        unset($_DB_DATAOBJECT['RESULTS'][$id]);
      }

      if (isset($_DB_DATAOBJECT['RESULTFIELDS'][$id])) {
        unset($_DB_DATAOBJECT['RESULTFIELDS'][$id]);
      }
    }
  }

  /**
   * This function is to make a shallow copy of an object
   * and all the fields in the object
   *
   * @param string $daoName                 name of the dao
   * @param array  $criteria                array of all the fields & values
   *                                        on which basis to copy
   * @param array  $newData                 array of all the fields & values
   *                                        to be copied besides the other fields
   * @param string $fieldsFix               array of fields that you want to prefix/suffix
   * @param string $blockCopyOfDependencies fields that you want to block from
   *                                        getting copied
   *
   *
   * @return (reference )                   the newly created copy of the object
   * @access public
   */
  static function &copyGeneric($daoName, $criteria, $newData = NULL, $fieldsFix = NULL, $blockCopyOfDependencies = NULL) {
    $object = new $daoName();

    if (!$newData) {
      $object->id = $criteria['id'];
    }
    else {
      foreach ($criteria as $key => $value) {
        $object->$key = $value;
      }
    }

    $object->find();
    while ($object->fetch()) {

      // all the objects except with $blockCopyOfDependencies set
      // be copied - addresses #CRM-1962

      if ($blockCopyOfDependencies && $object->$blockCopyOfDependencies) {
        break;
      }

      $newObject = new $daoName();

      $fields = &$object->fields();
      if (!is_array($fieldsFix)) {
        $fieldsToPrefix = [];
        $fieldsToSuffix = [];
        $fieldsToReplace = [];
      }
      if (CRM_Utils_Array::value('prefix', $fieldsFix)) {
        $fieldsToPrefix = $fieldsFix['prefix'];
      }
      if (CRM_Utils_Array::value('suffix', $fieldsFix)) {
        $fieldsToSuffix = $fieldsFix['suffix'];
      }
      if (CRM_Utils_Array::value('replace', $fieldsFix)) {
        $fieldsToReplace = $fieldsFix['replace'];
      }

      foreach ($fields as $name => $value) {
        if ($name == 'id' || $value['name'] == 'id') {
          // copy everything but the id!
          continue;
        }

        $dbName = $value['name'];
        $newObject->$dbName = $object->$dbName;
        if (isset($fieldsToPrefix[$dbName])) {
          $newObject->$dbName = $fieldsToPrefix[$dbName] . $newObject->$dbName;
        }
        if (isset($fieldsToSuffix[$dbName])) {
          $newObject->$dbName .= $fieldsToSuffix[$dbName];
        }
        if (isset($fieldsToReplace[$dbName])) {
          $newObject->$dbName = $fieldsToReplace[$dbName];
        }

        if (substr($name, -5) == '_date' ||
          substr($name, -10) == '_date_time'
        ) {
          $newObject->$dbName = CRM_Utils_Date::isoToMysql($newObject->$dbName);
        }

        if ($newData) {
          foreach ($newData as $k => $v) {
            $newObject->$k = $v;
          }
        }
      }
      $newObject->save();
    }
    return $newObject;
  }

  /**
   * Given the component id, compute the contact id
   * since its used for things like send email
   */
  public static function &getContactIDsFromComponent(&$componentIDs, $tableName) {
    $contactIDs = [];

    if (empty($componentIDs)) {
      return $contactIDs;
    }

    $IDs = CRM_Utils_Array::implode(',', $componentIDs);
    $query = "
SELECT contact_id
  FROM $tableName
 WHERE id IN ( $IDs )
";

    $dao = &CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contactIDs[] = $dao->contact_id;
    }
    return $contactIDs;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param string $daoName  name of the dao object
   * @param array  $params   (reference ) an assoc array of name/value pairs
   * @param array  $defaults (reference ) an assoc array to hold the flattened values
   * @param array  $returnProperities     an assoc array of fields that need to be returned, eg array( 'first_name', 'last_name')
   *
   * @return object an object of type referenced by daoName
   * @access public
   * @static
   */
  static function commonRetrieveAll($daoName, $fieldIdName, $fieldId, &$details, $returnProperities = NULL) {
    $object = new $daoName();
    $object->$fieldIdName = $fieldId;

    // return only specific fields if returnproperties are sent
    if (!empty($returnProperities)) {
      $object->selectAdd();
      $object->selectAdd('id');
      $object->selectAdd(CRM_Utils_Array::implode(',', $returnProperities));
    }

    $object->find();
    while ($object->fetch()) {
      $defaults = [];
      self::storeValues($object, $defaults);
      $details[$object->id] = $defaults;
    }

    return $details;
  }

  static function dropAllTables() {

    // first drop all the custom tables we've created

    CRM_Core_BAO_CustomGroup::dropAllTables();

    // drop all multilingual views

    CRM_Core_I18n_Schema::dropAllViews();


    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN,
      dirname(__FILE__) . DIRECTORY_SEPARATOR .
      '..' . DIRECTORY_SEPARATOR .
      '..' . DIRECTORY_SEPARATOR .
      'sql' . DIRECTORY_SEPARATOR .
      'civicrm_drop.mysql'
    );
  }

  static function escapeString($string) {
    static $_dao = NULL;

    if (!$_dao) {
      if (!defined('CIVICRM_DSN')) {
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
        return str_replace($search, $replace, $string);
      }
      $_dao = new CRM_Core_DAO();
    }
    return $_dao->escape($string);
  }

  static function escapeWildCardString($string) {
    // CRM-9155
    // ensure we escape the single characters % and _ which are mysql wild
    // card characters and could come in via sortByCharacter
    // note that mysql does not escape these characters
    if ($string && in_array($string,
        ['%', '_', '%%', '_%']
      )) {
      return '\\' . $string;
    }

    return self::escapeString($string);
  }

  //Creates a test object, including any required objects it needs via recursion
  //createOnly: only create in database, do not store or return the objects (useful for perf testing)
  //ONLY USE FOR TESTING
  static function createTestObject($daoName, $params = [], $numObjects = 1, $createOnly = FALSE) {

    static $counter = 0;


    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");

    for ($i = 0; $i < $numObjects; ++$i) {

      ++$counter;
      $object = new $daoName();

      $fields = &$object->fields();
      foreach ($fields as $name => $value) {
        $dbName = $value['name'];

        $FKClassName = CRM_Utils_Array::value('FKClassName', $value);
        $required = CRM_Utils_Array::value('required', $value);
        if (CRM_Utils_Array::value($dbName, $params) &&
          !is_array($params[$dbName])
        ) {
          $object->$dbName = $params[$dbName];
        }
        elseif ($dbName != 'id') {
          if ($FKClassName != NULL) {
            //skip the FK if it is not required
            if (!$required) {
              continue;
            }

            //if it is required we need to generate the dependency object first
            $depObject = CRM_Core_DAO::createTestObject($FKClassName,
              CRM_Utils_Array::value($dbName, $params, 1)
            );
            $object->$dbName = $depObject->id;
            unset($depObject);

            continue;
          }

          switch ($value['type']) {
            case CRM_Utils_Type::T_INT:
            case CRM_Utils_Type::T_BOOL:
            case CRM_Utils_Type::T_BOOLEAN:
            case CRM_Utils_Type::T_FLOAT:
            case CRM_Utils_Type::T_MONEY:
              $object->$dbName = $counter;
              break;

            case CRM_Utils_Type::T_DATE:
            case CRM_Utils_Type::T_TIMESTAMP:
              $object->$dbName = '19700101';
              break;

            case CRM_Utils_Type::T_TIME:
              CRM_Core_Error::fatal('T_TIME shouldnt be used.');
              //$object->$dbName='000000';
              //break;
            case CRM_Utils_Type::T_CCNUM:
              $object->$dbName = '4111 1111 1111 1111';
              break;

            case CRM_Utils_Type::T_URL:
              $object->$dbName = 'http://www.civicrm.org';
              break;

            case CRM_Utils_Type::T_STRING:
            case CRM_Utils_Type::T_BLOB:
            case CRM_Utils_Type::T_MEDIUMBLOB:
            case CRM_Utils_Type::T_TEXT:
            case CRM_Utils_Type::T_LONGTEXT:
            case CRM_Utils_Type::T_EMAIL:
            default:
              if (isset($value['enumValues'])) {
                if (isset($value['default'])) {
                  $object->$dbName = $value['default'];
                }
                else $object->$dbName = $value['enumValues'][0];
              }
              else {
                $object->$dbName = $dbName . '_' . $counter;
                $maxlength = CRM_Utils_Array::value('maxlength', $value);
                if ($maxlength > 0 && strlen($object->$dbName) > $maxlength) {
                  $object->$dbName = substr($object->$dbName, 0, $value['maxlength']);
                }
              }
          }
        }
      }

      $object->save();

      if (!$createOnly) {

        $objects[$i] = $object;

      }
      else unset($object);
    }

    if ($createOnly) {

      return;

    }
    elseif ($numObjects == 1) {
      return $objects[0];
    }
    else return $objects;
  }

  //deletes the this object plus any dependent objects that are associated with it
  //ONLY USE FOR TESTING

  static function deleteTestObjects($daoName, $params = []) {
    $object = new $daoName();
    $object->id = CRM_Utils_Array::value('id', $params);

    if ($object->find(TRUE)) {

      $fields = &$object->fields();
      foreach ($fields as $name => $value) {

        $dbName = $value['name'];

        $FKClassName = CRM_Utils_Array::value('FKClassName', $value);

        if ($FKClassName != NULL && $object->$dbName) {

          //if it is required we need to generate the dependency object first
          CRM_Core_DAO::deleteTestObjects($FKClassName, ['id' => $object->$dbName]);
        }
      }
    }

    $object->delete();
  }

  static function createTempTableName($prefix = 'civicrm', $addRandomString = TRUE) {
    $tableName = $prefix . "_temp";

    if ($addRandomString) {
      $tableName .= "_" . md5(uniqid('', TRUE));
    }
    return $tableName;
  }

  static function getNextId($tableName) {
    if($tableName) {
      $query = CRM_Core_DAO::executeQuery("SHOW TABLE STATUS LIKE %1", [1 => [$tableName, 'String']]);
      $query->fetch();
      if (!empty($query->Auto_increment)) {
        return $query->Auto_increment;
      }
    }
    return NULL;
  }

  /**
   * Given a list of fields, create a list of references.
   *
   * @param string $className
   *   BAO/DAO class name.
   * @return array<CRM_Core_Reference_Interface>
   */
  public static function createReferenceColumns($className) {
    $result = [];
    $fields = $className::fields();
    foreach ($fields as $field) {
      if (isset($field['pseudoconstant'], $field['pseudoconstant']['optionGroupName'])) {
        $result[] = new CRM_Core_Reference_OptionValue(
          $className::getTableName(),
          $field['name'],
          'civicrm_option_value',
          CRM_Utils_Array::value('keyColumn', $field['pseudoconstant'], 'value'),
          $field['pseudoconstant']['optionGroupName']
        );
      }
    }
    return $result;
  }

  /**
   * Find all records which refer to this entity.
   *
   * @return array
   *   Array of objects referencing this
   */
  public function findReferences() {
    $links = self::getReferencesToTable(static::getTableName());

    $occurrences = [];
    foreach ($links as $refSpec) {
      /** @var $refSpec CRM_Core_Reference_Interface */
      $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($refSpec->getReferenceTable());
      $result = $refSpec->findReferences($this);
      if ($result) {
        while ($result->fetch()) {
          $obj = new $daoName();
          $obj->id = $result->id;
          $occurrences[] = $obj;
        }
      }
    }

    return $occurrences;
  }

  /**
   * @return array
   *   each item has keys:
   *   - name: string
   *   - type: string
   *   - count: int
   *   - table: string|null SQL table name
   *   - key: string|null SQL column name
   */
  public function getReferenceCounts() {
    $links = self::getReferencesToTable(static::getTableName());

    $counts = [];
    foreach ($links as $refSpec) {
      /** @var $refSpec CRM_Core_Reference_Interface */
      $count = $refSpec->getReferenceCount($this);
      if ($count['count'] != 0) {
        $counts[] = $count;
      }
    }

    foreach (CRM_Core_Component::getEnabledComponents() as $component) {
      /** @var $component CRM_Core_Component_Info */
      $counts = array_merge($counts, $component->getReferenceCounts($this));
    }
    CRM_Utils_Hook::referenceCounts($this, $counts);

    return $counts;
  }

  /**
   * List all tables which have hard foreign keys to this table.
   *
   * For now, this returns a description of every entity_id/entity_table
   * reference.
   * TODO: filter dynamic entity references on the $tableName, based on
   * schema metadata in dynamicForeignKey which enumerates a restricted
   * set of possible entity_table's.
   *
   * @param string $tableName
   *   Table referred to.
   *
   * @return array
   *   structure of table and column, listing every table with a
   *   foreign key reference to $tableName, and the column where the key appears.
   */
  public static function getReferencesToTable($tableName) {
    $refsFound = [];
    foreach (CRM_Core_DAO_AllCoreTables::getClasses() as $daoClassName) {
      $links = $daoClassName::getReferenceColumns();
      $daoTableName = $daoClassName::getTableName();

      foreach ($links as $refSpec) {
        /** @var $refSpec CRM_Core_Reference_Interface */
        if ($refSpec->matchesTargetTable($tableName)) {
          $refsFound[] = $refSpec;
        }
      }
    }
    return $refsFound;
  }

  /**
   * Get all references to contact table.
   *
   * This includes core tables, custom group tables, tables added by the merge
   * hook and  the entity_tag table.
   *
   * Refer to CRM-17454 for information on the danger of querying the information
   * schema to derive this.
   */
  public static function getReferencesToContactTable() {
    if (isset(\Civi::$statics[__CLASS__]) && isset(\Civi::$statics[__CLASS__]['contact_references'])) {
      return \Civi::$statics[__CLASS__]['contact_references'];
    }
    $contactReferences = [];
    $coreReferences = CRM_Core_DAO::getReferencesToTable('civicrm_contact');
    foreach ($coreReferences as $coreReference) {
      if (!is_a($coreReference, 'CRM_Core_Reference_Dynamic')) {
        $contactReferences[$coreReference->getReferenceTable()][] = $coreReference->getReferenceKey();
      }
    }
    self::appendCustomTablesExtendingContacts($contactReferences);

    // FixME for time being adding below line statically as no Foreign key constraint defined for table 'civicrm_entity_tag'
    \Civi::$statics[__CLASS__]['contact_references'] = $contactReferences;
    return \Civi::$statics[__CLASS__]['contact_references'];
  }

  /**
   * Add custom tables that extend contacts to the list of contact references.
   *
   * CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity seems like a safe-ish
   * function to be sure all are retrieved & we don't miss subtypes or inactive or multiples
   * - the down side is it is not cached.
   *
   * Further changes should be include tests in the CRM_Core_MergerTest class
   * to ensure that disabled, subtype, multiple etc groups are still captured.
   *
   * @param array $cidRefs
   */
  public static function appendCustomTablesExtendingContacts(&$cidRefs) {
    $customValueTables = CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity('Contact');
    $customValueTables->find();
    while ($customValueTables->fetch()) {
      $cidRefs[$customValueTables->table_name] = ['entity_id'];
    }
  }

  /**
   * Profiling Database Query
   *
   * @param bool $enable enable or disable profiling
   * @return void
   */
  public static function profiling($enable = 1) {
    if (CRM_Core_Config::singleton()->debugDatabaseProfiling) {
      if ($enable) {
        $profiling = 1;
      }
      else {
        $profiling = 0;
      }
      CRM_Core_DAO::executeQuery("SET SESSION profiling = $profiling");
    }
  }

  /**
   * Get profiles result array and disable profiling
   *
   * @return array
   */
  public static function getProfiles($types, $onlyPure = FALSE) {
    global $_DB_PROFILING;
    if (CRM_Core_Config::singleton()->debugDatabaseProfiling) {
      $dao = CRM_Core_DAO::executeQuery("SHOW PROFILES");
      while($dao->fetch()) {
        $_DB_PROFILING[] = [
          'id' => $dao->Query_ID,
          'duration' => $dao->Duration,
          'query' => $dao->Query,
          'details' => self::getProfile($dao->Query_ID),
        ];
      }
    }
    // disable profiling after fetch result
    self::profiling(0);
    return $_DB_PROFILING;
  }

  /**
   * Get specific profile details
   *
   * @param int $queryId
   * @return array
   */
  public static function getProfile($queryId) {
    $details = [];
    if (CRM_Core_Config::singleton()->debugDatabaseProfiling) {
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM INFORMATION_SCHEMA.PROFILING WHERE QUERY_ID = %1", [
        1 => [$queryId, 'Integer']
      ]);
      $columnName = explode(',', self::PROFILE_RESULT_COLUMNS);
      $count = 0;
      while($dao->fetch()) {
        $count++;
        foreach($columnName as $col) {
          $details[$count][$col] = $dao->$col;
        }
      }
    }
    return $details;
  }
}

