<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Rate Limiter utility class
 *
 * Provides IP-based rate limiting using the civicrm_sequence table (MEMORY engine).
 * Uses a sliding window approach to limit requests per IP address.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 */
class CRM_Utils_RateLimiter {

  /**
   * Default time window in seconds
   */
  const DEFAULT_WINDOW_SECONDS = 60;

  /**
   * Default maximum requests per window
   */
  const DEFAULT_MAX_REQUESTS = 10;

  /**
   * Probability of running cleanup (1 in N requests)
   */
  const CLEANUP_PROBABILITY = 100;

  /**
   * Maximum key length for civicrm_sequence.name column
   */
  const MAX_KEY_LENGTH = 64;

  /**
   * Check if the current request should be rate limited
   *
   * @param string $prefix
   *   Unique prefix for the rate limit context (e.g., 'username_check')
   * @param int|null $windowSeconds
   *   Time window in seconds (default: 60)
   * @param int|null $maxRequests
   *   Maximum requests allowed per window (default: 10)
   *
   * @return bool
   *   TRUE if request should be blocked (rate limited), FALSE if allowed
   */
  public static function isRateLimited($prefix, $windowSeconds = NULL, $maxRequests = NULL) {
    $windowSeconds = $windowSeconds !== NULL ? (int) $windowSeconds : self::DEFAULT_WINDOW_SECONDS;
    $maxRequests = $maxRequests !== NULL ? (int) $maxRequests : self::DEFAULT_MAX_REQUESTS;

    // Get client IP address
    $ip = CRM_Utils_System::ipAddress();

    // If we cannot determine IP, allow the request (fail open for safety)
    if (empty($ip)) {
      return FALSE;
    }

    // Generate the storage key
    $key = self::generateKey($prefix, $ip);

    $now = microtime(TRUE);

    // Try to get existing record
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = $key;

    if ($dao->find(TRUE)) {
      // Parse existing value
      $data = self::parseValue($dao->value);

      if ($data === NULL) {
        // Corrupted data, reset the counter
        $data = [
          'count' => 1,
          'window_start' => $now,
        ];
        $dao->value = json_encode($data);
        $dao->timestamp = $now;
        $dao->update();
        return FALSE;
      }

      $windowStart = $data['window_start'];
      $count = $data['count'];

      // Check if window has expired
      if (($now - $windowStart) >= $windowSeconds) {
        // Window expired, reset counter
        $data = [
          'count' => 1,
          'window_start' => $now,
        ];
        $dao->value = json_encode($data);
        $dao->timestamp = $now;
        $dao->update();
        return FALSE;
      }

      // Window is still active, check count
      if ($count >= $maxRequests) {
        // Rate limit exceeded
        return TRUE;
      }

      // Increment counter
      $data['count'] = $count + 1;
      $dao->value = json_encode($data);
      $dao->timestamp = $now;
      $dao->update();
      return FALSE;
    }
    else {
      // No existing record, create new one
      $data = [
        'count' => 1,
        'window_start' => $now,
      ];
      $dao->value = json_encode($data);
      $dao->timestamp = $now;
      $dao->insert();
      return FALSE;
    }
  }

  /**
   * Clean up expired rate limit records
   *
   * This method uses probabilistic execution (1/100 chance) to avoid
   * running cleanup on every request.
   *
   * @param string $prefix
   *   Unique prefix for the rate limit context
   * @param int|null $windowSeconds
   *   Time window in seconds (default: 60)
   *
   * @return bool
   *   TRUE if cleanup was executed, FALSE if skipped
   */
  public static function cleanup($prefix, $windowSeconds = NULL) {
    // Probabilistic execution: only run 1 in CLEANUP_PROBABILITY times
    if (mt_rand(1, self::CLEANUP_PROBABILITY) !== 1) {
      return FALSE;
    }

    $windowSeconds = $windowSeconds !== NULL ? (int) $windowSeconds : self::DEFAULT_WINDOW_SECONDS;
    $expireTime = microtime(TRUE) - $windowSeconds;

    // Delete expired records matching the prefix pattern
    // Use LIKE pattern with escaped prefix
    $escapedPrefix = CRM_Core_DAO::escapeString($prefix . '_');

    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_sequence WHERE name LIKE %1 AND timestamp < %2",
      [
        1 => [$escapedPrefix . '%', 'String'],
        2 => [$expireTime, 'Float'],
      ]
    );

    return TRUE;
  }

  /**
   * Generate a storage key from prefix and IP address
   *
   * @param string $prefix
   *   The prefix for the rate limit context
   * @param string $ip
   *   The IP address
   *
   * @return string
   *   The generated key (max 64 characters)
   */
  private static function generateKey($prefix, $ip) {
    // Sanitize IP: replace non-alphanumeric characters with underscore
    $sanitizedIp = preg_replace('/[^a-zA-Z0-9]/', '_', $ip);

    $key = $prefix . '_' . $sanitizedIp;

    // If key exceeds max length, use prefix + md5 hash
    if (strlen($key) > self::MAX_KEY_LENGTH) {
      $hash = md5($ip);
      $maxPrefixLength = self::MAX_KEY_LENGTH - 33; // 32 for md5 + 1 for underscore
      $truncatedPrefix = substr($prefix, 0, $maxPrefixLength);
      $key = $truncatedPrefix . '_' . $hash;
    }

    return $key;
  }

  /**
   * Parse the stored JSON value
   *
   * @param string $value
   *   The JSON value from database
   *
   * @return array|null
   *   Parsed array with 'count' and 'window_start', or NULL if invalid
   */
  private static function parseValue($value) {
    if (empty($value)) {
      return NULL;
    }

    $data = json_decode($value, TRUE);

    if (!is_array($data)) {
      return NULL;
    }

    if (!isset($data['count']) || !isset($data['window_start'])) {
      return NULL;
    }

    if (!is_numeric($data['count']) || !is_numeric($data['window_start'])) {
      return NULL;
    }

    return [
      'count' => (int) $data['count'],
      'window_start' => (float) $data['window_start'],
    ];
  }
}
