<?php

define('GW_EXPIRE_DAY', 7);

/**
 * Checkout functions
 *
 * We place the code into drupal inorder to seperate the civicrm role.
 * Called by Civicrm doTransferCheckout of Class Payment_GW
 * 
 * @param $vars 
 * @param $component 
 * @param $payment_processor 
 */
function civicrm_gw_do_transfer_checkout(&$vars, &$component, &$payment_processor){
  // to see what instrument for newweb
  $instrument_id = $vars['civicrm_instrument_id'];
  $instrument_name = civicrm_instrument_by_id($instrument_id, 'name');
  $gw_instruments = _civicrm_gw_instrument('code');
  $instrument_code = strtolower(str_replace(' ','_',$gw_instruments[$instrument_name]));

  // The first, we insert every contribution into record. After this, we'll use update for the record.
  drupal_write_record("civicrm_contribution_gw", array('cid' => $vars['contributionID']));

  $is_pay_later = TRUE;
  switch($instrument_code){
    case 'credit_card':
      $redirect_vars = _civicrm_gw_ecpay($vars, $component, $payment_processor, $instrument_code);
      $is_pay_later = FALSE;
      break;
    case 'vacc':
    case 'web_atm':
    case 'barcode':
    case 'cvs':
    case 'ibon':
      $redirect_vars = _civicrm_gw_ecbank($vars, $component, $payment_processor, $instrument_code);
      break;
  }
  // now process contribution to save some default value
  require_once 'CRM/Contribute/DAO/Contribution.php';
  $contribution =& new CRM_Contribute_DAO_Contribution();
  $contribution->id = $vars['contributionID'];
  $contribution->find(true);
  $contribution->is_pay_later = $is_pay_later;
  $contribution->trxn_id = $params['contributionID'];
  $contribution->save();

  // Inject in quickform sessions
  // Special hacking for display trxn_id after thank you page.
  $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$vars['qfKey']]['params']['trxn_id'] = $contribution->trxn_id;

  print _civicrm_gw_form_redirect($redirect_vars, $instrument_code);
  // move things to CiviCRM cache as needed
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );
  module_invoke_all('exit');
  exit;
}

function _civicrm_gw_ecpay(&$vars, $component, &$payment_processor, $instrument_code){
  global $base_url, $base_path, $language;

  // notify url for receive payment result
  $notify_url = _civicrm_gw_notify_url($vars, 'gw/ipn/'.$instrument_code, $component);

  // building vars
  $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];
  $name = truncate_utf8($vars['item_name'], 10);
  $notify_url .= "&qfKey=".$vars['qfKey'];

  $redirect_vars = array(
    "client" => $payment_processor['user_name'],
    "od_sob" => $vars['contributionID'],
    "amount" => $amount,
    "roturl" => $notify_url,
    "bk_posturl" => $notify_url.'&bk_posturl=1',
    "qfKey" => $vars['qfKey'],
    "#action" => $payment_processor['url_site'],
  );
  if(!strstr($language->language, 'zh')){
    $redirect_vars['Enn'] = 'e';
  }

  return $redirect_vars;
}

function _civicrm_gw_ecbank(&$vars, $component, &$payment_processor, $instrument_code){
  // prepare necessery variables
  $post = array();
  $post['mer_id'] = $payment_processor['signature'];
  $post['payment_type'] = $instrument_code;
  $post['od_sob'] = $vars['contributionID'];
  $post['amt'] = ($vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ) ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) .'.00' : $vars['amount'];
  if($instrument_code != 'web_atm'){
    $post['enc_key'] = $payment_processor['subject'];
    $post['ok_url'] = _civicrm_gw_notify_url($vars, "gw/ipn/$instrument_code", $component);
  }

  // prepare option variables
  switch($instrument_code){
    case 'web_atm':
      // none
      $post['return_url'] = _civicrm_gw_notify_url($vars, "gw/ipn/$instrument_code", $component);
      break;
    case 'vacc':
      $post['expire_day'] = GW_EXPIRE_DAY;
      $post['setbank'] = 'ESUN';
      break;
    case 'barcode':
      $post['expire_day'] = GW_EXPIRE_DAY;
      break;
    // cvs and ibon
    case 'ibon':
      $post['prd_desc'] = truncate_utf8($vars['item_name'], 10);
      $post['desc_1'] = '';
      $post['desc_2'] = '';
      $post['desc_3'] = '';
    case 'cvs':
      // add desc_4
      $post['desc_4'] = '';
      break;
  }

  // done, fetch the result.
  $http_query = http_build_query($post);
  $api = url($payment_processor['url_api'], array('query'=>$http_query));
  $return = file_get_contents($api);
  parse_str($return, $res);

  // save fetched result
  $save = array('bankcode', 'vaccno', 'payno', 'expire_datetime');
  foreach($save as $k){
    $record[$k] = $res[$k];
    if($k == 'expire_datetime'){
      $record[$k] = strtotime($res['expire_date'].$res['expire_time'].' +0800');
    }
  }
  drupal_write_record('civicrm_contribution_gw', $record, 'cid');

  // return the popup js script for some method
  // TODO

}

function _civicrm_gw_form_redirect($redirect_vars, $instrument_code){
  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Expires: 0');

  switch($instrument_code){
    case 'Credit Card':
    case 'web_atm':
      $js = 'document.forms.redirect.submit();';
      $o .= '<form action="'.$redirect_vars['#action'].'" name="redirect" method="post" id="redirect-form">';
      foreach($redirect_vars as $k=>$p){
        if($k[0] != '#'){
          $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
        }
      }
      $o .= '</form>';
      break;
    case 'vacc':
    case 'barcode':
    case 'cvs':
    case 'ibon':
      $js = '
    function print_redirect(){
      // creating the "newebresult" window with custom features prior to submitting the form
      window.open("", "newebresult", "scrollbars=yes,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no,left=150,top=150");
      document.forms.print.submit();
      window.location = "'.$redirect_vars['nexturl'].'";
    }
    var t = 6;
    var timeout;
    function showtime(){
      t -= 1;
      document.getElementById("showtime").innerHTML= t;
      timeout = setTimeout("showtime()",1000);
      if(t == 0){
        clearTimeout(timeout);
      }
    }
    showtime();
      ';

      $o .= '<form action="'.$redirect_vars['#action'].'" name="print" method="post" id="redirect-form" target="newebresult">';
      foreach($redirect_vars as $k=>$p){
        if($k[0] != '#'){
          $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
        }
      }
      $o .= '</form>';
      $o .= '<div align="center"><p>若網頁沒有自動轉向，您可自行按下「列印」按鈕以取得付款資訊</p><div id="showtime"></div><div><input type="button" value="列印" onclick="print_redirect();" /></div></div>';
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

function _civicrm_gw_notify_url(&$vars, $path, $component){
  $query = array();
  $query[] = "contact_id={$vars['contactID']}";
  $query[] = "cid={$vars['contributionID']}";
  $query[] = "module={$component}";

  if ( $component == 'event' ) {
    $query[] = "eid={$vars['eventID']}";
    $query[] = "pid={$vars['participantID']}";
  }
  else {
    if ( $vars['mid'] ) {
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

