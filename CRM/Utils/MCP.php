<?php

/**
 * This class handles MCP (Model Context Protocol) requests.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 *
 */
class CRM_Utils_MCP {
  public const LAST_HIT = 'mcp_lasthit';  // Kept for backward compatibility, used as prefix
  public const RATE_LIMIT = 0.1;  // Kept for backward compatibility
  public const RATE_LIMIT_WINDOW = 60;  // 60 second time window
  public const RATE_LIMIT_MAX_REQUESTS = 300;  // Maximum 300 requests per window

  /**
   * Defines the read-only views exposed to MCP queries.
   *
   * Each key is the view name (as seen by SQL queries).
   * 'source' is the underlying base table.
   * 'fields' lists every column the view exposes (and that the parser will allow).
   *
   * This single definition is the source-of-truth for:
   *   - CRM_Core_ReadonlyDAO (view + user provisioning)
   *   - handleMCPQuery allowlist
   *   - listTools query description
   */
  public const READONLY_QUERY_DEFINITIONS = [
    'v_civicrm_contact' => [
      'source' => 'civicrm_contact',
      'alias' => 'contact',
      'fields' => [
        'id', 'contact_type', 'employer_id', 'birth_date',
        'prefix_id', 'suffix_id', 'gender_id', 'job_title',
        'created_date', 'modified_date',
      ],
    ],
    'v_civicrm_contribution' => [
      'source' => 'civicrm_contribution',
      'alias' => 'c',
      'fields' => [
        'id', 'contact_id', 'total_amount', 'amount_level', 'receive_date',
        'is_test', 'contribution_recur_id', 'contribution_status_id',
        'contribution_page_id', 'contribution_type_id', 'cancel_date', 'receipt_date',
      ],
    ],
    'v_civicrm_participant_payment' => [
      'alias' => 'pp',
      'source' => 'civicrm_participant_payment',
      'fields' => ['id', 'contribution_id', 'participant_id'],
    ],
    'v_civicrm_membership_payment' => [
      'source' => 'civicrm_membership_payment',
      'alias' => 'mp',
      'fields' => ['id', 'contribution_id', 'membership_id'],
    ],
    'v_civicrm_contribution_recur' => [
      'source' => 'civicrm_contribution_recur',
      'alias' => 'cr',
      'fields' => [
        'id', 'contact_id', 'amount', 'contribution_status_id',
        'create_date', 'start_date', 'end_date', 'installments',
      ],
    ],
    'v_civicrm_contribution_page' => [
      'alias' => 'cp',
      'source' => 'civicrm_contribution_page',
      'fields' => ['id', 'created_date', 'title'],
    ],
    'v_civicrm_participant' => [
      'source' => 'civicrm_participant',
      'alias' => 'p',
      'fields' => ['id', 'contact_id', 'event_id', 'status_id', 'register_date', 'fee_level', 'is_test', 'fee_amount'],
    ],
    'v_civicrm_event' => [
      'source' => 'civicrm_event',
      'alias' => 'e',
      'fields' => ['id', 'title', 'event_type_id', 'start_date', 'end_date', 'is_active', 'is_template'],
    ],
    'v_civicrm_membership' => [
      'source' => 'civicrm_membership',
      'alias' => 'm',
      'fields' => ['id', 'contact_id', 'membership_type_id', 'join_date', 'start_date', 'end_date', 'status_id', 'is_test'],
    ],
  ];

  /**
   * @var bool Whether to output streaming responses
   */
  private $_isStreamable = FALSE;

  /**
   * @var int User who use mcp
   */
  private $_contactId;

  /**
   * Set streaming mode
   * @param bool $isStreamable Whether to enable streaming responses
   */
  public function setStreamable($isStreamable) {
    $this->_isStreamable = $isStreamable;
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
    }
    else {
      $input = $this->getRawInput();
      $request = json_decode($input, TRUE);
    }

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

