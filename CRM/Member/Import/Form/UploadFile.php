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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Member/Import/Parser/Membership.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_UploadFile extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/member/import', 'reset=1'));

    $this->_contactTypes = array(
      CRM_Member_Import_Parser::CONTACT_INDIVIDUAL,
      CRM_Member_Import_Parser::CONTACT_HOUSEHOLD,
      CRM_Member_Import_Parser::CONTACT_ORGANIZATION,
    );
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
    //Setting Upload File Size
    $config = CRM_Core_Config::singleton();
    if ($config->maxImportFileSize >= 8388608) {
      $uploadFileSize = 8388608;
    }
    else {
      $uploadFileSize = $config->maxImportFileSize;
    }
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);

    $this->assign('uploadSize', $uploadSize);

    $this->add('file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=60', TRUE);

    $this->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');
    $this->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize);
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');

    $this->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));

    $duplicateOptions = array(
      CRM_Member_Import_Parser::DUPLICATE_SKIP => ts('Insert new Membership'),
      CRM_Member_Import_Parser::DUPLICATE_UPDATE => ts('Update existing Membership'),
    );
    $this->addRadio('onDuplicate', ts('Import mode'), $duplicateOptions);

    $createContactMode[] = $this->addElement('advcheckbox', 'createContact', NULL, ts('Create new contacts.'), array('onClick' => 'showHideCreateContact();'));
    $createContactMode[] = $this->addElement('advcheckbox', 'updateMember', NULL, ts('Update current membership data.'), array('onClick' => 'showHideCreateContact();'));
    $label = ts('Create New Contact');
    $this->addGroup($createContactMode, 'createContactMode', $label);
    $this->addRule('createContactMode', ts('%1 is a required field.', array(1 => $label)), 'required');

    //contact types option
    $contactOptions = array();
    foreach($this->_contactTypes as $type) {
      if (CRM_Contact_BAO_ContactType::isActive($type)) {
        $contactOptions[$type] = ts($type);
      }
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactOptions);

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
      $dedupeRule[$dedupegroup_id] = $label . ' - '.$groupValues['name'] . ' (' . implode(', ', $fields) .')';
    }
    $this->add('select', 'dedupeRuleGroup', ts('Dedupe Rule of Contact'), $dedupeRule);

    //get the saved mapping details
    require_once "CRM/Core/BAO/Mapping.php";
    require_once "CRM/Core/OptionGroup.php";
    $mappingArray = CRM_Core_BAO_Mapping::getMappings(CRM_Core_OptionGroup::getValue('mapping_type',
        'Import Membership',
        'name'
      ));
    $this->assign('savedMapping', $mappingArray);
    $this->add('select', 'savedMapping', ts('Mapping Option'), array('' => ts('- select -')) + $mappingArray);
    $this->addElement('submit', 'loadMapping', ts('Load Mapping'), NULL, array('onclick' => 'checkSelect()'));

    //build date formats
    require_once 'CRM/Core/Form/Date.php';
    CRM_Core_Form_Date::buildAllowedDateFormats($this);

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

  public function setDefaultValues(){
    $defaults = $this->_submitValues;
    if (!$defaults['onDuplicate']) {
      $defaults['onDuplicate'] = CRM_Member_Import_Parser::DUPLICATE_SKIP;
    }
    if (!$defaults['createContactOption']) {
      $defaults['createContactOption'] = CRM_Member_Import_Parser::CONTACT_NOIDCREATE;
    }
    if (!$defaults['contactType']) {
      $defaults['contactType'] = CRM_Member_Import_Parser::CONTACT_INDIVIDUAL;
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
    if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE) {
      $createContactOption = CRM_Contribute_Import_Parser::CONTACT_DONTCREATE; 
      $this->set('createContactOption', $createContactOption);
    }
    else{
      $this->set('createContactOption', $createContactOption);
    }

    $session = CRM_Core_Session::singleton();
    $session->set("dateTypes", $dateFormats);

    $config = CRM_Core_Config::singleton();
    $seperator = $config->fieldSeparator;

    $mapper = array();

    $parser = new CRM_Member_Import_Parser_Membership($mapper);
    $parser->setMaxLinesToProcess(100);
    $parser->run($fileName, $seperator,
      $mapper,
      $skipColumnHeader,
      CRM_Member_Import_Parser::MODE_MAPFIELD, $contactType
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

