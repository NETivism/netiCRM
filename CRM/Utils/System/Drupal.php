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
 * Drupal specific stuff goes here
 */
class CRM_Utils_System_Drupal {
  public $is_drupal;
  public $version;
  public $versionalClass;
  public static $jsLibraries;

  function __construct() {
    $this->is_drupal = TRUE;
    if(class_exists('DRUPAL')) { // drupal 8 or 9
      $this->version = (float )substr(DRUPAL::VERSION, 0, strrpos(DRUPAL::VERSION, '.'));
    }
    elseif(defined('VERSION')){  // drupal 7
      $this->version = (float) VERSION;
    }
    else{ // drupal 6 only
      $config = CRM_Core_Config::singleton();
      $db_cms = DB::connect($config->userFrameworkDSN);
      if (DB::isError($db_cms)) {
        die("Cannot connect to UF db, " . $db_cms->getMessage());
      }
      $query = $db_cms->query("SELECT info FROM system WHERE name = 'system'");
      $row = $query->fetchRow();
      $info = unserialize($row[0]);
      $this->version = (float) $info['version'];
    }

    // pseudoMethods make life easier
    $v = floor($this->version);
    $v = empty($v) ? '' : $v;
    $class = 'CRM_Utils_System_Drupal'.$v;
    $this->versionalClass = new $class();

    // bootstrap drupal when needed
    global $user;
    if (empty($user)) {
      $this->versionalClass->loadBootStrap();
    }

    // #27780, correct SameSite for chrome 80
    if (CRM_Utils_System::isSSL() && CRM_Utils_System::sameSiteCheck()) {
      $sparams = session_get_cookie_params();
      if (!$sparams['lifetime']) {
        $lifetime = 0;
      }
      else{
        $lifetime = CRM_REQUEST_TIME + $sparams['lifetime'];
      }

      if (PHP_VERSION_ID < 70300) {
        setcookie(session_name(), session_id(), $lifetime, '/; domain='.$sparams['domain'].'; Secure; HttpOnly; SameSite=None');
      }
      else {
        setcookie(session_name(), session_id(), array(
          'expires' => $lifetime,
          'path' => '/',
          'domain' => $sparams['domain'],
          'secure' => TRUE,
          'httponly' => TRUE,
          'samesite' => 'None',
        ));
      }
    }
  }

  /**
   * Magic method handling
   * 
   * Usage: CRM_Core_Config::singleton()->userSystem->$function
   */
  function __call($method, $args) {
    if(method_exists($this->versionalClass, $method)) {
      return call_user_func_array(array($this->versionalClass, $method), $args);
    }
    else{
      return FALSE;
    }
  }

