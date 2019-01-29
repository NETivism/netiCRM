<?php
/**
 * Business object for managing coupon
 *
 */
class CRM_Coupon_BAO_Coupon extends CRM_Coupon_DAO_Coupon {

  function __construct() {
    parent::__construct();
  }
  static function add(&$params) {
    $coupon = new CRM_Coupon_BAO_Coupon();
    $coupon->copyValues($params);
    $coupon->save();
    return $coupon;
  }

  static function create(&$params) {
    // save
    $fields = CRM_Coupon_DAO_Coupon::fields();
    $data = $additional = array();
    foreach($params as $key => $value) {
      if (isset($fields[$key])) {
        $data[$key] = $value;
      }
      else {
        $additional[$key] = $value;
      }
    }
    $coupon = self::add($data);

    // logic to process limit entities  
    self::saveCouponEntity($coupon->id, $additional);
  }

  function saveCouponEntity($couponId, $data) {
    if (empty($couponId) || !is_numeric($couponId)) {
      return;
    } 
    self::formatCouponEntity($data);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_coupon_entity WHERE coupon_id = %1', array(1 => array($couponId, 'Integer')));
    foreach($data as $entity_table => $entities) {
      foreach($entities as $entity_id) {
        if (!empty($entity_id)) {
          $ce = new CRM_Coupon_DAO_CouponEntity();
          $ce->coupon_id = $couponId;
          $ce->entity_table = $entity_table;
          $ce->entity_id = $entity_id;
          $ce->save();
          $ce->free();
        }
      }
    }
  }

  function getCouponList($filter, $returnFetchedResult = False) {
    $sql = "SELECT cc.*, e.entity_table, e.entity_id FROM civicrm_coupon cc LEFT JOIN civicrm_coupon_entity e ON cc.id = e.coupon_id WHERE ";
    $where = $args = array();
    foreach($filter as $field => $value) {
      if (empty($value)) {
        continue;
      }
      if ($field == 'entity_table') {
        $where[] = "e.entity_table = %1";
        $args[1] =  array($value, 'String');
      }
      elseif ($field == 'entity_id') {
        if(is_array($value)){
          $where[] = "e.entity_id in (".implode(',', $value).")";
        }else{
          $where[] = "e.entity_id = %2";
          $args[2] =  array($value, 'Integer');
        }
      }
      elseif ($field == 'code') {
        $where[] = "cc.code LIKE %3";
        $args[3] =  array("%$value%", 'String');
      }
      elseif ($field == 'id') {
        $where[] = "cc.id = %4";
        $args[4] =  array($value, 'Positive');
      }
      elseif ($field == 'is_active') {
        $where[] = "cc.is_active = %5";
        $args[5] =  array($value, 'Integer');
      }
      elseif ($field == 'date') {
        $where[] = "(cc.start_date < %6 OR cc.start_date is NULL) AND (%6 < cc.end_date OR cc.end_date IS NULL)";
        $args[6] = array($value, 'String');
      }
    }
    if (empty($where)) {
      $where[] = ' (1) ';
    }
    $sql .= implode(' AND ', $where);
    $sql .= " ORDER BY cc.id DESC, e.entity_table, e.entity_id ASC";
    $dao = CRM_Core_DAO::executeQuery($sql, $args);
    if(!$returnFetchedResult){
      return $dao;
    }
    else if(!empty($dao->N)){
      $dao->fetch();
      $coupon = array();
      foreach($dao as $idx => $value) {
        if ($idx[0] != '_') {
          $coupon[$idx] = $value;
        }
      }
      return $coupon;
    }
    else{
      return False;
    }
  }

  function getCouponUsed($ids) {
    $result = array_fill_keys($ids, 0);
    if (!empty($ids)) {
      $couponIds = implode(',', $ids);
      $sql = "SELECT c.id, COUNT(*) as `count` FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id WHERE ct.used_date IS NOT NULL AND ct.coupon_id IN({$couponIds}) GROUP BY ct.coupon_id";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while($dao->fetch()) {
        $result[$dao->id] = $dao->count;
      }
    }
    return $result;
  }

