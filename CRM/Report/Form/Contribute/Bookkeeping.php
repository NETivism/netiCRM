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



class CRM_Report_Form_Contribute_Bookkeeping extends CRM_Report_Form {
  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_from;
  /**
   * @var string
   */
  public $_groupBy;
  /**
   * @var string
   */
  public $_orderBy;
  public $_aliases;
  public $_where;
  public $_absoluteUrl;
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = ['Membership']; function __construct() {
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name'),
            'operator' => 'like',
          ],
          'id' =>
          ['title' => ts('Contact ID'),
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_membership' =>
      ['dao' => 'CRM_Member_DAO_Membership',
        'fields' =>
        ['id' =>
          ['title' => ts('Membership #'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_contribution' =>
      ['dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        [
          'receive_date' => ['default' => TRUE],
          'total_amount' => ['title' => ts('Amount'),
            'required' => TRUE,
            'statistics' =>
            ['sum' => ts('Amount')],
          ],
          'contribution_type_id' => ['title' => ts('Contribution Type'),
            'default' => TRUE,
          ],
          'trxn_id' => ['title' => ts('Trans #'),
            'default' => TRUE,
          ],
          'invoice_id' => ['title' => ts('Invoice ID'),
            'default' => TRUE,
          ],
          'check_number' => ['title' => ts('Cheque #'),
            'default' => TRUE,
          ],
          'payment_instrument_id' => ['title' => ts('Payment Instrument'),
            'default' => TRUE,
          ],
          'contribution_status_id' => ['title' => ts('Status'),
            'default' => TRUE,
          ],
          'id' => ['title' => ts('Contribution #'),
            'default' => TRUE,
          ],
        ],
        'filters' =>
        ['receive_date' =>
          ['operatorType' => CRM_Report_Form::OP_DATE],
          'contribution_type_id' =>
          ['title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ],
          'contribution_status_id' =>
          ['title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
          'total_amount' =>
          ['title' => ts('Contribution Amount')],
        ],
        'grouping' => 'contri-fields',
      ],
    ];

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

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
        FROM  civicrm_contact      {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
              LEFT JOIN civicrm_membership_payment payment
                        ON ( {$this->_aliases['civicrm_contribution']}.id = payment.contribution_id )
              LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
              		  ON payment.membership_id = {$this->_aliases['civicrm_membership']}.id ";
  }

  function groupBy() {
    $this->_groupBy = "";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contribution']}.id ";
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount,
               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg
        ";

    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      $statistics['counts']['amount'] = ['value' => $dao->amount,
        'title' => 'Total Amount',
        'type' => CRM_Utils_Type::T_MONEY,
      ];
      $statistics['counts']['avg'] = ['value' => $dao->avg,
        'title' => 'Average',
        'type' => CRM_Utils_Type::T_MONEY,
      ];
    }

    return $statistics;
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $checkList = [];
    $entryFound = FALSE;
    $display_flag = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();


    foreach ($rows as $rowNum => $row) {

      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_display_name', $rows[$rowNum]) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      // handle contribution status id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contribution_contribution_status_id', $row)) {
        if ($value = $row['civicrm_contribution_contribution_status_id']) {
          $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
        }
        $entryFound = TRUE;
      }

      // handle payment instrument id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contribution_payment_instrument_id', $row)) {
        if ($value = $row['civicrm_contribution_payment_instrument_id']) {
          $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        }
        $entryFound = TRUE;
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }
}

