<?php
/**
 * Custom search form for finding contacts by legal identifier
 *
 */

class CRM_Contact_Form_Search_Custom_LegalId implements CRM_Contact_Form_Search_Interface {
  public $_columns;
  protected $_formValues;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = $formValues;

    $this->_columns = [ts('Contact Id') => 'contact_id',
      ts('Name') => 'display_name',
      ts('Legal Identifier') => 'legal_identifier',
    ];
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $form->add('text', 'legal_identifier', ts('Legal Identifier'));
    $form->add('text', 'display_name', ts('Display Name'));
  }

  /**
   * Get the count of contacts found.
   *
   * @return int
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * Get the SQL for retrieving contact IDs.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE);
  }

  /**
   * Build the all query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    $where = $this->where();

    $sql = "SELECT DISTINCT cc.id as contact_id, cc.display_name, cc.legal_identifier FROM civicrm_contact AS cc WHERE $where";

    return $sql;
  }

  /**
   * Get the FROM clause.
   *
   * @return null
   */
  public function from() {
    return NULL;
  }

  /**
   * Build the WHERE clause.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
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

  /**
   * Getter for columns.
   *
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * Get the path to the template file.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/LegalId.tpl';
  }

  /**
   * Get summary data.
   *
   * @return null
   */
  public function summary() {
    return NULL;
  }
}
