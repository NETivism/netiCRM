<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
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

class CRM_Contact_Form_Search_Custom_RecurSearch implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  
  function __construct(&$formValues){
    $this->_formValues = $formValues;
    $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->_config = CRM_Core_Config::singleton();

    /**
     * Define the columns for search result rows
     */
    $this->_columns = array(
      ts('ID') => 'id',
      ts('Amount') => 'amount',
      ts('Name') => 'sort_name',
      ts('Frequency Unit') => 'frequency_unit',
      ts('Installments') => 'installments',
      ts('Start Date') => 'start_date',
      ts('End Date') => 'end_date',
      ts('Cancel Date') => 'cancel_date',
      ts('Success Donation Count') => 'donation_count',
      ts('Total Donation Amount') => 'total_amount',
      ts('Status') => 'contribution_status_id',
    );
  }

  function buildForm(&$form){
    CRM_Core_OptionValue::getValues(array('name' => 'custom_search'), $custom_search);
    $csid = !empty($form->_formValues['customSearchID']) ? $form->_formValues['customSearchID'] : (!empty($_GET['csid']) ? $_GET['csid'] : NULL);
    if($csid){
      foreach ($custom_search as $c) {
        if ($c['value'] == $csid) {
          $this->setTitle($c['description']);
          break;
        }
      }
    }

    // Define the search form fields here
    $form->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'custom'));
    

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('start_date'));
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeRecurIds= FALSE, $onlyIDs = FALSE){
    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select = "r.id as id";
    }
    else {
      $select = "r.id as id,
contact.sort_name as sort_name,
r.contact_id as contact_id,
r.amount as amount,
r.frequency_unit as frequency_unit,
r.installments as installments,
r.start_date as start_date,
r.end_date as end_date,
r.cancel_date as cancel_date,
COUNT(c.id) as donation_count,
SUM(c.total_amount) as total_amount, 
r.contribution_status_id as contribution_status_id
";
    }
    $from = $this->from();

    $where = $this->where($includeRecurIds);

    $having = $this->having();
    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY r.id
$having
";
    // for only contact ids ignore order.
    if(!$onlyIDs){
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
        $sql .= " ORDER BY r.id ASC ";
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  function from() {
    return "civicrm_contribution_recur AS r 
    INNER JOIN civicrm_contribution AS c ON c.contribution_recur_id = r.id
    INNER JOIN civicrm_contact AS contact ON contact.id = r.contact_id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function where($includeRecurIds = FALSE){
    $clauses = array();

    $clauses[] = "r.contact_id = contact.id";
    $clauses[] = "r.is_test = 0";
    $clauses[] = "c.contribution_status_id = 1"; // only count success contributions

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = "r.start_date >= $startDate";
    }

    if ($includeRecurIds) {
      $recurIds = array();
      foreach ($this->_formValues as $id => $value) {
        if ($value && substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $recurIds[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($recurIds)) {
        $recurIds = implode(', ', $recurIds);
        $clauses[] = "r.id IN ($recurIds)";
      }
    }

    return implode(' AND ', $clauses);
  }

  function having($includeRecurIds = FALSE){
    $clauses = array();
    if(count($clauses)){
      return implode(' AND ', $clauses);
    }
    return '';
  }

  /**
   * Functions below generally don't need to be modified
   */
  function count(){
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function &columns(){
    return $this->_columns;
  }

  function setTitle($title){
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary(){
    $select = "
r.id,
COUNT(DISTINCT(r.id)) as recurring_count,
COUNT(c.id) as donation_count,
SUM(c.total_amount) as total_amount 
    ";
    $from = $this->from();
    $where = $this->where($includeRecurIds);
    $having = $this->having();

    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "SELECT $select FROM $from WHERE $where GROUP BY r.id WITH ROLLUP $having";
    $sql = "SELECT * FROM 
      ($sql) summary
    WHERE id IS NULL";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $summary = array(
        $dao->recurring_count . ' '.ts('Recurring Count'),
        $dao->donation_count . ' '. ts('Contribution Count'),
        round($dao->total_amount) .' '. ts('Total Amount'),
      );
      if(!empty($this->_formValues['start_date'])){
        $start_date = CRM_Utils_Date::customFormat($this->_formValues['start_date'], $this->_config->dateformatFull);
        $title = ts('Start Date') .' '. ts('>') .' '. $start_date;
      }
      else{
        $title = ts('Search Results');
      }
      return array(
        'summary' => $title,
        'total' => '<ul><li>'.implode('</li><li>', $summary).'</li></ul>',
      );
    }
    else{
      return NULL;
    }
  }

  function alterRow(&$row) {
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    $date = array('start_date', 'end_date', 'cancel_date');
    foreach($date as $d){
      if(!empty($row[$d])){
        $row[$d] = CRM_Utils_Date::customFormat($row[$d], $this->_config->dateformatFull);
      }
    }
    if(!empty($row['frequency_unit'])){
      $row['frequency_unit'] = ts($row['frequency_unit']);
    }
    $row['action'] = '<a href="'.CRM_Utils_System::url('civicrm/contact/view/contributionrecur', "reset=1&id={$row['id']}&cid={$row['contact_id']}").'" target="_blank">'.ts('View').'</a>';
  }
}

