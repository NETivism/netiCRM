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
require_once 'CRM/Contribute/Import/Parser/Contribution.php';

/**
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Contribute_Import_Form_Preview extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');

    //get the data from the session
    $this->_dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
    $softCreditFields = $this->get('softCreditFields');
    $pcpCreatorFields = $this->get('pcpCreatorFields');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $mismatchCount = $this->get('unMatchCount');

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
      $this->assign('loadedMapping', $mappingId);
      $this->assign('savedName', $mapDAO->name);
    }


    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);

      $columnNames = $this->_columnHeaders = $this->get('originalColHeader');
      array_unshift($this->_dataValues, $this->_columnHeaders);
    }
    else {
      $this->assign('skipColumnHeader', NULL);
      $this->assign('rowDisplayCount', 2);
    }
    $this->assign('dataValues', $this->_dataValues);

    $tableName = $this->get('importTableName');
    $fileName = str_replace('civicrm_import_job_', 'import_', $tableName);
    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Contribute_Import_Parser::ERROR . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($conflictRowCount) {
      $urlParams = 'type=' . CRM_Contribute_Import_Parser::CONFLICT . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Contribute_Import_Parser::NO_MATCH . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }


    $properties = array(
      'mapper', 'softCreditFields', 'pcpCreatorFields', 'columnCount',
      'totalRowCount', 'validRowCount',
      'invalidRowCount', 'conflictRowCount',
      'downloadErrorRecordsUrl',
      'downloadConflictRecordsUrl',
      'downloadMismatchRecordsUrl',
    );

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }

    $statusID = $this->get('statusID');
    if (!$statusID) {
      $statusID = md5(uniqid(rand(), TRUE));
      $this->set('statusID', $statusID);
    }
    $statusUrl = CRM_Utils_System::url('civicrm/ajax/status', "id={$statusID}", FALSE, NULL, FALSE);
    $this->assign('statusUrl', $statusUrl);
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $attr = array('onclick' => "return verify();");
    $locked = CRM_Core_Lock::isUsed($this->get('importTableName'));
    if ($locked) {
      $attr['disabled'] = 'disabled';
      $this->assign('locked_import', TRUE);
    }
    $this->addButtons(array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array('type' => 'next',
          'name' => ts('Import Now >>'),
          'js' => $attr,
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Preview');
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // prevent table error and duplicated import
    $isCompleted = $this->get('complete');
    if ($isCompleted) {
      return;
    }
    $config = CRM_Core_Config::singleton();
    $onDuplicate = $this->get('onDuplicate');

    $importJobParams = array(
      'invalidRowCount' => $this->get('invalidRowCount'),
      'conflictRowCount' => $this->get('conflictRowCount'),
      'onDuplicate' => $this->get('onDuplicate'),
      'mapper' => $this->get('mapperKeys'),
      'contactType' => $this->get('contactType'),
      'primaryKeyName' => $this->get('primaryKeyName'),
      'statusFieldName' => $this->get('statusFieldName'),
      'statusID' => $this->get('statusID'),
      'totalRowCount' => $this->get('totalRowCount'),
      'dedupeRuleGroupId' => $this->get('dedupeRuleGroup'),
      'createContactOption' => $this->get('createContactOption'),
    );
    $properties = array(
      'ims' => 'mapperImProvider',
      'phones' => 'mapperPhoneType',
      'websites' => 'mapperWebsiteType',
      'locationTypes' => 'mapperLocType',
      'locations' => 'locations',
    );
    foreach ($properties as $propertyName => $propertyVal) {
      $importJobParams[$propertyVal] = $this->get($propertyName);
    }

    $tableName = $this->get('importTableName');
    $importJob = new CRM_Contribute_Import_ImportJob_Contribution($tableName);
    $importJob->setJobParams($importJobParams);

    // update cache before starting with runImport
    $session = &CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    CRM_ACL_BAO_Cache::updateEntry($userID);

    // run the import
    $importJob->runImport($this);

    // update cache after we done with runImport
    CRM_ACL_BAO_Cache::updateEntry($userID);

    // add all the necessary variables to the form
    $importJob->setFormVariables($this);

    // check if there is any error occured
    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();

    $errorMessage = array();

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      // there is no fileName since this is a sql import
      // so fudge it
      $config = CRM_Core_Config::singleton();
      $errorFile = $config->uploadDir . "sqlImport.error.log";
      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);

      $tableName = $this->get('importTableName');
      $fileName = str_replace('civicrm_import_job_', 'import_', $tableName);
      $urlParams = 'type='. CRM_Contribute_Import_Parser::ERROR . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Contribute_Import_Parser::CONFLICT . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Contribute_Import_Parser::NO_MATCH . '&parser=CRM_Contribute_Import_Parser&file='.$fileName;
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    //do not drop table, leave it to auto purge
    $importJob->isComplete();
  }
}

