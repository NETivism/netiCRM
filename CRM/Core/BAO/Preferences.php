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

/**
 *
 */
class CRM_Core_BAO_Preferences extends CRM_Core_DAO_Preferences {
  private static $_systemObject = NULL;

  private static $_userObject = NULL;

  private static $_mailingPref = NULL;

  /**
   * Retrieve the system-wide preferences object.
   *
   * @return CRM_Core_DAO_Preferences system-wide preferences
   */
  public static function systemObject() {
    if (!self::$_systemObject) {
      self::$_systemObject = new CRM_Core_DAO_Preferences();
      self::$_systemObject->domain_id = CRM_Core_Config::domainID();
      self::$_systemObject->is_domain = TRUE;
      self::$_systemObject->contact_id = NULL;
      self::$_systemObject->find(TRUE);
    }
    return self::$_systemObject;
  }

  /**
   * Retrieve the mailing backend preferences.
   *
   * @return array associative array of mailing configuration
   */
  public static function mailingPreferences() {
    global $civicrm_conf;
    if (!self::$_mailingPref) {
      if (isset($civicrm_conf['mailing_backend'])) {
        self::$_mailingPref = $civicrm_conf['mailing_backend'];
        return $civicrm_conf['mailing_backend'];
      }
      $mailingPref = new CRM_Core_DAO_Preferences();
      $mailingPref->domain_id = CRM_Core_Config::domainID();
      $mailingPref->is_domain = TRUE;
      $mailingPref->contact_id = NULL;
      $mailingPref->find(TRUE);
      if ($mailingPref->mailing_backend) {
        self::$_mailingPref = unserialize($mailingPref->mailing_backend);
      }
    }
    return self::$_mailingPref;
  }

  /**
   * Retrieve the preferences object for a specific user.
   *
   * @param int|null $userID optional user ID (defaults to logged in user)
   *
   * @return CRM_Core_DAO_Preferences user preferences object
   */
  public static function userObject($userID = NULL) {
    if (!self::$_userObject) {
      if (!$userID) {
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID');
      }
      self::$_userObject = new CRM_Core_DAO_Preferences();
      self::$_userObject->domain_id = CRM_Core_Config::domainID();
      self::$_userObject->is_domain = FALSE;
      self::$_userObject->contact_id = $userID;
      self::$_userObject->find(TRUE);
    }
    return self::$_userObject;
  }

  /**
   * Get the value of a specific preference.
   *
   * @param string $name preference name
   * @param bool $system TRUE for system-wide, FALSE for user-specific
   * @param int|null $userID optional user ID for user preferences
   *
   * @return mixed preference value
   */
  public static function value($name, $system = TRUE, $userID = NULL) {
    if ($system) {
      $object = self::systemObject();
    }
    else {
      $object = self::userObject($userID);
    }

    if ($name == 'address_sequence') {
      return self::addressSequence(self::$_systemObject->address_format);
    }
    elseif ($name == 'mailing_sequence') {
      return self::addressSequence(self::$_systemObject->mailing_format);
    }

    return self::$_systemObject->$name;
  }

  /**
   * Compute the address field sequence based on a format string.
   *
   * @param string $format address format string (e.g., with {street_address})
   *
   * @return string[] array of field names in order
   */
  public static function addressSequence($format) {
    // also compute and store the address sequence
    $addressSequence = ['address_name',
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'city',
      'county',
      'state_province',
      'postal_code',
      'country',
    ];

    // get the field sequence from the format
    $newSequence = [];
    foreach ($addressSequence as $field) {
      if (substr_count($format, $field)) {
        $newSequence[strpos($format, $field)] = $field;
      }
    }
    ksort($newSequence);

    // add the addressSequence fields that are missing in the addressFormat
    // to the end of the list, so that (for example) if state_province is not
    // specified in the addressFormat it's still in the address-editing form
    $newSequence = array_merge($newSequence, $addressSequence);
    $newSequence = array_unique($newSequence);
    return $newSequence;
  }

  /**
   * Get the enabled options for a multi-choice preference.
   *
   * @param string $name preference name
   * @param bool $system TRUE for system-wide, FALSE for user-specific
   * @param int|null $userID optional user ID
   * @param bool $localize whether to localize labels
   * @param string $returnField field to use as key ('name' or 'label')
   * @param bool $returnNameANDLabels whether to return name => label pairs
   * @param string|null $condition optional SQL condition for options
   *
   * @return array associative array of enabled options
   */
  public static function valueOptions(
    $name,
    $system = TRUE,
    $userID = NULL,
    $localize = FALSE,
    $returnField = 'name',
    $returnNameANDLabels = FALSE,
    $condition = NULL
  ) {
    if ($system) {
      $object = self::systemObject();
    }
    else {
      $object = self::userObject($userID);
    }

    $optionValue = $object->$name;

    $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, $returnField);

