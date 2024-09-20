<?php
class CRM_Coupon_Form_Coupon extends CRM_Core_Form {
  
  public $_batch;
  public $_prefix;
  public $_defaults;
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
    $this->_batch = CRM_Utils_Request::retrieve('batch', 'String', $this, FALSE);
    $this->_prefix = CRM_Utils_Request::retrieve('prefix', 'String', $this, FALSE);
    if (!empty($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Coupon_BAO_Coupon::retrieve($params, $this->_defaults);
      if ($this->_action == CRM_Core_Action::UPDATE && empty($this->_defaults['id'])) {
        CRM_Core_Error::fatal(ts('No any coupon found by this url.'));
      }
      CRM_Utils_System::setTitle(ts("Coupon").': '.$this->_defaults['code']);
    }
    if ($this->_batch) {
      CRM_Utils_System::setTitle(ts('Bulk').' '.ts('Create').' '.ts("Coupon"));
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/coupon/batch', 'reset=1'));
    }
  }

  static function formRule($fields, $files, $form) {
    $errors = array();
    if (!empty($fields['batch_prefix'])) {
      // check action
      if ($form->_action != CRM_Core_Action::ADD) {
        $errors['batch_prefix'] = ts("You can only use batch on create new coupon");
      }

      // check prefix exists or not
      $duplicated = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_coupon WHERE code LIKE %1", array(
        1 => array($fields['batch_prefix'].'-%', 'String'),
      ));
      if ($duplicated) {
        $errors['batch_prefix'] = ts('Name already exists in Database.');
      }
    }
    if (!empty($fields['code'])) {
      if (!preg_match('/^[0-9a-z-]+$/i', $fields['code'])) {
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
    if ($this->_batch && $this->_action == CRM_Core_Action::ADD) {
      $this->add('text', 'batch_prefix', ts('Coupon Code Prefix'), $attr, TRUE);
      $this->addNumber('num_generate', ts('Number to Generate'), array('min' => 1, 'max' => 20000), TRUE);
    }
    else {
      $this->add('text', 'code', ts('Coupon Code'), $attr, TRUE);
    }
    $this->add('text', 'description', ts('Description'), NULL, TRUE);
    $this->addDateTime('start_date', ts('Start Date'), false);
    $this->addDateTime('end_date', ts('End Date'), false);
    $this->addSelect('coupon_type', ts('Coupon Type'), array(
      'monetary' => ts('Monetary'),
      'percentage' => ts('Percentage'),
    ), NULL, TRUE);
    $this->addNumber('discount', ts('Discounted Fees'), NULL, TRUE);
    $this->addNumber('minimal_amount', ts('Minimum Amount'), NULL, TRUE);
    if ($this->_batch) {
      $ele = $this->addNumber('count_max', ts('Maximum Uses'), array('min' => 1, 'max' => 100), TRUE);
      $this->setDefaults(array('count_max' => 1));
    }
    else {
      $this->addNumber('count_max', ts('Maximum Uses'), NULL, FALSE);
    }

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
          'name' => $this->_batch ? ts('Bulk').' '.ts('Create') : ts('Save'),
          'isDefault' => TRUE,
          'js' => array('data' => $this->_batch ? 'batch-create' : ''),
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
    else {
      if ($this->_prefix) {
        $defaults['code'] = $this->_prefix;
      }
      $defaults['is_active'] = 1;
    }
    return $defaults;
  }

  public function postProcess() {
    $fields = CRM_Coupon_DAO_Coupon::fields();
    $params = $this->controller->exportValues();
    if (!empty($params['batch_prefix']) && !empty($params['num_generate'])) {
      $batchPrefix = $params['batch_prefix'];
      $numGenerate = $params['num_generate'];
      unset($param['batch_prefix']);
      unset($param['num_generate']);
      unset($param['code']);
    }

    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    if(empty($params['start_date'])){
      $params['start_date'] = 'NULL';
    }
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time']);
    if(empty($params['end_date'])){
      $params['end_date'] = 'NULL';
    }

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

    if ($this->_id && !$prefix) {
      $coupon['id'] = $this->_id;
    }
    if ($batchPrefix && $numGenerate) {
      // batch
      $generated = array();
      $seed = mt_rand(101, 200);
      $plus = mt_rand(1, 100);
      $try = 0;
      while(count($generated) < $numGenerate) {
        $seed = $seed+mt_rand(1,10);
        for($i = 10000; $i < 10000+$numGenerate; $i++) {
          $code = '';
          $n = $i*$seed+$plus;
          $code = str_replace(array('=','+','/'),'',base64_encode($n));
          $code  = str_shuffle($code);
          $generated[$code] = 1;
          if($try) {
            if (count($generated) >= $numGenerate) {
              break;
            }
          }
        }
        $try++;
        if ($try > 2) {
          break;
        }
      }
      foreach($generated as $code => $dontcare) {
        $code = $batchPrefix.'-'.$code;
        $coupon['code'] = $code;
        CRM_Coupon_BAO_Coupon::create($coupon);
        unset($coupon['code']);
      }
    }
    else {
      // single
      CRM_Coupon_BAO_Coupon::create($coupon);
    }
  }
}
