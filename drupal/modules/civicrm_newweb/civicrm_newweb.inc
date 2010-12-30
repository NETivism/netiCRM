<?php
define('NEWWEB_TEST_HOST', '61.57.29.212');
define('NEWWEB_TEST_USER', 'rp_nncf');
define('NEWWEB_TEST_PASS', '');

define('NEWWEB_HOST', '');
define('NEWWEB_USER', '');
define('NEWWEB_PASS', '');

function civicrm_newweb_process_response($is_test){
  $now = time();
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewwebIPN.php';

  $p = new CRM_Core_BAO_PaymentProcessor();
  if($is_test){
    $payment = $p->getPayment(1,'test');
  }
  else{
    $payment = $p->getPayment(1,'live');
  }
  $newwebpath = _civicrm_newweb_path($is_test);

  $filename = 'RP_'.$payment['user_name'].'_'.date('Ymd',time()-86400).'.out';
  //$filename = 'RP_'.$payment['user_name'].'_'.'20101104'.'.out';
  $response_str = _civicrm_newweb_sslftp($payment['user_name'], $newwebpath.'/'.$filename, 0, $is_test);
  if($response_str === false){
    // error when download files
    print $error = "error in civicrm_newwweb_process_response: Can't download files. ";
    watchdog("CiviCRM", $error);
  }
  elseif($response_str){
    $response = _civicrm_newweb_decodeline($response_str, 'response');

    foreach($response as $k => $r){
      $sql = "UPDATE civicrm_contribution_newweb_recur SET response=$now WHERE recur_id = {$r['REFNUMBER']}";
      CRM_Core_DAO::executeQuery($sql);

      if($r['RESPONSE'] == 0){
        // record success proccessed
        // nothing to do here
        // just to keep record to prevent further transaction errors
      }
      else{
        // fail
        print $error = "error in civicrm_newwweb_process_response:". $r['REFNUMBER'] . ":" . $r['RESPONSE'];
        watchdog("CiviCRM", $error);
      }
    }
  }
  else{
    // have file but no response
    print $error = "error in civicrm_newwweb_process_response: No response today.";
    watchdog("CiviCRM", $error);
  }

  if($error){
    return FALSE;
  }
  else{
    return TRUE;
  }
  
}