    //enabled name => label require for new contact edit form, CRM-4605
    if ($returnNameANDLabels) {
      $names = $labels = $nameAndLabels = [];
      if ($returnField == 'name') {
        $names = $groupValues;
        $labels = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'label');
      }
      else {
        $labels = $groupValues;
        $names = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'name');
      }
    }

    $returnValues = [];
    foreach ($groupValues as $gn => $gv) {
      $returnValues[$gv] = 0;
    }

    if ($optionValue && !empty($groupValues)) {

      $dbValues = explode(
        CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
        substr($optionValue, 1, -1)
      );

      if (!empty($dbValues)) {
        foreach ($groupValues as $key => $val) {
          if (in_array($key, $dbValues)) {
            $returnValues[$val] = 1;
            if ($returnNameANDLabels) {
              $nameAndLabels[$names[$key]] = $labels[$key];
            }
          }
        }
      }
    }

    return ($returnNameANDLabels) ? $nameAndLabels : $returnValues;
  }

  /**
   * Set the value of a specific preference.
   *
   * @param string $name preference name
   * @param mixed $value new value (string or array for multi-choice)
   * @param bool $system TRUE for system-wide, FALSE for user-specific
   * @param int|null $userID optional user ID for user preferences
   * @param string $keyField field to use for mapping array values
   *
   * @return void
   */
  public static function setValue($name, $value, $system = TRUE, $userID = NULL, $keyField = 'name') {
    if ($system) {
      $object = self::systemObject();
    }
    else {
      $object = self::userObject($userID);
    }

    if (empty($value)) {
      $object->$name = 'NULL';
    }
    elseif (is_array($value)) {

      $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, FALSE, NULL, $keyField);

      $cbValues = [];
      foreach ($groupValues as $key => $val) {
        if (CRM_Utils_Array::value($val, $value)) {
          $cbValues[$key] = 1;
        }
      }

      if (!empty($cbValues)) {
        $object->$name = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(
          CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
          array_keys($cbValues)
        ) . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
      }
      else {
        $object->$name = 'NULL';
      }
    }
    else {
      $object->$name = $value;
    }

    $object->save();
  }

  /**
   * Extract and store directory and URL preferences from parameters.
   *
   * @param array &$params associative array of configuration parameters
   *
   * @return void
   */
  public static function fixAndStoreDirAndURL(&$params) {
    $sql = "
SELECT v.name as valueName, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";

    $dirParams = [];
    $urlParams = [];
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!isset($params[$dao->valueName])) {
        continue;
      }
      if ($dao->optionName == 'directory_preferences') {
        $dirParams[$dao->valueName] = CRM_Utils_Array::value($dao->valueName, $params, '');
      }
      else {
        $urlParams[$dao->valueName] = CRM_Utils_Array::value($dao->valueName, $params, '');
      }
      unset($params[$dao->valueName]);
    }

    if (!empty($dirParams)) {
      CRM_Core_BAO_Preferences::storeDirectoryOrURLPreferences($dirParams, 'directory');
    }

    if (!empty($urlParams)) {
      CRM_Core_BAO_Preferences::storeDirectoryOrURLPreferences($urlParams, 'url');
    }
  }

  /**
   * Store directory or URL preferences in the option_value table.
   *
   * @param array &$params associative array of (name => value)
   * @param string $type preference type ('directory' or 'url')
   *
   * @return void
   */
  public static function storeDirectoryOrURLPreferences(&$params, $type = 'directory') {
    $optionName = ($type == 'directory') ? 'directory_preferences' : 'url_preferences';

    $sql = "
UPDATE civicrm_option_value v,
       civicrm_option_group g
SET    v.value = %1,
       v.is_active = 1
WHERE  g.name = %2
AND    v.option_group_id = g.id
AND    v.name = %3
";

    foreach ($params as $name => $value) {
      // always try to store relative directory or url from CMS root
      if ($type == 'directory') {
        $value = CRM_Utils_File::relativeDirectory($value);
      }
      else {
        $value = CRM_Utils_System::relativeURL($value);
      }
      $sqlParams = [1 => [$value, 'String'],
        2 => [$optionName, 'String'],
        3 => [$name, 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  /**
   * Retrieve directory and URL preferences from the database.
   *
   * @param array &$params associative array to store retrieved values
   * @param bool $setInConfig whether to also set values in the global config object
   *
   * @return void
   */
  public static function retrieveDirectoryAndURLPreferences(&$params, $setInConfig = FALSE) {
    if ($setInConfig) {
      $config = &CRM_Core_Config::singleton();
    }

    $sql = "
SELECT v.name as valueName, v.value, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!$dao->value) {
        continue;
      }
      if ($dao->optionName == 'directory_preferences') {
        $value = CRM_Utils_File::absoluteDirectory($dao->value);
      }
      else {
        $value = CRM_Utils_System::absoluteURL($dao->value, TRUE);
      }
      $params[$dao->valueName] = $value;
      if ($setInConfig) {
        $config->{$dao->valueName} = $value;
      }
    }
  }
}
