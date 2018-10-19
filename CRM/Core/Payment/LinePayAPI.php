<?php
/**
 * Standalone api without extends from class
 */
class CRM_Core_Payment_LinePayAPI {
  CONST LINEPAY_TEST = 'https://sandbox-api-pay.line.me';
  CONST LINEPAY_PROD = 'https://api-pay.line.me';

  public static $_currencies = array(
    'USD' => 'USD',
    'JPY' => 'JPY',
    'TWD' => 'TWD',
    'THB' => 'THB',
  );
  public static $_lang = array(
    'zh_TW' => 'zh-Hant',
    'zh_CN' => 'zh-Hans',
    'en_US' => 'en',
    'ja_JP' => 'ja',
    'ko_KR' => 'ko',
    'th_TH' => 'th',
  );
  public static $_errorMessage = array(
    '1101' => 'This user is not a LINE Pay user.',
    '1102' => 'The purchasing user suspended for transaction.',
    '1104' => 'Merchant not found.',
    '1105' => 'This Merchant cannot use LINE Pay.',
    '1106' => 'Header information error',
    '1110' => 'Not available credit card.',
    '1124' => 'Error in Amount (scale).',
    '1133' => 'Invalid oneTimeKey',
    '1141' => 'Account status error.',
    '1142' => 'Insufficient balance remains.',
    '1145' => 'Payment in progress.',
    '1150' => 'Transaction record not found.',
    '1152' => 'Transaction has already been made.',
    '1153' => 'Request amount is different from real amount.',
    '1154' => 'Preapproved payment account not available.',
    '1155' => 'The transaction Id not eligible for Refund.',
    '1159' => 'Omitted request payment information.',
    '1163' => 'Exceeded the expiration for Refund.',
    '1164' => 'Refund limit exceeded.',
    '1165' => 'The transaction has already been refunded.',
    '1169' => 'Payment method and password must be certificated by LINE Pay.',
    '1170' => 'User’s account remains have been changed.',
    '1172' => 'Existing same orderId.',
    '1177' => 'Exceeded max. number of transactions (100) allowed to be retrieved.',
    '1178' => 'Unsupported currency.',
    '1179' => 'Status can not be processed.',
    '1180' => 'Expired the payment date',
    '1183' => 'Payment amount must be greater than 0.',
    '1184' => 'Payment amount exceeds amount requested.',
    '1190' => 'The regKey does not exist.',
    '1193' => 'The regKey expired.',
    '1194' => 'This Merchant cannot use Preapproved Payment.',
    '1197' => 'Already processing payment with regKey',
    '1198' => 'Duplicated the request calling API.',
    '1199' => 'Internal request error.',
    '1280' => 'Temporary error while making a payment with Credit Card',
    '1281' => 'Credit Card Payment Error',
    '1282' => 'Credit Card Authorization Error',
    '1283' => 'The payment has been declined due to suspected fraud.',
    '1284' => 'Payment amount must be greater than 0.',
    '1285' => 'Omitted credit card information',
    '1286' => 'Incorrect credit card payment information',
    '1287' => 'Credit card expiration date has passed.',
    '1288' => 'Credit card has insufficient funds.',
    '1289' => 'Maximum credit card limit exceeded.',
    '1290' => 'One-time payment limit exceeded.',
    '1291' => 'This card has been reported stolen.',
    '1292' => 'This card has been suspended.',
    '1293' => 'Invalid Card Verification Number (CVN)',
    '1294' => 'This card is blacklisted.',
    '1295' => 'Invalid credit card number',
    '1296' => 'Invalid amount',
    '1298' => 'The credit card payment declined.',
    '2101' => 'Parameter error',
    '2102' => 'JSON data format error',
    '9000' => 'Internal error',
  );

  public $_request;
  public $_response;
  public $_success;

  protected $_apiURL;
  protected $_isTest;

  protected $_apiType;
  protected $_channelId;
  protected $_channelSecret;

  protected $_apiTypes = array(
    'query' => '/v2/payments?orderId={orderId}&transactionId={transactionId}',
    'request' => '/v2/payments/request',
    'confirm' => '/v2/payments/{transactionId}/confirm',
    // not supportted api types
    #'refund' => '/v2/payments/{transactionId}/refund',
    #'authorization' => '/v2/payments/authorizations',
    #'capture' => '/v2/payments/authorizations/{transactionId}/capture',
    #'void' => '/v2/payments/authorizations/{transactionId}/void',
    #'recurring/payment' => '/v2/payments/preapprovedPay/{regKey}/payment',
    #'recurring/check' => '/v2/payments/preapprovedPay/{regKey}/check',
    #'recurring/expire' => '/v2/payments/preapprovedPay/{regKey}/expire',
  );

  protected $_apiGetMethodTypes = array('query', 'authorization', 'recurring/check');
  protected $_apiMethod;

