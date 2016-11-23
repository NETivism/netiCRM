<?php
class CRM_Contribute_Form_TaxReceipt extends CRM_Core_Form {

  public $_permission = NULL;
  public $_contactId = NULL;
  public $_id = NULL;
  public $_type = NULL;
  public $_tplParams = array();
  public $_taxReceipt = NULL;
  public $_userContext = NULL;

  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_type = CRM_Utils_Request::retrieve('type', 'String', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $this->_id;
    if($contribution->find(TRUE)) {
      if ($contribution->contribution_status_id != 1) {
        CRM_Core_Error::fatal(ts('This record not complete, you can only create tax receipt for completed payment.'));
      }
      if (empty($contribution->trxn_id)) {
        CRM_Core_Error::fatal(ts('You need specify transaction number to create new tax receipt'));
      }
      CRM_Utils_Hook::prepareTaxReceipt($this->_id, $this->_tplParams, $this->_taxReceipt, $contribution);
      if (!empty($this->_tplParams)) {
        // assign these element
        $this->assign($this->_tplParams);
      }

      // we needs taxReceipt have receipt_info, receipt_status, receipt_message
      if ($this->_taxReceipt['receipt_status']) {
        $this->assign('taxReceiptInfo', $this->_taxReceipt['receipt_info']);
      }

      if ($this->_taxReceipt['receipt_print']) {
        $this->assign('taxReceiptPrint', $this->_taxReceipt['receipt_print']);
      }
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if (empty($this->_taxReceipt) || empty($this->_taxReceipt['issue_response']) || (isset($this->_taxReceipt['issue_response']->Status) && $this->_taxReceipt['issue_response']->Status != 'SUCCESS')) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Create Tax Receipt'),
            'isDefault' => TRUE,
          ),
        )
      );
    }
    else {
      // display message here
    }
    return;
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if (!empty($this->_id)) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $this->_id;
      if($contribution->find(TRUE)) {
        $result = array();
        CRM_Utils_Hook::createTaxReceipt($this->_id, $result, $contribution);
        if (!empty($result)) {
          CRM_Core_Session::setStatus(ts('Successuful created tax receipt.'));
        }
      }
      $query = "reset=1&action=update&id={$this->_id}&cid={$contribution->contact_id}&context=search";
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/taxreceipt', $query));
    }
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }
}
