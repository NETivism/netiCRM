<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */


class CRM_Contact_Form_Search_Custom_ContributionAggregate implements CRM_Contact_Form_Search_Interface {

  public $_columns;
  protected $_formValues; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = [ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Donation Count') => 'donation_count',
      ts('Donation Amount') => 'donation_amount',
    ];
  }

  function buildForm(&$form) {
    /**
     * Define the search form fields here
     */
    $form->add('text',
      'min_amount',
      ts('Aggregate Total Between $')
    );
    $form->addRule('min_amount', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');

    $form->add('text',
      'max_amount',
      ts('...and $')
    );
    $form->addRule('max_amount', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');

    $form->addDate('start_date', ts('Contribution Date From'), FALSE, ['formatType' => 'custom']);
    $form->addDate('end_date', ts('...through'), FALSE, ['formatType' => 'custom']);

    $form->add('text', 'top_contributors', ts('Amount of top contributors'));

    $form->add('select', 'contribution_recurring', ts('Find Recurring Contributions?'), [
      'all' => ts('All'),
      'recur' => ts('Recurring Contribution'),
      'not_recur' => ts('Non-Recurring Contribution'),
    ]);

    $form->setDefaults(['top_contributors' => 100]);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign( 'elements', [ 'min_amount', 'max_amount', 'start_date', 'end_date','top_contributors','contribution_recurring'] );
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $onlyIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select = "DISTINCT contact_a.id as contact_id";
    }
    else {
      $select = "
DISTINCT contact_a.id as contact_id,
contact_a.sort_name as sort_name,
sum(contrib.total_amount) AS donation_amount,
count(contrib.id) AS donation_count
";
    }
    $from = $this->from();

    $where = $this->where($includeContactIDs);

    if($this->_formValues['contribution_recurring'] == 'recur'){
      $where .= ' AND contrib.contribution_recur_id IS NOT NULL';
    }else if($this->_formValues['contribution_recurring'] == 'not_recur'){
      $where .= ' AND contrib.contribution_recur_id IS NULL';
    }

    $having = $this->having();
    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact_a.id
$having
";
    //for only contact ids ignore order.
    if (!$onlyIDs) {
      if(!empty($this->_formValues['top_contributors'])){
        $top_amount = $this->_formValues['top_contributors'];
        $sql .= "ORDER BY donation_amount DESC LIMIT $top_amount ";
        $sql = "SELECT * FROM ($sql) orig ";
      }
      // Define ORDER BY for query in $sort, with default value
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= " ORDER BY " . trim($sort->orderBy());
        }
      }
      else {
        $sql .= "ORDER BY donation_amount desc";
      }
    }
    else {
      if(!empty($this->_formValues['top_contributors'])){
        $top_amount = $this->_formValues['top_contributors'];
        $sql .= "ORDER BY sum(contrib.total_amount) DESC LIMIT $top_amount ";
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  function from() {
    return "
civicrm_contribution AS contrib,
civicrm_contact AS contact_a
";
  }

  /*
      * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
      *
      */
  function where($includeContactIDs = FALSE) {
    $clauses = [];

    $clauses[] = "contrib.contact_id = contact_a.id";
    $clauses[] = "contrib.contribution_status_id = 1";
    $clauses[] = "contrib.is_test = 0";
    $clauses[] = "contact_a.is_deleted = 0";

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = "contrib.receive_date >= $startDate";
    }

    $endDate = CRM_Utils_Date::processDate($this->_formValues['end_date'], '23:59:59');
    if ($endDate) {
      $clauses[] = "contrib.receive_date <= $endDate";
    }

    if ($includeContactIDs) {
      $contactIDs = [];
      foreach ($this->_formValues as $id => $value) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
        if ($value && !empty($contactID)) {
          $contactIDs[] = $contactID;
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ( $contactIDs )";
      }
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function having($includeContactIDs = FALSE) {
    $clauses = [];
    $min = CRM_Utils_Array::value('min_amount', $this->_formValues);
    if ($min) {
      $min = CRM_Utils_Rule::cleanMoney($min);
      $clauses[] = "sum(contrib.total_amount) >= $min";
    }

    $max = CRM_Utils_Array::value('max_amount', $this->_formValues);
    if ($max) {
      $max = CRM_Utils_Rule::cleanMoney($max);
      $clauses[] = "sum(contrib.total_amount) <= $max";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  /* 
     * Functions below generally don't need to be modified
     */
  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function &columns() {
    return $this->_columns;
  }

  function summary() {
    return NULL;
  }
}
