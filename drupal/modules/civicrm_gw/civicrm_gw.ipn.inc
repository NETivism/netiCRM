<?php
civicrm_initialize();
require_once 'CRM/Core/Payment/BaseIPN.php';

/**
 * Helper function for receive pingback from payment server
 */
function civicrm_gw_ipn(){
  // detect variables
  if(empty($_POST)){
    watchdog("civicrm_gw", "Could not find POST data from payment server");
  }
  else{
    $component = $_GET['module'];
    $ipn = new CRM_Core_Payment_GWIPN();
    switch ( $component) {
      case 'contribute':
       $ipn->main('contribute');
       break;
      case 'event':
       $ipn->main('event');
       break;
      default:
       watchdog('civicrm_gw', "Could not get module name from request url");
       break;
    }
  }
}

class CRM_Core_Payment_GWIPN extends CRM_Core_Payment_BaseIPN {
  static $_paymentProcessor = null;
  function __construct( ) {
    parent::__construct( );
  }

  function main(  $component = 'contribute'  ){
    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $this->getInput($input);
    $this->getIds($ids, $component);
    $input['component'] = $component;
    $qfKey = $_GET['qfKey'] ? $_GET['qfKey'] : $input['od_hoho']['qfKey'];
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

    // save response
    $this->saveResponse($_GET['cid'], $input);
    
    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$_paymentProcessor =& $objects['paymentProcessor'];
    if($objects['contribution']->contribution_status_id == 1){
      // already completed. skip and redirect to thank you page
      drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
    }
    else{
      // start validation
      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $transaction, $note) ){
        if($this->go($input, $ids, $objects, $transaction, $note) && !$_GET['bk_posturl']){
          drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
        }
      }
    }
    
    // error stage: doesn't goto and not the background posturl
    if(!$_GET['bk_posturl']){
      $error_base_url =  $component == 'event' ? 'civicrm/event/confirm' : 'civicrm/contribute/transact';
      $error_argument = $component == 'event' ? "reset=1&cc=fail&participantId={$ids['participant']}" : "_qf_Main_display=1&cancel=1&qfKey=$qfKey";
      drupal_goto($error_base_url, $error_argument);
    }
  }

  function saveResponse($cid, &$input){
    $sql = "REPLACE INTO civicrm_contribution_gw SET cid={$cid},succ={$input['succ']},response_code='{$input['response_code']}',response_msg='{$input['response_msg']}',auth_code='{$input['auth_code']}',data='%s'";
    $record = array(
      'cid' => $cid,
      'succ' => $input['succ'],
      'response_code' => $input['response_code'],
      'response_msg' => $input['response_msg'],
      'auth_code' => $input['auth_code'],
      'data' => serialize($input),
    );
    drupal_write_record('civicrm_contribution_gw', $record, 'cid');
  }

  function getInput( &$input) {
    $input = $_POST;

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
  function gwSpcheck($process_time,$gwsr,$amount,$spcheck,$check_sum){
    $T = $process_time+$gwsr+$amount;	
    $a = substr($T,0,1).substr($T,2,1).substr($T,4,1);
    $b = substr($T,1,1).substr($T,3,1).substr($T,5,1);
    $c = ( $check_sum % $T ) + $check_sum + $a + $b;

    if($spcheck == $c) {
      return TRUE;
    }
    else {
      return FALSE;
    }  
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
    if ($ids['contributionRecur']) {
      $contribution->total_amount = $input['amount'];
    }
    elseif ( $contribution->total_amount != $input['amount'] ) {
      watchdog("civicrm_gw", "Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // checksum
    if(! $this->gwSpcheck($input["process_time"],$input["gwsr"],$input["amount"],$input["spcheck"], $objects['paymentProcessor']['password']) ){
      watchdog("civicrm_gw", "Checksum Error" );
      $note .= ts("Failuare: Transaction number and system response number doesn't match. Please contact us for further assistant.")."\n";
      $this->failed( $objects, $transaction );
      $pass = FALSE;
    }

    // process fail response
    if(!$input['succ']){
      $response_code = $input['response_code'];
      $response_msg = $input['response_msg'];
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

  function go( &$input, &$ids, &$objects, &$transaction, $note) {
    $contribution =& $objects['contribution'];

    $contribution->receive_date = date('YmdHis');
    $input['trxn_id'] = $contribution->id;
    $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );

    $note .= ts('Completed')."\n";
    $this->addNote($note, $contribution);
    return TRUE;
  }
}
