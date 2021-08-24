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
require_once 'IDS/autoload.php';

use IDS\Init;
use IDS\Monitor;
use IDS\Report;

class CRM_Core_IDS {

  CONST CONFIG_FILE = 'Config.IDS.ini';

  /**
   * define the threshold for the ids reactions
   */
  private $threshold = array(
    'log' => 25,
    'warn' => 50,
    'kick' => 75,
  );

  /**
   * the init object
   */
  private $init = NULL;

  /**
   * This function includes the IDS vendor parts and runs the
   * detection routines on the request array.
   *
   * @param object cake controller object
   *
   * @return boolean
   */
  public function check(&$args) {

    // lets bypass a few civicrm urls from this check
    static $skip = array('civicrm/ajax', 'civicrm/admin/setting/updateConfigBackend', 'civicrm/admin/messageTemplates');
    $path = implode('/', $args);
    if (in_array($path, $skip)) {
      return;
    }

    // add request url and user agent
    $request = $_REQUEST;
    $request['IDS_request_uri'] = $_SERVER['REQUEST_URI'];
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $request['IDS_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    // add json as whole body when request content-type is application/json
    if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
      $request['IDS_php_input'] = file_get_contents('php://input');
    }

    // init the PHPIDS and pass the REQUEST array
    $config = CRM_Core_Config::singleton();
    $configFile = $config->configAndLogDir . self::CONFIG_FILE;
    self::initConfig($configFile);

    $init = Init::init($configFile);

    // dynamic definition of ids config
    if ($path == 'civicrm/ajax/track') {
      $init->config['General']['json'][] = 'data';
    }
    if (isset($request['IDS_php_input'])) {
      $init->config['General']['json'][] = 'IDS_php_input';
    }
    $ids = new Monitor($init);
    $result = $ids->run($request);
    unset($request); // release memory

    $impact = $result->getImpact();
    if (!$result->isEmpty()) {
      $this->react($result, $impact);
    }

    return TRUE;
  }

  public static function initConfig($configFile = NULL, $forceCreate = FALSE) {
    global $tsLocale;
    $config = CRM_Core_Config::singleton();

    // loop all sapi names
    if (empty($configFile)) {
      $temp = CRM_Utils_System::cmsDir('temp');
      if ($temp) {
        $dirs = glob($temp.DIRECTORY_SEPARATOR.'smarty*');
        if (!empty($dirs)) {
          foreach($dirs as $dir) {
            $configFile = $dir . DIRECTORY_SEPARATOR . $_SERVER['HTTP_HOST'] . DIRECTORY_SEPARATOR . $tsLocale . DIRECTORY_SEPARATOR . 'ConfigAndLog' . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
            self::initConfig($configFile, $forceCreate);
          }
        }
      }
      return;
    }

    if (!empty($configFile) && (!file_exists($configFile) || $forceCreate)) {
      $tmpDir = dirname($configFile).DIRECTORY_SEPARATOR;
      // also clear the stat cache in case we are upgrading
      clearstatcache();

      global $civicrm_root;
      $civicrm_path = rtrim($civicrm_root, '/');
      $contents = "
[General]
    filter_type         = json
    filter_path         = {$civicrm_path}/packages/IDS/default_filter.json
    tmp_path            = $tmpDir
    HTML_Purifier_Path  = IDS/vendors/htmlpurifier/HTMLPurifier.auto.php
    HTML_Purifier_Cache = $tmpDir
    scan_keys           = false
    exceptions[]        = __utmz
    exceptions[]        = __utmc
    exceptions[]        = widget_code
    exceptions[]        = html_message
    exceptions[]        = msg_html
    exceptions[]        = msg_text
    exceptions[]        = msg_subject
    exceptions[]        = description
    exceptions[]        = intro
    exceptions[]        = thankyou_text
    exceptions[]        = intro_text
    exceptions[]        = body_text
    exceptions[]        = footer_text
    exceptions[]        = thankyou_text
    exceptions[]        = thankyou_footer
    exceptions[]        = thankyou_footer_text
    exceptions[]        = receipt_text
    exceptions[]        = new_text
    exceptions[]        = renewal_text
    exceptions[]        = help_pre
    exceptions[]        = help_post
    exceptions[]        = confirm_title
    exceptions[]        = confirm_text
    exceptions[]        = confirm_footer_text
    exceptions[]        = confirm_email_text
    exceptions[]        = report_header
    exceptions[]        = report_footer
    exceptions[]        = body_html
    json[]              = body_json

[Caching]
    caching         = file
    expiration_time = 600
    path            = {$tmpDir}default_filter.cache
";
      if (file_put_contents($configFile, $contents) === FALSE) {
        CRM_Core_Error::movedSiteError($configFile);
      }

      // also create the .htaccess file so we prevent the reading of the log and ini files
      // via a browser, CRM-3875
      require_once 'CRM/Utils/File.php';
      CRM_Utils_File::restrictAccess($config->configAndLogDir);
    }
  }

  /**
   * This function rects on the values in
   * the incoming results array.
   *
   * Depending on the impact value certain actions are
   * performed.
   *
   * @param Report $result
   * @param int $impact
   *
   * @return boolean
   */
  private function react(Report $result, $impact) {
    if ($impact >= $this->threshold['kick']) {
      $this->record($result, 'kick', $impact);
      $this->kick();
    }
    elseif ($impact >= $this->threshold['warn']) {
      $this->record($result, 'warn', $impact);
      $this->warn();
    }
    elseif ($impact >= $this->threshold['log']) {
      $this->record($result, 'log', $impact);
      $this->log();
    }
  }

  /**
   * These function 
   */
  private function log() {
    return TRUE;
  }

  private function warn() {
    return TRUE; 
  }

  private function kick() {
    $session = CRM_Core_Session::singleton();
    $session->reset(2);

    CRM_Core_Error::fatal(ts('There is a validation error with your HTML input. Your activity is a bit suspicious, hence aborting'));
  }

  /**
   * Record suspicious action to log
   *
   * @param Report $result Object of \IDS\Report
   * @param string $reaction action that civicrm take
   * @param int $impact calculation from IDS
   * @return void
   */
  private function record($result, $reaction, $impact) {
    $ip = CRM_Utils_System::ipAddress();
    $session = CRM_Core_Session::singleton();
    $contact = $session->get('userID');
    $data = array(
      'time' => date('c'),
      'ip' => $ip,
      'domain' => $_SERVER['HTTP_HOST'],
      'account' => CRM_Utils_System::getLoggedInUfID(),
      'contact' => $contact ? $contact : 0,
      'url' => $_SERVER['REQUEST_URI'],
      'method' => $_SERVER['REQUEST_METHOD'],
      'content_type' => $_SERVER["CONTENT_TYPE"],
    );
    foreach ($result as $event) {
      $filters = $event->getFilters();
      $description = array();
      foreach($filters as $filter) {
        $description[] = array(
          'id' => $filter->getId(),
          'desc' => $filter->getDescription(),
        );
      }
      $log = array(
        'impact' => $impact,
        'reaction' => $reaction,
        'field' => $event->getName(),
        'value' => $event->getValue(),
        'tags' => $event->getTags(),
        'filters' => $description,
      );
      $data['events'][] = $log;
    }
    // civicrm logger
    $dataJSON = json_encode($data);
    CRM_Core_Error::debug_var('PHPIDS', $dataJSON);

    // special logger for centralize json parser
    if ($logPath = CRM_Core_Config::singleton()->IDSLogPath) {
      @file_put_contents($logPath, $dataJSON."\n", FILE_APPEND);
    }
  }
}

