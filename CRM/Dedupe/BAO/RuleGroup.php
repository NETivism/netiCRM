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
class CRM_Dedupe_BAO_RuleGroup extends CRM_Dedupe_DAO_RuleGroup {

  public $_aclFrom;
  public $_aclWhere;
  /**
   * ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   */
  var $contactIds = [];

  /**
   * params to dedupe against (queries against the whole contact set otherwise)
   */
  var $params = [];

  /**
   * custom rules when using rules made by program
   * ```php
   *   array(
   *     array('table'=>'civicrm_contact', 'field'=>'external_identifier', 'weight'=>10),
   *   )
   * ``` 
   */
  var $rules = [];

  /**
   * if there are no rules in rule group
   */
  var $noRules = FALSE;

  /**
   * Return a structure holding the supported tables, fields and their titles
   *
   * @param string $requestedType  the requested contact type
   *
   * @return array  a table-keyed array of field-keyed arrays holding supported fields' titles
   */
  static function &supportedFields($requestedType) {
    static $fields = NULL;
    if (!$fields) {
      // this is needed, as we're piggy-backing importableFields() below
      $replacements = [
        'civicrm_country.name' => 'civicrm_address.country_id',
        'civicrm_county.name' => 'civicrm_address.county_id',
        'civicrm_state_province.name' => 'civicrm_address.state_province_id',
        'gender.label' => 'civicrm_contact.gender_id',
        'individual_prefix.label' => 'civicrm_contact.prefix_id',
        'individual_suffix.label' => 'civicrm_contact.suffix_id',
        'addressee.label' => 'civicrm_contact.addressee_id',
        'email_greeting.label' => 'civicrm_contact.email_greeting_id',
        'postal_greeting.label' => 'civicrm_contact.postal_greeting_id',
      ];
      // the table names we support in dedupe rules - a filter for importableFields()
      $supportedTables = ['civicrm_address', 'civicrm_contact', 'civicrm_email',
        'civicrm_im', 'civicrm_note', 'civicrm_openid', 'civicrm_phone',
      ];



      foreach (['Individual', 'Organization', 'Household'] as $ctype) {
        // take the table.field pairs and their titles from importableFields() if the table is supported
        foreach (CRM_Contact_BAO_Contact::importableFields($ctype) as $iField) {
          if (isset($iField['where'])) {
            $where = $iField['where'];
            if (isset($replacements[$where])) {
              $where = $replacements[$where];
            }
            list($table, $field) = explode('.', $where);
            if (!in_array($table, $supportedTables)) {
              continue;
            }
            $fields[$ctype][$table][$field] = $iField['title'];
          }
        }
        // add custom data fields
        foreach (CRM_Core_BAO_CustomGroup::getTree($ctype, CRM_Core_DAO::$_nullObject, NULL, -1) as $key => $cg) {
          if (!is_int($key)) {
            continue;
          }
          foreach ($cg['fields'] as $cf) {
            $fields[$ctype][$cg['table_name']][$cf['column_name']] = $cf['label'];
          }
        }

        // add special field only for contact table to speed up name dedupe
        $special_contact_field = ['sort_name' => ts('Sort Name'), 'display_name' => ts('Display Name')];
        foreach($special_contact_field as $value => $name){
          foreach($fields as $ctype => $table){
            $fields[$ctype]['civicrm_contact'][$value] = $name;
          }
        }
      }
    }
    return $fields[$requestedType];
  }

  /**
   * Return the SQL query for dropping the temporary table.
   */
  function tableDropQuery() {
    return 'DROP TEMPORARY TABLE IF EXISTS dedupe';
  }

  /**
   * Return the SQL query for creating the temporary table.
   */
  function tableQuery() {
    $queries = [];
    $idx = 0;
    if ($this->rules) {
      foreach($this->rules as $rule) {
        $bao = new CRM_Dedupe_BAO_Rule();
        $bao->contactIds = $this->contactIds;
        $bao->params = $this->params;
        $bao->rule_table = $rule['table'];
        $bao->rule_field = $rule['field'];
        $bao->rule_weight = $rule['weight'];
        $bao->rule_length = NULL;
        if ($query = $bao->sql()) {
          $queries["{$rule['table']}.{$rule['field']}.{$rule['weight']}"] = $query;
        }
      }
    }
    else {
      $bao = new CRM_Dedupe_BAO_Rule();
      $bao->dedupe_rule_group_id = $this->id;
      $bao->orderBy('rule_weight DESC');
      $bao->find();
      while ($bao->fetch()) {
        $bao->contactIds = $this->contactIds;
        $bao->params = $this->params;
        if ($query = $bao->sql()) {
          $queries["{$bao->rule_table}.{$bao->rule_field}.{$bao->rule_weight}"] = $query;
        }
      }
      $idx++;
    }

    // if there are no rules in this rule group, add an empty query fulfilling the pattern
    if (empty($queries)) {
      $queries = ['SELECT 0 id1, 0 id2, 0 weight LIMIT 0'];
      $this->noRules = TRUE;
    }

    return $queries;
  }

