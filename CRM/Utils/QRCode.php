<?php
class CRM_Utils_QRcode {
  public $_dataImg = '';
  public $_format = 'png';
  private $_data;
  
  function __construct($string, $format = 'png') {
    require_once 'phpqrcode/phpqrcode.php';

    ob_start();
    switch($format) {
      case 'jpg':
      case 'jpeg':
        $this->_format = 'jpg';
        echo QRcode::jpg($string, FALSE, QR_ECLEVEL_L, 4);
        break;
      case 'png':
      default:
        $this->_format = 'png';
        echo QRcode::png($string, FALSE, QR_ECLEVEL_L, 4);
        break;
    }
    $this->_data = ob_get_contents();
    ob_end_clean();
  }

  function img(){
    switch($this->_format) {
      case 'jpg':
        Header("Content-type: image/jpeg");
        echo $this->_data;
        CRM_Utils_System::civiExit();
        break;
      case 'png':
        Header("Content-type: image/png");
        echo $this->_data;
        CRM_Utils_System::civiExit();
        break;
    }
  }
  function fileImg($filename) {
    switch($this->_format) {
      case 'jpg':
        $filename = preg_replace('/\.jpg$/i', '', $filename).'.jpg';
        break;
      case 'png':
        $filename = preg_replace('/\.png$/i', '', $filename).'.png';
        break;
    }
    $filepath = CRM_Utils_System::cmsDir('temp').'/'.$filename;
    file_put_contents($filepath, $this->_data);
    return $filepath;
  }

  function dataImg() {
    switch($this->_format) {
      case 'jpg':
        $this->_dataImg = 'data:image/jpeg;base64,'.base64_encode($this->_data);
        break;
      case 'png':
        $this->_dataImg = 'data:image/png;base64,'.base64_encode($this->_data);
        break;
    }
    return $this->_dataImg;
  }
}