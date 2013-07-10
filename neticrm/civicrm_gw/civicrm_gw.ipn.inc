<?php
civicrm_initialize();
require_once 'CRM/Core/Payment/BaseIPN.php';

/**
 * Helper function for receive pingback from payment server
 */
function civicrm_gw_ipn($instrument){
  // detect variables
  if(empty($_POST)){
    watchdog("civicrm_gw", "Could not find POST data from payment server");
  }
  else{
    if($component = $_GET['module']){
      $ipn = new CRM_Core_Payment_GWIPN();
      $ipn->main($component, $instrument);
    }
    else{
      watchdog('civicrm_gw', "Could not get module name from request url");
    }
  }
}

class CRM_Core_Payment_GWIPN extends CRM_Core_Payment_BaseIPN {
  static $payment_processor= null;
  function __construct( ) {
    parent::__construct( );
  }

  function main(  $component = 'contribute', $instrument){
    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $this->getInput($input);
    $this->getIds($ids, $component);
    $input['component'] = $component;
    $qfKey = $_GET['qfKey'] ? $_GET['qfKey'] : $input['od_hoho']['qfKey'];
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
    $this->saveRecord($_GET['cid'], $input);
    
    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$payment_processor =& $objects['paymentProcessor'];
    if($objects['contribution']->contribution_status_id == 1 || $instrument == 'unionpay'){
      // already completed, or unionpay, skip and redirect to thank you page
      if($qfKey){
        drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
      }
      else{
        echo 'OK';
      }
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
        if(!$_GET['bg']){
          drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
        }
        else{
          echo 'OK';
        }
      }
      else{
        $note .= ts('Failed')."\n";
        drupal_set_message(ts("Failed").":".$input['response_msg']."({$input['response_code']})", 'error');
        $this->addNote($note, $objects['contribution']);
      }
    }
    
    // error stage: doesn't goto and not the background posturl
    if(!$_GET['bg']){
      $error_base_url =  $component == 'event' ? 'civicrm/event/confirm' : 'civicrm/contribute/transact';
      $error_argument = $component == 'event' ? "reset=1&cc=fail&participantId={$ids['participant']}" : "_qf_Main_display=1&cancel=1&qfKey=$qfKey";
      drupal_goto($error_base_url, $error_argument);
    }
  }

  function saveRecord($cid, &$input){
    $save = array(
      'succ',
      'response_code', 'response_msg', 'auth_code', 'card4no', // credit card
      'payno', 'payfrom', 'payer_bank', 'payer_acc', 'paid_date', // non credit card
      'proc_datetime', 'expire_datetime', // special case
      '',
    );
    foreach($save as $n){
      if($n == 'proc_datetiem'){
        if($input['proc_date'] && $input['proc_time']){
          $record[$n] = $input['proc_date'] . $input['proc_time'] .' +0800';
        }
      }
      elseif($n == 'expire_datetime' && $input['expire_date']){
        $record[$n] = $input['expire_date'] .'000000 +0800';
      }
      elseif($input[$n]){
        $record[$n] = $input[$n];
      }
    }
    $record['cid'] = $cid;
    $record['data'] = serialize($input);
    drupal_write_record('civicrm_contribution_gw', $record, 'cid');
  }

  function getInput( &$input) {
    $input = $_POST;

    // this will fix receipt amount missing issue.
    $input['amount'] = $input['amount'] ? $input['amount'] : $input['amt'];

    if($input['od_hoho']) {
      $hoho = str_replace('<BR>', '<br>', $input['od_hoho']);
      $hoho_ary = explode('<br>', trim($hoho, '<br>'));
      $i = 0;
      $input['od_hoho'] = array();
      foreach($hoho_ary as $h){
        if($i==1){
          $h = str_replace('ha: ', '', $h);
          list($k, $v) = explode(':',$h);
          $k = trim($k);
          $v = trim($v);
          $input['od_hoho'][$k] = $v;
        }
        $i++;
      }
    }
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

  // Greenworld check 
  function gwEcpayCheck($i){
    $serial = $i['process_date'].$i['process_time'].$i['gwsr'];
    $response = file("https://ecpay.com.tw/g_recheck.php?key={$i['rech_key']}&serial={$serial}&amt={$i['amount']}");
    parse_str($response[0]);
    if($valid==1){
      return TRUE;
    }
    else{
      $error = array(
        0 => 'unknown',
        2 => 'Time error',
        3 => 'Amount error',
      );
      watchdog("civicrm_gw", "Checksum Error:". $error[$valid]);
      return FALSE;
    }
  }

  /**
   * Validation success message
   *
   * Borrow from ecbank api.
   */
  function gwEcbankCheck(&$input){
    $key = self::$payment_processor['subject'];
    
    $serial = trim($input['proc_date'].$input['proc_time'].$input['tsr']);
    $tac = trim($input['tac']);
    $ws_url = 'https://ecbank.com.tw/web_service/get_outmac_valid.php?key='.$key.
              '&serial='.$serial.
              '&tac='.$tac;
    $pass = file_get_contents($ws_url);
    return $pass;  
  }

  function validateOthers( &$input, &$ids, &$objects, &$transaction, &$note){
    $contribution = &$objects['contribution'];
    $pass = TRUE;
    
    // check contribution id matches
    if ( $contribution->id != $input['od_sob'] ) {
      watchdog("civicrm_gw", "OrderNumber values doesn't match between database and IPN request" );
      $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request")."\n";
      $pass = FALSE;
    } 

    // check amount
    $amount = $input['amount'] ? $input['amount'] : $input['amt'];
    if ( $contribution->total_amount != $amount ) {
      watchdog("civicrm_gw", "Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // non-credit card validation
    if($input['payment_type']){
      if(! $this->gwEcbankCheck($input)) {
        $note .= ts("Failuare: Transaction number and system response number doesn't match. Please contact us for further assistant.")."\n";
        $this->failed( $objects, $transaction );
        $pass = FALSE;
      }
      
    }
    // credit card validation
    else{
      if(! $this->gwEcpayCheck($input) ){
        watchdog("civicrm_gw", "Checksum Error" );
        $note .= ts("Failuare: Transaction number and system response number doesn't match. Please contact us for further assistant.")."\n";
        $this->failed( $objects, $transaction );
        $pass = FALSE;
      }
    }

    // process fail response
    if(!$input['succ']){
      $response_code = $input['response_code'];
      $response_msg = $input['response_msg'];
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
