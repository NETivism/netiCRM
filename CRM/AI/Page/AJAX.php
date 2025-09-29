<?php

/**
 * This class contains all the AI function that are called by AJAX
 */
class CRM_AI_Page_AJAX {

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
        ]);
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
        ]);
      }

      $text = $jsondata['text'];
      if (mb_strlen($text) > $maxlength) {
        self::responseError([
          'status' => 0,
          'message' => "Content exceeds the maximum character limit.",
        ]);
      }

      // URL whitelist check (follow chat() pattern)
      $url = $jsondata['sourceUrlPath'];
      $allowPatterns = [
        'CiviContribute' => ['civicrm/admin/contribute/add', 'civicrm/admin/contribute/setting'],
        'CiviEvent' => ['civicrm/event/add', 'civicrm/event/manage/eventInfo'],
        'CiviMail' => ['civicrm/mailing/send'],
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
        ]);
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
        self::responseError([
          'status' => 0,
          'message' => 'Image generation failed: ' . $e->getMessage(),
        ]);
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
          ],
        ]);
      } else {
        // Handle generation failure
        self::responseError([
          'status' => 0,
          'message' => 'Image generation failed: ' . ($generateResult['error'] ?? 'Unknown error occurred during image generation'),
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
   * This function handles the response in case of an error.
   *
   * @param mixed $error The error message or object that needs to be sent as a response.
   */
  public static function responseError($error) {
    http_response_code(400);
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
  public static function validateJsonData($jsondata, $allowedInput) {
    foreach ($allowedInput as $key => $type) {
      if (!isset($jsondata[$key])) {
        return false;
      }
      if ($type === 'integer' || $type === 'double') {
        if (!is_numeric($jsondata[$key])) {
          return false;
        }
      } else if (gettype($jsondata[$key]) != $type) {
        return false;
      }
    }
    return true;
  }
}