<?php
/**
 * Standalone api without extends from class
 * @package CiviCRM_PaymentProcessor
 */
class CRM_Core_Payment_LinePayAPI {
  public const LINEPAY_TEST = 'https://sandbox-api-pay.line.me';
  public const LINEPAY_PROD = 'https://api-pay.line.me';

  public static $_currencies = [
    'USD' => 'USD',
    'JPY' => 'JPY',
    'TWD' => 'TWD',
    'THB' => 'THB',
  ];
  // LINE Pay v4 付款等待畫面語言 (options.display.locale)，預設值為 'en'
  public static $_lang = [
    'zh_TW' => 'zh_TW',
    'en_US' => 'en',
    ## LINE Pay API v4 supported
    // 'zh_CN' => 'zh_CN',
    // 'ja_JP' => 'ja',
    // 'ko_KR' => 'ko',
    // 'th_TH' => 'th',
  ];
  public static $_errorMessage = [
    '0110' => 'Customer has completed LINE Pay authentication; payment confirmation is available.',
    '0121' => 'Customer canceled payment or exceeded LINE Pay authentication wait time.',
    '0122' => 'Payment failed.',
    '0123' => 'Payment completed.',
    '1101' => 'This user is not a LINE Pay user.',
    '1102' => 'This user is temporarily unable to use LINE Pay transactions.',
    '1104' => 'Merchant not found. Please confirm the credentials you entered are correct.',
    '1105' => 'This merchant cannot use LINE Pay.',
    '1106' => 'Header information error.',
    '1110' => 'Not available credit card.',
    '1124' => 'Error in amount.',
    '1141' => 'Account status error.',
    '1142' => 'Insufficient balance.',
    '1145' => 'Payment in progress.',
    '1150' => 'Transaction record not found.',
    '1152' => 'Transaction has already been made.',
    '1153' => 'Request amount is different from real amount.',
    '1154' => 'Preapproved payment account not available.',
    '1155' => 'The transaction Id is incorrect.',
    '1159' => 'Omitted request payment information.',
    '1163' => 'Unable to refund. (Exceeded the refundable period.)',
    '1164' => 'Refund limit exceeded.',
    '1165' => 'The transaction has already been refunded.',
    '1169' => 'Payment method and password must be certificated by LINE Pay.',
    '1170' => 'User account balance has been changed.',
    '1172' => 'Existing same orderId.',
    '1177' => 'Exceeded max. number of transactions (100) allowed to be retrieved.',
    '1178' => 'Unsupported currency.',
    '1179' => 'Status can not be processed.',
    '1180' => 'Expired the payment date.',
    '1183' => 'Payment amount must be greater than the minimum amount.',
    '1184' => 'Payment amount must be less than the maximum amount.',
    '1190' => 'The regKey does not exist.',
    '1193' => 'The regKey expired.',
    '1194' => 'This merchant cannot use Preapproved Payment.',
    '1198' => 'Duplicated the request calling API.',
    '1199' => 'Internal request error.',
    '1280' => 'Temporary error while making a payment with credit card.',
    '1281' => 'Credit card payment error.',
    '1282' => 'Credit card authorization error.',
    '1283' => 'The payment has been declined due to suspected fraud.',
    '1284' => 'Credit card payment temporarily suspended.',
    '1285' => 'Omitted credit card information.',
    '1286' => 'Incorrect credit card payment information.',
    '1287' => 'Credit card expiration date has passed.',
    '1288' => 'Credit card has insufficient funds.',
    '1289' => 'Maximum credit card limit exceeded.',
    '1290' => 'One-time payment limit exceeded.',
    '1291' => 'This card has been reported stolen.',
    '1292' => 'This card has been suspended.',
    '1293' => 'Invalid Card Verification Number (CVN).',
    '1294' => 'This card is blacklisted.',
    '1295' => 'Invalid credit card number.',
    '1296' => 'Invalid amount.',
    '1298' => 'The credit card payment declined.',
    '190X' => 'A temporary error occurred. Please try again later.',
    '2042' => 'EPI refund failed due to insufficient merchant reserve.',
    '2101' => 'Parameter error.',
    '2102' => 'JSON data format error.',
    '9000' => 'Internal error.',
  ];

