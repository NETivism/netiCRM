<?php

class CRM_Contact_Form_Search_Custom_LegalId implements CRM_Contact_Form_Search_Interface {
  public $_columns;
  protected $_formValues; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    $this->_columns = [ts('Contact Id') => 'contact_id',
      ts('Name') => 'display_name',
      ts('Legal Identifier') => 'legal_identifier',
    ];
  }

  function buildForm(&$form) {
    $form->add('text', 'legal_identifier', ts('Legal Identifier'));
    $form->add('text', 'display_name', ts('Display Name'));
  }

  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE);
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    $where = $this->where();

    $sql = "SELECT DISTINCT cc.id as contact_id, cc.display_name, cc.legal_identifier FROM civicrm_contact AS cc WHERE $where";

    return $sql;
  }

  function from() {
    return NULL;
  }

  function where($includeContactIDs = FALSE) {
    $clauses = [];

    $legalid = CRM_Utils_Array::value('legal_identifier', $this->_formValues);
    if ($legalid) {
      $clauses[] = "cc.legal_identifier like '%{$legalid}%'";
    }

    $name = CRM_Utils_Array::value('display_name', $this->_formValues);
    if ($name) {
      $clauses[] = "cc.display_name like '%{$name}%'";
    }

    return !empty($clauses) ? CRM_Utils_Array::implode(' AND ', $clauses) : '(1)';
  }

  function &columns() {
    return $this->_columns;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/LegalId.tpl';
  }

  function summary() {
    return NULL;
  }
}

