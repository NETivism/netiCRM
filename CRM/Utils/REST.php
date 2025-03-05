<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.2                                                |
   +--------------------------------------------------------------------+
   | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * This class handles all REST client requests.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 *
 */
class CRM_Utils_REST {
  const LAST_HIT = 'rest_lasthit';
  const RATE_LIMIT = 0.2;

  /**
   * Response row limit per request
   */
  static $limitRows = 100;

  /**
   * Number of seconds we should let a REST process idle
   * @static
   */
  static $rest_timeout = 0;

  /**
   * Cache the actual UF Class
   */
  public $ufClass;

  /**
   * Class constructor.  This caches the real user framework class locally,
   * so we can use it for authentication and validation.
   *
   * @param  string $uf       The userframework class
   */
  public function __construct() {
    // any external program which call Rest Server is responsible for
    // creating and attaching the session
    $args = func_get_args();
    $this->ufClass = array_shift($args);
  }

  /**
   * Simple ping function to test for liveness.
   *
   * @param string $var   The string to be echoed
   *
   * @return string       $var
   * @access public
   */
  public function ping($var = NULL) {
    $session = CRM_Core_Session::singleton();
    $key = $session->get('key');
    //$session->set( 'key', $var );
    return self::simple(array('message' => "PONG: $key"));
  }

  /**
   * Authentication wrapper to the UF Class
   *
   * @param string $name      Login name
   * @param string $pass      Password
   *
   * @return string           The REST Client key
   * @access public
   * @static
   */
  public function authenticate($name, $pass) {

    $result = &CRM_Utils_System::authenticate($name, $pass);

    if (empty($result)) {
      return self::error('Could not authenticate user, invalid name or password.');
    }

    $session = CRM_Core_Session::singleton();
    $api_key = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $result[0], 'api_key');

    if (empty($api_key)) {
      // These two lines can be used to set the initial value of the key.  A better means is needed.
      //CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact', $result[0], 'api_key', sha1($result[2]) );
      //$api_key = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $result[0], 'api_key');
      return self::error("This user does not have a valid API key in the database, and therefore cannot authenticate through this interface");
    }

    // Test to see if I can pull the data I need, since I know I have a good value.
    $contactId = &CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

    $session->set('api_key', $api_key);
    $session->set('key', $result[2]);
    $session->set('rest_time', time());
    $session->set('PHPSESSID', CRM_Utils_System::getSessionID());
    $session->set('cms_user_id', $result[1]);

