<?php

class CRM_Mailing_External_SmartMarketing_Flydove extends CRM_Mailing_External_SmartMarketing {

  public function getRemoteGroups() {
    return array();
  }

  public function parseSavedData($json) {
    // check all element is good
    return json_decode($json);
  }
}