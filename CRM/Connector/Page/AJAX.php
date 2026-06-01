<?php
/*
 +--------------------------------------------------------------------+
 | netiCRM                                                            |
 +--------------------------------------------------------------------+
 | Connector AJAX handlers                                            |
 +--------------------------------------------------------------------+
*/

/**
 * AJAX handlers for netiCRM Connector page actions.
 *
 * All methods are POST-only, accept JSON body with { "cid": <int> },
 * verify permissions, perform the action, log to civicrm_log, and
 * return JSON via responseSuccess() / responseError().
 */
class CRM_Connector_Page_AJAX {

  /**
   * Generate MCP URL for a contact (first-time, keeps existing key if any).
   */
  public static function generateMCP() {
    $cid = self::_requireAuthorizedCid();

    $apiKey = CRM_Contact_BAO_Contact_Utils::generateAPIKey($cid);
    if (!$apiKey) {
      self::responseError(['status' => 0, 'message' => ts('Failed to generate API key.')]);
    }

    $checksum = CRM_Contact_BAO_Contact_Utils::generateAPIChecksum($cid);
    if (!$checksum) {
      self::responseError(['status' => 0, 'message' => ts('Failed to generate checksum.')]);
    }

    self::_auditLog($cid, 'connector.mcp', 'generate');

    self::responseSuccess([
      'status'   => 1,
      'full_url' => self::_buildMCPUrl($cid, $checksum),
    ]);
  }

  /**
   * Reset the MCP connector for a contact by regenerating contact.hash.
   *
   * Regenerating the hash invalidates all previously issued checksums.
   * The api_key is intentionally left unchanged.
   */
  public static function resetMCP() {
    $cid = self::_requireAuthorizedCid();

    if (!CRM_Contact_BAO_Contact_Utils::resetContactHash($cid)) {
      self::responseError(['status' => 0, 'message' => ts('Failed to reset MCP connector.')]);
    }

    $checksum = CRM_Contact_BAO_Contact_Utils::generateAPIChecksum($cid);
    if (!$checksum) {
      self::responseError(['status' => 0, 'message' => ts('Failed to generate checksum.')]);
    }

    self::_auditLog($cid, 'connector.mcp', 'reset');

    self::responseSuccess([
      'status'   => 1,
      'full_url' => self::_buildMCPUrl($cid, $checksum),
    ]);
  }

  /**
   * Generate API key for a contact (first-time, keeps existing key if any).
   */
  public static function generateAPIKey() {
    $cid = self::_requireAuthorizedCid();

    $apiKey = CRM_Contact_BAO_Contact_Utils::generateAPIKey($cid);
    if (!$apiKey) {
      self::responseError(['status' => 0, 'message' => ts('Failed to generate API key.')]);
    }

    self::_auditLog($cid, 'connector.apikey', 'generate');

    self::responseSuccess([
      'status'  => 1,
      'api_key' => $apiKey,
    ]);
  }

  /**
   * Revoke all connector access for a contact by setting api_key to NULL.
   *
   * This disables both MCP and API Key access immediately.
   */
  public static function revokeKey() {
    $cid = self::_requireAuthorizedCid();

    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_contact SET api_key = NULL WHERE id = %1",
      [1 => [$cid, 'Integer']]
    );

    self::_auditLog($cid, 'connector.mcp', 'revoke');

    self::responseSuccess(['status' => 1]);
  }

  /**
   * Reset (force-regenerate) API key for a contact.
   */
  public static function resetAPIKey() {
    $cid = self::_requireAuthorizedCid();

    $apiKey = CRM_Contact_BAO_Contact_Utils::generateAPIKey($cid, TRUE);
    if (!$apiKey) {
      self::responseError(['status' => 0, 'message' => ts('Failed to reset API key.')]);
    }

    self::_auditLog($cid, 'connector.apikey', 'reset');

    self::responseSuccess([
      'status'  => 1,
      'api_key' => $apiKey,
    ]);
  }

  // ---------------------------------------------------------------------------
  // Private helpers
  // ---------------------------------------------------------------------------

  /**
   * Parse cid from POST JSON body and verify permission.
   * Returns validated int $cid, or calls responseError() + civiExit on failure.
   */
  private static function _requireAuthorizedCid() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      self::responseError(['status' => 0, 'message' => ts('POST required.')], 405);
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, TRUE);
    if ($data === NULL || !isset($data['cid']) || !is_numeric($data['cid'])) {
      self::responseError(['status' => 0, 'message' => ts('Invalid request.')], 400);
    }

    $cid         = (int)$data['cid'];
    $session     = CRM_Core_Session::singleton();
    $loggedInCid = (int)$session->get('userID');

    $adminPerm = CRM_Core_Config::$_userSystem->admin_permissions ?? 'administer CiviCRM';
    $isAdmin = CRM_Core_Permission::check($adminPerm);
    $isOwnPage  = ($loggedInCid && $loggedInCid === $cid);
    $hasMCPPerm = CRM_Core_Permission::check('MCP query');

    if (!$isAdmin && !($isOwnPage && $hasMCPPerm)) {
      self::responseError(['status' => 0, 'message' => ts('Permission denied.')], 403);
    }

    return $cid;
  }

  /**
   * Build the full MCP URL with checksum.
   */
  private static function _buildMCPUrl($cid, $checksum) {
    $config = CRM_Core_Config::singleton();
    $base = $config->userFrameworkResourceURL . 'extern/mcp.php';
    return $base . '?cid=' . $cid . '&cs=' . $checksum;
  }

  /**
   * Write an audit entry to civicrm_log.
   *
   * @param int    $cid       Contact being acted upon
   * @param string $auditType e.g. 'connector.mcp' or 'connector.apikey'
   * @param string $action    'generate' or 'reset' or 'revoke'
   */
  private static function _auditLog($cid, $auditType, $action) {
    $session = CRM_Core_Session::singleton();
    $data    = json_encode([
      'action'       => $action,
      'contact_id' => $cid,
    ]);
    $ufId = CRM_Core_BAO_UFMatch::getUFId($cid);
    CRM_Core_BAO_Log::audit($ufId, $auditType, $data);
  }

  /**
   * Output a JSON success response and exit.
   */
  public static function responseSuccess($data) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    CRM_Utils_System::civiExit();
  }

  /**
   * Output a JSON error response and exit.
   */
  public static function responseError($error, $statusCode = 400) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error);
    CRM_Utils_System::civiExit();
  }
}
