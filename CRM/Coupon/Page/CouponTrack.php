<?php

class CRM_Coupon_Page_CouponTrack extends CRM_Core_Page {

  function run() {
    $couponId = CRM_Utils_Request::retrieve('coupon_id', 'Positive', $this);
    $dao = CRM_Coupon_BAO_Coupon::getCouponUsedBy(array($couponId));

    while ($dao->fetch()) {
      $used[$dao->id] = array();
      foreach($dao as $field => $value) {
        if ($field[0] == '_') {
          continue;
        }
        else {
          $used[$dao->id][$field] = $value;
        }
      }
    }
    if (!empty($used)) {
      $first = reset($used);
      CRM_Utils_System::setTitle(ts('Coupon'). ' - '.$first['code']);
    }
    $this->assign('rows', $used);
    parent::run();
  }
}
