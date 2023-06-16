<?php

class CRM_Admin_Form_Setting_Security extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Website Security'));

    if (CRM_Core_Permission::check('administer neticrm')) {
      $this->assign('admin', TRUE);
    }
    else {
      $this->addTextarea('trustedHostsPatterns', ts('Trusted Host Settings'), array(
        'placeholder' => ts('Example'). ":" . $_SERVER['HTTP_HOST']
      ));

      $this->addElement('textarea', 'cspRules', ts('Content Security Policy'));
      $this->addElement('textarea', 'cspExcludePath', ts('Exclude path'));

      $config = CRM_Core_Config::singleton();
      if ($config->defaultCSP) {
        $this->assign('defaultCSP', $config->defaultCSP);
      }
      $this->assign('admin', FALSE);
    }

    parent::buildQuickForm();
    $this->addFormRule(array('CRM_Admin_Form_Setting_Security', 'formRule'));
  }

  /**
   * Form rule for security
   *
   * @param array $fields
   *
   * @return bool|array
   */
  public static function formRule($fields) {
    $errors = array();
    if (!empty(trim($fields['cspRules']))) {
      $csp = new CRM_Utils_CSP($fields['cspRules']);
      if (!count($csp->policies)) {
        $errors['cspRules'] = ts('%1 has error on format.', array(1 => 'CSP'));
      }
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    if (!empty($params['cspRules'])) {
      $csp = new CRM_Utils_CSP($params['cspRules']);
      $params['cspRules'] = (string) $csp;
    }

    self::commonProcess($params);
  }

}
