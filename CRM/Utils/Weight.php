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
 * Utility class for managing weight-based ordering of database rows.
 *
 * Provides methods to add, remove, reorder, and fix weight values
 * used for sorting rows within DAO tables.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */
class CRM_Utils_Weight {

  /**
   * Correct duplicate weight entries by putting them in sequence.
   *
   * Recursively finds rows sharing the same weight and increments
   * weights to eliminate duplicates.
   *
   * @param string $daoName
   *   Full class name of the DAO (e.g., 'CRM_Core_DAO_OptionValue').
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return bool|void
   *   TRUE if no duplicates remain, FALSE if the update failed,
   *   or void on successful recursive correction.
   */
  public static function correctDuplicateWeights($daoName, $fieldValues = NULL, $weightField = 'weight') {
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
   * Remove a row from the weight sequence and shift rows below it up.
   *
   * Finds the weight of the specified row and decrements the weight
   * of all rows with a higher weight value to fill the gap.
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param int $fieldID
   *   The ID of the row to remove from the weight sequence.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return bool
   *   TRUE if weights were successfully updated, FALSE if the row was
   *   not found or had a weight less than 1.
   */
  public static function delWeight($daoName, $fieldID, $fieldValues = NULL, $weightField = 'weight') {
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
   * Update the weight fields of other rows to accommodate a weight change.
   *
   * Shifts other rows up or down to make room for the new weight.
   * If oldWeight is 0 or absent, creates a gap for a new row at
   * the specified newWeight position.
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param int $oldWeight
   *   The current weight of the row being moved (0 if inserting a new row).
   * @param int $newWeight
   *   The desired new weight position.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return int|null
   *   The weight value to assign to the row, or NULL if duplicate
   *   weights were corrected and no further change is needed.
   */
  public static function updateOtherWeights($daoName, $oldWeight, $newWeight, $fieldValues = NULL, $weightField = 'weight') {
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
    if (self::isDuplicateWeights($daoName, $fieldValues, $weightField)) {
      $isDuplicateWeights = TRUE;
      self::correctDuplicateWeights($daoName, $fieldValues, $weightField);
    }

    // if they're the same, nothing to do
    if ($oldWeight == $newWeight) {

      return $isDuplicateWeights ? NULL : $newWeight;
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
   * Calculate a new weight value accounting for duplicates.
   *
   * Examines existing weights and returns an appropriate new weight.
   * If duplicate weights exist, returns one above the maximum weight.
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return int
   *   The calculated weight value for a new row.
   */
  public static function getNewWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
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
   * Get the highest weight value among matching rows.
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return int
   *   The maximum weight value, or 0 if no rows exist.
   */
  public static function getMax($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $selectField = "MAX(ROUND($weightField)) AS max_weight";
    $weightDAO = &CRM_Utils_Weight::query('SELECT', $daoName, $fieldValues, $selectField);
    $weightDAO->fetch();
    if ($weightDAO->max_weight) {
      return $weightDAO->max_weight;
    }
    return 0;
  }

  /**
   * Get the default weight for a new row (highest weight + 1).
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return int
   *   The next available weight value.
   */
  public static function getDefaultWeight($daoName, $fieldValues = NULL, $weightField = 'weight') {
    $maxWeight = CRM_Utils_Weight::getMax($daoName, $fieldValues, $weightField);
    return $maxWeight + 1;
  }

  /**
   * Execute a weight-related SQL query.
   *
   * Builds and executes a SELECT, UPDATE, or DELETE query against
   * the table associated with the given DAO class.
   *
   * @param string $queryType
   *   The type of query: 'SELECT', 'UPDATE', or 'DELETE'.
   * @param string $daoName
   *   Full class name of the DAO.
   * @param array<string, mixed>|null $fieldValues
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $queryData
   *   Query-type-dependent data: column list for SELECT, SET clause
   *   for UPDATE, or additional WHERE condition for DELETE.
   * @param string|null $additionalWhere
   *   Optional additional WHERE clause fragment.
   * @param string|null $orderBy
   *   Optional ORDER BY clause (used only for SELECT).
   * @param string|null $groupBy
   *   Optional GROUP BY clause (used only for SELECT).
   *
   * @return CRM_Core_DAO|false
   *   The DAO result object, or FALSE if an invalid field or
   *   unknown query type is specified.
   */
  public static function &query(
    $queryType,
    $daoName,
    $fieldValues,
    $queryData,
    $additionalWhere = NULL,
    $orderBy = NULL,
    $groupBy = NULL
  ) {

    require_once(str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");

    $dao = new $daoName();
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

  /**
   * Add order navigation links (arrows) to rows for weight reordering.
   *
   * Generates HTML links with up/down/top/bottom arrow icons for each
   * row, enabling users to reorder items via the admin weight endpoint.
   *
   * @param array<int, array<string, mixed>> $rows
   *   The rows to add order links to, keyed by row ID. Modified by reference.
   * @param string $daoName
   *   Full class name of the DAO.
   * @param string $idName
   *   The column name of the ID field in the DAO table.
   * @param string $returnURL
   *   The URL to redirect back to after reordering.
   * @param string|null $filter
   *   Optional filter string for the WHERE clause (e.g., 'option_group_id=1').
   *
   * @return void
   */
  public static function addOrder(&$rows, $daoName, $idName, $returnURL, $filter = NULL) {
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
    $baseURL = CRM_Utils_System::url(
      'civicrm/admin/weight',
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

  /**
   * Fix the weight order based on HTTP request parameters.
   *
   * Retrieves source (src) and destination (dst) IDs along with
   * direction (dir: 'swap', 'first', 'last') from the request,
   * then reorders weights accordingly. Redirects to the return URL
   * when done. Also corrects any duplicate weights before reordering.
   *
   * @return void
   */
  public static function fixOrder() {
    $daoName = CRM_Utils_Request::retrieve('dao', 'String', CRM_Core_DAO::$_nullObject);
    $id = CRM_Utils_Request::retrieve('id', 'Integer', CRM_Core_DAO::$_nullObject);
    $idName = CRM_Utils_Request::retrieve('idName', 'String', CRM_Core_DAO::$_nullObject);
    $url = CRM_Utils_Request::retrieve('url', 'String', CRM_Core_DAO::$_nullObject);
    $filter = CRM_Utils_Request::retrieve('filter', 'String', CRM_Core_DAO::$_nullObject);
    $src = CRM_Utils_Request::retrieve('src', 'Integer', CRM_Core_DAO::$_nullObject);
    $dst = CRM_Utils_Request::retrieve('dst', 'Integer', CRM_Core_DAO::$_nullObject);
    $dir = CRM_Utils_Request::retrieve('dir', 'String', CRM_Core_DAO::$_nullObject);

    $wheres = explode('AND', $filter);
    foreach ($wheres as $where) {
      $where_array = explode('=', $where);
      $fieldValues[trim($where_array[0])] = trim($where_array[1]);
    }
    if (self::isDuplicateWeights($daoName, $fieldValues)) {
      self::correctDuplicateWeights($daoName, $fieldValues);
    }

    $srcWeight = CRM_Core_DAO::getFieldValue(
      $daoName,
      $src,
      'weight',
      $idName
    );
    $dstWeight = CRM_Core_DAO::getFieldValue(
      $daoName,
      $dst,
      'weight',
      $idName
    );
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");
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
   * Check if there are multiple rows with the same weight value.
   *
   * @param string $daoName
   *   Full class name of the DAO.
   * @param array<string, mixed>|null $filter
   *   Field-value pairs to filter rows in the WHERE clause.
   * @param string $weightField
   *   Column name that stores the weight value. Defaults to 'weight'.
   *
   * @return bool
   *   TRUE if duplicate weights exist, FALSE otherwise.
   */
  public static function isDuplicateWeights($daoName, $filter, $weightField = 'weight') {
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
