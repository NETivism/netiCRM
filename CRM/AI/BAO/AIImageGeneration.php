<?php

class CRM_AI_BAO_AIImageGeneration extends CRM_AI_DAO_AIImageGeneration {

  // Status constants based on planning document
  const STATUS_SUCCESS = 1;      // Success: Image generated and saved successfully
  const STATUS_PENDING = 2;      // Pending: Request created, waiting to start processing
  const STATUS_FAILED = 4;       // Failed: Error occurred during translation or image generation
  const STATUS_PROCESSING = 5;   // Processing: Currently translating prompt or generating image

  /**
   * Create image generation record
   *
   * @param array $data Record data
   * @return CRM_AI_BAO_AIImageGeneration
   * @throws CRM_Core_Exception
   */
  public static function create(&$data) {
    if (!is_array($data)) {
      throw new CRM_Core_Exception("Data should be an Array.");
    }

    $id = CRM_Utils_Array::value('id', $data);
    $op = $id ? 'edit' : 'create';

    // Auto-set contact ID if not provided
    // Note: contact_id field is not yet implemented in schema
    // This code is prepared for future schema update
    // TEMPORARY FIX: Commented out to resolve double JSON response issue
    // The contact_id field does not exist in the database schema yet
    /*
    if (empty($data['contact_id'])) {
      $session = CRM_Core_Session::singleton();
      $data['contact_id'] = $session->get('userID') ?: NULL;
    }
    */

    // Set created date for new records
    if (!$id && empty($data['created_date'])) {
      $data['created_date'] = date('Y-m-d H:i:s');
    }

    // Trigger pre hooks
    CRM_Utils_Hook::pre($op, 'AIImageGeneration', $id, $data);

    // Save to database
    $imageGeneration = new CRM_AI_DAO_AIImageGeneration();
    $imageGeneration->copyValues($data);
    $imageGeneration->save();

    // Trigger post hooks
    CRM_Utils_Hook::post($op, 'AIImageGeneration', $imageGeneration->id, $imageGeneration);

    // Return complete record
    $params = ['id' => $imageGeneration->id];
    $defaults = [];
    return self::retrieve($params, $defaults);
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params      (reference) an assoc array of name/value pairs
   * @param array $defaults    (reference) an assoc array to hold the flattened values
   *
   * @return object   CRM_AI_BAO_AIImageGeneration object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_AI_DAO_AIImageGeneration', $params, $defaults);
  }

  /**
   * Update generation status
   *
   * @param int $id Record ID
   * @param int $statusId Status ID
   * @param array $additionalData Additional data to update
   * @return bool Success status
   */
  public static function updateStatus($id, $statusId, $additionalData = []) {
    $data = array_merge($additionalData, [
      'id' => $id,
      'status_id' => $statusId
    ]);

    try {
      self::create($data);
      return TRUE;
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message("Failed to update image generation status: " . $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Save generation record with full workflow
   *
   * @param array $data Complete generation data
   * @return CRM_AI_BAO_AIImageGeneration
   */
  public function saveGenerationRecord($data) {
    return self::create($data);
  }

  /**
   * Get generation record by ID
   *
   * @param int $id Record ID
   * @return array|null Generation record data
   */
  public static function getById($id) {
    if (empty($id) || !is_numeric($id)) {
      return NULL;
    }

    $params = ['id' => $id];
    $defaults = [];
    $result = self::retrieve($params, $defaults);

    return $result ? $defaults : NULL;
  }

  /**
   * Get generation statistics
   *
   * @param int $contactId Contact ID (optional, not yet implemented in schema)
   * @param array $params Additional parameters
   * @return array Statistics data
   */
  public static function getStatistics($contactId = NULL, $params = []) {
    $whereClause = "1=1";
    $queryParams = [];
    $paramIndex = 1;

    // Note: contact_id field is not yet implemented in schema
    // if ($contactId) {
    //   $whereClause .= " AND contact_id = %{$paramIndex}";
    //   $queryParams[$paramIndex] = [$contactId, 'Integer'];
    //   $paramIndex++;
    // }

    // Date range filter
    if (!empty($params['date_from'])) {
      $whereClause .= " AND created_date >= %{$paramIndex}";
      $queryParams[$paramIndex] = [$params['date_from'], 'String'];
      $paramIndex++;
    }

    if (!empty($params['date_to'])) {
      $whereClause .= " AND created_date <= %{$paramIndex}";
      $queryParams[$paramIndex] = [$params['date_to'], 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        COUNT(*) as total_generations,
        SUM(CASE WHEN status_id = " . self::STATUS_SUCCESS . " THEN 1 ELSE 0 END) as successful_generations,
        SUM(CASE WHEN status_id = " . self::STATUS_FAILED . " THEN 1 ELSE 0 END) as failed_generations,
        SUM(CASE WHEN status_id = " . self::STATUS_PENDING . " THEN 1 ELSE 0 END) as pending_generations,
        SUM(CASE WHEN status_id = " . self::STATUS_PROCESSING . " THEN 1 ELSE 0 END) as processing_generations
      FROM civicrm_aiimagegeneration
      WHERE {$whereClause}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    $stats = [];
    if ($dao->fetch()) {
      $stats = [
        'total' => $dao->total_generations,
        'successful' => $dao->successful_generations,
        'failed' => $dao->failed_generations,
        'pending' => $dao->pending_generations,
        'processing' => $dao->processing_generations,
        'success_rate' => $dao->total_generations > 0
          ? round(($dao->successful_generations / $dao->total_generations) * 100, 2)
          : 0
      ];
    }

    return $stats;
  }

  /**
   * Clean up failed records older than specified days
   *
   * @param int $days Number of days to keep failed records
   * @return int Number of records deleted
   */
  public static function cleanupFailedRecords($days = 30) {
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $sql = "
      DELETE FROM civicrm_aiimagegeneration
      WHERE status_id = " . self::STATUS_FAILED . "
      AND created_date < %1
    ";

    $queryParams = [1 => [$cutoffDate, 'String']];
    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    return $dao->affectedRows();
  }
}