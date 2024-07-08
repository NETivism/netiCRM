<?php

class CRM_SMS_Provider_Flydove extends CRM_SMS_Provider {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  public $_bulkMode = FALSE;

  public $_bulkLimit = 500;

  public static function &singleton($providerParams = array(), $force = FALSE) {
    $providerId = CRM_Utils_Array::value('provider_id', $providerParams);
    $providerId = CRM_Utils_Type::validate($providerId, 'Integer');
    if (empty($providerId)) {
      CRM_Core_Error::fatal('Provider not known or not provided.');
    }
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_SMS_Provider_Flydove($providerId);
    }
    return self::$_singleton;
  }

  function __construct($providerId) {
  }

  /**
   * Main function to send SMS
   *
   * The result should be mapping to activity status name for better update activity
   *
   * @param array $messages [
   *  'phone' => string,
   *  'body' => string,
   *  'guid' => string,
   *  'activityId' => int,
   * ]
   * @return array response of self::doRequest
   */
  public function send(&$messages){
    $response = array();
    return $response;
  }

  public function activityUpdate() {
  }
}