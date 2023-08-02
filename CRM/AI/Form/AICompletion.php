<?php
class CRM_AI_Form_AICompletion extends CRM_Core_Form {

  /**
   * The completion id when editing item
   *
   * @var int
   */
  protected $_id;

  /**
   * Completion item values
   *
   * @var array
   */
  protected $_itemValues = array();

  function preProcess() {
    $this->_id = $this->get('id');
    if (empty($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    $params['id'] = $this->_id;
    CRM_Core_DAO::commonRetrieve('CRM_AI_DAO_AICompletion', $params, $this->_itemValues);
    $details = CRM_Contact_BAO_Contact::getContactDetails($this->_itemValues['contact_id']);
    $this->_itemValues['display_name'] = $details[0];
    $this->assign('item', $this->_itemValues);

    if ($this->_itemValues['template_title']) {
      $pageTitle = ts('AI Copywriter') .' - '.$this->_itemValues['template_title'];
      CRM_Utils_System::setTitle('pageTitle', $pageTitle);
    }

    $this->addFormRule(array('CRM_AI_Form_AICompletion', 'formRule'), $this);
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();
    if (!empty($fields['is_template']) && empty($fields['template_title'])) {
      $errors['template_title'] = ts('%1 is a required field.', array(1 => ts('Template Title')));
    }
    return $errors;
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults['template_title'] = $this->_itemValues['template_title'];
    $defaults['is_template'] = $this->_itemValues['is_template'];

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addCbx('is_template', ts('Enable this message template'));
    $this->addTextfield('template_title', ts('Template Title'), array('size' => 100));

    $this->addButtons(array(
        array('type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
      $data = array();
      $data = array(
        'id' => $this->_id,
        'is_template' => !empty($params['is_template']) ? 1 : 0,
        'template_title' => $params['template_title'],
      );
      CRM_AI_BAO_AICompletion::setTemplate($data);
      CRM_Core_Session::setStatus(ts("The %1 '%2' has been saved.", array(
        1 => ts('Template Title'),
        2 => $params['template_title'],
      )));
    }

  }
}

