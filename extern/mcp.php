<?php
require_once __DIR__.'/extern.inc';
CRM_Core_Config::singleton();

// Set JSON-RPC 2.0 appropriate headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Check if MCP is enabled
if (!defined('CIVICRM_MCP_ENABLED')) {
  http_response_code(404);
  exit;
}
if (!CIVICRM_MCP_ENABLED) {
  http_response_code(404);
  exit;
}

$mcp = new CRM_Utils_MCP();
echo $mcp->bootAndRun();