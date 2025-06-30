<?php
/**
 * Batch BAO class.
 */
class CRM_Batch_BAO_Batch extends CRM_Batch_DAO_Batch {

  /**
   * queue name
   */
  const QUEUE_NAME = 'batch_auto';

  /**
   * expire day
   */
  const EXPIRE_DAY = 8;

  /**
   * stuck expire hour
   */
  const EXPIRE_HOUR = 4;

  /**
   * Batch id to load
   * @var int
   */
  public $_id = NULL;

  /**
   * Cache for the current batch object.
   * @var object
   */
  public $_batch = NULL;

  /**
   * Status of batch
   * @var array
   */
  public static $_batchStatus = array();

  /**
   * Type of batch
   * @var array
   */
  public static $_batchType = array();


  /**
   * Create a new batch.
   *
   * @param array $params
   *
   * @return object
   *   $batch batch object
   */
  public static function create(&$params) {
    $op = 'edit';
    $batchId = CRM_Utils_Array::value('id', $params);
    if (!$batchId) {
      $op = 'create';
      if (empty($params['name'])) {
        $params['name'] = CRM_Utils_String::titleToVar($params['title']);
      }
    }
    CRM_Utils_Hook::pre($op, 'Batch', $batchId, $params);
    $batch = new CRM_Batch_DAO_Batch();
    if (!empty($params['data'])) {
      if (is_array($params['data'])) {
        $params['data'] = serialize($params['data']);
      }
    }
    $batch->copyValues($params);
    $batch->save();

    CRM_Utils_Hook::post($op, 'Batch', $batch->id, $batch);

    $params = array(
      'id' => $batch->id,
    );
    $defaults = array();
    $batch = self::retrieve($params, $defaults);
    return $batch;
  }

  /**
   * Retrieve the information about the batch.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return object
   *   CRM_Batch_BAO_Batch object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $batch = new CRM_Batch_DAO_Batch();
    $batch->copyValues($params);
    if ($batch->find(TRUE)) {
      CRM_Core_DAO::storeValues($batch, $defaults);
      if (!empty($batch->data)) {
        $batch->data = unserialize($batch->data);
        $defaults['data'] = $batch->data;
      }
      return $batch;
    }
    return NULL;
  }

  /**
   * Function get batch statuses.
   *
   * @return array
   *   array of statuses 
   */
  public static function batchStatus() {
    self::$_batchStatus = CRM_Core_OptionGroup::values('batch_status', TRUE, FALSE, FALSE, NULL, 'name');
    return self::$_batchStatus;
  }

  /**
   * Function get batch types.
   *
   * @return array
   *   array of batches
   */
  public static function batchType() {
    self::$_batchType = CRM_Core_OptionGroup::values('batch_type', TRUE, FALSE, FALSE, NULL, 'name');
    return self::$_batchType;
  }

  /**
   * Run last queuing batching
   *
   * @return string
   *   message that indicate current running status
   */
  public static function runQueue() {
    $type = self::batchType();
    $status = self::batchStatus();
    unset($status['Completed']);
    unset($status['Canceled']);
    $sql = "SELECT id FROM civicrm_batch WHERE type_id = %1 AND status_id IN (".CRM_Utils_Array::implode(',', $status).") ORDER BY created_date ASC LIMIT 1";
    $batchId = CRM_Core_DAO::singleValueQuery($sql, array(
      1 => array($type['Auto'], 'Integer'),
    ));

    $message = '';
    if (!empty($batchId)) {
      // check if running currently or running over 1 hour
      // delay start to prevent cron duplicate running
      sleep(mt_rand(5, 15));
      $batch = new CRM_Batch_BAO_Batch($batchId);
      $running = $batch->dupeCheck();
      if (!empty($running) && $running->value) {
        if (CRM_REQUEST_TIME - $running->timestamp > 3600) {
          if ($batch->dupeDelete()) {
            $message = ts('Found batch job number %1 running over 1 hour. We delete this job then start another batch job number %2.', array(1 => $running->value, 2 => $batch->_id));
            CRM_Core_Error::debug_log_message($message);
            // start another process
            $batch->process();
          }
        }
        else {
          $message = ts('We still have running batch job in queue recently.');
          CRM_Core_Error::debug_log_message($message);
        }
      }
      else {
        if (empty($batch->_batch->data)) {
          $batch->finish();
        }
        else {
          $batch->process();
        }
        $message = ts('Success processing queuing batch.');
      }
    }
    return $message;
  }