  // Traditional Chinese (zh-Hant) messages from the official v4 result-code table.
  // Keys mirror self::$_errorMessage; used when the system locale is zh_TW.
  public static $_errorMessageZhTW = [
    '0110' => '顧客已完成 LINE Pay 認證，可以進行付款授權。',
    '0121' => '顧客取消付款或超過 LINE Pay 認證等待時間。',
    '0122' => '付款失敗。',
    '0123' => '付款完成。',
    '1101' => '該用戶不是 LINE Pay 用戶。',
    '1102' => '該用戶目前無法使用 LINE Pay 交易。',
    '1104' => '您的商店尚未在合作商店中心註冊成為合作商店。請確認輸入的 credentials 是否正確。',
    '1105' => '該合作商店目前無法使用 LINE Pay。',
    '1106' => '請求標頭訊息有錯誤。',
    '1110' => '該信用卡無法正常使用。',
    '1124' => '金額訊息有誤。',
    '1141' => '帳戶狀態有問題。如為 EPI 交易，商家有可能未開通 EPI 支付方式；如為 Preapproved 交易，有可能用戶已刪除該支付方式，需重新取得 Regkey。',
    '1142' => '餘額不足。',
    '1145' => '付款進行中。',
    '1150' => '無交易歷史。',
    '1152' => '有相同交易歷史。',
    '1153' => '付款請求金額和請款金額不同。',
    '1154' => '無法使用設定為預先授權付款的付款方式。',
    '1155' => '交易 ID 有誤。',
    '1159' => '無付款請求訊息。',
    '1163' => '無法退款。（超過可退款期限）',
    '1164' => '超出可退款金額。',
    '1165' => '已退款的交易。',
    '1169' => '須在 LINE Pay 中選擇付款方式並驗證認證密碼。',
    '1170' => '會員帳戶餘額發生變化。',
    '1172' => '已存在相同訂單號碼的交易記錄。',
    '1177' => '超出可查看的最多交易數量（100 筆）。',
    '1178' => '合作商店不支援該貨幣。',
    '1179' => '無法處理該狀態。',
    '1180' => '已超過付款期限。',
    '1183' => '付款金額必須大於設定的最低金額。',
    '1184' => '付款金額必須小於設定的最高金額。',
    '1190' => '無預先授權付款密鑰。',
    '1193' => '預先授權付款密鑰已逾期。',
    '1194' => '合作商店不支援預先授權付款。',
    '1198' => 'API 呼叫請求重複。',
    '1199' => '內部請求發生錯誤。',
    '1280' => '信用卡付款時發生臨時錯誤。',
    '1281' => '信用卡付款時發生錯誤。',
    '1282' => '信用卡授權時發生錯誤。',
    '1283' => '有不當使用疑慮，付款被拒絕。',
    '1284' => '信用卡付款暫時暫停。',
    '1285' => '信用卡付款訊息缺失。',
    '1286' => '信用卡付款訊息中有錯誤訊息。',
    '1287' => '信用卡已過期。',
    '1288' => '信用卡帳戶餘額不足。',
    '1289' => '超出信用卡額度。',
    '1290' => '超出信用卡單筆付款額度。',
    '1291' => '該卡已被通報失竊。',
    '1292' => '該卡已停用。',
    '1293' => 'CVN 輸入錯誤。',
    '1294' => '該卡已被列入黑名單。',
    '1295' => '信用卡號碼錯誤。',
    '1296' => '無法處理此金額。',
    '1298' => '該卡被拒絕。',
    '190X' => '發生臨時錯誤，請稍後再試一次。',
    '2042' => '由於商家的退款準備金不足，未能為該 EPI 交易進行退款。',
    '2101' => '參數錯誤。',
    '2102' => 'JSON 數據格式錯誤。',
    '9000' => '發生了內部錯誤。',
  ];

