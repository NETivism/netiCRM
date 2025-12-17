<?php

/**
 * This class contains all the AI function that are called by AJAX
 */
class CRM_AI_Page_AJAX {

  // HTTP status code constants
  const HTTP_BAD_REQUEST = 400;
  const HTTP_UNAUTHORIZED = 401;
  const HTTP_FORBIDDEN = 403;
  const HTTP_NOT_FOUND = 404;
  const HTTP_METHOD_NOT_ALLOWED = 405;
  const HTTP_PAYLOAD_TOO_LARGE = 413;
  const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
  const HTTP_UNPROCESSABLE_ENTITY = 422;
  const HTTP_TOO_MANY_REQUESTS = 429;
  const HTTP_INTERNAL_SERVER_ERROR = 500;
  const HTTP_BAD_GATEWAY = 502;
  const HTTP_SERVICE_UNAVAILABLE = 503;
  const HTTP_GATEWAY_TIMEOUT = 504;

  public static function chat() {
    $maxlength = 2000;
    $toneStyle = $aiRole = $context = null;
    $data = [];
    $result = FALSE;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }
      $allowedInput = [
        'tone' => 'string',
        'role' => 'string',
        'content' => 'string',
        'sourceUrlPath' => 'string',
        'sourceUrl' => 'string',
        'sourceUrlQuery' => 'string',
      ];
      $checkFormatResult = self::validateJsonData($jsondata, $allowedInput);
      if (!$checkFormatResult) {
        self::responseError([
          'status' => 0,
          'message' => 'The request does not match the expected format.',
        ]);
      }

      $toneStyle = $jsondata['tone'];
      $data['tone_style'] = $toneStyle;

      $aiRole = $jsondata['role'];
      $data['ai_role'] = $aiRole;

      $context = $jsondata['content'];
      $contextCount = mb_strlen($context);

      if ($contextCount > $maxlength) {
        self::responseError([
          'status' => 0,
          'message' => "Content exceeds the maximum character limit.",
        ]);
      }
      $data['context'] = $context;

      // get url and check component
      $mailTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Email', 'name');
      $url = $jsondata['sourceUrlPath'];

      $allowPatterns = [
        'CiviContribute' => ['civicrm/admin/contribute/add', 'civicrm/admin/contribute/setting'],
        'CiviEvent' => ['civicrm/event/add', 'civicrm/event/manage/eventInfo'],
        'CiviMail' => ['civicrm/mailing/send'],
        'Activity' => ['civicrm/activity/add', 'civicrm/contact/view/activity', 'civicrm/contact/search'],
      ];

      foreach ($allowPatterns as $component => $allowedUrls) {
        foreach ($allowedUrls as $allowedUrl) {
          if (strstr($url, $allowedUrl)) {
            if ($component === "Activity" && (strstr($jsondata['sourceUrl'], "atype=$mailTypeId") || strstr($jsondata['sourceUrl'], "_qf_Email_display"))) {
              $data['component'] = $component;
              break 2;
            } elseif ($component !== "Activity") {
              $data['component'] = $component;
              break 2;
            }
          }
        }
      }
      if (empty($data['component'])) {
        self::responseError([
          'status' => 0,
          'message' => "No corresponding component was found.",
        ]);
      }

