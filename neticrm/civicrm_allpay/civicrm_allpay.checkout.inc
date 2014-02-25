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

  if(!empty($_SESSION['allpay']['submitted']) && $instrument_code == 'Credit' ) {
    drupal_goto($_SESSION['allpay']['path'], $_SESSION['allpay']['query']);
  }
  $_SESSION['allpay']['submitted'] = TRUE;
  $_SESSION['allpay']['instrument'] = $instrument_code;

  if($instrument_code == 'Credit' || $instrument_code == 'WebATM'){
    $is_pay_later = FALSE;
  }
  else{
    $is_pay_later = TRUE;
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
    'TradeDesc' => $vars['description'],
    'ItemName' => !empty($vars['amount_level']) ? $vars['amount_level'] : $vars['item_name'],
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

/*
function _civicrm_allpay_ecbank(&$vars, $component, &$payment_processor, $instrument_code){
  // prepare necessery variables
  $post = array();
  $post['mer_id'] = $payment_processor['signature'];
  $post['payment_type'] = $instrument_code;
  $post['od_sob'] = $vars['contributionID'];
  $post['amt'] = ($vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ) ? substr($vars['amount'], 0, strpos($vars['amount'],'.'))  : $vars['amount'];
  if($instrument_code != 'web_atm'){
    $post['enc_key'] = $payment_processor['subject'];
    $post['ok_url'] = _civicrm_allpay_notify_url($vars, "allpay/ipn/$instrument_code", $component).'&bg=1';
  }

  // prepare option variables
  switch($instrument_code){
    // only web_atm use POST method to pass the parameter
    case 'web_atm':
      $post['return_url'] = _civicrm_allpay_notify_url($vars, "allpay/ipn/$instrument_code", $component)."&qfKey=".$vars['qfKey'];
      $post['#action'] = $payment_processor['url_api'];
      return $post;
    // otherwise, we need to fetch result from backgrouund
    case 'vacc':
      $post['expire_day'] = ALLPAY_EXPIRE_DAY;
      $post['setbank'] = 'ESUN';
      break;
    case 'barcode':
      $post['expire_day'] = ALLPAY_EXPIRE_DAY;
      break;
    case 'ibon':
      $post['prd_desc'] = $vars['item_name'];
      $post['desc_1'] = '';
      $post['desc_2'] = '';
      $post['desc_3'] = '';
      break;
    case 'cvs':
      $post['prd_desc'] = $vars['item_name'];
      $post['desc_1'] = '';
      $post['desc_2'] = '';
      $post['desc_3'] = '';
      $post['desc_4'] = '';
      break;
  }

  // fetch the result, but exclude web_atm (return in upper case)
  $http_query = http_build_query($post, '', '&');
  $api = url($payment_processor['url_api'], array('query'=>$http_query));
  $return = file_get_contents($api);
  parse_str($return, $res);

  // save fetched result
  $record = array('cid' => $vars['contributionID']);
  $save = array('error', 'bankcode', 'vaccno', 'payno', 'expire_datetime', 'tsr');
  foreach($save as $k){
    $record[$k] = $res[$k];
    if($k == 'expire_datetime'){
      $datetime = $res['expire_date'];
      $datetime .= $res['expire_time'] ? $res['expire_time'] : '000000';
      $record[$k] = strtotime($datetime.' +0800');
    }
  }
  $record['data'] = serialize($res);
  drupal_write_record('civicrm_contribution_allpay', $record, 'cid');

  // after saved record, we should modify the status of participant / membership
  if($component == 'event'){
    $participant_id = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $vars['contributionID'], 'participant_id', 'contribution_id');
    if($participant_id){
      $pstatus = CRM_Event_PseudoConstant::participantStatus();
      if($new_pstatus = array_search('Pending from pay later', $pstatus)){
        CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $participant_id, 'status_id', $new_pstatus, 'id');
      }
    }
  }

  // inject info
  $form_key = $component == 'event' ? 'CRM_Event_Controller_Registration_'.$vars['qfKey'] : 'CRM_Contribute_Controller_Contribution_'.$vars['qfKey'];
  if($component == 'event'){
    $values =& $_SESSION['CiviCRM'][$form_key]['values']['event'];
  }
  else{
    $values =& $_SESSION['CiviCRM'][$form_key]['values'];
  }

  // After saving the record, we only return the popup javascript for these method
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

  if($res['tsr']){
    $redirect_vars = array(
      "#action" => $thankyou_url,
    );
    if($instrument_code == 'barcode'){
      $redirect_vars['#popup'] = _civicrm_allpay_barcode($payment_processor['signature'], $res['tsr']);
    }
    else{
      // cvs vacc ibon
      $redirect_vars['#popup'] = _civicrm_allpay_popupurl($instrument_code, $vars['contributionID']);
      //dpr($_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$vars['qfKey']]['values']['thankyou_text']);
    }
    // send mail
    if(empty($_SESSION['CiviCRM'][$form_key]['params']['sent'])){
      $values['thankyou_text'] .= '<div>如果您沒有看到繳費資訊，點此<strong>開啟'.l('付款資訊', $redirect_vars['#popup'].'&width=900&height=600&iframe=true', array('attributes' => array('class' => 'colorbox-load', 'target' => '_blank'))).'</strong></div>';
      $p['user'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $vars['contactID'], 'display_name');

      // prepare template parameter
      $email = $vars['email-5'];
      // default from_email
      $from_emails = array();
      CRM_Core_OptionValue::getValues(array('name' => 'from_email_address'), &$from_emails);
      foreach($from_emails as $id => $v){
        if($v['is_default'] == 1){
          $from_default = $v['name'];
          break;
        }
      }
      if($component == 'event'){
        if($values['event']['confirm_from_name'] && $values['event']['confirm_from_email']){
          $from = $values['event']['confirm_from_name']. '<'.$values['event']['confirm_from_email'];
        }
      }
      else{ // contribute
        if($values['receipt_from_name'] && $values['receipt_from_email']){
          $from = $values['receipt_from_name']. ' <'.$values['receipt_from_email'].'>';
        }
      }
      $html = '您好！<br /><br />這是您在本網站<u>'.variable_get('site_name', '').'</u>的<u>'.$values['title'].'</u>頁面產生的付款資訊，點此可<strong>'.l('開啟付款資訊', $redirect_vars['#popup'], array('attributes' => array('target' => '_blank'))).'</strong>。<br />若您已經繳費，可忽略本訊息，提醒您，本繳費單有7天的期限，超過期限本訂單會自動取消。<br />--<br />'.variable_get('site_name', '');
      $send_params                = array( );
      $send_params['groupName'  ] = 'Activity Email Sender';
      $send_params['from'       ] = $from ? $from : $from_default;
      $send_params['toName'     ] = $p['user'];
      $send_params['toEmail'    ] = $email;
      $send_params['subject'    ] = '您在本站的付款資訊 - 訂單編號#'.$vars['contributionID'];
      $send_params['text'       ] = '';
      $send_params['html'       ] = $html;
      if ( ! CRM_Utils_Mail::send( $send_params ) ) {
        return false;
      }
      // prevent re-send invoice
      $_SESSION['CiviCRM'][$form_key]['params']['sent'] = true;;
    }

    return $redirect_vars;
  }
  else{
    drupal_set_message("Error on fetch response. Code number #{$res['error']}", 'error');
  }
}
*/

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
