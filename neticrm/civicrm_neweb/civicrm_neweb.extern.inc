<?php
civicrm_initialize();
require_once 'CRM/Core/Payment/BaseIPN.php';
require_once "CRM/Core/DAO.php";

function civicrm_neweb_ipn(){
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Contribute/DAO/Contribution.php';

  // process the real contribution verify
  require_once 'CRM/Utils/Array.php';
  require_once 'CRM/Core/Payment/NewebIPN.php';
  $NewebIPN = new CRM_Core_Payment_NewebIPN( );
  switch ( $_GET['module'] ) {
    case 'contribute':
      $NewebIPN->main( 'contribute' );
      break;
    case 'event':
      $NewebIPN->main( 'event' );
      break;
    default:
      watchdog('neweb', "Error for doesn't retrive module from url.", array(), WATCHDOG_WARNING);
      break;
  }
}

class CRM_Core_Payment_NewebIPN extends CRM_Core_Payment_BaseIPN {
    static $_paymentProcessor = null;

    function __construct( ) {
        parent::__construct( );
    }

    static function retrieve( $name, $type, $location = 'POST', $abort = true ) 
    {
        static $store = null;
        $value = CRM_Utils_Request::retrieve( $name, $type, $store,
                                              false, null, $location );
        if ( $abort && $value === null ) {
            CRM_Core_Error::debug_log_message( "Could not find an entry for $name in $location" );
            echo "Failure: Missing Parameter";
            exit();
        }
        return $value;
    }

