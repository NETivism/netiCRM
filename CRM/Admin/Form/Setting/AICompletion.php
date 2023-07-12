<?php

class CRM_Admin_Form_Setting_AICompletion extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {

    $this->addTextarea('aiOrganizationProfile', ts('Organization profile'), array(
      'placeholder' => ts('The Smiling Elderly Foundation is a non-profit organization in Taiwan, dedicated to improving the quality of life for the elderly and providing care and support. We organize welfare activities, provide elder care, medical assistance, and advocate for the rights of the elderly. Our goal is to make the elderly feel the love and respect of society.')
    ));

    parent::buildQuickForm();
  }
}
