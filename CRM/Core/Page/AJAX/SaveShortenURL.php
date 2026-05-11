<?php
/**
 * AJAX handler for saving shortened URLs associated with contribution pages, events, and profiles
 *
 */

class CRM_Core_Page_AJAX_SaveShortenURL {
  /**
   * AJAX entry point to save a shortened URL for a specific page.
   *
   * @return void
   */
  public static function run() {
    $pageId = CRM_Utils_Request::retrieve('page_id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $pageType = CRM_Utils_Request::retrieve('page_type', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
    $url = CRM_Utils_Request::retrieve('url', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');

    if (!in_array($pageType, CRM_Core_BAO_ShortenURLHistory::ALLOWED_PAGE_TYPES, TRUE)) {
      http_response_code(400);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['is_error' => 1, 'error_message' => 'Invalid page type']);
      CRM_Utils_System::civiExit();
    }

    $utmParams = [];
    foreach (CRM_Core_BAO_ShortenURLHistory::UTM_KEYS as $utmKey) {
      $utmParams[$utmKey] = CRM_Utils_Request::retrieve($utmKey, 'String', CRM_Core_DAO::$_nullObject, FALSE, '', 'REQUEST');
    }

    $provider = new CRM_Utils_ShortenURLProvider_NetiCC();
    $shortUrl = $provider->create($url);

    if ($shortUrl === FALSE) {
      http_response_code(400);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['is_error' => 1, 'error_message' => 'Failed to shorten URL']);
      CRM_Utils_System::civiExit();
    }

    $exists = CRM_Core_OptionGroup::getValue('shorten_url', $pageType . '.' . $pageId, 'name', 'String', 'id');
    $groupParams = [
      'name' => 'shorten_url',
      'is_active' => 1,
      'is_reserved' => 1,
    ];
    $optionParams = [
      'label' => $pageType . '.' . $pageId,
      'name' => $pageType . '.' . $pageId,
      'value' => $shortUrl,
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

    CRM_Core_BAO_ShortenURLHistory::create($pageType, $pageId, $shortUrl, $utmParams);

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['is_error' => 0, 'shorten' => $shortUrl]);
    CRM_Utils_System::civiExit();
  }
}
