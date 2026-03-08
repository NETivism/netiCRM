<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_BAO_MailSettings extends CRM_Core_DAO_MailSettings {
  public static $_mailerTypes = [
    3 => 'Transaction Notification',
    2 => 'Mass Mailing',
    1 => 'Bounce Processing',
    0 => 'Disabled',
  ];

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Get the default mail settings DAO object.
   *
   * @return CRM_Core_BAO_MailSettings default mail settings object
   */
  public static function &defaultDAO() {
    static $dao = NULL;
    if (!$dao) {
      $dao = new self();
      $dao->is_default = 1;
      $dao->domain_id = CRM_Core_Config::domainID();
      if ($dao->find(TRUE)) {
        global $civicrm_conf;
        if (isset($civicrm_conf['mailing_mailstore'])) {
          foreach ($civicrm_conf['mailing_mailstore'] as $k => $v) {
            if (isset($dao->$k) && !empty($v)) {
              $dao->$k = $v;
            }
          }
        }
      }
    }
    return $dao;
  }

  /**
   * Get the default domain for mail settings.
   *
   * @return string|null default domain
   */
  public static function defaultDomain() {
    return self::defaultDAO()->domain;
  }

  /**
   * Get the default localpart for mail settings.
   *
   * @return string|null default localpart
   */
  public static function defaultLocalpart() {
    return self::defaultDAO()->localpart;
  }

  /**
   * Get the default return path for mail settings.
   *
   * @return string|null default return path
   */
  public static function defaultReturnPath() {
    return self::defaultDAO()->return_path;
  }

  /**
   * Retrieve a mail settings record based on provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_MailSettings|null matching mail settings object
   */
  public static function retrieve(&$params, &$defaults) {
    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->copyValues($params);

    $result = NULL;
    if ($mailSettings->find(TRUE)) {
      global $civicrm_conf;
      if (isset($civicrm_conf['mailing_mailstore']) && $mailSettings->is_default == 1) {
        foreach ($civicrm_conf['mailing_mailstore'] as $k => $v) {
          if (isset($mailSettings->$k) && !empty($v)) {
            $mailSettings->$k = $v;
          }
        }
      }
      CRM_Core_DAO::storeValues($mailSettings, $defaults);
      $result = $mailSettings;
    }

    return $result;
  }

  /**
   * Add a new mail settings record.
   *
   * @param array $params associative array of mail settings data
   *
   * @return CRM_Core_DAO_MailSettings|null created mail settings object
   */
  public static function add(&$params) {
    $result = NULL;
    if (empty($params)) {
      return $result;
    }

    $params['is_ssl'] = CRM_Utils_Array::value('is_ssl', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    //handle is_default.
    if ($params['is_default'] == 1) {
      $query = 'UPDATE civicrm_mail_settings SET is_default = 0 WHERE domain_id = %1 AND is_default = 1';
      $queryParams = [1 => [CRM_Core_Config::domainID(), 'Integer']];
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->copyValues($params);
    $result = $mailSettings->save();

    return $result;
  }

  /**
   * Create or update a mail settings record using a transaction.
   *
   * @param array $params associative array of mail settings data
   *
   * @return CRM_Core_BAO_MailSettings|CRM_Core_Error created/updated object or error
   */
  public static function &create(&$params) {

    $transaction = new CRM_Core_Transaction();

    $mailSettings = self::add($params);
    if (is_a($mailSettings, 'CRM_Core_Error')) {
      $mailSettings->rollback();
      return $mailSettings;
    }

    $transaction->commit();

    return $mailSettings;
  }

  /**
   * Delete a mail settings record.
   *
   * @param int $id mail settings ID
   *
   * @return bool TRUE on success, FALSE otherwise
   */
  public static function deleteMailSettings($id) {
    $results = NULL;

    $transaction = new CRM_Core_Transaction();

    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->id = $id;
    $results = $mailSettings->delete();

    $transaction->commit();

    return $results;
  }
}
