<?php

class CRM_Admin_Form_FromEmailAddress_Finish extends CRM_Admin_Form_FromEmailAddress {

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Done');
  }

  /**
   * Preprocess Form
   *
   * @return void
   */
  function preProcess() {
    $this->set('action', CRM_Core_Action::UPDATE);
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   */
  function setDefaultValues() {
    $defaults = [];
    $defaults['is_active'] = $this->_values['is_active'];
    $defaults['is_default'] = $this->_values['is_default'];
    return $defaults;
  }

  /**
   * Function to actually build the form
   */
  public function buildQuickForm() {
    $eleActive = $this->addCbx('is_active', ts('Enabled?'));
    $eleDefault = $this->addCbx('is_default', ts('Default Option?'));
    if ($this->_values['is_default']) {
      $eleActive->freeze();
      $eleDefault->freeze();
    }

    $this->addButtons([
        [
          'type' => 'back',
          'name' => ts('<< Previous'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'done',
          'name' => ts('Save and Done'),
        ],
      ]
    );
  }

  /**
   * Function to process the form
   */
  public function postProcess() {
    $this->_values['is_active'] = $this->exportValue('is_active');
    $this->_values['is_default'] = $this->exportValue('is_default');
    $this->_values['grouping'] = ts('Last validation') . ':' . date('Y-m-d H:i:s');
    $this->saveValues();
    if (!empty($this->_values['is_active'])) {
      CRM_Utils_Mail::validDKIMDomainList(TRUE, CRM_Utils_Mail::DKIM_EXTERNAL_VERIFIED_FILE);
    }
  }
}