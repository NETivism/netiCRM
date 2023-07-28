<?php

class CRM_AI_CompletionService_OpenAI extends CRM_AI_CompletionService {

  CONST END_POINT_LIST = [
    CRM_AI_BAO_AICompletion::CHAT_COMPLETION => 'https://api.openai.com/v1/chat/completions',
  ];

  CONST MODEL_LIST = [
    'gpt-3.5-turbo',
  ];

  /**
   * OpenAI API Key
   * 
   * @var string
   */
  private $_apiKey = NULL;

  /**
   * Model name when using completion service
   *
   * @var string
   */
  private $_model = NULL;

  /**
   * Maximum tokens when using completion service
   *
   * @var int
   */
  private $_maxTokens = NULL;

  /**
   * Temperature
   *
   * @var float
   */
  private $_temperature = NULL;

  /**
   * AICompletion ID
   * 
   * @var int
   */
  private $_id = NULL;

  /**
   * Post data , json format.
   * 
   * @var string
   */
  private $_postData = '';

  /**
   * Response data
   * 
   * @var string
   */
  private $_responseData = '';


  /**
   * Abstract function for setting the model name
   *
   * Should set to default model when provide model name not available
   *
   * @param string $model
   * @return string the real model name set on this function
   */
  public function setModel($model) {
    // TODO: check if model name is in available list
    $this->_model = $model;
  }

  /**
   * Abstract function for setting the max tokens
   *
   * Should set to max tokens when value not provided
   *
   * @param int $maxTokens
   * @return int the real tokens set on this function
   */
  public function setMaxTokens($maxTokens) {
    if ($maxTokens >= CRM_AI_BAO_AICompletion::COMPLETION_MAX_TOKENS) {
      // Set NULL is different with set token to max value
      // When passing max_token into OpenAI, it will calc response and request token first
      // When null given, OpenAI will not check token and trying his best to reply message in the limitation of max token
      $this->_maxTokens = NULL;
    }
    else {
      $this->_maxTokens = $maxTokens;
    }
  }

