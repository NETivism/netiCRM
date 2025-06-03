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
    
    self::responseSuccess([
      'status' => 1,
      'message' => 'Upload endpoint is working'
    ]);
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
