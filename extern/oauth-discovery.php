<?php
/**
 * OAuth 2.1 Discovery Endpoints for MCP
 *
 * Implements RFC 8414 (OAuth 2.0 Authorization Server Metadata)
 * and draft-ietf-oauth-resource-metadata for MCP remote server compatibility.
 *
 * Handles:
 * - /.well-known/oauth-protected-resource
 * - /.well-known/oauth-authorization-server
 *
 * @see https://modelcontextprotocol.io/specification/
 * @see https://datatracker.ietf.org/doc/html/rfc8414
 */

require_once __DIR__ . '/extern.inc';

// Get the request path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Initialize config for base URL
$config = CRM_Core_Config::singleton();
$baseUrl = rtrim($config->userFrameworkBaseURL, '/');

// MCP endpoint URL
$mcpEndpoint = $baseUrl . '/extern/mcp.php';

// OAuth server base URL (Drupal OAuth2 module)
$oauthServer = $baseUrl . '/oauth2';

// Set common headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header('Access-Control-Allow-Methods: GET, OPTIONS');
  header('Access-Control-Allow-Headers: Accept, Content-Type');
  header('Access-Control-Max-Age: 86400');
  http_response_code(200);
  exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  header('Allow: GET, OPTIONS');
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// Route based on path
if (strpos($path, 'oauth-protected-resource') !== false) {
  // RFC draft-ietf-oauth-resource-metadata
  // Protected Resource Metadata
  echo json_encode([
    'resource' => $mcpEndpoint,
    'authorization_servers' => [$oauthServer],
    'bearer_methods_supported' => ['header'],
    'scopes_supported' => [
      'mcp:read',
      'mcp:write',
      'mcp:tools',
    ],
    'resource_documentation' => $baseUrl . '/civicrm/admin/setting/mcp',
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
elseif (strpos($path, 'oauth-authorization-server') !== false) {
  // RFC 8414 - OAuth 2.0 Authorization Server Metadata
  echo json_encode([
    'issuer' => $baseUrl,
    'authorization_endpoint' => $oauthServer . '/authorize',
    'token_endpoint' => $oauthServer . '/token',
    'token_endpoint_auth_methods_supported' => [
      'client_secret_basic',
      'client_secret_post',
    ],
    'response_types_supported' => ['code'],
    'grant_types_supported' => [
      'authorization_code',
      'refresh_token',
    ],
    'code_challenge_methods_supported' => ['S256', 'plain'],
    'scopes_supported' => [
      'mcp:read',
      'mcp:write',
      'mcp:tools',
    ],
    'service_documentation' => $baseUrl . '/civicrm/admin/setting/mcp',
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
else {
  // Unknown discovery endpoint
  http_response_code(404);
  echo json_encode([
    'error' => 'not_found',
    'error_description' => 'Unknown discovery endpoint',
    'available_endpoints' => [
      '/.well-known/oauth-protected-resource',
      '/.well-known/oauth-authorization-server',
    ],
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
