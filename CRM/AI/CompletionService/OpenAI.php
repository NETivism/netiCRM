<?php

class CRM_AI_CompletionService_OpenAI extends CRM_AI_CompletionService {
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

  }


  /**
   * Format parameters before sending via request
   *
   * @param array $params(reference)
   * @return void
   */
  private function formatParams(&$params) {

  }

  /**
   * Format response before saving to CRM_AI_DAO_AICompletion
   *
   * @param string $responseString
   * @return array
   */
  private function formatResponse($responseString) {

  }

  /**
   * Low level function to determine if result in response is an error.
   *
   * @param array $response
   * @return boolean
   */
  private function isError($response) {

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