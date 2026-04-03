<?php

/**
 * neti.cc URL shortener provider.
 */
class CRM_Utils_ShortenURLProvider_NetiCC extends CRM_Utils_ShortenURLProvider {

  /**
   * @var string
   */
  private $baseUrl = 'https://neti.cc';

  /**
   * @var string
   */
  private $apiKey;

  public function __construct() {
    parent::__construct();
    $this->apiKey = $this->config->netiCCAPIKey ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function create($longUrl) {
    $ch = curl_init($this->baseUrl . '/handle/create');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['redirect' => $longUrl]));

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
    ];
    if ($this->apiKey !== '') {
      $headers[] = 'Authorization: Bearer ' . $this->apiKey;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, TRUE);
    $apiMessage = $data['message'] ?? NULL;
    $logSuffix = $apiMessage ? "{$apiMessage}, response: {$response}" : $response;

    if ($httpCode !== 201) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::create failed, HTTP {$httpCode}, url: {$longUrl}: {$logSuffix}");
      return FALSE;
    }

    if (!is_array($data) || empty($data['success'])) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::create invalid response, url: {$longUrl}: {$logSuffix}");
      return FALSE;
    }

    return $this->baseUrl . '/' . $data['result'][0]['short'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCount($shortUrl) {
    $result = $this->getVisits($shortUrl);
    if ($result === FALSE) {
      return FALSE;
    }
    return (int) $result['total'];
  }

  /**
   * Get full visit statistics for a shortened URL.
   *
   * @param string $shortUrl
   *   The shortened URL to look up.
   *
   * @return array|false
   *   Associative array with keys: total, referrer_statistics, dates.
   *   Returns FALSE on failure.
   */
  public function getVisits($shortUrl) {
    $code = basename(parse_url($shortUrl, PHP_URL_PATH));
    if (empty($code)) {
      return FALSE;
    }

    $ch = curl_init($this->baseUrl . '/handle/visits/' . urlencode($code));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $headers = [
      'Accept: application/json',
    ];
    if ($this->apiKey !== '') {
      $headers[] = 'Authorization: Bearer ' . $this->apiKey;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, TRUE);
    $apiMessage = $data['message'] ?? NULL;
    $logSuffix = $apiMessage ? "{$apiMessage}, response: {$response}" : $response;

    if ($httpCode !== 200) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::getVisits failed, HTTP {$httpCode}, shortUrl: {$shortUrl}: {$logSuffix}");
      return FALSE;
    }

    if (!is_array($data) || empty($data['success']) || !is_array($data['result'])) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::getVisits invalid response, shortUrl: {$shortUrl}: {$logSuffix}");
      return FALSE;
    }

    return $data['result'];
  }

}
