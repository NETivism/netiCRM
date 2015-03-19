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

class CRM_Contact_Form_Search_Custom_AnnualReceipt extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);

    if (!isset($formValues['year'])) {
      $this->_year = CRM_Utils_Request::retrieve('year', 'Integer', CRM_Core_DAO::$_nullObject);
      if ($this->_year ) {
        $formValues['year'] = $this->_year;
      }
    }

    $this->_columns = array(ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Count') => 'count',
      ts('Total Amount') => 'total_amount',
    );
  }

  function buildForm(&$form) {
    $years = array();
    for($year = date('Y'); $year < date('Y') + 4; $year++) {
      $years[$year - 3] = $year - 3;
    }
    $form->addElement('select', 'year', ts('Receipt Date'), $years);
    $this->setTitle(ts('Print Annual Receipt'));
    $form->assign('elements', array('year'));

    // reset session when visit first selection
    $session = CRM_Core_Session::singleton();
    if(!empty($_GET['csid'])){
      $session->resetScope('AnnualReceipt');
    }
  }

  function summary() {
    $year = CRM_Utils_Array::value('year', $this->_formValues);
    $summary = array(
      'summary' => ts('Date'),
      'total' => $year.'-01-01 ~ '.$year.'-12-31',
    );
    return $summary;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE) {
    $select = "
contact_a.id           as contact_id  ,
contact_a.sort_name    as sort_name,
COUNT(contribution.id) as count,
SUM(contribution.total_amount) as total_amount
";
    $groupby = "GROUP BY contribution.contact_id";
    $sql = $this->sql($select, $offset, $rowcount, $sort, $includeContactIDs, $groupby);
    $year = CRM_Utils_Array::value('year', $this->_formValues);
    if(!empty($year)){
      $session = CRM_Core_Session::singleton();
      $session->set('year', $year, 'AnnualReceipt');
    }
    else{
      $session->set('year', $year, 'AnnualReceipt');
    }
    return $sql;
  }

  function from() {
    return "
FROM      civicrm_contact contact_a
INNER JOIN civicrm_contribution contribution ON contact_a.id = contribution.contact_id";
  }

  function where($includeContactIDs = FALSE) {
    $params = array();
    $where = array(
      'contribution.is_test = 0',
      'contribution.contribution_status_id = 1',
      'contact_a.is_deleted = 0',
    );
    $year = CRM_Utils_Array::value('year', $this->_formValues);
    if(!empty($year)){
      $start = $year.'-01-01 00:00:00';
      $end = $year.'-12-31 23:59:59';
      $where[] = "contribution.receipt_date >= '$start' AND contribution.receipt_date <= '$end'";
    }

    $where = implode(' AND ', $where);
    $where = $this->whereClause($where, $params);
    return $where;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function setDefaultValues() {
  }

  function alterRow(&$row) {
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }
}

