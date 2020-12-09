<?php
class CRM_Mailing_Page_Unsubscribe extends CRM_Core_Page{
  function run() {
    $form = new CRM_Core_Controller_Simple('CRM_Mailing_Form_Unsubscribe', ts('Unsubscribe'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
    return parent::run();
  }
  function getTemplateFileName() {
    // trick to use form tpl instead
    $tpl = str_replace('_', DIRECTORY_SEPARATOR, str_replace('_Page_', '_Form_', CRM_Utils_System::getClassName($this))) . '.tpl';
    return $tpl;
  }
}