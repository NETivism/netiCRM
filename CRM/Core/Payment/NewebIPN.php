<?php
require_once 'CRM/Core/Payment/BaseIPN.php';
require_once "CRM/Core/DAO.php";

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
        }
        else{
          $month = $month_now;
          $cycle_day = $day_now;
        }
        $year = $month_now == 12 ? date('Y') + 1  : date('Y');
        $next_recur = mktime(0,0,0, $month, $cycle_day, $year);
        if($recur->installments){
          $end_recur = strtotime('+'.$installments_total.' month', $next_recur);
          $end_recur = mktime(0,0,0, date('n', $end_recur), $cycle_day, date('Y', $end_recur));
          $recur->end_date = date('YmdHis', $end_recur);
        }

        $recur->next_sched_contribution = date('YmdHis', $next_recur);
        $recur->start_date = $recur->next_sched_contribution;
        $recur->cycle_day = $cycle_day;
        $recur->save();
        CRM_Core_Error::debug_log_message( "Done the recurring object save." );
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contribution_neweb_recur (recur_id,order_num,cycle) VALUES ($recur->id, $order_num, 0)");

        //send recurring Notification email for user
        require_once 'CRM/Contribute/BAO/ContributionPage.php';
        CRM_Core_Error::debug_log_message( "Start to send recurring notify" );
        //CRM_Contribute_BAO_ContributionPage::recurringNotify( 'START', $ids['contact'], $ids['contributionPage'], $recur);
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


    function main( $component = 'contribute' , $input = NULL, $ids = NULL, $objects = NULL) {
      require_once 'CRM/Utils/Request.php';
      
      if(!$input){
        $input['component'] = $component;
        $this->getInput( $input);
      }

      if(!$ids){
        // get the contribution and contact ids from the GET params
        $ids['contact'] = self::retrieve( 'contactID', 'Integer', 'GET' , true);
        $ids['contribution'] = self::retrieve( 'contributionID', 'Integer', 'GET' , true);
        if ( $component == 'event' ) {
          $ids['event'] = self::retrieve( 'eventID'      , 'Integer', 'GET', true );
          $ids['participant'] = self::retrieve( 'participantID', 'Integer', 'GET', true );
        } else {
          // get the optional ids
          $ids['membership'] = self::retrieve( 'membershipID'       , 'Integer', 'GET', false );
          $ids['contributionRecur'] = self::retrieve( 'contributionRecurID', 'Integer', 'GET', false );
          $ids['contributionPage'] = self::retrieve( 'contributionPageID' , 'Integer', 'GET', false );
          $ids['related_contact'] = self::retrieve( 'relatedContactID'   , 'Integer', 'GET', false );
          $ids['onbehalf_dupe_alert'] = self::retrieve( 'onBehalfDupeAlert'  , 'Integer', 'GET', false );
        }
      }

      if(!$objects){
        $objects = array();
      }
      
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

