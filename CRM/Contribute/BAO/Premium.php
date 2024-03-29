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

require_once 'CRM/Contribute/DAO/Premium.php';
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
    require_once 'CRM/Contribute/DAO/Premium.php';
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

    require_once 'CRM/Contribute/DAO/Premium.php';
    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    $dao->premiums_active = 1;

    if ($dao->find(TRUE)) {
      $premiumID = $dao->id;
      $premiumBlock = array();
      CRM_Core_DAO::storeValues($dao, $premiumBlock);

      require_once 'CRM/Contribute/DAO/PremiumsProduct.php';
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->premiums_id = $premiumID;
      $dao->orderBy('weight');
      $dao->find();

      $products = array();
      $radio = array();
      while ($dao->fetch()) {
        require_once 'CRM/Contribute/DAO/Product.php';
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
        $productAttr = array();
        $productAttr['data-min-contribution'] = $products[$productDAO->id]['min_contribution'];
        $productAttr['data-min-contribution-recur'] = $products[$productDAO->id]['min_contribution_recur'];
        $productAttr['data-calculate-mode'] = $products[$productDAO->id]['calculate_mode'];
        $productAttr['data-installments'] = $products[$productDAO->id]['installments'];
        
        $radio[$productDAO->id] = $form->createElement('radio', NULL, NULL, ' ', $productDAO->id, $productAttr);
        $options = $temp = array();
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
          $form->addRule('selectProduct', ts('%1 is a required field.', array(1 => ts('Premium'))), 'required');
          $default = array('selectProduct' => 'no_thanks');
          $form->setDefaults($default);
        }
        $form->assign('showSelectOptions', $formItems);
        $form->assign('products', $products);
        $form->assign('premiumBlock', $premiumBlock);
      }
    }
  }

  /**
   * Function to build Premium B im Contribution Pages
   *
   * @param int $pageId
   * @static
   */
  static function buildPremiumPreviewBlock($form, $productID, $premiumProductID = NULL) {

    require_once 'CRM/Contribute/DAO/Product.php';
    if ($premiumProductID) {
      require_once 'CRM/Contribute/DAO/PremiumsProduct.php';
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
    $options = $temp = array();
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
    require_once 'CRM/Contribute/DAO/Premium.php';
    require_once 'CRM/Contribute/DAO/PremiumsProduct.php';

    $params = array('entity_id' => $contributionPageID,
      'entity_table' => 'civicrm_contribution_page',
    );

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
}

