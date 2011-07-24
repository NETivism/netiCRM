<?php
/**
 * Checkout functions
 *
 * We place the code into drupal inorder to seperate the civicrm role.
 * Called by Civicrm doTransferCheckout of Class Payment_GW
 */
function civicrm_gw_do_transfer_checkout(&$vars, &$component, &$payment_processor){
  // to see what instrument for newweb
  // $instrument_id = $vars['gw_instrument_id'];
  // $instrument = $this->getInstrument($gw_instrument_id);
  $instrument = 'Credit Card';

  $is_pay_later = TRUE;
  switch($instrument){
    case 'Credit Card':
      $output = _civicrm_gw_ecpay($vars, $component, $instrument, $payment_processor);
      $is_pay_later = FALSE;
      break;
      /*
    case 'WEBATM':
    case 'ATM':
    case 'CS':
    case 'MMK':
    case 'ALIPAY':
      break;
     */
  }

  print $output;
  // move things to CiviCRM cache as needed
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );
  module_invoke_all('exit');
  exit;
}

function _civicrm_gw_ecpay(&$vars, &$component, &$instrument, &$payment_processor){
  global $base_url, $base_path, $language;

  // notify url for receive payment result
  $notify_url = _civicrm_gw_notify_url($vars, 'gw/ipn', $component);

  // building vars
  $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];
  $name = truncate_utf8($vars['item_name'], 10);
  $notify_url .= "&qfKey=".$vars['qfKey'];

  $gw_vars = array(
    "client" => $payment_processor['user_name'],
    "od_sob" => $vars['contributionID'],
    "amount" => $amount,
    "roturl" => $notify_url,
    "bk_posturl" => $notify_url.'&bk_posturl=1',
    "qfKey" => $vars['qfKey'],
    "#action" => $payment_processor['url_site'],
  );
  if(!strstr($language->language, 'zh')){
    $gw_vars['Enn'] = 'e';
  }

  return _civicrm_gw_form_redirect($gw_vars);
}

function _civicrm_gw_ecbank(){

}

function _civicrm_gw_form_redirect($redirect_vars){
  if(is_array($redirect_vars)){
    $o .= '<form action="'.$redirect_vars['#action'].'" name="redirect" method="post" id="redirect-form">';
    foreach($redirect_vars as $k=>$p){
      if($k[0] != '#'){
        $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
      }
    }
    $o .= '</form>';
  }

  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Expires: 0');
  return '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.ts('Redirect to Payment Page').'</title>
</head>
<body>
'.$o.'
<script type="text/javascript">
document.forms.redirect.submit();
</script>
</body>
<html>
  ';
}

function _civicrm_gw_notify_url(&$vars, $path, $component){
  $query = array();
  $query[] = "reset=1";
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
