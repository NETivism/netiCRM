<?php
class CRM_Core_Page_AJAX_SaveShortenURL {
  static function run() {
    $pageId = CRM_Utils_Request::retrieve('page_id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $pageType = CRM_Utils_Request::retrieve('page_type', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $shorten = CRM_Utils_Request::retrieve('shorten', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    if (in_array($pageType, ['civicrm_contribution_page', 'civicrm_pcp', 'civicrm_event.info', 'civicrm_event.register', 'civicrm_uf_group'])) {
      $exists = CRM_Core_OptionGroup::getValue('shorten_url', $pageType.'.'.$pageId, 'name', 'String', 'id');
      $groupParams = [
        'name' => 'shorten_url',
        'is_active' => 1,
        'is_reserved' => 1,
      ];
      $optionParams = [
        'label' => $pageType.'.'.$pageId,
        'name' => $pageType.'.'.$pageId,
        'value' => $shorten,
        'is_active' => 1,
      ];
      if ($exists) {
        $action = CRM_Core_Action::UPDATE;
        CRM_Core_OptionValue::addOptionValue($optionParams, $groupParams, $action, $exists);
      }
      else {
        $optionId = NULL;
        $action = CRM_Core_Action::ADD;
        CRM_Core_OptionValue::addOptionValue($optionParams, $groupParams, $action, $optionId);
      }
    }
    CRM_Utils_System::civiExit();
  }  
}