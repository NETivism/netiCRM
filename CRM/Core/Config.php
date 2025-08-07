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
 * Config handles all the run time configuration changes that the system needs to deal with.
 * Typically we'll have different values for a user's sandbox, a qa sandbox and a production area.
 * The default values in general, should reflect production values (minimizes chances of screwing up)
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */
/* we must load db first, never delete this require */

require_once 'api/api.php';

define('CRM_REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

class CRM_Core_Config extends CRM_Core_Config_Variables {
  /**
   * @var mixed
   */
  public $userFrameworkVersion;
  /**
   * @var string
   */
  public $customFileUploadURL;
  /**
   * @var string
   */
  public $version;
  /**
   * @var string
   */
  public $ver;
  ///
  /// BASE SYSTEM PROPERTIES (CIVICRM.SETTINGS.PHP)
  ///
  /**
   * System default language(fallback language)
   */
  CONST SYSTEM_LANG = 'en_US';
  CONST SYSTEM_FILEDIR = 'civicrm';

  /**
   * the dsn of the database connection
   * @var string
   */
  public $dsn;

  /**
   * the name of user framework
   * @var string
   */
  public $userFramework = 'Drupal';

  /**
   * the name of user framework url variable name
   * @var string
   */
  public $userFrameworkURLVar = 'q';

  /**
   * the dsn of the database connection for user framework
   * @var string
   */
  public $userFrameworkDSN = NULL;

  /**
   * The connector module for the CMS/UF
   * @todo Introduce an interface.
   *
   * @var CRM_Utils_System_Base
   */
  public $userSystem = NULL;

  /**
   * User system. We use drupal as default
   * @var      CRM_Utils_System_Drupal
   * @access   public
   */
  public static $_userSystem = NULL;

  /**
   * The root directory where Smarty should store
   * compiled files
   * @var string
   */
  public $templateCompileDir = NULL;

  public $configAndLogDir = NULL;

  // END: BASE SYSTEM PROPERTIES (CIVICRM.SETTINGS.PHP)

  ///
  /// BEGIN HELPER CLASS PROPERTIES
  ///

  /**
   * are we initialized and in a proper state
   * @var string
   */
  public $initialized = 0;

  /**
   * Shut down callbacks
   * 
   * The elements of this array will be called before / after civicrm shutdown
   * The format will be array(
   *   'before' => array(
   *      0 => array(callable $callback => $args),
   *    )
   *   'after' => array(
   *      0 => array(callable $callback => $args),
   *    )
   * ))
   * The $callback must be static method or function. The session may not exists.
   * In after shutdown, callback will be called after fastcgi_finish_request
   * 
   * @var array
   */
  public static $_shutdownCallbacks = [];

  /**
   * the factory class used to instantiate our DB objects
   * @var string
   */
  private $DAOFactoryClass = 'CRM_Contact_DAO_Factory';

  /**
   * The handle to the log that we are using
   * @var object
   */
  private static $_log = NULL;

  /**
   * the handle on the mail handler that we are using
   * @var object
   */
  private static $_mail = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * component registry object (of CRM_Core_Component type)
   */
  public $componentRegistry = NULL;

  ///
  /// END HELPER CLASS PROPERTIES
  ///

  ///
  /// RUNTIME SET CLASS PROPERTIES
  ///

  /**
   * to determine wether the call is from cms or civicrm
   */
  public $inCiviCRM = FALSE;

  ///
  /// END: RUNTIME SET CLASS PROPERTIES
  ///

  /**
   *  Define recaptcha key
   */

  public $recaptchaPublicKey;

  /**
   * The constructor. Sets domain id if defined, otherwise assumes
   * single instance installation.
   *
   * @return void
   * @access private
   */
  private function __construct() {}

  /**
   * Singleton function used to manage this object.
   *
   * @param $loadFromDB boolean  whether to load from the database
   * @param $force      boolean  whether to force a reconstruction
   *
   * @return object
   * @static
   */
  static function &singleton($loadFromDB = TRUE, $force = FALSE) {
    if (self::$_singleton === NULL || $force) {
      global $civicrm_root;
      $civicrm_root = rtrim($civicrm_root, '/').'/'; // force add traling slash
      // make sure date.timezone set, support php 5.3 / 5.4
      $timezone = date_default_timezone_get();

      // first, attempt to get configuration object from cache
      $cache = &CRM_Utils_Cache::singleton();
      self::$_singleton = $cache->get('CRM_Core_Config' . CRM_Core_Config::domainID());

      // if not in cache, fire off config construction
      if (!self::$_singleton) {
        self::$_singleton = new CRM_Core_Config;
        self::$_singleton->_initialize($loadFromDB);

        //initialize variables. for gencode we cannot load from the
        //db since the db might not be initialized
        if ($loadFromDB) {
          self::$_singleton->_initVariables();

          // retrieve and overwrite stuff from the settings file
          self::$_singleton->setCoreVariables();
        }
        $cache->set('CRM_Core_Config_' . CRM_Core_Config::domainID(), self::$_singleton);
      }
      else {
        // we retrieve the object from memcache, so we now initialize the objects
        self::$_singleton->_initialize($loadFromDB);

        if ($loadFromDB) {
          self::$_singleton->_initVariables();
        }
        // add component specific settings
        self::$_singleton->componentRegistry->addConfig($this);
      }

      self::$_singleton->initialized = 1;

      // Get trusted host patterns
      $trustedHostsPatterns = CRM_Utils_System::getTrustedHostsPatterns();
      if (!empty($trustedHostsPatterns)) {
        // Check trusted HTTP Host headers to protect against header attacks
        if (!CRM_Utils_System::checkTrustedHosts($_SERVER['HTTP_HOST']) && php_sapi_name() !== 'cli') {
          if (CRM_Core_Permission::check('access CiviCRM')) {
            CRM_Core_Session::singleton()->setStatus(ts("Current host \"%1\" doesn't in Trusted Host. If you are sure it's correct host, please add your domain into the <a href=\"%2\">trusted host setting</a>.", [
              1 => $_SERVER['HTTP_HOST'],
              2 => CRM_Utils_System::url('civicrm/admin/setting/security', 'reset=1')
            ]), TRUE, 'error');
          }
          else {
            CRM_Core_Error::fatalWithoutInitialized(ts('Access Denied.'));
          }
        }
      }


      if (isset(self::$_singleton->customPHPPathDir) &&
        self::$_singleton->customPHPPathDir
      ) {
        $include_path = self::$_singleton->customPHPPathDir . PATH_SEPARATOR . get_include_path();
        set_include_path($include_path);
      }

      if (!empty(self::$_singleton->domain->id)) {
        self::$_singleton->domain->getLocationValues();
      }

      // set the callback at the very very end, to avoid an infinite loop
      // set the error callback
      CRM_Core_Error::setCallback();

      if (self::$_singleton->debug) {
        CRM_Utils_System::errorReporting(1);
      }
      else {
        CRM_Utils_System::errorReporting(0);
      }

      // call the hook so other modules can add to the config
      // again doing this at the very very end

      CRM_Utils_Hook::config(self::$_singleton);

      // make sure session is always initialised
      $session = CRM_Core_Session::singleton();
    }
    return self::$_singleton;
  }


  private function _setUserFrameworkConfig($userFramework) {
    global $civicrm_root;

    $this->userFrameworkClass = 'CRM_Utils_System_' . $userFramework;
    $this->userHookClass = 'CRM_Utils_Hook_' . $userFramework;
    $this->userPermissionClass = 'CRM_Core_Permission_' . $userFramework;

    if (defined('CIVICRM_UF_DSN')) {
      $this->userFrameworkDSN = CIVICRM_UF_DSN;
    }

    $class = $this->userFrameworkClass;
    $this->userSystem = new $class();
    if(isset($this->userSystem->version)){
      $this->userFrameworkVersion = $this->userSystem->version;
    }

    if ($userFramework == 'Joomla') {
      $this->userFrameworkURLVar = 'task';
    }

    $scheme = CRM_Utils_System::isSSL() ? 'https' : 'http';
    if (defined('CIVICRM_UF_BASEURL')) {
      $url = parse_url(CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/'));
      $host = $url['host'];
      $port = empty($url['port'])? '': ':'.$url['port'];
      $path = $url['path'];
    }
    else {
      $host = $_SERVER['HTTP_HOST'];
      $path = '/';
      define('CIVICRM_UF_BASEURL', $scheme."://".$_SERVER['HTTP_HOST'].$path);
    }
    $this->userFrameworkBaseURL = $scheme.'://'.$host.$port.$path;

    //format url for language negotiation, CRM-7803
    $this->userFrameworkBaseURL = CRM_Utils_System::languageNegotiationURL($this->userFrameworkBaseURL);

    // this is dynamically figured out in the civicrm.settings.php file
    if (defined('CIVICRM_CLEANURL')) {
      $this->cleanURL = CIVICRM_CLEANURL;
    }
    else {
      $this->cleanURL = 0;
    }

    if (!$this->userFrameworkResourceURL) {
      $this->userFrameworkResourceURL = rtrim(CIVICRM_UF_BASEURL, '/') . str_replace($this->userSystem->cmsRootPath(), '', $civicrm_root);
    }
  }

  /**
   * Initializes the entire application.
   * Reads constants defined in civicrm.settings.php and
   * stores them in config properties.
   *
   * @return void
   * @access public
   */
  private function _initialize($loadFromDB = TRUE) {
    // following variables should be set in CiviCRM settings and
    // as crucial ones, are defined upon initialisation
    // instead of in CRM_Core_Config_Defaults
    if (defined('CIVICRM_DSN')) {
      $this->dsn = CIVICRM_DSN;
      $this->_initDAO();
    }
    elseif ($loadFromDB) {
      // bypass when calling from gencode
      echo 'You need to define CIVICRM_DSN in civicrm.settings.php';
      exit();
    }

    if (defined('CIVICRM_UF')) {
      $this->userFramework = CIVICRM_UF;
      $this->_setUserFrameworkConfig($this->userFramework);
    }
    else {
      echo 'You need to define CIVICRM_UF in civicrm.settings.php';
      exit();
    }

    // also initialize the logger
    self::$_log = &Log::singleton('display');

    // initialize component registry early to avoid "race"
    // between CRM_Core_Config and CRM_Core_Component (they
    // are co-dependant)

    $this->componentRegistry = new CRM_Core_Component();
  }

  /**
   * initialize the DataObject framework
   *
   * @return void
   * @access private
   */
  private function _initDAO() {
    CRM_Core_DAO::init($this->dsn);

    $factoryClass = $this->DAOFactoryClass;
    $factory = new $factoryClass();
    CRM_Core_DAO::setFactory($factory);
  }

  /**
   * returns the singleton logger for the application
   *
   * @param
   * @access private
   *
   * @return object
   */
  static public function &getLog() {
    if (!isset(self::$_log)) {
      self::$_log = &Log::singleton('display');
    }

    return self::$_log;
  }

  /**
   * initialize the config variables
   *
   * @return void
   * @access private
   */
  private function _initVariables() {
    global $civicrm_root;
    // retrieve serialised settings
    $variables = [];
    CRM_Core_BAO_ConfigSetting::retrieve($variables);

    // after locales initialized in configsetting
    global $tsLocale;
    $temp = CRM_Utils_System::cmsDir('temp');
    if ($temp) {
      $dirName = CRM_Utils_Type::escape($_SERVER['HTTP_HOST'], 'DirectoryName');
      $this->templateCompileDir = $temp . DIRECTORY_SEPARATOR . 'smarty' . php_sapi_name() . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $tsLocale . DIRECTORY_SEPARATOR;
    }
    elseif(defined('CIVICRM_TEMPLATE_COMPILEDIR')) {
      $this->templateCompileDir = CRM_Utils_File::addTrailingSlash(CIVICRM_TEMPLATE_COMPILEDIR).CRM_Utils_File::addTrailingSlash($tsLocale);
    }

    // also make sure we create the config directory within this directory
    // the below statement will create both the templates directory and the config and log directory
    $this->configAndLogDir = $this->templateCompileDir . CRM_Utils_File::addTrailingSlash('ConfigAndLog');
    CRM_Utils_File::createDir($this->configAndLogDir);

    // if settings are not available, go down the full path
    if (empty($variables['userFrameworkResourceURL']) || !empty($_GET['reset_variables'])) {
      // Step 1. get system variables with their hardcoded defaults
      $defaultVariables = get_object_vars($this);
      $variables = array_merge($defaultVariables, $variables);

      // Step 2. get default values (with settings file overrides if
      // available - handled in CRM_Core_Config_Defaults)

      CRM_Core_Config_Defaults::setValues($variables);

      // retrieve directory and url preferences also

      CRM_Core_BAO_Preferences::retrieveDirectoryAndURLPreferences($defaults);

      // add component specific settings
      $this->componentRegistry->addConfig($this);

      // serialise settings
      CRM_Core_BAO_ConfigSetting::add($variables);
    }

    $urlArray = ['userFrameworkResourceURL', 'imageUploadURL'];
    $dirArray = ['uploadDir', 'customFileUploadDir'];

    foreach ($variables as $key => $value) {
      if (in_array($key, $urlArray)) {
        $value = CRM_Utils_File::addTrailingSlash($value, '/');
      }
      elseif (in_array($key, $dirArray)) {
        $value = CRM_Utils_File::addTrailingSlash($value);
        if (CRM_Utils_File::createDir($value, FALSE) === FALSE) {
          // seems like we could not create the directories
          // settings might have changed, lets suppress a message for now
          // so we can make some more progress and let the user fix their settings
          // for now we assign it to a know value
          // CRM-4949
          $value = $this->templateCompileDir;
          $url = CRM_Utils_System::url('civicrm/admin/setting/path', 'reset=1');
          CRM_Core_Session::setStatus(ts('%1 has an incorrect directory path. Please go to the <a href="%2">path setting page</a> and correct it.', [1 => $key, 2 => $url]) . '<br/>');
        }
      }
      $this->$key = $value;
    }

    $rrb = parse_url($this->userFrameworkResourceURL);
    // dont use absolute path if resources are stored on a different server
    // CRM-4642
    $this->resourceBase = $this->userFrameworkResourceURL;
    if (isset($_SERVER['HTTP_HOST'])) {
      $this->resourceBase = ($rrb['host'] == $_SERVER['HTTP_HOST']) ? $rrb['path'] : $this->userFrameworkResourceURL;
    }

    // we need to do this here so all blocks also load from an ssl server
    if(CRM_Utils_System::isSSL()) {
      CRM_Utils_System::mapConfigToSSL();
    }

    if (!$this->customFileUploadDir) {
      $this->customFileUploadDir = $this->uploadDir;
    }
    $this->customFileUploadURL = rtrim(CIVICRM_UF_BASEURL, '/').'/'. CRM_Utils_System::cmsDir('public') . '/civicrm/custom/';

    if ($this->mapProvider) {
      $this->geocodeMethod = 'CRM_Utils_Geocode_' . $this->mapProvider;
    }

    if ($this->debug) {
      ini_set('xdebug.var_display_max_data', '5000');
    }
    $civicrm_path = rtrim($civicrm_root, '/').DIRECTORY_SEPARATOR;
    if (file_exists($civicrm_path.'civicrm-version.txt')) {
      $this->version = trim(file_get_contents($civicrm_path.'civicrm-version.txt'));
      $this->ver = substr($this->version, -8, -1);
    }
  }

  /**
   * retrieve a mailer to send any mail from the applciation
   *
   * @param int $mailerType 
   * @access private
   *
   * @return object
   */
  static function &getMailer($mailerType = '') {
    $mailerTypes = CRM_Core_BAO_MailSettings::$_mailerTypes;
    // refs #30289, special case for retrieve mailer type from mail settings
    if (is_numeric($mailerType) && !empty($mailerTypes[$mailerType])) {
      if (!isset(self::$_mail[$mailerType])) {
        $mailSettings = [];
        CRM_Core_BAO_MailSettings::commonRetrieveAll('CRM_Core_BAO_MailSettings', 'is_default', $mailerType, $mailSettings);
        if (count($mailSettings)) {
          self::$_mail[$mailerType] = [];
          $filters = [];
          foreach($mailSettings as $setting) {
            $params['host'] = $setting['server'];
            $params['port'] = !empty($setting['port']) ? $setting['port'] : 25;
            $params['username'] = $setting['username'];
            $params['password'] = $setting['password'];
            $params['auth'] = TRUE;
            $params['localhost'] = $_SERVER['SERVER_NAME'];
            if ($params['host']) {
              // when we have more than 1 mass mailing settings, and the settings have localpart field
              // this will be treat as filter rule of recipients email domain
              if ($mailerType == 2 && !empty($setting['localpart'])) {
                $filters[$setting['id']] = &Mail::factory('smtp', $params);
                $filters[$setting['id']]->_mailSetting = $setting;
              }
              else {
                self::$_mail[$mailerType][$setting['id']] = &Mail::factory('smtp', $params);
                self::$_mail[$mailerType][$setting['id']]->_mailSetting = $setting;
              }
            }
          }
          foreach($filters as &$setting) {
            foreach(self::$_mail[$mailerType] as $sid => &$mailSetting) {
              $mailSetting->_filters[] = &$setting;
            }
          }
        }
      }
      if (!empty(self::$_mail[$mailerType]) && is_array(self::$_mail[$mailerType])) {
        if (count(self::$_mail[$mailerType]) > 1) {
          $key = array_rand(self::$_mail[$mailerType]);
          return self::$_mail[$mailerType][$key];
        }
        else {
          return reset(self::$_mail[$mailerType]);
        }
      }
    }

    // always fallback to default mailer
    if (!isset(self::$_mail[$mailerType]) || empty(self::$_mail[$mailerType])) {
      $mailingInfo = &CRM_Core_BAO_Preferences::mailingPreferences();;

      if (defined('CIVICRM_MAILER_SPOOL') &&
        CIVICRM_MAILER_SPOOL
      ) {

        self::$_mail[$mailerType] = new CRM_Mailing_BAO_Spool();
      }
      elseif ($mailingInfo['outBound_option'] == 0) {
        if ($mailingInfo['smtpServer'] == '' ||
          !$mailingInfo['smtpServer']
        ) {
          CRM_Core_Error::fatal(ts('There is no valid smtp server setting. Click <a href=\'%1\'>Administer CiviCRM >> Global Settings</a> to set the SMTP Server.', [1 => CRM_Utils_System::url('civicrm/admin/setting', 'reset=1')]));
        }

        $params['host'] = $mailingInfo['smtpServer'] ? $mailingInfo['smtpServer'] : 'localhost';
        $params['port'] = $mailingInfo['smtpPort'] ? $mailingInfo['smtpPort'] : 25;

        if ($mailingInfo['smtpAuth']) {

          $params['username'] = $mailingInfo['smtpUsername'];
          $params['password'] = CRM_Utils_Crypt::decrypt($mailingInfo['smtpPassword']);
          $params['auth'] = TRUE;
        }
        else {
          $params['auth'] = FALSE;
        }

        // set the localhost value, CRM-3153
        $params['localhost'] = $_SERVER['SERVER_NAME'];

        self::$_mail[$mailerType] = &Mail::factory('smtp', $params);
      }
      elseif ($mailingInfo['outBound_option'] == 1) {
        if ($mailingInfo['sendmail_path'] == '' ||
          !$mailingInfo['sendmail_path']
        ) {
          CRM_Core_Error::fatal(ts('There is no valid sendmail path setting. Click <a href=\'%1\'>Administer CiviCRM >> Global Settings</a> to set the Sendmail Server.', [1 => CRM_Utils_System::url('civicrm/admin/setting', 'reset=1')]));
        }
        $params['sendmail_path'] = $mailingInfo['sendmail_path'];
        $params['sendmail_args'] = $mailingInfo['sendmail_args'];

        self::$_mail[$mailerType] = &Mail::factory('sendmail', $params);
      }
      elseif ($mailingInfo['outBound_option'] == 3) {
        $params = [];
        self::$_mail[$mailerType] = &Mail::factory('mail', $params);
      }
      else {
        CRM_Core_Session::setStatus(ts('There is no valid SMTP server Setting Or SendMail path setting. Click <a href=\'%1\'>Administer CiviCRM >> Global Settings</a> to set the OutBound Email.', [1 => CRM_Utils_System::url('civicrm/admin/setting', 'reset=1')]));
      }
    }
    return self::$_mail[$mailerType];
  }

  /**
   * delete the web server writable directories
   *
   * @param int $value 1 - clean templates_c, 2 - clean upload, 3 - clean both
   *
   * @access public
   *
   * @return void
   */
  public function cleanup($value, $rmdir = TRUE) {
    $value = (int ) $value;

    if ($value & 1) {
      // clean templates_c
      CRM_Utils_File::cleanDir($this->templateCompileDir, $rmdir);
      CRM_Utils_File::createDir($this->templateCompileDir);
    }
    if ($value & 2) {
      // clean upload dir
      CRM_Utils_File::cleanDir($this->uploadDir);
      CRM_Utils_File::createDir($this->uploadDir);
      CRM_Utils_File::restrictAccess($this->uploadDir);
    }
  }

  /**
   * verify that the needed parameters are not null in the config
   *
   * @param CRM_Core_Config (reference ) the system config object
   * @param array           (reference ) the parameters that need a value
   *
   * @return boolean
   * @static
   * @access public
   */
  static function check(&$config, &$required) {
    foreach ($required as $name) {
      if (CRM_Utils_System::isNull($config->$name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * reset the serialized array and recompute
   * use with care
   */
  function reset() {
    $query = "UPDATE civicrm_domain SET config_backend = null";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * one function to get domain ID
   */
  static function domainID($domainID = NULL, $reset = FALSE) {
    static $domain;
    if ($domainID) {
      $domain = $domainID;
    }
    if ($reset || empty($domain)) {
      $domain = defined('CIVICRM_DOMAIN_ID') ? CIVICRM_DOMAIN_ID : 1;
    }

    return $domain;
  }

  /**
   * clear db cache
   */
  static function clearDBCache() {
    $queries = ['TRUNCATE TABLE civicrm_acl_cache',
      'TRUNCATE TABLE civicrm_acl_contact_cache',
      'TRUNCATE TABLE civicrm_cache',
      'UPDATE civicrm_group SET cache_date = NULL',
      'TRUNCATE TABLE civicrm_group_contact_cache',
      'TRUNCATE TABLE civicrm_menu',
      'UPDATE civicrm_preferences SET navigation = NULL WHERE contact_id IS NOT NULL',
    ];

    foreach ($queries as $query) {
      CRM_Core_DAO::executeQuery($query);
    }

    // rebuild menu
    CRM_Core_Menu::store();
  }

  /**
   * clear up session
   */
  function sessionReset(){
    $session = CRM_Core_Session::singleton();
    $session->reset(2);
  }

  /**
   * clear leftover temporary tables
   */
  function clearTempTables() {
    // CRM-5645

    $dao = new CRM_Contact_DAO_Contact();
    $importTablePrefix = CRM_Import_ImportJob::TABLE_PREFIX;
    $query = "
 SELECT TABLE_NAME as import_table
   FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = %1 AND TABLE_NAME LIKE '{$importTablePrefix}_%'";
    $params = [1 => [$dao->database(), 'String']];
    $tableDAO = CRM_Core_DAO::executeQuery($query, $params);
    $importTables = [];
    while ($tableDAO->fetch()) {
      $microtime = str_replace($importTablePrefix.'_', '', $tableDAO->import_table);
      list($microtime) = explode('_', $microtime);
      // check if over 30 days
      if (is_numeric($microtime)) {
        $microtime = (int) $microtime;
        if (CRM_REQUEST_TIME - $microtime > 86400*30) {
          $importTables[] = $tableDAO->import_table;
        }
      }
      // no microtime format
      else {
        $importTables[] = $tableDAO->import_table;
      }
    }
    if (!empty($importTables)) {
      $importTable = CRM_Utils_Array::implode(',', $importTables);
      // drop leftover import temporary tables
      CRM_Core_DAO::executeQuery("DROP TABLE $importTable");
    }
  }

  /**
   * function to check if running in upgrade mode
   */
  static function isUpgradeMode($path = NULL) {
    if ($path && $path == 'civicrm/upgrade') {
      return TRUE;
    }
    $config = &self::singleton();
    if (CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET) == 'civicrm/upgrade') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Wrapper function to allow unit tests to switch user framework on the fly
   */
  public function setUserFramework($userFramework = NULL) {
    $this->userFramework = $userFramework;
    $this->_setUserFrameworkConfig($userFramework);
  }

  /**
   * add shutdown callback to static array
   *
   * @param string $type allow value is before | after
   * @param string $callback
   * @param array $args
   * @return bool
   */
  public static function addShutdownCallback($type, $callback, $args = NULL) {
    if (is_array($callback) && count($callback) == 2) {
      $callback = $callback[0].'::'.$callback[1];
    }
    if (!is_string($callback) || !is_callable($callback)) {
      return FALSE;
    }
    if (!in_array($type, ['before', 'after'])) {
      return FALSE;
    }
    $args = !empty($args) ? $args : [];
    if (!is_array($args)) {
      return FALSE;
    }
    self::$_shutdownCallbacks[$type][] = [$callback => $args];
    return TRUE;
  }
}
// end CRM_Core_Config

