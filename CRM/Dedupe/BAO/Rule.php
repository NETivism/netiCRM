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



/**
 * The CiviCRM duplicate discovery engine is based on an
 * algorithm designed by David Strauss <david@fourkitchens.com>.
 */
class CRM_Dedupe_BAO_Rule extends CRM_Dedupe_DAO_Rule {

  /**
   * ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   */
  var $contactIds = [];

  /**
   * params to dedupe against (queries against the whole contact set otherwise)
   */
  var $params = [];

  /**
   * Return the SQL query for the given rule - either for finding matching
   * pairs of contacts, or for matching against the $params variable (if set).
   *
   * @return string  SQL query performing the search
   */
  function sql() {
    if ($this->params &&
      (!CRM_Utils_Array::arrayKeyExists($this->rule_table, $this->params) ||
        !CRM_Utils_Array::arrayKeyExists($this->rule_field, $this->params[$this->rule_table])
      )
    ) {
      // if params is present and doesn't have an entry for a field, don't construct the clause.
      return NULL;
    }

    // we need to initialise WHERE, ON and USING here, as some table types
    // extend them; $where is an array of required conditions, $on and
    // $using are arrays of required field matchings (for substring and
    // full matches, respectively)
    $where = $whereOr = [];
    $on = ["SUBSTR(t1.{$this->rule_field}, 1, {$this->rule_length}) = SUBSTR(t2.{$this->rule_field}, 1, {$this->rule_length})"];
    $using = [$this->rule_field];

    switch ($this->rule_table) {
      case 'civicrm_contact':
        $id = 'id';
        break;

      case 'civicrm_address':
        $id = 'contact_id';
        break;

      case 'civicrm_email':
      case 'civicrm_im':
      case 'civicrm_openid':
      case 'civicrm_phone':
        $id = 'contact_id';
        break;

      case 'civicrm_note':
        $id = 'entity_id';
        if ($this->params) {
          $where[] = "t1.entity_table = 'civicrm_contact'";
        }
        else {
          $where[] = "t1.entity_table = 'civicrm_contact'";
          $where[] = "t2.entity_table = 'civicrm_contact'";
        }
        break;

      default:
        // custom data tables
        if (preg_match('/^civicrm_value_/', $this->rule_table) || preg_match('/^custom_value_/', $this->rule_table)) {
          $id = 'entity_id';
        }
        else {
          CRM_Core_Error::fatal("Unsupported rule_table for civicrm_dedupe_rule.id of {$this->id}");
        }
        break;
    }

    // build SELECT based on the field names containing contact ids
    // if there are params provided, id1 should be 0
    if ($this->params) {
      $select = "t1.$id id1, {$this->rule_weight} weight";
    }
    else {
      $select = "t1.$id id1, t2.$id id2, {$this->rule_weight} weight";
    }

    // build FROM (and WHERE, if it's a parametrised search)
    // based on whether the rule is about substrings or not
    if ($this->params) {
      $from = "{$this->rule_table} t1";
      $str = 'NULL';
      if (CRM_Utils_Array::arrayKeyExists($this->rule_field, $this->params[$this->rule_table])) {
        if (is_array($this->params[$this->rule_table][$this->rule_field])) {
          foreach($this->params[$this->rule_table][$this->rule_field] as $str) {
            $str = CRM_Utils_Type::escape($str, 'String');
            if ($this->rule_length) {
              $where[] = "SUBSTR(t1.{$this->rule_field}, 1, {$this->rule_length}) = SUBSTR('$str', 1, {$this->rule_length})";
              $whereOr[] = "t1.{$this->rule_field} IS NOT NULL";
            }
            else {
              $whereOr[] = "t1.{$this->rule_field} = '$str'";
            }
          }
        }
        else {
          $str = CRM_Utils_Type::escape($this->params[$this->rule_table][$this->rule_field], 'String');
          if ($this->rule_length) {
            $where[] = "SUBSTR(t1.{$this->rule_field}, 1, {$this->rule_length}) = SUBSTR('$str', 1, {$this->rule_length})";
            $where[] = "t1.{$this->rule_field} IS NOT NULL";
          }
          elseif ($this->rule_field === 'phone') {
            $where[] = "REPLACE(t1.{$this->rule_field}, '-', '') = REPLACE('$str', '-', '')";
          }
          else {
            $where[] = "t1.{$this->rule_field} = '$str'";
          }
        }
      }
    }
    else {
      if ($this->rule_length) {
        $from = "{$this->rule_table} t1 JOIN {$this->rule_table} t2 ON (" . CRM_Utils_Array::implode(' AND ', $on) . ")";
      }
      else {
        $from = "{$this->rule_table} t1 JOIN {$this->rule_table} t2 USING (" . CRM_Utils_Array::implode(', ', $using) . ")";
      }
    }

    // finish building WHERE, also limit the results if requested
    if (!$this->params) {
      $where[] = "t1.$id < t2.$id";
      $where[] = "NULLIF(t1.{$this->rule_field} , '') IS NOT NULL";
    }
    if ($this->contactIds) {
      $cids = [];
      foreach ($this->contactIds as $cid) {
        $cids[] = CRM_Utils_Type::escape($cid, 'Integer');
      }
      if (count($cids) == 1) {
        $where[] = "(t1.$id = {$cids[0]} OR t2.$id = {$cids[0]})";
      }
      else {
        $where[] = "(t1.$id IN (" . CRM_Utils_Array::implode(',', $cids) . ") OR t2.$id IN (" . CRM_Utils_Array::implode(',', $cids) . "))";
      }
    }

    $sql = "SELECT $select FROM $from WHERE " . CRM_Utils_Array::implode(' AND ', $where);
    if (!empty($whereOr)) {
      if (!empty($where)) {
        $sql .= " AND ( ".CRM_Utils_Array::implode(' OR ', $whereOr)." )";
      }
      else {
        $sql .= " ( ".CRM_Utils_Array::implode(' OR ', $whereOr)." )";
      }
    }
    return $sql;
  }

  /**
   * To find fields related to a rule group.
   *
   * @param array contains the rule group property to identify rule group
   *
   * @return rule fields array associated to rule group
   * @access public
   */
  static function dedupeRuleFields($params) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    if (!empty($params['id'])) {
      $rgBao->id = $params['id'];
    }
    else{
      // find default
      $rgBao->level = $params['level'];
      $rgBao->contact_type = $params['contact_type'];
      $rgBao->is_default = 1;
    }
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_Rule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = [];
    while ($ruleBao->fetch()) {
      $ruleFields[] = $ruleBao->rule_field;
    }
    return $ruleFields;
  }

  /**
   * To find fields related to a rule group.
   *
   * @param array contains the rule group property to identify rule group
   *
   * @return rule fields array associated to rule group
   * @access public
   */
  static function dedupeRuleFieldsMapping($params) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    if (!empty($params['id'])) {
      $rgBao->id = $params['id'];
    }
    else{
      // find default
      $rgBao->level = $params['level'];
      $rgBao->contact_type = $params['contact_type'];
      $rgBao->is_default = 1;
    }
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_Rule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = [];
    while ($ruleBao->fetch()) {
      // custom field needs id
      if (strstr($ruleBao->rule_table, 'civicrm_value_')) {
        $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $ruleBao->rule_field, 'id', 'column_name');
        if (!empty($customFieldId)) {
          $ruleFields[] = 'custom_'.$customFieldId;
          continue;
        }
      }
      $ruleFields[] = $ruleBao->rule_field;
    }
    return $ruleFields;
  }
}

