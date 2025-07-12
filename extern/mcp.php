<?php
/**
 * Model Context Protocol (MCP) Server endpoint for netiCRM
 * 
 * This endpoint implements the Model Context Protocol specification
 * for secure tool calling and resource access.
 * 
 * @see https://modelcontextprotocol.io/specification/
 */

require_once __DIR__.'/extern.inc';
CRM_Core_Config::singleton();

// Only allow POST requests for MCP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle CORS preflight requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CIVICRM-API-KEY');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
  } else {
    http_response_code(405);
    header('Allow: POST, OPTIONS');
    echo json_encode([
      'jsonrpc' => '2.0',
      'error' => [
        'code' => -32600,
        'message' => 'Invalid Request - Only POST method allowed for MCP'
      ],
      'id' => null
    ]);
    exit;
  }
}

// Set JSON-RPC 2.0 appropriate headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Check if MCP is enabled
if (!defined('CIVICRM_MCP_ENABLED') || !CIVICRM_MCP_ENABLED) {
  http_response_code(404);
  echo json_encode([
    'jsonrpc' => '2.0',
    'error' => [
      'code' => -32600,
      'message' => 'MCP Server not available'
    ],
    'id' => null
  ]);
  exit;
}

// Initialize and run MCP server
try {
  $mcp = new CRM_Utils_MCP();
  echo $mcp->bootAndRun();
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'jsonrpc' => '2.0',
    'error' => [
      'code' => -32603,
      'message' => 'Internal error: ' . $e->getMessage()
    ],
    'id' => null
  ]);
}