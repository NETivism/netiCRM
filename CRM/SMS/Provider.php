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
  const MAX_SMS_CHAR = 170;
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
    $mailingID = CRM_Utils_Array::value('mailing_id', $providerParams);
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
  abstract public function send($recipients, $header, $message, $dncID = NULL);

  /**
   * Return message text.
   *
   * Child class could override this function to have better control over the message being sent.
   *
   * @param string $message
   * @param int $contactID
   * @param array $contactDetails
   *
   * @return string
   */
  public function getMessage($message, $contactID, $contactDetails) {
    $html = $message->getHTMLBody();
    $text = $message->getTXTBody();

    return $html ? $html : $text;
  }

  /**
   * Get recipient details.
   *
   * @param array $fields
   * @param array $additionalDetails
   *
   * @return mixed
   */
  public function getRecipientDetails($fields, $additionalDetails) {
    // we could do more altering here
    $fields['To'] = $fields['phone'];
    return $fields;
  }

  /**
   * @param int $apiMsgID
   * @param $message
   * @param array $headers
   * @param int $jobID
   * @param int $userID
   *
   * @return self|null|object
   * @throws CRM_Core_Exception
   */
  public function createActivity($apiMsgID, $message, $headers = array(), $jobID = NULL, $userID = NULL) {
    if ($jobID) {
      $sql = "
SELECT scheduled_id FROM civicrm_mailing m
INNER JOIN civicrm_mailing_job mj ON mj.mailing_id = m.id AND mj.id = %1";
      $sourceContactID = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($jobID, 'Integer')));
    }
    elseif ($userID) {
      $sourceContactID = $userID;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $sourceContactID = $session->get('userID');
    }

    if (!$sourceContactID) {
      $sourceContactID = CRM_Utils_Array::value('Contact', $headers);
    }
    if (!$sourceContactID) {
      return FALSE;
    }

    // $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type', 'SMS delivery', 'name');
    $activityTypeID = CRM_Utils_Array::key('SMS delivery', CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name', TRUE));
    // note: lets not pass status here, assuming status will be updated by callback
    $activityParams = array(
      'source_contact_id' => $sourceContactID,
      'target_contact_id' => $headers['contact_id'],
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'details' => $message,
      'result' => $apiMsgID,
    );
    return CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * @param string $name
   * @param $type
   * @param bool $abort
   * @param null $default
   * @param string $location
   *
   * @return mixed
   */
  public function retrieve($name, $type, $abort = TRUE, $default = NULL, $location = 'REQUEST') {
    static $store = NULL;
    $value = CRM_Utils_Request::retrieve($name, $type, $store,
      FALSE, $default, $location
    );
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name in $location");
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }

}
