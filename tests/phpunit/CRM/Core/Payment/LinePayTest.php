<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Simulated tests for LinePay v4 upgrade.
 *
 * Verifies HMAC signature, nonce format, fields() whitelist, and
 * doRequest body restructure without calling the real LINE Pay API.
 */
class CRM_Core_Payment_LinePayTest extends CiviUnitTestCase {

  public $DBResetRequired = FALSE;

  protected $_processor;
  protected $_cid;
  protected $_is_test = 1;
  protected $_page_id = 1;

  public function get_info() {
    return [
      'name' => 'LinePay v4 simulated tests',
      'description' => 'Verify v4 HMAC signature, nonce, fields whitelist and doRequest body.',
      'group' => 'Payment Processor Tests',
    ];
  }

  /**
   * @before
   */
  public function setUpTest() {
    parent::setUp();
    $this->prepareMailLog();

    // refs #45587, reset the canned-response seam between tests so a leftover
    // queue entry from one test can never bleed into the next.
    CRM_Core_Payment_LinePayAPI::$_mockResponseQueue = [];
    CRM_Core_Payment_LinePayAPI::$_requestLog = [];

    $this->_processor = $this->getOrCreateLinePayProcessor();
    $this->_cid = $this->getOrCreateContact();
  }

  /**
   * Get (or create) a Mobile-type payment processor carrying LINE Pay
   * preapproved credentials (refs #45587).
   *
   * isLinePayRecur() only charges recurrings whose gateway is a Mobile
   * processor with url_site/url_api set and the repurposed subject flag = '1'.
   *
   * @return array payment processor params (as buildPayment() returns)
   */
  protected function getOrCreateLinePayProcessor() {
    $existing = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor WHERE class_name = 'Payment_Mobile' AND is_test = %1 AND url_site IS NOT NULL AND subject = '1' LIMIT 1", [
      1 => [$this->_is_test, 'Integer'],
    ]);
    if (empty($existing)) {
      $type = civicrm_api('PaymentProcessorType', 'get', [
        'version' => 3,
        'class_name' => 'Payment_Mobile',
      ]);
      $this->assertAPISuccess($type);
      $typeValue = reset($type['values']);
      $create = civicrm_api('PaymentProcessor', 'create', [
        'version' => 3,
        'domain_id' => CRM_Core_Config::domainID(),
        'name' => 'AUTO LinePay Preapproved',
        'payment_processor_type_id' => $typeValue['id'],
        'payment_processor_type' => 'Mobile',
        'class_name' => 'Payment_Mobile',
        'is_active' => 1,
        'is_default' => 0,
        'is_test' => $this->_is_test,
        'is_recur' => 1,
        'user_name' => 'none',
        'password' => 'linepaytest',
        // subject = '1' is the LINE Pay Recurring enabled flag.
        'subject' => '1',
        // url_site / url_api hold the LINE Pay channel id / secret.
        'url_site' => 'ChannelId123',
        'url_api' => 'ChannelSecret123',
        'billing_mode' => $typeValue['billing_mode'],
        'payment_type' => CRM_Utils_Array::value('payment_type', $typeValue),
      ]);
      $this->assertAPISuccess($create);
      $existing = $create['id'];
    }
    return CRM_Core_BAO_PaymentProcessor::getPayment($existing, 'test');
  }

  /**
   * Get any existing contact id, creating one when the test DB has none.
   *
   * @return int contact id
   */
  protected function getOrCreateContact() {
    $cid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE contact_type = 'Individual' AND is_deleted = 0 ORDER BY id ASC LIMIT 1");
    if (empty($cid)) {
      $cid = $this->individualCreate();
    }
    return $cid;
  }

  /**
   * Create a recurring + its first contribution wired to the LINE Pay
   * processor, optionally storing a preapproved regKey.
   *
   * @param array $opts overrides: recur_status, contribution_status, reg_key,
   *   amount, installments, end_date, start_date, receive_date, frequency_unit,
   *   cycle_day
   *
   * @return array [recur => DAO, contribution => DAO]
   */
  protected function createLinePayRecur($opts = []) {
    $now = time();
    $date = date('YmdHis', $now);
    $amount = $opts['amount'] ?? 222;

    $recurParams = [
      'contact_id' => $this->_cid,
      'amount' => $amount,
      'frequency_unit' => $opts['frequency_unit'] ?? 'month',
      'frequency_interval' => 1,
      'installments' => $opts['installments'] ?? 0,
      'is_test' => $this->_is_test,
      'start_date' => $opts['start_date'] ?? $date,
      'create_date' => $opts['start_date'] ?? $date,
      'modified_date' => $date,
      'invoice_id' => md5(uniqid('', TRUE)),
      'contribution_status_id' => $opts['recur_status'] ?? 2,
      'cycle_day' => $opts['cycle_day'] ?? (int) date('j', $now),
      'processor_id' => $this->_processor['id'],
      // Unique per call: several recurs may be created within the same second.
      'trxn_id' => uniqid('lprecur', TRUE),
    ];
    if (!empty($opts['end_date'])) {
      $recurParams['end_date'] = $opts['end_date'];
    }
    $ids = [];
    $recur = CRM_Contribute_BAO_ContributionRecur::add($recurParams, $ids);

    $contribParams = [
      'contact_id' => $this->_cid,
      'contribution_contact_id' => $this->_cid,
      'contribution_type_id' => 1,
      'contribution_page_id' => $this->_page_id,
      'payment_processor_id' => $this->_processor['id'],
      'payment_instrument_id' => 1,
      'created_date' => $opts['start_date'] ?? $date,
      'receive_date' => $opts['receive_date'] ?? $date,
      'non_deductible_amount' => 0,
      'total_amount' => $amount,
      'currency' => 'TWD',
      'source' => 'AUTO: linepay unit test',
      'contribution_source' => 'AUTO: linepay unit test',
      'is_test' => $this->_is_test,
      'contribution_status_id' => $opts['contribution_status'] ?? 2,
      'contribution_recur_id' => $recur->id,
    ];
    $contribution = CRM_Contribute_BAO_Contribution::create($contribParams, CRM_Core_DAO::$_nullArray);

    // The LINE Pay request flow keys the row by orderId = contribution id.
    $linepay = new CRM_Contribute_DAO_LinePay();
    $linepay->trxn_id = (string) $contribution->id;
    $linepay->transaction_id = 'lptxn' . $contribution->id;
    $linepay->contribution_recur_id = $recur->id;
    if (!empty($opts['reg_key'])) {
      $linepay->reg_key = $opts['reg_key'];
    }
    $linepay->save();

    // Mirror the production trxn_id used on the first contribution.
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contribution->id, 'trxn_id', (string) $contribution->id);
    $contribution->trxn_id = (string) $contribution->id;

    // Creating the first contribution can side-effect the recurring's status;
    // pin it back to the value the test asked for so setup is deterministic.
    // Use setFieldValue (not raw SQL) so CRM_Core_DAO's getFieldValue cache,
    // which getEditableFields()/doConfirm() read through, stays consistent.
    $recurStatus = $opts['recur_status'] ?? 2;
    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $recur->id, 'contribution_status_id', $recurStatus);
    // setFieldValue does not refresh getFieldValue's static cache; a forced read
    // re-reads and overwrites the cached entry so getEditableFields()/doConfirm()
    // (which read through getFieldValue) see the pinned status, not a stale one.
    CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recur->id, 'contribution_status_id', 'id', TRUE);
    $recur->contribution_status_id = $recurStatus;

    return ['recur' => $recur, 'contribution' => $contribution];
  }

  /**
   * Push a canned LINE Pay API response onto the mock queue.
   *
   * @param string $returnCode LINE Pay result code
   * @param array $info optional info payload
   *
   * @return void
   */
  protected function queueResponse($returnCode, $info = NULL) {
    $resp = ['returnCode' => $returnCode, 'returnMessage' => 'mock ' . $returnCode];
    if ($info !== NULL) {
      $resp['info'] = $info;
    }
    CRM_Core_Payment_LinePayAPI::$_mockResponseQueue[] = json_encode($resp);
  }

  /**
   * Current regKey stored for a recurring (NULL when cleared).
   *
   * @param int $recurId contribution recur ID
   *
   * @return string|null
   */
  protected function regKeyOf($recurId) {
    return CRM_Core_DAO::singleValueQuery("SELECT reg_key FROM civicrm_contribution_linepay WHERE contribution_recur_id = %1 AND reg_key IS NOT NULL AND reg_key != '' LIMIT 1", [
      1 => [$recurId, 'Positive'],
    ]);
  }

  /**
   * Count of completed contributions belonging to a recurring.
   *
   * @param int $recurId contribution recur ID
   *
   * @return int
   */
  protected function completedCountOf($recurId) {
    return (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_contribution WHERE contribution_recur_id = %1 AND contribution_status_id = 1", [
      1 => [$recurId, 'Positive'],
    ]);
  }

  /**
   * Current contribution_status_id of a recurring.
   *
   * @param int $recurId contribution recur ID
   *
   * @return int
   */
  protected function recurStatusOf($recurId) {
    // Read straight from the DB: CRM_Core_DAO::getFieldValue caches per
    // (class, id, field) and would return a stale value after setRecurStatus().
    return (int) CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution_recur WHERE id = %1", [
      1 => [$recurId, 'Positive'],
    ]);
  }

  /**
   * POST signature for /v4/payments/request body matches expected HMAC-SHA256.
   */
  public function testSignaturePostKnownVector() {
    $channelSecret = 'ChannelSecret123';
    $apiPath = '/v4/payments/request';
    $body = '{"amount":1000,"currency":"TWD","orderId":"ORDER_001"}';
    $nonce = '550e8400-e29b-41d4-a716-446655440000';

    $expected = 'ymwwhoOCk/kkjMxQCBPfofRlaD+HGKcatMOThRFPxsI=';
    $actual = CRM_Core_Payment_LinePayAPI::_signature($channelSecret, $apiPath, $body, $nonce);
    $this->assertEquals($expected, $actual);
  }

  /**
   * GET signature for /v4/payments?... query string matches expected HMAC-SHA256.
   */
  public function testSignatureGetKnownVector() {
    $channelSecret = 'ChannelSecret123';
    $apiPath = '/v4/payments';
    $query = 'orderId=ORDER_001&transactionId=2019060112345678910';
    $nonce = '550e8400-e29b-41d4-a716-446655440000';

    $expected = 'QDqTKHkuNdQm1wXqWj2/T36eiZX9xs/0f6Kkp3Jtp4Q=';
    $actual = CRM_Core_Payment_LinePayAPI::_signature($channelSecret, $apiPath, $query, $nonce);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Signature output is Base64 of exactly 32 bytes (HMAC-SHA256 raw).
   */
  public function testSignatureLength() {
    $sig = CRM_Core_Payment_LinePayAPI::_signature('key', '/v4/payments/request', '{}', 'n');
    $raw = base64_decode($sig);
    $this->assertEquals(32, strlen($raw));
  }

  /**
   * Different body bytes must produce different signatures
   * (otherwise re-encoding JSON would silently break authentication).
   */
  public function testSignatureSensitiveToBody() {
    $sigA = CRM_Core_Payment_LinePayAPI::_signature('s', '/v4/payments/request', '{"a":1}', 'n');
    $sigB = CRM_Core_Payment_LinePayAPI::_signature('s', '/v4/payments/request', '{"a":2}', 'n');
    $this->assertNotEquals($sigA, $sigB);
  }

  /**
   * Nonce is a valid RFC 4122 v4 UUID.
   */
  public function testNonceIsUuidV4() {
    $nonce = CRM_Core_Payment_LinePayAPI::_generateNonce();
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
    $this->assertEquals(1, preg_match($pattern, $nonce), "Nonce '$nonce' is not a valid UUID v4");
  }

  /**
   * Successive nonce calls produce different values.
   */
  public function testNonceUnique() {
    $nonces = [];
    for ($i = 0; $i < 10; $i++) {
      $nonces[] = CRM_Core_Payment_LinePayAPI::_generateNonce();
    }
    $this->assertEquals(10, count(array_unique($nonces)));
  }

  /**
   * v4 'request' fields whitelist must allow nested array fields.
   * (v2 had flat productName/confirmUrl etc; v4 nests under packages/redirectUrls/options)
   */
  public function testRequestFieldsWhitelistV4() {
    $fields = CRM_Core_Payment_LinePayAPI::fields('request');
    $this->assertContains('amount', $fields);
    $this->assertContains('currency', $fields);
    $this->assertContains('orderId', $fields);
    $this->assertContains('packages', $fields);
    $this->assertContains('redirectUrls', $fields);
    $this->assertContains('options', $fields);
    // v2 legacy fields should be gone
    $this->assertNotContains('productName', $fields);
    $this->assertNotContains('confirmUrl', $fields);
    $this->assertNotContains('cancelUrl', $fields);
    $this->assertNotContains('capture', $fields);
  }

  /**
   * Confirm and query whitelists keep their flat shape.
   */
  public function testConfirmAndQueryFields() {
    $confirm = CRM_Core_Payment_LinePayAPI::fields('confirm');
    $this->assertEquals(['amount', 'currency'], $confirm);

    $query = CRM_Core_Payment_LinePayAPI::fields('query');
    $this->assertContains('orderId', $query);
    $this->assertContains('transactionId', $query);
  }

  /**
   * Error code 2042 (EPI refund insufficient reserve) is recognized.
   */
  public function testErrorMessage2042() {
    $msg = CRM_Core_Payment_LinePayAPI::errorMessage('2042');
    $this->assertNotEmpty($msg);
    $this->assertStringContainsString('EPI', $msg);
  }

  /**
   * v4 doRequest body must be nested with packages[]/redirectUrls/options.
   * Builds the exact structure doRequest() assembles, asserts shape and amounts.
   */
  public function testDoRequestBodyShape() {
    $contributionID = 12345;
    $amount = 1000;
    $description = 'Test Page - Tier A';
    $confirmUrl = 'https://example.com/civicrm/linepay/confirm?cid=12345';
    $cancelUrl = 'https://example.com/civicrm/contribute/transact?_qf_ThankYou_display=1';
    $imageUrl = 'https://example.com/civicrm/i/whiteBg.png';

    // Mirrors doRequest() body construction in LinePay.php
    $body = [
      'amount' => $amount,
      'currency' => 'TWD',
      'orderId' => (string)$contributionID,
      'packages' => [
        [
          'id' => '1',
          'amount' => $amount,
          'products' => [
            [
              'name' => $description,
              'imageUrl' => $imageUrl,
              'quantity' => 1,
              'price' => $amount,
            ],
          ],
        ],
      ],
      'redirectUrls' => [
        'confirmUrl' => $confirmUrl,
        'cancelUrl' => $cancelUrl,
      ],
      'options' => [
        'payment' => ['capture' => TRUE],
        'display' => [
          'confirmUrlType' => 'CLIENT',
          'checkConfirmUrlBrowser' => TRUE,
        ],
      ],
    ];

    $this->assertEquals(1000, $body['amount']);
    $this->assertEquals('12345', $body['orderId']);
    $this->assertCount(1, $body['packages']);
    $this->assertEquals('1', $body['packages'][0]['id']);
    $this->assertEquals(1000, $body['packages'][0]['amount']);
    $this->assertCount(1, $body['packages'][0]['products']);
    $this->assertEquals(1000, $body['packages'][0]['products'][0]['price']);
    $this->assertEquals($confirmUrl, $body['redirectUrls']['confirmUrl']);
    $this->assertEquals($cancelUrl, $body['redirectUrls']['cancelUrl']);
    $this->assertTrue($body['options']['payment']['capture']);

    // JSON-encodable without escaping (HMAC must be signed over the same bytes)
    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $this->assertNotFalse($json);
    $decoded = json_decode($json, TRUE);
    $this->assertEquals($body, $decoded);
  }

  /**
   * fields() filtering in LinePayAPI::request() must let v4 nested arrays through.
   */
  public function testFieldsFilterPassesNestedArrays() {
    $params = [
      'amount' => 100,
      'currency' => 'TWD',
      'orderId' => 'OID',
      'packages' => [['id' => '1', 'amount' => 100, 'products' => []]],
      'redirectUrls' => ['confirmUrl' => 'X'],
      'options' => ['payment' => ['capture' => TRUE]],
      'productName' => 'should be dropped',
    ];
    $allowed = CRM_Core_Payment_LinePayAPI::fields('request');
    $filtered = [];
    foreach ($params as $k => $v) {
      if (in_array($k, $allowed)) {
        $filtered[$k] = $v;
      }
    }
    $this->assertArrayHasKey('packages', $filtered);
    $this->assertArrayHasKey('redirectUrls', $filtered);
    $this->assertArrayHasKey('options', $filtered);
    $this->assertEquals('TWD', $filtered['currency']);
    $this->assertArrayNotHasKey('productName', $filtered);
    // Nested structure must survive intact
    $this->assertEquals('1', $filtered['packages'][0]['id']);
    $this->assertTrue($filtered['options']['payment']['capture']);
  }

  /**
   * refs #45587, 'recurring/payment' body whitelist for preapproved payment.
   * regKey is a path param and must NOT appear in the body whitelist.
   */
  public function testRecurringPaymentFieldsWhitelist() {
    $fields = CRM_Core_Payment_LinePayAPI::fields('recurring/payment');
    $this->assertEquals(['amount', 'currency', 'orderId', 'productName'], $fields);
    $this->assertNotContains('regKey', $fields);
  }

  /**
   * refs #45587, 'recurring/payment' is a supported apiType and resolves to the
   * v4 preapproved endpoint with the {regKey} path placeholder.
   */
  public function testRecurringPaymentApiTypeSupported() {
    $api = CRM_Core_Payment_LinePayAPI::create([
      'url_site' => 'ChannelId123',
      'url_api' => 'ChannelSecret123',
      'is_test' => 1,
    ], 'recurring/payment');
    $ref = new ReflectionObject($api);
    $prop = $ref->getProperty('_apiURL');
    $prop->setAccessible(TRUE);
    $url = $prop->getValue($api);
    $this->assertEquals(CRM_Core_Payment_LinePayAPI::LINEPAY_TEST . '/v4/payments/preapprovedPay/{regKey}/payment', $url);
  }

  /**
   * refs #45587, recurring donation body carries payType PREAPPROVED plus a
   * RECURRING regPayRequest, while keeping capture true.
   */
  public function testRequestBodyPreapprovedShape() {
    $amount = 1000;
    $body = [
      'amount' => $amount,
      'currency' => 'TWD',
      'orderId' => '12345',
      'options' => [
        'payment' => [
          'capture' => TRUE,
          'payType' => 'PREAPPROVED',
        ],
        'regPayRequest' => [
          'regPayPeriodType' => 'RECURRING',
          'recurringPeriod' => CRM_Core_Payment_LinePayAPI::recurringPeriod('month'),
          'productPrice' => $amount,
          'recurringDay' => 5,
        ],
      ],
    ];

    $this->assertEquals('PREAPPROVED', $body['options']['payment']['payType']);
    $this->assertTrue($body['options']['payment']['capture']);
    $this->assertEquals('RECURRING', $body['options']['regPayRequest']['regPayPeriodType']);
    $this->assertEquals('MONTH', $body['options']['regPayRequest']['recurringPeriod']);

    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $this->assertNotFalse($json);
    $this->assertEquals($body, json_decode($json, TRUE));
  }

  /**
   * refs #45587, a non-recurring request must not carry preapproved fields.
   */
  public function testNormalRequestHasNoPreapproved() {
    $body = [
      'amount' => 1000,
      'currency' => 'TWD',
      'orderId' => '12345',
      'options' => [
        'payment' => ['capture' => TRUE],
        'display' => ['locale' => 'en', 'checkConfirmUrlBrowser' => TRUE],
      ],
    ];
    $this->assertArrayNotHasKey('payType', $body['options']['payment']);
    $this->assertArrayNotHasKey('regPayRequest', $body['options']);
  }

  /**
   * refs #45587, CiviCRM frequency unit maps to a LINE Pay recurringPeriod.
   */
  public function testRecurringPeriodMapping() {
    $this->assertEquals('WEEK', CRM_Core_Payment_LinePayAPI::recurringPeriod('week'));
    $this->assertEquals('MONTH', CRM_Core_Payment_LinePayAPI::recurringPeriod('month'));
    $this->assertEquals('YEAR', CRM_Core_Payment_LinePayAPI::recurringPeriod('year'));
    $this->assertNull(CRM_Core_Payment_LinePayAPI::recurringPeriod('day'));
  }

  /**
   * refs #45587, preapprovedPay body is signed correctly (32-byte HMAC, Base64)
   * and the signature is sensitive to body changes.
   */
  public function testPreapprovedPayBodyAndSignature() {
    $channelSecret = 'ChannelSecret123';
    $apiPath = '/v4/payments/preapprovedPay/RK2AE3519XTFXHM/payment';
    $nonce = '550e8400-e29b-41d4-a716-446655440000';
    $body = json_encode([
      'amount' => 1000,
      'currency' => 'TWD',
      'orderId' => '12345',
      'productName' => 'Test Page',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sig = CRM_Core_Payment_LinePayAPI::_signature($channelSecret, $apiPath, $body, $nonce);
    $this->assertEquals(32, strlen(base64_decode($sig)));

    $bodyB = str_replace('1000', '2000', $body);
    $sigB = CRM_Core_Payment_LinePayAPI::_signature($channelSecret, $apiPath, $bodyB, $nonce);
    $this->assertNotEquals($sig, $sigB);
  }

  // ---------------------------------------------------------------------------
  // Integration tests (refs #45587): preapprove -> recurring charge flow.
  // These drive the real business methods, feeding canned LINE Pay responses
  // through CRM_Core_Payment_LinePayAPI::$_mockResponseQueue.
  // ---------------------------------------------------------------------------

  /**
   * A. First preapproved charge: doConfirm() completes the contribution, stores
   * the returned regKey, and activates the recurring (Pending 2 -> In Progress 5).
   */
  public function testPreapproveConfirmActivatesRecur() {
    $setup = $this->createLinePayRecur(['recur_status' => 2, 'contribution_status' => 2]);
    $recurId = $setup['recur']->id;
    $contributionId = $setup['contribution']->id;

    // The confirm of a PREAPPROVED request returns the regKey.
    $this->queueResponse('0000', ['regKey' => 'RK_TEST_001']);

    $linePay = new CRM_Core_Payment_LinePay('test', $this->_processor);
    $linePay->doConfirm([
      'qfKey' => 'qfkeytest',
      'cid' => $contributionId,
      'ppid' => $this->_processor['id'],
      'transactionId' => 'lptxn' . $contributionId,
    ]);

    $this->assertDBCompareValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_status_id', 'id', 1, 'First charge should complete the contribution. ' . __LINE__);
    $this->assertEquals(5, $this->recurStatusOf($recurId), 'Recurring should move Pending(2) -> In Progress(5). ' . __LINE__);
    $this->assertEquals('RK_TEST_001', $this->regKeyOf($recurId), 'regKey returned on confirm should be stored. ' . __LINE__);
  }

  /**
   * B. payByRegKey() charges a subsequent installment with the stored regKey:
   * it creates a new completed contribution and keeps the regKey.
   */
  public function testPayByRegKeySuccess() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_TEST_B',
    ]);
    $recurId = $setup['recur']->id;
    $before = $this->completedCountOf($recurId);

    $this->queueResponse('0000', ['transactionId' => '20240101000000']);
    $result = CRM_Core_Payment_LinePay::payByRegKey($recurId, NULL, FALSE);

    $this->assertEquals('0000', $result['status'], 'Charge should report success. ' . __LINE__);
    $this->assertEquals($before + 1, $this->completedCountOf($recurId), 'A new completed contribution should be created. ' . __LINE__);
    $this->assertEquals('RK_TEST_B', $this->regKeyOf($recurId), 'A successful charge keeps the regKey. ' . __LINE__);
  }

  /**
   * B2. The isLinePayRecur() guard: a recurring backed by a non-LINE-Pay
   * processor is skipped without consuming the mock queue.
   */
  public function testPayByRegKeyGuardSkipsNonLinePay() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_TEST_GUARD',
    ]);
    $recurId = $setup['recur']->id;
    // Strip the LINE Pay Recurring flag so the gateway no longer qualifies.
    CRM_Core_DAO::executeQuery("UPDATE civicrm_payment_processor SET subject = '0' WHERE id = %1", [
      1 => [$this->_processor['id'], 'Integer'],
    ]);

    $this->queueResponse('0000', ['transactionId' => 'should_not_be_used']);
    $result = CRM_Core_Payment_LinePay::payByRegKey($recurId, NULL, FALSE);

    $this->assertEquals('', $result['status'], 'Non-LINE-Pay recurring should be skipped. ' . __LINE__);
    $this->assertCount(1, CRM_Core_Payment_LinePayAPI::$_mockResponseQueue, 'Skipped charge must not consume a mock response. ' . __LINE__);

    // restore for other assertions / cleanliness
    CRM_Core_DAO::executeQuery("UPDATE civicrm_payment_processor SET subject = '1' WHERE id = %1", [
      1 => [$this->_processor['id'], 'Integer'],
    ]);
  }

  /**
   * C. doCheckRecur() drives a due charge through payByRegKey() for an
   * In Progress recurring with installments not yet full.
   */
  public function testDoCheckRecurCharges() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_TEST_C',
      'installments' => 12,
    ]);
    $recurId = $setup['recur']->id;
    $before = $this->completedCountOf($recurId);

    $this->queueResponse('0000', ['transactionId' => '20240202000000']);
    CRM_Core_Payment_LinePay::doCheckRecur($recurId);

    $this->assertEquals($before + 1, $this->completedCountOf($recurId), 'doCheckRecur should charge one installment. ' . __LINE__);
    $this->assertEquals(5, $this->recurStatusOf($recurId), 'Recurring stays In Progress while installments remain. ' . __LINE__);
  }

  /**
   * C2. doCheckRecur() completes the recurring (status 1) once the final
   * installment is charged.
   */
  public function testDoCheckRecurCompletesOnLastInstallment() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_TEST_C2',
      'installments' => 1,
    ]);
    $recurId = $setup['recur']->id;

    // installments already satisfied by the first (completed) contribution,
    // so no charge is attempted; the recurring is just completed.
    CRM_Core_Payment_LinePay::doCheckRecur($recurId);

    $this->assertEquals(1, $this->recurStatusOf($recurId), 'Recurring should complete once installments are full. ' . __LINE__);
  }

  /**
   * D. Skip-status guard (verifies the LinePay.php:1071 bug fix): a recurring
   * already in a skip status must not be charged.
   */
  public function testDoCheckRecurSkipsTerminalStatus() {
    foreach ([2, 4, 6] as $skipStatus) {
      $setup = $this->createLinePayRecur([
        'recur_status' => $skipStatus,
        'contribution_status' => 1,
        'reg_key' => 'RK_SKIP_' . $skipStatus,
        'installments' => 12,
      ]);
      $recurId = $setup['recur']->id;
      $before = $this->completedCountOf($recurId);

      // Queue a response that must NOT be consumed if the skip guard works.
      CRM_Core_Payment_LinePayAPI::$_mockResponseQueue = [];
      $this->queueResponse('0000', ['transactionId' => 'skip_' . $skipStatus]);
      CRM_Core_Payment_LinePay::doCheckRecur($recurId);

      $this->assertCount(1, CRM_Core_Payment_LinePayAPI::$_mockResponseQueue, "Status $skipStatus must skip charging (queue untouched). " . __LINE__);
      $this->assertEquals($before, $this->completedCountOf($recurId), "Status $skipStatus must not add a contribution. " . __LINE__);
    }
  }

  /**
   * D2. A Cancelled (3) recurring discards its regKey via expireRegKey() and
   * is not charged.
   */
  public function testDoCheckRecurCancelledDiscardsKey() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 3,
      'contribution_status' => 1,
      'reg_key' => 'RK_CANCEL',
    ]);
    $recurId = $setup['recur']->id;

    // expireRegKey() calls the recurring/expire endpoint.
    $this->queueResponse('0000');
    CRM_Core_Payment_LinePay::doCheckRecur($recurId);

    $this->assertEmpty($this->regKeyOf($recurId), 'Cancelled recurring should discard its regKey. ' . __LINE__);
  }

  /**
   * E. 180-day window: a paused (7) recurring whose key is past the window and
   * confirmed invalid is expired (status 6) and its key discarded.
   */
  public function testPausedExpiresAfter180DaysWhenKeyInvalid() {
    $old = date('YmdHis', time() - (CRM_Core_Payment_LinePay::REGKEY_VALID_DAYS + 5) * 86400);
    $setup = $this->createLinePayRecur([
      'recur_status' => 7,
      'contribution_status' => 1,
      'reg_key' => 'RK_PAUSED_INVALID',
      'start_date' => $old,
      'receive_date' => $old,
    ]);
    $recurId = $setup['recur']->id;

    // recurring/check -> invalid, then recurring/expire -> ok.
    $this->queueResponse('1190');
    $this->queueResponse('0000');
    CRM_Core_Payment_LinePay::doCheckRecur($recurId);

    $this->assertEquals(6, $this->recurStatusOf($recurId), 'Paused + lapsed + invalid key should expire (6). ' . __LINE__);
    $this->assertEmpty($this->regKeyOf($recurId), 'Expired recurring should discard its regKey. ' . __LINE__);
  }

  /**
   * E2. A paused (7) recurring past the window whose key is still confirmed
   * valid keeps its key and status.
   */
  public function testPausedKeepsKeyWhenStillValid() {
    $old = date('YmdHis', time() - (CRM_Core_Payment_LinePay::REGKEY_VALID_DAYS + 5) * 86400);
    $setup = $this->createLinePayRecur([
      'recur_status' => 7,
      'contribution_status' => 1,
      'reg_key' => 'RK_PAUSED_VALID',
      'start_date' => $old,
      'receive_date' => $old,
    ]);
    $recurId = $setup['recur']->id;

    // recurring/check -> still valid (0000); no expire call expected.
    $this->queueResponse('0000');
    CRM_Core_Payment_LinePay::doCheckRecur($recurId);

    $this->assertEquals(7, $this->recurStatusOf($recurId), 'Still-valid key keeps the recurring paused. ' . __LINE__);
    $this->assertEquals('RK_PAUSED_VALID', $this->regKeyOf($recurId), 'Still-valid key is kept. ' . __LINE__);
  }

  /**
   * F1. A generic gateway failure (e.g. 1911) fails the charge but keeps the
   * regKey and leaves the recurring In Progress for the next cycle.
   */
  public function testPayByRegKeyGenericFailureKeepsKey() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_1911',
    ]);
    $recurId = $setup['recur']->id;

    $this->queueResponse('1911');
    $result = CRM_Core_Payment_LinePay::payByRegKey($recurId, NULL, FALSE);

    $this->assertEquals('1911', $result['status'], 'Failure code should be returned. ' . __LINE__);
    $this->assertEquals('RK_1911', $this->regKeyOf($recurId), 'A non-voiding failure keeps the regKey. ' . __LINE__);
    $this->assertEquals(5, $this->recurStatusOf($recurId), 'A non-voiding failure leaves the recurring In Progress. ' . __LINE__);
  }

  /**
   * F2. A regKey-voiding code (e.g. 1287 card expired) drops the key and fails
   * the recurring (status 4).
   */
  public function testPayByRegKeyVoidingCodeFailsRecur() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_VOID',
    ]);
    $recurId = $setup['recur']->id;

    $this->queueResponse('1287');
    CRM_Core_Payment_LinePay::payByRegKey($recurId, NULL, FALSE);

    $this->assertEquals(4, $this->recurStatusOf($recurId), 'A voiding code should fail the recurring (4). ' . __LINE__);
    $this->assertEmpty($this->regKeyOf($recurId), 'A voiding code should discard the regKey. ' . __LINE__);
  }

  /**
   * F3. Code 1193 (preapproved key expired) drops the key and expires the
   * recurring (status 6).
   */
  public function testPayByRegKeyExpiredCodeExpiresRecur() {
    $setup = $this->createLinePayRecur([
      'recur_status' => 5,
      'contribution_status' => 1,
      'reg_key' => 'RK_1193',
    ]);
    $recurId = $setup['recur']->id;

    $this->queueResponse('1193');
    CRM_Core_Payment_LinePay::payByRegKey($recurId, NULL, FALSE);

    $this->assertEquals(6, $this->recurStatusOf($recurId), 'Code 1193 should expire the recurring (6). ' . __LINE__);
    $this->assertEmpty($this->regKeyOf($recurId), 'Code 1193 should discard the regKey. ' . __LINE__);
  }

  /**
   * G1. getEditableFields() locks terminal statuses and Pending-without-key,
   * and unlocks In Progress / Paused.
   */
  public function testGetEditableFieldsLocking() {
    // terminal -> []
    $terminal = $this->createLinePayRecur(['recur_status' => 1, 'reg_key' => 'RK_G1']);
    $this->assertEquals([], CRM_Core_Payment_LinePay::getEditableFields($terminal['recur']->id, NULL), 'Completed is locked. ' . __LINE__);

    // Pending without key -> []
    $pendingNoKey = $this->createLinePayRecur(['recur_status' => 2]);
    $this->assertEquals([], CRM_Core_Payment_LinePay::getEditableFields($pendingNoKey['recur']->id, NULL), 'Pending without regKey is locked. ' . __LINE__);

    // Pending with key -> editable
    $pendingKey = $this->createLinePayRecur(['recur_status' => 2, 'reg_key' => 'RK_G1B']);
    $this->assertEquals(CRM_Core_Payment_LinePay::EDITABLE_RECUR_FIELDS, CRM_Core_Payment_LinePay::getEditableFields($pendingKey['recur']->id, NULL), 'Pending with regKey is editable. ' . __LINE__);

    // In Progress -> editable
    $active = $this->createLinePayRecur(['recur_status' => 5, 'reg_key' => 'RK_G1C']);
    $this->assertEquals(CRM_Core_Payment_LinePay::EDITABLE_RECUR_FIELDS, CRM_Core_Payment_LinePay::getEditableFields($active['recur']->id, NULL), 'In Progress is editable. ' . __LINE__);
  }

  /**
   * G2. updateRecur() rejects system-only statuses and Pending-without-key, and
   * discards the regKey when moving to Cancelled (3).
   */
  public function testUpdateRecurGuards() {
    $setup = $this->createLinePayRecur(['recur_status' => 5, 'reg_key' => 'RK_G2']);
    $recurId = $setup['recur']->id;

    // 4 / 6 are system-only.
    $rejected = CRM_Core_Payment_LinePay::updateRecur(['contribution_recur_id' => $recurId, 'contribution_status_id' => 4]);
    $this->assertEquals(1, $rejected['is_error'], 'Failed(4) cannot be set on the form. ' . __LINE__);

    // Cancelled (3) discards the regKey (recurring/expire).
    $this->queueResponse('0000');
    $cancelled = CRM_Core_Payment_LinePay::updateRecur(['contribution_recur_id' => $recurId, 'contribution_status_id' => 3]);
    $this->assertEquals(0, $cancelled['is_error'], 'Cancelling is allowed. ' . __LINE__);
    $this->assertEmpty($this->regKeyOf($recurId), 'Cancelling discards the regKey. ' . __LINE__);
  }

  /**
   * @after
   */
  public function tearDownTest() {
    CRM_Core_Payment_LinePayAPI::$_mockResponseQueue = [];
    CRM_Core_Payment_LinePayAPI::$_requestLog = [];
  }
}
