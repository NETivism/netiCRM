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
        $combinationData['stock_status'] = FALSE;
        $combinationData['out_of_stock'] = FALSE;
        $combinationData['out_of_stock_products'] = [];
        foreach($combinationData['products'] as $productInfo) {
          if (!empty($productInfo['stock_status']) && $productInfo['stock_qty'] > 0) {
            $combinationData['stock_status'] = TRUE;
          }
          if (!empty($productInfo['stock_status']) && $productInfo['stock_qty'] <= $productInfo['send_qty']) {
            $combinationData['out_of_stock_products'][$productInfo['product_id']] = TRUE;
            break;
          }
        }
        if ($combinationData['stock_status'] && !empty($combinationData['out_of_stock_products'])) {
          $combinationData['out_of_stock'] = TRUE;
        }

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
          $combinationAttr['data-out-of-stock'] = $combinationData['out_of_stock'];
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

        $products[$productDAO->id]['stock_status'] = FALSE;
        $products[$productDAO->id]['out_of_stock'] = FALSE;
        $products[$productDAO->id]['out_of_stock_products'] = [];
        if (!empty($productDAO->stock_status) && $productDAO->stock_qty > 0) {
          $products[$productDAO->id]['stock_status'] = TRUE;
        }
        if (!empty($productDAO->stock_status) && $productDAO->stock_qty <= $productDAO->send_qty) {
          $products[$productDAO->id]['out_of_stock_products'][$productDAO->id] = TRUE;
          $products[$productDAO->id]['out_of_stock'] = TRUE;
        }
      }

      $productAttr = [];
      $productAttr['data-min-contribution'] = $products[$productDAO->id]['min_contribution'];
      $productAttr['data-min-contribution-recur'] = $products[$productDAO->id]['min_contribution_recur'];
      $productAttr['data-calculate-mode'] = $products[$productDAO->id]['calculate_mode'];
      $productAttr['data-installments'] = $products[$productDAO->id]['installments'];
      $productAttr['data-out-of-stock'] = $products[$productDAO->id]['out_of_stock'];
      
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
    
    $creditCardDays = $config->premiumIRCreditCardDays ?? 7;
    $nonCreditCardDays = $config->premiumIRNonCreditCardDays ?? 3;
    $convenienceStoreDays = $config->premiumIRConvenienceStoreDays ?? 3;
    $checkStatuses = $config->premiumIRCheckStatuses ?? [2]; // Pending
    $statusChangeStyle = $config->premiumIRStatusChange ?? 'maintain'; // maintain or cancelled

    // Get contributions that need to be processed for restocking
    $contributionsToRestock = self::getExpiredOnlineContributions($creditCardDays, $nonCreditCardDays, $convenienceStoreDays, $checkStatuses);
    
    if (empty($contributionsToRestock)) {
      return [];
    }

    $restockedContributions = [];
    
    foreach ($contributionsToRestock as $contribution) {
      try {
        // Update contribution status only if statusChangeStyle is 'cancelled'
        if ($statusChangeStyle === 'cancelled') {
          $sql = "UPDATE civicrm_contribution SET contribution_status_id = %1 WHERE id = %2";
          $params = [
            1 => [3, 'Integer'], // 3 = Cancelled status
            2 => [$contribution['id'], 'Integer']
          ];
          CRM_Core_DAO::executeQuery($sql, $params);
        }
        // If statusChangeStyle is 'maintain', we skip status update

        // Restock the premium products
        self::restockPremiumInventory($contribution['id'], $contribution['reason']);

        $restockedContributions[] = $contribution['id'];
      } catch (Exception $e) {
        $errorMessage = "Failed to restock contribution ID {$contribution['id']}: " . $e->getMessage();
        CRM_Core_Error::debug_log_message($errorMessage);
        // Continue processing other contributions
      }
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
   * @param int $convenienceStoreDays Days for convenience store barcode transactions
   * @param array $checkStatuses Statuses to check for
   * @return array
   * @static
   */
  static function getExpiredOnlineContributions($creditCardDays, $nonCreditCardDays, $convenienceStoreDays, $checkStatuses) {
    // Get all contribution statuses and map names to IDs
    $allStatuses = CRM_Contribute_PseudoConstant::contributionStatus('', 'name');
    $statusNameToId = array_flip($allStatuses);

    // Map status names to IDs
    $statusIds = [];
    foreach ($checkStatuses as $statusName) {
      if (isset($statusNameToId[$statusName])) {
        $statusIds[] = $statusNameToId[$statusName];
      }
    }
    $statusList = implode(',', array_map('intval', $statusIds));
    
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
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.contribution_status_id, c.created_date
        FROM civicrm_contribution c
        INNER JOIN civicrm_contribution_product cp ON cp.contribution_id = c.id
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_processor_id IS NOT NULL
          AND c.payment_instrument_id IN ({$creditCardIds})
          AND DATEDIFF(NOW(), c.created_date) > %1
          AND cp.restock <= 0
      ";
      
      $dao = CRM_Core_DAO::executeQuery($creditCardSql, [1 => [$creditCardDays, 'Integer']]);
      while ($dao->fetch()) {
        if (!isset($results[$dao->id])) {
          $results[$dao->id] = [
            'id' => $dao->id,
            'payment_instrument_id' => $dao->payment_instrument_id,
            'receive_date' => $dao->receive_date,
            'contribution_status_id' => $dao->contribution_status_id,
            'created_date' => $dao->created_date,
            'reason' => ts('Credit card transaction expired (over %1 days)', [1 => $creditCardDays])
          ];
        }
      }
    }

    // Non-credit card transactions (ATM, convenience store code)
    if (!empty($nonCreditCardIds)) {
      $nonCreditCardSql = "
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.contribution_status_id, c.created_date
        FROM civicrm_contribution c
        INNER JOIN civicrm_contribution_product cp ON cp.contribution_id = c.id
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_processor_id IS NOT NULL
          AND c.payment_instrument_id IN ({$nonCreditCardIds})
          AND DATEDIFF(NOW(), c.created_date) > %1
          AND cp.restock <= 0
      ";

      $dao = CRM_Core_DAO::executeQuery($nonCreditCardSql, [1 => [$nonCreditCardDays, 'Integer']]);
      while ($dao->fetch()) {
        if (!isset($results[$dao->id])) {
          $results[$dao->id] = [
            'id' => $dao->id,
            'payment_instrument_id' => $dao->payment_instrument_id,
            'receive_date' => $dao->receive_date,
            'contribution_status_id' => $dao->contribution_status_id,
            'created_date' => $dao->created_date,
            'reason' => ts('Non-credit card transaction expired (over %1 days)', [1 => $nonCreditCardDays])
          ];
        }
      }
    }

    // Special case for convenience store barcodes (slower processing)
    if (!empty($barcodeIds)) {
      $barcodeSpecialSql = "
        SELECT c.id, c.payment_instrument_id, c.receive_date, c.contribution_status_id, c.created_date
        FROM civicrm_contribution c
        INNER JOIN civicrm_contribution_product cp ON cp.contribution_id = c.id
        WHERE c.contribution_status_id IN ({$statusList})
          AND c.payment_processor_id IS NOT NULL
          AND c.payment_instrument_id IN ({$barcodeIds})
          AND DATEDIFF(NOW(), c.created_date) > %1
          AND cp.restock <= 0
      ";

      $dao = CRM_Core_DAO::executeQuery($barcodeSpecialSql, [1 => [$convenienceStoreDays, 'Integer']]);
      while ($dao->fetch()) {
        if (!isset($results[$dao->id])) {
          $results[$dao->id] = [
            'id' => $dao->id,
            'payment_instrument_id' => $dao->payment_instrument_id,
            'receive_date' => $dao->receive_date,
            'contribution_status_id' => $dao->contribution_status_id,
            'created_date' => $dao->created_date,
            'reason' => ts('Convenience store barcode transaction expired (over %1 days)', [1 => $convenienceStoreDays])
          ];
        }
      }
    }

    return $results;
  }

  /**
   * Restock premium inventory for a specific contribution
   *
   * @param int $contributionId The contribution ID
   * @param string $source The source of this restock
   * @static
   */
  static function restockPremiumInventory($contributionId, $source) {
    // Get premium products associated with this contribution from civicrm_contribution_product
    // Only get products that haven't been restocked yet (restock IS NULL OR restock <= 0)
    $sql = "
      SELECT cp.id, cp.product_id, cp.contribution_id, cp.product_option, cp.quantity
      FROM civicrm_contribution_product cp
      WHERE cp.contribution_id = %1 
        AND (cp.restock IS NULL OR cp.restock <= 0)
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$contributionId, 'Integer']]);
    
    $productsToRestock = [];
    $invalidProducts = [];
    
    // Pre-validate all products before starting transaction
    while ($dao->fetch()) {
      $quantity = $dao->quantity ?: 1;
      // Check if product meets restock conditions
      $checkSql = "
        SELECT id, name, send_qty, stock_status, stock_qty
        FROM civicrm_product 
        WHERE id = %1 AND send_qty IS NOT NULL AND send_qty >= %2 AND stock_status > 0 AND stock_qty > %2
      ";
      
      $checkParams = [
        1 => [$dao->product_id, 'Integer'],
        2 => [$quantity, 'Integer']
      ];
      
      $productDao = CRM_Core_DAO::executeQuery($checkSql, $checkParams);
      
      if ($productDao->fetch()) {
        $productsToRestock[] = [
          'contribution_product_id' => $dao->id,
          'product_id' => $dao->product_id,
          'quantity' => $quantity,
          'contribution_id' => $contributionId,
        ];
      } else {
        // Get product details for error message
        $productDetailSql = "SELECT id, name, send_qty, stock_status, stock_qty FROM civicrm_product WHERE id = %1";
        $productDetailDao = CRM_Core_DAO::executeQuery($productDetailSql, [1 => [$dao->product_id, 'Integer']]);
        
        if ($productDetailDao->fetch()) {
          $invalidProducts[] = [
            'id' => $productDetailDao->id,
            'name' => $productDetailDao->name,
            'send_qty' => $productDetailDao->send_qty,
            'stock_status' => $productDetailDao->stock_status,
            'stock_qty' => $productDetailDao->stock_qty,
            'required_quantity' => $quantity
          ];
        } else {
          $invalidProducts[] = [
            'id' => $dao->product_id,
            'name' => 'Unknown Product',
            'send_qty' => null,
            'stock_status' => null,
            'stock_qty' => null,
            'required_quantity' => $quantity
          ];
        }
      }
    }
    
    // If any products don't meet conditions, throw error
    if (!empty($invalidProducts)) {
      $errorMessages = [];
      foreach ($invalidProducts as $product) {
        // skip no stock management product
        if (empty($product['stock_status'])) {
          continue;
        }
        $reasons = [];
        if (is_null($product['send_qty'])) {
          $reasons[] = 'send_qty is NULL';
        } elseif ($product['send_qty'] < $product['required_quantity']) {
          $reasons[] = "send_qty ({$product['send_qty']}) < required quantity ({$product['required_quantity']})";
        }
        if ($product['stock_qty'] <= 0) {
          $reasons[] = "stock_qty ({$product['stock_qty']}) <= 0";
        }
        
        $errorMessages[] = "Product '{$product['name']}' (ID: {$product['id']}): " . implode(', ', $reasons);
      }
      if (!empty($errorMessages)) {
        throw new Exception("Restock failed - the following products do not meet restock conditions: " . implode('; ', $errorMessages));
      }
    }
    
    // If no products to restock, return early
    if (empty($productsToRestock)) {
      return;
    }
    
    // Start transaction only after validation passes
    $transaction = new CRM_Core_Transaction();
    
    // Process validated products for restocking
    foreach ($productsToRestock as $productInfo) {
      // Update product send_qty by reducing the quantity (restock)
      $updateSql = "
        UPDATE civicrm_product 
        SET send_qty = send_qty - %1
        WHERE id = %2 AND send_qty IS NOT NULL AND send_qty >= %1 AND stock_status > 0 AND stock_qty > 0
      ";
      
      $params = [
        1 => [$productInfo['quantity'], 'Integer'],
        2 => [$productInfo['product_id'], 'Integer']
      ];
      
      $resultDao = CRM_Core_DAO::executeQuery($updateSql, $params);
      
      // Verify that the update actually affected a row
      if ($resultDao->affectedRows() == 0) {
        $transaction->rollback();
        throw new Exception("Restock failed - Product ID {$productInfo['product_id']} could not be updated. This may indicate the product conditions changed during processing.");
      }
      else {
        $logParams = [
          'entity_table' => 'civicrm_product',
          'entity_id' => $productInfo['product_id'],
          'modified_date' => date('YmdHis'),
          'data' => "+{$productInfo['quantity']}::{$productInfo['contribution_id']}::".$source,
        ];
        $userID = CRM_Core_Session::singleton()->get('userID');
        if (!empty($userID)) {
          $logParams['modified_id'] = $userID;
        }
        CRM_Core_BAO_Log::add($logParams);
      }
      
      // Mark the contribution product as restocked
      $restockSql = "
        UPDATE civicrm_contribution_product 
        SET restock = 1
        WHERE id = %1
      ";
      
      CRM_Core_DAO::executeQuery($restockSql, [1 => [$productInfo['contribution_product_id'], 'Integer']]);
    }
    
    $transaction->commit();
  }

  /**
   * Get contribution premium details from civicrm_contribution_product table
   *
   * This function retrieves what products were actually selected by the user
   * in a contribution. It queries the civicrm_contribution_product table which
   * stores the actual selection, not the combination definition.
   *
   * @param int $contributionId The contribution ID
   *
   * @return array Array containing premium details with keys:
   *   - 'is_combination': boolean - Whether this is a combination premium
   *   - 'combination_id': int|null - Combination ID if applicable
   *   - 'combination_name': string - Combination name (or [Deleted Combination #X] if deleted)
   *   - 'product_name': string - Full product display name
   *   - 'product_content': string - Detailed product list with quantities (e.g., "T-Shirt x1, Hat x2")
   *   - 'products': array - Array of individual products with name, quantity, sku
   *   - 'product_option': string - Product option selected
   *
   * @access public
   * @static
   */
  public static function getContributionPremiumDetails($contributionId) {
    $details = [
      'is_combination' => FALSE,
      'combination_id' => NULL,
      'combination_name' => '',
      'product_name' => '',
      'product_content' => '',
      'products' => [],
      'product_option' => '',
    ];

    // Get basic contribution product info
    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $contributionId;

    if (!$dao->find(TRUE)) {
      return $details;
    }

    $details['product_option'] = $dao->product_option;

    // Handle combination premium
    if (!empty($dao->combination_id)) {
      $details['is_combination'] = TRUE;
      $details['combination_id'] = $dao->combination_id;

      // Get combination name
      $combinationDAO = new CRM_Contribute_DAO_PremiumsCombination();
      $combinationDAO->id = $dao->combination_id;
      if ($combinationDAO->find(TRUE)) {
        $details['combination_name'] = $combinationDAO->combination_name;
      }
      else {
        $details['combination_name'] = '[Deleted Combination #' . $dao->combination_id . ']';
      }

      // Get actual selected products from contribution_product
      $sql = "
        SELECT cp.product_id, cp.quantity, p.name, p.sku
        FROM civicrm_contribution_product cp
        LEFT JOIN civicrm_product p ON cp.product_id = p.id
        WHERE cp.contribution_id = %1 AND cp.combination_id = %2
        ORDER BY p.name
      ";

      $productDao = CRM_Core_DAO::executeQuery($sql, [
        1 => [$contributionId, 'Integer'],
        2 => [$dao->combination_id, 'Integer']
      ]);

      $productContentArray = [];
      while ($productDao->fetch()) {
        $details['products'][] = [
          'product_id' => $productDao->product_id,
          'name' => $productDao->name,
          'quantity' => $productDao->quantity,
          'sku' => $productDao->sku,
        ];
        $productContentArray[] = $productDao->name . ' x' . $productDao->quantity;
      }

      $details['product_content'] = implode(', ', $productContentArray);
      $details['product_name'] = $details['combination_name'] . ' (' . $details['product_content'] . ')';
    }
    // Handle single product premium
    else if (!empty($dao->product_id)) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $dao->product_id;
      if ($productDAO->find(TRUE)) {
        $details['product_name'] = $productDAO->name;
        $details['products'][] = [
          'product_id' => $productDAO->id,
          'name' => $productDAO->name,
          'quantity' => 1,
          'sku' => $productDAO->sku,
        ];
      }
    }
    return $details;
  }

  /**
   * Get stock logs for a contribution's premium products
   *
   * @param int $contributionId The contribution ID
   *
   * @return array Array of stock log entries grouped by timestamp
   */
  public static function getStockLogs($contributionId) {
    $logs = [];

    // Get product IDs associated with this contribution
    $sql = "
      SELECT DISTINCT cp.product_id, p.name as product_name
      FROM civicrm_contribution_product cp
      LEFT JOIN civicrm_product p ON cp.product_id = p.id
      WHERE cp.contribution_id = %1
    ";

    $productDao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$contributionId, 'Integer']
    ]);

    $productMap = [];
    while ($productDao->fetch()) {
      $productMap[$productDao->product_id] = $productDao->product_name;
    }

    if (empty($productMap)) {
      return $logs;
    }

    // Build product ID list for IN clause
    $productIds = implode(',', array_map('intval', array_keys($productMap)));

    // Query logs for these products that match this contribution
    $logSql = "
      SELECT l.id, l.entity_id, l.data, l.modified_date, l.modified_id,
             c.display_name as modified_by
      FROM civicrm_log l
      LEFT JOIN civicrm_contact c ON l.modified_id = c.id
      WHERE l.entity_table = 'civicrm_product'
        AND l.entity_id IN ({$productIds})
        AND l.data LIKE %1
      ORDER BY l.modified_date ASC
    ";

    $logDao = CRM_Core_DAO::executeQuery($logSql, [
      1 => ["%::{$contributionId}::%", 'String']
    ]);

    $groupedLogs = [];

    while ($logDao->fetch()) {
      $data = $logDao->data;
      $parsed = self::parseStockLogData($data);

      if ($parsed === FALSE) {
        continue;
      }

      $dateKey = $logDao->modified_date;

      if (!isset($groupedLogs[$dateKey])) {
        $groupedLogs[$dateKey] = [
          'modified_date' => $logDao->modified_date,
          'modified_by' => $logDao->modified_by ?: '',
          'entries' => [],
          'reason' => '',
        ];
      }

      $groupedLogs[$dateKey]['entries'][] = [
        'type' => $parsed['type'],
        'product_name' => $productMap[$logDao->entity_id] ?? ts('Unknown Product'),
        'quantity' => $parsed['quantity'],
      ];

      if (!empty($parsed['reason']) && empty($groupedLogs[$dateKey]['reason'])) {
        $groupedLogs[$dateKey]['reason'] = $parsed['reason'];
      }
    }

    return array_values($groupedLogs);
  }

  /**
   * Parse stock log data string
   *
   * @param string $data The data string from civicrm_log.data
   *
   * @return array|false Array with 'type', 'quantity', 'contribution_id', 'reason' or FALSE
   */
  private static function parseStockLogData($data) {
    // Pattern: {sign}{quantity}::{contribution_id}::{reason}
    if (!preg_match('/^([+-])(\d+)::(\d+)::(.*)$/s', $data, $matches)) {
      return FALSE;
    }

    $sign = $matches[1];
    $quantity = (int)$matches[2];
    $contributionId = (int)$matches[3];
    $reason = trim($matches[4]);

    // Clean up reason - remove "via contribution ID XXX" suffix
    $reason = preg_replace('/\s*via contribution ID \d+$/i', '', $reason);

    return [
      'type' => ($sign === '-') ? 'deduct' : 'restock',
      'quantity' => $quantity,
      'contribution_id' => $contributionId,
      'reason' => $reason,
    ];
  }

  /**
   * Reserve product stock by updating send_qty for a given product
   *
   * This function handles stock management when a premium product is selected
   * in a contribution. It checks stock availability, updates the send quantity,
   * and creates a log entry for inventory tracking.
   *
   * @param int $contributionId The ID of the contribution requesting the product
   * @param int $productId The ID of the product to reserve
   * @param int $quantity The quantity to reserve
   * @param string $comment The comment/description for the stock reservation log
   *
   * @throws CRM_Core_Exception When product not found, insufficient stock or product is out of stock
   *
   * @return bool TRUE if stock was reserved, FALSE if stock management not enabled
   * @access public
   * @static
   */
  public static function reserveProductStock($contributionId, $productId, $quantity, $comment) {
    $product = new CRM_Contribute_DAO_Product();
    $product->id = $productId;

    if (!$product->find(TRUE)) {
      throw new CRM_Core_Exception(ts('Premium product not found.'));
    }

    if ($product->stock_status <= 0) {
      // Stock management not enabled for this product, skip silently
      return FALSE;
    }

    if ($product->stock_qty <= 0) {
      throw new CRM_Core_Exception(ts('Product is out of stock.'));
    }

    $remainQty = $product->stock_qty - $product->send_qty;

    if ($remainQty < $quantity) {
      throw new CRM_Core_Exception(ts('Insufficient stock. Available quantity: %1, Requested: %2',
        [1 => $remainQty, 2 => $quantity]));
    }

    $transaction = new CRM_Core_Transaction();
    $product->send_qty += $quantity;
    $product->save();

    $logParams = [
      'entity_table' => 'civicrm_product',
      'entity_id' => $product->id,
      'modified_date' => date('YmdHis'),
      'data' => "-{$quantity}::{$contributionId}::" . ($comment ? $comment : ''),
    ];
    $userID = CRM_Core_Session::singleton()->get('userID');
    if (!empty($userID)) {
      $logParams['modified_id'] = $userID;
    }
    else {
      $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contact_id');
      if (!empty($contactId)) {
        $logParams['modified_id'] = $contactId;
      }
    }
    CRM_Core_BAO_Log::add($logParams);
    $transaction->commit();

    return TRUE;
  }
}

