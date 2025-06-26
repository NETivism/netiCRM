<?php
/**
 * Standalone api without extends from class
 */

class CRM_Core_Payment_TapPayAPI {
  CONST TAPPAY_TEST = 'https://sandbox.tappaysdk.com';
  CONST TAPPAY_PROD = 'https://prod.tappaysdk.com';

  public static $_currencies = [
    'USD' => 'USD',
    'JPY' => 'JPY',
    'TWD' => 'TWD',
    'THB' => 'THB',
  ];

  public $_request;
  public $_response;
  public $_success;

  protected $_apiURL;
  protected $_isTest;

  protected $_apiType;
  protected $_partnerKey;

  protected $_apiTypes = [
    'pay_by_prime' => '/tpc/payment/pay-by-prime',
    'pay_by_token' => '/tpc/payment/pay-by-token',
    'record' => '/tpc/transaction/query',
    'trade_history' => '/tpc/transaction/trade-history',
    'card_metadata' => '/tpc/card/metadata',
    'card_notify_api_sandbox' => '/tpc/sandbox/card/metadata/notify',
    'bind_card' => '/tpc/card/bind',
    /* not supportted api types
    'refund' => '/tpc/transaction/refund',
    'cap' => '/tpc/transaction/cap',
    'remove_card' => '/tpc/card/remove',
    'refund_cancel' => '/tpc/transaction/refund/cancel',
    'cap_cancel' => '/tpc/transaction/cap/cancel',
    */
  ];

  protected $_apiNeedSaveData = ['pay_by_prime', 'pay_by_token', 'trade_history', 'bind_card'];

  protected $_contribution_id; // this request relative contribution.

  protected $_apiMethod; // In Tappay , Always Use POST.

  /**
   * $apiParams must has these fields: 
   *   apiType
   *   partnerKey
   *   isTest
   */
  function __construct($apiParams){
    extract($apiParams);
    if (is_null($partnerKey) || empty($apiType)) {
      CRM_Core_Error::fatal('Required parameters missing: $partnerKey, $apiType');
    }
    foreach($apiParams as $name => $val) {
      $name = '_'.$name;
      $this->$name = $val;
    }
    if (empty($this->_apiTypes[$this->_apiType])) {
      CRM_Core_Error::fatal('API type not supported currently or given wrong type');
    }
    else {
      $this->_apiURL = $apiParams['isTest'] ? self::TAPPAY_TEST : self::TAPPAY_PROD; 
      $this->_apiURL .= $this->_apiTypes[$this->_apiType];
      $this->_apiMethod = 'POST';
    }
  }

  public function request($params) {
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

    $requiredFields = self::fields($this->_apiType, TRUE);
    foreach ($requiredFields as $required) {
      if(empty($post[$required])){
        $missingRequired[] = $required;
      }
    }
    if(!empty($missingRequired)) {
      CRM_Core_Error::fatal('Required parameters missing: '.CRM_Utils_Array::implode(',', $missingRequired));
    }

    // Format of amount
    if(!empty($post['amount'])) {
      if($post['currency'] == 'TWD') {
        $post['amount'] = floor($post['amount']);
      }
      else {
        $post['amount'] = floor($post['amount'] * 100);
      }
    }

    // verify some parameter
    if($this->_apiType == 'pay_by_prime') {
      if(!is_array($post['cardholder'])){
        CRM_Core_Error::fatal('cardholder must be array.');
      }
    }

    if($this->_apiType == 'pay_by_token') {
      if ($post['three_domain_secure']) {
        if (empty($post['result_url'])) {
          CRM_Core_Error::fatal('result_url is required if enable "three_domain_secure" when pay_by_token.');
        }
        if(!is_array($post['result_url'])){
          CRM_Core_Error::fatal('result_url must be array.');
        }
      }
    }

    if ($this->_apiType != 'card_notify_api_sandbox') {
      // prepare contribution_id for record data.
      if (empty($params['contribution_id']) && empty($params['order_number']) && empty($this->_contribution_id)) {
        CRM_Core_Error::fatal('You need to specify contribution_id or order_number.');
      }
      if (empty($this->_contribution_id)) {
        if (!empty($params['contribution_id'])) {
          $this->_contribution_id = $params['contribution_id'];
        }
        else {
          $this->_contribution_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['order_number'], 'id', 'trxn_id');
        }
      }
    }

