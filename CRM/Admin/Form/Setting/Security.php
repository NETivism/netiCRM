<?php

class CRM_Admin_Form_Setting_Security extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Website Security'));

    if (defined('ENABLE_DECRYPT_BLOCK')) {
      //add select option
      $label = ts("Export excel file encryption settings");
      $decryptExcelOptions = array(
        '0' => ts("No password set"),
        '1' => ts("Use the email of the exporting user as the password"),
        '2' => ts("Use a generic password")
      );
      $this->addRadio('decryptExcelOption', $label, $decryptExcelOptions, NULL, "<br>", FALSE);
      $this->addTextfield('decryptExcelPwd', ts("Generic Password"), NULL, FALSE);
    }

    parent::buildQuickForm();
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (empty($defaults['decryptExcelOption'])) {
      $defaults['decryptExcelOption'] = 0;
    }
    return $defaults;
  }
}