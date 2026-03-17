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
   * @param string $model The model name.
   * @return void
   */
  abstract public function setModel($model);

  /**
   * Abstract function for setting the max tokens
   *
   * Should set to max tokens when value not provided
   *
   * @param int $maxTokens The maximum number of tokens.
   * @return void
   */
  abstract public function setMaxTokens($maxTokens);

  /**
   * Abstract function for getting the current model name
   *
   * @return string the current model name
   */
  abstract public function getModel();

  /**
   * Abstract function for getting the current max tokens
   *
   * @return int|null the current max tokens
   */
  abstract public function getMaxTokens();

  /**
   * Sending request using service API
   *
   * Error handling should using try - catch when doing request
   *
   * @param array $params The request parameters.
   * @return array|void The response data.
   */
  abstract public function request($params);

  /**
   * Format parameters before sending via request
   *
   * @param array $params The request parameters (reference).
   * @return void
   */
  abstract protected function formatParams(&$params);

  /**
   * Format response before saving to CRM_AI_DAO_AICompletion
   *
   * @param string $responseString The raw response string.
   * @return array The formatted response data.
   */
  abstract protected function formatResponse($responseString);

  /**
   * Low level function to determine if result in response is an error.
   *
   * @param array $response The response data.
   * @return boolean True if the response contains an error.
   */
  abstract protected function isError($response);
}
