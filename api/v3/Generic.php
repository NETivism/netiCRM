<?php
// $Id$

/**
 * Get information about fields for a given api request. Getfields information
 * is used for documentation, validation, default setting
 * We first query the scheme using the $dao->fields function & then augment
 * that information by calling the _spec functions that apply to the relevant function
 * Note that we use 'unique' field names as described in the xml/schema files
 * for get requests & just field name for create. This is because some get functions
 * access multiple objects e.g. contact api accesses is_deleted from the activity
 * table & from the contact table
 *
 * @param array $apiRequest api request as an array. Keys are
 *  - entity: string
 *  - action: string
 *  - version: string
 *  - function: callback (mixed)
 *  - params: array, varies
 *  @return array API success object
 */
function civicrm_api3_generic_getfields($apiRequest) {
  static $results = array();
  if ((CRM_Utils_Array::value('cache_clear', $apiRequest['params']))) {
    $results = array();
  }
  $entity       = _civicrm_api_get_camel_name($apiRequest['entity']);
  $lcase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  $subentity    = CRM_Utils_Array::value('contact_type', $apiRequest['params']);
  $action       = strtolower(CRM_Utils_Array::value('action', $apiRequest['params']));
  $apiOptions = CRM_Utils_Array::value('options', $apiRequest['params'], array());
  if ($action == 'getvalue' || $action == 'getvalue' || $action == 'getcount') {
    $action = 'get';
  }

  if (empty($action)) {
    $action='get';
  }
  // determines whether to use unique field names - seem comment block above
  $unique = TRUE;
  $cache_key = $apiRequest['action'].':'.$action;
  if (isset($results[$entity . $subentity]) && CRM_Utils_Array::value($cache_key, $results[$entity])
    && empty($apiOptions)) {
    return $results[$entity . $subentity][$cache_key];
  }
  // defaults based on data model and API policy
  switch ($action) {
    case 'getfields':
      $values = _civicrm_api_get_fields($entity, false, $apiRequest['params']);
      $results[$entity][$cache_key] = civicrm_api3_create_success($values,
        $apiRequest['params'], $entity, 'getfields'
      );
      return $results[$entity][$cache_key];
    case 'create':
    case 'update':
    case 'replace':
      $unique = FALSE;
    case 'get':
      $fields = _civicrm_api_get_fields($apiRequest['entity'], $unique, $apiRequest['params']);
      $metadata = array();
      foreach($fields as $fldname => $fldvalue) {
        $metadata[$fldname] = $fldvalue;
      }
      if (empty($metadata['id']) && !empty($metadata[$apiRequest['entity'] . '_id'])) {
        $metadata['id'] = $metadata[$lcase_entity . '_id'];
        $metadata['id']['api.aliases'] = array($lcase_entity . '_id');
        unset($metadata[$lcase_entity . '_id']);
      }
      break;

    case 'delete':
      $metadata = array(
        'id' => array('title' => 'Unique Identifier',
          'api.required' => 1,
          'api.aliases' => array($lcase_entity . '_id'),
        ));
      break;

    case 'getoptions':
      $metadata = array(
        'field' => array('title' => 'Field to retrieve options for',
        'api.required' => 1,
      ));
        break;
    default:
      // oddballs are on their own
      $metadata = array();
  }

  // find any supplemental information
  $hypApiRequest = array('entity' => $apiRequest['entity'], 'action' => $action, 'version' => $apiRequest['version']);
  $hypApiRequest += _civicrm_api_resolve($hypApiRequest);
  $helper = '_' . $hypApiRequest['function'] . '_spec';
  if (function_exists($helper)) {
    // alter
    $helper($metadata);
  }

  $fieldsToResolve = CRM_Utils_Array::value('get_options', $apiOptions, array());

  foreach ($metadata as $fieldname => $fieldSpec) {
    _civicrm_api3_generic_get_metadata_options($metadata, $fieldname, $fieldSpec, $fieldsToResolve);
  }

  $results[$entity][$cache_key] = civicrm_api3_create_success($metadata, $apiRequest['params'], NULL, 'getfields');
  return $results[$entity][$cache_key];
}

