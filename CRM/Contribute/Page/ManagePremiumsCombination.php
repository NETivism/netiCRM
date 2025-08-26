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
 * Page for displaying list of Premium Combinations
 */
class CRM_Contribute_Page_ManagePremiumsCombination extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Contribute_BAO_PremiumsCombination';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/contribute/managePremiumsCombination',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Premium Combination'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/contribute/managePremiumsCombination',
          'qs' => 'action=preview&id=%%id%%',
          'title' => ts('Preview Premium Combination'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contribute_BAO_PremiumsCombination' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Premium Combination'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contribute_BAO_PremiumsCombination' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Premium Combination'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/contribute/managePremiumsCombination',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Premium Combination'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {

    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::PREVIEW)) {
      $this->edit($action, $id, TRUE);
    }
    // finally browse the custom groups
    $this->browse();

    // parent run
    parent::run();
  }

  /**
   * Browse all premium combinations.
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    // get all premium combinations sorted by weight
    $combinations = [];

    $dao = new CRM_Contribute_DAO_PremiumsCombination();
    $dao->orderBy('combination_name');
    $dao->find();

    while ($dao->fetch()) {
      $combinations[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $combinations[$dao->id]);
      $productCount = $this->getProductCount($dao->id);
      $combinations[$dao->id]['product_count'] = $productCount;
      // form all action links
      $action = array_sum(array_keys($this->links()));

      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $combinations[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        ['id' => $dao->id]
      );
    }
    $this->assign('rows', $combinations);
  }

  /**
   * Get product count for a combination
   *
   * @param int $combinationId
   * @return int
   */
  private function getProductCount($combinationId) {
    $dao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
    $dao->combination_id = $combinationId;
    $dao->find();
    return $dao->N;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Contribute_Form_ManagePremiumsCombination';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Manage Premium Combinations';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/contribute/managePremiumsCombination';
  }
}