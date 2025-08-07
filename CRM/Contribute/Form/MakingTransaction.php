<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */



/**
 * This class generates form components generic to recurring contributions
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contribute_Form_MakingTransaction extends CRM_Core_Form {

  /**
   * @var bool
   */
  public $_preventMultipleSubmission;
  /**
   * The recurring contribution id, used when editing the recurring contribution
   *
   * @var int
   */
  protected $_id;
  protected $_online;

  /**
   * the id of the contact associated with this recurring contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  function preProcess() {
    $this->_preventMultipleSubmission = TRUE;
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {

    return $defaults;
  }

  /**
   * Function to actually build the components of the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $id = $this->get('recurId');
    $contributionId = $this->get('contributionId');

    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);
    if (method_exists($paymentClass, 'doRecurUpdate')) {
      $name = $this->getButtonName('upload');
      $message = ts("Are you sure you want to sync the recurring status and check the contributions?");
      if (method_exists($paymentClass, 'getSyncNowMessage')) {
        $message = $paymentClass::getSyncNowMessage($contributionId, $id);
      }
      if (!empty($message)) {
        $this->addElement('submit', $name, ts("Sync Now"), ['onclick' => "return confirm('".$message."')"]);
        $this->assign('update_notify', $name);
      }
    }
    if (method_exists($paymentClass, 'doRecurTransact')) {
      $showButton = TRUE;
      if (method_exists($paymentClass, 'checkProceedRecur')) {
        $showButton = $paymentClass::checkProceedRecur($id);
      }
      if ($showButton) {
        $name = $this->getButtonName('submit');
        $this->addElement('submit', $name, ts('Process now'), ['onclick' => "return confirm('".ts("Are you sure you want to process a transaction of %1?", [1 => $id])."')"]);
        $this->assign('submit_name', $name);
      }
    }
  }

  /**
   * This function is called after the user submits the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if (!CRM_Core_Permission::check('edit contributions')) {
      return FALSE;
    }
    $recurId = $this->get('recurId');
    $contributionId = $this->get('contributionId');

    if (isset($this->_elementIndex['_qf_MakingTransaction_upload'])) {
      $isActionUpdate = TRUE;
    }

    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);
    if ($isActionUpdate) {
      if (method_exists($paymentClass, 'doRecurUpdate')) {
        $resultMessage = $paymentClass::doRecurUpdate($recurId, 'recur', $this);
      }
    }
    else {
      if (method_exists($paymentClass, 'doRecurTransact')) {
        $result = $paymentClass::doRecurTransact($recurId);
        $resultMessage = ts("Total Payments: %1", [1]);
      }
    }

    $contactId = $this->get('contactId');
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view/contributionrecur',
      'reset=1&id='.$recurId.'&cid=' . $contactId
    );
    $message = ts("The contribution record has been processed.").$resultMessage;
     return CRM_Core_Error::statusBounce($message, $url);
  }
  //end of function
}