  /**
   * Sending request using service API
   *
   * Error handling should using try - catch when doing request
   *
   * @param array $params
   * @return string
   */
  public function request($params) {
    $config = CRM_Core_Config::singleton();
    // Check API key exist
    if (empty($config->openAIAPIKey)) {
      throw new Exception("OpenAI API Key doesn't found");
    }

    // Set the API endpoint based on the action
    $api_endpoint = self::END_POINT_LIST[$params['action']];

    // Format the parameters for the request
    $this->_postData = $this->formatParams($params);

    // Set API Key
    $this->_apiKey = $config->openAIAPIKey;

    // Send the request to OpenAI
    $ch = curl_init($api_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->_apiKey,
    ]);
    if ($params['stream']) {
      header("Content-Type: text/event-stream");
      header("X-Accel-Buffering: no");
      while ($level = ob_get_level()) {
        ob_end_clean();
      }
      $responseData = &$this->_responseData;
      $responseData = [
        'status_id' => 2, // 2: pending, 5: processing, 1: finished, 
        'id' => $this->_id,
      ];
      curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$responseData){
        $chunks = explode("\n", $data);
        if (is_array($chunks)) {
          $chunks = array_filter($chunks);
        }
        foreach($chunks as $resp) {
          $json = preg_replace('/^data:\s/', '', $resp);
          $decoded = json_decode($json, TRUE);
          if ($decoded === FALSE) {

          }
          elseif ($decoded['error']['message'] != "") {
            CRM_Core_Error::debug_log_message($decoded['error']['message']);
            // error handler
            // some error message for $decoded['error']['message'] example:
            // "Rate limit reached"
            // "Your access was terminated"
            // "You didn't provide an API key"
            // "You exceeded your current quota"
            // "That model is currently overloaded"
            echo 'data: '.json_encode(
              array(
                'is_error' => 1,
                'message' => $decoded['error']['message'],
              )
            );
          }
          else {
            if (trim($data) != "data: [DONE]" && isset($decoded["choices"][0]["delta"]["content"])) {
              // log response to variable
              $responseData['message'] .= $decoded["choices"][0]["delta"]["content"];
              // output data
              $outputData = array(
                "message" => $decoded['choices'][0]['delta']['content'],
              );
              // change state
              if ($responseData['status_id'] == 2) {
                $responseData['status_id'] = 5;
                $outputData['id'] = $responseData['id'];
              }
              echo 'data: '.json_encode($outputData)."\n\n";
            }
            if (!empty($decoded["choices"][0]["finish_reason"]) && $decoded["choices"][0]["finish_reason"] === 'stop') {
              $responseData['is_finished'] = 1;
              $responseData['status_id'] = 1;
              // Last response, Save data.
              $aiCompletionData = $responseData;
              $aiCompletionData['output_text'] = $responseData['message'];
              $aiCompletionData['return_data'] = json_encode($decoded);
              if (json_decode($json)) {
                $aiCompletionData['return_data'] = $json;
              }
              $aiCompletionData['post_data'] = $this->_postData;
              $aiCompletionData['temperature'] = $this->_temperature;
              CRM_AI_BAO_AICompletion::create($aiCompletionData);
              echo 'data: '.json_encode($responseData)."\n\n";
            }
            if (!empty($decoded["choices"][0]["finish_reason"]) && $decoded["choices"][0]["finish_reason"] !== 'stop') {
              $responseData['is_finished'] = 1;
              $responseData['is_error'] = 1;
              $responseData['error_message'] = $decoded["choices"][0]["finish_reason"];

              echo 'data: '.json_encode($responseData)."\n\n";
            }
          }
        }
        ob_flush();
        flush();
        return strlen($data);
      });
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
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
   * Return the fields array that API used.
   * 
   * @param string $apiType field name
   * @param boolean $is_required only return required fields or not  
   * @return array $fields An array contain needed fields.
   */
  static private function fields($apiType, $is_required = FALSE) {
    $fields = array();
    switch($apiType){
      case 'CHAT_COMPLETION':
        // Refs: https://platform.openai.com/docs/api-reference/chat/create
        $fields = explode(',', 'model*,messages*,temperature,top_p,n,stream,stop,max_tokens,presence_penalty,frequency_penalty,logit_bias,user');
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
   * Format parameters before sending via request
   *
   * @param array $params(reference)
   * @return string json encoded string.
   */
  protected function formatParams(&$params) {
    if ($params['id']) {
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
      if (empty($params['max_tokens']) && isset($this->_maxTokens)) {
        $params['max_tokens'] = $this->_maxTokens;
      }
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
   * Format response before saving to CRM_AI_DAO_AICompletion
   *
   * @param string $responseString
   * @return array
   */
  protected function formatResponse($responseString) {
    $response = json_decode($responseString, TRUE);
    $responseData = [
      'post_data' => $this->_postData,
      'return_data' => $responseString,
      'response' => $response,
      'used_token' => [
        'prompt_tokens' => $response['usage']['prompt_tokens'],
        'completion_tokens' => $response['usage']['completion_tokens'],
        'total_tokens' => $response['usage']['total_tokens'],
      ],
    ];
    if (isset($response['choices']) && count($response['choices']) == 1) {
      $choice = reset($response['choices']);
      $responseData['message'] = $choice['message']['content'];
    }
    if (isset($responseData['response']['error'])) {
      $responseData['status_id'] = 4; // Failed
    }
    else {
      $responseData['status_id'] = 1; // Finished
    }
    return $responseData;
  }

  /**
   * Low level function to determine if result in response is an error.
   *
   * @param array $response
   * @return boolean
   */
  protected function isError($response) {

  }

  /**
   * Calculate token numbers
   *
   * @param string $string
   * @return int
   */
  public static function calculateTokenNumbers($input) {

  }
}