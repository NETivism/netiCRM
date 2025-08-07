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
 * Business object for Saved searches
 *
 */
class CRM_Contact_BAO_SavedSearch extends CRM_Contact_DAO_SavedSearch {

  /**
   * class constructor
   *
   * @return object CRM_Contact_BAO_SavedSearch
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * query the db for all saved searches.
   *
   * @return array $aSavedSearch - contains the search name as value and and id as key
   *
   * @access public
   */
  function getAll() {
    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->selectAdd();
    $savedSearch->selectAdd('id, name');
    $savedSearch->find();
    while ($savedSearch->fetch()) {
      $aSavedSearch[$savedSearch->id] = $savedSearch->name;
    }
    return $aSavedSearch;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects.
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contact_BAO_SavedSearch
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->copyValues($params);
    if ($savedSearch->find(TRUE)) {
      CRM_Core_DAO::storeValues($savedSearch, $defaults);
      return $savedSearch;
    }
    return NULL;
  }

  /**
   * given an id, extract the formValues of the saved search
   *
   * @param int $id the id of the saved search
   *
   * @return array the values of the posted saved search
   * @access public
   * @static
   */
  static function &getFormValues($id) {
    $fv = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'form_values');
    $result = NULL;
    if ($fv) {
      // make sure u unserialize - since it's stored in serialized form
      $result = unserialize($fv);
    }
    return $result;
  }

  static function getSearchParams($id) {
    $fv = &self::getFormValues($id);
    //check if the saved seach has mapping id
    if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'mapping_id')) {

      return CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    elseif (CRM_Utils_Array::value('customSearchID', $fv)) {
      return $fv;
    }
    else {

      return CRM_Contact_BAO_Query::convertFormValues($fv);
    }
  }

  /**
   * get the where clause for a saved search
   *
   * @param int $id saved search id
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the where clause for this saved search
   * @access public
   * @static
   */
  static function whereClause($id, &$tables, &$whereTables) {
    $params = &self::getSearchParams($id);
    if ($params) {
      return CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
    }
    return NULL;
  }

  static function contactIDsSQL($id) {
    $params = &self::getSearchParams($id);
    if ($params) {
      if (CRM_Utils_Array::value('customSearchID', $params)) {

        return CRM_Contact_BAO_SearchCustom::contactIDSQL(NULL, $id);
      }
    }
    $tables = $whereTables = [];
    $where = CRM_Contact_BAO_SavedSearch::whereClause($id, $tables, $whereTables);
    if (!$where) {
      $where = '( 1 )';
    }
    $from = CRM_Contact_BAO_Query::getFromClause($whereTables);
    return "
SELECT contact_a.id
$from
WHERE  $where";
  }

  static function fromWhereEmail($id) {
    $params = &self::getSearchParams($id);

    if ($params) {
      if (CRM_Utils_Array::value('customSearchID', $params)) {

        return CRM_Contact_BAO_SearchCustom::fromWhereEmail(NULL, $id);
      }
      else {
        $tables = $whereTables = ['civicrm_contact' => 1, 'civicrm_email' => 1];
        $where = CRM_Contact_BAO_SavedSearch::whereClause($id, $tables, $whereTables);
        $from = CRM_Contact_BAO_Query::getFromClause($whereTables);
        return [$from, $where];
      }
    }
    else {
      CRM_Core_Error::fatal('No contactID clause');
    }
  }

  /**
   * given a saved search compute the clause and the tables
   * and store it for future use
   */
  function buildClause() {
    $fv = unserialize($this->form_values);

    if ($this->mapping_id) {

      $params = CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    else {

      $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    }

    if (!empty($params)) {
      $tables = $whereTables = [];
      $this->where_clause = CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      if (!empty($tables)) {
        $this->select_tables = serialize($tables);
      }
      if (!empty($whereTables)) {
        $this->where_tables = serialize($whereTables);
      }
    }

    return;
  }

  function save() {
    // first build the computed fields
    $this->buildClause();

    parent::save();
  }

  /**
   * given an id, get the name of the saved search
   *
   * @param int $id the id of the saved search
   *
   * @return string the name of the saved search
   * @access public
   * @static
   */
  static function getName($id, $value = 'name') {

    $group = new CRM_Contact_DAO_Group();
    $group->saved_search_id = $id;
    if ($group->find(TRUE)) {
      return $group->$value;
    }
    return NULL;
  }
}

