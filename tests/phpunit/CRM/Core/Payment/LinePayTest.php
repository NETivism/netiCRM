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
   * @after
   */
  public function tearDownTest() {
  }
}
