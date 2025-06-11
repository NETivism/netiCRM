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

    // Check if image blob was received via FormData
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
      self::responseError([
        'status' => 0,
        'message' => 'No valid image file received',
        'debug_info' => [
          'files_received' => array_keys($_FILES),
          'post_data' => array_keys($_POST),
          'upload_error' => isset($_FILES['image']) ? $_FILES['image']['error'] : 'No file'
        ]
      ]);
      return;
    }

    $uploadedFile = $_FILES['image'];

    // Validate file information
    $fileInfo = [
      'original_name' => $uploadedFile['name'],
      'tmp_name' => $uploadedFile['tmp_name'],
      'size' => $uploadedFile['size'],
      'mime_type' => $uploadedFile['type'],
      'timestamp' => $_POST['timestamp'] ?? null
    ];

    // Validate MIME type against whitelist
    $allowedMimeTypes = [
      'image/jpeg',
      'image/jpg',
      'image/png',
      'image/gif'
    ];

    if (!in_array($fileInfo['mime_type'], $allowedMimeTypes)) {
      self::responseError([
        'status' => 0,
        'message' => 'Unsupported image format: ' . $fileInfo['mime_type'],
        'allowed_formats' => $allowedMimeTypes
      ]);
      return;
    }

    // Validate file size (max 10MB)
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($fileInfo['size'] > $maxFileSize) {
      self::responseError([
        'status' => 0,
        'message' => 'File too large. Maximum size: ' . ($maxFileSize / 1024 / 1024) . 'MB',
        'received_size' => round($fileInfo['size'] / 1024 / 1024, 2) . 'MB'
      ]);
      return;
    }

    // Additional security check: verify it's actually an image
    $imageInfo = getimagesize($uploadedFile['tmp_name']);
    if ($imageInfo === false) {
      self::responseError([
        'status' => 0,
        'message' => 'Invalid image file'
      ]);
      return;
    }

    // SUCCESS: Blob received and validated
    self::responseSuccess([
      'status' => 1,
      'message' => 'Image blob received and validated successfully',
      'file_info' => [
        'name' => $fileInfo['original_name'],
        'tmp_name' => $fileInfo['tmp_name'],
        'size' => $fileInfo['size'],
        'type' => $fileInfo['mime_type'],
        'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
        'timestamp' => $fileInfo['timestamp']
      ],
      'next_steps' => 'TODO: File is ready for processing and storage'
    ]);

    // TODO: Implement actual file processing and storaged
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
