<?php

class CRM_Core_Payment_SPGATEWAYIPN extends CRM_Core_Payment_BaseIPN {

  private $_post;
  private $_get;
  private $_paymentProcessor;

  function __construct($post, $get) {
    parent::__construct();
    $this->_post = $post;
    $this->_get = $get;
  }

  function main($instrument){
    $objects = $ids = $input = [];
    $this->getIds($ids);
    // agreement
    if (!empty($this->_post['TradeInfo']) && !empty($this->_post['Version']) && $this->_post['Version'] === CRM_Core_Payment_SPGATEWAY::AGREEMENT_VERSION) {
      $ppid = NULL;
      $recur = FALSE;
      if (!empty($ids['contributionRecur'])) {
        $recur = TRUE;
        $sql = 'SELECT processor_id FROM civicrm_contribution_recur WHERE id = %1';
        $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contributionRecur'], 'Integer']]);
      }
      if (empty($ppid)) {
        $sql = 'SELECT payment_processor_id FROM civicrm_contribution WHERE id = %1';
        $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contribution'], 'Integer']]);
      }
      if (empty($ppid)) {
        CRM_Core_Error::debug_log_message("Spgateway: could not find payment processor id on this contribution {$ids['contribution']}");
        CRM_Utils_System::civiExit();
      }
      $isTest = CRM_Core_DAO::singleValueQuery("SELECT is_test FROM civicrm_payment_processor WHERE id = %1", [1 => [$ppid, 'Integer']]);
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $isTest ? 'test' : 'live');
      $post = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($this->_post['TradeInfo'], $paymentProcessor);
      // special case for credit card agreement
      // first contribution and follow contribution have diffrent merchant id
      if (empty($post)) {
        $sql = 'SELECT payment_processor_id FROM civicrm_contribution WHERE id = %1';
        $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contribution'], 'Integer']]);
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $isTest ? 'test' : 'live');
        $post = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($this->_post['TradeInfo'], $paymentProcessor);
      }
      $this->_post = $post;

