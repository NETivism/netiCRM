<?php
class CRM_Core_FieldHierarchy {
  public static $hierarchy = array(
    'reserved' => array(
      'do_not_import' => 1,
      'id' => 1,
      'external_identifier' => 1,
    ),
    'household' => array(
      'household_name' => 1,
    ),
    'organization_info' => array(
      'organization_name' => 1,
      'legal_name' => 1,
      'sic_code' => 1,
    ),
    'individual_info' => array(
      'individual_prefix' => 1,
      'first_name' => 1,
      'middle_name' => 1,
      'last_name' => 1,
      'individual_suffix' => 1,
      'birth_date' => 1,
      'deceased_date' => 1,
      'is_deceased' => 1,
      'gender' => 1,
      'nick_name' => 1,
      'legal_identifier' => 1,
      'job_title' => 1,
    ),
    'contact_info' => array(
      'email' => 1,
      'phone' => 1,
      'im' => 1,
      'url' => 1,
    ),
    'address' => array(
      'postal_code' => 1,
      'postal_code_suffix' => 1,
      'state_province' => 1,
      'city' => 1,
      'street_address' => 1,
      'master_id' => 1,
      'geo_code_1' => 1,
      'geo_code_2' => 1,
    ),
    'privacy_info' => array(
      'preferred_communication_method' => 1,
      'is_opt_out' => 1,
      'do_not_trade' => 1,
      'do_not_email' => 1,
      'do_not_sms' => 1,
      'do_not_mail' => 1,
      'do_not_phone' => 1,
      'email_greeting' => 1,
      'email_greeting_custom' => 1,
      'addressee' => 1,
      'addressee_custom' => 1,
      'postal_greeting_custom' => 1,
      'postal_greeting_custom' => 1,
    ),
    'event' => array( // use component
      'participant_id' => 1,
      'participant_register_date' => 1,
      'participant_fee_level' => 1,
      'participant_fee_amount' => 1,
      'participant_is_pay_later' => 1,
      'participant_status' => 1,
      'participant_role' => 1,
      'participant_source' => 1,
      'participant_registered_by_id' => 1,
      'participant_note' => 1,
      'participant_is_test' => 1,
    ),
    'contribute' => array( // use component name
      'contribution_page_id' => 1,
      'contribution_id' => 1,
      'trxn_id' => 1,
      'invoice_id' => 1,
      'contribution_status' => 1,
      'total_amount' => 1,
      'payment_instrument' => 1,
      'is_pay_later' => 1,
      'contribution_type' => 1,
      'created_date' => 1,
      'receive_date' => 1,
      'contribution_source' => 1,
      'cancel_date' => 1,
      'cancel_reason' => 1,
      'receipt_id' => 1,
      'receipt_date' => 1,
      'thankyou_date' => 1,
      'contribution_note' => 1,
      'contribution_recur_id' => 1,
      'product_name' => 1,
      'sku' => 1,
      'product_option' => 1,
      'quantity' => 1,
      'fulfilled_date' => 1,
      'contribution_start_date' => 1,
      'contribution_end_date' => 1,
      'is_test' => 1,
    ),
    'otherwise' => array(
      '/custom_.*/i' => 1,
    ),
  );

  public static function arrange($fields){
    $priority = self::$hierarchy;
    $names = array_keys($fields);
    $copy = $fields;
    $new = array();
    foreach($priority as $group => $values){
      if(is_array($values)) {
        foreach($values as $key => $value) {
          if($key[0] === '/') {
            foreach($names as $n){
              if (preg_match($key, $n)) {
                $new[$n] = $fields[$n];
                unset($copy[$n]);
              }
            }
          }
          else{
            if (isset($fields[$key])) {
              $new[$key] = $fields[$key];
              unset($copy[$key]);
            }
          }
        }
      }
    }
    foreach($copy as $k => $v){
      $new[$k] = $v;
    }
    return $new;
  }
}
