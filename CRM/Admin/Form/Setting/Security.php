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

    if (CRM_Core_Permission::check('administer neticrm')) {
      $this->assign('admin', TRUE);

      $this->addTextarea('trustedHostsPatterns', ts('Trusted Host Settings'), array(
        'placeholder' => ts('Example'). ":" . $_SERVER['HTTP_HOST']
      ));
    }
    else {
      $this->assign('admin', FALSE);
    }

    parent::buildQuickForm();
  }
}