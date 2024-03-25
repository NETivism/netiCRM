<?php

class CRM_AI_CompletionService_Claude extends CRM_AI_CompletionService {

  CONST END_POINT_LIST = [
    CRM_AI_BAO_AICompletion::CHAT_COMPLETION => 'https://api.anthropic.com/v1/messages',
  ];

  CONST MODEL_LIST = [
    'claude-3-haiku-20240307',
    'claude-3-sonnet-20240229',
    'claude-3-opus-20240229',
  ];

  /**
   *Anthropic API Key
   *
   *@var string
   */
  private $_apiKey = NULL;

  /**
   *Model name when using completion service
   *
   *@var string
   */
  private $_model = NULL;

  /**
   *Maximum tokens when using completion service
   *
   *@var int
   */
  private $_maxTokens = NULL;

  /**
   *Temperature
   *
   *@var float
   */
  private $_temperature = NULL;

  /**
   *AICompletion ID
   *
   *@var int
   */
  private $_id = NULL;

  /**
   *Post data , json format.
   *
   *@var string
   */
  private $_postData = '';

  /**
   *Response data
   *
   *@var string
   */
  private $_responseData = '';


  /**
   *Abstract function for setting the model name
   *
   *Should set to default model when provide model name not available
   *
   *@param string $model
   *@return string the real model name set on this function
   */
  public function setModel($model) {
    // TODO: check if model name is in available list
    $this->_model = $model;
  }

  /**
   *Abstract function for setting the max tokens
   *
   *Should set to max tokens when value not provided
   *
   *@param int $maxTokens
   *@return int the real tokens set on this function
   */
  public function setMaxTokens($maxTokens) {
    if ($maxTokens >= CRM_AI_BAO_AICompletion::COMPLETION_MAX_TOKENS) {
      // Set NULL is different with set token to max value
      // When passing max_token into Claude, it will calc response and request token first
      // When null given, Claude will not check token and trying his best to reply message in the limitation of max token
      $this->_maxTokens = NULL;
    }
    else {
      $this->_maxTokens = $maxTokens;
    }
  }

