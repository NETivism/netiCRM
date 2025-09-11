<?php

/**
 * Class CRM_AI_BAO_AIGenImage
 *
 * Main business logic controller for AI image generation
 * Integrates prompt translation, image generation, and file processing
 */
class CRM_AI_BAO_AIGenImage {

  /**
   * Prompt translator instance
   *
   * @var CRM_AI_BAO_AITransPrompt
   */
  private $translator;

  /**
   * Image generation service instance
   *
   * @var CRM_AI_GenImageService
   */
  private $imageService;

  /**
   * Configuration instance
   *
   * @var CRM_Core_Config
   */
  private $config;

  /**
   * Constructor with dependency injection
   *
   * @param CRM_AI_BAO_AITransPrompt $translator Optional prompt translator
   * @param CRM_AI_GenImageService $imageService Optional image service
   */
  public function __construct($translator = null, $imageService = null) {
    $this->translator = $translator ?? new CRM_AI_BAO_AITransPrompt();
    $this->imageService = $imageService ?? new CRM_AI_GenImageService_ITRIICL();
    $this->config = CRM_Core_Config::singleton();
  }

  /**
   * Main image generation workflow
   * Orchestrates the complete process from prompt to final image
   *
   * @param array $params Generation parameters
   * @return array Result with success status and image path
   * @throws Exception On validation or processing errors
   */
  public function generate($params) {
    try {
      // Step 1: Parameter validation
      $this->validateInput($params);

      // Step 2: Quota checking
      if (!$this->checkQuota()['available']) {
        throw new Exception('Quota exceeded');
      }

      // Step 3: Prompt translation
      $translatedPrompt = $this->translator->translate($params['text'], [
        'style' => $params['style'] ?? '',
        'ratio' => $params['ratio'] ?? '1:1'
      ]);

      // Step 4: Call image generation service
      $imageData = $this->imageService->generateImage([
        'prompt' => $translatedPrompt,
        'ratio' => $params['ratio'] ?? '1:1'
      ]);

      // Step 5: Check for generation errors
      if ($this->imageService->isError($imageData)) {
        throw new Exception('Image generation failed: ' .
          ($imageData['error']['message'] ?? 'Unknown error'));
      }

      // Step 6: Process and store image
      $imagePath = $this->processImage($imageData['data']);

      // Step 7: Save generation record
      // $this->saveGenerationRecord($params, $imagePath, $translatedPrompt);

      return ['success' => true, 'image_path' => $imagePath];

    } catch (Exception $e) {
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Process and store binary image data to designated directory
   * Handles file naming, directory creation, storage, and WebP conversion
   *
   * @param array $responseData Response data from image service containing format and binary data
   * @return string Relative path to stored image file
   * @throws Exception On file operations failure
   */
  public function processImage($responseData) {
    // Step 1: Validate response data structure
    if (empty($responseData) || !isset($responseData['image_data'])) {
      throw new Exception('Empty or invalid image data received');
    }

    // Step 2: Extract format and binary data
    $format = $responseData['format'] ?? 'png';
    $binaryData = $responseData['image_data'];

    if (empty($binaryData)) {
      throw new Exception('Empty binary image data');
    }

    // Step 2.5: WebP conversion for PNG images
    if ($format === 'png' && function_exists('imagewebp')) {
      $image = imagecreatefromstring($binaryData);
      if ($image !== FALSE) {
        ob_start();
        imagewebp($image, null, 80); // 80% quality for good balance of size/quality
        $webpData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        // Use converted WebP data and update format
        $binaryData = $webpData;
        $format = 'webp';
      }
      // If conversion fails, keep original PNG format and data
    }

    // Step 3: Get upload directory configuration
    $uploadDir = $this->getUploadDirectory();

    // Step 4: Ensure directory exists
    $this->ensureDirectoryExists($uploadDir);

    // Step 5: Generate unique filename with correct extension
    $filename = $this->generateUniqueFilename($format);

    // Step 6: Build full file path
    $fullPath = $uploadDir . '/' . $filename;

    // Step 7: Write binary data to file
    $result = file_put_contents($fullPath, $binaryData);
    if ($result === FALSE) {
      throw new Exception('Failed to write image file to: ' . $fullPath);
    }

    // Step 8: Return relative path for database storage
    return $this->getRelativePath($fullPath);
  }

  /**
   * Validate input parameters for image generation
   *
   * @param array $params Input parameters
   * @throws Exception On validation failure
   */
  public function validateInput($params) {
    if (empty($params['text'])) {
      throw new Exception('Text description is required');
    }

    if (strlen($params['text']) > 1000) {
      throw new Exception('Text description is too long (max 1000 characters)');
    }

    $allowedRatios = ['1:1', '4:3', '16:9'];
    if (!empty($params['ratio']) && !in_array($params['ratio'], $allowedRatios)) {
      throw new Exception('Invalid ratio. Allowed: ' . implode(', ', $allowedRatios));
    }
  }

  /**
   * Check user quota for image generation
   * Basic implementation - can be enhanced with user-specific limits
   *
   * @return array Quota status information
   */
  public function checkQuota() {
    // Basic implementation - always allow for now
    // TODO: Implement actual quota checking logic
    return [
      'available' => true,
      'remaining' => 100,
      'total' => 100
    ];
  }

  /**
   * Save generation record to database
   *
   * @param array $params Original parameters
   * @param string $imagePath Stored image path
   * @param string $translatedPrompt Translated prompt
   */
  private function saveGenerationRecord($params, $imagePath, $translatedPrompt) {
    // TODO: Implement database record saving
    // This will create records in both civicrm_aicompletion and civicrm_aiimagegeneration
  }

  /**
   * Get configured upload directory path
   * Uses configuration or falls back to default
   *
   * @return string Absolute directory path
   */
  private function getUploadDirectory() {
    $configDir = $this->config->aiGenImageUploadDir ?? null;

    if ($configDir && is_dir($configDir)) {
      return $configDir;
    }

    // Default directory path using CMS public directory
    $defaultDir = CRM_Utils_System::cmsDir('public') . '/civicrm/persist/gen-image';
    return $defaultDir;
  }

  /**
   * Ensure directory exists and is writable
   * Creates directory recursively if needed
   *
   * @param string $directory Directory path
   * @throws Exception If directory cannot be created or is not writable
   */
  private function ensureDirectoryExists($directory) {
    if (!is_dir($directory)) {
      if (!mkdir($directory, 0755, true)) {
        throw new Exception('Cannot create upload directory: ' . $directory);
      }
    }

    if (!is_writable($directory)) {
      throw new Exception('Upload directory is not writable: ' . $directory);
    }
  }

  /**
   * Generate unique filename with timestamp and random suffix
   * Supports dynamic format based on processed image format
   *
   * @param string $format Image format (png, webp, jpg, etc.)
   * @return string Unique filename with appropriate extension
   */
  private function generateUniqueFilename($format = 'png') {
    $timestamp = date('Ymd_His');
    $random = substr(md5(uniqid(rand(), true)), 0, 8);
    return "genimg_{$timestamp}_{$random}.{$format}";
  }

  /**
   * Convert absolute path to relative path for database storage
   *
   * @param string $fullPath Absolute file path
   * @return string Relative path from CMS public directory
   */
  private function getRelativePath($fullPath) {
    $publicDir = rtrim(CRM_Utils_System::cmsDir('public'), '/');
    if (strpos($fullPath, $publicDir) === 0) {
      return substr($fullPath, strlen($publicDir) + 1);
    }
    return $fullPath;
  }
}