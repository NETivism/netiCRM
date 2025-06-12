<?php
/**
 * AJAX handler for editor image upload functionality with proper filename handling
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

    // Get filename information from POST data
    $originalFilename = isset($_POST['original_filename']) ? trim($_POST['original_filename']) : '';
    $suggestedFilename = isset($_POST['suggested_filename']) ? trim($_POST['suggested_filename']) : '';
    $timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : null;

    // Validate file information
    $fileInfo = [
      'temp_name' => $uploadedFile['tmp_name'],
      'size' => $uploadedFile['size'],
      'mime_type' => $uploadedFile['type'],
      'uploaded_name' => $uploadedFile['name'], // This is the temp name from frontend
      'original_filename' => $originalFilename,
      'suggested_filename' => $suggestedFilename,
      'timestamp' => $timestamp
    ];

    // Determine the best display name for logging and response
    $displayName = '';
    if (!empty($originalFilename)) {
      $displayName = $originalFilename;
      $fileInfo['source'] = 'file_drop';
    } else {
      $displayName = $suggestedFilename;
      $fileInfo['source'] = 'clipboard_paste';
    }

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

    // SUCCESS: Blob received and validated with proper filename handling
    self::responseSuccess([
      'status' => 1,
      'message' => 'Image blob received and validated successfully',
      'file_info' => [
        'original_filename' => $originalFilename, // Real original name if available
        'suggested_filename' => $suggestedFilename, // Frontend generated name
        'display_name' => $displayName, // Best name for display
        'tmp_name' => $fileInfo['temp_name'], // Server temp file path
        'size' => $fileInfo['size'],
        'type' => $fileInfo['mime_type'],
        'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
        'source' => $fileInfo['source'], // 'clipboard_paste' or 'file_drop'
        'timestamp' => $timestamp
      ],
      'title_attribute' => $displayName . ' | ' . basename($fileInfo['temp_name']),
      'next_steps' => 'TODO: File is ready for processing and storage'
    ]);

    // TODO: Implement actual file processing and storage
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