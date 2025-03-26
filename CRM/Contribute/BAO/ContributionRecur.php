<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Contribute/DAO/ContributionRecur.php';
class CRM_Contribute_BAO_ContributionRecur extends CRM_Contribute_DAO_ContributionRecur {

  /**
   * takes an associative array and creates a contribution object
   *
   * the function extract all the params it needs to initialize the create a
   * contribution object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Contribute_BAO_Contribution object
   * @access public
   * @static
   */
  static function add(&$params, &$ids = NULL) {
    $transaction = new CRM_Core_Transaction();

    // pre-processing hooks
    require_once 'CRM/Utils/Hook.php';
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'ContributionRecur', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ContributionRecur', NULL, $params);
    }
    // make sure we're not creating a new recurring contribution with the same trasaction ID
    // or invoice ID as an existing recurring contribution
    $duplicates = array();
    if (self::checkDuplicate($params, $duplicates)) {
      $error = &CRM_Core_Error::singleton();
      $d = CRM_Utils_Array::implode(', ', $duplicates);
      $error->push(CRM_Core_Error::DUPLICATE_CONTRIBUTION,
        'Fatal',
        array($d),
        "Found matching recurring contribution(s): $d"
      );
      return $error;
    }

    if ($params['id']) {
      $oldRecurring = new CRM_Contribute_BAO_ContributionRecur();
      $oldRecurring->id = $params['id'];
      $oldRecurring->find(TRUE);
    }

    $recurring = new CRM_Contribute_BAO_ContributionRecur();
    $recurring->copyValues($params);

    // set currency for CRM-1496
    if (!isset($recurring->currency)) {
      $config = CRM_Core_Config::singleton();
      $recurring->currency = $config->defaultCurrency;
    }
    $recurring->modified_date = date('YmdHis');

    $result = $recurring->save();

