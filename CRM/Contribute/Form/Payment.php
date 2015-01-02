<?php

class CRM_Contribute_Form_Payment extends CRM_Core_Form {

  public $_id;

  public $_entityid;

  public $_entityTable;
  
  public $_action;

  public $_mode;

  public $_values;

  protected $_ids;

  protected $_component;

  protected $_paymentProcessors;

  protected $_ppType;


  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';
    $pass = TRUE;
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      $pass = FALSE;
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    $this->_ids = array();
    $session = CRM_Core_Session::singleton();
    $current_contact_id = $session->get('userID');
    $ufid = $session->get('ufID');

    // permission check
    $contribution_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_id = $contribution_id;
    if(empty($current_contact_id) || empty($ufid)){
      $pass = FALSE;
    }
    else{
      $details = CRM_Contribute_BAO_Contribution::getComponentDetails(array($contribution_id));
      $ids = reset($details);
      if(!empty($ids['contact_id'])){
        if($ids['contact_id'] != $current_contact_id){
          if(!CRM_Core_Permission::check('access CiviContribute')){
            $pass = FALSE;
          }
        }
      }
      if(!empty($ids)){
        $this->_component = $ids['component'];
        $this->_ids = $ids;
        $this->_ids['ufid'] = $ufid;
        $this->_ids['current_contact_id'] = $current_contact_id;
      }
    }

    // check status and end date
    if(!empty($this->_ids)){
      $available = CRM_Contribute_BAO_Contribution::checkPaymentAvailable($this->_id, $this->_ids, $this);
      if($available === FALSE){
        CRM_Core_Error::fatal(ts('Payment expired.'));
      }
      else{
        $this->_paymentProcessors = $this->get('paymentProcessors');
        if(!count($this->_paymentProcessors)){
          CRM_Core_Error::fatal(ts("We don't have available method for this payment."));
        }
      }
    }
    else{
      $pass = FALSE;
    }

    // set entity id and entity table
    switch($this->_component){
      case 'event':
        $this->_entityId = $ids['event'];
        $this->_entityTable =  'civicrm_event';
        break;
      case 'contribute':
        $this->_entityId = $ids['page_id'];
        $this->_entityTable = 'civicrm_contribution_page';
        break;
    }

    if(!$pass){
      CRM_Utils_System::notFound();
      CRM_Utils_Ssytem::civiExit();
    }
    else{
      $this->_ppType = CRM_Utils_Array::value('type', $_GET);
      $this->assign('ppType', FALSE);
      if ($this->_ppType) {
        $this->assign('ppType', TRUE);
        return CRM_Core_Payment_ProcessorForm::preProcess($this);
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
    if ($this->_ppType) {
      return CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
    }

    if (!empty($this->_paymentProcessors)) {
      $pps = $this->_paymentProcessors;
      foreach ($pps as $key => & $name) {
        $pps[$key] = $name['name'];
      }
    }

    if (count($pps) >= 1) {
      $this->addRadio('payment_processor', ts('Payment Method'), $pps, NULL, "&nbsp;", TRUE);
    }

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => '>> ' . ts('Change Payment Method'),
          'isDefault' => TRUE,
        ),
      )
    );
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
    $params = $this->controller->exportValues($this->_name);
    $processor = $this->_paymentProcessors[$params['payment_processor']];
    $contrib = CRM_Contribute_BAO_Contribution::copy($this->_id);
    if(!empty($params['payment_processor'])){
      $contrib->payment_processor_id = $params['payment_processor'];
    }
    if(!empty($params['payment_instrument_id'])){
      $contrib->payment_instrument_id = $params['payment_instrument_id'];
    }
    if(!empty($contrib->source)){
      $contrib->source = str_replace(' '. ts('Change Payment Method'), '', $params['source']).' '.ts('Change Payment Method');
    }
    else{
      $contrib->source = ' '.ts('Change Payment Method');
    }
    if(!empty($contrib->invoice_id)){
      $invoice_id = md5(uniqid(rand(), TRUE));
      $contrib->invoice_id = $invoice_id;;
    }
    $contrib->save();
    $payment = &CRM_Core_Payment::singleton($this->_mode, $processor, $this);
    $vars = $payment->prepareTransferCheckoutParams($contrib, $params);

    // TODO: we have to redirect to correct thank you page 
    // maybe create own controller for that
    $payment->doTransferCheckout($vars, $this->_component);
  }

  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }
}