    function getInput( &$input) {
      $input['CheckSum'] = self::retrieve('CheckSum', 'String', 'POST', false);
      $input['PRC'] = self::retrieve('PRC', 'Int', 'POST', true);
      $input['SRC'] = self::retrieve('SRC', 'Int', 'POST', true);
      $input['ApprovalCode'] = self::retrieve('ApprovalCode', 'String', 'POST', false);
      $input['BankResponseCode'] = self::retrieve('BankResponseCode', 'String', 'POST', false);
      $input['MerchantNumber'] = self::retrieve('MerchantNumber', 'String', 'POST', true);
      $input['OrderNumber'] = self::retrieve('OrderNumber', 'String', 'POST', true);
      $input['Amount'] = self::retrieve('Amount', 'String', 'POST', true);
      $input['amount'] = $input['Amount'];
      $input['BatchNumber'] = self::retrieve('BatchNumber', 'String', 'POST', false);
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

    function recur( &$input, &$ids, &$objects) {
      // check contribution first.
      $contribution =& $objects['contribution'];
      $order_num = $input['OrderNumber'];
      $note = $note ? $note : '';
      $failed = false;

      if ( $contribution->total_amount != $input['amount'] ) {
        CRM_Core_Error::debug_log_message( "Amount values dont match between database and IPN request" );
        $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
        $failed = true;
      }
      else {
        $contribution->total_amount = $input['amount'];
      }

      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();

      $participant =& $objects['participant'];
      $membership  =& $objects['membership' ];

      $signature = $objects['paymentProcessor']['signature'];
      $checksum = md5($input['MerchantNumber'].$input['OrderNumber'].$input['PRC'].$input['SRC'].$signature.$input['Amount']);
      if($checksum != $input['CheckSum']){
        CRM_Core_Error::debug_log_message( "Checksum Error" );
        $note .= ts("Failuare: Transaction number and system response number doesn't match. Please contact us for further assistant.")."\n";
        $this->failed( $objects, $transaction );
        $failed = true;
      }

      if($input['PRC'] || $input['SRC']){
        $error = civicrm_neweb_response($input['PRC'], $input['SRC'], $input['BankResponseCode'], 'detail');
        $note .= implode("\n",$error);
        $note .= " (Error code: PRC-{$input['PRC']},SRC-{$input['SRC']},BRC-{$input['BRC']})\n";
        $this->failed( $objects, $transaction );
        $failed = true;
      }
      $this->addNote($note, $contribution);

      // start recuring
      $recur =& $objects['contributionRecur'];
      if($failed){
        CRM_Core_Error::debug_log_message( "Cancel recurring immediately." );
        $recur->cancel_date = date('YmdHis');
        $recur->save();
      }
      else{
        require_once 'CRM/Core/Payment.php';
        CRM_Core_Error::debug_log_message( "Start building recurring object." );

        // caculate date of recurring contrib
        $time = time();
        $now = date( 'YmdHis', $time);
        // fix dates that already exist
        $dates = array('create', 'start', 'end', 'cancel', 'modified');
        foreach($dates as $date) {
          $name = "{$date}_date";
          if ( $recur->$name ) {
            $recur->$name = CRM_Utils_Date::isoToMysql( $recur->$name );
          }
        }

        // building recurring object stuff
        $recur->processor_id = $objects['paymentProcessor']->id;

        // caculate end_date
        $recur->create_date = $recur->create_date ? $recur->create_date : $now;
        $recur->modified_date =  $now;
        $installments_total = $recur->installments - 1;

        // every recuring contrib start on next month
        $month_now = date('n');
        $day_now = date('j') + 1;
        if($day_now > 25 ){
          $month = $month_now == 12 ? 1 : $month_now +1;
          $cycle_day = 1;
          $year = $month_now == 12 ? date('Y') + 1  : date('Y');
        }
        else{
          $month = $month_now;
          $cycle_day = $day_now;
          $year = date('Y');
        }
        $next_recur = mktime(0,0,0, $month, $cycle_day, $year);
        if($recur->installments){
          $end_recur = strtotime('+'.$installments_total.' month', $next_recur);
          $end_recur = mktime(0,0,0, date('n', $end_recur), $cycle_day, date('Y', $end_recur));
          $recur->end_date = date('YmdHis', $end_recur);
        }

        $recur->next_sched_contribution = date('YmdHis', $next_recur);
        $recur->start_date = $recur->next_sched_contribution;
        $recur->cycle_day = $cycle_day;
        $recur->contribution_status_id = 2;
        $recur->save();
        CRM_Core_Error::debug_log_message( "Done the recurring object save." );
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_neweb_recur (recur_id,order_num,cycle) VALUES ($recur->id, $order_num, 0)");

        //recurring Notification email for user
        require_once 'CRM/Contribute/BAO/ContributionPage.php';
        CRM_Core_Error::debug_log_message( "Start to send recurring notify" );
        //CRM_Contribute_BAO_ContributionPage::recurringNotify( 'START', $ids['contact'], $ids['contributionPage'], $recur);
        
        // now, complete the transaction
        $contribution =& $objects['contribution'];
        $contribution->receive_date = date('YmdHis');
        $input['trxn_id'] = $input['OrderNumber'];
        $note .= ts('Completed')."\n";
        $this->completeTransaction( $input, $ids, $objects, $transaction);
        $this->addNote($note, $contribution);
      }
    }

    function go( &$input, &$ids, &$objects) {
      $contribution =& $objects['contribution'];
      $order_num = $contribution->id;
      $note = $note ? $note : '';
      $failed = false;

      if ( $order_num != $input['OrderNumber'] ) {
        CRM_Core_Error::debug_log_message( "OrderNumber values doesn't match between database and IPN request" );
        $note .= ts("Failuare: OrderNumber values doesn't match between database and IPN request")."\n";
        $failed = true;
      } 

      if ( $contribution->total_amount != $input['amount'] ) {
        CRM_Core_Error::debug_log_message( "Amount values dont match between database and IPN request" );
        $note .= ts("Failuare: Amount values dont match between database and IPN request")."\n";
        $failed = true;
      }
      else {
        $contribution->total_amount = $input['amount'];
      }

      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();

      $participant =& $objects['participant'];
      $membership  =& $objects['membership' ];

      $signature = $objects['paymentProcessor']['signature'];
      $checksum = md5($input['MerchantNumber'].$input['OrderNumber'].$input['PRC'].$input['SRC'].$signature.$input['Amount']);
      if($checksum != $input['CheckSum']){
        CRM_Core_Error::debug_log_message( "Checksum Error" );
        $note .= ts("Failuare: Transaction number and system response number doesn't match. Please contact us for further assistant.")."\n";
        $this->failed( $objects, $transaction );
        $failed = true;
      }

      if($input['PRC'] || $input['SRC']){
        $error = civicrm_neweb_response($input['PRC'], $input['SRC'], $input['BankResponseCode'], 'detail');
        $note .= implode("\n",$error);
        $note .= " (Error code: PRC-{$input['PRC']},SRC-{$input['SRC']},BRC-{$input['BRC']})\n";
        $this->failed( $objects, $transaction );
        $failed = true;
      }

      if(!$failed){
        // check if contribution is already completed, if so we ignore this ipn
        $contribution->receive_date = date('YmdHis');
        $input['trxn_id'] = $input['OrderNumber'];
        if ( $contribution->contribution_status_id == 1 ) {
            $transaction->commit();
            CRM_Core_Error::debug_log_message( "returning since contribution has already been handled" );
            $note .= ts('Duplicate submitting. This aontribution has already been handled.')."\n";
            $return = true;
        }
        else{
          $note .= ts('Completed')."\n";
          $this->completeTransaction( $input, $ids, $objects, $transaction);
        }
      }

      $this->addNote($note, $contribution);

      return $return;
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

    function main( $component = 'contribute' , $input = NULL, $ids = NULL, $objects = NULL) {
      require_once 'CRM/Utils/Request.php';
      $objects = $ids = $input = array();
      
      if(!$input){
        $input['component'] = $component;
        $this->getInput( $input);
      }

      if(!$ids){
        $this->getIds($ids, $component);
      }
       
      // save record
      $record = array(
        'cid' => $ids['contribution'],
        'prc' => $input['PRC'],
        'src' => $input['SRC'],
        'bankrc' => $input['BankResponseCode'],
        'approvalcode' => $input['ApprovalCode'],
      );
      drupal_write_record('civicrm_contribution_neweb', $record, 'cid');

      if ( ! $this->validateData( $input, $ids, $objects ) ) {
        return false;
      }

      self::$_paymentProcessor =& $objects['paymentProcessor'];
      if($ids['contributionRecur'] && $objects['contribution']->contribution_recur_id ){
        return $this->recur($input, $ids, $objects);
      }
      else{
        return $this->go($input, $ids, $objects);
      }
    }
}

function dwd($in){
  ob_start();
  print '<pre>';
  print_r($in);
  print '</pre>';
  $c = ob_get_contents();
  ob_end_flush();
  watchdog('civicrm_neweb', $c);
}


function civicrm_neweb_response($prc, $src, $brc = NULL, $mode = 'normal'){
  if($mode == 'detail'){
    $error[$prc] = civicrm_neweb_prc($prc);
    $error[$brc] = civicrm_neweb_prc($brc);
  }
  else{
    $error['nopaid'] = ts("Transaction failed. You won't be doing any charge of this transaction. Below is the detail of this failed transaction:");
    if($prc == 8){
  "contribution";
      $error[$prc] = civicrm_neweb_prc($prc);
      $error[$prc] .= ': '.ts("This may occurred because you press the back button of browser. You can ignore this transaction, and try <a href='%1'>create a new one.</a>.", array($dup_link));
    }
    elseif($prc == 52){
      $error[$prc] = civicrm_neweb_prc($prc);
      if($brc){
        $error[$prc] .= ": ".civicrm_neweb_brc($brc);
      }
    }
    elseif($prc == 34){
      $error[$prc] = civicrm_neweb_prc($prc);
      if($brc){
        $error[$prc] .= ": ".civicrm_neweb_brc($brc);
      }
    }
    else{
      $error['system'] = ts("Network or system error. Please try again a minutes later, if you still can't success, please contact us for further assistance.");
      if($brc){
        $error[$brc] = civicrm_neweb_brc($brc);
      }
    }
  }
  return $error;
}

// Bank response	
function civicrm_neweb_brc($c = NULL){
  static $code = array();

  $code['00'] = ts('BRC: Approved or completed successfully ');
  $code['01'] = ts('BRC: Refer to card issuer');
  $code['02'] = ts('BRC: Refer to card issuer\'s special conditions');
  $code['03'] = ts('BRC: Invalid merchant');
  $code['04'] = ts('BRC: Pick-up card');
  $code['05'] = ts('BRC: Do not honour');
  $code['06'] = ts('BRC: Error');
  $code['07'] = ts('BRC: Pick-up card, special condition');
	$code['08'] = ts('BRC: Honour with identification');
	$code['11'] = ts('BRC: Approved(VIP)');
  $code['12'] = ts('BRC: Invalid transaction');
  $code['13'] = ts('BRC: Invalid amount');
  $code['14'] = ts('BRC: Invalid card number (no such number)');
  $code['15'] = ts('BRC: No such issuer');
  $code['19'] = ts('BRC: Re-Enter Transaction');
  $code['21'] = ts('BRC: No Action Taken (Unable back out prior trans)');
  $code['25'] = ts('BRC: Unable to Locate Record in File');
  $code['28'] = ts('BRC: File Temporarily not Available for Update or Inquiry');
  $code['30'] = ts('BRC: Format error');
	$code['31'] = ts('BRC: Bank not supported by switch');
  $code['33'] = ts('BRC: Expired card');
	$code['36'] = ts('BRC: Restricted card');
	$code['38'] = ts('BRC: Allowable PIN tries exceeded');
  $code['41'] = ts('BRC: Lost card');
  $code['43'] = ts('BRC: Stolen card, pick-up');
  $code['51'] = ts('BRC: Not sufficient funds');
  $code['54'] = ts('BRC: Expired card');
  $code['55'] = ts('BRC: Incorrect personal identification number (PIN)');
	$code['56'] = ts('BRC: No card record');
  $code['57'] = ts('BRC: Transaction not permitted to cardholder');
  $code['61'] = ts('BRC: Exceeds withdrawal amount limit');
  $code['62'] = ts('BRC: Restricted card');
  $code['65'] = ts('BRC: Exceeds withdrawal frequency limit');
	$code['67'] = ts('BRC: decline Exceeds withdrawal frequency limit Hart capture (requires that card be picked up at the ATM)');
	$code['68'] = ts('BRC: Response received too late');
  $code['75'] = ts('BRC: Allowable number of PIN exceeded');
	$code['76'] = ts('BRC: Unable to Locate Previous Message');
  $code['80'] = ts('BRC: Invalid Date');
  $code['81'] = ts('BRC: Cryptographic Error Found in PIN or CVV');
  $code['82'] = ts('BRC: Incorrect CVV');
  $code['85'] = ts('BRC: No Reason To Decline a Request for AddressVerification');
	$code['87'] = ts('BRC: Bad track 2 (reserved for BASE24 use)');
	$code['88'] = ts('BRC: Reserved for private use');
	$code['89'] = ts('BRC: System error (reserved for BASE24 use)');
	$code['90'] = ts('BRC: Cutoff is in process (switch ending a day\'s business and starting the next. Transaction can be sent again in a few minutes)');
  $code['91'] = ts('BRC: Issuer or switch is inoperative');
	$code['92'] = ts('BRC: Financial institution or intermediate  network facility cannot be found for routing');
  $code['93'] = ts('BRC: Transaction cannot be Completed Violation of Law');
  $code['94'] = ts('BRC: Duplicate transmission');
  $code['96'] = ts('BRC: System malfunction');
  $code['99'] = ts('BRC: Line Busy');
  $code['IE'] = ts('BRC: ID Error');
  
  if($c){
    return $code[$c];
  }
  else{
    return $code;
  }
}


// Main Response
function civicrm_neweb_prc($c = NULL){
  static $code = array();

  $code['0'] = ts('PRC: operation success');
  $code['1'] = ts('PRC: operation pending');
  $code['2'] = ts('PRC: undefined object');
  $code['3'] = ts('PRC: parameter not found');
  $code['4'] = ts('PRC: parameter too short');
  $code['5'] = ts('PRC: parameter too long');
  $code['6'] = ts('PRC: parameter format error');
  $code['7'] = ts('PRC: parameter value error');
  $code['8'] = ts('PRC: duplicate object');
  $code['9'] = ts('PRC: parameter mismatch');
  $code['10'] = ts('PRC: input error');
  $code['11'] = ts('PRC: verb not valid in present state');
  $code['12'] = ts('PRC: communication error');
  $code['13'] = ts('PRC: internal etill error');
  $code['14'] = ts('PRC: database error');
  $code['15'] = ts('PRC: cassette error');
  $code['17'] = ts('PRC: unsupported API version');
  $code['18'] = ts('PRC: obsolete API version');
  $code['19'] = ts('PRC: autoapprove failed');
  $code['20'] = ts('PRC: autodeposit failed');
  $code['21'] = ts('PRC: cassette not running');
  $code['22'] = ts('PRC: cassette not valid');
  $code['23'] = ts('PRC: unsupported in sysplex');
  $code['24'] = ts('PRC: parameter null value');
  $code['30'] = ts('PRC: XML error');
  $code['31'] = ts('PRC: corequisite parameter not found');
  $code['32'] = ts('PRC: invalid parameter combination');
  $code['33'] = ts('PRC: batch error');
  $code['34'] = ts('PRC: financial failure');
  $code['43'] = ts('PRC: block black BIN');
  $code['44'] = ts('PRC: block foreign');
  $code['50'] = ts('PRC: servlet init error');
  $code['51'] = ts('PRC: authentication error');
  $code['52'] = ts('PRC: authorization error');
  $code['53'] = ts('PRC: unhandled exception');
  $code['54'] = ts('PRC: duplicate parameter value not allowed');
  $code['55'] = ts('PRC: command not supported');
  $code['56'] = ts('PRC: crypto error');
  $code['57'] = ts('PRC: not active');
  $code['58'] = ts('PRC: parameter not allowed');
  $code['59'] = ts('PRC: delete error');
  $code['60'] = ts('PRC: websphere');
  $code['61'] = ts('PRC: supported in sysplex admin only');
  $code['62'] = ts('PRC: realm');
  $code['32768'] = ts('PRC: missing API version');
  $code['-1'] = ts('PRC: dispathcer error');
  
  if($c){
    return $code[$c];
  }
  else{
    return $code;
  }
}
