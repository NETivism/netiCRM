<?php

class CRM_Contribute_Form_TaiwanACH_Preview extends CRM_Core_Form {
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH_Preview', 'formRule'), $this);
    $this->_parseResult = $this->get('parseResult');
    if (!empty($this->_parseResult) && !empty($this->_parseResult['process_id'])) {
      $this->assign('parseResult', $this->_parseResult);
    }
  }

  function buildQuickForm() {
    if (!empty($this->_parseResult)) {
      $this->addButtons(array(
          array('type' => 'back',
            'name' => ts('<< Previous'),
          ),
          array('type' => 'upload',
            'name' => ts('Import Now >>'),
            'isDefault' => TRUE,
          ),
          array('type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      CRM_Core_Session::setStatus(ts('Invalid file being import, abort.'), FALSE, 'error');
      $this->addButtons(array(
          array('type' => 'back',
            'name' => ts('<< Previous'),
          ),
          array('type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();
    if (empty($self->_parseResult)) {
      $errors['qfKey'] = ts('Invalid file being import, abort.');
    }
    return $errors;
  }

  function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }


  function postProcess() {
    // send parseResult into BAO
    switch($this->_parseResult['payment_type']) {
      case 'ACH Bank':
        if ($this->_parseResult['import_type'] == 'verification') {

        }
        elseif ($this->_parseResult['import_type'] == 'transaction') {

        }
        break;
      case 'ACH Post':
        if ($this->_parseResult['import_type'] == 'verification') {

        }
        elseif ($this->_parseResult['import_type'] == 'transaction') {

        }
        break;
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Preview');
  }
}