  /**
   * Expire batches over expire days
   * This will delete data column and purge download file to prevent db growing
   *
   * @return string
   *   message that indicate expires
   */
  public static function expireBatch() {
    $type = self::batchType();
    $status = self::batchStatus();
    unset($status['Running']);
    unset($status['Pending']);
    $purgeDay = self::EXPIRE_DAY*4;
    $sql = "SELECT id FROM civicrm_batch WHERE type_id = %1 AND status_id IN (".CRM_Utils_Array::implode(',', $status).") AND DATE_ADD(modified_date, INTERVAL ".$purgeDay." DAY) < NOW() AND modified_date IS NOT NULL AND data IS NOT NULL ORDER BY modified_date ASC";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array($type['Auto'], 'Integer'),
    ));
    $expires = array();
    while($dao->fetch()) {
      $params = array(
        'id' => $dao->id,
      );
      $defaults = array();
      $batch = CRM_Batch_BAO_Batch::retrieve($params, $defaults);
      if ($batch->id) {
        if (isset($batch->data['download']['file']) && file_exists($batch->data['download']['file'])) {
          @unlink($batch->data['download']['file']);
          $expires[] = $dao->id;
        }
        CRM_Core_DAO::executeQuery("UPDATE civicrm_batch SET data = NULL WHERE id = %1", array(1 => array($dao->id, 'Integer')));
      }
      // refs #41959, free memory of batch result to prevent memory leak
      $batch->free();
      unset($batch);
    }
    if (count($expires)) {
      $msg = 'Batch ids in '.CRM_Utils_Array::implode(",", $expires).' has been expires';
      CRM_Core_Error::debug_log_message($msg);
      return $msg;
    }
    return '';
  }

  /**
   * Auto remove stuck batch
   *
   * @return null
   */
  public static function cancelStuckBatch() {
    $type = self::batchType();
    $status = self::batchStatus();
    $statusRunning = $status['Running'];
    $statusCanceled = $status['Canceled'];

    $sql = "SELECT id, data, modified_date, description FROM civicrm_batch WHERE type_id = %1 AND status_id = %2 ORDER BY created_date ASC LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array($type['Auto'], 'Integer'),
      2 => array($statusRunning, 'Integer'),
    ));
    $dao->fetch();
    if (!empty($dao->id)) {
      if ($dao->data) {
        $meta = unserialize($dao->data);
        // after 4 hours without any progress, cancel it
        if (is_array($meta) && empty($meta['processed']) && !empty($dao->modified_date)) {
          $lastSuccessTime = strtotime($dao->modified_date);
          if (CRM_REQUEST_TIME - $lastSuccessTime > 3600 * self::EXPIRE_HOUR) {
            CRM_Core_Error::debug_log_message("Canceled running batch id {$dao->id} due to zero progress over ".self::EXPIRE_HOUR." hours.");
            CRM_Core_DAO::executeQuery("UPDATE civicrm_batch SET status_id = %1, description = %2 WHERE id = %3", array(
              1 => array($statusCanceled, 'Integer'),
              2 => array(ts('Batch running failed. Contact the site administrator for assistance.'), 'String'),
              3 => array($dao->id, 'Integer'),
            ));
          }
        }
        elseif(!empty($meta['processed'])){
          if (!empty($dao->description)) {
            $processHistories = explode(':', $dao->description);
          }
          else {
            $processHistories = array();
          }
          $stuck = 0;
          foreach($processHistories as $lastProcessed) {
            if ((int)$meta['processed'] == (int)$lastProcessed) {
              $stuck++;
            }
          }
          if ($stuck <= self::EXPIRE_HOUR) {
            array_unshift($processHistories, $meta['processed']);
            $processHistories = array_slice($processHistories, 0, self::EXPIRE_HOUR+2);
            CRM_Core_DAO::executeQuery("UPDATE civicrm_batch SET description = %1 WHERE id = %2", array(
              1 => array(implode(':', $processHistories), 'String'),
              2 => array($dao->id, 'Integer'),
            ));
          }
          else {
            // no progress after 4 times(have same processed records), cancel it
            CRM_Core_Error::debug_log_message("Canceled running batch id {$dao->id} due to stuck in progress {$meta['processed']} for {$stuck} times.");
            CRM_Core_DAO::executeQuery("UPDATE civicrm_batch SET status_id = %1, description = %2 WHERE id = %3", array(
              1 => array($statusCanceled, 'Integer'),
              2 => array(ts('Batch running failed. Contact the site administrator for assistance.').' ('.$dao->description.')', 'String'),
              3 => array($dao->id, 'Integer'),
            ));
          }
        }
      }
    }
  }

  /**
   * Constructor
   * 
   * @param int
   *   batch id to load whole batch object
   * 
   * @return object
   */
  function __construct($batchId = NULL) {
    self::batchType();
    self::batchStatus();
    if ($batchId) {
      $this->_id = $batchId;
      $params = array('id' => $this->_id);
      $defaults = array();
      $this->_batch = self::retrieve($params, $defaults);
    }
  }

  /**
   * Create and start a batch process 
   * 
   * @param array
   *   information that batch process needed.
   * 
   * @return object
   *   batch object that just insert into db
   */
  public function start($arguments) {
    // check if we have running job currently
    $runningStatus = self::$_batchStatus['Running'];
    $runningBatch = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $runningStatus, 'id', 'status_id', TRUE);
    if ($runningBatch) {
      $statusId = self::$_batchStatus['Pending'];
    }
    else {
      $statusId = $runningStatus;
    }
    $session = CRM_Core_Session::singleton();
    $currentContact = $session->get('userID');
    $params = array(
      'name' => 'batch-'.date('YmdHis').'.'.mt_rand(1,100),
      'label' => $arguments['label'],
      'description' => $arguments['description'],
      'created_id' => $currentContact,
      'created_date' => date('Y-m-d H:i:s'),
      'modified_id' => 'null',
      'modified_date' => 'null',
      'status_id' => $statusId,
      'type_id' => self::$_batchType['Auto'],
      'data' => $arguments,
    );
    $batch = self::create($params);
    $this->_batch = $batch;
    $this->_id = $batch->id;

    global $civicrm_batch;
    $civicrm_batch = $this->_batch;

    // after saved start logic, trigger logic to handling before start warehousing
    // do not use start callback to process rows. use process instead.
    if (isset($this->_batch->data['startCallback'])) {
      $args = array();
      if (!empty($this->_batch->data['startCallbackArgs'])) {
        foreach($this->_batch->data['startCallbackArgs'] as $idx => &$arg) {
          $args[$idx] = &$arg;
        }
      }
      $started = call_user_func_array($this->_batch->data['startCallback'], $args);
    }
    if ($started === FALSE) {
      $cancelStatus = self::$_batchStatus['Canceled'];
      $this->_batch->status_id = $cancelStatus;
      $this->saveBatch();
      return FALSE;
    }
    else {
      $this->saveBatch();
    }
    return $this->_batch;
  }

  /**
   * Process part of batch each run
   * 
   * @param int   $batchId
   *   an id of batch process to load 
   * 
   * @return null
   */
  public function process($force = FALSE) {
    // start processing, insert record in db to prevent duplicate running
    $this->dupeInsert();

    // set current logged user as batch creator
    $session = CRM_Core_Session::singleton();
    $session->set('userID', $this->_batch->created_id);

    global $civicrm_batch;
    $civicrm_batch = $this->_batch;

    // real processing logic 
    if (isset($this->_batch->data['processCallback'])) {
      $args = array();
      if (!empty($this->_batch->data['processCallbackArgs'])) {
        foreach($this->_batch->data['processCallbackArgs'] as $idx => &$arg) {
          $args[$idx] = &$arg;
        }
      }
      call_user_func_array($this->_batch->data['processCallback'], $args);
    }

    // check batch is finished or not
    if ($this->_batch->data['processed'] >= $this->_batch->data['total'] || $this->_batch->data['isCompleted']) {
      $finishStatus = self::$_batchStatus['Completed'];
      $this->_batch->status_id = $finishStatus;
      $this->saveBatch();
      $this->finish();
    }
    else {
      if (!empty($this->_batch->data['processed'])) {
        $this->_batch->modified_date = date('YmdHis');
      }
      $this->saveBatch();
    }

    // end processing
    $this->dupeDelete();
  }

  /**
   * Finish batch
   * 
   * @param int   $batchId
   *   an id of batch process to load 
   * 
   * @return null
   */
  public function finish() {
    global $civicrm_batch;
    $civicrm_batch = $this->_batch;
    // before finish, trigger logic to handling ending of batch
    if (isset($this->_batch->data['finishCallback'])) {
      $args = array();
      if (!empty($this->_batch->data['finishCallbackArgs'])) {
        foreach($this->_batch->data['finishCallbackArgs'] as $idx => &$arg) {
          $args[$idx] = &$arg;
        }
      }
      $finished = call_user_func_array($this->_batch->data['finishCallback'], $args);
    }

    // after finish, don't forget to delete job, and change status of batch
    $this->dupeDelete();
    if ($finished === FALSE) {
      return;
    }

    $completeStatus = self::$_batchStatus['Completed'];
    $this->_batch->modified_id = $this->_batch->created_id;
    $this->_batch->modified_date = date('YmdHis');
    $this->_batch->status_id = $completeStatus;
    $this->saveBatch();

    // notify author of this batch by email
    if (!empty($this->_batch->created_id)) {
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
      list($toName, $toEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_batch->created_id, FALSE);

      $detail = CRM_Contact_BAO_Contact::getContactDetails($this->_batch->created_id);
      if (!empty($detail[5])) {
        CRM_Core_Error::debug_log_message("Skipped email notify pcp_notify for contact {$this->_batch->created_id} due to do_not_notify marked");
        $message = ts('Email has NOT been sent to %1 contact(s) - communication preferences specify DO NOT NOTIFY OR valid Email is NOT present.', array(1 => '1'));
        CRM_Core_Session::singleton()->setStatus($message);
        return;
      }
      if (!empty($toEmail)) {
        $sendTemplateParams = array(
          'groupName' => 'msg_tpl_workflow_meta',
          'valueName' => 'batch_complete_notification',
          'contactId' => $this->_batch->created_id,
          'from' => "$domainEmailName <$domainEmailAddress>",
          'toName' => $toName,
          'toEmail' => $toEmail,
          'tplParams' => array(
            'batch_id' => $this->_id,
            'label' => $this->_batch->label,
            'description' => $this->_batch->description,
            'created_id' => $this->_batch->created_id,
            'created_date' => $this->_batch->created_date,
            'modified_id' => $this->_batch->modified_id,
            'modified_date' => $this->_batch->modified_date,
            'expire_date' => date('Y-m-d H:i:s', strtotime($this->_batch->modified_date) + 86400*self::EXPIRE_DAY),
            'status_id' => $this->_batch->status_id,
          ),
        );
        if ($this->_batch->data['total']) {
          $sendTemplateParams['tplParams']['total'] = $this->_batch->data['total'];
        }
        CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
      }
    }
  }


  /**
   * Duplicate running process check
   * 
   * @return object|bool
   */
  public function dupeCheck() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      return $dao;
    }
    return FALSE;
  }

  /**
   * Duplicate running object insert
   * 
   * @return object
   */
  public function dupeInsert() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      $dao->timestamp = microtime(true);
      $dao->value = $this->_id;
      $dao->update();
    }
    else {
      $dao->timestamp = microtime(true);
      $dao->value = $this->_id;
      $dao->insert();
    }
    return $dao;
  }

  /**
   * Duplicate running object delete
   * 
   * @return bool
   */
  public function dupeDelete() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find()) {
      return $dao->delete();
    }
    return FALSE;
  }

  public function saveBatch() {
    $params = array();
    foreach($this->_batch as $key => $val) {
      $params[$key] = $val;
    }
    $params['id'] = $this->_id;
    $batch = self::create($params);
    $this->_batch = $batch;
  }
}
