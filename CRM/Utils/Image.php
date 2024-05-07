<?php
/**
 * class to provide simple static functions for image handling
 */
class CRM_Utils_Image {

  /**
   * Source file full path
   *
   * @var string
   */
  private $_source;

  /**
   * Destination file full path
   *
   * @var string
   */
  private $_destination;

  /**
   * Validation success of not
   */
  private $_prepared;

  /**
   * Image info
   *
   * @var array
   */
  private $_info;

  /**
   * Convert params detect by internal
   *
   * @var array
   */
  private $_convert;

  /**
   * Image resource created by GD
   *
   * @var GdImage
   */
  private $_resource;

  /**
   * Temp image resource created by GD
   *
   * @var GdImage
   */
  private $_tmp;

  /**
   * Constructor
   *
   * @param string $source
   * @param string $destination
   * @param integer $quality
   * @param boolean $replace
   *
   * @return void
   */
  function __construct($source, $destination, $quality = 90, $replace = FALSE) {
    $this->_prepared = FALSE;
    if (!is_file($source) && !is_uploaded_file($source)) {
      return;
    }
    if (is_file($destination) && $source !== $destination) {
      if (!$replace) {
        $destination = CRM_Utils_File::existsRename($destination);
      }
    }
    if (empty($quality)) {
      $quality = 90;
    }
    $this->_source = $source;
    $this->_destination = $destination;

    $data = @getimagesize($this->_source);
    if (isset($data) && is_array($data)) {
      $extensions = array('1' => 'gif', '2' => 'jpg', '3' => 'png');
      $extension = isset($extensions[$data[2]]) ? $extensions[$data[2]] : '';
      $aspect = $data[1] / $data[0];
      $this->_info = array(
        'width' => $data[0],
        'height' => $data[1],
        'extension' => $extension,
        'mime_type' => $data['mime'],
        'aspect' => $aspect
      );
      $this->_convert = array(
        'quality' => $quality,
      );
      $this->_prepared = TRUE;
    }
  }

  /**
   * Get dimension calc result of image
   *
   * @param int $width
   * @param int $height
   * @param bool $upscale
   *
   * @return array [width, height]
   */
  private function getConvertDimensions($width, $height, $upscale) {
    $aspect = $this->_info['aspect'];
    $widthCal = $width;
    $heightCal = $height;

    // get dimension
    if (($width && !$height) || ($width && $height && $aspect < $height / $width)) {
      $heightCal = (int) round($width * $aspect);
    }
    else {
      $widthCal = (int) round($height / $aspect);
    }
    if (!$upscale) {
      if ($this->_info['width'] >= $width && $this->_info['height'] >= $height) {
        $width = (int) round($widthCal);
        $height = (int) round($heightCal);
      }
      else {
        $this->_convert['skip'] = TRUE;
        $width = $this->_info['width'];
        $height = $this->_info['height'];
      }
    }
    else {
      $width = (int) round($widthCal);
      $height = (int) round($heightCal);
    }
    return array($width, $height);
  }

  /**
   * Create GD resource
   *
   * @return void
   */
  private function gdCreateResource() {
    // create image gd resource
    $extension = str_replace('jpg', 'jpeg', $this->_info['extension']);
    $function = 'imagecreatefrom' . $extension;
    if (function_exists($function) && $this->_resource = $function($this->_source)) {
      if (!imageistruecolor($this->_resource)) {
        // Convert indexed images to truecolor, copying the image to a new
        // truecolor resource, so that filters work correctly and don't result
        // in unnecessary dither.
        $this->gdCreateTmp($this->_info['width'], $this->_info['height']);
        if ($this->_tmp) {
          imagecopy($this->_tmp, $this->_resource, 0, 0, 0, 0, imagesx($this->_tmp), imagesy($this->_tmp));
          imagedestroy($this->_resource);
          $this->_resource = $this->_tmp;
        }
      }
    }
  }

