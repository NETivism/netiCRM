<?php
class CRM_Event_Page_EventLinks extends CRM_Core_Page {

  protected $_id;

  function run() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);
    $params = ['id' => $this->_id];
    $defaults = [];
    CRM_Event_BAO_Event::retrieve($params, $defaults);
    $this->assign('event', $defaults);
    CRM_Utils_System::setTitle($defaults['event_title'].' - '.ts('Event Links'));
    $this->assign('id', $this->_id);

    $shortenInfo = CRM_Core_OptionGroup::getValue('shorten_url', 'civicrm_event.info.'.$this->_id, 'name', 'String', 'value');
    if (!empty($shortenInfo)) {
      $this->assign('shorten_info', $shortenInfo);
    }
    $shortenRegister = CRM_Core_OptionGroup::getValue('shorten_url', 'civicrm_event.register.'.$this->_id, 'name', 'String', 'value');
    if (!empty($shortenRegister)) {
      $this->assign('shorten_register', $shortenRegister);
    }
    parent::run();
  }
}