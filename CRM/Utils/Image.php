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
      $extensions = ['1' => 'gif', '2' => 'jpg', '3' => 'png'];
      $extension = $extensions[$data[2]] ?? '';
      $aspect = $data[1] / $data[0];
      $this->_info = [
        'width' => $data[0],
        'height' => $data[1],
        'extension' => $extension,
        'mime_type' => $data['mime'],
        'aspect' => $aspect
      ];
      $this->_convert = [
        'quality' => $quality,
      ];
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
    return [$width, $height];
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
  private function sharpen($matrix = []) {
    if (empty($matrix)) {
      $matrix = [
        [0, -2, 0],
        [-2, 11, -2],
        [0, -2, 0],
      ];
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
    return [
      'url' => $file,
      'width' => $imageWidth,
      'height' => $imageHeight,
      'thumb' => [
        'width' => $imageThumbWidth,
        'height' => $imageThumbHeight,
      ],
    ];
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

  /**
   * Process blob images in form content and move temporary files to permanent directories
   *
   * @param array $submitValues Reference to form submit values
   * @param array $formElements Form elements array to identify CKeditor fields
   * @param int $userId Optional user ID, if not provided will get current logged in user
   * @return array Result array with success status and details
   */
  public static function processBlobImagesInContent(&$submitValues, $formElements, $userId = null) {
    $result = [
      'success' => true,
      'processed_fields' => [],
      'moved_files' => [],
      'errors' => [],
      'warnings' => []
    ];

    try {
      // Get user ID using Drupal system
      if (empty($userId)) {
        $userId = CRM_Utils_System_Drupal::getBestUFID();
        if (empty($userId)) {
          $result['success'] = false;
          $result['errors'][] = 'User not logged in or user ID not available';
          return $result;
        }
      }

      // Get directory paths
      $tempDir = CRM_Core_Config::singleton()->uploadDir;
      if (!$tempDir || !is_dir($tempDir)) {
        $result['success'] = false;
        $result['errors'][] = 'Temporary directory not found or not accessible';
        return $result;
      }

      // Get CMS public directory - no fallback, strict error handling
      $cmsPublicDir = CRM_Utils_System::cmsDir('public');
      if (!$cmsPublicDir || !is_dir($cmsPublicDir)) {
        $result['success'] = false;
        $result['errors'][] = 'CMS public directory not found or not accessible';
        return $result;
      }

      // Create user-specific directory: u[uid] directly in CMS public dir
      $userDir = $cmsPublicDir . DIRECTORY_SEPARATOR . 'u' . $userId;

      // Check if user directory exists, create if not
      if (!is_dir($userDir)) {
        if (!mkdir($userDir, 0755, true)) {
          $result['success'] = false;
          $result['errors'][] = 'Cannot create user directory: ' . $userDir;
          return $result;
        }
      }

      // Check if user directory is writable
      if (!is_writable($userDir)) {
        $result['success'] = false;
        $result['errors'][] = 'User directory not writable: ' . $userDir;
        return $result;
      }

      // Field identification strategy: Only process CKeditor fields
      $ckeditorFields = self::identifyCKeditorFields($formElements);

      if (empty($ckeditorFields)) {
        $result['warnings'][] = 'No CKeditor fields found in form';
        return $result;
      }

      // Process only CKeditor fields
      foreach ($ckeditorFields as $fieldName) {
        if (isset($submitValues[$fieldName]) &&
            is_string($submitValues[$fieldName]) &&
            !empty($submitValues[$fieldName])) {

          $processedContent = self::processBlobImagesInField(
            $fieldName,
            $submitValues[$fieldName],
            $tempDir,
            $userDir,
            $userId,
            $result
          );

          // Update the submit value if content was modified
          if ($processedContent !== $submitValues[$fieldName]) {
            $submitValues[$fieldName] = $processedContent;
            $result['processed_fields'][] = $fieldName;
          }
        }
      }

      // Log success summary
      if (!empty($result['moved_files']) && CRM_Core_Config::singleton()->debug) {
        CRM_Core_Error::debug('Blob images processed successfully', [
          'user_id' => $userId,
          'ckeditor_fields' => $ckeditorFields,
          'processed_fields' => $result['processed_fields'],
          'moved_files_count' => count($result['moved_files'])
        ]);
      }

    } catch (Exception $e) {
      $result['success'] = false;
      $result['errors'][] = 'Exception in blob image processing: ' . $e->getMessage();
      CRM_Core_Error::debug('Exception in processBlobImagesInContent', $e);
    }

    return $result;
  }

  /**
   * Identify CKeditor fields from form elements
   *
   * @param array $formElements Form elements array from $this->_elements
   * @return array Array of field names that are CKeditor type
   */
  private static function identifyCKeditorFields($formElements) {
    $ckeditorFields = [];

    if (!is_array($formElements)) {
      return $ckeditorFields;
    }

    foreach ($formElements as $element) {
      // Check if element is CKeditor type
      if (is_object($element) &&
          isset($element->_type) &&
          $element->_type === 'CKeditor') {

        // Get field name from element attributes
        $fieldName = null;
        if (isset($element->_attributes['name'])) {
          $fieldName = $element->_attributes['name'];
        } elseif (isset($element->_name)) {
          $fieldName = $element->_name;
        }

        if (!empty($fieldName)) {
          $ckeditorFields[] = $fieldName;
        }
      }
    }

    return $ckeditorFields;
  }

  /**
   * Process blob images in a single field content with immediate URL replacement
   *
   * @param string $fieldName Field name for logging
   * @param string $content HTML content to process
   * @param string $tempDir Temporary directory path
   * @param string $userDir User's permanent directory path
   * @param int $userId User ID
   * @param array $result Reference to result array for logging
   * @return string Processed content with updated image paths
   */
  private static function processBlobImagesInField($fieldName, $content, $tempDir, $userDir, $userId, &$result) {
    // Blob image parsing strategy: match img tags with blob URLs and title attributes
    $pattern = '/
      <img\s+                               # img tag start
      [^>]*                                 # any attributes before src
      src="blob:[^"]*"                      # blob URL in src attribute
      [^>]*                                 # any attributes between src and title
      title="([^|]+)\s*\|\s*([^"]+)"        # title with "original_name | temp_name" format
      [^>]*                                 # any remaining attributes
      >/ix';

    // Find all blob images in the content
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $fullImgTag = $match[0];
        $originalName = trim($match[1]);
        $tempFileName = trim($match[2]);

        try {
          // File moving strategy: locate and move temporary file
          $movedFile = self::moveTemporaryFile(
            $tempFileName,
            $originalName,
            $tempDir,
            $userDir,
            $userId
          );

          if ($movedFile['success']) {
            // Generate public URL for the moved file
            $urlResult = self::generatePublicUrl($movedFile['final_path']);

            if ($urlResult['success']) {
              $publicUrl = $urlResult['url'];

              // Immediate replacement: build new img tag and replace in content
              $newImgTag = '<img class="ckeditor-clipboard-image" alt="" src="' . htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') . '" />';
              $content = str_replace($fullImgTag, $newImgTag, $content);

              $result['moved_files'][] = [
                'field' => $fieldName,
                'original_name' => $originalName,
                'temp_name' => $tempFileName,
                'final_path' => $movedFile['final_path'],
                'final_name' => $movedFile['final_name'],
                'public_url' => $publicUrl,
                'img_tag_replaced' => true
              ];
            } else {
              $result['warnings'][] = "Generated URL failed for field '{$fieldName}': " . $urlResult['error'];

              $result['moved_files'][] = [
                'field' => $fieldName,
                'original_name' => $originalName,
                'temp_name' => $tempFileName,
                'final_path' => $movedFile['final_path'],
                'final_name' => $movedFile['final_name'],
                'public_url' => null,
                'img_tag_replaced' => false
              ];
            }
          } else {
            $result['errors'][] = "Failed to move file for field '{$fieldName}': " . $movedFile['error'];
          }
        } catch (Exception $e) {
          $result['errors'][] = "Exception processing image in field '{$fieldName}': " . $e->getMessage();
        }
      }
    }

    return $content; // Return modified content with updated URLs
  }

  /**
   * Parse attributes from img tag HTML
   *
   * @param string $imgTag HTML img tag
   * @return array Associative array of attribute name => value
   */
  private static function parseImgAttributes($imgTag) {
    $attributes = [];

    // Enhanced regex to capture all attributes properly
    $pattern = '/(\w+(?:-\w+)*)=(["\'])([^"\']*)\2/i';

    if (preg_match_all($pattern, $imgTag, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $attrName = strtolower(trim($match[1]));
        $attrValue = $match[3];
        $attributes[$attrName] = $attrValue;
      }
    }

    // Ensure required attributes exist
    if (!isset($attributes['alt'])) {
      $attributes['alt'] = 'Uploaded image';
    }

    return $attributes;
  }

  /**
   * Build img tag HTML from attributes array
   *
   * @param array $attributes Associative array of attributes
   * @return string Complete img tag HTML
   */
  private static function buildImgTag($attributes) {
    $attrStrings = [];

    // Define attribute order for consistency
    $orderedAttrs = ['src', 'alt', 'title', 'width', 'height', 'class', 'style', 'id'];

    // Add ordered attributes first
    foreach ($orderedAttrs as $attrName) {
      if (isset($attributes[$attrName]) && $attributes[$attrName] !== '') {
        $attrValue = htmlspecialchars($attributes[$attrName], ENT_QUOTES, 'UTF-8');
        $attrStrings[] = $attrName . '="' . $attrValue . '"';
        unset($attributes[$attrName]);
      }
    }

    // Add remaining attributes
    foreach ($attributes as $attrName => $attrValue) {
      if ($attrValue !== '') {
        $attrValue = htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8');
        $attrStrings[] = $attrName . '="' . $attrValue . '"';
      }
    }

    return '<img ' . implode(' ', $attrStrings) . ' />';
  }

  /**
   * Move temporary file to permanent user directory
   *
   * @param string $tempFileName Temporary file name from title attribute
   * @param string $originalName Original file name from title attribute
   * @param string $tempDir Temporary directory path
   * @param string $userDir User's permanent directory path (u[uid])
   * @param int $userId User ID for logging
   * @return array Result with success status and file details
   */
  private static function moveTemporaryFile($tempFileName, $originalName, $tempDir, $userDir, $userId) {
    $result = ['success' => false, 'error' => ''];

    try {
      // Find temporary file with any extension (from EditorImageUpload.php processing)
      $tempFilePattern = $tempDir . DIRECTORY_SEPARATOR . $tempFileName . '.*';
      $tempFiles = glob($tempFilePattern);

      if (empty($tempFiles)) {
        $result['error'] = 'Temporary file not found: ' . $tempFileName;
        return $result;
      }

      $sourceFile = $tempFiles[0]; // Use first match
      if (!is_file($sourceFile)) {
        $result['error'] = 'Source file does not exist: ' . $sourceFile;
        return $result;
      }

      // Generate final filename with conflict resolution
      $finalFileName = self::generateFinalFileName($originalName, $userDir, $sourceFile);
      $finalPath = $userDir . DIRECTORY_SEPARATOR . $finalFileName;

      // Ensure target file doesn't exist (additional safety check)
      if (file_exists($finalPath)) {
        $finalFileName = self::resolveFileNameConflict($finalFileName, $userDir);
        $finalPath = $userDir . DIRECTORY_SEPARATOR . $finalFileName;
      }

      // Move file from temp to permanent location
      if (rename($sourceFile, $finalPath)) {
        // Set appropriate file permissions
        chmod($finalPath, 0644);

        $result['success'] = true;
        $result['final_path'] = $finalPath;
        $result['final_name'] = $finalFileName;
        $result['source_file'] = $sourceFile;

        CRM_Core_Error::debug('File moved successfully', [
          'source' => $sourceFile,
          'destination' => $finalPath,
          'user_id' => $userId,
          'user_dir' => basename($userDir) // Just log u[uid] part
        ]);

      } else {
        $result['error'] = 'Failed to move file from ' . $sourceFile . ' to ' . $finalPath;
      }

    } catch (Exception $e) {
      $result['error'] = 'Exception in moveTemporaryFile: ' . $e->getMessage();
    }

    return $result;
  }

  /**
   * Generate final filename using custom format to avoid encoding issues
   * Format: pasted_image_YYYYMMDD_HHMMSS-randomstring.ext
   *
   * @param string $originalName Original filename from title attribute (not used in new logic)
   * @param string $userDir User directory path (for future conflict checking if needed)
   * @param string $sourceFile Source file path to get extension
   * @return string Final filename with custom format
   */
  private static function generateFinalFileName($originalName, $userDir, $sourceFile) {
    // Get file extension from source file
    $sourceExtension = pathinfo($sourceFile, PATHINFO_EXTENSION);

    // Ensure we have a valid extension, default to jpg if none
    if (empty($sourceExtension)) {
      $sourceExtension = 'jpg';
    }

    // Generate timestamp in the format YYYYMMDD_HHMMSS
    $timestamp = date('Ymd_His');

    // Generate random string (7 characters) for uniqueness
    $randomString = substr(bin2hex(random_bytes(4)), 0, 7);

    // Create final filename with custom format
    // Example: pasted_image_20250703_181320-xy1asda.jpg
    $finalFileName = 'pasted_image_' . $timestamp . '-' . $randomString . '.' . strtolower($sourceExtension);

    return $finalFileName;
  }

  /**
   * Resolve filename conflicts by adding numeric suffix
   *
   * @param string $fileName Original filename
   * @param string $directory Target directory
   * @return string Unique filename
   */
  private static function resolveFileNameConflict($fileName, $directory) {
    $pathInfo = pathinfo($fileName);
    $baseName = $pathInfo['filename'];
    $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

    $counter = 1;
    while (file_exists($directory . DIRECTORY_SEPARATOR . $fileName)) {
      $fileName = $baseName . '_' . $counter . $extension;
      $counter++;

      // Prevent infinite loop
      if ($counter > 1000) {
        $fileName = $baseName . '_' . time() . $extension;
        break;
      }
    }

    return $fileName;
  }

  /**
   * Generate public URL from file path using dynamic path calculation
   *
   * @param string $filePath Full file path
   * @return array Result with success status and URL
   */
  private static function generatePublicUrl($filePath) {
    $result = ['success' => false, 'url' => '', 'error' => ''];

    try {
      // Get CMS public directory
      $cmsPublicDir = CRM_Utils_System::cmsDir('public');
      if (!$cmsPublicDir || !is_dir($cmsPublicDir)) {
        $result['error'] = 'CMS public directory not found';
        return $result;
      }

      // Normalize paths for comparison
      $normalizedPublicDir = rtrim(str_replace('\\', '/', realpath($cmsPublicDir)), '/');
      $normalizedFilePath = str_replace('\\', '/', realpath($filePath));

      // Check if file is within public directory
      if (strpos($normalizedFilePath, $normalizedPublicDir) !== 0) {
        $result['error'] = 'File is not in public directory';
        return $result;
      }

      // Calculate relative path from public directory to file
      $fileRelativePath = substr($normalizedFilePath, strlen($normalizedPublicDir));
      $fileRelativePath = ltrim($fileRelativePath, '/');

      // Calculate public directory relative to document root
      $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');

      // Check if public directory is within document root
      if (strpos($normalizedPublicDir, $documentRoot) !== 0) {
        $result['error'] = 'Public directory is not within document root';
        return $result;
      }

      $publicUrlPath = substr($normalizedPublicDir, strlen($documentRoot));
      $publicUrlPath = ltrim($publicUrlPath, '/');

      // Generate final relative URL
      $relativeUrl = '/' . $publicUrlPath . '/' . $fileRelativePath;
      $relativeUrl = preg_replace('/\/+/', '/', $relativeUrl); // Clean up multiple slashes

      // Verify URL format is reasonable
      if (strlen($relativeUrl) > 0 && $relativeUrl !== '/') {
        $result['success'] = true;
        $result['url'] = $relativeUrl;

        // Debug log the URL generation process
        CRM_Core_Error::debug('URL generation details', [
          'file_path' => $filePath,
          'public_dir' => $normalizedPublicDir,
          'document_root' => $documentRoot,
          'file_relative_path' => $fileRelativePath,
          'public_url_path' => $publicUrlPath,
          'final_url' => $relativeUrl
        ]);
      } else {
        $result['error'] = 'Generated URL is invalid: ' . $relativeUrl;
      }

    } catch (Exception $e) {
      $result['error'] = 'Exception in generatePublicUrl: ' . $e->getMessage();
      CRM_Core_Error::debug('Exception in generatePublicUrl', [
        'file_path' => $filePath,
        'error' => $e->getMessage()
      ]);
    }

    return $result;
  }
}