    // Check site key and api key source and validation method
    // Site key and api_key MUST be in header for REST API style authentication
    // If site_key or api_key is in query parameters, require checksum (cs) authentication instead
    $siteKeyInHeader = isset($_SERVER['HTTP_X_CIVICRM_SITE_KEY']);
    $apiKeyInHeader = isset($_SERVER['HTTP_X_CIVICRM_API_KEY']);
    $siteKeyInRequest = CRM_Utils_Request::retrieve('site_key', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $apiKeyInRequest = CRM_Utils_Request::retrieve('api_key', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $hasChecksumParams = CRM_Utils_Request::retrieve('cs', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $hasContactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    $hasBearerToken = $this->getBearerToken();

    if ($siteKeyInHeader && $apiKeyInHeader) {
      // Use standard REST API authentication
      if (!CRM_Utils_System::authenticateKey(FALSE)) {
        return $this->error(-32000, 'FATAL: site key is incorrect.', $id);
      }
    }
    elseif ($siteKeyInRequest || $apiKeyInRequest) {
      // Site key or api_key in query/POST without checksum - reject and guide to use checksum
      return $this->error(-32000, 'FATAL: site_key and api_key in query parameters are not allowed. Please use checksum authentication (cid + cs parameters) instead. ', $id);
    }
    elseif ($hasChecksumParams && $hasContactId) {
      // Has checksum params and cid - will validate in validateChecksumAuth() later
      // Site key validation will be done as part of checksum verification
    }
    elseif ($hasBearerToken) {
      // Has bearer token - will validate later
    }
    else {
      // No authentication method provided
      return $this->error(-32000, 'FATAL: Authentication required. Provide site_key and api_key in HTTP headers or use checksum authentication (cid + cs).', $id);
    }

    // There are three ways to check for a trusted user:
    // First: they can be someone that has provided a valid OAuth Bearer token
    // Second: they can be someone that has provided a valid api_key in header (REST API style)
    // Third: they can be someone that has provided a valid checksum (cid + cs)
    $validUser = FALSE;

    // Check for valid session. Session ID's only appear here if you have
    // run the rest_api login function. That might be a problem for the
    // AJAX methods.
    CRM_Core_Session::singleton();

    // Check for OAuth Bearer token first
    if ($hasBearerToken) {
      $validUser = $this->validateBearerToken($hasBearerToken);
      if (!$validUser) {
        // Only Bearer token authentication failures trigger OAuth challenge
        return $this->sendOAuthChallenge($id);
      }
    }

    // If no valid bearer token, check REST API authentication (api_key in header)
    if (!$validUser && $apiKeyInHeader) {
      $api_key = trim($_SERVER['HTTP_X_CIVICRM_API_KEY']);
      if (!empty($api_key)) {
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
        }
      }
      if (!$validUser) {
        return $this->error(-32000, 'FATAL: Invalid API key.', $id);
      }
    }

    // If no valid user yet, check checksum-based authentication (cid + cs)
    if (!$validUser && $hasChecksumParams) {
      $validUser = $this->validateChecksumAuth($id);
      if (!$validUser) {
        return $this->error(-32000, 'FATAL: Checksum authentication failed. Please verify cid and cs parameters.', $id);
      }
    }

    // Final validation check
    if (empty($validUser)) {
      return $this->error(-32000, 'FATAL: Authentication failed.', $id);
    }

    $this->_contactId = $validUser;
    CRM_Core_Error::debug_var("mcp_post_via_contact_$this->_contactId", $request);

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
    if (CRM_Core_Config::singleton()->debug) {
      CRM_Core_Error::debug_var("mcp_result_contact_{$this->_contactId}", $result);
    }

    if ($this->_isStreamable) {
      return $this->formatSSE('message', $jsonResponse);
    }
    else {
      return $jsonResponse;
    }
  }

  /**
   * Get raw input from php://input stream
   * Extracted as protected method to allow testing via subclass override
   * @return string Raw request body
   */
  protected function getRawInput() {
    return file_get_contents('php://input');
  }

  /**
   * Format data as Server-Sent Event
   * @param string $event Event type (message, error, ping, etc.)
   * @param string $data JSON data
   * @param string|null $id Optional event ID
   * @return string SSE formatted string
   */
  private function formatSSE($event, $data, $id = NULL) {
    $output = '';
    if ($id !== NULL) {
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
    if (!$this->_isStreamable) {
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
  public function sendProgress($progressToken, $progress, $total = NULL) {
    if (!$this->_isStreamable || empty($progressToken)) {
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

    if ($total !== NULL) {
      $notification['params']['total'] = $total;
    }

    $this->streamEvent('message', $notification);
  }

  /**
   * Initialize streaming mode - disable output buffering for real-time output
   */
  public function initStreaming() {
    if (!$this->_isStreamable) {
      return;
    }

    // Disable output buffering for real-time streaming
    while (ob_get_level() > 0) {
      ob_end_flush();
    }

    // Disable implicit flush
    ob_implicit_flush(TRUE);

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
        "[contact:{$this->_contactId}] MCP client connected: " . ($clientInfo['name'] ?? 'unknown') .
        ' v' . ($clientInfo['version'] ?? 'unknown')
      );
    }

    return [
      'jsonrpc' => '2.0',
      'result' => [
        'protocolVersion' => '2025-03-26',
        'capabilities' => [
          'tools' => [
            'listChanged' => FALSE
          ],
          'resources' => [
            'subscribe' => FALSE,
            'listChanged' => FALSE
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
    // MCP query permission is always required
    if (!CRM_Core_Permission::check('MCP query')) {
      return FALSE;
    }

    // Each tool requires its own component permission
    $toolPermissions = [
      'contribution_query' => 'access CiviContribute',
      'participant_query'  => 'access CiviEvent',
      'membership_query'   => 'access CiviMember',
    ];

    if (isset($toolPermissions[$toolName])) {
      return CRM_Core_Permission::check($toolPermissions[$toolName]);
    }

    return TRUE;
  }

  /**
   * Return the subset of READONLY_QUERY_DEFINITIONS relevant to a given tool,
   * extended with views from other components when the user holds those permissions.
   *
   * @param string $toolName
   * @return array  Keyed by view name, same structure as READONLY_QUERY_DEFINITIONS.
   */
  private function getToolViewDefs($toolName) {
    $all = self::READONLY_QUERY_DEFINITIONS;
    $hasCiviContribute = CRM_Core_Permission::check('access CiviContribute');
    $hasCiviEvent      = CRM_Core_Permission::check('access CiviEvent');
    $hasCiviMember     = CRM_Core_Permission::check('access CiviMember');

    switch ($toolName) {
      case 'contribution_query':
        // Base: contact + all contribution-related views (incl. payment bridge tables)
        $views = [
          'v_civicrm_contact',
          'v_civicrm_contribution',
          'v_civicrm_contribution_recur',
          'v_civicrm_contribution_page',
          'v_civicrm_participant_payment',
          'v_civicrm_membership_payment',
        ];
        if ($hasCiviEvent) {
          $views[] = 'v_civicrm_participant';
          $views[] = 'v_civicrm_event';
        }
        if ($hasCiviMember) {
          $views[] = 'v_civicrm_membership';
        }
        break;

      case 'participant_query':
        // Base: contact + participant + event + bridge table
        $views = [
          'v_civicrm_contact',
          'v_civicrm_participant',
          'v_civicrm_event',
          'v_civicrm_participant_payment',
        ];
        if ($hasCiviContribute) {
          $views[] = 'v_civicrm_contribution';
          $views[] = 'v_civicrm_contribution_recur';
          $views[] = 'v_civicrm_contribution_page';
        }
        break;

      case 'membership_query':
        // Base: contact + membership + bridge table
        $views = [
          'v_civicrm_contact',
          'v_civicrm_membership',
          'v_civicrm_membership_payment',
        ];
        if ($hasCiviContribute) {
          $views[] = 'v_civicrm_contribution';
          $views[] = 'v_civicrm_contribution_recur';
          $views[] = 'v_civicrm_contribution_page';
        }
        break;

      default:
        return [];
    }

    return array_intersect_key($all, array_flip($views));
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
      return NULL;
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
    // Static metadata per tool: description and JOIN guidance for the AI.
    $toolMeta = [
      'contribution_query' => [
        'description' => 'Generate a MariaDB SELECT query against read-only views for contribution-based analysis.',
        'joinHint'    => 'To isolate direct (non-event, non-membership) contributions use: '
          . 'LEFT JOIN v_civicrm_participant_payment pp ON pp.contribution_id = c.id '
          . 'LEFT JOIN v_civicrm_membership_payment mp ON mp.contribution_id = c.id '
          . 'WHERE pp.id IS NULL AND mp.id IS NULL.',
      ],
      'participant_query' => [
        'description' => 'Generate a MariaDB SELECT query against read-only views for event participant analysis.',
        'joinHint'    => 'Link participants to contributions via: '
          . 'LEFT JOIN v_civicrm_participant_payment pp ON pp.participant_id = p.id.',
      ],
      'membership_query' => [
        'description' => 'Generate a MariaDB SELECT query against read-only views for membership analysis.',
        'joinHint'    => 'Link memberships to contributions via: '
          . 'LEFT JOIN v_civicrm_membership_payment mp ON mp.membership_id = m.id.',
      ],
    ];

    $tools = [];
    foreach ($toolMeta as $toolName => $meta) {
      if (!$this->hasToolPermission($toolName)) {
        continue;
      }

      $viewDefs = $this->getToolViewDefs($toolName);
      $viewDetails = [];
      foreach ($viewDefs as $viewName => $def) {
        $viewDetails[] = $viewName . ' ' . $def['alias'] . ' (fields: ' . implode(', ', $def['fields']) . ')';
      }

      $queryDescription = 'AI generated query that matches MariaDB / MySQL syntax. '
        . 'Allowed views: [' . implode(', ', array_keys($viewDefs)) . ']. '
        . 'View details — ' . implode('; ', $viewDetails) . '. '
        . $meta['joinHint'];

      $tools[] = [
        'name' => $toolName,
        'description' => $meta['description'],
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'prompt' => ['type' => 'string', 'description' => 'The original natural language prompt or question from the user that led to generating this SQL query. Plain text only, HTML will be stripped.'],
            'query'  => ['type' => 'string', 'description' => $queryDescription],
          ],
        ],
      ];
    }

    return [
      'jsonrpc' => '2.0',
      'result'  => ['tools' => $tools],
      'id'      => $id,
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
      case 'contribution_query':
      case 'participant_query':
      case 'membership_query':
        return $this->handleMCPQuery($toolName, $arguments, $id);
      default:
        return $this->error(-32601, 'Unknown tool: ' . $toolName, $id);
    }
  }

  private function handleMCPQuery($toolName, $arguments, $id) {
    // Log prompt and query for auditing
    $prompt = isset($arguments['prompt']) ? $this->sanitizePrompt($arguments['prompt']) : '';
    if (!empty($prompt)) {
      CRM_Core_Error::debug_log_message("[contact:{$this->_contactId}] MCP {$toolName} prompt: " . $prompt);
    }
    CRM_Core_Error::debug_log_message("[contact:{$this->_contactId}] MCP {$toolName} sql: " . ($arguments['query'] ?? ''));

    // Build allowlist from only the views this tool is permitted to access.
    $viewDefs = $this->getToolViewDefs($toolName);
    $allFields = [];
    foreach ($viewDefs as $def) {
      foreach ($def['fields'] as $field) {
        $allFields[$field] = TRUE;
      }
    }
    $allowlist = [
      'statement' => 'SELECT',
      'table'     => array_keys($viewDefs),
      'field'     => array_keys($allFields),
    ];
    $parser = new CRM_Utils_SqlParser($arguments['query'], $allowlist);
    $isError = FALSE;
    if ($parser->isValid()) {
      try {
        $sql = $parser->getQuery(TRUE);
        $dao = new CRM_Core_ReadonlyDAO(self::READONLY_QUERY_DEFINITIONS);
        $dao->setup();
        $pdo = $dao->connectReadonly();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $results = $sth->fetchAll();
      }
      catch (\Exception $e) {
        CRM_Core_Error::debug_log_message("[contact:{$this->_contactId}] MCP query error: " . $e->getMessage());
        $results = ['Query execution failed.'];
        $isError = TRUE;
      }
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
            'text' => json_encode($results)
          ]
        ],
        'isError' => $isError
      ],
      'id' => $id
    ];
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
  public function requestRateLimit($args) {
    // IP-based rate limiting using CRM_Utils_RateLimiter
    $prefix = self::LAST_HIT;
    $windowSeconds = self::RATE_LIMIT_WINDOW;
    $maxRequests = self::RATE_LIMIT_MAX_REQUESTS;

    // Check if custom rate limit is configured
    $config = CRM_Core_Config::singleton();
    if (!empty($config->restAPIRateLimit)) {
      // Convert old time-interval config to request count
      // Old config: seconds between requests (e.g., 0.1 = 10 req/sec)
      // New config: max requests per window
      $requestsPerSecond = 1 / $config->restAPIRateLimit;
      $maxRequests = (int) ($requestsPerSecond * $windowSeconds);
    }

    if (CRM_Utils_RateLimiter::isRateLimited($prefix, $windowSeconds, $maxRequests)) {
      return 'Request rate limit reached. Please try again later.';
    }

    // Probabilistic cleanup of expired records
    CRM_Utils_RateLimiter::cleanup($prefix, $windowSeconds);
  }

  /**
   * Sanitize a prompt string to plain text with UTF-8 encoding and max 512 chars
   * @param string $prompt Raw prompt text (may contain HTML)
   * @return string Sanitized plain text
   */
  private function sanitizePrompt($prompt) {
    // Strip HTML tags to get plain text
    $prompt = strip_tags($prompt);
    // Decode HTML entities (e.g. &amp; &lt;)
    $prompt = html_entity_decode($prompt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Strip any tags that may have appeared after entity decoding
    $prompt = strip_tags($prompt);
    // Ensure valid UTF-8 by re-encoding and dropping invalid byte sequences
    $prompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');
    // Collapse whitespace to single spaces
    $prompt = preg_replace('/\s+/', ' ', trim($prompt));
    // Truncate to max 512 characters (UTF-8 char-aware)
    if (mb_strlen($prompt, 'UTF-8') > 500) {
      $prompt = mb_substr($prompt, 0, 500, 'UTF-8');
    }
    return $prompt;
  }

  /**
   * Create MCP JSON-RPC 2.0 error response
   * @param int $code Error code
   * @param string $message Error message
   * @param mixed $id Request ID
   * @return array Error response
   */
  private function error($code, $message, $id) {
    $contactStr = $this->_contactId ? "[contact:{$this->_contactId}] " : '';
    CRM_Core_Error::debug_log_message("{$contactStr}MCP error response: code:$code $message $id");
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
      return NULL;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
      return trim($matches[1]);
    }

    return NULL;
  }

  /**
   * Validate OAuth Bearer token using Drupal OAuth2 Server module
   * @param string $token Bearer token
   * @return int|false Contact ID if valid, false otherwise
   */
  private function validateBearerToken($token) {
    if (empty($token)) {
      return FALSE;
    }
    // TODO: implement bearer token validation
    return FALSE;
  }

  /**
   * Validate checksum-based authentication using cid and cs parameters.
   *
   * The checksum is computed as: hash('sha256', site_key + contact.hash + contact.api_key)
   * This avoids transmitting the raw api_key and site_key over the wire.
   *
   * @param mixed $id JSON-RPC request ID for error responses
   * @return int|false Contact ID on success, FALSE on failure
   */
  private function validateChecksumAuth($id) {
    // Step 1: Retrieve cid from request
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    if (empty($cid)) {
      return FALSE;
    }

    // Step 2: Retrieve cs from request
    $cs = CRM_Utils_Request::retrieve('cs', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'REQUEST');
    if (empty($cs)) {
      return FALSE;
    }

    // Step 3: Look up contact record (only fetch needed fields)
    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $cid;
    $contact->selectAdd();
    $contact->selectAdd('hash, api_key');
    if (!$contact->find(TRUE)) {
      return FALSE;
    }

    // Step 4: Verify contact has api_key
    if (empty($contact->api_key)) {
      return FALSE;
    }

    // Step 5: Verify contact has hash
    if (empty($contact->hash)) {
      return FALSE;
    }

    // Step 6: Get site key from configuration
    $siteKey = defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : NULL;
    if (empty($siteKey)) {
      return FALSE;
    }

    // Step 7: Compute expected checksum using site_key + contact.hash + contact.api_key
    $expectedCs = hash('sha256', $siteKey . $contact->hash . $contact->api_key);

    // Step 8: Timing-safe comparison
    if (!hash_equals($expectedCs, $cs)) {
      return FALSE;
    }

    // Step 9: Load the Drupal user and verify
    $uid = CRM_Core_BAO_UFMatch::getUFId($cid);
    if ($uid) {
      CRM_Utils_System::loadUser(['uid' => $uid]);
      $ufId = CRM_Utils_System::getLoggedInUfID();
      if (CRM_Utils_System::isUserLoggedIn() && $ufId == $uid) {
        return $cid;
      }
    }

    return FALSE;
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
          'checksum_params' => 'cid (contact ID) and cs (checksum) query parameters. Checksum = sha256(contact.hash + contact.api_key)',
        ]
      ],
      'id' => $id
    ];
  }

}
