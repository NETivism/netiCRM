<?php

define("NEWEB_UPLOAD", 1);
define("NEWEB_DOWNLOAD", 0);
define("NEWEB_DEBUG", 1);

function civicrm_neweb_process_response($is_test, $now = NULL){
  $now = $now ? $now : time();

  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewebIPN.php';
  require_once 'CRM/Contribute/DAO/Contribution.php';
  require_once 'CRM/Utils/Array.php';
  require_once 'CRM/Contribute/BAO/ContributionPage.php';

  $p = new CRM_Core_BAO_PaymentProcessor();
  $pid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor WHERE is_test = $is_test");
  if($is_test){
    $payment = $p->getPayment($pid,'test');
  }
  else{
    $payment = $p->getPayment($pid,'live');
  }
  $newebpath = _civicrm_neweb_path($is_test);
  $filename = 'RP_'.$payment['user_name'].'_'.date('Ymd', $now).'.out';
  if(is_file($newebpath."/".$filename)){
    $response_str = file_get_contents($newebpath."/".$filename);
  }
  else{
    $response_str = _civicrm_neweb_sslftp($payment['user_name'], $newebpath.'/'.$filename, NEWEB_DOWNLOAD, $is_test);
  }

  if($response_str === false){
    // error when download files
    $error = "error in civicrm_newwweb_process_response: Can't download files. ";
    _civicrm_neweb_error($error, $is_test);
    return FALSE;
  }
  elseif($response_str == -1){
    // $error = "error in civicrm_newwweb_process_response: No response today.";
    // _civicrm_neweb_error($error, $is_test);
    return -1;
  }
  elseif($response_str){
    $response = _civicrm_neweb_decodeline($response_str, 'response');

    foreach($response as $k => $r){
      // find out recur
      $rid = $r['REFNUMBER'] ? $r['REFNUMBER'] : 0;
      $sql = "SELECT * FROM civicrm_contribution_recur c INNER JOIN civicrm_contribution_neweb_recur cnr ON c.id = cnr.recur_id WHERE c.id = {$rid}";
      $recur = CRM_Core_DAO::executeQuery($sql);
      $recur->fetch();

      if($recur->id){
        $contribution_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution c WHERE c.contribution_recur_id = {$recur->id} ORDER BY c.id ASC");
        $contrib =& new CRM_Contribute_DAO_Contribution();
        $contrib->id = $contribution_id;
        $sql = "UPDATE civicrm_contribution_neweb_recur SET response=$now WHERE recur_id = {$recur->id}";
        CRM_Core_DAO::executeQuery($sql);
        $action = strtolower($r['ACTION']);
        switch($action){
          case 'new':
            if($r['RESPONSE'] == '0'){
              if ( $contrib->find( true ) ) { 
                // send notification email of recurring contribution
                CRM_Contribute_BAO_ContributionPage::recurringNotify( 'START', $recur->contact_id, $contrib->contribution_page_id, $recur);
              }
              $recur->contribution_status_id = 1;
            }
            else{
              $recur->contribution_status_id = 4; // failed
            }
            $recur->modified_date = date('YmdHis');
            $recur->save();
            break;
          case 'delete':
            if($r['RESPONSE'] == '0' || $r['RESPONSE'] == '-31'){
              $recur->contribution_status_id = 3;
              $recur->modified_date = date('YmdHis');
              $recur->cancel_date = date('YmdHis');
              $recur->save();
            }
            break;
        }
      }

/*
      // fail
      $error = "error in civicrm_newwweb_process_response:". $r['REFNUMBER'] . ":" . $r['RESPONSE'];
      _civicrm_neweb_error($error, $is_test);
*/
    }

    return TRUE;
  }
}

