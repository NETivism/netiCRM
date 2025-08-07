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


class CRM_Report_Form_Walklist extends CRM_Report_Form {
  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_from;
  public $_aliases;
  public $_where;
  /**
   * @var mixed[]|string
   */
  public $_orderBy;
  public $_absoluteUrl;
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = ['Contact', 'Individual', 'Household', 'Organization']; function __construct() {
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['id' =>
          ['title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'display_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name'),
            'operator' => 'like',
          ],
        ],
        'grouping' => 'contact-fields',
        'order_bys' =>
        ['display_name' => ['title' => ts('Contact Name'),
            'required' => TRUE,
          ]],
      ],
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        [
          'street_number' => ['title' => ts('Street Number'),
            'type' => 1,
          ],
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          ['title' => ts('State/Province'),
            'default' => TRUE,
          ],
          'country_id' =>
          ['title' => ts('Country'),
          ],
        ],
        'filters' =>
        [
          'street_number' => ['title' => ts('Street Number'),
            'type' => 1,
            'name' => 'street_number',
          ],
          'street_address' => NULL,
          'city' => NULL,
        ],
        'grouping' => 'location-fields',
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['email' => ['default' => TRUE]],
        'grouping' => 'location-fields',
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        ['phone' => NULL],
        'grouping' => 'location-fields',
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
      foreach ($table['fields'] as $fieldName => $field) {
        if (CRM_Utils_Array::value('required', $field) ||
          CRM_Utils_Array::value($fieldName, $this->_params['fields'])
        ) {
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
          $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(",\n", $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
FROM       civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
";
    if ($this->_addressField) {
      $this->_from .= "LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    if ($this->_emailField) {
      $this->_from .= "LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }

    if ($this->_phoneField) {
      $this->_from .= "LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND {$this->_aliases['civicrm_phone']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function orderBy() {
    $this->_orderBy = "";
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('order_bys', $table)) {
        foreach ($table['order_bys'] as $fieldName => $field) {
          $this->_orderBy[] = $field['dbAlias'];
        }
      }
    }
    $this->_orderBy = "ORDER BY " . CRM_Utils_Array::implode(', ', $this->_orderBy) . " ";
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // handle state province
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
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

