<?php
class CRM_Contribute_Import_ImportJob_Contribution extends CRM_Import_ImportJob {

  protected $_mapperSoftCredit;
  protected $_mapperPCP;

  public function __construct($tableName = NULL, $createSql = NULL, $createTable = FALSE) {
    parent::__construct($tableName);

    //initialize the properties.
    $properties = array(
      'mapperSoftCredit',
      'mapperPCP',
      'mapperLocTypes',
      'mapperPhoneTypes',
      'mapperImProviders',
      'mapperWebsiteTypes',
    );
    foreach ($properties as $property) {
      $this->{"_$property"} = array();
    }
  }

  public function runImport(&$form) {
    global $civicrm_batch;
    $allArgs = func_get_args();
    $lock = NULL;
    if (empty($civicrm_batch)) {
      if ($this->_totalRowCount > CRM_Import_ImportJob::BATCH_THRESHOLD) {
        $fileName = str_replace('civicrm_import_job_', '', $this->_tableName);
        $fileName = 'import_contribution_'.$fileName.'.zip';
        $config = CRM_Core_Config::singleton();
        $file = $config->uploadDir.$fileName;
        $batchParams = array(
          'label' => ts('Import Contributions'),
          'startCallback' => array($this, 'batchStartCallback'),
          'startCallbackArgs' => NULL,
          'processCallback' => array($this, __FUNCTION__),
          'processCallbackArgs' => $allArgs,
          'finishCallback' => array($this, 'batchFinishCallback'),
          'finishCallbackArgs' => NULL,
          'download' => array(
            'header' => array(
              'Content-Type: application/zip',
              'Content-Transfer-Encoding: Binary',
              'Content-Disposition: attachment;filename="'.$fileName.'"',
            ),
            'file' => $file,
          ),
          'actionPermission' => '',
          'total' => $this->_totalRowCount,
          'processed' => 0,
        );
        $batch = new CRM_Batch_BAO_Batch();
        $batch->start($batchParams);

        // redirect to notice page
        CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
      }
      else {
        // not batch process, acuire lock
        $lock = new CRM_Core_Lock($this->_tableName);
        if (!$lock->isAcquired()) {
          CRM_Core_Error::statusBounce(ts("The selected import job is already running. To prevent duplicate records being imported, please wait the job complete."));
          CRM_Core_Error::debug_log_message("Trying acquire lock $this->_tableName failed at line ".__LINE__);
        }
      }
    }
    else {
      // unserialized batch object need re-init controller
      $this->prepareSessionObject($form);
    }

    $mapper = $this->_mapper;
    $mapperFields = array();
    $mapperSoftCredit = array();
    $mapperPCP = array();
    foreach ($mapper as $key => $value) {
      $mapperFields[$key] = $mapper[$key][0];
      if (isset($mapper[$key][0]) && $mapper[$key][0] == 'soft_credit') {
        $mapperSoftCredit[$key] = $mapper[$key][1];
      }
      elseif (isset($mapper[$key][0]) && $mapper[$key][0] == 'pcp_creator') {
        $mapperPCP[$key] = $mapper[$key][1];
      }
      else {
        $mapperSoftCredit[$key] = NULL;
        $mapperPCP[$key] = NULL;
      }
    }

    $this->_parser = new CRM_Contribute_Import_Parser_Contribution($mapperFields, $mapperSoftCredit, $this->_mapperLocType, $this->_mapperPhoneType, $this->_mapperWebsiteType, $this->_mapperImProvider, $mapperPCP);
    if (!empty($this->_dedupeRuleGroupId)) {
      $this->_parser->_dedupeRuleGroupId = $this->_dedupeRuleGroupId;
    }

    $this->_parser->_job = $this;

    // set max process lines per batch
    if ($civicrm_batch) {
      $this->_parser->setMaxLinesToProcess(CRM_Import_ImportJob::BATCH_LIMIT);
    }
    $this->_parser->_skipColumnHeader = $form->get('skipColumnHeader');
    $this->_parser->_dateFormats = $form->get('dateFormats');
    $this->_parser->run(
      $this->_tableName,
      $mapperFields,
      CRM_Contribute_Import_Parser::MODE_IMPORT,
      $this->_contactType,
      $this->_primaryKeyName,
      $this->_statusFieldName,
      $this->_onDuplicate,
      $this->_statusID, 
      $this->_totalRowCount,
      $this->_createContactOption,
      $this->_dedupeRuleGroupId
    );
    $this->_parser->set($form, CRM_Contribute_Import_Parser::MODE_IMPORT);
    $processedRowCount = $form->get('rowCount');
    if (!empty($civicrm_batch)) {
      if ($processedRowCount > 0) {
        $civicrm_batch->data['processed'] += $processedRowCount;
      }
      else {
        // when no pending records to process, finish this job.
        $query = "SELECT * FROM $this->_tableName WHERE $this->_statusFieldName = 'NEW'";
        $dao = CRM_Core_DAO::executeQuery($query);
        if (!$dao->N && $civicrm_batch->data['processed'] > 0) {
          $civicrm_batch->data['processed'] = $civicrm_batch->data['total'];
        }
      }
    }
  }

