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
 * Business access object for managing premium combinations
 */
class CRM_Contribute_BAO_PremiumsCombination extends CRM_Contribute_DAO_PremiumsCombination {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return CRM_Contribute_BAO_PremiumsCombination object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $combination = new CRM_Contribute_DAO_PremiumsCombination();
    $combination->copyValues($params);
    if ($combination->find(TRUE)) {
      CRM_Core_DAO::storeValues($combination, $defaults);
      return $combination;
    }
    return NULL;
  }

  /**
   * Function to add premium combination
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['created_date'] = date('YmdHis');
    $params['modified_date'] = date('YmdHis');

    $combination = new CRM_Contribute_DAO_PremiumsCombination();

    if ($combinationId = CRM_Utils_Array::value('combination', $ids)) {
      $combination->id = $combinationId;
      $combination->modified_date = date('YmdHis');
    }

    $combination->copyValues($params);
    $combination->save();
    return $combination;
  }

  /**
   * Function to create premium combination
   *
   * @param array $params associated array of fields
   *
   * @return object|null object on success, null otherwise
   * @access public
   * @static
   */
  static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $combination = self::add($params, CRM_Utils_Array::value('id', $params));

    if (is_a($combination, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $combination;
    }

    $transaction->commit();
    return $combination;
  }

  /**
   * Function to delete premium combination
   *
   * @param int $id premium combination id
   *
   * @access public
   * @static
   *
   */
  static function del($id) {
    $productDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
    $productDao->combination_id = $id;
    $productDao->delete();

    $combination = new CRM_Contribute_DAO_PremiumsCombination();
    $combination->id = $id;
    return $combination->delete();
  }

  /**
   * Function to set default values for form
   *
   * @param array $defaults associated array of default values
   * @param int   $id       premium combination id
   *
   * @access public
   * @static
   */
  static function setDefaultValues(&$defaults, $id = NULL) {
    if (!$id) {
      return $defaults;
    }

    $combination = new CRM_Contribute_DAO_PremiumsCombination();
    $combination->id = $id;
    if ($combination->find(TRUE)) {
      $defaults = (array) $combination;
    }

    return $defaults;
  }

  /**
   * Get combinations for a specific premium
   *
   * @param int $premiumsId
   * @param boolean $onlyActive
   *
   * @return array
   * @access public
   * @static
   */
  static function getCombinations($premiumsId, $onlyActive = TRUE, $unassignedOnly = TRUE) {
    $combinations = [];
    $combination = new CRM_Contribute_DAO_PremiumsCombination();
    
    if ($unassignedOnly) {
      // Get combinations that are not assigned to any page (premiums_id is NULL)
      $combination->premiums_id = NULL;
    } else {
      // Get combinations assigned to specific premiums_id
      $combination->premiums_id = $premiumsId;
    }

    if ($onlyActive) {
      $combination->is_active = 1;
    }
    $combination->orderBy('weight, combination_name');
    $combination->find();

    while ($combination->fetch()) {
      $combinations[$combination->id] = $combination->combination_name;
    }

    return $combinations;
  }

  /**
   * Get products in a combination
   *
   * @param int $combinationId
   *
   * @return array
   * @access public
   * @static
   */
  static function getCombinationProducts($combinationId) {
    $products = [];

    $dao = CRM_Core_DAO::executeQuery("
      SELECT cp.product_id, cp.quantity, p.name, p.sku, p.price, p.is_active
      FROM civicrm_premiums_combination_products cp
      LEFT JOIN civicrm_product p ON cp.product_id = p.id
      WHERE cp.combination_id = %1 And p.is_active = 1
    ", [
      1 => [$combinationId, 'Integer']
    ]);

    while ($dao->fetch()) {
      $products[] = [
        'product_id' => $dao->product_id,
        'quantity' => $dao->quantity,
        'name' => $dao->name,
        'sku' => $dao->sku,
        'price' => $dao->price,
      ];
    }

    return $products;
  }
}