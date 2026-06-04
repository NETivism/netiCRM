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
 * Utility methods for array manipulation, traversal, and transformation
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Utils_Array {

  /**
   * Return the value for a key in an array, or a default if the key does not exist.
   *
   * @param string|int $key      The key to look up.
   * @param array      $list     The array to search.
   * @param mixed      $default  Value to return when the key is not found (default NULL).
   *
   * @return mixed  The value at $key, or $default if not found.
   */
  public static function value($key, $list, $default = NULL) {
    if (is_array($list)) {
      // faster
      if (isset($list[$key])) {
        return $list[$key];
      }
    }
    return $default;
  }

  /**
   * Recursively search a nested array for the first occurrence of a key and return its value.
   *
   * @param array  $params  The array to search (passed by reference).
   * @param string $key     The key to search for.
   *
   * @return mixed|null  The value of the key, or NULL if not found.
   */
  public static function retrieveValueRecursive(&$params, $key) {
    if (!is_array($params)) {
      return NULL;
    }
    elseif ($value = CRM_Utils_Array::value($key, $params)) {
      return $value;
    }
    else {
      foreach ($params as $subParam) {
        if (is_array($subParam) &&
          $value = self::retrieveValueRecursive($subParam, $key)
        ) {
          return $value;
        }
      }
    }
    return NULL;
  }

  /**
   * Find the key associated with a given value in an array.
   *
   * @param mixed $value The value to search for.
   * @param array $list  The array to be searched (passed by reference).
   *
   * @return string|int|null The key if found, or NULL otherwise.
   */
  public static function key($value, &$list) {
    if (is_array($list)) {
      $key = array_search($value, $list);

      // array_search returns key if found, false otherwise
      // it may return values like 0 or empty string which
      // evaluates to false
      // hence we must use identical comparison operator
      return ($key === FALSE) ? NULL : $key;
    }
    return NULL;
  }

  /**
   * Convert an associative array to an XML string.
   *
   * @param array  $list      The array to convert (passed by reference).
   * @param int    $depth     Current nesting depth for indentation.
   * @param string $seperator Line separator between XML elements.
   *
   * @return string The generated XML string.
   */
  public static function &xml(&$list, $depth = 1, $seperator = "\n") {
    $xml = '';
    foreach ($list as $name => $value) {
      $xml .= str_repeat(' ', $depth * 4);
      if (is_array($value)) {
        $xml .= "<{$name}>{$seperator}";
        $xml .= self::xml($value, $depth + 1, $seperator);
        $xml .= str_repeat(' ', $depth * 4);
        $xml .= "</{$name}>{$seperator}";
      }
      else {
        // make sure we escape value
        $value = self::escapeXML($value);
        $xml .= "<{$name}>$value</{$name}>{$seperator}";
      }
    }
    return $xml;
  }

  /**
   * Escape special XML characters in a string.
   *
   * Replaces &, <, > and null bytes with their XML entity equivalents.
   *
   * @param string $value The string to escape.
   *
   * @return string The escaped string.
   */
  public static function escapeXML($value) {
    static $src = NULL;
    static $dst = NULL;

    if (!$src) {
      $src = ['&', '<', '>', ''];
      $dst = ['&amp;', '&lt;', '&gt;', ','];
    }

    return str_replace($src, $dst, $value);
  }

  /**
   * Flatten a nested associative array into a single-level array with compound keys.
   *
   * Non-empty leaf values are stored with keys formed by joining nested
   * key names using the specified separator.
   *
   * @param array  $list      The nested array to flatten (passed by reference).
   * @param array  $flat      The resulting flat array (passed by reference, populated in place).
   * @param string $prefix    Key prefix for the current recursion level.
   * @param string $seperator Separator used to join key segments.
   *
   * @return void
   */
  public static function flatten(&$list, &$flat, $prefix = '', $seperator = ".") {
    foreach ($list as $name => $value) {
      $newPrefix = ($prefix) ? $prefix . $seperator . $name : $name;
      if (is_array($value)) {
        self::flatten($value, $flat, $newPrefix, $seperator);
      }
      else {
        if (!empty($value)) {
          $flat[$newPrefix] = $value;
        }
      }
    }
  }

  /**
   * Merge two arrays recursively, combining sub-arrays that share the same key.
   *
   * Unlike array_merge_recursive(), values from $a1 take precedence for
   * non-array keys, and sub-arrays are merged (not nested).
   *
   * @param array $a1 The first array.
   * @param array $a2 The second array.
   *
   * @return array The merged array.
   */
  public static function arrayMerge($a1, $a2) {
    if (empty($a1)) {
      return $a2;
    }

    if (empty($a2)) {
      return $a1;
    }

    $a3 = [];
    foreach ($a1 as $key => $value) {
      if (CRM_Utils_Array::arrayKeyExists($key, $a2) &&
        is_array($a2[$key]) && is_array($a1[$key])
      ) {
        $a3[$key] = array_merge($a1[$key], $a2[$key]);
      }
      else {
        $a3[$key] = $a1[$key];
      }
    }

    foreach ($a2 as $key => $value) {
      if (CRM_Utils_Array::arrayKeyExists($key, $a1)) {
        // already handled in above loop
        continue;
      }
      $a3[$key] = $a2[$key];
    }

    return $a3;
  }

  /**
   * Determine whether an array contains any nested arrays (i.e. is hierarchical).
   *
   * @param array $list The array to check (passed by reference).
   *
   * @return bool TRUE if at least one element is an array, FALSE otherwise.
   */
  public static function isHierarchical(&$list) {
    foreach ($list as $n => $v) {
      if (is_array($v)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Create a deep copy of an array up to a maximum recursion depth.
   *
   * @param array $array    The array to copy (passed by reference).
   * @param int   $maxdepth Maximum recursion depth to prevent infinite loops.
   * @param int   $depth    Current recursion depth (used internally).
   *
   * @return array A deep copy of the array.
   */
  public static function arrayDeepCopy(&$array, $maxdepth = 50, $depth = 0) {
    if ($depth > $maxdepth) {
      return $array;
    }
    $copy = [];
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        self::arrayDeepCopy($copy[$key], $maxdepth, ++$depth);
      }
      else {
        $copy[$key] = $value;
      }
    }
    return $copy;
  }

  /**
   * Remove a range of elements from an associative array while preserving keys.
   *
   * Unlike PHP's built-in array_splice(), this function preserves
   * associative keys. Specify the start and end indices (0-based)
   * of the elements to remove.
   *
   * @param array $params The array to modify (passed by reference).
   * @param int   $start  The start index (inclusive).
   * @param int   $end    The end index (exclusive).
   *
   * @return void
   */
  public static function arraySplice(&$params, $start, $end) {
    // verify start and end date
    if ($start < 0) {
      $start = 0;
    }
    if ($end > count($params)) {
      $end = count($params);
    }

    $i = 0;

    // procees unset operation
    foreach ($params as $key => $value) {
      if ($i >= $start && $i < $end) {
        unset($params[$key]);
      }
      $i++;
    }
  }

  /**
   * Search for a value in an array, optionally case-insensitive and recursive.
   *
   * @param string $value           The value to search for.
   * @param array  $params          The array to search.
   * @param bool   $caseInsensitive Whether to perform a case-insensitive comparison.
   *
   * @return bool TRUE if the value is found, FALSE otherwise.
   */
  public static function inArray($value, $params, $caseInsensitive = TRUE) {
    foreach ($params as $item) {
      if (is_array($item)) {
        $ret = self::inArray($value, $item, $caseInsensitive);
      }
      else {
        $ret = ($caseInsensitive) ? strtolower($item) == strtolower($value) : $item == $value;
        if ($ret) {
          return $ret;
        }
      }
    }
    return FALSE;
  }

  /**
   * Strict type version of array_key_exists
   *
   * During php 8, null given args will throw fatal error, use this for safer replacement
   *
   * @param string|int $key
   * @param array $array
   * @return bool
   */
  public static function arrayKeyExists($key, $array) {
    if (!is_array($array)) {
      return FALSE;
    }
    return array_key_exists($key, $array);
  }

  /**
   * Strict type version of implode
   *
   * During php 8, null given args will throw fatal error, use this for safer replacement
   *
   * @param string $separator
   * @param array $array
   * @return string
   */
  public static function implode($separator, $array) {
    if (!is_array($array)) {
      return '';
    }
    return implode($separator, $array);
  }

  /**
   * Look up a value from a mapping array and store the result back in $defaults.
   *
   * Uses a value from $defaults (keyed by $property or $property_id) to find
   * the corresponding entry in $lookup, then writes the result back into
   * $defaults under the complementary key.
   *
   * @param array  $defaults The array containing the source value and receiving the result (passed by reference).
   * @param string $property The base property name (e.g. 'prefix'). The ID key is derived as "{$property}_id".
   * @param array  $lookup   The mapping array to search.
   * @param bool   $reverse  If TRUE, flip $lookup before searching (swap keys and values).
   *
   * @return bool TRUE if the lookup succeeded, FALSE if the source key or value was not found.
   */
  public static function lookupValue(&$defaults, $property, $lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!CRM_Utils_Array::arrayKeyExists(strtolower($src), array_change_key_case($defaults, CASE_LOWER))) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    //trim lookup array, ignore . ( fix for CRM-1514 ), eg for prefix/suffix make sure Dr. and Dr both are valid
    $newLook = [];
    foreach ($look as $k => $v) {
      $newLook[trim($k, ".")] = $v;
    }

    $look = $newLook;

    if (is_array($look)) {
      if (!CRM_Utils_Array::arrayKeyExists(trim(strtolower($defaults[strtolower($src)]), '.'), array_change_key_case($look, CASE_LOWER))) {
        return FALSE;
      }
    }

    $tempLook = array_change_key_case($look, CASE_LOWER);

    $defaults[$dst] = $tempLook[trim(strtolower($defaults[strtolower($src)]), '.')];
    return TRUE;
  }

  /**
   * Check whether an array is empty or not actually an array.
   *
   * @param mixed $array The value to check.
   *
   * @return bool TRUE if $array is not an array or is empty, FALSE otherwise.
   */
  public static function isEmpty($array = []) {
    if (!is_array($array)) {
      return TRUE;
    }
    if (empty($array)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Sorts an array and maintains index association (with localization).
   *
   * Uses Collate from the PECL "intl" package, if available, for UTF-8
   * sorting (e.g. list of countries). Otherwise calls PHP's asort().
   *
   * On Debian/Ubuntu: apt-get install php5-intl
   *
   * @param array $array array to be sorted.
   *
   * @return array Sorted array.
   */
  public static function asort($array = []) {
    $lcMessages = CRM_Utils_System::getUFLocale();

    if ($lcMessages && $lcMessages != 'en_US' && class_exists('Collator')) {
      $collator = new Collator($lcMessages . '.utf8');
      $collator->asort($array);
    }
    else {
      // This calls PHP's built-in asort().
      asort($array);
    }

    return $array;
  }

  /**
   * Get a single value from an array-tree.
   *
   * @param array $values
   *   Ex: ['foo' => ['bar' => 123]].
   * @param array $path
   *   Ex: ['foo', 'bar'].
   * @param mixed $default
   * @return mixed
   *   Ex 123.
   */
  public static function pathGet($values, $path, $default = NULL) {
    foreach ($path as $key) {
      if (!is_array($values) || !isset($values[$key])) {
        return $default;
      }
      $values = $values[$key];
    }
    return $values;
  }

  /**
   * Check if a key isset which may be several layers deep.
   *
   * This is a helper for when the calling function does not know how many layers deep
   * the path array is so cannot easily check.
   *
   * @param array $values
   * @param array $path
   * @return bool
   */
  public static function pathIsset($values, $path) {
    foreach ($path as $key) {
      if (!is_array($values) || !isset($values[$key])) {
        return FALSE;
      }
      $values = $values[$key];
    }
    return TRUE;
  }

  /**
   * Set a single value in an array tree.
   *
   * @param array $values
   *   Ex: ['foo' => ['bar' => 123]].
   * @param array $pathParts
   *   Ex: ['foo', 'bar'].
   * @param $value
   *   Ex: 456.
   */
  public static function pathSet(&$values, $pathParts, $value) {
    $r = &$values;
    $last = array_pop($pathParts);
    foreach ($pathParts as $part) {
      if (!isset($r[$part])) {
        $r[$part] = [];
      }
      $r = &$r[$part];
    }
    $r[$last] = $value;
  }

  /**
   * Deprecated function, use arrayMerge instead
   *
   * @param array $a1
   * @param array $a2
   * @return array
   */
  public static function crmArrayMerge($a1, $a2) {
    return self::arrayMerge($a1, $a2);
  }

  /**
   * Deprecated function, use arraySplice instead
   *
   * @param array $params
   * @param int $start
   * @param int $end
   * @return void
   */
  public static function crmArraySplice(&$params, $start, $end) {
    self::arraySplice($params, $start, $end);
  }

  /**
   * Deprecated: use inArray() instead.
   *
   * @param string $value           The value to search for.
   * @param array  $params          The array to search.
   * @param bool   $caseInsensitive Whether to perform a case-insensitive comparison.
   *
   * @return bool TRUE if found, FALSE otherwise.
   *
   * @deprecated
   */
  public static function crmInArray($value, $params, $caseInsensitive = TRUE) {
    self::inArray($value, $params, $caseInsensitive);
  }

  /**
   * Deprecated: use isEmpty() instead.
   *
   * @param mixed $array The value to check.
   *
   * @return bool TRUE if empty or not an array.
   *
   * @deprecated
   */
  public static function crmIsEmptyArray($array = []) {
    return self::isEmpty($array);
  }
}
