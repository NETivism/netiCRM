<?php

class CRM_Admin_Form_Setting_AICompletion extends CRM_Admin_Form_Setting {
  public function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts('AI Copywriter').' - '.ts('Settings'));
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {

    $this->addTextarea('aiOrganizationIntro', ts('Organization intro'), array(
      'placeholder' => ts('Example').': '.ts('The Smiling Elderly Foundation, a non-profit organization based in Taiwan, is committed to enhancing the quality of life for seniors. We offer thoughtful care, support, and organize welfare activities, in addition to providing medical assistance. Additionally, we are staunch advocates for the rights of the elderly. Our objective is to ensure that seniors feel the warmth, respect, and appreciation of society.')
    ));

    parent::buildQuickForm();
  }
}
