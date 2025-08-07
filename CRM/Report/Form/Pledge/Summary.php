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


class CRM_Report_Form_Pledge_Summary extends CRM_Report_Form {

  public $_columnHeaders;
  public $_addressField;
  public $_emailField;
  public $_from;
  public $_aliases;
  public $_where;
  public $_outputMode;
  public $_absoluteUrl;
  protected $_summary = NULL;
  protected $_totalPaid = FALSE;
  protected $_customGroupExtends = ['Pledge']; function __construct() {
    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name')],
          'id' =>
          ['no_display' => TRUE],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        ['street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          ['title' => ts('State/Province'),
          ],
          'country_id' =>
          ['title' => ts('Country'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['email' =>
          ['no_repeat' => TRUE],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_pledge' =>
      ['dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' =>
        ['id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
          'amount' =>
          ['title' => ts('Pledge Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'frequency_unit' =>
          ['title' => ts('Frequency Unit'),
          ],
          'installments' =>
          ['title' => ts('Installments'),
          ],
          'pledge_create_date' =>
          ['title' => ts('Pledge Made Date'),
          ],
          'start_date' =>
          ['title' => ts('Pledge Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' =>
          ['title' => ts('Pledge End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' =>
          ['title' => ts('Pledge Status'),
            'required' => TRUE,
          ],
          'total_paid' =>
          ['title' => ts('Total Amount Paid'),
          ],
        ],
        'filters' =>
        [
          'pledge_create_date' =>
          ['title' => 'Pledge Made Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'pledge_amount' =>
          ['title' => ts('Pledged Amount'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'sid' =>
          ['name' => 'status_id',
            'title' => ts('Pledge Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('contribution_status'),
          ],
        ],
      ],
      'civicrm_group' =>
      ['dao' => 'CRM_Contact_DAO_Group',
        'alias' => 'cgroup',
        'filters' =>
        ['gid' =>
          ['name' => 'group_id',
            'title' => ts(' Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ],
        ],
      ],
    ];

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            if (CRM_Utils_Array::value('total_paid', $this->_params['fields'])) {
              $this->_totalPaid = TRUE;
              unset($this->_params['fields']['total_paid']);
            }

            // to include optional columns address and email, only if checked
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    $this->_select = "SELECT DISTINCT " . CRM_Utils_Array::implode(', ', $select);
  }

  function from() {
    $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                      ON ({$this->_aliases['civicrm_contact']}.id = 
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

    // include address field if address column is to be included
    if ($this->_addressField) {
      $this->_from .= "
                 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                           ON ({$this->_aliases['civicrm_contact']}.id = 
                               {$this->_aliases['civicrm_address']}.contact_id) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "
                 LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                           ON ({$this->_aliases['civicrm_contact']}.id = 
                               {$this->_aliases['civicrm_email']}.contact_id) AND 
                               {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
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
                CRM_Utils_Array::value("{$fieldName}_value",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_min",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_max",
                  $this->_params
                )
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    if (empty($clauses)) {
      $this->_where = "WHERE ({$this->_aliases['civicrm_pledge']}.is_test=0 ) ";
    }
    else {
      $this->_where = "WHERE  ({$this->_aliases['civicrm_pledge']}.is_test=0 )  AND 
                                      " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->where();
    $this->limit();

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_limit}";

    $rows = $payment = [];
    $count = $due = $paid = 0;

    $dao = CRM_Core_DAO::executeQuery($sql);

    // Set pager for the Main Query only which displays basic information
    $this->setPager();
    $this->assign('columnHeaders', $this->_columnHeaders);

    while ($dao->fetch()) {
      $pledgeID = $dao->civicrm_pledge_id;
      foreach ($this->_columnHeaders as $columnHeadersKey => $columnHeadersValue) {
        $row = [];
        if (property_exists($dao, $columnHeadersKey)) {
          $display[$pledgeID][$columnHeadersKey] = $dao->$columnHeadersKey;
        }
      }
      $pledgeIDArray[] = $pledgeID;
    }

    // Pledge- Payment Detail Headers
    $tableHeader = ['scheduled_date' => ['type' => CRM_Utils_Type::T_DATE,
        'title' => 'Next Payment Due',
      ],
      'scheduled_amount' => ['type' => CRM_Utils_Type::T_MONEY,
        'title' => 'Next Payment Amount',
      ],
      'total_paid' => ['type' => CRM_Utils_Type::T_MONEY,
        'title' => 'Total Amount Paid',
      ],
      'balance_due' => ['type' => CRM_Utils_Type::T_MONEY,
        'title' => 'Balance Due',
      ],
      'status_id' => NULL,
    ];
    foreach ($tableHeader as $k => $val) {
      $this->_columnHeaders[$k] = $val;
    }

    if (!$this->_totalPaid) {
      unset($this->_columnHeaders['total_paid']);
    }

    // To Display Payment Details of pledged amount
    // for pledge payments In Progress
    if (!empty($display)) {
      $sqlPayment = "
                 SELECT min(payment.scheduled_date) as scheduled_date,
                        payment.pledge_id, 
                        payment.scheduled_amount, 
                        pledge.contact_id
              
                  FROM civicrm_pledge_payment payment 
                       LEFT JOIN civicrm_pledge pledge 
                                 ON pledge.id = payment.pledge_id
                     
                  WHERE payment.status_id = 2  

                  GROUP BY payment.pledge_id";

      $daoPayment = CRM_Core_DAO::executeQuery($sqlPayment);

      while ($daoPayment->fetch()) {
        foreach ($pledgeIDArray as $key => $val) {
          if ($val == $daoPayment->pledge_id) {

            $display[$daoPayment->pledge_id]['scheduled_date'] = $daoPayment->scheduled_date;

            $display[$daoPayment->pledge_id]['scheduled_amount'] = $daoPayment->scheduled_amount;
          }
        }
      }

      // Do calculations for Total amount paid AND
      // Balance Due, based on Pledge Status either
      // In Progress, Pending or Completed
      foreach ($display as $pledgeID => $data) {
        $count = $due = $paid = 0;

        // Get Sum of all the payments made
        $payDetailsSQL = "
                    SELECT SUM( payment.actual_amount ) as total_amount 
                       FROM civicrm_pledge_payment payment 
                       WHERE payment.pledge_id = {$pledgeID} AND
                             payment.status_id = 1";

        $totalPaidAmt = CRM_Core_DAO::singleValueQuery($payDetailsSQL);

        if (CRM_Utils_Array::value('civicrm_pledge_status_id', $data) == 5) {
          $due = $data['civicrm_pledge_amount'] - $totalPaidAmt;
          $paid = $totalPaidAmt;
          $count++;
        }
        elseif (CRM_Utils_Array::value('civicrm_pledge_status_id', $data) == 2) {
          $due = $data['civicrm_pledge_amount'];
          $paid = 0;
        }
        elseif (CRM_Utils_Array::value('civicrm_pledge_status_id', $data) == 1) {
          $due = 0;
          $paid = $paid + $data['civicrm_pledge_amount'];
        }

        $display[$pledgeID]['total_paid'] = $paid;
        $display[$pledgeID]['balance_due'] = $due;
      }
    }

    // Displaying entire data on the form
    if (!empty($display)) {
      foreach ($display as $key => $value) {
        $row = [];
        foreach ($this->_columnHeaders as $columnKey => $columnValue) {
          if (CRM_Utils_Array::arrayKeyExists($columnKey, $value)) {
            $row[$columnKey] = CRM_Utils_Array::value($columnKey, $value) ? $value[$columnKey] : '';
          }
        }
        $rows[] = $row;
      }
    }

    unset($this->_columnHeaders['status_id']);
    unset($this->_columnHeaders['civicrm_pledge_id']);
    unset($this->_columnHeaders['civicrm_pledge_contact_id']);

    $this->formatDisplay($rows, FALSE);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = [];
    $display_flag = $prev_cid = $cid = 0;

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (CRM_Utils_Array::arrayKeyExists('civicrm_pledge_contact_id', $row)) {
          if ($cid = $row['civicrm_pledge_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                if (in_array($colName, $this->_noRepeats)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_pledge_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_pledge_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      //handle status id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Core_OptionGroup::getLabel('contribution_status', $value);
        }
        $entryFound = TRUE;
      }

      // handle state province
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value, FALSE);
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

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}

