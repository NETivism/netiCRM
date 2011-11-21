<?php

function civicrm_contribution_cck_admin_settings() {
  civicrm_initialize();
  require_once("api/v2/UFGroup.php");

  $types = node_get_types('names');
  $form['civicc_enable_dornor_list'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable Donor List',
    '#default_value' => variable_get('civicc_enable_dornor_list', 0),
  );
  $form['civicc_enable_admin'] = array(
    '#type' => 'checkbox',
    '#title' => 'Enable CiviCRM Contributino Admin Link',
    '#default_value' => variable_get('civicc_enable_admin', 0),
  );
  $form['civicc_enabled_type'] = array(
    '#type' => 'checkboxes',
    '#title' => 'Content type to enable for sync',
    '#default_value' => variable_get('civicc_enabled_type', array()),
    '#options' => $types,
    '#required' => TRUE,
  );

  $option_payment_processor =& CRM_Core_PseudoConstant::paymentProcessor( );
  $form['civicc_payment_processor_id'] = array(
    '#type' => 'select',
    '#title' => 'Default payment processor for contribution page',
    '#default_value' => variable_get('civicc_payment_processor_id', 0),
    '#options' => $option_payment_processor,
  );

  $form['civicc_price_amount_block'] = array(
    '#type' => 'textarea',
    '#title' => 'Default amount label / value',
    '#default_value' => variable_get('civicc_price_amount_block', 'TEST|100'),
    '#description' => 'Please enter label|value per line.',
  );

  $option_profile[0] = ts('- select -');
  $option_profile += civicrm_uf_profile_groups_get();
  $form['civicc_custom_pre'] = array(
    '#type' => 'select',
    '#title' => 'Default profile form for contribution page',
    '#default_value' => variable_get('civicc_custom_pre', 0),
    '#options' => $option_profile,
  );
  $form['civicc_custom_post'] = array(
    '#type' => 'select',
    '#title' => 'Default post profile form for contribution page',
    '#default_value' => variable_get('civicc_custom_post', 0),
    '#options' => $option_profile,
  );

  $form['civicc_fields'] = array(
    '#type' => 'fieldset',
    '#title' => t('CiviContributionPage / CCK field mapping'),
  );
  $fields = content_fields();
  foreach($fields as $name => $f){
    if($f['type'] != 'filefield' && $f['type'] != 'content_taxonomy')
      $option_fields[$name] = $f['type'].":".$f['widget']['label']." ($name)";
  }
  $civicc_fields = civicrm_contribution_cck_fields();
  foreach($civicc_fields as $f => $name){
    $form['civicc_fields']['civicc_'.$f] = array(
      '#type' => 'select',
      '#title' => $name,
      '#default_value' => variable_get('civicc_'.$f, ''),
      '#options' => $option_fields,
      '#required' => TRUE,
    );
  }

  if(module_exists('civicrm_instrument')){
    $gid = variable_get('civicrm_instrument_gid', 0);
    $instruments = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_option_value WHERE option_group_id = {$gid} AND is_active = 1");
    while($instruments->fetch()){
      $checkbox_name = _civicrm_instrument_form_name($instruments->name);
      $option_instrument[$checkbox_name] = ts($instruments->name);
    }
    $form['civicc_instrument'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Default payment instrument for contribution',
      '#default_value' => variable_get('civicc_instrument', array()),
      '#options' => $option_instrument,
    );
  }
  
  return system_settings_form($form);
}

function civicrm_contribution_cck_donors(&$node){
  civicrm_initialize();
  $is_test = $_GET['action'] == 'preview' ? 1 : 0;

  $donors = civicrm_contribution_cck_donors_filter($node, $is_test);
  if(empty($donors)){
    $donors = array( '' => array());
  }
  foreach($donors as $type => $d){
    $output .= theme("civicrm_contribution_cck_donors", $type, $node, $d);
  }

  return $output;
}

function civicrm_contribution_cck_donors_filter(&$node, $is_test = 0){
  require_once("CRM/Contribute/PseudoConstant.php");
  require_once("CRM/Utils/Money.php");
  $query = "SELECT u.uf_id as uid, c.display_name, cc.id as contribution_id, cc.total_amount, cc.contact_id, cc.contribution_type_id FROM civicrm_contribution cc INNER JOIN civicrm_contact c ON c.id = cc.contact_id LEFT JOIN civicrm_uf_match u ON cc.contact_id = u.contact_id WHERE cc.contribution_status_id = 1 AND cc.cancel_date IS NULL AND cc.contribution_page_id = {$node->civicrm_contribution_cck['id']} AND cc.is_test = $is_test AND c.is_deleted = 0";

  $contact = CRM_Core_DAO::executeQuery( $query );
  $types = CRM_Contribute_PseudoConstant::contributionType( );

  $donors = array();
  while($contact->fetch()){
    $type = $types[$contact->contribution_type_id];
    if($contact->uid){
      $account = db_fetch_object(db_query("SELECT picture, uid, name FROM {users} WHERE uid = %d", $contact->uid));
    }
    $picture = theme('user_picture', $account);
    $donors[$type][$contact->contribution_id] = array(
      'contact_id' => $contact->contact_id,
      'uid' => $contact->uid,
      'amount' => CRM_Utils_Money::format($contact->total_amount),
      'name' => $contact->display_name,
      'picture' => $picture,
    );
  }
  return $donors;
}

function template_preprocess_civicrm_contribution_cck_donors(&$vars){
  static $delta;
  $delta++;
  $vars['delta'] = $delta;
  foreach($vars['donors'] as $d){
    $vars['content'] .= theme('civicrm_contribution_cck_donor_item', $d, $d['picture'], $d['amount'], $d['name']);
  }
  if(!$vars['content']){
    $vars['content'] = t('No donations.');
  }
}

function template_preprocess_civicrm_contribution_cck_donor_item(&$vars){
}

