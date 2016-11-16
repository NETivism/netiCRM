<?php

/**
 * This class generates form components for CiviContribute
 */
class CRM_Admin_Form_Setting_TaxReceipt extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Receipt Tax included'));


    $fields = CRM_Core_BAO_CustomField::getFields('Contribution');
    $option = array(0 => ts('-- Select --'));
    foreach ($fields as $custom_id => $f) {
      $option[$custom_id] = $f['label'];
    }
    $this->addElement('select', 'taxReceiptType', ts('Tax receipt type'), $option);
    $this->addElement('select', 'taxReceiptDeviceType', ts('Field to save tax receipt device type'), $option);
    $this->addElement('select', 'taxReceiptDeviceNumber', ts('Field to save tax receipt device number'), $option);
    $this->addElement('select', 'taxReceiptDonate', ts('Field to save tax receipt donate org'), $option);
    $this->addElement('textarea', 'taxReceiptDonateSelect', ts('Give tax receipt to listed organization'));
    $this->addElement('select', 'taxReceiptSerial', ts('Field for tax receipt serial number'), $option);
    $this->addElement('select', 'taxReceiptNumber', ts('Field for invoice number that service return'), $option);

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    $check = TRUE;
    parent::buildQuickForm($check);
  }
}

