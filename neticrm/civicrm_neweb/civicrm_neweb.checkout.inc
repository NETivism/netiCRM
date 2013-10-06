<?php

function civicrm_neweb_do_transfer_checkout($params, $component, &$payment_processor){
  // to see what instrument for neweb
  $neweb_instrument_id = $params['civicrm_instrument_id'];
  $instrument_name  = civicrm_instrument_by_id($neweb_instrument_id, 'name');
  $neweb_instruments = _civicrm_neweb_instrument();
  $instrument_code = $neweb_instruments[$instrument_name]['code'];

  // The first, we insert every contribution into record. After this, we'll use update for the record.
  $record = array('cid' => $params['contributionID'], 'created' => time());
  drupal_write_record("civicrm_contribution_neweb", $record);

  $is_pay_later = TRUE;
  switch($instrument_code){
    case 'Credit Card':
      $params_form = _civicrm_neweb_mpp($params, $component, $payment_processor, $instrument_code);
      $is_pay_later = FALSE;
      break;
    case 'ATM':
    case 'WEBATM':
    case 'CS':
    case 'MMK':
    case 'ALIPAY':
      $params_form = _civicrm_neweb_ezpay($params, $component, $payment_processor, $instrument_code);
      break;
  }

  // now process contribution to save some default value
  $contrib_params = array( 'id' => $params['contributionID'] );
  $contrib_values = $contrib_ids = array();
  CRM_Contribute_BAO_Contribution::getValues($contrib_params, $contrib_values, $contrib_ids);
  if($params['civicrm_instrument_id']){
    $contrib_values['payment_instrument_id'] = $params['civicrm_instrument_id'];
  }
  if($contrib_values['is_pay_later'] != $is_pay_later){
    $contrib_values['is_pay_later'] = $is_pay_later;
  }
  $contrib_values['payment_processor_id'] = $payment_processor['id'];
  $contrib_values['trxn_id'] = $params['is_recur'] ? $params['contributionID'] + 990000000  : $params['contributionID'];
  $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

  // Inject in quickform sessions
  // Special hacking for display trxn_id after thank you page.
  $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$params['qfKey']]['params']['trxn_id'] = $contribution->trxn_id;

  // making redirect form
  print _civicrm_neweb_form_redirect($params_form, $instrument_code);
  // move things to CiviCRM cache as needed
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );
  exit();
}

function _civicrm_neweb_mpp(&$params, $component, $payment_processor, $neweb_instrument){
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
  $cancel_url = CRM_Utils_System::url($civi_base_url,"_qf_Confirm_display=true&qfKey={$params['qfKey']}",false, null, false );
  $return_url = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}",true, null, false );

  // notify url for receive payment result
  $notify_url = _civicrm_neweb_notify_url($params, 'neweb/ipn', $component);

  // building params
  $amount = $params['currencyID'] == 'TWD' && strstr($params['amount'], '.') ? substr($params['amount'], 0, strpos($params['amount'],'.')) .'.00' : $params['amount'];
  $name = function_exists('truncate_utf8') ? truncate_utf8($params['item_name'], 10) : $params['item_name'];

  $neweb_params = array(
    "MerchantNumber" => $payment_processor['user_name'],
    "OrderNumber"    => $params['is_recur'] ? $params['contributionID'] + 990000000  : $params['contributionID'],
    "Amount"         => $amount,
    "OrgOrderNumber" => $params['contributionID'],
    "ApproveFlag"    => 1,
    "DepositFlag"    => 0,
    "Englishmode"    => 0,
    "OrderURL"       => $notify_url,
    "ReturnURL"      => $return_url,
    "op"             => "AcceptPayment",
    "#action"        => $payment_processor['url_site'],
    "#paymentProcessor" => $payment_processor,
  );
  $neweb_params["checksum"] = md5($payment_processor['user_name'].$neweb_params['OrderNumber'].$payment_processor['signature'].$amount);

  return $neweb_params;
}

function _civicrm_neweb_ezpay(&$params, $component, $payment_processor, $neweb_instrument){
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
  $post['merchantnumber'] = $payment_processor['password'];
  $post['ordernumber'] = $params['contributionID'];
  $post['amount'] = $amount;
  $post['paymenttype'] = $neweb_instrument;
  $post['paytitle'] = truncate_utf8($params['item_name'], 12);
  $post['bankid'] = '007';
  $post['duedate'] = date('Ymd', time()+86400*7);
  if($neweb_instrument == 'CS'){
    $post['payname'] = $params['last_name']." ".$params['first_name'];
    if($params['phone']){
      $post['payphone'] = preg_replace("/[^\d]+/i", $params['phone']);
    }
  }
  $post['returnvalue'] = 0;
  $post['hash'] = md5($post['merchantnumber'].$payment_processor['subject'].$amount.$post['ordernumber']);
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
  $post['nexturl'] = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}&instrument={$neweb_instrument}",true, null, false );
  $post["#action"] = rtrim($payment_processor['url_api'],'/')."/Payment";

  return $post;
}

function _civicrm_neweb_notify_url(&$vars, $path, $component){
  $query = array();
  $query[] = "contact_id={$vars['contactID']}";
  $query[] = "cid={$vars['contributionID']}";
  $query[] = "module={$component}";

  if ( $component == 'event' ) {
    $query[] = "eid={$vars['eventID']}";
    $query[] = "pid={$vars['participantID']}";
  }
  else {
    if ( $vars['membershipID'] ) {
      $query[] = "mid=".$vars['membershipID'];
    }
    if ($vars['related_contact']) {
      $query[] = "rid=".$vars['related_contact'];
      if ($vars['onbehalf_dupe_alert']) {
        $query[] = "onbehalf_dupe_alert=".$vars['onbehalf_dupe_alert'];
      }
    }
  }

  // if recurring donations, add a few more items
  if ( !empty( $vars['is_recur']) ) {
     if ($vars['contributionRecurID']) {
       $query[] = "crid={$vars['contributionRecurID']}";
       $query[] = "cpid={$vars['contributionPageID']}";
     }
  }

  return url(
    $path,
    array(
      'query'=> implode('&', $query),
      'absolute' => true,
    )
  );
}

function _civicrm_neweb_form_redirect($redirect_params, $instrument){
  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Expires: 0');

  switch($instrument){
    case 'Credit Card':
    case 'WEBATM':
    case 'ALIPAY':
      $js = 'document.forms.redirect.submit();';
      $o .= '<form action="'.$redirect_params['#action'].'" name="redirect" method="post" id="redirect-form">';
      foreach($redirect_params as $k=>$p){
        if($k[0] != '#'){
          $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
        }
      }
      $o .= '</form>';
      break;
    case 'ATM':
    case 'CS':
    case 'MMK':
      $js = '
function print_redirect(){
  // creating the "newebresult" window with custom features prior to submitting the form
  window.open("", "newebresult", "scrollbars=yes,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no,left=150,top=150");
  document.forms.print.submit();
  window.location = "'.$redirect_params['nexturl'].'";
}
      ';

      $o .= '<form action="'.$redirect_params['#action'].'" name="print" method="post" id="redirect-form" target="newebresult">';
      foreach($redirect_params as $k=>$p){
        if($k[0] != '#'){
          $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
        }
      }
      $o .= '</form>';
      $o .= '<div align="center"><p>若網頁沒有自動跳出付款資訊，您可自行按下「取得付款資訊」按鈕以獲得繳款訊息</p><div><input type="button" value="取得付款資訊" onclick="print_redirect();" /></div></div>';
      break;
  }
  return '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
'.$o.'
<script type="text/javascript">
'.$js.'
</script>
</body>
<html>
';
}
