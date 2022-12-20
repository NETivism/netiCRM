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
  const MAX_SMS_CHAR = 160;
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

    if (!$providerID && $mailingID) {
      $providerID = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailingID, 'sms_provider_id', 'id');
      $providerParams['provider_id'] = $providerID;
    }
    if ($providerID) {
      $providerName = CRM_SMS_BAO_Provider::getProviderInfo($providerID, 'name');
    }

    if (!$providerName) {
      CRM_Core_Error::fatal('Provider not known or not provided.');
    }

    $providerName = CRM_Utils_Type::escape($providerName, 'String');
    $cacheKey = "{$providerName}_" . (int) $providerID . "_" . (int) $mailingID;
    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $paymentClass = $providerName;
      $paymentFile = str_replace('_', '/', $providerName);
      require_once "{$paymentFile}.php";

      self::$_singleton[$cacheKey] = $paymentClass::singleton($providerParams, $force);
    }
    return self::$_singleton[$cacheKey];
  }

  /**
   * Send an SMS Message via the API Server.
   *
   * @param array $recipients
   * @param string $header
   * @param string $message
   * @param int $dncID
   */
  abstract public function send($recipients, $header, $message, $additional = NULL);

}