/**
 * API return function to reformat results as count
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getcount($apiRequest) {
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  return $result['count'];
}

/**
 * API return function to reformat results as single result
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getsingle($apiRequest) {
  // so the first entity is always result['values'][0]
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] === 1) {
    return $result['values'][0];
  }
  if ($result['count'] !== 1) {
    return civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], array('count' => $result['count']));
  }
  return civicrm_api3_create_error("Undefined behavior");
}

/**
 * API return function to reformat results as single value
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getvalue($apiRequest) {
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] !== 1) {
    $result = civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], array('count' => $result['count']));
    return $result;
  }

  // we only take "return=" as valid options
  if (CRM_Utils_Array::value('return', $apiRequest['params'])) {
    if (!isset($result['values'][0][$apiRequest['params']['return']])) {
      return civicrm_api3_create_error("field " . $apiRequest['params']['return'] . " unset or not existing", array('invalid_field' => $apiRequest['params']['return']));
    }

    return $result['values'][0][$apiRequest['params']['return']];
  }

  return civicrm_api3_create_error("missing param return=field you want to read the value of", array('error_type' => 'mandatory_missing', 'missing_param' => 'return'));
}

/**
 * API wrapper for replace function
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_replace($apiRequest) {
  return _civicrm_api3_generic_replace($apiRequest['entity'], $apiRequest['params']);
}

/**
 * API wrapper for replace function
 *
 * @param array $apiRequest api request as an array. Keys are
 *
 * @return integer count of results
 */
function civicrm_api3_generic_getoptions($apiRequest) {
  static $optionGroups;
  $camelName = _civicrm_api_get_camel_name($apiRequest['entity'], $apiRequest['version']);
  $entity = strtolower($camelName);

  $getFieldsArray = array(
    'version' => 3,
    'action' => 'create',
    'options' => array('get_options' => $apiRequest['params']['field'])
  );

  $result = civicrm_api($apiRequest['entity'], 'getfields', $getFieldsArray);

  // add some exceptions for location related when entity is contact
  if ($entity == 'contact') {
    $result['values'] = array_merge($result['values'], array(
      'location_type_id' => array(1),
      'worldregion_id' => array(1),
      'country_id' => array(1),
      'state_province_id' => array(1),
      'phone_type_id' => array(1),
      'provider_id' => array(1),
      'world_region' => array(1),
    ));
  }

  // field_name is correct
  if (!empty($result['values'][$apiRequest['params']['field']])) {
    $values = array();

    // field has related options id (eg. custom_field)
    if (!empty($result['values'][$apiRequest['params']['field']]['options'])) {
      foreach($result['values'][$apiRequest['params']['field']]['options'] as $key => $label) {
        $values[$key] = array(
          'value' => $key,
          'label' => $label,
        );
      }
    }
    elseif (strstr($result['values'][$apiRequest['params']['field']]['name'], 'custom_') && empty($result['values'][$apiRequest['params']['field']]['option_group_id'])) {
      switch($result['values'][$apiRequest['params']['field']]['data_type']) {
        case 'Boolean':
          $values = array(
            0 => array(
              'value' => 0,
              'label' => ts('No'),
            ),
            1 => array(
              'value' => 1,
              'label' => ts('Yes'),
            ),
          );
          break;
        case 'StateProvince':
          $constantParams = array(
            'version' => 3,
            'class' => 'CRM_Core_PseudoConstant',
            'name' => 'stateProvince',
          );
          $result = civicrm_api('constant', 'get', $constantParams);
          if (!$result['is_error'] && !empty($result['values'])) {
            foreach($result['values'] as $key => $val) {
              $values[$key] = array(
                'value' => $key,
                'label' => $val,
              );
            }
          }
          break;
        case 'Country':
          $constantParams = array(
            'version' => 3,
            'class' => 'CRM_Core_PseudoConstant',
            'name' => 'Country',
          );
          $result = civicrm_api('constant', 'get', $constantParams);
          if (!$result['is_error'] && !empty($result['values'])) {
            foreach($result['values'] as $key => $val) {
              $values[$key] = array(
                'value' => $key,
                'label' => $val,
              );
            }
          }
          break;
      }
    }
    // check constant or option group name
    else {
      $fieldName = $apiRequest['params']['field'];
      // special case for participant
      if ($entity == 'participant') {
        $entity = 'event';
        if (in_array($apiRequest['params']['field'], array('status_id', 'status', 'role_id', 'role'))) {
          $fieldName = 'participant_'.$apiRequest['params']['field'];
        }
      }
      // special case for activity 
      if ($entity == 'activity') {
        if (in_array($apiRequest['params']['field'], array('status_id', 'type_id', 'status', 'type'))) {
          $fieldName = 'activity_'.$apiRequest['params']['field'];
        }
      }

      $constantEntities = _civicrm_api3_pseudoconstant_entity();
      $fieldNameWithoutId = strtolower(preg_replace('/_id$/i', '', $fieldName));

      // constant api
      if (!empty($constantEntities[$entity])) {
        $constantName = _civicrm_api_get_constant_camel_name($fieldNameWithoutId);
        if (!empty($constantName)) {
          $constantParams = array(
            'version' => 3,
            'class' => 'CRM_'.$constantEntities[$entity].'_PseudoConstant',
            'name' => $constantName,
          );
          $result = civicrm_api('constant', 'get', $constantParams);
          if (!$result['is_error'] && !empty($result['values'])) {
            foreach($result['values'] as $key => $val) {
              if (is_array($val)) {
                if (!isset($val['value'])) {
                  $val['value'] = "$key";
                }
                if (!isset($val['label'])) {
                  $val['label'] = $val['label'] ? $val['label'] : ($val['name'] ? $val['name'] : '');
                }
                $values[$key] = $val;
              }
              elseif (is_string($val)) {
                $values[$key] = array(
                  'value' => $key,
                  'label' => $val,
                );
              }
            }
          }
        }
      }
    }

    if (!empty($values)) {
      return civicrm_api3_create_success($values, $apiRequest['params'], $apiRequest['entity'], 'getoptions');
    }
    else{ 
      return civicrm_api3_create_error("Found field '{$apiRequest['params']['field']}' in entity '{$apiRequest['entity']}', but doesn't have option.");
    }
  }
  elseif($entity == 'optiongroup') {
    $values = array();
    // option group name
    // whatever entity is, get option group values by name
    $fieldNameWithoutId = strtolower(preg_replace('/_id$/i', '', $apiRequest['params']['field']));
    $optionGroupName = strtolower($fieldNameWithoutId);
    $result = civicrm_api('option_value', 'get', array(
      'version' => 3,
      'option_group_name' => $optionGroupName,
    ));
    if (!empty($result['values'])) {
      foreach($result['values'] as $val) {
        $values[$val['value']] = array(
          'label' => $val['label'],
          'name' => $val['name'],
          'value' => $val['value'],
        );
      }
      return civicrm_api3_create_success($values, $apiRequest['params'], $apiRequest['entity'], 'getoptions');
    }
    else {
      return civicrm_api3_create_error("Option group '{$apiRequest['params']['field']}' doesn't exists.");
    }
  }
  else {
    return civicrm_api3_create_error("Field '{$apiRequest['params']['field']}' doesn't exists in entity '{$apiRequest['entity']}'.");
  }
  return '';
}

