<?php

class CRM_Contribute_StateMachine_NewebpayImport extends CRM_Core_StateMachine {

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

    $this->_pages = [
      'CRM_Contribute_Form_NewebpayImport_Upload' => NULL,
      'CRM_Contribute_Form_NewebpayImport_Preview' => NULL,
      'CRM_Contribute_Form_NewebpayImport_Summary' => NULL,
    ];

    $this->addSequentialPages($this->_pages, $action);
  }
}

