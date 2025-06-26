<?php
class CRM_Coupon_Page_CouponBatch extends CRM_Core_Page {

  function run() {
    $list = [];
    $dao = CRM_Core_DAO::executeQuery("SELECT SUBSTR(code, 1, LOCATE('-' , code)) as batch_prefix, count(*) as generated, c.description FROM civicrm_coupon c WHERE code LIKE '%-%' GROUP BY SUBSTR(code, 1, LOCATE('-' , code)) ORDER BY start_date DESC, batch_prefix ASC");
    while($dao->fetch()) {
      $used = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_coupon_track t INNER JOIN civicrm_coupon c ON c.id = t.coupon_id WHERE c.code LIKE %1 AND t.used_date IS NOT NULL", [
        1 => [$dao->batch_prefix.'%', 'String'],
      ]);
      if (empty($used)) {
        $used = 0;
      }
      $list[] = [
        'batch_prefix' => $dao->batch_prefix,
        'used_max' => $used.' / ' .$dao->generated,
        'description' => $dao->description,
      ];
    }
    $this->assign('rows', $list);
    parent::run();
  }

}
