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
 * form to process actions on Premiums
 */
class CRM_Contribute_Form_ContributionPage_Premium extends CRM_Contribute_Form_ContributionPage {

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = [];
    if (isset($this->_id)) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
      CRM_Utils_System::setTitle(ts('Premiums (%1)', [1 => $title]));
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->_id;
      $dao->find(TRUE);
      CRM_Core_DAO::storeValues($dao, $defaults);
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  /**
   * Get combination action Links
   *
   * @return array (reference) of action links
   */
  function &combinationLinks() {
    $deleteExtra = ts('Are you sure you want to remove this combination from this page?');

    $links = [
      CRM_Core_Action::UPDATE => [
        'name' => ts('Edit'),
        'url' => 'civicrm/admin/contribute/addPremiumsCombinationToPage',
        'qs' => 'action=update&id=%%id%%&pid=%%pid%%&reset=1',
        'title' => ts('Edit Premium'),
      ],
      CRM_Core_Action::PREVIEW => [
        'name' => ts('Preview'),
        'url' => 'civicrm/admin/contribute/addPremiumsCombinationToPage',
        'qs' => 'action=preview&id=%%id%%&pid=%%pid%%',
        'title' => ts('Preview Premium Combination'),
      ],
      CRM_Core_Action::DELETE => [
        'name' => ts('delete'),
        'url' => 'civicrm/admin/contribute/addPremiumsCombinationToPage',
        'qs' => 'action=delete&id=%%id%%&pid=%%pid%%',
        'extra' => 'onclick = "if (confirm(\'' . $deleteExtra . '\') ) {  this.href+=\'&amp;confirmed=1\'; else return false;}"',
        'title' => ts('Delete Premium Combination'),
      ],
    ];
    return $links;
  }

  public function buildQuickForm() {
    $this->addElement('checkbox', 'premiums_active', ts('Premiums Section Enabled?'));
    $this->addElement('checkbox', 'premiums_combination', ts('Enable Gift Combination Feature'));

    $this->addElement('text', 'premiums_intro_title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Premium', 'premiums_intro_title'));

    $this->add('textarea', 'premiums_intro_text', ts('Introductory Message'), 'rows=5, cols=50');

    $this->add('text', 'premiums_contact_email', ts('Contact Email') . ' ', CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Premium', 'premiums_contact_email'));

    $this->addRule('premiums_contact_email', ts('Please enter a valid email address for Contact Email') . ' ', 'email');

    $this->add('text', 'premiums_contact_phone', ts('Contact Phone'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Premium', 'premiums_contact_phone'));

    $this->addRule('premiums_contact_phone', ts('Please enter a valid phone number.'), 'phone');

    $this->addElement('checkbox', 'premiums_display_min_contribution', ts('Display Minimum Contribution Amount?'));

    $showForm = TRUE;
    $activePremiums = FALSE;
    $enablePremiumsCombination = FALSE;

    if ($this->_single) {
      if ($this->_id) {
        $daoPremium = new CRM_Contribute_DAO_Premium();
        $daoPremium->entity_id = $this->_id;
        $daoPremium->entity_table = 'civicrm_contribution_page';
        if ($daoPremium->find(TRUE)) {
          $showForm = FALSE;
          if ($daoPremium->premiums_combination == 1) {
            $enablePremiumsCombination = TRUE;
          }
          if ($daoPremium->premiums_active == 1) {
            $activePremiums = TRUE;
          }
        }
      }
    }
    $this->assign('showForm', $showForm);
    $this->assign('enablePremiumsCombination', $enablePremiumsCombination);
    $this->assign('activePremiums', $activePremiums);

    // Get combinations data if combination feature is enabled
    $combinations = [];
    if ($enablePremiumsCombination && $this->_id) {
      $daoPremium = new CRM_Contribute_DAO_Premium();
      $daoPremium->entity_id = $this->_id;
      $daoPremium->entity_table = 'civicrm_contribution_page';
      if ($daoPremium->find(TRUE)) {
        // Query combinations for this premium
        $query = "
          SELECT 
            pc.id,
            pc.combination_name,
            pc.sku,
            pc.min_contribution,
            pc.min_contribution_recur,
            pc.currency,
            pc.is_active,
            pc.weight
          FROM civicrm_premiums_combination pc
          WHERE pc.premiums_id = %1
          ORDER BY pc.weight, pc.combination_name
        ";
        $dao = CRM_Core_DAO::executeQuery($query, [
          1 => [$daoPremium->id, 'Integer']
        ]);
        
        while ($dao->fetch()) {
          // Get products for each combination
          $productsQuery = "
            SELECT
              p.name,
              p.sku as product_sku,
              pcp.quantity
            FROM civicrm_premiums_combination_products pcp
            LEFT JOIN civicrm_product p ON pcp.product_id = p.id
            WHERE pcp.combination_id = %1
            ORDER BY p.id
          ";
          $productsDao = CRM_Core_DAO::executeQuery($productsQuery, [
            1 => [$dao->id, 'Integer']
          ]);
          
          $products = [];
          while ($productsDao->fetch()) {
            $products[] = [
              'name' => $productsDao->name,
              'sku' => $productsDao->product_sku,
              'quantity' => $productsDao->quantity,
            ];
          }
          
          // Format combination content for display
          $combinationContent = [];
          foreach ($products as $product) {
            $productDisplay = $product['name'];
            if ($product['quantity'] >= 1) {
              $productDisplay .= ' x' . $product['quantity'];
            }
            $combinationContent[] = $productDisplay;
          }
          // Generate action links for combination
          $action = array_sum(array_keys($this->combinationLinks()));
          $combinations[] = [
            'id' => $dao->id,
            'combination_name' => $dao->combination_name,
            'sku' => $dao->sku,
            'combination_content' => implode(', ', $combinationContent),
            'min_contribution' => $dao->min_contribution,
            'min_contribution_recur' => $dao->min_contribution_recur,
            'currency' => $dao->currency,
            'is_active' => $dao->is_active,
            'weight' => $dao->weight,
            'action' => CRM_Core_Action::formLink($this->combinationLinks(), $action,
              ['id' => $dao->id, 'pid' => $this->_id]
            ),
          ];
        }
      }
    }
    $this->assign('combinations', $combinations);

    parent::buildQuickForm();
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // we do this in case the user has hit the forward/back button

    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $this->_id;
    $dao->find(TRUE);
    $premiumID = $dao->id;
    if ($premiumID) {
      $params['id'] = $premiumID;
    }

    $params['premiums_active'] = CRM_Utils_Array::value('premiums_active', $params, FALSE);
    $params['premiums_combination'] = CRM_Utils_Array::value('premiums_combination', $params, FALSE);
    $params['premiums_display_min_contribution'] = CRM_Utils_Array::value('premiums_display_min_contribution', $params, FALSE);
    $params['entity_table'] = 'civicrm_contribution_page';
    $params['entity_id'] = $this->_id;

    $dao = new CRM_Contribute_DAO_Premium();
    $dao->copyValues($params);
    $dao->save();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Premiums');
  }
}

