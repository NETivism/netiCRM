<?php 
require_once 'CRM/Core/Payment/BaseIPN.php';

class CRM_Core_Payment_GWIPN extends CRM_Core_Payment_BaseIPN {
  static $_paymentProcessor = null;
  function __construct( ) {
    parent::__construct( );
  }

  static function retrieve( $name, $type, $location = 'POST', $abort = true ) {
    static $store = null;
    $value = CRM_Utils_Request::retrieve( $name, $type, $store, false, null, $location );
    if ( $abort && $value === null ) {
      CRM_Core_Error::debug_log_message( "Could not find an entry for $name in $location" );
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }

  
  function error($errorCode = null, $errorMessage = null) {
    $e =& CRM_Core_Error::singleton( );
    if ( $errorCode ) {
      $e->push( $errorCode, 0, null, $errorMessage );
    } else {
      $e->push( 9001, 0, null, 'Unknown System Error.' );
    }
    return $e;
  }


  function getInput( &$input) {
    $input['succ'] = self::retrieve('succ', 'Int', 'POST', false);
    $input['gwsr'] = self::retrieve('gwsr', 'String', 'POST', false);
    $input['response_code'] = self::retrieve('response_code', 'String', 'POST', false);
    $input['response_msg'] = self::retrieve('response_msg', 'String', 'POST', false);
    $input['process_date'] = self::retrieve('process_date', 'String', 'POST', false);
    $input['process_time'] = self::retrieve('process_time', 'String', 'POST', false);
    $input['od_sob'] = self::retrieve('od_sob', 'String', 'POST', false);
    $input['auth_code'] = self::retrieve('auth_code', 'String', 'POST', false);
    $input['amount'] = self::retrieve('amount', 'Int', 'POST', false);
    $input['od_hoho'] = self::retrieve('od_hoho', 'String', 'POST', false);
    $input['eci'] = self::retrieve('eci', 'Int', 'POST', false);
    $input['red_dan'] = self::retrieve('red_dan', 'Int', 'POST', false);
    $input['red_de_amt'] = self::retrieve('red_de_amt', 'Int', 'POST', false);
    $input['red_ok_amt'] = self::retrieve('red_ok_amt', 'Int', 'POST', false);
    $input['red_yet'] = self::retrieve('red_yet', 'Int', 'POST', false);
    $input['inspect'] = self::retrieve('inspect', 'String', 'POST', false);
    $input['spcheck'] = self::retrieve('spcheck', 'Int', 'POST', false);
    $input['card4no'] = self::retrieve('card4no', 'String', 'POST', false);
    $input['card6no'] = self::retrieve('card6no', 'String', 'POST', false);

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
    $bk_posturl = self::retrieve( 'bk_posturl', 'Integer', 'GET' , false);
    $ids['contact'] = self::retrieve( 'contactID', 'Integer', 'GET' , true);
    $ids['contribution'] = self::retrieve( 'contributionID', 'Integer', 'GET' , true);
    if ( $component == 'event' ) {
      $ids['event'] = self::retrieve( 'eventID'      , 'Integer', 'GET', true );
      $ids['participant'] = self::retrieve( 'participantID', 'Integer', 'GET', true );
    }
    else {
      $ids['membership'] = self::retrieve( 'membershipID'       , 'Integer', 'GET', false );
      $ids['contributionRecur'] = self::retrieve( 'contributionRecurID', 'Integer', 'GET', false );
      $ids['contributionPage'] = self::retrieve( 'contributionPageID' , 'Integer', 'GET', false );
      $ids['related_contact'] = self::retrieve( 'relatedContactID'   , 'Integer', 'GET', false );
      $ids['onbehalf_dupe_alert'] = self::retrieve( 'onBehalfDupeAlert'  , 'Integer', 'GET', false );
    }
  }

  // Greenworld check 
  function gwSpcheck($process_time,$gwsr,$amount,$spcheck,$check_sum) {    
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
      CRM_Core_Error::debug_log_message( "OrderNumber values doesn't match between database and IPN request" );
      $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request")."\n";
      $pass = FALSE;
    } 

    // check amount
    if ($ids['contributionRecur']) {
      $contribution->total_amount = $input['amount'];
    }
    elseif ( $contribution->total_amount != $input['amount'] ) {
      CRM_Core_Error::debug_log_message( "Amount values dont match between database and IPN request" );
      $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
      $pass = FALSE;
    }

    // checksum
    $signature = $objects['paymentProcessor']['signature'];
    if(! $this->gwSpcheck($input["process_time"],$input["gwsr"],$input["amount"],$input["spcheck"], $signature) ){
      CRM_Core_Error::debug_log_message( "Checksum Error" );
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

    //$contribution->receive_date = date('YmdHis');
    $input['trxn_id'] = $contribution->id;
    $this->completeTransaction( $input, $ids, $objects, $transaction, $recur );

    $note .= ts('Completed')."\n";
    $this->addNote($note, $contribution);
    return TRUE;
  }

  function main(  $component = 'contribute'  ){
    $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
    $qfKey = $_GET['qfKey'] ? $_GET['qfKey'] : $input['od_hoho']['qfKey'];

    // get the contribution and contact ids from the GET params
    require_once 'CRM/Utils/Request.php';
    $objects = $ids = $input = array();
    $input['component'] = $component;
    $this->getInput( $input);
    $this->getIds($ids, $component);
    
    // now, retrieve full object by validateData, or false fallback
    if ( ! $this->validateData( $input, $ids, $objects ) ) {
      return false;
    }
    // set global variable for paymentProcessor
    self::$_paymentProcessor =& $objects['paymentProcessor'];

    if($objects['contribution']->contribution_status_id == 1){
      // already completed. skip and redirect to thank you page
      $redirect = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=true&qfKey={$qfKey}", false, null, false);
    }
    else{
      // start validation
      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();
      $note = '';
      if( $this->validateOthers($input, $ids, $objects, $transaction, $note) ){
        if( $this->go($input, $ids, $objects, $transaction, $note) ){
          $redirect = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=true&qfKey={$qfKey}", false, null, false);
        }
      }
    }

    if($bk_posturl){
      echo 'Done.';
    }
    else{
      // provide error url
      $error_base_url =  $component == 'event' ? 'civicrm/event/confirm' : 'civicrm/contribute/transact';
      $error_argument = $component == 'event' ? "reset=1&cc=fail&participantId={$ids['participant']}" : "_qf_Main_display=1&cancel=1&qfKey=$qfKey";
      if(!$redirect){ // error or not success.
        $redirect = CRM_Utils_System::url($error_base_url, $error_argument, false, null, false);
        //$error = self::error($input['response_code'], $input['response_msg']);
      }
      CRM_Utils_System::redirect($redirect);
    }
  }
}
