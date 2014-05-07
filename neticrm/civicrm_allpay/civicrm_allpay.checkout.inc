<?php

define('ALLPAY_EXPIRE_DAY', 7);

/**
 * Checkout functions
 *
 * We place the code into drupal inorder to seperate the civicrm role.
 * Called by Civicrm doTransferCheckout of Class Payment_ALLPAY
 * 
 * @param $vars 
 * @param $component 
 * @param $payment_processor 
 */
function civicrm_allpay_do_transfer_checkout(&$vars, &$component, &$payment_processor){
  // once they enter here, we will check SESSION
  // to see what instrument for newweb
  $instrument_id = $vars['civicrm_instrument_id'];
  $instrument_name = civicrm_instrument_by_id($instrument_id, 'name');
  $allpay_instruments = _civicrm_allpay_instrument('code');
  $instrument_code = $allpay_instruments[$instrument_name];
  $form_key = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$vars['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$vars['qfKey'];

  // The first, we insert every contribution into record. After this, we'll use update for the record.
  $record = array('cid' => $vars['contributionID']);
  drupal_write_record("civicrm_contribution_allpay", $record);

  if(!empty($_SESSION['allpay']['submitted'])) {
    drupal_goto($_SESSION['allpay']['path'], $_SESSION['allpay']['query']);
  }
  $_SESSION['allpay']['submitted'] = TRUE;
  $_SESSION['allpay']['instrument'] = $instrument_code;

  if($instrument_code == 'Credit' || $instrument_code == 'WebATM'){
    $is_pay_later = FALSE;
  }
  else{
    $is_pay_later = TRUE;
    if($vars['participantID']){
      $pstatus = CRM_Event_PseudoConstant::participantStatus();
      if($new_pstatus = array_search('Pending from pay later', $pstatus)){
        CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $vars['participantID'], 'status_id', $new_pstatus, 'id');
      }
    }
  }

  // now process contribution to save some default value
  $contrib_params = array( 'id' => $vars['contributionID'] );
  $contrib_values = $contrib_ids = array();
  CRM_Contribute_BAO_Contribution::getValues($contrib_params, $contrib_values, $contrib_ids);
  if($vars['civicrm_instrument_id']){
    $contrib_values['payment_instrument_id'] = $vars['civicrm_instrument_id'];
  }
  $contrib_values['is_pay_later'] = $is_pay_later;
  $contrib_values['trxn_id'] = $vars['contributionID'];
  $contribution =& CRM_Contribute_BAO_Contribution::create($contrib_values, $contrib_ids);

  // Inject in quickform sessions
  // Special hacking for display trxn_id after thank you page.
  $_SESSION['CiviCRM'][$form_key]['params']['trxn_id'] = $contribution->trxn_id;
  $_SESSION['CiviCRM'][$form_key]['params']['is_pay_later'] = $is_pay_later;

  $arguments = _civicrm_allpay_order($vars, $component, $payment_processor, $instrument_code, $form_key);
  _civicrm_allpay_checkmacvalue($arguments, $payment_processor);
  print _civicrm_allpay_form_redirect($arguments, $payment_processor);
  // move things to CiviCRM cache as needed
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );
  exit;
}

function _civicrm_allpay_order(&$vars, $component, &$payment_processor, $instrument_code, $form_key){
  global $base_url, $base_path, $language;

  // url 
  $notify_url = _civicrm_allpay_notify_url($vars, 'allpay/ipn/'.$instrument_code, $component);
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
  $thankyou_url = url($civi_base_url, array( 'query' => "_qf_ThankYou_display=1&qfKey={$vars['qfKey']}", 'absolute' => true));

  // parameter
  if($component == 'event' && !empty($_SESSION['CiviCRM'][$form_key])){
    $values =& $_SESSION['CiviCRM'][$form_key]['values']['event'];
  }
  else{
    $values =& $_SESSION['CiviCRM'][$form_key]['values'];
  }

  // building vars
  $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];

  $args = array(
    'MerchantID' => $payment_processor['user_name'],
    'MerchantTradeNo' => $vars['contributionID'],
    'MerchantTradeDate' => date('Y/m/d H:i:s'),
    'PaymentType' => 'aio',
    'TotalAmount' => $amount,
    'TradeDesc' => preg_replace('~[^\p{L}\p{N}]++~u', ' ', $vars['description']),
    'ItemName' => preg_replace('~[^\p{L}\p{N}]++~u', ' ', $vars['item_name']),
    'ReturnURL' => $notify_url."&qfKey=".$vars['qfKey'],
    'ChoosePayment' => $instrument_code,
    #'CheckMacValue' => '', // add in civicrm_allpay_checkmacvalue
    'ClientBackURL' => $thankyou_url,
    'ItemURL' => '',
    'Remark' => '',
    'ChooseSubPayment' => '',
    'OrderResultURL' => $thankyou_url,
    'NeedExtraPaidInfo' => '',
    'DeviceSource' => '',
  );

  switch($instrument_code){
    case 'ATM':
      $args['ExpireDate'] = !empty($values['expiration_time']) ? ceil($values['expiration_time']/24) : 7;
    case 'BARCODE':
    case 'CVS':
      $args['Desc_1'] = '';
      $args['Desc_2'] = '';
      $args['Desc_3'] = '';
      $args['Desc_4'] = '';

      #ATM / CVS / BARCODE
      $args['PaymentInfoURL'] = url('allpay/record/'.$vars['contributionID'], array('absolute' => true));
      break;
    case 'Alipay':
      $params = array(
        'version' => 3,
        'id' => $vars['contactID'],
        'return.sort_name' => 1,
        'return.phone' => 1,
      );
      $result = civicrm_api('contact', 'get', $params);
      if(!empty($result['count'])){
        $phone = $result['values'][$result['id']]['phone'];
        $name = $result['values'][$result['id']]['sort_name'];
      }
      $args['AlipayItemName'] = $vars['item_name'];
      $args['AlipayItemCounts'] = 1;
      $args['AlipayItemPrice'] = $amount;
      $args['Email'] = $vars['email-5'];
      $args['PhoneNo'] = $phone;
      $args['UserName'] = $name;
      break;
    /*
    case 'Tenpay':
      $args['ExpireTime'] = '';
      break;
     */
    case 'WebATM':
      break;
    case 'Credit':
      if($vars['is_recur']){
        $args['PeriodAmount'] = $amount;
        $period = strtoupper($vars['frequency_unit'][0]);
        $args['PeriodType'] = $period;
        $args['Frequency'] = 1; // now only support continious recurring
        $args['ExecTimes'] = empty($vars['installments']) ? 999 : $vars['installments']; // support endless
        $args['PeriodReturnURL'] = $notify_url.'&is_recur=1';
      }
      # Recurring
      break;
  }
  return $args ;
}

function _civicrm_allpay_form_redirect($redirect_vars, $payment_processor){
  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Expires: 0');

  $js = 'document.forms.redirect.submit();';
  $o .= '<form action="'.$payment_processor['url_site'].'" name="redirect" method="post" id="redirect-form">';
  foreach($redirect_vars as $k=>$p){
    if($k[0] != '#'){
      $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
    }
  }
  $o .= '</form>';
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

function _civicrm_allpay_notify_url(&$vars, $path, $component){
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

  $url = url(
    $path,
    array(
      'query'=> implode('&', $query),
      'absolute' => true,
    )
  );
  if($_SERVER['HTTP_HTTPS'] == 'on' || $_SERVER['HTTPS'] == 'on'){
    return str_replace('http://', 'https://', $url);
  }
  else{
    return $url;
  }
}
