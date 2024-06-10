<?php

class CRM_Core_Payment_ALLPAYIPN extends CRM_Core_Payment_BaseIPN {
  static $_payment_processor = NULL;
  static $_input = NULL;
  public $_post = NULL;
  public $_get = NULL;
  function __construct($post, $get) {
    parent::__construct();
    $this->_post = $post;
    $this->_get = $get;
  }

  function main($component, $instrument){
    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $input = $this->_post;
    $this->getIds($ids, $component);
    if (!empty($ids['participant'])) {
      $input['component'] = 'event';
    }
    else {
      $input['component'] = 'contribute';
    }
    $qfKey = CRM_Utils_Array::value('qfKey', $this->_get);
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

    if(empty($this->_get['is_recur'])){
      self::doRecordData($ids['contribution'], $this->_post);
    }
    if(empty($ids['contributionRecur'])){
      // we will save record later if this is recurring
      $recur = FALSE;
    }
    else{
      $recur = TRUE;
    }

    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$_payment_processor =& $objects['paymentProcessor'];
    self::$_input = $input;

    if($objects['contribution']->contribution_status_id == 1 && empty($this->_get['is_recur'])){
      // already completed, skip
      return '1|OK';
    }
    else{
      // start validation
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $note) ){
        $contribution =& $objects['contribution'];
        if(empty($contribution->receive_date)){
          if (!empty($input['PaymentDate'])) {
            $contribution->receive_date = date('YmdHis', strtotime($input['PaymentDate']));
          }
          elseif (!empty($input['ProcessDate'])) {
            $contribution->receive_date = date('YmdHis', strtotime($input['ProcessDate']));
          }
          else {
            $contribution->receive_date = date('YmdHis');
          }
        }

        // assign trxn_id before complete transaction
        $input['trxn_id'] = $objects['contribution']->trxn_id;
        $transaction = new CRM_Core_Transaction();
        $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );
        $note .= ts('Completed')."\n";
        $this->addNote($note, $contribution);
        return '1|OK';
      }
      else{
        $note .= ts('Failed')."\n";
        $note .= ts("Payment Information").": ".ts("Failed").' - '.$input['RtnMsg']."({$input['RtnCode']})";
        $this->addNote($note, $objects['contribution']);
      }
    }
    
    // error stage: doesn't goto and not the background posturl
    // never for front-end user.
  }

  function getIds(&$ids){
    $contribId = CRM_Utils_Array::value('cid', $this->_get);
    if (!empty($contribId) && CRM_Utils_Type::escape($contribId, 'Integer')) {
      $ids = CRM_Contribute_BAO_Contribution::buildIds($contribId, FALSE);
      if (empty($ids)) {
        CRM_Core_Error::debug_log_message("Allpay: Could not found contribution id $contribId");
        CRM_Utils_System::civiExit();
      }
    }
    if (!empty($ids['participant'])) {
      if (!empty($this->_get['rid'])) {
        $ids['related_contact'] = CRM_Utils_Array::value('rid', $this->_get);
      }
      if (!empty($this->_get['onbehalf_dupe_alert'])) {
        $ids['onbehalf_dupe_alert'] = CRM_Utils_Array::value('onbehalf_dupe_alert', $this->_get);
      }
    }
  }

  function validateOthers( &$input, &$ids, &$objects, &$note){
    $contribution = &$objects['contribution'];
    $pass = TRUE;
    
    // check contribution id matches
    if (!strstr($contribution->trxn_id, $input['MerchantTradeNo'])) {
      $msg = "AllPay: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$input['MerchantTradeNo']} ";
      CRM_Core_Error::debug_log_message($msg);
      $note .= ts("Failuare: $msg")."\n";
      $pass = FALSE;
    } 

    // check amount
    $amount = $input['TradeAmt'] ? $input['TradeAmt'] : $input['Amount'];
    if ( round($contribution->total_amount) != $amount && $input['RtnCode'] == 1 ) {
      $msg = "AllPay: Amount values dont match between database and IPN request. {$contribution->trxn_id}-{$input['Gwsr']} : {$input['amount']}";
      CRM_Core_Error::debug_log_message($msg);
      $note .= ts("Failuare: $msg")."\n";
      $pass = FALSE;
    }

    // allpay validation
    // only validate this when not test.
    if(!empty($input['CheckMacValue'])){
      $mac = CRM_Core_Payment_ALLPAY::generateMacValue($this->_post, self::$_payment_processor);
      if(strtolower($input['CheckMacValue']) != strtolower($mac)) {
        $note .= ts("Failuare: CheckMacValue not match. Contact system admin.")."\n";
        $msg = "AllPay: Failuare: CheckMacValue not match. Should be '{$mac}', but '{$input['CheckMacValue']}' displayed.";
        CRM_Core_Error::debug_log_message($msg);
        $pass = FALSE;
      }
    }

    // recurring validation
    // certainly this is recurring contribution
    if($ids['contributionRecur']){
      $recur = &$objects['contributionRecur'];
      $params = $null = array();
      // see if we are first time, if not first time, save new contribution
      // 6 - expired
      // 5 - processing
      // 4 - fail
      // 3 - canceled
      // 2 - pending
      // 1 - completed

      // not the first time (PeriodReturnURL)
      if($this->_get['is_recur']){
        $trxn_id = CRM_Core_Payment_ALLPAY::generateRecurTrxn($input['MerchantTradeNo'], $input['Gwsr']);
        $local_succ_times = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1", array(1 => array($recur->id, 'Integer')));
        if($input['RtnCode'] != 1){
          $contribution->contribution_status_id = 4; // Failed
          $c = self::copyContribution($contribution, $ids['contributionRecur'], $trxn_id);
        }
        elseif($input['RtnCode'] == 1){
          if ($local_succ_times >= $input['TotalSuccessTimes']) {
            // Possible over charged. Record on the contribtion
            $local_succ_times++;
            $msg = 'AllPay: Possible over charge, detect from TotalSuccessTimes: '.$input['TotalSuccessTimes'];
            CRM_Core_Error::debug_log_message($msg);
            $note .= "Possible over charge. Will be $local_succ_times successful contributions in CRM, but greenworld only have {$input['TotalSuccessTimes']} success execution.";
          }
          $contribution->contribution_status_id = 1; // Completed
          $c = self::copyContribution($contribution, $ids['contributionRecur'], $trxn_id);
        }
        if(!empty($c)){
          unset($objects['contribution']);
          self::doRecordData($c->id, $this->_post);
          // Set expire time
          $data = $this->_post;
          if(!empty($data['#info']['ExpireDate'])){
            $expire_date = $data['#info']['ExpireDate'];
          }
          if(!empty($data['ExpireDate'])){
            $expire_date = $data['ExpireDate'];
          }
          if(!empty($expire_date)){
            if (strlen($expire_date) < 11) {
              $expire_date = str_replace('/', '-', $expire_date).' 23:59:59';
            }
            $sql = "UPDATE civicrm_contribution SET expire_date = %1 WHERE id = %2";
            $params = array(
              1 => array( $expire_date, 'String'),
              2 => array( $c->id, 'Integer'),
            );
            CRM_Core_DAO::executeQuery($sql, $params);
          }
          $objects['contribution'] = $c;

          // update recurring object
          // never end if TotalSuccessTimes not excceed the ExecTimes
          if($input['TotalSuccessTimes'] == $recur->installments){
            $params['id'] = $recur->id;
            $params['modified_date'] = date('YmdHis');
            $params['end_date'] = date('YmdHis');
            $params['contribution_status_id'] = 1; // completed
            CRM_Contribute_BAO_ContributionRecur::add($params, $null);
          }
        }
      }
      else{
        // is first time
        if($input['RtnCode'] == 1){
          $params['id'] = $recur->id;
          $params['start_date'] = date('YmdHis', strtotime($input['PaymentDate']));
          $params['contribution_status_id'] = 5; // from pending to processing
          $params['modified_date'] = date('YmdHis');
          CRM_Contribute_BAO_ContributionRecur::add($params, $null);
        }
        else{
          CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($recur->id, CRM_Core_DAO::$_nullObject, 4);
        }
      }
    }
      
    // process fail response
    if($input['RtnCode'] != 1 && $pass){
      if (!empty($input['ProcessDate'])) {
        $time = strtotime($input['ProcessDate']);
        $objects['contribution']->cancel_date = date('YmdHis', $time);
      }
      $response_code = $input['RtnCode'];
      $response_msg = $input['RtnMsg'];
      $response_msg .= "\n".CRM_Core_Payment_ALLPAY::getErrorMsg($response_code);
      $failed_reason = $response_msg.' ('.ts('Error Code:').$response_code.')';
      $note .= $failed_reason;
      $transaction = new CRM_Core_Transaction();
      $this->failed($objects, $transaction, $failed_reason);
      $pass = FALSE;
    }

    return $pass;
  }

  function addNote($note, &$contribution){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s"). ts("Transaction record").": \n".$note."\n===============================\n";
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

  /**
   * Save data to database. Original civicrm_allpay_record.
   * 
   * @param integer|array $cid Contribution ID or Array of Contribution IDs.
   * @param array $data The data need to write in database.
   * 
   * @return void
   */
  static function doRecordData($cid, $data = null){
    if (is_array($cid)) {
      // from civicrm route, first parameter is array.
      // for example: as allpay/record/1, $var1 = ['allpay', 'record', '1']
      $params = $cid;
      $cid = end($params);
    }
    if(is_numeric($cid)){
      $billing_notify = FALSE;
      if(empty($data) && !empty($_POST)){
        if(!empty($params) && $params[1] == 'record'){
          $billing_notify = TRUE;
          $data['#info'] = $_POST;
        }
        else{
          $data = $_POST;
        }
      }
      if(!empty($data['MerchantID']) || !empty($data['#info']['MerchantID'])){
        $allpayDAO = new CRM_Contribute_DAO_AllPay();
        $allpayDAO->cid = $cid;
        if ($allpayDAO->find(TRUE)) {
          $query = "UPDATE civicrm_contribution_allpay SET data = %2 WHERE cid = %1";
          $existsData = json_decode($allpayDAO->data, TRUE);
          $data = array_merge($existsData, $data);
          CRM_Core_DAO::executeQuery($query, array(
            1 => array($cid, 'Positive'),
            2 => array(json_encode($data), 'String'),
          ));
        }
        else {
          $query = "INSERT INTO civicrm_contribution_allpay (cid, data) VALUES (%1, %2)";
          CRM_Core_DAO::executeQuery($query, array(
            1 => array($cid, 'Positive'),
            2 => array(json_encode($data), 'String'),
          ));
        }

        if($billing_notify && function_exists('civicrm_allpay_notify_generate')){
          $maybeSent = CRM_Core_DAO::singleValueQuery("SELECT expire_date FROM civicrm_contribution WHERE id = %1", array(
            1 => array( $cid, 'Integer'),
          ));
          if (!$maybeSent) {
            civicrm_allpay_notify_generate($cid, TRUE); // send mail
          }

          // return allpay successful received notify
          echo "1|OK";
        }

        // Set expire time
        if(!empty($data['#info']['ExpireDate'])){
          $expire_date = $data['#info']['ExpireDate'];
        }
        if(!empty($data['ExpireDate'])){
          $expire_date = $data['ExpireDate'];
        }
        if(!empty($expire_date)){
          if (strlen($expire_date) < 11) {
            $expire_date = str_replace('/', '-', $expire_date).' 23:59:59';
          }
          $sql = "UPDATE civicrm_contribution SET expire_date = %1 WHERE id = %2";
          $params = array(
            1 => array( $expire_date, 'String'),
            2 => array( $cid, 'Integer'),
          );
          CRM_Core_DAO::executeQuery($sql, $params);
        }
      }
    }
  }
}
