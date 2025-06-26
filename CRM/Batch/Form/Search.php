<?php

class CRM_Batch_Form_Search extends CRM_Core_Form {

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch', 'label');
    $this->add('text', 'label', ts('Label'), $attributes);

    $batchType = CRM_Core_OptionGroup::values('batch_type');
    $attrs = ['multiple' => 'multiple'];
    $this->addElement('select', 'type_id', ts('Batch Type'), $batchType, $attrs);

    $batchStatus = CRM_Core_OptionGroup::values('batch_status');
    $attrs = ['multiple' => 'multiple'];
    $this->addElement('select', 'status_id', ts('Batch Status'), $batchStatus, $attrs);

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    if (!empty($params)) {
      $fields = ['title', 'type_id', 'status_id'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}
