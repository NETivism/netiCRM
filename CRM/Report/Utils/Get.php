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
 * Processes URL parameters and query string values for report filtering
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Report_Utils_Get {

  /**
   * Retrieves a GET parameter and escapes it to the requested CRM type.
   *
   * @param string $name GET parameter name.
   * @param int $type CRM_Utils_Type constant (e.g. CRM_Utils_Type::T_STRING, T_INT).
   *
   * @return mixed|null The typed and escaped value, or NULL if the parameter is absent.
   */
  public static function getTypedValue($name, $type) {
    $value = CRM_Utils_Array::value($name, $_GET);
    if ($value === NULL) {
      return NULL;
    }
    return CRM_Utils_Type::escape(
      $value,
      CRM_Utils_Type::typeToString($type),
      FALSE
    );
  }

  /**
   * Reads date range GET parameters for a report filter field and populates defaults.
   * Supports both explicit _from/_to values and a _relative shorthand via
   * CRM_Report_Form::getFromTo(). Clears other filter defaults when a value is found.
   *
   * @param string $fieldName Base field name used to derive GET keys ({fieldName}_from,
   *   {fieldName}_to, {fieldName}_relative).
   * @param array &$field Field definition array (unused; kept for API consistency).
   * @param array &$defaults Report form defaults array to populate with date values.
   *
   * @return bool FALSE if neither from nor to value is present; no return otherwise.
   */
  public static function dateParam($fieldName, &$field, &$defaults) {
    // type = 12 (datetime) is not recognized by Utils_Type::escape() method,
    // and therefore the below hack
    $type = 4;

    $from = self::getTypedValue("{$fieldName}_from", $type);
    $to = self::getTypedValue("{$fieldName}_to", $type);

    $relative = CRM_Utils_Array::value("{$fieldName}_relative", $_GET);
    if ($relative) {
      list($from, $to) = CRM_Report_Form::getFromTo($relative, NULL, NULL);
      $from = substr($from, 0, 8);
      $to = substr($to, 0, 8);
    }

    if (!($from || $to)) {
      return FALSE;
    }
    elseif ($from || $to || $relative) {
      // unset other criteria
      self::unsetFilters($defaults);
    }

    if ($from !== NULL) {
      $dateFrom = CRM_Utils_Date::setDateDefaults($from);
      if ($dateFrom !== NULL &&
        !empty($dateFrom[0])
      ) {
        $defaults["{$fieldName}_from"] = $dateFrom[0];
      }
    }

    if ($to !== NULL) {
      $dateTo = CRM_Utils_Date::setDateDefaults($to);
      if ($dateTo !== NULL &&
        !empty($dateTo[0])
      ) {
        $defaults["{$fieldName}_to"] = $dateTo[0];
      }
    }
  }

  /**
   * Reads a string filter GET parameter and populates defaults with the value and operator.
   * Supported operators: has, sw (starts with), ew (ends with), nhas (not has), like, neq.
   *
   * @param string $fieldName Base field name used to derive GET keys ({fieldName}_value, {fieldName}_op).
   * @param array &$field Field definition array; 'type' key is used for value escaping.
   * @param array &$defaults Report form defaults array to populate.
   *
   * @return void
   */
  public static function stringParam($fieldName, &$field, &$defaults) {
    $fieldOP = CRM_Utils_Array::value("{$fieldName}_op", $_GET, 'like');

    switch ($fieldOP) {
      case 'has':
      case 'sw':
      case 'ew':
      case 'nhas':
      case 'like':
      case 'neq':
        $value = self::getTypedValue("{$fieldName}_value", $field['type']);
        if ($value !== NULL) {
          self::unsetFilters($defaults);
          $defaults["{$fieldName}_value"] = $value;
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;
    }
  }

  /**
   * Reads an integer or money filter GET parameter and populates defaults.
   * Supported operators: lte, gte, eq, lt, gt, neq (single-value);
   * bw/nbw (between, uses _min/_max keys); in (comma-separated list, max 15 values).
   *
   * @param string $fieldName Base field name used to derive GET keys.
   * @param array &$field Field definition array; 'type' key is used for value escaping.
   * @param array &$defaults Report form defaults array to populate.
   *
   * @return void
   */
  public static function intParam($fieldName, &$field, &$defaults) {
    $fieldOP = CRM_Utils_Array::value("{$fieldName}_op", $_GET, 'eq');

    switch ($fieldOP) {
      case 'lte':
      case 'gte':
      case 'eq':
      case 'lt':
      case 'gt':
      case 'neq':
        $value = self::getTypedValue("{$fieldName}_value", $field['type']);
        if ($value !== NULL) {
          self::unsetFilters($defaults);
          $defaults["{$fieldName}_value"] = $value;
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;

      case 'bw':
      case 'nbw':
        $minValue = self::getTypedValue("{$fieldName}_min", $field['type']);
        $maxValue = self::getTypedValue("{$fieldName}_max", $field['type']);
        if ($minValue !== NULL ||
          $maxValue !== NULL
        ) {
          self::unsetFilters($defaults);
          if ($minValue !== NULL) {
            $defaults["{$fieldName}_min"] = $minValue;
          }
          if ($maxValue !== NULL) {
            $defaults["{$fieldName}_max"] = $maxValue;
          }
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;

      case 'in':
        // send the type as string so that multiple values can also be retrieved from url.
        // for e.g url like - "memtype_in=in&memtype_value=1,2,3"
        $value = self::getTypedValue("{$fieldName}_value", CRM_Utils_Type::T_STRING);
        if (!preg_match('/^(\d+)(,\d+){0,14}$/', $value)) {
          // extra check. Also put a limit of 15 max values.
          $value = NULL;
        }
        // unset any default filters already applied for example - incase of an instance.
        self::unsetFilters($defaults);
        if ($value !== NULL) {
          $defaults["{$fieldName}_value"] = explode(",", $value);
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;
    }
  }

  /**
   * Reads the chart type GET parameter and sets it in defaults if it is a valid chart type.
   * Valid values: 'barChart', 'pieChart'.
   *
   * @param array &$defaults Report form defaults array to populate with the 'charts' key.
   *
   * @return void
   */
  public static function processChart(&$defaults) {
    $chartType = CRM_Utils_Array::value("charts", $_GET);
    if (in_array($chartType, ['barChart', 'pieChart'])) {
      $defaults["charts"] = $chartType;
    }
  }

  /**
   * Iterates over all filter fields grouped by table and dispatches to the appropriate
   * parameter handler based on field type (int/money → intParam, string → stringParam,
   * date/datetime → dateParam).
   *
   * @param array &$fieldGrp Two-dimensional array: tableName => [ fieldName => fieldDef ].
   * @param array &$defaults Report form defaults array to populate.
   *
   * @return void
   */
  public static function processFilter(&$fieldGrp, &$defaults) {
    // process only filters for now
    foreach ($fieldGrp as $tableName => $fields) {
      foreach ($fields as $fieldName => $field) {
        switch (CRM_Utils_Array::value('type', $field)) {
          case CRM_Utils_Type::T_INT:
          case CRM_Utils_Type::T_MONEY:
            self::intParam($fieldName, $field, $defaults);
            break;

          case CRM_Utils_Type::T_STRING:
            self::stringParam($fieldName, $field, $defaults);
            break;

          case CRM_Utils_Type::T_DATE:
          case CRM_Utils_Type::T_DATE | CRM_Utils_Type::T_TIME:
            self::dateParam($fieldName, $field, $defaults);
            break;
        }
      }
    }
  }

  /**
   * Removes all filter-related keys from the defaults array on the first call.
   * Subsequent calls within the same request are no-ops (uses a static flag).
   * Keys removed are those ending in: _value, _op, _min, _max, _from, _to, _relative.
   *
   * @param array &$defaults Report form defaults array to clean up.
   *
   * @return void
   */
  public static function unsetFilters(&$defaults) {
    static $unsetFlag = TRUE;
    if ($unsetFlag) {
      foreach ($defaults as $field_name => $field_value) {
        $newstr = substr($field_name, strrpos($field_name, '_'));
        if ($newstr == '_value' || $newstr == '_op' ||
          $newstr == '_min' || $newstr == '_max' ||
          $newstr == '_from' || $newstr == '_to' ||
          $newstr == '_relative'
        ) {
          unset($defaults[$field_name]);
        }
      }
      $unsetFlag = FALSE;
    }
  }

  /**
   * Reads the 'gby' GET parameter (space-separated field names) and sets matching
   * group_bys in defaults. Clears any existing group_bys on the first match found.
   *
   * @param array &$fieldGrp Two-dimensional array: tableName => [ fieldName => fieldDef ].
   * @param array &$defaults Report form defaults array to populate with group_bys.
   *
   * @return void
   */
  public static function processGroupBy(&$fieldGrp, &$defaults) {
    // process only group_bys for now
    $flag = FALSE;

    if (is_array($fieldGrp)) {
      foreach ($fieldGrp as $tableName => $fields) {
        if ($groupBys = CRM_Utils_Array::value("gby", $_GET)) {
          $groupBys = explode(' ', $groupBys);
          if (!empty($groupBys)) {
            if (!$flag) {
              unset($defaults['group_bys']);
              $flag = TRUE;
            }
            foreach ($groupBys as $gby) {
              if (CRM_Utils_Array::arrayKeyExists($gby, $fields)) {
                $defaults['group_bys'][$gby] = 1;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Reads the 'fld' GET parameter (comma-separated field names) and enables matching
   * fields in the defaults['fields'] array.
   *
   * @param array &$reportFields Two-dimensional array: tableName => [ fieldName => fieldDef ].
   * @param array &$defaults Report form defaults array to populate with fields selections.
   *
   * @return void
   */
  public static function processFields(&$reportFields, &$defaults) {
    //add filters from url
    if (is_array($reportFields)) {
      if ($urlFields = CRM_Utils_Array::value("fld", $_GET)) {
        $urlFields = explode(',', $urlFields);
      }
      if (!empty($urlFields)) {
        foreach ($reportFields as $tableName => $fields) {
          foreach ($urlFields as $fld) {
            if (CRM_Utils_Array::arrayKeyExists($fld, $fields)) {
              $defaults['fields'][$fld] = 1;
            }
          }
        }
      }
    }
  }
}
