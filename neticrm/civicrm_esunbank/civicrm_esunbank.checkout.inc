<?php

/**
 * Checkout functions
 *
 * We place the code into drupal inorder to seperate the civicrm.
 * Called by Civicrm doTransferCheckout of Class Payment_ESUNBANK
 * 
 * @param $vars 
 * @param $component 
 * @param $payment_processor 
 */
function civicrm_esunbank_do_transfer_checkout(&$vars, &$component, &$payment_processor){
  // First, we insert every contribution into record. After this, we'll use update for the record.
  $record = array('cid' => $vars['contributionID'], 'created' => time());
  drupal_write_record("civicrm_contribution_esunbank", $record);
  $form_key = 'CRM_Contribute_Controller_Contribution_'.$vars['qfKey'];
  $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';

  // Generate esunbank payment serial
  // the order is matter, see function esunbank_api
  $p['code'] = $payment_processor['user_name'];
  $p['number'] = $vars['contributionID'];
  $p['length'] = $payment_processor['password'];
  $p['price'] = $vars['amount'];
  $p['timestamp'] = strtotime('+7 days');
  $p['store'] = $payment_processor['subject'];
  $p['type'] = $payment_processor['signature'];
  $p['return'] = 1;

  // Generate invoice in web and pdf
  $invoice_html = esunbank_api($p);
  
  // now process contribution to save some default value
  require_once 'CRM/Contribute/DAO/Contribution.php';
  $contribution =& new CRM_Contribute_DAO_Contribution();
  $contribution->id = $vars['contributionID'];
  $contribution->find(true);
  $contribution->is_pay_later = 1;

  $contribution->trxn_id = $p['serial']; // we use payment serial to be trxn_id
  /*
  if($vars['civicrm_instrument_id']){
    $contribution->payment_instrument_id = $vars['civicrm_instrument_id'];
  }
  */
  $contribution->save();

  // TODO: Send payment invoice to contact
  if(empty($_SESSION['CiviCRM'][$form_key]['params']['sent'])){
    $email = $vars['email-5'];
  }

  // Inject some in quickform sessions
  // Special hacking for display trxn_id after thank you page.
  $_SESSION['CiviCRM'][$form_key]['params']['trxn_id'] = $contribution->trxn_id;
  $_SESSION['CiviCRM'][$form_key]['params']['is_pay_later'] = 1;
  $values =& $_SESSION['CiviCRM'][$form_key]['values'];
  $values['thankyou_title'] = '123';
  $values['thankyou_text'] = '<em>test text</em>';
  $values['pay_later_text'] = '222';
  $values['pay_later_receipt'] = '333';
  $values['receipt_text'] = '999';

  // prevent re-send invoice
  $_SESSION['CiviCRM'][$form_key]['params']['sent'] = true;;
  require_once 'CRM/Core/Session.php';
  CRM_Core_Session::storeSessionObjects( );

  // redirect to thank-you page
  drupal_goto($civi_base_url, "_qf_ThankYou_display=true&qfKey={$vars['qfKey']}");
}

