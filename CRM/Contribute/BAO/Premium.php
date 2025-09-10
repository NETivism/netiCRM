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


class CRM_Contribute_BAO_Premium extends CRM_Contribute_DAO_Premium {

  /**
   * class constructor
   */
  function __construct() {
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
   * @return object CRM_Contribute_BAO_ManagePremium object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $premium = new CRM_Contribute_DAO_Product();
    $premium->copyValues($params);
    if ($premium->find(TRUE)) {
      CRM_Core_DAO::storeValues($premium, $defaults);
      return $premium;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Premium', $id, 'premiums_active ', $is_active);
  }

  /**
   * Function to delete contribution Types
   *
   * @param int $contributionTypeId
   * @static
   */

  static function del($premiumID) {
    //check dependencies

    //delete from contribution Type table

    $premium = new CRM_Contribute_DAO_Premium();
    $premium->id = $premiumID;
    $premium->delete();
  }

  /**
   * Function to build Premium Block im Contribution Pages
   *
   * @param int $pageId
   * @static
   */
  static function buildPremiumBlock(&$form, $pageID, $formItems = FALSE, $selectedProductID = NULL, $selectedOption = NULL) {


    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    $dao->premiums_active = 1;

    if ($dao->find(TRUE)) {
      $premiumID = $dao->id;
      $premiumBlock = [];
      CRM_Core_DAO::storeValues($dao, $premiumBlock);

      // Check if this premium uses combinations
      if (!empty($premiumBlock['premiums_combination']) && $premiumBlock['premiums_combination'] == 1) {
        // Build combination block
        self::buildCombinationBlock($form, $premiumID, $formItems, $selectedProductID, $selectedOption);
      }
      else {
        // Build regular premium block
        self::buildRegularPremiumBlock($form, $premiumID, $formItems, $selectedProductID, $selectedOption, $premiumBlock);
      }
    }
  }

  /**
   * Build combination premium block
   */
  static function buildCombinationBlock(&$form, $premiumID, $formItems, $selectedProductID = NULL, $selectedOption = NULL) {
    $dao = new CRM_Contribute_DAO_Premium();
    $dao->id = $premiumID;
    if ($dao->find(TRUE)) {
      $premiumBlock = [];
      CRM_Core_DAO::storeValues($dao, $premiumBlock);

      // Get combinations
      $combinationDAO = new CRM_Contribute_DAO_PremiumsCombination();
      $combinationDAO->premiums_id = $premiumID;
      $combinationDAO->is_active = 1;
      $combinationDAO->orderBy('weight');
      $combinationDAO->find();

      $combinations = [];
      $radio = [];
      while ($combinationDAO->fetch()) {
        $combinationData = [];
        CRM_Core_DAO::storeValues($combinationDAO, $combinationData);
        // Get products in this combination
        $combinationData['products'] = CRM_Contribute_BAO_PremiumsCombination::getCombinationProducts($combinationDAO->id);
        // Apply selection filter similar to regular premium block
        if ($selectedProductID != NULL) {
          if ($selectedProductID == $combinationDAO->id) {
            if ($selectedOption) {
              $combinationData['selected_option'] = ts('Selected Option') . ': ' . $selectedOption;
            }
            $combinations[$combinationDAO->id] = $combinationData;
          }
        }
        else {
          $combinations[$combinationDAO->id] = $combinationData;
        }

        if ($formItems) {
          $combinationAttr = [];
          $combinationAttr['data-min-contribution'] = $combinationDAO->min_contribution;
          $combinationAttr['data-min-contribution-recur'] = $combinationDAO->min_contribution_recur;
          $combinationAttr['data-calculate-mode'] = $combinationDAO->calculate_mode;
          $combinationAttr['data-installments'] = $combinationDAO->installments;
          $radio[$combinationDAO->id] = $form->createElement('radio', NULL, NULL, ' ', $combinationDAO->id, $combinationAttr);
        }
      }

      if (count($combinations)) {
        $form->assign('showRadioPremium', $formItems);
        if ($formItems) {
          $radio[''] = $form->createElement('radio', NULL, NULL, ' ', 'no_thanks', NULL);
          $form->assign('no_thanks_label', ts('No thank you'));
          $form->addGroup($radio, 'selectProduct', NULL);
          $form->addRule('selectProduct', ts('%1 is a required field.', [1 => ts('Premium')]), 'required');
          $default = ['selectProduct' => 'no_thanks'];
          $form->setDefaults($default);
          // Add hidden field to indicate this is combination
          $form->addElement('hidden', 'premium_type', 'combination');
        }
        $form->assign('combinations', $combinations);
        $form->assign('premiumBlock', $premiumBlock);
        $form->assign('useCombinations', TRUE);
      }
    }
  }

  /**
   * Build regular premium block
   */
  static function buildRegularPremiumBlock(&$form, $premiumID, $formItems, $selectedProductID, $selectedOption, $premiumBlock) {
    $dao = new CRM_Contribute_DAO_PremiumsProduct();
    $dao->premiums_id = $premiumID;
    $dao->orderBy('weight');
    $dao->find();

    $products = [];
    $radio = [];
    while ($dao->fetch()) {

      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $dao->product_id;
      $productDAO->is_active = 1;
      if ($productDAO->find(TRUE)) {
        // #26455, backward compatibility needed
        if (is_null($productDAO->min_contribution_recur)) {
          $productDAO->min_contribution_recur = $productDAO->min_contribution;
        }
        if (is_null($productDAO->calculate_mode)) {
          $productDAO->calculate_mode = 'cumulative';
        }
        if (is_null($productDAO->installments)) {
          $productDAO->installments = 0;
        }
        if ($selectedProductID != NULL) {
          if ($selectedProductID == $productDAO->id) {
            if ($selectedOption) {
              $productDAO->options = ts('Selected Option') . ': ' . $selectedOption;
            }
            else {
              $productDAO->options = NULL;
            }
            CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
          }
        }
        else {
          CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
        }
      }
      $productAttr = [];
      $productAttr['data-min-contribution'] = $products[$productDAO->id]['min_contribution'];
      $productAttr['data-min-contribution-recur'] = $products[$productDAO->id]['min_contribution_recur'];
      $productAttr['data-calculate-mode'] = $products[$productDAO->id]['calculate_mode'];
      $productAttr['data-installments'] = $products[$productDAO->id]['installments'];
      
      $radio[$productDAO->id] = $form->createElement('radio', NULL, NULL, ' ', $productDAO->id, $productAttr);
      $options = $temp = [];
      $temp = explode(',', $productDAO->options);
      foreach ($temp as $value) {
        $options[trim($value)] = trim($value);
      }
      if ($temp[0] != '') {
        $form->addElement('select', 'options_' . $productDAO->id, NULL, $options);
      }
    }
    if (count($products)) {
      $form->assign('showRadioPremium', $formItems);
      if ($formItems) {
        $radio[''] = $form->createElement('radio', NULL, NULL, ' ', 'no_thanks', NULL);
        $form->assign('no_thanks_label', ts('No thank you'));
        $form->addGroup($radio, 'selectProduct', NULL);
        $form->addRule('selectProduct', ts('%1 is a required field.', [1 => ts('Premium')]), 'required');
        $default = ['selectProduct' => 'no_thanks'];
        $form->setDefaults($default);
        // Add hidden field to indicate this is product
        $form->addElement('hidden', 'premium_type', 'product');
      }
      $form->assign('showSelectOptions', $formItems);
      $form->assign('products', $products);
      $form->assign('premiumBlock', $premiumBlock);
    }
  }

  /**
   * Function to build Premium B im Contribution Pages
   *
   * @param int $pageId
   * @static
   */
  static function buildPremiumPreviewBlock($form, $productID, $premiumProductID = NULL) {


    if ($premiumProductID) {

      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->id = $premiumProductID;
      $dao->find(TRUE);
      $productID = $dao->product_id;
    }
    $productDAO = new CRM_Contribute_DAO_Product();
    $productDAO->id = $productID;
    $productDAO->is_active = 1;
    if ($productDAO->find(TRUE)) {
      CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
    }

    $radio[$productDAO->id] = $form->createElement('radio', NULL, NULL, NULL, $productDAO->id, NULL);
    $options = $temp = [];
    $temp = explode(',', $productDAO->options);
    foreach ($temp as $value) {
      $options[$value] = $value;
    }
    if ($temp[0] != '') {
      $form->add('select', 'options_' . $productDAO->id, NULL, $options);
    }


    $form->addGroup($radio, 'selectProduct', NULL);

    $form->assign('showRadio', TRUE);
    $form->assign('showSelectOptions', TRUE);
    $form->assign('products', $products);
    $form->assign('preview', TRUE);
  }

  /**
   * Function to build Premium Combination Preview Block
   *
   * @param $form
   * @param $combinationID
   * @static
   */
  static function buildCombinationPreviewBlock($form, $combinationID) {
    $combinations = [];
    $dao = new CRM_Contribute_DAO_PremiumsCombination();
    $dao->id = $combinationID;
    if ($dao->find(TRUE)) {
      $combinationData = [];
      CRM_Core_DAO::storeValues($dao, $combinationData);
      // Get products in this combination
      $combinationData['products'] = CRM_Contribute_BAO_PremiumsCombination::getCombinationProducts($dao->id);
      $combinations[$dao->id] = $combinationData;

      $radio[$dao->id] = $form->createElement('radio', NULL, NULL, NULL, $dao->id, NULL);
      $form->addGroup($radio, 'selectProduct', NULL);
    }

    $form->assign('showRadio', TRUE);
    $form->assign('combinations', $combinations);
    $form->assign('useCombinations', TRUE);
    $form->assign('preview', TRUE);
  }

  /**
   * Function to delete premium associated w/ contribution page.
   *
   * @param int $contribution page id
   * @static
   */
  static function deletePremium($contributionPageID) {
    if (!$contributionPageID) {
      return;
    }

    //need to delete entries from civicrm_premiums
    //as well as from civicrm_premiums_product, CRM-4586



    $params = ['entity_id' => $contributionPageID,
      'entity_table' => 'civicrm_contribution_page',
    ];

    $premium = new CRM_Contribute_DAO_Premium();
    $premium->copyValues($params);
    $premium->find();
    while ($premium->fetch()) {
      //lets delete from civicrm_premiums_product
      $premiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
      $premiumsProduct->premiums_id = $premium->id;
      $premiumsProduct->delete();

      //now delete premium
      $premium->delete();
    }
  }

  /**
   * Restock contribution products based on expired/failed online payments
   *
   * @static
   */
  static function restockContributionProducts() {
    $config = CRM_Core_Config::singleton();
    
    $creditCardDays = isset($config->premiumIRCreditCardDays) ? $config->premiumIRCreditCardDays : 7;
    $nonCreditCardDays = isset($config->premiumIRNonCreditCardDays) ? $config->premiumIRNonCreditCardDays : 3;
    $checkStatuses = isset($config->premiumIRCheckStatuses) ? $config->premiumIRCheckStatuses : [2]; // Pending
    $statusChange = isset($config->premiumIRStatusChange) ? $config->premiumIRStatusChange : 3; // Cancelled

    // Get contributions that need to be processed for restocking
    $contributionsToRestock = self::getExpiredOnlineContributions($creditCardDays, $nonCreditCardDays, $checkStatuses);
    
    if (empty($contributionsToRestock)) {
      return [];
    }

    $restockedContributions = [];
    
    foreach ($contributionsToRestock as $contribution) {
      // Update contribution status
      $sql = "UPDATE civicrm_contribution SET contribution_status_id = %1 WHERE id = %2";
      $params = [
        1 => [$statusChange, 'Integer'],
        2 => [$contribution['id'], 'Integer']
      ];
      CRM_Core_DAO::executeQuery($sql, $params);
      
      // Restock the premium products
      self::restockPremiumInventory($contribution['id']);
      
      $restockedContributions[] = $contribution['id'];
    }

    return $restockedContributions;
  }

  /**
   * Get payment instrument IDs by name
   *
   * @param array $names Array of payment instrument names
   * @return array
   * @static
   */
  static function getPaymentInstrumentIdsByNames($names) {
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $ids = [];
    foreach ($names as $name) {
      $id = array_search($name, $paymentInstruments);
      if ($id !== FALSE) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

  /**
   * Get expired online contributions that need restocking
   *
   * @param int $creditCardDays Days for credit card transactions
   * @param int $nonCreditCardDays Days for non-credit card transactions  
   * @param array $checkStatuses Statuses to check for
   * @return array
   * @static
   */
  static function getExpiredOnlineContributions($creditCardDays, $nonCreditCardDays, $checkStatuses) {
    $statusList = implode(',', array_map('intval', $checkStatuses));
    
    // Get payment instrument IDs based on names
    $creditCardInstruments = self::getPaymentInstrumentIdsByNames(['Credit Card', 'Web ATM', 'LinePay']);
    $nonCreditCardInstruments = self::getPaymentInstrumentIdsByNames(['ATM', 'Convenient Store (Code)']);
    $barcodeInstruments = self::getPaymentInstrumentIdsByNames(['Convenient Store']);
    
    $creditCardIds = implode(',', array_map('intval', $creditCardInstruments));
    $nonCreditCardIds = implode(',', array_map('intval', $nonCreditCardInstruments));
    $barcodeIds = implode(',', array_map('intval', $barcodeInstruments));
    
    $results = [];
    
    // Credit card transactions (immediate)
    if (!empty($creditCardIds)) {
      $creditCardSql = "
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.expiry_date, c.contribution_status_id
        FROM civicrm_contribution c
        INNER JOIN civicrm_entity_financial_trxn eft ON eft.entity_table = 'civicrm_contribution' AND eft.entity_id = c.id
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_instrument_id IN ({$creditCardIds})
          AND DATEDIFF(NOW(), c.receive_date) > %1
          AND c.id IN (
            SELECT cp.contribution_id FROM civicrm_contribution_premium cp WHERE cp.contribution_id = c.id
          )
      ";
      
      $dao = CRM_Core_DAO::executeQuery($creditCardSql, [1 => [$creditCardDays, 'Integer']]);
      while ($dao->fetch()) {
        $results[] = [
          'id' => $dao->id,
          'payment_instrument_id' => $dao->payment_instrument_id,
          'receive_date' => $dao->receive_date,
          'expiry_date' => $dao->expiry_date,
          'contribution_status_id' => $dao->contribution_status_id
        ];
      }
    }
    
    // Non-credit card transactions (ATM, convenience store code)
    if (!empty($nonCreditCardIds)) {
      $nonCreditCardSql = "
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.expiry_date, c.contribution_status_id
        FROM civicrm_contribution c
        INNER JOIN civicrm_entity_financial_trxn eft ON eft.entity_table = 'civicrm_contribution' AND eft.entity_id = c.id  
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_instrument_id IN ({$nonCreditCardIds})
          AND c.expiry_date IS NOT NULL
          AND DATEDIFF(NOW(), c.expiry_date) > %1
          AND c.id IN (
            SELECT cp.contribution_id FROM civicrm_contribution_premium cp WHERE cp.contribution_id = c.id
          )
      ";
      
      $dao = CRM_Core_DAO::executeQuery($nonCreditCardSql, [1 => [$nonCreditCardDays, 'Integer']]);
      while ($dao->fetch()) {
        $results[] = [
          'id' => $dao->id,
          'payment_instrument_id' => $dao->payment_instrument_id,
          'receive_date' => $dao->receive_date,
          'expiry_date' => $dao->expiry_date,
          'contribution_status_id' => $dao->contribution_status_id
        ];
      }
    }
    
    // Special case for convenience store barcodes (slower processing)
    if (!empty($barcodeIds)) {
      $barcodeSpecialSql = "
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.expiry_date, c.contribution_status_id
        FROM civicrm_contribution c
        INNER JOIN civicrm_entity_financial_trxn eft ON eft.entity_table = 'civicrm_contribution' AND eft.entity_id = c.id
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_instrument_id IN ({$barcodeIds})
          AND c.expiry_date IS NOT NULL  
          AND DATEDIFF(NOW(), c.expiry_date) > 3
          AND c.id IN (
            SELECT cp.contribution_id FROM civicrm_contribution_premium cp WHERE cp.contribution_id = c.id
          )
      ";
      
      $dao = CRM_Core_DAO::executeQuery($barcodeSpecialSql);
      while ($dao->fetch()) {
        $results[] = [
          'id' => $dao->id,
          'payment_instrument_id' => $dao->payment_instrument_id,
          'receive_date' => $dao->receive_date,
          'expiry_date' => $dao->expiry_date,
          'contribution_status_id' => $dao->contribution_status_id
        ];
      }
    }

    return $results;
  }

  /**
   * Restock premium inventory for a specific contribution
   *
   * @param int $contributionId The contribution ID
   * @static
   */
  static function restockPremiumInventory($contributionId) {
    $transaction = new CRM_Core_Transaction();
    
    // Get premium products associated with this contribution from civicrm_contribution_product
    $sql = "
      SELECT cp.id, cp.product_id, cp.product_option, cp.quantity
      FROM civicrm_contribution_product cp
      WHERE cp.contribution_id = %1
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$contributionId, 'Integer']]);
    
    while ($dao->fetch()) {
      // Update product send_qty by reducing the quantity (restock)
      $updateSql = "
        UPDATE civicrm_product 
        SET send_qty = send_qty - %1
        WHERE id = %2 AND send_qty IS NOT NULL AND send_qty >= %1 AND stock_status > 0
      ";
      
      $params = [
        1 => [$dao->quantity ?: 1, 'Integer'],
        2 => [$dao->product_id, 'Integer']
      ];
      
      CRM_Core_DAO::executeQuery($updateSql, $params);
      
      // Mark the contribution product as restocked
      $restockSql = "
        UPDATE civicrm_contribution_product 
        SET restock = 1
        WHERE id = %1
      ";
      
      CRM_Core_DAO::executeQuery($restockSql, [1 => [$dao->id, 'Integer']]);
    }
    
    $transaction->commit();
  }
}

