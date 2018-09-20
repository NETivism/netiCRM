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

  public $_response;
  public $_success;

  protected $_apiURL;
  protected $_isTest;

  protected $_apiType;
  protected $_channelId;
  protected $_channelSecret;

  protected $_apiTypes = array(
    'query' => '/v2/payments',
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
    }
  }

  public function request($params) {
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
    }

    // change api url base on parameter
    if (preg_match('/{([a-z0-9]*)}/i', $this->_apiURL, $matches)) {
      $search = $matches[0];
      $replace = $params[$matches[1]];
      $newApiURL = str_replace($search, $replace, $this->_apiURL);
      if ($newApiURL == $this->_apiURL) {
        CRM_Core_Error::fatal("Required params '$search' of this API type $this->_apiURL");
      }
      else {
        $this->_apiURL = $newApiURL;
      }
    }
    $this->_curl($post);
    if ($this->_success) {
      // write success record
      return $this->_response;
    }
    else {
      // write error response
      return FALSE;
    }
  }

  private function _curl($data) {
    $this->_success = FALSE;
    $ch = curl_init($this->_apiURL);
    $opt = array();

    $opt[CURLOPT_HTTPHEADER] = array(
      'Content-Type: application/json',
      'X-LINE-ChannelId: ' . $this->_channelId, // 10 bytes
      'X-LINE-ChannelSecret: ' . $this->_channelSecret, // 32 bytes
      #'X-LINE-MerchantDeviceType' => '',
    );
    $opt[CURLOPT_POST] = TRUE;
    $opt[CURLOPT_RETURNTRANSFER] = TRUE;
    $opt[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
    /*
    if ($isResponse) {
      switch($apiType){
        case 'query':
          $fields = explode(',', 'returnCode,returnMessage');
          break;
        case 'request':
          $fields = explode(',', '');
          break;
        case 'confirm':
          $fields = explode(',', '');
          break;
      }
    }
    */
    return $fields;
  }
}
