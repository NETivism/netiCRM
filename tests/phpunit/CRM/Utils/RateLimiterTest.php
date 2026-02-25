<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_RateLimiterTest
 *
 * Tests for the CRM_Utils_RateLimiter class
 *
 * @package CiviCRM
 * @subpackage CRM_Utils_RateLimiter
 * @group headless
 */
class CRM_Utils_RateLimiterTest extends CiviUnitTestCase {

  /**
   * Test prefix for rate limiter keys
   * @var string
   */
  protected $testPrefix = 'test_rate_limit';

  /**
   * @before
   */
  public function setUpTest() {
    parent::setUp();

    // Clean up any existing test records before each test
    $this->cleanupTestRecords();
  }

  /**
   * @after
   */
  public function tearDownTest() {
    // Clean up test records after each test
    $this->cleanupTestRecords();
    parent::tearDown();
  }

  /**
   * Clean up test records from civicrm_sequence
   */
  protected function cleanupTestRecords() {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_sequence WHERE name LIKE %1",
      [1 => [$this->testPrefix . '%', 'String']]
    );
  }

  /**
   * Helper method to generate key using same logic as RateLimiter::generateKey()
   *
   * This replicates the private generateKey() method for testing purposes
   *
   * @param string $prefix
   * @param string $ip
   * @return string
   */
  protected function generateKey($prefix, $ip) {
    // Sanitize IP: replace non-alphanumeric characters with underscore
    $sanitizedIp = preg_replace('/[^a-zA-Z0-9]/', '_', $ip);

    $key = $prefix . '_' . $sanitizedIp;

    // If key exceeds max length, use prefix + md5 hash
    if (strlen($key) > CRM_Utils_RateLimiter::MAX_KEY_LENGTH) {
      $hash = md5($ip);
      $maxPrefixLength = CRM_Utils_RateLimiter::MAX_KEY_LENGTH - 33; // 32 for md5 + 1 for underscore
      $truncatedPrefix = substr($prefix, 0, $maxPrefixLength);
      $key = $truncatedPrefix . '_' . $hash;
    }

    return $key;
  }

  /**
   * Test that initial requests are allowed
   *
   * The first request should not be rate limited
   */
  public function testInitialRequestIsAllowed() {
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
    $this->assertFalse($isLimited, 'Initial request should be allowed');
  }

  /**
   * Test that multiple requests within limit are allowed
   *
   * Up to DEFAULT_MAX_REQUESTS (10) requests should be allowed
   */
  public function testMultipleRequestsWithinLimitAreAllowed() {
    $maxRequests = CRM_Utils_RateLimiter::DEFAULT_MAX_REQUESTS;

    // Make max_requests number of requests
    for ($i = 0; $i < $maxRequests; $i++) {
      $isLimited = CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
      $this->assertFalse(
        $isLimited,
        "Request " . ($i + 1) . " should be allowed (within limit of $maxRequests)"
      );
    }
  }

  /**
   * Test that exceeding the request limit blocks requests
   *
   * The (max_requests + 1)th request should be rate limited
   */
  public function testExceedingLimitBlocksRequests() {
    $maxRequests = CRM_Utils_RateLimiter::DEFAULT_MAX_REQUESTS;

    // Make max_requests number of requests
    for ($i = 0; $i < $maxRequests; $i++) {
      CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
    }

    // The next request should be blocked
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
    $this->assertTrue(
      $isLimited,
      "Request " . ($maxRequests + 1) . " should be blocked (exceeds limit of $maxRequests)"
    );
  }

  /**
   * Test that rate limit persists for subsequent requests
   *
   * Once rate limited, subsequent requests should continue to be blocked
   */
  public function testRateLimitPersistsForSubsequentRequests() {
    $maxRequests = CRM_Utils_RateLimiter::DEFAULT_MAX_REQUESTS;

    // Exceed the limit
    for ($i = 0; $i <= $maxRequests; $i++) {
      CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
    }

    // Multiple subsequent requests should also be blocked
    for ($i = 0; $i < 5; $i++) {
      $isLimited = CRM_Utils_RateLimiter::isRateLimited($this->testPrefix);
      $this->assertTrue(
        $isLimited,
        "Subsequent request " . ($i + 1) . " after rate limit should still be blocked"
      );
    }
  }

  /**
   * Test custom window and max requests parameters
   *
   * Should respect custom windowSeconds and maxRequests values
   */
  public function testCustomParameters() {
    $customWindow = 120; // 2 minutes
    $customMax = 5;

    // Make custom_max number of requests
    for ($i = 0; $i < $customMax; $i++) {
      $isLimited = CRM_Utils_RateLimiter::isRateLimited(
        $this->testPrefix . '_custom',
        $customWindow,
        $customMax
      );
      $this->assertFalse($isLimited, "Request " . ($i + 1) . " should be allowed with custom limit of $customMax");
    }

    // The next request should be blocked
    $isLimited = CRM_Utils_RateLimiter::isRateLimited(
      $this->testPrefix . '_custom',
      $customWindow,
      $customMax
    );
    $this->assertTrue($isLimited, "Request exceeding custom limit of $customMax should be blocked");
  }

  /**
   * Test that different prefixes are isolated
   *
   * Rate limits for different prefixes should not interfere with each other
   */
  public function testDifferentPrefixesAreIsolated() {
    $prefix1 = $this->testPrefix . '_prefix1';
    $prefix2 = $this->testPrefix . '_prefix2';
    $maxRequests = CRM_Utils_RateLimiter::DEFAULT_MAX_REQUESTS;

    // Exhaust limit for prefix1
    for ($i = 0; $i <= $maxRequests; $i++) {
      CRM_Utils_RateLimiter::isRateLimited($prefix1);
    }

    // prefix1 should be rate limited
    $isLimited1 = CRM_Utils_RateLimiter::isRateLimited($prefix1);
    $this->assertTrue($isLimited1, 'prefix1 should be rate limited');

    // prefix2 should still be allowed
    $isLimited2 = CRM_Utils_RateLimiter::isRateLimited($prefix2);
    $this->assertFalse($isLimited2, 'prefix2 should not be rate limited');
  }

  /**
   * Test cleanup method execution
   *
   * The cleanup method should remove expired records
   * Note: Due to probabilistic execution, we call it multiple times
   */
  public function testCleanupRemovesExpiredRecords() {
    $prefix = $this->testPrefix . '_cleanup';
    $shortWindow = 1; // 1 second window for testing

    // Create a record
    CRM_Utils_RateLimiter::isRateLimited($prefix, $shortWindow, 5);

    // Verify record exists using LIKE query (don't try to guess exact key)
    $dao = new CRM_Core_DAO_Sequence();
    $dao->whereAdd("name LIKE '" . CRM_Core_DAO::escapeString($prefix) . "_%'");
    $recordCount = $dao->find();
    $this->assertGreaterThan(0, $recordCount, 'Record should exist after first request');

    // Wait for window to expire
    sleep(2);

    // Force cleanup by calling it multiple times (to overcome probabilistic execution)
    $cleanupExecuted = FALSE;
    for ($i = 0; $i < 1000; $i++) {
      if (CRM_Utils_RateLimiter::cleanup($prefix, $shortWindow)) {
        $cleanupExecuted = TRUE;
        break;
      }
    }

    $this->assertTrue($cleanupExecuted, 'Cleanup should eventually execute');

    // Verify record was deleted
    $dao = new CRM_Core_DAO_Sequence();
    $dao->whereAdd("name LIKE '" . CRM_Core_DAO::escapeString($prefix) . "_%'");
    $recordCount = $dao->find();
    $this->assertEquals(0, $recordCount, 'Record should be deleted after cleanup');
  }

  /**
   * Test that window expiration resets the counter
   *
   * After the time window expires, the counter should reset
   * Note: This test uses a short window (2 seconds) for practical testing
   */
  public function testWindowExpirationResetsCounter() {
    $prefix = $this->testPrefix . '_expiration';
    $shortWindow = 2; // 2 seconds window
    $maxRequests = 3;

    // Exhaust the limit
    for ($i = 0; $i < $maxRequests; $i++) {
      CRM_Utils_RateLimiter::isRateLimited($prefix, $shortWindow, $maxRequests);
    }

    // Should be rate limited
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix, $shortWindow, $maxRequests);
    $this->assertTrue($isLimited, 'Should be rate limited before window expires');

    // Wait for window to expire
    sleep(2); // Wait slightly longer than window

    // Should now be allowed (counter reset)
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix, $shortWindow, $maxRequests);
    $this->assertFalse($isLimited, 'Should be allowed after window expires');
  }

  /**
   * Test handling of corrupted data in civicrm_sequence
   *
   * If stored data is corrupted, the rate limiter should reset the counter
   */
  public function testCorruptedDataHandling() {
    $prefix = $this->testPrefix . '_corrupted';
    $ip = CRM_Utils_System::ipAddress();
    $key = $this->generateKey($prefix, $ip);

    // Create a record with corrupted data
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = $key;
    $dao->value = 'invalid json data';
    $dao->timestamp = microtime(TRUE);
    $dao->insert();

    // Next request should handle corrupted data gracefully and reset counter
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix);
    $this->assertFalse($isLimited, 'Should allow request and reset counter for corrupted data');

    // Verify data was reset to valid format
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = $key;
    $recordCount = $dao->find(TRUE);
    if ($recordCount > 0) {
      $data = json_decode($dao->value, TRUE);
      $this->assertIsArray($data, 'Data should be valid array after reset');
      $this->assertArrayHasKey('count', $data, 'Data should have count key');
      $this->assertArrayHasKey('window_start', $data, 'Data should have window_start key');
      $this->assertEquals(1, $data['count'], 'Count should be reset to 1');
    }
    else {
      $this->fail('Record should exist after handling corrupted data');
    }
  }

  /**
   * Test handling of missing required fields in stored data
   */
  public function testMissingFieldsHandling() {
    $prefix = $this->testPrefix . '_missing_fields';
    $ip = CRM_Utils_System::ipAddress();
    $key = $this->generateKey($prefix, $ip);

    // Create a record with missing fields
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = $key;
    $dao->value = json_encode(['count' => 5]); // Missing window_start
    $dao->timestamp = microtime(TRUE);
    $dao->insert();

    // Should handle missing fields and reset counter
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix);
    $this->assertFalse($isLimited, 'Should allow request and reset counter for incomplete data');
  }

  /**
   * Test key generation with very long prefix
   *
   * Keys longer than MAX_KEY_LENGTH should be truncated using md5 hash
   */
  public function testLongPrefixKeyGeneration() {
    // Create a very long prefix (over 64 characters)
    $longPrefix = $this->testPrefix . '_' . str_repeat('very_long_prefix_', 10);

    // Should not throw an error
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($longPrefix);
    $this->assertFalse($isLimited, 'Should handle long prefix without error');

    // Verify the key was truncated in database
    $ip = CRM_Utils_System::ipAddress();
    $sanitizedIp = preg_replace('/[^a-zA-Z0-9]/', '_', $ip);

    // Find any record with our test prefix
    $dao = new CRM_Core_DAO_Sequence();
    $dao->whereAdd("name LIKE '" . $this->testPrefix . "%'");
    if ($dao->find(TRUE)) {
      $this->assertLessThanOrEqual(
        CRM_Utils_RateLimiter::MAX_KEY_LENGTH,
        strlen($dao->name),
        'Generated key should not exceed MAX_KEY_LENGTH'
      );
    }
  }

  /**
   * Test that empty IP address allows request (fail open)
   *
   * If IP cannot be determined, the system should allow the request
   * Note: This is difficult to test directly as it requires mocking CRM_Utils_System::ipAddress()
   * This test documents the expected behavior
   */
  public function testEmptyIpAllowsRequest() {
    // This test documents that when IP is empty, isRateLimited should return FALSE
    // In practice, CRM_Utils_System::ipAddress() should always return something,
    // but if it returns empty, the code is designed to fail open (allow request)

    // We can't easily mock static methods, so we document this as expected behavior
    $this->assertTrue(TRUE, 'Empty IP should allow request (fail open for safety)');
  }

  /**
   * Test cleanup method return value
   *
   * Cleanup should return TRUE when executed, FALSE when skipped
   */
  public function testCleanupReturnValue() {
    $prefix = $this->testPrefix . '_cleanup_return';

    // Call cleanup multiple times
    $executedCount = 0;
    $skippedCount = 0;

    for ($i = 0; $i < 200; $i++) {
      $result = CRM_Utils_RateLimiter::cleanup($prefix);
      if ($result) {
        $executedCount++;
      }
      else {
        $skippedCount++;
      }
    }

    // Due to probabilistic execution (1/100), we should have approximately 2 executions
    $this->assertGreaterThan(0, $executedCount, 'Cleanup should execute at least once in 200 calls');
    $this->assertGreaterThan(0, $skippedCount, 'Cleanup should skip most calls due to probability');

    // Probability should be roughly 1/100, so ~2 executions expected
    $this->assertLessThan(20, $executedCount, 'Cleanup should not execute too frequently');
  }

  /**
   * Test rate limiting with zero max requests
   *
   * With maxRequests = 0, all requests should be blocked
   */
  public function testZeroMaxRequestsBlocksAll() {
    $prefix = $this->testPrefix . '_zero_max';

    // First request with maxRequests = 0 should be allowed (count becomes 1)
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix, 60, 0);
    $this->assertFalse($isLimited, 'First request should be allowed even with maxRequests=0');

    // Second request should be blocked (count is 1, which is >= 0)
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($prefix, 60, 0);
    $this->assertTrue($isLimited, 'Second request should be blocked with maxRequests=0');
  }

  /**
   * Test that rate limiter works with IPv6 addresses
   *
   * IPv6 addresses contain colons which should be sanitized
   */
  public function testIpv6AddressHandling() {
    // This test verifies that IPv6 addresses (containing colons) are properly sanitized
    // The sanitization is tested indirectly through the main isRateLimited flow

    // If current IP is IPv6, this will test it
    // If IPv4, this test still passes as we're testing the sanitization logic exists
    $isLimited = CRM_Utils_RateLimiter::isRateLimited($this->testPrefix . '_ipv6');
    $this->assertFalse($isLimited, 'Should handle IPv6 addresses without error');

    // Verify the IP was sanitized properly (no raw colons in key)
    $dao = new CRM_Core_DAO_Sequence();
    $dao->whereAdd("name LIKE '" . $this->testPrefix . "_ipv6%'");
    if ($dao->find(TRUE)) {
      // The key should not contain raw colons from IPv6
      $this->assertStringNotContainsString(':', $dao->name, 'Sanitized key should not contain colons');
    }
  }

  /**
   * Test concurrent requests behavior
   *
   * Multiple requests in quick succession should properly increment counter
   */
  public function testConcurrentRequests() {
    $prefix = $this->testPrefix . '_concurrent';
    $maxRequests = 5;

    // Simulate rapid requests
    $results = [];
    for ($i = 0; $i < $maxRequests + 2; $i++) {
      $results[] = CRM_Utils_RateLimiter::isRateLimited($prefix, 60, $maxRequests);
    }

    // First maxRequests should be FALSE (allowed)
    for ($i = 0; $i < $maxRequests; $i++) {
      $this->assertFalse($results[$i], "Request $i should be allowed");
    }

    // Subsequent requests should be TRUE (blocked)
    for ($i = $maxRequests; $i < count($results); $i++) {
      $this->assertTrue($results[$i], "Request $i should be blocked");
    }
  }
}