function civicrm_neweb_process_transaction($is_test, $now = NULL){
  $now = $now ? $now : time();
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewebIPN.php';
  require_once 'CRM/Contribute/DAO/Contribution.php';
  require_once 'CRM/Utils/Array.php';

  $p = new CRM_Core_BAO_PaymentProcessor();
  $pid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor WHERE is_test = $is_test");
  if($is_test){
    $payment = $p->getPayment($pid,'test');
  }
  else{
    $payment = $p->getPayment($pid,'live');
  }
  $newebpath = _civicrm_neweb_path($is_test);

  $filename = 'RP_Trans_'.$payment['user_name'].'_'.date('Ymd', $now).'.log';
  if(is_file($newebpath."/".$filename)){
    $transaction_str  = file_get_contents($newebpath."/".$filename);
  }
  else{
    $transaction_str = _civicrm_neweb_sslftp($payment['user_name'], $newebpath.'/'.$filename, NEWEB_DOWNLOAD, $is_test);
  }

  if($transaction_str === false){
    // error when download files
    $error = "In civicrm_newwweb_process_transaction: Today have nothing to download. ";
    _civicrm_neweb_error($error, $is_test);
  }
  elseif($transaction_str == -1){
    // have file but no response
    // $error = "error in civicrm_newwweb_process_response: No response today.";
    // _civicrm_neweb_error($error, $is_test);
    return -1;
  }
  elseif($transaction_str){
    $trans = _civicrm_neweb_decodeline($transaction_str,'transaction');
    foreach($trans as $k => $t){
      $sql = "SELECT * FROM civicrm_contribution_recur c INNER JOIN civicrm_contribution_neweb_recur cnr ON c.id = cnr.recur_id WHERE c.id = {$t['REFNUMBER']}";
      $recur = CRM_Core_DAO::executeQuery($sql);
      $recur->fetch();
      $contribution_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution c WHERE c.contribution_recur_id = {$t['REFNUMBER']} ORDER BY c.id ASC");
      $contrib =& new CRM_Contribute_DAO_Contribution();
      $contrib->id = $contribution_id;
      if ( !$contrib->find( true ) ) { 
        $error = "Can't not find the first contribution id here";
        _civicrm_neweb_error($error, $is_test);
      }

      if($recur->id && $recur->contribution_status_id == 1){
        // check trxn_id first
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->trxn_id = $t['ORGORDERNUMBER'];
        if($contribution->find( true )){
          $contribution->receive_date = date('YmdHis', $now);
        }
        else{
          // clone exists
          unset($contribution);
          $contribution =& $contrib;
          unset($contribution->id);
          unset($contribution->receive_date);
          unset($contribution->trxn_id);
          unset($contribution->invoice_id);
          $contribution->receive_date = date('YmdHis', $now);
          $contribution->trxn_id = $t['ORGORDERNUMBER'];
        }
        // we save contribution first, anyway.
        $contribution->save();

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
          'OrderNumber' => $contribution->trxn_id,
          'Amount' => (int)$recur->amount,
          'amount' => (int)$recur->amount,
          '_recurring' => TRUE,
        );
        $ids = array(
          'contact' => $recur->contact_id,
          'contribution' => $contribution->id,
          'contributionRecur' => $recur->id,
          'contributionPage' => $contrib->contribution_page_id,
        );
        $objects = array(
          'contribution' => $contribution, 
          'membership' => NULL,
          'participant' => NULL,
          'event' => NULL,
          'paymentProcessor' => $payment,
        );
        require_once 'CRM/Core/Transaction.php';
        $transaction = new CRM_Core_Transaction( );
        $IPN = new CRM_Core_Payment_NewebIPN();

        // Check the response
        // Status: Complete
        if( ($t['PRC'] == 0 && $t['SRC'] == 0) || ($t['PRC'] == '11' && $t['SRC'] == '205') ){
          $IPN->completeTransaction( $input, $ids, $objects, $transaction, true);
          $cycle = $recur->cycle + 1;
          $sql = "UPDATE civicrm_contribution_neweb_recur SET contributions = $now, cycle = $cycle WHERE recur_id = $recur->id";
          CRM_Core_DAO::executeQuery($sql);
        }
        // Status: failed, cancel. We don't have retry mechnism.
        else{
          require_once "CRM/Core/Payment/NewebResponse.php";
          $e = new CRM_Core_Payment_NewebResponse;
          $err= $e->response($t['PRC'], $t['SRC']);
          $IPN->failed($objects, $transaction);
          // don't use cancel date , don't combine single failure to whole recurring process
          // $recur->cancel_date = date("Y-m-d H:i:s");
          // $recur->contribution_status_id = 4;
          $recur->failure_count++;
          $recur->save();
        }
      }
    }
    return TRUE;
  }
}

