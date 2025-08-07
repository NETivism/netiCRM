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
class CRM_Core_OptionGroup {
  static $_values = [];
  static $_cache = [];

  /*
     * $_domainIDGroups array maintains the list of option groups for whom 
     * domainID is to be considered.
     *
     */

  static $_domainIDGroups = ['from_email_address',
    'grant_type',
  ];

  static function &valuesCommon($dao, $flip = FALSE, $grouping = FALSE,
    $localize = FALSE, $labelColumnName = 'label', $keyColumnName = 'value'
  ) {
    self::$_values = [];
    if ($keyColumnName !== 'value' && !CRM_Utils_Rule::alphanumeric($keyColumnName)) {
      $keyColumnName = 'value';
    }

    while ($dao->fetch()) {
      if (isset($dao->$keyColumnName) && !is_array($dao->$keyColumnName) && (is_string($dao->$keyColumnName) || is_numeric($dao->$keyColumnName))) {
        $keyColumn = $dao->$keyColumnName;
      }
      if ($flip) {
        if ($grouping) {
          self::$_values[$dao->value] = $dao->grouping;
        }
        else {
          self::$_values[$dao->{$labelColumnName}] = $keyColumn;
        }
      }
      else {
        if ($grouping) {
          self::$_values[$dao->{$labelColumnName}] = $dao->grouping;
        }
        else {
          self::$_values[$keyColumn] = $dao->{$labelColumnName};
        }
      }
    }
    if ($localize) {
      $i18n = &CRM_Core_I18n::singleton();
      $i18n->localizeArray(self::$_values);
    }
    return self::$_values;
  }

  static function &values($name, $flip = FALSE, $grouping = FALSE,
    $localize = FALSE, $condition = NULL,
    $labelColumnName = 'label', $onlyActive = TRUE, $fresh = FALSE, $keyColumnName = 'value'
  ) {
    $cache = CRM_Utils_Cache::singleton();
    $cacheKey = self::createCacheKey($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName);
    if (!$fresh) {
      // Fetch from static var
      if (CRM_Utils_Array::arrayKeyExists($cacheKey, self::$_cache)) {
        return self::$_cache[$cacheKey];
      }
      // Fetch from main cache
      $var = $cache->get($cacheKey);
      if ($var) {
        return $var;
      }
    }

    if ($labelColumnName !== 'label') {
      $query = "
  SELECT  v.label as label, v.{$labelColumnName} as {$labelColumnName} ,v.value as value, v.grouping as grouping, v.id as id
  FROM   civicrm_option_value v,
        civicrm_option_group g
  WHERE  v.option_group_id = g.id
    AND  g.name            = %1
    AND  g.is_active       = 1 ";
    }
    else {
      $query = "
  SELECT  v.{$labelColumnName} as {$labelColumnName} ,v.value as value, v.grouping as grouping, v.id as id
  FROM   civicrm_option_value v,
        civicrm_option_group g
  WHERE  v.option_group_id = g.id
    AND  g.name            = %1
    AND  g.is_active       = 1 ";
    }

    if ($onlyActive) {
      $query .= " AND  v.is_active = 1 ";
    }
    if (in_array($name, self::$_domainIDGroups)) {
      $query .= " AND v.domain_id = " . CRM_Core_Config::domainID();
    }

    if ($condition) {
      $query .= $condition;
    }

    $query .= "  ORDER BY v.weight";

    $p = [1 => [$name, 'String']];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    $var = &self::valuesCommon($dao, $flip, $grouping, $localize, $labelColumnName, $keyColumnName);

    $cache->set($cacheKey, $var);

    // call option value hook

    CRM_Utils_Hook::optionValues($var, $name);

    self::$_cache[$cacheKey] = $var;
    $cache->set($cacheKey, $var);

    return $var;
  }

  /**
   * Counterpart to values() which removes the item from the cache
   *
   * @param $name
   * @param $flip
   * @param $grouping
   * @param $localize
   * @param $condition
   * @param $labelColumnName
   * @param $onlyActive
   */
  protected static function flushValues($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName = 'value') {
    $cacheKey = self::createCacheKey($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName);
    $cache = CRM_Utils_Cache::singleton();
    $cache->delete($cacheKey);
    unset(self::$_cache[$cacheKey]);
  }

  protected static function createCacheKey() {
    $cacheKey = "CRM_OG_" . serialize(func_get_args());
    return $cacheKey;
  }

