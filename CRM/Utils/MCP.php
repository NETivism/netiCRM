<?php

/**
 * This class handles MCP (Model Context Protocol) requests.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 *
 */
class CRM_Utils_MCP {
  const LAST_HIT = 'mcp_lasthit';
  const RATE_LIMIT = 0.1;

  /**
   * @var bool Whether to output streaming responses
   */
  private $isStreamable = false;

  /**
   * Set streaming mode
   * @param bool $isStreamable Whether to enable streaming responses
   */
  public function setStreamable($isStreamable) {
    $this->isStreamable = $isStreamable;
  }

  /**
   * MCP (Model Context Protocol) entry point
   * @return string JSON-RPC 2.0 response
   */
  public function bootAndRun() {
    return $this->run();
  }

  /**
   * Main MCP runner
   * @return string JSON-RPC 2.0 response
   */
  public function run() {
    $result = $this->handle();
    return $this->output($result);
  }

  /**
   * Handle MCP JSON-RPC 2.0 requests
   * @return array Response data
   */
  public function handle() {
    // Parse JSON-RPC 2.0 request
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_POST)) {
      // GET request was converted to mock POST request in extern/mcp.php
      $request = $_POST;
    } else {
      $input = file_get_contents('php://input');
      $request = json_decode($input, TRUE);
    }
    CRM_Core_Error::debug_var('mcp_post', $request);

    if (!$request || !isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
      return $this->error(-32600, 'Invalid Request', $request['id'] ?? NULL);
    }

    $method = $request['method'] ?? '';
    $params = $request['params'] ?? [];
    $id = $request['id'] ?? NULL;

    // Check from IP address when allowed list defined
    if (defined('CIVICRM_API_ALLOWED_IP')) {
      $allowedIPs = explode(',', CIVICRM_API_ALLOWED_IP);
      if (!empty($allowedIPs)) {
        $match = FALSE;
        $remoteIP = CRM_Utils_System::ipAddress();
        if (!empty($remoteIP)) {
          $match = CRM_Utils_Rule::checkIp($remoteIP, $allowedIPs);
        }
        if (!$match) {
          return $this->error(-32000, 'FATAL: Your IP is not in allowed list.', $id);
        }
      }
    }

    // Everyone should be required to provide the server key, so the whole
    // interface can be disabled in more change to the configuration file.
    // first check for civicrm site key
    if (!CRM_Utils_System::authenticateKey(FALSE)) {
      return $this->error(-32000, 'FATAL: site key or api key is incorrect.', $id);
    }

    // At this point we know we are not calling either login or ping (neither of which
    // require authentication prior to being called. Therefore, at this point we need
    // to make sure we're working with a trusted user.

    // There are three ways to check for a trusted user:
    // First: they can be someone that has provided an API_Key
    // Second: they can be someone that has provided a valid OAuth Bearer token
    $validUser = FALSE;

    // Check for valid session. Session ID's only appear here if you have
    // run the rest_api login function. That might be a problem for the
    // AJAX methods.
    CRM_Core_Session::singleton();

    // Check for OAuth Bearer token first
    $bearerToken = $this->getBearerToken();
    if ($bearerToken) {
      $validUser = $this->validateBearerToken($bearerToken);
      if (!$validUser) {
        return $this->sendOAuthChallenge($id);
      }
    }

    // If the user does not have a valid session or bearer token (most likely to be used by people using
    // an ajax interface), we need to check to see if they are carrying a valid user's
    // secret key.
    if (!$validUser) {
      if (isset($_SERVER['HTTP_X_CIVICRM_API_KEY'])) {
        $api_key = trim($_SERVER['HTTP_X_CIVICRM_API_KEY']);
      }
      else {
        $api_key = trim(CRM_Utils_Request::retrieve('api_key', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST'));
      }
      if (!$api_key || strtolower($api_key) == 'null') {
        // If no API key and no bearer token, send OAuth challenge
        return $this->sendOAuthChallenge($id);
      }
      $api_key = CRM_Utils_Type::escape($api_key, 'String');
      $contactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
      if ($contactId) {
        $uid = CRM_Core_BAO_UFMatch::getUFId($contactId);
        if ($uid) {
          CRM_Utils_System::loadUser(['uid' => $uid]);
          $ufId = CRM_Utils_System::getLoggedInUfID();
          if (CRM_Utils_System::isUserLoggedIn() && $ufId == $uid) {
            $validUser = $contactId;
          }
        }
        if (!$validUser) {
          return $this->sendOAuthChallenge($id);
        }
      }
    }

    // If we didn't find a valid user either way, then send OAuth challenge.
    if (empty($validUser)) {
      return $this->sendOAuthChallenge($id);
    }

    // Check request rate limit
    $args = ['mcp', $method];
    $error = $this->requestRateLimit($args);
    if (!empty($error)) {
      return $this->error(-32000, 'FATAL: ' . $error, $id);
    }

    return $this->routeMethod($method, $params, $id);
  }

  /**
   * Route to appropriate MCP method handler
   * @param string $method Method name
   * @param array $params Method parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function routeMethod($method, $params, $id) {
    switch ($method) {
      case 'initialize':
        return $this->initialize($params, $id);
      case 'notifications/initialized':
        return $this->notificationsInitialized($params, $id);
      case 'tools/list':
        return $this->listTools($params, $id);
      case 'tools/call':
        return $this->callTool($params, $id);
      case 'resources/list':
        return $this->listResources($params, $id);
      case 'resources/read':
        return $this->readResource($params, $id);
      default:
        return $this->error(-32601, 'Method not found', $id);
    }
  }

  /**
   * Output MCP JSON-RPC 2.0 response
   * @param array $result Response data
   * @return string JSON response or SSE format
   */
  public function output($result) {
    // Headers are set in extern/mcp.php, don't duplicate
    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_IGNORE')) {
      $options |= JSON_INVALID_UTF8_IGNORE;
    }

    $jsonResponse = json_encode($result, $options);

    if ($this->isStreamable) {
      return $this->formatSSE('message', $jsonResponse);
    } else {
      return $jsonResponse;
    }
  }

  /**
   * Format data as Server-Sent Event
   * @param string $event Event type (message, error, ping, etc.)
   * @param string $data JSON data
   * @param string|null $id Optional event ID
   * @return string SSE formatted string
   */
  private function formatSSE($event, $data, $id = null) {
    $output = '';
    if ($id !== null) {
      $output .= "id: {$id}\n";
    }
    $output .= "event: {$event}\n";
    $output .= "data: {$data}\n\n";
    return $output;
  }

  /**
   * Send a streaming event immediately (for long-running operations)
   * This flushes output buffers to send data to client in real-time
   * @param string $event Event type
   * @param array $data Event data
   */
  public function streamEvent($event, $data) {
    if (!$this->isStreamable) {
      return;
    }

    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_IGNORE')) {
      $options |= JSON_INVALID_UTF8_IGNORE;
    }

    $json = json_encode($data, $options);
    echo $this->formatSSE($event, $json);

    // Flush output buffers for real-time streaming
    if (ob_get_level() > 0) {
      ob_flush();
    }
    flush();
  }

  /**
   * Send a progress notification during tool execution
   * @param string $progressToken Progress token from client
   * @param int $progress Current progress (0-100)
   * @param int|null $total Total items (optional)
   */
  public function sendProgress($progressToken, $progress, $total = null) {
    if (!$this->isStreamable || empty($progressToken)) {
      return;
    }

    $notification = [
      'jsonrpc' => '2.0',
      'method' => 'notifications/progress',
      'params' => [
        'progressToken' => $progressToken,
        'progress' => $progress,
      ]
    ];

    if ($total !== null) {
      $notification['params']['total'] = $total;
    }

    $this->streamEvent('message', $notification);
  }

  /**
   * Initialize streaming mode - disable output buffering for real-time output
   */
  public function initStreaming() {
    if (!$this->isStreamable) {
      return;
    }

    // Disable output buffering for real-time streaming
    while (ob_get_level() > 0) {
      ob_end_flush();
    }

    // Disable implicit flush
    ob_implicit_flush(true);

    // Set unlimited execution time for long-running streams
    set_time_limit(0);
  }

  /**
   * MCP initialize method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function initialize($params, $id) {
    // Log client info for debugging
    $clientInfo = $params['clientInfo'] ?? [];
    if (!empty($clientInfo)) {
      CRM_Core_Error::debug_log_message(
        'MCP client connected: ' . ($clientInfo['name'] ?? 'unknown') .
        ' v' . ($clientInfo['version'] ?? 'unknown')
      );
    }

    return [
      'jsonrpc' => '2.0',
      'result' => [
        'protocolVersion' => '2025-03-26',
        'capabilities' => [
          'tools' => [
            'listChanged' => false
          ],
          'resources' => [
            'subscribe' => false,
            'listChanged' => false
          ]
        ],
        'serverInfo' => [
          'name' => 'netiCRM MCP Server',
          'version' => '1.1.0'
        ],
        'instructions' => 'netiCRM MCP Server provides access to CRM data including contacts and contributions.'
      ],
      'id' => $id
    ];
  }

  /**
   * MCP notifications/initialized method
   * This is called after the client has processed the initialize response
   * @param array $params Parameters (unused for notifications)
   * @param mixed $id Request ID (should be null for notifications)
   * @return array|null Response (null for notifications, but return empty array for consistency)
   */
  private function notificationsInitialized($params, $id) {
    // This is a notification, so we don't return a response according to MCP spec
    // However, for consistency with our JSON-RPC framework, we return an empty success response
    // The client should ignore responses to notifications anyway
    return [
      'jsonrpc' => '2.0',
      'result' => [],
      'id' => $id
    ];
  }

  /**
   * Check if user has permission for a specific MCP tool
   * @param string $toolName The MCP tool name
   * @return bool True if user has permission, false otherwise
   */
  private function hasToolPermission($toolName) {
    // Parse tool name to extract entity and action
    $parts = explode('_', $toolName);
    if (count($parts) < 2) {
      return FALSE;
    }
    
    $entity = $parts[0];
    $action = $parts[1];
    
    // Handle special entity names
    switch ($entity) {
      case 'contributionrecur':
        $entity = 'ContributionRecur';
        break;
      default:
        // Capitalize first letter for proper API entity name
        $entity = ucfirst($entity);
        break;
    }
    
    // Map tool action to API action
    $apiAction = '';
    switch ($action) {
      case 'search':
        $apiAction = 'get';
        break;
      case 'create':
        $apiAction = 'create';
        break;
      case 'update':
        $apiAction = 'update';
        break;
      case 'delete':
        $apiAction = 'delete';
        break;
      case 'query':
        $apiAction = 'query';
        break;
      default:
        return FALSE;
    }
    
    // Check REST API permissions based on action
    $permissionRequired = '';
    if (in_array($apiAction, ['create'])) {
      $permissionRequired = 'API create';
    }
    elseif (in_array($apiAction, ['update'])) {
      $permissionRequired = 'API update';
    }
    elseif (in_array($apiAction, ['delete'])) {
      $permissionRequired = 'API delete';
    }
    elseif (in_array($apiAction, ['get', 'getsingle', 'getvalue', 'getcount', 'getoptions', 'getfields'])) {
      $permissionRequired = 'API search';
    }
    elseif (in_array($apiAction, ['query'])) {
      $permissionRequired = 'MCP query';
    }
    
    // Check REST API permission if required
    if (!empty($permissionRequired) && !CRM_Core_Permission::check($permissionRequired)) {
      return FALSE;
    }

    // Check standard API permissions using the permission checking function
    if (!empty($entity) && !empty($apiAction)) {
      try {
        require_once 'api/v3/utils.php';
        $emptyParams = [];
        _civicrm_api3_api_check_permission($entity, $apiAction, $emptyParams, TRUE);
        return TRUE;
      }
      catch (Exception $e) {
        return FALSE;
      }
    }
    
    return FALSE;
  }

  /**
   * Get searchable form fields for entity with detailed information
   * @param string $entity Entity name (contact or contribution)
   * @return array Available searchable fields with detailed info
   */
  private function getSearchableFormFields($entity) {
    require_once 'api/v3/utils.php';
    try {
      $searchableFields = _civicrm_api3_get_entity_form_searchable($entity);
      $detailedFields = [];
      
      foreach (array_keys($searchableFields) as $fieldName) {
        $fieldInfo = [
          'name' => $fieldName,
          'type' => $this->determineFieldType($fieldName),
          'description' => $this->generateFieldDescription($fieldName, $entity)
        ];
        
        // Handle custom fields
        if (preg_match('/^custom_(\d+)$/', $fieldName, $matches)) {
          $customFieldId = $matches[1];
          $customFieldInfo = $this->getCustomFieldInfo($customFieldId);
          if ($customFieldInfo) {
            $fieldInfo['description'] = $customFieldInfo['label'] ?? $fieldInfo['description'];
            if (!empty($customFieldInfo['options'])) {
              $fieldInfo['options'] = $customFieldInfo['options'];
            }
            if (!empty($customFieldInfo['data_type'])) {
              $fieldInfo['type'] = $this->mapCustomFieldType($customFieldInfo['data_type']);
            }
          }
        }
        
        $detailedFields[$fieldName] = $fieldInfo;
      }
      
      return $detailedFields;
    }
    catch (Exception $e) {
      return [];
    }
  }

  /**
   * Determine field type based on field name patterns
   * @param string $fieldName Field name
   * @return string Field type
   */
  private function determineFieldType($fieldName) {
    if (strpos($fieldName, 'amount') !== FALSE) {
      return 'number';
    }
    if (preg_match('/_id$/', $fieldName)) {
      return 'integer';
    }
    return 'string';
  }

  /**
   * Generate field description based on field name
   * @param string $fieldName Field name
   * @param string $entity Entity name
   * @return string Field description
   */
  private function generateFieldDescription($fieldName, $entity) {
    $humanReadable = str_replace('_', ' ', $fieldName);
    return 'Search by ' . $humanReadable;
  }

  /**
   * Get custom field information including options
   * @param int $customFieldId Custom field ID
   * @return array|null Custom field info
   */
  private function getCustomFieldInfo($customFieldId) {
    try {
      // Get custom field details
      $customField = civicrm_api('CustomField', 'getsingle', [
        'id' => $customFieldId,
        'version' => 3,
      ]);
      
      $fieldInfo = [
        'label' => $customField['label'] ?? '',
        'data_type' => $customField['data_type'] ?? 'String'
      ];
      
      // Get options using getoptions API for custom fields
      $customFieldName = 'custom_' . $customFieldId;
      
      // Determine entity based on custom field's extends property
      $entity = 'Contact'; // Default entity
      if (!empty($customField['custom_group_id'])) {
        try {
          $customGroup = civicrm_api('CustomGroup', 'getsingle', [
            'id' => $customField['custom_group_id'],
            'version' => 3,
          ]);
          
          if (!empty($customGroup['extends'])) {
            $extends = $customGroup['extends'];
            $entityMap = [
              'Contact' => 'Contact',
              'Individual' => 'Contact',
              'Organization' => 'Contact', 
              'Household' => 'Contact',
              'Contribution' => 'Contribution',
              'Event' => 'Event',
              'Participant' => 'Participant',
              'Membership' => 'Membership',
              'Activity' => 'Activity'
            ];
            $entity = $entityMap[$extends] ?? 'Contact';
          }
        }
        catch (Exception $e) {
          // Use default entity if custom group lookup fails
        }
      }
      
      // Try to get options using getoptions API
      try {
        $optionsResult = civicrm_api($entity, 'getoptions', [
          'field' => $customFieldName,
          'version' => 3,
        ]);
        
        if (!empty($optionsResult['values'])) {
          $fieldInfo['options'] = $optionsResult['values'];
        }
      }
      catch (Exception $e) {
        // If getoptions fails, fall back to option group method
        if (!empty($customField['option_group_id'])) {
          try {
            $options = civicrm_api('OptionValue', 'get', [
              'version' => 3,
              'option_group_id' => $customField['option_group_id'],
              'is_active' => 1,
              'options' => ['sort' => 'weight']
            ]);
            
            $fieldOptions = [];
            foreach ($options['values'] as $option) {
              $fieldOptions[$option['value']] = $option['label'];
            }
            $fieldInfo['options'] = $fieldOptions;
          }
          catch (Exception $e2) {
            // Options not available
          }
        }
      }
      
      return $fieldInfo;
    }
    catch (Exception $e) {
      return null;
    }
  }

  /**
   * Map custom field data type to schema type
   * @param string $dataType Custom field data type
   * @return string Schema type
   */
  private function mapCustomFieldType($dataType) {
    $typeMap = [
      'String' => 'string',
      'Int' => 'integer',
      'Float' => 'number',
      'Money' => 'number',
      'Date' => 'string',
      'Boolean' => 'boolean',
      'Memo' => 'string',
      'Link' => 'string',
      'File' => 'string'
    ];
    
    return $typeMap[$dataType] ?? 'string';
  }

  /**
   * Generate input schema properties for searchable fields
   * @param string $entity Entity name (contact or contribution)
   * @param array $baseProperties Base properties to include
   * @return array Properties array for inputSchema
   */
  private function generateInputSchemaProperties($entity, $baseProperties = []) {
    $searchableFields = $this->getSearchableFormFields($entity);
    $properties = $baseProperties;
    
    // Add return.* properties for base properties
    foreach ($baseProperties as $fieldName => $fieldConfig) {
      $returnFieldName = 'return.' . $fieldName;
      if (!isset($properties[$returnFieldName])) {
        $properties[$returnFieldName] = [
          'type' => 'boolean',
          'description' => 'Include ' . $fieldConfig['description'] . ' in the returned results'
        ];
      }
    }
    
    // Add each searchable field with detailed information
    foreach ($searchableFields as $fieldName => $fieldInfo) {
      if (!isset($properties[$fieldName])) {
        $property = [
          'type' => $fieldInfo['type'],
          'description' => $fieldInfo['description']
        ];
        
        // Add options if available (for select fields)
        if (!empty($fieldInfo['options'])) {
          $property['enum'] = array_keys($fieldInfo['options']);
          $property['description'] .= '. Options: ' . implode(', ', $fieldInfo['options']);
        }
        
        $properties[$fieldName] = $property;
      }
      
      // Add corresponding return.field_name property for field selection
      $returnFieldName = 'return.' . $fieldName;
      if (!isset($properties[$returnFieldName])) {
        $properties[$returnFieldName] = [
          'type' => 'boolean',
          'description' => 'Include ' . $fieldInfo['description'] . ' in the returned results'
        ];
      }
    }
    
    // Add common search properties
    $commonProperties = [
      'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
      'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
      'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "field_name asc", "created_date desc")'],
      'return' => ['type' => 'string', 'description' => 'Comma-separated list of fields to return (alternative to using individual return.* parameters)']
    ];
    
    foreach ($commonProperties as $key => $value) {
      if (!isset($properties[$key])) {
        $properties[$key] = $value;
      }
    }
    
    return $properties;
  }

  /**
   * MCP list_tools method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function listTools($params, $id) {
    // Get available searchable fields for contact and contribution
    $contactSearchableFields = $this->getSearchableFormFields('contact');
    $contributionSearchableFields = $this->getSearchableFormFields('contribution');
    
    $tools = [
      [
        'name' => 'contact_search',
        'description' => 'Search contacts using various filters. Available searchable fields: ' . implode(', ', array_keys($contactSearchableFields)) . '. To specify which fields to return: use return.<field_name> (e.g., return.total_amount) for individual fields, or use "return" parameter with comma-separated field names (e.g., "display_name,email,phone"). Field names alone are used for filtering.',
        'inputSchema' => [
          'type' => 'object',
          'properties' => $this->generateInputSchemaProperties('contact', [
            'contact_type' => ['type' => 'string', 'description' => 'Contact type (Individual, Organization, Household)'],
            'display_name' => ['type' => 'string', 'description' => 'Contact display name'],
            'first_name' => ['type' => 'string', 'description' => 'First name'],
            'last_name' => ['type' => 'string', 'description' => 'Last name'],
            'email' => ['type' => 'string', 'description' => 'Email address'],
            'phone' => ['type' => 'string', 'description' => 'Phone number'],
            'external_identifier' => ['type' => 'string', 'description' => 'External identifier'],
            'id' => ['type' => 'integer', 'description' => 'Contact ID']
          ])
        ]
      ],
      [
        'name' => 'contribution_search',
        'description' => 'Search contributions using various filters. Available searchable fields: ' . implode(', ', array_keys($contributionSearchableFields)) . '. To specify which fields to return: use return.<field_name> (e.g., return.total_amount) for individual fields, or use "return" parameter with comma-separated field names (e.g., "total_amount,contact_id,receive_date"). Field names alone are used for filtering.',
        'inputSchema' => [
          'type' => 'object',
          'properties' => $this->generateInputSchemaProperties('contribution', [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'contribution_type_id' => ['type' => 'integer', 'description' => 'Contribution type ID'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'payment_instrument_id' => ['type' => 'integer', 'description' => 'Payment instrument ID'],
            'receive_date' => ['type' => 'string', 'description' => 'Receive date (YYYY-MM-DD)'],
            'total_amount' => ['type' => 'number', 'description' => 'Total amount'],
            'id' => ['type' => 'integer', 'description' => 'Contribution ID']
          ])
        ]
      ],
      [
        'name' => 'contact_query',
        'description' => 'Generate MariaDB related SQL Query on table "civicrm_contact" based and other related tables to doing contact based analysis.',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'query' => ['type' => 'string', 'description' => 'AI generated query that match MariaDB / MySQL Syntax and follow the rules: Allowed tables: [civicrm_contribution, civicrm_contact, civicrm_contribution_recur, civicrm_contribution_page, civicrm_membership_payment, civicrm_participant_payment]. Allowed selectable fields [id,contact_type,employer_id,birth_date,prefix_id,suffix_id,gender_id,job_title,created_date,modified_date,contact_id,total_amount,receive_date,is_test,contribution_status_id,contribution_page_id,contribution_type_id,contribution_recur_id] .Always join tables "LEFT JOIN civicrm_participant_payment p ON p.contribution_id = cc.id LEFT JOIN civicrm_membership_payment m ON m.contribution_id = cc.id" and add WHERE to check civicrm_participant_payment.id IS NULL AND civicrm_membership_payment.id IS NULL'],
          ],
        ],
      ],
      [
        'name' => 'contribution_query',
        'description' => 'Generate MariaDB related SQL Query on table "civicrm_contact" based and other related tables to doing contact based analysis.',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'query' => ['type' => 'string', 'description' => 'AI generated query that match MariaDB / MySQL Syntax and follow the rules: Allowed tables: [civicrm_contribution, civicrm_contact, civicrm_contribution_recur, civicrm_contribution_page, civicrm_membership_payment, civicrm_participant_payment]. Allowed selectable fields [id,contact_type,employer_id,birth_date,prefix_id,suffix_id,gender_id,job_title,created_date,modified_date,contact_id,total_amount,receive_date,is_test,contribution_status_id,contribution_page_id,contribution_type_id,contribution_recur_id] .Always join tables "LEFT JOIN civicrm_participant_payment p ON p.contribution_id = cc.id LEFT JOIN civicrm_membership_payment m ON m.contribution_id = cc.id" and add WHERE to check civicrm_participant_payment.id IS NULL AND civicrm_membership_payment.id IS NULL'],
          ],
        ],
      ],
    ];

    // Filter tools based on user permissions
    $allowedTools = [];
    foreach ($tools as $tool) {
      if ($this->hasToolPermission($tool['name'])) {
        $allowedTools[] = $tool;
      }
    }

    return [
      'jsonrpc' => '2.0',
      'result' => ['tools' => $allowedTools],
      'id' => $id
    ];
  }

  /**
   * MCP call_tool method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function callTool($params, $id) {
    $toolName = $params['name'] ?? '';
    $arguments = $params['arguments'] ?? [];
    
    // Check if user has permission to call this tool
    if (!$this->hasToolPermission($toolName)) {
      return $this->error(-32000, 'FATAL: You do not have permission to call this tool: ' . $toolName, $id);
    }
    
    switch ($toolName) {
      case 'contact_search':
        return $this->handleContactSearch($arguments, $id);
      case 'contribution_search':
        return $this->handleContributionSearch($arguments, $id);
      case 'contact_query':
      case 'contribution_query':
        return $this->handleMCPQuery($arguments, $id);
      case 'civicrm_api':
        return $this->handleGenericApi($arguments, $id);
      default:
        return $this->error(-32601, 'Unknown tool: ' . $toolName, $id);
    }
  }

  /**
   * Handle contact search
   */
  private function handleContactSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['contact_type', 'display_name', 'first_name', 'last_name', 'email', 'phone', 'external_identifier', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    $apiParams['return.id'] = 1;
    $apiParams['return.contact_type'] = 1;
    $apiParams['return.employer_id'] = 1;
    $apiParams['return.birth_date'] = 1;
    $apiParams['return.prefix_id'] = 1;
    $apiParams['return.suffix_id'] = 1;
    $apiParams['return.gender_id'] = 1;
    $apiParams['return.job_title'] = 1;
    $apiParams['return.created_date'] = 1;
    $apiParams['return.modified_date'] = 1;
    return $this->executeApiCall('Contact', 'get', $apiParams, $id);
  }

  /**
   * Handle contribution search
   */
  private function handleContributionSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['contact_id', 'contribution_type_id', 'contribution_status_id', 'payment_instrument_id', 'receive_date', 'total_amount', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    $apiParams['return.contact_id'] = 1;
    $apiParams['return.contribution_page_id'] = 1;
    $apiParams['return.contribution_type_id'] = 1;
    $apiParams['return.contribution_recurring_id'] = 1;
    $apiParams['return.contribution_status_id'] = 1;
    $apiParams['return.amount_level'] = 1;
    $apiParams['return.payment_instrument_id'] = 1;
    $apiParams['return.currency'] = 1;
    $apiParams['return.total_amount'] = 1;
    $apiParams['return.receive_date'] = 1;
    $results = $this->executeApiCall('Contribution', 'get', $apiParams, $id);
    return $results;
  }

  private function handleMCPQuery($arguments, $id) {
    $allowlist = [
      'statement' => 'SELECT',
      'table' => ['civicrm_contribution', 'civicrm_contact', 'civicrm_participant_payment', 'civicrm_membership_payment', 'civicrm_contribution_recur', 'civicrm_contribution_page'],
      'field' => [
        'id','contact_type','employer_id','birth_date','prefix_id','suffix_id','gender_id','job_title','created_date','modified_date','contact_id','total_amount','receive_date','is_test','contribution_recur_id','contribution_status_id','contribution_page_id','contribution_type_id','contribution_id','participant_id','membership_id'
      ],
    ];
    $parser = new CRM_Utils_SqlParser($arguments['query'], $allowlist);
    $isError = FALSE;
    if ($parser->isValid()) {
      $sql = $parser->getQuery(TRUE);
      $dbo = CRM_Core_DAO::initReadonly();
      $sth = $dbo->prepare($sql);
      $sth->execute();
      $results = $sth->fetchAll();

    }
    else {
      $results = $parser->getErrors();
      $isError = TRUE;
    }
    return [
      'jsonrpc' => '2.0',
      'result' => [
        'content' => [
          [
            'type' => 'text',
            'text' => json_encode($results, JSON_PRETTY_PRINT)
          ]
        ],
        'isError' => $isError
      ],
      'id' => $id
    ];
  }

  /**
   * Handle generic API calls
   */
  private function handleGenericApi($arguments, $id) {
    $entity = $arguments['entity'] ?? '';
    $action = $arguments['action'] ?? '';
    $apiParams = $arguments['params'] ?? [];
    
    if (!$entity || !$action) {
      return $this->error(-32602, 'Invalid params: entity and action required', $id);
    }
    
    // Restrict to read-only actions only
    $allowedActions = ['get', 'getoptions'];
    if (!in_array(strtolower($action), $allowedActions)) {
      return $this->error(-32000, 'Only read-only actions are allowed: ' . implode(', ', $allowedActions), $id);
    }
    
    return $this->executeApiCall($entity, $action, $apiParams, $id);
  }

  /**
   * Execute CiviCRM API call
   */
  private function executeApiCall($entity, $action, $apiParams, $id) {
    // Use existing API processing from REST class
    $args = ['civicrm', $entity, $action];
    $apiParams['version'] = 3;
    $apiParams['sequential'] = 1;
    
    // Handle options parameter for pagination and sorting
    $options = [];
    if (isset($apiParams['limit'])) {
      $options['limit'] = min((int)$apiParams['limit'], 100); // Max 100 results
      unset($apiParams['limit']);
    }
    if (isset($apiParams['offset'])) {
      $options['offset'] = (int)$apiParams['offset'];
      unset($apiParams['offset']);
    }
    if (isset($apiParams['sort'])) {
      $options['sort'] = $apiParams['sort'];
      unset($apiParams['sort']);
    }
    
    // Add options to API params if any are set
    if (!empty($options)) {
      $apiParams['options'] = $options;
    }
    
    try {
      $result = CRM_Utils_REST::process($args, $apiParams);
      
      // Check if API call was successful
      $isError = false;
      if (isset($result['is_error']) && $result['is_error']) {
        $isError = true;
      }
      
      return [
        'jsonrpc' => '2.0',
        'result' => [
          'content' => [
            [
              'type' => 'text',
              'text' => json_encode($result, JSON_PRETTY_PRINT)
            ]
          ],
          'isError' => $isError
        ],
        'id' => $id
      ];
    }
    catch (Exception $e) {
      return [
        'jsonrpc' => '2.0',
        'result' => [
          'content' => [
            [
              'type' => 'text',
              'text' => 'Error: ' . $e->getMessage()
            ]
          ],
          'isError' => true
        ],
        'id' => $id
      ];
    }
  }

  /**
   * MCP list_resources method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function listResources($params, $id) {
    return [
      'jsonrpc' => '2.0',
      'result' => ['resources' => []],
      'id' => $id
    ];
  }

  /**
   * MCP read_resource method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function readResource($params, $id) {
    return $this->error(-32601, 'Resource not found', $id);
  }

  /**
   * Check request rate limit
   * @param array $args Request arguments for tracking
   * @return string Error message if rate limit exceeded, empty string otherwise
   */
  function requestRateLimit($args) {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::LAST_HIT;
    if ($dao->find(TRUE)) {
      $interval = microtime(true) - $dao->timestamp;
      $config = CRM_Core_Config::singleton();
      $rateLimit = $config->restAPIRateLimit ? $config->restAPIRateLimit : self::RATE_LIMIT;
      if ($interval < $rateLimit) {
        return 'Request rate limit reached. Last hit: '.round($interval, 2).' seconds ago. Usage: '.$dao->value;
      }
      $dao->timestamp = microtime(true);
      $dao->value = CRM_Utils_Array::implode('-', $args);
      $dao->update();
    }
    else {
      $dao->timestamp = microtime(true);
      $dao->value = CRM_Utils_Array::implode('-', $args);
      $dao->insert();
    }
    return [];
  }

  /**
   * Create MCP JSON-RPC 2.0 error response
   * @param int $code Error code
   * @param string $message Error message
   * @param mixed $id Request ID
   * @return array Error response
   */
  private function error($code, $message, $id) {
    CRM_Core_Error::debug_log_message("MCP error response: code:$code $message $id");
    return [
      'jsonrpc' => '2.0',
      'error' => [
        'code' => $code,
        'message' => $message
      ],
      'id' => $id
    ];
  }

  /**
   * Extract Bearer token from HTTP Authorization header
   * @return string|null Bearer token or null if not found
   */
  private function getBearerToken() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader)) {
      return null;
    }
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
      return trim($matches[1]);
    }
    
    return null;
  }

  /**
   * Validate OAuth Bearer token using Drupal OAuth2 Server module
   * @param string $token Bearer token
   * @return int|false Contact ID if valid, false otherwise
   */
  private function validateBearerToken($token) {
    if (empty($token)) {
      return false;
    }
    // TODO: implement bearer token validation
    return false;
  }

  /**
   * Send OAuth challenge response with 401 status and WWW-Authenticate header
   *
   * Implements RFC 6750 (Bearer Token Usage) and MCP OAuth requirements.
   * The WWW-Authenticate header points to the resource metadata endpoint
   * which contains OAuth server discovery information.
   *
   * @param mixed $id Request ID
   * @return array OAuth challenge response
   */
  private function sendOAuthChallenge($id) {
    // Set HTTP 401 Unauthorized status
    http_response_code(401);

    // Get base URL
    $config = CRM_Core_Config::singleton();
    $baseUrl = rtrim($config->userFrameworkBaseURL, '/');

    // Discovery endpoints
    $resourceMetadata = $baseUrl . '/.well-known/oauth-protected-resource';
    $authServerMetadata = $baseUrl . '/.well-known/oauth-authorization-server';
    $authServer = $baseUrl . '/oauth2';

    // RFC 6750 compliant WWW-Authenticate header with MCP extensions
    // MCP clients use resource_metadata to discover OAuth server
    $wwwAuthenticate = sprintf(
      'Bearer realm="netiCRM MCP API", resource_metadata="%s"',
      $resourceMetadata
    );

    header('WWW-Authenticate: ' . $wwwAuthenticate);

    // Return JSON-RPC error response with discovery information
    return [
      'jsonrpc' => '2.0',
      'error' => [
        'code' => -32001,
        'message' => 'Unauthorized: Authentication required. Use OAuth Bearer token or API key.',
        'data' => [
          'resource_metadata' => $resourceMetadata,
          'authorization_server_metadata' => $authServerMetadata,
          'authorization_endpoint' => $authServer . '/authorize',
          'token_endpoint' => $authServer . '/token',
          'api_key_header' => 'X-CIVICRM-API-KEY',
        ]
      ],
      'id' => $id
    ];
  }

}
