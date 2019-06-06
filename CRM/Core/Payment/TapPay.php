<?php

class CRM_Core_Payment_TapPay extends CRM_Core_Payment {
  
  protected $_mode = NULL;

  protected $_api = NULL;

  protected $_apiType = NULL;

  // Used for contribution recurring form ( /CRM/Contribute/Form/ContributionRecur.php ).
  public static $_editableFields = array('amount', 'installments', 'end_date', 'cycle_day', 'contribution_status_id');

  public static $_cardType = array(
    1 => 'VISA',
    2 => 'MasterCard',
    3 => 'JCB',
    4 => 'Union Pay',
    5 => 'AMEX',
  );

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_apiType = $apiType;
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  public static function &singleton($mode, &$paymentProcessor, $apiType = '') {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TapPay($mode, $paymentProcessor, $apiType);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (!empty($this->_paymentProcessor['user_name']) xor !empty($this->_paymentProcessor['password'])) {
      $error[] = ts('User Name is not set in the Administer CiviCRM &raquo; Payment Processor.');
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }


    if (!empty($error)) {
      return implode('<br>', $error);
    }
    else {
      return NULL;
    }
  }

  function setExpressCheckOut(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getExpressCheckoutDetails($token) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doExpressCheckout(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  function getPaymentFrame() {
    if (!empty($this->_paymentProcessor)) {
      $this->_paymentForm->add('hidden', 'prime', '');
      $this->_paymentForm->assign('button_name', $this->_paymentForm->getButtonName('next'));
      $this->_paymentForm->assign('payment_processor', $this->_paymentProcessor);
      $className = get_class($this->_paymentForm);
      $qfKey = $this->_paymentForm->get('qfKey');
      // needs payment processor keys
      // we needs these to process payByPrime
      $this->_paymentForm->assign('contribution_id', $payment['contributionID']);
      $this->_paymentForm->assign('class_name', $className);
      $this->_paymentForm->assign('qfKey', $qfKey);

      // get template and render some element
      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
      $config = CRM_Core_Config::singleton();
      $tplFile = $config->templateDir[0].'CRM/Core/Page/Payment/TapPay.tpl';
      $tplContent = 'string:'.file_get_contents($tplFile);
      $smarty = CRM_Core_Smarty::singleton();
      $html = $smarty->fetch($tplContent);
      return $html;
    }
  }

  function doTransferCheckout(&$params, $component) {
    $currentPath = CRM_Utils_System::currentPath();
    $params['prime'] = CRM_Utils_Type::escape($_POST['prime'], 'String');
    $params['mode'] = $this->_mode;
    $paymentResult = self::payByPrime($params);
    if ($paymentResult['status'] == 0) {
      $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey'].'&payment_result_type=1');
    }
    else {
      $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey'].'&payment_result_type=4&payment_result_message='.$paymentResult['msg']);
    }
    CRM_Utils_System::redirect($thankyou);
  }

  public static function payByPrime($payment) {
    if ($payment && !empty($payment['payment_processor_id'])) {
      $trxn_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id');
      if(empty($trxn_id)){
        $trxn_id = self::getContributionTrxnID($payment['contributionID']);
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $payment['contributionID'], 'trxn_id', $trxn_id);
      }

      $contribution = $ids = array();
      $params = array('id' => $payment['contributionID']);
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($payment['payment_processor_id'], $payment['mode']);
      $prime = $payment['prime'];
      $tappayParams = array(
        'apiType' => 'pay_by_prime',
        'partnerKey' => $paymentProcessor['password'],
      );
      $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      $data = array(
        'prime' => $prime,
        'partner_key' => $paymentProcessor['password'],
        'merchant_id' => $paymentProcessor['user_name'],
        'amount' => $contribution['currency'] == 'TWD' ? (int)$contribution['total_amount'] : $contribution['total_amount'],
        'currency' => $contribution['currency'],
        'order_number' => $contribution['trxn_id'],
        'details' => $details, // item name
        'cardholder'=> array(
          'phone_number'=> '', #required #TODO
          'name' => $sortName, # required
          'email' => $email, #required
          'zip_code' => '',    //optional
          'address' => '',     //optional
          'national_id' => '', //optional
        ),
        'remember' => TRUE,
        'contribution_id' => $id,
      );

      // Allow further manipulation of the arguments via custom hooks ..
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = self::singleton($mode, $paymentProcessor, $tappayParams['apiType']);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      $result = $api->request($data);
      self::doTransaction($result, $payment['contributionID']);

      // update token status after transaction completed
      if ($result->status === 0 && !empty($contribution['contribution_recur_id'])) {
        self::cardMetadata($payment['contributionID']); 
      }

      $response = array('status' => $result->status, 'msg' => $result->msg);
      return $response;
    }
    return FALSE;
  }

  public static function payByToken($recurringId = NULL, $contributionId = NULL, $sendMail = TRUE) {
    if(empty($recurringId)){
      $recurringId = CRM_Utils_Request::retrieve('crid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, $recurringId, 'REQUEST');
    }

    $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
    $contributionRecur->id = $recurringId;
    $contributionRecur->find(TRUE);

    if (empty($contributionId)) {
      // Find the first contribution
      $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, $contributionId, 'REQUEST');
      if(empty($contributionId)){
        $sql = "SELECT MIN(c.id) FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1";
        $params = array(1 => array($recurringId, 'Positive'));
        $contributionId = CRM_Core_DAO::singleValueQuery($sql, $params);
      }
    }

    // Clone Contribution
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);

    $c = clone $contribution;
    unset($c->id);
    unset($c->receive_date);
    unset($c->cancel_date);
    unset($c->cancel_reason);
    unset($c->invoice_id);
    unset($c->receipt_id);
    unset($c->trxn_id);
    $c->contribution_status_id = 2;
    $c->created_date = date('YmdHis');
    $c->total_amount = $contributionRecur->amount;
    $c->save();
    CRM_Contribute_BAO_ContributionRecur::syncContribute($recurringId, $c->id);

    // Update new trxn_id
    $c->trxn_id = self::getContributionTrxnID($c->id);
    $c->save();

    $ppid = $contribution->payment_processor_id;
    $mode = $contribution->is_test ? 'test' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    if ($paymentProcessor) {
      //Prepare tappay api
      $contribution = $ids = array();
      $params = array('id' => $c->id);
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $tappayParams = array(
        'apiType' => 'pay_by_token',
        'partnerKey' => $paymentProcessor['password'],
      );
      $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

      // Prepare tappay api post data
      $details = !empty($contribution['amount_level']) ? $contribution['source'].'-'.$contribution['amount_level'] : $contribution['source'];
      $tappayData = new CRM_Contribute_DAO_TapPay();
      $tappayData->contribution_id = $contributionId;
      $tappayData->find(TRUE);
      if (!empty($tappayData->card_key) && !empty($tappayData->card_token)) {
        if ($contributionRecur->currency == 'TWD') {
          $amount = (int)$contributionRecur->amount;
        }
        else {
          $amount = (float)$contributionRecur->amount;
        }
        $data = array(
          'card_key' => $tappayData->card_key,
          'card_token' => $tappayData->card_token,
          'partner_key' => $paymentProcessor['password'],
          'merchant_id' => $paymentProcessor['user_name'],
          'amount' => $amount,
          'currency' => $contributionRecur->currency,
          'order_number' => $contribution['trxn_id'],
          'details' => $details, // item name
        );
      }
      else {
        CRM_Core_Error::fatal(ts('Missing required fields').': card_key, card_token');
      }

      // Allow further manipulation of the arguments via custom hooks ..
      $paymentClass = self::singleton($mode, $paymentProcessor, $tappayParams['apiType']);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      // Send tappay pay_by_token post
      $result = $api->request($data);

      // Validate the result.
      self::doTransaction($result, $c->id, $sendMail);

      $response = array('status' => $result->status, 'msg' => $result->msg);
    }
    return $response;
  }

