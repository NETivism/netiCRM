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

require_once 'CRM/Report/Form.php';
require_once 'CRM/Member/PseudoConstant.php';
class CRM_Report_Form_Member_Lapse extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_addressField = FALSE;
  protected $_emailField = FALSE;
  protected $_phoneField = FALSE;
  protected $_charts = array('' => 'Tabular');
  protected $_customGroupExtends = array('Membership'); function __construct() {
    // UI for selecting columns to appear in the report list
    // array conatining the columns, group_bys and filters build and provided to Form
    $this->_columns = array('civicrm_contact' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array('display_name' =>
          array('title' => ts('Member Name'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ),
          'id' =>
          array('no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership_type' =>
      array('dao' => 'CRM_Member_DAO_MembershipType',
        'grouping' => 'member-fields',
        'filters' =>
        array('tid' =>
          array('name' => 'id',
            'title' => ts('Membership Types'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
      ),
      'civicrm_membership' =>
      array('dao' => 'CRM_Member_DAO_Membership',
        'grouping' => 'member-fields',
        'fields' =>
        array('membership_type_id' =>
          array('title' => 'Membership Type',
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'membership_start_date' => array('title' => ts('Current Cycle Start Date'),
          ),
          'membership_end_date' => array('title' => ts('Membership Lapse Date'),
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array('membership_end_date' =>
          array('title' => 'Lapsed Memberships',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
      'civicrm_membership_status' =>
      array('dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' =>
        array(
          'name' => array('title' => ts('Current Status'),
            'required' => TRUE,
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_address' =>
      array('dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array('street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array('dao' => 'CRM_Core_DAO_Phone',
        'alias' => 'phone',
        'fields' =>
        array('phone' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array('dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_group' =>
      array('dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'filters' =>
        array('gid' =>
          array('name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ),
        ),
      ),
    );

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // to include optional columns address ,email and phone only if checked
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    //check for searching combination of dispaly columns and
    //grouping criteria

    return $errors;
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
        FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']} 
                         ON {$this->_aliases['civicrm_contact']}.id = 
                            {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
              LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                         ON {$this->_aliases['civicrm_membership_status']}.id = 
                            {$this->_aliases['civicrm_membership']}.status_id
              LEFT  JOIN civicrm_membership_type {$this->_aliases['civicrm_membership_type']} 
                         ON {$this->_aliases['civicrm_membership']}.membership_type_id =
                            {$this->_aliases['civicrm_membership_type']}.id";

    //  include address field if address column is to be included
    if ($this->_addressField) {
      $this->_from .= "
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }

    // include phone field if phone column is to be included
    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id 
                     AND {$this->_aliases['civicrm_phone']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if ($field['operatorType'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }
          if (!empty($clause)) {
            $clauses[$fieldName] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE end_date < '" . date('Y-m-d') . "' AND {$this->_aliases['civicrm_membership_status']}.name = 'Expired'";
    }
    else {
      if (!CRM_Utils_Array::arrayKeyExists('end_date', $clauses)) {
        $this->_where = "WHERE end_date < '" . date('Y-m-d') . "' AND " . CRM_Utils_Array::implode(' AND ', $clauses);
      }
      else {
        $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
      }
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function postProcess() {
    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = $graphRows = array();
    $count = 0;
    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }

      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row

        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      //handle the Membership Type Ids
      if (CRM_Utils_Array::arrayKeyExists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // handle state province
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('member/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Membership Detail for this Contact.");
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}

