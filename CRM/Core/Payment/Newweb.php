<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';
require_once 'CRM/Core/Payment/NewwebResponse.php';

class CRM_Core_Payment_Newweb extends CRM_Core_Payment {
    /**
     * mode of operation: live or test
     *
     * @var object
     * @static
     */
    static protected $_mode = null;

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;
    
    /**
     * Constructor
     *
     * @param string $mode the mode of operation: live or test
     *
     * @return void
     */
    function __construct( $mode, &$paymentProcessor ) {
      $this->_mode             = $mode;
      $this->_paymentProcessor = $paymentProcessor;
      $this->_processorName    = ts('Newweb');
      $config =& CRM_Core_Config::singleton( );
      $this->_config = $config;
    }

    /** 
     * singleton function used to manage this object 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return object 
     * @static 
     * 
     */ 
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new CRM_Core_Payment_Newweb( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }

    /** 
     * This function checks to see if we have the right config values 
     * 
     * @return string the error message if any 
     * @public 
     */ 
    function checkConfig( ) {
        $config = CRM_Core_Config::singleton( );

        $error = array( );

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $error[] = ts( 'User Name is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }

        if ( empty( $this->_paymentProcessor['password'] ) ) {
            $error[] = ts( 'Password is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( empty( $this->_paymentProcessor['signature'] ) ) {
            $error[] = ts( 'Signature is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( ! empty( $error ) ) {
            return implode( '<p>', $error );
        } else {
            return null;
        }
    }

    function setExpressCheckOut( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function getExpressCheckoutDetails( $token ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function doExpressCheckout( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function doDirectPayment( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) );
    }

    function doTransferCheckout(&$params, $component) {
      $component = strtolower( $component );
      if ( $component != 'contribute' && $component != 'event' ) {
        CRM_Core_Error::fatal( ts('Component is invalid') );
      }

      // to see what instrument for newweb
      $newweb_instrument_id = $params['newweb_instrument_id'];
      $newweb_instrument = $this->getInstrument($newweb_instrument_id);
      
      $is_pay_later = TRUE;
      switch($newweb_instrument){
        case 'Credit Card':
          $redirect = $this->newwebCreditCard($params, $component, $newweb_instrument);
          $is_pay_later = FALSE;
          break;
        case 'WEBATM':
        case 'ATM':
        case 'CS':
        case 'MMK':
        case 'ALIPAY':
          $redirect = $this->newwebEZPay($params, $component, $newweb_instrument);
          break;
      }

      // now process contribution to save some default value
      require_once 'CRM/Contribute/DAO/Contribution.php';
      $contribution =& new CRM_Contribute_DAO_Contribution();
      $contribution->id = $params['contributionID'];
      $contribution->find(true);
      if($contribution->payment_instrument_id != $params['newweb_instrument_id']){
        $contribution->payment_instrument_id = $params['newweb_instrument_id'];
      }
      if($contribution->is_pay_later != $is_pay_later){
        $contribution->is_pay_later = $is_pay_later;
      }
      $contribution->trxn_id = $params['contributionID'];
      $contribution->save();

      // record for thank you display
      $_SESSION['newweb']['trxn_id'] = $params['contributionID'];
      $_SESSION['newweb']['is_pay_later'] = $is_pay_later;
      $newweb_instrument_label = $this->getInstrument($newweb_instrument_id, 'label');
      $_SESSION['newweb']['payment_instrument'] = $newweb_instrument_label;

      // doing redirect
      CRM_Utils_System::redirect($redirect);
    }

    function newwebCreditCard(&$params, $component, $newweb_instrument){
      $config = $this->_config;
      $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
      $cancel_url = CRM_Utils_System::url($civi_base_url,"_qf_Confirm_display=true&qfKey={$params['qfKey']}",false, null, false );
      $return_url = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}",true, null, false );

      // notify url for receive payment result
      $notify_url = $config->userFrameworkResourceURL."extern/newwebipn.php?reset=1&contactID={$params['contactID']}"."&contributionID={$params['contributionID']}"."&module={$component}";

      if ( $component == 'event' ) {
        $notify_url .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
      }
      else {
        $membershipID = CRM_Utils_Array::value( 'membershipID', $params );
        if ( $membershipID ) {
          $notify_url .= "&membershipID=$membershipID";
        }
        $relatedContactID = CRM_Utils_Array::value( 'related_contact', $params );
        if ($relatedContactID) {
          $notify_url .= "&relatedContactID=$relatedContactID";
          $onBehalfDupeAlert = CRM_Utils_Array::value( 'onbehalf_dupe_alert', $params );
          if ($onBehalfDupeAlert) {
            $notify_url .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
          }
        }
      }
      // if recurring donations, add a few more items
      if ( !empty( $params['is_recur']) ) {
         if ($params['contributionRecurID']) {
           $notify_url .= "&contributionRecurID={$params['contributionRecurID']}&contributionPageID={$params['contributionPageID']}";
         }
         else {
           CRM_Core_Error::fatal( ts( 'Recurring contribution, but no database id' ) );
         }
      }
      else {
      }

      // building params
      $amount = $params['currencyID'] == 'TWD' && strstr($params['amount'], '.') ? substr($params['amount'], 0, strpos($params['amount'],'.')) .'.00' : $params['amount'];
      $name = function_exists('truncate_utf8') ? truncate_utf8($params['item_name'], 10) : $params['item_name'];

      $newweb_params = array(
        "MerchantNumber" => $this->_paymentProcessor['user_name'],
        "OrderNumber"    => $params['contributionID'],
        "Amount"         => $amount,
        "OrgOrderNumber" => $params['contributionID'],
        "ApproveFlag"    => 1,
        "DepositFlag"    => $params['is_recur'] ? 1 : 0,
        "Englishmode"    => 0,
        "OrderURL"       => $notify_url,
        "ReturnURL"      => $return_url,
        "checksum"       => md5($this->_paymentProcessor['user_name'].$params['contributionID'].$this->_paymentProcessor['signature'].$amount),
        "op"             => "AcceptPayment",
        "#action"        => $this->_paymentProcessor['url_site'],
        "#params"         => $params,
        "#paymentProcessor" => $this->_paymentProcessor,
        "#redirect" => $_SERVER['HTTP_REFERER'],
      );
      $_SESSION['newweb_form'] = $newweb_params;

      $redirect = CRM_Utils_System::url("civicrm_newweb?qfKey={$params['qfKey']}");
      return $redirect;
    }

    function newwebEZPay(&$params, $component, $newweb_instrument){
      require_once 'CRM/Contact/DAO/Contact.php';
      $contact =& new CRM_Contact_DAO_Contact( );
      $contact->id = $params['contact'];
      $contact->find(true);

      if(strpos($params['amount'],'.') ){
        $amount = substr($params['amount'], 0, strpos($params['amount'],'.'));
      }
      else{
        $amount = $params['amount'];
      }
      
      $post = array();
      $post['merchantnumber'] = $this->_paymentProcessor['password'];
      $post['ordernumber'] = $params['contributionID'];
      $post['amount'] = $amount;
      $post['paymenttype'] = $newweb_instrument;
      $post['paytitle'] = $params['item_name'];
      $post['bankid'] = '004';
      $post['duedate'] = date('Ymd', time()+86400*7);
      if($newweb_instrument == 'CS'){
        $post['payname'] = $params['last_name']." ".$params['first_name'];
        $post['payphone'] = preg_replace("/[^\d]+/i", $params['phone']);
      }
      $post['returnvalue'] = 1;
      $post['hash'] = md5($post['merchantnumber'].$this->_paymentProcessor['url_button'].$amount.$post['ordernumber']);
      $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
      $post['nexturl'] = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}",true, null, false );
    
      $post["#redirect"] = $_SERVER['HTTP_REFERER'];
      $post["#params"] = $params;
      $post["#action"] = rtrim($this->_paymentProcessor['url_api'],'/')."/Payment";
      $post["#paymentProcessor"] = $this->_paymentProcessor;
      $post['returnvalue'] = 0;
      $_SESSION['newweb_form'] = $post;
      $redirect = CRM_Utils_System::url("civicrm_newweb?qfKey={$params['qfKey']}&instrument=$newweb_instrument");   
      return $redirect;
      /*
      if($newweb_instrument == 'WEBATM' || $newweb_instrument == 'CS' || $newweb_instrument == 'MMK'){
        $post["#redirect"] = $_SERVER['HTTP_REFERER'];
        $post["#params"] = $params;
        $post["#action"] = rtrim($this->_paymentProcessor['url_api'],'/')."/Payment";
        $post["#paymentProcessor"] = $this->_paymentProcessor;
        $post['returnvalue'] = 0;
        $_SESSION['newweb_form'] = $post;
        $redirect = CRM_Utils_System::url("civicrm_newweb?qfKey={$params['qfKey']}&instrument=$newweb_instrument");   
        return $redirect;
      }
      else{
        $result = $this->postData($post);
      }

      if($result === FALSE){
        // false message here.
      }
      else{
        // checksum
        if($this->checkSum($result)){
          if($newweb_instrument == 'ATM'){
            $result_note = $newweb_instrument. ' ('.$result['bankid'].ts('Taiwan Bank').', '.ts('Account Number').': '.$result['virtualaccount'].')';
            $_SESSION['newweb']['payment_instrument'] = $result_note;
            $this->addNote($result_note, $params);
            dpm($result_note);
          }
        }
        else{
          dpm('error here');
        }
      }

      $redirect = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}",true,null,false);
      return $redirect;
      */
    }

    function vars2array($str){
      $vars = explode('&', $str);
      foreach($vars as $var){
        list($name, $value) = explode('=', $var, 2);
        if($name == 'errormessage'){
          $value = iconv("Big5","UTF-8",$value);
        }
        $params[$name] = $value;
      }
      return $params;
    }

    function vars2str($post){
      $array = array();
      foreach($post as $name => $value){
        if($value){
          $array[] = $name."=".urlencode($value);
        }
      }
      return implode('&', $array);
    }

    function postData($post, $type = 0){
      $postdata = $this->vars2str($post);
      $payment_url = rtrim($this->_paymentProcessor['url_api'],'/')."/Payment";
      $query_url = rtrim($this->_paymentProcessor['url_api'],'/')."/Query"; 

      $url = $type ? $query_url : $payment_url;

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
//      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
      $receive = curl_exec($ch);
      if(curl_errno($ch)){
        $ch2 = curl_init($url);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $postdata);
//        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch2, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
        $receive = curl_exec($ch2);
      }
      curl_close($ch);

      if($receive){
        $vars = $this->vars2array($receive);
        if($vars['rc'] == 70){
          $regetorder = curl_init($query_url);
          $post['operation'] = "regetorder";
          $postdata = $this->vars2str($post);
          curl_setopt($regetorder, CURLOPT_POST, 1);
          curl_setopt($regetorder, CURLOPT_POSTFIELDS, $postdata);
//          curl_setopt($regetorder, CURLOPT_FOLLOWLOCATION, 1);
          curl_setopt($regetorder, CURLOPT_HEADER, 0);
          curl_setopt($regetorder, CURLOPT_RETURNTRANSFER, 1);
          $receive2 = curl_exec($regetorder);
          curl_close($regetorder);
          $vars2 = $this->vars2array($receive2);
          return $vars2;
        }
        return $vars;
      }
      else{
        return FALSE;
      }
    }

    function checkSum($array){
      $checksum = $array['checksum'];
      unset($array['checksum']);
      foreach($array as $n => $v){
        $str .= $n."=".$v.'&';
      }
      $str .= 'code='.$this->_paymentProcessor['url_button'];
      if($checksum == md5($str)){
        return TRUE;
      }
      else{
        return FALSE;
      }
    }

    function addNote($note, &$params){
      require_once 'CRM/Core/BAO/Note.php';
      $note = date("Y/m/d H:i:s")." ". ts("Transaction record").": \n".$note."\n===============================\n";
      $note_exists = CRM_Core_BAO_Note::getNote( $params['contributionID'], 'civicrm_contribution' );
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
        'entity_id'     => $params['contributionID'],
        'contact_id'    => $params['contactID'],
      );
      CRM_Core_BAO_Note::add( $noteParams, $note_id );
    }