    $this->_request = $post;
    $result = $this->_curl();
    if ($result['status'] && !empty($this->_response)) {
      if (in_array($this->_apiType, $this->_apiNeedSaveData)) {
        // Record tappay data
        self::saveTapPayData($this->_contribution_id, $this->_response, $this->_apiType);
      }

      // Format of amount
      $response =& $this->_response;
      if(!empty($response->amount) && $response->currency != 'TWD') {
        $response->amount = (float)$response->amount / 100;
      }

      return $this->_response;
    }
    else {
      return FALSE;
    }
  }

  public static function writeRecord($logId, $data = []) {
    $recordType = ['contribution_id', 'url', 'date', 'post_data', 'return_data'];

    $record = new CRM_Contribute_DAO_TapPayLog();
    if(!empty($logId)) {
      $record->id = $logId;
      $record->find(TRUE);
    }

    foreach ($recordType as $key) {
      $record->$key = $data[$key];
    }
    $record->save();
    return $record->id;
  }

  private function _curl() {
    $this->_success = FALSE;
    if (!empty(getenv('CIVICRM_TEST_DSN'))) {
      return  [
        'success' => FALSE,
        'status' => NULL,
        'curlError' => NULL,
      ];
    }
    $ch = curl_init($this->_apiURL);
    $opt = [];

    $opt[CURLOPT_HTTPHEADER] = [
      'Content-Type: application/json',
      'x-api-key: ' . $this->_partnerKey,
    ];
    $opt[CURLOPT_RETURNTRANSFER] = TRUE;
    if($this->_apiMethod == 'POST'){
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_POSTFIELDS] = json_encode($this->_request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    curl_setopt_array($ch, $opt);

    $recordData = [
      'contribution_id' => $this->_contribution_id,
      'url' => $this->_apiTypes[$this->_apiType],
      'date' => date('Y-m-d H:i:s'),
      'post_data' => $opt[CURLOPT_POSTFIELDS],
    ];
    $lodId = self::writeRecord(NULL, $recordData);

    $result = curl_exec($ch);

    $recordData = [
      'return_data' => $result,
    ];
    self::writeRecord($lodId, $recordData);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    if (!empty($errno)) {
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        CRM_Core_Error::debug_log_message("CURL: $err :: $errno");
    }

    if ($result === FALSE) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      $curlError = [$errno => $err];
    }
    else{
      $curlError = [];
    }
    curl_close($ch);
    if (!empty($result)) {
      $response = json_decode($result);
      $this->_response = $response;
      $this->_success = isset($response->status) && $response->status == '0' ? TRUE : FALSE;
    }
    else {
      $this->_response = NULL;
    }
    $return = [
      'success' => $this->_success,
      'status' => $status,
      'curlError' => $curlError,
    ];
    return $return;
  }

  static public function saveTapPayData($contributionId, $response, $apiType = '') {
    $tappay = new CRM_Contribute_DAO_TapPay();
    if($contributionId) {
      $tappay->contribution_id = $contributionId;
      $tappay->find(TRUE);
      $tappay->contribution_recur_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_recur_id');
    }
    if (!empty($tappay->contribution_recur_id)) {
      // clone last contribution tappay data
      $lastTappay = new CRM_Contribute_DAO_TapPay();
      $lastTappay->contribution_recur_id = $tappay->contribution_recur_id;
      $lastTappay->orderBy("contribution_id DESC");
      $lastTappay->find(TRUE);

      if ($lastTappay->contribution_id != $tappay->contribution_id) {
        $tappay->card_token = $lastTappay->card_token;
        $tappay->card_key = $lastTappay->card_key;
        $tappay->expiry_date = $lastTappay->expiry_date;
        $tappay->last_four = $lastTappay->last_four;
        $tappay->bin_code = $lastTappay->bin_code;
      }
      $lastTappay->free();
    }
    $tappay->data = json_encode($response);
    if (!empty($tappay->contribution_recur_id)) {
      if (!empty($response->card_secret->card_token)) {
        $tappay->card_token = $response->card_secret->card_token;
      }
      if (!empty($response->card_secret->card_key)) {
        $tappay->card_key = $response->card_secret->card_key;
      }
      if (!empty($response->card_token)) {
        $tappay->card_token = $response->card_token;
      }
    }
    if (!empty($response->card_info)) {
      if (!empty($response->card_info->last_four)) {
        $tappay->last_four = $response->card_info->last_four;
      }
      if (!empty($response->card_info->bin_code)) {
        $tappay->bin_code = $response->card_info->bin_code;
      }
      if(!empty($response->card_info->expiry_date)){
        $year = substr($response->card_info->expiry_date, 0, 4);
        $month = substr($response->card_info->expiry_date, 4, 2);
        $tappay->expiry_date = date('Y-m-d', strtotime('last day of this month', strtotime($year.'-'.$month.'-01')));
      }
    }
    if($response->rec_trade_id) {
      $tappay->rec_trade_id = $response->rec_trade_id;
    }
    if($response->order_number) {
      $tappay->order_number = $response->order_number;
    }
    if($response->card_info && $response->card_info->token_status) {
      $tappay->token_status = $response->card_info->token_status;
    }
    CRM_Utils_Hook::alterTapPayResponse($response, $tappay, 'TapPay', $apiType);
    $tappay->save();
  }

  /**
   * API query fields
   */
  static public function fields($apiType, $is_required = FALSE) {
    $fields = [];
    switch($apiType){
      case 'pay_by_prime':
        $fields = explode(',', 'prime*,partner_key*,merchant_id*,amount*,currency,order_number,bank_transaction_id,details*,cardholder*,instalment,delay_capture_in_days,remember,three_domain_secure,result_url');
        break;
      case 'pay_by_token':
        $fields = explode(',', 'card_key*,card_token*,partner_key*,merchant_id*,amount*,currency*,order_number,bank_transaction_id,details*,instalment,delay_capture_in_days,three_domain_secure,result_url,fraud_id,card_ccv');
        break;
      case 'record':
        $fields = explode(',', 'partner_key*,records_per_page,page,filters,order_by');
        break;
      case 'trade_history':
        $fields = explode(',', 'partner_key*,rec_trade_id*');
        break;
      case 'card_metadata':
        $fields = explode(',', 'partner_key*,card_key*,card_token*');
        break;
      case 'card_notify_api_sandbox':
        $fields = explode(',', 'partner_key*,card_key*,card_token*,tsp_notify_url');
        break;
      case 'bind_card':
        $fields = explode(',', 'prime*,partner_key*,merchant_id*,merchant_group_id,currency*,three_domain_secure,result_url,cardholder*,cardholder_verify,kyc_verification_merchant_id');
        break;
    }
    foreach ($fields as $key => &$value) {
      if(!strstr($value, '*') && $is_required) {
        unset($fields[$key]);
      }
      else{
        $value = str_replace('*', '', $value);
      }
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

  public static $_errorMessage = [
    "-4" => "Unknown Error", 
    "-3" => "Unknown Error", 
    "-2" => "Unknown Error", 
    "-1" => "Unknown Error", 
    "0" => "Success", 
    "2" => "End of list", 
    "4" => "IP mismatch", 
    "5" => "Wrong JSON format", 

    "11" => "App ID not found", 
    "12" => "App name mismatch", 
    "13" => "Unknown app error", 
    "14" => "App type mismatch", 
    "16" => "App key mismatch", 
    "17" => "App id exist already", 

    "30" => "Device not support", 
    "31" => "iOS SDK Version too old", 
    "32" => "Android SDK version too old", 
    "33" => "SDK version not sent", 

    "41" => "Card wrong format", 
    "42" => "Card not authorized", 
    "43" => "Card unauthorized access", 
    "44" => "Cardnumber error", 
    "45" => "Cardduedate error", 
    "46" => "Cardccv error", 

    "61" => "Merchant not found or removed", 
    "62" => "Repeated Merchant ID", 
    "63" => "Acquirer not found", 
    "64" => "Incomplete merchant info or wrong format", 
    "65" => "Merchant unauthorized", 
    "66" => "Not a line pay merchant", 
    "67" => "This merchant does not support this payment method", 
    "68" => "This merchant does not support refund cancel", 

    "81" => "Unknown partner Error", 
    "82" => "Partner key mismatch", 
    "84" => "Partner unauthorized", 
    "85" => "Partner key existed", 
    "87" => "Partner IP allow list wrong format", 

    "91" => "Transaction Timeout", 

    "121" => "Invalid prime", 
    "122" => "Card encrypt error", 

    "400" => "Can\’t make payment", 
    "401" => "Request Cancel", 
    "402" => "Can not obtain payment data", 

    "421" => "API Gateway time out.", 
    "422" => "Authorization Timeout.", 

    "501" => "Missing arguments : cardholder", 
    "502" => "Missing arguments : merchantid", 
    "503" => "Missing arguments : prime", 
    "504" => "Missing arguments : currency", 
    "505" => "Missing arguments : details", 
    "506" => "Missing arguments : partnerkey", 
    "507" => "Missing arguments : cardholder > phonenumber", 
    "508" => "Missing arguments : cardholder > name", 
    "509" => "Missing arguments : cardholder > email", 
    "510" => "Invalid arguments : amount", 
    "511" => "Invalid arguments : instalment", 
    "512" => "Invalid arguments : authtocapperiodinday", 
    "513" => "Instalment out of range", 
    "514" => "authtocapperiodinday out of range", 
    "515" => "details out of range", 
    "516" => "currency out of range", 
    "517" => "ptradeid out of range", 
    "518" => "orderid out of range", 
    "519" => "cardholder > phonenumber out of range", 
    "520" => "cardholder > name out of range", 
    "521" => "cardholder > email out of range", 
    "522" => "cardholder > addr out of range", 
    "523" => "cardholder > zip out of range", 
    "524" => "cardholder > nationalid out of range", 
    "525" => "Missing arguments : remember", 
    "526" => "Missing arguments : cardkey", 
    "527" => "Missing arguments : cardtoken", 
    "528" => "cardkey out of range", 
    "529" => "cardtoken out of range", 
    "530" => "Invalid arguments : appid", 
    "531" => "Missing arguments : appkey", 
    "532" => "Missing arguments : appname", 
    "533" => "Missing arguments : cardnumber", 
    "534" => "Missing arguments : cardduedate", 

    "535" => "Invalid arguments : recordsperpage", 
    "536" => "Invalid arguments : page", 
    "537" => "Invalid arguments : filters > time > starttime", 
    "538" => "Invalid arguments : filters > time > endtime", 
    "539" => "Invalid arguments : filters > rectradeid", 
    "545" => "Invalid arguments : filters > amount > upperlimit", 
    "546" => "Invalid arguments : filters > amount > lowerlimit", 
    "547" => "Invalid arguments : filters > appid", 
    "548" => "Invalid arguments : filters > merchantid", 
    "549" => "Invalid arguments : filters > recordstatus", 
    "550" => "Invalid arguments : orderby > attribute", 
    "551" => "Invalid arguments : orderby > isdescending", 
    "559" => "recordsperpage out of range", 
    "560" => "page out of range", 
    "561" => "filters > time > starttime out of range", 
    "562" => "filters > time > endtime out of range", 
    "563" => "filters > time > starttime can’t be more than the endtime", 
    "564" => "filters > amount > upperlimit out of range", 
    "565" => "filters > amount > lowerlimit out of range", 
    "566" => "filters > amount > lowerlimit can’t be more than the upperlimit", 
    "567" => "filters > appid out of range", 
    "568" => "order_number out of range", 
    "569" => "bank_transaction_id out of range", 
    "570" => "appkey out of range", 
    "571" => "appname out of range", 
    "572" => "cardnumber out of range", 
    "573" => "cardduedate out of range", 
    "574" => "Invalid arguments : cardccv", 
    "579" => "deviceLatitude out of range", 
    "580" => "deviceLongitude out of range", 
    "581" => "Invalid arguments : devicetype", 
    "582" => "Invalid arguments : sdkversion", 
    "583" => "identifier out of range", 
    "584" => "devicemodel out of range", 
    "585" => "osversion out of range", 
    "586" => "geoloc out of range", 
    "587" => "partnerKey out of range", 
    "588" => "Invalid arguments : delayCaptureInDays", 
    "589" => "Invalid arguments : platformType", 
    "590" => "Missing arguments : androidMerchantId", 
    "591" => "Missing arguments : payTokenData", 
    "592" => "Missing arguments : appleMerchantId", 
    "593" => "merchantId out of range", 
    "594" => "appleMerchantId out of range", 
    "595" => "delayCaptureInDays out of range", 
    "596" => "Missing arguments : recTradeId", 
    "597" => "recTradeId out of range", 
    "598" => "androidMerchantId out of range", 
    "599" => "Missing arguments : pay_token_data > ephemeralPublicKey", 
    "600" => "Missing arguments : pay_token_data > encryptedMessage", 
    "601" => "Missing arguments : pay_token_data > tag", 
    "602" => "pay_token_data > ephemeralPublicKey out of range", 
    "603" => "pay_token_data > encryptedMessage out of range", 
    "604" => "pay_token_data > tag out of range", 
    "605" => "Missing arguments : pay_token_data > data", 
    "606" => "Missing arguments : pay_token_data > version", 
    "607" => "Missing arguments : pay_token_data > signature", 
    "608" => "Missing arguments : pay_token_data > header > ephemeralPublicKey", 
    "609" => "Missing arguments : pay_token_data > header > publicKeyHash", 
    "610" => "Missing arguments : pay_token_data > header > transactionId", 
    "611" => "pay_token_data > data out of range", 
    "612" => "pay_token_data > version out of range", 
    "613" => "pay_token_data > header > ephemeralPublicKey out of range", 
    "614" => "pay_token_data > header > publicKeyHash out of range", 
    "615" => "pay_token_data > header > transactionId out of range", 
    "616" => "Missing arguments : pay_token_data > header", 
    "617" => "fraudId out of range", 
    "618" => "pay_token_data > protocolVersion out of range", 
    "619" => "pay_token_data > signature out of range", 
    "620" => "Missing arguments : card > expiryDate", 
    "621" => "number out of range", 
    "622" => "expiryDate out of range", 
    "623" => "Invalid arguments : ccv", 
    "624" => "Invalid remember request parameter", 
    "625" => "Missing arguments : card > number", 
    "626" => "Invalid arguments : platform_type", 
    "627" => "Invalid arguments : backend_notify_url", 
    "628" => "Invalid arguments : frontend_redirect_url", 
    "629" => "Missing arguments : result_url > backend_notify_url", 
    "630" => "Missing arguments : result_url > frontend_redirect_url", 
    "631" => "Missing arguments : result_url", 
    "632" => "Acquirer doesn’t Support 3D Secure.", 
    "633" => "3D Secure Get Redirect Url Error", 
    "634" => "Missing arguments : pay_token_data > protocolVersion", 
    "635" => "Acquirer doesn’t Support Instalment", 
    "636" => "Acquirer doesn’t Support Redeem", 
    "637" => "Acquirer doesn’t Support Instalment and Redeem concurrently", 
    "638" => "Missing arguments : merchant_app_launch_uri", 
    "639" => "merchant_app_launch_uri out of range", 
    "640" => "Invalid arguments : web_name", 
    "641" => "Invalid arguments : ios_name", 
    "642" => "Invalid arguments : android_name", 
    "643" => "Invalid arguments : additional_data", 
    "644" => "Invalid arguments : line_pay_product_image_url", 
    "645" => "Missing arguments : standardCheckoutToken", 
    "646" => "Missing arguments : masterpassMerchantId", 
    "647" => "MasterpassMerchantId out of range", 
    "648" => "Invalid arguments : standardCheckoutToken", 
    "649" => "Missing arguments : pairingId", 
    "650" => "Missing arguments : precheckoutTransactionId", 
    "651" => "Missing arguments : cardId", 
    "652" => "Invalid arguments : currency", 
    "653" => "Missing arguments : googleMerchantId", 
    "654" => "googleMerchantId out of range", 
    "655" => "Missing arguments : reference_id", 
    "656" => "Missing arguments : samsung_merchant_id", 
    "657" => "samsung_merchant_id out of range", 
    "658" => "Missing arguments : pay_token_data > type", 
    "659" => "pay_token_data > type out of range", 
    "660" => "reference_id out of range", 
    "661" => "Missing arguments : merchant_identifier", 
    "662" => "Missing arguments : private_key_file_path", 
    "663" => "Missing arguments : card > payment_method", 
    "664" => "Invalid arguments : card > payment_method", 
    "665" => "Missing arguments : cryptogram", 
    "666" => "Missing arguments : cryptogram > cavv", 
    "667" => "Invalid arguments : cryptogram > eci", 
    "668" => "expiryDate format error", 
    "669" => "request_id out of range", 
    "670" => "Invalid arguments : refund_id", 
    "671" => "Invalid arguments : bincode", 
    "672" => "Missing arguments : platform_type", 
    "673" => "Invalid arguments : platform_type", 
    "674" => "Missing arguments : issuer", 
    "675" => "Invalid arguments : issuer", 
    "676" => "Missing arguments : funding", 
    "677" => "Invalid arguments : funding", 
    "678" => "Missing arguments : type", 
    "679" => "Invalid arguments : type", 
    "680" => "Missing arguments : level", 
    "681" => "Invalid arguments : level", 
    "682" => "Missing arguments : country", 
    "683" => "Invalid arguments : country", 
    "684" => "Missing arguments : country_code", 
    "685" => "Invalid arguments : country_code", 
    "686" => "filters > cap_time > start_time out of range", 
    "687" => "filters > cap_time > end_time out of range", 
    "688" => "filters > cap_time > start_time can’t be later than end_time", 
    "689" => "Invalid arguments : filters > cap_time > start_time", 
    "690" => "Invalid arguments : filters > cap_time > end_time", 
    "691" => "Acquirer doesn’t Support Non-3D Secure.", 
    "692" => "Missing arguments : bincode", 
    "801" => "BIN Codes server Error", 

    "901" => "Decrypt payment data Error", 
    "902" => "Merchant’s identifier exist already", 
    "903" => "Merchant Error", 
    "904" => "Merchant’s acquirer not found", 
    "905" => "Apple Pay Transaction Not Found", 
    "906" => "Apple Pay Order Id already exist", 
    "907" => "Android Pay Transaction Not Found", 
    "908" => "Android Pay Order Id already exist", 
    "909" => "Inconsistent amount", 
    "910" => "Inconsistent currency", 
    "911" => "Apple Merchant Not Found", 
    "912" => "Android Merchant Not Found", 
    "915" => "System Error, please contact TapPay customer service", 
    "916" => "Signature Verification Not Proceed", 
    "917" => "Signature Verification Error", 
    "918" => "AndroidMerchantId is inconsistent with gatewayMerchantId", 
    "919" => "PaymentMethodToken Expired", 
    "920" => "Message Decryption Not Proceed", 

    "921" => "Line Pay Bank Transaction Id Already Exist", 
    "922" => "Line Pay Get Redirect Url Error", 
    "923" => "Line Pay Transaction Not Found", 
    "924" => "Line Pay Order Cancel", 

    "925" => "Three Domain Secure Order Cancel", 

    "926" => "Acquirer doesn’t support this currency", 

    "929" => "Google Merchant Not Found", 
    "930" => "Samsung Merchant Not Found", 
    "931" => "Samsung Pay Order Id already exist", 
    "932" => "Samsung Pay Transaction Not Found", 

    "934" => "Incorrect status [Transaction has been Captured,could not be canceled]", 
    "935" => "Incorrect status [Transaction does not arrange capture,could not be canceled]", 
    "936" => "Incorrect status [Transaction is capturing,could not be canceled]", 

    "1001" => "Direct Pay Transaction Not Found", 

    "2002" => "Invalid tradeId", 
    "2003" => "Repeated request", 
    "2004" => "Repeated request", 
    "2005" => "Repeated request", 

    "2011" => "Card not found, Invalid token", 
    "2012" => "Card key Error", 
    "2013" => "Card expired", 

    "4002" => "Invalid rectradeid", 
    "4014" => "Repeated refund", 
    "6001" => "Duplicate bank_transaction_id", 

    "10001" => "Transaction Pending", 
    "10002" => "IP mismatch", 
    "10003" => "Wrong Card, Ask For Issuer", 
    "10004" => "Bank Timeout", 
    "10005" => "Bank System Error", 
    "10006" => "Duplicate Transaction", 
    "10007" => "3D Validate Error", 
    "10008" => "Merchant Account Error", 
    "10009" => "Amount Error", 
    "10010" => "Transaction pending capture, can not refund", 
    "10011" => "Refund error. Please try again.", 
    "10012" => "Data format error", 
    "10013" => "Bank transaction id duplicate", 
    "10014" => "Invalid bank transaction id", 
    "10023" => "Bank Error", 
    "10024" => "Authorized transaction cannot be partially refunded", 
    "10025" => "Auth code mismatch error", 
    "10026" => "Bank transaction id mismatch error", 
    "10027" => "Token Service Provider error", 
    "10028" => "Samsung Pay error", 
    "10029" => "CTBC decrypt data error", 
    "10030" => "CTBC decrypt data invalid eci", 
    "10031" => "CTBC decrypt data missing errorCode", 
    "10032" => "Card CTBC decrypt data missing xid", 
    "10033" => "CTBC decrypt data missing cardNumber", 
    "10034" => "CTBC decrypt data missing expiry", 
    "10035" => "CTBC decrypt data missing eci", 
    "10036" => "Card Number mismatch error", 
    "10037" => "Card Exp Date mismatch error", 

    "10039" => "This merchant does not support this card type", 
    "10042" => "Doesn’t support this card’s country code", 
    "10043" => "This card can’t find issuer’s country code", 

    "10050" => "Amount out of range", 
    "10051" => "Refund amount out of range", 

    "11000" => "RecTradeId wrong format", 

    "12000" => "Partner server not set", 
    "12001" => "Partner server unreachable", 
    "12002" => "Partner server returns Error", 

    "13000" => "Query page error", 
    "13001" => "Query time filter error", 
    "13002" => "Query amount filter error", 
    "13003" => "Query ordering attribute error", 

    "21001" => "SAMSUNG PAY ERROR (samsung_pay_err_code)", 
    "21002" => "SAMSUNG PAY ERROR (NETWORK)", 

    "88003" => "Lost Parameter", 
    "88004" => "Parameter Format Error", 
    "88005" => "Device Not Support Apple Pay", 
    "88006" => "No Apple Pay Setup Card.", 
    "88007" => "Input Form Not Set", 

  ];
}
