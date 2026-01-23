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
   * @var array
   * @access public
   */
  public static $exceptions;

  /**
   * define general exceptions and field types
   *
   * @var array
   * @access public
   */
  public static $definition = [
    'administer CiviCRM' => [
      'civicrm/admin/messageTemplates/add' => [
        'msg_html',
        'msg_text',
      ],
      'civicrm/admin/custom/group' => [
        'help_pre',
        'help_post',
      ],
      'civicrm/admin/custom/group/field/update' => [
        'help_pre',
        'help_post',
      ],
      'civicrm/admin/custom/group/field/add' => [
        'help_pre',
        'help_post',
      ],
      'civicrm/admin/uf/group' => [
        'help_pre',
        'help_post',
      ],
      'civicrm/ajax/rest' => [
        'json:json'
      ],
      'civicrm/admin/options/from_email_address' => ['label']
    ],
    'access CiviContribute' => [
      'civicrm/admin/contribute/settings' => [
        'intro_text',
        'footer_text',
      ],
      'civicrm/admin/contribute/thankYou' => [
        'thankyou_text',
        'thankyou_footer',
        'receipt_text',
      ],
      'civicrm/admin/contribute/membership' => [
        'new_text',
        'renewal_text',
      ],
      'civicrm/admin/contribute/widget' => [
        'widget_code',
        'about',
      ],
      'civicrm/admin/contribute/friend' => ['thankyou_text'],
      'civicrm/contribute/search' => [
        'html_message',
        'receipt_text',
        'cancel_reason',
        'contribution_source',
        'amount_level',
      ],
      'civicrm/contact/view/contribution' => ['from_email_address'],
    ],
    'access CiviEvent' => [
      'civicrm/event/manage/eventInfo' => ['description'],
      'civicrm/event/manage/registration' => [
        'intro_text',
        'footer_text',
        'confirm_text',
        'confirm_footer_text',
        'thankyou_text',
        'confirm_email_text',
      ],
      'civicrm/event/search' => ['html_message'],
      'civicrm/event/manage/friend' => ['thankyou_text'],
      'civicrm/contact/view/participant' => ['receipt_text'],
      'civicrm/contact/view/participant' => ['from_email_address'],
    ],
    'access CiviMail' => [
      'civicrm/admin/component' => [
        'body_text',
        'body_html',
      ],
    ],
    'access CiviMember' => [
      'civicrm/member/search' => ['html_message'],
    ],
    'access CiviReport' => [
      'civicrm/report/*/detail' => ['report_header', 'report_footer'],
      'civicrm/report/instance/*' => ['report_header', 'report_footer'],
      'civicrm/report/*/*' => ['report_header', 'report_footer'],
    ],
    'access CiviCRM' => [
      '*' => [
        'html_message', // too many urls have this
        'body_json', // contact search can submit newsletter / email, too
      ],
      'civicrm/activity' => ['details'],
      'civicrm/activity/add' => ['details'],
      'civicrm/contact/view/activity' => ['fromEmailAddress'],
      'civicrm/*/search' => ['fromEmailAddress'],
    ],
    '*' => [
      'civicrm/ajax/track' => ['data:json'],
      'civicrm/contribute/transact' => ['JSONData:json:_qf_ThankYou_display=1'],
      'civicrm/event/register' => ['JSONData:json:_qf_ThankYou_display=1'],
      '*/civicrm/extern/rest.php' => ['json:json'],
      '*/civicrm/extern/mcp.php' => ['IDS_php_input'],
    ],
  ];

  /**
   * define the threshold for the ids reactions
   *
   * @var array
   * @access private
   */
  private $_threshold = [
    'log' => 30,
    'warn' => 55,
    'kick' => 75,
  ];

  /**
   * This function includes the IDS vendor parts and runs the
   * detection routines on the request array.
   *
   * @param object cake controller object
   *
   * @return boolean
   */
  public function check(&$args) {
    $path = CRM_Utils_Array::implode('/', $args);

    // remove tracking parameters to prevent false positive
    $trackingG = ['fbclid', 'gclid', 'wbraid'];
    $trackingC = [ '__utma', '__utmb', '__utmc', '__utmv', '__utmz', '_gid', '_ga', '_gcl_au', '_fbp'];
    foreach($trackingG as $g) {
      if (isset($_GET[$g])) {
        unset($_GET[$g]);
      }
      if (isset($_REQUEST[$g])) {
        unset($_REQUEST[$g]);
      }
    }
    foreach($trackingC as $c) {
      if (isset($_COOKIE[$c])) {
        unset($_COOKIE[$c]);
      }
      // php 7 will not have these
      if (isset($_REQUEST[$c])) {
        unset($_REQUEST[$c]);
      }
    }

    // add request url and user agent
    $request = $_REQUEST;

    // lets request parameter handling by others
    // check document uri only
    $request['IDS_document_uri'] = isset($_SERVER['DOCUMENT_URI']) ? urldecode($_SERVER['DOCUMENT_URI']) : '';

    // init the PHPIDS
    $config = CRM_Core_Config::singleton();
    $configFile = $config->configAndLogDir . self::CONFIG_FILE;
    self::initConfig($configFile);

    $init = Init::init($configFile);

    // dynamic definition of ids config
    // only apply exception when has certain permission
    foreach(self::$definition as $perm => $permPath) {
      if (CRM_Core_Permission::check($perm) || $perm === '*') {
        foreach($permPath as $p => $epts) {
          if ($path == $p) {
            self::parseDefinitions($epts);
          }
          elseif ($p === '*') {
            self::parseDefinitions($epts);
          }
          elseif (strstr($p, '*')) {
            $regex = preg_quote($p);
            $regex = str_replace('\*', '.*', $regex);
            if (preg_match('@'.$regex.'@', $path)) {
              self::parseDefinitions($epts);
            }
          }
        }
      }
    }

    if (!empty(self::$exceptions)) {
      foreach(self::$exceptions as $type => $epts) {
        $epts = array_unique($epts);
        $init->config['General'][$type] = $epts;
      }
    }

    // add json as whole body when request content-type is application/json
    if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
      $request['IDS_php_input'] = file_get_contents('php://input');
      $init->config['General']['json'][] = 'IDS_php_input';
    }

    $ids = new Monitor($init, ['sqli', 'dt', 'id', 'lfi', 'rfe']);
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
            $hostname = CRM_Utils_Type::escape($_SERVER['HTTP_HOST'], 'DirectoryName', FALSE);
            $configFile = $dir . DIRECTORY_SEPARATOR . $hostname . DIRECTORY_SEPARATOR . $tsLocale . DIRECTORY_SEPARATOR . 'ConfigAndLog' . DIRECTORY_SEPARATOR . self::CONFIG_FILE;
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
    if ($impact >= $this->_threshold['kick']) {
      $this->record($result, 'kick', $impact);
      $this->kick();
    }
    elseif ($impact >= $this->_threshold['warn']) {
      $this->record($result, 'warn', $impact);
      $this->warn();
    }
    elseif ($impact >= $this->_threshold['log']) {
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
    if(CRM_Utils_System::isUserLoggedIn()) {
      return TRUE;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $session->reset(2);
      CRM_Core_Error::fatal(ts('There is a validation error with your HTML input. Your activity is a bit suspicious, hence aborting'));
    }
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
    $data = [
      'time' => date('c'),
      'ip' => $ip,
      'impact' => $impact,
      'reaction' => $reaction,
      'domain' => $_SERVER['HTTP_HOST'],
      'account' => CRM_Utils_System::getLoggedInUfID(),
      'contact' => $contact ? $contact : 0,
      'url' => $_SERVER['REQUEST_URI'],
      'method' => $_SERVER['REQUEST_METHOD'],
      'content_type' => $_SERVER["CONTENT_TYPE"],
    ];
    foreach ($result as $event) {
      $filters = $event->getFilters();
      $description = [];
      foreach($filters as $filter) {
        $description[] = [
          'id' => $filter->getId(),
          'desc' => $filter->getDescription(),
        ];
      }
      $log = [
        'field' => $event->getName(),
        'value' => $event->getValue(),
        'tags' => $event->getTags(),
        'filters' => $description,
      ];
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

  public function parseDefinitions($definition) {
    if (is_array($definition)) {
      foreach($definition as $def) {
        list($field, $type, $condition) = explode(':', $def, 3);
        if (empty($type)) {
          $type = 'exceptions';
        }
        if ($condition) {
          list($cond, $value) = explode('=', $condition, 2);
          if ($_REQUEST[$cond] == $value) {
            self::$exceptions[$type][] = $field;
          }
        }
        else {
          self::$exceptions[$type][] = $field;
        }
      }
    }
  }
}

