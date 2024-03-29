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

require_once 'CRM/Contact/Form/Search/Interface.php';
class CRM_Contact_Form_Search_Custom_EmployerListing implements CRM_Contact_Form_Search_Interface {

  protected $_formValues; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = array(ts('Contact Id') => 'contact_id',
      ts('Individual Name') => 'sort_name',
      ts('Individual State') => 'indState',
      ts('Employer') => 'employer',
      ts('Employer State') => 'empState',
    );
  }

  function buildForm(&$form) {
    /**
     * Define the search form fields here
     */
    $form->add('text',
      'sort_name',
      ts('Individual\'s Name (last, first)')
    );

    $stateProvince = array('' => ts('- any state/province -')) + CRM_Core_PseudoConstant::stateProvince();
    $form->addElement('select', 'state_province_id', ts('Individual\'s Home State'), $stateProvince);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('sort_name', 'state_province_id'));
  }

  /*
     * Set search form field defaults here.
     */
  function setDefaultValues() {
    // Setting default search state to California
    return array('state_province_id' => 1004,
    );
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
    $includeContactIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    $select = "
            DISTINCT cInd.id as contact_id,
            cInd.sort_name as sort_name,
            indSP.name as indState,
            cEmp.sort_name as employer,
            empSP.name as empState
            ";

    $from = $this->from();

    $where = $this->where($includeContactIDs);

    $having = $this->having();
    if ($having) {
      $having = " HAVING $having ";
    }

    // Define GROUP BY here if needed.
    $grouping = "";

    $sql = "
            SELECT $select
            FROM   $from
            WHERE  $where
            $grouping
            $having
            ";
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
      $sql .= "ORDER BY sort_name asc";
    }

    /* Uncomment the next 2 lines to see the exact query you're generating */

    // CRM_Core_Error::debug('sql',$sql);
    // exit();

    return $sql;
  }

  function from() {
    return "
            civicrm_relationship cR,
            civicrm_contact cInd
            LEFT JOIN civicrm_address indAddress ON ( indAddress.contact_id = cInd.id AND
            indAddress.is_primary       = 1 )
            LEFT JOIN civicrm_state_province indSP ON indSP.id = indAddress.state_province_id,           
            civicrm_contact cEmp
            LEFT JOIN civicrm_address empAddress ON ( empAddress.contact_id = cEmp.id AND
            empAddress.is_primary       = 1 )
            LEFT JOIN civicrm_state_province empSP ON empSP.id = empAddress.state_province_id
            ";
  }

  /*
     * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
     *
     */
  function where($includeContactIDs = FALSE) {
    $clauses = array();

    // These are required filters for our query.
    $clauses[] = "cInd.contact_type = 'Individual'";
    $clauses[] = "cR.relationship_type_id = 4";
    $clauses[] = "cR.contact_id_a = cInd.id";
    $clauses[] = "cR.contact_id_b = cEmp.id";
    $clauses[] = "cR.is_active = 1";

    // These are conditional filters based on user input
    $name = CRM_Utils_Array::value('sort_name',
      $this->_formValues
    );
    if ($name != NULL) {
      if (strpos($name, '%') === FALSE) {
        $name = "%{$name}%";
      }
      $clauses[] = "cInd.sort_name LIKE '$name'";
    }

    $state = CRM_Utils_Array::value('state_province_id',
      $this->_formValues
    );
    if ($state) {
      $clauses[] = "indSP.id = $state";
    }

    if ($includeContactIDs) {
      $contactIDs = array();
      foreach ($this->_formValues as $id => $value) {
        list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
        if ($value && !empty($contactID)) {
          $contactIDs[] = $contactID;
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
        $clauses[] = "contact.id IN ( $contactIDs )";
      }
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function having($includeContactIDs = FALSE) {
    $clauses = array();
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
    return $this->all($offset, $rowcount, $sort);
  }

  function &columns() {
    return $this->_columns;
  }

  function summary() {
    return NULL;
  }
}

