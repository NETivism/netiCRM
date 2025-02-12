<?php

class CRM_Coupon_Page_Coupon extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks;

  protected $_pager = NULL;

  /**
   * Get the action links for this page.
   *
   * @param null
   *
   * @return  array   array of action links that we need to display for the browse screen
   * @access public
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this coupon?');
      $copyExtra = ts('Are you sure you want to make a copy of this coupon?');
      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/coupon',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Edit'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&id=%%id%%&key=%%key%%',
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Coupon_BAO_Coupon' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Coupon'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Coupon_BAO_Coupon' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Coupon'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/coupon',
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_actionLinks;
  }

  function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      CRM_Utils_System::appendBreadCrumb(array(
        0 => array('title' => ts('Coupon'), 'url' => CRM_Utils_System::url('civicrm/admin/coupon', 'reset=1'))
      ));
      $this->edit($id, $action);
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts("A copy of this coupon has been created"));
      $this->copy();
    }
    elseif ($action & CRM_Core_Action::EXPORT) {
      $this->export();
      CRM_Utils_System::civiExit();
    }
    else {
      // if action is delete do the needful.
      if ($action & (CRM_Core_Action::DELETE)) {
        $coupon = new CRM_Coupon_DAO_Coupon();
        $coupon->id = $id;
        $coupon->find(TRUE);
        if (!empty($coupon->N)) {
          // prompt to delete
          $session = &CRM_Core_Session::singleton();
          $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/coupon', 'action=browse'));
          $controller = new CRM_Core_Controller_Simple('CRM_Coupon_Form_DeleteCoupon', 'Delete Coupon', NULL);
          $controller->set('id', $coupon->id);
          $controller->setEmbedded(TRUE);
          $controller->process();
          $controller->run();
        }
      }

      // finally browse 
      $this->browse();
    }
    parent::run();
  }
  
  function browse() {
    // get all coupon
    $coupon = array();
    $usedFor = array(
      'civicrm_event' => ts('Event'),
      'civicrm_price_field_value' => ts('Price Option'),
    );
    $this->assign('usedForName', $usedFor);

    $priceSets = CRM_Price_BAO_Field::getPriceLevels();
    $priceOptions = array();
    foreach($priceSets as $set => &$field) {
      foreach($field as $key => $val) {
        $field_id = str_replace('priceset:', '', $key);
        if (is_numeric($field_id)) {
          $priceOptions[$field_id] = $val;
        }
      }
      if(is_string($field)){
        $field_id = str_replace('priceset:', '', $set);
        if (is_numeric($field_id)) {
          $priceOptions[$field_id] = $field;
        }
      }
    }

    $entityTable = CRM_Utils_Request::retrieve('entity_table', 'String', $this, FALSE);
    $entityId = CRM_Utils_Request::retrieve('entity_id', 'Positive', $this, FALSE);

    $filter = array();
    $filter['entity_table'] = CRM_Utils_Request::retrieve('entity_table', 'String', $this);
    $filter['entity_id'] = CRM_Utils_Request::retrieve('entity_id', 'Positive', $this);
    $filter['code'] = CRM_Utils_Request::retrieve('code', 'String', $this);
    $filter['description'] = CRM_Utils_Request::retrieve('description', 'String', $this);
    $filter['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    foreach($filter as $f => $v) {
      if(empty($v)) {
        unset($filter[$f]);
      }
    }
    if (count($filter)) {
      $this->assign('clear_filter', 1);
    }
    if (!empty($filter['code']) && strstr($filter['code'], '-')) {
      $this->assign('default_prefix', $filter['code']);
    }

    $dao = CRM_Coupon_BAO_Coupon::getCouponList($filter);
    if ($dao->N) {
      $this->pager($dao->N);
      list($filter['offset'], $filter['limit']) = $this->_pager->getOffsetAndRowCount();
      unset($dao);
    }
    $dao = CRM_Coupon_BAO_Coupon::getCouponList($filter);
    while ($dao->fetch()) {
      if (!empty($coupon[$dao->id]) && !empty($dao->entity_table)) {
        if($dao->entity_table == 'civicrm_event'){
          $coupon[$dao->id]['used_for'][$dao->entity_table][$dao->entity_id] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $dao->entity_id, 'title');
        }
        elseif($dao->entity_table == 'civicrm_price_field_value'){
          $coupon[$dao->id]['used_for'][$dao->entity_table][$dao->entity_id] = $priceOptions[$dao->entity_id];
        }
        continue;
      }
      $coupon[$dao->id] = array();
      foreach($dao as $field => $value) {
        if ($field == 'entity_table') {
          if($dao->entity_table == 'civicrm_event'){
            $coupon[$dao->id]['used_for'][$dao->entity_table][$dao->entity_id] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $dao->entity_id, 'title');
          }
          elseif($dao->entity_table == 'civicrm_price_field_value'){
            $coupon[$dao->id]['used_for'][$dao->entity_table][$dao->entity_id] = $priceOptions[$dao->entity_id];
          }
        }
        if ($field == 'entity_id' || $field[0] == '_') {
          continue;
        }
        else {
          $coupon[$dao->id][$field] = $value;
        }
      }

      // form all action links
      $action = array_sum(array_keys($this->actionLinks()));

      // update enable/disable links depending on coupon properties.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $name = get_class($this);
      $key = CRM_Core_Key::get($name);
      $this->assign('key', $key);

      $coupon[$dao->id]['action'] = CRM_Core_Action::formLink(
        self::actionLinks(),
        $action,
        array(
          'id' => $dao->id,
          'key' => $key
        )
      );
    }
    $couponIds = array_keys($coupon);
    $couponUses = CRM_Coupon_BAO_Coupon::getCouponUsed($couponIds);
    foreach($couponUses as $couponId => $count) {
      $coupon[$couponId]['count_max'] = $count." / ".$coupon[$couponId]['count_max'];
    }
    $this->assign('rows', $coupon);
    return $coupon;
  }

  function export() {
    $filter = array();
    $filter['entity_table'] = CRM_Utils_Request::retrieve('entity_table', 'String', $this);
    $filter['entity_id'] = CRM_Utils_Request::retrieve('entity_id', 'Positive', $this);
    $filter['code'] = CRM_Utils_Request::retrieve('code', 'String', $this);
    $filter['description'] = CRM_Utils_Request::retrieve('description', 'String', $this);
    $filter['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    foreach($filter as $f => $v) {
      if(empty($v)) {
        unset($filter[$f]);
      }
    }

    $dao = CRM_Coupon_BAO_Coupon::getCouponList($filter);
    if ($filter['code']) {
      $code = '-'.trim($filter['code'], '-');
    }
    $filename = 'coupon-export'.$code.'.xlsx';
    $writer = CRM_Core_Report_Excel::singleton('excel');
    $writer->openToBrowser($filename);
    $header = array(
      ts('ID'),
      ts('Start Date'),
      ts('End Date'),
      ts('Coupon Code'),
      ts('Coupon Type'),
      ts('Discounted Fees'),
      ts('Minimum Amount'),
      ts('Used').' / '.ts('Max'),
      ts('Description'),
      ts('Enabled?'),
    );
    $writer->addRow($header);

    $exists = array();
    while ($dao->fetch()) {
      if (!empty($exists[$dao->id])) {
        continue;
      }
      $exists[$dao->id] = 1;
      $coupon = array();
      foreach($dao as $field => $value) {
        if ($field == 'entity_table' || $field == 'entity_id' || $field[0] == '_' || $field == 'N') {
          continue;
        }
        else {
          $coupon[$field] = $value;
        }
      }
      $coupon['discount'] = $coupon['coupon_type'] == 'percentage' ? $coupon['discount'].'%' : (int)$coupon['discount'];
-     $coupon['coupon_type'] = ts(ucfirst($coupon['coupon_type']));

      $couponUses = CRM_Coupon_BAO_Coupon::getCouponUsed(array($coupon['id']));
      $coupon['count_max'] = $couponUses[$coupon['id']]." / ".$coupon['count_max'];
      $writer->addRow($coupon);
      unset($coupon);
      unset($couponUses);
    }
    $writer->close();
  }

  function edit($id, $action) {
    // create a simple controller for editing price sets
    $controller = new CRM_Core_Controller_Simple('CRM_Coupon_Form_Coupon', ts('Coupon'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/coupon', 'action=browse'));
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }


  /**
   * This function is to make a copy of a coupon, including
   * all the fields in the page
   *
   * @return void
   * @access public
   */
  function copy() {
    $key = CRM_Utils_Request::retrieve('key', 'String',
      CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
    );

    $name = get_class($this);
    if (!CRM_Core_Key::validate($key, $name)) {
      return CRM_Core_Error::statusBounce(ts('Sorry, we cannot process this request for security reasons. The request may have expired or is invalid. Please return to the coupon list and try again.'));
    }

    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    CRM_Coupon_BAO_Coupon::copy($id);

    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
  }

  function pager($total) {
    $params = array(); 
    $params['status'] = '';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $params['total'] = $total;
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }
}
