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
    $options['data'] = $this->prepareSmsData();
    print_r($this->do_request($this->_providerInfo['api_url'] . '?' . $data_str .'&' . $options['data'] .'&outtype=1&Encoding_PostIn=UTF8', 'POST', $options));
	}

	protected function do_request($req_uri, $method = 'GET', $options = array()) {

    if($method == 'POST'){
    	$ch = curl_init($req_uri);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  // curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
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
    $str = '';
    if ($this->multi_mode) {
      foreach ($this->sms as $sms) {
        if ($this->long_mode) {
          $str .= REQUEST_TIME . $sms->dest .  $sms->formatedSMS('ML') . "\r\n";
        }
        else {
          $str .= '[' . REQUEST_TIME . $sms->dest . "]\r\n" . $sms->formatedSMS('MS') . "\r\n";
        }
      }
    }
    else {
      if ($this->long_mode) {
        $str .= $this->sms->formatedSMS('SL');
      }
      else {
        $str .= $this->sms->formatedSMS('SS');
      }
    }
    return $str;
  }
}


/**
 * Created by PhpStorm.
 * User: Ken
 * Date: 2016/7/21
 * Time: 上午 01:50
 */

// namespace Drupal\sms_mitake;


class SmsData {
  public $dest;
  public $destname;
  public $body;
  public $dlvtime;
  public $vldtime;
  public $response;
  public $smsFlag;

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

  }

//  public function setDestination($phoneNum) {
//    $this->dest = $this->check_and_format_phone($phoneNum);
//    return $this;
//  }
//
//  public function setBody($text) {
//    $this->body = $text;
//    return $this;
//  }
//
//  public function setDeliveryTime($dlvtime) {
//    $this->dlvtime = $dlvtime;
//    return $this;
//  }
//
//  public function getBody() {
//    return $this->body;
//  }
//
//  public function getDestination() {
//    return $this->dest;
//  }


//  protected function encoder($encode_type, $body) {
//    switch ($encode_type) {
//      case 'urlencode':
//        return urlencode($body);
//        break;
//      case 'big5':
//        break;
//    }
//    return $body;
//  }

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
    $str = '';
    switch ($format_type) {
      case 'SS'://單筆短簡訊
        $param = array(
          'encoding'=>'UTF8',
          'dstaddr' => $this->dest,
          'smbody' => $this->body,
        );
        $str .= http_build_query($param);
        break;

      case 'SL'://單筆長簡訊
        $param = array(
          'dstaddr' => $this->dest,
          'smbody' => $this->body,
          'CharsetURL' => 'utf-8',
        );
        if ($this->destname) {
          $param['destname'] = $this->destname;
        }
        $str .= http_build_query($param);

      case 'MS'://多筆短簡訊
        $str .= 'dstaddr=' . $this->dest . "\r\n" . 'smbody=' . $this->body . "\r\n";
        break;

      case 'ML'://多筆長簡訊
        $str .= '$$' . $this->dest .
          (($this->dlvtime) ? '$$' . $this->dlvtime : '$$') .
          (($this->vldtime) ? '$$' . $this->vldtime : '$$') .
          (($this->destname) ? '$$' . $this->destname : '$$') .
          (($this->response) ? '$$' . $this->response : '$$') .
          '$$'.$this->body;

        break;
    }
    return $str;

  }

}