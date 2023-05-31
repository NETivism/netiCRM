<?php

class CRM_Admin_Form_Setting_Security extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Website Security'));

    //add select option
    $label = "匯出Excel檔案加密設定";
    $decryptExcelOptions = array(
      '0' => "不設定密碼",
      '1' => "以該檔案匯出操作者的email作為密碼",
      '2' => "使用通用密碼"
    );
    $this->addRadio('decryptExcelOption', $label, $decryptExcelOptions, NULL, "<br>", FALSE);
    $this->addTextfield('decryptExcelPwd', "通用密碼：", NULL, FALSE);

    parent::buildQuickForm();
  }
}