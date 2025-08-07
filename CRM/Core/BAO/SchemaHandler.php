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

/**
 *  This file contains functions for creating and altering CiviCRM-tables.
 */



/**
 * structure, similar to what is used in GenCode.php
 *
 * $table = array( 'name'       => TABLE_NAME,
 *                'attributes' => ATTRIBUTES,
 *                'fields'     => array(
 *                                      array( 'name'          => FIELD_NAME,
 *                                             'type'          => FIELD_SQL_TYPE,
 // can be field, index, constraint
 *                                             'class'         => FIELD_CLASS_TYPE,
 *                                             'primary'       => BOOLEAN,
 *                                             'required'      => BOOLEAN,
 *                                             'searchable'    => true,
 *                                             'fk_table_name' => FOREIGN_KEY_TABLE_NAME,
 *                                             'fk_field_name' => FOREIGN_KEY_FIELD_NAME,
 *                                             'comment'       => COMMENT,
 *                                             'default'       => DEFAULT, )
 *                                      ...
 *                                      ) );
 */
class CRM_Core_BAO_SchemaHandler {

  /**
   * Function for creating a civiCRM-table
   *
   * @param  String  $tableName        name of the table to be created.
   * @param  Array   $tableAttributes  array containing atrributes for the table that needs to be created
   *
   * @return true if successfully created, false otherwise
   *
   * @static
   * @access public
   */
  static function createTable(&$params) {
    $sql = self::buildTableSQL($params);
    // do not i18n-rewrite
    $dao = &CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
    $dao->free();

    // logging support

    $logging = new CRM_Logging_Schema;
    $logging->fixSchemaDifferencesFor($params['name']);

    return TRUE;
  }

  static function buildTableSQL(&$params) {
    $sql = "CREATE TABLE {$params['name']} (";
    if (isset($params['fields']) &&
      is_array($params['fields'])
    ) {
      $separator = "\n";
      $prefix = NULL;
      foreach ($params['fields'] as $field) {
        $sql .= self::buildFieldSQL($field, $separator, $prefix);
        $separator = ",\n";
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildPrimaryKeySQL($field, $separator, $prefix);
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildSearchIndexSQL($field, $separator, $prefix);
      }
      if (isset($params['indexes'])) {
        foreach ($params['indexes'] as $index) {
          $sql .= self::buildIndexSQL($index, $separator, $prefix);
        }
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildForeignKeySQL($field, $separator, $prefix, $params['name']);
      }
    }
    $sql .= "\n) {$params['attributes']};";
    return $sql;
  }

  static function buildFieldSQL(&$params, $separator, $prefix) {
    $sql = '';
    $sql .= $separator;
    $sql .= str_repeat(' ', 8);
    $sql .= $prefix;
    $sql .= "`{$params['name']}` {$params['type']}";

    if (CRM_Utils_Array::value('required', $params)) {
      $sql .= " NOT NULL";
    }

    if (CRM_Utils_Array::value('attributes', $params)) {
      $sql .= " {$params['attributes']}";
    }

    if (CRM_Utils_Array::value('default', $params) &&
      $params['type'] != 'text'
    ) {
      $sql .= " DEFAULT {$params['default']}";
    }

    if (CRM_Utils_Array::value('comment', $params)) {
      $sql .= " COMMENT '{$params['comment']}'";
    }

    return $sql;
  }

