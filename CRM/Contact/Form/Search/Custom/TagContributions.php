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


class CRM_Contact_Form_Search_Custom_TagContributions implements CRM_Contact_Form_Search_Interface {

  public $_columns;
  protected $_formValues; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = [ts('Contact Id') => 'contact_id',
      ts('Full Name') => 'sort_name',
      ts('First Name') => 'first_name',
      ts('Last Name') => 'last_name',
      ts('Tag') => 'tag_name',
      ts('Totals') => 'amount',
    ];
  }

  function buildForm(&$form) {
    /**
     * Define the search form fields here
     */


    $form->addDate('start_date', ts('Contribution Date From'), FALSE, ['formatType' => 'custom']);
    $form->addDate('end_date', ts('...through'), FALSE, ['formatType' => 'custom']);
    $tag = ['' => ts('- any tag -')] + CRM_Core_PseudoConstant::tag();
    $form->addElement('select', 'tag', ts('Tagged'), $tag);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', ['start_date', 'end_date', 'tag']);
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
      $select = "DISTINCT civicrm_contact.id as contact_id";
    }
    else {
      $select = "
DISTINCT
civicrm_contact.id as contact_id,
civicrm_contact.sort_name as sort_name,
civicrm_contact.first_name as first_name,
civicrm_contact.last_name as last_name,
GROUP_CONCAT(DISTINCT civicrm_tag.name ORDER BY  civicrm_tag.name ASC ) as tag_name,
sum(civicrm_contribution.total_amount) as amount
";
    }
    $from = $this->from();

    $where = $this->where($includeContactIDs);

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY civicrm_contact.id
";
    //for only contact ids ignore order.
    if (!$onlyIDs) {
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
        $sql .= "";
      }
    }
    return $sql;
  }

  function from() {
    return "
      civicrm_contribution,
      civicrm_contact
      LEFT JOIN civicrm_entity_tag ON ( civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                                        civicrm_entity_tag.entity_id = civicrm_contact.id )
      LEFT JOIN civicrm_tag ON civicrm_tag.id = civicrm_entity_tag.tag_id
";
  }

  /*
  * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
  *
  */
  function where($includeContactIDs = FALSE) {
    $clauses = [];

    $clauses[] = "civicrm_contact.contact_type = 'Individual'";
    $clauses[] = "civicrm_contribution.contact_id = civicrm_contact.id";

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = "civicrm_contribution.receive_date >= $startDate";
    }

    $endDate = CRM_Utils_Date::processDate($this->_formValues['end_date']);
    if ($endDate) {
      $clauses[] = "civicrm_contribution.receive_date <= $endDate";
    }

    $tag = CRM_Utils_Array::value('tag', $this->_formValues);
    if ($tag) {
      $clauses[] = "civicrm_entity_tag.tag_id = $tag";
      $clauses[] = "civicrm_tag.id = civicrm_entity_tag.tag_id";
    }
    else {
      $clauses[] = "civicrm_entity_tag.tag_id IS NOT NULL";
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

