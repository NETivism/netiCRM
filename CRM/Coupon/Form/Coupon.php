<?php
class CRM_Coupon_Form_Coupon extends CRM_Core_Form {
  
  /**
   * the set id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_id;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);
    if (!empty($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Coupon_BAO_Coupon::retrieve($params, $this->_defaults);
      if ($this->_action == CRM_Core_Action::UPDATE && empty($this->_defaults['id'])) {
        CRM_Core_Error::fatal(ts('No any coupon found by this url.'));
      }
      CRM_Utils_System::setTitle(ts("Coupon").': '.$this->_defaults['code']);
    }
  }

  static function formRule($fields, $files, $form) {
    $errors = array();
    if (!empty($fields['code'])) {
      if (!preg_match('/^[0-9a-z]+$/i', $fields['code'])) {
        $errors['code'] = ts('Name can only consist of alpha-numeric characters');
      }
      $id = $form->_id ? $form->_id : 0;
      $duplicated = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_coupon WHERE code LIKE %1 AND id != %2", array(
        1 => array($fields['code'], 'String'),
        2 => array($id, 'Integer'),
      ));
      if ($duplicated) {
        $errors['code'] = ts('Name already exists in Database.');
      }
    }
    if(!empty($fields['start_date']) && !empty($fields['end_date'])){
      $start_date = CRM_Utils_Date::processDate($fields['start_date'], $fields['start_date_time']);
      $end_date = CRM_Utils_Date::processDate($fields['end_date'], $fields['end_date_time']);
      if(strtotime($start_date) >= strtotime($end_date)){
        $errors["start_date_time"] = ts('The discount end date cannot be prior to the start date.');
      }
    }
    if ($fields['coupon_type'] == 'percentage' && $fields['discount'] > 100) {
      $errors['discount'] = ts("Cannot over 100% off when coupon type is percentage.");
    }
    return empty($errors) ? TRUE : $errors;
  }

  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $attr = array('readonly' => $readonly); 
    }
    else {
      $attr = array();
    }
    $this->add('text', 'code', ts('Coupon Code'), $attr, TRUE);
    $this->add('text', 'description', ts('Description'), NULL, TRUE);
    $this->addDateTime('start_date', ts('Start Date'), false);
    $this->addDateTime('end_date', ts('End Date'), false);
    $this->addSelect('coupon_type', ts('Coupon Type'), array(
      'monetary' => ts('Monetary'),
      'percentage' => ts('Percentage'),
    ), NULL, TRUE);
    $this->addNumber('discount', ts('Discounted Fees'), NULL, TRUE);
    $this->addNumber('minimal_amount', ts('Minimum Amount'), NULL, TRUE);
    $this->addNumber('count_max', ts('Maximum Uses'), NULL, FALSE);

    $events = CRM_Event_BAO_Event::getEvents(TRUE);
    if (!empty($events)) {
      $this->addSelect('civicrm_event', ts('Limited on Events'), $events, array('multiple' => 'multiple'), FALSE);
    }

    $priceSets = CRM_Price_BAO_Field::getPriceLevels();
    $priceOptions = array();
    foreach($priceSets as $set => &$field) {
      foreach($field as $key => $val) {
        $key = str_replace('priceset:', '', $key);
        if (is_numeric($key)) {
          $priceOptions[$set][$key] = $val;
        }
      }
    }

    $this->addSelect('civicrm_price_field_value', ts('Limited on Price Option'), $priceOptions, array('multiple' => 'multiple'), FALSE);

    $this->add('checkbox', 'is_active', ts('Active?'));
    $this->addButtons(array(
        array('type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    $this->addFormRule(array('CRM_Coupon_Form_Coupon', 'formRule'), $this);
  }

  function setDefaultValues() {
    $defaults = array();
    if (isset($this->_id)) {
      $defaults = $this->_defaults;
      if(!empty($defaults['start_date'])){
        list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('start_date', $defaults), 'activityDateTime');
      }
      if(!empty($defaults['end_date'])){
        list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('end_date', $defaults), 'activityDateTime');
      }

      $filter = array('id' => $this->_id);
      $dao = CRM_Coupon_BAO_Coupon::getCouponList($filter);
      while($dao->fetch()) {
        if (!empty($dao->entity_table) && !empty($dao->entity_id)) {
          $defaults[$dao->entity_table][$dao->entity_id] = $dao->entity_id;
        }
      }

    }
    return $defaults;
  }

  public function postProcess() {
    $fields = CRM_Coupon_DAO_Coupon::fields();
    $params = $this->controller->exportValues();
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time']);

    $additional = array();
    $coupon = array();
    foreach($params as $key => $value) {
      if (isset($fields[$key])) {
        $coupon[$key] = $value;
      }
      else {
        $additional[$key] = $value;
      }
    }

    // format additional
    CRM_Coupon_BAO_Coupon::formatCouponEntity($additional);
    $coupon += $additional;
    if ($this->_id) {
      $coupon['id'] = $this->_id;
    }
    CRM_Coupon_BAO_Coupon::create($coupon);
  }
}
