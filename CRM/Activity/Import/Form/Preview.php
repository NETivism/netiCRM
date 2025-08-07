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
class CRM_Activity_Import_Form_Preview extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');

    //get the data from the session
    $dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
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
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }

    $prefix = $this->get('errorFilenamePrefix');
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);

    CRM_Import_Parser::setImportErrorFilenames($qfKey, ['error', 'conflict','no_match'], 'CRM_Activity_Import_Parser', $prefix, $this);

    $properties = ['mapper',
      'dataValues', 'columnCount',
      'totalRowCount', 'validRowCount',
      'invalidRowCount', 'conflictRowCount',
      'downloadErrorRecordsUrl',
      'downloadConflictRecordsUrl',
      'downloadMismatchRecordsUrl',
    ];

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $attr = [];
    $locked = CRM_Core_Lock::isUsed($this->controller->_key);
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
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
          'js' => $attr,
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
    if (PHP_SAPI != 'cli') {
      set_time_limit(1800);
    }
    $fileName = $this->controller->exportValue('UploadFile', 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue('UploadFile', 'skipColumnHeader');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $onDuplicate = $this->get('onDuplicate');

    $config = CRM_Core_Config::singleton();
    $seperator = $config->fieldSeparator;

    $mapper = $this->get('mapperKeys');
    $mapperKeys = [];
    $mapperLocType = [];
    $mapperPhoneType = [];

    foreach ($mapper as $key => $value) {
      $mapperKeys[$key] = $mapper[$key][0];

      if (is_numeric($mapper[$key][1])) {
        $mapperLocType[$key] = $mapper[$key][1];
      }
      else {
        $mapperLocType[$key] = NULL;
      }

      if (!is_numeric($mapper[$key][2])) {
        $mapperPhoneType[$key] = $mapper[$key][2];
      }
      else {
        $mapperPhoneType[$key] = NULL;
      }
    }

    $parser = new CRM_Activity_Import_Parser_Activity($mapperKeys, $mapperLocType, $mapperPhoneType);

    $mapFields = $this->get('fields');

    foreach ($mapper as $key => $value) {
      $header = [];
      if (isset($mapFields[$mapper[$key][0]])) {
        $header[] = $mapFields[$mapper[$key][0]];
      }
      $mapperFields[] = CRM_Utils_Array::implode(' - ', $header);
    }

    $lock = new CRM_Core_Lock($this->controller->_key);
    if (!$lock->isAcquired()) {
      CRM_Core_Error::statusBounce(ts("The selected import job is already running. To prevent duplicate records being imported, please wait the job complete."));
      CRM_Core_Error::debug_log_message("Trying acquire lock {$this->controller->_key} failed at line ".__LINE__);
    }


    $errorFilenamePrefix = CRM_Activity_Import_Parser::ERROR_FILE_PREFIX.'_'.date('YmdHis', CRM_REQUEST_TIME);
    $this->set('errorFilenamePrefix', $errorFilenamePrefix);
    $parser->run($fileName, $seperator,
      $mapperFields,
      $skipColumnHeader,
      CRM_Activity_Import_Parser::MODE_IMPORT,
      $onDuplicate,
      $errorFilenamePrefix
    );

    // add all the necessary variables to the form
    $parser->set($this, CRM_Activity_Import_Parser::MODE_IMPORT);

    // check if there is any error occured

    $errorStack = &CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();

    $errorMessage = [];

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      $errorFile = $fileName['name'] . '.error.log';

      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, CRM_Utils_Array::implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);
    }
  }
}

