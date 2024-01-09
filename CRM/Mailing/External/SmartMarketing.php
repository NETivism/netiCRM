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
}