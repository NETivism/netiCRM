<?php
class CRM_AI_BAO_AICompletion extends CRM_AI_DAO_AICompletion {

  public const
    // default completion service
    COMPLETION_SERVICE = 'OpenAI',
    // default model base on above service
    COMPLETION_MODEL = 'gpt-5.6-terra',
    // default max tokens base on model
    COMPLETION_MAX_TOKENS = 4096,

    TEMPLATE_LIST_ROW_LIMIT = 10,

    // Every turn resends the whole conversation, so both the cost and the size
    // of post_data grow with each one. Cap the thread and ask the user to start
    // a new conversation instead.
    CONVERSATION_MAX_TURNS = 20,

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
   * @var CRM_AI_CompletionService
   */
  public $_serviceProvider;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_AI_BAO_AICompletion|null
   * @static
   */
  private static $_singleton = NULL;

  /**
   * This is a static function that returns a reference to a singleton instance of a class.
   *
   * @param string $serviceProvider (optional) The service provider to use for the singleton instance.
   * @param string $model (optional) The model to use for the singleton instance.
   * @param int $maxTokens (optional) The maximum number of tokens to use for the singleton instance.
   *
   * @return CRM_AI_BAO_AICompletion A reference to the singleton instance of the class.
   */
  public static function &singleton($serviceProvider = NULL, $model = NULL, $maxTokens = NULL) {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_AI_BAO_AICompletion($serviceProvider, $model, $maxTokens);
    }
    return self::$_singleton;
  }

  /**
   * Saving chat parameters to DB, return the encrypted text about id.
   *
   * @param array $params The input chat parameters. ['ai_role', 'tone_style', 'context', 'prompt', 'conversation_id']
   *
   * @return array The prepared chat session data. ['token', 'id', 'conversation_id']
   *
   * @throws CRM_Core_Exception
   */
  public static function prepareChat($params = []) {
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
    // A conversation is identified by the id of its own first row, so the very
    // first turn can only be stamped after the insert gave us that id.
    // Follow up turns already carry the value through $aicompletionData.
    $conversationId = $aicompletion->conversation_id;
    if (empty($conversationId)) {
      $conversationId = $aicompletion->id;
      CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $aicompletion->id, 'conversation_id', $conversationId);
    }
    // Get token for validation.
    $keyToken = CRM_Core_Key::get('aicompletion_'.$aicompletion->id);
    // prepare return array.
    return [
      'token' => $keyToken,
      'id' => $aicompletion->id,
      'conversation_id' => $conversationId,
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
  public static function chat($params = []) {

    // Prepare follow parameters will be used.
    $requestData = self::validateChatParams($params);
    $requestData['action'] = self::CHAT_COMPLETION;

    // Multi turn: replace the single prompt with the whole conversation.
    // This only kicks in when earlier finished turns exist, so the first turn
    // of a new conversation and every legacy caller keep the original prompt
    // path in CRM_AI_CompletionService_OpenAI::formatParams().
    if (!empty($requestData['conversation_id']) && !empty($requestData['id'])) {
      $history = self::buildMessages($requestData['conversation_id'], $requestData['id']);
      if (!empty($history)) {
        // Rebuilt from this row's own role and tone, so changing either one
        // mid conversation takes effect from the next request onwards.
        $systemPrompt = self::buildSystemPrompt($requestData['ai_role'], $requestData['tone_style']);
        $requestData['messages'] = array_merge(
          [['role' => 'system', 'content' => $systemPrompt]],
          $history,
          [['role' => 'user', 'content' => $requestData['context']]]
        );
      }
    }

    // Send request to OpenAI API
    $responseData = CRM_AI_BAO_AICompletion::getCompletion($requestData);

    // Streaming wrote the record from inside the curl write callback and has
    // already flushed the whole reply, so there is nothing left to merge or
    // save. request() returns nothing in that branch.
    if (!empty($requestData['stream'])) {
      return $responseData;
    }

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
   * @throws CRM_Core_Exception If the validation fails or required parameters are missing.
   */
  private static function validateChatParams($params) {
    $aicompletion = [];
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
   * Build the system instruction out of the role and tone of one turn.
   *
   * Single turn requests glue this in front of the user message and store the
   * result in the prompt column, multi turn requests send it as a real system
   * message. Both go through here so the wording stays identical.
   *
   * @param string $aiRole The role the AI should play.
   * @param string $toneStyle The tone of the requested copy.
   *
   * @return string The system instruction.
   */
  public static function buildSystemPrompt($aiRole, $toneStyle) {
    global $tsLocale;
    $countryId = CRM_Core_Config::singleton()->defaultContactCountry;
    $languages = CRM_Core_PseudoConstant::languages();
    $countries = CRM_Core_PseudoConstant::country();
    $country = $countries[$countryId];
    $language = $languages[$tsLocale];
    if ($toneStyle && $aiRole) {
      return ts(
        "Please use %4 language of %3 to play the role of %1 and help generate a %2.",
        [1 => $aiRole, 2 => $toneStyle, 3 => $country, 4 => ts($language)]
      );
    }
    return ts('Please using %1 language to generate content.', [2 => ts($language)]);
  }

  /**
   * Build the finished turns of a conversation as chat API messages.
   *
   * Reads context, the raw user input, rather than prompt, because prompt has
   * the system instruction glued in front of it.
   *
   * @param int $conversationId The conversation to read.
   * @param int $beforeId Only turns older than this row, so the row being
   *   generated right now is never fed back to itself.
   *
   * @return array Alternating user and assistant messages, oldest first.
   */
  public static function buildMessages($conversationId, $beforeId) {
    $messages = [];
    if (empty($conversationId) || empty($beforeId)) {
      return $messages;
    }
    $sql = "SELECT context, output_text FROM civicrm_aicompletion
      WHERE conversation_id = %1 AND id < %2 AND status_id = %3 ORDER BY id";
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$conversationId, 'Integer'],
      2 => [$beforeId, 'Integer'],
      3 => [self::STATUS_SUCCESS, 'Integer'],
    ]);
    while ($dao->fetch()) {
      $messages[] = [
        'role' => 'user',
        'content' => $dao->context,
      ];
      $messages[] = [
        'role' => 'assistant',
        'content' => $dao->output_text,
      ];
    }
    return $messages;
  }

  /**
   * Check that a conversation exists and belongs to the given contact.
   *
   * Every AI ajax endpoint only requires 'access CiviCRM', so without this any
   * logged in user could read someone else's conversation by guessing an id.
   * Templates are shared on purpose, conversation history is not.
   *
   * A conversation that does not exist has no owner and fails the same way, so
   * the caller cannot tell the two cases apart.
   *
   * @param int $conversationId The conversation to check.
   * @param int $contactId The contact the request claims to act as.
   *
   * @return bool TRUE when the contact owns the conversation.
   */
  public static function isConversationOwner($conversationId, $contactId) {
    if (empty($conversationId) || empty($contactId)) {
      return FALSE;
    }
    $ownerId = CRM_Core_DAO::singleValueQuery(
      "SELECT contact_id FROM civicrm_aicompletion WHERE conversation_id = %1 ORDER BY id LIMIT 1",
      [1 => [$conversationId, 'Integer']]
    );
    return !empty($ownerId) && $ownerId == $contactId;
  }

  /**
   * Count the turns already stored in a conversation.
   *
   * Counts every row, matching how quota() counts usage: one submission is one
   * turn whether or not the reply came back.
   *
   * @param int $conversationId The conversation to count.
   *
   * @return int The number of turns.
   */
  public static function getConversationTurns($conversationId) {
    if (empty($conversationId)) {
      return 0;
    }
    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_aicompletion WHERE conversation_id = %1",
      [1 => [$conversationId, 'Integer']]
    );
  }

  /**
   * Usage information
   *
   * @return array The usage information.
   */
  public static function quota() {
    $config = CRM_Core_Config::singleton();
    $used = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_aicompletion WHERE created_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01 00:00:00') AND created_date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00');");
    $percent = $used < $config->openAICompletionQuota ? floor(($used / $config->openAICompletionQuota) * 100) : 100;

    return [
      'max' => $config->openAICompletionQuota,
      'used' => $used,
      'percent' => $percent,
    ];
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
   * @return CRM_AI_DAO_AICompletion|null
   * @access public
   * @static
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_AI_DAO_AICompletion', $params, $defaults);
  }

  /**
   * Only use for database record saving
   *
   * @param array $data (reference) an assoc array of field values to save
   *
   * @return CRM_AI_DAO_AICompletion|null
   *
   * @throws CRM_Core_Exception
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

    $params = [
      'id' => $aicompletion->id,
    ];
    $defaults = [];
    $aicompletion = self::retrieve($params, $defaults);
    return $aicompletion;
  }

  /**
   * Retrieve AI Completion data array by ID.
   *
   * @param int $aiCompletionID The ID of the AI Completion.
   *
   * @return array The retrieved AI Completion data array.
   *
   * @throws CRM_Core_Exception
   */
  private static function retrieveAICompletionDataArray($aiCompletionID) {
    if (empty($aiCompletionID)) {
      throw new CRM_Core_Exception("\$aiCompletionID has no value.");
    }
    elseif (!is_numeric($aiCompletionID)) {
      throw new CRM_Core_Exception("\$aiCompletionID is not number.");
    }
    $params = [
      'id' => $aiCompletionID,
    ];
    $returnArray = [];
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
   *
   * @throws CRM_Core_Exception
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
   * @param array $params request parameters
   *
   * @return array|false
   *  The return array should be compatible for function create
   *
   * @throws CRM_Core_Exception
   */
  public static function getCompletion($params) {
    if (!is_array($params)) {
      throw new CRM_Core_Exception("params should be an Array.");
    }
    $model = $params['model'] ?? self::COMPLETION_MODEL;
    $maxToken = $params['max_token'] ?? self::COMPLETION_MAX_TOKENS;
    $completion = self::singleton(self::COMPLETION_SERVICE, $model, $maxToken);
    $params['temperature'] = $params['temperature'] ?? self::TEMPERATURE_DEFAULT;
    $result = $completion->_serviceProvider->request($params);
    // format result
    return $result;
  }

  /**
   * Retrieve AICompletion Template object(array) by AICompletion ID.
   *
   * @param int $acID The AICompletion ID in DB row.
   *
   * @return array AICompletion data row.
   *
   * @throws CRM_Core_Exception
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
   *
   * @throws CRM_Core_Exception
   */
  public static function getTemplateList($params = []) {
    if (!is_array($params)) {
      throw new CRM_Core_Exception("params should be an Array.");
    }
    $whereClause = [];
    $sqlParams = [];
    $whereClause[] = 'is_template = 1';
    if (isset($params['component'])) {
      $whereClause[] = "component = %1";
      $sqlParams[1] = [$params['component'], 'String'];
    }
    if (isset($params['field'])) {
      $whereClause[] = "field = %2";
      $sqlParams[2] = [$params['field'], 'String'];
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
      $aiCompletionData = [];
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
    foreach ($data as $key => $val) {
      if (!in_array($key, ['id', 'is_template', 'template_title'])) {
        unset($data[$key]);
      }
    }
    $acId = $data['id'];
    $isSuccess = FALSE;
    // Set is template.
    $setTemplateValue = $data['is_template'];
    $originalIsTemplate = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_template');
    $msg = [];
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
   * @return int Return 1 when set successfully. 0 when failed. -1 when already shared.
   */
  public static function setShare($acId) {
    $isShare = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others');
    if ($isShare == 0) {
      return CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others', 1) ? 1 : 0;
    }
    else {
      return -1;
    }
    return 0;
  }

  /**
   * Retrieve the default AI completion template for a given component.
   *
   * @param string $component The component name (e.g. 'CiviContribute', 'Activity').
   *
   * @return string The default template content as JSON string.
   */
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

  /**
   * Retrieve the shared AI completion template for a given component.
   *
   * @param string $component The component name (e.g. 'CiviContribute', 'Activity').
   *
   * @return array|null The decoded shared template data, or NULL if not found.
   */
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
    $decodedShared = json_decode($shared, TRUE);

    if (!$decodedShared) {
      // fallback to non-locale template
      $path = 'CRM/AI/shared/'.$component.$suffix.'.tpl';
      $shared = $smarty->fetch($path);
      $decodedShared = json_decode($shared, TRUE);
    }

    return $decodedShared;
  }
}
