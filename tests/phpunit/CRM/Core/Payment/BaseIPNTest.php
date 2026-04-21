<?php
/**
 * Concurrency safety tests for IPN processing.
 *
 * Covers two independent locking mechanisms introduced to prevent receipt-id
 * gaps when two IPN notifications for the same trxn_id arrive simultaneously:
 *
 *  1. CRM_Core_Lock in BaseIPN::completeTransaction — serialises the entire
 *     completion flow per contribution_id so duplicate IPNs are detected and
 *     skipped after the lock is acquired.
 *
 *  2. CRM_Core_Lock in genReceiptID — prevents two concurrent callers from
 *     both reading receipt_id = NULL, each consuming a sequence number, and
 *     the later writer overwriting the earlier receipt_id (causing a gap).
 *     (SELECT … FOR UPDATE is not used because CiviCRM's PEAR DB layer only
 *     starts a MySQL transaction on the first DML statement, so a SELECT runs
 *     in autocommit mode and the row-lock releases immediately.)
 *
 * Subprocess-based concurrent tests use the same pattern as the existing
 * ContributionTest::testLastReceiptId: launch a worker phpunit process in the
 * background, introduce a controlled sleep window to maximise the race surface,
 * then verify the outcome after both processes finish.
 *
 * Run:
 *   docker exec neticrm-ci-php83-d10 bash -c \
 *     "cd tests/phpunit && phpunit CRM/Core/Payment/BaseIPNTest.php"
 */

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Payment_BaseIPNTest extends CiviUnitTestCase {

  public $DBResetRequired = FALSE;

  // Receipt-id prefix used by the tests.  Chosen to be recognisable and
  // unlikely to collide with real data in a development database.
  public const RECEIPT_PREFIX = 'BIPNTEST';

  public function get_info() {
    return [
      'name'        => 'BaseIPN Concurrency Tests',
      'description' => 'Verify CRM_Core_Lock prevents receipt-id gaps under concurrent IPN processing.',
      'group'       => 'Payment Processor Tests',
    ];
  }

  // ------------------------------------------------------------------
  // Setup / teardown
  // ------------------------------------------------------------------

  /** @before */
  public function setUpTest() {
    parent::setUp();
    // Worker subprocesses must NOT clean up: the main process's contribution
    // has already been committed to DB and would be wiped by cleanup here.
    if (empty(getenv('BASEIPN_CONTRIB_ID')) && empty(getenv('BASEIPN_LOCK_NAME'))) {
      $this->_cleanupTestData();
    }
  }

  /** @after */
  public function tearDownTest() {
    // Only the main process cleans up; workers let the main process teardown
    // handle it so there is no race between teardown and the main test body.
    if (empty(getenv('BASEIPN_CONTRIB_ID')) && empty(getenv('BASEIPN_LOCK_NAME'))) {
      $this->_cleanupTestData();
    }
  }

  /**
   * Remove any contributions and sequence rows created by these tests.
   * Uses FK-safe deletion: child tables first, then contributions.
   */
  protected function _cleanupTestData() {
    $prefix = self::RECEIPT_PREFIX;
    // Identify test contribution ids
    $ids = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contribution WHERE trxn_id LIKE 'BIPN_%'");
    $contribIds = [];
    while ($ids->fetch()) {
      $contribIds[] = (int) $ids->id;
    }
    foreach ($contribIds as $cid) {
      CRM_Contribute_BAO_Contribution::deleteContribution($cid);
    }
    // Use LIKE '%prefix%' so the 'M'/'A' online-mode prefix prepended by
    // genReceiptID (e.g. "MBIPNTEST", "ABIPNTEST") is also matched.
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_contribution SET receipt_id = NULL WHERE receipt_id LIKE '%{$prefix}%'"
    );
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_sequence WHERE name LIKE '%{$prefix}%'"
    );
  }

  // ------------------------------------------------------------------
  // Helper factories
  // ------------------------------------------------------------------

  /**
   * Return any existing Individual contact id (cheap; avoids creating contacts).
   */
  protected function _getContactId() {
    $cid = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_contact WHERE contact_type = 'Individual' ORDER BY id ASC LIMIT 1"
    );
    if (empty($cid)) {
      $r = civicrm_api('Contact', 'create', [
        'version' => 3, 'contact_type' => 'Individual',
        'first_name' => 'IPN', 'last_name' => 'Test',
      ]);
      $cid = $r['id'];
    }
    return (int) $cid;
  }

  /**
   * Return a deductible contribution_type id (required for genReceiptID to run).
   */
  protected function _getDeductibleTypeId() {
    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_contribution_type WHERE is_deductible = 1 AND is_active = 1 LIMIT 1"
    );
  }

  /**
   * Create a Completed contribution whose receipt_id is intentionally NULL.
   *
   * The contribution is first created as Pending (status=2) so that create()
   * does not invoke genReceiptID automatically.  Then it is flipped to
   * Completed + receipt_date via a direct SQL UPDATE, replicating the state
   * that exists immediately after BaseIPN::completeTransaction() commits but
   * before genReceiptID is called.
   *
   * @return CRM_Contribute_DAO_Contribution|null  NULL when no deductible type exists.
   */
  protected function _createCompletedContribNoReceiptId() {
    $typeId = $this->_getDeductibleTypeId();
    if (empty($typeId)) {
      return NULL;
    }
    $trxn = 'BIPN_' . getmypid() . '_' . rand(1000, 9999);
    // create() takes $params by reference; must be a variable, not a literal.
    $params = [
      'contact_id'             => $this->_getContactId(),
      'currency'               => 'TWD',
      'contribution_type_id'   => $typeId,
      'contribution_status_id' => 2,  // Pending — genReceiptID skipped by create()
      'payment_instrument_id'  => 1,
      'receive_date'           => date('YmdHis'),
      'total_amount'           => 100.00,
      'fee_amount'             => 0,
      'net_amount'             => 100.00,
      'trxn_id'                => $trxn,
    ];
    $ids = ['contribution' => NULL];
    $contrib = CRM_Contribute_BAO_Contribution::create($params, $ids);

    // Simulate the post-completeTransaction state: Completed + receipt_date,
    // but receipt_id still NULL.
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_contribution
          SET contribution_status_id = 1,
              receipt_date = %1
        WHERE id = %2",
      [
        1 => [date('Y-m-d H:i:s'), 'String'],
        2 => [$contrib->id, 'Integer'],
      ]
    );

    return $contrib;
  }

  // ------------------------------------------------------------------
  // Test 1 – MySQL advisory lock: basic acquire / hold / release
  // ------------------------------------------------------------------

  /**
   * Verify that CRM_Core_Lock (MySQL advisory lock) behaves correctly.
   *
   * This test confirms that CRM_Core_Lock::acquire(), isAcquired(),
   * isFree(), and release() all work as expected in the container's MySQL
   * version before we rely on them in the more complex concurrent tests.
   */
  public function testIPNLockMysqlBasicMechanism() {
    $shortName = 'ipn_contrib_test_' . getmypid();

    // Acquire the lock (must be free immediately; use a short timeout)
    $lock = new CRM_Core_Lock($shortName, 1);
    $this->assertTrue($lock->isAcquired(), 'CRM_Core_Lock should be acquired when lock is free');

    // While held, IS_FREE_LOCK must return 0
    $free = (int) CRM_Core_Lock::isFree($shortName);
    $this->assertEquals(0, $free, 'CRM_Core_Lock::isFree should return 0 while lock is held');

    // Release and verify return value (RELEASE_LOCK returns 1 on success)
    $released = (int) $lock->release();
    $this->assertEquals(1, $released, 'CRM_Core_Lock::release should return 1');

    // After release IS_FREE_LOCK must return 1
    $free = (int) CRM_Core_Lock::isFree($shortName);
    $this->assertEquals(1, $free, 'CRM_Core_Lock::isFree should return 1 after lock is released');
  }

  // ------------------------------------------------------------------
  // Test 2 – GET_LOCK blocks a second process
  //
  //   Main process   │  acquires lock  │  holds 5+ s  │  releases
  //   Worker process │  -              │  GET_LOCK(2s) → must fail (0)
  // ------------------------------------------------------------------

  /**
   * Worker invoked by testIPNGetLockBlocksConcurrentProcess via subprocess.
   *
   * Reads BASEIPN_LOCK_NAME from the environment, attempts GET_LOCK with a
   * 2-second timeout, and asserts that it returns 0 (timeout) because the
   * main process is holding the same lock.
   *
   * This test method is NOT meant to be run directly by the test suite; the
   * @group annotation excludes it from the default run.
   *
   * @group worker
   */
  public function testIPNGetLockWorker() {
    $shortName = getenv('BASEIPN_LOCK_NAME');
    if (empty($shortName)) {
      $this->markTestSkipped('Worker: BASEIPN_LOCK_NAME env var not set — run via testIPNGetLockBlocksConcurrentProcess.');
      return;
    }
    // Main process holds the lock.  Try to acquire with a 2 s timeout;
    // it should not be released in that window.
    $lock = new CRM_Core_Lock($shortName, 2);
    $this->assertFalse(
      $lock->isAcquired(),
      "CRM_Core_Lock should time out (not acquired) while main process holds '{$shortName}'"
    );
  }

  /**
   * Acquire ipn_contrib_{id} in the main process, then spawn a subprocess
   * that tries to acquire the same lock.  The subprocess must time out.
   *
   * This reproduces the serialisation that BaseIPN::completeTransaction
   * applies: when IPN A holds the lock and is inside the completion flow,
   * IPN B's GET_LOCK call blocks (here verified to time out), preventing
   * duplicate DB writes.
   */
  public function testIPNGetLockBlocksConcurrentProcess() {
    global $civicrm_root;

    $contribId = 9999999; // Fake id — only the lock name matters here
    $shortName = "ipn_contrib_{$contribId}";

    // Main process acquires the IPN advisory lock via CRM_Core_Lock
    $lock = new CRM_Core_Lock($shortName, 1);
    $this->assertTrue($lock->isAcquired(), 'Main process should acquire IPN lock');

    // Spawn a worker subprocess that tries to acquire the same lock.
    // The worker uses the same short name; CRM_Core_Lock adds the same
    // database+domainID prefix, so both resolve to the same MySQL lock.
    // The worker's 2-second timeout expires while main holds the lock.
    $cmd = sprintf(
      'cd %s/tests/phpunit && BASEIPN_LOCK_NAME=%s phpunit --filter testIPNGetLockWorker CRM/Core/Payment/BaseIPNTest.php 2>&1',
      escapeshellarg($civicrm_root),
      escapeshellarg($shortName)
    );
    exec($cmd, $output, $returnCode);

    // Release before asserting so remaining code is not blocked
    $lock->release();

    $this->assertEquals(
      0,
      $returnCode,
      "Worker subprocess should exit 0 (its assertion passed — lock timed out):\n" . implode("\n", $output)
    );
  }

  // ------------------------------------------------------------------
  // Test 2.5 – Sequential smoke test: genReceiptID works in this env
  // ------------------------------------------------------------------

  /**
   * Verify that genReceiptID allocates a receipt_id for a single call
   * (no concurrency) before relying on it in the concurrent test.
   */
  public function testGenReceiptIDSequentialSmoke() {
    $typeId = $this->_getDeductibleTypeId();
    if (empty($typeId)) {
      $this->markTestSkipped('No deductible contribution type.');
      return;
    }
    $config = CRM_Core_Config::singleton();
    $savedPrefix = $config->receiptPrefix;
    $config->receiptPrefix = self::RECEIPT_PREFIX;

    try {
      $contrib = $this->_createCompletedContribNoReceiptId();
      $this->assertNotEmpty($contrib->id, 'Test contribution must be created');

      $GLOBALS['CiviTest_ContributionTest_sleep'] = 0;
      $receiptId = CRM_Contribute_BAO_Contribution::genReceiptID($contrib->id, TRUE, FALSE);

      $this->assertNotEmpty($receiptId, 'genReceiptID must return a non-empty receipt_id');
      $this->assertStringContainsString(
        self::RECEIPT_PREFIX,
        $receiptId,
        'receipt_id must include the test prefix (' . self::RECEIPT_PREFIX . ')'
      );

      $dbReceiptId = CRM_Core_DAO::singleValueQuery(
        "SELECT receipt_id FROM civicrm_contribution WHERE id = %1",
        [1 => [$contrib->id, 'Integer']]
      );
      $this->assertEquals($receiptId, $dbReceiptId, 'receipt_id returned must match DB value');

      // Cleanup is handled by tearDownTest() → _cleanupTestData()
    }
    finally {
      $config->receiptPrefix = $savedPrefix;
    }
  }

  // ------------------------------------------------------------------
  // Test 3 – GET_LOCK prevents receipt-id gap under concurrent genReceiptID
  //
  //   Note: SELECT … FOR UPDATE is NOT used for this serialisation because
  //   CiviCRM's PEAR DB layer only starts a real MySQL transaction on the
  //   first DML statement; a SELECT runs in autocommit mode and the row-lock
  //   would be released immediately.  GET_LOCK (session-level advisory lock)
  //   is used instead.
  //
  //   Process A (worker, sleep=4 s inside lastReceiptID)
  //     GET_LOCK('genReceiptID_contrib_X') → holds lock
  //       → re-read receipt_id (NULL) → calls lastReceiptID
  //         → touches flag file → sleep 4 s
  //         → allocate seq N → save receipt_id → RELEASE_LOCK
  //
  //   Process B (main, sleep=0 s)
  //     GET_LOCK('genReceiptID_contrib_X') → BLOCKED until A releases
  //       → re-read receipt_id (A's value, non-empty) → early return
  //
  //   Expected: contribution has exactly one receipt_id; sequence.value == N
  // ------------------------------------------------------------------

  /**
   * Worker invoked by testConcurrentGenReceiptIDNoGap via subprocess.
   *
   * Calls genReceiptID with a sleep window so that the main process has time
   * to start its own genReceiptID call and then block on the FOR UPDATE lock.
   *
   * BASEIPN_CONTRIB_ID env var supplies the contribution to target.
   * BASEIPN_RECEIPT_PREFIX env var supplies the prefix override.
   *
   * @group worker
   */
  public function testConcurrentGenReceiptIDWorker() {
    $contribId = (int) getenv('BASEIPN_CONTRIB_ID');
    if (empty($contribId)) {
      $this->markTestSkipped('Worker: BASEIPN_CONTRIB_ID env var not set — run via testConcurrentGenReceiptIDNoGap.');
      return;
    }

    // Diagnostic: CIVICRM_TEST_DSN must be set so the sleep hook activates
    $this->assertNotEmpty(
      getenv('CIVICRM_TEST_DSN'),
      'Worker: CIVICRM_TEST_DSN must be inherited by the subprocess'
    );

    $receiptPrefix = getenv('BASEIPN_RECEIPT_PREFIX');
    if (!empty($receiptPrefix)) {
      $config = CRM_Core_Config::singleton();
      $config->receiptPrefix = $receiptPrefix;
    }

    // Verify the contribution is in the expected state before proceeding
    $status = (int) CRM_Core_DAO::singleValueQuery(
      "SELECT contribution_status_id FROM civicrm_contribution WHERE id = %1",
      [1 => [$contribId, 'Integer']]
    );
    $this->assertEquals(
      1,
      $status,
      "Worker: contribution {$contribId} must be Completed before genReceiptID"
    );

    // Sleep inside lastReceiptID to hold the FOR UPDATE lock long enough for
    // the main process to start its own genReceiptID call and block.
    $GLOBALS['CiviTest_ContributionTest_sleep'] = 4;
    $result = CRM_Contribute_BAO_Contribution::genReceiptID($contribId, TRUE, FALSE);

    $this->assertNotEmpty(
      $result,
      "Worker: genReceiptID must return a receipt_id (returned: " . var_export($result, TRUE) . ")"
    );
  }

  /**
   * Run two concurrent genReceiptID calls on the same Completed contribution
   * (receipt_id = NULL) and verify that:
   *
   *  (a) Exactly one receipt_id ends up in the database (no overwrite).
   *  (b) civicrm_sequence.value equals the numeric suffix of that receipt_id
   *      (no gap — the sequence counter was not advanced twice).
   */
  public function testConcurrentGenReceiptIDNoGap() {
    global $civicrm_root;

    $typeId = $this->_getDeductibleTypeId();
    if (empty($typeId)) {
      $this->markTestSkipped('No deductible contribution type — cannot test genReceiptID.');
      return;
    }

    // Override receipt prefix to a known test value so we can clean up
    // deterministically and avoid colliding with real data.
    $config = CRM_Core_Config::singleton();
    $savedPrefix     = $config->receiptPrefix;
    $config->receiptPrefix = self::RECEIPT_PREFIX;

    try {
      $contrib = $this->_createCompletedContribNoReceiptId();
      $this->assertNotEmpty($contrib->id, 'Failed to create test contribution');
      $contribId = $contrib->id;

      // Pre-condition: receipt_id must be NULL
      $pre = CRM_Core_DAO::singleValueQuery(
        "SELECT receipt_id FROM civicrm_contribution WHERE id = %1",
        [1 => [$contribId, 'Integer']]
      );
      $this->assertEmpty($pre, 'Pre-condition failed: contribution must have receipt_id = NULL before the test');

      // ---- Spawn Process A in background (sleep = 4 s) ----
      // A flag file is used for deterministic synchronisation: A touches it
      // from inside lastReceiptID *after* acquiring the FOR UPDATE row-lock
      // but *before* sleeping.  B polls for the file so it only calls
      // genReceiptID once the row-lock is definitely held, guaranteeing that
      // B's SELECT FOR UPDATE will block rather than win the race.
      $logFile  = sys_get_temp_dir() . '/bipn_worker_' . $contribId . '.log';
      $flagFile = sys_get_temp_dir() . '/bipn_lock_acquired_' . $contribId . '.flag';
      @unlink($flagFile); // clean slate from any prior run

      $cmd = sprintf(
        'cd %s/tests/phpunit && BASEIPN_CONTRIB_ID=%d BASEIPN_RECEIPT_PREFIX=%s BASEIPN_LOCK_ACQUIRED_FLAG=%s phpunit --filter testConcurrentGenReceiptIDWorker CRM/Core/Payment/BaseIPNTest.php > %s 2>&1',
        escapeshellarg($civicrm_root),
        $contribId,
        escapeshellarg(self::RECEIPT_PREFIX),
        escapeshellarg($flagFile),
        escapeshellarg($logFile)
      );
      exec("{$cmd} &");

      // Poll until A signals that it holds the FOR UPDATE lock (max 15 s).
      // This is deterministic: B will not call genReceiptID until A's row-lock
      // is confirmed, so B is guaranteed to block on SELECT FOR UPDATE.
      $deadline = time() + 15;
      while (!file_exists($flagFile) && time() < $deadline) {
        usleep(100000); // poll every 100 ms
      }
      @unlink($flagFile);
      $workerLog = file_exists($logFile) ? file_get_contents($logFile) : '(log file missing)';
      $this->assertTrue(
        time() < $deadline,
        "Timed out (15 s) waiting for worker to acquire FOR UPDATE lock.\nWorker log:\n{$workerLog}"
      );

      // ---- Process B (this process, no sleep) ----
      // B opens its transaction and issues SELECT … FOR UPDATE.  A holds the
      // row-lock (and is sleeping inside lastReceiptID), so B blocks here until
      // A commits (~2-3 s remaining of A's 4 s sleep).  After A commits, B
      // reads A's receipt_id (non-empty) and returns early without allocating
      // another sequence number.
      $GLOBALS['CiviTest_ContributionTest_sleep'] = 0;
      $receiptIdFromB = CRM_Contribute_BAO_Contribution::genReceiptID($contribId, TRUE, FALSE);

      // Wait for A to finish completely (4 s sleep + overhead)
      sleep(5);

      // ---- Verify outcome ----
      $finalReceiptId = CRM_Core_DAO::singleValueQuery(
        "SELECT receipt_id FROM civicrm_contribution WHERE id = %1",
        [1 => [$contribId, 'Integer']]
      );

      $workerLog = file_exists($logFile) ? file_get_contents($logFile) : '(log file missing)';
      $this->assertNotEmpty(
        $finalReceiptId,
        "Contribution must have a receipt_id after concurrent genReceiptID calls.\nWorker log:\n{$workerLog}"
      );

      // B must have returned the receipt_id that A saved — not allocated a new one
      $this->assertEquals(
        $finalReceiptId,
        $receiptIdFromB,
        'Process B should return the receipt_id already saved by process A (SELECT FOR UPDATE early-return), not overwrite it'
      );

      // Extract the numeric suffix from the receipt_id
      // e.g. "MBIPNTEST-000001" → suffix = "000001" (= 1)
      $parts   = explode('-', $finalReceiptId);
      $seqNum  = (int) end($parts);
      // Prefix is everything before the last '-'
      $seqKey  = implode('-', array_slice($parts, 0, count($parts) - 1));

      $seqValue = (int) CRM_Core_DAO::singleValueQuery(
        "SELECT value FROM civicrm_sequence WHERE name = %1",
        [1 => [$seqKey, 'String']]
      );

      $this->assertEquals(
        $seqNum,
        $seqValue,
        "civicrm_sequence.value ({$seqValue}) must equal the receipt_id numeric suffix ({$seqNum}). " .
        "A mismatch means a sequence number was consumed but never stored — a gap."
      );

      // Cleanup handled by tearDownTest() → _cleanupTestData()
    }
    finally {
      $config->receiptPrefix = $savedPrefix;
    }
  }
}