  function getCouponUsedBy($ids, $field = 'ct.coupon_id') {
    if (!empty($ids)) {
      if (empty($field)) {
        $field = 'ct.coupon_id';
      }
      if (!strstr($field, '.')) {
        $field = 'ct.'.$field;
      }
      $couponIds = implode(',', $ids);
      $sql = "SELECT c.*, ct.id as coupon_track_id, ct.*, contact.sort_name, contrib.total_amount, ct.used_date FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id INNER JOIN civicrm_contact contact ON ct.contact_id = contact.id INNER JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE ct.used_date IS NOT NULL AND {$field} IN({$couponIds}) ORDER BY ct.used_date DESC";
      return CRM_Core_DAO::executeQuery($sql);
    }
  }

  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Coupon_DAO_Coupon', $params, $defaults);
  }

  static function formatCouponEntity(&$params) {
    foreach($params as $key => $value) {
      if (in_array($key, array('civicrm_event', 'civicrm_price_field_value'))) {
        if (!is_array($value)) {
          $val = explode(',', $value);
        }
        else {
          $val = $value;
        }
        foreach($val as $k => $n){
          if (!is_numeric($n)) {
            unset($val[$k]);
          }
        }
        $params[$key] = $val;
      }
      else {
        unset($params[$key]);
      }
    }
  }

  static function addQuickFormElement(&$form) {
    $ele = $form->add('text', 'coupon', ts('Coupon'), array('placeholder' => ts('Enter coupon code')));
    if (!empty($form->_coupon['coupon_track_id'])) {
      $form->add('hidden', 'coupon_track_id', $form->_coupon['coupon_track_id']);
      $form->assign('coupon', $form->_coupon);
      $form->assign('coupon_json', json_encode($form->_coupon));
      $ele->freeze();
    }
    else{
      $form->add('hidden', 'coupon_is_valid', false);
    }
  }

  static function validFromCode($code) {
    if(empty($code)){
      return NULL;
    }
    $sql = "SELECT * FROM civicrm_coupon WHERE code = %1";
    $params = array(1 => array($code, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $isValid = true;
    $currentTime = time();
    if($dao->N){
      $dao->fetch();
      if(!empty($dao->start_date) && $currentTime < strtotime($dao->start_date)){
        $isValid = false;
      }
      if(!empty($dao->end_date) && strtotime($dao->end_date) < $currentTime){
        $isValid = false;
      }
      if(!$dao->is_active){
        $isValid = false;
      }

      $sql = "SELECT count(ct.id) as count FROM civicrm_coupon_track ct LEFT JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE (ct.used_date IS NOT NULL AND ct.coupon_id = %1 AND contrib.contribution_status_id = 1)";
      $params = array(1 => array($dao->id, 'Integer'));
      $count = CRM_Core_DAO::singleValueQuery($sql, $params);
      if($dao->count_max <= $count && $count != 0){
        $isValid = false;
      }
    }
    else{
      $isValid = false;
    }
    if($isValid){
      $coupon = array();
      foreach($dao as $idx => $value) {
        if ($idx[0] != '_') {
          $coupon[$idx] = $value;
        }
      }
      return $coupon;
    }
    else{
      return false;
    }
  }

  function validEventFromCode($code, $ids = NULL, $entity_table = 'civicrm_event') {
    $coupon = self::validFromCode($code);
    if(!empty($coupon) && !empty($ids)){
      if(is_array($ids)){
        $idsText = implode(',', $ids);
      }else{
        $idsText = $ids;
      }
      $sql = "SELECT entity_id, entity_table FROM civicrm_coupon_entity ce WHERE entity_id IN ({$idsText}) AND entity_table = %1 AND coupon_id = %2";
      $params = array(
        1 => array($entity_table, 'String'),
        2 => array($coupon['id'], 'Integer'),
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $entity_ids = array(); 
      while($dao->fetch()){
        $coupon['entity_table'] = $entity_table;
        $entity_id = $dao->entity_id;
        $entity_ids[$entity_id] = $entity_id;
      }
      $coupon['entity_id'] = $entity_ids;
      if(!empty($coupon['entity_id'])){
        return $coupon;
      }
      else{
        return False;
      }
    }
  }

  static function getCouponFromFormSubmit($form, $fields){
    $code = $fields['coupon'];
    if(!empty($code)){
      if(CRM_Utils_Array::value('priceSetId', $fields)){
        // Get 2 used array 
        $usedOptionsCount = array();
        $usedOptions = array();
        $totalAmount = 0;
        foreach ($fields as $fieldKey => $value) {
          if(preg_match('/^price_\d+$/', $fieldKey) && !empty($value)){
            $fieldId = str_replace('price_', '', $fieldKey);
            if(is_array($value)){
              foreach ($value as $optionKey => $count) {
                $countFieldKey = 'price_'.$fieldId.'_'.$optionKey.'_count';
                if(!empty($fields[$countFieldKey])){
                  $count = $fields[$countFieldKey];
                }
                $usedOptionsCount[$optionKey] = $count;
                $usedOptions[$optionKey] = $option = $form->_values['fee'][$fieldId]['options'][$optionKey];
              }
            }
            $totalAmount += $count * $option['amount'];
          }
        }

        if(!empty($usedOptionsCount)){
          $usedOptionsCountText = implode(',', array_keys($usedOptionsCount));
          $coupon = CRM_Coupon_BAO_Coupon::validEventFromCode($code, $usedOptionsCountText, 'civicrm_price_field_value');     
        }
      }
      else{
        // Not use price set.
        if(empty($fields['is_primary'])){
          // formRule in register.php
          $totalAmount = $form->_values['fee'][$fields['amount']]['value'];
        }
        else{
          // postProcess
          $totalAmount = $fields['amount'];
        }
      }
      if(empty($coupon)){
        $coupon = CRM_Coupon_BAO_Coupon::validEventFromCode($code, $form->_eventId);
      }
      if(!empty($coupon)){
        $coupon['usedOptionsCount'] = $usedOptionsCount;
        $coupon['usedOptions'] = $usedOptions;
        $coupon['totalAmount'] = $totalAmount;
        return $coupon;
      }
      else{
        return false;
      }
    }
  }

  static function countAmount($form, $submitValues) {
    $coupon = self::getCouponFromFormSubmit($form, $submitValues);
    if(!empty($coupon)){
      $usedOptionsCount = $coupon['usedOptionsCount'];
      $usedOptions = $coupon['usedOptions'];
      $totalAmount = $coupon['totalAmount'];

      if($coupon['coupon_type'] == 'monetary'){
        $totalDiscount = ($totalAmount < $coupon['discount']) ? $totalAmount : $coupon['discount'];
      }
      else{
        // coupon_type == percentage
        if($coupon['entity_table'] == 'civicrm_price_field_value'){
          $usedOptionsDiscount = array();
          foreach ($coupon['entity_id'] as $optionId) {
            $count = $usedOptionsCount[$optionId];
            $amount = $usedOptions[$optionId]['amount'] * $count;
            $discount = round($amount * $coupon['discount'] / 100);
            $usedOptionsDiscount[$optionId] = $discount;
            $totalDiscount += $discount;
          }
          $form->_usedOptionsDiscount = $usedOptionsDiscount;
          $form->set('usedOptionsDiscount', $usedOptionsDiscount);
        }else{
          $totalDiscount = round($totalAmount * $coupon['discount'] / 100);
        }
      }
      $form->_totalDiscount = $totalDiscount;
      $form->_coupon = $coupon;
      $form->set('coupon', $coupon);
    }
    else{
      $form->set('coupon', NULL);
    }
  }

  static function checkError($form, $submitValues) {
    $coupon = self::getCouponFromFormSubmit($form, $submitValues);
    if(!empty($coupon)){
      $usedOptionsCount = $coupon['usedOptionsCount'];
      $usedOptions = $coupon['usedOptions'];
      $totalAmount = $coupon['totalAmount'];

      // Coupon is valid, But we don't check the amount.
      // Check the amount is enough to minimal_amount.
      if($coupon['entity_table'] == 'civicrm_price_field_value'){
        $totalAmount = 0;
        foreach ($coupon['entity_id'] as $optionId) {
          $count = $usedOptionsCount[$optionId];
          $totalAmount += $usedOptions[$optionId]['amount'] * $count;
        }
      }

      if($totalAmount < $coupon['minimal_amount']){
        $errors['coupon'] = ts("The amount is not enough for coupon. The minimal amount is %1. The summary of validated amount is %2.", array(
          1 => $coupon['minimal_amount'],
          2 => $totalAmount,
        ));
      }
    }
    else if($submitValues['coupon_is_valid']){
      // If coupon_id_valid is check, Told user that it's not valid since now.
      $errors['coupon'] = ts("The coupon is not valid for this selection.");
    }
    else{
      // Only Show notification text.
      CRM_Core_Session::setStatus(ts('The coupon is not applied for any selected option.'));

    }
    return $errors;
  }

  static function addCouponTrack($couponId, $contributionId, $contactId = NULL) {
    $coupon = new CRM_Coupon_DAO_Coupon();
    $coupon->id = $couponId;
    $coupon->find(True);
    $couponTrack = new CRM_Coupon_DAO_CouponTrack();
    $couponTrack->coupon_id = $coupon->id;
    $couponTrack->contribution_id = $contributionId;
    $couponTrack->contact_id = $contactId;
    $couponTrack->used_date = date('Y-m-d H:i:s');
    $couponTrack->save();
    return $couponTrack;
  }
}
