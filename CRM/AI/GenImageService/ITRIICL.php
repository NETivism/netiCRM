<?php

/**
 * ITRI ICL AI Image Generation Service
 *
 * Provides image generation using ITRI's ICL (Industrial Computer Laboratory) API
 * Supports Stable Diffusion 3.5 model for high-quality image generation
 */
class CRM_AI_GenImageService_ITRIICL extends CRM_AI_GenImageService {

  const DEFAULT_TIMEOUT = 90;

  private $apiKey;
  private $endpoint;
  private $timeout;

  /**
   * Constructor
   *
   * Initialize service with configuration from CiviCRM settings
   */
  public function __construct() {
    parent::__construct();

    // Load configuration from CiviCRM config object
    $this->apiKey = $this->config->itriIclApiKey ?? '';
    $this->endpoint = $this->config->itriIclEndpoint ?? '';
    $this->timeout = $this->config->itriIclTimeout ?? self::DEFAULT_TIMEOUT;

    // Validate required configuration
    if (empty($this->apiKey)) {
      throw new Exception('ITRI ICL API Key is not configured');
    }

    if (empty($this->endpoint)) {
      throw new Exception('ITRI ICL API Endpoint is not configured');
    }
  }

  /**
   * Set AI model for image generation
   * ITRI ICL currently only supports Stable Diffusion 3.5
   *
   * @param string $model Model name (will be set to default)
   * @return string The actual model name set
   */
  public function setModel($model) {
    // ITRI ICL currently only supports Stable Diffusion 3.5
    $this->model = 'stable-diffusion-3.5';
    return $this->model;
  }

  /**
   * Set image dimensions
   * Validate and adjust dimensions according to ITRI ICL limitations
   *
   * @param int $width Image width in pixels
   * @param int $height Image height in pixels
   * @return array The actual dimensions set
   */
  public function setImageSize($width, $height) {
    // Basic dimension validation and setting
    $this->imageWidth = $width ?? 832;
    $this->imageHeight = $height ?? 832;

    return [
      'width' => $this->imageWidth,
      'height' => $this->imageHeight
    ];
  }

  /**
   * Generate image using ITRI ICL API
   * Main entry point for image generation process
   *
   * @param array $params Generation parameters
   * @return array Standard response format
   */
  public function generateImage($params) {
    try {
      // Step 1: Basic parameter validation
      $this->validateParams($params);

      // Step 2: Format parameters for ITRI ICL
      $this->formatParams($params);

      // Step 3: Build HTTP request
      $requestData = $this->formatRequest($params);

      // Step 4: Execute API call
      $response = $this->executeRequest($requestData);

      // Step 5: Format response (minimal version)
      return $this->formatResponse($response);

    } catch (Exception $e) {
      return $this->createErrorResponse('api_error', $e->getMessage());
    }
  }

  /**
   * Format parameters before sending to ITRI ICL API
   * Convert standardized parameters to service-specific format
   *
   * @param array $params Input parameters (by reference)
   * @return void
   */
  protected function formatParams(&$params) {
    // Set basic default parameters for ITRI ICL
    $params['negative_prompt'] = $params['negative_prompt'] ?? '';
    $params['steps'] = $params['steps'] ?? 30;
    $params['cfg'] = $params['cfg'] ?? 7;
    $params['sampler'] = $params['sampler'] ?? 'dpmpp_2m';

    // Set seed parameter - user can customize, defaults to random generation
    $params['seed'] = $params['seed'] ?? $this->generateRandomSeed();

    // Process ratio conversion to specific dimensions
    if (!empty($params['ratio'])) {
      $this->processRatio($params['ratio']);
    }
  }

  /**
   * Format response from ITRI ICL API
   * Convert service response to standardized format
   *
   * @param string $responseString Raw response from service
   * @return array Standardized response format
   */
  protected function formatResponse($responseString) {
    // Minimal version: direct return of raw data
    if (empty($responseString)) {
      return $this->createErrorResponse('empty_response', 'No data received from API');
    }

    return $this->createSuccessResponse([
      'image_data' => $responseString,
      'format' => 'png',
      'size' => strlen($responseString)
    ]);
  }

  /**
   * Determine if response indicates an error
   * Simple error detection logic
   *
   * @param array $response Parsed response data
   * @return bool True if response indicates error
   */
  public function isError($response) {
    return !is_array($response) || !isset($response['success']) || $response['success'] !== true;
  }

  /**
   * Format HTTP request for ITRI ICL API
   * Build request payload and headers
   *
   * @param array $params Generation parameters
   * @return array Request data structure
   */
  public function formatRequest($params) {
    $requestPayload = [
      'prompt' => $params['prompt'],
      'negative_prompt' => $params['negative_prompt'],
      'steps' => $params['steps'],
      'cfg' => $params['cfg'],
      'sampler' => $params['sampler'],
      'seed' => $params['seed'],
      'width' => $this->imageWidth,
      'height' => $this->imageHeight
    ];

    return [
      'url' => $this->endpoint,
      'headers' => [
        'accept: image/png',
        'Authorization: Bearer ' . $this->apiKey,
        'Content-Type: application/json'
      ],
      'payload' => json_encode($requestPayload)
    ];
  }

  /**
   * Execute HTTP request to ITRI ICL API
   * Handle cURL communication with error checking
   *
   * @param array $requestData Request data structure
   * @return string Raw response from API
   * @throws Exception On request failure
   */
  private function executeRequest($requestData) {
    $ch = curl_init($requestData['url']);

    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $requestData['payload'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $requestData['headers'],
      CURLOPT_TIMEOUT => $this->timeout,
      CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
      throw new Exception("cURL Error: {$curlError}");
    }

    if ($httpCode !== 200) {
      throw new Exception("HTTP Error: {$httpCode}");
    }

    return $response;
  }

  /**
   * Generate random seed for image generation
   * Range: 0 to 4294967294 (based on Stability AI reference)
   * Reference: https://colab.research.google.com/github/stability-ai/stability-sdk/blob/main/nbs/Stable_Image_API_Public.ipynb
   *
   * @return int Random seed value
   */
  private function generateRandomSeed() {
    return mt_rand(0, 4294967294);
  }

  /**
   * Process ratio setting to specific dimensions
   * Convert ratio strings to width/height values
   *
   * @param string $ratio Ratio string (e.g., "1:1", "4:3", "16:9")
   * @return void
   */
  private function processRatio($ratio) {
    $ratioMap = [
      '1:1' => ['width' => 832, 'height' => 832],
      '4:3' => ['width' => 832, 'height' => 640],
      '3:4' => ['width' => 640, 'height' => 832],
      '16:9' => ['width' => 832, 'height' => 448],
      '9:16' => ['width' => 448, 'height' => 832]
    ];

    if (isset($ratioMap[$ratio])) {
      $this->setImageSize($ratioMap[$ratio]['width'], $ratioMap[$ratio]['height']);
    }
  }
}