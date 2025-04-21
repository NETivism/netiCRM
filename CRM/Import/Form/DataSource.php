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
 | Version 3, 19 November 2009.                                       |
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
 * This class delegates to the chosen DataSource to grab the data to be
 *  imported.
 */
class CRM_Import_Form_DataSource extends CRM_Core_Form {

  public $_params;
  private $_dataSource;

  private $_dataSourceIsValid = FALSE;

  private $_dataSourceClassFile;

  private $_dedupeRuleGroupId;

  private $_dedupeRuleGroups;

  private $_dedupeRuleFields;

  private $_contactTypes;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    //Test database user privilege to create table(Temporary) CRM-4725
    CRM_Core_Error::ignoreException();
    $daoTestPrivilege = new CRM_Core_DAO;
    $daoTestPrivilege->query("CREATE TEMPORARY TABLE import_job_permission_one(test int) ENGINE=InnoDB");
    $daoTestPrivilege->query("CREATE TEMPORARY TABLE import_job_permission_two(test int) ENGINE=InnoDB");
    $daoTestPrivilege->query("DROP TABLE IF EXISTS import_job_permission_one, import_job_permission_two");
    CRM_Core_Error::setCallback();

    if ($daoTestPrivilege->_lastError) {
      CRM_Core_Error::fatal(ts('Database Configuration Error: Insufficient permissions. Import requires that the CiviCRM database user has permission to create temporary tables. Contact your site administrator for assistance.'));
    }

    $results = array();
    $config = CRM_Core_Config::singleton();
    $handler = opendir($config->uploadDir);
    $errorFiles = array('sqlImport.errors', 'sqlImport.conflicts', 'sqlImport.duplicates', 'sqlImport.mismatch');

    while ($file = readdir($handler)) {
      if ($file != '.' && $file != '..' &&
        in_array($file, $errorFiles) && !is_writable($config->uploadDir . $file)
      ) {
        $results[] = $file;
      }
    }
    closedir($handler);
    if (!empty($results)) {
      CRM_Core_Error::fatal(ts('<b>%1</b> file(s) in %2 directory are not writable. Listed file(s) might be used during the import to log the errors occurred during Import process. Contact your site administrator for assistance.', array(1 => CRM_Utils_Array::implode(', ', $results), 2 => $config->uploadDir)));
    }

    $this->_dataSourceIsValid = FALSE;
    $this->_dataSource = CRM_Utils_Request::retrieve('dataSource', 'String',
      CRM_Core_DAO::$_nullObject
    );

    $this->_params = $this->controller->exportValues($this->_name);
    if (!$this->_dataSource) {
      //considering dataSource as base criteria instead of hidden_dataSource.
      $this->_dataSource = CRM_Utils_Array::value('dataSource',
        $_POST,
        CRM_Utils_Array::value('dataSource',
          $this->_params
        )
      );
      $this->assign('showOnlyDataSourceFormPane', FALSE);
    }
    else {
      $this->assign('showOnlyDataSourceFormPane', TRUE);
    }

