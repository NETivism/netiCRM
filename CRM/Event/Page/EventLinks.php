<?php
class CRM_Event_Page_EventLinks extends CRM_Core_Page {

  protected $_id;

  function run() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);
    $params = array('id' => $this->_id);
    $defaults = array();
    CRM_Event_BAO_Event::retrieve($params, $defaults);
    $this->assign('event', $defaults);
    CRM_Utils_System::setTitle($defaults['event_title'].' - '.ts('Event Links'));
    $this->assign('id', $this->_id);
    parent::run();
  }
}