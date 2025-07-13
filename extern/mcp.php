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

// Check if client requests streaming response
$isStreamable = false;
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($acceptHeader, 'text/event-stream') !== false) {
  $isStreamable = true;
  header('Content-Type: text/event-stream; charset=utf-8');
  header('Cache-Control: no-cache');
  header('Connection: keep-alive');
} else {
  header('Content-Type: application/json; charset=utf-8');
}
header('Access-Control-Allow-Origin: *');

// Check if MCP is enabled
if (!defined('CIVICRM_MCP_ENABLED') || !CIVICRM_MCP_ENABLED) {
  http_response_code(404);
  $errorResponse = [
    'jsonrpc' => '2.0',
    'error' => [
      'code' => -32600,
      'message' => 'MCP Server not available'
    ],
    'id' => null
  ];
  
  if ($isStreamable) {
    echo "data: " . json_encode($errorResponse) . "\n\n";
  } else {
    echo json_encode($errorResponse);
  }
  exit;
}

// Initialize and run MCP server
try {
  $mcp = new CRM_Utils_MCP();
  $mcp->setStreamable($isStreamable);
  echo $mcp->bootAndRun();
} catch (Exception $e) {
  http_response_code(500);
  $errorResponse = [
    'jsonrpc' => '2.0',
    'error' => [
      'code' => -32603,
      'message' => 'Internal error: ' . $e->getMessage()
    ],
    'id' => null
  ];
  
  if ($isStreamable) {
    echo "data: " . json_encode($errorResponse) . "\n\n";
  } else {
    echo json_encode($errorResponse);
  }
}