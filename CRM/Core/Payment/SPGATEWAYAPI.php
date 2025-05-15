<?php
class CRM_Core_Payment_SPGATEWAYAPI {

  public $_urlDomain;

  // Used for request parameter
  public $_isTest;
  public $_paymentProcessorId;
  public $_paymentProcessor;
  public $_apiType;
  public $_apiMethod = 'POST'; // In This API , Always Use POST.

  public $_contribution_id; // this request relative contribution.
  public $_logId;

  public static $_apiTypes = array(
    'alter-status' => '/MPG/period/AlterStatus',
    'alter-amt' => '/MPG/period/AlterAmt',
  );
  public static $_alterStatus = array(
    'suspend',    // Paused
    'terminate',  // Stop
    'restart',    // Only used in paused recur.
  );

  // Used for request result
  public $_apiURL;
  public $_request;
  public $_response;
  public $_success;
  public $_curlResult;

  /**
   * Constructor
   *
   * @param array $apiParams
   *   apiType
   *   paymentProcessorId
   *   paymentProcessor
   *   isTest
   */
  function __construct($apiParams) {
    if (!empty($apiParams['paymentProcessor'])) {
      $this->_paymentProcessor = $apiParams['paymentProcessor'];
    }
    else if (!empty($apiParams['paymentProcessorId'])) {
      $mode = $apiParams['isTest'] ? '' : 'test';
      $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($apiParams['paymentProcessorId'], $mode);
    }
    else {
      CRM_Core_Error::fatal('Missing payment processor or payment processor ID');
    }
    if ($apiParams['isTest']) {
      $this->_urlDomain = CRM_Core_Payment_SPGATEWAY::TEST_DOMAIN;
    }
    else {
      $this->_urlDomain = CRM_Core_Payment_SPGATEWAY::REAL_DOMAIN;
    }
    $this->_isTest = $apiParams['isTest'];
    $this->_paymentProcessorId = $this->_paymentProcessor['id'];
    $this->_apiType = $apiParams['apiType'];
  }

