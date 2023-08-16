<?php
class CRM_AI_BAO_AICompletion extends CRM_AI_DAO_AICompletion {

  const
    // default completion service
    COMPLETION_SERVICE = 'OpenAI',
    // default model base on above service
    COMPLETION_MODEL = 'gpt-3.5-turbo',
    // default max tokens base on model
    COMPLETION_MAX_TOKENS = 4096,

    TEMPLATE_LIST_ROW_LIMIT = 10,

    // temperature TODO: add client side adjustment
    TEMPERATURE_DEFAULT = 0.7,

    // Action:
    CHAT_COMPLETION = 1,
    GET_TOKEN = 2,

    // Status:
    STATUS_PENDING = 2,
    STATUS_SUCCESS = 1,

    // Fallback Component
    COMPONENT = 'Activity';
  /**
   * What action is execute now
   *
   * @var int
   */
  private static $_action;

  /**
   * Max tokens user define which should use base service model max tokens
   *
   * @var int
   */
  public $_maxToken = NULL;

  /**
   * completion class object which will handilng real http request ther
   *
   * @var CRM_AI_Completion
   */
  public $_serviceProvider;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * This is a static function that returns a reference to a singleton instance of a class.
   *
   * @param string $serviceProvider (optional) The service provider to use for the singleton instance.
   * @param string $model (optional) The model to use for the singleton instance.
   * @param int $maxTokens (optional) The maximum number of tokens to use for the singleton instance.
   *
   * @return object A reference to the singleton instance of the class.
   */
  static function &singleton($serviceProvider = NULL, $model = NULL, $maxTokens = NULL) {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_AI_BAO_AICompletion($serviceProvider, $model, $maxTokens);
    }
    return self::$_singleton;
  }

  /**
   * Saving chat parameters to DB, return the encrypted text about id.
   *
   * @param array $params ['ai_role', 'tone_style', 'context', 'prompt']
   *
   * @return array ['token', 'id]
   */
  public static function prepareChat($params = array()) {
    // prepare saving data.
    $aicompletionData = $params;
    if (!isset($params['prompt'])) {
      throw new CRM_Core_Exception("Missing for prompt parameters");
    }
    else {
      if (is_array($params['prompt'])) {
        // If Prompt is not String, use json_encode.
        $aicompletionData['prompt'] = json_encode($params['prompt']);
      }
    }
    $aicompletionData['created_date'] = date('Y-m-d H:i:s');
    $aicompletionData['status_id'] = self::STATUS_PENDING;
    $session = CRM_Core_Session::singleton();
    $aicompletionData['contact_id'] = $session->get('userID');
    // save data to DB
    $aicompletion = self::create($aicompletionData);
    // Get token for validation.
    $keyToken = CRM_Core_Key::get('aicompletion_'.$aicompletion->id);
    // prepare return array.
    return [
      'token' => $keyToken,
      'id' => $aicompletion->id,
    ];
  }

  /**
   * High level function to call AI completion and save records into db
   *
   * Use this for making request and save data.
   * Use getCompletion() for send request only
   *
   * @param array $params [
   *   'id': The data that retrive from DB.
   *   'token': Used to validate 'id'.
   *   'prompt': The prompt straightly send to API. If use prompt, we don't need 'id' and 'token'.
   *   'model'
   *   'max_tokens'
   *   'stream': Boolean, is the cURL stream or not.
   * ]
   *
   * @return array result array.
   */
  public static function chat($params = array()) {

    // Prepare follow parameters will be used.
    $requestData = self::validateChatParams($params);
    $requestData['action'] = self::CHAT_COMPLETION;

    // Send request to OpenAI API
    $responseData = CRM_AI_BAO_AICompletion::getCompletion($requestData);

    // Save response data to db record
    if (isset($requestData['id'])) {
      $data = array_merge($requestData, $responseData);
      $data['id'] = $requestData['id'];
      $data['output_text'] = $responseData['message'];
      self::create($data);
    }

    return $responseData;
  }

  /**
   * Validates chat parameters.
   *
   * @param array $params The parameters to validate.
   *
   * @return array The validated parameters.
   *
   * @throws Exception If the validation fails or required parameters are missing.
   */
  private static function validateChatParams($params) {
    $aicompletion = array();
    $isPass = FALSE;

    // If prompt is in $params, just use it.
    if (isset($params['prompt'])) {
      if (is_array($params['prompt'])) {
        $aicompletion['prompt'] = json_encode($params['prompt']);
      }
      else {
        $aicompletion['prompt'] = $params['prompt'];
      }
      $isPass = TRUE;
    }

    if (isset($params['id'])) {
      $acID = $aicompletion['id'] = $params['id'];
      // If there are 'token' in $params, validate the 'token' and assiociated 'id' is correct.
      if (isset($params['token'])) {
        $key = $params['token'];
        $getKey = CRM_Core_Key::validate($key, 'aicompletion_'.$acID);
        $isKeyPass = ($getKey == $key);
        if ($isKeyPass) {
          $isPass = TRUE;
          $aiCompletionArray = self::retrieveAICompletionDataArray($acID);
          $aicompletion = array_merge($aicompletion, $aiCompletionArray);
        }
        else {
          throw new CRM_Core_Exception('Invalid token.');
        }
      }
      else {
        // if there are no token, let is pass anyway.
        $isPass = TRUE;
        $aiCompletionArray = self::retrieveAICompletionDataArray($acID);
        $aicompletion = array_merge($aicompletion, $aiCompletionArray);
      }
    }
    // Get all the keys from the $params array
    $paramsKeys = array_keys($params);
    if ($isPass) {
      $dontCopyParams = ['prompt', 'id', 'token'];
      $copyKeys = array_diff($paramsKeys, $dontCopyParams);
      foreach ($copyKeys as $key) {
        $aicompletion[$key] = $params[$key];
      }
    }
    else {
      if (!isset($params['id']) && !isset($params['token'])) {
        $requiredKeys = ['id', 'token'];
        $lackKeys = array_diff($requiredKeys, $paramsKeys);
        throw new CRM_Core_Exception('Missing required parameters: ' . implode(', ', $lackKeys));
      }
      else {
        throw new CRM_Core_Exception('Doesn\'t pass the validation.');
      }
    }
    return $aicompletion;
  }

  /**
   * Usage information
   *
   * @return array
   */
  public static function quota() {
    $config = CRM_Core_Config::singleton();
    $used = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_aicompletion WHERE created_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01 00:00:00') AND created_date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00');");
    $percent = $used < $config->openAICompletionQuota ? floor(($used/$config->openAICompletionQuota)*100) : 100;

    return array(
      'max' => $config->openAICompletionQuota,
      'used' => $used,
      'percent' => $percent,
    );
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params      (reference) an assoc array of name/value pairs
   * @param array $defaults    (reference) an assoc array to hold the flattened values
   *
   * @return object   CRM_Core_DAO_UFGroup object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_AI_DAO_AICompletion', $params, $defaults);
  }

  /**
   * Only use for database record saving
   *
   * @return object
   */
  public static function create(&$data) {
    if (!is_array($data)) {
      throw new CRM_Core_Exception("data should be an Array.");
    }
    $id = CRM_Utils_Array::value('id', $data);
    if (!$id) {
      $op = 'create';
    }
    else {
      $op = 'edit';
    }
    if (empty($data['contact_id'])) {
      $session = CRM_Core_Session::singleton();
      $data['contact_id'] = $session->get('userID') ? $session->get('userID') : NULL;
    }
    CRM_Utils_Hook::pre($op, 'AICompletion', $id, $data);
    $aicompletion = new CRM_AI_DAO_AICompletion();
    $aicompletion->copyValues($data);
    $aicompletion->save();

    CRM_Utils_Hook::post($op, 'AICompletion', $aicompletion->id, $aicompletion);

    $params = array(
      'id' => $aicompletion->id,
    );
    $defaults = array();
    $aicompletion = self::retrieve($params, $defaults);
    return $aicompletion;
  }

  /**
   * Retrieve AI Completion data array by ID.
   *
   * @param int $aiCompletionID The ID of the AI Completion.
   * @return array The retrieved AI Completion data array.
   */
  private static function retrieveAICompletionDataArray($aiCompletionID) {
    if (empty($aiCompletionID)) {
      throw new CRM_Core_Exception("\$aiCompletionID has no value.");
    }
    elseif (!is_numeric($aiCompletionID)) {
      throw new CRM_Core_Exception("\$aiCompletionID is not number.");
    }
    $params = array(
      'id' => $aiCompletionID,
    );
    $returnArray = array();
    self::retrieve($params, $returnArray);
    return $returnArray;
  }

  /**
   * Verify data structure before saving into database
   *
   * @return void
   */
  public static function validateData() {
  }


  /**
   * Constructor for the AI Completion class.
   *
   * @param string|null $serviceProvider The service provider for AI completion. Default is NULL.
   * @param string|null $model The model for AI completion. Default is NULL.
   * @param int|null $maxTokens The maximum number of tokens for AI completion. Default is NULL.
   */
  public function __construct($serviceProvider = NULL, $model = NULL, $maxTokens = NULL) {
    if (empty($serviceProvider)) {
      $serviceProvider = self::COMPLETION_SERVICE;
    }
    if (empty($model)) {
      $model = self::COMPLETION_MODEL;
    }
    if (empty($maxTokens)) {
      $maxTokens = self::COMPLETION_MAX_TOKENS;
    }
    elseif (!is_numeric($maxTokens)) {
      throw new CRM_Core_Exception("maxTokens should be a integer number.");
    }
    $serviceProvider = preg_replace('/[^a-zA-Z0-9_]/', '', $serviceProvider);
    $className = 'CRM_AI_CompletionService_'.$serviceProvider;
    if (class_exists($className)) {
      $this->_serviceProvider = new $className();
      $this->_serviceProvider->setModel($model);
      $this->_serviceProvider->setMaxTokens($maxTokens);
    }
    else {
      throw new CRM_Core_Exception(" Class `CRM_AI_CompletionService_$serviceProvider` doesn't existed.");
    }
  }

  /**
   * Low level function to send request via $this->_serviceProvider
   *
   * We should use chat() for fetching and saving result in most case
   *
   * @return FALSE|array
   *  The return array should be compatible for function create
   */
  public static function getCompletion($params) {
    if (!is_array($params)) {
      throw new CRM_Core_Exception("params should be an Array.");
    }
    $model = isset($params['model']) ? $params['model'] : self::COMPLETION_MODEL;
    $maxToken = isset($params['max_token']) ? $params['max_token'] : self::COMPLETION_MAX_TOKENS;
    $completion = self::singleton(self::COMPLETION_SERVICE, $model, $maxToken);
    $params['temperature'] = isset($params['temperature']) ? $params['temperature'] : self::TEMPERATURE_DEFAULT;
    $result = $completion->_serviceProvider->request($params);
    // format result
    return $result;
  }

  /**
   * Retrieve AICompletion Template object(array) by AICompletion ID.
   *
   * @param Int $acID The AICompletion ID in DB row.
   *
   * @return FALSE|array AICompletion data row.
   */
  public static function getTemplate($acID) {
    $retrieveAICompletionArray = self::retrieveAICompletionDataArray($acID);
    return $retrieveAICompletionArray;
  }

  /**
   * Retrieve certain quantity of aicompletion data rows which 'is_template' = 1.
   * @param array $param [
   *   "component" => the component of records.
   *   "field" => the page of the record.
   *   "offset" => the offset of records.
   * ]
   *
   * @return array AICompletion data rows.
   */
  public static function getTemplateList($params = array()) {
    if (!is_array($params)) {
      throw new CRM_Core_Exception("params should be an Array.");
    }
    $whereClause = [];
    $sqlParams = [];
    $whereClause[] = 'is_template = 1';
    if (isset($params['component'])) {
      $whereClause[] = "component = %1";
      $sqlParams[1] = array($params['component'], 'String');
    }
    if (isset($params['field'])) {
      $whereClause[] = "field = %2";
      $sqlParams[2] = array($params['field'], 'String');
    }

    $sql = "SELECT * FROM civicrm_aicompletion WHERE ".implode(' AND ', $whereClause);
    if (!empty($params['limit'])) {
      $sql .= " LIMIT ".self::TEMPLATE_LIST_ROW_LIMIT;
    }
    if (!empty($params['offset']) && is_numeric($params['offset'])) {
      $sql .= " OFFSET ".$params['offset'];
    }
    if (empty($sqlParams)) {
      $dao = CRM_Core_DAO::executeQuery($sql);
    }
    else {
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
    // retrieve all AICompletion values to assoc data.
    $return = [];
    $fields = CRM_AI_DAO_AICompletion::fields();
    while ($dao->fetch()) {
      $aiCompletionData = array();
      // do not call retrieve again for save database load
      foreach ($fields as $field) {
        $dbName = $field['name'];
        if (isset($dao->$dbName) && $dao->$dbName !== 'null') {
          $aiCompletionData[$dbName] = $dao->$dbName;
        }
      }
      $return[] = $aiCompletionData;
    }
    return $return;
  }

  /**
   * Set is_template value to 1 for AICompletion data by ID.
   *
   * @param array $data [
   *   'id' => AICompletion ID
   *   "is_template": 1 or 0
   *   "template_title": "Template title"
   * ]
   *
   * @return array Result data array.
   */
  public static function setTemplate($data) {
    foreach($data as $key => $val) {
      if (!in_array($key, ['id', 'is_template', 'template_title'])) {
        unset($data[$key]);
      }
    }
    $acId = $data['id'];
    $isSuccess = FALSE;
    // Set is template.
    $setTemplateValue = $data['is_template'];
    $originalIsTemplate = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_template');
    $msg = array();
    if ($originalIsTemplate != $setTemplateValue) {
      if (CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'id')) {
        $isSuccess = CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_template', $setTemplateValue);
      }
      else {
        $msg[] = "Data of the ID doesn't existed.";
      }
    }
    else {
      $msg[] = "The value of is_template is already `{$setTemplateValue}`";
    }
    // set template title.
    if ($setTemplateValue) {
      $originalTitle = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'template_title');
      $setTemplateTitle = $data['template_title'];
      if ($originalTitle != $setTemplateTitle) {
        $isSuccess = CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'template_title', $setTemplateTitle);
      }
      else {
        $msg[] = "Template title is already `{$setTemplateTitle}`";
      }
    }
    else {
      // The case is_template is set to 0, then clear template_title anyway.
      CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'template_title', '');
    }

    $returnData = $data;
    $returnData['is_error'] = $isSuccess ? 0 : 1;
    $returnData['message'] = implode('', $msg);

    return $returnData;
  }

  /**
   * Set is_share_with_others value to 1 for AICompletion data by ID.
   * @param int $acId The ID of AiCompletion data.
   *
   * @return int Return 1 when set successfully. 0 when failed. -1 for already
   */
  public static function setShare($acId) {
    $isShare = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others');
    if ($isShare == 0) {
      return CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others', 1) ? 1 :0;
    }
    else {
      return -1;
    }
    return 0;
  }

  public static function getDefaultTemplate($component) {
    global $tsLocale;
    $session = CRM_Core_Session::singleton();
    $smarty = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();
    $enabledComponents = CRM_Core_Component::getEnabledComponents();

    // sort name
    $contactId = $session->get('userID');
    $sortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'sort_name');
    $smarty->assign('sort_name', $sortName);

    // org intro
    $smarty->assign('org_intro', $config->aiOrganizationIntro);

    // usage
    $quota = self::quota();
    $smarty->assign('usage', $quota);

    $suffix = '-1';
    if ($tsLocale !== CRM_Core_Config::SYSTEM_LANG) {
      $locale = '.'.$tsLocale;
    }
    if (!isset($enabledComponents[$component])) {
      $component = 'Activity';
    }
    $path = 'CRM/AI/defaults/'.$component.$suffix.$locale.'.tpl';
    $default = $smarty->fetch($path);
    $verified = json_decode($default);
    if (!$verified) {
      // fallback to non-locale template
      $path = 'CRM/AI/defaults/'.$component.$suffix.'.tpl';
      $default = $smarty->fetch($path);
    }
    return $default;
  }

  public static function getSharedTemplate($component) {
    global $tsLocale;
    $smarty = CRM_Core_Smarty::singleton();
    $enabledComponents = CRM_Core_Component::getEnabledComponents();

    $suffix = '-1';
    if ($tsLocale !== CRM_Core_Config::SYSTEM_LANG) {
      $locale = '.'.$tsLocale;
    }
    if (!isset($enabledComponents[$component])) {
      $component = 'Activity';
    }
    $path = 'CRM/AI/shared/'.$component.$suffix.$locale.'.tpl';
    $shared = $smarty->fetch($path);
    $decodedShared = json_decode($shared, true);

    if (!$decodedShared) {
      // fallback to non-locale template
      $path = 'CRM/AI/shared/'.$component.$suffix.'.tpl';
      $shared = $smarty->fetch($path);
      $decodedShared = json_decode($shared, true);
    }

    return $decodedShared;
  }
}