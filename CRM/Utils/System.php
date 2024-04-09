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
 * System wide utilities.
 *
 */
class CRM_Utils_System {

  static $_callbacks = NULL;

  /**
   * Compose a new url string from the current url string
   * Used by all the framework components, specifically,
   * pager, sort and qfc
   *
   * @param string $urlVar the url variable being considered (i.e. crmPageID, crmSortID etc)
   *
   * @return string the url fragment
   * @access public
   */
  static function makeURL($urlVar, $includeReset = FALSE, $includeForce = TRUE) {
    $config = CRM_Core_Config::singleton();

    if (!isset($_GET[$config->userFrameworkURLVar])) {
      return '';
    }
    $links = CRM_Utils_System::getLinksUrl($urlVar, $includeReset, $includeForce);
    return self::url(self::currentPath(), $links, FALSE, NULL, FALSE);
  }

  /**
   * get the query string and clean it up. Strip some variables that should not
   * be propagated, specically variable like 'reset'. Also strip any side-affect
   * actions (i.e. export)
   *
   * This function is copied mostly verbatim from Pager.php (_getLinksUrl)
   *
   * @param string  $urlVar       the url variable being considered (i.e. crmPageID, crmSortID etc)
   * @param boolean $includeReset should we include the reset var (generally this variable should be skipped)
   *
   * @return string
   * @access public
   */
  static function getLinksUrl($urlVar, $includeReset = FALSE, $includeForce = TRUE) {
    // Sort out query string to prevent messy urls
    $querystring = array();
    $qs = array();
    $arrays = array();

    $config = CRM_Core_Config::singleton();
    if (!empty($_SERVER['QUERY_STRING'])) {
      $qs = explode('&', str_replace('&amp;', '&', $_SERVER['QUERY_STRING']));
      for ($i = 0, $cnt = count($qs); $i < $cnt; $i++) {
        // check first if exist a pair
        if (strstr($qs[$i], '=') !== FALSE) {
          list($name, $value) = explode('=', $qs[$i]);
          if ($name != $urlVar) {
            $name = rawurldecode($name);
            //check for arrays in parameters: site.php?foo[]=1&foo[]=2&foo[]=3
            if ((strpos($name, '[') !== FALSE) &&
              (strpos($name, ']') !== FALSE)
            ) {
              $arrays[] = $qs[$i];
            }
            else {
              $qs[$name] = $value;
            }
          }
        }
        else {
          $qs[$qs[$i]] = '';
        }
        unset($qs[$i]);
      }
    }

    if ($includeForce) {
      $qs['force'] = 1;
    }
    foreach ($qs as $name => $value) {
      if ($name == 'snippet') {
        continue;
      }
      if (isset($config->userFrameworkURLVar) && $name == $config->userFrameworkURLVar) {
        continue;
      }

      if ($name != 'reset' || $includeReset) {
        $querystring[] = $name . '=' . $value;
      }
    }
    $querystring = array_merge($querystring, array_unique($arrays));
    return CRM_Utils_Array::implode('&', $querystring) . (!empty($querystring) ? '&' : '') . $urlVar . '=';
  }

  /**
   * Wrapping function to themeing
   *
   * For drupal 9 and new exception handling, we use exception to handle what kind of theme we should output
   * Do not use this control themeing anymore. Use drupal invoke function to display output
   * All content will use stdout and capture by drupal
   *
   * @param string  $content the content that will be themed
   *
   * @return void           prints content on stdout
   * @access public
   */
  static function theme(&$content) {
    if(empty($content)){
      return self::notFound();
    }
    echo $content;
  }

  /**
   * Generate an internal CiviCRM URL
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   *
   */
  static function url($path = NULL, $query = NULL, $absolute = FALSE,
    $fragment = NULL, $htmlize = FALSE, $frontend = FALSE
  ) {
    // we have a valid query and it has not yet been transformed
    if ( $htmlize && ! empty( $query ) && strpos( $query, '&amp;' ) === false ) {
      $query = htmlentities( $query );
    }

    return CRM_Core_Config::$_userSystem->url($path, $query, $absolute, $fragment, $htmlize, $frontend);
  }

  static function href($text, $path = NULL, $query = NULL, $absolute = TRUE,
    $fragment = NULL, $htmlize = TRUE, $frontend = FALSE
  ) {
    $url = self::url($path, $query, $absolute, $fragment, $htmlize, $frontend);
    return "<a href=\"$url\">$text</a>";
  }

  static function permissionDenied() {
    return CRM_Core_Config::$_userSystem->permissionDenied();
  }

  static function logout() {
    return CRM_Core_Config::$_userSystem->logout();
  }

