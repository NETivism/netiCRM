<?php
civicrm_initialize();

/**
 * Helper function for receive pingback from payment server
 */
function civicrm_nccc_ipn(){
  // detect variables
  if(empty($_POST)){
    watchdog("civicrm_nccc", "Could not find POST data from payment server");
  }
  else{
    if($component = $_GET['module']){
      $ipn = new CRM_Core_Payment_NCCCIPN();
      $ipn->main($component);
    }
    else{
      watchdog('civicrm_nccc', "Could not get module name from request url");
    }
  }
}

class CRM_Core_Payment_NCCCIPN extends CRM_Core_Payment_BaseIPN {
  static $payment_processor= null;
  function __construct( ) {
    parent::__construct( );
  }

  function main(  $component = 'contribute'){
    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $this->getInput($input);
    $input['component'] = $component;
    $this->getIds($ids, $component);
    $qfKey = $_GET['qfKey'] ? $_GET['qfKey'] : '';
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

    // we use cid from url for mapping contribution
    $this->saveRecord($_GET['cid'], $input);
    
    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }

    // set global variable for paymentProcessor
    self::$payment_processor =& $objects['paymentProcessor'];
    if($objects['contribution']->contribution_status_id == 1){
      // already completed, or unionpay, skip and redirect to thank you page
      if($qfKey){
        drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
      }
      else{
        // do nothing for now. We should never have this page
      }
    }
    else{
      // start validation
      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $transaction, $note) ){
        $contribution =& $objects['contribution'];

        $contribution->receive_date = date('YmdHis', strtotime($input['transdatetime']));

        // we seperate orderid and contribution id
        $input['trxn_id'] = $input['orderid'];
        $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );
        $note .= ts('Completed')."\n";
        $this->addNote($note, $contribution);

        drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$qfKey}");
      }
      else{
        $this->addNote($note, $objects['contribution']);
        $this->failed($objects, $transaction);

        // Fail reason and fail page.
        drupal_goto('payment-nccc/err/'.$input['responsecode'], 'msg='.$input['responsemsg']);
      }
    }
  }

  function saveRecord($cid, &$input){
    $schema = drupal_get_schema_unprocessed('civicrm_nccc', 'civicrm_contribution_nccc');
    $record = array_intersect_key($input, $schema['fields']);
    $record['cid'] = $cid;
    $record['redeem'] = serialize($record['redeem']);

    drupal_write_record('civicrm_contribution_nccc', $record, 'cid');
  }

  function getInput( &$input) {
    foreach($_POST as $k => $v){
      // skip empty value
      if($v === '') continue;

      $key = strtolower($k);
      if(strpos($key, 'redeem') !== FALSE){
        $input['redeem'][$key] = $v;
      }
      elseif($key == 'transdate' || $key == 'transtime'){
        continue;
      }
      elseif($key == 'responsemsg'){
        $v = iconv("big5","UTF-8", $v);
        $input[$key] = $v;
      }
      // we can't use mysql reserved word for column name
      elseif($key == 'foreign'){
        $input['foreigncard'] = $v;
      }
      else{
        $input[$key] = $v;
      }
    }

    $input['transdatetime'] = date('Y-m-d H:i:s', strtotime($_POST['TransDate'].$_POST['TransTime']));
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
    foreach($ids as $k => $v){
      if(empty($v)) unset($ids[$k]);
    }
  }


  function validateOthers( &$input, &$ids, &$objects, &$transaction, &$note){
    $contribution = &$objects['contribution'];
    $pass = TRUE;
    
    // check contribution id matches
    if ( $contribution->trxn_id != $input['orderid'] ) {
      watchdog("civicrm_nccc", "{$contribution->trxn_id}: Orderid doesn't match in trxn_id" );
      $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request")."\n";
      $pass = FALSE;
    } 

    // check amount
    $amount = $input['transamt'];
    if ( $contribution->total_amount != $amount ) {
      watchdog("civicrm_nccc", "{$contribution->trxn_id}: Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // process success response
    if($input['responsecode'] == '00' || $input['responsecode'] == '08' || $input['responsecode'] == '11'){
      $tc = $input['approvecode'];
      $rc = $input['responsecode'];
      $note .= "Response code / approve code: {$rc}/{$tc}\n";
      $note .= "Message: ".$rmsg."\n";
    }
    // process fail response
    else{
      $tc = $input['approvecode'];
      $rc = $input['responsecode'];
      $rmsg = $input['responsemsg'];
      $this->failed( $objects, $transaction );
      watchdog("civicrm_nccc", "{$contribution->trxn_id}: Transaction failed from nccc server response." );
      $note .= ts("Transaction failed from remote response")."\n";
      $note .= "Error response code / approve code: {$rc}/{$tc}\n";
      $note .= "Error message: ".$rmsg."\n";
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