  function fillTable() {
    // get the list of queries handy
    $tableQueries = $this->tableQuery();

    if ($this->params && !$this->noRules) {
      $tempTableQuery = "CREATE TEMPORARY TABLE dedupe (id1 int, weight int, UNIQUE UI_id1 (id1)) ENGINE=MyISAM";
      $insertClause = "INSERT INTO dedupe (id1, weight)";
      $groupByClause = "GROUP BY id1";
      $dupeCopyJoin = " JOIN dedupe_copy ON dedupe_copy.id1 = t1.column WHERE ";
    }
    else {
      $tempTableQuery = "CREATE TEMPORARY TABLE dedupe (id1 int, id2 int, weight int, UNIQUE UI_id1_id2 (id1, id2)) ENGINE=MyISAM";
      $insertClause = "INSERT INTO dedupe (id1, id2, weight)";
      $groupByClause = "GROUP BY id1, id2";
      $dupeCopyJoin = " JOIN dedupe_copy ON dedupe_copy.id1 = t1.column AND dedupe_copy.id2 = t2.column WHERE ";
    }
    $patternColumn = '/t1.(\w+)/';
    $exclWeightSum = [];

    // create temp table
    $dao = new CRM_Core_DAO();
    $dao->query($tempTableQuery);


    CRM_Utils_Hook::dupeQuery($this, 'table', $tableQueries);

    while (!empty($tableQueries)) {
      list($isInclusive, $isDie) = self::isQuerySetInclusive($tableQueries, $this->threshold, $exclWeightSum);

      if ($isInclusive) {
        // order queries by table count
        self::orderByTableCount($tableQueries);

        $weightSum = array_sum($exclWeightSum);
        $searchWithinDupes = !empty($exclWeightSum) ? 1 : 0;

        while (!empty($tableQueries)) {
          // extract the next query ( and weight ) to be executed
          $fieldWeight = array_keys($tableQueries);
          $fieldWeight = $fieldWeight[0];
          $query = array_shift($tableQueries);

          if ($searchWithinDupes) {
            // get prepared to search within already found dupes if $searchWithinDupes flag is set
            $dao->query("DROP TEMPORARY TABLE IF EXISTS dedupe_copy");
            $dao->query("CREATE TEMPORARY TABLE dedupe_copy SELECT * FROM dedupe WHERE weight >= {$weightSum}");
            $dao->free();

            preg_match($patternColumn, $query, $matches);
            $query = str_replace(' WHERE ', str_replace('column', $matches[1], $dupeCopyJoin), $query);
          }
          $searchWithinDupes = 1;

          // construct and execute the intermediate query
          $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
          $dao->query($query);

          // FIXME: we need to be more acurate with affected rows, especially for insert vs duplicate insert.
          // And that will help optimize further.
          $affectedRows = $dao->affectedRows();
          $dao->free();

          // In an inclusive situation, failure of any query means no further processing -
          if ($affectedRows == 0) {
            // reset to make sure no further execution is done.
            $tableQueries = [];
            break;
          }
          $weightSum = substr($fieldWeight, strrpos($fieldWeight, '.') + 1) + $weightSum;
        }
        // An exclusive situation -
      }
      elseif (!$isDie) {
        // since queries are already sorted by weights, we can continue as is
        $fieldWeight = array_keys($tableQueries);
        $fieldWeight = $fieldWeight[0];
        $query = array_shift($tableQueries);
        $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
        $dao->query($query);
        if ($dao->affectedRows() >= 1) {
          $exclWeightSum[] = substr($fieldWeight, strrpos($fieldWeight, '.') + 1);
        }
        $dao->free();
      }
      else {
        // its a die situation
        break;
      }
    }
  }

  // Function to determine if a given query set contains inclusive or exclusive set of weights.
  // The function assumes that the query set is already ordered by weight in desc order.
  static function isQuerySetInclusive($tableQueries, $threshold, $exclWeightSum = []) {
    $input = [];
    foreach ($tableQueries as $key => $query) {
      $input[] = substr($key, strrpos($key, '.') + 1);
    }

    if (!empty($exclWeightSum)) {
      $input = array_merge($input, $exclWeightSum);
      rsort($input);
    }

    if (count($input) == 1) {
      return [FALSE, $input[0] < $threshold];
    }

    $totalCombinations = 0;
    for ($i = 0; $i < count($input); $i++) {
      $combination = [$input[$i]];
      if (array_sum($combination) >= $threshold) {
        $totalCombinations++;
        continue;
      }
      for ($j = $i + 1; $j < count($input); $j++) {
        $combination[] = $input[$j];
        if (array_sum($combination) >= $threshold) {
          $totalCombinations++;
        }
      }
    }
    return [$totalCombinations == 1, $totalCombinations <= 0];
  }

