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
 * This class gets the name of the file to upload
 */
class CRM_Contribute_Import_Form_UploadFile extends CRM_Core_Form {

  public $_submitValues;
  public $_params;
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
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/contribute/import', 'reset=1'));

    $this->_contactTypes = [
      CRM_Contribute_Import_Parser::CONTACT_INDIVIDUAL,
      CRM_Contribute_Import_Parser::CONTACT_HOUSEHOLD,
      CRM_Contribute_Import_Parser::CONTACT_ORGANIZATION,
    ];
    foreach ($this->_contactTypes as $type) {
      $supportFields = &CRM_Dedupe_BAO_RuleGroup::supportedFields($type);
      foreach($supportFields as $array) {
        foreach($array as $name => $label){
          if (!isset($this->_dedupeRuleFields[$name])) {
            $this->_dedupeRuleFields[$name] = $label;
          }  
        }
      }
    }

    $dedupeGroupParams = ['level' => 'Strict'];
    $this->_dedupeRuleGroups = CRM_Dedupe_BAO_RuleGroup::getDetailsByParams($dedupeGroupParams);
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    //Setting Upload File Size
    CRM_Import_DataSource_CSV::buildQuickForm($this);

    $duplicateOptions = [
      CRM_Contribute_Import_Parser::DUPLICATE_SKIP => ts('Insert new contributions'),
      CRM_Contribute_Import_Parser::DUPLICATE_UPDATE => ts('Update existing contributions'),
    ];
    $this->addRadio('onDuplicate', ts('Import mode'), $duplicateOptions, NULL, NULL, TRUE);

    $duplicateContactOptions = [
      CRM_Contribute_Import_Parser::CONTACT_NOIDCREATE => ts('Create contact only on identifier not import'),
      CRM_Contribute_Import_Parser::CONTACT_AUTOCREATE => ts('Create contact when not found'),
      CRM_Contribute_Import_Parser::CONTACT_DONTCREATE => ts('Do not create or update contact'),
    ];
    $this->addRadio('createContactOption', ts('Create New Contact'), $duplicateContactOptions, NULL, '<br>');

    //contact types option
    $contactOptions = [];
    foreach($this->_contactTypes as $type) {
      if (CRM_Contact_BAO_ContactType::isActive($type)) {
        $contactOptions[$type] = ts($type);
      }
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactOptions);

    foreach ($this->_dedupeRuleGroups as $dedupegroup_id => $groupValues) {
      $fields = [];
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
    $this->add('select', 'dedupeRuleGroup', ts('Dedupe Rule of Contact'), $dedupeRule);

    //get the saved mapping details
    $mappingArray = CRM_Core_BAO_Mapping::getMappings(CRM_Core_OptionGroup::getValue('mapping_type', 'Import Contribution', 'name'));
    $this->assign('savedMapping', $mappingArray);
    $this->add('select', 'savedMapping', ts('Mapping Option'), ['' => ts('- select -')] + $mappingArray);
    $this->addElement('submit', 'loadMapping', ts('Load Mapping'), NULL, ['onclick' => 'checkSelect()']);

    //build date formats
    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $this->addButtons([
        ['type' => 'upload',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  public function setDefaultValues(){
    $defaults = $this->_submitValues;
    if (!$defaults['createContactOption']) {
      $defaults['createContactOption'] = CRM_Contribute_Import_Parser::CONTACT_NOIDCREATE;
    }
    if (!$defaults['contactType']) {
      $defaults['contactType'] = CRM_Contribute_Import_Parser::CONTACT_INDIVIDUAL;
    }

    if ($loadeMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadeMapping);
      $defaults['savedMapping'] = $loadeMapping;
    }

    return $defaults;
  }

  /**
   * Process the uploaded file
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $this->controller->resetPage('MapField');
    $this->_params = $this->controller->exportValues($this->_name);

    $fileName = $this->controller->exportValue($this->_name, 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue($this->_name, 'skipColumnHeader');
    $onDuplicate = $this->controller->exportValue($this->_name, 'onDuplicate');
    $contactType = $this->controller->exportValue($this->_name, 'contactType');
    $dateFormats = $this->controller->exportValue($this->_name, 'dateFormats');
    $savedMapping = $this->controller->exportValue($this->_name, 'savedMapping');
    $createContactOption = $this->controller->exportValue($this->_name, 'createContactOption');
    $dedupeRuleGroup = $this->controller->exportValue($this->_name, 'dedupeRuleGroup');

    $this->set('onDuplicate', $onDuplicate);
    $this->set('contactType', $contactType);
    $this->set('dateFormats', $dateFormats);
    $this->set('savedMapping', $savedMapping);
    $this->set('dedupeRuleGroup', $dedupeRuleGroup);
    $this->set('skipColumnHeader', $skipColumnHeader);

    if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE) {
      $createContactOption = CRM_Contribute_Import_Parser::CONTACT_DONTCREATE; 
      $this->set('createContactOption', $createContactOption);
    }
    else{
      $this->set('createContactOption', $createContactOption);
    }

    // Get the PEAR::DB object
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    //hack to prevent multiple tables.
    $this->_params['import_table_name'] = $this->get('importTableName');
    if (!$this->_params['import_table_name']) {
      $tableName = str_replace('.', '_', microtime(TRUE));
      $this->_params['import_table_name'] = CRM_Import_ImportJob::TABLE_PREFIX.'_' . $tableName;
    }
    CRM_Import_DataSource_CSV::postProcess($this, $this->_params, $db);
    $importTableName = $this->get('importTableName');
    $primaryKeyName = $this->get('primaryKeyName');
    $statusFieldName = $this->get('statusFieldName');

    $mapper = [];

    $parser = new CRM_Contribute_Import_Parser_Contribution($mapper);
    $parser->setMaxLinesToProcess(100);
    $parser->run(
      $importTableName,
      $mapper,
      CRM_Contribute_Import_Parser::MODE_MAPFIELD,
      $contactType,
      $primaryKeyName,
      $statusFieldName,
      CRM_Contribute_Import_Parser::DUPLICATE_SKIP,
      NULL, 
      NULL,
      CRM_Contribute_Import_Parser::CONTACT_NOIDCREATE
    );

    // add all the necessary variables to the form
    $parser->set($this);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Upload Data');
  }
}

