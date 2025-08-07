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


class CRM_Contact_Form_Search_Custom_ContribSYBNT extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_amounts;
  public $_dates;
  public $exclude_start_date;
  public $exclude_end_date;
  public $include_start_date;
  public $include_end_date;
  protected $_formValues;

  protected $_contribution_type_id;

  function __construct(&$formValues) {
    $this->_formValues = $formValues;

    $this->_contribution_type_id = CRM_Contribute_PseudoConstant::contributionType();

    $this->_columns = [
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'display_name',
      ts('Completed Donation') => 'completed_count',
      ts('Total Receive Amount') => 'receive_amount',
    ];

    $this->_amounts = [
      'include_min_amount' => ts('Min Amount'),
      'include_max_amount' => ts('Max Amount'),
    ];

    $this->_dates = [
      'include_start_date' => ts('Start Date'),
      'include_end_date' => ts('End Date'),
      'exclude_start_date' => ts('Exclusion Start Date'),
      'exclude_end_date' => ts('Exclusion End Date'),
    ];

    foreach ($this->_amounts as $name => $title) {
      $this->{$name} = CRM_Utils_Array::value($name, $this->_formValues);
    }

    foreach ($this->_dates as $name => $title) {
      if (CRM_Utils_Array::value($name, $this->_formValues)) {
        if (strstr($name, 'end_date')) {
          $this->{$name} = CRM_Utils_Date::processDate($this->_formValues[$name], '23:59:59');
        }
        else {
          $this->{$name} = CRM_Utils_Date::processDate($this->_formValues[$name]);
        }
      }
    }
  }

  function buildForm(&$form) {
    $form->addSelect('contribution_type_id', ts('Contribution Type'), $this->_contribution_type_id, ['multiple' => 'multiple']);

    foreach ($this->_amounts as $name => $title) {
      $form->add('text',
        $name,
        $title
      );
    }

    foreach ($this->_dates as $name => $title) {
      $form->addDate($name, $title, FALSE);
    }

  }

  function setDefaultValues($form) {
    $thisYear = date('Y');
    $lastYear = date('Y', strtotime('-1 year'));
    $defaults = [
      'include_start_date' => $lastYear.'-01-01',
      'include_end_date' => $lastYear.'-12-31',
      'exclude_start_date' => $thisYear.'-01-01',
      'exclude_end_date' => $thisYear.'-12-31',
      'include_min_amount' => 100,
      'include_max_amount' => 0,
    ];
    $form->set(CRM_Utils_Sort::SORT_ID, '4_d');
    return $defaults;
  }

  function qill() {
    $qill = [];
    if (!empty($this->_formValues['contribution_type_id'])) {
      foreach ($this->_formValues['contribution_type_id'] as $type_id) {
        $contribution_type[] = $this->_contribution_type_id[$type_id];
      }
      $qill[1]['contributionType'] = ts('Contribution Type').': '.CRM_Utils_Array::implode(', ', $contribution_type);
    }
    else {
      $qill[1]['contributionType'] = ts('Contribution Type').': '.ts('All');
    }
    if ($this->_formValues['include_start_date'] && $this->_formValues['include_end_date']) {
      $qill[1]['includeDate'] = ts('Have Donations').': '.$this->_formValues['include_start_date'] . ' ~ ' . $this->_formValues['include_end_date'];
    }

    if ($this->_formValues['include_min_amount'] || $this->_formValues['include_max_amount']) {
      $min = $this->_formValues['include_min_amount'] ? '$'.$this->_formValues['include_min_amount'] : '0';
      $max = $this->_formValues['include_max_amount'] ? '$'.$this->_formValues['include_max_amount'] : ts('no limit');
      $qill[1]['amountRange'] = ts('Have Donations').': '."$min ~ $max";
    }
    if ($this->_formValues['exclude_start_date'] && $this->_formValues['exclude_end_date']) {
      $qill[1]['excludeDate'] = ts('Without Donations').': '.$this->_formValues['exclude_start_date'] . ' ~ ' . $this->_formValues['exclude_end_date'];
    }
    return $qill;
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  function setTitle() {
    $title = ts('Last year but not this year donors');
    CRM_Utils_System::setTitle($title);
  }

  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE) {

    $where = $this->where();
    if (!empty($where)) {
      $where = " AND $where";
    }

    $having = $this->having();
    if ($having) {
      $having = " HAVING $having ";
    }

    $from = $this->from();

    $select = $this->select();
    $fields = "DISTINCT contact.id as id, contrib_1.contact_id as contact_id, contact.display_name as display_name, " . $select;

    $sql = "
SELECT     $fields
FROM       civicrm_contact AS contact
LEFT JOIN  civicrm_contribution contrib_1 ON contrib_1.contact_id = contact.id
           $from
WHERE      contrib_1.contact_id = contact.id
AND        contrib_1.is_test = 0 
           $where
GROUP BY   contact.id
           $having
";
    if ($onlyIDs) {
      $sql = "SELECT contact_a.contact_id FROM ($sql) contact_a WHERE (1) ";
    }

    $this->addSortOffset($sql, $offset, $rowcount, $sort);

    return $sql;
  }

  function select() {
    return "
sum(contrib_1.total_amount) AS receive_amount,
count(contrib_1.id) AS completed_count
";
  }

  function from() {
    $from = NULL;

    if ($this->exclude_start_date || $this->exclude_end_date) {
      $from .= " LEFT JOIN XG_CustomSearch_SYBNT xg ON xg.contact_id = contact.id ";
    }

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $clauses = [];
    $clauses[] = "contrib_1.is_test = 0";
    $clauses[] = "contrib_1.contribution_status_id = 1";

    if ($this->include_start_date) {
      $clauses[] = "contrib_1.receive_date >= {$this->include_start_date}";
    }

    if ($this->include_end_date) {
      $clauses[] = "contrib_1.receive_date <= {$this->include_end_date}";
    }

    $contribution_type_ids = $this->_formValues['contribution_type_id'];
    if (!empty($contribution_type_ids)) {
      $clauses[] = "contrib_1.contribution_type_id IN (".CRM_Utils_Array::implode(",", $contribution_type_ids).")";
    }

    if ($this->exclude_start_date || $this->exclude_end_date) {
      // first create temp table to store contact ids
      $sql = "DROP TEMPORARY TABLE IF EXISTS XG_CustomSearch_SYBNT";
      CRM_Core_DAO::executeQuery($sql);

      $sql = "CREATE TEMPORARY TABLE XG_CustomSearch_SYBNT ( contact_id int primary key, sum_total int) ENGINE=HEAP";
      CRM_Core_DAO::executeQuery($sql);

      $excludeClauses = [];
      $excludeClauses[] = "c.contribution_status_id = 1";
      if ($this->exclude_start_date) {
        $excludeClauses[] = "c.receive_date >= {$this->exclude_start_date}";
      }

      if ($this->exclude_end_date) {
        $excludeClauses[] = "c.receive_date <= {$this->exclude_end_date}";
      }

      if (!empty($contribution_type_ids)) {
        $excludeClauses[] = "c.contribution_type_id IN (".CRM_Utils_Array::implode(",", $contribution_type_ids).")";
      }

      $excludeClause = NULL;
      if ($excludeClauses) {
        $excludeClause = ' AND ' . CRM_Utils_Array::implode(' AND ', $excludeClauses);
      }

      if ($excludeClause || $havingClause) {
        // Run subquery
        $query = "
REPLACE   INTO XG_CustomSearch_SYBNT
SELECT   contact.id AS contact_id, SUM(c.total_amount) as sum_total
FROM     civicrm_contact contact LEFT JOIN civicrm_contribution c ON contact.id = c.contact_id AND c.is_test = 0 $excludeClause
GROUP BY contact.id
";

        $dao = CRM_Core_DAO::executeQuery($query);
      }

      // now ensure we this donors without donation will be filtered
      $clauses[] = " NULLIF(xg.sum_total, 0) IS NULL ";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function having($includeContactIDs = FALSE) {
    $clauses = [];
    $min = CRM_Utils_Array::value('include_min_amount', $this->_formValues);
    if ($min) {
      $clauses[] = "sum(contrib_1.total_amount) >= $min";
    }

    $max = CRM_Utils_Array::value('include_max_amount', $this->_formValues);
    if ($max) {
      $clauses[] = "sum(contrib_1.total_amount) <= $max";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function &columns() {
    return $this->_columns;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/ContribSYBNT.tpl';
  }

  function summary() {
    return NULL;
  }
}

