<?php

/**
 * Audit recurring contribution execution by payment gateway.
 *
 * Compact summaries are stored in civicrm_log. Detailed recurring IDs are
 * stored separately in CRM_Contribute_BAO_AuditContributionRecurCache.
 */
class CRM_Contribute_BAO_AuditContributionRecur {

  const VERSION = 1;
  const ESTIMATE_PREFIX = 'audit.recur.estimate.';
  const DISPATCH_PREFIX = 'audit.recur.dispatch.';
  const DISPATCH_RECHECK_SECONDS = 1800;
  const MAX_LOG_BYTES = 60000;

  /**
   * Supported recurring payment gateways.
   *
   * @var array
   */
  protected static $_gateways = [
    'tappay',
    'linepay',
    'spgateway',
  ];

  /**
   * Check whether a recurring execution uses the current request time.
   *
   * An empty value is treated as current because doExecuteAllRecur() replaces
   * it with the current timestamp. An explicit time must match the request
   * timestamp so backfill and test executions do not create audit records.
   *
   * @param int|null $time
   *
   * @return bool
   */
  public static function isCurrentExecutionTime($time = NULL) {
    return empty($time) || (
      is_numeric($time) &&
      (int) $time === (int) CRM_REQUEST_TIME
    );
  }

  /**
   * Record the daily Not Executed estimate for one gateway.
   *
   * @param string $gateway
   * @param int|null $time
   *
   * @return array
   */
  public static function recordEstimate($gateway, $time = NULL) {
    $gateway = strtolower(trim((string) $gateway));
    if (!in_array($gateway, self::$_gateways, TRUE)) {
      return [];
    }

    try {
      $time = is_numeric($time) && (int) $time > 0 ? (int) $time : time();
      $dateId = (int) date('Ymd', $time);
      $table = self::ESTIMATE_PREFIX . $gateway;
      $log = self::readLog($table, $dateId);

      if ($log !== NULL) {
        if (
          (int) CRM_Utils_Array::value('version', $log['data'], 0) !==
            self::VERSION
        ) {
          CRM_Core_Error::debug_log_message(
            "[AuditContributionRecur] $gateway incompatible audit exists for " .
            "$dateId; current audit was deferred."
          );
          return [];
        }
        return $log['data'] + ['log_id' => (int) $log['id']];
      }

      $cached = CRM_Contribute_BAO_AuditContributionRecurCache::get(
        $gateway,
        $dateId
      );
      if (
        (int) CRM_Utils_Array::value('version', $cached, 0) ===
          self::VERSION &&
        (int) CRM_Utils_Array::value('date_id', $cached, 0) === $dateId &&
        CRM_Utils_Array::value('gateway', $cached) === $gateway &&
        isset(
          $cached['ids']['estimate'],
          $cached['ids']['dispatch'],
          $cached['ids']['attempt'],
          $cached['processor_by_recur'],
          $cached['processor_names']
        ) &&
        empty($cached['ids']['estimate'])
      ) {
        $createdAt = CRM_Utils_Array::value(
          'created_at',
          $cached,
          date('Y-m-d H:i:s', $time)
        );
        $data = [
          'version' => self::VERSION,
          'date' => date('Y-m-d', $time),
          'date_id' => $dateId,
          'gateway' => $gateway,
          'state' => 'pending',
          'checks' => 0,
          'previous' => NULL,
          'created_at' => $createdAt,
          'updated_at' => $createdAt,
        ];
        return self::buildSummary($data, $cached);
      }

      $rows = self::findCandidates($gateway, $time);
      $estimateIds = [];
      $processorByRecur = [];
      $processorNames = [];
      foreach ($rows as $row) {
        $recurId = (int) $row['recur_id'];
        $processorId = (int) $row['processor_id'];
        $estimateIds[] = $recurId;
        $processorByRecur[$recurId] = $processorId;
        $processorNames[$processorId] = (string) $row['processor_name'];
      }
      $estimateIds = self::normalizeIds($estimateIds);
      ksort($processorByRecur);
      ksort($processorNames);

      $now = date('Y-m-d H:i:s', $time);
      $cache = [
        'version' => self::VERSION,
        'date_id' => $dateId,
        'gateway' => $gateway,
        'created_at' => $now,
        'checks' => 0,
        'ids' => [
          'estimate' => $estimateIds,
          'dispatch' => [],
          'attempt' => [],
          'added' => [],
          'removed' => [],
        ],
        'processor_by_recur' => $processorByRecur,
        'processor_names' => $processorNames,
      ];
      $data = [
        'version' => self::VERSION,
        'date' => date('Y-m-d', $time),
        'date_id' => $dateId,
        'gateway' => $gateway,
        'state' => 'pending',
        'checks' => 0,
        'previous' => NULL,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $data = self::buildSummary($data, $cache);

      $transaction = new CRM_Core_Transaction();
      if (!CRM_Contribute_BAO_AuditContributionRecurCache::set(
        $gateway,
        $dateId,
        $cache
      )) {
        $transaction->rollback();
        $transaction = NULL;
        CRM_Core_Error::debug_log_message(
          "[AuditContributionRecur] $gateway estimate cache write failed."
        );
        return [];
      }

      if (!$data['estimate']['count']) {
        $transaction->commit();
        $transaction = NULL;
        return $data;
      }

      $saved = self::saveLog(
        $table,
        $dateId,
        $data,
        $time
      );
      if ($saved === NULL) {
        $transaction->rollback();
        $transaction = NULL;
        return [];
      }
      $transaction->commit();
      $transaction = NULL;
      return $saved['data'] + ['log_id' => (int) $saved['id']];
    }
    catch (Throwable $e) {
      if (isset($transaction)) {
        $transaction->rollback();
        $transaction = NULL;
      }
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway estimate failed: " . $e->getMessage()
      );
      return [];
    }
  }

