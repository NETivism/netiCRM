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
require_once 'CRM/Contribute/PseudoConstant.php';
class CRM_Report_Form_Contribute_OrganizationSummary extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL; function __construct() {

    $this->validRelationships();

    $this->_columns = array(
      'civicrm_contact_organization' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'organization_name' =>
          array('title' => ts('Organization Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array('no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'organization_name' =>
          array('title' => ts('Organization Name')),
        ),
        'grouping' => 'organization-fields',
      ),
      'civicrm_relationship' =>
      array('dao' => 'CRM_Contact_DAO_Relationship',
        'fields' =>
        array(
          'relationship_type_id' =>
          array('title' => ts('Relationship Type'),
          ),
        ),
        'filters' =>
        array('relationship_type_id' =>
          array(
            'title' => ts('Relationship Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->relationTypes,
            'default' => array(1),
          ),
        ),
        'grouping' => 'organization-fields',
      ),
      'civicrm_contact' =>
      array('dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array('sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
          ),
          'id' =>
          array('no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' =>
      array('dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array('total_amount' => array('title' => ts('Amount'),
            'required' => TRUE,
          ),
          'contribution_status_id' => array('title' => ts('Contribution Status'),
            'default' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
        ),
        'filters' =>
        array('receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'total_amount' =>
          array('title' => ts('Amount Between')),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
        ),
        'grouping' => 'contri-fields',
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
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array('dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
    );

    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $this->_columnHeaders = $select = array();
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
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

            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}_{$stat}";
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
              }
            }
            else {
              $select[] = "{$table['alias']}.{$fieldName} as {$tableName}_{$fieldName}";

              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }
    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = NULL;
    $this->_from = "
        FROM  civicrm_relationship  {$this->_aliases['civicrm_relationship']}
            LEFT  JOIN civicrm_contact {$this->_aliases['civicrm_contact_organization']} ON 
                      ({$this->_aliases['civicrm_contact_organization']}.id = {$this->_aliases['civicrm_relationship']}.$this->orgContact AND {$this->_aliases['civicrm_contact_organization']}.contact_type='Organization')
            LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON 
                      ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_relationship']}.$this->otherContact )       
            {$this->_aclFrom}
            INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} ON
                      ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_relationship']}.$this->otherContact ) AND {$this->_aliases['civicrm_contribution']}.is_test = 0 ";

    if ($this->_addressField) {
      $this->_from .= " 
            LEFT JOIN civicrm_address  {$this->_aliases['civicrm_address']} ON 
                      {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                      {$this->_aliases['civicrm_address']}.is_primary = 1\n ";
    }
    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} ON 
                      {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                      {$this->_aliases['civicrm_email']}.is_primary = 1\n ";
    }
  }

  function where() {
    $clauses = array();
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
              if ($fieldName == 'relationship_type_id') {
                $clause = "{$this->_aliases['civicrm_relationship']}.relationship_type_id=" . $this->relationshipId;
              }
              else {
                $clause = $this->whereClause($field,
                  $op,
                  CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                );
              }
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 )";
    }
    else {
      $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_relationship']}.$this->orgContact, {$this->_aliases['civicrm_relationship']}.$this->otherContact , {$this->_aliases['civicrm_contribution']}.id, {$this->_aliases['civicrm_relationship']}.relationship_type_id ";
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    //hack filter display for relationship type
    $type = substr($this->_params['relationship_type_id_value'], -3);
    foreach ($statistics['filters'] as $id => $value) {
      if ($value['title'] == 'Relationship Type') {
        $statistics['filters'][$id]['value'] = 'Is equal to ' . $this->relationTypes[$this->relationshipId . '_' . $type];
      }
    }
    return $statistics;
  }

  function postProcess() {

    $this->beginPostProcess();
    $getRelationship = $this->_params['relationship_type_id_value'];
    $type = substr($getRelationship, -3);
    $this->relationshipId = intval((substr($getRelationship, 0, strpos($getRelationship, '_'))));
    if ($type == 'b_a') {
      $this->orgContact = 'contact_id_b';
      $this->otherContact = 'contact_id_a';
    }
    else {
      $this->orgContact = 'contact_id_a';
      $this->otherContact = 'contact_id_b';
    }

    $this->buildACLClause(array($this->_aliases['civicrm_contact'], $this->_aliases['civicrm_contact_organization']));
    $sql = $this->buildQuery(TRUE);
    $rows = array();

    $this->buildRows($sql, $rows);
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function validRelationships() {
    require_once ("api/v2/RelationshipType.php");
    $this->relationTypes = $relationTypes = array();

    $params = array('contact_type_b' => 'Organization');
    $typesA = civicrm_relationship_types_get($params);
    foreach ($typesA as $rel) {
      $relationTypes[$rel['id']][$rel['id'] . '_b_a'] = $rel['label_b_a'];
    }

    $params = array('contact_type_a' => 'Organization');
    $typesB = civicrm_relationship_types_get($params);
    foreach ($typesB as $rel) {
      $relationTypes[$rel['id']][$rel['id'] . '_a_b'] = $rel['label_a_b'];
    }

    ksort($relationTypes);
    foreach ($relationTypes as $relationship) {
      foreach ($relationship as $index => $label) {
        $this->relationTypes[$index] = $label;
      }
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $type = substr($this->_params['relationship_type_id_value'], -3);

    $entryFound = FALSE;
    $flagOrganization = $flagContact = 0;

    foreach ($rows as $rowNum => $row) {

      //replace retionship id by relationship name
      if (CRM_Utils_Array::arrayKeyExists('civicrm_relationship_relationship_type_id', $row)) {
        if ($value = $row['civicrm_relationship_relationship_type_id']) {
          $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = $this->relationTypes[$value . '_' . $type];
          $entryFound = TRUE;
        }
      }

      //remove duplicate Organization names
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_organization_id', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_organization_id']) {
          if ($rowNum == 0) {
            $previousOrganization = $value;
          }
          else {
            if ($previousOrganization == $value) {
              $flagOrganization = 1;
              $previousOrganization = $value;
            }
            else {
              $flagOrganization = 0;
              $previousOrganization = $value;
            }
          }

          if ($flagOrganization == 1) {
            foreach ($row as $colName => $colVal) {
              if (in_array($colName, $this->_noRepeats)) {
                unset($rows[$rowNum][$colName]);
              }
            }
          }
          $entryFound = TRUE;
        }
      }

      // convert Organization display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_organization_organization_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_organization_organization_name',
          $rows[$rowNum]
        ) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_organization_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' .
          $rows[$rowNum]['civicrm_contact_organization_id'],
          $this->_absoluteUrl
        );

        $rows[$rowNum]['civicrm_contact_organization_organization_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_organization_organization_name_hover'] = ts("View Contact Summary for this Organization.");
      }

      //remove duplicate Contact names and relationship type
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_id']) {
          if ($rowNum == 0) {
            $previousContact = $value;
          }
          else {
            if ($previousContact == $value) {
              $flagContact = 1;
              $previousContact = $value;
            }
            else {
              $flagContact = 0;
              $previousContact = $value;
            }
          }

          if ($flagContact == 1 && $flagOrganization == 1) {
            $rows[$rowNum]['civicrm_contact_sort_name'] = "";
            $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = "";
          }

          $entryFound = TRUE;
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_contribution_contribution_status_id', $row)) {
        if ($value = $row['civicrm_contribution_contribution_status_id']) {
          $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
        }
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

      // convert Individual display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;

        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }
}

