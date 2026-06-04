<?php
/**
 * Business object for managing coupon
 *
 */
class CRM_Coupon_BAO_Coupon extends CRM_Coupon_DAO_Coupon {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Adds a new coupon to the database.
   *
   * @param array $params An associative array of coupon data.
   * @return CRM_Coupon_BAO_Coupon The newly created coupon object.
   */
  public static function add(&$params) {
    $coupon = new CRM_Coupon_BAO_Coupon();
    $coupon->copyValues($params);
    $coupon->save();
    return $coupon;
  }

  /**
   * Creates a new coupon, including its associated entities.
   *
   * @param array $params An associative array of coupon data, potentially including entity associations.
   */
  public static function create(&$params) {
    // save
    $fields = CRM_Coupon_DAO_Coupon::fields();
    $data = $additional = [];
    foreach ($params as $key => $value) {
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

  /**
   * Updates the 'is_active' flag for a given coupon in the database.
   *
   * @param int $id The ID of the database record for the coupon.
   * @param bool $isActive The boolean value to set for the 'is_active' field.
   * @return object|null The DAO object on success, or null otherwise.
   */
  public static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Coupon_DAO_Coupon', $id, 'is_active', $isActive);
  }

  /**
   * Saves or updates the entities associated with a coupon.
   *
   * @param int $couponId The ID of the coupon.
   * @param array $data An associative array where keys are entity table names and values are arrays of entity IDs.
   */
  public static function saveCouponEntity($couponId, $data) {
    if (empty($couponId) || !is_numeric($couponId)) {
      return;
    }
    self::formatCouponEntity($data);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_coupon_entity WHERE coupon_id = %1', [1 => [$couponId, 'Integer']]);
    foreach ($data as $entity_table => $entities) {
      foreach ($entities as $entity_id) {
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

  /**
   * Deletes a coupon and its associated tracking and entity records.
   *
   * @param int $id The ID of the coupon to delete.
   * @return bool TRUE on successful deletion.
   */
  public static function deleteCoupon($id) {
    $coupon = new CRM_Coupon_DAO_Coupon();
    $coupon->id = $id;
    $coupon->find(TRUE);

    $entity = new CRM_Coupon_DAO_CouponEntity();
    $entity->coupon_id = $id;
    $entity->find();
    while ($entity->fetch()) {
      $entity->delete();
    }

    $track = new CRM_Coupon_DAO_CouponTrack();
    $track->coupon_id = $id;
    $track->find();
    while ($track->fetch()) {
      $track->delete();
    }

    $coupon->delete();
    return TRUE;
  }

  /**
   * Creates a copy of an existing coupon, including its associated entities.
   *
   * @param int $id The ID of the coupon to copy.
   * @return object The newly created coupon object.
   */
  public static function copy($id) {
    $maxId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_coupon");

    $fieldsFix = [
      'prefix' => [
        'code' => '__Copy_id_' . ($maxId + 1) . '_',
      ],
    ];

    $copy = &CRM_Core_DAO::copyGeneric('CRM_Coupon_DAO_Coupon', ['id' => $id], NULL, $fieldsFix);

    //copying all the blocks pertaining to the price set
    $copyCouponEntity = &CRM_Core_DAO::copyGeneric('CRM_Coupon_DAO_CouponEntity', ['coupon_id' => $id], ['coupon_id' => $copy->id]);
    $copy->save();

    CRM_Utils_Hook::copy('Coupon', $copy);
    return $copy;
  }

  /**
   * Retrieves a coupon by its ID or code, using a static cache.
   *
   * @param int|null $id The ID of the coupon to retrieve.
   * @param string|null $code The code of the coupon to retrieve.
   * @return array An associative array representing the coupon data, or an empty array if not found.
   */
  public static function getCoupon($id = NULL, $code = NULL) {
    static $coupons = [];
    if ($code && !empty($coupons[$code])) {
      return $coupons[$code];
    }
    if ($id && !empty($coupons[$id])) {
      return $coupons[$id];
    }
    $filter = [];
    if ($id && is_numeric($id)) {
      $filter['id'] = $id;
    }
    if ($code) {
      $filter['code='] = $code;
    }
    $dao = self::getCouponList($filter);

    $coupon = [];
    while ($dao->fetch()) {
      if (empty($coupon)) {
        foreach ($dao as $idx => $value) {
          if ($idx[0] != '_') {
            $coupon[$idx] = $value;
          }
        }
      }
      unset($coupon['entity_table']);
      unset($coupon['entity_id']);
      if (!empty($dao->entity_table)) {
        $coupon['used_for'][$dao->entity_table][$dao->entity_id] = $dao->entity_id;
      }
    }
    $coupons[$coupon['id']] = $coupon;
    $coupons[$coupon['code']] = $coupon;
    return $coupon;
  }

  /**
   * Retrieves a list of coupons based on specified filters.
   *
   * @param array $filter An associative array of filter criteria.
   * @param bool $returnFetchedResult If true, returns a single fetched result array instead of a DAO object.
   * @return CRM_Core_DAO|array|FALSE The DAO result set, a fetched array, or FALSE if no results.
   */
  public static function getCouponList($filter, $returnFetchedResult = FALSE) {
    $sql = "SELECT cc.*, e.entity_table, e.entity_id FROM civicrm_coupon cc LEFT JOIN civicrm_coupon_entity e ON cc.id = e.coupon_id WHERE ";
    $where = $args = [];
    foreach ($filter as $field => $value) {
      if (empty($value)) {
        continue;
      }
      if ($field == 'entity_table') {
        $where[] = "e.entity_table = %1";
        $args[1] =  [$value, 'String'];
      }
      elseif ($field == 'entity_id') {
        if (is_array($value)) {
          $where[] = "e.entity_id in (".CRM_Utils_Array::implode(',', $value).")";
        }
        else {
          $where[] = "e.entity_id = %2";
          $args[2] =  [$value, 'Integer'];
        }
      }
      elseif ($field == 'code') {
        $where[] = "cc.code LIKE %3";
        $args[3] =  ["%$value%", 'String'];
      }
      elseif ($field == 'id') {
        $where[] = "cc.id = %4";
        $args[4] =  [$value, 'Positive'];
      }
      elseif ($field == 'is_active') {
        $where[] = "cc.is_active = %5";
        $args[5] =  [$value, 'Integer'];
      }
      elseif ($field == 'date') {
        $where[] = "(cc.start_date < %6 OR cc.start_date is NULL) AND (%6 < cc.end_date OR cc.end_date IS NULL)";
        $args[6] = [$value, 'String'];
      }
      elseif ($field == 'code=') {
        $where[] = "cc.code = %7";
        $args[7] =  [$value, 'String'];
      }
      elseif ($field == 'description') {
        $where[] = "cc.description LIKE %8";
        $args[8] =  ["%$value%", 'String'];
      }
    }
    if (empty($where)) {
      $where[] = ' (1) ';
    }
    $sql .= CRM_Utils_Array::implode(' AND ', $where);
    $sql .= " ORDER BY cc.id DESC, e.entity_table, e.entity_id ASC ";
    if (isset($filter['offset']) && !empty($filter['limit'])) {
      $sql .= " LIMIT {$filter['offset']}, {$filter['limit']} ";
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $args);
    if (!$returnFetchedResult) {
      return $dao;
    }
    elseif (!empty($dao->N)) {
      $dao->fetch();
      $coupon = [];
      foreach ($dao as $idx => $value) {
        if ($idx[0] != '_') {
          $coupon[$idx] = $value;
        }
      }
      return $coupon;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Counts the number of times each coupon has been used.
   *
   * @param array $ids An array of coupon IDs.
   * @return array An associative array where keys are coupon IDs and values are their usage counts.
   */
  public static function getCouponUsed($ids) {
    $result = array_fill_keys($ids, 0);
    if (!empty($ids)) {
      $couponIds = CRM_Utils_Array::implode(',', $ids);
      $sql = "SELECT c.id, COUNT(*) as `count` FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id WHERE ct.used_date IS NOT NULL AND ct.coupon_id IN({$couponIds}) GROUP BY ct.coupon_id";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $result[$dao->id] = $dao->count;
      }
    }
    return $result;
  }

  /**
   * Retrieves detailed information about coupon usage, optionally filtered by IDs.
   *
   * @param array $ids An array of IDs to filter the results by. Defaults to an empty array.
   * @param string $field The field to use for filtering. Defaults to 'ct.coupon_id'.
   * @return CRM_Core_DAO The DAO result set containing coupon usage details.
   */
  public static function getCouponUsedBy($ids = [], $field = 'ct.coupon_id') {
    if (!empty($ids)) {
      if (empty($field)) {
        $field = 'ct.coupon_id';
      }
      if (!strstr($field, '.')) {
        $field = 'ct.'.$field;
      }
      $couponIds = CRM_Utils_Array::implode(',', $ids);
      $sql = "SELECT c.*, ct.id as coupon_track_id, ct.*, contact.sort_name, contrib.total_amount, ct.used_date FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id INNER JOIN civicrm_contact contact ON ct.contact_id = contact.id INNER JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE ct.used_date IS NOT NULL AND {$field} IN({$couponIds}) ORDER BY ct.used_date DESC";
      return CRM_Core_DAO::executeQuery($sql);
    }
    else {
      $sql = "SELECT c.*, ct.id as coupon_track_id, ct.*, contact.sort_name, contrib.total_amount, ct.used_date FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id INNER JOIN civicrm_contact contact ON ct.contact_id = contact.id INNER JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE ct.used_date IS NOT NULL ORDER BY ct.used_date DESC";
      return CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Retrieves coupon usage details for specific contacts and coupons.
   *
   * @param array $contactIds An array of contact IDs.
   * @param array $ids An array of coupon IDs.
   * @return CRM_Core_DAO|null The DAO result set or null if input is invalid.
   */
  public function getContactCouponUsed($contactIds, $ids) {
    if (!is_array($ids) || !is_array($contactIds)) {
      return;
    }
    if (empty($ids) || empty($contactIds)) {
      return;
    }
    $couponIds = CRM_Utils_Array::implode(',', $ids);
    if (!empty($contactIds) && is_array($contactIds)) {
      $contactIdsWhere = ' AND ct.contact_id IN('.CRM_Utils_Array::implode(',', $contactIds).')';
    }

    $sql = "SELECT c.*, ct.id as coupon_track_id, ct.*, contrib.total_amount, ct.used_date, contrib.contribution_status_id FROM civicrm_coupon c INNER JOIN civicrm_coupon_track ct ON ct.coupon_id = c.id INNER JOIN civicrm_contribution contrib ON contrib.id = ct.contribution_id WHERE ct.used_date IS NOT NULL AND ct.coupon_id IN({$couponIds}) {$contactIdsWhere} ORDER BY ct.used_date DESC";
    return CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Retrieves coupon data using common DAO retrieval methods.
   *
   * @param array &$params An associative array of parameters for retrieval.
   * @param array &$defaults An associative array to store the retrieved defaults.
   * @return mixed The result of the commonRetrieve call.
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Coupon_DAO_Coupon', $params, $defaults);
  }

  /**
   * Formats or cleans up entity data within the parameters array.
   *
   * @param array &$params The parameters array, modified in place.
   */
  public static function formatCouponEntity(&$params) {
    foreach ($params as $key => $value) {
      if (in_array($key, ['civicrm_event', 'civicrm_price_field_value'])) {
        if (!is_array($value)) {
          $val = explode(',', $value);
        }
        else {
          $val = $value;
        }
        foreach ($val as $k => $n) {
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

  /**
   * Adds a coupon input element to a form.
   *
   * @param CRM_Core_Form &$form The form object to which the element is added.
   */
  public static function addQuickFormElement(&$form) {
    $ele = $form->add('text', 'coupon', ts('Coupon'), ['placeholder' => ts('Enter coupon code')]);
    if (!empty($form->_coupon['coupon_track_id'])) {
      $form->add('hidden', 'coupon_track_id', $form->_coupon['coupon_track_id']);
      $form->assign('coupon', $form->_coupon);
      $form->assign('coupon_json', json_encode($form->_coupon));
      $ele->freeze();
    }
    else {
      $form->add('hidden', 'coupon_is_valid', FALSE);
    }
  }

  /**
   * Validates if a given coupon code is valid based on its properties and current time.
   *
   * @param string $code The coupon code to validate.
   * @return array|bool|null Returns the coupon data array if valid, FALSE if invalid, or NULL if the code is empty.
   */
  public static function validFromCode($code) {
    if (empty($code)) {
      return NULL;
    }
    $valid = TRUE;
    $coupon = self::getCoupon(NULL, $code);
    $currentTime = CRM_REQUEST_TIME;
    if (!empty($coupon) && $coupon['code'] == $code) {
      if (!empty($coupon['start_date']) && $currentTime < strtotime($coupon['start_date'])) {
        $valid = FALSE;
      }
      if (!empty($coupon['end_date']) && $currentTime > strtotime($coupon['end_date'])) {
        $valid = FALSE;
      }
      if (!$coupon['is_active']) {
        $valid = FALSE;
      }

      // whatever status , used is used.
      $couponCount = self::getCouponUsed([$coupon['id']]);
      $coupon['used'] = $couponCount[$coupon['id']];
      if (!empty($coupon['count_max'])) {
        if ($coupon['count_max'] <= $coupon['used'] && $coupon['used'] != 0) {
          $valid = FALSE;
        }
      }

      CRM_Utils_Hook::validateCoupon($coupon, $valid, 'code');
      if ($valid) {
        // success
        return $coupon;
      }
    }
    $valid = FALSE;
    return $valid;
  }

  /**
   * Validates if a coupon code is valid for a specific event and potentially other entities.
   *
   * @param string $code The coupon code to validate.
   * @param int $eventId The ID of the event to validate against.
   * @param array $additionalVerify An associative array of additional entities and their IDs to verify against.
   * @return bool TRUE if the coupon is valid for the event and additional verifications, FALSE otherwise.
   */
  public static function validEventFromCode($code, $eventId, $additionalVerify = []) {
    $valid = TRUE;
    $coupon = self::validFromCode($code);
    if (empty($coupon)) {
      return FALSE;
    }

    // always validate event when given eventId
    if (!empty($eventId)) {
      // we limited used for specific event, but this event id not listed
      if (!empty($coupon['used_for']['civicrm_event']) && empty($coupon['used_for']['civicrm_event'][$eventId])) {
        $valid = FALSE;
      }
    }

    // validate additional
    if (!empty($additionalVerify) && is_array($additionalVerify)) {
      foreach ($additionalVerify as $entityTable => $entityIds) {
        $matches = [];
        if (!is_array($entityIds)) {
          $entityIds = explode(',', $entityIds);
        }

        // only validate when coupon setting has limited specify entity table
        if (!empty($coupon['used_for'][$entityTable])) {
          $matches = array_intersect($coupon['used_for'][$entityTable], $entityIds);
          if (empty($matches)) {
            $valid = FALSE;
          }
        }
      }
    }
    CRM_Utils_Hook::validateCoupon($coupon, $valid, 'event');
    if ($valid) {
      return $coupon;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves and validates coupon information based on form submission values.
   *
   * @param CRM_Core_Form $form The form object.
   * @param array $fields An associative array of submitted form fields.
   * @return array|bool Returns the validated coupon data if found and valid, otherwise FALSE.
   */
  public static function getCouponFromFormSubmit($form, $fields) {
    $code = $fields['coupon'];
    if (!empty($code)) {
      if (CRM_Utils_Array::value('priceSetId', $fields)) {
        // Get the used arraies
        $usedOptionsCount = [];
        $usedOptions = [];
        $usedOptionsSum = [];
        $totalAmount = $fields['amount'];
        foreach ($fields as $fieldKey => $value) {
          if (preg_match('/^price_\d+$/', $fieldKey) && !empty($value)) {
            $fieldId = str_replace('price_', '', $fieldKey);
            if (is_array($value)) {
              foreach ($value as $optionKey => $count) {
                $countFieldKey = 'price_'.$fieldId.'_'.$optionKey.'_count';
                if (!empty($fields[$countFieldKey])) {
                  $count = $fields[$countFieldKey];
                }
                $usedOptionsCount[$optionKey] = $count;
                $usedOptions[$optionKey] = $option = $form->_values['fee'][$fieldId]['options'][$optionKey];
                $usedOptionsSum[$optionKey] = $count * $option['amount'];
                // $totalAmount += $usedOptionsSum[$optionKey];
              }
            }
          }
        }

        if (!empty($usedOptionsCount)) {
          $usedOptionsCountText = CRM_Utils_Array::implode(',', array_keys($usedOptionsCount));
          $additionalVerify = ['civicrm_price_field_value' => $usedOptionsCountText];
        }
      }
      else {
        // Not use price set.
        if ($fields['is_primary']) {
          // postProcess
          $totalAmount = $fields['amount'];
        }
        else {
          // form rule validation (no is_primary tag)
          $availableAmount = [];
          foreach ($form->_values['fee'] as $option) {
            $availableAmount[$option['amount_id']] = $option['value'];
          }
          // refs #29642, collect all options of discount
          if (!empty($form->_values['discount'])) {
            foreach ($form->_values['discount'] as $discount) {
              foreach ($discount as $option) {
                $availableAmount[$option['amount_id']] = $option['value'];
              }
            }
          }
          $totalAmount = $availableAmount[$fields['amount']];
        }
      }
      if (empty($coupon)) {
        $coupon = self::validEventFromCode($code, $form->_eventId, $additionalVerify);
      }
      if (!empty($coupon)) {
        $coupon['usedOptionsCount'] = $usedOptionsCount;
        $coupon['usedOptions'] = $usedOptions;

        // Count correct totalAmount by $coupon used for
        if (!empty($coupon['used_for']['civicrm_price_field_value'])) {
          $matches = array_intersect($coupon['used_for']['civicrm_price_field_value'], array_keys($usedOptionsSum));
          $totalAmount = 0;
          foreach ($matches as $entity_id) {
            $totalAmount += $usedOptionsSum[$entity_id];
          }
        }

        $coupon['totalAmount'] = $totalAmount;
        return $coupon;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Calculates and applies coupon discount to form values.
   *
   * @param CRM_Core_Form $form The form object.
   * @param array $submitValues An associative array of submitted form values.
   */
  public static function countAmount($form, $submitValues) {
    $coupon = self::getCouponFromFormSubmit($form, $submitValues);
    if (!empty($coupon)) {
      $usedOptionsCount = $coupon['usedOptionsCount'];
      $usedOptions = $coupon['usedOptions'];
      $totalAmount = $coupon['totalAmount'];

      if ($coupon['coupon_type'] == 'monetary') {
        $totalDiscount = ($totalAmount < $coupon['discount']) ? $totalAmount : $coupon['discount'];
      }
      else {
        // coupon_type == percentage
        // the civicrm_price_field_value's totalamount is calculate when getCouponFromFormSubmit()
        $totalDiscount = round($totalAmount * $coupon['discount'] / 100);
      }
      $form->_totalDiscount = $totalDiscount;
      $form->_coupon = $coupon;
      $form->set('coupon', $coupon);
    }
    else {
      $form->set('coupon', NULL);
    }
  }

  /**
   * Checks for errors related to coupon application based on form submission.
   *
   * @param CRM_Core_Form $form The form object.
   * @param array $submitValues An associative array of submitted form values.
   * @return array|null An array of errors if any, or null if no errors.
   */
  public static function checkError($form, $submitValues) {
    if (empty($submitValues['coupon'])) {
      return ;
    }
    $coupon = self::getCouponFromFormSubmit($form, $submitValues);
    if (!empty($coupon)) {
      $usedOptionsCount = $coupon['usedOptionsCount'];
      $usedOptions = $coupon['usedOptions'];
      $totalAmount = $coupon['totalAmount'];

      // Coupon is valid, But we don't check the amount.
      // Check the amount is enough to minimal_amount.
      // the civicrm_price_field_value's totalamount is calculate when getCouponFromFormSubmit()

      if ($totalAmount < $coupon['minimal_amount']) {
        $errors['coupon'] = ts("The amount is not enough for coupon. The minimal amount is %1. The summary of validated amount is %2.", [
          1 => $coupon['minimal_amount'],
          2 => $totalAmount,
        ]);
      }
    }
    elseif ($submitValues['coupon_is_valid']) {
      // If coupon_id_valid is check, Told user that it's not valid since now.
      $errors['coupon'] = ts("The coupon is not valid for this selection.");
    }
    else {
      // Only Show notification text.
      CRM_Core_Session::setStatus(ts('The coupon is not applied for any selected option.'));

    }
    return $errors;
  }

  /**
   * Adds a record to the coupon track table to log coupon usage.
   *
   * @param int $couponId The ID of the coupon used.
   * @param int $contributionId The ID of the contribution associated with the usage.
   * @param int|null $contactId The ID of the contact who used the coupon (optional).
   * @param int|null $discountAmount The amount of discount applied (optional).
   * @return CRM_Coupon_DAO_CouponTrack The newly created coupon track object.
   */
  public static function addCouponTrack($couponId, $contributionId, $contactId = NULL, $discountAmount = NULL) {
    $coupon = new CRM_Coupon_DAO_Coupon();
    $coupon->id = $couponId;
    $coupon->find(TRUE);
    $couponTrack = new CRM_Coupon_DAO_CouponTrack();
    $couponTrack->coupon_id = $coupon->id;
    $couponTrack->contribution_id = $contributionId;
    $couponTrack->contact_id = $contactId;
    $couponTrack->used_date = date('Y-m-d H:i:s');
    $couponTrack->discount_amount = $discountAmount;
    $couponTrack->save();
    return $couponTrack;
  }
}
