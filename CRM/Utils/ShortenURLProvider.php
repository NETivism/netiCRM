<?php

/**
 * Abstract base class for URL shortener providers.
 */
abstract class CRM_Utils_ShortenURLProvider {

  /**
   * @var CRM_Core_Config
   */
  protected $config;

  public function __construct() {
    $this->config = CRM_Core_Config::singleton();
  }

  /**
   * Create a shortened URL from a long URL.
   *
   * @param string $longUrl
   *   The original URL to shorten.
   *
   * @return string|false
   *   The shortened URL string, or FALSE on failure.
   */
  abstract public function create($longUrl);

  /**
   * Get the click count for a shortened URL.
   *
   * @param string $shortUrl
   *   The shortened URL to look up.
   *
   * @return int|false
   *   The click count, or FALSE on failure.
   */
  abstract public function getCount($shortUrl);

  /**
   * Look up redirect targets for multiple shortened URLs in a single request.
   *
   * @param string[] $shortUrls
   *   Full shortened URL strings (e.g. https://d.neti.cc/aB3xY).
   *
   * @return array<string,string>|false
   *   Map keyed by the input shortened URL → redirect target (long URL).
   *   Missing or unknown entries are returned as empty strings. FALSE on
   *   transport / authentication failure.
   */
  abstract public function getBatchInfo(array $shortUrls);

}
