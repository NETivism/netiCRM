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
  const RATE_LIMIT = 0.2;

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
    $input = file_get_contents('php://input');
    $request = json_decode($input, TRUE);
    
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

    // There are two ways to check for a trusted user:
    // First: they can be someone that has a valid session currently
    // Second: they can be someone that has provided an API_Key
    $validUser = FALSE;

    // Check for valid session. Session ID's only appear here if you have
    // run the rest_api login function. That might be a problem for the
    // AJAX methods.
    $session = CRM_Core_Session::singleton();

    // If the user does not have a valid session (most likely to be used by people using
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
        return $this->error(-32000, 'FATAL: site key or api key is incorrect.', $id);
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
            $session->set('ufID', $uid);
            $session->set('userID', $contactId);
          }
        }
        if (!$validUser) {
          return $this->error(-32000, 'FATAL: site key or api key is incorrect.', $id);
        }
      }
    }

    // If we didn't find a valid user either way, then die.
    if (empty($validUser)) {
      return $this->error(-32000, 'FATAL: site key or api key is incorrect.', $id);
    }

    // Check request rate limit
    $args = ['mcp', $method];
    $error = $this->requestRateLimit($args);
    if (!empty($error)) {
      return $this->error(-32000, 'FATAL: ' . $error, $id);
    }
    
    // Route to appropriate MCP method
    switch ($method) {
      case 'initialize':
        return $this->initialize($params, $id);
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
   * @return string JSON response
   */
  public function output($result) {
    // Headers are set in extern/mcp.php, don't duplicate
    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_IGNORE')) {
      $options |= JSON_INVALID_UTF8_IGNORE;
    }
    return json_encode($result, $options);
  }

  /**
   * MCP initialize method
   * @param array $params Parameters
   * @param mixed $id Request ID
   * @return array Response
   */
  private function initialize($params, $id) {
    return [
      'jsonrpc' => '2.0',
      'result' => [
        'protocolVersion' => '2024-11-05',
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
          'version' => '1.0.0'
        ]
      ],
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
      default:
        return FALSE;
    }
    
    // Check REST API permissions based on action
    $permissionRequired = '';
    if (in_array($apiAction, ['create'])) {
      $permissionRequired = 'REST API create';
    }
    elseif (in_array($apiAction, ['update'])) {
      $permissionRequired = 'REST API update';
    }
    elseif (in_array($apiAction, ['delete'])) {
      $permissionRequired = 'REST API delete';
    }
    elseif (in_array($apiAction, ['get', 'getsingle', 'getvalue', 'getcount', 'getoptions', 'getfields'])) {
      $permissionRequired = 'REST API search';
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
        'name' => 'contact_create',
        'description' => 'Create a new contact',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_type' => ['type' => 'string', 'description' => 'Contact type (Individual, Organization, Household)', 'enum' => ['Individual', 'Organization', 'Household']],
            'display_name' => ['type' => 'string', 'description' => 'Contact display name'],
            'first_name' => ['type' => 'string', 'description' => 'First name'],
            'last_name' => ['type' => 'string', 'description' => 'Last name'],
            'organization_name' => ['type' => 'string', 'description' => 'Organization name'],
            'email' => ['type' => 'string', 'description' => 'Primary email address'],
            'phone' => ['type' => 'string', 'description' => 'Primary phone number'],
            'birth_date' => ['type' => 'string', 'description' => 'Birth date (YYYY-MM-DD)'],
            'gender_id' => ['type' => 'integer', 'description' => 'Gender ID'],
            'external_identifier' => ['type' => 'string', 'description' => 'External identifier']
          ],
          'required' => ['contact_type']
        ]
      ],
      [
        'name' => 'contact_update',
        'description' => 'Update an existing contact',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'contact_type' => ['type' => 'string', 'description' => 'Contact type (Individual, Organization, Household)', 'enum' => ['Individual', 'Organization', 'Household']],
            'display_name' => ['type' => 'string', 'description' => 'Contact display name'],
            'first_name' => ['type' => 'string', 'description' => 'First name'],
            'last_name' => ['type' => 'string', 'description' => 'Last name'],
            'organization_name' => ['type' => 'string', 'description' => 'Organization name'],
            'email' => ['type' => 'string', 'description' => 'Primary email address'],
            'phone' => ['type' => 'string', 'description' => 'Primary phone number'],
            'birth_date' => ['type' => 'string', 'description' => 'Birth date (YYYY-MM-DD)'],
            'gender_id' => ['type' => 'integer', 'description' => 'Gender ID'],
            'external_identifier' => ['type' => 'string', 'description' => 'External identifier']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'contact_delete',
        'description' => 'Delete a contact (moves to recycle bin)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Contact ID']
          ],
          'required' => ['id']
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
        'name' => 'contribution_create',
        'description' => 'Create a new contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'total_amount' => ['type' => 'number', 'description' => 'Total amount'],
            'contribution_type_id' => ['type' => 'integer', 'description' => 'Contribution type ID'],
            'receive_date' => ['type' => 'string', 'description' => 'Receive date (YYYY-MM-DD)'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'payment_instrument_id' => ['type' => 'integer', 'description' => 'Payment instrument ID'],
            'source' => ['type' => 'string', 'description' => 'Source of contribution'],
            'invoice_id' => ['type' => 'string', 'description' => 'Invoice ID']
          ],
          'required' => ['contact_id', 'total_amount']
        ]
      ],
      [
        'name' => 'contribution_update',
        'description' => 'Update an existing contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Contribution ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'total_amount' => ['type' => 'number', 'description' => 'Total amount'],
            'contribution_type_id' => ['type' => 'integer', 'description' => 'Contribution type ID'],
            'receive_date' => ['type' => 'string', 'description' => 'Receive date (YYYY-MM-DD)'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'payment_instrument_id' => ['type' => 'integer', 'description' => 'Payment instrument ID'],
            'source' => ['type' => 'string', 'description' => 'Source of contribution'],
            'invoice_id' => ['type' => 'string', 'description' => 'Invoice ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'contribution_delete',
        'description' => 'Delete a contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Contribution ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'contributionrecur_search',
        'description' => 'Search recurring contributions using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'amount' => ['type' => 'number', 'description' => 'Contribution amount'],
            'frequency_unit' => ['type' => 'string', 'description' => 'Payment recurrence (day, week, month, year)'],
            'frequency_interval' => ['type' => 'integer', 'description' => 'Frequency interval'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'id' => ['type' => 'integer', 'description' => 'Recurring contribution ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'contributionrecur_create',
        'description' => 'Create a new recurring contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'amount' => ['type' => 'number', 'description' => 'Contribution amount'],
            'currency' => ['type' => 'string', 'description' => 'Currency code (e.g., USD, EUR)'],
            'frequency_unit' => ['type' => 'string', 'description' => 'Payment recurrence (day, week, month, year)', 'enum' => ['day', 'week', 'month', 'year']],
            'frequency_interval' => ['type' => 'integer', 'description' => 'Frequency interval'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'installments' => ['type' => 'integer', 'description' => 'Total number of payments'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'payment_instrument_id' => ['type' => 'integer', 'description' => 'Payment instrument ID'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'processor_id' => ['type' => 'string', 'description' => 'Payment processor ID']
          ],
          'required' => ['contact_id', 'amount', 'currency', 'frequency_unit', 'frequency_interval', 'start_date']
        ]
      ],
      [
        'name' => 'contributionrecur_update',
        'description' => 'Update an existing recurring contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Recurring contribution ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'amount' => ['type' => 'number', 'description' => 'Contribution amount'],
            'currency' => ['type' => 'string', 'description' => 'Currency code (e.g., USD, EUR)'],
            'frequency_unit' => ['type' => 'string', 'description' => 'Payment recurrence (day, week, month, year)', 'enum' => ['day', 'week', 'month', 'year']],
            'frequency_interval' => ['type' => 'integer', 'description' => 'Frequency interval'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'installments' => ['type' => 'integer', 'description' => 'Total number of payments'],
            'contribution_status_id' => ['type' => 'integer', 'description' => 'Contribution status ID'],
            'payment_instrument_id' => ['type' => 'integer', 'description' => 'Payment instrument ID'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'processor_id' => ['type' => 'string', 'description' => 'Payment processor ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'contributionrecur_delete',
        'description' => 'Delete a recurring contribution',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Recurring contribution ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'participant_search',
        'description' => 'Search participants using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'event_id' => ['type' => 'integer', 'description' => 'Event ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Participant status ID'],
            'role_id' => ['type' => 'integer', 'description' => 'Participant role ID'],
            'register_date' => ['type' => 'string', 'description' => 'Registration date (YYYY-MM-DD)'],
            'source' => ['type' => 'string', 'description' => 'Registration source'],
            'id' => ['type' => 'integer', 'description' => 'Participant ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'participant_create',
        'description' => 'Create a new participant',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'event_id' => ['type' => 'integer', 'description' => 'Event ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Participant status ID'],
            'role_id' => ['type' => 'integer', 'description' => 'Participant role ID'],
            'register_date' => ['type' => 'string', 'description' => 'Registration date (YYYY-MM-DD)'],
            'source' => ['type' => 'string', 'description' => 'Registration source']
          ],
          'required' => ['contact_id', 'event_id']
        ]
      ],
      [
        'name' => 'participant_update',
        'description' => 'Update an existing participant',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Participant ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'event_id' => ['type' => 'integer', 'description' => 'Event ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Participant status ID'],
            'role_id' => ['type' => 'integer', 'description' => 'Participant role ID'],
            'register_date' => ['type' => 'string', 'description' => 'Registration date (YYYY-MM-DD)'],
            'source' => ['type' => 'string', 'description' => 'Registration source']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'participant_delete',
        'description' => 'Delete a participant',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Participant ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'membership_search',
        'description' => 'Search memberships using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'membership_type_id' => ['type' => 'integer', 'description' => 'Membership type ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Membership status ID'],
            'join_date' => ['type' => 'string', 'description' => 'Join date (YYYY-MM-DD)'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'is_override' => ['type' => 'boolean', 'description' => 'Override membership status'],
            'is_test' => ['type' => 'boolean', 'description' => 'Test membership'],
            'is_pay_later' => ['type' => 'boolean', 'description' => 'Pay later flag'],
            'id' => ['type' => 'integer', 'description' => 'Membership ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'membership_create',
        'description' => 'Create a new membership',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'membership_type_id' => ['type' => 'integer', 'description' => 'Membership type ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Membership status ID'],
            'join_date' => ['type' => 'string', 'description' => 'Join date (YYYY-MM-DD)'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'is_override' => ['type' => 'boolean', 'description' => 'Override membership status'],
            'is_test' => ['type' => 'boolean', 'description' => 'Test membership'],
            'is_pay_later' => ['type' => 'boolean', 'description' => 'Pay later flag']
          ],
          'required' => ['contact_id', 'membership_type_id', 'status_id']
        ]
      ],
      [
        'name' => 'membership_update',
        'description' => 'Update an existing membership',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Membership ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'membership_type_id' => ['type' => 'integer', 'description' => 'Membership type ID'],
            'status_id' => ['type' => 'integer', 'description' => 'Membership status ID'],
            'join_date' => ['type' => 'string', 'description' => 'Join date (YYYY-MM-DD)'],
            'start_date' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
            'end_date' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
            'is_override' => ['type' => 'boolean', 'description' => 'Override membership status'],
            'is_test' => ['type' => 'boolean', 'description' => 'Test membership'],
            'is_pay_later' => ['type' => 'boolean', 'description' => 'Pay later flag']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'membership_delete',
        'description' => 'Delete a membership',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Membership ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'activity_search',
        'description' => 'Search activities using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'source_contact_id' => ['type' => 'integer', 'description' => 'Source contact ID'],
            'activity_type_id' => ['type' => 'integer', 'description' => 'Activity type ID'],
            'subject' => ['type' => 'string', 'description' => 'Activity subject'],
            'activity_date_time' => ['type' => 'string', 'description' => 'Activity date and time (YYYY-MM-DD HH:MM:SS)'],
            'status_id' => ['type' => 'integer', 'description' => 'Activity status ID'],
            'priority_id' => ['type' => 'integer', 'description' => 'Activity priority ID'],
            'details' => ['type' => 'string', 'description' => 'Activity details'],
            'duration' => ['type' => 'integer', 'description' => 'Activity duration in minutes'],
            'location' => ['type' => 'string', 'description' => 'Activity location'],
            'id' => ['type' => 'integer', 'description' => 'Activity ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'activity_create',
        'description' => 'Create a new activity',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'source_contact_id' => ['type' => 'integer', 'description' => 'Source contact ID'],
            'activity_type_id' => ['type' => 'integer', 'description' => 'Activity type ID'],
            'subject' => ['type' => 'string', 'description' => 'Activity subject'],
            'activity_date_time' => ['type' => 'string', 'description' => 'Activity date and time (YYYY-MM-DD HH:MM:SS)'],
            'status_id' => ['type' => 'integer', 'description' => 'Activity status ID'],
            'priority_id' => ['type' => 'integer', 'description' => 'Activity priority ID'],
            'details' => ['type' => 'string', 'description' => 'Activity details'],
            'duration' => ['type' => 'integer', 'description' => 'Activity duration in minutes'],
            'location' => ['type' => 'string', 'description' => 'Activity location']
          ],
          'required' => ['source_contact_id', 'activity_type_id']
        ]
      ],
      [
        'name' => 'activity_update',
        'description' => 'Update an existing activity',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Activity ID'],
            'source_contact_id' => ['type' => 'integer', 'description' => 'Source contact ID'],
            'activity_type_id' => ['type' => 'integer', 'description' => 'Activity type ID'],
            'subject' => ['type' => 'string', 'description' => 'Activity subject'],
            'activity_date_time' => ['type' => 'string', 'description' => 'Activity date and time (YYYY-MM-DD HH:MM:SS)'],
            'status_id' => ['type' => 'integer', 'description' => 'Activity status ID'],
            'priority_id' => ['type' => 'integer', 'description' => 'Activity priority ID'],
            'details' => ['type' => 'string', 'description' => 'Activity details'],
            'duration' => ['type' => 'integer', 'description' => 'Activity duration in minutes'],
            'location' => ['type' => 'string', 'description' => 'Activity location']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'activity_delete',
        'description' => 'Delete an activity',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Activity ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'contributionpage_search',
        'description' => 'Search contribution pages using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Contribution page ID'],
            'title' => ['type' => 'string', 'description' => 'Page title'],
            'is_monetary' => ['type' => 'boolean', 'description' => 'Whether monetary contribution is required'],
            'is_recur' => ['type' => 'boolean', 'description' => 'Recurring donation options'],
            'is_active' => ['type' => 'boolean', 'description' => 'Page active status'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'event_search',
        'description' => 'Search events using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Event ID'],
            'title' => ['type' => 'string', 'description' => 'Event title'],
            'start_date' => ['type' => 'string', 'description' => 'Event start date (YYYY-MM-DD)'],
            'end_date' => ['type' => 'string', 'description' => 'Event end date (YYYY-MM-DD)'],
            'is_public' => ['type' => 'boolean', 'description' => 'Public event status'],
            'is_online_registration' => ['type' => 'boolean', 'description' => 'Online registration availability'],
            'is_active' => ['type' => 'boolean', 'description' => 'Event active status'],
            'event_type_id' => ['type' => 'integer', 'description' => 'Event type ID'],
            'max_participants' => ['type' => 'integer', 'description' => 'Maximum participants'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'event_create',
        'description' => 'Create a new event',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'title' => ['type' => 'string', 'description' => 'Event title'],
            'start_date' => ['type' => 'string', 'description' => 'Event start date (YYYY-MM-DD HH:MM:SS)'],
            'end_date' => ['type' => 'string', 'description' => 'Event end date (YYYY-MM-DD HH:MM:SS)'],
            'is_public' => ['type' => 'boolean', 'description' => 'Public event status'],
            'is_online_registration' => ['type' => 'boolean', 'description' => 'Online registration availability'],
            'is_active' => ['type' => 'boolean', 'description' => 'Event active status'],
            'event_type_id' => ['type' => 'integer', 'description' => 'Event type ID'],
            'max_participants' => ['type' => 'integer', 'description' => 'Maximum participants'],
            'summary' => ['type' => 'string', 'description' => 'Event summary'],
            'description' => ['type' => 'string', 'description' => 'Event description']
          ],
          'required' => ['title', 'start_date']
        ]
      ],
      [
        'name' => 'event_update',
        'description' => 'Update an existing event',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Event ID'],
            'title' => ['type' => 'string', 'description' => 'Event title'],
            'start_date' => ['type' => 'string', 'description' => 'Event start date (YYYY-MM-DD HH:MM:SS)'],
            'end_date' => ['type' => 'string', 'description' => 'Event end date (YYYY-MM-DD HH:MM:SS)'],
            'is_public' => ['type' => 'boolean', 'description' => 'Public event status'],
            'is_online_registration' => ['type' => 'boolean', 'description' => 'Online registration availability'],
            'is_active' => ['type' => 'boolean', 'description' => 'Event active status'],
            'event_type_id' => ['type' => 'integer', 'description' => 'Event type ID'],
            'max_participants' => ['type' => 'integer', 'description' => 'Maximum participants'],
            'summary' => ['type' => 'string', 'description' => 'Event summary'],
            'description' => ['type' => 'string', 'description' => 'Event description']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'event_delete',
        'description' => 'Delete an event',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Event ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'group_search',
        'description' => 'Search groups using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Group ID'],
            'name' => ['type' => 'string', 'description' => 'Group name'],
            'title' => ['type' => 'string', 'description' => 'Group title'],
            'description' => ['type' => 'string', 'description' => 'Group description'],
            'is_active' => ['type' => 'boolean', 'description' => 'Group active status'],
            'visibility' => ['type' => 'string', 'description' => 'Group visibility'],
            'group_type' => ['type' => 'string', 'description' => 'Group type'],
            'domain_id' => ['type' => 'integer', 'description' => 'Domain ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'group_create',
        'description' => 'Create a new group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'name' => ['type' => 'string', 'description' => 'Group name'],
            'title' => ['type' => 'string', 'description' => 'Group title'],
            'description' => ['type' => 'string', 'description' => 'Group description'],
            'is_active' => ['type' => 'boolean', 'description' => 'Group active status'],
            'visibility' => ['type' => 'string', 'description' => 'Group visibility'],
            'group_type' => ['type' => 'string', 'description' => 'Group type'],
            'domain_id' => ['type' => 'integer', 'description' => 'Domain ID']
          ],
          'required' => ['name', 'title']
        ]
      ],
      [
        'name' => 'group_update',
        'description' => 'Update an existing group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Group ID'],
            'name' => ['type' => 'string', 'description' => 'Group name'],
            'title' => ['type' => 'string', 'description' => 'Group title'],
            'description' => ['type' => 'string', 'description' => 'Group description'],
            'is_active' => ['type' => 'boolean', 'description' => 'Group active status'],
            'visibility' => ['type' => 'string', 'description' => 'Group visibility'],
            'group_type' => ['type' => 'string', 'description' => 'Group type'],
            'domain_id' => ['type' => 'integer', 'description' => 'Domain ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'group_delete',
        'description' => 'Delete a group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Group ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'email_search',
        'description' => 'Search email records using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Email ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'email' => ['type' => 'string', 'description' => 'Email address'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary email flag'],
            'is_billing' => ['type' => 'boolean', 'description' => 'Billing email flag'],
            'on_hold' => ['type' => 'boolean', 'description' => 'Email on hold flag'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'email_create',
        'description' => 'Create a new email record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'email' => ['type' => 'string', 'description' => 'Email address'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary email flag'],
            'is_billing' => ['type' => 'boolean', 'description' => 'Billing email flag'],
            'on_hold' => ['type' => 'boolean', 'description' => 'Email on hold flag']
          ],
          'required' => ['contact_id', 'location_type_id', 'email']
        ]
      ],
      [
        'name' => 'email_update',
        'description' => 'Update an existing email record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Email ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'email' => ['type' => 'string', 'description' => 'Email address'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary email flag'],
            'is_billing' => ['type' => 'boolean', 'description' => 'Billing email flag'],
            'on_hold' => ['type' => 'boolean', 'description' => 'Email on hold flag']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'email_delete',
        'description' => 'Delete an email record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Email ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'phone_search',
        'description' => 'Search phone records using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Phone ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'phone' => ['type' => 'string', 'description' => 'Phone number'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary phone flag'],
            'phone_type_id' => ['type' => 'integer', 'description' => 'Phone type ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'phone_create',
        'description' => 'Create a new phone record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'phone' => ['type' => 'string', 'description' => 'Phone number'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary phone flag'],
            'phone_type_id' => ['type' => 'integer', 'description' => 'Phone type ID']
          ],
          'required' => ['contact_id']
        ]
      ],
      [
        'name' => 'phone_update',
        'description' => 'Update an existing phone record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Phone ID'],
            'contact_id' => ['type' => 'integer', 'description' => 'Contact ID'],
            'location_type_id' => ['type' => 'integer', 'description' => 'Location type ID'],
            'phone' => ['type' => 'string', 'description' => 'Phone number'],
            'is_primary' => ['type' => 'boolean', 'description' => 'Primary phone flag'],
            'phone_type_id' => ['type' => 'integer', 'description' => 'Phone type ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'phone_delete',
        'description' => 'Delete a phone record',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Phone ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'customgroup_search',
        'description' => 'Search custom groups using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'title' => ['type' => 'string', 'description' => 'Custom group title'],
            'name' => ['type' => 'string', 'description' => 'Custom group name'],
            'extends' => ['type' => 'string', 'description' => 'Entity type the group extends'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'customgroup_create',
        'description' => 'Create a new custom group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'title' => ['type' => 'string', 'description' => 'Custom group title'],
            'name' => ['type' => 'string', 'description' => 'Custom group name'],
            'extends' => ['type' => 'string', 'description' => 'Entity type the group extends'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'weight' => ['type' => 'integer', 'description' => 'Group weight/order'],
            'collapse_display' => ['type' => 'boolean', 'description' => 'Collapse display flag'],
            'style' => ['type' => 'string', 'description' => 'Display style']
          ],
          'required' => ['title', 'extends']
        ]
      ],
      [
        'name' => 'customgroup_update',
        'description' => 'Update an existing custom group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'title' => ['type' => 'string', 'description' => 'Custom group title'],
            'name' => ['type' => 'string', 'description' => 'Custom group name'],
            'extends' => ['type' => 'string', 'description' => 'Entity type the group extends'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'weight' => ['type' => 'integer', 'description' => 'Group weight/order'],
            'collapse_display' => ['type' => 'boolean', 'description' => 'Collapse display flag'],
            'style' => ['type' => 'string', 'description' => 'Display style']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'customgroup_delete',
        'description' => 'Delete a custom group',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom group ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'customfield_search',
        'description' => 'Search custom fields using various filters',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom field ID'],
            'custom_group_id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'label' => ['type' => 'string', 'description' => 'Field label'],
            'name' => ['type' => 'string', 'description' => 'Field name'],
            'data_type' => ['type' => 'string', 'description' => 'Data type'],
            'html_type' => ['type' => 'string', 'description' => 'HTML input type'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'customfield_create',
        'description' => 'Create a new custom field',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'custom_group_id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'label' => ['type' => 'string', 'description' => 'Field label'],
            'name' => ['type' => 'string', 'description' => 'Field name'],
            'data_type' => ['type' => 'string', 'description' => 'Data type (String, Int, Float, Money, Memo, Date, Boolean, etc.)'],
            'html_type' => ['type' => 'string', 'description' => 'HTML input type (Text, TextArea, Select, Radio, CheckBox, etc.)'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'is_required' => ['type' => 'boolean', 'description' => 'Required field flag'],
            'weight' => ['type' => 'integer', 'description' => 'Field weight/order'],
            'option_group_id' => ['type' => 'integer', 'description' => 'Option group ID for select fields']
          ],
          'required' => ['custom_group_id', 'label', 'data_type', 'html_type']
        ]
      ],
      [
        'name' => 'customfield_update',
        'description' => 'Update an existing custom field',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom field ID'],
            'custom_group_id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'label' => ['type' => 'string', 'description' => 'Field label'],
            'name' => ['type' => 'string', 'description' => 'Field name'],
            'data_type' => ['type' => 'string', 'description' => 'Data type'],
            'html_type' => ['type' => 'string', 'description' => 'HTML input type'],
            'is_active' => ['type' => 'boolean', 'description' => 'Active status'],
            'is_required' => ['type' => 'boolean', 'description' => 'Required field flag'],
            'weight' => ['type' => 'integer', 'description' => 'Field weight/order'],
            'option_group_id' => ['type' => 'integer', 'description' => 'Option group ID for select fields']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'customfield_delete',
        'description' => 'Delete a custom field',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer', 'description' => 'Custom field ID']
          ],
          'required' => ['id']
        ]
      ],
      [
        'name' => 'customvalue_search',
        'description' => 'Search custom values for entities',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'entity_table' => ['type' => 'string', 'description' => 'Entity table name (Contact, Individual, etc.)'],
            'entity_id' => ['type' => 'integer', 'description' => 'Entity ID'],
            'custom_group_id' => ['type' => 'integer', 'description' => 'Custom group ID'],
            'limit' => ['type' => 'integer', 'description' => 'Number of results to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination'],
            'sort' => ['type' => 'string', 'description' => 'Sort field and direction (e.g., "total_amount desc", "receive_date asc")']
          ]
        ]
      ],
      [
        'name' => 'customvalue_create',
        'description' => 'Create or update custom values for an entity',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'entity_table' => ['type' => 'string', 'description' => 'Entity table name (Contact, Individual, etc.)'],
            'entity_id' => ['type' => 'integer', 'description' => 'Entity ID'],
            'custom_values' => ['type' => 'object', 'description' => 'Custom field values as key-value pairs']
          ],
          'required' => ['entity_table', 'entity_id', 'custom_values']
        ]
      ],
      [
        'name' => 'customvalue_delete',
        'description' => 'Delete custom values for an entity',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'entity_table' => ['type' => 'string', 'description' => 'Entity table name'],
            'entity_id' => ['type' => 'integer', 'description' => 'Entity ID'],
            'custom_field_id' => ['type' => 'integer', 'description' => 'Custom field ID to delete value for']
          ],
          'required' => ['entity_table', 'entity_id']
        ]
      ],
      [
        'name' => 'getoptions',
        'description' => 'Get option lists for entity fields (dropdown values, select options)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'entity' => ['type' => 'string', 'description' => 'Entity name (Contact, Contribution, Activity, etc.)'],
            'field' => ['type' => 'string', 'description' => 'Field name to get options for'],
            'context' => ['type' => 'string', 'description' => 'Context for options (create, edit, search, etc.)'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array'],
            'limit' => ['type' => 'integer', 'description' => 'Number of options to return'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination']
          ],
          'required' => ['entity', 'field']
        ]
      ],
      [
        'name' => 'contact_getoptions',
        'description' => 'Get contact-specific option lists (contact types, prefixes, suffixes, genders)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['contact_type', 'contact_sub_type', 'prefix_id', 'suffix_id', 'gender_id']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'location_getoptions',
        'description' => 'Get location-specific option lists (location types, countries, states)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['location_type_id', 'worldregion_id', 'country_id', 'state_province_id', 'phone_type_id']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array'],
            'country_id' => ['type' => 'integer', 'description' => 'Country ID for state/province options']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'contribution_getoptions',
        'description' => 'Get contribution-specific option lists (types, statuses, payment methods)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['contribution_type_id', 'contribution_status_id', 'payment_instrument_id', 'currency']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'activity_getoptions',
        'description' => 'Get activity-specific option lists (types, statuses, priorities)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['activity_type_id', 'status_id', 'priority_id']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'membership_getoptions',
        'description' => 'Get membership-specific option lists (types, statuses)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['membership_type_id', 'status_id']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'event_getoptions',
        'description' => 'Get event-specific option lists (types, participant statuses, roles)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'field' => ['type' => 'string', 'description' => 'Field name', 'enum' => ['event_type_id', 'participant_status_id', 'participant_role_id']],
            'context' => ['type' => 'string', 'description' => 'Context for options'],
            'sequential' => ['type' => 'boolean', 'description' => 'Return sequential array']
          ],
          'required' => ['field']
        ]
      ],
      [
        'name' => 'civicrm_api',
        'description' => 'Execute general CiviCRM API calls (get, getoptions)',
        'inputSchema' => [
          'type' => 'object',
          'properties' => [
            'entity' => ['type' => 'string', 'description' => 'API entity'],
            'action' => ['type' => 'string', 'description' => 'API action (get or getoptions only)', 'enum' => ['get', 'getoptions']],
            'params' => ['type' => 'object', 'description' => 'API parameters']
          ],
          'required' => ['entity', 'action']
        ]
      ]
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
      case 'contact_create':
        return $this->handleContactCreate($arguments, $id);
      case 'contact_update':
        return $this->handleContactUpdate($arguments, $id);
      case 'contact_delete':
        return $this->handleContactDelete($arguments, $id);
      case 'contribution_search':
        return $this->handleContributionSearch($arguments, $id);
      case 'contribution_create':
        return $this->handleContributionCreate($arguments, $id);
      case 'contribution_update':
        return $this->handleContributionUpdate($arguments, $id);
      case 'contribution_delete':
        return $this->handleContributionDelete($arguments, $id);
      case 'contributionrecur_search':
        return $this->handleContributionRecurSearch($arguments, $id);
      case 'contributionrecur_create':
        return $this->handleContributionRecurCreate($arguments, $id);
      case 'contributionrecur_update':
        return $this->handleContributionRecurUpdate($arguments, $id);
      case 'contributionrecur_delete':
        return $this->handleContributionRecurDelete($arguments, $id);
      case 'participant_search':
        return $this->handleParticipantSearch($arguments, $id);
      case 'participant_create':
        return $this->handleParticipantCreate($arguments, $id);
      case 'participant_update':
        return $this->handleParticipantUpdate($arguments, $id);
      case 'participant_delete':
        return $this->handleParticipantDelete($arguments, $id);
      case 'membership_search':
        return $this->handleMembershipSearch($arguments, $id);
      case 'membership_create':
        return $this->handleMembershipCreate($arguments, $id);
      case 'membership_update':
        return $this->handleMembershipUpdate($arguments, $id);
      case 'membership_delete':
        return $this->handleMembershipDelete($arguments, $id);
      case 'activity_search':
        return $this->handleActivitySearch($arguments, $id);
      case 'activity_create':
        return $this->handleActivityCreate($arguments, $id);
      case 'activity_update':
        return $this->handleActivityUpdate($arguments, $id);
      case 'activity_delete':
        return $this->handleActivityDelete($arguments, $id);
      case 'contributionpage_search':
        return $this->handleContributionPageSearch($arguments, $id);
      case 'event_search':
        return $this->handleEventSearch($arguments, $id);
      case 'event_create':
        return $this->handleEventCreate($arguments, $id);
      case 'event_update':
        return $this->handleEventUpdate($arguments, $id);
      case 'event_delete':
        return $this->handleEventDelete($arguments, $id);
      case 'group_search':
        return $this->handleGroupSearch($arguments, $id);
      case 'group_create':
        return $this->handleGroupCreate($arguments, $id);
      case 'group_update':
        return $this->handleGroupUpdate($arguments, $id);
      case 'group_delete':
        return $this->handleGroupDelete($arguments, $id);
      case 'email_search':
        return $this->handleEmailSearch($arguments, $id);
      case 'email_create':
        return $this->handleEmailCreate($arguments, $id);
      case 'email_update':
        return $this->handleEmailUpdate($arguments, $id);
      case 'email_delete':
        return $this->handleEmailDelete($arguments, $id);
      case 'phone_search':
        return $this->handlePhoneSearch($arguments, $id);
      case 'phone_create':
        return $this->handlePhoneCreate($arguments, $id);
      case 'phone_update':
        return $this->handlePhoneUpdate($arguments, $id);
      case 'phone_delete':
        return $this->handlePhoneDelete($arguments, $id);
      case 'customgroup_search':
        return $this->handleCustomGroupSearch($arguments, $id);
      case 'customgroup_create':
        return $this->handleCustomGroupCreate($arguments, $id);
      case 'customgroup_update':
        return $this->handleCustomGroupUpdate($arguments, $id);
      case 'customgroup_delete':
        return $this->handleCustomGroupDelete($arguments, $id);
      case 'customfield_search':
        return $this->handleCustomFieldSearch($arguments, $id);
      case 'customfield_create':
        return $this->handleCustomFieldCreate($arguments, $id);
      case 'customfield_update':
        return $this->handleCustomFieldUpdate($arguments, $id);
      case 'customfield_delete':
        return $this->handleCustomFieldDelete($arguments, $id);
      case 'customvalue_search':
        return $this->handleCustomValueSearch($arguments, $id);
      case 'customvalue_create':
        return $this->handleCustomValueCreate($arguments, $id);
      case 'customvalue_delete':
        return $this->handleCustomValueDelete($arguments, $id);
      case 'getoptions':
        return $this->handleGetOptions($arguments, $id);
      case 'contact_getoptions':
        return $this->handleContactGetOptions($arguments, $id);
      case 'location_getoptions':
        return $this->handleLocationGetOptions($arguments, $id);
      case 'contribution_getoptions':
        return $this->handleContributionGetOptions($arguments, $id);
      case 'activity_getoptions':
        return $this->handleActivityGetOptions($arguments, $id);
      case 'membership_getoptions':
        return $this->handleMembershipGetOptions($arguments, $id);
      case 'event_getoptions':
        return $this->handleEventGetOptions($arguments, $id);
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
    
    return $this->executeApiCall('Contact', 'get', $apiParams, $id);
  }

  /**
   * Handle contact create
   */
  private function handleContactCreate($arguments, $id) {
    $requiredFields = ['contact_type'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_type', 'display_name', 'first_name', 'last_name', 'organization_name', 'email', 'phone', 'birth_date', 'gender_id', 'external_identifier'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contact', 'create', $apiParams, $id);
  }

  /**
   * Handle contact update
   */
  private function handleContactUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_type', 'display_name', 'first_name', 'last_name', 'organization_name', 'email', 'phone', 'birth_date', 'gender_id', 'external_identifier'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contact', 'create', $apiParams, $id);
  }

  /**
   * Handle contact delete
   */
  private function handleContactDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Contact', 'delete', ['id' => $arguments['id']], $id);
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
    
    return $this->executeApiCall('Contribution', 'get', $apiParams, $id);
  }

  /**
   * Handle contribution create
   */
  private function handleContributionCreate($arguments, $id) {
    $requiredFields = ['contact_id', 'total_amount'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'total_amount', 'contribution_type_id', 'receive_date', 'contribution_status_id', 'payment_instrument_id', 'source', 'invoice_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contribution', 'create', $apiParams, $id);
  }

  /**
   * Handle contribution update
   */
  private function handleContributionUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'total_amount', 'contribution_type_id', 'receive_date', 'contribution_status_id', 'payment_instrument_id', 'source', 'invoice_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contribution', 'create', $apiParams, $id);
  }

  /**
   * Handle contribution delete
   */
  private function handleContributionDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Contribution', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle contribution recur search
   */
  private function handleContributionRecurSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['contact_id', 'amount', 'frequency_unit', 'frequency_interval', 'contribution_status_id', 'start_date', 'end_date', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('ContributionRecur', 'get', $apiParams, $id);
  }

  /**
   * Handle contribution recur create
   */
  private function handleContributionRecurCreate($arguments, $id) {
    $requiredFields = ['contact_id', 'amount', 'currency', 'frequency_unit', 'frequency_interval', 'start_date'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'amount', 'currency', 'frequency_unit', 'frequency_interval', 'start_date', 'installments', 'contribution_status_id', 'payment_instrument_id', 'end_date', 'processor_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('ContributionRecur', 'create', $apiParams, $id);
  }

  /**
   * Handle contribution recur update
   */
  private function handleContributionRecurUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'amount', 'currency', 'frequency_unit', 'frequency_interval', 'start_date', 'installments', 'contribution_status_id', 'payment_instrument_id', 'end_date', 'processor_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('ContributionRecur', 'create', $apiParams, $id);
  }

  /**
   * Handle contribution recur delete
   */
  private function handleContributionRecurDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('ContributionRecur', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle participant search
   */
  private function handleParticipantSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['contact_id', 'event_id', 'status_id', 'role_id', 'register_date', 'source', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Participant', 'get', $apiParams, $id);
  }

  /**
   * Handle participant create
   */
  private function handleParticipantCreate($arguments, $id) {
    $requiredFields = ['contact_id', 'event_id'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'event_id', 'status_id', 'role_id', 'register_date', 'source'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Participant', 'create', $apiParams, $id);
  }

  /**
   * Handle participant update
   */
  private function handleParticipantUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'event_id', 'status_id', 'role_id', 'register_date', 'source'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Participant', 'create', $apiParams, $id);
  }

  /**
   * Handle participant delete
   */
  private function handleParticipantDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Participant', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle membership search
   */
  private function handleMembershipSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['contact_id', 'membership_type_id', 'status_id', 'join_date', 'start_date', 'end_date', 'is_override', 'is_test', 'is_pay_later', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Membership', 'get', $apiParams, $id);
  }

  /**
   * Handle membership create
   */
  private function handleMembershipCreate($arguments, $id) {
    $requiredFields = ['contact_id', 'membership_type_id', 'status_id'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'membership_type_id', 'status_id', 'join_date', 'start_date', 'end_date', 'is_override', 'is_test', 'is_pay_later'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Membership', 'create', $apiParams, $id);
  }

  /**
   * Handle membership update
   */
  private function handleMembershipUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'membership_type_id', 'status_id', 'join_date', 'start_date', 'end_date', 'is_override', 'is_test', 'is_pay_later'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Membership', 'create', $apiParams, $id);
  }

  /**
   * Handle membership delete
   */
  private function handleMembershipDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Membership', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle activity search
   */
  private function handleActivitySearch($arguments, $id) {
    $apiParams = [];
    $fields = ['source_contact_id', 'activity_type_id', 'subject', 'activity_date_time', 'status_id', 'priority_id', 'details', 'duration', 'location', 'id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Activity', 'get', $apiParams, $id);
  }

  /**
   * Handle activity create
   */
  private function handleActivityCreate($arguments, $id) {
    $requiredFields = ['source_contact_id', 'activity_type_id'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['source_contact_id', 'activity_type_id', 'subject', 'activity_date_time', 'status_id', 'priority_id', 'details', 'duration', 'location'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Activity', 'create', $apiParams, $id);
  }

  /**
   * Handle activity update
   */
  private function handleActivityUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'source_contact_id', 'activity_type_id', 'subject', 'activity_date_time', 'status_id', 'priority_id', 'details', 'duration', 'location'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Activity', 'create', $apiParams, $id);
  }

  /**
   * Handle activity delete
   */
  private function handleActivityDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Activity', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle contribution page search
   */
  private function handleContributionPageSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'title', 'is_monetary', 'is_recur', 'is_active', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('ContributionPage', 'get', $apiParams, $id);
  }

  /**
   * Handle event search
   */
  private function handleEventSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'title', 'start_date', 'end_date', 'is_public', 'is_online_registration', 'is_active', 'event_type_id', 'max_participants', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Event', 'get', $apiParams, $id);
  }

  /**
   * Handle event create
   */
  private function handleEventCreate($arguments, $id) {
    $requiredFields = ['title', 'start_date'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['title', 'start_date', 'end_date', 'is_public', 'is_online_registration', 'is_active', 'event_type_id', 'max_participants', 'summary', 'description'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Event', 'create', $apiParams, $id);
  }

  /**
   * Handle event update
   */
  private function handleEventUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'title', 'start_date', 'end_date', 'is_public', 'is_online_registration', 'is_active', 'event_type_id', 'max_participants', 'summary', 'description'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Event', 'create', $apiParams, $id);
  }

  /**
   * Handle event delete
   */
  private function handleEventDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Event', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle group search
   */
  private function handleGroupSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'name', 'title', 'description', 'is_active', 'visibility', 'group_type', 'domain_id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Group', 'get', $apiParams, $id);
  }

  /**
   * Handle group create
   */
  private function handleGroupCreate($arguments, $id) {
    $requiredFields = ['name', 'title'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['name', 'title', 'description', 'is_active', 'visibility', 'group_type', 'domain_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Group', 'create', $apiParams, $id);
  }

  /**
   * Handle group update
   */
  private function handleGroupUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'name', 'title', 'description', 'is_active', 'visibility', 'group_type', 'domain_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Group', 'create', $apiParams, $id);
  }

  /**
   * Handle group delete
   */
  private function handleGroupDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Group', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle email search
   */
  private function handleEmailSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'contact_id', 'location_type_id', 'email', 'is_primary', 'is_billing', 'on_hold', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Email', 'get', $apiParams, $id);
  }

  /**
   * Handle email create
   */
  private function handleEmailCreate($arguments, $id) {
    $requiredFields = ['contact_id', 'location_type_id', 'email'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'location_type_id', 'email', 'is_primary', 'is_billing', 'on_hold'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Email', 'create', $apiParams, $id);
  }

  /**
   * Handle email update
   */
  private function handleEmailUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'location_type_id', 'email', 'is_primary', 'is_billing', 'on_hold'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Email', 'create', $apiParams, $id);
  }

  /**
   * Handle email delete
   */
  private function handleEmailDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Email', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle phone search
   */
  private function handlePhoneSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'contact_id', 'location_type_id', 'phone', 'is_primary', 'phone_type_id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Phone', 'get', $apiParams, $id);
  }

  /**
   * Handle phone create
   */
  private function handlePhoneCreate($arguments, $id) {
    $requiredFields = ['contact_id'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['contact_id', 'location_type_id', 'phone', 'is_primary', 'phone_type_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Phone', 'create', $apiParams, $id);
  }

  /**
   * Handle phone update
   */
  private function handlePhoneUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'contact_id', 'location_type_id', 'phone', 'is_primary', 'phone_type_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Phone', 'create', $apiParams, $id);
  }

  /**
   * Handle phone delete
   */
  private function handlePhoneDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('Phone', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle custom group search
   */
  private function handleCustomGroupSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'title', 'name', 'extends', 'is_active', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomGroup', 'get', $apiParams, $id);
  }

  /**
   * Handle custom group create
   */
  private function handleCustomGroupCreate($arguments, $id) {
    $requiredFields = ['title', 'extends'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['title', 'name', 'extends', 'is_active', 'weight', 'collapse_display', 'style'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomGroup', 'create', $apiParams, $id);
  }

  /**
   * Handle custom group update
   */
  private function handleCustomGroupUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'title', 'name', 'extends', 'is_active', 'weight', 'collapse_display', 'style'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomGroup', 'create', $apiParams, $id);
  }

  /**
   * Handle custom group delete
   */
  private function handleCustomGroupDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('CustomGroup', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle custom field search
   */
  private function handleCustomFieldSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['id', 'custom_group_id', 'label', 'name', 'data_type', 'html_type', 'is_active', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomField', 'get', $apiParams, $id);
  }

  /**
   * Handle custom field create
   */
  private function handleCustomFieldCreate($arguments, $id) {
    $requiredFields = ['custom_group_id', 'label', 'data_type', 'html_type'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [];
    $fields = ['custom_group_id', 'label', 'name', 'data_type', 'html_type', 'is_active', 'is_required', 'weight', 'option_group_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomField', 'create', $apiParams, $id);
  }

  /**
   * Handle custom field update
   */
  private function handleCustomFieldUpdate($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    $apiParams = [];
    $fields = ['id', 'custom_group_id', 'label', 'name', 'data_type', 'html_type', 'is_active', 'is_required', 'weight', 'option_group_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomField', 'create', $apiParams, $id);
  }

  /**
   * Handle custom field delete
   */
  private function handleCustomFieldDelete($arguments, $id) {
    if (!isset($arguments['id'])) {
      return $this->error(-32602, 'Missing required field: id', $id);
    }
    
    return $this->executeApiCall('CustomField', 'delete', ['id' => $arguments['id']], $id);
  }

  /**
   * Handle custom value search
   */
  private function handleCustomValueSearch($arguments, $id) {
    $apiParams = [];
    $fields = ['entity_table', 'entity_id', 'custom_group_id', 'limit', 'offset', 'sort'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('CustomValue', 'get', $apiParams, $id);
  }

  /**
   * Handle custom value create
   */
  private function handleCustomValueCreate($arguments, $id) {
    $requiredFields = ['entity_table', 'entity_id', 'custom_values'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [
      'entity_table' => $arguments['entity_table'],
      'entity_id' => $arguments['entity_id']
    ];
    
    // Add custom field values
    foreach ($arguments['custom_values'] as $fieldKey => $value) {
      $apiParams[$fieldKey] = $value;
    }
    
    return $this->executeApiCall('CustomValue', 'create', $apiParams, $id);
  }

  /**
   * Handle custom value delete
   */
  private function handleCustomValueDelete($arguments, $id) {
    $requiredFields = ['entity_table', 'entity_id'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = [
      'entity_table' => $arguments['entity_table'],
      'entity_id' => $arguments['entity_id']
    ];
    
    if (isset($arguments['custom_field_id'])) {
      $apiParams['custom_field_id'] = $arguments['custom_field_id'];
    }
    
    return $this->executeApiCall('CustomValue', 'delete', $apiParams, $id);
  }

  /**
   * Handle generic get options
   */
  private function handleGetOptions($arguments, $id) {
    $requiredFields = ['entity', 'field'];
    foreach ($requiredFields as $field) {
      if (!isset($arguments[$field])) {
        return $this->error(-32602, "Missing required field: $field", $id);
      }
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential', 'limit', 'offset'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall($arguments['entity'], 'getoptions', $apiParams, $id);
  }

  /**
   * Handle contact-specific get options
   */
  private function handleContactGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contact', 'getoptions', $apiParams, $id);
  }

  /**
   * Handle location-specific get options
   */
  private function handleLocationGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential', 'country_id'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    // Determine entity based on field
    $entity = 'Address';
    if ($arguments['field'] === 'phone_type_id') {
      $entity = 'Phone';
    }
    
    return $this->executeApiCall($entity, 'getoptions', $apiParams, $id);
  }

  /**
   * Handle contribution-specific get options
   */
  private function handleContributionGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Contribution', 'getoptions', $apiParams, $id);
  }

  /**
   * Handle activity-specific get options
   */
  private function handleActivityGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Activity', 'getoptions', $apiParams, $id);
  }

  /**
   * Handle membership-specific get options
   */
  private function handleMembershipGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    return $this->executeApiCall('Membership', 'getoptions', $apiParams, $id);
  }

  /**
   * Handle event-specific get options
   */
  private function handleEventGetOptions($arguments, $id) {
    if (!isset($arguments['field'])) {
      return $this->error(-32602, 'Missing required field: field', $id);
    }
    
    $apiParams = ['field' => $arguments['field']];
    $fields = ['context', 'sequential'];
    foreach ($fields as $field) {
      if (isset($arguments[$field])) {
        $apiParams[$field] = $arguments[$field];
      }
    }
    
    // Determine entity based on field
    $entity = 'Event';
    if (strpos($arguments['field'], 'participant_') === 0) {
      $entity = 'Participant';
    }
    
    return $this->executeApiCall($entity, 'getoptions', $apiParams, $id);
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
    return [
      'jsonrpc' => '2.0',
      'error' => [
        'code' => $code,
        'message' => $message
      ],
      'id' => $id
    ];
  }

}