  public function prepareSessionObject(&$form) {
    $form->controller->initTemplate();
    $form->controller->initSession();
    $name = $form->controller->_name;
    $scope = CRM_Utils_System::getClassName($form->controller);
    $scope .= '_'.$form->controller->_key;
    CRM_Core_Session::registerAndRetrieveSessionObjects(array("_{$name}_container", array('CiviCRM', $scope)));
  }

  public function batchStartCallback() {
    global $civicrm_batch;
    if ($civicrm_batch) {
      $query = "SELECT COUNT(*) FROM $this->_tableName WHERE $this->_statusFieldName != %1";
      $processed = CRM_Core_DAO::singleValueQuery($query, array(
        1 => array(CRM_Import_Parser::PENDING, 'Integer')
      ));
      $civicrm_batch->data['processed'] += $processed;
    }
  }

  public function batchFinishCallback() {
    global $civicrm_batch;
    if (!empty($civicrm_batch)) {
      // calculate import results from table
      $query = "SELECT $this->_statusFieldName as status, COUNT(*) as count FROM $this->_tableName WHERE 1 GROUP BY $this->_statusFieldName";
      $dao = CRM_Core_DAO::executeQuery($query);
      $statusCount = array();
      while($dao->fetch()) {
        $name = CRM_Import_Parser::statusName($dao->status);
        $statusCount[$name] = $dao->count;
      }
      $name = CRM_Import_Parser::statusName(CRM_Import_Parser::VALID);
      if (!isset($statusCount[$name])) {
        $statusCount[$name] = 0;
      }
      $civicrm_batch->data['statusCount'] = $statusCount;

      // zip error files from table
      $zipFile = $civicrm_batch->data['download']['file'];
      $zip = new ZipArchive();

      if ($zip->open($zipFile, ZipArchive::CREATE) == TRUE) {
        $config = CRM_Core_Config::singleton();
        $fileName = str_replace('civicrm_import_job_', 'import_', $this->_tableName);
        $errorFiles = array();
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::ERROR, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::CONFLICT, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::DUPLICATE, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::NO_MATCH, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::UNPARSED_ADDRESS_WARNING, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR, $fileName);
        $errorFiles[] = CRM_Contribute_Import_Parser::saveFileName(CRM_Contribute_Import_Parser::PCP_ERROR, $fileName);
        foreach($errorFiles as $idx => $fileName) {
          $filePath = $config->uploadDir.$fileName;
          if (is_file($filePath)) {
            $zip->addFile($filePath, $fileName);
          }
          else {
            unset($errorFiles[$idx]);
          }
        }
        $zip->close();

        // purge zipped files
        foreach($errorFiles as $fileName) {
          unlink($config->uploadDir.$fileName);
        }
      }
    }
  }
}