  function __construct($apiParams){
    extract($apiParams);
    if (empty($channelId) || empty($channelSecret) || is_null($isTest) || empty($apiType)) {
      CRM_Core_Error::fatal('Required parameters missing: channelId, channelSecret, isTest, apiType');
    }
    foreach($apiParams as $name => $val) {
      $name = '_'.$name;
      $this->$name = $val;
    }
    if (empty($this->_apiTypes[$this->_apiType])) {
      CRM_Core_Error::fatal('API type not supported currently or given wrong type');
    }
    else {
      $this->_apiURL = $isTest ? self::LINEPAY_TEST : self::LINEPAY_PROD; 
      $this->_apiURL .= $this->_apiTypes[$this->_apiType];
      if(in_array($this->_apiType, $this->_apiGetMethodTypes)){
        $this->_apiMethod = 'GET';
      }
      else{
        $this->_apiMethod = 'POST';
      }
    }
  }

  public function request($params) {
    $orderId = $transactionId = NULL;
    $allowedFields = self::fields($this->_apiType);
    $post = array();
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
      global $tsLocale;
      if (empty($post['langCd'])) {
        $post['langCd'] = !empty(self::$_lang[$tsLocale]) ? self::$_lang[$tsLocale] : 'en';
      }
      elseif(!in_array($post['langCd'], self::$_lang)){ // if wrong lang
        $post['langCd'] = !empty(self::$_lang[$tsLocale]) ? self::$_lang[$tsLocale] : 'en';
      }

      if (!in_array($post['currency'], self::$_currencies)) {
        CRM_Core_Error::fatal("Wrong currency specified: {$post['currency']}");
      }

      if (empty($post['orderId'])) {
        CRM_Core_Error::fatal("No contribution trxn_id (linepay orderId) specify");
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

    // change api url base on parameter
    while (preg_match('/{([a-z0-9]*)}/i', $this->_apiURL, $matches)) {
      $search = $matches[0];
      $replace = $params[$matches[1]];
      $newApiURL = str_replace($search, $replace, $this->_apiURL);
      if ($newApiURL == $this->_apiURL) {
        CRM_Core_Error::fatal("Required params '$search' of this API type $this->_apiURL");
        break;
      }
      else {
        $this->_apiURL = $newApiURL;
      }
    }

    $this->_request = $post;
    $this->_curl();
    if (!empty($this->_response)) {
      $record = array(
        'url' => $this->_apiURL,
        'timestamp' => CRM_REQUEST_TIME,
        'request' => $this->_request,
        'response' => $this->_response,
      );
      // api response single record
      if (!empty($this->_response->info) && is_object($this->_response->info)) {
        if (!empty($this->_response->info->transactionId)) {
          $transactionId = $this->_response->info->transactionId;
        }
        $this->writeRecord($orderId, $transactionId, $record);
      }
      // api response multiple records
      elseif (!empty($this->_response->info) && is_array($this->_response->info)) {
        foreach($this->_response->info as $idx => $info) {
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
    $id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_linepay WHERE trxn_id LIKE %1 OR transaction_id LIKE %2", array(
      1 => array($orderId, 'String'),
      2 => array($transactionId, 'String'),
    ));
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
    if (!empty($responseField) && !empty($data)){
      $record->$responseField = $data; 
    }
    $record->save();
    return $record;
  }

  private function _curl() {
    $this->_success = FALSE;
    $ch = curl_init($this->_apiURL);
    $opt = array();

    $opt[CURLOPT_HTTPHEADER] = array(
      'Content-Type: application/json',
      'X-LINE-ChannelId: ' . $this->_channelId, // 10 bytes
      'X-LINE-ChannelSecret: ' . $this->_channelSecret, // 32 bytes
      #'X-LINE-MerchantDeviceType' => '',
    );
    $opt[CURLOPT_RETURNTRANSFER] = TRUE;
    if($this->_apiMethod == 'POST'){
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_POSTFIELDS] = json_encode($this->_request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    curl_setopt_array($ch, $opt);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($result === FALSE) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      $curlError = array($errno => $err);
    }
    else{
      $curlError = array();
    }
    curl_close($ch);
    if (!empty($result)) {
      $response = json_decode($result);
      $this->_response = $response;
      $this->_success = $response->returnCode == '0000' ? TRUE : FALSE;
    }
    else {
      $this->_response = NULL;
    }
    $return = array(
      'success' => $this->_success,
      'status' => $status,
      'curlError' => $curlError,
    );
    return $return;
  }

  /**
   * API query fields
   */
  static public function fields($apiType, $isResponse = FALSE) {
    $fields = array();
    switch($apiType){
      case 'query':
        $fields = explode(',', 'transactionId,orderId');
        break;
      case 'request':
        $fields = explode(',', 'productName,productImageUrl,amount,currency,mid,oneTimeKey,confirmUrl,confirmUrlType,checkConfirmUrlBrowser,cancelUrl,packageName,orderId,deliveryPlacePhone,payType,langCd,capture,extras');
        break;
      case 'confirm':
        $fields = explode(',', 'amount,currency');
        break;
    }
    return $fields;
  }

  static public function errorMessage($code) {
    $code = (string) $code;

    if (!empty(self::$_errorMessage[$code])) {
      return ts(self::$_errorMessage[$code]);
    }
    return FALSE;
  }
}
