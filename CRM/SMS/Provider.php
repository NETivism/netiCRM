<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 */
abstract class CRM_SMS_Provider {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = array();

  /**
   * Each array element is a message constructor by provider prepared to send
   *
   * The key of array may be the unique id of SMS message
   *
   * @var array
   */
  public $_sms = array();

  /**
   * Each acitvity ID that SMS need to update after send
   *
   * @var array
   */
  public $_activityId = array();

  /**
   * The result of this batch / process
   *
   * @var bool
   */
  public $_success = NULL;

  /**
   * HTTP or other response from provider
   *
   * @var array
   */
  public $_response = array();

  /**
   * Provider info increase secret
   *
   * @var array
   */
  private $_providerInfo = array();

  /**
   * Max SMS Characters to send in 1 message
   */
  const MAX_SMS_CHAR = 160;

  /**
   * Max multi-bytes charactiers to send in 1 message
   */
  const MAX_ZH_SMS_CHAR = 70;

  /**
   * Singleton function used to manage this object.
   *
   * @param array $providerParams
   * @param bool $force
   *
   * @return object
   */
  public static function &singleton($providerParams = array(), $force = FALSE) {
    $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
    $providerName = CRM_Utils_Array::value('provider', $providerParams);

    if ($providerID) {
      $providerName = CRM_SMS_BAO_Provider::getProviderInfo($providerID, 'name');
    }

    if (!$providerName) {
      CRM_Core_Error::fatal('Provider not known or not provided.');
    }

    $providerName = CRM_Utils_Type::escape($providerName, 'String');
    $cacheKey = "{$providerName}_" . (int) $providerID;
    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $providerClass = $providerName;
      $providerFile = str_replace('_', '/', $providerName);


      self::$_singleton[$cacheKey] = $providerClass::singleton($providerParams, $force);
    }
    return self::$_singleton[$cacheKey];
  }

  /**
   * Send an SMS Message via the API Server.
   *
   * @param array $messages
   */
  abstract public function send(&$messages);

  /**
   * Activity status and detail update after SMS send callback
   *
   * This will trigger by Activity.php which hook the activity update after normal or bulk send
   */
  abstract public function activityUpdate();

  /**
   * Free public / private params on each iteration
   *
   * @return void
   */
  public function free() {
    $this->_sms = array();
    $this->_activityId = array();
    $this->_success = NULL;
    $this->_response = array();
    $this->_activityId = array();
  }
}