function civicrm_newweb_process_transaction($is_test){
  $now = time();
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewwebIPN.php';
  require_once 'CRM/Contribute/DAO/Contribution.php';
  require_once 'CRM/Utils/Array.php';
  require_once 'CRM/Core/Payment/NewwebIPN.php';

  $p = new CRM_Core_BAO_PaymentProcessor();
  if($is_test){
    $payment = $p->getPayment(1,'test');
  }
  else{
    $payment = $p->getPayment(1,'live');
  }
  $newwebpath = _civicrm_newweb_path($is_test);

  $filename = 'RP_Trans_'.$payment['user_name'].'_'.date('Ymd').'.log';
  //$filename = 'RP_Trans_'.$payment['user_name'].'_'.'20101105'.'.log';
  $transaction_str = _civicrm_newweb_sslftp($payment['user_name'], $newwebpath.'/'.$filename, 0, $is_test);
  if($transaction_str === false){
    // error when download files
    print $error = "error in civicrm_newwweb_process_transaction: Can't download files. ";
    watchdog("CiviCRM", $error);
  }
  elseif($transaction_str){
    $trans = _civicrm_newweb_decodeline($transaction_str,'transaction');
    foreach($trans as $k => $t){
      $sql = "SELECT * FROM civicrm_contribution_recur c INNER JOIN civicrm_contribution_newweb_recur cnr ON c.id = cnr.recur_id WHERE c.id = {$t['REFNUMBER']}";
      $recur = CRM_Core_DAO::executeQuery($sql);
      $recur->fetch();

      $contrib =& new CRM_Contribute_DAO_Contribution();
      $contrib->id = $t['ORGORDERNUMBER'];
      if ( !$contrib->find( true ) ) { 
        exit();
      }   

      if($recur->id){
        if($recur->cycle == 0){
          // we have previous added contribution record
          $contribution = $contrib;
        }
        else{
          // we need to add new one.
          // create a contribution and then get it processed
          $contribution =& new CRM_Contribute_DAO_Contribution( );
          $contribution->contact_id = $contrib->contact;
          $contribution->contribution_type_id  = $contrib->contributionType;
          $contribution->contribution_page_id  = $contrib->contribution_page_id;
          $contribution->contribution_recur_id = $contrib->contribution_recur_id;
          $contribution->receive_date          = $now;
          $contribution->currency              = $contrib->currency;
          $contribution->payment_instrument_id = $contrib->payment_instrument_id;
          $contribution->amount_level          = $contrib->amount_level;
        }

        // now we have correct contribution object. procceed.

        // submit recurring items by ipn functions
        $input = $ids = array();
        $input = array(
          'component' => 'contribute',
          'PRC' => $t['PRC'],
          'SRC' => $t['SRC'],
          'ApprovalCode' => $t['APPROVECODE'],
          'BankResponseCode' => $t['BANKRESCODE'],
          'MerchantNumber' => $t['MERCHANTNUMBER'],
          'OrderNumber' => '',
          'Amount' => (int)$recur->amount,
          'amount' => (int)$recur->amount,
          '_recurring' => TRUE,
        );
        $ids = array(
          'contact' => $recur->contact_id,
          'contribution' => $t['ORGORDERNUMBER'],
          'contributionRecur' => $recur->id,
          'contributionPage' => $contrib->contribution_page_id,
        );
        $objects = array(
          'contribution' => $contribution, 
          'membership' => NULL,
          'participant' => NULL,
          'event' => NULL,
        );
        require_once 'CRM/Core/Transaction.php';
        $transaction = new CRM_Core_Transaction( );
        $IPN = new CRM_Core_Payment_NewwebIPN();

        // Check the response
        // Status: Complete
        if($t['PRC'] == 0 && $t['SRC'] == 0){
          $IPN->completeTransaction( $input, $ids, $objects, $transaction );
        }
        // Status: failed, cancel. We don't have retry mechnism.
        else{
          require_once "CRM/Core/Payment/NewwebResponse.php";
          $e = new CRM_Core_Payment_NewwebResponse;
          $err= $e->response($t['PRC'], $t['SRC']);
          $IPN->failed($objects, $transaction);
          $recur->cancel_date = date("Y-m-d H:i:s");
          $recur->save();
        }
      }
    }
    
  }
  else{
    // have file but no response
    print $error = "error in civicrm_newwweb_process_response: No response today.";
    watchdog("CiviCRM", $error);
  }

  if($error){
    return FALSE;
  }
  else{
    return TRUE;
  }
}