  static function &valuesByID($id, $flip = FALSE, $grouping = FALSE, $localize = FALSE, $labelColumnName = 'label', $onlyActive = TRUE, $fresh = FALSE) {
    $cacheKey = self::createCacheKey($id, $flip, $grouping, $localize, $labelColumnName, $onlyActive);

    $cache = CRM_Utils_Cache::singleton();
    if (!$fresh) {
      $var = $cache->get($cacheKey);
      if ($var) {
        return $var;
      }
    }

    $query = "
SELECT  v.{$labelColumnName} as {$labelColumnName} ,v.value as value, v.grouping as grouping
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.id              = %1
  AND  v.is_active       = 1 
  AND  g.is_active       = 1 
  ORDER BY v.weight, v.label; 
";
    $p = [1 => [$id, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    $var = self::valuesCommon($dao, $flip, $grouping, $localize, $labelColumnName);
    $cache->set($cacheKey, $var);

    return $var;
  }

  /**
   * Function to lookup titles OR ids for a set of option_value populated fields. The retrieved value
   * is assigned a new fieldname by id or id's by title
   * (each within a specificied option_group)
   *
   * @param  array   $params   Reference array of values submitted by the form. Based on
   *                           $flip, creates new elements in $params for each field in
   *                           the $names array.
   *                           If $flip = false, adds     root field name     => title
   *                           If $flip = true, adds      actual field name   => id
   *
   * @param  array   $names    Reference array of fieldnames we want transformed.
   *                           Array key = 'postName' (field name submitted by form in $params).
   *                           Array value = array('newName' => $newName, 'groupName' => $groupName).
   *
   *
   * @param  boolean $flip
   *
   * @return void
   *
   * @access public
   * @static
   */
  static function lookupValues(&$params, &$names, $flip = FALSE) {

    foreach ($names as $postName => $value) {
      // See if $params field is in $names array (i.e. is a value that we need to lookup)
      if (CRM_Utils_Array::value($postName, $params)) {
        // params[$postName] may be a Ctrl+A separated value list
        if (strpos($params[$postName], CRM_Core_BAO_CustomOption::VALUE_SEPERATOR)) {
          // eliminate the ^A frm the beginning and end if present
          if (substr($params[$postName], 0, 1) == CRM_Core_BAO_CustomOption::VALUE_SEPERATOR) {
            $params[$postName] = substr($params[$postName], 1, -1);
          }
        }
        $postValues = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $params[$postName]);
        $newValue = [];
        foreach ($postValues as $postValue) {
          if (!$postValue) {
            continue;
          }

          if ($flip) {
            $p = [1 => [$postValue, 'String']];
            $lookupBy = 'v.label= %1';
            $select = "v.value";
          }
          else {
            $p = [1 => [$postValue, 'Integer']];
            $lookupBy = 'v.value = %1';
            $select = "v.label";
          }

          $p[2] = [$value['groupName'], 'String'];
          $query = "
                        SELECT $select
                        FROM   civicrm_option_value v,
                               civicrm_option_group g
                        WHERE  v.option_group_id = g.id
                        AND    g.name            = %2
                        AND    $lookupBy";

          $newValue[] = CRM_Core_DAO::singleValueQuery($query, $p);
          $newValue = str_replace(',', '_', $newValue);
        }
        $params[$value['newName']] = CRM_Utils_Array::implode(', ', $newValue);
      }
    }
  }

  static function getName($groupName, $value, $onlyActiveValue = TRUE) {
    if (empty($groupName) || empty($value)) {
      return NULL;
    }

    $query = "
SELECT  v.name as name, v.value as value
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1 
  AND  g.is_active       = 1  
  AND  v.value           = %2
";
    if ($onlyActiveValue) {
      $query .= " AND  v.is_active = 1 ";
    }
    $p = [1 => [$groupName, 'String'],
      2 => [$value, 'Integer'],
    ];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      return $dao->name;
    }
    return NULL;
  }

