<?php
// $Id$

/**
 * Our original intention was not to have an update action. However, we wound up having
 * to retain it for backward compatibility. The only difference between update and create
 * is that update will throw an error if id is not a number
 * CRM-10908
 * @param $apiRequest an array with keys:
 *  - entity: string
 *  - action: string
 *  - version: string
 *  - function: callback (mixed)
 *  - params: array, varies
 */
function civicrm_api3_generic_update($apiRequest) {

  if (!CRM_Utils_Array::arrayKeyExists('id', $apiRequest['params']) ||
      empty($apiRequest['params']['id']) ||
      !is_numeric($apiRequest['params']['id'])) {
    throw new API_Exception("Mandatory parameter missing `id`", 2000);
  }
  return civicrm_api($apiRequest['entity'], 'create', $apiRequest['params']);
}

