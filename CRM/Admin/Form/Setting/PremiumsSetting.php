<?php

/**
 * This class generates form components for Premium Settings
 */
class CRM_Admin_Form_Setting_PremiumsSetting extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings') . ' - '. ts('Premium Settings'));

    // Pending inventory replenishment days
    $this->addNumber('premiumIRCreditCardDays', 
      ts('Credit Card Transaction Inventory Replenishment Days'), 
      ['min' => 1, 'max' => 3, 'size' => 2]);

    $this->addNumber('premiumIRNonCreditCardDays', 
      ts('Non-Credit Card Inventory Replenishment Days'), 
      ['min' => 1, 'max' => 7, 'size' => 2]);

    $this->addNumber('premiumIRConvenienceStoreDays', 
      ts('Convenience Store Barcode Inventory Replenishment Days'), 
      ['min' => 3, 'max' => 7, 'size' => 2]);

    // Status options for inventory replenishment check
    $statusOptions = [
      'Pending' => ts('Pending'),
      'Cancelled' => ts('Cancelled'), 
      'Failed' => ts('Failed'),
    ];
    $attrs = ['multiple' => 'multiple'];
    $this->addElement('select', 'premiumIRCheckStatuses', 
      ts('Check Status for Inventory Replenishment'), $statusOptions, $attrs);

    // Status change after inventory replenishment
    $statusChangeOptions = [
      'maintain' => ts('Maintain Unchanged'),
      'cancelled' => ts('Change to Cancelled'),
    ];
    $this->addElement('select', 'premiumIRStatusChange', 
      ts('Status Change After Inventory Replenishment'), $statusChangeOptions);

    // Manual cancellation inventory replenishment
    $manualCancelOptions = [
      '1' => ts('Yes'),
      '0' => ts('No'),
    ];
    $this->addElement('select', 'premiumIRManualCancel', 
      ts('Manual Cancellation Inventory Replenishment'), $manualCancelOptions);

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    parent::buildQuickForm();
  }

  /**
   * Set default values for the form
   *
   * @return array
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    
    // Set default values
    $defaults['premiumIRCreditCardDays'] = 1;
    $defaults['premiumIRNonCreditCardDays'] = 3;
    $defaults['premiumIRConvenienceStoreDays'] = 3;
    $defaults['premiumIRCheckStatuses'] = ['Pending'];
    $defaults['premiumIRStatusChange'] = 'maintain';
    $defaults['premiumIRManualCancel'] = '1';

    return $defaults;
  }

}