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

    #add request url and user agent
    $_REQUEST['IDS_request_uri'] = $_SERVER['REQUEST_URI'];
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $_REQUEST['IDS_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    require_once 'IDS/Init.php';

    // init the PHPIDS and pass the REQUEST array
    $config = CRM_Core_Config::singleton();
    $configFile = $config->configAndLogDir . self::CONFIG_FILE;
    self::initConfig($configFile);

    $init = IDS_Init::init($configFile);
    $ids = new IDS_Monitor($_REQUEST, $init);
    $result = $ids->run();

    if (!$result->isEmpty()) {
      $this->react($result);
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
    filter_type         = xml
    filter_path         = {$civicrm_path}/packages/IDS/default_filter.xml
    tmp_path            = $tmpDir
    HTML_Purifier_Path  = IDS/vendors/htmlpurifier/HTMLPurifier.auto.php
    HTML_Purifier_Cache = $tmpDir
    scan_keys           = false
    exceptions[]        = __utmz
    exceptions[]        = __utmc
    exceptions[]        = widget_code
    exceptions[]        = html_message
    exceptions[]        = body_html
    exceptions[]        = body_json
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
    exceptions[]        = data
    exceptions[]        = instructions
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
   * @param IDS_Report $result
   *
   * @return boolean
   */
  private function react(IDS_Report $result) {

    $impact = $result->getImpact();
    if ($impact >= $this->threshold['kick']) {
      $this->log($result, 3, $impact);
      $this->kick($result);
      return TRUE;
    }
    elseif ($impact >= $this->threshold['warn']) {
      // $this->log($result, 2, $impact);
      $this->warn($result);
      return TRUE;
    }
    elseif ($impact >= $this->threshold['log']) {
      // $this->log($result, 0, $impact);
      return TRUE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * This function writes an entry about the intrusion
   * to the intrusion database
   *
   * @param array $results
   *
   * @return boolean
   */
  private function log($result, $reaction = 0, $impact = NULL) {
    $config = CRM_Core_Config::singleton();
    $ip = CRM_Utils_System::ipAddress();

    $data = array();
    $session = CRM_Core_Session::singleton();
    foreach ($result as $event) {
      $filters = $event->getFilters();
      $description = array();
      foreach($filters as $filter) {
        $description[] = $filter->getId().":".$filter->getDescription();
      }

      $log = array(
        'impact' => $impact,
        'name' => $event->getName(),
        'tag' => implode("|", $event->getTags()),
        'problem' => "\n".implode("\n", $description),
        'value' => $event->getValue(),
        'page' => $_SERVER['REQUEST_URI'],
        'userid' => $session->get('userID'),
        'ip' => $ip,
        'reaction' => $reaction,
      );
      $data[] = $log;
    }
    if (!empty($data)) {
      $data['post'] = $_POST;
      CRM_Core_Error::debug_var('IDS Detector Details', $data);
    }
    return TRUE;
  }

  /**
   * //todo
   *
   *
   */
  private function warn($result) {
    return $result;
  }

  /**
   *  //todo
   *
   *
   */
  private function kick($result) {
    $session = CRM_Core_Session::singleton();
    $session->reset(2);

    CRM_Core_Error::fatal(ts('There is a validation error with your HTML input. Your activity is a bit suspicious, hence aborting'));
  }
}

