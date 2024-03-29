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

require_once 'CRM/Core/Page/Basic.php';
require_once 'CRM/Core/DAO/PaymentProcessor.php';

/**
 * Page for displaying list of payment processors
 */
class CRM_Admin_Page_PaymentProcessor extends CRM_Core_Page_Basic {

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
    return 'CRM_Core_BAO_PaymentProcessor';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/paymentProcessor',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Payment Processor'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_PaymentProcessor' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Payment Processor'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_PaymentProcessor' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Payment Processor'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/paymentProcessor',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Payment Processor'),
        ),
      );
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
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - Payment Processor'));
    $breadCrumb = array(array('title' => ts('Global Settings'),
        'url' => CRM_Utils_System::url('civicrm/admin/setting',
          'reset=1'
        ),
      ));
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    return parent::run();
  }

  /**
   * Browse all payment processors.
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    // get all custom groups sorted by weight
    $paymentProcessor = array();
    $dao = new CRM_Core_DAO_PaymentProcessor();
    $dao->is_test = 0;
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $paymentProcessor[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $paymentProcessor[$dao->id]);
      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $isHaveActiveRecur = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_recur WHERE processor_id = %1 AND contribution_status_id = 5", array( 1 => array( $dao->id, 'Positive')));
      if ($isHaveActiveRecur) {
        $action = $action & ~CRM_Core_Action::DISABLE;
        $action = $action & ~CRM_Core_Action::DELETE;
        $isShowMessage = TRUE;
      }

      $paymentProcessor[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $dao->id)
      );
    }
    $this->assign('rows', $paymentProcessor);

    if ($isShowMessage) {
      CRM_Core_Session::setStatus(ts("Some recurring contributions are in progress, so some payment processors can't be disabled or deleted."), TRUE, 'warning');
    }

    $dao = new CRM_Core_DAO_PaymentProcessorType();
    $dao->is_test = 0;
    $dao->is_active = 1;
    $dao->orderBy('name');
    if ($dao->find(TRUE)) {
      $this->assign('availablePaymentProcessor', $dao->name);
    }

    $hostIP = CRM_Utils_System::getHostIPAddress();
    if (!$hostIP) {
      $hostIP = ts('None');
    }
    $this->assign('hostIP', $hostIP);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_PaymentProcessor';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Payment Processors';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/paymentProcessor';
  }
}

