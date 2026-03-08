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
 * @copyright CiviCRM LLC (c) 2004-2012
 *
 */

/**
 * This class handles manual transaction processing or status synchronization for recurring contributions.
 *
 * It provides buttons to either sync the status with the payment gateway or
 * immediately process a pending transaction for a specific recurring record.
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

  /**
   * Set up variables before the form is built.
   *
   * Enables protection against multiple submissions.
   *
   * @return void
   */
  public function preProcess() {
    $this->_preventMultipleSubmission = TRUE;
  }

  /**
   * Set default values for the form.
   *
   * @return array the array of default values (currently empty)
   */
  public function setDefaultValues() {
    return $defaults;
  }

  /**
   * Actually build the form components.
   *
   * Adds the 'Sync Now' and 'Process Now' buttons based on the capabilities
   * of the payment processor associated with the recurring contribution.
   *
   * @return void
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
   * Process the form submission.
   *
   * Determines which action was requested (sync or process) and invokes the
   * corresponding method on the payment processor class. Then redirects the
   * user back to the recurring contribution view page.
   *
   * @return mixed false if permissions are missing, or a status bounce redirection
   */
  public function postProcess() {
    if (!CRM_Core_Permission::check('edit contributions')) {
      return FALSE;
    }
    $recurId = $this->get('recurId');
    $contributionId = $this->get('contributionId');
    $paymentClass = CRM_Contribute_BAO_Contribution::getPaymentClass($contributionId);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == '_qf_MakingTransaction_upload') {
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
    $url = CRM_Utils_System::url(
      'civicrm/contact/view/contributionrecur',
      'reset=1&id='.$recurId.'&cid=' . $contactId
    );
    $message = ts("The contribution record has been processed.").$resultMessage;
    return CRM_Core_Error::statusBounce($message, $url);
  }
}
