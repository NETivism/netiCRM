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
    
    // Action:
    CHAT_COMPLETION = 1,
    GET_TOKEN = 2;
  
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
   * High level function to call AI completion and save records into db
   *
   * Use this for making request and save data.
   * Use getCompletion() for send request only
   *
   * @return array
   */
  public static function chat($params = array()) {
    // Prepare follow parameters will be used.
    self::$_action = self::CHAT_COMPLETION;
    // TODO: validate -> validateChatParams()
    $params = $params ? $params : $_POST;
    // $defaults = [];
    // $args = self::retrieve($params, $defaults);
    $args = $params;
    if (!isset($params['prompt'])) {
      $missingParams = [];
      if (!isset($requestData['prompt'])) {
          $missingParams[] = 'prompt';
      }
      throw new Exception('Missing required parameters: ' . implode(', ', $missingParams));
    }

    // Validate request parameters
    $requestData = [
      'prompt' => $args['prompt'] ? $args['prompt'] : null,
    ];
    if (isset($args['model'])) {
      $model = $args['model'];
    }
    if (isset($args['max_tokens'])) {
      $maxTokens = $args['max_tokens'];
    }
    if (isset($args['stream'])) {
      $requestData['stream'] = $args['stream'];
    }
    $requestData['action'] = self::$_action;
    
    // Create or update db record
    $data = array_merge($requestData, [
      'date' => date('Y-m-d H:i:s'),
      'status' => 2, // Default status is 2 (didn't get response)
    ]);
    $aicompletion = self::create($data);

    // Send request to OpenAI API
    $responseData = CRM_AI_BAO_AICompletion::getCompletion($requestData, $model, $maxTokens);

    // Save response data to db record
    $data = array_merge($data, $responseData);
    $data['id'] = $aicompletion->id;
    self::create($responseData);

    // Return result
    return $responseData;
  }

  private static function validateChatParams($params) {
    return $params;
  }

  /**
   * Usage information
   *
   * @return array
   */
  public static function quota() {
    return array(
      'max' => $max,
      'usage' => $percent,
      'used' => $used,
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
    $op = 'edit';
    $id = CRM_Utils_Array::value('id', $data);
    if (!$id) {
      $op = 'create';
    }
    if (empty($data['contact_id'])) {
      $session = CRM_Core_Session::singleton();
      $data['contact_id'] = $session->get('userID') ? $session->get('userID') : 1; // TODO: don't use 1
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
   * Verify data structure before saving into database
   *
   * @return void
   */
  public static function validateData() {
  }


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
    $className = 'CRM_AI_CompletionService_'.$serviceProvider;
    if (class_exists($className)) {
      $this->_serviceProvider = new $className();
      $this->_serviceProvider->setModel($model);
      $this->_serviceProvider->setMaxTokens($maxTokens);
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
  public static function getCompletion($params, $model = NULL, $maxToken = NULL) {
    if (empty($model)) {
      $model = self::COMPLETION_MODEL;
    }
    if (empty($maxToken)) {
      $maxToken = self::COMPLETION_MAX_TOKENS;
    }
    $completion = self::singleton(self::COMPLETION_SERVICE, $model, $maxToken);
    $result = $completion->_serviceProvider->request($params);
    // format result
    return $result;
  }

  /**
   * Retrieve AICompletion Template object(array) by AICompletion ID.
   * @param Int $acId The AICompletion ID in DB row.
   * 
   * @return FALSE|array AICompletion data row.
   */
  public static function getTemplate($acId) {
    $params = array(
      'id' => $acId,
    );
    $objectArray = [];
    self::retrieve($params, $objectArray);
    return $objectArray;
  }

  /**
   * Retrieve certain quantity of aicompletion data rows which 'is_template' = 1.
   * @param Int $offset The offset of retrieve rows
   * 
   * @return array AICompletion data rows.
   */
  public static function getTemplateList($offset = 0) {
    $sql = "SELECT * FROM civicrm_aicompletion WHERE is_template = 1";
    $sql .= " LIMIT ".self::TEMPLATE_LIST_ROW_LIMIT;
    if ($offset) {
      $sql .= " OFFSET ".$offset;
    }
    $dao = CRM_Core_DAO::executeQuery($sql);
    $return = [];
    while ($dao->fetch()) {
      $daoArray = [];
      CRM_Core_DAO::storeValues($dao, $daoArray);
      $return[] = $daoArray;
    }
    return $return;
  }

  /** 
   * Set is_template value to 1 for AICompletion data by ID.
   * @param Int $acId The ID of AiCompletion data.
   * 
   * @return Int If value has been changed, return 1, otherwise return 0.  
   */
  public static function setTemplate($acId) {
    $returnValue = 0;
    $is_template = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_template');
    if ($is_template == 0) {
      $result = CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_template', 1);
      $returnValue = $result ? 1 : 0;
    }
    return $returnValue;
  }

  /**
   * Set is_share_with_others value to 1 for AICompletion data by ID.
   * @param Int $acId The ID of AiCompletion data.
   * 
   * @return Int If value has been changed, return 1, otherwise return 0.  
   */
  public static function setShare($acId) {
    $returnValue = 0;
    $is_template = CRM_Core_DAO::getFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others');
    if ($is_template == 0) {
      $result = CRM_Core_DAO::setFieldValue('CRM_AI_DAO_AICompletion', $acId, 'is_share_with_others', 1);
      $returnValue = $result ? 1 : 0;
    }
    return $returnValue;
  }


}