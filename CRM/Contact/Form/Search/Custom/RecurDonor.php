<?php
class CRM_Contact_Form_Search_Custom_RecurDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  function __construct(&$formValues){
    parent::__construct($formValues);
  }

  function buildForm(&$form){
    $options = array(
      1 => ts('active donors (and no past recurring donation)'),
      2 => ts('inactive donors (past recurring donors)'),
      3 => ts('recurring donors (no matter active or inactive)'),
      4 => ts('not recurring donors (no matter with/without one-time donation)'),
    );
    $form->addRadio('search_type', '', $options);

    $form->add('text', 'amount_low', ts('Amount').' '.ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    $form->addSelect('contribution_page_id', ts('Contribution Page'), array('' => ts('-- select --')) + $pages);

    $types = CRM_Contribute_PseudoConstant::contributionType();
    $form->addSelect('contribution_type_id', ts('Contribution Type'), array('' => ts('-- select --')) + $types);

    // assgin elements for tpl
    $form->assign('elements', array('search_type', 'amount_low', 'contribution_page_id', 'contribution_type_id'));
  }

  function setDefaultValues() {
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    /*
    $where = $this->where();

    $sql = "SELECT DISTINCT cc.id as contact_id, cc.display_name, cc.legal_identifier FROM civicrm_contact AS cc WHERE $where";

    return $sql;
    */
  }

  function from() {
//    return NULL;
  }

  function where($includeContactIDs = FALSE) {
    /*
    $clauses = array();

    $legalid = CRM_Utils_Array::value('legal_identifier', $this->_formValues);
    if ($legalid) {
      $clauses[] = "cc.legal_identifier like '%{$legalid}%'";
    }

    $name = CRM_Utils_Array::value('display_name', $this->_formValues);
    if ($name) {
      $clauses[] = "cc.display_name like '%{$name}%'";
    }

    return !empty($clauses) ? implode(' AND ', $clauses) : '(1)';
    */
  }

  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/RecurDonor.tpl';
  }

  function summary() {
    return NULL;
  }
}