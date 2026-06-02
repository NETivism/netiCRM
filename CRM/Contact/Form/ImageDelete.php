<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Confirm contact image deletion.
 */
class CRM_Contact_Form_ImageDelete extends CRM_Core_Form {

  /**
   * @var int
   */
  protected $cid;

  public function preProcess() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this);
    if (!($action & CRM_Core_Action::DELETE)) {
      throw new CRM_Core_Exception(ts('Only delete is supported'));
    }

    // look for `id` instead of `cid` when it's from a Profile
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (!$this->cid) {
      $this->cid = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    if (empty($this->cid) || !CRM_Contact_BAO_Contact_Permission::allow($this->cid, CRM_Core_Permission::EDIT)) {
      CRM_Utils_System::permissionDenied();
    }
  }

  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function postProcess() {
    CRM_Contact_BAO_Contact::deleteContactImage($this->cid);
    CRM_Core_Session::setStatus(ts('Contact image deleted successfully'), ts('Image Deleted'), 'success');
    CRM_Utils_System::redirect(CRM_Core_Session::singleton()->popUserContext());
  }

}
