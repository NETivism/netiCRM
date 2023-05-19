<?php
class CRM_AI_BAO_AICompletion extends CRM_AI_DAO_AICompletion {

  const
    // default completion service
    COMPLETION_SERVICE = 'OpenAI',
    // default model base on above service
    COMPLETION_MODEL = 'gpt-3.5-turbo',
    // default max tokens base on model
    COMPLETION_MAX_TOKEN = 4096,
    
  // Action:
    ChatCompletion = 1,
    GetToken = 2;
  
  /**
   * What action is execute now
   * 
   * @var int
   */
  private static $_action;

  /**
   * Max tokens user define which should use base service model max token
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
   * @param int $maxToken (optional) The maximum number of tokens to use for the singleton instance.
   *
   * @return object A reference to the singleton instance of the class.
   */
  static function &singleton($serviceProvider = NULL, $model = NULL, $maxToken = NULL) {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_AI_BAO_AICompletion($serviceProvider, $model, $maxToken);
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
  public static function chat($params = NULL) {
    // Prepare follow parameters will be used.
    self::$_action = self::ChatCompletion;
    $defaults = [];
    $params = $params ? $params : $_POST;
    $args = self::retrieve($params, $defaults);
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
    $requestData['action'] = self::$_action;
    
    // Create or update db record
    extract($requestData);
    $data = array_merge($requestData, [
      'date' => date('Y-m-d H:i:s'),
      'status' => 2, // Default status is 2 (didn't get response)
    ]);
    self::create($data);

    // Send request to OpenAI API
    $responseData = CRM_AI_BAO_AICompletion::getCompletion($requestData, $model, $maxToken);

    // Save response data to db record
    $data = array_merge($data, $responseData);
    self::create($responseData);

    // Return result
    return $responseData;
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
   * @return void
   */
  public static function create(&$data) {
  }

  /**
   * Verify data structure before saving into database
   *
   * @return void
   */
  public static function validateData() {
  }


  public function __construct($serviceProvider = NULL, $model = NULL, $maxToken = NULL) {
    if (empty($serviceProvider)) {
      $serviceProvider = self::COMPLETION_SERVICE;
    }
    if (empty($model)) {
      $model = self::COMPLETION_MODEL;
    }
    if (empty($maxToken)) {
      $maxToken = self::COMPLETION_MAX_TOKEN;
    }
    $className = 'CRM_AI_CompletionService_'.$serviceProvider;
    if (class_exists($className)) {
      $this->_serviceProvider = new $className();
      $this->_serviceProvider->setModel($model);
      $this->_serviceProvider->setMaxToken($maxToken);
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
      $maxToken = self::COMPLETION_MAX_TOKEN;
    }
    $completion = self::singleton(self::COMPLETION_SERVICE, $model, $maxToken);
    $result = $completion->_serviceProvider->request($params);
    // format result
    return $result;
  }
}