function civicrm_neweb_process_upload($is_test){
  $now = time();
  $nextday = mktime(6,0,0,date('n'),date('j'),date('Y'))+86400;
  $create_check = $now - 86400; // yesterday
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";
  require_once 'CRM/Core/Payment.php'; 
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/NewebIPN.php';
  $p = new CRM_Core_BAO_PaymentProcessor();
  $pid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor WHERE is_test = $is_test");
  if($is_test){
    $payment = $p->getPayment($pid,'test');
  }
  else{
    $payment = $p->getPayment($pid,'live');
  }

  // check first insert and recurring contributions.
  // need check nextday for support neweb only allow upload new transaction after today
  // only upload new recurring in recurring day.(Duplicate payment bug fix.)
  $sql = "SELECT * FROM civicrm_contribution_recur cr INNER JOIN civicrm_contribution_neweb_recur cnr ON cr.id = cnr.recur_id WHERE FROM_UNIXTIME($create_check) > cr.create_date && (cr.end_date >= FROM_UNIXTIME($now) OR cr.end_date IS NULL) && cr.cancel_date IS NULL && cr.is_test = $is_test && cr.contribution_status_id = 2 ORDER BY cr.next_sched_contribution DESC";
  $recur = CRM_Core_DAO::executeQuery($sql);

  while( $recur->fetch() ){
    // only effect when submit first recurring contribution
    $contrib = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_recur_id = $recur->id ORDER BY id ASC");
    $contrib->fetch();
    // start to collect output file string
    $upload = array(
      'MERCHANTNUMBER' => $payment['user_name'],
      'REFNUMBER' => $recur->id,
      'ORDERNUMBER' => $contrib->trxn_id,
      'BRAND' => '',       // null for our status
      'EXPIREDATE' => '',  // null for our status
      'AMOUNT' => (int)$recur->amount,
      'REGULAR' => $recur->cycle_day,
      'ACTION' => 'New',
      'STATUS' => '01',
      'MODE' => 0, // 0 for month mode, 1 for date mode
    );
    $rows[$recur->id] = $upload;
  }

  // cancelation by admin manually
  $sql = "SELECT * FROM civicrm_contribution_recur cr INNER JOIN civicrm_contribution_neweb_recur cnr ON cr.id = cnr.recur_id WHERE cr.contribution_status_id = 3 && cr.cancel_date IS NOT NULL && cr.cancel_date < FROM_UNIXTIME($now) && cr.is_test = $is_test ORDER BY cr.id ASC";
  $recur = CRM_Core_DAO::executeQuery($sql);
  while( $recur->fetch() ){
    $contrib = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_recur_id = $recur->id ORDER BY id ASC");
    $contrib->fetch();
    // start to collect output file string
    $upload = array(
      'MERCHANTNUMBER' => $payment['user_name'],
      'REFNUMBER' => $recur->id,
      'ORDERNUMBER' => $contrib->trxn_id,
      'BRAND' => '',       // null for our status
      'EXPIREDATE' => '',  // null for our status
      'AMOUNT' => (int)$recur->amount,
      'REGULAR' => $recur->cycle_day,
      'ACTION' => 'Delete',
      'STATUS' => '01',
      'MODE' => 0, // 0 for month mode, 1 for date mode
    );
    $rows[$recur->id] = $upload;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_recur SET contribution_status_id = 4 WHERE id = $recur->id");
  }

  if(count($rows)){
    $data = _civicrm_neweb_encodeline($rows);
    $newebpath = _civicrm_neweb_path($is_test);
    $filename = 'RP_'.$payment['user_name'].'_'.date('Ymd').'.dat';
    file_save_data($data, $newebpath.'/'.$filename, FILE_EXISTS_REPLACE);
    $uploaded =  _civicrm_neweb_sslftp($payment['user_name'], $newebpath.'/'.$filename, NEWEB_UPLOAD, $is_test);
    if($uploaded){
      $sql = "UPDATE civicrm_contribution_neweb_recur SET upload=$now WHERE recur_id IN (".implode(',', array_keys($rows)).")";
    }
    CRM_Core_DAO::executeQuery($sql);
    return $uploaded;
  }
  return false;
}

function civicrm_neweb_end_date_calculate($is_test){
  $now = time();
  civicrm_initialize();
  require_once "CRM/Core/DAO.php";

  // end recurring cancelation
  $end = $now + 86400;
  $sql = "SELECT * FROM civicrm_contribution_recur cr INNER JOIN civicrm_contribution_neweb_recur cnr ON cr.id = cnr.recur_id WHERE cr.end_date <= FROM_UNIXTIME($end) && cr.end_date IS NOT NULL && cr.cancel_date IS NULL && cr.is_test = $is_test && cr.contribution_status_id = 1 ORDER BY cr.end_date ASC";
  $recur = CRM_Core_DAO::executeQuery($sql);
  while( $recur->fetch() ){
    // just change status to pending and add cancel date.
    $cancel_date = date('Y-m-d H:i:s', $now);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_recur SET cancel_date = '{$cancel_date}', contribution_status_id = 2 WHERE id = {$recur->id}");
  }
}

