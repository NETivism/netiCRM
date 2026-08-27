<?php

/**
 * Persistent cache for recurring contribution audit detail IDs.
 *
 * This helper uses the core cache API with a dedicated cache group.
 */
class CRM_Contribute_BAO_AuditContributionRecurCache {

  /**
   * Dedicated, versioned civicrm_cache group.
   */
  const CACHE_GROUP = 'audit.recur.ids.v1';

  /**
   * Versioned cache path prefix.
   */
  const CACHE_KEY_PREFIX = 'audit.recur.ids.v1';

  /**
   * Detail cache lifetime in days, measured from the actual write time.
   */
  const CACHE_TTL_DAYS = 90;

  /**
   * Supported recurring processor aliases.
   *
   * @var array
   */
  protected static $_processorAliases = [
    'tappay' => 'tappay',
    'linepay' => 'linepay',
    'spgateway' => 'spgateway',
  ];

  /**
   * Get one recurring audit detail payload.
   *
   * @param string $processor
   * @param int|string $entityId
   *
   * @return array
   */
  public static function get($processor, $entityId) {
    $cacheKey = self::buildCacheKey($processor, $entityId);
    if ($cacheKey === NULL) {
      return [];
    }

    try {
      $createdTime = time() - (self::CACHE_TTL_DAYS * 86400) + 1;
      $details = CRM_Core_BAO_Cache::getItem(
        self::CACHE_GROUP,
        $cacheKey,
        NULL,
        $createdTime
      );
      if (!is_array($details)) {
        return [];
      }

      return $details;
    }
    catch (Throwable $e) {
      self::logFailure('get', $processor, $entityId, $e->getMessage());
      return [];
    }
  }

  /**
   * Store one recurring audit detail payload for 90 days.
   *
   * TTL is based on the actual database write time.
   *
   * @param string $processor
   * @param int|string $entityId
   * @param array $details
   *
   * @return bool
   */
  public static function set($processor, $entityId, array $details) {
    $cacheKey = self::buildCacheKey($processor, $entityId);
    if ($cacheKey === NULL) {
      return FALSE;
    }

    try {
      $expired = time() + (self::CACHE_TTL_DAYS * 86400);
      CRM_Core_BAO_Cache::setItem(
        $details,
        self::CACHE_GROUP,
        $cacheKey,
        NULL,
        $expired
      );
      return TRUE;
    }
    catch (Throwable $e) {
      self::logFailure('set', $processor, $entityId, $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Build the versioned cache key for one processor and date.
   *
   * @param string $processor
   * @param int|string $entityId
   *
   * @return string|null
   */
  protected static function buildCacheKey($processor, $entityId) {
    $normalizedProcessor = self::normalizeProcessor($processor);
    if ($normalizedProcessor === NULL) {
      self::logFailure(
        'validate',
        $processor,
        $entityId,
        'The payment processor alias is invalid.'
      );
      return NULL;
    }

    $normalizedEntityId = self::normalizeEntityId($entityId);
    if ($normalizedEntityId === NULL) {
      self::logFailure(
        'validate',
        $processor,
        $entityId,
        'The entity ID must be a valid YYYYmmdd date.'
      );
      return NULL;
    }

    return self::CACHE_KEY_PREFIX . '.' . $normalizedProcessor . '.' . $normalizedEntityId;
  }

  /**
   * Normalize one supported recurring processor alias.
   *
   * @param mixed $processor
   *
   * @return string|null
   */
  protected static function normalizeProcessor($processor) {
    if (!is_string($processor)) {
      return NULL;
    }

    $processor = preg_replace('/[^a-z0-9]/', '', strtolower(trim($processor)));
    if (!isset(self::$_processorAliases[$processor])) {
      return NULL;
    }

    return self::$_processorAliases[$processor];
  }

  /**
   * Normalize and validate an eight-digit audit date entity ID.
   *
   * @param mixed $entityId
   *
   * @return string|null
   */
  protected static function normalizeEntityId($entityId) {
    if (!is_int($entityId) && !is_string($entityId)) {
      return NULL;
    }

    $entityId = trim((string) $entityId);
    if (!preg_match('/^[0-9]{8}$/', $entityId)) {
      return NULL;
    }

    $date = DateTime::createFromFormat('!Ymd', $entityId);
    if (!$date || $date->format('Ymd') !== $entityId) {
      return NULL;
    }

    return $entityId;
  }

  /**
   * Write a fail-safe cache diagnostic without logging payload IDs.
   *
   * @param string $operation
   * @param mixed $processor
   * @param mixed $entityId
   * @param string $message
   *
   * @return void
   */
  protected static function logFailure($operation, $processor, $entityId, $message) {
    $processorLabel = is_scalar($processor) ? (string) $processor : gettype($processor);
    $entityIdLabel = is_scalar($entityId) ? (string) $entityId : gettype($entityId);
    CRM_Core_Error::debug_log_message(
      '[AuditContributionRecurCache] ' . $operation . ' failed for processor ' .
      $processorLabel . ' and entity ' . $entityIdLabel . ': ' . $message
    );
  }

}
