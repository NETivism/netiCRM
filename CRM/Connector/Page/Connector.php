<?php
/*
 +--------------------------------------------------------------------+
 | netiCRM                                                            |
 +--------------------------------------------------------------------+
 | Connector management page                                          |
 +--------------------------------------------------------------------+
*/

/**
 * Page for managing netiCRM Connector credentials (MCP and API Explorer).
 *
 * Access rules:
 *   - Own page: must have "MCP query" permission
 *   - Other contact's page: must have "administer CiviCRM"
 */
class CRM_Connector_Page_Connector extends CRM_Core_Page {

  public function run() {
    $cid = (int)CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (!$cid) {
      CRM_Utils_System::permissionDenied();
      return;
    }

    $session = CRM_Core_Session::singleton();
    $loggedInCid = (int)$session->get('userID');

    $adminPerm = CRM_Core_Config::$_userSystem->admin_permissions ?? 'administer CiviCRM';
    $isAdmin = CRM_Core_Permission::check($adminPerm);
    $isOwnPage = ($loggedInCid && $loggedInCid === $cid);
    $hasMCPPerm = CRM_Core_Permission::check('MCP query');
    $mcpEnabled         = defined('CIVICRM_MCP_ENABLED') && CIVICRM_MCP_ENABLED && $hasMCPPerm;
    $apiExplorerEnabled = defined('CIVICRM_APIEXPLORER_ENABLED') && CIVICRM_APIEXPLORER_ENABLED;
    if (!$mcpEnabled && !$apiExplorerEnabled) {
      CRM_Utils_System::permissionDenied();
      return;
    }
    if (!$isAdmin && !$isOwnPage) {
      CRM_Utils_System::permissionDenied();
    }

    CRM_Utils_System::setTitle(ts('netiCRM Connector'));

    $this->assign('cid', $cid);
    $this->assign('mcpEnabled', $mcpEnabled);
    $this->assign('apiExplorerEnabled', $apiExplorerEnabled);

    if ($mcpEnabled) {
      $this->assign('mcpInfo', $this->_getMCPInfo($cid));
    }

    if ($apiExplorerEnabled) {
      $this->assign('apiKeyInfo', $this->_getAPIKeyInfo($cid));
    }

    $this->assign('ajaxGenerateMCP', CRM_Utils_System::url('civicrm/ajax/connector/generate-mcp', NULL, FALSE));
    $this->assign('ajaxResetMCP', CRM_Utils_System::url('civicrm/ajax/connector/reset-mcp', NULL, FALSE));
    $this->assign('ajaxGenerateAPIKey', CRM_Utils_System::url('civicrm/ajax/connector/generate-apikey', NULL, FALSE));
    $this->assign('ajaxResetAPIKey', CRM_Utils_System::url('civicrm/ajax/connector/reset-apikey', NULL, FALSE));
    $this->assign('ajaxRevokeKey', CRM_Utils_System::url('civicrm/ajax/connector/revoke-key', NULL, FALSE));

    return parent::run();
  }

  /**
   * Build display data for the MCP section.
   */
  private function _getMCPInfo($cid) {
    $apiKey = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'api_key');
    $hasKey = !empty($apiKey);
    $hasChecksum = CRM_Contact_BAO_Contact_Utils::generateAPIChecksum($cid);

    $generatedDate   = NULL;
    $generatedByName = NULL;

    if ($hasKey && $hasChecksum) {
      $sql = "SELECT l.modified_date, c.display_name AS modifier_name
              FROM civicrm_log l
              LEFT JOIN civicrm_contact c ON l.modified_id = c.id
              WHERE l.entity_table = 'audit.connector.mcp'
                AND l.entity_id = %1
              ORDER BY l.modified_date DESC
              LIMIT 1";
      $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$cid, 'Integer']]);
      if ($dao->fetch()) {
        $generatedDate   = $dao->modified_date;
        $generatedByName = $dao->modifier_name;
      }
    }

    $maskedUrl = NULL;
    if ($hasKey) {
      $config    = CRM_Core_Config::singleton();
      $base      = $config->userFrameworkResourceURL . 'extern/mcp.php';
      $maskedUrl = $base . '?cid=' . $cid . '&cs='. CRM_Utils_String::mask($hasChecksum, 'custom', 4, -4);
    }

    return [
      'has_key'        => $hasKey,
      'masked_url'     => $maskedUrl,
      'generated_date' => $generatedDate,
      'generated_by'   => $generatedByName,
      'last_login'     => $this->_getLastLogin($cid),
    ];
  }

  /**
   * Build display data for the API Explorer section.
   */
  private function _getAPIKeyInfo($cid) {
    $apiKey = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'api_key');
    $hasKey = !empty($apiKey);

    $maskedApiKey    = NULL;
    $maskedSiteKey   = NULL;
    $fullSiteKey     = defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '';
    $generatedDate   = NULL;
    $generatedByName = NULL;

    if ($hasKey) {
      $maskedApiKey = (strlen($apiKey) >= 8)
        ? substr($apiKey, 0, 4) . '...' . substr($apiKey, -4)
        : '****';

      $maskedSiteKey = (strlen($fullSiteKey) >= 8)
        ? substr($fullSiteKey, 0, 4) . '...' . substr($fullSiteKey, -4)
        : '****';

      $sql = "SELECT l.modified_date, c.display_name AS modifier_name
              FROM civicrm_log l
              LEFT JOIN civicrm_contact c ON l.modified_id = c.id
              WHERE l.entity_table = 'audit.connector.apikey'
                AND l.entity_id = %1
              ORDER BY l.modified_date DESC
              LIMIT 1";
      $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$cid, 'Integer']]);
      if ($dao->fetch()) {
        $generatedDate   = $dao->modified_date;
        $generatedByName = $dao->modifier_name;
      }
    }

    return [
      'has_key'         => $hasKey,
      'masked_api_key'  => $maskedApiKey,
      'masked_site_key' => $maskedSiteKey,
      'full_site_key'   => $fullSiteKey,
      'generated_date'  => $generatedDate,
      'generated_by'    => $generatedByName,
      'last_login'      => $this->_getLastLogin($cid),
    ];
  }

  /**
   * Get last login datetime string for a contact's CMS user.
   */
  private function _getLastLogin($cid) {
    $ufId = CRM_Core_BAO_UFMatch::getUFId($cid);
    if (!$ufId) {
      return NULL;
    }
    $loginTime = CRM_Utils_System_Drupal::getLastLoginTime($ufId);
    return $loginTime ? date('Y-m-d H:i:s', $loginTime) : NULL;
  }
}
