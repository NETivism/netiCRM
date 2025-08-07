<?php
class CRM_Contribute_Page_Receipt extends CRM_Core_Page{

  public $_id;
  public $_permission = NULL;
  public $_contactId = NULL;
  public $_type = NULL;

  function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_type = CRM_Utils_Request::retrieve('type', 'String', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    if (empty($this->_type)) {
      $config = CRM_Core_Config::singleton();
      if (!empty($config->receiptTypeDefault)) {
        $this->_type = $config->receiptTypeDefault;
      }
    }

    // check logged in url permission

    CRM_Contact_Page_View::checkUserPermission($this);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit contributions')) {
      // demote to view since user does not have edit contrib rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  function run() {
    $this->preProcess();

    // refs #31631, needs hook here
    // but we can't use parent::run because will infinite loop
    // instead, trigger hook manually
    CRM_Utils_Hook::pageRun($this);

    $task = new CRM_Contribute_Form_Task_PDF();
    $task->makeReceipt($this->_id, $this->_type);
    $this->createActivity();

    $download = TRUE;
    $task->makePDF($download);
    return;
  }

  function createActivity() {
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Print Contribution Receipts', 'name');
    if (!empty($activityTypeId)) {
      if (empty($userID)) {
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID');
      }
      $statusId = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
      $receiptId = CRM_Core_DAO::getFieldValue("CRM_Contribute_DAO_Contribution", $this->_id, "receipt_id");
      $subject = $receiptId ? ts('Receipt ID') . " : ".$receiptId : ts('Print Contribution Receipts');
      $activityParams = [
        'activity_type_id' => $activityTypeId,
        'activity_date_time' => date('Y-m-d H:i:s'),
        'source_record_id' => $this->_id,
        'status_id' => $statusId,
        'subject' => $subject,
        'assignee_contact_id' => $this->_contactId,
        'source_contact_id' => $userID,
      ];
      CRM_Activity_BAO_Activity::create($activityParams);
    }
  }
}