  /**
   *Sending request using service API
   *
   *Error handling should using try - catch when doing request
   *
   *@param array $params
   *@return string
   */
  public function request($params) {
    $config = CRM_Core_Config::singleton();
    // Check API key exist
    if (empty($config->claudeAIAPIKey)) {
      throw new Exception("Anthropic API Key doesn't found");
    }

    // Set the API endpoint based on the action
    $api_endpoint = self::END_POINT_LIST[$params['action']];

    // Format the parameters for the request
    $this->_postData = $this->formatParams($params);

    // Set API Key
    $this->_apiKey = $config->claudeAIAPIKey;

    // Send the request to Claude
    $ch = curl_init($api_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'X-API-Key: ' . $this->_apiKey,
      'Anthropic-Version: 2023-06-01',
    ]);
    if (!empty($params['stream'])) {
      header("Content-Type: text/event-stream");
      header("X-Accel-Buffering: no");
      while ($level = ob_get_level()) {
        ob_end_clean();
      }
      $responseData = array(
        'status_id' => 2, // 2: pending, 5: processing, 1: finished, 
        'id' => !empty($this->_id) ? $this->_id : 0,
        'message' => '',
      );
      curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$responseData){
        $chunks = explode("\n", $data);
        if (is_array($chunks)) {
          $chunks = array_filter($chunks);
        }
        foreach($chunks as $resp) {
          $json = preg_replace('/^data:\s/', '', $resp);
          $decoded = json_decode($json, TRUE);
          if ($decoded === FALSE) {
            // error handler
            echo 'data: '.json_encode(
              array(
                'is_error' => 1,
                'message' => 'Response error',
              )
            );
          }
          elseif (isset($decoded['type']) && $decoded['type'] === 'error') {
            CRM_Core_Error::debug_log_message($decoded['error']['message']);
            // error handler
            echo 'data: '.json_encode(
              array(
                'is_error' => 1,
                'message' => $decoded['error']['message'],
              )
            );
          }
          elseif (isset($decoded['type'])) {
            switch($decoded['type']) {
              case 'message_start':
                if (!empty($decoded['message'])) {
                  $aiCompletionData = array(
                    'id' => $responseData['id'],
                    'status_id' => 5,
                  );
                  if (!empty($decoded['message']['usage']['input_tokens'])) {
                    $aiCompletionData['prompt_token'] = (int) $decoded['message']['usage']['input_tokens'];
                  }
                  if (!empty($responseData['id'])) {
                    CRM_AI_BAO_AICompletion::create($aiCompletionData);
                  }
                }
                break;
              case 'ping':
                break;
              case 'content_block_start':
                break;
              case 'content_block_delta':
                if (isset($decoded['delta']['text'])) {
                  $responseData['message'] .= $decoded['delta']['text'];
                  $outputData = array(
                    "message" => $decoded['delta']['text'],
                  );
                  echo 'data: '.json_encode($outputData)."\n\n";
                }
                break;
              case 'content_block_stop':
                break;
              case 'message_delta':
                if (!empty($decoded['delta']) && !empty($decoded['delta']['stop_reason'])) {
                  if ($decoded['delta']['stop_reason'] == 'end_turn' || $decoded['delta']['stop_reason'] == 'stop_sequence') {
                    $outputData = array(
                      'is_finished' => 1,
                      'status_id' => 1,
                    );
                  }
                  elseif($decoded['delta']['stop_reason'] == 'max_tokens'){
                    $outputData = array(
                      'is_finished' => 0,
                      'status_id' => 4,
                    );
                  }
                  $aiCompletionData = array(
                    'id' => $responseData['id'],
                    'status_id' => $outputData['status_id'],
                  );
                  $aiCompletionData['output_text'] = $responseData['message'];
                  if (!empty($decoded['usage']['output_tokens'])) {
                    $aiCompletionData['completion_token'] = $decoded['usage']['output_tokens'];
                  }
                  if (!empty($responseData['id'])) {
                    CRM_AI_BAO_AICompletion::create($aiCompletionData);
                  }
                  echo 'data: '.json_encode($outputData)."\n\n";
                }
                break;
              case 'message_stop':
                break;
            }
          }
        }
        ob_flush();
        flush();
        return strlen($data);
      });

      // this will break event stream
      curl_setopt($ch, CURLOPT_TIMEOUT, 300);

      // the common connection timeout
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_AUTOREFERER, true);

      curl_exec($ch);
      $curl_errno = curl_errno($ch);
      $curl_error = curl_error($ch);  
      if ($curl_errno > 0) {
        throw new CRM_Core_Exception("Curl Error. Error Number: {$curl_errno}. Error message: {$curl_error}");
      }
      curl_close($ch);
    }
    else {
      // this will break when claude took too long to response
      curl_setopt($ch, CURLOPT_TIMEOUT, 300);

      // this will limit common connection timeout
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
      $this->_responseData = curl_exec($ch);
      if(curl_errno($ch)){
        throw new CRM_Core_Exception(curl_error($ch));
      }
      curl_close($ch);
      // Format the response and return it
      return $this->formatResponse($this->_responseData);
    }
  }

  /**
   *Return the fields array that API used.
   *
   *@param string $apiType field name
   *@param boolean $is_required only return required fields or not
   *@return array $fields An array contain needed fields.
   */
  static private function fields($apiType, $is_required = FALSE) {
    $fields = array();
    switch($apiType){
      case 'CHAT_COMPLETION':
        // Refs: https://console.anthropic.com/docs/api/reference
        $fields = explode(',', 'model*,messages*,max_tokens,stop_sequences,stream,system,temperature,top_k,top_p');
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

  /**
   *Format parameters before sending via request
   *
   *@param array $params(reference)
   *@return string json encoded string.
   */
  protected function formatParams(&$params) {
    if (isset($params['id'])) {
      $this->_id = $params['id'];
    }
    if ($params['action'] == CRM_AI_BAO_AICompletion::CHAT_COMPLETION) {
      if ($params['prompt'] && empty($params['messages'])) {
        if ($prompt = json_decode($params['prompt'], TRUE)) {
          $params['messages'] = $prompt;
        }
        else {
          $params['messages'] = [[
            'role' => 'user',
            'content' => $params['prompt'],
          ]];
        }
      }
      if (empty($params['model'])) {
        $params['model'] = $this->_model;
      }
      $params['max_tokens'] = !empty($this->_maxTokens) ? $this->_maxTokens : 4096;
      if (isset($params['temperature'])) {
        $this->_temperature = $params['temperature'] = (float)$params['temperature'];
      }
    }
    $fields = self::fields('CHAT_COMPLETION');
    foreach ($params as $key => $value) {
      if (!in_array($key, $fields)) {
        unset($params[$key]);
      }
    }
    return json_encode($params);
  }

  /**
   *Format response before saving to CRM_AI_DAO_AICompletion
   *
   *@param string $responseString
   *@return array
   */
  protected function formatResponse($responseString) {
    $response = json_decode($responseString, TRUE);
    $responseData = [
      'post_data' => $this->_postData,
      'return_data' => $responseString,
      'response' => $response,
      'used_token' => [
        'prompt_tokens' => !empty($response['usage']['input_tokens']) ? (int) $response['usage']['input_tokens'] : 0,
        'completion_tokens' => !empty($response['usage']['output_tokens']) ? (int) $response['usage']['output_tokens'] : 0,
      ],
    ];
    $responseData['used_token']['total_tokens'] = $responseData['used_token']['prompt_tokens'] + $responseData['used_token']['completion_tokens'];
    if (isset($response['content'])) {
      $responseData['message'] = $response['content']['text'];
    }
    if (isset($response['error'])) {
      $responseData['status_id'] = 4; // Failed
    }
    else {
      $responseData['status_id'] = 1; // Finished
    }
    return $responseData;
  }

  /**
   *Low level function to determine if result in response is an error.
   *
   *@param array $response
   *@return boolean
   */
  protected function isError($response) {

  }

  /**
   *Calculate token numbers
   *
   *@param string $string
   *@return int
   */
  public static function calculateTokenNumbers($input) {

  }
}
