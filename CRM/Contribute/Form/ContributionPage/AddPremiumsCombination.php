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
 * form to process actions for adding premium combination to contribution page
 */
class CRM_Contribute_Form_ContributionPage_AddPremiumsCombination extends CRM_Contribute_Form_ContributionPage_AddProduct {

  static $_combinations;
  static $_cid;

  /**
   * Function to pre process the form
   *
   * @access public
   *
   * @return None
   */
  public function preProcess() {
    parent::preProcess();

    $this->_combinations = CRM_Contribute_BAO_PremiumsCombination::getCombinations($this->_id);
    $this->_cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);

    if ($this->_cid) {
      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      $dao->id = $this->_cid;
      $dao->find(TRUE);
      $this->_combinations[$dao->id] = $dao->combination_name;
    }
  }

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

    if ($this->_cid) {
      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      $dao->id = $this->_cid;
      $dao->find(TRUE);
      $defaults['combination_id'] = $dao->id;
      $defaults['weight'] = $dao->weight;
    }

    if (!isset($defaults['weight']) || !($defaults['weight'])) {
      $pageID = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $sql = 'SELECT max( weight ) as max_weight FROM civicrm_premiums_combination WHERE premiums_id = %1';
      $params = [1 => [$premiumID, 'Integer']];
      $dao = &CRM_Core_DAO::executeQuery($sql, $params);
      $dao->fetch();
      $defaults['weight'] = $dao->max_weight + 1;
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $urlParams = 'civicrm/admin/contribute/premium';

    if ($this->_action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      $session->pushUserContext($url);

      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
          CRM_Core_DAO::$_nullObject, '', '', 'GET'
        )) {
        $dao = new CRM_Contribute_DAO_PremiumsCombination();
        $dao->id = $this->_cid;

        // Only remove the page association, without deleting the combination itself.
        $dao->premiums_id = NULL;
        $dao->save();

        CRM_Core_Session::setStatus(ts('Selected Premium Combination has been removed from this Contribution Page.'));
        CRM_Utils_System::redirect($url);
      }

      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    if ($this->_action & CRM_Core_Action::PREVIEW) {
      // TODO: Preview
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Done with Preview'),
            'isDefault' => TRUE,
          ],
        ]
      );
      return;
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    $session->pushUserContext($url);

    $this->add('select', 'combination_id', ts('Select Premium Combination') . ' ', $this->_combinations, TRUE);
    $this->addElement('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'weight'));
    $this->addRule('weight', ts('Please enter integer value for weight'), 'integer');

    $session = CRM_Core_Session::singleton();
    $single = $session->get('singleForm');
    $session->pushUserContext(CRM_Utils_System::url($urlParams, 'action=update&reset=1&id=' . $this->_id));

    if ($single) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
    else {
      parent::buildQuickForm();
    }
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
    $pageID = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $urlParams = 'civicrm/admin/contribute/premium';

    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      CRM_Utils_System::redirect($url);
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);

      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      $dao->id = $this->_cid;
      
      // Only remove the page association, without deleting the combination itself.
      $dao->premiums_id = NULL;
      $dao->save();
      
      CRM_Core_Session::setStatus(ts('The selected premium combination has been removed from this contribution page.'));
      CRM_Utils_System::redirect($url);
    }
    else {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);

      // Get premiums_id
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      // Update the selected combination and assign it to this page.
      $combinationDao = new CRM_Contribute_DAO_PremiumsCombination();
      $combinationDao->id = $params['combination_id'];
      $combinationDao->premiums_id = $premiumID;
      $combinationDao->weight = $params['weight'];
      $combinationDao->save();

      CRM_Core_Session::setStatus(ts('Premium combination has been added to this contribution page.'));
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Add Premium Combination to Contribution Page');
  }
}