    function getInstrument($id = NULL, $type = 'name'){
      static $instruments;
      if(empty($instruments)){
        require_once "CRM/Core/DAO.php";
        $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_option_group cog INNER JOIN civicrm_option_value cov ON cog.id = cov.option_group_id WHERE cog.name LIKE 'payment_instrument' AND (cov.value = 1 OR cov.filter != 0 ) ORDER BY cov.value ASC");
        while($dao->fetch()){
          $instruments[$dao->value] = array('name' => $dao->name, 'label' => $dao->label);
        }
      }

      if(is_numeric($id)){
        return $instruments[$id][$type];
      }
      else{
        return $instruments;
      }
    }

    function notify($contact, $content){
    
    }

    function cron(){
      require_once 'CRM/Contribute/DAO/Contribution.php';
      require_once 'CRM/Core/Payment/BaseIPN.php';
      require_once 'CRM/Core/Transaction.php';
      require_once "CRM/Core/DAO.php";

      print "Strat to process:$this->_mode \n=========================== \n"; 

      $instruments = $this->getInstrument();
      unset($instruments[1]);
      $instrument_str = implode(',', array_keys($instruments));
      $time = date('YmdHis');
      $is_test = $this->_mode == 'test' ? 1 : 0;
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_status_id = 2 AND payment_instrument_id IN ($instrument_str) AND is_test = $is_test ORDER BY RAND() LIMIT 0, 6");
      while($dao->fetch()){
        $post = array();
        $post['merchantnumber'] = $this->_paymentProcessor['password'];
        $post['ordernumber'] = $dao->id;
        //$post['amount'] = (int)$dao->total_amount;
        //$post['paymenttype'] = $instruments[$dao->payment_instrument_id]['name'];
        //$post['status'] = 0;
        $post['operation'] = 'queryorders';
        $post['time'] = $time;
        $post['hash'] = md5($post['operation'].$this->_paymentProcessor['url_button'].$time);

        // initialize objects and ids
        $input = $object = $ids = $result = array();
        $note = '';
        $c =& new CRM_Contribute_DAO_Contribution();
        $c->id = $post['ordernumber'];
        $c->find(true);
        $note_array = array(
          'contributionID' => $c->id,
          'contactID' => $c->contact_id,
        );
        $ipn = & new CRM_Core_Payment_BaseIPN();
        $transaction = new CRM_Core_Transaction();
        $ids['contact'] = $c->contact_id;
        $ids['contribution'] = $c->id;
        $input['component'] = 'contribute'; // FIXME need to detect mode of contribute or event

        // fetch result and object
        $result = $this->postData($post, 1);
        // debug here
        print $c->id."\n";
        print_r($post);
        print_r($result);
        // 
        if($ipn->validateData($input, $ids, $objects) && $result){
          // check result
          if($result['rc'] == 0 && $result['status'] == 1){
            // after validate, start to complete some transaction
            $input['trxn_id'] = $c->trxn_id;
            $input['payment_instrument_id'] = $c->payment_instrument_id;
            $input['check_number'] = $result['writeoffnumber'];
            $input['amount'] = $result['amount'];
            if($result['timepaid']){
              $objects['contribution']->receive_date = $result['timepaid'];
            }
            else{
              $objects['contribution']->receive_date = date('YmdHis');
            }
            $ipn->completeTransaction($input, $ids, $objects, $transaction);

            // note here;
            $note .= ts("Serial number").": ".$result['serialnumber']."\n";
            $note .= ts("Payment Instrument").": ". $result['paymenttype'];
            $note .= ts("External order number").": ".$result['writeoffnumber']."\n";
            $note .= ts("Create date").": ".$result['timecreated']."\n";
            $note .= ts("Paid date").": ".$result['timepaid']."\n";
            $note .= ts("Pay count").": ".$result['paycount']."\n";
            $note .= ts("Completed");
            $this->addNote($note, $note_array);
          }
          elseif(!isset($result['status']) && $result['rc'] == 0) {
            // cancel contribution
            $input['reasonCode'] = ts('Overdue');
            $input['trxn_id'] = $c->trxn_id;
            $input['payment_instrument_id'] = $c->payment_instrument_id;
            $ipn->cancelled($objects, $transaction);
            $note .= ts("Canceled").": ".ts('Overdue')."\n";
            $this->addNote($note, $note_array);
          }
          elseif($result['rc']){
            // FIXME to see if cancel contribution
            $note .= ts("Error").": ".$result['rc']."/".$result['rc2']."\n";
            $this->addNote($note, $note_array);
          }
        }
      }
    }
}

