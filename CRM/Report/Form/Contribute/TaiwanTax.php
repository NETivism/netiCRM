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

  protected $_summary = NULL;
  protected $_customGroupExtends = array('Contribution');
  protected $_receiptTitle = NULL;
  protected $_receiptSerial = NULL;
  protected $_columnSort = NULL;

  function __construct() {
    $config = CRM_Core_Config::singleton();
    $this->_columns = array('civicrm_contact' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => FALSE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ),
          'legal_identifier' =>
          array(
            'title' => ts('Legal Identifier'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ),
          'sic_code' =>
          array(
            'title' => ts('sic_code'),
            'required' => TRUE,
            'no_repeat' => FALSE,
          ),
        ),
      ),
      'civicrm_contribution' =>
      array('dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'receive_date' => array(
            'default' => TRUE,
            'required' => TRUE,
          ),
          'total_amount' => array('title' => ts('Amount'),
            'required' => TRUE,
            'statistics' =>
            array('sum' => ts('Amount')),
          ),
        ),
        'filters' =>
        array('receive_date' =>
          array('default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'contribution_type_id' =>
          array('name' => 'contribution_type_id',
            'title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ),
          'contribution_status_id' =>
          array('title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
        ),
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

    // only allowed custom field
    $this->_receiptTitle = 'custom_'.$config->receiptTitle;
    $this->_receiptSerial = 'custom_'.$config->receiptSerial;
    $allowedFields = array(
      $this->_receiptTitle,
      $this->_receiptSerial,
    );
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
    $select = array();
    $columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // only include statistics columns if set
            if($fieldName == 'total_amount'){
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
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
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function modifyColumnHeaders(){
    $this->_columnHeaders["civicrm_contribution_total_amount"]['type'] = 1;
  }

  static function formRule($fields, $files, $self) {
    $errors = array();

    return $errors;
  }

  function from() {
    $this->_from = NULL;
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
        	INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
		          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        ";
  }

  function groupBy(){
    $this->_groupBy = " GROUP BY (CASE WHEN {$this->_aliases['civicrm_contact']}.legal_identifier IS NULL OR {$this->_aliases['civicrm_contact']}.legal_identifier = '' THEN {$this->_aliases['civicrm_contact']}.id ELSE {$this->_aliases['civicrm_contact']}.legal_identifier END)";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contribution']}.receive_date ";
  }

  function postProcess() {
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // change columnheader
    $columnHeaders = $this->_columnHeaders;
    $this->_columnHeaders = array();
    $this->_columnSort = array(
      'receive_date' => '捐贈年度',
      $this->_receiptSerial => '捐贈者身分證統一編號',
      $this->_receiptTitle => '捐贈者姓名',
      'total_amount' => '捐款金額',
    );
    foreach($this->_columnSort as $c => $name){
      foreach($columnHeaders as $header => $value){
        if(preg_match('/'.$c.'$/', $header)){
          $value['title'] = $name;
          $this->_columnHeaders[$header] = $value;
          $this->_receiptColumn[$c] = $header;
        }
      }
    }
    $this->_columnHeaders['other1'] = array('type' => 2, 'title' => '受捐贈單位統一編號');
    $this->_columnHeaders['other2'] = array('type' => 2, 'title' => '捐贈別');
    $this->_columnHeaders['other3'] = array('type' => 2, 'title' => '受捐贈者姓名');
    $this->_columnHeaders['other4'] = array('type' => 2, 'title' => '專案核准文號');

    // custom code to alter rows
    $receiptSerial = $this->_receiptColumn[$this->_receiptSerial];
    $receiptTitle = $this->_receiptColumn[$this->_receiptTitle];

    if (!empty($rows)) {
      foreach ($rows as $n => $row) {
        // chinese year
        if(!empty($row['civicrm_contribution_receive_date'])){
          $rows[$n]['civicrm_contribution_receive_date'] = $this->_chineseYear($row['civicrm_contribution_receive_date']);
        }

        // donor's name when not enough personal id
        if(empty($row[$receiptTitle])){
          $rows[$n][$receiptTitle] = !empty($row['civicrm_contact_sort_name']) ? $row['civicrm_contact_sort_name'] : '';
        }
        if(empty($row[$receiptSerial])){
          $alterSerialColumn = $row['civicrm_contact_contact_type'] == 'Individual' ?  'legal_identifier' : 'sic_code';
          $rows[$n][$receiptSerial] = !empty($row['civicrm_contact_'.$alterSerialColumn]) ? $row['civicrm_contact_'.$alterSerialColumn] : '';
        }
        if(!empty($row['civicrm_contribution_total_amount'])){
          $rows[$n]['civicrm_contribution_total_amount'] = round($row['civicrm_contribution_total_amount']);
        }
      }
    }
  }

  function _chineseYear($date){
    $year = date('Y', strtotime($date));
    $year -= 1911;
    return sprintf('%03s', $year);
  }
}

