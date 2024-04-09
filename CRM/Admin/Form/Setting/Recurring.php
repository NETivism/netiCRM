<?php

/**
 * This class generates form components for CiviContribute
 */
class CRM_Admin_Form_Setting_Recurring extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings') . ' - '. ts('Recurring Contribution'));

    $fields = CRM_Core_BAO_CustomField::getFields('Contribution');
    foreach ($fields as $custom_id => $f) {
      $option[$custom_id] = $f['label'];
    }
    $attrs = array('multiple' => 'multiple');
    $this->addElement('select', 'recurringSyncExclude', ts('Exclude to sync'), $option, $attrs);


    $option = array(
      'earliest' => ts('Earliest').' ('.ts('Default').')',
      'latest' => ts('Most Recent'),
    );
    $this->addElement('select', 'recurringCopySetting', ts('Copy Contribution'), $option);

    //add select option
    $options = array(
      '0' => ts("From source contribution."),
      '1' => ts("From contribution page setting."),
    );
    $this->addElement('select', 'copyContributionTypeSource', ts("Copied Contribution Type Source"), $options);

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    parent::buildQuickForm($check);
  }
}

