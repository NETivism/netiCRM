<?php
/**
 * AJAX handler for editor image upload functionality
 */
class CRM_Core_Page_AJAX_EditorImageUpload {

  /**
   * Handle image upload from editor
   *
   * @return void
   */
  public static function upload() {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      self::responseError([
        'status' => 0,
        'message' => 'Only POST method is allowed.'
      ]);
      return;
    }

    // Check permissions
    if (!CRM_Core_Permission::check('access CiviCRM') ||
        !CRM_Core_Permission::check('upload and post images')) {
      self::responseError([
        'status' => 0,
        'message' => 'Permission denied.'
      ]);
      return;
    }

    // Get the posted data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Check if image data exists
    if (empty($data['image'])) {
      self::responseError([
        'status' => 0,
        'message' => 'No image data provided.'
      ]);
      return;
    }

    $imageData = $data['image'];

    // Validate data URL format
    if (!self::isValidDataURL($imageData)) {
      self::responseError([
        'status' => 0,
        'message' => 'Invalid image data format.'
      ]);
      return;
    }

    // Extract image info
    $imageInfo = self::parseDataURL($imageData);

    if (!$imageInfo) {
      self::responseError([
        'status' => 0,
        'message' => 'Failed to parse image data.'
      ]);
      return;
    }

    // Validate image type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($imageInfo['mime_type'], $allowedTypes)) {
      self::responseError([
        'status' => 0,
        'message' => 'Unsupported image type: ' . $imageInfo['mime_type']
      ]);
      return;
    }

    // Validate image size (optional - basic size check)
    $imageSize = strlen($imageInfo['data']);
    $maxSize = 5 * 1024 * 1024; // 5MB limit

    if ($imageSize > $maxSize) {
      self::responseError([
        'status' => 0,
        'message' => 'Image size too large. Maximum allowed: 5MB'
      ]);
      return;
    }

    // Log successful reception (for development/debugging)
    CRM_Core_Error::debug_log_message('Editor image upload received: ' .
      $imageInfo['mime_type'] . ', size: ' . number_format($imageSize / 1024, 2) . 'KB');

    // Return success response with image info
    self::responseSuccess([
      'status' => 1,
      'message' => 'Image received successfully.',
      'data' => [
        'mime_type' => $imageInfo['mime_type'],
        'size_kb' => round($imageSize / 1024, 2),
        'original_data_url' => $imageData // Return original for now
      ]
    ]);
  }

  /**
   * Validate if string is a valid data URL
   *
   * @param string $dataUrl
   * @return bool
   */
  private static function isValidDataURL($dataUrl) {
    // Check basic data URL format: data:[mediatype][;base64],data
    return preg_match('/^data:image\/[a-zA-Z+]+;base64,/', $dataUrl) === 1;
  }

  /**
   * Parse data URL and extract components
   *
   * @param string $dataUrl
   * @return array|false
   */
  private static function parseDataURL($dataUrl) {
    // Match data URL pattern
    if (!preg_match('/^data:([^;]+);base64,(.+)$/', $dataUrl, $matches)) {
      return false;
    }

    $mimeType = $matches[1];
    $base64Data = $matches[2];

    // Decode base64 data
    $decodedData = base64_decode($base64Data, true);

    if ($decodedData === false) {
      return false;
    }

    return [
      'mime_type' => $mimeType,
      'data' => $decodedData,
      'base64' => $base64Data
    ];
  }

  /**
   * This function handles the response in case of an error.
   *
   * @param mixed $error The error message or object that needs to be sent as a response.
   */
  public static function responseError($error) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error);
    CRM_Utils_System::civiExit();
  }

  /**
   * This function handles the response in case of success.
   *
   * @param mixed $data The data that needs to be sent as a response.
   */
  public static function responseSuccess($data) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    CRM_Utils_System::civiExit();
  }
}