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
 * class for managing a http request
 *
 */
class CRM_Utils_Request {

  /**
   * Singleton instance placeholder (currently unused).
   *
   * @var object
   */
  private static $_singleton = NULL;

  /**
   * class constructor
   */
  public function __construct() {
  }

  /**
   * Retrieve a typed variable from the HTTP request, with optional session fallback.
   *
   * Looks up $name in the appropriate superglobal ($_GET, $_POST, or $_REQUEST),
   * validates/casts the value using CRM_Utils_Type::validate(), then falls back to
   * the session store $store if the request value is absent. If $abort is TRUE and
   * no value can be found, triggers a status bounce.
   *
   * @param string      $name     Name of the request/session variable to retrieve.
   * @param string      $type     Type name as understood by CRM_Utils_Type (e.g. 'Integer', 'String').
   * @param object|null $store    Session/store object implementing get()/set(), or NULL to skip session lookup.
   * @param bool        $abort    When TRUE, redirects with an error message if the variable is missing.
   * @param mixed       $default  Fallback value when the variable is not found in the request or session.
   * @param string      $method   HTTP method to read from: 'GET' (default), 'POST', or any other value for $_REQUEST.
   *
   * @return mixed  The retrieved, validated, and type-cast value, or NULL if not found and $abort is FALSE.
   */
  public static function retrieve($name, $type, &$store, $abort = FALSE, $default = NULL, $method = 'GET') {

    // hack to detect stuff not yet converted to new style
    if (!is_string($type)) {
      CRM_Core_Error::backtrace();
      CRM_Core_Error::fatal(ts("Please convert retrieve call to use new function signature"));
    }

    $value = NULL;
    switch ($method) {
      case 'GET':
        $value = CRM_Utils_Array::value($name, $_GET);
        break;

      case 'POST':
        $value = CRM_Utils_Array::value($name, $_POST);
        break;

      default:
        $value = CRM_Utils_Array::value($name, $_REQUEST);
        break;
    }

    if (isset($value) &&
      (CRM_Utils_Type::validate($value, $type, $abort, $name) === NULL)
    ) {
      $value = NULL;
    }

    if (!isset($value) && $store) {
      $value = $store->get($name);
    }

    if (!isset($value) && $abort) {
      return CRM_Core_Error::statusBounce(ts("Could not find valid value for %1", [1 => $name]));
    }

    if (!isset($value) && $default !== NULL) {
      $value = $default;
    }

    // minor hack for action
    if ($name == 'action' && is_string($value)) {
      $value = CRM_Core_Action::resolve($value);
    }

    if (isset($value) && $store) {
      $store->set($name, $value);
    }

    return $value;
  }

  /**
   * Follow an HTTP redirect and return the destination URL extracted from the response body.
   *
   * Performs a cURL request to $url and attempts to parse the first anchor href
   * from the HTML response. Returns an empty string if no redirect destination is found.
   *
   * @param string $url  The URL to fetch and inspect for a redirect link.
   *
   * @return string  The destination URL found in the response, or an empty string.
   */
  public static function getRedirectDestination($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $redirect = curl_exec($ch);
    curl_close($ch);
    if (preg_match('/<a href="([^"]*)"/i', $redirect, $match)) {
      $redirect = $match[1];
      if ($redirect) {
        return $redirect;
      }
    }
    return '';
  }
}
