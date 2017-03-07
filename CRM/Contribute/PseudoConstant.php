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

require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Core/PseudoConstant.php';

/**
 * This class holds all the Pseudo constants that are specific to Contributions. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Contribute_PseudoConstant extends CRM_Core_PseudoConstant {
  /**
   * financial types
   * @var array
   * @static
   */
  private static $financialType;

  /**
   * contribution types
   * @var array
   * @static
   */
  private static $contributionType;
  private static $deductibleType;
  private static $taxType;

  /**
   * contribution pages
   * @var array
   * @static
   */
  private static $contributionPage;

  /**
   * payment instruments
   *
   * @var array
   * @static
   */
  private static $paymentInstrument;

  /**
   * credit card
   *
   * @var array
   * @static
   */
  private static $creditCard;

  /**
   * contribution status
   *
   * @var array
   * @static
   */
  private static $contributionStatus;

  /**
   * pcp status
   *
   * @var array
   * @static
   */
  private static $pcpStatus;

  /**
   * Personal campaign pages
   * @var array
   * @static
   */
  private static $pcPage;

  /**
   * Get all the financial types
   *
   * @access public
   *
   * @param null $id
   *
   * @return array - array reference of all financial types if any
   * @static
   */
  public static function &financialType($id = NULL) {
    return self::contributionType($id);
  }

  /**
   * Get all the contribution types
   *
   * @access public
   *
   * @return array - array reference of all contribution types if any
   * @static
   */
  public static function &contributionType($id = NULL, $receiptType = FALSE, $receiptTypeLabel = FALSE) {
    if (!self::$contributionType) {
      CRM_Core_PseudoConstant::populate(self::$contributionType, 'CRM_Contribute_DAO_ContributionType');
      CRM_Core_PseudoConstant::populate(self::$deductibleType, 'CRM_Contribute_DAO_ContributionType', FALSE, 'is_deductible', 'is_active', 'is_deductible=1');
      CRM_Core_PseudoConstant::populate(self::$taxType, 'CRM_Contribute_DAO_ContributionType', FALSE, 'is_taxreceipt', 'is_active', 'is_taxreceipt=1');
    }
    $types = array();
    if ($receiptType == 'is_deductible') {
      $types = array_intersect_key(self::$contributionType, self::$deductibleType);
    }
    elseif($receiptType == 'is_taxreceipt'){
      $types = array_intersect_key(self::$contributionType, self::$taxType);
    }
    else {
      $types = self::$contributionType;
    }

    if ($receiptTypeLabel) {
      foreach ($types as $k => $v) {
        if(!empty(self::$deductibleType[$k])) {
          $types[$k] .= ' (' . ts('Deductible') . ')';
        }
        elseif(!empty(self::$taxType[$k])) {
          $types[$k] .= ' (' . ts('Tax Receipt') . ')';
        }
      }
    }
    if ($id) {
      $result = CRM_Utils_Array::value($id, $types);
      return $result;
    }
    return $types;
  }

  /**
   * Get all the contribution pages
   *
   * @access public
   *
   * @return array - array reference of all contribution pages if any
   * @static
   */
  public static function &contributionPage($id = NULL, $isActive = FALSE) {
    if (!self::$contributionPage) {
      CRM_Core_PseudoConstant::populate(self::$contributionPage,
        'CRM_Contribute_DAO_ContributionPage',
        $isActive, 'title'
      );
    }
    if ($id) {
      $pageTitle = CRM_Utils_Array::value($id, self::$contributionPage);
      return $pageTitle;
    }
    return self::$contributionPage;
  }

  /**
   * Get all the payment instruments
   *
   * @access public
   *
   * @return array - array reference of all payment instruments if any
   * @static
   */
  public static function &paymentInstrument($columnName = 'label') {
    if (!isset(self::$paymentInstrument[$columnName])) {
      self::$paymentInstrument[$columnName] = CRM_Core_OptionGroup::values('payment_instrument',
        FALSE, FALSE, FALSE, NULL, $columnName
      );
    }

    return self::$paymentInstrument[$columnName];
  }

  /**
   * Get all the valid accepted credit cards
   *
   * @access public
   *
   * @return array - array reference of all payment instruments if any
   * @static
   */
  public static function &creditCard() {
    $acceptCreditCard = array();
    $creditCard = CRM_Core_OptionGroup::values('accept_creditcard');

    if (!$creditCard) {
      $creditCard = array();
    }
    foreach ($creditCard as $key => $value) {
      $acceptCreditCard[$value] = $value;
    }
    return $acceptCreditCard;
  }

  /**
   * Get all premiums
   *
   * @access public
   *
   * @return array - array of all Premiums if any
   * @static
   */
  public static function products($pageID = NULL) {
    $products = array();
    require_once 'CRM/Contribute/DAO/Product.php';
    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->orderBy('id');
    $dao->find();

    while ($dao->fetch()) {
      $products[$dao->id] = $dao->name;
    }
    if ($pageID) {
      require_once 'CRM/Contribute/DAO/Premium.php';
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $productID = array();

      require_once 'CRM/Contribute/DAO/PremiumsProduct.php';
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->premiums_id = $premiumID;
      $dao->find();
      while ($dao->fetch()) {
        $productID[$dao->product_id] = $dao->product_id;
      }

      $tempProduct = array();
      foreach ($products as $key => $value) {
        if (!array_key_exists($key, $productID)) {
          $tempProduct[$key] = $value;
        }
      }

      return $tempProduct;
    }

    return $products;
  }

  /**
   * Get all the contribution statuses
   *
   * @access public
   *
   * @return array - array reference of all contribution statuses
   * @static
   */
  public static function &contributionStatus($id = NULL, $columnName = 'label') {
    $cacheKey = $columnName;
    if (!isset(self::$contributionStatus[$cacheKey])) {
      self::$contributionStatus[$cacheKey] = CRM_Core_OptionGroup::values('contribution_status',
        FALSE, FALSE, FALSE, NULL, $columnName
      );
    }
    $result = self::$contributionStatus[$cacheKey];
    if ($id) {
      $result = CRM_Utils_Array::value($id, $result);
    }

    return $result;
  }

  /**
   * Get all the pcp status
   *
   * @access public
   *
   * @return array - array reference of all pcp status
   * @static
   */
  public static function &pcpStatus() {
    self::$pcpStatus = array();
    if (!self::$pcpStatus) {
      self::$pcpStatus = CRM_Core_OptionGroup::values("pcp_status", FALSE, FALSE, FALSE, NULL, 'label');
    }
    return self::$pcpStatus;
  }

  /**
   * Get all the Personal campaign pages
   *
   * @access public
   *
   * @return array - array reference of all pcp if any
   * @static
   */
  public static function &pcPage($id = NULL) {
    if (!self::$pcPage) {
      CRM_Core_PseudoConstant::populate(self::$pcPage,
        'CRM_Contribute_DAO_PCP',
        FALSE, 'title'
      );
    }
    if ($id) {
      return CRM_Utils_Array::value($id, self::$pcPage);
    }
    return self::$pcPage;
  }
}

