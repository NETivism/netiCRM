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
   * @return array
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
    $sql = "SELECT id FROM civicrm_batch WHERE type_id = %1 AND status_id IN (".implode(',', $status).") ORDER BY created_date ASC LIMIT 1";
    $batchId = CRM_Core_DAO::singleValueQuery($sql, array(
      1 => array($type['Auto'], 'Integer'),
    ));

    $message = '';
    if (!empty($batchId)) {
      // check if running currently or running over 1 hour
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

    // after saved start logic, trigger logic to handling before start warehousing
    // do not use start callback to process rows. use process instead.
    if (isset($this->_batch->data['startCallback'])) {
      if (!empty($this->_batch->data['startCallbackArgs'])) {
        $started = call_user_func_array($this->_batch->data['startCallback'], $this->_batch->data['startCallbackArgs']);
      }
      else {
        $started = call_user_func($this->_batch->data['startCallback']);
      }
    }
    if ($started === FALSE) {
      $cancelStatus = self::$_batchStatus['Canceled'];
      $this->_batch->status_id = $cancelStatus;
      $this->saveBatch();
      return FALSE;
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
    global $civicrm_batch;
    $civicrm_batch = $this->_batch;
    // start processing, insert record in db to prevent duplicate running
    $this->dupeInsert();

    // real processing logic 
    if (isset($this->_batch->data['processCallback'])) {
      // TODO - still need a way to calculate processed rows
      if (!empty($this->_batch->data['processCallbackArgs'])) {
        call_user_func_array($this->_batch->data['processCallback'], $this->_batch->data['processCallbackArgs']);
      }
      else {
        call_user_func($this->_batch->data['processCallback']);
      }
    }

    // end processing
    $this->dupeDelete();

    // check batch is finished or not
    if ($this->_batch->data['processed'] >= $this->_batch->data['total'] || $this->_batch->data['isCompleted']) {
      $finishStatus = self::$_batchStatus['Completed'];
      $this->_batch->status_id = $finishStatus;
      $this->saveBatch();
      $this->finish();
    }
    else {
      $this->saveBatch();
    }
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
    // before finish, trigger logic to handling ending of batch
    if (isset($this->_batch->data['finishCallback'])) {
      if (!empty($this->_batch->data['finishCallbackArgs']) && is_array($this->_batch->data['finishCallbackArgs'])) {
        $finished = call_user_func_array($this->_batch->data['finishCallback'], $this->_batch->data['finishCallbackArgs']);
      }
      else {
        $finished = call_user_func($this->_batch->data['finishCallback']);
      }
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
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
    list($toName, $toEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_batch->created_id, FALSE);
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_batch',
      'valueName' => 'batch_complete_notification',
      'contactId' => $this->_batch->created_id,
      'from' => "$domainEmailName <$domainEmailAddress>",
      'toEmail' => "$toName <$toEmail>",
      'tplParams' => array(
        'label' => $this->_batch->label,
        'description' => $this->_batch->description,
        'created_id' => $this->_batch->created_id,
        'created_date' => $this->_batch->created_date,
        'modified_id' => $this->_batch->modified_id,
        'modified_date' => $this->_batch->modified_date,
        'status_id' => $this->_batch->status_id,
      ),
    );
    CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
  }


  /**
   * Duplicate running process check
   * 
   * @return object|bool
   */
  protected function dupeCheck() {
    $dao = new CRM_Core_BAO_Sequence();
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
  protected function dupeInsert() {
    $dao = new CRM_Core_BAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      $dao->timestamp = time();
      $dao->value = $this->_id;
      $dao->save();
    }
    else {
      $dao->timestamp = time();
      $dao->value = $this->_id;
      $dao->save();
    }
    return $dao;
  }

  /**
   * Duplicate running object delete
   * 
   * @return bool
   */
  protected function dupeDelete() {
    $dao = new CRM_Core_BAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      return $dao->delete();
    }
    return FALSE;
  }

  protected function saveBatch() {
    $params = array();
    foreach($this->_batch as $key => $val) {
      $params[$key] = $val;
    }
    $params['id'] = $this->_id;
    $batch = self::create($params);
    $this->_batch = $batch;
  }
}
