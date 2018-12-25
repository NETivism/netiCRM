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


require_once "PEAR.php";

require_once "CRM/Core/Error.php";
class CRM_Core_Session {

  /**
   * Cache of all the session names that we manage
   */
  static $_managedNames = NULL;

  /**
   * key is used to allow the application to have multiple top
   * level scopes rather than a single scope. (avoids naming
   * conflicts). We also extend this idea further and have local
   * scopes within a global scope. Allows us to do cool things
   * like resetting a specific area of the session code while
   * keeping the rest intact
   *
   * @var string
   */
  protected $_key = 'CiviCRM';
  CONST USER_CONTEXT = 'userContext';
  CONST EXPIRED_TIME = 21600; // second

  /**
   * This is just a reference to the real session. Allows us to
   * debug this class a wee bit easier
   *
   * @var object
   */
  protected $_session;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static  */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * Since we are now a client / module of drupal, drupal takes care
   * of initiating the php session handler session_start ().
   *
   * When using CiviCRM standalone (i.e. w/o Drupal), we start the session
   * in index.php and then pass it off to here.
   *
   * All crm code should always use the session using
   * CRM_Core_Session. we prefix stuff to avoid collisions with drupal and also
   * collisions with other crm modules!!
   * This constructor is invoked whenever any module requests an instance of
   * the session and one is not available.
   *
   * @return void
   */
  function __construct() {
    $this->_session = NULL;
  }