  // sort queries by number of records for the table associated with them
  static function orderByTableCount(&$tableQueries) {
    static $tableCount = [];

    $tempArray = [];
    foreach ($tableQueries as $key => $query) {
      $table = explode(".", $key);
      $table = $table[0];
      if (!CRM_Utils_Array::arrayKeyExists($table, $tableCount)) {
        $query = "SELECT COUNT(*) FROM {$table}";
        $tableCount[$table] = CRM_Core_DAO::singleValueQuery($query);
      }
      $tempArray[$key] = $tableCount[$table];
    }

    asort($tempArray);
    foreach ($tempArray as $key => $count) {
      $tempArray[$key] = $tableQueries[$key];
    }
    $tableQueries = $tempArray;
  }

  /**
   * Return the SQL query for getting only the interesting results out of the dedupe table.
   *
   * @$checkPermission boolean $params a flag to indicate if permission should be considered.
   * default is to always check permissioning but public pages for example might not want
   * permission to be checked for anonymous users. Refer CRM-6211. We might be beaking
   * Multi-Site dedupe for public pages.
   *
   */
  function thresholdQuery($checkPermission = TRUE) {

    $this->_aclFrom = '';
    // CRM-6603: anonymous dupechecks side-step ACLs
    $this->_aclWhere = ' AND is_deleted = 0 ';

    if ($this->params && !$this->noRules) {
      if ($checkPermission) {
        list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('civicrm_contact');
        $this->_aclWhere = $this->_aclWhere ? "AND {$this->_aclWhere}" : '';
      }
      $query = "SELECT dedupe.id1 as id
                FROM dedupe JOIN civicrm_contact ON dedupe.id1 = civicrm_contact.id {$this->_aclFrom}
                WHERE contact_type = '{$this->contact_type}' {$this->_aclWhere}
                AND weight >= {$this->threshold}";
    }
    else {
      $this->_aclWhere = ' AND c1.is_deleted = 0 AND c2.is_deleted = 0';
      if ($checkPermission) {
        list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause(['c1', 'c2']);
        $this->_aclWhere = $this->_aclWhere ? "AND {$this->_aclWhere}" : '';
      }
      $query = "SELECT dedupe.id1, dedupe.id2, dedupe.weight
                FROM dedupe JOIN civicrm_contact c1 ON dedupe.id1 = c1.id 
                            JOIN civicrm_contact c2 ON dedupe.id2 = c2.id {$this->_aclFrom}
                       LEFT JOIN civicrm_dedupe_exception exc ON dedupe.id1 = exc.contact_id1 AND dedupe.id2 = exc.contact_id2
                WHERE c1.contact_type = '{$this->contact_type}' AND 
                      c2.contact_type = '{$this->contact_type}' {$this->_aclWhere}
                      AND weight >= {$this->threshold} AND exc.contact_id1 IS NULL";
    }

    CRM_Utils_Hook::dupeQuery($this, 'threshold', $query);
    return $query;
  }

  /**
   * To find fields related to a rule group.
   *
   * @param array contains the rule group property to identify rule group
   *
   * @return (rule field => weight) array and threshold associated to rule group
   * @access public
   */
  static function dedupeRuleFieldsWeight($params) {
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
      if (strstr($ruleBao->rule_table, 'civicrm_value_')) {
        $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $ruleBao->rule_field, 'id', 'column_name');
        if (!empty($customFieldId)) {
          $ruleFields['custom_'.$customFieldId] = $ruleBao->rule_weight;
          continue;
        }
      }
      $ruleFields[$ruleBao->rule_field] = $ruleBao->rule_weight;
    }

    return [$ruleFields, $rgBao->threshold];
  }

  /**
   * Get an array of rule group id to rule group name
   * for all th groups for that contactType. If contactType
   * not specified, do it for all
   *
   * @param string $contactType Individual, Household or Organization
   *
   * @static
   *
   * @return array id => "nice name" of rule group
   */
  static function getByType($contactType = NULL) {
    $dao = new CRM_Dedupe_DAO_RuleGroup();

    if ($contactType) {
      $dao->contact_type = $contactType;
    }

    $dao->find();
    $result = [];
    while ($dao->fetch()) {
      if (!empty($dao->name)) {
        $name = "{$dao->name} - {$dao->level}";
      }
      else {
        $name = "{$dao->contact_type} - {$dao->level}";
      }
      $result[$dao->id] = $name;
    }
    return $result;
  }

  static function getDetailsByParams($params = []) {
    $ruleGroups = [];
    $dao = new CRM_Dedupe_DAO_RuleGroup();
    $dao->orderBy('contact_type,level,is_default DESC');
    foreach($params as $k => $v) {
      $dao->{$k} = $v;
    }
    $dao->find();
    while ($dao->fetch()) {
      $ruleGroups[$dao->id] = [];
      $fields = [];
      $fieldParams = ['id' => $dao->id];
      $fields = CRM_Dedupe_BAO_Rule::dedupeRuleFields($fieldParams);
      CRM_Core_DAO::storeValues($dao, $ruleGroups[$dao->id]);
      $ruleGroups[$dao->id]['fields'] = $fields;
    }
    return $ruleGroups;
  }
}