  // this is a very drupal specific function for now
  static function updateCategories() {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      require_once 'CRM/Utils/System/Drupal.php';
      CRM_Utils_System_Drupal::updateCategories();
    }
  }

  /**
   * Current Path without language prefix and leading slash
   *
   * @return string
   */
  static function currentPath() {
    return CRM_Core_Config::$_userSystem->currentPath();
  }

  /**
	 * This static function sets the Content-Security-Policy header based on the configuration
	 * rules defined in CRM_Core_Config. If the current path matches the CSPexcludePath
	 * configuration rule, the header is not set.
	 */
  static function setCSPHeader() {
    if (empty(CRM_Core_Config::singleton()->cspRules)) {
      return;
    }
    else {
      $cspRules = CRM_Core_Config::singleton()->cspRules;
    }
    $csp = new CRM_Utils_CSP($cspRules);
    $csp = (string) $csp;

    $currentPath = self::currentPath();
    $cspExcludePath = CRM_Core_Config::singleton()->cspExcludePath;

    if (!self::matchPath($cspExcludePath, $currentPath) && !empty($csp)) {
      header("Content-Security-Policy: ".$csp);
    }
  }


  /**
   * This static function checks if the given path matches any of the patterns in the
   * given pattern string. The pattern string should be a newline-separated list of
   * Unix-style shell wildcards (e.g. *.php, /admin/*).
   *
   * @param string $pattern The pattern string to match against.
   * @param string $path The path to test.
   * @return bool Whether the path matches any of the patterns in the pattern string.
   */
  static function matchPath($pattern, $path) {

    if (empty($pattern)) {
      return false;
    }

    $patterns = preg_split('/\r?\n/', $pattern);
    $patterns = array_map('trim', $patterns);
    static $results = [];

    foreach ($patterns as $pattern) {
      if (isset($results[$pattern])) {
        $result = $results[$pattern];
      }
      else {
        $result = fnmatch($pattern, $path);
        $results[$pattern] = $result;
      }

      if ($result) {
        return true;
      }
    }
    return false;
  }

  /**
   * this function is called from a template to compose a url
   *
   * @param array $params list of parameters
   *
   * @return string url
   * @access public
   */
  static function crmURL($params) {
    $p = CRM_Utils_Array::value('p', $params);
    if (!isset($p)) {
      $p = self::currentPath();
    }

    return self::url($p,
      CRM_Utils_Array::value('q', $params),
      CRM_Utils_Array::value('a', $params, FALSE),
      CRM_Utils_Array::value('f', $params),
      CRM_Utils_Array::value('h', $params, FALSE),
      CRM_Utils_Array::value('fe', $params, FALSE)
    );
  }

  /**
   * sets the title of the page
   *
   * @param string $title
   * @param string $pageTitle
   *
   * @return void
   * @access public
   */
  static function setTitle($title, $pageTitle = NULL) {
    return CRM_Core_Config::$_userSystem->setTitle($title, $pageTitle);
  }

  /**
   * figures and sets the userContext. Uses the referer if valid
   * else uses the default
   *
   * @param array  $names   refererer should match any str in this array
   * @param string $default the default userContext if no match found
   *
   * @return void
   * @access public
   */
  static function setUserContext($names, $default = NULL) {
    $url = $default;

    $session = CRM_Core_Session::singleton();
    $referer = CRM_Utils_Array::value('HTTP_REFERER', $_SERVER);

    if ($referer && !empty($names)) {
      foreach ($names as $name) {
        if (strstr($referer, $name)) {
          $url = $referer;
          break;
        }
      }
    }

    if ($url) {
      $session->pushUserContext($url);
    }
  }

  /**
   * gets a class name for an object
   *
   * @param  object $object      - object whose class name is needed
   *
   * @return string $className   - class name
   *
   * @access public
   * @static  */
  static function getClassName($object) {
    return get_class($object);
  }

  /**
   * redirect to another url
   *
   * @param string $url the url to goto
   *
   * @return void
   * @access public
   * @static  */
  static function redirect($url = NULL) {
    return CRM_Core_Config::$_userSystem->redirect($url);
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url
   *
   * @return void
   * @access public
   * @static  */
  static function appendBreadCrumb($breadCrumbs) {
    return CRM_Core_Config::$_userSystem->appendBreadCrumb($breadCrumbs);
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static  */
  static function resetBreadCrumb() {
    return CRM_Core_Config::$_userSystem->resetBreadCrumb();
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   * @static  */
  static function addHTMLHead($bc) {
    return CRM_Core_Config::$_userSystem->addHTMLHead($bc);
  }


  /**
   * Append a javascript file
   *
   * @param array $params   template call's parameters
   * @param string $text    {js} block contents from the template
   *
   * @return void
   * @access public
   * @static  */
  static function addJs($params, $text) {
    return CRM_Core_Config::$_userSystem->addJs($params, $text);
  }

  /**
   * figure out the post url for the form
   *
   * @param the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   * @static  */
  static function postURL($action) {
    return CRM_Core_Config::$_userSystem->postURL($action);
  }

  /**
   * Get sitename from CMS system
   *
   * @return string
   * @access public
   * @static
   */
  static function siteName() {
    return CRM_Core_Config::$_userSystem->siteName($name, $default);
  }

  /**
   * Get user registration setting from CMS system
   *
   * @return boolean
   * @access public
   * @static
   */
  static function allowedUserRegisteration() {
    return CRM_Core_Config::$_userSystem->allowedUserRegisteration();
  }

  /**
   * Get user registration setting from CMS system
   *
   * @return boolean
   * @access public
   * @static
   */
  static function userEmailVerification() {
    return CRM_Core_Config::$_userSystem->userEmailVerification();
  }

  /**
   * Check module exists on system
   * @return string
   * @access public
   * @static
   */
  static function moduleExists($module) {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      return CRM_Core_Config::$_userSystem->moduleExists($module);
    }
    return FALSE;
  }

  /**
   * Check hook exists in module list
   * @return array
   * @access public
   * @static
   */
  static function moduleImplements($hook) {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      return CRM_Core_Config::$_userSystem->moduleImplements($hook);
    }
    return array();
  }

  /**
   * rewrite various system urls to https
   *
   * @return void
   * access public
   * @static  */
  static function mapConfigToSSL() {
    $config =& CRM_Core_Config::singleton();
    $url = str_replace('http://', 'https://', $config->userFrameworkResourceURL);
    $config->userFrameworkResourceURL = $url;
    $config->resourceBase = $url;

    if (!empty($config->extensionsURL)) {
      $config->extensionsURL = str_replace('http://', 'https://', $config->extensionsURL);
    }
    CRM_Core_Config::$_userSystem->mapConfigToSSL();
  }

  /**
   * Get the base URL from the system
   *
   * @param
   *
   * @return string
   * @access public
   * @static  */
  static function baseURL() {
    $config = CRM_Core_Config::singleton();
    return $config->userFrameworkBaseURL;
  }

  static function authenticateAbort($message, $abort) {
    if ($abort) {
      echo $message;
      self::civiExit(0);
    }
    else {
      return FALSE;
    }
  }

  static function authenticateKey($abort = TRUE) {
    if (isset($_SERVER['HTTP_X_CIVICRM_SITE_KEY'])) {
      $key = $_SERVER['HTTP_X_CIVICRM_SITE_KEY'];
    }
    else {
      $key = CRM_Utils_Request::retrieve('site_key', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    }


    if (!$key) {
      return self::authenticateAbort("ERROR: You need to send a valid key to execute this file.", $abort);
    }

    $siteKey = defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : NULL;

    if (!$siteKey || empty($siteKey)) {
      return self::authenticateAbort("ERROR: You need to set a valid site key in civicrm.settings.php.", $abort);
    }

    if (strlen($siteKey) < 8) {
      return self::authenticateAbort("ERROR: Site key needs to be greater than 7 characters in civicrm.settings.php.", $abort);
    }

    if (!hash_equals($key, $siteKey)) {
      return self::authenticateAbort("ERROR: Invalid key value sent.", $abort);
    }

    return TRUE;
  }

  static function authenticateScript($abort = TRUE, $name = NULL, $pass = NULL, $storeInSession = TRUE) {
    // auth to make sure the user has a login/password to do a shell
    // operation
    // later on we'll link this to acl's
    if (!$name) {
      $name = trim(CRM_Utils_Array::value('name', $_REQUEST));
      $pass = trim(CRM_Utils_Array::value('pass', $_REQUEST));
    }

    // its ok to have an empty password
    if (!$name) {
      return self::authenticateAbort("ERROR: You need to send a valid user name and password to execute this file\n",
        $abort
      );
    }

    if (!self::authenticateKey($abort)) {
      return FALSE;
    }

    $result = CRM_Utils_System::authenticate($name, $pass);
    if (!$result) {
      return self::authenticateAbort("ERROR: Invalid username and/or password\n",
        $abort
      );
    }
    elseif ($storeInSession) {
      // lets store contact id and user id in session
      list($userID, $ufID, $randomNumber) = $result;
      if ($userID && $ufID) {
        $session = CRM_Core_Session::singleton();
        $session->set('ufID', $ufID);
        $session->set('userID', $userID);
      }
      else {
        return self::authenticateAbort("ERROR: Unexpected error, could not match userID and contactID",
          $abort
        );
      }
    }

    return $result;
  }

  /**
   * Authenticate the user against the uf db
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   *
   * @return mixed false if no auth
   *               array( contactID, ufID, unique string ) if success
   * @access public
   * @static  */
  static function authenticate($name, $password) {
    return CRM_Core_Config::$_userSystem->authenticate($name, $password);
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $name     the message to set
   *
   * @access public
   * @static  */
  static function setUFMessage($message) {
    return CRM_Core_Config::$_userSystem->setMessage($message);
  }



  static function isNull($value) {
    if (!isset($value) || $value === NULL || $value === '') {
      return TRUE;
    }
    if (is_array($value)) {
      foreach ($value as $key => $value) {
        if (!self::isNull($value)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  static function mungeCreditCard($number, $keep = 4) {
    $number = trim($number);
    if (empty($number)) {
      return NULL;
    }
    $replace = str_repeat('*', strlen($number) - $keep);
    return substr_replace($number, $replace, 0, -$keep);
  }

  /** parse php modules from phpinfo */
  static function parsePHPModules() {
    ob_start();
    phpinfo(INFO_MODULES);
    $s = ob_get_contents();
    ob_end_clean();

    $s = strip_tags($s, '<h2><th><td>');
    $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
    $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
    $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $vModules = array();
    for ($i = 1; $i < count($vTmp); $i++) {
      if (preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
        $vName = trim($vMat[1]);
        $vTmp2 = explode("\n", $vTmp[$i + 1]);
        foreach ($vTmp2 AS $vOne) {
          $vPat = '<info>([^<]+)<\/info>';
          $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
          $vPat2 = "/$vPat\s*$vPat/";
          // 3cols
          if (preg_match($vPat3, $vOne, $vMat)) {
            $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]), trim($vMat[3]));
            // 2cols
          }
          elseif (preg_match($vPat2, $vOne, $vMat)) {
            $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
          }
        }
      }
    }
    return $vModules;
  }

  /** get a module setting */
  static function getModuleSetting($pModuleName, $pSetting) {
    $vModules = self::parsePHPModules();
    return $vModules[$pModuleName][$pSetting];
  }

  static function memory($title = NULL) {
    $memory = memory_get_usage() . ", ". time();
    if ($title) {
      CRM_Core_Error::debug_var($title, $memory);
    }
    return $memory;
  }

  static function download($name, $mimeType, &$buffer, $ext = NULL, $output = TRUE) {
    $now = gmdate('D, d M Y H:i:s') . ' GMT';

    header('Content-Type: ' . $mimeType);
    header('Expires: ' . $now);

    // lem9 & loic1: IE need specific headers
    if ($ext) {
      $fileName = $name.".".$ext;
    }
    else {
      $fileName = $name;
    }
    $fileString = 'filename="'.rawurlencode($fileName).'";';
    $fileString .= " filename*=utf-8''".rawurlencode($fileName);

    if (strstr($mimeType, 'image')) {
      header("Content-Disposition: inline");
    }
    else {
      header("Content-Disposition: attachment; $fileString");
    }
    header('Pragma: no-cache');

    if ($output) {
      print $buffer;
      self::civiExit();
    }
  }

  static function xMemory($title = NULL, $log = FALSE) {
    $mem = (float ) xdebug_memory_usage() / (float )(1024);
    $mem = number_format($mem, 5) . ", " . time();
    if ($log) {
      echo "<p>$title: $mem<p>";
      flush();
      CRM_Core_Error::debug_var($title, $mem);
    }
    else {
      echo "<p>$title: $mem<p>";
      flush();
    }
  }

  static function fixURL($url) {
    $components = parse_url($url);

    if (!$components) {
      return NULL;
    }

    // at some point we'll add code here to make sure the url is not
    // something that will mess up up, so we need to clean it up here
    return $url;
  }

  /**
   * make sure the callback is valid in the current context
   *
   * @param string $callback the name of the function
   *
   * @return boolean
   * @static  */
  static function validCallback($callback) {
    if (self::$_callbacks === NULL) {
      self::$_callbacks = array();
    }

    if (!CRM_Utils_Array::arrayKeyExists($callback, self::$_callbacks)) {
      if (strpos($callback, '::') !== FALSE) {
        list($className, $methodName) = explode('::', $callback);
        $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        // ignore errors if any
        @include_once ($fileName);
        if (!class_exists($className)) {
          self::$_callbacks[$callback] = FALSE;
        }
        else {
          // instantiate the class
          $object = new $className();
          if (!method_exists($object, $methodName)) {
            self::$_callbacks[$callback] = FALSE;
          }
          else {
            self::$_callbacks[$callback] = TRUE;
          }
        }
      }
      else {
        self::$_callbacks[$callback] = function_exists($callback);
      }
    }
    return self::$_callbacks[$callback];
  }

  /**
   * This serves as a wrapper to the php explode function
   * we expect exactly $limit arguments in return, and if we dont
   * get them, we pad it with null
   */
  static function explode($separator, $string, $limit) {
    $result = explode($separator, $string, $limit);
    for ($i = count($result); $i < $limit; $i++) {
      $result[$i] = NULL;
    }
    return $result;
  }

  static function checkURL($url, $addCookie = FALSE) {
    CRM_Core_Error::ignoreException();
    require_once 'HTTP/Request.php';
    $params = array('method' => 'GET');
    $request = new HTTP_Request($url, $params);
    if ($addCookie) {
      foreach ($_COOKIE as $name => $value) {
        $request->addCookie($name, $value);
      }
    }
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Standalone') {
      session_write_close();
    }
    $request->sendRequest();
    $result = $request->getResponseCode() == 200 ? TRUE : FALSE;
    if ($config->userFramework == 'Standalone') {
      session_start();
    }
    CRM_Core_Error::setCallback();
    return $result;
  }

  static function checkPHPVersion($ver = 5, $abort = FALSE) {
    if (is_int($ver)) {
      $phpVersion = PHP_MAJOR_VERSION;
      $phpVersion = (int) $phpVersion;
    }
    if (is_float($ver)) {
      $phpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
      $phpVersion = (float) $phpVersion;
    }
    if ($phpVersion >= $ver) {
      return TRUE;
    }

    if ($abort) {
      CRM_Core_Error::fatal(ts('This feature requires PHP Version %1 or greater', array(1 => $ver)));
    }
    return FALSE;
  }

  static function formatWikiURL($string, $encode = FALSE) {
    $items = explode(' ', trim($string), 2);
    if (count($items) == 2) {
      $title = $items[1];
    }
    else {
      $title = $items[0];
    }

    // fix for CRM-4044
    $url = $encode ? self::urlEncode($items[0]) : $items[0];
    return "<a href=\"$url\">$title</a>";
  }

  static function urlEncode($url) {
    $items = parse_url($url);
    if ($items === FALSE) {
      return NULL;
    }

    if (!CRM_Utils_Array::value('query', $items)) {
      return $url;
    }

    $items['query'] = urlencode($items['query']);

    $url = $items['scheme'] . '://';
    if (CRM_Utils_Array::value('user', $items)) {
      $url .= "{$items['user']}:{$items['pass']}@";
    }

    $url .= $items['host'];
    if (CRM_Utils_Array::value('port', $items)) {
      $url .= ":{$items['port']}";
    }

    $url .= "{$items['path']}?{$items['query']}";
    if (CRM_Utils_Array::value('fragment', $items)) {
      $url .= "#{$items['fragment']}";
    }

    return $url;
  }

  /**
   * Function to return the latest civicrm version.
   *
   * @return string civicrm version
   * @access public
   */
  static function version() {
    static $version;

    if (!$version) {
      $verFile = CRM_Utils_Array::implode(DIRECTORY_SEPARATOR,
        array(dirname(__FILE__), '..', '..', 'civicrm-version.txt')
      );
      if (file_exists($verFile)) {
        $str = file_get_contents($verFile);
        $parts = explode('+', $str, 2);
        $version = trim($parts[0]);
      }
      else {
        // svn installs don't have version.txt by default. In that case version.xml should help -
        $verFile = CRM_Utils_Array::implode(DIRECTORY_SEPARATOR,
          array(dirname(__FILE__), '..', '..', 'xml', 'version.xml')
        );
        if (file_exists($verFile)) {
          $str = file_get_contents($verFile);
          $xmlObj = simplexml_load_string($str);
          $version = (string) $xmlObj->version_no;
        }
      }

      // pattern check
      /* remove version check of crm
      if (!CRM_Utils_System::isVersionFormatValid($version)) {
        CRM_Core_Error::fatal('Unknown codebase version.');
      }
      */
    }

    return $version;
  }

  static function isVersionFormatValid($version) {
    return preg_match("/^(\d{1,2}\.){2}(\d{1,2}|(alpha|beta)\d{1,2})(\.upgrade)?$/", $version);
  }

  static function getAllHeaders() {
    if (function_exists('getallheaders')) {
      return getallheaders();
    }

    // emulate get all headers
    // http://www.php.net/manual/en/function.getallheaders.php#66335
    $headers = array();
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ',
          '-',
          ucwords(strtolower(str_replace('_',
                ' ',
                substr($name, 5)
              )
            ))
        )] = $value;
      }
    }
    return $headers;
  }

  static function getRequestHeaders() {
    if (function_exists('apache_request_headers')) {
      return apache_request_headers();
    }
    else {
      return $_SERVER;
    }
  }

  static function redirectToSSL($abort = FALSE) {
    $config = CRM_Core_Config::singleton();
    $req_headers = CRM_Utils_System::getRequestHeaders();
    if ($config->enableSSL &&
       (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') &&
       strtolower($req_headers['X_FORWARDED_PROTO']) != 'https'
    ) {
      // ensure that SSL is enabled on a civicrm url (for cookie reasons etc)
      $url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      if (!self::checkURL($url, TRUE)) {
        if ($abort) {
          CRM_Core_Error::fatal('HTTPS is not set up on this machine');
        }
        else {
          CRM_Core_Session::setStatus('HTTPS is not set up on this machine');
          // admin should be the only one following this
          // since we dont want the user stuck in a bad place
          return;
        }
      }
      CRM_Utils_System::redirect($url);
    }
  }

  static function ipAddress() {
    static $address;
    if (!isset($address)) {
      $address = CRM_Utils_Array::value('REMOTE_ADDR', $_SERVER);
      global $civicrm_conf;
      if(isset($civicrm_conf['reverse_proxy_addrs'])) {
        $proxy = $civicrm_conf['reverse_proxy_addrs'];
      }
      else {
        $proxy = CRM_Core_Config::singleton()->reverseProxyAddrs;
      }

      if ($proxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $forwarded = array_map('trim', $forwarded);
        $forwarded[] = $address;
        $trusted = explode(',', $proxy);
        $untrusted = array_diff($forwarded, $trusted);
        if (!empty($untrusted)) {
          $address = array_pop($untrusted);
        }
        else {
          $address = array_shift($forwarded);
        }
      }
    }
    return $address;
  }

  /**
   * Returns you the referring / previous page url
   *
   * @return string the previous page url
   * @access public
   */
  static function refererPath() {
    return CRM_Utils_Array::value('HTTP_REFERER', $_SERVER);
  }

  /**
   * Returns documentation URL base
   *
   * @return string documentation url
   * @access public
   */
  static function getDocBaseURL() {
    return CRM_Core_Config::singleton()->docURLBase;
  }

  /**
   * Returns trusted Hosts patterns
   *
   * @return string hosts patterns
   * @access public
   */
  static function getTrustedHostsPatterns() {
    global $civicrm_conf;
    $patterns = array();
    if (!empty($civicrm_conf['trustedHostsPatterns'])) {
      $patterns[] = trim($civicrm_conf['trustedHostsPatterns']);
    }
    if (!empty(CRM_Core_Config::singleton()->trustedHostsPatterns)) {
      $patterns[] = trim(CRM_Core_Config::singleton()->trustedHostsPatterns);
    }
    if (!empty($patterns)) {
      return trim(CRM_Utils_Array::implode("\n", $patterns));
    }
    return '';
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   * For use in PHP code.
   * WARNING: Always returns URL, if ts function is not defined ($URLonly has no effect).
   *
   * @param string  $page    Title of documentation wiki page
   * @param boolean $URLonly Whether function should return URL only or whole link (default)
   * @param string  $text    Text of HTML link (no effect if $URLonly = false)
   * @param string  $title   Tooltip text for HTML link (no effect if $URLonly = false)
   * @param string  $style   Style attribute value for HTML link (no effect if $URLonly = false)
   *
   * @return string URL or link to documentation page, based on provided parameters
   * @access public
   */
  static function docURL2($page, $URLonly = FALSE, $text = NULL, $title = NULL, $style = NULL) {
    // if ts function doesn't exist, it means that CiviCRM hasn't been fully initialised yet -
    // return just the URL, no matter what other parameters are defined
    if (!function_exists('ts')) {
      $docBaseURL = self::getDocBaseURL();
      if (!empty($docBaseURL)) {
        return $docBaseURL . str_replace(' ', '+', $page);
      }
      else {
        return '';
      }
    }
    else {
      $params = array(
        'page' => $page,
        'URLonly' => $URLonly,
        'text' => $text,
        'title' => $title,
        'style' => $style,
      );
      return self::docURL($params);
    }
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   * For use in templates code.
   *
   * @param array $params An array of parameters (see CRM_Utils_System::docURL2 method for names)
   *
   * @return string URL or link to documentation page, based on provided parameters
   * @access public
   */
  static function docURL($params) {
    if (!isset($params['page'])) {
      return;
    }

    $docBaseURL = self::getDocBaseURL();
    if (!empty($docBaseURL)) {
      if (!isset($params['title']) or $params['title'] === NULL) {
        $params['title'] = ts('Opens documentation in a new window.');
      }

      if (!isset($params['text']) or $params['text'] === NULL) {
        $params['text'] = ts('(learn more...)');
      }

      if (!isset($params['style']) || $params['style'] === NULL) {
        $style = '';
      }
      else {
        $style = "style=\"{$params['style']}\"";
      }

      $link = $docBaseURL . str_replace(' ', '+', $params['page']);

      if (isset($params['URLonly']) && $params['URLonly'] == TRUE) {
        return $link;
      }
      else {
        return "<a class=\"crm-docurl\" href=\"{$link}\" $style target=\"_blank\" title=\"{$params['title']}\">".ts($params['text'])."</a>";
      }
    }
    return '';
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  the used locale or null for none
   */
  static function getUFLocale() {
    return CRM_Core_Config::$_userSystem->getUFLocale();
  }

  /**
   * Execute external or internal urls and return server response
   *
   *  @param string   $url request url
   *  @param boolean  $addCookie  should be true to access internal urls
   *
   *  @return string  $response response from url
   *  @static  */
  static function getServerResponse($url, $addCookie = TRUE) {
    CRM_Core_Error::ignoreException();
    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($url);

    if ($addCookie) {
      foreach ($_COOKIE as $name => $value) {
        $request->addCookie($name, $value);
      }
    }

    if (isset($_SERVER['AUTH_TYPE'])) {
      $request->setBasicAuth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Standalone') {
      session_write_close();
    }

    $request->sendRequest();
    $response = $request->getResponseBody();

    CRM_Core_Error::setCallback();
    return $response;
  }

  static function isDBVersionValid(&$errorMessage) {
    require_once 'CRM/Core/BAO/Domain.php';
    $dbVersion = CRM_Core_BAO_Domain::version();

    if (!$dbVersion) {
      // if db.ver missing
      $errorMessage = ts('Version information found to be missing in database. You will need to determine the correct version corresponding to your current database state.');
      return FALSE;
    }
    elseif (!CRM_Utils_System::isVersionFormatValid($dbVersion)) {
      $errorMessage = ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.');
      return FALSE;
    }
    elseif (stripos($dbVersion, 'upgrade')) {
      // if db.ver indicates a partially upgraded db
      $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");
      $errorMessage = ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the <a href=\'%1\'>upgrade process</a> again.', array(1 => $upgradeUrl));
      return FALSE;
    }
    else {
      // $codeVersion = CRM_Utils_System::version();

      // if db.ver < code.ver, time to upgrade
      /* // remove for neticrm will use neticrm_update for upgrade handling
      if (version_compare($dbVersion, $codeVersion) < 0) {
        $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");
        $errorMessage = ts('New codebase version detected. You might want to visit <a href=\'%1\'>upgrade screen</a> to upgrade the database.', array(1 => $upgradeUrl));
        return FALSE;
      }

      // if db.ver > code.ver, sth really wrong
      if (version_compare($dbVersion, $codeVersion) > 0) {
        $errorMessage = ts('Your database is marked with an unexpected version number: %1. The v%2 codebase may not be compatible with your database state. You will need to determine the correct version corresponding to your current database state. You may want to revert to the codebase you were using until you resolve this problem.',
          array(1 => $dbVersion, 2 => $codeVersion)
        );
        $errorMessage .= "<p>" . ts('OR if this is an svn install, you might want to fix version.txt file.') . "</p>";
        return FALSE;
      }
      */
    }
    // FIXME: there should be another check to make sure version is in valid format - X.Y.alpha_num

    return TRUE;
  }

  /**
   * Exit the program
   *
   * No user output since here, no additional header here.
   * We should also commit session before here to prevent session miss.
   * The civiBeforeShutdown will doing session commit well.
   * Only functions in register_shutdown_function will be call after this.
   * You should add callbacks into CRM_Core_Config::shutdownCallbacks
   * When using fpm, we may have fastcgi_finish_request and location of header here.
   *
   * @param integer $status
   * @return void
   */
  static function civiExit($status = 0) {
    $version = CRM_Core_Config::$_userSystem->version;
    $config = CRM_Core_Config::singleton();
    self::civiBeforeShutdown();
    if ($config->userFramework == 'Drupal') {
      // drupal 6,7, change old exit method. Use exception to handling route
      // drupal 8,9, the correct way to exit
      // let symfony router handling this
      // will trigger event(KernelEvents::TERMINATE at controller
      // set default null exception handler to prevent no catch after this
      set_exception_handler(array('CRM_Core_Exception', 'nullExceptionHandler'));
      throw new CRM_Core_Exception('', CRM_Core_Error::NO_ERROR);
    }

    // we should never hit here when using drupal
    exit($status);
  }

  static function civiBeforeInvoke(&$args = NULL) {
    $config = CRM_Core_Config::singleton();

    // qfPrivateKey for drupal 9
    if (!self::isUserLoggedIn()) {
      $qfPrivateKey = CRM_Core_Config::$_userSystem->tempstoreGet('qfPrivateKey');
      if (!empty($qfPrivateKey)) {
        $session = CRM_Core_Session::singleton();
        $session->set('qfPrivateKey', $qfPrivateKey);
      }
    }

    // IDS and check for bad stuff
    if ($config->useIDS) {
      $ids = new CRM_Core_IDS();
      $ids->check($args);
    }

    // also initialize the i18n framework
    CRM_Core_I18n::singleton();

    // reset session when needed
    if ($config->debug) {
      $sessionReset = CRM_Utils_Request::retrieve('sessionReset', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET');
      if ($sessionReset) {
        $config->sessionReset();
      }
    }

    // initialize smarty
    // set active Component
    $template = CRM_Core_Smarty::singleton();
    $template->assign('activeComponent', 'CiviCRM');
    $template->assign('formTpl', 'default');
  }

  static function civiBeforeShutdown() {
    // now we register shutdown functions here
    if (!empty(CRM_Core_Config::$_shutdownCallbacks)) {
      $registerFastcgiFinishRequest = FALSE;
      if (!empty(CRM_Core_Config::$_shutdownCallbacks['before'])) {
        foreach(CRM_Core_Config::$_shutdownCallbacks['before'] as $idx => $call) {
          unset(CRM_Core_Config::$_shutdownCallbacks['before'][$idx]);
          $callback = key($call);
          $args = reset($call);
          if (is_callable($callback)) {
            if (!empty($ele['args']) && is_array($ele['args'])) {
              $args = $ele['args'];
            }
            else {
              $args = array();
            }
            call_user_func_array($callback, $args);
          }
          else {
            // do not silent fail here
            // make sure all callbacks can be call
            CRM_Core_Error::fatal('shutdown callback '.$callback. ' is not callable');
          }
        }
      }
      if (!empty(CRM_Core_Config::$_shutdownCallbacks['after'])) {
        register_shutdown_function('CRM_Utils_System::civiAfterShutdown');
        foreach(CRM_Core_Config::$_shutdownCallbacks['after'] as $idx => $call) {
          unset(CRM_Core_Config::$_shutdownCallbacks['after'][$idx]);
          $callback = key($call);
          $args = reset($call);
          if (is_callable($callback)) {
            if (!empty($args) && is_array($args)) {
              switch(count($args)) {
                case 0:
                  register_shutdown_function($callback);
                  break;
                case 1:
                  register_shutdown_function($callback, $args[0]);
                  break;
                case 2:
                  register_shutdown_function($callback, $args[0], $args[1]);
                  break;
                case 3:
                  register_shutdown_function($callback, $args[0], $args[1], $args[2]);
                  break;
                case 4:
                  register_shutdown_function($callback, $args[0], $args[1], $args[2], $args[3]);
                  break;
                case 5:
                default:
                  register_shutdown_function($callback, $args[0], $args[1], $args[2], $args[3], $args[4]);
                  break;
              }
            }
            else {
              register_shutdown_function($callback);
            }
          }
          else {
            // do not silent fail here
            // make sure all callbacks can be call
            CRM_Core_Error::fatal('shutdown callback '.$callback. ' is not callable');
          }
        }
      }
    }

    // save session before shutdown
    CRM_Core_Session::storeSessionObjects();
    if (!self::isUserLoggedIn() && isset($_SESSION[CRM_Core_Session::KEY]['qfPrivateKey'])) {
      CRM_Core_Config::$_userSystem->tempstoreSet('qfPrivateKey', $_SESSION[CRM_Core_Session::KEY]['qfPrivateKey']);
    }
  }

  static function civiAfterShutdown() {
    if (function_exists('fastcgi_finish_request')) {
      fastcgi_finish_request();
    }
  }

  /**
   * session_id() is not reliable after drupal 9.2
   * use this instead
   *
   * @return void
   */
  static function getSessionID() {
    return CRM_Core_Config::$_userSystem->sessionID();
  }

  /**
   * Reset the memory cache, typically memcached
   */
  static function flushCache($daoName = NULL) {
    // flush out all cache entries so we can reload new data
    // a bit aggressive, but livable for now
    $cache = CRM_Utils_Cache::singleton();
    $cache->flush();

    // also reset the various static memory caches

    // reset the memory or array cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields', NULL, FALSE);

    // reset ACL cache
    CRM_ACL_BAO_Cache::resetCache();

    // reset various static arrays used here
    CRM_Contact_BAO_Contact::$_importableFields = CRM_Contact_BAO_Contact::$_exportableFields = CRM_Contribute_BAO_Contribution::$_importableFields = CRM_Contribute_BAO_Contribution::$_exportableFields = CRM_Pledge_BAO_Pledge::$_exportableFields = CRM_Contribute_BAO_Query::$_contributionFields = CRM_Core_BAO_CustomField::$_importFields = CRM_Core_BAO_Cache::$_cache = CRM_Core_DAO::$_dbColumnValueCache = NULL;

    CRM_Core_OptionGroup::flushAll();
  }

  /**
   * load cms bootstrap
   *
   * @param $name string  optional username for login
   * @param $pass string  optional password for login
   */
  static function loadBootStrap($params, $throwError = TRUE) {
    $config = CRM_Core_Config::singleton();
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userFrameworkClass) . '.php');
    return call_user_func(array($config->userFrameworkClass, "loadBootStrap"), $params, $throwError);
  }

  /**
   * check is user logged in.
   *
   * @return boolean.
   */
  public static function isUserLoggedIn() {
    return CRM_Core_Config::$_userSystem->isUserLoggedIn();
  }

  /**
   * Get current logged in user id.
   *
   * @return int ufId, currently logged in user uf id.
   */
  public static function getLoggedInUfID() {
    return CRM_Core_Config::$_userSystem->getLoggedInUfID();
  }

  static function baseCMSURL() {
    static $_baseURL = NULL;
    if (!$_baseURL) {
      $config = CRM_Core_Config::singleton();
      $_baseURL = $userFrameworkBaseURL = $config->userFrameworkBaseURL;

      if ($config->userFramework == 'Joomla') {
        // gross hack
        // we need to remove the administrator/ from the end
        $_baseURL = str_replace("/administrator/", "/", $userFrameworkBaseURL);
      }
      else {
        // Drupal setting
        global $civicrm_root;
        if (strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'sites' .
            DIRECTORY_SEPARATOR . 'all' .
            DIRECTORY_SEPARATOR . 'modules'
          ) === FALSE) {
          $startPos = strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR
          );
          $endPos = strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
          );
          if ($startPos && $endPos) {
            // if component is in sites/SITENAME/modules
            $siteName = substr($civicrm_root,
              $startPos + 7,
              $endPos - $startPos - 7
            );

            $_baseURL = $userFrameworkBaseURL . "sites/$siteName/";
          }
        }
      }
    }
    return $_baseURL;
  }

  static function relativeURL($url) {
    // check if url is relative, if so return immediately
    if (substr($url, 0, 4) != 'http') {
      return $url;
    }

    // make everything relative from the baseFilePath
    $baseURL = self::baseCMSURL();

    // check if baseURL is a substr of $url, if so
    // return rest of string
    if (substr($url, 0, strlen($baseURL)) == $baseURL) {
      return substr($url, strlen($baseURL));
    }

    // return the original value
    return $url;
  }

  static function absoluteURL($url, $removeLanguagePart = FALSE) {
    // check if url is already absolute, if so return immediately
    if (substr($url, 0, 4) == 'http') {
      return $url;
    }

    // make everything absolute from the baseFileURL
    $baseURL = self::baseCMSURL();

    //CRM-7622: drop the language from the URL if requested (and it’s there)
    if ($removeLanguagePart) {
      $baseURL = self::languageNegotiationURL($baseURL, FALSE, TRUE);
    }

    return $baseURL . $url;
  }

  /**
   * Format the url as per language Negotiation.
   *
   * @param string $url
   *
   * @return string $url, formatted url.
   * @static  */
  static function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    return CRM_Core_Config::$_userSystem->languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
  }

  /**
   * Redirect to not found page of CMS
   *
   * @return string  the used locale or null for none
   */
  static function notFound() {
    return CRM_Core_Config::$_userSystem->notFound();
  }

  /**
   * Determine whether this is an SSL request.
   *
   * Note that we inline this function in install/civicrm.php, so if you change
   * this function, please go and change the code in the install script as well.
   */
  static function isSSL( ) {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
      return TRUE;
    }
    elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] == 'on') {
      return TRUE;
    }
    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      if($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Free memory of given object
   */
  static function freeObject(&$obj, $debug = FALSE){
    if($debug){
      $classname = get_class($obj);
      $memory = CRM_Utils_System::memory();
      CRM_Core_Error::debug("before free $classname: $memory", NULL, TRUE, FALSE);
    }
    if(is_object($obj)){
      foreach($obj as $key => $o) {
        unset($obj->$key);
      }
    }
    if($debug){
      $mem = CRM_Utils_System::memory();
      CRM_Core_Error::debug("after free $classname: $mem", NULL, TRUE, FALSE);
      $diff = $memory - $mem;
      CRM_Core_Error::debug("decrease $diff", NULL, TRUE, FALSE);
    }
  }

  /**
   * Get CMS public or private or temp dir
   *
   * @return string
   */
  public static function cmsDir($type) {
    return CRM_Core_Config::$_userSystem->cmsDir($type);
  }

  /**
   * Get CMS public or private or temp dir
   *
   * @return boolean.
   */
  public static function cmsRootPath() {
    return CRM_Core_Config::$_userSystem->cmsRootPath();
  }

  /**
   * Get CMS public or private or temp dir
   *
   * @return boolean.
   */
  public static function confPath() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->confPath();
  }

  /**
   * Get CMS logo URL
   *
   * @return boolean.
   */
  public static function getLogoURL() {
    return CRM_Core_Config::$_userSystem->getLogoURL();
  }

  /**
   * Set Html Head Meta
   * Contains: site, title, image, description
   */
  public static function setPageMetaInfo($params) {
    extract($params);

    // Set params to meta attributes array.
    $metaAttributes[] = array(
      'name' => 'description',
      'content' => $description,
    );

    $metaAttributes[] =  array(
      'property' => 'og:description',
      'content' => $description,
    );
    $metaAttributes[] = array(
      'property' => 'og:title',
      'content' => $title,
    );
    $metaAttributes[] = array(
      'property' => 'og:image',
      'content' => $image,
    );

    $metaAttributes[] = array(
      'name' => 'twitter:card',
      'content' => 'summary_large_image',
    );
    $metaAttributes[] = array(
      'name' => 'twitter:site',
      'content' => $site,
    );
    $metaAttributes[] = array(
      'name' => 'twitter:title',
      'content' => $title,
    );
    $metaAttributes[] =  array(
      'name' => 'twitter:description',
      'content' => $description,
    );
    $metaAttributes[] = array(
      'name' => 'twitter:image',
      'content' => $image,
    );

    // Use html head utils to inject tags
    foreach ($metaAttributes as $value) {
      if (!empty($value['content'])) {
        // tags should be like this:
        // array(
        //   'tag' => 'meta',
        //   'attributes' > array(
        //     'attributeName' => 'attributeValue',
        //   ),
        // )
        CRM_Utils_System::addHTMLHead(array(
          'tag' => 'meta',
          'attributes' => $value,
        ));
      }
    }
  }


  /**
   * SameSite cookie compatibility check
   *
   * from https://www.chromium.org/updates/same-site/incompatible-clients
   */
  public static function sameSiteCheck() {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $isIOS = preg_match('/(iP.+; CPU .*OS (\d+)[_\d]*.*) AppleWebKit\//i', $useragent, $ios);
    if ($isIOS && $ios[2] == '12') {
      return FALSE;
    }
    $safariStr = preg_match('/Version\/.* Safari\//i', $useragent);
    $isChromiumBased = preg_match('/Chrom(e|ium)/i', $useragent);
    $isSafari = !empty($safariStr) && !$isChromiumBased;
    if ($isSafari) {
      $isMAC = preg_match('/(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*) AppleWebKit\//i', $useragent, $mac);
      if ($isMAC && $mac['2'] == '10' && $mac['3'] == '14') {
        return FALSE;
      }
    }

    $isUcBrowser = preg_match('/UCBrowser\//i', $useragent);
    if ($isUcBrowser) {
      preg_match('/UCBrowser\/(\d+)\.(\d+)\.(\d+)[\.\d]* /i', $useragent, $ucVersion);
      if ($ucVersion[1] < 12) { // major
        return FALSE;
      }
      if ($ucVersion[2] < 13) { // minor
        return FALSE;
      }
      if ($ucVersion[3] < 2) { // buil
        return FALSE;
      }
    }

    if ($isChromiumBased) {
      preg_match('/Chrome\/(\d+)\.(\d+)\.(\d+)[\.\d]* /i', $useragent, $chVersion);
      if ($chVersion[1] >= 51 && $chVersion[1] <= 66) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public static function errorReporting($debug = 0) {
    if ($debug) {
      // all except notice
      error_reporting(E_ALL & ~E_NOTICE);
    }
    else {
      // all except deprecated, strict, warning
      error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
    }
  }

  /**
   * Get IP address from provided host
   *
   * @param string $host
   *   Use host to resolve IP address when provided. Default NULL will provide IP address of current CRM
   * @return string|false
   *   Return IP address when success. return FALSE when IP address is private or can't resolve
   */
  public static function getHostIPAddress($host = NULL) {
    $ip = FALSE;
    if (!empty($host)) {
      $ipByHost = gethostbyname($host);
      $ipByHost = filter_var(
        $ipByHost,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
      );
    }
    else {
      $ipByHost = filter_var(
        $_SERVER['SERVER_ADDR'],
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
      );
      if (empty($ipByHost)) {
        $host = $_SERVER['SERVER_NAME'];
        $ipByHost = gethostbyname($host);
        $ipByHost = filter_var(
          $ipByHost,
          FILTER_VALIDATE_IP,
          FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
        );
      }
    }
    if ($ipByHost && preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ipByHost)) {
      $ip = $ipByHost;
    }
    return $ip;
  }

  /**
   * Load and login user by uid or name
   *
   * @param array $params
   *   'uid' => uid
   *   'name' => username
   * @return void
   */
  public static function loadUser($params) {
    return CRM_Core_Config::$_userSystem->loadUser($params);
  }

  /**
   * Validates that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
   *
   * @return bool
   *   TRUE if only containing valid characters, or FALSE otherwise.
   */
  static function checkTrustedHosts($host = null) {
    if (!empty($host)) {
      $trusted_host_patterns = self::getTrustedHostsPatterns();
      $trusted_host_patterns_arr = explode("\n", $trusted_host_patterns);

      if (php_sapi_name() !== 'cli' && !empty($trusted_host_patterns_arr)) {
        foreach ($trusted_host_patterns_arr as $pattern) {
          $pattern = trim($pattern);
          if ($pattern === '') {
            continue;
          }

          // Replace wildcard character "*" with regular expression ".*"
          // and add anchors at the start and end of the pattern
          $pattern = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

          if (preg_match($pattern, $host)) {
            return TRUE;
          }
        }

        return FALSE;
      }

      return TRUE;
    }
  }
}

