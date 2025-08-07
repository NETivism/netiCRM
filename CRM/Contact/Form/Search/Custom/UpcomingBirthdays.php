<?php
/*
 +--------------------------------------------------------------------+
 | |                                                    |
 +--------------------------------------------------------------------+
 | Copyright Sarah Gladstone (c) 2004-2010                             |
 +--------------------------------------------------------------------+
 | This is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 .   |
 |                                                                    |
 | This is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.                         |
 +--------------------------------------------------------------------+                |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * $Id$
 *
 */


class CRM_Contact_Form_Search_Custom_UpcomingBirthdays implements CRM_Contact_Form_Search_Interface {

  public $_columns;
  protected $_formValues;
  protected $_tableName = NULL; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = [
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'name',
      ts('Year') => 'year',
      ts('Date') => 'birth',
    ];
  }

  function buildForm(&$form) {

    /**
     * You can define a custom title for the search form
     */
    $form->setTitle(ts('Birth Date Search'));

    /**
     * Define the search form fields here
     */
    $groups = CRM_Core_PseudoConstant::staticGroup();
    $form->addSelect('limit_groups', ts('Groups'), ['' => ts('- select -')] + $groups, ['multiple' => 'multiple']);

    $month = ['' => ts('- select -'), '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12'];
    $form->addSelect('oc_month_start', ts('month')." (".ts("Start").")", $month, NULL, TRUE);
    $form->addSelect('oc_month_end', ts('month')." (".ts("End").")", $month, NULL, TRUE);
    $form->addNumber('oc_day_start', ts('day')." (".ts('Start').")", NULL, TRUE);
    $form->addNumber('oc_day_end', ts('day')." (".ts('End').")", NULL, TRUE);
    $form->setDefaults([
      'oc_month_start' => date('n', $time),
      'oc_month_end' => date('n', $time),
      'oc_day_start' => 1,
      'oc_day_end' => date('j', strtotime('first day of next month') - 86400),
    ]);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', ['limit_groups', 'oc_month_start', 'oc_month_end', 'oc_day_start', 'oc_day_end']);
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/UpcomingBirthday.tpl';
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE) {
    // SELECT clause must include contact_id as an alias for civicrm_contact.id

    /******************************************************************************/
    // Get data for contacts

    if ($onlyIDs) {
      $select = "DISTINCT contact_a.id as contact_id";
    }
    else {
      $select = "DISTINCT contact_a.id as contact_id, DATE_FORMAT(contact_a.birth_date,'%Y') as year, CAST(DATE_FORMAT(contact_a.birth_date,'%m.%d') as DECIMAL(5,2)) as birth, contact_a.display_name as name";
    }

    $from = $this->from();
    $where = $this->where($includeContactIDs);
    $sql = "SELECT $select FROM $from WHERE $where GROUP BY contact_a.id";

    //order by month(birth_date), oc_day";
    //for only contact ids ignore order.
    if (!$onlyIDs) {
      // Define ORDER BY for query in $sort, with default value
      if (empty($_GET['crmSID'])) {
        // default sort
        $sql .= " ORDER BY birth ASC";
      }
      elseif (!empty($sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= " ORDER BY " . trim($sort->orderBy());
        }
      }
      else {
        $sql .= " ORDER BY birth ASC";
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    return $sql;
  }

  function from() {
    $limit_group = $this->_formValues['limit_groups'];
    if (!empty($limit_group) && is_array($limit_group)) {
      return " civicrm_contact contact_a INNER JOIN civicrm_group_contact g ON g.contact_id = contact_a.id AND g.status = 'Added' ";
    }
    else {
      return " civicrm_contact contact_a ";
    }
  }

  function where($includeContactIDs = FALSE) {
    $clauses = [];

    $oc_month_start = (int)$this->_formValues['oc_month_start'];
    $oc_month_end = (int)$this->_formValues['oc_month_end'];

    $oc_day_start = (int)$this->_formValues['oc_day_start'];
    $oc_day_end = (int)$this->_formValues['oc_day_end'];

    if (($oc_month_start <> '') && is_numeric($oc_month_start)) {
      $clauses[] = "month(contact_a.birth_date) >= " . $oc_month_start;
    }
    if (($oc_month_end <> '') && is_numeric($oc_month_end)) {
      $clauses[] = "month(contact_a.birth_date) <= " . $oc_month_end;
    }
    if (($oc_day_start <> '') && is_numeric($oc_day_start)) {
      $clauses[] = "day(contact_a.birth_date) >= " . $oc_day_start;
    }
    if (($oc_day_end <> '') && is_numeric($oc_day_end)) {
      $clauses[] = "day(contact_a.birth_date) <= " . $oc_day_end;
    }
    $clauses[] = "contact_a.birth_date IS NOT NULL";

    $limit_group = $this->_formValues['limit_groups'];
    if (!empty($limit_group) && is_array($limit_group)) {
      foreach($limit_group as $idx => $g) {
        if (!is_numeric($g)){
          unset($limit_group[$idx]);
        }
      }
      if (!empty($limit_group)) {
        $clauses[] = "g.group_id IN(".CRM_Utils_Array::implode(',', $limit_group).")";
      }
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

    $clauses[] = "contact_a.is_deleted = 0";

    $partial_where_clause = CRM_Utils_Array::implode(' AND ', $clauses);
    return $partial_where_clause;
  }

  /* 
   * Functions below generally don't need to be modified
   */
  function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    return $dao->N;
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  function &columns() {
    return $this->_columns;
  }

  function summary(){
    $summary = [];
    return $summary;
  }

  function alterRow(&$row) {
    $row['birth'] = str_replace('.', '/', $row['birth']);
  }
}

