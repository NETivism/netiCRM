<?php
/**
 * Utility class for generating QR code images via the phpqrcode library.
 *
 * Generates a QR code at construction time and provides methods to output
 * it as an HTTP response, save it to a temporary file, or return it as a
 * base64-encoded data URI.
 */
class CRM_Utils_QRcode {
  /** @var string  Base64-encoded data URI of the generated QR code image. */
  public $_dataImg = '';

  /** @var string  Image format: 'png' or 'jpg'. */
  public $_format = 'png';

  /** @var string  Raw binary image data of the generated QR code. */
  private $_data;

  /**
   * Generate a QR code image for the given string.
   *
   * @param string $string  The data to encode in the QR code.
   * @param string $format  The output image format: 'png' (default) or 'jpg'/'jpeg'.
   */
  public function __construct($string, $format = 'png') {
    require_once 'phpqrcode/phpqrcode.php';

    ob_start();
    switch ($format) {
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

  /**
   * Output the QR code image directly as an HTTP response and exit.
   *
   * Sets the appropriate Content-type header and echoes the raw image data.
   *
   * @return void
   */
  public function img() {
    switch ($this->_format) {
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
  /**
   * Save the QR code image to a temporary file and return the file path.
   *
   * The correct extension (.png or .jpg) is appended automatically.
   *
   * @param string $filename  Base filename (without extension) to save under the CMS temp directory.
   *
   * @return string  The absolute path to the saved image file.
   */
  public function fileImg($filename) {
    switch ($this->_format) {
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

  /**
   * Return the QR code image as a base64-encoded data URI string.
   *
   * @return string  A data URI of the form "data:image/png;base64,..." (or jpeg).
   */
  public function dataImg() {
    switch ($this->_format) {
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
