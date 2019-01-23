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

  function getCouponList($filter) {
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
    }
    if (empty($where)) {
      $where[] = ' (1) ';
    }
    $sql .= implode(' AND ', $where);
    $sql .= " ORDER BY cc.id DESC, e.entity_table, e.entity_id ASC";
    return CRM_Core_DAO::executeQuery($sql, $args);
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
      $sql = "SELECT c.*, ct.id as coupon_track_id, ct.*, contact.sort_name, contrib.total_amount FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id INNER JOIN civicrm_contact contact ON ct.contact_id = contact.id INNER JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE ct.used_date IS NOT NULL AND {$field} IN({$couponIds}) ORDER BY ct.used_date DESC";
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
      $form->add('button', 'coupon_valid', ts('Confirm'), array('onClick' => 'couponValid();'));
    }
  }

  static function validFromCode($code) {
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
      if($dao->count_max <= $count){
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

  function validEventFromCode($code, $ids = NULL, $entity_table = 'civicrm_event'){
    $coupon = self::validFromCode($code);
    if(!empty($coupon) && $ids){
      if(is_array($ids)){
        $idsText = implode(',', $ids);
      }else{
        $idsText = $ids;
      }
      $sql = "SELECT entity_id FROM civicrm_coupon_entity ce WHERE entity_id IN ({$idsText}) AND entity_table = %1 AND coupon_id = %2";
      $params = array(
        1 => array($entity_table, 'String'),
        2 => array($coupon['id'], 'Integer'),
      );
      $entity_id = CRM_Core_DAO::singleValueQuery($sql, $params);
      if(!empty($entity_id)){
        return $coupon;
      }
      else{
        return False;
      }
    }
  }

  static function addCouponTrack($couponId, $contributionId, $contactId = NULL){
    $coupon = new CRM_Coupon_DAO_Coupon();
    $coupon->id = $couponId;
    $coupon->find(True);
    $couponTrack = new CRM_Coupon_DAO_CouponTrack();
    $couponTrack->coupon_id = $coupon->id;
    $couponTrack->contribution_id = $contributionId;
    $couponTrack->contact_id = $contactId;
    $couponTrack->save();
    return $couponTrack;
  }
}
