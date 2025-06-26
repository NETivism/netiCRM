<?php

class CRM_Contact_StateMachine_AnnualReceiptEmail extends CRM_Core_StateMachine {

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
      'CRM_Contact_Form_Task_AnnualReceiptEmail_SearchOption' => NULL,
      'CRM_Contact_Form_Task_AnnualReceiptEmail_MailingOption' => NULL,
      'CRM_Contact_Form_Task_AnnualReceiptEmail_Finish' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }
}

