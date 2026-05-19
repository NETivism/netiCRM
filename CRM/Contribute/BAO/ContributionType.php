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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Contribute_BAO_ContributionType extends CRM_Contribute_DAO_ContributionType {

  /**
   * static holder for the default LT
   */
  public static $_defaultContributionType = NULL;

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return CRM_Contribute_BAO_ContributionType|null CRM_Contribute_BAO_ContributionType object
   * @access public
   * @static
   */
  public static function retrieve(&$params, &$defaults) {
    $contributionType = new CRM_Contribute_DAO_ContributionType();
    $contributionType->copyValues($params);
    if ($contributionType->find(TRUE)) {
      CRM_Core_DAO::storeValues($contributionType, $defaults);
      return $contributionType;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return boolean             TRUE on success, FALSE otherwise
   * @static
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionType', $id, 'is_active', $is_active);
  }

  /**
   * function to add the contribution types
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return CRM_Contribute_BAO_ContributionType
   */
  public static function add(&$params, &$ids) {

    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_deductible'] = CRM_Utils_Array::value('is_deductible', $params, FALSE);
    $params['is_taxreceipt'] = CRM_Utils_Array::value('is_taxreceipt', $params, FALSE);
    $params['tax_rate'] = CRM_Utils_Array::value('tax_rate', $params, FALSE);

    // action is taken depending upon the mode
    $contributionType = new CRM_Contribute_DAO_ContributionType();
    $contributionType->copyValues($params);
    ;

    $contributionType->id = CRM_Utils_Array::value('contributionType', $ids);
    $contributionType->save();

    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('CRM_PC_CRM_Contribute_DAO_ContributionType*');
    return $contributionType;
  }

  /**
   * Function to delete contribution Types
   *
   * @param int $contributionTypeId
   *
   * @return void|string
   * @static
   */
  public static function del($contributionTypeId) {
    //checking if contribution type is present
    $check = FALSE;

    //check dependencies
    $dependancy = [
      ['Contribute', 'Contribution'],
      ['Contribute', 'ContributionPage'],
      ['Member', 'MembershipType'],
    ];
    foreach ($dependancy as $name) {
      $baoName = 'CRM_' . $name[0] . '_BAO_' . $name[1];
      $bao = new $baoName();
      $bao->contribution_type_id = $contributionTypeId;
      if ($bao->find(TRUE)) {
        $check = TRUE;
      }
    }

    if ($check) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts(
        'This contribution type cannot be deleted because it is being referenced by one or more of the following types of records: Contributions, Contribution Pages, or Membership Types. Consider disabling this type instead if you no longer want it used.'
      ));
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/contribute/contributionType', "reset=1&action=browse"));
    }

    //delete from contribution Type table

    $contributionType = new CRM_Contribute_DAO_ContributionType();
    $contributionType->id = $contributionTypeId;
    $contributionType->delete();
    $cache = &CRM_Utils_Cache::singleton();
    $cache->delete('CRM_PC_CRM_Contribute_DAO_ContributionType*');
  }

  /**
   * Get all contribution pages and events that use the given contribution type.
   *
   * @param int $contributionTypeId
   *
   * @return array Each entry has: id, title, is_active (bool), type ('contribute'|'event'), url
   */
  public static function getUsedPagesAndEvents($contributionTypeId) {
    $pages = [];

    $dao = CRM_Core_DAO::executeQuery(
      "SELECT id, title, is_active FROM civicrm_contribution_page WHERE contribution_type_id = %1 ORDER BY title",
      [1 => [$contributionTypeId, 'Integer']]
    );
    while ($dao->fetch()) {
      $pages[] = [
        'id' => $dao->id,
        'title' => $dao->title,
        'is_active' => (bool) $dao->is_active,
        'type' => 'contribute',
        'url' => CRM_Utils_System::url('civicrm/admin/contribute/amount', "reset=1&action=update&id={$dao->id}"),
      ];
    }

    $dao = CRM_Core_DAO::executeQuery(
      "SELECT id, title, is_active FROM civicrm_event WHERE contribution_type_id = %1 AND (is_template IS NULL OR is_template = 0) ORDER BY title",
      [1 => [$contributionTypeId, 'Integer']]
    );
    while ($dao->fetch()) {
      $pages[] = [
        'id' => $dao->id,
        'title' => $dao->title,
        'is_active' => (bool) $dao->is_active,
        'type' => 'event',
        'url' => CRM_Utils_System::url('civicrm/event/manage/fee', "reset=1&action=update&id={$dao->id}"),
      ];
    }

    return $pages;
  }

  /**
   * Check whether any contribution of this type has a non-empty receipt_id.
   *
   * Includes cancelled contributions because the receipt serial already exists
   * in financial records once a receipt_id is assigned.
   *
   * @param int $contributionTypeId
   * @return bool
   */
  public static function hasReceiptsIssued($contributionTypeId) {
    $count = CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_type_id = %1 AND receipt_id IS NOT NULL AND receipt_id != ''",
      [1 => [$contributionTypeId, 'Integer']]
    );
    return (int) $count > 0;
  }

  /**
   * Function to see if contribution type is deductible
   *
   * @param int $contributionTypeId contribution type id to retrieve
   * @param boolean $all default FALSE. TRUE will return type even type is not active.
   *
   * @return int|false when contribution type found. FALSE when not found.
   */
  public static function deductible($contributionTypeId, $all = FALSE) {
    $types = [];
    CRM_Core_PseudoConstant::populate($types, 'CRM_Contribute_DAO_ContributionType', $all, 'is_deductible');
    if (isset($types[$contributionTypeId])) {
      return $types[$contributionTypeId];
    }
    return FALSE;
  }
}
