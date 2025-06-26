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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Page for displaying list of current batches
 */
class CRM_Batch_Form_Batch extends CRM_Core_Form {
  protected $_batch = NULL;
  function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $defaults = [];
    $params = [
      'id' => $id
    ];
    $batch = CRM_Batch_BAO_Batch::retrieve($params, $defaults);
    if ($batch) {
      $this->_batch = $batch;
      CRM_Utils_System::setTitle(ts('Edit').' - '.$this->_batch->label);
    }
  }
  public function buildQuickForm() {
    $ele = $this->add('text', 'label', ts('Label'));
    $ele->freeze();
    $this->add('textarea', 'description', ts('Description'));
    $batchStatus = CRM_Batch_BAO_Batch::batchStatus();
    $cancelStatus = $batchStatus['Canceled'];
    $batchStatusLabel = CRM_Core_OptionGroup::values('batch_status');
    $this->addSelect('status_id', ts('Status'), [
      $this->_batch->status_id => $batchStatusLabel[$this->_batch->status_id], // current status
      $cancelStatus => $batchStatusLabel[$cancelStatus],
    ], NULL, TRUE);
    $this->addButtons([
      ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);

    $defaults = [];
    $defaults["label"] = $this->_batch->label;
    $defaults["description"] = $this->_batch->description;
    $defaults["status_id"] = $this->_batch->status_id;
    $this->setDefaults($defaults);
  }
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    unset($params['qfKey']);
    unset($params['pageKey']);
    unset($params['label']);
    $params['id'] = $this->_batch->id;
    CRM_Batch_BAO_Batch::create($params);
  }
}