  /**
   * singleton function used to manage this object
   *
   * @return object
   * @static  */
  static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Session;
    }
    return self::$_singleton;
  }

  /**
   * Creates an array in the session. All variables now will be stored
   * under this array
   *
   * @param boolean isRead is this a read operation, in this case, the session will not be touched
   *
   * @access private
   *
   * @return void
   */
  function initialize($isRead = FALSE) {
    // lets initialize the _session variable just before we need it
    // hopefully any bootstrapping code will actually load the session from the CMS
    if (!isset($this->_session)) {
      // CRM-9483
      if (!isset($_SESSION) && PHP_SAPI !== 'cli') {
        if ($isRead) {
          return;
        }
        if (function_exists('drupal_session_start')) {
          // https://issues.civicrm.org/jira/browse/CRM-14356
          if (! (isset($GLOBALS['lazy_session']) && $GLOBALS['lazy_session'] == true)) {
            drupal_session_start();
          }
          $_SESSION = array();
        }
        else {
          ini_set('session.save_handler', 'files');
          session_start();
        }
      }
      $this->_session =& $_SESSION;
    }

    if ($isRead) {
      return;
    }

    if (!isset($this->_session[$this->_key]) ||
      !is_array($this->_session[$this->_key])
    ) {
      $this->_session[$this->_key] = array();
    }
    return;
  }

  /**
   * Resets the session store
   *
   * @access public
   *
   * @return void
   */
  function reset($all = 1) {
    if ($all != 1) {
      // to make certain we clear it, first initialize it to empty
      $this->_session[$this->_key] = array();
      unset($this->_session[$this->_key]);
    }
    else {
      $this->_session = array();
    }

    return;
  }

  /**
   * creates a session local scope
   *
   * @param string local scope name
   * @access public
   *
   * @return void
   */
  function createScope($prefix, $isRead = FALSE) {
    $this->initialize($isRead);

    if ($isRead || empty($prefix)) {
      return;
    }

    if (empty($this->_session[$this->_key][$prefix])) {
      $this->_session[$this->_key][$prefix] = array();
    }
  }

  /**
   * resets the session local scope
   *
   * @param string local scope name
   * @access public
   *
   * @return void
   */
  function resetScope($prefix) {
    $this->initialize();

    if (empty($prefix)) {
      return;
    }

    if (array_key_exists($prefix, $this->_session[$this->_key])) {
      unset($this->_session[$this->_key][$prefix]);
    }
  }

  /**
   * Change scope name and move old scope to new
   *
   * @param string old scope name
   * @access public
   *
   * @param new scope name
   * @access public
   *
   * @return Boolean
   */
  function changeScope($oldPrefix, $newPrefix) {
    $this->initialize();

    if (!empty($oldPrefix) && !empty($newPrefix)) {
      if (array_key_exists($oldPrefix, $this->_session[$this->_key]) && !isset($this->_session[$this->_key][$newPrefix])) {
        $this->_session[$this->_key][$newPrefix] = $this->_session[$this->_key][$oldPrefix];
        unset($this->_session[$this->_key][$oldPrefix]);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check local scope exists
   *
   * @param string for check
   * @access public
   *
   * @return Boolean
   */
  function checkScope($prefix) {
    $this->initialize();

    return isset($this->_session[$this->_key][$prefix]);
  }

  /**
   * Lookup scope name by inner value
   *
   * @param string for lookup
   * @param string name of inner prefix to find
   * @param string value of inner prefix to find
   * @access public
   *
   * @return Boolean
   */
  function lookupScope($lookup, $name, $value) {
    $this->initialize();
    foreach($this->_session[$this->_key] as $prefix => $v){
      if (strstr($prefix, $lookup)) {
        if(is_array($v) && isset($v[$name]) && $v[$name] == $value) {
          return $prefix;
        }
        else{
          if($v === $value) {
            return $prefix;
          }
        }
      }
    }
  }

  /**
   * Store the variable with the value in the session scope
   *
   * This function takes a name, value pair and stores this
   * in the session scope. Not sure what happens if we try
   * to store complex objects in the session. I suspect it
   * is supported but we need to verify this
   *
   * @access public
   *
   * @param  string $name    name  of the variable
   * @param  mixed  $value   value of the variable
   * @param  string $prefix  a string to prefix the keys in the session with
   *
   * @return void
   *
   */
  function set($name, $value = NULL, $prefix = NULL) {
    // create session scope
    $this->initialize();
    $this->createScope($prefix);

    if (empty($prefix)) {
      $session = &$this->_session[$this->_key];
    }
    else {
      $session = &$this->_session[$this->_key][$prefix];
      if (empty($session)) {
        $session['expired'] = CRM_REQUEST_TIME + EXPIRED_TIME;
      }
    }

    if (is_array($name)) {
      foreach ($name as $n => $v) {
        $session[$n] = $v;
      }
    }
    else {
      $session[$name] = $value;
    }
  }

  /**
   * Gets the value of the named variable in the session scope
   *
   * This function takes a name and retrieves the value of this
   * variable from the session scope.
   *
   * @access public
   *
   * @param  string name  : name  of the variable
   * @param  string prefix : adds another level of scope to the session
   *
   * @return mixed
   *
   */
  function get($name, $prefix = NULL) {
    $this->createScope($prefix, TRUE);

    if (empty($this->_session) || empty($this->_session[$this->_key])) {
      return null;
    }

    if (empty($prefix)) {
      $session =& $this->_session[$this->_key];
    }
    else {
      if (empty($this->_session[$this->_key][$prefix])) {
        return null;
      }
      $session =& $this->_session[$this->_key][$prefix];
    }

    return CRM_Utils_Array::value($name, $session);
  }

  /**
   * Gets all the variables in the current session scope
   * and stuffs them in an associate array
   *
   * @access public
   *
   * @param  array  vars : associative array to store name/value pairs
   * @param  string  Strip prefix from the key before putting it in the return
   *
   * @return void
   *
   */
  function getVars(&$vars, $prefix = '') {
    // create session scope
    $this->createScope($prefix, TRUE);

    if (empty($prefix)) {
      $values = &$this->_session[$this->_key];
    }
    else {
      require_once 'CRM/Core/BAO/Cache.php';
      $values = CRM_Core_BAO_Cache::getItem('CiviCRM Session', "CiviCRM_{$prefix}");
    }

    foreach ($values as $name => $value) {
      $vars[$name] = $value;
    }
  }

  /**
   * adds a userContext to the stack
   *
   * @param string  $userContext the url to return to when done
   * @param boolean $check       should we do a dupe checking with the top element
   *
   * @return void
   *
   * @access public
   *
   */
  function pushUserContext($userContext, $check = TRUE) {
    if (empty($userContext)) {
      return;
    }

    $this->createScope(self::USER_CONTEXT);

    // hack, reset if too big
    if (count($this->_session[$this->_key][self::USER_CONTEXT]) > 10) {
      $this->resetScope(self::USER_CONTEXT);
      $this->createScope(self::USER_CONTEXT);
    }

    $topUC = array_pop($this->_session[$this->_key][self::USER_CONTEXT]);

    // see if there is a match between the new UC and the top one. the match needs to be
    // fuzzy since we use the referer at times
    // if close enough, lets just replace the top with the new one
    if ($check && $topUC && CRM_Utils_String::match($topUC, $userContext)) {
      array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
    }
    else {
      if ($topUC) {
        array_push($this->_session[$this->_key][self::USER_CONTEXT], $topUC);
      }
      array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
    }
    // CRM_Core_Error::debug( 'UC', $this->_session[$this->_key][self::USER_CONTEXT] );
  }

  /**
   * replace the userContext of the stack with the passed one
   *
   * @param string the url to return to when done
   *
   * @return void
   *
   * @access public
   *
   */
  function replaceUserContext($userContext) {
    if (empty($userContext)) {
      return;
    }

    $this->createScope(self::USER_CONTEXT);

    array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
    array_push($this->_session[$this->_key][self::USER_CONTEXT], $userContext);
  }

  /**
   * pops the top userContext stack
   *
   * @param void
   *
   * @return the top of the userContext stack (also pops the top element)
   *
   */
  function popUserContext() {
    $this->createScope(self::USER_CONTEXT);

    return array_pop($this->_session[$this->_key][self::USER_CONTEXT]);
  }

  /**
   * reads the top userContext stack
   *
   * @param void
   *
   * @return the top of the userContext stack
   *
   */
  function readUserContext() {
    $this->createScope(self::USER_CONTEXT);

    $config = CRM_Core_Config::singleton();
    $lastElement = count($this->_session[$this->_key][self::USER_CONTEXT]) - 1;
    return $lastElement >= 0 ? $this->_session[$this->_key][self::USER_CONTEXT][$lastElement] : $config->userFrameworkBaseURL;
  }

  function purgeExpired($force = FALSE) {
    if (empty($this->_session[$this->_key]['lastExpired'])) {
      $this->_session[$this->_key]['lastExpired'] = CRM_REQUEST_TIME;
      return;
    }

    // trigger purge every one hour
    if (CRM_REQUEST_TIME - $this->_session[$this->_key]['lastExpired'] > 3600 || $force) {
      // CiviCRM/*Controller
      $crmEleCount = $rootEleCount = 0;
      foreach ($this->_session[$this->_key] as $prefix => $object) {
        if (is_array($object)) {
          if(!empty($object['expired']) && $object['expired'] < CRM_REQUEST_TIME) {
            unset($this->_session[$this->_key][$prefix]);
            $crmEleCount++;
          }
          elseif(empty($object['expired'])) {
            $this->_session[$this->_key][$prefix]['expired'] = CRM_REQUEST_TIME + EXPIRED_TIME;
          }
        }
      }

      // _CRM__*__container
      foreach ($this->_session as $prefix => $object) {
        if (preg_match('/^_CRM.*_container$/', $prefix)) {
          if(!empty($object['expired']) && $object['expired'] < CRM_REQUEST_TIME) {
            unset($this->_session[$prefix]);
            $rootEleCount++;
          }
          elseif(empty($object['expired'])) {
            $this->_session[$prefix]['expired'] = CRM_REQUEST_TIME + EXPIRED_TIME;
          }
        }
      }

      $this->_session[$this->_key]['lastExpired'] = CRM_REQUEST_TIME;
      CRM_Core_Error::debug('Completed purge expired session form: '."$crmEleCount $this->_key forms / $rootEleCount quickform container");
    }
  }

  /**
   * dumps the session to the log
   */
  function debug($all = 1) {
    $this->initialize();
    if ($all != 1) {
      CRM_Core_Error::debug('CRM Session', $this->_session);
    }
    else {
      CRM_Core_Error::debug('CRM Session', $this->_session[$this->_key]);
    }
  }

  /**
   * stores a status message, resets status if asked to
   *
   * @param $reset boolean should we reset the status variable?
   *
   * @return string        the status message if any
   */
  function getStatus($reset = FALSE) {
    $this->initialize();

    $status = NULL;
    if (array_key_exists('status', $this->_session[$this->_key])) {
      $status = $this->_session[$this->_key]['status'];
    }
    if ($reset) {
      $this->_session[$this->_key]['status'] = NULL;
      unset($this->_session[$this->_key]['status']);
    }
    return $status;
  }

  /**
   * stores the status message in the session
   *
   * @param $status string the status message
   * @param $append boolean if you want to append or set new status
   *
   * @static  *
   * @return void
   */
  static function setStatus($status, $append = TRUE) {
    $session = self::singleton();
    $session->initialize();

    if (isset(self::$_singleton->_session[self::$_singleton->_key]['status'])) {
      if ($append) {
        if (is_array($status)) {
          if (is_array(self::$_singleton->_session[self::$_singleton->_key]['status'])) {
            self::$_singleton->_session[self::$_singleton->_key]['status'] += $status;
          }
          else {
            $currentStatus = self::$_singleton->_session[self::$_singleton->_key]['status'];
            // add an empty element to the beginning which will go in the <h3>
            self::$_singleton->_session[self::$_singleton->_key]['status'] = array('', $currentStatus) + $status;
          }
        }
        else {
          self::$_singleton->_session[self::$_singleton->_key]['status'] .= " $status";
        }
      }
      else {
        self::$_singleton->_session[self::$_singleton->_key]['status'] = " $status";
      }
    }
    else {
      self::$_singleton->_session[self::$_singleton->_key]['status'] = $status;
    }
  }

  static function registerAndRetrieveSessionObjects($names) {
    if (!is_array($names)) {
      $names = array($names);
    }

    if (!self::$_managedNames) {
      self::$_managedNames = $names;
    }
    else {
      self::$_managedNames = array_merge(self::$_managedNames, $names);
    }

    require_once 'CRM/Core/BAO/Cache.php';
    CRM_Core_BAO_Cache::restoreSessionFromCache($names);
  }

  static function storeSessionObjects($reset = TRUE) {
    if (empty(self::$_managedNames)) {
      return;
    }

    require_once 'CRM/Core/BAO/Cache.php';
    CRM_Core_BAO_Cache::storeSessionToCache(self::$_managedNames, $reset);

    self::$_managedNames = NULL;
  }

}