    if (CRM_Utils_Array::value('custom', $params) && is_array($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution_recur', $recurring->id);
    }
    $transaction->commit();

    $params['id'] = $recurring->id;
    if ($ids['log']) {
      $logId = $ids['log'];
    }
    if (!empty($params['message'])) {
      $message = $params['message'];
    }
    self::saveLogData($recurring, $oldRecurring, $logId, $message);

    // create post-processing hooks
    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'ContributionRecur', $recurring->id, $recurring);
    }
    else {
      CRM_Utils_Hook::post('create', 'ContributionRecur', $recurring->id, $recurring);
    }

    return $result;
  }

  /**
   * Check if there is a contribution with the same trxn_id or invoice_id
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param array  $duplicates (reference ) store ids of duplicate contribs
   *
   * @return boolean true if duplicate, false otherwise
   * @access public
   * static  */
  static function checkDuplicate($params, &$duplicates) {
    $id = CRM_Utils_Array::value('id', $params);
    $trxn_id = CRM_Utils_Array::value('trxn_id', $params);
    $invoice_id = CRM_Utils_Array::value('invoice_id', $params);

    $clause = array();
    $params = array();

    if ($trxn_id) {
      $clause[] = "trxn_id = %1";
      $params[1] = array($trxn_id, 'String');
    }

    if ($invoice_id) {
      $clause[] = "invoice_id = %2";
      $params[2] = array($invoice_id, 'String');
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = CRM_Utils_Array::implode(' OR ', $clause);
    if ($id) {
      $clause = "( $clause ) AND id != %3";
      $params[3] = array($id, 'Integer');
    }

    $query = "SELECT id FROM civicrm_contribution_recur WHERE $clause";
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $result = FALSE;
    while ($dao->fetch()) {
      $duplicates[] = $dao->id;
      $result = TRUE;
    }
    return $result;
  }

  static function getPaymentProcessor($id, $mode) {
    $sql = "SELECT c.payment_processor_id, r.processor_id FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE c.payment_processor_id IS NOT NULL AND r.id = %1 ORDER BY c.id ASC LiMIT 0, 1";

    $params = array(1 => array($id, 'Integer'));
    $query = CRM_Core_DAO::executeQuery($sql, $params);
    $query->fetch();
    if(empty($query->payment_processor_id) && empty($query->processor_id)){
      return NULL;
    }
    else{
      $pid = $query->processor_id ? $query->processor_id : $query->payment_processor_id;
    }

    return CRM_Core_BAO_PaymentProcessor::getPayment($pid, $mode);
  }

  /**
   * Function to get the number of installment done/completed for each recurring contribution
   *
   * @param array  $ids (reference ) an array of recurring contribution ids
   *
   * @return array $totalCount an array of recurring ids count
   * @access public
   * static  */
  static function getCount(&$ids) {
    $recurID = CRM_Utils_Array::implode(',', $ids);
    $totalCount = array();

    $query = " 
         SELECT contribution_recur_id, count( contribution_recur_id ) as commpleted
         FROM civicrm_contribution
         WHERE contribution_recur_id IN ( {$recurID}) AND is_test = 0
         GROUP BY contribution_recur_id";

    $res = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    while ($res->fetch()) {
      $totalCount[$res->contribution_recur_id] = $res->commpleted;
    }
    return $totalCount;
  }

  /**
   * Delete Recurring contribution.
   *
   * @return true / false.
   * @access public
   * @static
   */
  static function deleteRecurContribution($recurId) {
    $result = FALSE;
    if (!$recurId) {
      return $result;
    }

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $result = $recur->delete();

    return $result;
  }

  /**
   * Cancel Recurring contribution.
   *
   * @param integer  $recurId recur contribution id.
   * @param array    $objects an array of objects that is to be cancelled like
   *                          contribution, membership, event. At least contribution object is a must.
   *
   * @return true / false.
   * @access public
   * @static
   */
  static function cancelRecurContribution($recurId, $objects, $canceledId = 2) {
    if (!$recurId) {
      return FALSE;
    }
    require_once 'CRM/Contribute/PseudoConstant.php';
    // for now, we use pending for cancel id, because we need further response to make sure the contribution is cancelled.
    // $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus( null, 'name' );
    // $canceledId         = array_search( 'Cancelled', $contributionStatus );

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    if ($recur->find(TRUE)) {
      require_once 'CRM/Core/Transaction.php';
      $transaction = new CRM_Core_Transaction();
      $recur->contribution_status_id = $canceledId;
      $recur->start_date = CRM_Utils_Date::isoToMysql($recur->start_date);
      $recur->create_date = CRM_Utils_Date::isoToMysql($recur->create_date);
      $recur->modified_date = date('YmdHis');
      $recur->cancel_date = date('YmdHis');
      $recur->save();

      if ($objects == CRM_Core_DAO::$_nullObject) {
        $transaction->commit();
        return TRUE;
      }
      else {
        require_once 'CRM/Core/Payment/BaseIPN.php';
        $baseIPN = new CRM_Core_Payment_BaseIPN();
        return $baseIPN->cancelled($objects, $transaction);
      }
    }
    return FALSE;
  }

  /**
   * Function to get list of recurring contribution of contact Ids
   *
   * @param int $contactId Contact ID
   *
   * @return return the list of recurring contribution fields
   *
   * @access public
   * @static
   */
  static function getRecurContributions($contactId) {
    $params = array();
    require_once 'CRM/Contribute/DAO/ContributionRecur.php';
    $recurDAO = new CRM_Contribute_DAO_ContributionRecur();
    $recurDAO->contact_id = $contactId;
    $recurDAO->find();
    require_once 'CRM/Contribute/PseudoConstant.php';
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentProcessors = CRM_Core_PseudoConstant::paymentProcessor();

    while ($recurDAO->fetch()) {
      $params[$recurDAO->id]['id'] = $recurDAO->id;
      $params[$recurDAO->id]['contactId'] = $recurDAO->contact_id;
      $params[$recurDAO->id]['payment_processor'] = $paymentProcessors[$recurDAO->processor_id];
      $params[$recurDAO->id]['payment_processor_id'] = $recurDAO->processor_id;
      $params[$recurDAO->id]['start_date'] = $recurDAO->start_date;
      $params[$recurDAO->id]['end_date'] = $recurDAO->end_date;
      $params[$recurDAO->id]['cancel_date'] = $recurDAO->cancel_date;
      $params[$recurDAO->id]['next_sched_contribution'] = $recurDAO->next_sched_contribution;
      $params[$recurDAO->id]['amount'] = $recurDAO->amount;
      $params[$recurDAO->id]['currency'] = $recurDAO->currency;
      $params[$recurDAO->id]['failure_count'] = $recurDAO->failure_count;
      $params[$recurDAO->id]['failure_retry_date'] = $recurDAO->failure_retry_date;
      $params[$recurDAO->id]['frequency_unit'] = $recurDAO->frequency_unit;
      $params[$recurDAO->id]['frequency_interval'] = $recurDAO->frequency_interval;
      $params[$recurDAO->id]['installments'] = $recurDAO->installments;
      $params[$recurDAO->id]['contribution_status_id'] = $recurDAO->contribution_status_id;
      $params[$recurDAO->id]['contribution_status'] = CRM_Utils_Array::value($recurDAO->contribution_status_id, $contributionStatus);
      $params[$recurDAO->id]['is_test'] = $recurDAO->is_test;
      $params[$recurDAO->id]['cycle_day'] = $recurDAO->cycle_day;
    }

    return $params;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    if (!$is_active) {
      return self::cancelRecurContribution($id, CRM_Core_DAO::$_nullObject, 2);
    }
    return FALSE;
  }

  /**
   * Sync custom field from first recurring contrib to others
   *
   * @param int      $id             id of the recurring
   * @param int      $contributionId id of the contribution target to sync
   */
  static function syncContribute($id, $contributionId = NULL) {
    $config = CRM_Core_Config::singleton();
    if (!empty($config->recurringCopySetting) && $config->recurringCopySetting == 'latest') {
      if (!empty($contributionId)) {
        $queryContributionExcepFor = 'AND id != %2';
      }
      $query = CRM_Core_DAO::executeQuery("SELECT id, trxn_id FROM civicrm_contribution WHERE contribution_recur_id = %1 {$queryContributionExcepFor} ORDER BY created_date DESC", array(
        1 => array($id, 'Integer'),
        2 => array($contributionId, 'Integer'),
      ));
    }
    else {
      $query = CRM_Core_DAO::executeQuery("SELECT id, trxn_id FROM civicrm_contribution WHERE contribution_recur_id = %1 ORDER BY id ASC", array(1 => array($id, 'Integer')));
    }
    $i = 1;
    $children = array();
    $config = CRM_Core_Config::singleton();
    $exclude = !empty($config->recurringSyncExclude) ? $config->recurringSyncExclude : array();
    while ($query->fetch()) {
      if ($i == 1) {
        // load custom field values
        $parent = CRM_Core_BAO_CustomValueTable::getEntityValues($query->id, 'Contribution');

        // load contribution_soft for #22323
        $parent_soft = new CRM_Contribute_DAO_ContributionSoft();
        $parent_soft->contribution_id = $query->id;
        $parent_soft->find(TRUE);

        // Load membership payment. From #33382.
        $membership_payment = new CRM_Member_DAO_MembershipPayment();
        $membership_payment->contribution_id = $query->id;
        $membership_payment->find(TRUE);

        if ($contributionId) {
          $children = array(0 => $contributionId);
          break;
        }
      }
      elseif ($i > 1 && !$contributionId) {
        $children[] = $query->id;
      }
      $i++;
    }

    if (!empty($parent) && !empty($children)) {
      // prepare original params
      foreach ($parent as $k => $v) {
        if ($v !== NULL) {
          $params_parent['custom_' . $k] = $v;
        }
      }
      foreach ($children as $cid) {
        $params = array('entityID' => $cid);
        $params = array_merge($params, $params_parent);
        foreach($exclude as $e){
          if(isset($params['custom_'.$e])) {
            unset($params['custom_'.$e]);
          }
        }
        $exists = CRM_Core_BAO_CustomValueTable::getValues($params);
        foreach($exists as $k => $e){
          if(!empty($e) && strstr($k, 'custom_') && isset($params[$k])) {
            unset($params[$k]);
          }
        }
        CRM_Core_BAO_CustomValueTable::setValues($params);
      }
    }

    // Duplicate contribution soft .For #22323
    if(!empty($parent_soft->id) && !empty($children)){
      foreach ($children as $cid) {
        $cs = new CRM_Contribute_DAO_ContributionSoft();
        $cs->contribution_id = $cid;
        if(!$cs->find(TRUE)){
          $cs = clone $parent_soft;
          unset($cs->id);
          $cs->contribution_id = $cid;
          $cs->save();
          unset($cs);
        }
      }
    }

    // Duplicate membership payment. From #33382
    if (!empty($membership_payment->id) && !empty($children)) {
      foreach ($children as $cid) {
        $mp = new CRM_Member_DAO_MembershipPayment();
        $mp->contribution_id = $cid;
        if(!$mp->find(TRUE)){
          $mp = clone $membership_payment;
          unset($mp->id);
          $mp->contribution_id = $cid;
          $mp->save();
          unset($mp);
        }
      }
    }
  }

  static function calculateRecurDay($id, $today = NULL, $base = 'start_date'){
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $id;
    if($recur->find(TRUE) && !empty($recur->$base)){
      $result = CRM_Utils_Date::intervalAdd($recur->frequency_unit, $recur->frequency_interval, $recur->$base);
      return $result;
    }
    return FALSE;
  }

  static function currentRunningSummary(){
    $sql = " SELECT SUM( c.contributions ) AS contributions, SUM( c.amount ) AS amount, SUM( c.groupby ) AS contacts, c.currency
FROM (
  SELECT COUNT( r.id ) AS contributions, SUM( r.amount ) AS amount,  '1' AS groupby, r.currency
  FROM civicrm_contribution_recur r
  WHERE r.contribution_status_id =5
  AND r.frequency_unit =  'month'
  GROUP BY r.contact_id
  ) c
GROUP BY c.currency";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $summary = array();
    while($dao->fetch()){
      $summary[$dao->currency] = array(
        'contacts' => $dao->contacts,
        'contributions' => $dao->contributions,
        'amount' => $dao->amount,
      );
    }
    return $summary;
  }

  static function chartEstimateMonthly($limit = 12){
    $frequency_unit = 'month';
    $sql = "SELECT SUM(result.amount) as amount, result.installments FROM (SELECT r.amount, CAST(r.installments AS SIGNED) - CAST(count(c.id) AS SIGNED) as installments FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON c.contribution_recur_id = r.id WHERE r.contribution_status_id = 5 AND r.is_test = 0 AND r.frequency_unit = 'month' AND c.contribution_status_id = 1 AND c.is_test = 0 GROUP BY r.id ORDER BY installments ASC) as result WHERE result.installments > 0 OR result.installments IS NULL GROUP BY result.installments DESC";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $unlimit = $over = NULL;
    $slot = array_fill(1, $limit, 0);
    krsort($slot);
    while($dao ->fetch()){
      if(empty($dao->installments)){
        $unlimit = $dao->amount;
      }
      elseif($dao->installments > $limit) {
        $over += $dao->amount;
      }
      elseif(isset($slot[$dao->installments])){
        $slot[$dao->installments] = $dao->amount;
      }
      else{
        break;
      }
    }
    $dao->free();

    $labels = $values = array();
    $increment = NULL;
    $axisformat = array(
      'month' => 'n',
      'year' => 'Y',
      'day' => 'd',
    );
    foreach($slot as $installment => $amount){
      $increment += $amount;
      $amount = $unlimit + $over + $increment;
      $labels[$installment] = strftime('%b', strtotime('+'.$installment.' '.$frequency_unit));
      $values[$installment] = $amount;
    }
    ksort($values);
    ksort($labels);
    
    $chart = array(
      'id' => 'chart-recur',
      'selector' => '#chart-recur',
      'title' => ts('Recurring contributions estimated in next %1 %2', array(1 => $limit, 2 => ts($frequency_unit))),
      'labels' => json_encode(array_values($labels)),
      'series' => json_encode(array(array_values($values))),
      'type' => 'Line',
    );
    return $chart;
  }

  static function saveLogData($params, $before = NULL, &$logId = NULL, $message = NULL) {
    $params = (object) $params;
    if (empty($params->id)) {
      CRM_Core_Error::debug_log_message(ts('Lack of ID in parameters when saving log data.'), TRUE);
    }
    else if (!empty($before)) {
      $recurDAO = (object) $before;
    }
    else {
      $recurDAO = new CRM_Contribute_DAO_ContributionRecur();
      $recurDAO->id = $params->id;
      $recurDAO->find(TRUE);
    }

    $recurFields = array_keys((new CRM_Contribute_DAO_ContributionRecur())->fields());

    $before = $after = array();
    foreach ($recurFields as $field) {
      $before[$field] = empty($recurDAO->$field) ? NULL : $recurDAO->$field;
      $after[$field] = empty($params->$field) ? NULL : $params->$field;
      // $params Only save modified value, So copy from before.
      if (!empty($recurDAO->$field) && $after[$field] === NULL) {
        $after[$field] = $recurDAO->$field; 
      }
    }
    $data = array('before' => $before, 'after' => $after);
    if (!empty($message)) {
      $data['message'] = $message;
    }
    $session = CRM_Core_Session::singleton();
    $contactId = $session->get('userID');
    $logParams = array(
      'entity_table' => 'civicrm_contribution_recur',
      'entity_id' => $params->id,
      'data' => serialize($data),
      'modified_id' => $contactId,
      'modified_date' => date('YmdHis'),
    );
    if (!empty($logId)) {
      $logParams['id'] = $logId;
    }
    $log = CRM_Core_BAO_Log::add( $logParams );
  }

  static function addNote($recurringId, $title, $body = NULL) {
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (empty($userId)) {
      $userId = "NULL";
    }
    $noteParams = array(
      'entity_table'  => 'civicrm_contribution_recur',
      'subject'       => $title,
      'note'          => $body,
      'entity_id'     => $recurringId,
      'contact_id'    => $userId,
      'modified_date' => date('YmdHis'),
    );
    $note = CRM_Core_BAO_Note::add( $noteParams, NULL );
  }
}

