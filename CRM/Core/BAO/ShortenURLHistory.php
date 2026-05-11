<?php

/**
 * BAO for shortened URL history records stored in civicrm_log.
 *
 * Records are persisted with entity_table = "{pageType}.shortenurl" and
 * entity_id = pageId. The `data` column carries a JSON payload containing
 * the short URL together with the five UTM fields captured at creation time.
 */
class CRM_Core_BAO_ShortenURLHistory {

  /**
   * Suffix appended to a page type to form the entity_table value.
   * Using a "." keeps the value distinct from any real table name.
   */
  const ENTITY_TABLE_SUFFIX = '.shortenurl';

  /**
   * Page types that may have shortened URL history.
   * Kept in sync with the whitelist used by CRM_Core_Page_AJAX_SaveShortenURL.
   */
  const ALLOWED_PAGE_TYPES = [
    'civicrm_contribution_page',
    'civicrm_pcp',
    'civicrm_event.info',
    'civicrm_event.register',
    'civicrm_uf_group',
  ];

  /**
   * UTM keys carried in the JSON payload.
   */
  const UTM_KEYS = [
    'utm_source',
    'utm_medium',
    'utm_term',
    'utm_content',
    'utm_campaign',
  ];

  /**
   * Write a shortened URL record to civicrm_log.
   *
   * @param string $pageType
   *   One of self::ALLOWED_PAGE_TYPES.
   * @param int $pageId
   *   The id of the page the short URL belongs to.
   * @param string $shortUrl
   *   The shortened URL returned by the provider.
   * @param array $utmParams
   *   Associative array keyed by UTM field name (see self::UTM_KEYS). Missing
   *   keys are stored as empty strings.
   *
   * @return CRM_Core_DAO_Log|false
   *   The persisted DAO on success, FALSE when input is invalid or JSON
   *   encoding fails.
   */
  public static function create($pageType, $pageId, $shortUrl, array $utmParams) {
    if (!in_array($pageType, self::ALLOWED_PAGE_TYPES, TRUE)) {
      return FALSE;
    }
    $pageId = (int) $pageId;
    if ($pageId <= 0 || $shortUrl === '' || $shortUrl === NULL) {
      return FALSE;
    }

    $payload = ['short_url' => (string) $shortUrl];
    foreach (self::UTM_KEYS as $key) {
      $payload[$key] = isset($utmParams[$key]) ? (string) $utmParams[$key] : '';
    }

    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === FALSE) {
      CRM_Core_Error::debug_log_message('CRM_Core_BAO_ShortenURLHistory::create json_encode failed: ' . json_last_error_msg());
      return FALSE;
    }

    $userID = CRM_Core_Session::singleton()->get('userID');

    $log = new CRM_Core_DAO_Log();
    $log->entity_table  = $pageType . self::ENTITY_TABLE_SUFFIX;
    $log->entity_id     = $pageId;
    $log->modified_id   = $userID ?: NULL;
    $log->modified_date = date('YmdHis');
    $log->data          = $encoded;
    $log->save();
    return $log;
  }

}