    return self::simple(array('api_key' => $api_key, 'PHPSESSID' => CRM_Utils_System::getSessionID(), 'key' => sha1($result[2])));
  }

  // Generates values needed for error messages
  public static function error($message = 'Unknown Error') {

    $values = array(
      'error_message' => $message,
      'is_error' => 1,
    );
    return $values;
  }

  // Generates values needed for non-error responses.
  function simple($params) {
    $values = array('is_error' => 0);
    $values += $params;
    return $values;
  }

  function run() {
    $result = self::handle();
    return self::output($result);
  }

  function bootAndRun() {
    return $this->run();
  }

  function requestRateLimit($args) {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::LAST_HIT;
    if ($dao->find(TRUE)) {
      $interval = microtime(true) - $dao->timestamp;
      $config = CRM_Core_Config::singleton();
      $rateLimit = $config->restAPIRateLimit ? $config->restAPIRateLimit : self::RATE_LIMIT;
      if ($interval < $rateLimit) {
        return 'Request rate limit reached. Last hit: '.round($interval, 2).' seconds ago. Usage: '.$dao->value;
      }
      $dao->timestamp = microtime(true);
      $dao->value = CRM_Utils_Array::implode('-', $args);
      $dao->update();
    }
    else {
      $dao->timestamp = microtime(true);
      $dao->value = CRM_Utils_Array::implode('-', $args);
      $dao->insert();
    }
    return array();
  }

  static function output(&$result) {
    $hier = FALSE;
    if (is_scalar($result)) {
      if (!$result) {
        $result = 0;
      }
      $result = self::simple(array('result' => $result));
    }
    elseif (is_array($result)) {
      if (CRM_Utils_Array::isHierarchical($result)) {
        $hier = TRUE;
      }
      elseif (!CRM_Utils_Array::arrayKeyExists('is_error', $result)) {
        $result['is_error'] = 0;
      }
    }
    else {
      $result = self::error('Could not interpret return values from function.');
    }

    if (CRM_Utils_Array::value('xml', $_REQUEST)) {
      header('Content-Type: text/xml');
      if (isset($result['count'])) {


        $count = ' count="' . $result['count'] . '" ';
      }
      else $count = "";
      $xml = "<?xml version=\"1.0\"?>
        <ResultSet xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" $count>
        ";
      // check if this is a single element result (contact_get etc)
      // or multi element
      if ($hier) {
        foreach ($result['values'] as $n => $v) {
          $xml .= "<Result>\n" . CRM_Utils_Array::xml($v) . "</Result>\n";
        }
      }
      else {
        $xml .= "<Result>\n" . CRM_Utils_Array::xml($result) . "</Result>\n";
      }

      $xml .= "</ResultSet>\n";
      return $xml;
    }
    else {
      header('Content-Type: application/json; charset=utf-8');
      if (CRM_Utils_Array::value('debug', $_REQUEST)) {
        return json_encode(array_merge($result), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
      }
      if (CRM_Utils_Array::value('pretty', $_REQUEST)) {
        return json_encode(array_merge($result), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
      }
      $json = json_encode(array_merge($result));
      return $json;
    }
  }

  function handle() {
    // block ajax request REST API to prevent database info leak
    /* It's not reliable way to detect, and shouldn't block whole connection
    if(CRM_Utils_Array::arrayKeyExists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
      return self::error("FATAL: this API can only request from backend. *DO NOT* use ajax application call this.");
    }
    */
    // Get the function name being called from the q parameter in the query string

    // or for the rest interface, from fnName
    // If the function isn't in the civicrm namespace, reject the request.
    $args = array();

    // check from IP address when allowed list defined
    if (defined('CIVICRM_API_ALLOWED_IP')) {
      $allowedIPs = explode(',', CIVICRM_API_ALLOWED_IP);
      if (!empty($allowedIPs)) {
        $match = FALSE;
        $remoteIP = CRM_Utils_System::ipAddress();
        if (!empty($remoteIP)) {
          $match = CRM_Utils_Rule::checkIp($remoteIP, $allowedIPs);
        }
        if (!$match) {
          return self::error("FATAL: Your IP is not in allowed list.");
        }
      }
    }

    // or the new format (entity+action)
    $args[1] = CRM_Utils_Request::retrieve('entity', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $args[2] = CRM_Utils_Array::value('action', $_REQUEST);

    // Everyone should be required to provide the server key, so the whole
    //  interface can be disabled in more change to the configuration file.
    //  This used to be done in the authenticate function, but that was bad...trust me
    // first check for civicrm site key
    if (!CRM_Utils_System::authenticateKey(FALSE)) {
      return self::error("FATAL: site key or api key is incorrect.");
    }

    // At this point we know we are not calling either login or ping (neither of which
    //  require authentication prior to being called.  Therefore, at this point we need
    //  to make sure we're working with a trusted user.

    // There are two ways to check for a trusted user:
    //  First: they can be someone that has a valid session currently
    //  Second: they can be someone that has provided an API_Key
    $validUser = FALSE;

    // Check for valid session.  Session ID's only appear here if you have
    // run the rest_api login function.  That might be a problem for the
    // AJAX methods.
    $session = CRM_Core_Session::singleton();

    // If the user does not have a valid session (most likely to be used by people using
    // an ajax interface), we need to check to see if they are carring a valid user's
    // secret key.
    if (!$validUser) {
      if (isset($_SERVER['HTTP_X_CIVICRM_API_KEY'])) {
        $api_key = trim($_SERVER['HTTP_X_CIVICRM_API_KEY']);
      }
      else {
        $api_key = trim(CRM_Utils_Request::retrieve('api_key', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST'));
      }
      if (!$api_key || strtolower($api_key) == 'null') {
        return self::error("FATAL: site key or api key is incorrect.");
      }
      $api_key = CRM_Utils_Type::escape($api_key, 'String');
      $contactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
      if ($contactId) {
        $uid = CRM_Core_BAO_UFMatch::getUFId($contactId);
        if ($uid) {
          CRM_Utils_System::loadUser(array('uid' => $uid));
          $ufId = CRM_Utils_System::getLoggedInUfID();
          if (CRM_Utils_System::isUserLoggedIn() && $ufId == $uid) {
            $validUser = $contactId;
            $session->set('ufID', $uid);
            $session->set('userID', $contactId);
          }
        }
        if (!$validUser) {
          return self::error("FATAL: site key or api key is incorrect.");
        }
      }
    }

    // If we didn't find a valid user either way, then die.
    if (empty($validUser)) {
      return self::error("FATAL: site key or api key is incorrect.");
    }

    // check request limit
    $error = $this->requestRateLimit($args);
    if (!empty($error)) {
      return self::error("FATAL: ".$error);
    }

    return self::process($args);
  }

  static function process(&$args, $params = array()) {
    if (empty($params)) {
      $params = &self::buildParamList();
    }

    if (!isset($params['check_permissions'])) {
      $params['check_permissions'] = TRUE;
    }
    $fnName = $apiFile = NULL;
    // clean up all function / class names. they should be alphanumeric and _ only
    for ($i = 1; $i <= 3; $i++) {
      if (!empty($args[$i])) {
        $args[$i] = CRM_Utils_String::munge($args[$i]);
      }
    }

    // incase of ajax functions className is passed in url
    if (isset($params['className'])) {
      $params['className'] = CRM_Utils_String::munge($params['className']);

      // functions that are defined only in AJAX.php can be called via
      // rest interface
      if (!CRM_Core_Page_AJAX::checkAuthz('method', $params['className'], $params['fnName'])) {
        return self::error('Unknown function invocation.');
      }

      return call_user_func(array($params['className'], $params['fnName']), $params);
    }

    if (!CRM_Utils_Array::arrayKeyExists('version', $params)) {
      $params['version'] = 3;
    }

    if ($params['version'] == 2) {
      $result['is_error'] = 1;
      $result['error_message'] = "FATAL: API v2 not accessible from ajax/REST";
      $result['deprecated'] = "Please upgrade to API v3";
      return $result;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET' && !strstr(strtolower((string)$args[2]), 'get') && strtolower((string)$args[2]) != 'check') {
      // get only valid for non destructive methods
      require_once 'api/v3/utils.php';
      return civicrm_api3_create_error("SECURITY: All requests that modify the database must be http POST, not GET.",
        array(
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'Destructive HTTP GET',
        )
      );
    }

    // check options, all options should be inside option object
    $disableOptions = array(
      'sort', 'limit', 'rowCount', 'offset'
    );
    foreach($disableOptions as $opt) {
      if (isset($params[$opt])) unset($params[$opt]);
      if (isset($params['option.'.$opt])) unset($params['option.'.$opt]);
      if (isset($params['option_'.$opt])) unset($params['option_'.$opt]);
    }
    if (isset($params['options'])) {
      $options =& $params['options'];
      // don't allow sort for query security concern
      if (isset($options['sort'])) {
        if (!self::validateSortParameter($options['sort'])) {
          return self::error("sort in options is invalid. format: field_name DESC|ASC");
        }
      }

      if (isset($options['limit']) && !CRM_Utils_Rule::integer($options['limit'])) {
        return self::error('limit in options should be integer.');
      }
      if (defined('CIVICRM_REST_LIMIT_ROWS') && CRM_Utils_Rule::positiveInteger(CIVICRM_REST_LIMIT_ROWS) && CIVICRM_REST_LIMIT_ROWS > self::$limitRows) {
        self::$limitRows = CIVICRM_REST_LIMIT_ROWS;
      }
      if (isset($options['limit']) && $options['limit'] > self::$limitRows) {
        return self::error('limit in options can\'t not larger than '.self::$limitRows.'.');
      }
      if (isset($options['offset']) && !CRM_Utils_Rule::integer($options['offset'])) {
        return self::error('offset in options should be integer.');
      }
    }

    // trap all fatal errors
    CRM_Core_Error::setCallback(array('CRM_Utils_REST', 'fatal'));
    if (!isset($params['sequential'])) {
      $params['sequential'] = 1;
    }
    $result = civicrm_api($args[1], $args[2], $params);
    CRM_Core_Error::setCallback();

    if ($result === FALSE) {
      return self::error('Unknown error.');
    }
    return $result;
  }

  static function &buildParamList() {
    $params = array();

    $skipVars = array(
      'q' => 1,
      'json' => 1,
      'key' => 1,
      'api_key' => 1,
      'entity' => 1,
      'action' => 1,
    );

    if($_SERVER["CONTENT_TYPE"] === strtolower('application/json')) {
      $input = file_get_contents('php://input');
      $params = json_decode($input, TRUE);
      if (empty($params)) {
        echo json_encode(array('is_error' => 1, 'error_message', 'invalid json format: ?{"param_with_double_quote":"value"}'));
        CRM_Utils_System::civiExit();
      }
    }
    elseif (CRM_Utils_Array::arrayKeyExists('json', $_REQUEST) && $_REQUEST['json'][0] == "{") {
      $params = json_decode($_REQUEST['json'], TRUE);
      if (empty($params)) {
        echo json_encode(array('is_error' => 1, 'error_message', 'invalid json format: ?{"param_with_double_quote":"value"}'));
        CRM_Utils_System::civiExit();
      }
    }

    foreach ($_REQUEST as $n => $v) {
      if (!CRM_Utils_Array::arrayKeyExists($n, $skipVars)) {
        $params[$n] = $v;
      }
    }
    if (CRM_Utils_Array::arrayKeyExists('return', $_REQUEST) && is_array($_REQUEST['return'])) {
      foreach ($_REQUEST['return'] as $key => $v) $params['return.' . $key] = 1;
    }
    return $params;
  }

  static function fatal($pearError) {
    header('Content-Type: text/xml');
    $error = array();
    $error['code'] = $pearError->getCode();
    $error['error_message'] = $pearError->getMessage();
    $error['mode'] = $pearError->getMode();
    $error['debug_info'] = $pearError->getDebugInfo();
    $error['type'] = $pearError->getType();
    $error['user_info'] = $pearError->getUserInfo();
    $error['to_string'] = $pearError->toString();
    $error['is_error'] = 1;

    echo self::output($error);

    CRM_Utils_System::civiExit();
  }

  static function APIDoc() {

    CRM_Utils_System::setTitle("API Parameters");
    $template = CRM_Core_Smarty::singleton();
    $content = $template->fetch('CRM/Core/APIDoc.tpl');
    return CRM_Utils_System::theme($content);
  }

  /** used to load a template "inline", eg. for ajax, without having to build a menu for each template */
  static function loadTemplate() {
    /*
    $request = CRM_Utils_Request::retrieve('q', 'String', CRM_Core_DAO::$_nullObject);
    if (FALSE !== strpos($request, '..')) {
      die("SECURITY FATAL: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
    }

    $request = preg_split('/\//', $request);
    $entity = _civicrm_api_get_camel_name($request[2]);
    $tplfile = _civicrm_api_get_camel_name($request[3]);

    $tpl = 'CRM/' . $entity . '/Page/Inline/' . $tplfile . '.tpl';
    $smarty = CRM_Core_Smarty::singleton();
    CRM_Utils_System::setTitle("$entity::$tplfile inline $tpl");
    if (!$smarty->template_exists($tpl)) {
      header("Status: 404 Not Found");
      die("Can't find the requested template file templates/$tpl");
    }
    // special treatmenent, because it's often used
    if (CRM_Utils_Array::arrayKeyExists('id', $_GET)) {
      // an id is always positive
      $smarty->assign('id', (int)$_GET['id']);
    }
    $pos = strpos(CRM_Utils_Array::implode(array_keys($_GET)), '<');

    if ($pos !== FALSE) {
      die("SECURITY FATAL: one of the param names contains &lt;");
    }
    $param = array_map('htmlentities', $_GET);
    unset($param['q']);
    $smarty->assign_by_ref("request", $param);

    if (!CRM_Utils_Array::arrayKeyExists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
      $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
    ) {

      $smarty->assign('tplFile', $tpl);
      $config = CRM_Core_Config::singleton();
      $content = $smarty->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');

      if ($region = CRM_Core_Region::instance('html-header', FALSE)) {
        CRM_Utils_System::addHTMLHead($region->render(''));
      }
      CRM_Utils_System::appendTPLFile($tpl, $content);

      return CRM_Utils_System::theme($content);
    }
    else {
      $content = "<!-- .tpl file embeded: $tpl -->\n";
      CRM_Utils_System::appendTPLFile($tpl, $content);
      echo $content . $smarty->fetch($tpl);
      CRM_Utils_System::civiExit();
    }
    */
  }

  /** This is a wrapper so you can call an api via json (it returns json too)
   * http://example.org/civicrm/api/json?entity=Contact&action=Get"&json={"contact_type":"Individual","email.get.email":{}} to take all the emails from individuals
   * works for POST & GET (POST recommended)
   **/
  static function ajaxJson() {
    require_once 'api/v3/utils.php';
    if (!$config->debug && (!CRM_Utils_Array::arrayKeyExists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
      )) {
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. $().crmAPI().",
        array(
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        )
      );
      echo json_encode($error);
      CRM_Utils_System::civiExit();
    }
    if (empty($_REQUEST['entity'])) {
      echo json_encode(civicrm_api3_create_error('missing entity param'));
      CRM_Utils_System::civiExit();
    }
    if (empty($_REQUEST['entity'])) {
      echo json_encode(civicrm_api3_create_error('missing entity entity'));
      CRM_Utils_System::civiExit();
    }
    if (!empty($_REQUEST['json'])) {
      $params = json_decode($_REQUEST['json'], TRUE);
    }
    $entity = CRM_Utils_String::munge(CRM_Utils_Array::value('entity', $_REQUEST));
    $action = CRM_Utils_String::munge(CRM_Utils_Array::value('action', $_REQUEST));
    if (!is_array($params)) {
      echo json_encode(array('is_error' => 1, 'error_message', 'invalid json format: ?{"param_with_double_quote":"value"}'));
      CRM_Utils_System::civiExit();
    }

    $params['check_permissions'] = TRUE;
    $params['version'] = 3;
    $_REQUEST['json'] = 1;
    if (!$params['sequential']) {
      $params['sequential'] = 1;
    }
    // trap all fatal errors
    CRM_Core_Error::setCallback(array('CRM_Utils_REST', 'fatal'));
    $result = civicrm_api($entity, $action, $params);

    CRM_Core_Error::setCallback();

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  static function ajax() {
    // this is driven by the menu system, so we can use permissioning to
    // restrict calls to this etc
    // the request has to be sent by an ajax call. First line of protection against csrf
    $config = CRM_Core_Config::singleton();
    if (!$config->debug &&
      (!CRM_Utils_Array::arrayKeyExists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
      )
    ) {
      require_once 'api/v3/utils.php';
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. $().crmAPI().",
        array(
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        )
      );
      echo json_encode($error);
      CRM_Utils_System::civiExit();
    }

    $q = CRM_Utils_Array::value('fnName', $_REQUEST);
    if (!$q) {
      $entity = CRM_Utils_Array::value('entity', $_REQUEST);
      $action = CRM_Utils_Array::value('action', $_REQUEST);
      if (!$entity || !$action) {
        $err = array('error_message' => 'missing mandatory params "entity=" or "action="', 'is_error' => 1);
        echo self::output($err);
        CRM_Utils_System::civiExit();
      }
      $args = array('civicrm', $entity, $action);
    }
    else {
      $args = explode('/', $q);
    }

    // get the class name, since all ajax functions pass className
    $className = CRM_Utils_Array::value('className', $_REQUEST);

    // If the function isn't in the civicrm namespace, reject the request.
    if (($args[0] != 'civicrm' &&
        count($args) != 3
      ) && !$className) {
      return self::error('Unknown function invocation.');
    }

    $result = self::process($args, FALSE);

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  /**
   * validate sort parameter
   *
   * @param string $sort The sort parameter to validate. Can be a single field
   *                     or multiple fields separated by commas (`,`), optionally
   *                     followed by ASC or DESC.
   * @return boolean true if valid, else false
   */
  public static function validateSortParameter($sort) {
    if (empty($sort) || !is_string($sort)) {
      return FALSE;
    }
    $sort = trim($sort);
    $sortFields = explode(',', $sort);

    foreach ($sortFields as $field) {
      $field = trim($field);

      if (preg_match('/^(.*?)\s+(ASC|DESC)$/i', $field, $matches)) {
        $fieldName = trim($matches[1]);
      }
      else {
        $fieldName = $field;
      }
      if (!preg_match('/^[0-9A-Za-z_.]+$/', $fieldName)) {
        return FALSE;
      }
      if ($fieldName === '') {
        return FALSE;
      }
    }

    return TRUE;
  }
}

