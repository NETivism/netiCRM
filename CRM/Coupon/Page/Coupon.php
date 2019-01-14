<?php
/**
 * Page for invoking report templates
 */
class CRM_Coupon_Page_Coupon extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks;

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
          'qs' => 'action=copy&id=%%id%%',
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
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Coupon_BAO_Coupon' . '\',\'' . 'enable-disable' . '\' );"',
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
    else {
      // if action is delete do the needful.
      if ($action & (CRM_Core_Action::DELETE)) {
        $usedBy = &CRM_Coupon_BAO_Coupon::getUsedBy($id);
        if (empty($usedBy)) {
          // prompt to delete
          $session = &CRM_Core_Session::singleton();
          $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/coupon', 'action=browse'));
          $controller = new CRM_Core_Controller_Simple('CRM_Coupon_Form_CouponDelete', 'Delete Coupon', NULL);
          // $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, false, 0);
          $controller->set('id', $id);
          $controller->setEmbedded(TRUE);
          $controller->process();
          $controller->run();
        }
        else {
          // add breadcrumb
          $this->assign('usedCouponSetTitle', CRM_Coupon_BAO_Coupon::getTitle($sid));
          $this->assign('usedBy', $usedBy);
          $comps = array("Event" => "civicrm_event",
            "Contribution" => "civicrm_contribution_page",
          );
          $contexts = array();
          foreach ($comps as $name => $table) {
            if (array_key_exists($table, $usedBy)) {
              $contexts[] = $name;
            }
          }
          $this->assign('contexts', $contexts);
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
    $usedBy = array(
      'civicrm_event' => ts('Event'),
      'civicrm_price_field_value' => ts('Price Option'),
    );
    $entityTable = CRM_Utils_Request::retrieve('entity_table', 'String', $this, FALSE);
    $entityId = CRM_Utils_Request::retrieve('entity_id', 'Positive', $this, FALSE);

    $filter = array();
    $filter['entity_table'] = CRM_Utils_Request::retrieve('entity_table', 'String', $this);
    $filter['entity_id'] = CRM_Utils_Request::retrieve('entity_id', 'Positive', $this);
    $filter['code'] = CRM_Utils_Request::retrieve('code', 'String', $this);
    $filter['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $dao = CRM_Coupon_BAO_Coupon::getCouponList($filter);
    foreach($filter as $f => $v) {
      if(empty($v)) {
        unset($filter[$f]);
      }
    }
    if (count($filter)) {
      $this->assign('clear_filter', 1);
    }
    while ($dao->fetch()) {
      if (!empty($coupon[$dao->id]) && !empty($dao->entity_table)) {
        $coupon[$dao->id]['used_by'][$dao->entity_table] = $usedBy[$dao->entity_table];
        continue;
      }
      $coupon[$dao->id] = array();
      foreach($dao as $field => $value) {
        if ($field == 'entity_table') {
          $coupon[$dao->id]['used_by'][$dao->entity_table] = $usedBy[$dao->entity_table];
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

      $coupon[$dao->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action, array('id' => $dao->id));
    }
    $this->assign('rows', $coupon);
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
}
