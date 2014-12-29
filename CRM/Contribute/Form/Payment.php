<?php

class CRM_Contribute_Form_Payment extends CRM_Core_Form {

  public $_id;

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
    $this->_ppType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('ppType', FALSE);
    if ($this->_ppType) {
      $this->assign('ppType', TRUE);
      return CRM_Core_Payment_ProcessorForm::preProcess($this);
    }

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

    // check payment processors
    switch($this->_component){
      case 'event':
        break;
      case 'contribute':
        break;
    }

    if(!$pass){
      CRM_Utils_System::notFound();
      CRM_Utils_Ssytem::civiExit();
    }
  }

  function setDefaultValues() {
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

/*
    if (CRM_Utils_Array::value('is_pay_later', $this->_values['event']) &&
      ($this->_allowConfirmation || (!$this->_requireApproval && !$this->_allowWaitlist))
    ) {
      $pps[0] = $this->_values['event']['pay_later_text'];
    }
*/

    if (count($pps) >= 1) {
      $this->addRadio('payment_processor', ts('Payment Method'), $pps, NULL, "&nbsp;", TRUE);
    }

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Continue >>'),
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
  }

  
}
