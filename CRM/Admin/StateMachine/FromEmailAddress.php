<?php

class CRM_Admin_StateMachine_FromEmailAddress extends CRM_Core_StateMachine {

  /**
   * class constructor
   *
   * @param object  CRM_Import_Controller
   * @param int     $action
   *
   * @return object CRM_Import_StateMachine
   */
  function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $session = CRM_Core_Session::singleton();
    $session->set('singleForm', FALSE);

    $this->_pages = array(
      'CRM_Admin_Form_FromEmailAddress_Edit' => NULL,
      'CRM_Admin_Form_FromEmailAddress_EmailVerify' => NULL,
      'CRM_Admin_Form_FromEmailAddress_DNSVerify' => NULL,
      'CRM_Admin_Form_FromEmailAddress_Finish' => NULL,
    );
    $skipEmailVerify = $controller->get('skipEmailVerify');
    if ($skipEmailVerify) {
      unset($this->_pages['CRM_Admin_Form_FromEmailAddress_EmailVerify']);
    }

    $this->addSequentialPages($this->_pages);
  }
}