      CRM_Core_Payment_SPGATEWAYAPI::writeRecord($ids['contribution'], $this->_post, $ids['contributionRecur'] ?? $ids['contributionRecur']);
      $input = CRM_Core_Payment_SPGATEWAYAPI::dataDecode($this->_post);
    }
    // common credit card
    elseif(empty($ids['contributionRecur'])){
      $recur = FALSE;
      if (!empty($this->_post['JSONData'])) {
        CRM_Core_Payment_SPGATEWAYAPI::writeRecord($ids['contribution'], $this->_post);
        $input = CRM_Core_Payment_SPGATEWAYAPI::dataDecode($this->_post);
      }
      elseif (!empty($this->_post['TradeInfo'])) {
        $ppid = NULL;
        if (empty($ppid)) {
          $sql = 'SELECT payment_processor_id FROM civicrm_contribution WHERE id = %1';
          $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contribution'], 'Integer']]);
        }
        if (empty($ppid)) {
          CRM_Core_Error::debug_log_message("Spgateway: could not find payment processor id on this contribution {$ids['contribution']}");
          CRM_Utils_System::civiExit();
        }
        $isTest = CRM_Core_DAO::singleValueQuery("SELECT is_test FROM civicrm_payment_processor WHERE id = %1", [1 => [$ppid, 'Integer']]);
        $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $isTest ? 'test' : 'live');
        $post = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($this->_post['TradeInfo'], $paymentProcessor);
        $this->_post = $post;

        CRM_Core_Payment_SPGATEWAYAPI::writeRecord($ids['contribution'], $this->_post);
        $input = CRM_Core_Payment_SPGATEWAYAPI::dataDecode($this->_post);
      }
    }
    // recurring 1.0 or 1.1
    else{
      $recur = TRUE;
      // Refs #35316, recur.proessor_id => contribution.payment_processor_id => $_GET['ppid']
      $sql = 'SELECT processor_id FROM civicrm_contribution_recur WHERE id = %1';
      $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contributionRecur'], 'Integer']]);
      if (empty($ppid)) {
        $sql = 'SELECT payment_processor_id FROM civicrm_contribution WHERE id = %1';
        $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contribution'], 'Integer']]);
      }
      if (empty($ppid)) {
        CRM_Core_Error::debug_log_message("Spgateway: could not find payment processor id on this contribution {$ids['contribution']}");
        CRM_Utils_System::civiExit();
      }
      $isTest = CRM_Core_DAO::singleValueQuery("SELECT is_test FROM civicrm_payment_processor WHERE id = %1", [
        1 => [$ppid, 'Integer'],
      ]);
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $isTest ? 'test' : 'live');
      $post = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($this->_post['Period'], $paymentProcessor);
      // special case for credit card agreement
      // first contribution and follow contribution have diffrent merchant id
      if ($post === FALSE) {
        $sql = 'SELECT payment_processor_id FROM civicrm_contribution WHERE id = %1';
        $ppid = CRM_Core_DAO::singleValueQuery($sql, [1 => [$ids['contribution'], 'Integer']]);
        if (!empty($ppid)) {
          $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $isTest ? 'test' : 'live');
          $post = CRM_Core_Payment_SPGATEWAYAPI::recurDecrypt($this->_post['Period'], $paymentProcessor);
        }
      }
      $this->_post = $post;
      $input = CRM_Core_Payment_SPGATEWAYAPI::dataDecode($this->_post);
      // we will save record later if this is recurring after second times.
      if(empty($input['AlreadyTimes'])){
        // First time recurring
        CRM_Core_Payment_SPGATEWAYAPI::writeRecord($ids['contribution'], $this->_post, $ids['contributionRecur']);
      }
    }
    $input['component'] = !empty($ids['participant']) ? 'event' : 'contribute';

    // now, retrieve full object by validateData, or false fallback
    // when it's recurring, this will load first recurring contrib into object even it's not
    if (!$this->validateData( $input, $ids, $objects ) ) {
      return FALSE;
    }

    // set global variable for paymentProcessor
    $this->_paymentProcessor = $objects['paymentProcessor'];

    if($objects['contribution']->contribution_status_id == 1 && empty($input['AlreadyTimes'])){
      // already completed, skip
      return '1|OK';
    }
    else{
      // skip doing job when contribution already success
      if(!empty($input['AlreadyTimes']) && $input['Status'] === 'SUCCESS'){
        $newTrxnId = $input['OrderNo'];
        if ($newTrxnId == $input['MerchantOrderNo'] . "_1") {
          if($objects['contribution']->contribution_status_id == 1){
            CRM_Core_Error::debug_log_message("Spgateway: The transaction {$newTrxnId}, associated with the contribution {$objects['contribution']->trxn_id}, has been successfully processed before. Skipped.");
            return '1|OK';
          }
        }
        else {
          $alreadySuccessId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1 AND contribution_status_id = 1", [
            1 => [$newTrxnId, 'String'],
          ]);
          if (!empty($alreadySuccessId)) {
            CRM_Core_Error::debug_log_message("Spgateway: The transaction {$newTrxnId}, associated with the contribution {$alreadySuccessId}, has been successfully processed before. Skipped.");
            return '1|OK';
          }
        }
      }
      // start validation
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $note, $instrument) ){
        $contribution =& $objects['contribution'];
        if(empty($contribution->receive_date)){
          if(!empty($input['PayTime'])){
            $contribution->receive_date = date('YmdHis', strtotime($input['PayTime']));
          }elseif(!empty($input['AuthTime'])){
            $contribution->receive_date = date('YmdHis', strtotime($input['AuthTime']));
          }elseif(!empty($input['AuthDate'])){
            $contribution->receive_date = date('YmdHis', strtotime($input['AuthDate']));
          }else{
            $contribution->receive_date = date('YmdHis');
          }
        }

        // assign trxn_id before complete transaction
        $recurContribCount = NULL;
        if (!empty($contribution->contribution_recur_id)) {
          $recurContribCount = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_contribution WHERE contribution_recur_id = %1", [
            1 => [$contribution->contribution_recur_id, 'Integer']
          ]);
        }
        if(!empty($input['PeriodAmt']) && $recurContribCount == 1){
          // first contribution of recurring, update trxn_id.
          $input['trxn_id'] = $input['MerchantOrderNo'] . "_1";
        }
        else {
          $input['trxn_id'] = $objects['contribution']->trxn_id;
        }
        $transaction = new CRM_Core_Transaction();
        $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );
        $note .= ts('Completed')."\n";
        $this->addNote($note, $contribution);
        return '1|OK';
      }
      else{
        $rtnCode = !empty($input['RtnCode']) ? $input['RtnCode'] : '';
        $rtnMsg = !empty($input['RtnMsg']) ? $input['RtnMsg'] : '';
        $note .= ts('Failed')."\n";
        $note .= ts("Payment Information").": ".ts("Failed").' - '.$rtnMsg."({$rtnCode})";
        $this->addNote($note, $objects['contribution']);
        return '';
      }
    }

    // error stage: doesn't goto and not the background posturl
    // never for front-end user.
    return FALSE;
  }

  function getIds(&$ids){
    $contribId = CRM_Utils_Array::value('cid', $this->_get);
    if (!empty($contribId) && CRM_Utils_Type::escape($contribId, 'Integer')) {
      $ids = CRM_Contribute_BAO_Contribution::buildIds($contribId, FALSE);
      if (empty($ids)) {
        CRM_Core_Error::debug_log_message("Spgateway: Could not found contribution id $contribId");
        CRM_Utils_System::civiExit();
      }
    }
    // component is contribute
    if (empty($ids['participant'])) {
      if (!empty($this->_get['rid'])) {
        $ids['related_contact'] = CRM_Utils_Array::value('rid', $this->_get);
      }
      if (!empty($this->_get['onbehalf_dupe_alert'])) {
        $ids['onbehalf_dupe_alert'] = CRM_Utils_Array::value('onbehalf_dupe_alert', $this->_get);
      }
    }
  }

  function validateOthers( &$input, &$ids, &$objects, &$note, $instrument = ''){
    $contribution = &$objects['contribution'];
    $pass = TRUE;
    $validValue = [];
    if(!empty($input['MerchantOrderNo'])){
      $validValue['MerchantOrderNo'] = $input['MerchantOrderNo'];
    }else{
      $validValue['MerchantOrderNo'] = $input['MerOrderNo'];
    }
    // for credit card agreement
    if (!empty($input['TokenLife'])) {
      $validValue['Amt'] = !empty($input['Amt']) ? $input['Amt'] : '0';
    }
    elseif(!empty($ids['contributionRecur'])){
      if(!empty($input['AuthAmt'])){
        // after the first recurring
        $validValue['Amt'] = $input['AuthAmt'];
        if ($contribution->total_amount != $validValue['Amt']) {
          $note .= ts("Amount values dont match between database and IPN request. Force use IPN instead {$contribution->trxn_id}-{$input['AlreadyTimes']} . Original: {$contribution->total_amount}, IPN:{$validValue['Amt']}")."\n";
          $contribution->total_amount = $validValue['Amt'];
        }
      }
      else{
        // first recurring
        $validValue['Amt'] = !empty($input['PeriodAmt']) ? $input['PeriodAmt'] : '0';
      }
    }
    else{
      $validValue['Amt'] = !empty($input['Amt']) ? $input['Amt'] : '0';
      $validValue['CheckCode'] = !empty($input['CheckCode']) ? $input['CheckCode'] : '';
    }

    // check contribution id matches
    // If is from old neweb. Skip check.
    // if it's recurring, the merchant order not should be first recurring no
    if ( !strstr($contribution->trxn_id, $validValue['MerchantOrderNo']) && !preg_match('/^99[\d]{7}$/', $validValue['MerchantOrderNo'])) {
      CRM_Core_Error::debug_log_message("Spgateway: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$validValue['MerchantOrderNo']} " );
      $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request. {$contribution->trxn_id} : {$validValue['MerchantOrderNo']}")."\n";
      $pass = FALSE;
    }

    // check amount
    if ( round($contribution->total_amount) != $validValue['Amt'] && $input['Status'] == 'SUCCESS' ) {
      CRM_Core_Error::debug_log_message("Spgateway: Amount values dont match between database and IPN request. {$contribution->trxn_id}-{$input['AlreadyTimes']} : {$validValue['Amt']}" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request. {$contribution->trxn_id}-{$input['AlreadyTimes']} : {$validValue['Amt']}")."\n";
      $pass = FALSE;
    }

    // spgateway validation
    // only validate this when not test.
    if (strtolower($instrument) == 'googlepay') {
      $ppid = $this->_paymentProcessor['user_name'];
      $test = $contribution->is_test ? 'test':'live';
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, $test);
    }
    else {
      $paymentProcessor = $this->_paymentProcessor;
    }
    if(!empty($validValue['CheckCode'])){
      $checkCode = CRM_Core_Payment_SPGATEWAYAPI::checkCode($input, $paymentProcessor);
      if(strtolower($validValue['CheckCode']) != strtolower($checkCode)) {
        $note .= ts("Failuare: CheckCode not match. Contact system admin.")."\n";
        CRM_Core_Error::debug_log_message("Spgateway: Failuare: CheckCode not match. Should be '{$checkCode}', but '{$input['CheckCode']}' provided.");
        $pass = FALSE;
      }
    }

    // recurring validation
    if(!empty($ids['contributionRecur'])){
      $recur = &$objects['contributionRecur'];
      $params = $null = [];
      // see if we are first time, if not first time, save new contribution
      // 6 - expired
      // 5 - processing
      // 4 - fail
      // 3 - canceled
      // 2 - pending
      // 1 - completed

      // not the first time (PeriodReturnURL)
      if(!empty($input['AlreadyTimes'])){
        $trxn_id = $input['OrderNo'];
        $alreadyExistsId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1", [
          1 => [$trxn_id, 'String'],
        ]);
        if($input['Status'] != 'SUCCESS'){
          $contribution->contribution_status_id = 4; // Failed
          if (!$alreadyExistsId) {
            $c = self::copyContribution($contribution, $ids['contributionRecur'], $trxn_id);
          }
          else {
            $c = new CRM_Contribute_DAO_Contribution();
            $c->id = $alreadyExistsId;
            $c->find(TRUE);
          }
        }
        else{
          $contribution->contribution_status_id = 1; // Completed
          // Check if trxn_id is existed or not.
          if (!$alreadyExistsId) {
            // Trxn_id is not existed, clone contribution.
            $c = self::copyContribution($contribution, $ids['contributionRecur'], $trxn_id);
          }
          else {
            // Sync contribution from given trxn_id.
            // Will only process this when contribution status is not success.
            $c = new CRM_Contribute_DAO_Contribution();
            $c->id = $alreadyExistsId;
            $c->find(TRUE);
          }
        }
        if(!empty($c)){
          unset($objects['contribution']);
          CRM_Core_Payment_SPGATEWAYAPI::writeRecord($c->id, $this->_post);
          $objects['contribution'] = $c;
          if(isset($input['TotalTimes']) && $input['AlreadyTimes'] == $input['TotalTimes']){
            $recurParam = [
              'id' => $ids['contributionRecur'],
              'modified_date' => date('YmdHis'),
              'end_date' => date('YmdHis'),
              'contribution_status_id' => 1, // completed
            ];
            CRM_Contribute_BAO_ContributionRecur::add($recurParam, $null);
          }
        }
      }
      else{
        // is first time
        if($input['Status'] == 'SUCCESS'){
          $params['id'] = $recur->id;
          $params['start_date'] = date('YmdHis', strtotime($input['AuthTime']));
          $params['contribution_status_id'] = 5; // from pending to processing
          $params['modified_date'] = date('YmdHis');
          $params['trxn_id'] = $input['PeriodNo'];
          if (!empty($input['AuthTimes']) && $input['AuthTimes'] != $params['installments']) {
            $params['installments'] = $input['AuthTimes'];
            $params['message'] = ts('Modify installments by Newebpay data.');
            CRM_Contribute_BAO_ContributionRecur::addNote($recur->id, $params['message']);
          }
          CRM_Contribute_BAO_ContributionRecur::add($params, $null);
        }
        else{
          CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($recur->id, CRM_Core_DAO::$_nullObject, 4);
        }
      }
    }

    // process fail response
    if($input['Status'] != 'SUCCESS' && $pass){
      $responseCode = $input['Status'];
      $responseMsg = $input['Message']."\n"._civicrm_spgateway_error_msg($responseCode);
      if ($input['Status'] == 'Error' && !empty($input['RespondCode'])) {
        $responseCode .= ': ' . $input['RespondCode'];
      }
      $failedReason = $responseMsg. ' ('.ts('Error Code:'). $responseCode.')';
      $note .= $failedReason;
      if ($input['PayTime']) {
        $objects['contribution']->cancel_date = $input['PayTime'];
      }
      elseif ($input['CreateTime']) {
        $objects['contribution']->cancel_date = $input['CreateTime'];
      }
      elseif ($input['AuthDate']) {
        $objects['contribution']->cancel_date = $input['AuthDate'];
      }
      $transaction = new CRM_Core_Transaction();
      $this->failed($objects, $transaction, $failedReason);
      $pass = FALSE;
    }

    return $pass;
  }

  function addNote($note, &$contribution){
    $note = date("Y/m/d H:i:s"). ts("Transaction record").": \n".$note."\n===============================\n";
    $noteExists = CRM_Core_BAO_Note::getNote( $contribution->id, 'civicrm_contribution' );
    if(count($noteExists)){
      $noteId = [ 'id' => reset(array_keys($noteExists)) ];
      $note = $note . reset($noteExists);
    }
    else{
      $noteId = NULL;
    }

    $noteParams = [
      'entity_table'  => 'civicrm_contribution',
      'note'          => $note,
      'entity_id'     => $contribution->id,
      'contact_id'    => $contribution->contact_id,
      'modified_date' => date('Ymd')
    ];
    CRM_Core_BAO_Note::add($noteParams, $noteId);
  }
}