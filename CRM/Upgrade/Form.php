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


class CRM_Upgrade_Form extends CRM_Core_Form {

  protected $_config;

  // note latestVersion is legacy code, and
  // only used for 2.0 -> 2.1 upgrade
  public $latestVersion;

  /**
   * Upgrade for multilingual
   *
   * @var boolean
   * @public
   */
  public $multilingual = FALSE;

  /**
   * locales available for multilingual upgrade
   *
   * @var array
   * @public
   */
  public $locales;

  /**
   * number to string mapper
   *
   * @var array
   * @public
   */
  static $_numberMap = [0 => 'Zero',
    1 => 'One',
    2 => 'Two',
    3 => 'Three',
    4 => 'Four',
    5 => 'Five',
    6 => 'Fix',
    7 => 'Seven',
    8 => 'Eight',
    9 => 'Nine',
  ]; function __construct($state = NULL,
    $action = CRM_Core_Action::NONE,
    $method = 'post',
    $name = NULL
  ) {
    $this->_config = CRM_Core_Config::singleton();

    // this->latestVersion is legacy code, only used for 2.0 -> 2.1 upgrade
    // latest ver in 2.1 series
    $this->latestVersion = '2.1.6';


    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

    $this->multilingual = (bool) $domain->locales;
    $this->locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    $smarty = CRM_Core_Smarty::singleton();
    $smarty->compile_dir = $this->_config->templateCompileDir;
    $smarty->assign('multilingual', $this->multilingual);
    $smarty->assign('locales', $this->locales);

    // we didn't call CRM_Core_BAO_ConfigSetting::retrieve(), so we need to set $dbLocale by hand
    if ($this->multilingual) {
      global $dbLocale;
      $dbLocale = "_{$this->_config->lcMessages}";
    }

    parent::__construct($state, $action, $method, $name);
  }

  static function &incrementalPhpObject($version) {
    static $incrementalPhpObject = [];

    $versionParts = explode('.', $version);
    $versionName = self::$_numberMap[$versionParts[0]] . self::$_numberMap[$versionParts[1]];

    if (!CRM_Utils_Array::arrayKeyExists($versionName, $incrementalPhpObject)) {
      $className = "CRM_Upgrade_Incremental_php_{$versionName}";
      $incrementalPhpObject[$versionName] = new $className;
    }
    return $incrementalPhpObject[$versionName];
  }

  function checkVersionRelease($version, $release) {
    $versionParts = explode('.', $version);
    if ($versionParts[2] == $release) {
      return TRUE;
    }
    return FALSE;
  }

  function checkSQLConstraints(&$constraints) {
    $pass = $fail = 0;
    foreach ($constraints as $constraint) {
      if ($this->checkSQLConstraint($constraint)) {
        $pass++;
      }
      else {
        $fail++;
      }
      return [$pass, $fail];
    }
  }

  function checkSQLConstraint($constraint) {
    // check constraint here
    return TRUE;
  }

  function source($fileName, $isQueryString = FALSE) {


    CRM_Utils_File::sourceSQLFile($this->_config->dsn,
      $fileName, NULL, $isQueryString
    );
  }

  function preProcess() {
    CRM_Utils_System::setTitle($this->getTitle());
    if (!$this->verifyPreDBState($errorMessage)) {
      if (!isset($errorMessage)) {
        $errorMessage = 'pre-condition failed for current upgrade step';
      }
      CRM_Core_Error::fatal($errorMessage);
    }
    $this->assign('recentlyViewed', FALSE);
  }

  function buildQuickForm() {
    $this->addDefaultButtons($this->getButtonTitle(),
      'next',
      NULL,
      TRUE
    );
  }

  function getTitle() {
    return ts('Title not Set');
  }

  function getFieldsetTitle() {
    return ts('');
  }

  function getButtonTitle() {
    return ts('Continue');
  }

  function getTemplateFileName() {
    $this->assign('title',
      $this->getFieldsetTitle()
    );
    $this->assign('message',
      $this->getTemplateMessage()
    );
    return 'CRM/Upgrade/Base.tpl';
  }

  function postProcess() {
    $this->upgrade();

    if (!$this->verifyPostDBState($errorMessage)) {
      if (!isset($errorMessage)) {
        $errorMessage = 'post-condition failed for current upgrade step';
      }
      CRM_Core_Error::fatal($errorMessage);
    }
  }

  function runQuery($query) {
    return CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );
  }

  function setVersion($version) {
    $this->logVersion($version);

    $query = "
UPDATE civicrm_domain
SET    version = '$version'
";
    return $this->runQuery($query);
  }

  function logVersion($newVersion) {
    if ($newVersion) {
      $oldVersion = CRM_Core_BAO_Domain::version();


      $session = CRM_Core_Session::singleton();
      $logParams = [
        'entity_table' => 'civicrm_domain',
        'entity_id' => 1,
        'data' => "upgrade:{$oldVersion}->{$newVersion}",
        // lets skip 'modified_id' for now, as it causes FK issues And
        // is not very important for now.
        'modified_date' => date('YmdHis'),
      ];
      CRM_Core_BAO_Log::add($logParams);
      return TRUE;
    }

    return FALSE;
  }

  function checkVersion($version) {
    $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
      $version, 'id',
      'version'
    );
    return $domainID ? TRUE : FALSE;
  }

  function getRevisionSequence() {
    $revList = [];
    $sqlDir = CRM_Utils_Array::implode(DIRECTORY_SEPARATOR,
      [dirname(__FILE__), 'Incremental', 'sql']
    );
    $sqlFiles = scandir($sqlDir);

    $sqlFilePattern = '/^(\d{1,2}\.\d{1,2}\.(\d{1,2}|\w{4,7}))\.(my)?sql(\.tpl)?$/i';
    foreach ($sqlFiles as $file) {
      if (preg_match($sqlFilePattern, $file, $matches)) {
        if (!in_array($matches[1], $revList)) {
          $revList[] = $matches[1];
        }
      }
    }

    // sample test list
    /*         $revList = array('2.1.0', '2.2.beta2', '2.2.beta1', '2.2.alpha1', */

    /*                          '2.2.alpha3', '2.2.0', '2.2.2', '2.1.alpha1', '2.1.3'); */


    usort($revList, 'version_compare');
    return $revList;
  }

  function processLocales($tplFile, $rev) {
    $smarty = CRM_Core_Smarty::singleton();

    $this->source($smarty->fetch($tplFile), TRUE);

    if ($this->multilingual) {

      CRM_Core_I18n_Schema::rebuildMultilingualSchema($this->locales, $rev);
    }
    return $this->multilingual;
  }

  function processSQL($rev) {
    $sqlFile = CRM_Utils_Array::implode(DIRECTORY_SEPARATOR,
      [dirname(__FILE__), 'Incremental',
        'sql', $rev . '.mysql',
      ]
    );
    $tplFile = "$sqlFile.tpl";

    if (file_exists($tplFile)) {
      $this->processLocales($tplFile, $rev);
    }
    else {
      if (!file_exists($sqlFile)) {
        CRM_Core_Error::fatal("sqlfile - $rev.mysql not found.");
      }
      $this->source($sqlFile);
    }
  }
}

