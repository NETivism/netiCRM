<?php
/**
 * Standalone api without extends from class
 */

class CRM_Core_Payment_MyPayAPI {

  public static function writeRecord($logId, $data = array()) {
    $recordType = array('contribution_id', 'url', 'date', 'post_data', 'return_data');

    $record = new CRM_Contribute_DAO_MyPayLog();
    if(!empty($logId)) {
      $record->id = $logId;
      $record->find(TRUE);
    }

    foreach ($recordType as $key) {
      $record->$key = $data[$key];
    }
    $record->save();
    return $record->id;
  }

  static public function saveMyPayData($contributionId, $data, $apiType = '') {
    $recordType = array('contribution_id', 'contribution_recur_id', 'uid', 'expired_date', 'data');
    $mypay = new CRM_Contribute_DAO_MyPay();
    if($contributionId) {
      $mypay->contribution_id = $contributionId;
      $mypay->find(TRUE);
      $mypay->contribution_recur_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    }
    foreach ($recordType as $key) {
      $mypay->$key = $data[$key];
    }
    // CRM_Utils_Hook::alterMyPayResponse($response, $mypay, 'MyPay', $apiType);
    $mypay->save();
  }
}
