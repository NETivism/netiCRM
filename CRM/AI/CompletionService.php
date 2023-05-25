<?php

/**
 * abstract class for AI completion
 */

abstract class CRM_AI_CompletionService {
  /**
   * Abstract function for setting the model name
   *
   * Should set to default model when provide model name not available
   *
   * @param string $model
   * @return string the real model name set on this function
   */
  abstract public function setModel($model);

  /**
   * Abstract function for setting the max tokens
   *
   * Should set to max tokens when value not provided
   *
   * @param int $maxTokens
   * @return int the real token set on this function
   */
  abstract public function setMaxTokens($maxTokens);

  /**
   * Sending request using service API
   *
   * Error handling should using try - catch when doing request
   *
   * @param array $params
   * @return string
   */
  abstract public function request($params);


  /**
   * Format parameters before sending via request
   *
   * @param array $params(reference)
   * @return void
   */
  abstract protected function formatParams(&$params);

  /**
   * Format response before saving to CRM_AI_DAO_AICompletion
   *
   * @param string $responseString
   * @return array
   */
  abstract protected function formatResponse($responseString);

  /**
   * Low level function to determine if result in response is an error.
   *
   * @param array $response
   * @return boolean
   */
  abstract protected function isError($response);
}