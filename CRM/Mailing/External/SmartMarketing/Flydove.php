<?php

class CRM_Mailing_External_SmartMarketing_Flydove extends CRM_Mailing_External_SmartMarketing {
  const API_BASE_PATH = 'https://api.flydove.net/index.php?r=api/';
  const BATCH_NUM = 500;

  /**
   * tokens of flydove
   *
   * @var array
   */
  private $_tokens;

  /**
   * API URL of flydove
   *
   * @var string
   */
  private $_apiUrl;

  /**
   * Constructor of flydove smart marketing
   *
   * @param int $providerId
   *
   * @return bool
   */
  public function __construct($providerId) {
    $dao = new CRM_SMS_DAO_Provider();
    $dao->id = $providerId;
    if ($dao->find(TRUE)) {
      if (!empty($dao->api_params)) {
        $apiParams = json_decode($dao->api_params, TRUE);
        if (!empty($apiParams['tokens'])) {
          $this->_tokens = $apiParams['tokens'];
          $this->_apiUrl = $dao->api_url;
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function getRemoteGroups() {
    $groups = array();
    try {
      $results = $this->apiRequestSend('GetGroupList');
      foreach($results as $group) {
        $groups[$group['id']] = $group['name'];
      }
    }
    catch(CRM_Core_Exception $e) {
      $errorMessage =$e->getMessage();
      $errorCode =$e->getErrorCode();
      CRM_Core_Error::debug_log_message("Flydove error - getRemoteGroups: $errorCode $errorMessage");
      CRM_Core_Session::setStatus(ts('Cannot retrive remote group, try again later'), TRUE, 'warning');
    }
    return $groups;
  }

  public function parseSavedData($json) {
    // check all element is good
    return json_decode($json, TRUE);
  }

  /**
   * Send request to flydove
   *
   * @param string $apiType API type to call. Available APIs: GetGroupList, DeleteCustomer, CreateMailFile
   * @param string $data The data to send to API when needed.
   * @return array
   */
  private function apiRequestSend($apiType, $data = NULL) {
    if (!in_array($apiType, array('GetGroupList', 'DeleteCustomer', 'CreateMailFile'))) {
      throw new CRM_Core_Exception("Flydove: API type not supported. Provided api type: $apiType");
    }
    $token = '';
    switch($apiType) {
      case 'GetGroupList':
        $token = !empty($this->_tokens['group']) ? $this->_tokens['group'] : '';
        break;
      case 'DeleteCustomer':
        $token = !empty($this->_tokens['subscribe']) ? $this->_tokens['subscribe'] : '';
        break;
      case 'CreateMailFile':
        $token = !empty($this->_tokens['import']) ? $this->_tokens['import'] : '';
        break;
    }
    if (empty($token)) {
      throw new CRM_Core_Exception("Flydove: The API you call is $apiType. Do not have matches token.");
    }

    $apiUrl = $this->_apiUrl.'?r=api/'.$apiType;
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $postData = array(
      'token' => $token,
      'data' => $data,
    );
    $postFields = http_build_query($postData, "", "&", PHP_QUERY_RFC1738);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'accept: application/json',
      'Content-Type: application/x-www-form-urlencoded',
    ));
    $responseData = curl_exec($ch);
    if(curl_errno($ch)){
      throw new CRM_Core_Exception('Flydove: connection error. CURL:'.curl_error($ch));
    }
    curl_close($ch);
    // Format the response and return it
    $decoded = json_decode($responseData, TRUE);
    if ($decoded) {
      if ($decoded['is_error']) {
        throw new CRM_Core_Exception($decoded['error_message'], $decoded['error_code']);
      }
      else {
        if (!empty($decoded['data'])) {
          return $decoded['data'];
        }
        return TRUE;
      }
    }
    else {
      throw new CRM_Core_Exception('Flydove: response cannot be decode by json. Response:'. $responseData);
    }
  }

  private function batchSchedule($contactIds, $destRemoteGroup, $providerId) {
    $remoteGroups = $this->getRemoteGroups();
    if (isset($remoteGroups[$destRemoteGroup])) {
      if (!empty(count($contactIds))) {
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Flydove').': '.ts('Manually Synchronize'),
          'startCallback' => NULL,
          'startCallbackArgs' => NULL,
          'processCallback' => array($this, 'batchRunning'),
          'processCallbackArgs' => array($contactIds, $destRemoteGroup, $providerId),
          'finishCallback' => NULL,
          'finishCallbackArgs' => NULL,
          'total' => count($contactIds),
          'processed' => 0,
        );
        $batch->start($batchParams);
      }
      else {
        throw new CRM_Core_Exception(ts("Please provide at least 1 contact."));
      }
    }
    else {
      throw new CRM_Core_Exception(ts('Flydove').':'.ts("Group you request doesn't exists in flydove."));
    }
  }

  /**
   * Add contact to remote group
   *
   * @param array $contactIds
   * @param int $destRemoteGroup
   * @param int $providerId
   *
   * @static
   * @return void|array
   */
  public static function addContactToRemote($contactIds, $destRemoteGroup, $providerId) {
    global $civicrm_batch;
    $remoteGroups = $this->getRemoteGroups();
    $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
    $mobileTypeId = $phoneTypes['Mobile'];
    if (isset($remoteGroups[$destRemoteGroup])) {
      if (!empty($contactIds)) {
        $offset = $limit = 0;
        if ($civicrm_batch) {
          if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
            $offset = $civicrm_batch->data['processed'];
          }
          $limit = 10;
        }
        $slices = array_slice($contactIds, $offset, self::BATCH_NUM);
        $sliceResults = array();
        foreach($slices as $numOfSlice => $ids) {
          if (!empty($limit) && $numOfSlice >= $limit) {
            break;
          }
          $syncData = array();
          $queryParams = array();
          $returnProperties = array(
            'sort_name' => 1,
            'individual_prefix' => 1,
            'email' => 1,
            'birth_date' => 1,
            'do_not_email' => 1,
            'is_deceased' => 1,
          );
          foreach ($ids as $contactId) {
            $queryParams[] = array(
              CRM_Core_Form::CB_PREFIX.$contactId, '=', 1, 0, 0,
            );
          }
          $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties);
          $numContacts = count($ids);
          $details = $query->apiQuery($queryParams, $returnProperties, NULL, NULL, 0, $numContacts, TRUE, TRUE);
          $mobilePhoneQuery = "
SELECT civicrm_phone.contact_id, civicrm_phone.phone, civicrm_phone.id as phone_id, civicrm_phone.phone_type_id
FROM civicrm_contact
LEFT JOIN civicrm_phone ON ( civicrm_contact.id = civicrm_phone.contact_id )
WHERE civicrm_contact.id IN (%1) AND civicrm_phone.phone_type_id = %2
ORDER BY civicrm_phone.is_primary DESC, phone_id ASC";
          $mobilePhoneResult = CRM_Core_DAO::executeQuery($mobilePhoneQuery, array(
            1 => array(CRM_Utils_Array::implode(',', $ids), 'CommaSeperatedIntegers'),
            2 => array($mobileTypeId, 'Integer'),
          ));
          while ($mobilePhoneResult->fetch()) {
            $details[$mobilePhoneResult->contact_id]['phone'] = $mobilePhoneResult->phone;
          }
          $skipped = array();
          foreach($details[0] as $contactId => $detail) {
            if (!CRM_Utils_Rule::email($detail['email']) || empty($detail['email'])) {
              $skipped['invalid_or_empty_email'][] = $contactId;
              continue;
            }
            $detail['phone'] = preg_replace('/[^0-9]/', '', $detail['phone']);
            $syncData[] = array(
              'email' => $detail['email'],
              'phone' => !empty($detail['phone']) && strlen($detail['phone']) == 10 ? trim($detail['phone']) : '',
              'title' => !empty($detail['individual_prefix']) && strlen($detail['individual_prefix']) < 10 ? $detail['individual_prefix'] : '',
              'name' => !empty($detail['sort_name']) && mb_strlen($detail['sort_name']) < 50 ? $detail['sort_name'] : '',
              'var1' => !empty($detail['birth_date']) && mb_strlen($detail['birth_date']) < 50 ? $detail['birth_date'] : '',
            );
          }
          $flydove = new CRM_Mailing_External_SmartMarketing_Flydove($providerId);
          try {
            $apiResult = $flydove->apiRequestSend('BatchCreateCustomer', array(
              'customers' => $syncData,
              'group_ids' => array($destRemoteGroup),
            ));
            if ($apiResult) {
              $sliceResults[$numOfSlice]['success'] = TRUE;
              $sliceResults[$numOfSlice]['skipped'] = $skipped;
            }
          }
          catch(CRM_Core_Exception $e) {
            $errorMessage =$e->getMessage();
            $errorCode =$e->getErrorCode();
            CRM_Core_Error::debug_log_message("Flydove error - BatchCreateCustomer: $errorCode $errorMessage");
          }

          if ($civicrm_batch) {
            $civicrm_batch->data['processed'] += self::BATCH_NUM;
          }
          usleep(500000);
        }
        if ($civicrm_batch) {
          if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
            $civicrm_batch->data['processed'] = $civicrm_batch->data['total'];
            $civicrm_batch->data['isCompleted'] = TRUE;
          }
        }
        else {
          return $sliceResults;
        }
      }
      else {
        throw new CRM_Core_Exception(ts("Please provide at least 1 contact."));
      }
    }
    else {
      throw new CRM_Core_Exception(ts('Flydove').':'.ts("Group you request doesn't exists in flydove."));
    }
  }
}