  /**
   * Validate and send request to spgateway / neweb
   *
   * @param array $params
   * @return void
   */
  public function request($params) {
    $allowedFields = self::getRequestFields($this->_apiType);
    $post = array();
    foreach ($params as $name => $value) {
      if (!in_array($name, $allowedFields)) {
        continue;
      }
      else {
        $post[$name] = $value;
      }
    }
    if (empty($post['RespondType'])) {
      $post['RespondType'] = CRM_Core_Payment_SPGATEWAY::RESPONSE_TYPE;
    }
    if (empty($post['Version'])) {
      $post['Version'] = CRM_Core_Payment_SPGATEWAY::RECUR_VERSION;
    }
    if (empty($post['TimeStamp'])) {
      $post['TimeStamp'] = time();
    }

    $requiredFields = self::getRequestFields($this->_apiType, TRUE);
    foreach ($requiredFields as $required) {
      if(empty($post[$required])){
        $missingRequired[] = $required;
      }
    }
    if(!empty($missingRequired)) {
      CRM_Core_Error::fatal('Required parameters missing: '.implode(',', $missingRequired));
    }

    if (!empty($post['PeriodType']) xor !empty($post['PeriodPoint'])) {
      CRM_Core_Error::fatal('PeriodType and PeriodPoint must exist at same time.');
    }
    if (!empty($post['PeriodType'])) {
      if ($post['PeriodType'] == 'Y' && !preg_match('/\d{4}/', $post['PeriodPoint'])) {
        CRM_Core_Error::fatal('PeriodPoint format should be MMDD when PeriodType is "Y".');
      }
      if ($post['PeriodType'] == 'M' && !preg_match('/\d{2}/', $post['PeriodPoint'])) {
        CRM_Core_Error::fatal('PeriodPoint format should be DD when PeriodType is "M".');
      }
    }

    $this->_request = $post;
    $this->_apiURL = $this->_urlDomain.self::$_apiTypes[$this->_apiType];
    $result = $this->_curlResult = $this->_request();
    CRM_Core_Error::debug('Spgateway_RecurChangeAPI_request', $this->_request);
    if ($result['status'] && !empty($this->_response)) {

      CRM_Core_Error::debug('Spgateway_RecurChangeAPI_response', $this->_response);
      // Curl Correct and have response

      if ($this->_response->Status == 'SUCCESS') {
        // Format Every thing.
        // Format of amount
        $response =& $this->_response;
        if(!empty($response->amount) && $response->currency != 'TWD') {
          $response->amount = (float)$response->amount / 100;
        }

        $apiResult = $this->_response->Result;

        // For AlterType API
        $resultType = $apiResult->AlterType;
        if (!empty($resultType)) {
          $statusReverseMap = array_flip(CRM_Core_Payment_SPGATEWAY::$_statusMap);
          $recurResult['contribution_status_id'] = $statusReverseMap[$resultType];
        }
        if (!empty($result['NewNextTime'])) {
          $recurResult['next_sched_contribution'] = $result['NewNextTime'];
        }

        // For AlterOther API
        if (!empty($apiResult->PeriodType)) {
          $unitReverseMap = array_flip(CRM_Core_Payment_SPGATEWAY::$_unitMap);
          $recurResult['frequency_unit'] = $unitReverseMap[$apiResult->PeriodType];
        }
        $result['cycle_day'] = $apiResult->PeriodPoint;
        if (!empty($apiResult->NewNextAmt) && $apiResult->NewNextAmt != '-') {
          $recurResult['amount'] = $apiResult->NewNextAmt;
        }
        if (!empty($apiResult->NewNextTime)) {
          $recurResult['next_sched_contribution'] = $apiResult->NewNextTime;
        }
        if (!empty($apiResult->PeriodTimes)) {
          $recurResult['installments'] = $apiResult->PeriodTimes;
        }

        return $recurResult;
      }
      else if ( in_array($this->_response->Status, array('PER10061', 'PER10063', 'PER10062', 'PER10064')) ) {
        // Refs #30842, Status is already changed in NewebPay.
        $recurResult['response_status'] = $this->_response->Status;
        $recurResult['msg'] = $recurResult['note_body'] = ts('NewebPay response:') . $this->_response->Message;
        return $recurResult;
      }
      else {
        $errResult['is_error'] = 1;
        $errResult['msg'] = $this->_response->Status.': '.$this->_response->Message;
        CRM_Core_Error::debug('Spgateway_RecurChangeAPI_errResult', $errResult);
        return $errResult;
      }

    }
    else if ($result['success'] == FALSE && $result['status'] == 0){
      // Curl Error
      $return = array(
        'is_error' => 1,
        'msg' => reset($result['curlError']),
      );
      return $return;
    }
    else if (empty($this->_response)) {
      // No any response, need to ask Newebpay
      CRM_Core_Error::debug('NewebPay api request as empty response', $this);
      $return = array(
        'is_error' => 1,
        'msg' => ts('The response from payment provider is empty.'),
      );
      return $return;
    }
    else {
      CRM_Core_Error::debug('NewebPay api request else', $this);
      return FALSE;
    }
  }

