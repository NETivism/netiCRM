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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Form for the first step of the payment process.
 */
class CRM_Contribute_Form_Payment_Main extends CRM_Contribute_Form_Payment {
  /**
   * Prevent multiple submission
   *
   * @var Boolean
   * @protected
   */
  protected $_preventMultipleSubmission;

  /**
   * Set up variables before the form is built.
   *
   * This method initializes the payment process, checks if the user has passed
   * the entry criteria, and handles payment processor form initialization.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    if (!$this->_pass) {
      CRM_Utils_System::notFound();
      CRM_Utils_System::civiExit();
    }
    else {
      $this->assign('ppType', FALSE);
      $this->_ppType = CRM_Utils_Array::value('type', $_GET);
      if ($this->_ppType) {
        $this->assign('ppType', TRUE);
        CRM_Core_Payment_ProcessorForm::preProcess($this);
        $this->_preventMultipleSubmission = TRUE;
      }
    }
  }

  /**
   * Actually build the form components.
   *
   * Adds radio buttons for selecting payment methods or pay later options
   * based on the current context (contribution or event).
   *
   * @return mixed the built form components
   */
  public function buildQuickForm() {
    if ($this->_ppType) {
      return CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
    }

    if (!empty($this->_paymentProcessors)) {
      $pps = $this->_paymentProcessors;
      foreach ($pps as $key => &$name) {
        $pps[$key] = $name['name'];
      }
    }

    if ($this->getVar('_component') == 'event') {
      $event = new CRM_Event_DAO_Event();
      $event->id = $this->_entityId;
      $event->find(TRUE);
      if ($event->is_pay_later) {
        $pps[0] = $event->pay_later_text;
        $this->assign('pay_later_receipt', $event->pay_later_receipt);
      }
    }

    if (count($pps) >= 1) {
      $this->addRadio('payment_processor', ts('Payment Method'), $pps, NULL, "&nbsp;", TRUE);
    }

    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => ts('Change Payment Method'),
          'isDefault' => TRUE,
        ],
      ]
    );
  }

  /**
   * Global form rule for validation.
   *
   * @param array $fields the input form values
   * @param array $files the uploaded files array
   * @param CRM_Core_Form $self the form object
   *
   * @return array list of errors to be posted back to the form (currently empty)
   */
  public static function formRule($fields, $files, $self) {
    return $errors;
  }

  /**
   * Process the form submission.
   *
   * Copies the original contribution, updates the selected payment processor,
   * generates a new invoice ID, and initiates the transfer checkout process.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $processor = $this->_paymentProcessors[$params['payment_processor']];
    $contrib = CRM_Contribute_BAO_Contribution::copy($this->_id);
    if (!empty($params['payment_processor'])) {
      $contrib->payment_processor_id = $params['payment_processor'];
    }
    if (!empty($params['payment_instrument_id'])) {
      $contrib->payment_instrument_id = $params['payment_instrument_id'];
    }
    if (!empty($contrib->source)) {
      $contrib->source = str_replace(' '. ts('Change Payment Method'), '', $contrib->source).' '.ts('Change Payment Method');
    }
    else {
      $contrib->source = ' '.ts('Change Payment Method');
    }
    if (!empty($contrib->invoice_id)) {
      $invoice_id = md5(uniqid((string)rand(), TRUE));
      $contrib->invoice_id = $invoice_id;
      ;
      $this->set('invoiceID', $invoiceID);
    }
    $contrib->save();
    $values = [];
    $contrib->storeValues($contrib, $values);
    $this->set('contrib', $values);
    $ids = $this->getVar('_ids');
    $pid = $ids['participant'];
    if (!empty($pid)) {
      $this->payLaterProcessor($pid);
    }
    if (!empty($params['payment_processor'])) {
      $payment = CRM_Core_Payment::singleton($this->_mode, $processor, $this);
      $vars = $payment->prepareTransferCheckoutParams($contrib, $params);
    }

    // before leave to transfercheckout, call hook
    CRM_Utils_Hook::postProcess(get_class($this), $this);

    // TODO: we have to redirect to correct thank you page
    // maybe create own controller for that
    if (!empty($params['payment_processor'])) {
      $payment->doTransferCheckout($vars, $this->_component);
    }
  }

  /**
   * Update participant status when pay later is selected.
   *
   * Sets the participant status to 'Pending from pay later'.
   *
   * @param int $pid the participant ID to update
   *
   * @return void
   */
  private function payLaterProcessor($pid) {
    // Update status
    $value = [];
    $participant = new CRM_Event_DAO_Participant();
    $participant->id = $pid;
    if ($participant->find(TRUE)) {
      $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
      $status = 'Pending from pay later';
      $value['participant_status_id'] = array_search($status, $pendingStatuses);
      $participant->copyValues($value);
      $participant->save();
    }
  }

  /**
   * Determine the current action for the page.
   *
   * Overwrites the parent action to ensure elements are shown in frozen mode
   * without help displays.
   *
   * @return int the action code (VIEW or VIEW|PREVIEW)
   */
  public function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }
}