  public $_request;
  public $_response;
  public $_success;

  /**
   * Canned API responses consumed by tests (refs #45587).
   *
   * When non-empty, _curl() shifts one entry per request instead of calling
   * the real LINE Pay API. Each entry is a JSON string or a decoded response
   * object. Signature/nonce/body are still computed so tests exercise the
   * exact bytes a real call would send; they are appended to
   * self::$_requestLog for assertions.
   */
  public static $_mockResponseQueue = [];

  /**
   * Requests served from the mock queue, for test assertions.
   * Each entry: url, method, body, nonce, signature.
   */
  public static $_requestLog = [];

  protected $_apiURL;
  protected $_isTest;

  protected $_apiType;
  protected $_channelId;
  protected $_channelSecret;

  public $_curlError;

  protected $_apiTypes = [
    'query' => '/v4/payments',
    'request' => '/v4/payments/request',
    'confirm' => '/v4/payments/{transactionId}/confirm',
    // refs #45587, preapproved payment via regKey
    'recurring/payment' => '/v4/payments/preapprovedPay/{regKey}/payment',
    // refs #45587, discard a preapproved regKey so it can never charge again
    'recurring/expire' => '/v4/payments/preapprovedPay/{regKey}/expire',
    // refs #45587, check whether a preapproved regKey is still valid
    'recurring/check' => '/v4/payments/preapprovedPay/{regKey}/check',
    // not supportted api types
    #'refund' => '/v4/payments/{transactionId}/refund',
    #'check' => '/v4/payments/requests/{transactionId}/check',
    #'capture' => '/v4/payments/authorizations/{transactionId}/capture',
    #'void' => '/v4/payments/authorizations/{transactionId}/void',
  ];

  protected $_apiGetMethodTypes = ['query', 'check', 'recurring/check'];
  protected $_apiPath;
  protected $_queryString;
  protected $_apiMethod;

  /**
   * Class constructor.
   *
   * @param array $apiParams API configuration parameters (channelId, channelSecret, isTest, apiType)
   */
  public function __construct($apiParams) {
    extract($apiParams);
    if (empty($channelId) || empty($channelSecret) || is_null($isTest) || empty($apiType)) {
      throw new CRM_Core_Exception('Required parameters missing: channelId, channelSecret, isTest, apiType');
    }
    foreach ($apiParams as $name => $val) {
      $name = '_'.$name;
      $this->$name = $val;
    }
    if (empty($this->_apiTypes[$this->_apiType])) {
      throw new CRM_Core_Exception('API type not supported currently or given wrong type');
    }
    else {
      $this->_apiURL = $isTest ? self::LINEPAY_TEST : self::LINEPAY_PROD;
      $this->_apiURL .= $this->_apiTypes[$this->_apiType];
      if (in_array($this->_apiType, $this->_apiGetMethodTypes)) {
        $this->_apiMethod = 'GET';
      }
      else {
        $this->_apiMethod = 'POST';
      }
    }
  }

  /**
   * Create an API client from a payment processor configuration.
   *
   * Lets callers build a client for a specific API type at the moment they
   * need it, without reaching into the LINE Pay business object.
   *
   * @param array $paymentProcessor payment processor params (url_site, url_api, is_test)
   * @param string $apiType API type (request, confirm, query)
   *
   * @return CRM_Core_Payment_LinePayAPI
   */
  public static function create($paymentProcessor, $apiType) {
    return new self([
      'channelId' => $paymentProcessor['url_site'],
      'channelSecret' => $paymentProcessor['url_api'],
      'apiType' => $apiType,
      'isTest' => !empty($paymentProcessor['is_test']),
    ]);
  }