  /**
   * Record one recurring execution observation for a gateway.
   *
   * The estimate becomes stable when two consecutive observations contain
   * the same recurring IDs whose last_execute_date is in the audit day.
   *
   * Stable empty observations are reused for 30 minutes before checking for
   * late asynchronous completions.
   *
   * @param string $gateway
   * @param array $recurIds
   * @param int|null $time
   *
   * @return array
   */
  public static function recordDispatch($gateway, array $recurIds, $time = NULL) {
    $gateway = strtolower(trim((string) $gateway));
    if (!in_array($gateway, self::$_gateways, TRUE)) {
      return [];
    }

    try {
      $time = is_numeric($time) && (int) $time > 0 ? (int) $time : time();
      $dateId = (int) date('Ymd', $time);
      $table = self::ESTIMATE_PREFIX . $gateway;
      $log = self::readLog($table, $dateId);
      if (
        $log === NULL ||
        (int) CRM_Utils_Array::value('version', $log['data'], 0) !==
          self::VERSION
      ) {
        return [];
      }

      $checkedAt = strtotime((string) CRM_Utils_Array::value(
        'updated_at',
        $log['data'],
        ''
      ));
      if (
        !$recurIds &&
        $checkedAt &&
        $time >= $checkedAt &&
        $time < $checkedAt + self::DISPATCH_RECHECK_SECONDS &&
        CRM_Utils_Array::value('state', $log['data']) === 'stable'
      ) {
        $daily = self::readLog(
          self::DISPATCH_PREFIX . $gateway,
          $dateId
        );
        $section = $daily === NULL ? NULL : $daily['data'];
        if (
          is_array($section) &&
          (int) CRM_Utils_Array::value('version', $section, 0) ===
            self::VERSION &&
          CRM_Utils_Array::value('gateway', $section) === $gateway &&
          CRM_Utils_Array::value('state', $section) === 'stable' &&
          (int) CRM_Utils_Array::value('checks', $section, -1) ===
            (int) CRM_Utils_Array::value('checks', $log['data'], 0) &&
          CRM_Utils_Array::value(
            'hash',
            CRM_Utils_Array::value('attempt', $section, [])
          ) === CRM_Utils_Array::value(
            'hash',
            CRM_Utils_Array::value('attempt', $log['data'], [])
          )
        ) {
          $data = $log['data'] + ['log_id' => (int) $log['id']];
          $data['daily'] = $section;
          $data['daily_log_id'] = (int) $daily['id'];
          return $data;
        }
      }

      $cache = self::loadCache($gateway, $dateId, $log['data']);
      if ($cache === NULL) {
        return [];
      }
      if (empty($log['data']['estimate']['count'])) {
        return $log['data'] + ['log_id' => (int) $log['id']];
      }

      $dispatchIds = self::normalizeIds(array_merge(
        $cache['ids']['dispatch'],
        $recurIds
      ));
      $trackedIds = self::normalizeIds(array_merge(
        $cache['ids']['estimate'],
        $dispatchIds
      ));
      $previousIds = $cache['ids']['attempt'];
      $attemptIds = self::findAttempted($trackedIds, $time);
      $checks = (int) CRM_Utils_Array::value('checks', $log['data'], 0);
      $hasPrevious = $checks > 0;
      $addedIds = $hasPrevious
        ? array_values(array_diff($attemptIds, $previousIds))
        : [];
      $removedIds = $hasPrevious
        ? array_values(array_diff($previousIds, $attemptIds))
        : [];
      $stable = $hasPrevious && $attemptIds === $previousIds;

      $cache['checks'] = $checks + 1;
      $cache['ids']['dispatch'] = $dispatchIds;
      $cache['ids']['attempt'] = $attemptIds;
      $cache['ids']['added'] = $addedIds;
      $cache['ids']['removed'] = $removedIds;

      $data = $log['data'];
      $data['version'] = self::VERSION;
      $data['state'] = $stable ? 'stable' : 'observing';
      $data['checks'] = $cache['checks'];
      $data['previous'] = $hasPrevious
        ? self::summarizeIds($previousIds)
        : NULL;
      $data['updated_at'] = date('Y-m-d H:i:s', $time);
      $data = self::buildSummary($data, $cache);

      $transaction = new CRM_Core_Transaction();
      if (!CRM_Contribute_BAO_AuditContributionRecurCache::set(
        $gateway,
        $dateId,
        $cache
      )) {
        $transaction->rollback();
        $transaction = NULL;
        CRM_Core_Error::debug_log_message(
          "[AuditContributionRecur] $gateway dispatch cache write failed."
        );
        return [];
      }

      $saved = self::saveLog(
        $table,
        $dateId,
        $data,
        $time
      );
      if ($saved === NULL) {
        $transaction->rollback();
        $transaction = NULL;
        return [];
      }
      $transaction->commit();
      $transaction = NULL;

      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway observation: previous=" .
        ($hasPrevious ? count($previousIds) : 'none') .
        ', current=' . count($attemptIds) .
        ', added=' . count($addedIds) .
        ', removed=' . count($removedIds) .
        ', stable=' . ($stable ? 'yes' : 'no')
      );

      if ($stable) {
        try {
          self::updateDailyLog($gateway, $time);
        }
        catch (Throwable $e) {
          CRM_Core_Error::debug_log_message(
            '[AuditContributionRecur] Daily dispatch update failed: ' .
            $e->getMessage()
          );
        }
      }

      return $saved['data'] + ['log_id' => (int) $saved['id']];
    }
    catch (Throwable $e) {
      if (isset($transaction)) {
        $transaction->rollback();
        $transaction = NULL;
      }
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway dispatch failed: " . $e->getMessage()
      );
      return [];
    }
  }

  /**
   * Get one gateway's daily estimate and verified daily dispatch section.
   *
   * @param string $gateway
   * @param int|null $time
   *
   * @return array
   */
  public static function getAudit($gateway, $time = NULL) {
    $gateway = strtolower(trim((string) $gateway));
    if (!in_array($gateway, self::$_gateways, TRUE)) {
      return [];
    }

    try {
      $time = is_numeric($time) && (int) $time > 0 ? (int) $time : time();
      $dateId = (int) date('Ymd', $time);
      $log = self::readLog(self::ESTIMATE_PREFIX . $gateway, $dateId);
      if (
        $log === NULL ||
        (int) CRM_Utils_Array::value('version', $log['data'], 0) !==
          self::VERSION
      ) {
        return [];
      }

      $data = $log['data'] + ['log_id' => (int) $log['id']];
      $daily = self::readLog(
        self::DISPATCH_PREFIX . $gateway,
        $dateId
      );
      $section = $daily === NULL ? NULL : $daily['data'];
      if (
        is_array($section) &&
        (int) CRM_Utils_Array::value('version', $section, 0) ===
          self::VERSION &&
        CRM_Utils_Array::value('gateway', $section) === $gateway &&
        CRM_Utils_Array::value('state', $data) === 'stable' &&
        CRM_Utils_Array::value('state', $section) === 'stable' &&
        (int) CRM_Utils_Array::value('checks', $section, -1) ===
          (int) CRM_Utils_Array::value('checks', $data, 0) &&
        CRM_Utils_Array::value(
          'hash',
          CRM_Utils_Array::value('attempt', $section, [])
        ) === CRM_Utils_Array::value(
          'hash',
          CRM_Utils_Array::value('attempt', $data, [])
        )
      ) {
        $data['daily'] = $section;
        $data['daily_log_id'] = (int) $daily['id'];
      }
      return $data;
    }
    catch (Throwable $e) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway audit read failed: " .
        $e->getMessage()
      );
      return [];
    }
  }

  /**
   * Find all daily candidates for one gateway.
   *
   * @param string $gateway
   * @param int $time
   *
   * @return array
   */
  protected static function findCandidates($gateway, $time) {
    $month = date('m', $time);
    $nextDayMonth = date('m', $time + 86400);
    $day = (int) date('j', $time);
    if ($month === $nextDayMonth) {
      $cycleClause = 'r.cycle_day = ' . $day;
    }
    else {
      $days = [];
      for ($cycleDay = $day; $cycleDay <= 31; $cycleDay++) {
        $days[] = $cycleDay;
      }
      $cycleClause = 'r.cycle_day IN (' .
        CRM_Utils_Array::implode(',', $days) . ')';
    }

    $extraJoin = '';
    $extraWhere = '';
    if ($gateway === 'tappay') {
      $statusClause = 'r.contribution_status_id IN (5,6)';
      $processorType = 'TapPay';
    }
    elseif ($gateway === 'linepay') {
      $statusClause = 'r.contribution_status_id IN (5,7)';
      $processorType = 'Mobile';
      $extraJoin = "
INNER JOIN civicrm_contribution_linepay lp
  ON lp.contribution_recur_id = r.id
  AND lp.reg_key IS NOT NULL
  AND lp.reg_key != ''";
    }
    else {
      $statusClause = 'r.contribution_status_id = 5';
      $processorType = 'SPGATEWAY';
      $extraJoin = "
INNER JOIN civicrm_contribution_spgateway s
  ON s.contribution_recur_id = r.id";
      $extraWhere = "
AND COALESCE(p.url_api, '') != ''
AND s.token_value IS NOT NULL";
    }

    $params = [
      1 => [date('Ym01000000', $time), 'Timestamp'],
      2 => [date('Ymd000000', $time), 'Timestamp'],
      3 => [$processorType, 'String'],
    ];
    $sql = "
SELECT
  r.id AS recur_id,
  c.payment_processor_id AS processor_id,
  p.name AS processor_name
FROM civicrm_contribution_recur r
INNER JOIN civicrm_contribution c
  ON c.contribution_recur_id = r.id
INNER JOIN civicrm_payment_processor p
  ON p.id = c.payment_processor_id
$extraJoin
WHERE $cycleClause
  AND (
    SELECT MAX(created_date)
    FROM civicrm_contribution
    WHERE contribution_recur_id = r.id
    GROUP BY r.id
  ) < %1
  AND $statusClause
  AND r.frequency_unit = 'month'
  AND p.payment_processor_type = %3
  AND (r.last_execute_date IS NULL OR r.last_execute_date < %2)
  $extraWhere
GROUP BY r.id
ORDER BY r.id
";
    $loggedSql = CRM_Core_DAO::composeQuery($sql, $params, TRUE);
    CRM_Core_Error::debug_log_message(
      "[AuditContributionRecur] $gateway estimate query:\n$loggedSql"
    );

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'recur_id' => (int) $dao->recur_id,
        'processor_id' => (int) $dao->processor_id,
        'processor_name' => (string) $dao->processor_name,
      ];
    }
    $dao->free();
    return $rows;
  }

  /**
   * Find tracked recurring IDs executed in the audit day.
   *
   * @param array $recurIds
   * @param int $time
   *
   * @return array
   */
  protected static function findAttempted(array $recurIds, $time) {
    $recurIds = self::normalizeIds($recurIds);
    if (!$recurIds) {
      return [];
    }

    $dao = CRM_Core_DAO::executeQuery(
      'SELECT id
       FROM civicrm_contribution_recur
       WHERE id IN (%1)
         AND last_execute_date >= %2
         AND last_execute_date < %3
       ORDER BY id',
      [
        1 => [
          CRM_Utils_Array::implode(',', $recurIds),
          'CommaSeparatedIntegers',
        ],
        2 => [date('Ymd000000', $time), 'Timestamp'],
        3 => [date('Ymd000000', $time + 86400), 'Timestamp'],
      ]
    );
    $attemptIds = [];
    while ($dao->fetch()) {
      $attemptIds[] = (int) $dao->id;
    }
    $dao->free();
    return self::normalizeIds($attemptIds);
  }

  /**
   * Build a compact log summary from detailed cache data.
   *
   * @param array $data
   * @param array $cache
   *
   * @return array
   */
  protected static function buildSummary(array $data, array $cache) {
    $estimateIds = self::normalizeIds($cache['ids']['estimate']);
    $dispatchIds = self::normalizeIds($cache['ids']['dispatch']);
    $attemptIds = self::normalizeIds($cache['ids']['attempt']);
    $processorByRecur = $cache['processor_by_recur'];
    $processorNames = $cache['processor_names'];
    $ready = CRM_Utils_Array::value('state', $data) === 'stable';

    $sets = [];
    foreach ($estimateIds as $recurId) {
      $processorId = CRM_Utils_Array::value($recurId, $processorByRecur);
      if (!$processorId) {
        continue;
      }
      if (!isset($sets[$processorId])) {
        $sets[$processorId] = [
          'estimate' => [],
          'dispatch' => [],
          'attempt' => [],
        ];
      }
      $sets[$processorId]['estimate'][] = $recurId;
    }
    foreach (['dispatch' => $dispatchIds, 'attempt' => $attemptIds] as $key => $ids) {
      foreach ($ids as $recurId) {
        $processorId = CRM_Utils_Array::value($recurId, $processorByRecur);
        if ($processorId && isset($sets[$processorId])) {
          $sets[$processorId][$key][] = $recurId;
        }
      }
    }

    $processors = [];
    foreach ($sets as $processorId => $ids) {
      $estimate = self::normalizeIds($ids['estimate']);
      $dispatch = self::normalizeIds($ids['dispatch']);
      $attempt = self::normalizeIds($ids['attempt']);
      $remaining = array_values(array_diff($estimate, $attempt));
      $undispatched = array_values(array_diff($estimate, $dispatch));
      $processors[$processorId] = [
        'id' => (int) $processorId,
        'name' => CRM_Utils_Array::value($processorId, $processorNames, ''),
        'estimate' => self::summarizeIds($estimate),
        'dispatch' => self::summarizeIds($dispatch),
        'attempt' => self::summarizeIds($attempt),
        'remaining' => count($remaining),
        'undispatched' => count($undispatched),
        'dispatch_ok' => $ready
          ? !$undispatched && !array_diff($dispatch, $estimate)
          : NULL,
        'attempt_ok' => $ready
          ? !$remaining && !array_diff($attempt, $estimate)
          : NULL,
      ];
    }
    ksort($processors);

    $remaining = array_values(array_diff($estimateIds, $attemptIds));
    $undispatched = array_values(array_diff($estimateIds, $dispatchIds));
    $extraDispatch = array_values(array_diff($dispatchIds, $estimateIds));
    $extraAttempt = array_values(array_diff($attemptIds, $estimateIds));
    $data['estimate'] = self::summarizeIds($estimateIds);
    $data['dispatch'] = self::summarizeIds($dispatchIds);
    $data['attempt'] = self::summarizeIds($attemptIds);
    $data['remaining'] = count($remaining);
    $data['undispatched'] = count($undispatched);
    $data['extra_dispatch'] = count($extraDispatch);
    $data['extra_attempt'] = count($extraAttempt);
    $data['dispatch_ok'] = $ready
      ? !$undispatched && !$extraDispatch
      : NULL;
    $data['attempt_ok'] = $ready
      ? !$remaining && !$extraAttempt
      : NULL;
    $data['processors'] = $processors;
    return $data;
  }

  /**
   * Create or update one gateway's daily dispatch log.
   *
   * @param string $gateway
   * @param int $time
   *
   * @return array
   */
  protected static function updateDailyLog($gateway, $time) {
    $dateId = (int) date('Ymd', $time);
    $log = self::readLog(self::ESTIMATE_PREFIX . $gateway, $dateId);
    if (
      $log === NULL ||
      (int) CRM_Utils_Array::value('version', $log['data'], 0) !==
        self::VERSION ||
      CRM_Utils_Array::value('state', $log['data']) !== 'stable'
    ) {
      return [];
    }

    $cache = self::loadCache($gateway, $dateId, $log['data']);
    if ($cache === NULL) {
      return [];
    }

    $trackedIds = self::normalizeIds(array_merge(
      $cache['ids']['estimate'],
      $cache['ids']['dispatch']
    ));
    $attemptIds = self::findAttempted($trackedIds, $time);
    $attempt = self::summarizeIds($attemptIds);
    if (
      $attemptIds !== $cache['ids']['attempt'] ||
      $attempt['count'] !==
        (int) CRM_Utils_Array::value('count', $log['data']['attempt'], -1) ||
      $attempt['hash'] !==
        CRM_Utils_Array::value('hash', $log['data']['attempt'])
    ) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway stable observation changed; " .
        'daily dispatch was deferred.'
      );
      return [];
    }

    $table = self::DISPATCH_PREFIX . $gateway;
    $existing = self::readLog($table, $dateId);
    $now = date('Y-m-d H:i:s', $time);
    $createdAt = $existing === NULL
      ? $now
      : CRM_Utils_Array::value(
        'created_at',
        $existing['data'],
        CRM_Utils_Array::value('recorded_at', $existing['data'], $now)
      );

    $data = self::buildSummary($log['data'], $cache);
    $data['rule'] = 'same_attempt_ids_as_previous_check';
    $data['correct'] =
      $data['dispatch_ok'] === TRUE && $data['attempt_ok'] === TRUE;
    $data['created_at'] = $createdAt;
    $data['updated_at'] = $now;

    $saved = self::saveLog($table, $dateId, $data, $time);
    if ($saved === NULL) {
      return [];
    }

    CRM_Core_Error::debug_log_message(
      "[AuditContributionRecur] $gateway daily audit $dateId: estimate=" .
      $data['estimate']['count'] .
      ', dispatch=' . $data['dispatch']['count'] .
      ', attempt=' . $data['attempt']['count'] .
      ', remaining=' . $data['remaining']
    );
    return $saved['data'] + ['log_id' => (int) $saved['id']];
  }

  /**
   * Normalize recurring IDs as a sorted unique integer list.
   *
   * @param array $recurIds
   *
   * @return array
   */
  protected static function normalizeIds(array $recurIds) {
    $normalized = [];
    foreach ($recurIds as $recurId) {
      if (is_numeric($recurId) && (int) $recurId > 0) {
        $normalized[(int) $recurId] = (int) $recurId;
      }
    }
    ksort($normalized);
    return array_values($normalized);
  }

  /**
   * Build a compact summary for a recurring ID set.
   *
   * @param array $recurIds
   *
   * @return array
   */
  protected static function summarizeIds(array $recurIds) {
    $recurIds = self::normalizeIds($recurIds);
    $count = count($recurIds);
    return [
      'count' => $count,
      'hash' => hash('sha256', CRM_Utils_Array::implode(',', $recurIds)),
      'first_id' => $count ? $recurIds[0] : NULL,
      'last_id' => $count ? $recurIds[$count - 1] : NULL,
    ];
  }

  /**
   * Load, normalize, and verify one detailed cache payload.
   *
   * @param string $gateway
   * @param int $dateId
   * @param array $data
   *
   * @return array|null
   */
  protected static function loadCache($gateway, $dateId, array $data) {
    $cache = CRM_Contribute_BAO_AuditContributionRecurCache::get(
      $gateway,
      $dateId
    );
    if (!$cache || !is_array(CRM_Utils_Array::value('ids', $cache))) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway detail cache is missing for $dateId."
      );
      return NULL;
    }

    foreach (['estimate', 'dispatch', 'attempt', 'added', 'removed'] as $key) {
      $cache['ids'][$key] = self::normalizeIds(
        CRM_Utils_Array::value($key, $cache['ids'], [])
      );
    }
    $processorByRecur = [];
    foreach (
      CRM_Utils_Array::value('processor_by_recur', $cache, [])
      as $recurId => $processorId
    ) {
      if (
        is_numeric($recurId) &&
        (int) $recurId > 0 &&
        is_numeric($processorId) &&
        (int) $processorId > 0
      ) {
        $processorByRecur[(int) $recurId] = (int) $processorId;
      }
    }
    ksort($processorByRecur);
    $cache['processor_by_recur'] = $processorByRecur;

    $processorNames = [];
    foreach (
      CRM_Utils_Array::value('processor_names', $cache, [])
      as $processorId => $name
    ) {
      if (is_numeric($processorId) && (int) $processorId > 0) {
        $processorNames[(int) $processorId] = (string) $name;
      }
    }
    ksort($processorNames);
    $cache['processor_names'] = $processorNames;
    $cache['checks'] = (int) CRM_Utils_Array::value('checks', $cache, 0);

    $estimate = self::summarizeIds($cache['ids']['estimate']);
    $attempt = self::summarizeIds($cache['ids']['attempt']);
    $loggedEstimate = CRM_Utils_Array::value('estimate', $data, []);
    $loggedAttempt = CRM_Utils_Array::value('attempt', $data, []);
    if (
      (int) CRM_Utils_Array::value('version', $cache, 0) !== self::VERSION ||
      (int) CRM_Utils_Array::value('date_id', $cache, 0) !== (int) $dateId ||
      CRM_Utils_Array::value('gateway', $cache) !== $gateway ||
      $cache['checks'] !== (int) CRM_Utils_Array::value('checks', $data, 0) ||
      $estimate['count'] !==
        (int) CRM_Utils_Array::value('count', $loggedEstimate, -1) ||
      $estimate['hash'] !== CRM_Utils_Array::value('hash', $loggedEstimate) ||
      (
        $cache['checks'] > 0 &&
        (
          $attempt['count'] !==
            (int) CRM_Utils_Array::value('count', $loggedAttempt, -1) ||
          $attempt['hash'] !== CRM_Utils_Array::value('hash', $loggedAttempt)
        )
      )
    ) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] $gateway detail cache does not match log $dateId."
      );
      return NULL;
    }
    return $cache;
  }

  /**
   * Read one JSON audit row from civicrm_log.
   *
   * @param string $table
   * @param int $dateId
   *
   * @return array|null
   */
  protected static function readLog($table, $dateId) {
    $log = CRM_Core_BAO_Log::lastModified($dateId, $table);
    if (empty($log['log_id'])) {
      return NULL;
    }

    $logId = (int) $log['log_id'];
    $data = json_decode((string) $log['data'], TRUE);
    if (!is_array($data)) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] Invalid JSON in audit log $logId."
      );
      $data = [];
    }
    return ['id' => $logId, 'data' => $data];
  }

  /**
   * Insert or update one compact JSON audit row in civicrm_log.
   *
   * @param string $table
   * @param int $dateId
   * @param array $data
   * @param int $time
   *
   * @return array|null
   */
  protected static function saveLog(
    $table,
    $dateId,
    array $data,
    $time
  ) {
    unset($data['log_id'], $data['daily'], $data['daily_log_id']);
    $stripDetails = function (array &$value) use (&$stripDetails) {
      foreach (array_keys($value) as $key) {
        if (
          $key === 'ids' ||
          $key === 'processor_by_recur' ||
          $key === 'processor_names' ||
          substr((string) $key, -4) === '_ids'
        ) {
          unset($value[$key]);
        }
        elseif (is_array($value[$key])) {
          $stripDetails($value[$key]);
        }
      }
    };
    $stripDetails($data);

    $json = json_encode(
      $data,
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($json === FALSE || strlen($json) > self::MAX_LOG_BYTES) {
      CRM_Core_Error::debug_log_message(
        "[AuditContributionRecur] Unable to store compact audit JSON for $table."
      );
      return NULL;
    }

    $params = [
      'entity_table' => $table,
      'entity_id' => $dateId,
      'data' => $json,
      'modified_date' => date('YmdHis', $time),
    ];
    $existing = self::readLog($table, $dateId);
    if ($existing !== NULL) {
      $params['id'] = (int) $existing['id'];
    }

    CRM_Core_BAO_Log::add($params);
    return self::readLog($table, $dateId);
  }

}
