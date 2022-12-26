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

  public $_multiMode = FALSE;

  private $_batchObjectId = NULL;

  private $_mitakeStatuses = NULL;

  public static function &singleton($providerParams = array(), $force = FALSE) {
    $providerId = CRM_Utils_Array::value('provider_id', $providerParams);
    $providerId = CRM_Utils_Type::validate($providerId, 'Integer');
    if (empty($providerId)) {
      CRM_Core_Error::fatal('Provider not known or not provided.');
    }
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_SMS_Provider_Mitake($providerId);
    }
    return self::$_singleton;
  }

  function __construct($providerId) {
    $providerInfo = CRM_SMS_BAO_Provider::getProviderInfo($providerId);
    $this->_providerInfo = $providerInfo;
    if (strstr($this->_providerInfo['api_url'], 'SmBulkSend')) {
      $this->_multiMode = TRUE;
    }
    $this->_batchObjectId = (string) microtime(true);
    $this->_mitakeStatuses = array(
      '0' => ts('Scheduled'),
      '1' => ts('Delivered'),
      '2' => ts('Delivered'),
      '4' => ts('Arrived Phone'),
      '5' => ts('Error').":".ts('Content'),
      '6' => ts('Error').":".ts('Phone Number'),
      '7' => ts('Error').":".ts('no SMS'),
      '8' => ts('Error').":".ts('Expires'),
      '9' => ts('Error').":".ts('Cancelled'),
    );
    $this->_mitakeStatusesMapping = array(
      '0' => 1,
      '1' => 4,
      '2' => 4,
      '4' => 2,
      '5' => 3,
      '6' => 5,
      '7' => 5,
      '8' => 5,
      '9' => 5,
    );
  }

  /**
   * Main function to send SMS
   *
   * The result should be mapping to activity status name for better update activity
   *
   * @param array $messages
   *   array should format like this
   *     phone(string) => phone number
   *     body(string) => message
   *     guid(string) => Unique ID to identify this sms
   *     activityId(int) => activity id correspond to this sms
   * @return array
   */
  public function send(&$messages){
    $data = array();
    if ($this->_multiMode){
      //$this->initMultiSMS($recipients, $message);
    }
    elseif (count($messages) == 1) {
      $data = $this->formatSMS(reset($messages));
    }
    else {
      // error
      CRM_Core_Error::debug_log_message("Mitake need to enable SmBulkSend to send to multiple recipients at once");
      return FALSE;
    }

    // TODO: if we have hook, we should place here

    // Sending request
    $query = http_build_query($data['http_query_params'], "", "&", PHP_QUERY_RFC3986);
    $response = $this->doRequest($this->_providerInfo['api_url'].'?'.$query, array(
      'post_data' => $data['http_post_params'],
    ));

    $this->activityUpdate();

    // prevent singleton re-use saved iterations, free result
    $this->free();
    return $response;
  }

  /**
   * Update activity after SMS send
   */
  public function activityUpdate() {
    foreach($this->_sms as $guid => $sms) {
      if ($sms['activityId']) {
        $details = array();
        $details[] = '<div class="content">'.ts("Body") . ": " . $sms['smbody'].'</div>';
        $details[] = '<div class="meta">';
        $details[] = ts("To") .": ". CRM_Utils_String::mask($sms['dstaddr'], 'custom', 4, 2);
        if (!empty($sms['result'])) {
          foreach($sms['result'] as $key => $val) {
            $details[] = $key.': '.$val;
          }
        }
        $details[] = '</div>';
        $details = implode("<br>", $details);
        if (is_numeric($sms['result']['statuscode']) && !empty($this->_mitakeStatusesMapping[$sms['result']['statuscode']])) {
          $toStatus = $this->_mitakeStatusesMapping[$sms['result']['statuscode']];
        }
        else {
          $toStatus = CRM_Utils_Array::key('Cancelled', CRM_Core_PseudoConstant::activityStatus('name'));
        }
        if ($toStatus) {
          $activity = new CRM_Activity_DAO_Activity();
          $activity->id = $sms['activityId'];
          if ($activity->find(TRUE)) {
            $activity->details = $details;
            $activity->status_id = $toStatus;
            $activity->save();
            $activity->free();
          }
        }
      }
    }
  }

  /**
   * Send request to Mitake
   *
   * @param string requestUri
   * @param array request
   *
   * @return array
   *   result array will contain these element
   *     raw(string) => response body
   *     body(array) => formatted response, contain real result of sms
   *     error(bool) => http request success or not
   *     error_message(bool) => error message that show http erro
   */
  protected function doRequest($requestUri, $request = array()) {
    $ch = curl_init($requestUri);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);

    // Mitake seems not accept multipart/form-data
    // When array passed into CURLOPT_POSTFIELDS, content-type will convert to multipart/form-data
    // We use http_build_query instead
    // PHP_QUERY_RFC1738 means application/x-www-form-urlencoded compatible
    $postFields = http_build_query($request['post_data'], "", "&", PHP_QUERY_RFC1738);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = array();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $responseBody = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === FALSE) {
      $errno = curl_errno($ch);
      $err = curl_error($ch);
      $error = array(
        'error_code' => $errno,
        'error' => $err,
        'http_status' => $status,
      );
      CRM_Core_Error::debug_log_message('Mitake send sms failed at curl');
      CRM_Core_Error::debug_var('mitake_http_error', $error);
      $response['error'] = 1;
      $response['error_message'] = $err;
      $response['raw'] = '';
    }
    else {
      $response['error'] = 0;
      $response['raw'] = $responseBody;
      $response['body'] = $this->formatResponse($responseBody);
    }
    curl_close($ch);

    return $response;
  }

  /**
   * SmsData constructor.
   *
   * @param $message
   *
   * @return array|bool
   */
  protected function formatSMS($message) {
    $rules = array();
    $rules['required'] = array(
      //'username:20:',
      //'password:24:',
      'dstaddr:20:',
      'smbody::',
    );
    $rules['optional'] = array(
      'destname:36:',
      'dlvtime:14:YmdHis',
      'vldtime:14:YmdHis',
      'response:256:',
      'clientid:36:',
      'objectID:16:',
    );

    // required fields
    $msg = array();
    $msg['dstaddr'] = $this->formatPhone($message['phone']);
    $msg['smbody'] = $message['body'];

    // TODO: optional fields

    // special field
    if ($message['guid']) {
      $msg['clientID'] = substr($message['guid'], 0, 36);
    }
    else {
      $msg['clientID'] = substr(md5($msg['dstaddr'].date('Ymd').$msg['body']), 0, 36);
    }
    if ($message['callback']) {
      $msg['response'] = $message['callback'];
    }
    $msg['objectID'] = $this->_batchObjectId;

    // TODO: validate the format base by rules

    $this->_sms[$msg['clientID']] = $msg;
    if (!empty($message['activityId'])) {
      $this->_sms[$msg['clientID']]['activityId'] = $message['activityId'];
    }

    // prepare request
    $msg['username'] = $this->_providerInfo['username'];
    $msg['password'] = $this->_providerInfo['password'];
    return $this->prepareSmsRequest(array($msg));
  }

  public function prepareSmsRequest($formatted) {
    if ($this->_multiMode) {
      foreach ($this->_sms as $sms) {
        /*
        $data = '[' . REQUEST_TIME . $sms->dest . "]\r\n" . $smsData['get'] . "\r\n";
        */
      }
    }
    else {
      $sms = reset($formatted);
      $data = array();
      $data['http_query_params'] = array(
        'CharsetURL' => 'UTF8',
      );
      $data['http_post_params'] = $sms;
    }
    return $data;
  }

  protected function formatPhone($phone) {
    $p = preg_replace('/[^0-9]/', '', str_replace('+886', '0', $phone));
    if ((strlen($p) != 10) || !preg_match('/09[0-9]{8}/', $p, $match)) {
      return '';
    }
    else {
      return $p;
    }
  }

  public function formatResponse($responseBody) {
    $responseLines = preg_split("/\r\n|\n|\r/", $responseBody);
    $msgCount = 0;
    $result = array();

    // any of SMS correctly response numberic status, this will be TRUE
    $success = FALSE;
    foreach($responseLines as $line) {
      if (preg_match('/^\[([0-9a-z]+)\]/i', $line, $matches)) {
        $msgCount++;
        $msgIndex = $matches[1];
      }
      if ($msgIndex) {
        if (preg_match('/([^=]*)=([^=]*)/ui', $line, $matches)) {
          $key = $matches[1];
          $val = $matches[2];
          $result[$msgIndex][$key] = $val;
          if ($key === 'statuscode') {
            // Send
            if (is_numeric($val)) {
              $success = TRUE;
              if (isset($this->_mitakeStatuses[$val])) {
                $result[$msgIndex]['status'] = $this->_mitakeStatuses[$val];
              }
              if ($val <=4 ) {
                $result[$msgIndex]['success'] = 1;
              }
              else {
                $result[$msgIndex]['success'] = 0;
              }
            }
            // Can not send
            else {
              $result[$msgIndex]['success'] = 0;
            }
          }
          if ($key === 'Error') {
            $result[$msgIndex]['status'] = $val;
          }
          if ($key === 'Duplicate') {
            $result[$msgIndex]['status'] .= '-'.ts('Duplicated message');
          }
        }
      }
    }

    foreach($this->_sms as $idx => $sms) {
      if (array_key_exists($idx, $result)) {
        $this->_sms[$idx]['result'] = $result[$idx];
      }
      elseif (!$result[1]['success']) {
        $this->_sms[$idx]['result'] = $result[1];
      }
    }
    $result['all']['success'] = $success ? 1 : 0;
    return $result;
  }
}