function _civicrm_neweb_sslftp($id, $localfile, $upload, $is_test){
  $dir = $upload ? 'import' : 'output';
  if($is_test){
    $ftp = variable_get("civicrm_neweb_ftp_test", array());
    if($ftp['ftp_host'] && $ftp['ftp_user']){
      $cid = ftp_connect($ftp['ftp_host']);
      $login = ftp_login($cid, $ftp['ftp_user'], $ftp['ftp_password']);
    }
  }
  else{
    $ftp = variable_get("civicrm_neweb_ftp", array());
    if($ftp['ftp_host'] && $ftp['ftp_user']){
      $cid = ssh2_connect($ftp['ftp_host'], 22);
      $login = ssh2_auth_password($cid, $ftp['ftp_user'], $ftp['ftp_password']);
    }
    // $sftp = ssh2_sftp($cid);
  }

  if($login){
    $path = $is_test ? $id.'/'.$dir : $dir;
    $remotefile = $path .'/'. basename($localfile);

    if($upload){
      if($is_test){
        $success = ftp_put($cid, $remotefile, $localfile, FTP_ASCII);
      }
      else{
        $buffer = file_get_contents($localfile);
        $success = file_put_contents("ssh2.sftp://".$ftp['ftp_user'].":".$ftp['ftp_password']."@".$ftp['ftp_host'].":22/$remotefile", $buffer);
      }
    }
    else{
      // download
      if($is_test){
        $pass = $ftp['ftp_password'] ? ':'.$ftp['ftp_password'] : '';
        $contents = file_get_contents("ftp://".$ftp['ftp_user'].$pass."@".$ftp['ftp_host']."/$remotefile");
        if($contents){
          $success = file_put_contents($localfile, $contents);
        }
      }
      else{
        $contents = file_get_contents("ssh2.sftp://".$ftp['ftp_user'].":".$ftp['ftp_password']."@".$ftp['ftp_host'].":22/$remotefile");
        if($contents){
          $success = file_put_contents($localfile, $contents);
        }
      }
    }
    if($is_test){
      ftp_close($cid);
    }

    // see if success login and get result
    if($success){
      if($upload){
        return true;
      }
      else{
        return file_get_contents($localfile);
      }
    }
    elseif(!$upload){
      // only return -1 when download.
      // upload will retry untill done.
      _civicrm_neweb_error("File not found: $localfile", $is_test, WATCHDOG_NOTICE);
      return -1;
    }
  }
  else{
    _civicrm_neweb_error("FTP loging or setup error.", $is_test);
  }
  return false;
}

function _civicrm_neweb_encodeline($in){
  foreach($in as $line){
    $o .= implode(',', $line)."\n";
  }
  return rtrim($o, "\n");
}

/**
 * type: upload / response / transaction
 */
