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

class CRM_Report_Form_Contribute_TaiwanTax extends CRM_Report_Form {

  public $_specialCase;
  public $_columnHeaders;
  /**
   * @var string
   */
  public $_from;
  public $_where;
  /**
   * @var string
   */
  public $_groupBy;
  /**
   * @var string
   */
  public $_orderBy;
  public $_aliases;
  public $_outputMode;
  public $_receiptColumn;
  protected $_summary = NULL;
  protected $_customGroupExtends = ['Contribution'];
  protected $_receiptTitle = NULL;
  protected $_receiptSerial = NULL;
  protected $_columnSort = NULL;

  function __construct() {
    $config = CRM_Core_Config::singleton();
    $contactTypes = CRM_Contact_BAO_ContactType::basicTypePairs();
    foreach($contactTypes as $key => $name) {
      if ($key !== 'Individual') {
        unset($contactTypes[$key]);
      }
    }
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        [
          'id' =>
          [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'sort_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ],
          'contact_type' =>
          [
            'title' => ts('Contact Type'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => FALSE,
          ],
          'contact_type' =>
          [
            'title' => ts('Contact Type'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ],
          'legal_identifier' =>
          [
            'title' => ts('Legal Identifier'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ],
          'sic_code' =>
          [
            'title' => ts('sic_code'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ],
        ],
        'filters' => 
        [
          'contact_type' =>
          ['title' => ts('Contact Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contactTypes,
            'default' => ['Individual'],
          ],
          'legal_identifier' => 
          [
            'title' => ts('Legal Identifier'),
            'operationPair' => [
              'nnll' => ts('Is not empty (Null)'),
              'nll' => ts('Is empty (Null)'),
            ],
          ],
        ],
      ],
      'civicrm_contribution' =>
      ['dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        [
          'receive_date' => [
            'default' => TRUE,
            'required' => TRUE,
          ],
          'total_amount' => ['title' => ts('Amount'),
            'required' => TRUE,
            'statistics' =>
            ['sum' => ts('Amount')],
          ],
        ],
        'filters' =>
        ['receive_date' =>
          ['default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'contribution_type_id' =>
          ['name' => 'contribution_type_id',
            'title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ],
          'contribution_status_id' =>
          ['title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
        ],
      ],
      'civicrm_group' =>
      ['dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'filters' =>
        ['gid' =>
          ['name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ],
        ],
      ],
    ];

    $this->_tagFilter = TRUE;
    parent::__construct();

    // only allowed custom field
    $this->_receiptTitle = 'custom_'.$config->receiptTitle;
    $this->_receiptSerial = 'custom_'.$config->receiptSerial;
    $allowedFields = [
      $this->_receiptTitle,
      $this->_receiptSerial,
    ];
    foreach($this->_columns as $key => $column){
      if(isset($column['extends']) && $column['extends'] == 'Contribution' && !empty($column['fields'])){
        foreach($column['fields'] as $field_name => $values){
          if(in_array($field_name, $allowedFields)){
            $this->_columns[$key]['fields'][$field_name]['required'] = TRUE;
          }
          else{
            unset($this->_columns[$key]['fields'][$field_name]);
          }
        }
      }
    }
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = [];
    $columnHeaders = [];
    $this->_specialCase = '';

    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // only include statistics columns if set
            if($fieldName == 'total_amount'){
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
            }
            elseif ($fieldName == $this->_receiptTitle) {
              $select[] = "
(CASE
  WHEN {$field['dbAlias']} IS NOT NULL AND LENGTH({$field['dbAlias']}) > 0 
  THEN {$field['dbAlias']}
  ELSE {$this->_aliases['civicrm_contact']}.sort_name END) as receipt_title
";
            }
            elseif ($fieldName == $this->_receiptSerial) {
              $this->_specialCase = "
(CASE
  WHEN {$field['dbAlias']} IS NOT NULL AND LENGTH({$field['dbAlias']}) > 0
  THEN {$field['dbAlias']}
  ELSE 
    (CASE WHEN {$this->_aliases['civicrm_contact']}.contact_type = 'Organization' THEN {$this->_aliases['civicrm_contact']}.sic_code ELSE {$this->_aliases['civicrm_contact']}.legal_identifier END)
  END)
";
              $select[] = $this->_specialCase .' as receipt_serial ';
            }
            else{
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
            $columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_columnHeaders = $columnHeaders;
    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function modifyColumnHeaders(){
    $this->_columnHeaders["civicrm_contribution_total_amount"]['type'] = 1;
    $this->_columnHeaders["civicrm_contribution_receive_date"]['type'] = 1;
    $this->_columnHeaders["receipt_title"]['type'] = 1;
    $this->_columnHeaders["receipt_serial"]['type'] = 1;
  }

  static function formRule($fields, $files, $self) {
    $errors = [];

    return $errors;
  }

  function from() {
    $this->_from = "
FROM civicrm_contribution {$this->_aliases['civicrm_contribution']}
INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0 ";
  }

  function where() {
    $params = $this->_params;

    // #27716, remove default null operation, because we will use REGEXP after parent build query
    if (isset($this->_params['legal_identifier_op'])) {
      $this->_params['legal_identifier_op'] = 'has';
    }
    parent::where();
    
    // contact doesn't has legal identifier
    if (isset($params['legal_identifier_op']) && $params['legal_identifier_op'] == 'nll') {
      // force empty string to go null
      $this->_where .= "AND (NULLIF({$this->_specialCase}, '') IS NULL)";
    }
    // only contacts has legal identifier
    else {
      // empty string will go null, then regexp can filter out
      $this->_where .= "AND (NULLIF({$this->_specialCase}, '') NOT REGEXP '^[0-9]{8}$')";
    }
  }

  function groupBy(){
    $this->_groupBy = "
GROUP BY receipt_title, receipt_serial";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY receipt_title ASC";
  }

  function postProcess() {
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function endPostProcess(&$rows = NULL) {
    if ($this->_outputMode == 'csv') {
      $year = $rows[0]['civicrm_contribution_receive_date'];
      CRM_Report_Utils_Report::export2xls($this, $rows, $year . '_' . ts('Your SIC Code'). '.xlsx');
    }
    else {
      parent::endPostProcess($rows);
    }
  }

  function alterDisplay(&$rows) {
    // change columnheader
    $columnHeaders = $this->_columnHeaders;
    $this->_columnHeaders = [];
    $this->_columnSort = [
      'receive_date' => '捐贈年度',
      'receipt_serial' => '捐贈者身分證統一編號',
      'receipt_title' => '捐贈者姓名',
      'total_amount' => '捐款金額',
      'other1' => '受捐贈單位統一編號',
    ];
    foreach($this->_columnSort as $c => $name){
      foreach($columnHeaders as $header => $value){
        if(preg_match('/'.$c.'$/', $header)){
          $value['title'] = $name;
          $this->_columnHeaders[$header] = $value;
          $this->_receiptColumn[$c] = $header;
        }
      }
    }
    $this->_columnHeaders['other1'] = ['type' => 2, 'title' => '受捐贈單位統一編號'];
    $this->_columnHeaders['other2'] = ['type' => 2, 'title' => '捐贈別'];
    $this->_columnHeaders['other3'] = ['type' => 2, 'title' => '受捐贈者名稱'];
    $this->_columnHeaders['other4'] = ['type' => 2, 'title' => '專案核准文號'];

    // custom code to alter rows
    $receiptSerial = $this->_receiptColumn[$this->_receiptSerial];
    $receiptTitle = $this->_receiptColumn[$this->_receiptTitle];

    if (!empty($rows)) {
      foreach ($rows as $n => $row) {
        // chinese year
        if(!empty($row['civicrm_contribution_receive_date'])){
          $rows[$n]['civicrm_contribution_receive_date'] = $this->_chineseYear($row['civicrm_contribution_receive_date']);
        }

        if(!empty($row['receipt_title'])) {
          $rows[$n]['receipt_title'] = mb_strtoupper($row['receipt_title']);
        }

        if(!empty($row['receipt_serial'])) {
          $rows[$n]['receipt_serial'] = mb_strtoupper($row['receipt_serial']);
        }

        // donor's name when not enough personal id
        if(!empty($row['civicrm_contribution_total_amount'])){
          $rows[$n]['civicrm_contribution_total_amount'] = round($row['civicrm_contribution_total_amount']);
        }
      }
    }
  }

  function _chineseYear($date){
    $year = (int) date('Y', strtotime($date));
    $year -= 1911;
    return sprintf('%03s', $year);
  }
}

