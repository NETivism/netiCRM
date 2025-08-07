<?php
class CRM_Contribute_Form_TaxReceipt extends CRM_Core_Form {

  public $_permission = NULL;
  public $_contactId = NULL;
  public $_id = NULL;
  public $_type = NULL;
  public $_name = NULL;
  public $_tplParams = [];
  public $_taxReceipt = NULL;
  public $_userContext = NULL;
  public $_contribution = NULL;

  public function preProcess() {
    $taxReceiptImplements = CRM_Utils_Hook::availableHooks('civicrm_validateTaxReceipt');
    $taxReceiptImplements = count($taxReceiptImplements);
    if (empty($taxReceiptImplements)) {
      CRM_Core_Error::fatal(ts('Tax receipt module not found.'));
    }
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_name = 'taxreceipt_'.$this->_id;
    $this->_type = CRM_Utils_Request::retrieve('type', 'String', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
 
    $breadcrumb = [
      [
				'title' => ts('View Contribution'),
				'url' => CRM_Utils_System::url('civicrm/contact/view/contribution', "reset=1&action=view&context=$context&selectedChild=contribute&cid=$this->_contactId&id=$this->_id"),
      ]
		];
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $this->_id;
    if($contribution->find(TRUE)) {
      $this->_contribution = $contribution;
      if ($contribution->total_amount <= 0) {
         return CRM_Core_Error::statusBounce(ts('Contribution amount must be greater than %1', [1 => 0]));
      }
      $this->assign('trxn_id', $contribution->trxn_id);
      CRM_Utils_Hook::prepareTaxReceipt($this->_id, $this->_tplParams, $this->_taxReceipt, $contribution);
      if (!empty($this->_tplParams)) {
        // assign these element
        $this->assign($this->_tplParams);
      }

      // we needs taxReceipt have receipt_info, receipt_status, receipt_message
      if ($this->_taxReceipt['receipt_status']) {
        if (!empty($this->_taxReceipt['invoice_number'])) {
          $this->_name = 'taxreceipt_'.$this->_taxReceipt['invoice_number'];
        }
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
    // just for display error message when issue tax receipt
    $this->addElement('hidden', 'error_placeholder', '');
    $createButton = $printButton = FALSE;
    $valid = CRM_Utils_Hook::validateTaxReceipt($this->_id, $this->_taxReceipt);

    // if tax receipt not validated, display create button let user create again.
    if (!empty($valid['create'])) {
      $createButton = TRUE;
    }
    if (!empty($valid['print'])) {
      $printButton = TRUE;
    }

    $config = CRM_Core_Config::singleton();
    if ($config->taxReceiptPaper && $createButton) {
      // force paper receipt
      $this->add('checkbox', 'tax_receipt_paper', ts('Use paper receipt'));
    }

    $button = [];
    $button['create'] = [
      'type' => 'next',
      'name' => ts('Create Tax Receipt'),
      'isDefault' => TRUE,
    ];
    $button['print'] = [
      'type' => 'print',
      'name' => ts('Print Tax Receipt'),
      'js' => ['disabled' => 'disabled'],
    ];
    $button['pdf'] = [
      'type' => 'pdf',
      'name' => ts('PDF'),
      'js' => ['disabled' => 'disabled'],
    ];
    if (!$createButton) {
      $button['create']['js'] = ['disabled' => 'disabled'];
    }
    if ($printButton) {
      $printUrl = CRM_Utils_System::url('civicrm/contribute/taxreceipt', "reset=1&id={$this->_id}&cid={$this->_contactId}&snippet=2", FALSE, NULL, FALSE);
      $button['print']['js'] = [
        'onclick' => 'window.open("'.$printUrl.'"); return false;',
      ];
      $pdfUrl = CRM_Utils_System::url('civicrm/contribute/taxreceipt', "reset=1&id={$this->_id}&cid={$this->_contactId}&snippet=3", FALSE, NULL, FALSE);
      $button['pdf']['js'] = [
        'onclick' => 'window.open("'.$pdfUrl.'"); return false;',
      ];
    }
    if (!empty($button)) {
      $this->addButtons($button);
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
