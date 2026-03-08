<?php

class CRM_Admin_Form_FromEmailAddress_EmailVerify extends CRM_Admin_Form_FromEmailAddress {

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string The form title.
   */
  public function getTitle() {
    return ts('Verify %1', [1 => ts('Email')]);
  }

  /**
   * Preprocess Form
   *
   * @return void None.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Sets the default values for the form.
   *
   * @return array{} The default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }

  /**
   * Builds the form.
   *
   * @return void None.
   */
  public function buildQuickForm() {
    $this->assign_by_ref('values', $this->_values);

    if ($this->_values['filter'] & self::VALID_EMAIL) {
      $this->assign('email_status', TRUE);
      $this->addButtons(
        [
          ['type' => 'back',
            'name' => ts('<< Previous'),
            'isDefault' => TRUE,
          ],
          ['type' => 'next',
            'name' => ts('Next >>'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
    else {
      $this->assign('email_status', FALSE);
      $this->addButton('refresh', ts('Re-send Validation Email'));
      $this->addButtons(
        [
          [
            'type' => 'jump',
            'name' => ts('Refresh'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
  }

  /**
   * Processes the submitted form values.
   *
   * @return void None.
   */
  public function postProcess() {
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == '_qf_EmailVerify_refresh') {
      CRM_Admin_Form_FromEmailAddress::sendValidationEmail($this->_values['email'], $this->_id);
    }
  }
}
