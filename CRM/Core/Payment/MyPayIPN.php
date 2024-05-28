<?php

class CRM_Core_Payment_MyPayIPN extends CRM_Core_Payment_BaseIPN {
  static $_payment_processor = NULL;
  static $_input = NULL;
  public $_post = NULL;
  public $_get = NULL;
  /**
   * @param array $post Input $_POST alternative.
   * @param array $get Input $_GET alternative.
   */
  function __construct($post = array(), $get = array()) {
    parent::__construct();
    $this->_post = $post;
    $this->_get = $get;
  }
  
  /**
   * Main function
   *
   * @param array $instrument The instrument, like 'Credit', 'BARCODE'... etc.
   *
   * @return void
   */
  function main($instrument){
    // get the contribution and contact ids from the GET params
    $objects = $ids = $input = array();
    $this->getIds($ids);
    $input = $this->_post;
    $input['component'] = !empty($ids['participant']) ? 'event' : 'contribute';

    // Record data to db. If it's not recur or first contribution.
    if (empty($input['nois']) || $input['nois'] == 1) {
      CRM_Core_Payment_MyPay::doRecordData($ids['contribution'], array('ipn_result_data' => json_encode($this->_post)));
    }
    if(empty($ids['contributionRecur'])){
      $isRecur = FALSE;
    }
    else{
      $isRecur = TRUE;
    }

    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$_payment_processor =& $objects['paymentProcessor'];
    self::$_input = $input;
    $result = NULL;
    if ($objects['contribution']->contribution_status_id == 1 && empty($input['nois']) && $input['prc'] == '250') {
      // Single contribution or first contribution, already completed, skip
      CRM_Core_Error::debug_log_message("MyPay: The transaction uid: {$input['uid']}, associated with the contribution {$objects['contribution']->trxn_id}, has been successfully processed before. Skipped.");
      $result = '8888';
    }
    elseif (!empty($input['nois']) && $input['prc'] == '250') {
      // Recurring and finished.
      $trxnId = CRM_Core_Payment_MyPay::getTrxnIdByPost($input);
      $sql = "SELECT contribution_status_id FROM civicrm_contribution WHERE trxn_id = %1";
      $params = array( 1 => array($trxnId, 'String'));
      $contributionStatusID = CRM_Core_DAO::singleValueQuery($sql, $params);
      if ($contributionStatusID == 1) {
        // already completed, skip
        CRM_Core_Error::debug_log_message("MyPay: The transaction uid: {$input['uid']}, associated with the contribution {$trxnId}, has been successfully processed before. Skipped.");
        $result = '8888';
      }
    }
    if ($result) {
      return $result;
    }
    else {
      // start validation
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $note, $instrument) ){
        $contribution =& $objects['contribution'];
        if(empty($contribution->receive_date)){
          if (!empty($input['finishtime'])) {
            $contribution->receive_date = date('YmdHis', strtotime($input['finishtime']));
          }
          else {
            $contribution->receive_date = date('YmdHis');
          }
        }

        // assign trxn_id before complete transaction
        $input['trxn_id'] = $objects['contribution']->trxn_id;
        $transaction = new CRM_Core_Transaction();
        $this->completeTransaction( $input, $ids, $objects, $transaction, $isRecur );
        $note .= "\n".ts('Completed')."\n";
        $this->addNote($note, $contribution);
        return '8888';
      }
      else{
        $note .= "\n".ts('Failed')."\n";
        $note .= ts("Payment Information").": ".ts("Failed").' - '.$input['RtnMsg']."({$input['RtnCode']})";
        $this->addNote($note, $objects['contribution']);
      }
    }
    
    // error stage: doesn't goto and not the background posturl
    // never for front-end user.
  }

  function validateOthers(&$input, &$ids, &$objects, &$note, $instrument) {
    $contribution = &$objects['contribution'];
    $pass = TRUE;

    // check contribution id matches
    // If it's recurring, Search for first contribution trxn_id.
    if (!strstr($contribution->trxn_id, $input['order_id'])) {
      $msg = "MyPay: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$input['order_id']} ";
      CRM_Core_Error::debug_log_message($msg);
      $note .= ts("Failuare: $msg")."\n";
      $pass = FALSE;
    }

    // check amount
    $amount = $input['cost'];
    if ( round($contribution->total_amount) != $amount) {
      $msg = "MyPay: Amount values dont match between database and IPN request. {$contribution->trxn_id} amount is '{$contribution->total_amount}', but return data is '{$input['cost']}'";
      CRM_Core_Error::debug_log_message($msg);
      $note .= ts("Failuare: $msg")."\n";
      $pass = FALSE;
    }

    // MyPay validation
    // the 'key' in request curl result must be same as 'uid_key' in `civicrm_contribution_mypay`.
    // If it's recurring , there are not key in the db. So this validation is only for single or first recurring.
    if (empty($input['nois']) || $input['nois'] == '1') {
      if(!empty($input['key'])){
        $key = CRM_Core_Payment_MyPay::getKey($contribution->id);
        if($key != $input['key']) {
          $note .= ts("Failuare: Key not match. Contact system admin.")."\n";
          $msg = "MyPay: Failuare: Key not match. Should be '{$key}', but '{$input['key']}' displayed.";
          CRM_Core_Error::debug_log_message($msg);
          $pass = FALSE;
        }
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

      // Todo: Validate Recurring.
      // Is Recurring
      if($this->_post['group_id']){
        $query_params = array(1 => array($recur->id, 'Integer'));
        $new_trxn_id = CRM_Core_Payment_MyPay::getTrxnIdByPost($input);
        if($input['prc'] != '250'){
          $contribution->contribution_status_id = 4; // Failed
          $id_from_trxn_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", array(
            1 => array($new_trxn_id, 'String'),
          ));
          if (!$id_from_trxn_id) {
            // Trxn_id is not existed, clone contribution.
            $c = self::copyContribution($contribution, $ids['contributionRecur'], $new_trxn_id);
          }
          else {
            // Sync contribution from given trxn_id. Usually used as syncing a recurring contribution.
            $c = new CRM_Contribute_DAO_Contribution();
            $c->id = $id_from_trxn_id;
            $c->find(TRUE);
          }
        }
        elseif($input['nois'] != '1'){
           // Completed
          $contribution->contribution_status_id = 1; // Completed
          $id_from_trxn_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", array(
            1 => array($new_trxn_id, 'String'),
          ));
          if (!$id_from_trxn_id) {
            // Trxn_id is not existed, clone contribution.
            $c = self::copyContribution($contribution, $ids['contributionRecur'], $new_trxn_id);
          }
          else {
            // Sync contribution from given trxn_id. Usually used as syncing a recurring contribution.
            $c = new CRM_Contribute_DAO_Contribution();
            $c->id = $id_from_trxn_id;
            $c->find(TRUE);
          }
        }
        if(!empty($c)){
          unset($objects['contribution']);
          // After retrive contribution object, first save db data via contribution_id.
          if ($input['nois'] > 1) {
            $data = array(
              'uid' => $input['uid'],
              'uid_key' => $input['key'],
              'ipn_result_data' => json_encode($input),
            );
            CRM_Core_Payment_MyPay::doRecordData($c->id, $data);
          }
          $objects['contribution'] = $c;

          // update recurring object
          // never end if TotalSuccessTimes not excceed the ExecTimes
          if (!empty($recur->installments) && $input['nois'] >= $recur->installments) {
            $params['id'] = $recur->id;
            $params['modified_date'] = date('YmdHis');
            $params['end_date'] = date('YmdHis');
            $params['contribution_status_id'] = 1; // completed
            CRM_Contribute_BAO_ContributionRecur::add($params, $null);
          }
        }
        else{
          // is first time
          if ($input['nois'] == 1) {
            if ($input['prc'] == '250'){
              $params['id'] = $recur->id;
              $params['start_date'] = $input['finishtime'];
              $params['contribution_status_id'] = 5; // from pending to processing
              $params['modified_date'] = date('YmdHis');
              CRM_Contribute_BAO_ContributionRecur::add($params, $null);
            }
            else{
              CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($recur->id, CRM_Core_DAO::$_nullObject, 4);
            }
          }
        }
      }
    }
    // process fail response
    if($input['prc'] != "250" && $pass){
      if (!empty($input['finishtime'])) {
        $time = strtotime($input['finishtime']);
        $objects['contribution']->cancel_date = date('YmdHis', $time);
      }
      $responseCode = $input['prc'];
      $responseMsg = $input['retmsg'];
      // $response_msg .= "\n".CRM_Core_Payment_MyPay::getErrorMsg($response_code);
      $failedReason = $responseMsg.' ('.ts('Error Code:').$responseCode.')';
      $note .= $failedReason;
      $transaction = new CRM_Core_Transaction();
      $this->failed($objects, $transaction, $failedReason);
      $pass = FALSE;
    }

    return $pass;
  }

  /**
   * MyPay ids doesn't be carried in GET params.
   * If it's recurring, The contribution should be the first time one.
   */
  function getIds(&$ids = array()) {
    $trxnId = $this->_post['order_id'];
    $contributionId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution WHERE trxn_id = %1', array(1 => array($trxnId, 'String')));
    $ids = CRM_Contribute_BAO_Contribution::buildIds($contributionId, '');
  }

  /**
   * 
   */
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
}