  /**
   * Send a request to the LinePay API.
   *
   * @param array $params request parameters
   *
   * @return object|bool API response object or FALSE on failure
   */
  public function request($params) {
    $orderId = $transactionId = NULL;
    $allowedFields = self::fields($this->_apiType);
    $post = [];
    foreach ($params as $name => $value) {
      if (!in_array($name, $allowedFields)) {
        continue;
      }
      else {
        $post[$name] = $value;
      }
    }

    // verify some parameter
    if ($this->_apiType == 'request') {
      if (!in_array($post['currency'], self::$_currencies)) {
        throw new CRM_Core_Exception("Wrong currency specified: {$post['currency']}");
      }

      if (empty($post['orderId'])) {
        throw new CRM_Core_Exception("No contribution trxn_id (linepay orderId) specify");
      }
      else {
        $this->writeRecord($post['orderId']);
      }
    }
    if (!empty($post['orderId'])) {
      $orderId = $post['orderId'];
    }
    if (!empty($post['transactionId'])) {
      $transactionId = $post['transactionId'];
    }

    // change api url base on parameter (path placeholders like {transactionId})
    while (preg_match('/{([a-z0-9]*)}/i', $this->_apiURL, $matches)) {
      $search = $matches[0];
      $replace = $params[$matches[1]] ?? '';
      $newApiURL = str_replace($search, $replace, $this->_apiURL);
      if ($newApiURL == $this->_apiURL) {
        throw new CRM_Core_Exception("Required params '$search' of this API type $this->_apiURL");
        break;
      }
      else {
        $this->_apiURL = $newApiURL;
      }
    }

    // For GET endpoints (v4), build query string from filtered post fields.
    // Strip the host so we can split apiPath / queryString for signature.
    $base = $this->_isTest ? self::LINEPAY_TEST : self::LINEPAY_PROD;
    $pathWithQuery = substr($this->_apiURL, strlen($base));
    if ($this->_apiMethod == 'GET') {
      $queryString = http_build_query($post);
      $this->_apiPath = $pathWithQuery;
      $this->_queryString = $queryString;
      if (!empty($queryString)) {
        $this->_apiURL = $base . $pathWithQuery . '?' . $queryString;
      }
    }
    else {
      $this->_apiPath = $pathWithQuery;
      $this->_queryString = '';
    }

    $this->_request = $post;
    $this->_curl();
    if (!empty($this->_response)) {
      $record = [
        'url' => $this->_apiURL,
        'timestamp' => CRM_REQUEST_TIME,
        'request' => $this->_request,
        'response' => $this->_response,
      ];
      // api response single record
      if (!empty($this->_response->info) && is_object($this->_response->info)) {
        if (!empty($this->_response->info->transactionId)) {
          $transactionId = $this->_response->info->transactionId;
        }
        $this->writeRecord($orderId, $transactionId, $record);
      }
      // api response multiple records
      elseif (!empty($this->_response->info) && is_array($this->_response->info)) {
        foreach ($this->_response->info as $idx => $info) {
          $r = clone $this->_response;
          $r->info = $info;
          if (!empty($info->transactionId)) {
            $record['response'] = $r;
            $this->writeRecord(NULL, $info->transactionId, $record);
          }
          unset($record['response']);
        }
        $this->writeRecord(NULL, NULL, $this->_response);
      }
      // api response doesn't have info
      // use orderId / transactionId from request params
      elseif (empty($this->_response->info)) {
        $this->writeRecord($orderId, $transactionId, $record);
      }
    }
    if ($this->_success) {
      return $this->_response;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Save API request/response data to the database.
   *
   * @param string|null $orderId contribution trxn_id
   * @param string|null $transactionId LinePay transaction ID
   * @param mixed $data data to be recorded
   *
   * @return CRM_Contribute_DAO_LinePay|bool DAO object or FALSE on failure
   */
  public function writeRecord($orderId = NULL, $transactionId = NULL, $data = NULL) {
    if (empty($orderId) && empty($transactionId)) {
      return FALSE;
    }
    $orderId = !empty($orderId) ? $orderId : '';
    $transactionId = !empty($transactionId) ? $transactionId : '';
    if (!empty($data)) {
      $responseField = str_replace('/', '_', $this->_apiType);
      $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    $record = new CRM_Contribute_DAO_LinePay();
    $id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_linepay WHERE trxn_id LIKE %1 OR transaction_id LIKE %2", [
      1 => [$orderId, 'String'],
      2 => [$transactionId, 'String'],
    ]);
    if ($id) {
      $record->id = $id;
      $record->find(TRUE);
    }
    if (!empty($transactionId)) {
      $record->transaction_id = $transactionId;
    }
    if (!empty($orderId)) {
      $record->trxn_id = $orderId;
    }
    if (!empty($responseField) && !empty($data)) {
      $record->$responseField = $data;
    }
    $record->save();
    return $record;
  }

  /**
   * Execute the API request using cURL.
   *
   * @return array<string, bool|int|string[]> [success => bool, status => int, curlError => array]
   */
  private function _curl() {
    $this->_success = FALSE;

    // Build the exact body bytes that will be both signed and POSTed.
    // Bytes must match exactly — re-encoding with different flags would break HMAC.
    // Body-less POSTs (e.g. recurring/expire) sign and send an empty string so
    // we never sign "[]" from an empty array.
    $body = '';
    if ($this->_apiMethod == 'POST' && !empty($this->_request)) {
      $body = json_encode($this->_request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $nonce = self::_generateNonce();
    $signTarget = $this->_apiMethod == 'GET' ? $this->_queryString : $body;
    $signature = self::_signature($this->_channelSecret, $this->_apiPath, $signTarget, $nonce);

    // refs #45587, serve a canned response in tests instead of hitting the
    // real LINE Pay API. Everything up to here (URL, body bytes, signature)
    // is computed exactly as a live call would.
    if (!empty(self::$_mockResponseQueue)) {
      $mock = array_shift(self::$_mockResponseQueue);
      self::$_requestLog[] = [
        'url' => $this->_apiURL,
        'method' => $this->_apiMethod,
        'body' => $body,
        'nonce' => $nonce,
        'signature' => $signature,
      ];
      $this->_response = is_string($mock) ? json_decode($mock) : $mock;
      $this->_success = !empty($this->_response->returnCode) && $this->_response->returnCode == '0000';
      return [
        'success' => $this->_success,
        'status' => 200,
        'curlError' => [],
      ];
    }

    $ch = curl_init($this->_apiURL);
    $opt = [];

    $opt[CURLOPT_HTTPHEADER] = [
      'Content-Type: application/json',
      'X-LINE-ChannelId: ' . $this->_channelId,
      'X-LINE-Authorization-Nonce: ' . $nonce,
      'X-LINE-Authorization: ' . $signature,
    ];
    $opt[CURLOPT_RETURNTRANSFER] = TRUE;
    $opt[CURLOPT_CONNECTTIMEOUT] = 10;
    $opt[CURLOPT_TIMEOUT] = 45;

    if ($this->_apiMethod == 'POST') {
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($ch, $opt);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($result === FALSE) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      $curlError = [$errno => $err];
    }
    else {
      $curlError = [];
    }
    curl_close($ch);
    if (!empty($result)) {
      $response = json_decode($result);
      $this->_response = $response;
      $this->_success = $response->returnCode == '0000' ? TRUE : FALSE;
    }
    else {
      $this->_response = NULL;
      $this->_curlError = $curlError;
    }
    $return = [
      'success' => $this->_success,
      'status' => $status,
      'curlError' => $curlError,
    ];
    return $return;
  }

  /**
   * Get the allowed or required fields for a given API type.
   *
   * @param string $apiType API type
   * @param bool $isResponse whether to return response fields (unused)
   *
   * @return array allowed field names
   */
  public static function fields($apiType, $isResponse = FALSE) {
    $fields = [];
    switch ($apiType) {
      case 'query':
        $fields = explode(',', 'transactionId,orderId');
        break;
      case 'request':
        $fields = explode(',', 'amount,currency,orderId,packages,redirectUrls,options');
        break;
      case 'confirm':
        $fields = explode(',', 'amount,currency');
        break;
        // refs #45587, preapproved payment body (regKey is a path param, not a body field)
      case 'recurring/payment':
        $fields = explode(',', 'amount,currency,orderId,productName');
        break;
    }
    return $fields;
  }

  /**
   * Map a CiviCRM recurring frequency unit to a LINE Pay recurringPeriod.
   *
   * refs #45587. Used by options.regPayRequest.recurringPeriod on the v4
   * preapproved payment request. Only WEEK / MONTH / YEAR are supported.
   *
   * @param string $frequencyUnit CiviCRM frequency unit (week, month, year)
   *
   * @return string|null LINE Pay recurringPeriod or NULL when unsupported
   */
  public static function recurringPeriod($frequencyUnit) {
    $map = [
      'week' => 'WEEK',
      'month' => 'MONTH',
      'year' => 'YEAR',
    ];
    return $map[strtolower((string) $frequencyUnit)] ?? NULL;
  }

  /**
   * Get the error message corresponding to a LinePay error code.
   *
   * The active CiviCRM locale (global $tsLocale) decides the language: zh_TW
   * returns the Traditional Chinese (zh-Hant) messages from the v4 docs, while
   * all other locales fall back to the English messages (kept translatable via
   * ts() so existing .po catalogs still apply).
   *
   * @param string $code error code
   *
   * @return string|false error message or FALSE if not found
   */
  public static function errorMessage($code) {
    $code = (string) $code;
    global $tsLocale;
    $isZhTW = ($tsLocale === 'zh_TW');

    $messages = $isZhTW ? self::$_errorMessageZhTW : self::$_errorMessage;
    $message = NULL;
    if (!empty($messages[$code])) {
      $message = $messages[$code];
    }
    // v4 groups codes 1900-1909 under the wildcard "190X" (temporary error).
    elseif (preg_match('/^190\d$/', $code) && !empty($messages['190X'])) {
      $message = $messages['190X'];
    }
    if ($message === NULL) {
      return FALSE;
    }
    return $isZhTW ? $message : ts($message);
  }

  /**
   * Map the active CiviCRM locale to a LINE Pay display locale.
   *
   * LINE Pay v4 accepts options.display.locale on the payment request to set
   * the language of the payment pages shown to the customer. Supported values
   * are defined in self::$_lang (zh-Hant, zh-Hans, en, ja, ko, th); unsupported
   * locales fall back to 'en'.
   *
   * @return string LINE Pay display locale code (e.g. zh-Hant, en, ja)
   */
  public static function displayLocale() {
    global $tsLocale;
    return self::$_lang[$tsLocale] ?? 'en';
  }

  /**
   * Compute the LINE Pay v4 X-LINE-Authorization signature.
   *
   * Sign string = channelSecret + apiPath + (body or queryString) + nonce.
   * HMAC-SHA256 keyed by channelSecret, returned Base64-encoded.
   *
   * @param string $channelSecret merchant channel secret used as HMAC key
   * @param string $apiPath request path without host (e.g. /v4/payments/request)
   * @param string $payload raw POST body or GET query string (without leading '?')
   * @param string $nonce request nonce sent in X-LINE-Authorization-Nonce header
   *
   * @return string Base64-encoded HMAC-SHA256 signature
   */
  public static function _signature($channelSecret, $apiPath, $payload, $nonce) {
    $signTarget = $channelSecret . $apiPath . $payload . $nonce;
    return base64_encode(hash_hmac('sha256', $signTarget, $channelSecret, TRUE));
  }

  /**
   * Generate a UUID v4 nonce for LINE Pay v4 requests.
   *
   * @return string RFC 4122 v4 UUID
   */
  public static function _generateNonce() {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
  }
}
