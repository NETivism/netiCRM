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
  private $_pseudoClass;

  function __construct() {
    $this->is_drupal = TRUE;
    if(defined('VERSION')){  // drupal 7 or 8
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
    $this->_pseudoClass = new $class();
  }

  /**
   * Magic method handling
   */
  function __call($method, $args) {
    if(method_exists($this->_pseudoClass, $method)) {
      return call_user_func_array(array($this->_pseudoClass, $method), $args);
    }
    else{
      return FALSE;
    }
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
    if (arg(0) == 'civicrm') {
      //set drupal title
      drupal_set_title($pageTitle);
    }
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
  static function appendBreadCrumb($breadCrumbs) {
    $breadCrumb = drupal_get_breadcrumb();

    if (is_array($breadCrumbs)) {
      foreach ($breadCrumbs as $crumbs) {
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
        $breadCrumb[] = "<a href=\"{$crumbs['url']}\">{$crumbs['title']}</a>";
      }
    }
    drupal_set_breadcrumb($breadCrumb);
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static
   */
  static function resetBreadCrumb() {
    $bc = array();
    drupal_set_breadcrumb($bc);
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
      // $head 應該塞入 "<meta brabrabra.... >"
      $line = '<' . $head['tag'] . ' ';
      foreach ($head['attributes'] as $key => $value) {
        $line .=  $key . "='$value' ";
      }
      $line .= '>';
      if(!empty($head['value'])){
        $line .= $head['value'] . '</' . $head['tag'] . '>';
      }
      drupal_set_html_head($line);
      return;
    }
    elseif($version >= 7 && $version < 8){
      $element = array();
      foreach ($head as $key => $value) {
        $element['#' . $key] = $value;
      }
      drupal_add_html_head($element);
      return;
    }
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
    return variable_get($name, $default);
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

    if (!variable_get('clean_url', 0)) {
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
    // copied this from profile.module. Seems a bit inefficient, but i dont know a better way
    // CRM-3600
    cache_clear_all();
    menu_rebuild();
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  with the locale or null for none
   */
  static function getUFLocale() {
    // return CiviCRM’s xx_YY locale that either matches Drupal’s Chinese locale
    // (for CRM-6281), Drupal’s xx_YY or is retrieved based on Drupal’s xx
    global $language;
    switch (TRUE) {
      case $language->language == 'zh-hans':
        return 'zh_CN';

      case $language->language == 'zh-hant':
        return 'zh_TW';

      case preg_match('/^.._..$/', $language->language):
        return $language->language;

      default:
        require_once 'CRM/Core/I18n/PseudoConstant.php';
        return CRM_Core_I18n_PseudoConstant::longForShort(substr($language->language, 0, 2));
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
  static function loadBootStrap($name = NULL, $pass = NULL) {
    //take the cms root path.
    $cmsPath = self::cmsRootPath();

    if (!file_exists("$cmsPath/includes/bootstrap.inc")) {
      echo '<br />Sorry, could not able to locate bootstrap.inc.';
      exit();
    }

    chdir($cmsPath);
    require_once 'includes/bootstrap.inc';
    @drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    if (!function_exists('module_exists') ||
      !module_exists('civicrm')
    ) {
      echo '<br />Sorry, could not able to load drupal bootstrap.';
      exit();
    }

    //load user, we need to check drupal permissions.
    $name = $name ? $name : trim(CRM_Utils_Array::value('name', $_REQUEST));
    $pass = $pass ? $pass : trim(CRM_Utils_Array::value('pass', $_REQUEST));
    if ($name) {
      $user = user_authenticate(array('name' => $name, 'pass' => $pass));
      if (empty($user->uid)) {
        echo '<br />Sorry, unrecognized username or password.';
        exit();
      }
    }
  }

  static function cmsRootPath() {
    $cmsRoot = $valid = NULL;
    $pathVars = explode('/', str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']));

    //might be windows installation.
    $firstVar = array_shift($pathVars);
    if ($firstVar) {
      $cmsRoot = $firstVar;
    }

    //start w/ csm dir search.
    foreach ($pathVars as $var) {
      $cmsRoot .= "/$var";
      $cmsIncludePath = "$cmsRoot/includes";
      //stop as we found bootstrap.
      if (@opendir($cmsIncludePath) &&
        file_exists("$cmsIncludePath/bootstrap.inc")
      ) {
        $valid = TRUE;
        break;
      }
    }

    return ($valid) ? $cmsRoot : NULL;
  }

  /**
   * check is user logged in.
   *
   * @return boolean true/false.
   */
  public static function isUserLoggedIn() {
    $isloggedIn = FALSE;
    if (function_exists('user_is_logged_in')) {
      $isloggedIn = user_is_logged_in();
    }

    return $isloggedIn;
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int $userID logged in user uf id.
   */
  public static function getLoggedInUfID() {
    $ufID = NULL;
    if (function_exists('user_is_logged_in') &&
      user_is_logged_in() &&
      function_exists('user_uid_optional_to_arg')
    ) {
      $ufID = user_uid_optional_to_arg(array());
    }

    return $ufID;
  }

  function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    static $exists;
    if (empty($url)) {
      return $url;
    }

    //CRM-7803 -from d7 onward.
    $config = CRM_Core_Config::singleton();
    $version = substr($config->userSystem->version, 0, strpos($config->userSystem->version, '.'));
    if ($version == '7') {
      if($exists || function_exists('language_negotiation_get')){
        $exists = TRUE;
        global $language;

        //does user configuration allow language
        //support from the URL (Path prefix or domain)
        if (language_negotiation_get('language') == 'locale-url') {
          $urlType = variable_get('locale_language_negotiation_url_part');

          //url prefix
          if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) {
            if (isset($language->prefix) && $language->prefix) {
              if ($addLanguagePart) {
                $url .= $language->prefix . '/';
              }
              if ($removeLanguagePart) {
                $url = str_replace("/{$language->prefix}/", '/', $url);
              }
            }
          }
          //domain
          if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN) {
            if (isset($language->domain) && $language->domain) {
              if ($addLanguagePart) {
                $cleanedUrl = preg_replace('#^https?://#', '', $language->domain);
                // drupal function base_path() adds a "/" to the beginning and end of the returned path
                if (substr($cleanedUrl, -1) == '/') {
                  $cleanedUrl = substr($cleanedUrl, 0, -1);
                }
                $url = (CRM_Utils_System::isSSL() ? 'https' : 'http') . '://' . $cleanedUrl . base_path();
              }
              if ($removeLanguagePart && defined('CIVICRM_UF_BASEURL')) {
                $url = str_replace('\\', '/', $url);
                $parseUrl = parse_url($url);

                //kinda hackish but not sure how to do it right
                //hope http_build_url() will help at some point.
                if (is_array($parseUrl) && !empty($parseUrl)) {
                  $urlParts           = explode('/', $url);
                  $hostKey            = array_search($parseUrl['host'], $urlParts);
                  $ufUrlParts         = parse_url(CIVICRM_UF_BASEURL);
                  $urlParts[$hostKey] = $ufUrlParts['host'];
                  $url                = implode('/', $urlParts);
                }
              }
            }
          }
        }
      }
    }
    elseif($version == '6'){
      //upto d6 only, already we have code in place for d7
      $config = CRM_Core_Config::singleton();
      $url = CRM_Utils_System_Drupal6::languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
    }
    return $url;
  }

  function notFound(){
    drupal_not_found();
    return;
  }

}

