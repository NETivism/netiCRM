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
   * Current generation record ID for tracking database operations
   *
   * @var int
   */
  protected $generationRecordId;

  /**
   * Style mapping for converting user-facing style names to internal style names
   *
   * @var array
   */
  private static $styleMapping = [
    'Simple Illustration' => 'Simple Illustration',
    'Japanese Simple Illustration' => 'Simple Illustration',
    'Storybook Style' => 'Children\'s picture book illustration',
    'Watercolor Painting' => 'Watercolor Painting',
    'Hand-Drawn Illustration' => 'Hand-Drawn Illustration',
  ];

  /**
   * Style prefixes for enhancing prompts based on selected style
   * Each style contains multiple prefix options for random selection
   *
   * @var array
   */
  private static $stylePrefixes = [
    'Simple Illustration' => [
      'Minimalist flat illustration, the character design is a minimalist cartoon style, with minimal facial features',
      'Minimalist flat illustration, the character design is a minimalist style, with minimal facial features',
      'Minimalist flat illustration, minimalist cartoon style',
    ],
    'Japanese Simple Illustration' => [
      'Minimalist flat illustration, the character design is a Japanese minimalist cartoon style, with minimal facial features',
      'Minimalist flat illustration, the character design is a Japanese minimalist style, with minimal facial features',
      'Children\'s picture book illustration in a mid-20th century Japanese style, soft pastel colors, clean bold lines, crayon texture, simple geometric composition, paper texture background, and a warm, minimal atmosphere. The character design is minimalist with minimal facial features',
    ],
    'Storybook Style' => [
      'Children\'s picture book illustration in the mid-20th century Polish style, featuring geometric composition, muted primary colors, paper texture, symbolic shapes, and minimalist folk-inspired characters',
      'Modern reinterpretation of Polish picture book illustration, bold flat colors, strong graphic layout, naive art influence, poster-like composition, warm matte tone',
      'Contemporary children\'s illustration with a humanistic Polish aesthetic,subtle textures, muted palette, expressive yet kind faces,showing compassion and community care in realistic scenes',
      'Children\'s book illustration in a warm Eastern European style, featuring soft geometric shapes, muted earth tones, and handmade textures,depicting empathy, cooperation, and human connection in daily life',
      'Modern Polish picture book illustration, flat geometric design with paper grain texture, featuring children learning and teachers guiding with gentle expression,balanced color composition and thoughtful mood',
      'Dreamlike watercolor illustration in the Polish children\'s book style,symbolic landscapes, surreal proportions, soft color diffusion,folk pattern details, expressive character silhouettes, evoking a poetic and nostalgic feeling',
    ],
    'Watercolor Painting' => [
      'Minimalist flat illustration, the character design is a minimalist cartoon style, with minimal facial features',
      'Minimalist flat illustration, the character design is a minimalist style, with minimal facial features',
      'Modern watercolor interpretation of Polish picture book illustration,combining soft watercolor washes with strong graphic composition,folk-inspired shapes, matte pastel palette, minimal outlines,delicate storytelling mood, vintage children\'s book atmosphere',
      'Modern watercolor interpretation of Polish picture book illustration,combining soft watercolor washes with strong graphic composition,folk-inspired shapes, matte pastel palette, minimal outlines,delicate storytelling mood, vintage children\'s book atmosphere, the character design is a minimalist cartoon style, with minimal facial features',
      'Modern watercolor interpretation of Polish picture book illustration,combining soft watercolor washes with strong graphic composition,folk-inspired shapes, matte pastel palette, minimal outlines,delicate storytelling mood, vintage children\'s book atmosphere, the character design is a minimalist style, with minimal facial features',
    ],
    'Hand-Drawn Illustration' => [
      'A hand-drawn illustration with a nouveau and minimalistic style, minimalist cartoon character design with simplified facial features, textured paper, handmade pencil and crayon strokes of various thicknesses',
      'A hand-drawn illustration with a nouveau and minimalistic style. minimalist cartoon character design with simplified facial features. The background is a white textured paper. The illustration has elegant, handmade pencil and crayon strokes of various thicknesses.',
      'A childlike and naive illustration drawn with thick, waxy oil pastels. The colors are heavily saturated and applied in broad, overlapping strokes, completely covering the canvas. The subject is simple and rendered with a deliberately clumsy, innocent hand. The texture is heavily impasto.',
    ],
  ];

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
   * Main image generation workflow with database integration
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

      // Step 2: Create initial database record
      $this->generationRecordId = $this->createInitialRecord($params);

      // Step 2.5: Process style mapping and text enhancement
      $params = $this->processStyleAndText($params);

      // Step 3: Prompt translation
      $translationResponse = $this->translator->translate($params['text'], [
        'style' => $params['style'] ?? '',
        'ratio' => $params['ratio'] ?? '1:1'
      ]);

      // Extract translated prompt and AI completion ID if available
      $translatedPrompt = $translationResponse;
      $aiCompletionId = null;

      // Handle different response formats from translator
      if (is_array($translationResponse)) {
        $translatedPrompt = $translationResponse['message'] ?? $translationResponse['translated_prompt'] ?? '';
        $aiCompletionId = $translationResponse['id'] ?? $translationResponse['aicompletion_id'] ?? null;

        // Parse JSON response if message contains JSON
        if (!empty($translatedPrompt) && is_string($translatedPrompt)) {
          $parsedData = $this->translator->parseJsonResponse($translatedPrompt);

          if ($parsedData !== false && isset($parsedData['data']['prompt'])) {
            $translatedPrompt = $parsedData['data']['prompt'];
          }
        }
      }

      // Step 4: Update translation result and establish AI completion relationship
      $this->updateTranslationResult($translatedPrompt, $aiCompletionId);

      // Step 5: Call image generation service
      $serviceParams = $this->prepareServiceParams($params, $translatedPrompt);
      $imageData = $this->imageService->generateImage($serviceParams);

      // Step 6: Check for generation errors
      if ($this->imageService->isError($imageData)) {
        throw new Exception('Image generation failed: ' .
          ($imageData['error']['message'] ?? 'Unknown error'));
      }

      // Step 7: Process and store image
      $imagePath = $this->processImage($imageData['data']);

      // Step 8: Update final result after successful image generation and storage
      $this->updateFinalResult($imagePath);

      // Extract advanced parameters from service response
      $advancedParams = $imageData['data']['advanced'] ?? [];

      return [
        'success' => true,
        'image_path' => $imagePath,
        'translated_prompt' => $translatedPrompt,
        'original_prompt' => $params['text'],
        'image_style' => $params['style'] ?? '',
        'image_ratio' => $params['ratio'] ?? '1:1',
        'advanced' => $advancedParams,
        'generation_id' => $this->generationRecordId
      ];

    } catch (Exception $e) {
      // Step 9: Handle errors and update database status
      $this->updateErrorStatus($e->getMessage());
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
   * Process and enhance text prompt based on style requirements
   * Adds random prefix based on original style, then applies style mapping
   *
   * @param array $params Original request parameters
   * @return array Modified parameters with enhanced text and mapped style
    */
  protected function processStyleAndText($params) {
    $modifiedParams = $params;
    $originalStyle = $params['style'] ?? '';

    // Step 1: Add prefix to text based on the original style (before mapping)
    if (!empty($originalStyle) && isset(self::$stylePrefixes[$originalStyle])) {
      $prefixOptions = self::$stylePrefixes[$originalStyle];

      if (!empty($prefixOptions)) {
        // Randomly select one prefix from available options
        $randomIndex = array_rand($prefixOptions);
        $selectedPrefix = $prefixOptions[$randomIndex];

        // Combine prefix with original text
        $originalText = $params['text'] ?? '';
        $modifiedParams['text'] = $selectedPrefix . ', ' . $originalText;
      }
    }

    // Step 2: Apply style mapping if style exists
    if (!empty($originalStyle) && isset(self::$styleMapping[$originalStyle])) {
      $mappedStyle = self::$styleMapping[$originalStyle];
      $modifiedParams['style'] = $mappedStyle;
    }

    return $modifiedParams;
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

    $allowedRatios = ['1:1', '4:3', '3:4', '16:9', '9:16'];
    if (!empty($params['ratio']) && !in_array($params['ratio'], $allowedRatios)) {
      throw new Exception('Invalid ratio. Allowed: ' . implode(', ', $allowedRatios));
    }
  }

  /**
   * Prepare parameters for image generation service
   * Combines standard parameters with advanced service-specific parameters
   *
   * @param array $params Original parameters from user
   * @param string $translatedPrompt Processed prompt text
   * @return array Service parameters ready for generation
   */
  protected function prepareServiceParams($params, $translatedPrompt) {
    // Standard parameters that all services understand
    $serviceParams = [
      'prompt' => $translatedPrompt,
      'ratio' => $params['ratio'] ?? '1:1'
    ];

    // Merge advanced parameters if provided
    // This allows service-specific parameters to be passed through
    if (!empty($params['advanced']) && is_array($params['advanced'])) {
      $serviceParams = array_merge($serviceParams, $params['advanced']);
    }

    return $serviceParams;
  }


  /**
   * Create initial generation record with pending status
   *
   * @param array $params Generation parameters
   * @return int Record ID
   * @throws Exception On database save failure
   */
  protected function createInitialRecord($params) {
    $data = [
      'original_prompt' => $params['text'],
      'image_style' => $params['style'] ?? '',
      'image_ratio' => $params['ratio'] ?? '1:1',
      'status_id' => CRM_AI_BAO_AIImageGeneration::STATUS_PENDING,
      'created_date' => date('Y-m-d H:i:s')
    ];

    $record = CRM_AI_BAO_AIImageGeneration::create($data);
    return $record->id;
  }

  /**
   * Update translation result and establish AI completion relationship
   *
   * @param string $translatedPrompt Translated prompt
   * @param int $aiCompletionId AI completion ID from translation process
   * @throws Exception On database update failure
   */
  protected function updateTranslationResult($translatedPrompt, $aiCompletionId = null) {
    if ($this->generationRecordId) {
      $updateData = [
        'translated_prompt' => $translatedPrompt,
        'status_id' => CRM_AI_BAO_AIImageGeneration::STATUS_PROCESSING
      ];

      // Add AI completion relationship if available
      if ($aiCompletionId) {
        $updateData['aicompletion_id'] = $aiCompletionId;
      }

      CRM_AI_BAO_AIImageGeneration::updateStatus(
        $this->generationRecordId,
        CRM_AI_BAO_AIImageGeneration::STATUS_PROCESSING,
        $updateData
      );
    }
  }

  /**
   * Update final generation result after successful image generation and storage
   * Uses try-catch to prevent database issues from affecting main workflow
   *
   * @param string $imagePath Generated image path (relative to public directory)
   */
  protected function updateFinalResult($imagePath) {
    if ($this->generationRecordId && !empty($imagePath)) {
      try {
        // Verify file actually exists before marking as success
        // Convert relative path to absolute path for file existence check
        $publicDir = rtrim(CRM_Utils_System::cmsDir('public'), '/');
        $fullPath = $publicDir . '/' . $imagePath;

        if (file_exists($fullPath)) {
          CRM_AI_BAO_AIImageGeneration::updateStatus(
            $this->generationRecordId,
            CRM_AI_BAO_AIImageGeneration::STATUS_SUCCESS,
            ['image_path' => $imagePath]
          );
        } else {
          // File doesn't exist, mark as failed but don't throw exception
          $this->updateErrorStatus('Generated image file could not be saved to: ' . $fullPath);
        }
      } catch (Exception $e) {
        // Log database update error but don't propagate to main workflow
        // This prevents double JSON response issue
        CRM_Core_Error::debug_log_message("Database update error in updateFinalResult: " . $e->getMessage());

        // Try to update error status without throwing exception
        try {
          $this->updateErrorStatus('Database update failed: ' . $e->getMessage());
        } catch (Exception $innerE) {
          // Even error status update failed, just log it
          CRM_Core_Error::debug_log_message("Error status update also failed: " . $innerE->getMessage());
        }
      }
    }
  }

  /**
   * Update generation record with error status and message
   * Uses try-catch to prevent database issues from causing additional errors
   *
   * @param string $errorMessage Error message to store
   */
  protected function updateErrorStatus($errorMessage) {
    if ($this->generationRecordId) {
      try {
        CRM_AI_BAO_AIImageGeneration::updateStatus(
          $this->generationRecordId,
          CRM_AI_BAO_AIImageGeneration::STATUS_FAILED,
          ['error_message' => $errorMessage]
        );
      } catch (Exception $e) {
        // Log database update error but don't throw exception
        CRM_Core_Error::debug_log_message("Failed to update error status: " . $e->getMessage());
      }
    }
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