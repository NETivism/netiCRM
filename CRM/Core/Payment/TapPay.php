<?php

class CRM_Core_Payment_TapPay extends CRM_Core_Payment {
  
  protected $_mode = NULL;

  protected $_api = NULL;

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
  public static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_TapPay($mode, $paymentProcessor);
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

  function doTransferCheckout(&$params, $component) {
    $currentPath = CRM_Utils_System::currentPath();
    $thankyou = CRM_Utils_System::url($currentPath, '_qf_ThankYou_display=1&qfKey='.$params['qfKey']);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($thankyou);
    $url = CRM_Utils_System::url("civicrm/tappay/directpay", "id={$params['contributionID']}&qfKey={$params['qfKey']}&component={$component}");
    CRM_Utils_System::redirect($url);
  }

  public static function payByPrime() {
    // validate sessions
    $id = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $class = CRM_Utils_Request::retrieve('class', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $payment = CRM_Core_Payment_TapPay::getAssociatedSession($qfKey, $class);

    if ($payment && !empty($payment['paymentProcessor'])) {
      $trxn_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $id, 'trxn_id');
      if(empty($trxn_id)){
        $trxn_id = self::getContributionTrxnID($id);
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $id, 'trxn_id', $trxn_id);
      }

      $contribution = $ids = array();
      $params = array('id' => $id);
      CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
      list($sortName, $email) = CRM_Contact_BAO_Contact::getContactDetails($contribution['contact_id']);
      $paymentProcessor = $payment['paymentProcessor'];
      $prime = CRM_Utils_Request::retrieve('prime', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
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
        'remember' => $contribution['contribution_recur_id'] ? TRUE : FALSE,
        'contribution_id' => $id,
      );

      // Allow further manipulation of the arguments via custom hooks ..
      $mode = $paymentProcessor['is_test'] ? 'test' : 'live';
      $paymentClass = self::singleton($mode, $paymentProcessor);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      $result = $api->request($data);
      self::validateData($result, $id);

      $response = array('status' => $result->status, 'msg' => $result->msg);
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
    return CRM_Utils_System::notFound();
  }

  public static function payByToken($recurringId = NULL, $contributionId = NULL) {

    if(empty($recurringId)){
      $recurringId = CRM_Utils_Request::retrieve('crid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, $recurringId, 'REQUEST');
    }

    // Find the first contribution
    $contributionId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, $contributionId, 'REQUEST');
    if(empty($contributionId)){
      $sql = "SELECT MIN(c.id) FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON r.id = c.contribution_recur_id WHERE r.id = %1";
      $params = array(1 => array($recurringId, 'Positive'));
      $contributionId = CRM_Core_DAO::singleValueQuery($sql, $params);
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
    $c->save();
    CRM_Contribute_BAO_ContributionRecur::syncContribute($recurringId, $c->id);

    // Update new trxn_id
    $c->trxn_id = self::getContributionTrxnID($c->id);
    $c->save();

    $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
    $contributionRecur->id = $recurringId;
    $contributionRecur->find(TRUE);

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

      // Allow further manipulation of the arguments via custom hooks ..
      $paymentClass = self::singleton($mode, $paymentProcessor);
      CRM_Utils_Hook::alterPaymentProcessorParams($paymentClass, $payment, $data);

      // Send tappay pay_by_token post
      $result = $api->request($data);

      // Validate the result.
      self::validateData($result, $c->id, $pid);

      $response = array('status' => $result->status, 'msg' => $result->msg);
    }
    return $response;
  }