      if ($context && $data['component']) {
        $countryId = CRM_Core_Config::singleton()->defaultContactCountry;
        $languages = CRM_Core_PseudoConstant::languages();
        $countries = CRM_Core_PseudoConstant::country();
        global $tsLocale;
        $country = $countries[$countryId];
        $language = $languages[$tsLocale];
        if ($toneStyle && $aiRole) {
          $system_prompt = ts("Please use %4 language of %3 to play the role of %1 and help generate a %2.",
            [1 => $aiRole, 2 => $toneStyle, 3 => $country, 4 => ts($language)]
          );
          $data['prompt'] = [
            [
              'role' => 'user',
              'content' => $system_prompt."\n".$context,
            ],
          ];
        }
        else {
          $data['prompt'] = [
            [
              'role' => 'user',
              'content' => ts('Please using %1 language to generate content.', [2 => ts($language)])."\n".$context,
            ],
          ];
        }
        try {
          $token = CRM_AI_BAO_AICompletion::prepareChat($data);
        }
        catch(CRM_Core_Exception $e) {
          $message = $e->getMessage();
          self::responseError([
            'status' => 0,
            'message' => $message,
          ]);
        }

        if (is_numeric($token['id']) && is_string($token['token'])) {
          $result = TRUE;
          self::responseSucess([
            'status' => 1,
            'message' => 'Chat created successfully.',
            'data' => [
              'id' => $token['id'],
              'token' => $token['token'],
            ]
          ]);
        }
      }
    }
    // When request method is get,Use stream to return ai content
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      if (isset($_GET['token']) && isset($_GET['id']) && is_string($_GET['token']) && is_string($_GET['id'])) {
        $token = $_GET['token'];
        $id = $_GET['id'];
        $params = [
          'token' => $token,
          'id' => $id,
          'stream' => TRUE,
          'temperature' => CRM_AI_BAO_AICompletion::TEMPERATURE_DEFAULT,
        ];
        try{
          $result = CRM_AI_BAO_AICompletion::chat($params);
        }
        catch(CRM_Core_Exception $e) {
          $message = $e->getMessage();

          // Check if the exception message is related to cURL timeout or any cURL errors
          if(strpos($message, "Curl Error") !== false) {
            self::responseSseError([
              'is_error' => 1,
              'message' => 'OpenAI Connect Error'
            ]);
          } else {
            self::responseError([
              'status' => 0,
              'message' => $message,
            ]);
          }
        }
        self::responseSucess([
          'status' => 1,
          'message' => 'Stream chat successfully.',
          'data' => $result,
        ]);
      }
    }
    if (!$result) {
      self::responseError([
        'status' => 0,
        'message' => 'An error occurred during processing. Please verify your input and try again.',
      ]);
    }
  }

  public static function getTemplateList() {
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }
      if (isset($jsondata['component']) && is_string($jsondata['component'])) {
        $component = $jsondata['component'];
        $data['component'] = $component;
      }
      if (isset($jsondata['field']) && is_string($jsondata['field'])) {
        $field = $jsondata['field'];
        $data['field'] = $field;
      }
      if (isset($jsondata['offset']) && is_numeric($jsondata['offset'])) {
        $offset = $jsondata['offset'];
        $data['offset'] = $offset;
      }
      if (isset($jsondata['is_share_with_others']) && is_numeric($jsondata['is_share_with_others'])) {
        $isShared = $jsondata['is_share_with_others'];
      }

      if ($isShared) {
        if (empty($component)) {
          self::responseError([
            'status' => 0,
            'message' => "Component is empty,failed to retrieve template list.",
          ]);
        }

        $sharedData = CRM_AI_BAO_AICompletion::getSharedTemplate($component);
        if (!empty($sharedData)) {
          self::responseSucess([
            'status' => 1,
            'message' => "Template list retrieved successfully.",
            'data' => $sharedData,
          ]);
        }
        else {
          self::responseError([
            'status' => 0,
            'message' => "Failed to retrieve template list.",
          ]);
        }
      }

      if (!empty($data)) {
        $getListResult = CRM_AI_BAO_AICompletion::getTemplateList($data);
      }
      else {
        //Get all template list
        $getListResult = CRM_AI_BAO_AICompletion::getTemplateList();
      }

      if (is_array($getListResult) && !empty($getListResult)) {
        self::responseSucess([
          'status' => 1,
          'message' => "Template list retrieved successfully.",
          'data' => $getListResult,
        ]);
      }
      else {
        self::responseError([
          'status' => 0,
          'message' => "Failed to retrieve template list.",
        ]);
      }
    }
  }

  public static function getTemplate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }
      if (isset($jsondata['id']) && is_numeric($jsondata['id'])) {
        $acId = $jsondata['id'];
      }
      if ($acId) {
        $getTemplateResult = CRM_AI_BAO_AICompletion::getTemplate($acId);
        if (is_array($getTemplateResult) && !empty($getTemplateResult)) {
          self::responseSucess([
            'status' => 1,
            'message' => "Template retrieved successfully.",
            'data' => $getTemplateResult,
          ]);
        }
        else {
          self::responseError([
            'status' => 0,
            'message' => "Failed to retrieve template.",
          ]);
        }
      }
    }
  }

  public static function setTemplate() {
    $result = FALSE;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }
      $allowedInput = [
        'id' => 'integer',
        'is_template' => 'integer',
        'template_title' => 'string',
      ];
      $checkFormatResult = self::validateJsonData($jsondata, $allowedInput);
      if (!$checkFormatResult) {
        self::responseError([
          'status' => 0,
          'message' => 'The request does not match the expected format.',
        ]);
      }
      $acId = $jsondata['id'];
      $data['id'] = $acId;

      $acIsTemplate = $jsondata['is_template'];
      $data['is_template'] = $acIsTemplate;

      $acTemplateTitle = $jsondata['template_title'];
      $data['template_title'] = $acTemplateTitle;

      if (!empty($acId) && !empty($acIsTemplate) && !empty($acTemplateTitle)) {
        $result = [];
        $setTemplateResult = CRM_AI_BAO_AICompletion::setTemplate($data);
        if ($setTemplateResult['is_error'] === 0) {
          $result = TRUE;
          //set or unset template successful return true
          if ($acIsTemplate == "1") {
            //0 -> 1
            $result = [
              'status' => 1,
              'message' => "AI completion is set as template successfully.",
              'data' => [
                'id' => $setTemplateResult['id'],
                'is_template' => $setTemplateResult['is_template'],
                'template_title' => $setTemplateResult['template_title'],
              ],
            ];
          }
          else {
            //  1 -> 0
            $result = [
              'status' => 1,
              'message' => "AI completion is unset as template successfully",
              'data' => [
                'id' => $setTemplateResult['id'],
                'is_template' => $setTemplateResult['is_template'],
                'template_title' => $setTemplateResult['template_title'],
              ],
            ];
          }
          self::responseSucess($result);
        }
      }
    }
    if (!$result) {
      self::responseError([
        'status' => 0,
        'message' => 'An error occurred during processing. Please verify your input and try again.',
      ]);
    }
  }

  public static function setShare() {
    $result = FALSE;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }
      if (isset($jsondata['id']) && is_numeric($jsondata['id'])) {
        $acId = $jsondata['id'];
      }
      if (isset($jsondata['is_share_with_others']) && is_numeric($jsondata['is_share_with_others'])) {
        $acIsShare = $jsondata['is_share_with_others'];
      }
      if (isset($acId) && isset($acIsShare)) {
        $setShareResult = CRM_AI_BAO_AICompletion::setShare($acId);
        $result = [];
        if ($setShareResult) {
          $result = TRUE;
          self::responseSucess([
            'status' => 1,
            'message' => "AI completion is set as shareable successfully.",
            'data' => [
              'id' => $acId,
              'is_template' => $acIsShare,
            ],
          ]);
        }
        else {
          self::responseError([
            'status' => 0,
            'message' => 'AI completion has already been set as shareable.',
          ]);
        }
      }
    }
    if (!$result) {
      self::responseError([
        'status' => 0,
        'message' => 'An error occurred during processing. Please verify your input and try again.',
      ]);
    }
  }

  public static function generateImage() {
    $maxlength = 1000;

    // Only handle POST requests for direct image generation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);

      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ], self::HTTP_BAD_REQUEST);
      }

      $allowedInput = [
        'text' => 'string',
        'style' => 'string',
        'ratio' => 'string',
        'sourceUrlPath' => 'string',
      ];

      $checkFormatResult = self::validateJsonData($jsondata, $allowedInput);
      if (!$checkFormatResult) {
        self::responseError([
          'status' => 0,
          'message' => 'The request does not match the expected format.',
        ], self::HTTP_BAD_REQUEST);
      }

      $text = $jsondata['text'];
      if (mb_strlen($text) > $maxlength) {
        self::responseError([
          'status' => 0,
          'message' => "Content exceeds the maximum character limit.",
        ], self::HTTP_UNPROCESSABLE_ENTITY);
      }

      // URL whitelist check (follow chat() pattern)
      $url = $jsondata['sourceUrlPath'];
      $allowPatterns = [
        'CiviContribute' => ['civicrm/admin/contribute/add', 'civicrm/admin/contribute/setting'],
        'CiviEvent' => ['civicrm/event/add', 'civicrm/event/manage/eventInfo'],
        'CiviMail' => ['civicrm/mailing/send'],
        'Activity' => ['civicrm/activity/add', 'civicrm/contact/view/activity', 'civicrm/contact/search'],
      ];

      $component = '';
      foreach ($allowPatterns as $comp => $allowedUrls) {
        foreach ($allowedUrls as $allowedUrl) {
          if (strstr($url, $allowedUrl)) {
            $component = $comp;
            break 2;
          }
        }
      }

      if (empty($component)) {
        self::responseError([
          'status' => 0,
          'message' => "No corresponding component was found.",
        ], self::HTTP_FORBIDDEN);
      }

      // Use complete AIGenImage workflow with database integration
      // Wrap only the image generation logic, not the response output
      $imageGenerator = new CRM_AI_BAO_AIGenImage();
      $generateResult = null;

      try {
        $generateResult = $imageGenerator->generate([
          'text' => $text,
          'style' => $jsondata['style'] ?? '',
          'ratio' => $jsondata['ratio'] ?? '1:1'
        ]);
      } catch (Exception $e) {
        // Handle image generation errors only
        // Parse error code and preserve original technical message
        $errorMessage = $e->getMessage();
        $errorCode = self::parseErrorCode($errorMessage);
        $statusCode = self::HTTP_INTERNAL_SERVER_ERROR; // Default to 500

        if ($errorCode === 'GATEWAY_TIMEOUT') {
          $statusCode = self::HTTP_GATEWAY_TIMEOUT;
        } elseif ($errorCode === 'BAD_GATEWAY' || $errorCode === 'API_ERROR') {
          $statusCode = self::HTTP_BAD_GATEWAY;
        } elseif ($errorCode === 'VALIDATION_ERROR') {
          $statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        }

        self::responseError([
          'status' => 0,
          'message' => 'Image generation failed: ' . $errorMessage,
          'error_code' => $errorCode
        ], $statusCode);
        return; // Ensure we don't continue after error response
      }

      if ($generateResult && $generateResult['success']) {
        // Create full URL for response
        $baseUrl = rtrim(CIVICRM_UF_BASEURL, '/');
        $publicPath = CRM_Utils_System::cmsDir('public');
        $imageUrl = $baseUrl . '/' . $publicPath . '/' . $generateResult['image_path'];

        // Call responseSucess without try-catch, let civiExit exception propagate to Drupal
        self::responseSucess([
          'status' => 1,
          'message' => 'Image generated successfully.',
          'data' => [
            'image_path' => $generateResult['image_path'],
            'image_url' => $imageUrl,
            'translated_prompt' => $generateResult['translated_prompt'] ?? '',
            'original_prompt' => $generateResult['original_prompt'] ?? '',
            'image_style' => $generateResult['image_style'] ?? '',
            'image_ratio' => $generateResult['image_ratio'] ?? '1:1',
            'advanced' => $generateResult['advanced'] ?? []
          ],
        ]);
      } else {
        // Handle generation failure
        // Parse error code and preserve original technical message
        $errorMessage = $generateResult['error'] ?? 'Unknown error occurred during image generation';
        $errorCode = self::parseErrorCode($errorMessage);
        self::responseError([
          'status' => 0,
          'message' => 'Image generation failed: ' . $errorMessage,
          'error_code' => $errorCode
        ], self::HTTP_BAD_GATEWAY);
      }
    }

    // This should not be reached due to early validation above
    self::responseError([
      'status' => 0,
      'message' => 'Invalid request method or missing data.',
    ], self::HTTP_BAD_REQUEST);
  }

  public static function getSampleImage() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);

      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }

      $allowedInput = [
        'locale' => 'string',
        'style' => 'string',
        'ratio' => 'string',
      ];

      $requiredFields = ['locale'];
      $checkFormatResult = self::validateJsonData($jsondata, $allowedInput, $requiredFields);
      if (!$checkFormatResult) {
        self::responseError([
          'status' => 0,
          'message' => 'The request does not match the expected format.',
        ]);
      }

      $locale = $jsondata['locale'];

      // Validate locale format
      if (!in_array($locale, ['en_US', 'zh_TW'])) {
        self::responseError([
          'status' => 0,
          'message' => 'Invalid locale format.',
        ]);
      }

      // Load sample prompts data using CiviCRM root path
      global $civicrm_root;
      $civicrm_root = rtrim($civicrm_root, DIRECTORY_SEPARATOR);
      $dataPath = $civicrm_root . "/packages/AIImageGeneration/data/{$locale}/defaultPrompts.json";

      if (!file_exists($dataPath)) {
        self::responseError([
          'status' => 0,
          'message' => 'Sample prompts data not found for the specified locale.',
        ]);
      }

      $jsonContent = file_get_contents($dataPath);
      $promptsData = json_decode($jsonContent, true);

      if ($promptsData === NULL || !isset($promptsData['prompts']) || empty($promptsData['prompts'])) {
        self::responseError([
          'status' => 0,
          'message' => 'Invalid or empty sample prompts data.',
        ]);
      }

      // Filter prompts based on optional parameters
      $prompts = $promptsData['prompts'];
      $filteredPrompts = self::filterPrompts($prompts, $jsondata);

      if (empty($filteredPrompts)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'status' => 0,
          'message' => 'No matching sample images found.',
        ]);
        CRM_Utils_System::civiExit();
      }

      // Get random prompt item from filtered results
      $randomIndex = array_rand($filteredPrompts);
      $randomPrompt = $filteredPrompts[$randomIndex];

      // Create image URL
      $config = CRM_Core_Config::singleton();
      $baseUrl = $config->userFrameworkResourceURL;
      $imagePath = "packages/AIImageGeneration/images/samples/{$randomPrompt['filename']}";
      $imageUrl = $baseUrl . $imagePath;

      self::responseSucess([
        'status' => 1,
        'message' => 'Sample image retrieved successfully.',
        'data' => [
          'text' => $randomPrompt['text'],
          'style' => $randomPrompt['style'],
          'ratio' => $randomPrompt['ratio'],
          'filename' => $randomPrompt['filename'],
          'image_url' => $imageUrl,
          'image_path' => $imagePath,
        ],
      ]);
    }

    // If we reach here, it means the request method is not POST or content-type is not JSON
    self::responseError([
      'status' => 0,
      'message' => 'Invalid request method or missing data.',
    ]);
  }

  public static function getImageHistory() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);

      if ($jsondata === NULL) {
        self::responseError([
          'status' => 0,
          'message' => 'The request is not a valid JSON format.',
        ]);
      }

      $allowedInput = [
        'page' => 'integer',
        'per_page' => 'integer',
      ];

      $checkFormatResult = self::validateJsonData($jsondata, $allowedInput, []);
      if (!$checkFormatResult) {
        self::responseError([
          'status' => 0,
          'message' => 'The request does not match the expected format.',
        ]);
      }

      // Get current user ID
      $session = CRM_Core_Session::singleton();
      $currentContactId = $session->get('userID');

      // Verify permission: only current user can view their own records
      if (empty($currentContactId)) {
        self::responseError([
          'status' => 0,
          'message' => 'User not authenticated.',
        ]);
      }

      // Input validation with defaults
      $page = max(1, (int)CRM_Utils_Array::value('page', $jsondata, 1));
      $perPage = min(50, max(1, (int)CRM_Utils_Array::value('per_page', $jsondata, 10)));
      $offset = ($page - 1) * $perPage;

      // Database operations with error handling
      $total = 0;
      $images = [];

      try {
        // Get total count for pagination
        $countQuery = "
          SELECT COUNT(img.id) as total
          FROM civicrm_aiimagegeneration img
          LEFT JOIN civicrm_aicompletion comp ON img.aicompletion_id = comp.id
          WHERE comp.contact_id = %1
            AND img.status_id = %2
        ";
        $countParams = [
          1 => [$currentContactId, 'Integer'],
          2 => [CRM_AI_BAO_AIImageGeneration::STATUS_SUCCESS, 'Integer'],
        ];
        $totalResult = CRM_Core_DAO::executeQuery($countQuery, $countParams);
        $totalResult->fetch();
        $total = (int)$totalResult->total;

        // Get image history data
        $query = "
          SELECT
            img.id,
            img.original_prompt,
            img.translated_prompt,
            img.image_style,
            img.image_ratio,
            img.image_path,
            img.created_date,
            img.status_id,
            comp.contact_id,
            comp.tone_style,
            comp.ai_role
          FROM civicrm_aiimagegeneration img
          LEFT JOIN civicrm_aicompletion comp ON img.aicompletion_id = comp.id
          WHERE comp.contact_id = %1
            AND img.status_id = %2
          ORDER BY img.created_date DESC
          LIMIT %3 OFFSET %4
        ";
        $params = [
          1 => [$currentContactId, 'Integer'],
          2 => [CRM_AI_BAO_AIImageGeneration::STATUS_SUCCESS, 'Integer'],
          3 => [$perPage, 'Integer'],
          4 => [$offset, 'Integer'],
        ];

        $dao = CRM_Core_DAO::executeQuery($query, $params);
        $baseUrl = rtrim(CIVICRM_UF_BASEURL, '/');
        $publicPath = CRM_Utils_System::cmsDir('public');

        while ($dao->fetch()) {
          $imageUrl = !empty($dao->image_path) ? $baseUrl . '/' . $publicPath . '/' . $dao->image_path : '';

          $images[] = [
            'id' => (int)$dao->id,
            'original_prompt' => $dao->original_prompt ?: '',
            'translated_prompt' => $dao->translated_prompt ?: '',
            'image_style' => $dao->image_style ?: '',
            'image_ratio' => $dao->image_ratio ?: '',
            'image_path' => $dao->image_path ?: '',
            'image_url' => $imageUrl,
            'created_date' => $dao->created_date ?: '',
            'status_id' => (int)$dao->status_id,
          ];
        }
      } catch (Exception $e) {
        self::responseError([
          'status' => 0,
          'message' => 'Database error occurred while retrieving image history.',
        ], self::HTTP_INTERNAL_SERVER_ERROR);
        return;
      }

      // Calculate pagination info
      $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;

      // Send response outside of try-catch block
      if (empty($images)) {
        self::responseSucess([
          'status' => 1,
          'message' => 'No image generation history found.',
          'data' => [
            'images' => [],
            'pagination' => [
              'total' => $total,
              'current_page' => $page,
              'per_page' => $perPage,
              'total_pages' => $totalPages,
            ],
          ],
        ]);
      } else {
        self::responseSucess([
          'status' => 1,
          'message' => 'Image history retrieved successfully.',
          'data' => [
            'images' => $images,
            'pagination' => [
              'total' => $total,
              'current_page' => $page,
              'per_page' => $perPage,
              'total_pages' => $totalPages,
            ],
          ],
        ]);
      }
    }

    // If we reach here, it means the request method is not POST or content-type is not JSON
    self::responseError([
      'status' => 0,
      'message' => 'Invalid request method or missing data.',
    ]);
  }

  /**
   * Filter prompts based on style and ratio parameters
   *
   * @param array $prompts Array of prompt items
   * @param array $filters Filter parameters from request
   * @return array Filtered prompt items
   */
  private static function filterPrompts($prompts, $filters) {
    $filteredPrompts = $prompts;

    // Filter by style if provided
    if (isset($filters['style']) && !empty($filters['style'])) {
      $style = $filters['style'];
      $filteredPrompts = array_filter($filteredPrompts, function($prompt) use ($style) {
        return isset($prompt['style']) && $prompt['style'] === $style;
      });
    }

    // Filter by ratio if provided
    if (isset($filters['ratio']) && !empty($filters['ratio'])) {
      $ratio = $filters['ratio'];
      $filteredPrompts = array_filter($filteredPrompts, function($prompt) use ($ratio) {
        return isset($prompt['ratio']) && $prompt['ratio'] === $ratio;
      });
    }

    // Re-index array to ensure array_rand works correctly
    return array_values($filteredPrompts);
  }

  /**
   * This function handles the response in case of an error.
   *
   * @param mixed $error The error message or object that needs to be sent as a response.
   */
  public static function responseError($error, $statusCode = 400) {
    // 405 status code needs special handling for Allow header
    if ($statusCode === self::HTTP_METHOD_NOT_ALLOWED) {
      header('Allow: POST');
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error);
    CRM_Utils_System::civiExit();
  }

  /**
   * This function handles the response in case of an error related to cURL.
   *
   * @param mixed $error The error message or object that needs to be sent as a response.
   */
  public static function responseSseError($error) {
    // Send the error as a SSE formatted message, SSE formatted message does not need to set http response code
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    echo "data: " . json_encode($error) . "\n\n";
    flush();
    CRM_Utils_System::civiExit();
  }

  /**
   * This function handles the response in case of success.
   *
   * @param mixed $data The data that needs to be sent as a response.
   */
  public static function responseSucess($data) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to validate JSON data.
   *
   * This function iterates over the allowed inputs and checks if these inputs exist in the JSON data,
   * and if the type of these inputs matches the expected type. If all inputs exist and are of the correct type,
   * the function will return true; otherwise, it will return false.
   *
   * @param array $jsondata The JSON data to be validated.
   * @param array $allowedInput An associative array where the keys are what we expect to find in the JSON data,
   *                            and the values are the types that these inputs should have.
   * @return bool Returns true if all inputs exist and are of the correct type; otherwise returns false.
   */
  public static function validateJsonData($jsondata, $allowedInput, $requiredFields = []) {
    // If no required fields specified, all fields are required (backward compatibility)
    if (empty($requiredFields)) {
      $requiredFields = array_keys($allowedInput);
    }

    // Check required fields exist
    foreach ($requiredFields as $field) {
      if (!isset($jsondata[$field])) {
        return false;
      }
    }

    // Check type validation for all provided fields
    foreach ($jsondata as $key => $value) {
      if (!isset($allowedInput[$key])) {
        return false; // Unknown field
      }

      $expectedType = $allowedInput[$key];
      if ($expectedType === 'integer' || $expectedType === 'double') {
        if (!is_numeric($value)) {
          return false;
        }
      } else if (gettype($value) != $expectedType) {
        return false;
      }
    }

    return true;
  }

  /**
   * Extract error code from exception message
   *
   * @param string $errorMessage Original error message
   * @return string Error code
   */
  private static function parseErrorCode($errorMessage) {
    // Pattern to extract error codes from AITransPrompt exceptions
    $pattern = '/Prompt translation failed - ([A-Z_]+):/';

    if (preg_match($pattern, $errorMessage, $matches)) {
      return $matches[1]; // Returns: 'CONTENT_VIOLATION', 'PROMPT_INJECTION', etc.
    }

    // Check for timeout errors
    if (strpos($errorMessage, 'timed out') !== false || strpos($errorMessage, 'timeout') !== false) {
      return 'GATEWAY_TIMEOUT';
    }

    // Check for other common error patterns related to API issues
    if (strpos($errorMessage, 'Image generation failed') !== false || strpos($errorMessage, 'API error') !== false) {
      return 'BAD_GATEWAY';
    }

    if (strpos($errorMessage, 'validation') !== false || strpos($errorMessage, 'invalid') !== false) {
      return 'VALIDATION_ERROR';
    }

    return 'UNKNOWN_ERROR';
  }
}