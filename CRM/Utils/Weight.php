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



class CRM_Utils_Weight {

  /**
   * Function to correct duplicate weight entries by putting them (duplicate weights) in sequence.
   *
   * @param string  $daoName full name of the DAO
   * @param array   $fieldValues field => value to be used in the WHERE
   * @param string  $weightField field which contains the weight value,
   * defaults to 'weight'
   *
   * @return bool
   */
  static function correctDuplicateWeights($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "MIN(id) AS dupeId, count(id) as dupeCount, $weightField as dupeWeight";
    $groupBy = "$weightField having dupeCount>1";

    $minDupeID = &CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField, NULL, NULL, $groupBy);
    $minDupeID->fetch();

    if ($minDupeID->dupeId) {
      $additionalWhere = "id !=" . $minDupeID->dupeId . " AND $weightField >= " . $minDupeID->dupeWeight;
      $update = "$weightField = $weightField + 1";
      $status = CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
    }

    if ($minDupeID->dupeId && $status) {
      //recursive call to correct all duplicate weight entries.
      return CRM_Utils_Weight::correctDuplicateWeights($daoName, $fieldValues, $weightField);
    }
    elseif (!$minDupeID->dupeId) {
      // case when no duplicate records are found.
      return TRUE;
    }
    elseif (!$status) {
      // case when duplicate records are found but update status is false.
      return FALSE;
    }
  }

  /**
   * Remove a row from the specified weight, and shift all rows below it up
   *
   * @param string $daoName full name of the DAO
   * $param integer $weight the weight to be removed
   * @param array $fieldValues field => value to be used in the WHERE
   * @param string $weightField field which contains the weight value,
   * defaults to 'weight'
   *
   * @return bool
   */
  static function delWeight($daoName, $fieldID, $fieldValues = NULL, $weightField = 'weight') {
    $object = new $daoName();
    $object->id = $fieldID;
    if (!$object->find(TRUE)) {
      return FALSE;
    }

    $weight = (int)$object->weight;
    if ($weight < 1) {
      return FALSE;
    }

    // fill the gap
    $additionalWhere = "$weightField > $weight";
    $update = "$weightField = $weightField - 1";
    $status = CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);

    return $status;
  }

  /**
   * Updates the weight fields of other rows according to the new and old weight paased in.
   * And returns the new weight be used. If old-weight not present, Creates a gap for a new row to be inserted
   * at the specified new weight
   *
   * @param string $daoName full name of the DAO
   * @param integer $oldWeight
   * @param integer $newWeight
   * @param array $fieldValues field => value to be used in the WHERE
   * @param string $weightField field which contains the weight value,
   * defaults to 'weight'
   *
   * @return bool
   */
  static function updateOtherWeights($daoName, $oldWeight, $newWeight, $fieldValues = NULL, $weightField = 'weight') {
    $oldWeight = (int ) $oldWeight;
    $newWeight = (int ) $newWeight;

    // max weight is the highest current weight
    $maxWeight = CRM_Utils_Weight::getMax($daoName, $fieldValues, $weightField);
    if (!$maxWeight) {
      $maxWeight = 1;
    }

    if ($newWeight > $maxWeight) {
      //calculate new weight, CRM-4133
      $calNewWeight = CRM_Utils_Weight::getNewWeight($daoName, $fieldValues, $weightField);

      //no need to update weight for other fields.
      if ($calNewWeight > $maxWeight) {
        return $calNewWeight;
      }
      $newWeight = $maxWeight;

      if (!$oldWeight) {
        return $newWeight + 1;
      }
    }
    elseif ($newWeight < 1) {
      $newWeight = 1;
    }

    // if there have duplicate weight, correct them.
    if(self::isDuplicateWeights($daoName, $fieldValues, $weightField)){
      $isDuplicateWeights = true;
      self::correctDuplicateWeights($daoName, $fieldValues, $weightField);
    }

    // if they're the same, nothing to do
    if ($oldWeight == $newWeight) {

      return $isDuplicateWeights ? null : $newWeight;
    }

    // if oldWeight not present, indicates new weight is to be added. So create a gap for a new row to be inserted.
    if (!$oldWeight) {
      $additionalWhere = "$weightField >= $newWeight";
      $update = "$weightField = ($weightField + 1)";
      CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
      return $newWeight;
    }
    else {
      if ($newWeight > $oldWeight) {
        $additionalWhere = "$weightField > $oldWeight AND $weightField <= $newWeight";
        $update = "$weightField = ($weightField - 1)";
      }
      elseif ($newWeight < $oldWeight) {
        $additionalWhere = "$weightField >= $newWeight AND $weightField < $oldWeight";
        $update = "$weightField = ($weightField + 1)";
      }
      CRM_Utils_Weight::query('UPDATE', $daoName, $fieldValues, $update, $additionalWhere);
      return $newWeight;
    }
  }

  /**
   * returns the new calculated weight.
   *
   * @param string  $daoName     full name of the DAO
   * @param array   $fieldValues field => value to be used in the WHERE
   * @param string  $weightField field which used to get the wt, default to 'weight'.
   *
   * @return integer
   */
  static function getNewWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "id AS fieldID, $weightField AS weight";
    $field = &CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField);
    $sameWeightCount = 0;
    $weights = [];
    while ($field->fetch()) {
      if (in_array($field->weight, $weights)) {
        $sameWeightCount++;
      }
      $weights[$field->fieldID] = $field->weight;
    }

    $newWeight = 1;
    if ($sameWeightCount) {
      $newWeight = max($weights) + 1;

      //check for max wt should not greater than cal max wt.
      $calMaxWt = min($weights) + count($weights) - 1;
      if ($newWeight > $calMaxWt) {
        $newWeight = $calMaxWt;
      }
    }
    elseif (!empty($weights)) {
      $newWeight = max($weights);
    }

    return $newWeight;
  }

  /**
   * returns the highest weight.
   *
   * @param string $daoName full name of the DAO
   * @param array  $fieldValues field => value to be used in the WHERE
   * @param string $weightField field which contains the weight value,
   * defaults to 'weight'
   *
   * @return integer
   */
  static function getMax($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "MAX(ROUND($weightField)) AS max_weight";
    $weightDAO = &CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField);
    $weightDAO->fetch();
    if ($weightDAO->max_weight) {
      return $weightDAO->max_weight;
    }
    return 0;
  }

  /**
   * returns the default weight ( highest weight + 1 ) to be used.
   *
   * @param string $daoName full name of the DAO
   * @param array  $fieldValues field => value to be used in the WHERE
   * @param string $weightField field which contains the weight value,
   * defaults to 'weight'
   *
   * @return integer
   */
  static function getDefaultWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $maxWeight = CRM_Utils_Weight::getMax($daoName, $fieldValues, $weightField);
    return $maxWeight + 1;
  }

  /**
   * Execute a weight-related query
   *
   * @param string $queryType SELECT, UPDATE, DELETE
   * @param string $daoName full name of the DAO
   * @param array $fieldValues field => value to be used in the WHERE
   * @param string $queryData data to be used, dependent on the query type
   * @param string $orderBy optional ORDER BY field
   *
   * @return Object CRM_Core_DAO objet that holds the results of the query
   */
  static function &query($queryType,
    $daoName,
    $fieldValues,
    $queryData,
    $additionalWhere = NULL,
    $orderBy = NULL,
    $groupBy = NULL
  ) {


    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");

    $dao = new $daoName;
    $table = $dao->getTablename();
    $fields = &$dao->fields();
    $fieldlist = array_keys($fields);

    $whereConditions = [];
    if ($additionalWhere) {
      $whereConditions[] = $additionalWhere;
    }
    $params = [];
    $fieldNum = 0;
    if (is_array($fieldValues)) {
      foreach ($fieldValues as $fieldName => $value) {
        if (!in_array($fieldName, $fieldlist)) {
          // invalid field specified.  abort.
          return FALSE;
        }
        $fieldNum++;
        $whereConditions[] = "$fieldName = %$fieldNum";
        $fieldType = $fields[$fieldName]['type'];
        $params[$fieldNum] = [$value, CRM_Utils_Type::typeToString($fieldType)];
      }
    }
    $where = CRM_Utils_Array::implode(' AND ', $whereConditions);

    switch ($queryType) {
      case 'SELECT':
        $query = "SELECT $queryData FROM $table";
        if ($where) {
          $query .= " WHERE $where";
        }
        if ($groupBy) {
          $query .= " GROUP BY $groupBy";
        }
        if ($orderBy) {
          $query .= " ORDER BY $orderBy";
        }
        break;

      case 'UPDATE':
        $query = "UPDATE $table SET $queryData";
        if ($where) {
          $query .= " WHERE $where";
        }
        break;

      case 'DELETE':
        $query = "DELETE FROM $table WHERE $where AND $queryData";
        break;

      default:
        return FALSE;
    }

    $resultDAO = CRM_Core_DAO::executeQuery($query, $params);
    return $resultDAO;
  }

  static function addOrder(&$rows, $daoName, $idName, $returnURL, $filter = NULL) {
    if (empty($rows)) {
      return;
    }

    $ids = array_keys($rows);
    $numIDs = count($ids);
    array_unshift($ids, 0);
    $ids[] = 0;
    $firstID = $ids[1];
    $lastID = $ids[$numIDs];
    if ($firstID == $lastID) {
      $rows[$firstID]['order'] = NULL;
      return;
    }
    $config = CRM_Core_Config::singleton();
    $imageURL = $config->userFrameworkResourceURL . 'i/arrow';
    $returnURL = urlencode($returnURL);
    $filter = urlencode($filter);
    $baseURL = CRM_Utils_System::url('civicrm/admin/weight',
      "reset=1&dao={$daoName}&idName={$idName}&url={$returnURL}&filter={$filter}"
    );

    for ($i = 1; $i <= $numIDs; $i++) {
      $id = $ids[$i];
      $prevID = $ids[$i - 1];
      $nextID = $ids[$i + 1];

      $links = [];
      $url = "{$baseURL}&src=$id";

      if ($prevID != 0) {
        $alt = ts('Move to top');
        $links[] = "<a href=\"{$url}&dst={$firstID}&dir=first\"><i class=\"zmdi zmdi-chevron-up zmdi-hc-fw order-icon\" title=\"$alt\"></i></a>";

        $alt = ts('Move up one row');
        $links[] = "<a href=\"{$url}&dst={$prevID}&dir=swap\"><i class=\"zmdi zmdi-caret-up zmdi-hc-fw order-icon\" title=\"$alt\"></i></a>";
      }
      else {
        $links[] = "<i class=\"zmdi zmdi-hc-fw order-icon\" style=\"visibility:hidden;\"></i>";
        $links[] = "<i class=\"zmdi zmdi-hc-fw order-icon\" style=\"visibility:hidden;\"></i>";
      }

      if ($nextID != 0) {
        $alt = ts('Move down one row');
        $links[] = "<a href=\"{$url}&dst={$nextID}&dir=swap\"><i class=\"zmdi zmdi-caret-down zmdi-hc-fw order-icon\" title=\"$alt\"></i></a>";

        $alt = ts('Move to bottom');
        $links[] = "<a href=\"{$url}&dst={$lastID}&dir=last\"><i class=\"zmdi zmdi-chevron-down zmdi-hc-fw order-icon\" title=\"$alt\"></i></a>";
      }
      else {
        $links[] = "<i class=\"zmdi zmdi-hc-fw order-icon\" style=\"visibility:hidden;\"></i>";
        $links[] = "<i class=\"zmdi zmdi-hc-fw order-icon\" style=\"visibility:hidden;\"></i>";
      }
      $rows[$id]['weight'] = CRM_Utils_Array::implode('&nbsp;', $links);
    }
  }

  static function fixOrder() {
    $daoName = CRM_Utils_Request::retrieve('dao', 'String', CRM_Core_DAO::$_nullObject);
    $id = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject);
    $idName = CRM_Utils_Request::retrieve('idName', 'String', CRM_Core_DAO::$_nullObject);
    $url = CRM_Utils_Request::retrieve('url', 'String', CRM_Core_DAO::$_nullObject);
    $filter = CRM_Utils_Request::retrieve('filter', 'String', CRM_Core_DAO::$_nullObject);
    $src = CRM_Utils_Request::retrieve('src', 'Integer', CRM_Core_DAO::$_nullObject);
    $dst = CRM_Utils_Request::retrieve('dst', 'Integer', CRM_Core_DAO::$_nullObject);
    $dir = CRM_Utils_Request::retrieve('dir', 'String', CRM_Core_DAO::$_nullObject);

    $wheres = explode('AND',$filter);
    foreach ($wheres as $where) {
      $where_array = explode('=',$where);
      $fieldValues[trim($where_array[0])] = trim($where_array[1]);
    }
    if(self::isDuplicateWeights($daoName, $fieldValues)){
      self::correctDuplicateWeights($daoName, $fieldValues);
    }

    $srcWeight = CRM_Core_DAO::getFieldValue($daoName,
      $src,
      'weight',
      $idName
    );
    $dstWeight = CRM_Core_DAO::getFieldValue($daoName,
      $dst,
      'weight',
      $idName
    );
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");
    $object = new $daoName();
    $tableName = $object->tableName();

    $query = "UPDATE $tableName SET weight = %1 WHERE $idName = %2";
    $params = [1 => [$dstWeight, 'Integer'],
      2 => [$src, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($query, $params);

    if ($dir == 'swap') {
      $params = [1 => [$srcWeight, 'Integer'],
        2 => [$dst, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }
    elseif ($dir == 'first') {
      // increment the rest by one
      $query = "UPDATE $tableName SET weight = weight + 1 WHERE $idName != %1 AND weight < %2";
      if ($filter) {
        $query .= " AND $filter";
      }
      $params = [1 => [$src, 'Integer'],
        2 => [$srcWeight, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }
    elseif ($dir == 'last') {
      // increment the rest by one
      $query = "UPDATE $tableName SET weight = weight - 1 WHERE $idName != %1 AND weight > %2";
      if ($filter) {
        $query .= " AND $filter";
      }
      $params = [1 => [$src, 'Integer'],
        2 => [$srcWeight, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $params);
    }

    CRM_Utils_System::redirect($url);
  }

  /**
   * Check if there are multiple same weight value.
   * @param string $daoName Dao class name in CRM
   * @param string $filter  WHERE clause in sql (Not include 'WHERE')
   */
  static function isDuplicateWeights($daoName, $filter, $weightField = 'weight'){
    $selectField = "COUNT($weightField) as count";
    $weightDAO = &CRM_Utils_Weight::query(
      'SELECT',
      $daoName,
      $fieldValues,
      $selectField,
      NULL,
      'count DESC LIMIT 1',
      $weightField
    );
    $weightDAO->fetch();
    return ($weightDAO->count > 1);

  }
}