  public static function validateData($result, $contributionId = NULL) {
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
        $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$result->order_number}")."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }

      // check amount
      if ( round($contribution->total_amount) != $result->amount ) {
        $msgText = ts("Failuare: Amount values dont match between database and IPN request. Trxn_id is {$contribution->trxn_id}, Data from payment : {$result->amount}, Data in CRM : {$contribution->total_amount}")."\n";
        CRM_Core_Error::debug_log_message($msgText);
        $note .= $msgText;
        $pass = FALSE;
      }

      // recurring validation
      // certainly this is recurring contribution
      if($ids['contributionRecur']){
        $recur = &$objects['contributionRecur'];
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->trxn_id = $result->order_number;
        if($contribution->find(TRUE)) {
          // solve recur data.
          $params['id'] = $recur->id;
          $params['contribution_status_id'] = 5; // from pending to processing
          $params['modified_date'] = date('YmdHis');
          CRM_Contribute_BAO_ContributionRecur::add($params, $null);
        }
      }

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
        $objects['contribution']->receive_date = date('YmdHis');
        $transaction_result = $ipn->completeTransaction($input, $ids, $objects, $transaction);
      }
      else{
        // Failed
        $ipn->failed($objects, $transaction, $error);
      }
      self::addNote($result->msg, $objects['contribution']);
    }

    return $isSuccess;
  }


  public static function doExecuteRecur ($paymentProcessorId, $time = NULL) {
    $executeDay = date('d', $time);

    // Get same cycle_day recur.
    $sql = "SELECT MIN(c.id) contribution_id, contribution_recur_id recur_id, r.contribution_status_id status_id, r.end_date end_date, installments FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.payment_processor_id = %1 AND r.cycle_day = %2 GROUP BY r.id AND contribution_status_id = 5";
    $params = array(
      1 => array('Positive',$paymentProcessorId),
      2 => array('Positive',$executeDay),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      // execute payment.
      $changeStatus = FALSE;

      // TODO check if same day
      if ($time <= strtotime($dao->end_date)) {

        // Check installments
        $sqlContribution = "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1";
        $params = array(
          1 => array('Positive',$dao->id),
        );
        $count = CRM_Core_DAO::singleValueQuery($sqlContribution, $paramsContribution);
        if ($count < $dao->installments) {
          // Credit card over date.
          $tappay = new CRM_Contribute_DAO_TapPay();
          $tappay->contribution_recur_id = $dao->recur_id;
          $tappay->find(TRUE);
          if (strtotime($tappay->expiry_date) <= $time) {
            // Do sync recur
          }
          else {
            // card expiry.
            $changeStatus = TRUE;
          }
        }
        else {
          // installments is full
          $changeStatus = TRUE;  
        }
      }
      else {
        // Over end_date
        $changeStatus = TRUE;
      }

      // change status.
      if ( $changeStatus ) {
        $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
        $contributionRecur->id = $dao->recur_id;
        $contributionRecur->find(TRUE);
        $contributionRecur->contribution_status_id = 1;
        $contributionRecur->save();
      }
    }
  }

  public static function syncRecord ($url_params, $get = array()) {
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
    $mode = $contribution->is_test ? 'test' : 'live';
    $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $mode);

    // setup tappay api
    $tappayParams = array(
      'apiType' => 'record',
      'partnerKey' => $paymentProcessor['password'],
    );
    $api = new CRM_Core_Payment_TapPayAPI($tappayParams);

    // retrieve record data
    $data = array(
      'contribution_id' => $contributionId,
      'partner_key' => $paymentProcessor['password'],
      'filters' => array(
        'order_number' => $contribution->trxn_id,
      ),
    );
    $result = $api->request($data);
    $record = !empty($result->trade_records[0]) ? $result->trade_records[0] : NULL;

    // check there are record in return list
    if(!empty($record)){

      // The status means refund
      if($record->record_status == 3) {

        // Call trade history api to get refund date.
        $tappayParams['apiType'] = 'trade_history';

        $api_history = new CRM_Core_Payment_TapPayAPI($tappayParams);
        $data = array(
          'contribution_id' => $contributionId,
          'partner_key' => $paymentProcessor['password'],
          'rec_trade_id' => $tappay->rec_trade_id,
        );
        $result = $api_history->request($data);
        // Get the refund type history from history list.
        foreach ($result->trade_history as $history) {
          if($history->action == 3){
            break;
          }
        }
        $record->refund_date = date('Y-m-d H:i:s', $history->millis / 1000);
      }

      $result_note .= "\n".ts('Sync to Tappay server success.');

      // Sync contribution status in CRM
      if($record->record_status == 0 && $contribution->contribution_status_id != 1) {
        self::validateData($record, $contributionId);
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
          $msgText = ts("Failuare: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$result->order_number}")."\n";
          $result_note .= $msgText;
          $pass = FALSE;
        }

        // check refund
        if($record->refunded_amount == $contribution->total_amount && $pass) {
          // find refund, check original status
          $contribution->cancel_date = $record->refund_date;
          $contribution->contribution_status_id = 3;
          $contribution->save();
          $result_note .= "\n".ts('The contribution has been canceled.');
        }
      }
      else{
        $result_note .= "\n".ts('There are no any change.');
      }
    }
    else {
      $result_note .= "\n".ts('There are no valid record back.');
    }

    if (!empty($result_note)) {
      // CRM_Core_Error::debug_log_message($result_note);
      CRM_Core_Payment_Mobile::addNote($result_note, $contribution);
    }

    // redirect to contribution view page
    $query = http_build_query($get);
    $redirect = CRM_Utils_System::url('civicrm/contact/view/contribution', $query);
    CRM_Core_Error::statusBounce($result_note, $redirect);
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

  static function addNote($note, &$contribution){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s "). ts("Transaction record").": \n\n".$note."\n===============================\n";
    $note_exists = CRM_Core_BAO_Note::getNote( $contribution->id, 'civicrm_contribution' );
    if(count($note_exists)){
      $note_id = array( 'id' => reset(array_keys($note_exists)) );
      $note = $note . reset($note_exists);
    }
    else{
      $note_id = NULL;
    }
    $noteParams = array(
      'entity_table'  => 'civicrm_contribution',
      'note'          => $note,
      'entity_id'     => $contribution->id,
      'contact_id'    => $contribution->contact_id,
      'modified_date' => date('Ymd')
    );
    CRM_Core_BAO_Note::add( $noteParams, $note_id );
  }
}
