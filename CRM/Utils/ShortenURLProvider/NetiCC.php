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
   * {@inheritdoc}
   */
  public function getBatchInfo(array $shortUrls) {
    $codeToShort = [];
    foreach ($shortUrls as $shortUrl) {
      if (!is_string($shortUrl) || $shortUrl === '') {
        continue;
      }
      $code = basename(parse_url($shortUrl, PHP_URL_PATH));
      if ($code === '' || $code === FALSE) {
        continue;
      }
      $codeToShort[$code][] = $shortUrl;
    }
    if (empty($codeToShort)) {
      return [];
    }

    $payload = json_encode(array_keys($codeToShort));
    $ch = curl_init($this->baseUrl . '/handle/batch-info');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

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

    if ($httpCode !== 200) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::getBatchInfo failed, HTTP {$httpCode}: {$logSuffix}");
      return FALSE;
    }
    if (!is_array($data) || empty($data['success']) || !isset($data['result']) || !is_array($data['result'])) {
      CRM_Core_Error::debug_log_message("ShortenURLProvider_NetiCC::getBatchInfo invalid response: {$logSuffix}");
      return FALSE;
    }

    $result = [];
    foreach ($data['result'] as $row) {
      $code = $row['id'] ?? '';
      if (!isset($codeToShort[$code])) {
        continue;
      }
      $redirect = (string) ($row['redirect'] ?? '');
      foreach ($codeToShort[$code] as $shortUrl) {
        $result[$shortUrl] = $redirect;
      }
    }
    // Ensure every input short URL has an entry (empty string when missing).
    foreach ($codeToShort as $codeKey => $shortUrlList) {
      foreach ($shortUrlList as $shortUrl) {
        if (!array_key_exists($shortUrl, $result)) {
          $result[$shortUrl] = '';
        }
      }
    }
    return $result;
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
