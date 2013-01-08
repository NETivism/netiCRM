<?php

function esunbank_admin_settings(){
  $form['esunbank_contact_info'] = array(
    '#type' => 'textarea',
    '#title' => t('Organization Contact info'),
    '#default_value' => variable_get('esunbank_contact_info', ''),
    '#description' => t('Organization can add contact information attach on invoice.'),
  );
  $form['esunbank_receipt_info'] = array(
    '#type' => 'textarea',
    '#title' => t('Receipt info'),
    '#default_value' => variable_get('esunbank_receipt_info', ''),
    '#description' => t('Receipt infomation display on invoice. This will tell user when they receive receipt after payment.'),
  );
  $form['esunbank_postoffice_account'] = array(
    '#type' => 'textarea',
    '#title' => t('Post Office Account'),
    '#default_value' => variable_get('esunbank_postoffice_account', ''),
    '#description' => t('Post Office account number for alternative payment.'),
  );
  return system_settings_form($form);
}
