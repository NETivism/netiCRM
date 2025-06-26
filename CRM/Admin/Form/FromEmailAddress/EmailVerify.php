<?php

class CRM_Admin_Form_FromEmailAddress_EmailVerify extends CRM_Admin_Form_FromEmailAddress {
  
  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Verify %1', [1 => ts('Email')]);
  }

  /**
   * Preprocess Form
   *
   * @return void
   */
  function preProcess() {
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   */
  function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }

  /**
   * Function to actually build the form
   */
  public function buildQuickForm() {
    $this->assign_by_ref('values', $this->_values);

    if ($this->_values['filter'] & self::VALID_EMAIL) {
      $this->assign('email_status', TRUE);
      $this->addButtons([
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
      $this->addButtons([
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
   * Function to process the form
   */
  public function postProcess() {
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == '_qf_EmailVerify_refresh') {
      CRM_Admin_Form_FromEmailAddress::sendValidationEmail($this->_values['email'], $this->_id);
    }
  }
}