  static function getLabel($groupName, $value, $onlyActiveValue = TRUE) {
    if (empty($groupName) ||
      empty($value)
    ) {
      return NULL;
    }

    $query = "
SELECT  v.label as label ,v.value as value
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1 
  AND  g.is_active       = 1  
  AND  v.value           = %2
";
    if ($onlyActiveValue) {
      $query .= " AND  v.is_active = 1 ";
    }
    $p = [1 => [$groupName, 'String'],
      2 => [$value, 'Integer'],
    ];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      return $dao->label;
    }
    return NULL;
  }

  static function getValue($groupName,
    $label,
    $labelField = 'label',
    $labelType = 'String',
    $valueField = 'value'
  ) {
    if (empty($label)) {
      return NULL;
    }
    $cacheKey = self::createCacheKey('getValue_', $groupName, $label, $labelField, $labelType, $valueField);
    if (CRM_Utils_Array::arrayKeyExists($cacheKey, self::$_cache)) {
      return self::$_cache[$cacheKey];
    }

    $query = "
SELECT  v.label as label ,v.{$valueField} as value
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1 
  AND  v.is_active       = 1  
  AND  g.is_active       = 1  
  AND  v.$labelField     = %2
";

    $p = [1 => [$groupName, 'String'],
      2 => [$label, $labelType],
    ];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      self::$_cache[$cacheKey] = $dao->value;
      return $dao->value;
    }
    return NULL;
  }

  static function createAssoc($groupName, &$values, &$defaultID, $groupLabel = NULL) {
    self::deleteAssoc($groupName);
    if (!empty($values)) {

      $group = new CRM_Core_DAO_OptionGroup();
      $group->name = $groupName;
      $group->label = $groupLabel;
      $group->is_reserved = 1;
      $group->is_active = 1;
      $group->save();


      foreach ($values as $v) {
        $value = new CRM_Core_DAO_OptionValue();
        $value->option_group_id = $group->id;
        $value->label = $v['label'];
        $value->value = $v['value'];
        if (isset($v['grouping'])) {
          $value->grouping = $v['grouping'];
        }
        if (isset($v['filter']) && is_numeric($v['filter'])) {
          $value->filter = $v['filter'];
        }
        $value->name = CRM_Utils_Array::value('name', $v);
        $value->description = CRM_Utils_Array::value('description', $v);
        $value->weight = CRM_Utils_Array::value('weight', $v);
        $value->is_default = CRM_Utils_Array::value('is_default', $v);
        $value->is_active = CRM_Utils_Array::value('is_active', $v);
        $value->filter = CRM_Utils_Array::value('filter', $v);
        $value->save();

        if ($value->is_default) {
          $defaultID = $value->id;
        }
      }
    }
    else {
      return $defaultID = 'null';
    }

    return $group->id;
  }

  static function getAssoc($groupName, &$values, $flip = FALSE, $field = 'name') {
    $query = "
SELECT v.id as amount_id, v.value, v.label, v.name, v.description, v.weight, v.grouping, v.filter, v.is_default
  FROM civicrm_option_group g,
       civicrm_option_value v
 WHERE g.id = v.option_group_id
   AND g.$field = %1
ORDER BY v.weight
";
    $params = [1 => [$groupName, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $fields = ['value', 'label', 'name', 'description', 'amount_id', 'weight', 'grouping', 'filter', 'is_default'];
    if ($flip) {
      $values = [];
    }
    else {
      foreach ($fields as $field) {
        $values[$field] = [];
      }
    }
    $index = 1;

    while ($dao->fetch()) {
      if ($flip) {
        $value = [];
        foreach ($fields as $field) {
          $value[$field] = $dao->$field;
        }
        $values[$dao->amount_id] = $value;
      }
      else {
        foreach ($fields as $field) {
          $values[$field][$index] = $dao->$field;
        }
        $index++;
      }
    }
  }

  static function deleteAssoc($groupName, $operator = "=") {
    $query = "
DELETE g, v
  FROM civicrm_option_group g,
       civicrm_option_value v
 WHERE g.id = v.option_group_id
   AND g.name {$operator} %1";

    $params = [1 => [$groupName, 'String']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
  }

  static function optionLabel($groupName, $value) {
    $query = "
SELECT v.label
  FROM civicrm_option_group g,
       civicrm_option_value v
 WHERE g.id = v.option_group_id
   AND g.name  = %1
   AND v.value = %2";
    $params = [1 => [$groupName, 'String'],
      2 => [$value, 'String'],
    ];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  static function getRowValues($groupName, $fieldValue, $field = 'name',
    $fieldType = 'String', $active = TRUE
  ) {
    $query = "
SELECT v.id, v.label, v.value, v.name, v.weight, v.description 
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1
  AND  g.is_active       = 1  
  AND  v.$field          = %2
";

    if ($active) {
      $query .= " AND  v.is_active = 1";
    }

    $p = [1 => [$groupName, 'String'],
      2 => [$fieldValue, $fieldType],
    ];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);
    $row = [];

    if ($dao->fetch()) {
      foreach (['id', 'name', 'value', 'label', 'weight', 'description'] as $fld) {
        $row[$fld] = $dao->$fld;
      }
    }
    return $row;
  }

  /*
     * Wrapper for calling values with fresh set to true to empty the given value
     *
     * Since there appears to be some inconsistency
     * (@todo remove inconsistency) around the pseudoconstant operations
     * (for example CRM_Contribution_PseudoConstant::paymentInstrument doesn't specify isActive
     * which is part of the cache key
     * will do a couple of variations & aspire to someone cleaning it up later
     */

  static function flush($name, $params = []) {
    $defaults = [
      'flip' => FALSE,
      'grouping' => FALSE,
      'localize' => FALSE,
      'condition' => NULL,
      'labelColumnName' => 'label',
    ];

    $params = array_merge($defaults, $params);
    self::flushValues(
      $name,
      $params['flip'],
      $params['grouping'],
      $params['localize'],
      $params['condition'],
      $params['labelColumnName'],
      TRUE,
      TRUE
    );
    self::flushValues(
      $name,
      $params['flip'],
      $params['grouping'],
      $params['localize'],
      $params['condition'],
      $params['labelColumnName'],
      FALSE,
      TRUE
    );
  }

  static function flushAll() {
    self::$_values = [];
    self::$_cache = [];
    CRM_Utils_Cache::singleton()->flush();
  }
}

