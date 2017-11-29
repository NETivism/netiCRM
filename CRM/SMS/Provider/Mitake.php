<?php

class CRM_SMS_Provider_Mitake extends CRM_SMS_Provider {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  private $multi_mode = false;

  private $long_mode = false;

  private $_providerInfo = array();

  private $sms = null;

  public static function &singleton($providerParams = array(), $force = FALSE) {
    $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
    $providerInfo = CRM_SMS_BAO_Provider::getProviderInfo($providerID);
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_SMS_Provider_Mitake($providerInfo);
    }
    return self::$_singleton;
  }

  function __construct($providerInfo) {
    $this->_providerInfo = $providerInfo;
  }

  // use via CRM_Activity_BAO_Activity::sendSMSMessage
  public function send($recipients, $header, $message, $dncID = NULL){
    $param = array(
      'username' => $this->_providerInfo['username'],
      'password' => $this->_providerInfo['password'],
    );
    $data_str = http_build_query($param);
    $this->sms = new SmsData($recipients, $message);
    $data = $this->prepareSmsData();
    $options['data'] = $data['post'];
    return $this->do_request($this->_providerInfo['api_url'] . '?' . $data_str .'&' . $data['get'] , 'POST', $options);
  }

  protected function do_request($req_uri, $method = 'GET', $options = array()) {
    if($method == 'POST'){
      $ch = curl_init($req_uri);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
      if(!empty($options['header'])){
        curl_setopt($ch, CURLOPT_HEADER, $options['header']);  // DO NOT RETURN HTTP HEADERS
      }else{
        curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
      }
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL
      $receive = curl_exec($ch);
      curl_close($ch);
    }

    return $receive;
  }

  protected function prepareSmsData() {
    $data = array();
    if ($this->multi_mode) {
      foreach ($this->sms as $sms) {
        if ($this->long_mode) {
          $smsTypeData = $sms->formatedSMS('ML');
          $data['get'] = REQUEST_TIME . $sms->dest . $smsTypeData['get']  . "\r\n";
        }
        else {
          $smsTypeData = $sms->formatedSMS('MS');
          $data = '[' . REQUEST_TIME . $sms->dest . "]\r\n" . $smsTypeData['get'] . "\r\n";
        }
      }
    }
    else {
      if ($this->long_mode) {
        $data = $this->sms->formatedSMS('SL');
      }
      else {
        $data = $this->sms->formatedSMS('SS');
      }
    }
    return $data;
  }
}

class SmsData {
  public $dest;
  public $destname;
  public $body;
  public $dlvtime;
  public $vldtime;
  public $response;
  public $smsFlag;
  public $clientID;

  /**
   * SmsData constructor.
   * @param $dest
   *   dest phone no.
   * @param $body
   *   sms body
   * @param null $delivery_time
   *   reserved time when the sms delivery
   * @param null $vldtime
   * @param null $destName
   * @param null $response
   */
  public function __construct($dest, $body, $delivery_time = NULL, $vldtime = NULL, $destName = NULL, $response = NULL) {
    if ($dest && $dev_check = $this->check_and_format_phone($dest)) {
      $this->dest = $dev_check;
    }
    $this->body = $body;
    if ($delivery_time) {
      $this->dlvtime = $delivery_time;
    }
    if ($vldtime) {
      $this->vldtime = $vldtime;
    }
    if ($destName) {
      $this->destname = $destName;
    }
    if ($response) {
      $this->response = $response;
    }

    if(!empty($this->dlvtime)){
      $dlvdate = date('Ymd',$this->dlvtime);
    }else{
      $dlvdate = date('Ymd');
    }
    $this->clientID = md5($this->dest.$dlvdate.$this->body);
  }

  protected function check_and_format_phone($phone) {
    $p = preg_replace('/[^0-9]/', '', str_replace('+886', '0', $phone));
    if ((strlen($p) != 10) || !preg_match('/09[0-9]{8}/', $p, $match)) {
      return '';
    }
    else {
      return $p;
    }
  }

  public function is_vaild(){
    if($this->body && $this->dest){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Format SmsData for POST
   */
  public function formatedSMS($format_type) {
    // $str = '';
    $data = array();
    switch ($format_type) {
      case 'SS': // Single Short
        $param = array(
          'encoding'=>'UTF8',
          'dstaddr' => $this->dest,
          'smbody' => $this->body,
          'clientID' => $this->clientID,
        );
        $data['get'] = http_build_query($param, "", "&", PHP_QUERY_RFC3986);
        break;

      case 'SL': // Single Long
        $param = array(
          'dstaddr' => $this->dest,
          'smbody' => $this->body,
          'CharsetURL' => 'utf-8',
          'clientID' => $this->clientID,
        );
        if ($this->destname) {
          $param['destname'] = $this->destname;
        }
        $data['get'] = http_build_query($param);
        break;

      case 'MS': // Multiple Short
        $data['get'] = 'encoding=UTF8';
        if(!empty($this->clientID)){
          $data['post'] = '['.$this->clientID.']';
        }
        $data['post'] = 'dstaddr=' . $this->dest . "\r\n" . 'smbody=' . $this->body . "\r\n";
        break;

      case 'ML': // Multiple Long
        $data['get'] = 'outtype=1&Encoding_PostIn=UTF8';
        $data['post'] = ($this->clientID) ? $this->clientID : '';
        $data['post'] = '$$' . $this->dest .
          (($this->dlvtime) ? '$$' . $this->dlvtime : '$$') .
          (($this->vldtime) ? '$$' . $this->vldtime : '$$') .
          (($this->destname) ? '$$' . $this->destname : '$$') .
          (($this->response) ? '$$' . $this->response : '$$') .
          '$$'.$this->body;

        break;
    }
    return $data;

  }

}