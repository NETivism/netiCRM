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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Class handles functions for JSON format
 */
class CRM_Utils_JSON {

  /**
   * Encode an array of items into a Dojo-style identifier/items object string.
   *
   * Produces a non-standard JSON-like string used by legacy Dojo data stores,
   * of the form: { identifier: "id", items: [{ name: "...", id: "..."}, ...] }
   *
   * @param array  $params      Array of associative arrays, each with at least a 'name' key
   *                            and the key named by $identifier.
   * @param string $identifier  The field name to use as the item identifier (default 'id').
   *
   * @return string  A Dojo-style object string (not valid JSON).
   */
  public static function encode($params, $identifier = 'id') {
    $buildObject = [];
    foreach ($params as $value) {
      $name = addslashes($value['name']);
      $buildObject[] = "{ name: \"$name\", {$identifier}:\"{$value[$identifier]}\"}";
    }

    $jsonObject = '{ identifier: "' . $identifier . '", items: [' . CRM_Utils_Array::implode(',', $buildObject) . ' ]}';

    return $jsonObject;
  }

  /**
   * Function to encode json format for flexigrid, NOTE: "id" should be present in $params for each row
   *
   * @param array  $params associated array of values rows
   * @param int    $page  page no for selector
   * @param array  $selectorElements selector rows
   *
   * @return json encode string
   */
  public static function encodeSelector(&$params, $page, $total, $selectorElements) {
    $json = "";
    $json .= "{\n";
    $json .= "page: $page,\n";
    $json .= "total: $total,\n";
    $json .= "rows: [";
    $rc = FALSE;

    foreach ($params as $key => $value) {
      if ($rc) {
        $json .= ",";
      }
      $json .= "\n{";
      $json .= "id:'" . $value['id'] . "',";
      $json .= "cell:[";
      $addcomma = FALSE;
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $json .= ",";
        }
        $json .= "'" . addslashes($value[$element]) . "'";
        $addcomma = TRUE;
      }
      $json .= "]}";
      $rc = TRUE;
    }

    $json .= "]\n";
    $json .= "}";

    return $json;
  }

  public static function encodeDataTableSelector($params, $sEcho, $iTotal, $iFilteredTotal, $selectorElements) {

    $sOutput = '{';
    $sOutput .= '"sEcho": ' . intval($sEcho) . ', ';
    $sOutput .= '"iTotalRecords": ' . $iTotal . ', ';
    $sOutput .= '"iTotalDisplayRecords": ' . $iFilteredTotal . ', ';
    $sOutput .= '"aaData": [ ';
    foreach ($params as $key => $value) {
      $addcomma = FALSE;
      $sOutput .= "[";
      foreach ($selectorElements as $element) {
        if ($addcomma) {
          $sOutput .= ",";
        }
        //$sOutput .= '"'.addslashes($value[$element]).'"';

        //CRM-7130 --lets addslashes to only double quotes,
        //since we are using it to quote the field value.
        $sOutput .= '"' . addcslashes($value[$element], '"\\') . '"';

        $addcomma = TRUE;
      }
      $sOutput .= "],";
    }
    $sOutput = substr_replace($sOutput, "", -1);
    $sOutput .= '] }';

    return $sOutput;
  }
}
