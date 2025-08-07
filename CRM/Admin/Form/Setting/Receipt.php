<?php

/**
 * This class generates form components for CiviContribute
 */
class CRM_Admin_Form_Setting_Receipt extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Contribution Receipt'));
    $this->addElement('file', 'receiptLogo', ts('Logo'));
    $this->addElement('text', 'receiptPrefix', ts('Prefix of Receipt ID'));
    $this->addElement('textarea', 'receiptDescription', ts('Description of Receipt Footer'));
    $this->addElement('textarea', 'receiptOrgInfo', ts('Organization info'));

    $fields = CRM_Core_BAO_CustomField::getFields('Contribution');
    $option = [0 => ts('-- Select --')];
    foreach ($fields as $custom_id => $f) {
      $option[$custom_id] = $f['label'];
    }
    $this->addElement('select', 'receiptYesNo', ts('Field to request receipt'), $option);
    $this->addElement('select', 'receiptTitle', ts('Field for receipt title'), $option);
    $this->addElement('select', 'receiptSerial', ts('Field for receipt serial number'), $option);
    $this->addElement('select', 'receiptDonorCredit', ts('Field for the name used of donor acknowledgement'), $option);

    // refs #42235, add customDonorCredit options
    $donorCreditOptions = [
      ts('Full Name') => 'full_name',
      ts('Partial Name') => 'partial_name',
      ts('Custom Name') => 'custom_name',
      ts("I don't agree to disclose name") => 'anonymous'
    ];
    $this->addCheckBox('customDonorCredit', ts('Donor Credit Name Options'), $donorCreditOptions);

    $this->addElement('text', 'anonymousDonorCreditDefault', ts("Default name when donor doesn't agree to disclose"));

    // refs #28471, switch to auto send receipt on email
    $haveAttachReceiptOption = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if (!empty($haveAttachReceiptOption)) {
      $this->addCheckBox('receiptEmailAuto', ts('Email Receipt'), ['' => 1]);
    }

    $addressFields = [
      'is_primary' => ts('Is Primary Address'),
      'is_billing' => ts('Is Billing Address'),
    ];
    $this->addElement('select', 'receiptAddrType', ts('Address Fields'), $addressFields);

    // default receipt type setting
    $receiptTypes = CRM_Contribute_Form_Task_PDF::getPrintingTypes();
    $this->addElement('select', 'receiptTypeDefault', ts('Default Receipt Type'), [ '' => ts('-- Select --')] + $receiptTypes);

    // https://github.com/NETivism/netiCRM/blob/develop/CRM/Contribute/Form/ManagePremiums.php#L291-L321
    $this->add('file', 'uploadBigStamp', ts('The stamp of organization.'));
    $this->add('file', 'uploadSmallStamp', ts('The stamp of the person in charge.'));
    $config = CRM_Core_Config::singleton();
    $this->controller->addActions($config->imageUploadDir, ['uploadBigStamp', 'uploadSmallStamp', 'receiptLogo']);

    if($config->imageBigStampName){
      $this->assign('imageBigStampUrl', $config->imageUploadURL . $config->imageBigStampName);
    }
    if($config->imageSmallStampName){
      $this->assign('imageSmallStampUrl', $config->imageUploadURL . $config->imageSmallStampName);
    }
    $receiptLogo = $config->receiptLogo;
    if ($receiptLogo) {
      if (preg_match('/^https?:\/\//i', $receiptLogo)  || substr($receiptLogo, 0, 13) === '/var/www/html') {
        $this->assign('receiptLogoUrl', $receiptLogo);
      }
      else if ($receiptLogo) {
        $this->assign('receiptLogoUrl', $config->imageUploadURL . $receiptLogo);
      }
    }

    $this->assign('stampDocUrl', CRM_Utils_System::docURL2('Receipt Stamp', TRUE));
    $this->add('hidden', 'deleteBigStamp');
    $this->add('hidden', 'deleteSmallStamp');
    $this->add('hidden', 'deleteReceiptLogo');

    $displayLegalIDOptions = ['complete' => ts('Complete display'), 'partial' => ts('Partial hide'), 'hide' => ts('Complete hide')];
    $this->addRadio('receiptDisplayLegalID', ts('The way displays legal ID in receipt.'), $displayLegalIDOptions);

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/receipt', 'reset=1'));

    $check = TRUE;
    parent::buildQuickForm($check);

    // Refs #38829, Add receipt Email Encryption option
    $this->add('checkbox', 'receiptEmailEncryption', ts('Email Receipt Password'));
    $this->addElement('text', 'receiptEmailEncryptionText', ts('Email Receipt Password Explanation Text'));
    $this->addFormRule([get_class($this), 'formRule']);
  }

  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $defaults['deleteBigStamp'] = '';
    $defaults['deleteSmallStamp'] = '';
    $defaults['deleteReceiptLogo'] = '';
    if (empty($defaults['receiptDisplayLegalID'])) {
      $defaults['receiptDisplayLegalID'] = 'complete';
    }
    $defaults['receiptDescription'] = htmlspecialchars_decode($defaults['receiptDescription']);
    $defaults['receiptOrgInfo'] = htmlspecialchars_decode($defaults['receiptOrgInfo']);

    $config = CRM_Core_Config::singleton();

    // refs #42235, compatibility handling for old sites that initially don't have customDonorCredit
    // default values for new sites are set in CRM/Core/Config/Variables.php
    if (!isset($config->customDonorCredit) || !is_array($config->customDonorCredit)) {
      if (isset($config->forbidCustomDonorCredit)) {
        $forbidCustomDonorCredit = !empty($config->forbidCustomDonorCredit) ? 1 : 0;
        $defaults['customDonorCredit'] = [
          'full_name' => 1,
          'partial_name' => 1
        ];

        if ($forbidCustomDonorCredit == 0) {
          $defaults['customDonorCredit']['custom_name'] = 1;
        }
      } else {
        $defaults['customDonorCredit'] = [
          'full_name' => 1,
          'partial_name' => 1,
          'custom_name' => 1
        ];
      }
    }

    if (!empty($defaults['customDonorCredit']['anonymous']) && empty($defaults['anonymousDonorCreditDefault'])) {
      $defaults['anonymousDonorCreditDefault'] = ts('Anonymous');
    }

    return $defaults;
  }

  // FROM : /CRM/Contribute/Form/ManagePremiums.php#L291-L321
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    $params = $this->controller->exportValues($this->_name);
    $uploadBigStamp = CRM_Utils_Array::value('uploadBigStamp', $params);
    $uploadBigStamp = $uploadBigStamp['name'];

    $uploadSmallStamp = CRM_Utils_Array::value('uploadSmallStamp', $params);
    $uploadSmallStamp = $uploadSmallStamp['name'];

    $uploadReceiptLogo = CRM_Utils_Array::value('receiptLogo', $params);
    $uploadReceiptLogo = $uploadReceiptLogo['name'];

    $deleteBigStamp = CRM_Utils_Array::value('deleteBigStamp', $params);
    $deleteSmallStamp = CRM_Utils_Array::value('deleteSmallStamp', $params);
    $deleteReceiptLogo = CRM_Utils_Array::value('deleteReceiptLogo', $params);
    unset($params['deleteBigStamp']);
    unset($params['deleteSmallStamp']);
    unset($params['deleteReceiptLogo']);

    if($deleteBigStamp){
      $params['imageBigStampName'] = '';
    }
    if($deleteSmallStamp){
      $params['imageSmallStampName'] = '';
    }
    if($deleteReceiptLogo){
      $params['receiptLogo'] = '';
    }

    // to check wether GD is installed or not
    $gdSupport = CRM_Utils_System::getModuleSetting('gd', 'GD Support');
    if($gdSupport) {
      if ($uploadBigStamp) {
        $error = false;
        $params['imageBigStampName'] = $this->_resizeImage($uploadBigStamp, "_full", 800, 350);
      }
      if ($uploadSmallStamp) {
        $error = false;
        $params['imageSmallStampName'] = $this->_resizeImage($uploadSmallStamp, "_full", 800, 200);
      }
      if ($uploadReceiptLogo) {
        $error = false;
        $params['receiptLogo'] = $this->_resizeImage($uploadReceiptLogo, "_full", 800, 200);
      }
    }else{
      $error = true;
    }

    // refs #42235, compatibility handling for old sites
    if (!empty($config->forbidCustomDonorCredit)) {
      $configParams = get_object_vars($config);
      $configParams['forbidCustomDonorCredit'] = 0;
      CRM_Core_BAO_ConfigSetting::add($configParams);
    }

    if (empty($params['customDonorCredit']['anonymous'])) {
      $params['anonymousDonorCreditDefault'] = '';
    }

    // refs #28471, switch to auto send receipt on email
    $haveAttachReceiptOption = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if (!empty($haveAttachReceiptOption) && empty($params['receiptEmailAuto'])) {
      $params['receiptEmailAuto'] = FALSE;
    }
    if (empty($params['receiptEmailEncryption'])) {
      $params['receiptEmailEncryption'] = FALSE;
    }

    parent::commonProcess($params);
  }

  static function formRule($fields, $files, $self) {
    $errors = [];
    if ((!empty($fields['receiptDisplayLegalID']) && $fields['receiptDisplayLegalID'] !== 'complete') && (!empty($fields['receiptEmailEncryption']) && $fields['receiptEmailEncryption'] === '1')) {
        $errors['receiptEmailEncryption'] = ts('When the legal ID display option is not set to complete display, email receipt encryption cannot be enabled.');
    }
    return empty($errors) ? true : $errors;
  }

  /**
   * Resize a premium image to a different size
   *
   * @access private
   *
   * @param string $filename
   * @param string $resizedName
   * @param $width
   * @param $height
   *
   * @return Path to image
   */
  private function _resizeImage($filename, $resizedName, $width, $height ) {
    // figure out the new filename
    $pathParts = pathinfo($filename);
    $newFilename = $pathParts['dirname']."/".$pathParts['filename'].$resizedName.".".$pathParts['extension'];

    // get image about original image
    $imageInfo = getimagesize($filename);
    $widthOrig = $imageInfo[0];
    $heightOrig = $imageInfo[1];

    if($widthOrig > $width){
      $widthNew = $width;
      $heightNew = $heightOrig * $widthNew / $widthOrig;
    }else{
      $widthNew = $widthOrig;
      $heightNew = $heightOrig;
    }
    $image = imagecreatetruecolor($widthNew, $heightNew);
    
    if($imageInfo['mime'] == 'image/gif') {
      $source = imagecreatefromgif($filename);
    }
    elseif($imageInfo['mime'] == 'image/png') {
      $source = imagecreatefrompng($filename);
    }
    else {
      $source = imagecreatefromjpeg($filename);
    }

    
    // resize
    ImageAlphaBlending($image,true);
    ImageSaveAlpha($image,true);
    $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $color);

    imagecopyresampled($image, $source, 0, 0, 0, 0, $widthNew, $heightNew, $widthOrig, $heightOrig);

    // save the resized image
    $fp = fopen($newFilename, 'w+');
    ob_start();
    imagepng($image);
    $image_buffer = ob_get_contents();
    ob_end_clean();
    ImageDestroy($image);
    fwrite($fp, $image_buffer);
    rewind($fp);
    fclose($fp);

    // return the URL to link to
    $config = CRM_Core_Config::singleton();
    return basename($newFilename);
  }
}