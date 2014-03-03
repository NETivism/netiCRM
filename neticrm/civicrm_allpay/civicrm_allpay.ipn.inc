<?php
civicrm_initialize();
require_once 'CRM/Core/Payment/BaseIPN.php';
date_default_timezone_set("Asia/Taipei");

/**
 * Helper function for receive pingback from payment server
 */
function civicrm_allpay_ipn($instrument){
  // detect variables
  if(empty($_POST)){
    watchdog("civicrm_allpay", "Could not find POST data from payment server");
  }
  else{
    if($component = $_GET['module']){
      $ipn = new CRM_Core_Payment_ALLPAYIPN();
      $ipn->main($component, $instrument);
    }
    else{
      watchdog('civicrm_allpay', "Could not get module name from request url");
    }
  }
}

class CRM_Core_Payment_ALLPAYIPN extends CRM_Core_Payment_BaseIPN {
  static $_payment_processor = null;
  static $_input = null;
  function __construct( ) {
    parent::__construct( );
  }

  function main(  $component = 'contribute', $instrument){
    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $input = $_POST;
    $this->getIds($ids, $component);
    $input['component'] = $component;
    $qfKey = $_GET['qfKey'];
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

    if(empty($ids['contributionRecur'])){
      // we will save record later if this is recurring
      civicrm_allpay_record($ids['contribution']);
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

    if($objects['contribution']->contribution_status_id == 1 && empty($_GET['is_recur'])){
      // already completed, skip
      echo '1|OK';
    }
    else{
      // start validation
      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $transaction, $note) ){
        $contribution =& $objects['contribution'];
        if(empty($contribution->receive_date)){
          $contribution->receive_date = $input['PaymentDate'] ? date('YmdHis', strtotime($input['PaymentDate'])) : date('YmdHis');
        }
        $input['trxn_id'] = $contribution->id;
        $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );
        $note .= ts('Completed')."\n";
        $this->addNote($note, $contribution);
        echo '1|OK';
      }
      else{
        $note .= ts('Failed')."\n";
        drupal_set_message(ts("Payment Information").": ".ts("Failed").' - '.$input['response_msg']."({$input['response_code']})", 'error');
        $this->addNote($note, $objects['contribution']);
      }
    }
    
    // error stage: doesn't goto and not the background posturl
    // never for front-end user.
  }

  function getIds( &$ids , $component){
    $ids['contact'] = $_GET['contact_id'];
    $ids['contribution'] = $_GET['cid'];
    if ( $component == 'event' ) {
      $ids['event'] = $_GET['eid'];
      $ids['participant'] = $_GET['pid'];
    }
    else {
      $ids['membership'] = $_GET['mid'];
      $ids['contributionRecur'] = $_GET['crid'];
      $ids['contributionPage'] = $_GET['cpid'];
      $ids['related_contact'] = $_GET['rid'];
      $ids['onbehalf_dupe_alert'] = $_GET['onbehalf_dupe_alert'];
    }
  }

  function validateOthers( &$input, &$ids, &$objects, &$transaction, &$note){
    $contribution = &$objects['contribution'];
    $pass = TRUE;
    
    // check contribution id matches
    if ( $contribution->id != $input['MerchantTradeNo'] ) {
      watchdog("civicrm_allpay", "OrderNumber values doesn't match between database and IPN request" );
      $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request")."\n";
      $pass = FALSE;
    } 

    // check amount
    $amount = $input['TradeAmt'] ? $input['TradeAmt'] : $input['Amount'];
    if ( round($contribution->total_amount) != $amount ) {
      watchdog("civicrm_allpay", "Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // allpay validation
    $mac = _civicrm_allpay_checkmacvalue($_POST, self::$_payment_processor);
    if(!$input['CheckMacValue'] != $mac) {
      $note .= ts("Failuare: CheckMacValue not match. Contact system admin.")."\n";
      watchdog("civicrm_allpay", "Failuare: CheckMacValue not match. Contact system admin.");
      $pass = FALSE;
    }

    // recurring validation
    // certainly this is recurring contribution
    if($ids['contributionRecur'] && $pass){
      $recur = &$objects['contributionRecur'];
      $params = $null = array();
      // see if we are first time, if not first time, save new contribution
      // cancel recurring
      // 4 - fail
      // 3 - end
      // 2 - pending
      // 1 - current(already submitted first time)

      // not the first time (PeriodReturnURL)
      if($_GET['is_recur']){
        $local_succ_times = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1", array(1 => array($recur->id, 'Integer')));
        if($input['RtnCode'] == 1 && $local_succ_times < $input['TotalSuccessTimes']){
          $contribution->contribution_status_id = 1; // Completed
          $c = $this->copyContribution($contribution, $ids['contributionRecur']);
        }
        elseif($input['RtnCode'] != 1){
          $contribution->contribution_status_id = 4; // Failed
          $c = $this->copyContribution($contribution, $ids['contributionRecur']);
        }
        else{
          // possible duplicate notify
          watchdog('civicrm_allpay', 'Possible duplicate notify, detect from TotalSuccessTimes: '.$input['TotalSuccessTimes']);
          $note .= "Possible duplicate notify. Already success $local_succ_times, but notify us only {$input['TotalSuccessTimes']} success execution.";
          $pass = FALSE;
        }
        if(!empty($c)){
          unset($objects['contribution']);
          $objects['contribution'] = $c;
          
          // update recurring object
          // never end if TotalSuccessTimes not excceed the ExecTimes
          if($input['TotalSuccessTimes'] == $recur->installments){
            $params['id'] = $recur->id;
            $params['modified_date'] = date('YmdHis');
            $params['end_date'] = date('YmdHis');
            $params['contribution_status_id'] = 3;
            CRM_Contribute_BAO_ContributionRecur::add($params, $null);
          }
        }
      }
      else{
      // is first time
        if($input['RtnCode'] == 1){
          $params['id'] = $recur->id;
          $params['start_date'] = date('YmdHis', strtotime($input['PaymentDate']));
          $params['contribution_status_id'] = 1;
          $params['modified_date'] = date('YmdHis');
          CRM_Contribute_BAO_ContributionRecur::add($params, $null);
        }
        else{
          CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($recur->id, CRM_Core_DAO::$_nullObject, 4);
        }
      }
      civicrm_allpay_record($objects['contribution']->id);
    }
      
    // process fail response
    if($input['RtnCode'] != 1 && $pass){
      $response_code = $input['RtnCode'];
      $response_msg = $input['RtnMsg'];
      $note .= "Failed $response_code: $response_msg";
      $this->failed( $objects, $transaction );
      $pass = FALSE;
    }

    return $pass;
  }

  function copyContribution(&$contrib, $rid){
    if(is_object($contrib)){
      $c = clone $contrib;
      unset($c->id);
      unset($c->receive_date);
      unset($c->trxn_id);
      unset($c->invoice_id);
      unset($c->receipt_id);
      $time = strtotime(self::$_input['ProcessDate']);
      $c->receive_date = date('YmdHis', $time);
      $c->created_date = date('YmdHis');
      $c->save();
      CRM_Contribute_BAO_ContributionRecur::syncContribute($rid, $c->id);
      return $c;
    }
    return FALSE;
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

}
