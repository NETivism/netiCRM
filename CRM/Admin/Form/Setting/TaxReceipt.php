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
    $this->addElement('select', 'taxReceiptDeviceType', ts('Tax receipt device type'), $option);
    $this->addElement('select', 'taxReceiptDeviceNumber', ts('Tax receipt device number'), $option);
    $this->addElement('select', 'taxReceiptSerial', ts('Tax receipt serial number field'), $option);
    $this->addElement('select', 'taxReceiptItem', ts('Invoice item name field'), $option);
    $this->addElement('select', 'taxReceiptNumber', ts('Invoice number field'), $option);
    $this->addElement('select', 'taxReceiptPaper', ts('Printing paper invoice field'), $option);

    $this->addElement('select', 'taxReceiptDonate', ts('Tax receipt give org field'), $option);
    $this->addElement('textarea', 'taxReceiptDonateSelect', ts('Give tax receipt to listed organization'));

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    $check = TRUE;
    parent::buildQuickForm($check);
  }
}

