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
 * This class generates form components for DedupeRules
 *
 */
class CRM_Contact_Form_DedupeRules extends CRM_Admin_Form {
  public $_contactTypeDisplay;
  CONST RULES_COUNT = 5;
  protected $_contactType;
  protected $_defaults = [];
  protected $_fields = [];
  protected $_rgid;

  /**
   * Function to pre processing
   *
   * @return None
   * @access public
   */
  function preProcess() {
    // Ensure user has permission to be here


    if (!CRM_Core_Permission::check('administer dedupe rules')) {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }

    $this->_rgid = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $this->_contactType = CRM_Utils_Request::retrieve('contact_type', 'String', $this, FALSE, 0);
    if ($this->_rgid) {
      $contactType = new CRM_Contact_BAO_ContactType();

      $rgDao = new CRM_Dedupe_DAO_RuleGroup();
      $rgDao->id = $this->_rgid;
      $rgDao->find(TRUE);
      $this->_defaults['threshold'] = $rgDao->threshold;
      $this->_contactType = $rgDao->contact_type;
      $params = ['name' => $rgDao->contact_type];
      $_contactType = $contactType->retrieve($params, $defaults);
      $this->_contactTypeDisplay = $_contactType->label;

      $this->_defaults['level'] = $rgDao->level;
      $this->_defaults['name'] = $rgDao->name;
      $this->_defaults['is_default'] = $rgDao->is_default;
      $ruleDao = new CRM_Dedupe_DAO_Rule();
      $ruleDao->dedupe_rule_group_id = $this->_rgid;
      $ruleDao->find();
      $count = 0;
      while ($ruleDao->fetch()) {
        $this->_defaults["where_$count"] = "{$ruleDao->rule_table}.{$ruleDao->rule_field}";
        $this->_defaults["length_$count"] = $ruleDao->rule_length;
        $this->_defaults["weight_$count"] = $ruleDao->rule_weight;
        $count++;
      }
    }
    $supported = &CRM_Dedupe_BAO_RuleGroup::supportedFields($this->_contactType);
    if (is_array($supported)) {
      foreach ($supported as $table => $fields) {
        foreach ($fields as $field => $title) {
          $this->_fields["$table.$field"] = $title;
        }
      }
    }
    asort($this->_fields);
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->add('text', 'name', ts('Rule Name'));
    $levelType = [
      'Strict' => ts('Strict'),
      'Fuzzy' => ts('Fuzzy'),
    ];
    $ruleLevel = $this->add('select', 'level', ts('Level'), $levelType);

    $default = $this->add('checkbox', 'is_default', ts('Default?'));
    if (CRM_Utils_Array::value('is_default', $this->_defaults)) {
      $default->freeze();
      $ruleLevel->freeze();
    }

    // #31293, it's will be disaster when user config this to wrong value
    // lock for uf id 1 for data preserve reason
    $ufID = CRM_Utils_System::getLoggedInUfID();
    if ($ufID != 1) {
      $default->freeze();
    }

    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      $this->add('select', "where_$count", ts('Field'), [NULL => ts('- none -')] + $this->_fields);
      $this->add('text', "length_$count", ts('Length'), ['class' => 'two', 'style' => 'text-align: right']);
      $this->add('text', "weight_$count", ts('Weight'), ['class' => 'two', 'style' => 'text-align: right']);
      $this->addRule("weight_$count", ts('%1 should be a postive number', [1 => ts('Weight')]), 'positiveInteger');
      $this->addRule("weight_$count", ts('%1 should be a postive number', [1 => ts('Weight')]), 'nonzero');
    }
    $this->add('text', 'threshold', ts("Weight Threshold to Consider Contacts 'Matching':"), ['class' => 'two', 'style' => 'text-align: right']);
    $this->addRule('threshold', ts('%1 should be a postive number', [1 => ts('Threshold')]), 'positiveInteger');
    $this->addRule('threshold', ts('%1 should be a postive number', [1 => ts('Threshold')]), 'nonzero');
    $this->addButtons([
        ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => ts('Cancel')],
      ]);
    $this->assign('contact_type', $this->_contactTypeDisplay);
    $this->addFormRule(['CRM_Contact_Form_DedupeRules', 'formRule']);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = [];
    $total = 0;
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (!empty($fields['weight_'.$count])) {
        $total += $fields['weight_'.$count]; 
      }
    }
    if ($total < $fields['threshold']) {
      for ($count = 0; $count < self::RULES_COUNT; $count++) {
        if (!empty($fields['weight_'.$count])) {
          $errors['weight_'.$count] = ts('Total of rule weight should greater then equal threshold.');
        }
      }
      $errors['threshold'] = ts('Total Weight')."($total)  < ".ts('Threshold')."({$fields['threshold']})";
    }
    return $errors;
  }

  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $values = $this->exportValues();
    $isDefault = CRM_Utils_Array::value('is_default', $values, FALSE);
    // reset defaults
    if ($isDefault) {
      $query = "
UPDATE civicrm_dedupe_rule_group 
   SET is_default = 0
 WHERE contact_type = %1 
   AND level = %2";
      $queryParams = [1 => [$this->_contactType, 'String'],
        2 => [$values['level'], 'String'],
      ];
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $rgDao = new CRM_Dedupe_DAO_RuleGroup();
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $rgDao->id = $this->_rgid;
    }
    $rgDao->threshold = $values['threshold'];
    $rgDao->name = $values['name'];
    $rgDao->level = $values['level'];
    $rgDao->contact_type = $this->_contactType;
    $rgDao->is_default = $isDefault;
    $rgDao->save();

    $ruleDao = new CRM_Dedupe_DAO_Rule();
    $ruleDao->dedupe_rule_group_id = $rgDao->id;
    $ruleDao->delete();
    $ruleDao->free();

    $substrLenghts = [];

    $tables = [];
    for ($count = 0; $count < self::RULES_COUNT; $count++) {
      if (!CRM_Utils_Array::value("where_$count", $values)) {
        continue;
      }
      list($table, $field) = explode('.', CRM_Utils_Array::value("where_$count", $values));
      $length = CRM_Utils_Array::value("length_$count", $values) ? CRM_Utils_Array::value("length_$count", $values) : NULL;
      $weight = $values["weight_$count"];
      if ($table and $field) {
        $ruleDao = new CRM_Dedupe_DAO_Rule();
        $ruleDao->dedupe_rule_group_id = $rgDao->id;
        $ruleDao->rule_table = $table;
        $ruleDao->rule_field = $field;
        $ruleDao->rule_length = $length;
        $ruleDao->rule_weight = $weight;
        $ruleDao->save();
        $ruleDao->free();

        if (!CRM_Utils_Array::arrayKeyExists($table, $tables)) {
          $tables[$table] = [];
        }
        $tables[$table][] = $field;
      }

      // CRM-6245: we must pass table/field/length triples to the createIndexes() call below
      if ($length) {
        if (!isset($substrLenghts[$table])) {
          $substrLenghts[$table] = [];
        }
        $substrLenghts[$table][$field] = $length;
      }
    }

    // also create an index for this dedupe rule
    // CRM-3837

    CRM_Core_BAO_SchemaHandler::createIndexes($tables, 'dedupe_index', $substrLenghts);
  }
}