    if (strpos($this->_dataSource, 'CRM_Import_DataSource_') === 0) {
      $this->_dataSourceIsValid = TRUE;
      $this->assign('showDataSourceFormPane', TRUE);
      $dataSourcePath = explode('_', $this->_dataSource);
      $templateFile = "CRM/Import/Form/" . $dataSourcePath[3] . ".tpl";
      $this->assign('dataSourceFormTemplateFile', $templateFile);
    }

    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $this->_contactTypes[CRM_Import_Parser::CONTACT_INDIVIDUAL] = ts('Individual');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $this->_contactTypes[CRM_Import_Parser::CONTACT_HOUSEHOLD] = ts('Household');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $this->_contactTypes[CRM_Import_Parser::CONTACT_ORGANIZATION] = ts('Organization');
    }
    foreach ($this->_contactTypes as $type => $tsName) {
      $supportFields = CRM_Dedupe_BAO_RuleGroup::supportedFields($type);
      foreach($supportFields as $array) {
        foreach($array as $name => $label){
          if (!isset($this->_dedupeRuleFields[$name])) {
            $this->_dedupeRuleFields[$name] = $label;
          } 
        }
      }
    }

    $dedupeGroupParams = array('level' => 'Strict');
    $this->_dedupeRuleGroups = CRM_Dedupe_BAO_RuleGroup::getDetailsByParams($dedupeGroupParams);
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */

  public function buildQuickForm() {

    // If there's a dataSource in the query string, we need to load
    // the form from the chosen DataSource class
    if ($this->_dataSourceIsValid) {
      $className = $this->_dataSource;
      $className::buildQuickForm( $this );
    }

    // Get list of data sources and display them as options
    $dataSources = $this->_getDataSources();

    $this->assign('urlPath', "civicrm/import");
    $this->assign('urlPathVar', 'snippet=4');

    $this->add('select', 'dataSource', ts('Data Source'), $dataSources, TRUE);
    $this->setDefaults(array('dataSource' => 'CRM_Import_DataSource_CSV'));

    // duplicate handling options
    $duplicateOptions = array();
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Skip'), CRM_Import_Parser::DUPLICATE_SKIP
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Update'), CRM_Import_Parser::DUPLICATE_UPDATE
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Fill'), CRM_Import_Parser::DUPLICATE_FILL
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('No Duplicate Checking'), CRM_Import_Parser::DUPLICATE_NOCHECK
    );

    $this->addGroup($duplicateOptions, 'onDuplicate',
      ts('For Duplicate Contacts')
    );



    $mappingArray = CRM_Core_BAO_Mapping::getMappings(CRM_Core_OptionGroup::getValue('mapping_type',
        'Import Contact',
        'name'
      ));

    $this->assign('savedMapping', $mappingArray);
    $this->addElement('select', 'savedMapping', ts('Mapping Option'), array('' => ts('- select -')) + $mappingArray);

    if ($loadeMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadeMapping);
      $this->setDefaults(array('savedMapping' => $loadeMapping));
    }

    $this->setDefaults(array('onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP));

    // contact types option
    $contactOptions = array();
    foreach($this->_contactTypes as $type => $tsName) {
      $contactOptions[] = $this->createElement('radio', NULL, NULL, $tsName, $type, $js);
    }
    $this->addGroup($contactOptions, 'contactType', ts('Contact Type'));

    $this->addElement('select', 'subType', ts('Subtype'));

    $this->setDefaults(array('contactType' =>
        CRM_Import_Parser::CONTACT_INDIVIDUAL,
      ));

    foreach ($this->_dedupeRuleGroups as $dedupegroup_id => $groupValues) {
      $fields = array();
      foreach($groupValues['fields'] as $name){
        if (isset($this->_dedupeRuleFields[$name])) {
          $fields[] = $this->_dedupeRuleFields[$name];
        }
      }
      $label = ts($groupValues['contact_type']);
      if ($groupValues['is_default']) {
        $label .= ts('Default');
      }
      $dedupeRule[$dedupegroup_id] = $label . ' - '.$groupValues['name'] . ' (' . CRM_Utils_Array::implode(', ', $fields) .')';
    }
    $this->addSelect('dedupeRuleGroupId', ts('Dedupe Rule of Contact'), $dedupeRule);
    if ($dedupeRuleGroupId = $this->get('dedupeRuleGroupId')) {
      $this->setDefaults(array('dedupeRuleGroupId' => $dedupeRuleGroupId));
    }


    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $config = CRM_Core_Config::singleton();
    $geoCode = FALSE;
    if (!empty($config->geocodeMethod)) {
      $geoCode = TRUE;
      $this->addElement('checkbox', 'doGeocodeAddress', ts('Lookup mapping info during import?'));
    }
    $this->assign('geoCode', $geoCode);

    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  private function _getDataSources() {
    // Open the data source dir and scan it for class files
    $config = CRM_Core_Config::singleton();
    $dataSourceDir = $config->importDataSourceDir;
    $dataSources = array();
    if (!is_dir($dataSourceDir)) {
      CRM_Core_Error::fatal("Import DataSource directory $dataSourceDir does not exist");
    }
    if (!$dataSourceHandle = opendir($dataSourceDir)) {
      CRM_Core_Error::fatal("Unable to access DataSource directory $dataSourceDir");
    }

    while (($dataSourceFile = readdir($dataSourceHandle)) !== FALSE) {
      $fileType = filetype($dataSourceDir . $dataSourceFile);
      $matches = array();
      if (($fileType == 'file' || $fileType == 'link') &&
        preg_match('/^(.+)\.php$/', $dataSourceFile, $matches)
      ) {
        $dataSourceClass = "CRM_Import_DataSource_" . $matches[1];
        require_once $dataSourceDir . DIRECTORY_SEPARATOR . $dataSourceFile;
        $object = new $dataSourceClass();
        $info = $object->getInfo();
        if ($object->checkPermission()) {
          $dataSources[$dataSourceClass] = $info['title'];
        }
      }
    }
    closedir($dataSourceHandle);
    return $dataSources;
  }

  /**
   * Call the DataSource's postProcess method to take over
   * and then setup some common data structures for the next step
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $this->controller->resetPage('MapField');

    if ($this->_dataSourceIsValid) {
      // Setup the params array
      $this->_params = $this->controller->exportValues($this->_name);

      $onDuplicate = $this->exportValue('onDuplicate');
      $contactType = $this->exportValue('contactType');
      $dateFormats = $this->exportValue('dateFormats');
      $savedMapping = $this->exportValue('savedMapping');
      $contactSubType = $this->exportValue('subType');

      $this->set('onDuplicate', $onDuplicate);
      $this->set('contactType', $contactType);
      $this->set('contactSubType', $contactSubType);
      $this->set('dateFormats', $dateFormats);
      $this->set('savedMapping', $savedMapping);
      $this->set('dataSource', $this->_params['dataSource']);
      $this->set('dedupeRuleGroupId', $this->_params['dedupeRuleGroupId']);
      $this->set('skipColumnHeader', CRM_Utils_Array::value('skipColumnHeader', $this->_params));

      // Get the PEAR::DB object
      $dao = new CRM_Core_DAO();
      $db = $dao->getDatabaseConnection();

      //hack to prevent multiple tables.
      $this->_params['import_table_name'] = $this->get('importTableName');
      if (!$this->_params['import_table_name']) {
        $tableName = str_replace('.', '_', microtime(TRUE));
        $this->_params['import_table_name'] = CRM_Import_ImportJob::TABLE_PREFIX.'_' . $tableName;
      }

      $className = $this->_dataSource;
      $className::postProcess($this, $this->_params, $db );

      // We should have the data in the DB now, parse it
      $importTableName = $this->get('importTableName');
      $primaryKeyName = $this->get('primaryKeyName');
      $statusFieldName = $this->get('statusFieldName');
      $mapper = array();

      $parser = new CRM_Import_Parser_Contact($mapper);
      $parser->setMaxLinesToProcess(100);
      $parser->run($importTableName, $mapper,
        CRM_Import_Parser::MODE_MAPFIELD, $contactType,
        $primaryKeyName, $statusFieldName,
        CRM_Import_Parser::DUPLICATE_SKIP, NULL, NULL, FALSE, NULL, $contactSubType
      );

      // add all the necessary variables to the form
      $parser->set($this);
    }
    else {
      CRM_Core_Error::fatal("Invalid DataSource on form post. This shouldn't happen!");
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Choose Data Source');
  }
}

