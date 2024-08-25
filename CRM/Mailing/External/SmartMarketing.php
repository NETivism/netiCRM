<?php

abstract class CRM_Mailing_External_SmartMarketing {

  /**
   * Get external available groups
   *
   * @return array
   */
  abstract public function getRemoteGroups();


  /**
   * Parse saved data in group table
   *
   * @param string $json
   *
   * @return object|array|false
   */
  abstract public function parseSavedData($json);

  /**
   * Sync all smart marketing to remote
   */
  public static function syncAll() {
    CRM_Core_Error::debug_log_message("Smart Marketing - Syncing Start");
    $availableGroupTypes = CRM_Core_OptionGroup::values('group_type');
    $typeNames = array();
    foreach($availableGroupTypes as $typeId => $typeName) {
      if (strstr($typeName, 'Smart Marketing')) {
        list($smartMarketingVendor) = explode(' ', $typeName);
        $typeNames[$typeId] = $smartMarketingVendor;
      }
    }
    if (!empty($typeNames)) {
      $syncResult = array();
      global $civicrm_batch;
      foreach($typeNames as $typeId => $class) {
        $groups = CRM_Core_PseudoConstant::allGroup($typeId);
        CRM_Core_Error::debug_log_message("Smart Marketing - found groups: ".implode(',', $groups));
        if (!empty($groups)) {
          foreach($groups as $groupId => $groupName) {
            // skip synced
            if (isset($syncResult[$groupId])) {
              CRM_Core_Error::debug_log_message("Smart Marketing - Skipped $groupId");
              continue;
            }
            CRM_Core_Error::debug_log_message("Smart Marketing - Processing $groupId");
            $syncResult[$groupId] = self::syncGroup($groupId);
            CRM_Core_Error::debug_log_message("Smart Marketing - $groupId end");
            $civicrm_batch = NULL;
            // sleep 0.6s each group sync
            usleep(600000);
          }
        }
      }
    }
    CRM_Core_Error::debug_log_message("Smart Marketing - Syncing Complete");
  }

  /**
   * Sync a group to remote
   */
  public static function syncGroup($groupId) {
    $groupParams = array(
      'id' => $groupId,
    );
    $provider = self::getProviderByGroup($groupId);
    CRM_Contact_BAO_Group::retrieve($groupParams, $group);
    if (!empty($group['sync_data']) && !empty($provider['id'])) {
      $syncData = json_decode($group['sync_data'], TRUE);
      $names = explode('_', $provider['name']);
      $vendorName = end($names);
      $smartMarketingClass = 'CRM_Mailing_External_SmartMarketing_'.$vendorName;
      if (!empty($syncData['remote_group_id']) && class_exists($smartMarketingClass)) {
        $apiParams = array(
          'group_id' => $groupId,
          'version' => 3,
        );
        $result = civicrm_api('group_contact', 'get', $apiParams);
        if (!empty($result['values'])) {
          $contactIds = array();
          foreach($result['values'] as $item) {
            $contactIds[$item] = $item;
          }
          if (count($contactIds) > $smartMarketingClass::BATCH_NUM) {
            $smartMarketingService = new $smartMarketingClass($provider['id']);
            $batchId = $smartMarketingService->batchSchedule($contactIds, $groupId, $syncData['remote_group_id'], $provider['id']);
            return array(
              'batch' => TRUE,
              'batch_id' => $batchId,
              'result' => ts('Scheduled').':'.ts('Batch ID').'-'.$batchId,
            );
          }
          else {
            $syncResult = call_user_func(array($smartMarketingClass, 'addContactToRemote'), $contactIds, $groupId, $syncData['remote_group_id'], $provider['id']);
            return array(
              'batch' => FALSE,
              'batch_id' => 0,
              'result' => $syncResult,
            );
          }
        }
        else {
          return array(
            'batch' => FALSE,
            'batch_id' => 0,
            'result' => array(
              '#report' => array('error' => ts('Please provide at least 1 contact.')),
            ),
          );
        }
      }
    }

    return array(
      'batch' => FALSE,
      'batch_id' => 0,
      'result' => array(
        '#report' => array('error' => ts('The group you request doesn\'t exists in flydove.')),
      ),
    );
  }

  public static function getProviderByGroup($groupId) {
    $group = array();
    $params = array(
      'id' => $groupId,
    );
    CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Group', $params, $group);
    if (!empty($group) && !empty($group['group_type'])) {
      $availableGroupTypes = CRM_Core_OptionGroup::values('group_type');
      $groupTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($group['group_type'], CRM_Core_DAO::VALUE_SEPARATOR));
      foreach($groupTypes as $typeId) {
        $smartMarketingName = $availableGroupTypes[$typeId];
        if (strstr($smartMarketingName, 'Smart Marketing')) {
          list($smartMarketingVendor) = explode(' ', $smartMarketingName);
          if (strlen($smartMarketingVendor) > 0) {
            $smartMarketingVendor = ucfirst($smartMarketingVendor);
            $smartMarketingClass = 'CRM_Mailing_External_SmartMarketing_'.$smartMarketingVendor;
            if (class_exists($smartMarketingClass)) {
              $providers = CRM_SMS_BAO_Provider::getProviders(NULL, array('name' => 'CRM_SMS_Provider_'.$smartMarketingVendor));
              if (!empty($providers)) {
                $provider = reset($providers);
                return $provider;
              }
            }
          }
        }
      }
    }
    return array();
  }
}