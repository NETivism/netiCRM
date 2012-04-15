<?php

function civicrm_neweb_ipn(){
  civicrm_initialize();
  require_once 'CRM/Core/BAO/PaymentProcessor.php';
  require_once 'CRM/Contribute/DAO/Contribution.php';
  $config =& CRM_Core_Config::singleton();

  // save post data from neweb credit card center
  if($_GET['contributionID'] && is_numeric($_GET['contributionID']) && !empty($_POST)){
    $cid = $_GET['contributionID'];
    if($cid == $_POST['OrderNumber']){
      $submitted = db_result(db_query("SELECT cid FROM civicrm_contribution_neweb WHERE cid = %d", $cid));
      if(!$submitted){
        $contribution =& new CRM_Contribute_DAO_Contribution( );
        $contribution->id = $cid;
        if($contribution->find( true )){
          db_query("INSERT INTO civicrm_contribution_neweb (cid, prc, src, bankrc, approvalcode) VALUES (%d, %d, %d, '%s', '%s')", $cid, $_POST['PRC'], $_POST['SRC'], $_POST['BankResponseCode'], $_POST['ApprovalCode']);
        }
      }
    }
  }

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
