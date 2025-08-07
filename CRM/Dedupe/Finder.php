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
class CRM_Dedupe_Finder {

  /**
   * Return a contact_id-keyed array of arrays of possible dupes
   * (of the key contact_id) - limited to dupes of $cids if provided.
   *
   * @param int   $rgid  rule group id
   * @param array $cids  contact ids to limit the search to
   *
   * @return array  array of (cid1, cid2, weight) dupe triples
   */
  static function dupes($rgid, $cids = []) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->id = $rgid;
    $rgBao->contactIds = $cids;
    if (!$rgBao->find(TRUE)) {
      CRM_Core_Error::fatal("$level rule for $ctype does not exist");
    }

    $rgBao->fillTable();
    $dao = new CRM_Core_DAO();
    $dao->query($rgBao->thresholdQuery());
    $dupes = [];
    while ($dao->fetch()) {
      $dupes[] = [$dao->id1, $dao->id2, $dao->weight];
    }
    $dao->query($rgBao->tableDropQuery());

    return $dupes;
  }

  /**
   * Return an array of possible dupes, based on the provided array of
   * params, using the default rule group for the given contact type and
   * level.
   *
   * check_permission is a boolean flag to indicate if permission should be considered.
   * default is to always check permissioning but public pages for example might not want
   * permission to be checked for anonymous users. Refer CRM-6211. We might be beaking
   * Multi-Site dedupe for public pages.
   *
   * @param array  $params  array of params of the form $params[$table][$field] == $value
   * @param string $ctype   contact type to match against
   * @param string $level   dedupe rule group level ('Fuzzy' or 'Strict')
   * @param array  $except  array of contacts that shouldn't be considered dupes
   * @param int    $ruleGroupID the id of the dedupe rule we should be using
   *
   * @return array  matching contact ids
   */
  static function dupesByParams($params,
    $ctype,
    $level = 'Strict',
    $except = [],
    $ruleGroupID = NULL
  ) {
    // If $params is empty there is zero reason to proceed.
    if (!$params) {
      return [];
    }

    $foundByID = FALSE;
    if ($ruleGroupID) {
      $rgBao = new CRM_Dedupe_BAO_RuleGroup();
      $rgBao->id = $ruleGroupID;
      $rgBao->contact_type = $ctype;
      if ($rgBao->find(TRUE)) {
        $foundByID = TRUE;
      }
    }

    if (!$foundByID) {
      $rgBao = new CRM_Dedupe_BAO_RuleGroup();
      $rgBao->contact_type = $ctype;
      $rgBao->level = $level;
      $rgBao->is_default = 1;
      if (!$rgBao->find(TRUE)) {
        CRM_Core_Error::fatal("$level rule for $ctype does not exist");
      }
    }
    $params['check_permission'] = CRM_Utils_Array::value('check_permission', $params, TRUE);

    $rgBao->params = $params;
    $rgBao->fillTable();
    $dao = new CRM_Core_DAO();
    $dao->query($rgBao->thresholdQuery($params['check_permission']));
    $dupes = [];
    while ($dao->fetch()) {
      if (isset($dao->id) && $dao->id) {
        $dupes[] = $dao->id;
      }
    }
    $dao->query($rgBao->tableDropQuery());

    return array_diff($dupes, $except);
  }

  /**
   * Return an array of possible dupes, based on the provided array of
   * params and rules.
   *
   * This function should be used on internal only, check_permission default is FALSE
   *
   * @param array  $params  array of params of the form $params[$table][$field] == $value
   * @param string $ctype   contact type to match against
   * @param string $level   dedupe rule group level ('Fuzzy' or 'Strict')
   * @param array  $except  array of contacts that shouldn't be considered dupes
   * @param array  $rules   rules which contain table,field,weight per array element, example:
   *   ```php
   *     [
   *       ['table'=>'civicrm_contact', 'field'=>'external_identifier', 'weight'=>10],
   *       ['table'=>'civicrm_email', 'field'=>'email', 'weight'=>10],
   *     ]
   *   ```
   * @param int    $threshold threshold that meet above rules
   * @return array  matching contact ids
   */
  static function dupesByRules($params, $ctype, $level = 'Strict', $except = [], $rules = [],     $threshold = 10) {
    // If $params is empty there is zero reason to proceed.
    if (!$params) {
      return [];
    }
    if (empty($rules)) {
      return [];
    }

    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->contact_type = $ctype;
    $rgBao->level = $level;
    $rgBao->threshold = $threshold;
    $rgBao->rules = $rules;
    $params['check_permission'] = CRM_Utils_Array::value('check_permission', $params, FALSE);
    $rgBao->params = $params;
    $rgBao->fillTable();
    $dao = new CRM_Core_DAO();
    $dao->query($rgBao->thresholdQuery($params['check_permission']));
    $dupes = [];
    while ($dao->fetch()) {
      if (isset($dao->id) && $dao->id) {
        $dupes[] = $dao->id;
      }
    }
    $dao->query($rgBao->tableDropQuery());

    return array_diff($dupes, $except);
  }

  /**
   * Return a contact_id-keyed array of arrays of possible dupes in the given group.
   *
   * @param int $rgid  rule group id
   * @param int $gid   contact group id (currently, works only with non-smart groups)
   *
   * @return array  array of (cid1, cid2, weight) dupe triples
   */
  static function dupesInGroup($rgid, $gid) {
    $cids = array_keys(CRM_Contact_BAO_Group::getMember($gid));
    return self::dupes($rgid, $cids);
  }

  /**
   * Return dupes of a given contact, using the default rule group (of a provided level).
   *
   * @param int    $cid    contact id of the given contact
   * @param string $level  dedupe rule group level ('Fuzzy' or 'Strict')
   * @param string $ctype  contact type of the given contact
   *
   * @return array  array of dupe contact_ids
   */
  function dupesOfContact($cid, $level = 'Strict', $ctype = NULL) {
    // if not provided, fetch the contact type from the database
    if (!$ctype) {
      $dao = new CRM_Contact_DAO_Contact();
      $dao->id = $cid;
      if (!$dao->find(TRUE)) {
        CRM_Core_Error::fatal("contact id of $cid does not exist");
      }
      $ctype = $dao->contact_type;
    }
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->level = $level;
    $rgBao->contact_type = $ctype;
    $rgBao->is_default = 1;
    if (!$rgBao->find(TRUE)) {
      CRM_Core_Error::fatal("$level rule for $ctype does not exist");
    }
    $dupes = self::dupes($rgBao->id, [$cid]);

    // get the dupes for this cid
    $result = [];
    foreach ($dupes as $dupe) {
      if ($dupe[0] == $cid) {
        $result[] = $dupe[1];
      }
      elseif ($dupe[1] == $cid) {
        $result[] = $dupe[0];
      }
    }
    return $result;
  }

  /**
   * A hackish function needed to massage CRM_Contact_Form_$ctype::formRule()
   * object into a valid $params array for dedupe
   *
   * @param array $fields  contact structure from formRule(), structure should be like this:
   *   [
   *     'contact_type' => 'Individual',
   *     'last_name' => 'abc',
   *     'first_name' => 'def',
   *     'email' => [
   *       1 => ['email' => 'aaa@bbb.ccc', 'location_type_id' => 1],
   *     ],
   *   ]
   * @param string $ctype  contact type of the given contact
   *
   * @return array  valid $params array for dedupe
   */
  static function formatParams($fields, $ctype) {
    $flat = [];
    CRM_Utils_Array::flatten($fields, $flat);

    // handle {birth,deceased}_date
    foreach (['birth_date', 'deceased_date'] as $date) {
      if (CRM_Utils_Array::value($date, $fields)) {
        $flat[$date] = $fields[$date];
        if (is_array($flat[$date])) {
          $flat[$date] = CRM_Utils_Date::format($flat[$date]);
        }
        $flat[$date] = CRM_Utils_Date::processDate($flat[$date]);
      }
    }

    if (CRM_Utils_Array::value('contact_source', $flat)) {
      $flat['source'] = $flat['contact_source'];
      unset($flat['contact_source']);
    }

    // handle preferred_communication_method
    if (CRM_Utils_Array::arrayKeyExists('preferred_communication_method', $fields)) {
      $methods = array_intersect($fields['preferred_communication_method'], ['1']);
      $methods = array_keys($methods);
      sort($methods);
      if ($methods) {
        $flat['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $methods) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }

    // handle custom data

    $tree = &CRM_Core_BAO_CustomGroup::getTree($ctype, CRM_Core_DAO::$_nullObject, NULL, -1);
    CRM_Core_BAO_CustomGroup::postProcess($tree, $fields, TRUE);
    foreach ($tree as $key => $cg) {
      if (!is_int($key)) {
        continue;
      }
      foreach ($cg['fields'] as $cf) {
        $flat[$cf['column_name']] = CRM_Utils_Array::value('data', $cf['customValue']);
      }
    }

    // if the key is dotted, keep just the last part of it
    foreach ($flat as $key => $value) {
      if (substr_count($key, '.')) {
        $last = array_pop(explode('.', $key));
        // make sure the first occurence is kept, not the last
        if (!isset($flat[$last])) {
          $flat[$last] = $value;
        }
        unset($flat[$key]);
      }
    }

    // drop the -digit (and -Primary, for CRM-3902) postfixes (so event registration's $flat['email-5'] becomes $flat['email'])
    // FIXME: CRM-5026 should be fixed here; the below clobbers all address info; we should split off address fields and match
    // the -digit to civicrm_address.location_type_id and -Primary to civicrm_address.is_primary
    foreach ($flat as $key => $value) {
      $matches = [];
      if (preg_match('/([^-]*)-(\d+|Primary)(-\d+)*$/', $key, $matches)) {
        if ($matches[2] == 'Primary') {
          $matches[2] = '0';
        }
        $flatKey = $matches[1];

        // collapsed all related value to array
        if (isset($flat[$flatKey]) && !is_array($flat[$flatKey])) {
          unset($flat[$flatKey]);
        }
        $hasResult = is_array($flat[$flatKey]) ? array_search($value, $flat[$flatKey]) : NULL;
        if(!$hasResult) {
          $flat[$flatKey][] = $value;
        }
        unset($flat[$key]);
      }
    }

    $params = [];
    $supportedFields = CRM_Dedupe_BAO_RuleGroup::supportedFields($ctype);
    if (is_array($supportedFields)) {
      foreach ($supportedFields as $table => $fields) {
        if ($table == 'civicrm_address') {
          // for matching on civicrm_address fields, we also need the location_type_id
          $fields['location_type_id'] = '';
          // FIXME: we also need to do some hacking for id and name fields, see CRM-3902’s comments
          $fixes = ['address_name' => 'name', 'country' => 'country_id',
            'state_province' => 'state_province_id', 'county' => 'county_id',
          ];
          foreach ($fixes as $orig => $target) {
            if (CRM_Utils_Array::value($orig, $flat)) {
              $params[$table][$target] = $flat[$orig];
            }
          }
        }
        foreach ($fields as $field => $title) {
          if (CRM_Utils_Array::value($field, $flat)) {
            if (is_array($flat[$field])) {
              foreach($flat[$field] as $val) {
                $params[$table][$field][] = $val;
              }
            }
            else {
              $params[$table][$field] = $flat[$field];
            }
          }
        }
      }
    }

    // #21211, we should prepare sort_name, display_name before saving to db
    // to support sort_name / display_name as dedupe rule
    if (!empty($params['civicrm_contact']) && $ctype == 'Individual') {
      $contact = new stdClass(); // null class for pass into
      $formatParams = ['contact_type' => 'Individual'];
      foreach($params['civicrm_contact'] as $field => $value) {
        $contact->$field = $value;
        $formatParams[$field] = $value;
      }
      foreach($params['civicrm_email'] as $field => $value) {
        $contact->$field = $value;
        $formatParams[$field][] = ['email' => $value];
      }
      CRM_Contact_BAO_Individual::format($formatParams, $contact);
      $params['civicrm_contact']['display_name'] = $contact->display_name;
      $params['civicrm_contact']['sort_name'] = $contact->sort_name;
    }
    return $params;
  }
}