/*
 * Function fills the 'options' array on the metadata returned by getfields if
 * 1) the param option 'get_options' is defined - e.g. $params['options']['get_options'] => array('custom_1)
 * (this is passed in as the $fieldsToResolve array)
 * 2) the field is a pseudoconstant and is NOT an FK
 * - the reason for this is that checking / transformation is done on pseudoconstants but
 * - if the field is an FK then mysql will enforce the data quality (& we have handling on failure)
 * @todo - if may be we should define a 'resolve' key on the psuedoconstant for when these rules are not fine enough
 * 3) if there is an 'enum' then it is split up into the relevant options
 *
 * This function is only split out for the purpose of code clarity / comment block documentation
 * @param array $metadata the array of metadata that will form the result of the getfields function
 * @param string $fieldname field currently being processed
 * @param array $fieldSpec metadata for that field
 * @param array $fieldsToResolve anny field resolutions specifically requested
 */
function _civicrm_api3_generic_get_metadata_options(&$metadata, $fieldname, $fieldSpec, $fieldsToResolve){
  if (CRM_Utils_Array::arrayKeyExists('enumValues', $fieldSpec)) {
    // use of a space after the comma is inconsistent in xml
    $enumStr = str_replace(', ', ',', $fieldSpec['enumValues']);
    $metadata[$fieldname]['options'] = explode(',', $enumStr);
    return;
  }

  if(empty($fieldSpec['pseudoconstant'])){
    return ;
  }
  elseif(!empty($fieldSpec['FKClassName']) && !in_array($fieldname, $fieldsToResolve)){
    return;
  }
  if(substr($fieldname, -3) == '_id'){
    $metadata[$fieldname]['api.aliases'][] = substr($fieldname, 0, -3);
  }

  $pseudoParams = $fieldSpec['pseudoconstant'];
  $pseudoParams['version'] = 3;
  $options = civicrm_api('constant', 'get', $pseudoParams);
  if (is_array(CRM_Utils_Array::value('values', $options))) {
    $metadata[$fieldname]['options'] = $options['values'];
  }
}
