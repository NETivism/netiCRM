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
    $config = CRM_Core_Config::singleton();

    $label = ts("Export excel file encryption settings");
    $decryptExcelOptions = [
      '0' => ts("No password set"),
      '1' => ts("Use the email of the exporting user as the password"),
      '2' => ts("Use a generic password")
    ];
    $this->addRadio('decryptExcelOption', $label, $decryptExcelOptions, NULL, "<br>", FALSE);
    $this->addTextfield('decryptExcelPwd', ts("Generic Password"), NULL, FALSE);

    $this->addTextarea('trustedHostsPatterns', ts('Trusted Host Settings'), [
      'data-host' => $_SERVER['HTTP_HOST']
    ]);
    $this->assign('current_host', $_SERVER['HTTP_HOST']);

    $this->addElement('textarea', 'cspRules', ts('Content Security Policy'));
    #$this->addElement('textarea', 'cspExcludePath', ts('Exclude path'));

    $config = CRM_Core_Config::singleton();
    if ($config->defaultCSP) {
      $this->assign('defaultCSP', $config->defaultCSP);
    }

    parent::buildQuickForm();
    $this->addFormRule(['CRM_Admin_Form_Setting_Security', 'formRule']);
  }

  /**
   * Form rule for security
   *
   * @param array $fields
   *
   * @return bool|array
   */
  public static function formRule($fields) {
    $errors = [];
    if (!empty(trim($fields['cspRules']))) {
      $csp = new CRM_Utils_CSP($fields['cspRules']);
      if (!count($csp->policies)) {
        $errors['cspRules'] = ts('%1 has error on format.', [1 => 'CSP']);
      }
    }
    return $errors;
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (!isset($defaults['decryptExcelOption'])) {
      $defaults['decryptExcelOption'] = 0;
    }
    return $defaults;
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $params = $this->controller->exportValues($this->_name);

    $decryptExcelOptions = [
      '0' => ts("No password set"),
      '1' => ts("Use the email of the exporting user as the password"),
      '2' => ts("Use a generic password")
    ];
    $currentOption = CRM_Utils_Array::value('decryptExcelOption', $params);
    $currentPwd = CRM_Utils_Array::value('decryptExcelPwd', $params);
    if($currentOption != "2"){
      unset($params['decryptExcelPwd']);
      $params['decryptExcelPwd'] = '';
    }


    if (!empty($params['cspRules'])) {
      $csp = new CRM_Utils_CSP($params['cspRules']);
      $params['cspRules'] = (string) $csp;
    }
    parent::commonProcess($params);

    $config = CRM_Core_Config::singleton();
    $previousOption = $config->decryptExcelOption;
    $previousPwd = $config->decryptExcelPwd;
    $serial = CRM_REQUEST_TIME;
    if ($currentOption != $previousOption) {
      $optionChange = ts("Settings option changed from %1 to %2." , [1 => $decryptExcelOptions[$previousOption], 2 => $decryptExcelOptions[$currentOption]]);
      $data = [
        'event' => ts("Export excel file encryption settings option Changed"),
        'log' => $optionChange,
      ];
      CRM_Core_BAO_Log::audit($serial, 'civicrm.security.option', json_encode($data));
    }
    if ($currentPwd !== $previousPwd) {
      $data = [
        'event' => ts("Export excel file encryption settings password Changed"),
      ];
      CRM_Core_BAO_Log::audit($serial, 'civicrm.security.pwd', json_encode($data));
    }
  }
}
