<?php

class CRM_Contribute_Form_Payment extends CRM_Core_Form {

  public $_id;

  public $_entityId;

  public $_entityTable;
  
  public $_action;

  public $_mode;

  public $_pass;

  protected $_ids;

  protected $_component;

  protected $_paymentProcessors;

  protected $_ppType;

  protected $_contrib;

  protected $_params;


  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_values = $this->get('values');
    $this->_params = $this->get('params');
    if(!$this->_pass){
      $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
      $this->_mode = ($this->_action == 1024) ? 'test' : 'live';
      $pass = TRUE;

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
      $state = $this->controller->_actionName[0];
      if(!empty($this->_ids) && $state != 'ThankYou'){
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
          $this->_entityTable = 'civicrm_event';
          break;
        case 'contribute':
          $this->_entityId = $ids['page_id'];
          $this->_entityTable = 'civicrm_contribution_page';
          break;
      }

      $this->_pass = $pass;
    }
  }
}
