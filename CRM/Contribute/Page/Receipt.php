<?php

class CRM_Contribute_Page_Receipt extends CRM_Core_Page{
  public $_permission = null;    
  public $_contactId  = null;
  function preProcess( ) {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this );
    $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
    $this->_contactId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true );

    // check logged in url permission
    require_once 'CRM/Contact/Page/View.php';
    CRM_Contact_Page_View::checkUserPermission( $this );

    if ( $this->_permission == CRM_Core_Permission::EDIT && ! CRM_Core_Permission::check( 'edit contributions' ) ) {
      $this->_permission = CRM_Core_Permission::VIEW; // demote to view since user does not have edit contrib rights
      $this->assign( 'permission', 'view' );
    }
  }

  function run( ) {
    $this->preProcess();
    // don't through template
    // send pdf directly
    $output = TRUE;
    $task = new CRM_Contribute_Form_Task_PDF();
    $task->makeReceipt($this->_id);
    $task->makePDF($output);
    return;
  }
}
