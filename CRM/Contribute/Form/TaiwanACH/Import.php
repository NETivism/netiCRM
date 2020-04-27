<?php

class CRM_Contribute_Form_TaiwanACH_Import extends CRM_Core_Form {
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH_Import', 'formRule'), $this);
  }

  function buildQuickForm() {

    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Import'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();

    return $errors;
  }

  function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }


  function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);
  }
}