  private function _request() {
    $this->_success = FALSE;
    if (!empty(getenv('CIVICRM_TEST_DSN'))) {
      return  array(
        'success' => FALSE,
        'status' => NULL,
        'curlError' => NULL,
      );
    }
    $ch = curl_init($this->_apiURL);
    $opt = array();
    $opt[CURLOPT_RETURNTRANSFER] = TRUE;
    $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
    if($this->_apiMethod == 'POST'){
      $requestString = http_build_query($this->_request, '', '&');
      $postDataString = self::recurEncrypt($requestString, $this->_paymentProcessor);
      $postFields = array(
        'MerchantID_' => $this->_paymentProcessor['user_name'],
        'PostData_' => $postDataString,
      );
      $opt[CURLOPT_POST] = TRUE;
      $opt[CURLOPT_POSTFIELDS] = $postFields;
    }
    curl_setopt_array($ch, $opt);

    $result = curl_exec($ch);

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
      $curlError = array($errno => $err);
    }
    else{
      $curlError = array();
    }
    curl_close($ch);
    if (!empty($result)) {
      $response = json_decode($result);
      $this->_response = json_decode(self::recurDecrypt($response->period, $this->_paymentProcessor));
      $this->_success = isset($response->status) && $response->status == '0' ? TRUE : FALSE;
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
   * Migrate from civicrm_spgateway_record
   *
   * @param int $cid
   * @param array $data
   * @return void
   */
  public static function writeRecord($cid, $data = null){
    if(is_numeric($cid)){
      if(empty($data) && !empty($_POST)){
        $data = $_POST;
      }

      if(!empty($data['JSONData'])){
        $data = $data['JSONData'];
      }
      $exists = CRM_Core_DAO::singleValueQuery("SELECT cid FROM civicrm_contribution_spgateway WHERE cid = %1", array(
        1 => array($cid, 'Integer'),
      ));
      if (!is_string($data)) {
        $data = json_encode($data);
      }
      $columns = array(
        'data' => $data,
      );
      if (is_string($data)) {
        $checkData = json_decode($data);
        if (!empty($checkData)) {
          if (isset($checkData->Result) && isset($checkData->Result->TokenValue)) {
            $columns['trade_no'] = $checkData->Result->TradeNo;
            $columns['token_value'] = $checkData->Result->TokenValue;
            $columns['token_life'] = $checkData->Result->TokenLife;
            $columns['last_four'] = $checkData->Result->Card4No;
            $columns['expiry_date'] = $checkData->Result->TokenLife;
          }
        }
      }

      $sqlParams = array();
      $counter = 1;
      if ($exists) {

        $updateFields = array();
        foreach ($columns as $field => $value) {
          $updateFields[] = "$field = %$counter";
          $sqlParams[$counter] = array($value, 'String');
          $counter++;
        }
        $sqlParams[$counter] = array($cid, 'Integer');
        $sql = "UPDATE civicrm_contribution_spgateway SET " . implode(', ', $updateFields) . " WHERE cid = %$counter";

        CRM_Core_DAO::executeQuery($sql, $sqlParams);
      }
      else {
        $fields = array_keys($columns);
        $values = array();

        foreach ($columns as $value) {
          $values[] = "%$counter";
          $sqlParams[$counter] = array($value, 'String');
          $counter++;
        }

        $fields[] = 'cid';
        $values[] = "%$counter";
        $sqlParams[$counter] = array($cid, 'Integer');

        $sql = "INSERT INTO civicrm_contribution_spgateway (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        CRM_Core_DAO::executeQuery($sql, $sqlParams);
      }

      // Set expire time
      $dataObj = self::dataDecode($data);
      if(!empty($dataObj['ExpireDate'])){
        $expire_date = $dataObj['ExpireDate'];
        if(!empty($dataObj['ExpireTime'])){
          $expire_date .= ' '.$dataObj['ExpireTime'];
        }
        else{
          $expire_date .= ' 23:59:59';
        }
      }
      if(!empty($expire_date)){
        $sql = "UPDATE civicrm_contribution SET expire_date = %1 WHERE id = %2";
        $params = array(
          1 => array( $expire_date, 'String'),
          2 => array( $cid, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }
  }

  /**
   * Migrate from _civicrm_spgateway_post_decode
   *
   * Decode JSON data before save from Spgateway / neweb response
   *
   * @param array|object $post
   * @return void
   */
  public static function dataDecode($post = null){
    $data = empty($post) ? $_POST : $post;
    if (is_object($data)) {
      $data = (array) $data;
    }
    if (is_array($data) && !empty($data['JSONData'])){
      // decode JSONData
      $data = $data['JSONData'];
    }
    if (is_string($data) && json_decode($data)){
      $data = json_decode($data, TRUE);
      if (is_string($data) && json_decode($data)) {
        // Sometimes, neweb will return 2 times encode json.
        $data = json_decode($data, TRUE);
      }
    }

    $return = $data;

    // flatten the jsonData object to 1-dimension array.
    if(isset($data['Result'])){
      if(is_string($data['Result']) && json_decode($data['Result'], true)){
        $return = $dataResult = json_decode($data['Result'], true);
      }
      else {
        $return = $dataResult = (array) $data['Result'];
      }
      if (!empty($data['Status'])) {
        if (empty($return['Status'])) {
          $return['Status'] = $data['Status'];
          // status is in origin data, not in 'Result' object.
        }
        else {
          $return['_RequestStatus'] = $data['Status'];
          // The condition jsonData status is success, but the error status is in 'Result' attribute.
        }
      }
      if (empty($dataResult['Message']) && !empty($data['Message'])) {
        // 'Result' has no 'Message', use original 'Message'.
        $return['Message'] = $data['Message'];
      }
    }
    return $return;
  }

  /**
   * Prepare available fields for spgateway
   *
   * @param string $apiType
   * @param bool $required
   * @return array
   */
  public static function getRequestFields($apiType, $required = FALSE) {
    $fields = array();
    switch($apiType){
      case 'alter-status':
        $fields = explode(',', 'RespondType*,Version*,MerOrderNo*,PeriodNo*,AlterType*,TimeStamp*');
        break;
      case 'alter-amt':
        $fields = explode(',', 'RespondType*,Version*,TimeStamp*,MerOrderNo*,PeriodNo*,AlterAmt,PeriodType,PeriodPoint,PeriodTimes,Extday');
        break;
    }
    foreach ($fields as $key => &$value) {
      if(!strstr($value, '*') && $required) {
        unset($fields[$key]);
      }
      else{
        $value = str_replace('*', '', $value);
      }
    }
    return $fields;
  }

  /**
   * Migrate from _civicrm_spgateway_postdata
   *
   * @param string $url
   * @param array $post_data
   * @return object
   */
  public static function sendRequest($url, $post_data){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
    $response = curl_exec($ch);
    if(curl_errno($ch)){
      CRM_Core_Error::debug_log_message('civicrm_spgateway: Fetch recuring error: curl_errno: '.curl_errno($ch).' / '. curl_error($ch), 'error');
    }
    else{
      $field_string = http_build_query($post_data, '', '&');
      CRM_Core_Error::debug_log_message('civicrm_spgateway: Request:'.$url."?".$field_string);
      CRM_Core_Error::debug_log_message('civicrm_spgateway: Response:'.$response);
    }
    curl_close($ch);
    if(!empty($response)){
      return json_decode($response);
    }
    else{
      return FALSE;
    }
  }

  /**
   * Migrate from _civicrm_spgateway_encode
   *
   * Encode spgateway / neweb API delivery
   *
   * @param array $args
   * @param object $payment_processor
   * @param array $checkArgs
   * @return string
   */
  public static function encode(&$args, $payment_processor, $checkArgs = array()){
    // remove empty arg
    if(is_array($args)){
      foreach($args as $k => $v){
        if($k == 'CheckValue'){
          unset($args[$k]);
        }
      }
    }
    elseif(is_string($args)){
      $tmp = explode('&', $args);
      $args = array();
      foreach($tmp as $v){
        list($key, $value) = explode('=', $v);
        $args[$key] = $value;
      }
    }
    if(count($checkArgs) == 0){
      $checkArgs = array('HashKey','Amt','MerchantID','MerchantOrderNo','TimeStamp','Version','HashIV');
    }
    foreach($checkArgs as $k){
      switch ($k) {
        case 'HashIV':
        case 'IV':
          $v = $payment_processor['signature'];
          self::checkKeyIV($v);
          break;
        case 'HashKey':
        case 'Key':
          $v = $payment_processor['password'];
          self::checkKeyIV($v);
          break;
        default:
          $v = $args[$k];
          break;
      }
      $a[] = $k.'='.$v;
    }
    $keystr = implode('&', $a);

    $checkvalue = strtoupper(hash("sha256", $keystr));
    $args['CheckValue'] = $checkvalue;
    return $checkvalue;
  }

  /**
   * Migrate from _civicrm_spgateway_checkKeyIV
   */
  public static function checkKeyIV($v){
    if(empty($v)){
      CRM_Core_Error::fatal(ts('KEY and IV should have value.'));
    }
  }

  /**
   * Migrate from _civicrm_spgateway_checkmacvalue
   *
   * @param array $args
   * @param object $payment_processor
   * @return string
   */
  public static function checkMacValue(&$args, $payment_processor){
    $used_args = array('HashKey','Amt','MerchantID','MerchantOrderNo','TimeStamp','Version','HashIV');
    return self::encode($args, $payment_processor, $used_args);
  }

  /**
   * Migrate from _civicrm_spgateway_checkcode
   *
   * @param array $args
   * @param object $payment_processor
   * @return string
   */
  public static function checkCode(&$args, $payment_processor){
    $used_args = array('HashIV','Amt','MerchantID','MerchantOrderNo','TradeNo','HashKey');
    return self::encode($args, $payment_processor, $used_args);
  }

  /**
   * tradeSha for agreement payment
   *
   * @param string $aesString
   * @param object $paymentProcessor
   */
  public static function tradeSha($aesString, $paymentProcessor) {
    $hashKey = $paymentProcessor['password'];
    self::checkKeyIV($hashKey);
    $hashIV = $paymentProcessor['signature'];
    self::checkKeyIV($hashIV);
    $sha = hash("sha256", implode('&', array(
      'HashKey='.$hashKey,
      $aesString,
      'HashIV='.$hashIV,
    )));
    return strtoupper($sha);
  }

  /**
   * Migrate from _civicrm_spgateway_recur_encrypt
   *
   * @param string $str
   * @param object $payment_processor
   * @return string
   */
  public static function recurEncrypt($str, $payment_processor){
    $key = $payment_processor['password'];
    $iv = $payment_processor['signature'];
    self::checkKeyIV($key);
    self::checkKeyIV($iv);
    $str = trim(self::encrypt($key, $iv, $str));
    return $str;
  }

  /**
   * Migrate from _civicrm_spgateway_recur_decrypt
   *
   * @param string $str
   * @param object $payment_processor
   * @return string
   */
  public static function recurDecrypt($str, $payment_processor){
    $key = $payment_processor['password'];
    $iv = $payment_processor['signature'];
    self::checkKeyIV($key);
    self::checkKeyIV($iv);
    $str = self::decrypt($key, $iv, $str);
    return $str;
  }

  /**
   * Migrate from _civicrm_spgateway_encrypt
   *
   * @param string $key
   * @param string $iv
   * @param string $str
   * @param bool $force
   *
   * @return string
   */
  public static function encrypt($key, $iv, $str, $force = NULL) {
    $data = self::addPadding($str);
    if ($force) {
      if ($force == 'openssl') {
        $openssl = TRUE;
      }
      elseif($force == 'mcrypt') {
        $mcrypt = TRUE;
      }
    }
    else {
      $openssl = extension_loaded('openssl') ? TRUE : FALSE;
      $mcrypt = extension_loaded('mcrypt') ? TRUE : FALSE;
    }
    if (empty($openssl) && empty($mcrypt)) {
      return FALSE;
    }

    if ($openssl) {
      $keyLen = strlen($key);
      switch($keyLen) {
        case 16:
          $encoding = 'AES-128-CBC';
          break;
        case 24:
          $encoding = 'AES-192-CBC';
          break;
        case 32:
        default:
          $encoding = 'AES-256-CBC';
          break;
      }
      $encrypted = openssl_encrypt($data, $encoding, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }
    elseif ($mcrypt) {
      $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
    }
    else {
      return '';
    }
    return bin2hex($encrypted);
  }

  /**
   * Migrate from _civicrm_spgateway_decrypt
   *
   * @param string $key
   * @param string $iv
   * @param string $encrypted
   * @param bool $force
   * @return void
   */
  public static function decrypt($key, $iv, $encrypted, $force = NULL) {
    if ($force) {
      if ($force == 'openssl') {
        $openssl = TRUE;
      }
      elseif($force == 'mcrypt') {
        $mcrypt = TRUE;
      }
    }
    else {
      $openssl = extension_loaded('openssl') ? TRUE : FALSE;
      $mcrypt = extension_loaded('mcrypt') ? TRUE : FALSE;
    }
    if (empty($openssl) && empty($mcrypt)) {
      return FALSE;
    }

    $data = hex2bin($encrypted);
    if ($openssl) {
      $keyLen = strlen($key);
      switch($keyLen) {
        case 16:
          $encoding = 'AES-128-CBC';
          break;
        case 24:
          $encoding = 'AES-192-CBC';
          break;
        case 32:
        default:
          $encoding = 'AES-256-CBC';
          break;
      }
      $decrypted = openssl_decrypt($data, $encoding, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }
    elseif ($mcrypt) {
      $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
    }
    return self::stripPadding($decrypted);
  }

  /**
   * Migrate from _civicrm_spgateway_addpadding
   *
   * @param string $string
   * @param int $blocksize
   * @return string
   */
  public static function addPadding($string, $blocksize = 32) {
    $len = strlen($string);
    $pad = $blocksize - ($len % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    return $string;
  }

  /**
   * Migrate from _civicrm_spgateway_strippadding
   *
   * @param string $string
   * @return bool|string
   */
  public static function stripPadding($string) {
      $slast = ord(substr($string, -1));
      $slastc = chr($slast);
      if (preg_match("/$slastc{" . $slast . "}/", $string)) {
          $string = substr($string, 0, strlen($string) - $slast);
          return $string;
      } else {
          return false;
      }
  }
}