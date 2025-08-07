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
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Contribute_Import_Form_Preview extends CRM_Core_Form {

  public $_dataValues;
  public $_columnHeaders;
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
    $prefix = str_replace(CRM_Import_ImportJob::TABLE_PREFIX, CRM_Contribute_Import_Parser::ERROR_FILE_PREFIX, $tableName);
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);

    CRM_Import_Parser::setImportErrorFilenames($qfKey, ['error', 'conflict','no_match'], 'CRM_Contribute_Import_Parser', $prefix, $this);

    $properties = [
      'mapper', 'softCreditFields', 'pcpCreatorFields', 'columnCount',
      'totalRowCount', 'validRowCount',
      'invalidRowCount', 'conflictRowCount',
      'downloadErrorRecordsUrl',
      'downloadConflictRecordsUrl',
      'downloadMismatchRecordsUrl',
    ];

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }

    $statusID = $this->get('statusID');
    if (!$statusID) {
      $statusID = md5(uniqid((string)rand(), TRUE));
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
    $attr = ['onclick' => "return verify();"];
    $locked = CRM_Core_Lock::isUsed($this->get('importTableName'));
    if ($locked) {
      $attr['disabled'] = 'disabled';
      $this->assign('locked_import', TRUE);
    }
    $this->addButtons([
        ['type' => 'back',
          'name' => ts('<< Previous'),
        ],
        ['type' => 'next',
          'name' => ts('Import Now >>'),
          'js' => $attr,
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
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

    $importJobParams = [
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
      'skipColumnHeader' => $this->get('skipColumnHeader'),
      'dateFormats' => $this->get('dateFormats'),
    ];
    $properties = [
      'ims' => 'mapperImProvider',
      'phones' => 'mapperPhoneType',
      'websites' => 'mapperWebsiteType',
      'locationTypes' => 'mapperLocType',
      'locations' => 'locations',
    ];
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

    $errorMessage = [];

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      // there is no fileName since this is a sql import
      // so fudge it
      $config = CRM_Core_Config::singleton();
      $errorFile = $config->uploadDir . "sqlImport.error.log";
      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, CRM_Utils_Array::implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);
    }

    //do not drop table, leave it to auto purge
    $importJob->isComplete();
  }
}

