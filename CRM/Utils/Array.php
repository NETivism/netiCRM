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
class CRM_Utils_Array {

  /**
   * if the key exists in the list returns the associated value
   *
   * @access public
   *
   * @param string $key   the key value
   * @param array $list  the array to be searched
   * @param mixed $default the default value when no value found, default NULL
   *
   * @return mixed if exists return array value, else null
   * @static
   * @access public
   *
   */
  static function value($key, $list, $default = NULL) {
    if (is_array($list)) {
      // faster
      if (isset($list[$key])) {
        return $list[$key];
      }
    }
    return $default;
  }

  /**
   * Given a parameter array and a key to search for,
   * search recursively for that key's value.
   *
   * @param array $values     The parameter array
   * @param string $key       The key to search for
   *
   * @return mixed            The value of the key, or null.
   * @access public
   * @static
   */
  static function retrieveValueRecursive(&$params, $key) {
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
   * if the value exists in the list returns the associated key
   *
   * @access public
   *
   * @param string $value the key of array
   * @param array $list pass by referenced, the array to be searched
   *
   * @return string|null if exists else null
   * @static
   * @access public
   *
   */
  static function key($value, &$list) {
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

  static function &xml(&$list, $depth = 1, $seperator = "\n") {
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

  static function escapeXML($value) {
    static $src = NULL;
    static $dst = NULL;

    if (!$src) {
      $src = array('&', '<', '>', '');
      $dst = array('&amp;', '&lt;', '&gt;', ',');
    }

    return str_replace($src, $dst, $value);
  }

  static function flatten(&$list, &$flat, $prefix = '', $seperator = ".") {
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
   * Funtion to merge to two arrays recursively
   *
   * @param array $a1
   * @param array $a2
   *
   * @return  $a3
   * @static
   */
  static function arrayMerge($a1, $a2) {
    if (empty($a1)) {
      return $a2;
    }

    if (empty($a2)) {
      return $a1;
    }

    $a3 = array();
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

  static function isHierarchical(&$list) {
    foreach ($list as $n => $v) {
      if (is_array($v)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Array deep copy
   *
   * @param  array  $array
   * @param  int    $maxdepth
   * @param  int    $depth
   *
   * @return  array  copy of the array
   *
   * @static
   * @access public
   */
  static function arrayDeepCopy(&$array, $maxdepth = 50, $depth = 0) {
    if ($depth > $maxdepth) {
      return $array;
    }
    $copy = array();
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
   * Array splice function that preserves associative keys
   * defauly php array_splice function doesnot preserve keys
   * So specify start and end of the array that you want to remove
   *
   * @param  array $params  array to slice
   * @param  int $start
   * @param  int $end
   *
   * @return  void
   * @static
   */
  static function arraySplice(&$params, $start, $end) {
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
   * Function for case insensitive in_array search
   *
   * @param string $value value or search string
   * @param array $params array that need to be searched
   * @param boolean $caseInsensitive   boolean true or false
   *
   * @return bool
   * @static
   */
  static function inArray($value, $params, $caseInsensitive = TRUE) {
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
   * look up property from given lookup, and padding to defaults
   *
   * Use one array value to search another array's value. Then padding retrieved value back to original array
   *
   * @param array $defaults the referenced array that retrive needle value from property
   * @param string $property array key name of defaults array. Will using retrieved value to 
   * @param array $lookup haystack. Value found here will padding back to defaults
   * @param boolean $reverse will flip lookup before search
   * @return void 
   */
  static function lookupValue(&$defaults, $property, $lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!CRM_Utils_Array::arrayKeyExists(strtolower($src), array_change_key_case($defaults, CASE_LOWER))) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    //trim lookup array, ignore . ( fix for CRM-1514 ), eg for prefix/suffix make sure Dr. and Dr both are valid
    $newLook = array();
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
   *  Function to check if give array is empty
   *
   *  @param array $array array that needs to be check for empty condition
   *
   *  @return boolean true is array is empty else false
   *  @static
   */
  static function isEmpty($array = array()) {
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
  public static function asort($array = array()) {
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
        $r[$part] = array();
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
   * Deprecated function, use inArray instead
   *
   * @param string $value value or search string
   * @param array $params array that need to be searched
   * @param boolean $caseInsensitive   boolean true or false
   *
   * @static
   */
  public static function crmInArray($value, $params, $caseInsensitive = TRUE) {
    self::inArray($value, $params, $caseInsensitive);
  }

  /**
   * Deprecated function, use isEmpty
   *
   * @param array $array array that needs to be check for empty condition
   *
   * @return bool
   * @static
   */
  static function crmIsEmptyArray($array = array()) {
    return self::isEmpty($array);
  }
}