  /**
   * Get best UF IF from drupal system
   * 
   * @return int
   */
  public static function getBestUFID($user = NULL) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if($version < 8){
      if (is_object($user)) {
        return is_numeric($user->uid) ? $user->uid : 0;
      }
      else {
        global $user;
        return !empty($user->uid) ? $user->uid : 0;
      }
    }
    else {
      // it's loaded user object
      if (is_object($user)) {
        $uid = $user->get('uid')->value;
        return $uid;
      }
      else {
        $uid = \Drupal::currentUser()->id();
        return !empty($uid) ? $uid : 0;
      }
    }
    return 0;
  }

  public static function getBestUFUniqueIdentifier($user) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if($version < 8){
      if (is_object($user)) {
        return !empty($user->mail) ? $user->mail: 0;
      }
      else {
        global $user;
        return !empty($user->mail) ? $user->mail: 0;
      }
    }
    else {
      // it's loaded user object
      if (is_object($user)) {
        return $user->get('mail')->value;
      }
      else {
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        return $user->get('mail')->value;
      }
    }
    return 0;
  }

  /**
   * sets the title of the page
   *
   * @param string $title
   * @paqram string $pageTitle
   *
   * @return void
   * @access public
   */
  function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    $config = CRM_Core_Config::singleton();
    $config->userSystem->versionalClass->setTitle($pageTitle);
  }

  /**
   * if we are using a theming system, invoke theme, else just print the
   * content
   *
   * @param string  $type    name of theme object/file
   * @param string  $content the content that will be themed
   * @param array   $args    the args for the themeing function if any
   * @param boolean $print   are we displaying to the screen or bypassing theming?
   * @param boolean $ret     should we echo or return output
   * @param boolean $maintenance  for maintenance mode
   *
   * @return void           prints content on stdout
   * @access public
   */
  function theme($type, &$content, $args = NULL, $print = FALSE, $ret = FALSE, $maintenance = FALSE){
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if($version >= 6 && $version < 7){
      if(!$print){
        if ($maintenance) {
          drupal_set_breadcrumb('');
          drupal_maintenance_theme();
        }
        $content = theme($type, $content, $args);
        if($ret) {
          return $content;
        }
        else{
          echo $content;
          return;
        }
      }
    }
    elseif($version >= 7 && $version < 8){
      // refs #20890, prevent infinite page loop when system needs cron
      global $conf;
      $conf['cron_safe_threshold'] = 0;
      if(!$print && $type == 'page'){
        if ($maintenance) {
          drupal_set_breadcrumb('');
          drupal_maintenance_theme();
        }
        if($ret){
          return drupal_render_page($content);
        }
        else{
          drupal_deliver_page($content);
          return;
        }
      }
    }
    elseif($version >= 8){
      echo 'We havnt support d8 yet';
      return;
    }

  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url
   *
   * @return void
   * @access public
   * @static
   */
  static function appendBreadCrumb($breadcrumbs) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version < 8) {
      $bc = drupal_get_breadcrumb();

      if (is_array($breadcrumbs)) {
        foreach ($breadcrumbs as $crumbs) {
          if (stripos($crumbs['url'], 'id%%')) {
            $args = array('cid', 'mid');
            foreach ($args as $a) {
              $val = CRM_Utils_Request::retrieve($a, 'Positive', CRM_Core_DAO::$_nullObject,
                FALSE, NULL, $_GET
              );
              if ($val) {
                $crumbs['url'] = str_ireplace("%%{$a}%%", $val, $crumbs['url']);
              }
            }
          }
          $bc[] = "<a href=\"{$crumbs['url']}\">{$crumbs['title']}</a>";
        }
      }
      drupal_set_breadcrumb($bc);
    }
    else {
      $config->userSystem->versionalClass->appendBreadCrumb($breadcrumbs);
    }
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static
   */
  static function resetBreadCrumb() {
    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->version < 8) {
      $bc = array();
      drupal_set_breadcrumb($bc);
    }
    else {
      $config->userSystem->versionalClass->resetBreadCrumb();
    }
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   * @static
   */
  static function addHTMLHead($head) {
    if(!is_array($head)){
      $message = 'Variable $head should be an Array';
      drupal_set_message($message);
      CRM_Core_Error::debug($message);
      return;
    }
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if($version >= 6 && $version < 7){
      if ($head['type'] == 'markup' && $head['markup']) {
        drupal_set_html_head($head['markup']);
      }
      else {
        $line = '<' . $head['tag'] . ' ';
        foreach ($head['attributes'] as $key => $value) {
          $line .=  $key . "='$value' ";
        }
        $line .= '>';
        if(!empty($head['value'])){
          $line .= $head['value'] . '</' . $head['tag'] . '>';
        }
        drupal_set_html_head($line);
      }
      return;
    }
    elseif($version >= 7 && $version < 8){
      $element = array();
      foreach ($head as $key => $value) {
        $element['#' . $key] = $value;
      }
      $head_key = '';
      foreach ($element['#attributes'] as $key => $value) {
        if($key == 'name' || $key == 'property'){
          if($head_key !== ''){
            $head_key .= '-';
          }
          $head_key = $key . '-' . $value;
        }
      }
      if (empty($head_key)) {
        $head_key = substr(md5(serialize($head)), 0, 32);
      }
      drupal_add_html_head($element, $head_key);
      return;
    }
  }

  /**
   * Append a javascript file
   *
   * @param array $params   template call's parameters
   * @param string $text    {js} block contents from the template
   *
   * @return void
   * @access public
   * @static
   */
  static function addJs($params, $text) {
    global $civicrm_root;
    $crmRelativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $civicrm_root);
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    $data = NULL;

    if ($version >= 6 && $version < 7) {
      $type = 'module';
      $scope = 'header';
      $defer = FALSE;
      $cache = TRUE;
      $preprocess = TRUE;

      if (!empty($params)) {
        extract($params);

        if (isset($src) && $src !== '') {
          $data = $src;

          // Check file path
          if (preg_match('/^https?:/i', $data)) {
            // If the path is absolute
            $data = preg_replace('/^https?:\/\/[^\/]*\//', '', $data);
          }
          else {
            // If the path is relative
            if (substr($data, 0, 1) === '/') {
              $data = ltrim($data, '/');
            }
            else {
              $crmRelativePath = ltrim($crmRelativePath, '/');
              $data = $crmRelativePath . $data;
            }
          }

          if (isset($type)) {
            // Change the value to 'module' if 'src' is assigned and the 'type' is set to 'inline'.
            $type = $type == 'inline' ? 'module' : $type;
          }

          drupal_add_js($data, $type, $scope, $defer, $cache, $preprocess);
        }
        else {
          if (isset($text) && $text !== '') {
            $data = $text;
            $type = 'inline';

            drupal_add_js($data, $type, $scope, $defer, $cache, $preprocess);
          }
        }
      }
      else {
        if (isset($text) && $text !== '') {
          $data = $text;
          $type = 'inline';

          drupal_add_js($data, $type, $scope, $defer, $cache, $preprocess);
        }
      }
    }
    elseif ($version >= 7 && $version < 8) {
      $options = NULL;

      if (!empty($params)) {
        $options = array();
        $possibleVars = array('scope', 'group', 'every_page', 'weight', 'requires_jquery', 'defer', 'cache', 'preprocess');

        foreach($possibleVars as $varName) {
          if (isset($params[$varName])) {
            $options[$varName] = $params[$varName];
          }
        }

        if (isset($params['src']) && $params['src'] !== '') {
          $data = $params['src'];

          // Check file path
          if (!preg_match('/^https?:/i', $data)) {
            // If the path is relative
            if (substr($data, 0, 1) === '/') {
              $data = ltrim($data, '/');
            }
            else {
              $crmRelativePath = ltrim($crmRelativePath, '/');
              $data = $crmRelativePath . $data;
            }
          }

          if (isset($params['type'])) {
            // Change the value to 'file' if 'src' is assigned and the 'type' is set to 'inline'.
            $options['type'] = $params['type'] == 'inline' ? 'file' : $params['type'];
          }

          drupal_add_js($data, $options);
        }
        else {
          if (isset($params['text']) && $params['text'] !== '') {
            $data = $params['text'];
            $options['type'] = 'inline';

            drupal_add_js($data, $options);
          }
        }
      }
      else {
        if (isset($text) && $text !== '') {
          $data = $text;
          $options['type'] = 'inline';

          drupal_add_js($data, $options);
        }
      }
    }
    elseif($version >= 8) {
      // refs civicrm.module civicrm_library_info_build
      if (strstr($params['src'], 'mailingEditor')) {
        self::$jsLibraries['civicrm/civicrm-js-mailingeditor'] = 1;
      }
      elseif(isset($params['type'])) {
        switch($params['type']) {
          case 'inline':
            \Drupal::service('civicrm.page_state')->addJs($text, $params['type']);
            break;
        }
      }
    }

    return;
  }

  /**
   * Get variable from CMS system
   *
   * @param variable name
   * @param Default value when variable is null.
   * 
   * @return void
   * @access public
   * @static  */
  static function variable_get($name, $default) {
    // drupal 6 and 7
    if (CRM_Core_Config::singleton()->userSystem->version < 8 ) {
      return variable_get($name, $default);
    }
    else {
      // exception
    }
  }

  /**
   * Get sitename from cms system
   *
   * @return string
   * @access public
   * @static
   */
  static function siteName() {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      return \Drupal::config('system.site')->get('name');
    }
    else {
      return self::variable_get('site_name', 'Drupal');
    }
  }

  /**
   * Get user registration setting from cms system
   *
   * @return string
   * @access public
   * @static
   */
  static function allowedUserRegisteration() {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      $allowedRegister = \Drupal::config('user.settings')->get('register');
      if ($allowedRegister == 'admin_only') {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
    else {
      return self::variable_get('user_register', TRUE);
    }
  }

  /**
   * User email verification setting
   *
   * @return string
   * @access public
   * @static
   */
  static function userEmailVerification() {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      return \Drupal::config('user.settings')->get('verify_email');
    }
    else {
      return self::variable_get('user_email_verification', TRUE);
    }
  }

  /**
   * check module exists
   *
   * @return string
   * @access public
   * @static
   */
  static function moduleExists($module) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      return \Drupal::moduleHandler()->moduleExists($module);
    }
    else {
      return module_exists($module);
    }
  }

  /**
   * rewrite various system urls to https
   *
   * @param null
   *
   * @return void
   * @access public
   * @static
   */
  static function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  /**
   * figure out the post url for the form
   *
   * @param mix $action the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   * @static
   */
  static function postURL($action) {
    if (!empty($action)) {
      return $action;
    }

    return self::url($_GET['q']);
  }

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   * @param $htmlize  boolean  whether to convert to html eqivalant
   * @param $frontend boolean  a gross joomla hack
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   *
   */
  function url($path = NULL, $query = NULL, $absolute = FALSE,
    $fragment = NULL, $htmlize = TRUE,
    $frontend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $script = 'index.php';

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }
    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = $htmlize ? '&amp;' : '&';

    if (!CIVICRM_CLEANURL) {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $script . '?q=' . $path . $separator . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

  /**
   * Authenticate the user against the drupal db
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   *
   * @return mixed false if no auth
   *               array( contactID, ufID, unique string ) if success
   * @access public
   * @static
   */
  static function authenticate($name, $password) {
    require_once 'DB.php';

    $config = CRM_Core_Config::singleton();

    $dbDrupal = DB::connect($config->userFrameworkDSN);
    if (DB::isError($dbDrupal)) {
      CRM_Core_Error::fatal("Cannot connect to drupal db via $config->userFrameworkDSN, " . $dbDrupal->getMessage());
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $password = md5($password);
    $name = $dbDrupal->escapeSimple($strtolower($name));
    $sql = 'SELECT u.* FROM ' . $config->userFrameworkUsersTableName . " u WHERE LOWER(u.name) = '$name' AND u.pass = '$password' AND u.status = 1";
    $query = $dbDrupal->query($sql);

    $user = NULL;
    // need to change this to make sure we matched only one row
    require_once 'CRM/Core/BAO/UFMatch.php';
    while ($row = $query->fetchRow(DB_FETCHMODE_ASSOC)) {
      CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row['uid'], $row['mail'], 'Drupal');
      $contactID = CRM_Core_BAO_UFMatch::getContactId($row['uid']);
      if (!$contactID) {
        return FALSE;
      }
      return array($contactID, $row['uid'], mt_rand());
    }
    return FALSE;
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $message the message to set
   *
   * @access public
   * @static
   */
  static function setMessage($message) {
    drupal_set_message($message);
  }

  static function permissionDenied() {
    drupal_access_denied();
  }

  static function logout() {
    module_load_include('inc', 'user', 'user.pages');
    return user_logout();
  }

  static function updateCategories() {
    $version = CRM_Core_Config::singleton()->userSystem->version;
    // CRM-3600
    if ($version < 8) {
      cache_clear_all();
      menu_rebuild();
    }
    else {
      \Drupal::service('router.builder')->rebuild();
    }
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  with the locale or null for none
   */
  static function getUFLocale() {
    $version = CRM_Core_Config::singleton()->userSystem->version;
    if ($version >= 8) {
      $languageCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    else {
      global $language;
      $languageCode = $language->language;
    }

    if ($languageCode == 'zh-hans') {
      return 'zh_CN';
    }
    elseif ($languageCode == 'zh-hant') {
      return 'zh_TW';
    }
    else {
      if (preg_match('/^.._..$/', $languageCode)) {
        return $languageCode;
      }
      else {
        return CRM_Core_I18n_PseudoConstant::longForShort(substr($languageCode, 0, 2));
      }
    }
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  with the locale or null for none
   */
  static function switchUFLocale($crmLocale = NULL) {
    if(empty($crmLocale)){
      global $tsLocale;
      $crmLocale = $tsLocale;
    }
    if(function_exists('language_list') && !empty($crmLocale)){
      global $language;
      $locale = $language->language;
      $languages = language_list(); 
      switch ($crmLocale){
        case 'zh_TW':
          $locale = 'zh-hant';
          break;
        case 'zh_CN':
          $locale = 'zh-hans';
          break;
        default:
          $locale = CRM_Core_I18n_PseudoConstant::shortForLong(substr($crmLocale, 0, 2));
          break; 
      }
      if(!empty($languages[$locale])){
        $language = $languages[$locale];
      }
    }
  }

  /**
   * load drupal bootstrap
   *
   * @param $name string  optional username for login
   * @param $pass string  optional password for login
   */
  static function loadBootStrap($params = array(), $throwError = TRUE) {
    //take the cms root path.
    $cmsPath = self::cmsRootPath();
    chdir($cmsPath);

    // call method in Drupalx.php
    $config = CRM_Core_Config::singleton();
    $config->userSystem->versionalClass->loadBootStrap($params);
  }

  static function cmsRootPath() {
    if (defined('DRUPAL_ROOT')) {
      return DRUPAL_ROOT;
    }

    $cmsRoot = $valid = NULL;
    if (!empty($_SERVER['PWD'])) {
      $scriptPath = $_SERVER['PWD'];
    }
    else {
      $scriptPath = $_SERVER['SCRIPT_FILENAME'];
    }
    $pathVars = explode('/', str_replace('\\', '/', $scriptPath));

    //might be windows installation.
    $firstVar = array_shift($pathVars);
    if ($firstVar) {
      $cmsRoot = $firstVar;
    }

    //start w/ csm dir search.
    foreach ($pathVars as $var) {
      $cmsRoot .= "/$var";
      $cmsIncludePath = array();
      $cmsIncludePath[] = "$cmsRoot/includes";
      $cmsIncludePath[] = "$cmsRoot/core/includes";
      foreach($cmsIncludePath as $path) {
        //stop as we found bootstrap.
        if (file_exists("$path/bootstrap.inc")) {
          $valid = TRUE;
          break 2;
        }
      }
    }

    if ($valid) {
      define('DRUPAL_ROOT', $cmsRoot);
      return $cmsRoot;
    }
  }

  /**
   * check is user logged in.
   *
   * @return boolean true/false.
   */
  public static function isUserLoggedIn() {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      return \Drupal::currentUser()->isAuthenticated();
    }
    else {
      return user_is_logged_in();
    }
    return FALSE;
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int $userID logged in user uf id.
   */
  public static function getLoggedInUfID() {
    return self::getBestUFID();
  }

  function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    // call method in Drupalx.php
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->versionalClass->languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
  }

  function notFound(){
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if ($version >= 8) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    else {
      drupal_not_found();
    }
    return;
  }

  function cmsDir($type) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    switch($type) {
      case 'temp':
      case 'tmp':
        if ($version >= 8 ) {
          return \Drupal::service('file_system')->getTempDirectory();
        }
        else {
          return file_directory_temp();
        }
      case 'public':
        if ($version >= 6 && $version < 7){
          return file_directory_path();
        }
        if ($version >= 7 && $version < 8) {
          return self::variable_get('file_public_path', 'sites/default/files');
        }
        if ($version >= 8 ) {
          return \Drupal\Core\StreamWrapper\PublicStream::basePath();
        }
      case 'private':
        if ($version >= 6 && $version < 7){
          return FALSE;
        }
        if ($version >= 7 && $version < 8) {
          return self::variable_get('file_private_path', '');
        }
        if ($version >= 8 ) {
          return \Drupal\Core\StreamWrapper\PrivateStream::basePath();
        }
    }
    return FALSE;
  }

  function confPath() {
    global $civicrm_conf_path;
    if (empty($civicrm_conf_path)) {
      $config = CRM_Core_Config::singleton();
      $version = $config->userSystem->version;
      if ($version >= 8) {
        $civicrm_conf_path = \Drupal::service('kernel')->getSitePath();
      }
      else {
        $civicrm_conf_path = conf_path(FALSE);
      }
    }
    return $civicrm_conf_path;
  }

  function getLogoURL() {
    $logoURL = theme_get_setting('logo');
    if (empty($logoURL)) {
      $logoURL = theme_get_setting('logo_path');
    }
    if (substr($logoURL, 0, 1) == '/') {
      $logoURL = $_SERVER['HTTP_ORIGIN'].$logoURL;
    }
    else if(substr($logoURL, 0, 4) != 'http') {
      $logoURL = $_SERVER['HTTP_ORIGIN'].'/'.$logoURL;
    }
    return $logoURL;
  }

  function transliteration($string) {
    require_once (drupal_get_path('module', 'transliteration') . '/transliteration.inc');
    $purgedName = '';
    if (self::moduleExists('transliteration')) {
      $purgedName = strtolower(transliteration_clean_filename($string));
      $purgedName = trim($purgedName, '_');
    }
    return $purgedName;
  }

  function moduleImplements($hook) {
    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->version < 8) {
      return module_implements($hook);
    }
    elseif($config->userSystem->version >= 8) {
      return \Drupal::moduleHandler()->getImplementations($hook);
    }
    elseif (function_exists('module_list')) {
      $implements = array();
      foreach (module_list() as $module) {
        $fnName = "{$module}_{$hook}";
        if (function_exists($fnName)) {
          $implements[] = $module;
        }
      }
      return $implements;
    }
    return array();
  }
  
}

