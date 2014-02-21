<?php
civicrm_initialize();
require_once 'CRM/Core/Payment/BaseIPN.php';

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
  static $payment_processor = null;
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
    civicrm_allpay_record($_GET['cid']);
    
    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$payment_processor =& $objects['paymentProcessor'];
    if($objects['contribution']->contribution_status_id == 1){
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

        $contribution->receive_date = date('YmdHis');
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
    $amount = $input['TradeAmt'];
    if ( round($contribution->total_amount) != $amount ) {
      watchdog("civicrm_allpay", "Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // allpay validation
    $mac = _civicrm_allpay_checkmacvalue($_POST, self::$payment_processor);
    if(!$input['CheckMacValue'] != $mac) {
      $note .= ts("Failuare: CheckMacValue not match. Contact system admin.")."\n";
      $this->failed( $objects, $transaction );
      $pass = FALSE;
    }
      
    // process fail response
    if($input['RtnCode'] != 1){
      $response_code = $input['RtnCode'];
      $response_msg = $input['RtnMsg'];
      $note .= "Failed $response_code: $response_msg";
      $this->failed( $objects, $transaction );
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

}
