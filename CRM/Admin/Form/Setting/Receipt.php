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
    $this->addElement('text', 'receiptLogo', ts('Logo'));
    $this->addElement('text', 'receiptPrefix', ts('Prefix of Receipt ID'));
    $this->addElement('textarea', 'receiptDescription', ts('Description of Receipt Footer'));
    $this->addElement('textarea', 'receiptOrgInfo', ts('Organization info'));

    $fields = CRM_Core_BAO_CustomField::getFields('Contribution');
    $option = array(0 => ts('-- Select --'));
    foreach ($fields as $custom_id => $f) {
      $option[$custom_id] = $f['label'];
    }
    $this->addElement('select', 'receiptYesNo', ts('Field to request receipt'), $option);
    $this->addElement('select', 'receiptTitle', ts('Field for receipt title'), $option);
    $this->addElement('select', 'receiptSerial', ts('Field for receipt serial number'), $option);
    $this->addElement('select', 'receiptDonorCredit', ts('Field for the name used of donor acknowledgement'), $option);
    $this->add('checkbox', 'forbidCustomDonorCredit', ts('Forbid above name field from being customized'));

    // refs #28471, switch to auto send receipt on email
    $haveAttachReceiptOption = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if (!empty($haveAttachReceiptOption)) {
      $this->addCheckBox('receiptEmailAuto', ts('Email Receipt'), array('' => 1));
    }

    $addressFields = array(
      'is_primary' => ts('Is Primary Address'),
      'is_billing' => ts('Is Billing Address'),
    );
    $this->addElement('select', 'receiptAddrType', ts('Address Fields'), $addressFields);

    // default receipt type setting
    $receiptTypes = CRM_Contribute_Form_Task_PDF::getPrintingTypes();
    $this->addElement('select', 'receiptTypeDefault', ts('Default Receipt Type'), array( '' => ts('-- Select --')) + $receiptTypes);

    // https://github.com/NETivism/netiCRM/blob/develop/CRM/Contribute/Form/ManagePremiums.php#L291-L321
    $this->add('file', 'uploadBigStamp', ts('The stamp of organization.'));
    $this->add('file', 'uploadSmallStamp', ts('The stamp of the person in charge.'));
    $config = CRM_Core_Config::singleton();
    $this->controller->addActions($config->imageUploadDir, array('uploadBigStamp', 'uploadSmallStamp'));

    if($config->imageBigStampName){
      $this->assign('imageBigStampUrl', $config->imageUploadURL . $config->imageBigStampName);
    }
    if($config->imageSmallStampName){
      $this->assign('imageSmallStampUrl', $config->imageUploadURL . $config->imageSmallStampName);
    }

    $this->assign('stampDocUrl', CRM_Utils_System::docURL2('Receipt Stamp', TRUE));
    $this->add('hidden', 'deleteBigStamp');
    $this->add('hidden', 'deleteSmallStamp');

    $displayLegalIDOptions = array('complete' => ts('Complete display'), 'partial' => ts('Partial hide'), 'hide' => ts('Complete hide'));
    $this->addRadio('receiptDisplayLegalID', ts('The way displays legal ID in receipt.'), $displayLegalIDOptions);

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/receipt', 'reset=1'));

    $check = TRUE;
    parent::buildQuickForm($check);

    // Refs #38829, Add receipt Email Encryption option
    $this->add('checkbox', 'receiptEmailEncryption', ts('Email Receipt Password'));
    $this->addElement('text', 'receiptEmailEncryptionText', ts('Email Receipt Password Explanation Text'));
  }

  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $defaults['deleteBigStamp'] = '';
    $defaults['deleteSmallStamp'] = '';
    if (empty($defaults['receiptDisplayLegalID'])) {
      $defaults['receiptDisplayLegalID'] = 'complete';
    }
    $defaults['receiptDescription'] = htmlspecialchars_decode($defaults['receiptDescription']);
    $defaults['receiptOrgInfo'] = htmlspecialchars_decode($defaults['receiptOrgInfo']);
    return $defaults;
  }

  // FROM : /CRM/Contribute/Form/ManagePremiums.php#L291-L321
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $uploadBigStamp = CRM_Utils_Array::value('uploadBigStamp', $params);
    $uploadBigStamp = $uploadBigStamp['name'];

    $uploadSmallStamp = CRM_Utils_Array::value('uploadSmallStamp', $params);
    $uploadSmallStamp = $uploadSmallStamp['name'];

    $deleteBigStamp = CRM_Utils_Array::value('deleteBigStamp', $params);
    $deleteSmallStamp = CRM_Utils_Array::value('deleteSmallStamp', $params);
    unset($params['deleteBigStamp']);
    unset($params['deleteSmallStamp']);

    if($deleteBigStamp){
      $params['imageBigStampName'] = '';
    }
    if($deleteSmallStamp){
      $params['imageSmallStampName'] = '';
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
    }else{
      $error = true;
    }

    if (empty($params['forbidCustomDonorCredit'])) {
      $params['forbidCustomDonorCredit'] = FALSE;
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

