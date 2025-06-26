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
   * currency
   *
   * @var array
   * @static
   */
  private static $currency;

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
   * Taiwan ACH bank code and name
   * @var array
   * @static
   */
  private static $taiwanACH;
  
  /**
   * Taiwan ACH return failed code and reason
   * @var array
   * @static
   */
  private static $taiwanACHFailedReason;

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
   * @param null $id - specify id for return
   * @param false $receiptType - limit option based on receipt type
   * @param false $receiptTypeLabel - display receipt type label
   *
   * @return array - array reference of all contribution types if any
   * @static
   */
  public static function &contributionType($id = NULL, $receiptType = FALSE, $receiptTypeLabel = FALSE) {
    if (!self::$contributionType) {
      CRM_Core_PseudoConstant::populate(self::$contributionType, 'CRM_Contribute_DAO_ContributionType');
      CRM_Core_PseudoConstant::populate(self::$deductibleType, 'CRM_Contribute_DAO_ContributionType', FALSE, 'is_deductible', 'is_active', 'is_deductible=1');
      CRM_Core_PseudoConstant::populate(self::$taxType, 'CRM_Contribute_DAO_ContributionType', FALSE, 'is_taxreceipt', 'is_active', 'is_taxreceipt <> 0');
    }
    $types = [];
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
    if ($isActive) {
      CRM_Core_PseudoConstant::populate(self::$contributionPage, 'CRM_Contribute_DAO_ContributionPage', FALSE, 'title');
    }
    else {
      CRM_Core_PseudoConstant::populate(self::$contributionPage, 'CRM_Contribute_DAO_ContributionPage', TRUE, 'title');
    }
    ksort(self::$contributionPage);
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
    $acceptCreditCard = [];
    $creditCard = CRM_Core_OptionGroup::values('accept_creditcard');

    if (!$creditCard) {
      $creditCard = [];
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
    $products = [];

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->orderBy('id');
    $dao->find();

    while ($dao->fetch()) {
      $products[$dao->id] = $dao->name;
    }
    if ($pageID) {

      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $productID = [];


      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->premiums_id = $premiumID;
      $dao->find();
      while ($dao->fetch()) {
        $productID[$dao->product_id] = $dao->product_id;
      }

      $tempProduct = [];
      foreach ($products as $key => $value) {
        if (!CRM_Utils_Array::arrayKeyExists($key, $productID)) {
          $tempProduct[$key] = $value;
        }
      }

      return $tempProduct;
    }

    return $products;
  }

  public static function &currency() {
    if (!isset(self::$currency)) {
      self::$currency = CRM_Core_OptionGroup::values('currencies_enabled', FALSE, FALSE, FALSE, NULL);
    }
    return self::$currency;
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
  public static function &pcpStatus($columnName = 'label') {
    self::$pcpStatus = [];
    if (!self::$pcpStatus) {
      self::$pcpStatus = CRM_Core_OptionGroup::values("pcp_status", FALSE, FALSE, FALSE, NULL, $columnName);
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
      self::$pcPage = [];
      $dao = CRM_Core_DAO::executeQuery("SELECT pcp.id, pcp.title, pcp.contact_id, c.sort_name, pcp.contact_id, c.external_identifier FROM civicrm_pcp pcp INNER JOIN civicrm_contact c ON c.id = pcp.contact_id");
      while($dao->fetch()){
        if ($dao->external_identifier) {
          self::$pcPage[$dao->id] = "$dao->title by $dao->sort_name ($dao->contact_id - $dao->external_identifier)";
        }
        else {
          self::$pcPage[$dao->id] = "$dao->title by $dao->sort_name ($dao->contact_id)";
        }
      }
    }
    if ($id) {
      return CRM_Utils_Array::value($id, self::$pcPage);
    }
    return self::$pcPage;
  }

  /**
   * Get all Taiwan ACH Bank Code
   *
   * @access public
   *
   * @return array - array reference of all pcp if any
   * @static
   */
  public static function &taiwanACH($code = '') {
    global $civicrm_root;
    if (!self::$taiwanACH) {
      $fp = fopen($civicrm_root.'xml/templates/taiwan_ach.tpl', 'r');
      $parent = '';
      while(($data = fgetcsv($fp, 100)) !== FALSE) {
        if (empty($data[1])) {
          $parent = $data[0];
          continue;
        }
        else {
          self::$taiwanACH[$parent][$data[0]] = $data[0].' '.$data[1];
        }
      }
      fclose($fp); 
    }
    if (!empty($code)) {
      foreach (self::$taiwanACH as $banks) {
        if (!empty($banks[$code])) {
          return $banks[$code];
        }
      }
    }
    return self::$taiwanACH;
  }

  public static function taiwanACHStampVerification() {
    return [
      0 => ts('Pending'),
      1 => ts('Completed'),
      2 => ts('Failed'),
    ];
  }

  /**
   * Get all Taiwan ACH failed code and reason
   *
   * @access public
   *
   * @return array - array reference of all pcp if any
   * @static
   */
  public static function &taiwanACHFailedReason() {
    global $civicrm_root;
    if (!self::$taiwanACHFailedReason) {
      $fp = fopen($civicrm_root.'xml/templates/taiwan_ach_failed_reason.tpl', 'r');
      $parent = '';
      while(($data = fgetcsv($fp, 100)) !== FALSE) {
        if (empty($data[1])) {
          $parent = explode('_', $data[0]);
          $instrumentName = $parent[0];
          $processType = $parent[1];
          $column = $parent[2];
          continue;
        }
        else {
          self::$taiwanACHFailedReason[$instrumentName][$processType][$column][$data[0]] = $data[1];
        }
      }
      fclose($fp);
    }
    return self::$taiwanACHFailedReason;
  }
}

