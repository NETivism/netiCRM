<?php

/**
 * Checkout functions
 *
 * We place the code into drupal inorder to seperate the civicrm.
 * Called by Civicrm doTransferCheckout of Class Payment_NCCC
 * 
 * @param $vars 
 * @param $component 
 * @param $payment_processor 
 */
function civicrm_nccc_do_transfer_checkout(&$vars, &$component, &$payment_processor){
  // to see what instrument for newweb
  $instrument_id = $vars['civicrm_instrument_id'];
  $instrument_name = civicrm_instrument_by_id($instrument_id, 'name');
  $instrument_code = $gw_instruments[$instrument_name];

  // First, we insert every contribution into record. After this, we'll use update for the record.
  $record = array('cid' => $vars['contributionID'], 'created' => time());
  drupal_write_record("civicrm_contribution_nccc", $record);

  $redirect_vars = _civicrm_nccc_getvars($vars, $component, $payment_processor, $instrument_code);

  // now process contribution to save some default value
  require_once 'CRM/Contribute/DAO/Contribution.php';
  $contribution =& new CRM_Contribute_DAO_Contribution();
  $contribution->id = $vars['contributionID'];
  $contribution->find(true);
  $contribution->is_pay_later = $is_pay_later;

  $contribution->trxn_id = $payment_processor['signature'].$vars['contributionID'];
  if($vars['civicrm_instrument_id']){
    $contribution->payment_instrument_id = $vars['civicrm_instrument_id'];
  }
  $contribution->save();

  // Inject in quickform sessions
  // Special hacking for display trxn_id after thank you page.
  $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$vars['qfKey']]['params']['trxn_id'] = $contribution->trxn_id;

  // here we should inlcude iframe to post to nccc
  // see http://css-tricks.com/snippets/html/post-data-to-an-iframe/
  print _civicrm_nccc_form_redirect($redirect_vars, $instrument_code);

  // move things to CiviCRM cache as needed
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );
  exit;
}

function _civicrm_nccc_getvars(&$vars, $component, &$payment_processor, $instrument_code){
  global $base_url, $base_path, $language;

  // notify url for receive payment result
  $notify_url = _civicrm_nccc_notify_url($vars, 'payment-nccc/ipn', $component);

  // for union card
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
  $mallurl = url($civi_base_url, array( 'query' => "_qf_ThankYou_display=1&qfKey={$vars['qfKey']}", 'absolute'=>true));

  // building vars
  $amount = $vars['currencyID'] == 'TWD' && strstr($vars['amount'], '.') ? substr($vars['amount'], 0, strpos($vars['amount'],'.')) : $vars['amount'];
  $name = truncate_utf8($vars['item_name'], 10);
  $notify_url .= "&qfKey=".$vars['qfKey'];

  $redirect_vars = array(
    "MerchantID" => $payment_processor['user_name'],
    "TerminalID" => $payment_processor['password'],
    "OrderID" => $vars['contributionID'],
    "TransAmt" => $amount,
    "TransMode" => "0",
    "PrivateData" => "qfKey=".$vars['qfKey'],
    "NotifyURL" => $notify_url,
    "CSS_URL" => $payment_processor['subject'],
    "qfKey" => $vars['qfKey'],
    "#action" => $payment_processor['url_site'],
  );
  if(!strstr($language->language, 'zh')){
    $redirect_vars['Enn'] = 'e';
  }

  return $redirect_vars;
}


function _civicrm_nccc_form_redirect($redirect_vars, $instrument_code){
  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Expires: 0');

  $js = 'document.forms.redirect.submit();';
  $o .= '<form action="'.$redirect_vars['#action'].'" name="redirect" method="post" id="redirect-form">';
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

function _civicrm_nccc_notify_url(&$vars, $path, $component){
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
}