  public static function cardMetadata($contributionId, $data = NULL) {
    if (empty($contributionId))  {
      return FALSE;
    }
    $tappayData = new CRM_Contribute_DAO_TapPay();
    $tappayData->contribution_id = $contributionId;
    if ($tappayData->find(TRUE)) {
      // 1. check card_token and card_key
      // 2. get payment processor infomation (partner key)
      if (!empty($tappayData->card_token) && !empty($tappayData->card_key)) {
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->id = $contributionId;
        if($contribution->find(TRUE)) {
          $ppid = $contribution->payment_processor_id;
          $mode = $contribution->is_test ? 'test' : 'live';
          if ($ppid) {
            $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);
            $tappayParams = array(
              'apiType' => 'card_metadata',
              'partnerKey' => $paymentProcessor['password'],
              'contribution_id' => $contributionId,
            );
            $api = new CRM_Core_Payment_TapPayAPI($tappayParams);
            if (empty($data)) {
              $result = $api->request(array(
                'partner_key' => $paymentProcessor['password'],
                'card_key' => $tappayData->card_key,
                'card_token' => $tappayData->card_token,
              ));
            }
            else {
              $result = $data;
            }

            // only set auto renew when contribution has recurring
            if ($result->status == 0 && $contribution->contribution_recur_id) {
              $cardStatus = $result->card_info->token_status;
              if (!empty($cardStatus) && ($cardStatus == 'ACTIVE' || $cardStatus == 'SUSPENDED')) {
                CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $contribution->contribution_recur_id, 'auto_renew', 1);
              }
              else {
                CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $contribution->contribution_recur_id, 'auto_renew', 0);
              }
            }
            return $result;
          }
        }
      } 
    }
    return FALSE;  
  }

  public static function doTransaction($result, $contributionId = NULL, $sendMail = TRUE) {
    $input = $ids = $objects = array();

    // prepare ids
    if (empty($contributionId)) {
      if (!empty($result['order_number'])) {
        $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $result->order_number, 'id', 'trxn_id');
      }
    }
    if (empty($contributionId)) {
      return FALSE;
    }
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contributionId, FALSE);

    // prepare input
    $input = (array)$result;
    if(!empty($ids['event'])){
      $input['component'] = 'event';
    }
    else{
      $input['component'] = 'contribute';
    }
    /* TODO: remove this because this should be done in TapPayAPI
    if (!empty($input['currency']) && $input['currency'] != 'TWD') {
      $input['amount'] = $input['amount'] / 100;
    }
    */

    // ipn transact
    $ipn = new CRM_Core_Payment_BaseIPN();

    // First use ipn validate
    $validate_result = $ipn->validateData($input, $ids, $objects, FALSE);
    if(!$validate_result){
      return FALSE;
    }
    else {
      $pass = TRUE;
      $contribution = $objects['contribution'];

      // check trxn_id when pay_by_prime
      if ( !empty($result->card_secret) && !strstr($contribution->trxn_id, $result->order_number)) {
        $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }

      if (!empty($objects['contributionRecur'])) {
        $amount = round($objects['contributionRecur']->amount);
      }
      else {
        $amount = round($contribution->total_amount);
      }

      // check amount
      if ( $amount != $result->amount ) {
        $msgText = ts("Failuare: Amount values dont match between database and IPN request. Trxn_id is %1, Data from payment : %2, Data in CRM : %3", array(1 => $contribution->trxn_id, 2 => $result->amount, 3 => $amount))."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }



      // recurring validation

      $transaction = new CRM_Core_Transaction();

      if(!empty($result->status)){
        $status = $result->status;
      }else if(!empty($result->record_status)){
        // recordAPI use record_status
        $status = $result->record_status;
      }
      if($pass && $status == 0){
        $input['payment_instrument_id'] = $objects['contribution']->payment_instrument_id;
        $input['amount'] = $objects['contribution']->amount;
        $receiveTime = empty($result->transaction_time_millis) ? time() : ($result->transaction_time_millis / 1000);
        $objects['contribution']->receive_date = date('YmdHis', $receiveTime);
        $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction, NULL, $sendMail);
        if (!empty($ids['contributionRecur'])) {
          $sql = "SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1";
          $params = array( 1 => array($ids['contributionRecur'], 'Positive'));
          $recurTimes = CRM_Core_DAO::singleValueQuery($sql, $params);
          if ($recurTimes == 1) {
            $recur_params = array(
              'id' => $ids['contributionRecur'],
              'contribution_status_id' => 5,
            );
            $null = array();
            CRM_Contribute_BAO_ContributionRecur::add($recur_params, $null);
          }

        }
      }
      else{
        // Failed
        $ipn->failed($objects, $transaction, $error);
      }
      self::addNote($result->msg, $objects['contribution']);
    }

    return $isSuccess;
  }


  public static function doExecuteAllRecur ($time = NULL) {
    if (empty($time)) {
      $time = time();
    }
    $executeDay = date('j', $time);

    $sql = "SELECT r.id recur_id, r.last_execute_date last_execute_date, c.payment_processor_id payment_processor_id, c.is_test is_test FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.cycle_day = %1 AND r.contribution_status_id = 5 GROUP BY r.id";
    $params = array(
      1 => array($executeDay, 'Positive'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {

      // Check payment processor
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test': 'live');
      if (strtolower($paymentProcessor['payment_processor_type']) != 'tappay') {
        CRM_Core_Error::debug_log_message($resultNote.ts("Payment processor of recur is not %1.", array(1 => 'TapPay')));
        continue;
      }

      // Check last execute date.
      $currentDayTime = strtotime(date('Y-m-d', $time));
      $lastExecuteDayTime = strtotime(date('Y-m-d', strtotime($dao->last_execute_date)));
      if (!empty($dao->last_execute_date) && $currentDayTime <= $lastExecuteDayTime) {
        CRM_Core_Error::debug_log_message($resultNote.ts("Last execute date of recur is over the date."));
        continue;
      }

      self::doCheckRecur($dao->recur_id, $time);
    }
  }

  public static function doCheckRecur ($recurId, $time = NULL) {
    if (empty($time)) {
      $time = time();
    }
    // Update last_execute_date
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'last_execute_date', date('Y-m-d H:i:s', $time));
    // Get same cycle_day recur.
    $sql = "SELECT c.id contribution_id, r.id recur_id, r.contribution_status_id recur_status_id, r.end_date end_date, r.installments, r.frequency_unit, c.is_test FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.contribution_recur_id = %1 ORDER BY c.id ASC LIMIT 1";
    $params = array(
      1 => array($recurId, 'Positive'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    $resultNote = "Syncing recurring $recurId ";
    $changeStatus = FALSE;
    $goPayment = $donePayment = FALSE;
    $sqlContribution = "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1 AND is_test = %2";
    $paramsContribution = array(
      1 => array($dao->recur_id, 'Positive'),
      2 => array($dao->is_test, 'Integer'),
    );
    $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);

    if (!empty($dao->end_date)) {
      if ($time <= strtotime($dao->end_date)) {
        $goPayment = TRUE;
        $reason = 'by end_date not due ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the end_date was dued.";
      }
    }
    elseif (!empty($dao->installments)) {
      if ($successCount < $dao->installments) {
        $goPayment = TRUE;
        $reason = 'by installments not full ...';
      }
      else {
        $resultNote .= "Payment doesn't be executed cause the installments was full.";
      }
    }
    else {
      // Obviously, the condition is empty($dao->installments) && empty($dao->end_date)
      $goPayment = TRUE;
      $reason = 'by no end_date and installments set ...';
    }

    if ($goPayment) {
      // Check if Credit card over date.
      $tappay = new CRM_Contribute_DAO_TapPay();
      $tappay->contribution_id = $dao->contribution_id;
      $tappay->find(TRUE);
      if ($time <= strtotime($tappay->expiry_date)) {
        $resultNote .= $reason;
        $resultNote .= "\n".ts("Finish synchronizing recurring.");
        self::payByToken($dao->recur_id, $dao->contribution_id);
        $donePayment = TRUE;
        // Count again for new contribution.
        $successCount = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);
      }
      else {
        $resultNote .= $reason;
        $resultNote .= ', but card expiry date due.';
      }
    }

    // check recurring status change and reason
    // no else for make sure every rule checked

    if ($donePayment && $dao->frequency_unit == 'month' && !empty($dao->end_date) && date('Ym', $time) == date('Ym', strtotime($dao->end_date))) {
      $resultNote .= "\n". ts("Stop recurring %1 because this is lastest contribution of this recurring (end date is %2).", array(1 => $recurId, 2 => $dao->end_date));
      $changeStatus = TRUE;
    }
    if ($donePayment && $dao->frequency_unit == 'month' && !empty($tappay->expiry_date) && date('Ym', $time) == date('Ym', strtotime($tappay->expiry_date))) {
      $resultNote .= "\n". ts("Stop recurring %1 because this is lastest contribution of this recurring (expiry date is %2).", array(1 => $recurId, 2 => $tappay->expiry_date));
      $changeStatus = TRUE;
    }
    if (!empty($dao->end_date) && $time > strtotime($dao->end_date)) {
      $resultNote .= "\n".ts("Stop recurring %1 because end date is dued.", array(1 => $recurId));
      $changeStatus = TRUE;
    }
    if (!empty($dao->installments) && $successCount >= $dao->installments) {
      $resultNote .= "\n".ts("Stop recurring %1 because installments id full.", array(1 => $recurId));
      $changeStatus = TRUE;
    }
    if ($time > strtotime($tappay->expiry_date)) {
      $resultNote .= "\n".ts("Stop recurring %1 because card expiry date is due.", array(1 => $recurId));
      $changeStatus = TRUE;
    }

    if ( $changeStatus ) {
      $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
      $contributionRecur->id = $dao->recur_id;
      $contributionRecur->find(TRUE);
      $contributionRecur->contribution_status_id = 1;
      $contributionRecur->save();

      $resultNote .= "\n".ts("Update recurring status to 'Finished'.");
    }

    CRM_Core_Error::debug_log_message($resultNote);
    return $resultNote;
  }

  public static function queryRecord ($url_params, $get = array()) {
    // apply $_GET to $get , and filted params 'q'
    if(empty($get)){
      foreach ($_GET as $key => $value) {
        if($key == 'q')continue;
        $get[$key] = $value;
      }
    }

    // retrieve contribution_id from $_GET
    if(empty($contributionId)){
      $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    }

    $resultNote = self::doSyncRecord($contributionId);
    
    // redirect to contribution view page
    $query = http_build_query($get);
    $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
    CRM_Core_Error::statusBounce($resultNote, $redirect);
  }

  public static function doSyncRecord($contributionId, $data = NULL) {
    // retrieve contribution object
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);

    // retrieve tappay data object
    $tappay = new CRM_Contribute_DAO_TapPay();
    $tappay->contribution_id = $contributionId;
    $tappay->find(TRUE);

    // retrieve payment processor object
    $ppid = $contribution->payment_processor_id;
    $mode = $contribution->is_test ? 'tset' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    // setup tappay api
    $tappayParams = array(
      'apiType' => 'record',
      'partnerKey' => $paymentProcessor['password'],
    );
    $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

    // retrieve record data
    if (empty($data)) {
      $params = array(
        'contribution_id' => $contributionId,
        'partner_key' => $paymentProcessor['password'],
        'filters' => array(
          'order_number' => $contribution->trxn_id,
        ),
      );

      // Allow further manipulation of the arguments via custom hooks ..
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = self::singleton($mode, $paymentProcessor, $tappayParams['apiType']);
      $nullObject = NULL;
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $nullObject, $params);

      $result = $api->request($params);
      $record = !empty($result->trade_records[0]) ? $result->trade_records[0] : NULL;
    }
    else {
      $record = $data;
    }

    // check there are record in return list
    if(!empty($record)){

      // The status means refund
      if($record->record_status == 3) {

        // Call trade history api to get refund date.
        $tappayParams['apiType'] = 'trade_history';

        if (empty($data)) {
          $api_history = new CRM_Core_Payment_TapPayAPI($tappayParams);
          $params = array(
            'contribution_id' => $contributionId,
            'partner_key' => $paymentProcessor['password'],
            'rec_trade_id' => $tappay->rec_trade_id,
          );

          // Allow further manipulation of the arguments via custom hooks ..
          $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
          $paymentClass = self::singleton($mode, $paymentProcessor, $tappayParams['apiType']);
          CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $nullObject, $params);

          $result = $api_history->request($params);
          // Get the refund type history from history list.
          foreach ($result->trade_history as $history) {
            if($history->action == 3){
              break;
            }
          }
          $record->refund_date = $history->millis;
        }
      }

      $resultNote .= "\n".ts('Synchronizing to Tappay server success.');

      // Sync contribution status in CRM
      if($record->record_status == 0 && $contribution->contribution_status_id != 1) {
        self::doTransaction($record, $contributionId);
      }
      else if($record->record_status == 3 && $contribution->contribution_status_id != 3) {
        // record original cancel_date, status_id data.
        $origin_cancel_date = $contribution->cancel_date;
        $origin_cancel_date = date('YmdHis', strtotime($origin_cancel_date));
        $origin_status_id = $contribution->contribution_status_id;

        // check data
        $pass = TRUE;
        if($record->order_number != $contribution->trxn_id) {
          // order number is not correct.
          $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
          $resultNote .= $msgText;
          $pass = FALSE;
        }

        // check refund
        if($record->refunded_amount == $contribution->total_amount && $pass) {
          // find refund, check original status
          $cancelDate = date('Y-m-d H:i:s', $record->refund_date / 1000);
          $contribution->cancel_date = $cancelDate;
          $contribution->contribution_status_id = 3;
          $contribution->save();
          $resultNote .= "\n".ts('The contribution has been canceled.');
        }
      }
      else if ($record->record_status == 2) {

        // record original cancel_date, status_id data.
        $origin_cancel_date = $contribution->cancel_date;
        $origin_cancel_date = date('YmdHis', strtotime($origin_cancel_date));
        $origin_status_id = $contribution->contribution_status_id;

        // check data
        $pass = TRUE;
        if($record->order_number != $contribution->trxn_id) {
          // order number is not correct.
          $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request.").$contribution->trxn_id.": ".$result->order_number."\n";
          $resultNote .= $msgText;
          $pass = FALSE;
        }

        // check refund
        if($record->amount != $contribution->total_amount && $pass) {
          // find refund, check original status
          $contribution->total_amount = $record->amount;
          $contribution->contribution_status_id = 1;
          $contribution->save();
          $resultNote .= "\n".ts('The transaction has already been refunded.')." {$record->refund_amount}";
        }
        else {
          $resultNote .= "\n".ts('There are no any change.');
        }

      }
      else{
        $resultNote .= "\n".ts('There are no any change.');
      }
    }
    else {
      $resultNote .= "\n".ts('There are no valid record back.');
    }

    if (!empty($resultNote)) {
      // CRM_Core_Error::debug_log_message($resultNote);
      self::addNote($resultNote, $contribution);
    }
    return $resultNote;
  }

  public static function doSyncLastDaysRecords() {
    $last2Day = date('Y-m-d 00:00:00', time() - (86400 * 2));
    $currentDay = date('Y-m-d 23:59:59');
    $sql = "SELECT id, payment_processor_id, is_test FROM civicrm_contribution WHERE receive_date >= '$last2Day' && receive_date <= '$currentDay'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // Check payment processor
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($dao->payment_processor_id, $dao->is_test ? 'test': 'live');
      if (strtolower($paymentProcessor['payment_processor_type']) != 'tappay') {
        CRM_Core_Error::debug_log_message($resultNote.ts("Payment processor of recur is not %1.", array(1 => 'TapPay')));
        continue;
      }

      self::doSyncRecord($dao->id);
    }
  }

  public static function getAssociatedSession($qfKey, $class) {
    if(!$qfKey){
      return FALSE;
    }
    if(empty($class)){
      return FALSE;
    }

    // validate if key is permit by this session
    $key = CRM_Core_Key::validate($qfKey, $class, TRUE);
    if (empty($key)) {
      return FALSE;
    }

    // handling session and validating key
    $name = "_{$class}_".$key.'_container';
    $scope = "{$class}_".$key;
    CRM_Core_Session::registerAndRetrieveSessionObjects(array($name, array('CiviCRM', $scope)));
    $session = CRM_Core_Session::singleton();
    $payment = $session->get($scope);
    return $payment;
  }

  public static function cardNotify($url_params, $request = NULL) {
    // Get Input
    if (empty($request)) {
      $input = file_get_contents('php://input');
      $data = json_decode($input);
    }
    elseif (is_string($request)){
      $input = $request;
      $data = json_decode($request);
    }
    else {
      $data = $request;
    }

    if (empty($data)) {
      return 0;
    }

    // Get contribution ids by token
    foreach ($data->card_token as $token) {
      $sql = "SELECT contribution_id, contribution_recur_id FROM civicrm_contribution_tappay WHERE card_token = %1";
      $params = array(
        1 => array($token, 'String'),
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while ($dao->fetch()) {
        $recordData = array(
          'contribution_id' => $dao->contribution_id,
          'url' => $_SERVER['HTTP_X_FORWARDED_PROTO'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
          'date' => date('Y-m-d H:i:s'),
          'post_data' => $input,
        );
        CRM_Core_Payment_TapPayAPI::writeRecord(NULL, $recordData);

        // Update contribution_tappay for new expiry_date...etc.
        $updateData = clone $data;
        $updateData->card_token = $token;
        CRM_Core_Payment_TapPayAPI::saveTapPayData($dao->contribution_id, $updateData);

        $autoRenew = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $dao->contribution_id, 'auto_renew');
        if ($autoRenew != 2) {
          $params = array(
            'id' => $dao->contribution_recur_id,
            'auto_renew' => 2,
          );
          CRM_Contribute_BAO_ContributionRecur::add($params);
          $msg = ts("Set 'auto renew' value to 'renewed'.");
          CRM_Contribute_BAO_ContributionRecur::addNote($dao->contribution_recur_id, $msg);
        }

      }
    }
    return 1;
  }

  public static function doRecurTransact ($recurId = NULL) {
    $contributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');
    $resultNote = self::payByToken($recurId, $contributionId, FALSE);

    return $resultNote;
  }

  public static function getRecordDetail ($contributionId) {
    $tappayDAO = new CRM_Contribute_DAO_TapPay();
    $tappayDAO->contribution_id = $contributionId;
    $tappayDAO->find(TRUE);
    $tappayObject = json_decode($tappayDAO->data);

    $returnData = array();
    $returnData[ts('Record Trade ID')] = $tappayDAO->rec_trade_id;
    $returnData[ts('Card Number')] = $tappayDAO->bin_code."**********".$tappayDAO->last_four;
    $returnData[ts('Card Expiry Date')] = date('Y/m',strtotime($tappayDAO->expiry_date));
    $returnData[ts('Response Code')] = $tappayObject->status;
    $returnData[ts('Response Message')] = $tappayObject->msg;
    if (!empty($tappayObject->card_info)) {
      $cardInfo = $tappayObject->card_info;
      $returnData[ts('Card Issuer')] = $cardInfo->issuer;
      $returnData[ts('Card Type')] = self::$_cardType[$cardInfo->type];
    }
    $tokenStatus = strtolower($tappayDAO->token_status);
    $support3jtsp = array('active', 'suspend');
    $returnData[ts('Support 3JTSP')] = in_array($tokenStatus, $support3jtsp) ? ts("Yes") : ts("No");
    return $returnData;
  }

  public static function getContributionAllRecordData ($contributionId) {
    $logs = array();
    $tappayLog = new CRM_Contribute_DAO_TapPayLog();
    $tappayLog->contribution_id = $contributionId;
    $tappayLog->find();
    while ($tappayLog->fetch()) {
      $logs[$tappayLog->id] = (array) $tappayLog;
    }
    return $logs;
  }

  static function getContributionTrxnID($contributionId, $recurringId = NULL) {
    $rand = base_convert(rand(16, 255), 10, 16);
    if(empty($recurringId)){
      $recurringId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    }

    if(!empty($recurringId)){
      $trxnId = 'r_'.$recurringId.'_'.$contributionId.'_'.$rand;
    }else{
      $trxnId = 'c_'.$contributionId.'_'.$rand;
    }
    return $trxnId;
  }

  static function getSyncDataUrl ($contributionId) {
    $get = $_GET;
    unset($get['q']);
    $query = http_build_query($get);
    $sync_url = CRM_Utils_System::url("civicrm/tappay/query", $query);
    return $sync_url;
  }

  static function addNote($note, &$contribution){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s "). ts("Transaction record")."Trxn ID: {$contribution->trxn_id} \n\n".$note;
    CRM_Core_Error::debug_log_message( $note );
  }
}