  static function buildPrimaryKeySQL(&$params, $separator, $prefix) {
    $sql = NULL;
    if (CRM_Utils_Array::value('primary', $params)) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $sql .= "PRIMARY KEY ( {$params['name']} )";
    }
    return $sql;
  }

  static function buildSearchIndexSQL(&$params, $separator, $prefix, $indexExist = FALSE) {
    $sql = NULL;

    // dont index blob
    if ($params['type'] == 'text') {
      return $sql;
    }

    //create index only for searchable fields during ADD,
    //create index only if field is become searchable during MODIFY,
    //drop index only if field is no more searchable and index was exist.
    if (CRM_Utils_Array::value('searchable', $params) && !$indexExist) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $sql .= "INDEX_{$params['name']} ( {$params['name']} )";
    }
    elseif (!CRM_Utils_Array::value('searchable', $params) && $indexExist) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= "DROP INDEX INDEX_{$params['name']}";
    }
    return $sql;
  }

  static function buildIndexSQL(&$params, $separator, $prefix) {
    $sql = '';
    $sql .= $separator;
    $sql .= str_repeat(' ', 8);
    if ($params['unique']) {
      $sql .= 'UNIQUE INDEX';
      $indexName = 'unique';
    }
    else {
      $sql .= 'INDEX';
      $indexName = 'index';
    }
    $indexFields = NULL;

    foreach ($params as $name => $value) {
      if (substr($name, 0, 11) == 'field_name_') {
        $indexName .= "_{$value}";
        $indexFields .= " $value,";
      }
    }
    $indexFields = substr($indexFields, 0, -1);

    $sql .= " $indexName ( $indexFields )";
    return $sql;
  }

  static function changeFKConstraint($tableName, $fkTableName) {
    $fkName = "{$tableName}_entity_id";
    if (strlen($fkName) >= 48) {
      $fkName = substr($fkName, 0, 32) . "_" . substr(md5($fkName), 0, 16);
    }
    $dropFKSql = "
ALTER TABLE {$tableName}
      DROP FOREIGN KEY `FK_{$fkName}`;";

    $dao = CRM_Core_DAO::executeQuery($dropFKSql);
    $dao->free();

    $addFKSql = "
ALTER TABLE {$tableName}
      ADD CONSTRAINT `FK_{$fkName}` FOREIGN KEY (`entity_id`) REFERENCES {$fkTableName} (`id`) ON DELETE CASCADE;";
    // CRM-7007: do not i18n-rewrite this query
    $dao = CRM_Core_DAO::executeQuery($addFKSql, [], TRUE, NULL, FALSE, FALSE);
    $dao->free();

    return TRUE;
  }

  static function buildForeignKeySQL(&$params, $separator, $prefix, $tableName) {
    $sql = NULL;
    if (CRM_Utils_Array::value('fk_table_name', $params) &&
      CRM_Utils_Array::value('fk_field_name', $params)
    ) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $fkName = "{$tableName}_{$params['name']}";
      if (strlen($fkName) >= 48) {
        $fkName = substr($fkName, 0, 32) . "_" . substr(md5($fkName), 0, 16);
      }

      $sql .= "CONSTRAINT FK_$fkName FOREIGN KEY ( `{$params['name']}` ) REFERENCES {$params['fk_table_name']} ( {$params['fk_field_name']} ) ";
      $sql .= CRM_Utils_Array::value('fk_attributes', $params);
    }
    return $sql;
  }

  static function alterFieldSQL(&$params, $indexExist = FALSE) {
    $sql = str_repeat(' ', 8);
    $sql .= "ALTER TABLE {$params['table_name']}";

    // lets suppress the required flag, since that can cause sql issue
    $params['required'] = FALSE;

    switch ($params['operation']) {
      case 'add':
        $separator = "\n";
        $prefix = "ADD ";
        $sql .= self::buildFieldSQL($params, $separator, "ADD COLUMN ");
        $separator = ",\n";
        $sql .= self::buildPrimaryKeySQL($params, $separator, "ADD PRIMARY KEY ");
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ");
        $sql .= self::buildForeignKeySQL($params, $separator, "ADD ", $params['table_name']);
        break;

      case 'modify':
        $separator = "\n";
        $prefix = "MODIFY ";
        $sql .= self::buildFieldSQL($params, $separator, $prefix);
        $separator = ",\n";
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ", $indexExist);
        break;

      case 'delete':
        $sql .= " DROP COLUMN `{$params['name']}`";
        if (CRM_Utils_Array::value('primary', $params)) {
          $sql .= ", DROP PRIMARY KEY";
        }
        if (CRM_Utils_Array::value('fk_table_name', $params)) {
          $sql .= ", DROP FOREIGN KEY FK_{$params['fkName']}";
        }
        break;
    }

    // CRM-7007: do not i18n-rewrite this query
    $dao = &CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
    $dao->free();

    // logging support: if we’re adding a column (but only then!) make sure the potential relevant log table gets a column as well
    if ($params['operation'] == 'add') {

      $logging = new CRM_Logging_Schema;
      $logging->fixSchemaDifferencesFor($params['table_name'], [$params['name']]);
    }

    return TRUE;
  }

  /**
   * Function to delete a civiCRM-table
   *
   * @param  String  $tableName   name of the table to be created.
   *
   * @return true if successfully deleted, false otherwise
   *
   * @static
   * @access public
   */
  static function dropTable($tableName) {
    $sql = "DROP TABLE $tableName";
    $dao = &CRM_Core_DAO::executeQuery($sql);
  }

  static function dropColumn($tableName, $columnName) {
    $sql = "ALTER TABLE $tableName DROP COLUMN $columnName";
    $dao = &CRM_Core_DAO::executeQuery($sql);
  }

  static function changeUniqueToIndex($tableName, $dropUnique = TRUE) {
    if ($dropUnique) {
      $sql = "ALTER TABLE $tableName 
DROP INDEX `unique_entity_id` ,
ADD INDEX `FK_{$tableName}_entity_id` ( `entity_id` )";
    }
    else {
      $sql = " ALTER TABLE $tableName
DROP INDEX `FK_{$tableName}_entity_id` ,
ADD UNIQUE INDEX `unique_entity_id` ( `entity_id` )";
    }
    $dao = &CRM_Core_DAO::executeQuery($sql);
  }

  static function createIndexes(&$tables, $createIndexPrefix = 'index', $substrLenghts = []) {
    $queries = [];

    $domain = new CRM_Core_DAO_Domain;
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    // if we're multilingual, cache the information on internationalised fields
    static $columns = NULL;

    if (!CRM_Utils_System::isNull($locales) and $columns === NULL) {

      $columns = &CRM_Core_I18n_SchemaStructure::columns();
    }

    foreach ($tables as $table => $fields) {
      $query = "SHOW INDEX FROM $table";
      $dao = CRM_Core_DAO::executeQuery($query);

      $currentIndexes = [];
      while ($dao->fetch()) {
        $currentIndexes[] = $dao->Key_name;
      }

      // now check for all fields if the index exists
      foreach ($fields as $field) {
        // handle indices over substrings, CRM-6245
        // $lengthName is appended to index name, $lengthSize is the field size modifier
        $lengthName = isset($substrLenghts[$table][$field]) ? "_{$substrLenghts[$table][$field]}" : '';
        $lengthSize = isset($substrLenghts[$table][$field]) ? "({$substrLenghts[$table][$field]})" : '';

        $names = ["index_{$field}{$lengthName}", "FK_{$table}_{$field}{$lengthName}", "UI_{$field}{$lengthName}", "{$createIndexPrefix}_{$field}{$lengthName}"];

        // skip to the next $field if one of the above $names exists; handle multilingual for CRM-4126
        foreach ($names as $name) {
          $regex = '/^' . preg_quote($name) . '(_[a-z][a-z]_[A-Z][A-Z])?$/';
          if (preg_grep($regex, $currentIndexes)) {
            continue 2;
          }
        }

        // the index doesn't exist, so create it
        // if we're multilingual and the field is internationalised, do it for every locale
        if (!CRM_Utils_System::isNull($locales) and isset($columns[$table][$field])) {
          foreach ($locales as $locale) {
            $queries[] = "CREATE INDEX {$createIndexPrefix}_{$field}{$lengthName}_{$locale} ON {$table} ({$field}_{$locale}{$lengthSize})";
          }
        }
        else {
          $queries[] = "CREATE INDEX {$createIndexPrefix}_{$field}{$lengthName} ON {$table} ({$field}{$lengthSize})";
        }
      }
    }

    // run the queries without i18n-rewriting
    $dao = new CRM_Core_DAO;
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }
  }

  public static function checkIndexCountByTable($table) {
    $table = CRM_Utils_Type::escape($table, 'String');
    $dao = CRM_Core_DAO::executeQuery("SHOW INDEX FROM $table");
    $dao->fetch();
    if (!empty($dao->N)) {
      return $dao->N;
    }

    // in case no N return
    $count = 1;
    while($dao->fetch()) {
      $count++;
    }
    return $count;
  }

}