  /**
   * Create GD temp canvas
   *
   * @param int $width
   * @param int $height
   *
   * @return void
   */
  private function gdCreateTmp($width, $height) {
    unset($this->_tmp);
		$res = @imagecreatetruecolor($width, $height);

		if ($this->_info['extension'] == 'gif') {
			// Find out if a transparent color is set, will return -1 if no
			// transparent color has been defined in the image.
			$transparent = imagecolortransparent($this->_resource);

			if ($transparent >= 0) {
				// Find out the number of colors in the image palette. It will be 0 for
				// truecolor images.
				$palette_size = imagecolorstotal($this->_resource);
				if ($palette_size == 0 || $transparent < $palette_size) {
					// Set the transparent color in the new resource, either if it is a
					// truecolor image or if the transparent color is part of the palette.
					// Since the index of the transparency color is a property of the
					// image rather than of the palette, it is possible that an image
					// could be created with this index set outside the palette size (see
					// http://stackoverflow.com/a/3898007).
					$transparent_color = imagecolorsforindex($this->_resource, $transparent);
					$transparent = imagecolorallocate($res, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

					// Flood with our new transparent color.
					imagefill($res, 0, 0, $transparent);
					imagecolortransparent($res, $transparent);
				}
				else {
					imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
				}
			}
		}
		elseif ($this->_info['extension'] == 'png') {
			imagealphablending($res, FALSE);
			$transparency = imagecolorallocatealpha($res, 0, 0, 0, 127);
			imagefill($res, 0, 0, $transparency);
			imagealphablending($res, TRUE);
			imagesavealpha($res, TRUE);
		}
		else {
			imagefill($res, 0, 0, imagecolorallocate($res, 255, 255, 255));
		}
    $this->_tmp = $res;
  }

  /**
   * Save image to destination
   *
   * @return bool
   */
  private function save() {
    $extension = str_replace('jpg', 'jpeg', $this->_info['extension']);
    $function = 'image' . $extension;
    $tmpDir = CRM_Utils_System::cmsDir('temp');
    $tempName = tempnam($tmpDir, 'crmgd_');

    if ($this->_convert['skip']) {
      $success = copy($this->_source, $this->_destination);
      return $success;
    }
    elseif (function_exists($function)) {
      if ($extension == 'jpeg') {
        $success = $function($this->_resource, $tempName, $this->_convert['quality']);
      }
      else {
				// Always save PNG images with full transparency.
				if ($extension == 'png') {
					imagealphablending($this->_resource, FALSE);
					imagesavealpha($this->_resource, TRUE);
				}
				$success = $function($this->_resource, $tempName);
      }
    }

    if ($success) {
      @copy($tempName, $this->_destination);
      @unlink($tempName);
      return $success;
    }
    return FALSE;
  }

  /**
   * Internal function to Resize image
   *
   * @param int $width
   * @param int $height
   *
   * @return bool
   */
  private function resize($width, $height) {
    $this->_convert['width'] = (int) round($width);
    $this->_convert['height'] = (int) round($height);

    // create original image and temp canvas
    $this->gdCreateResource();
    $this->gdCreateTmp($width, $height);

    $result = imagecopyresampled(
      $this->_tmp,
      $this->_resource,
      0,
      0,
      0,
      0,
      $this->_convert['width'],
      $this->_convert['height'],
      $this->_info['width'],
      $this->_info['height']
    );
    if ($result) {
      imagedestroy($this->_resource);
      $this->_resource = $this->_tmp;
      $this->_info['width'] = $width;
      $this->_info['height'] = $height;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Internal function to Sharpen image
   *
   * @param array $matrix
   *   [
   *     [0, -2, 0]
   *     [-2, 11, -2]
   *     [0, -2, 0]
   *   ]
   *
   * @return bool
   */
  private function sharpen($matrix = array()) {
    if (empty($matrix)) {
      $matrix = array(
        array(0, -2, 0),
        array(-2, 11, -2),
        array(0, -2, 0),
      );
    }
    $divisor = array_sum(array_map('array_sum', $matrix));
    $offset = 0;
    imageconvolution($this->_resource, $matrix, $divisor, $offset);
  }

  /**
   * Internal function to Crop image
   *
   * @param int $x
   * @param int $y
   * @param int $width
   * @param int $height
   * @return void
   */
  private function crop($x, $y, $width, $height) {
		$width = (int) round($width);
		$height = (int) round($height);
    $this->_convert['width'] = $width;
    $this->_convert['height'] = $height;
    $this->gdCreateTmp($width, $height);
    $result = imagecopyresampled(
      $this->_tmp,
      $this->_resource,
      0,
      0,
      $x,
      $y,
      $width,
      $height,
      $width,
      $height
    );
    if ($result) {
      imagedestroy($this->_resource);
      $this->_resource = $this->_tmp;
      $this->_info['width'] = $width;
      $this->_info['height'] = $height;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Scale image and respect dimension
   *
   * @param int $width
   * @param int $height
   * @param bool $upscale
   *
   * @return bool
   */
  public function scale($width, $height, $upscale = FALSE) {
    if (!$this->_prepared) {
      return FALSE;
    }
    list($width, $height) = $this->getConvertDimensions($width, $height, $upscale);
    if ($this->_convert['skip']) {
      return $this->save();
    }
    elseif ($this->resize($width, $height)) {
      $this->sharpen();
      return $this->save();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Scale and Crop image
   *
   * Crop will crop the center of image
   *
   * @param int $width
   * @param int $height
   * @param bool $upscale
   * @return void
   */
  public function scaleCrop($width, $height) {
    if (!$this->_prepared) {
      return FALSE;
    }
    $scale = max($width / $this->_info['width'], $height / $this->_info['height']);
    $x = ($this->_info['width'] * $scale - $width) / 2;
    $y = ($this->_info['height'] * $scale - $height) / 2;
    if ($this->resize($this->_info['width'] * $scale, $this->_info['height'] * $scale)) {
      if ($this->crop($x, $y, $width, $height)) {
        $this->sharpen();
        return $this->save();
      }
    }
    return FALSE;
  }

  /**
   * Destructor
   */
  function __destruct() {
    if (!empty($this->_resource)) {
      imagedestroy($this->_resource);
    }
    if (!empty($this->_tmp)) {
      imagedestroy($this->_tmp);
    }
  }

  /**
   * Get Image Info
   *
   * @return array
   *  [
   *    'url' => $file,
   *    'width' => $imageWidth,
   *    'height' => $imageHeight,
   *    'thumb' => [
   *      'width' => $imageThumbWidth,
   *      'height' => $imageThumbHeight,
   *    ],
   *  ]
   *
   */
  public static function getImageVars($file) {
    list($imageWidth, $imageHeight) = @getimagesize($file);
    $thumbWidth = 125;
    if ($imageWidth && $imageHeight) {
      $imageRatio = $imageWidth / $imageHeight;
    }
    else {
      $imageRatio = 1;
    }
    if ($imageRatio > 1) {
      $imageThumbWidth = $thumbWidth;
      $imageThumbHeight = round($thumbWidth / $imageRatio);
    }
    else {
      $imageThumbHeight = $thumbWidth;
      $imageThumbWidth = $thumbWidth * $imageRatio;
    }
    return array(
      'url' => $file,
      'width' => $imageWidth,
      'height' => $imageHeight,
      'thumb' => array(
        'width' => $imageThumbWidth,
        'height' => $imageThumbHeight,
      ),
    );
    return FALSE;
  }

  /**
   * Get image modal html
   *
   * @return string
   */
  public static function getImageModal($vars) {
    $template = CRM_Core_Smarty::singleton();
    $template->assign('modalImage', $vars);

    return $template->fetch('CRM/common/modal.tpl');
  }

}
