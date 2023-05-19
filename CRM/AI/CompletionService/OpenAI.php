<?php

class CRM_AI_CompletionService_OpenAI extends CRM_AI_CompletionService {

  CONST _endPointList = [
    CRM_AI_BAO_AICompletion::ChatCompletion => 'https://api.openai.com/v1/chat/completions',
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
  private $_maxToken = 4096;


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
   * Abstract function for setting the max token
   *
   * Should set to max token when value not provided
   *
   * @param int $maxToken
   * @return int the real token set on this function
   */
  public function setMaxToken($maxToken) {
    $this->_maxToken = $maxToken;
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
    // Check API key exist
    if (!defined('OPENAI_API_KEY')) {
      throw new Exception("OpenAI API Key doesn't found");
    }

    // Set the API endpoint based on the action
    $api_endpoint = self::_endPointList[$params['action']];

    // Format the parameters for the request
    $jsonParams = $this->formatParams($params);

    // Set API Key
    $this->_apiKey = OPENAI_API_KEY;

    // Send the request to OpenAI
    $ch = curl_init($api_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->_apiKey,
    ]);
    $response = curl_exec($ch);
    if(curl_errno($ch)){
      throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    // Format the response and return it
    return $this->formatResponse($response);
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
      case 'ChatCompletion':
        $fields = explode(',', 'model*,messages*');
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
    if ($params['action'] == CRM_AI_BAO_AICompletion::ChatCompletion) {
      if ($params['prompt'] && empty($params['messages'])) {
        $params['messages'] = [[
          'role' => 'user',
          'content' => $params['prompt'],
        ]];
      }
      if (empty($params['model'])) {
        $params['model'] = $this->_model;
      }
    }
    $fields = self::fields('ChatCompletion');
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
    $response = json_decode($responseString);
    $responseData = [
      'response' => $response,
      'used_token' => [
        'prompt_tokens' => $response['usage']['prompt_tokens'],
        'completion_tokens' => $response['usage']['completion_tokens'],
        'total_tokens' => $response['usage']['total_tokens'],
      ],
      'status' => 1, // Finished
    ];
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