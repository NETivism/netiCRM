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
class CRM_Contact_Form_Search_Custom_ActivitySearch implements CRM_Contact_Form_Search_Interface {

  protected $_formValues; function __construct(&$formValues) {
    $this->_formValues = $formValues;

    /**
     * Define the columns for search result rows
     */
    $this->_columns = array(
      ts('Name') => 'sort_name',
      ts('Status') => 'activity_status',
      ts('Activity Type') => 'activity_type',
      ts('Activity Subject') => 'activity_subject',
      ts('Scheduled By') => 'source_contact',
      ts('Scheduled Date') => 'activity_date',
      ts(' ') => 'activity_id',
      ts('  ') => 'activity_type_id',
      ts('   ') => 'case_id',
      ts('Location') => 'location',
      ts('Duration') => 'duration',
      ts('Details') => 'details',
      ts('Assignee') => 'assignee',
    );

    $this->_groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      'activity_status',
      'id',
      'name'
    );

    //Add custom fields to columns array for inclusion in export
    require_once 'CRM/Core/BAO/CustomGroup.php';
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Activity', $form, NULL,
      NULL, '', NULL
    );


    //use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $form);

    //cycle through custom fields and assign to _columns array
    foreach ($groupTree as $key) {
      foreach ($key['fields'] as $field) {
        $fieldlabel = $key['title'] . ": " . $field['label'];
        $this->_columns[$fieldlabel] = $field['column_name'];
      }
    }
    //end custom fields
  }

  function buildForm(&$form) {
    /**
     * Define the search form fields here
     */
    // Allow user to choose which type of contact to limit search on
    $form->add('select', 'contact_type', ts('Find...'), CRM_Core_SelectValues::contactType());

    // Text box for Activity Subject
    $form->add('text',
      'activity_subject',
      ts('Activity Subject')
    );

    // Select box for Activity Type
    $activityType = array('' => ' - select activity - ') + CRM_Core_PseudoConstant::activityType();

    $form->add('select', 'activity_type_id', ts('Activity Type'),
      $activityType,
      FALSE
    );

    // textbox for Activity Status
    $activityStatus = array('' => ' - select status - ') + CRM_Core_PseudoConstant::activityStatus();

    $form->add('select', 'activity_status_id', ts('Activity Status'),
      $activityStatus,
      FALSE
    );

    // Activity Date range
    $form->addDate('start_date', ts('Activity Date From'), FALSE, array('formatType' => 'custom'));
    $form->addDate('end_date', ts('...through'), FALSE, array('formatType' => 'custom'));


    // Contact Name field
    $form->add('text', 'sort_name', ts('Contact Name'));

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('contact_type', 'activity_subject', 'activity_type_id',
        'activity_status_id', 'start_date', 'end_date', 'sort_name',
      ));
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/ActivitySearch.tpl';
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $onlyIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      $select = 'contact_a.id as contact_id';
    }
    else {
      $select = '
                contact_a.id                as contact_id,
                contact_a.sort_name         as sort_name,
                contact_a.contact_type      as contact_type,
                activity.id                 as activity_id,
                activity.activity_type_id   as activity_type_id,
                contact_b.sort_name         as source_contact,
                ov1.label                   as activity_type,
                activity.subject            as activity_subject,
                activity.activity_date_time as activity_date,
                ov2.label                   as activity_status,
                cca.case_id                 as case_id,
                activity.location           as location,
                activity.duration           as duration,
                activity.details            as details,
                assignment.activity_id      as assignment_activity,
                contact_c.display_name      as assignee
                ';
    }

    $from = $this->from();

    $where = $this->where($includeContactIDs);

    if (!empty($where)) {
      $where = "WHERE $where";
    }

    // add custom group fields to SELECT and FROM clause
    require_once 'CRM/Core/BAO/CustomGroup.php';
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Activity', $form, NULL, NULL, '', NULL);

    foreach ($groupTree as $key) {
      if ($key['extends'] == 'Activity') {
        $select .= ", " . $key['table_name'] . ".*";
        $from .= " LEFT JOIN " . $key['table_name'] . " ON " . $key['table_name'] . ".entity_id = activity.id";
      }
    }
    // end custom groups add

    $sql = " SELECT $select FROM   $from $where ";

    //no need to add order when only contact Ids.
    if (!$onlyIDs) {
      // Define ORDER BY for query in $sort, with default value
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= ' ORDER BY ' . trim($sort->orderBy());
        }
      }
      else {
        $sql .= 'ORDER BY contact_a.sort_name, activity.activity_date_time DESC, activity.activity_type_id, activity.status_id, activity.subject';
      }
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  // Alters the date display in the Activity Date Column. We do this after we already have
  // the result so that sorting on the date column stays pertinent to the numeric date value
  function alterRow(&$row) {
    $row['activity_date'] = CRM_Utils_Date::customFormat($row['activity_date'], '%B %E%f, %Y %l:%M %P');
  }

  // Regular JOIN statements here to limit results to contacts who have activities.
  function from() {
    return "
        civicrm_contact contact_a
            JOIN civicrm_activity activity 
                 ON contact_a.id = activity.source_contact_id
            JOIN civicrm_option_value ov1 
                 ON activity.activity_type_id = ov1.value AND ov1.option_group_id = 2
            JOIN civicrm_option_value ov2 
                 ON activity.status_id = ov2.value AND ov2.option_group_id = {$this->_groupId}
            JOIN civicrm_contact contact_b 
                 ON activity.source_contact_id = contact_b.id
            LEFT JOIN civicrm_case_activity cca 
                 ON activity.id = cca.activity_id
            LEFT JOIN civicrm_activity_assignment assignment 
                 ON activity.id = assignment.activity_id
            LEFT JOIN civicrm_contact contact_c 
                 ON assignment.assignee_contact_id = contact_c.id ";
  }

  /*
     * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
     *
     */
  function where($includeContactIDs = FALSE) {
    $clauses = array();

    // add contact name search; search on primary name, source contact, assignee
    $contactname = $this->_formValues['sort_name'];
    if (!empty($contactname)) {
      $dao = new CRM_Core_DAO();
      $contactname = $dao->escape($contactname);
      $clauses[] = "(contact_a.sort_name LIKE '%{$contactname}%' OR 
                           contact_b.sort_name LIKE '%{$contactname}%' OR 
                           contact_c.display_name LIKE '%{$contactname}%')";
    }

    $subject = $this->_formValues['activity_subject'];

    if (!empty($this->_formValues['contact_type'])) {
      $clauses[] = "contact_a.contact_type LIKE '%{$this->_formValues['contact_type']}%'";
    }

    if (!empty($subject)) {
      $dao = new CRM_Core_DAO();
      $subject = $dao->escape($subject);
      $clauses[] = "activity.subject LIKE '%{$subject}%'";
    }

    if (!empty($this->_formValues['activity_status_id'])) {
      $clauses[] = "activity.status_id = {$this->_formValues['activity_status_id']}";
    }

    if (!empty($this->_formValues['activity_type_id'])) {
      $clauses[] = "activity.activity_type_id = {$this->_formValues['activity_type_id']}";
    }

    $startDate = $this->_formValues['start_date'];
    if (!empty($startDate)) {
      $startDate .= '00:00:00';
      $startDateFormatted = CRM_Utils_Date::processDate($startDate);
      if ($startDateFormatted) {
        $clauses[] = "activity.activity_date_time >= $startDateFormatted";
      }
    }

    $endDate = $this->_formValues['end_date'];
    if (!empty($endDate)) {
      $endDate .= '23:59:59';
      $endDateFormatted = CRM_Utils_Date::processDate($endDate);
      if ($endDateFormatted) {
        $clauses[] = "activity.activity_date_time <= $endDateFormatted";
      }
    }

    if ($includeContactIDs) {
      $contactIDs = array();
      foreach ($this->_formValues as $id => $value) {
        list($id, $additionalID) = CRM_Core_Form::cbExtract($id); 
        if ($value && !empty($id)) {
          $contactIDs[] = $id;
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