function civicrm_newweb_process_upload($is_test){
  $now = time();
  $nextday = mktime(6,0,0,date('n'),date('j'),date('Y'))+86400;
  $create_check = $now - 86400; // yesterday
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewwebIPN.php';
  $p = new CRM_Core_BAO_PaymentProcessor();
  if($is_test){
    $payment = $p->getPayment(1,'test');
  }
  else{
    $payment = $p->getPayment(1,'live');
  }

  // check first insert and recurring contributions.
  // need check nextday for support newweb only allow upload new transaction after today
  $sql = "SELECT * FROM civicrm_contribution_recur cr INNER JOIN civicrm_contribution_newweb_recur cnr ON cr.id = cnr.recur_id WHERE FROM_UNIXTIME($create_check) > cr.create_date && cr.end_date >= FROM_UNIXTIME($now) && cr.cancel_date IS NULL && cr.is_test = $is_test && cnr.cycle = 0 && cnr.upload = 0 ORDER BY cr.next_sched_contribution DESC";
  $recur = CRM_Core_DAO::executeQuery($sql);

  while( $recur->fetch() ){
    // only effect when submit first recurring contribution
    $contrib = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_recur_id = $recur->id ORDER BY id ASC");
    $contrib->fetch();
    // start to collect output file string
    $upload = array(
      'MERCHANTNUMBER' => $payment['user_name'],
      'REFNUMBER' => $recur->id,
      'ORDERNUMBER' => $contrib->id,
      'BRAND' => '',       // null for our status
      'EXPIREDATE' => '',  // null for our status
      'AMOUNT' => (int)$recur->amount,
      'REGULAR' => NEWWEB_RECUR_DAY,
      'ACTION' => 'New',
      'STATUS' => '01',
      'MODE' => 0, // 0 for month mode, 1 for date mode
    );
    $rows[$recur->id] = $upload;
    
    $upload_text = implode(',', $upload);
    CRM_Core_DAO::executeQuery($sql);
  }
  if(count($rows)){
    $data = _civicrm_newweb_encodeline($rows);
    $newwebpath = _civicrm_newweb_path($is_test);
    $filename = 'RP_'.$payment['user_name'].'_'.date('Ymd').'.dat';
    file_save_data($data, $newwebpath.'/'.$filename, FILE_EXISTS_REPLACE);
    $uploaded =  _civicrm_newweb_sslftp($payment['user_name'], $newwebpath.'/'.$filename, 1, $is_test);
    if($uploaded){
      $sql = "UPDATE civicrm_contribution_newweb_recur SET upload=$now WHERE recur_id IN (".implode(',', array_keys($rows)).")";
    }
    CRM_Core_DAO::executeQuery($sql);
    return $uploaded;
  }
  return false;
}

function _civicrm_newweb_sslftp($id, $localfile, $upload, $is_test){
  $dir = $upload ? 'import' : 'output';
  if($is_test){
    $cid = ftp_connect(NEWWEB_TEST_HOST);
    $login = ftp_login($cid, NEWWEB_TEST_USER, NEWWEB_TEST_PASS);
  }
  else{
    $cid = ftp_ssl_connect(NEWWEB_HOST);
    $login = ftp_login($cid, NEWWEB_USER, NEWWEB_PASS);
  }
  if($login){
    $path = $id.'/'.$dir;
    $remotefile = $path .'/'. basename($localfile);

    if($upload){
      $success = ftp_put($cid, $remotefile, $localfile, FTP_ASCII);
    }
    else{
      $success = ftp_get($cid, $localfile, $remotefile, FTP_ASCII);
    }

  }
  ftp_close($cid);
  if($success){
    if($upload){
      return true;
    }
    else{
      return file_get_contents($localfile);
    }
  }
  else{
    //retry ? or retry by next cron..
    return false;
  }
}
function _civicrm_newweb_encodeline($in){
  foreach($in as $line){
    $o .= implode(',', $line)."\n";
  }
  return rtrim($o, "\n");
}

/**
 * type: upload / response / transaction
 */
function _civicrm_newweb_decodeline($in, $type){
  $o = array();
  switch($type){
    case 'response':
      $key= array(
        'MERCHANTNUMBER',
        'REFNUMBER',
        'CARDNUMBER',
        'BRAND',
        'EXPIREDATE',
        'AMOUNT',
        'REGULAR',
        'ACTION',
        'STATUS',
        'MODE',
        'RESPONSE',
      );
      break;
    case 'transaction':
      $key= array(
        'MERCHANTNUMBER',
        'REFNUMBER',
        'ORGORDERNUMBER',
        'HTTPCODE',
        'PRC',
        'SRC',
        'BANKRESCODE',
        'APPROVECODE',
        'BATCHNUMBER',
        'MERCHANTORDERNUMBER',
        'MODE',
      );
      break;
  }
  $lines = explode("\n",$in);
  foreach($lines as $l){
    if($l){ // check if empty line
      $value = explode(",", $l);
      $o[] = array_combine($key, $value);
    }
  }
  return $o;
}
function _civicrm_newweb_path($is_test){
  if($is_test){
    $newwebpath = file_create_path('newweb_test');
  }
  else{
    $newwebpath = file_create_path('newweb');
  }
  file_check_directory($newwebpath, FILE_CREATE_DIRECTORY);
  return $newwebpath;
}
