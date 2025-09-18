<?php

/**
 * Abstract class for AI image generation services
 *
 * Provides unified interface for different image generation providers
 * like ITRI ICL, Stable Diffusion services, etc.
 */
abstract class CRM_AI_GenImageService {

  /**
   * Current AI model name
   *
   * @var string
   */
  protected $model;

  /**
   * Image width setting
   *
   * @var int
   */
  protected $imageWidth;

  /**
   * Image height setting
   *
   * @var int
   */
  protected $imageHeight;

  /**
   * Configuration instance
   *
   * @var CRM_Core_Config
   */
  protected $config;

  /**
   * Constructor
   */
  public function __construct() {
    $this->config = CRM_Core_Config::singleton();
    
    // Set default image size (1:1 ratio)
    $this->imageWidth = 1024;
    $this->imageHeight = 1024;
  }

  /**
   * Set AI model for image generation
   *
   * Should set to default model when provided model name not available
   *
   * @param string $model Model name
   * @return string The actual model name set
   */
  abstract public function setModel($model);

  /**
   * Set image dimensions
   *
   * Should validate and adjust dimensions according to service limitations
   *
   * @param int $width Image width in pixels
   * @param int $height Image height in pixels
   * @return array The actual dimensions set ['width' => int, 'height' => int]
   */
  abstract public function setImageSize($width, $height);

  /**
   * Generate image using AI service
   *
   * Main entry point for image generation process
   * 
   * @param array $params Standardized parameters:
   *   - prompt: string - Main description for image generation
   *   - negative_prompt: string (optional) - What to avoid
   *   - width: int (optional) - Image width
   *   - height: int (optional) - Image height  
   *   - style_params: array (optional) - Service-specific parameters
   *
   * @return array Standard response format:
   *   Success: ['success' => true, 'data' => ['image_data' => binary, 'format' => string]]
   *   Error: ['success' => false, 'error' => ['code' => string, 'message' => string]]
   */
  abstract public function generateImage($params);

  /**
   * Format parameters before sending to service API
   *
   * Convert standardized parameters to service-specific format
   * Add default values and handle service-specific requirements
   *
   * @param array $params Input parameters (by reference)
   * @return void
   */
  abstract protected function formatParams(&$params);

  /**
   * Format response from service API
   *
   * Convert service-specific response to standardized format
   * Handle image data processing and temporary storage
   *
   * @param string $responseString Raw response from service
   * @return array Standardized response format
   */
  abstract protected function formatResponse($responseString);

  /**
   * Determine if response indicates an error
   *
   * Service-specific error detection logic
   *
   * @param array $response Parsed response data
   * @return bool True if response indicates error
   */
  abstract public function isError($response);

  /**
   * Validate input parameters
   *
   * Common validation logic for all services
   *
   * @param array $params Input parameters
   * @return bool True if parameters are valid
   * @throws InvalidArgumentException If validation fails
   */
  protected function validateParams($params) {
    // Check required prompt parameter
    if (empty($params['prompt']) || !is_string($params['prompt'])) {
      throw new InvalidArgumentException('Prompt is required and must be a string');
    }

    // Validate prompt length (basic check)
    $prompt = trim($params['prompt']);
    if (strlen($prompt) === 0) {
      throw new InvalidArgumentException('Prompt cannot be empty');
    }

    // Validate dimensions if provided
    if (isset($params['width']) && (!is_int($params['width']) || $params['width'] <= 0)) {
      throw new InvalidArgumentException('Width must be a positive integer');
    }

    if (isset($params['height']) && (!is_int($params['height']) || $params['height'] <= 0)) {
      throw new InvalidArgumentException('Height must be a positive integer');
    }

    return true;
  }

  /**
   * Create standardized error response
   *
   * @param string $code Error code
   * @param string $message Error message
   * @param mixed $details Additional error details (optional)
   * @return array Error response format
   */
  protected function createErrorResponse($code, $message, $details = null) {
    $error = [
      'success' => false,
      'error' => [
        'code' => $code,
        'message' => $message
      ]
    ];

    if ($details !== null) {
      $error['error']['details'] = $details;
    }

    return $error;
  }

  /**
   * Create standardized success response
   *
   * @param array $data Response data
   * @return array Success response format
   */
  protected function createSuccessResponse($data) {
    return [
      'success' => true,
      'data' => $data
    ];
  }

  /**
   * Get current image dimensions
   *
   * @return array Current dimensions ['width' => int, 'height' => int]
   */
  public function getImageSize() {
    return [
      'width' => $this->imageWidth,
      'height' => $this->imageHeight
    ];
  }

  /**
   * Get current model name
   *
   * @return string Current model name
   */
  public function getModel() {
    return $this->model;
  }
}