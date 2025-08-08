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

// Handle different HTTP methods for MCP compatibility
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  // Handle CORS preflight requests
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CIVICRM-API-KEY, Accept');
  header('Access-Control-Max-Age: 86400');
  http_response_code(200);
  exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Handle GET request for MCP backwards compatibility detection
  // For Claude Desktop connector, we need to handle GET requests with query parameters
  
  // Check if this is a simple MCP endpoint detection
  if (empty($_GET)) {
    // Return SSE stream for legacy HTTP+SSE transport detection
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    
    // Send endpoint event for legacy HTTP+SSE transport detection
    echo "event: endpoint\n";
    echo "data: " . json_encode([
      'method' => 'notifications/endpoint',
      'params' => [
        'endpoint' => $_SERVER['REQUEST_URI'],
        'transport' => 'http+sse'
      ]
    ]) . "\n\n";
    
    // Keep connection alive for SSE
    while (true) {
      echo "event: ping\n";
      echo "data: " . json_encode(['timestamp' => time()]) . "\n\n";
      
      if (connection_aborted()) {
        break;
      }
      
      sleep(30); // Send ping every 30 seconds
    }
    exit;
  } else {
    // Handle GET with query parameters (like Claude Desktop)
    // Convert GET parameters to a simple JSON-RPC initialize request
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
    // Create a mock initialize request
    $mockRequest = [
      'jsonrpc' => '2.0',
      'method' => 'initialize',
      'params' => [
        'protocolVersion' => '2024-11-05',
        'capabilities' => [],
        'clientInfo' => [
          'name' => 'Claude Desktop',
          'version' => '1.0.0'
        ]
      ],
      'id' => 'get-init'
    ];
    
    // Override the input for GET requests
    $_POST = $mockRequest;
    // Continue processing as if it was a POST request
  }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  // Reject other methods
  http_response_code(405);
  header('Allow: GET, POST, OPTIONS');
  echo json_encode([
    'jsonrpc' => '2.0',
    'error' => [
      'code' => -32600,
      'message' => 'Invalid Request - Only GET, POST, OPTIONS methods allowed for MCP'
    ],
    'id' => null
  ]);
  exit;
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