function _civicrm_neweb_decodeline($in, $type){
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
function _civicrm_neweb_path($is_test){
  if($is_test){
    $newebpath = file_create_path('neweb_test');
  }
  else{
    $newebpath = file_create_path('neweb');
  }
  file_check_directory($newebpath, FILE_CREATE_DIRECTORY);
  return $newebpath;
}


function civicrm_neweb_process_ezpay($is_test, $now){
  $time = date('YmdHis');
  civicrm_initialize();
  require_once 'CRM/Contribute/DAO/Contribution.php';
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Core/Payment/BaseIPN.php';
  require_once 'CRM/Core/Transaction.php';
  require_once "CRM/Core/DAO.php";

  $instrument = _civicrm_neweb_instrument();
  $gid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

  foreach($instrument as $name => $obj){
    $vid = CRM_Core_DAO::singleValueQuery("SELECT value FROM civicrm_option_value WHERE name = '{$name}' AND option_group_id = {$gid} AND is_active = 1");
    if($vid){
      $instrument_id[$vid] = $vid;
    }
  }
  $instrument_str = implode(',', $instrument_id);
  $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_status_id = 2 AND payment_instrument_id IN ($instrument_str) AND is_test = $is_test ORDER BY id DESC");
  while($dao->fetch()){
    if(!isset($p[$dao->contribution_page_id])){
      $pid = CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_ContributionPage',$dao->contribution_page_id, 'payment_processor_id');
      $p[$dao->contribution_page_id] = CRM_Core_BAO_PaymentProcessor::getPayment( $pid, $is_test ? 'test' : 'live' );
    }
    $payment_processor = $p[$dao->contribution_page_id];
    $post = array();
    $post['merchantnumber'] = $payment_processor['password'];
    $post['ordernumber'] = $dao->id;
    $post['operation'] = 'queryorders';
    $post['time'] = $time;
    $post['hash'] = md5($post['operation'].$payment_processor['subject'].$time);

    // initialize objects and ids
    $input = $object = $ids = $result = array();
    $note = '';
    $c =& new CRM_Contribute_DAO_Contribution();
    $c->id = $dao->id;
    $c->find(true);
    $ids['contact'] = $c->contact_id;
    $ids['contribution'] = $c->id;
    $input['component'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_participant_payment WHERE contribution_id = {$c->id}") ? 'event' : 'contribute'; // FIXME need to detect mode of contribute or event

    // fetch result and object
    $result = civicrm_neweb_postdata($post, $payment_processor);
    $created = isset($result['timecreated']) ? strtotime($result['timecreated']) : '';
    if($created){
      // two week
      $overdue = $now - $created > 86400 * 14 ? TRUE : FALSE;
    }
    else{
      $overdue = $created = FALSE;
    }

    $transaction = new CRM_Core_Transaction();
    $ipn = & new CRM_Core_Payment_BaseIPN();

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
        $note .= ts("Payment Instrument").": ". $result['paymenttype']."\n";
        $note .= ts("External order number").": ".$result['writeoffnumber']."\n";
        $note .= ts("Create date").": ".$result['timecreated']."\n";
        $note .= ts("Paid date").": ".$result['timepaid']."\n";
        $note .= ts("Pay count").": ".$result['paycount']."\n";
        $note .= ts("Completed");
        _civicrm_neweb_addnote($note, $c->id, $c->contact_id);
      }
      elseif(!$result['status'] && $result['rc'] == 0 && $overdue) {
        // cancel contribution over 2 week
        $input['reasonCode'] = ts('Overdue');
        $input['trxn_id'] = $c->trxn_id;
        $input['payment_instrument_id'] = $c->payment_instrument_id;
        $ipn->cancelled($objects, $transaction);
        $note .= ts("Canceled").": ".ts('Overdue')."\n";
        $note .= "Over 14 days doesn't have payment from {$result['timecreated']}\n";
        _civicrm_neweb_addnote($note, $c->id, $c->contact_id);
      }
      elseif($result['rc']){
        // FIXME to see if cancel contribution
        $note .= ts("Error").": ".$result['rc']."/".$result['rc2']."\n";
        _civicrm_neweb_addnote($note, $c->id, $c->contact_id);
      }
    }
  }
}

function civicrm_neweb_postdata($post, $payment_processor){
  $postdata = _civicrm_neweb_vars2str($post);
  $url = rtrim($payment_processor['url_api'],'/')."/Query"; 

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
  $receive = curl_exec($ch);
  if(curl_errno($ch)){
    _civicrm_neweb_error("CURL Error: ".curl_error($ch));
    $ch2 = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_POST, 1);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch2, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
    $receive = curl_exec($ch2);
  }
  curl_close($ch);

  if($receive){
    $vars = _civicrm_neweb_vars2array($receive);
    if($vars['rc'] == 70){
      $regetorder = curl_init($query_url);
      $post['operation'] = "regetorder";
      $postdata = _civicrm_neweb_vars2str($post);
      curl_setopt($regetorder, CURLOPT_POST, 1);
      curl_setopt($regetorder, CURLOPT_POSTFIELDS, $postdata);
      curl_setopt($regetorder, CURLOPT_HEADER, 0);
      curl_setopt($regetorder, CURLOPT_RETURNTRANSFER, 1);
      $receive2 = curl_exec($regetorder);
      curl_close($regetorder);
      $vars2 = _civicrm_neweb_vars2array($receive2);
      return $vars2;
    }
    return $vars;
  }
  else{
    return FALSE;
  }
}

function _civicrm_neweb_vars2str($post){
  $array = array();
  foreach($post as $name => $value){
    if($value){
      $array[] = $name."=".urlencode($value);
    }
  }
  return implode('&', $array);
}

function _civicrm_neweb_vars2array($str){
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

function _civicrm_neweb_addnote($note, $contribution_id, $contact_id){
  require_once 'CRM/Core/BAO/Note.php';
  $note = date("Y/m/d H:i:s")." ". ts("Transaction record").": \n".$note."\n===============================\n";
  $note_exists = CRM_Core_BAO_Note::getNote( $contribution_id, 'civicrm_contribution' );
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
    'entity_id'     => $contribution_id,
    'contact_id'    => $contact_id,
  );
  CRM_Core_BAO_Note::add( $noteParams, $note_id );
}

function _civicrm_neweb_error($msg, $is_test = NULL, $type = NULL){
  $type = $type ? $type : WATCHDOG_ERROR;
  if($is_test && NEWEB_DEBUG){
    watchdog('civicrm_neweb', 'TEST Env: '.$msg, array(), $type);
  }
  else{
    watchdog('civicrm_neweb', $msg, array(), $type);
  }
}
