<?php

class CRM_Contribute_Form_Payment extends CRM_Core_Form {

  public $_values;
  public $_paymentProcessor;
  public $_id;

  public $_entityId;

  public $_entityTable;
  
  public $_action;

  public $_mode;

  public $_pass;

  public $_paymentProcessors;

  protected $_ids;

  protected $_component;

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
      $this->_mode = ($this->_action & CRM_Core_Action::PREVIEW) ? 'test' : 'live';
      $pass = TRUE;

      $this->_ids = [];
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
        $details = CRM_Contribute_BAO_Contribution::getComponentDetails([$contribution_id]);
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
          $this->_mode = $ids['is_test'] ? 'test' : 'live';
        }
      }

      // check status and end date
      $state = $this->controller->_actionName[0];
      if(!empty($this->_ids) && $state != 'ThankYou'){
        $available = CRM_Contribute_BAO_Contribution::checkPaymentAvailable($this->_id, $this->_ids, $this);
        if($available === FALSE){
           return CRM_Core_Error::statusBounce(ts('This payment cannot be made at the moment. If you have any questions, please contact the site administrator.'));
        }
        else{
          $this->_paymentProcessors = $this->get('paymentProcessors');
          $this->_paymentProcessor = $this->get('paymentProcessor');
          if(!count($this->_paymentProcessors)){
             return CRM_Core_Error::statusBounce(ts("We don't have available method for this payment."));
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
      $this->set('entityId', $this->_entityId);
      $this->set('entityTable', $this->_entityTable);

      $this->_pass = $pass;
    }
  }
}
