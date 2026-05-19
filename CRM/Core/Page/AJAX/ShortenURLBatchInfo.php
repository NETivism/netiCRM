<?php
/**
 * AJAX handler that proxies the goshort batch-info call.
 * Returns a JSON map from short URL → redirect target (long URL).
 */

class CRM_Core_Page_AJAX_ShortenURLBatchInfo {

  /**
   * Maximum number of short URLs accepted per request. The history table
   * shows at most 30 rows per accordion, so 60 leaves some headroom while
   * still keeping the request small.
   */
  const MAX_URLS = 60;

  /**
   * AJAX entry point.
   *
   * @return void
   */
  public static function run() {
    $raw = CRM_Utils_Request::retrieve('short_urls', 'String', CRM_Core_DAO::$_nullObject, FALSE, '', 'REQUEST');
    $shortUrls = json_decode($raw, TRUE);

    if (!is_array($shortUrls)) {
      self::respond(400, ['is_error' => 1, 'error_message' => 'Invalid short_urls payload']);
    }

    // Keep only valid http(s) strings, dedupe while preserving order.
    $cleaned = [];
    $seen = [];
    foreach ($shortUrls as $url) {
      if (!is_string($url) || $url === '') {
        continue;
      }
      if (!preg_match('#^https?://#i', $url)) {
        continue;
      }
      if (isset($seen[$url])) {
        continue;
      }
      $seen[$url] = TRUE;
      $cleaned[] = $url;
      if (count($cleaned) >= self::MAX_URLS) {
        break;
      }
    }

    if (empty($cleaned)) {
      self::respond(200, ['is_error' => 0, 'result' => (object) []]);
    }

    $provider = new CRM_Utils_ShortenURLProvider_NetiCC();
    $map = $provider->getBatchInfo($cleaned);
    if ($map === FALSE) {
      self::respond(502, ['is_error' => 1, 'error_message' => 'Failed to load batch info']);
    }

    self::respond(200, ['is_error' => 0, 'result' => (object) $map]);
  }

  /**
   * Emit a JSON response and terminate the request.
   *
   * @param int $httpCode
   * @param array $body
   *
   * @return void
   */
  private static function respond($httpCode, array $body) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body);
    CRM_Utils_System::civiExit();
  }

}
