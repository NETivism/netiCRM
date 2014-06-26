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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Core/DAO/Preferences.php';

/**
 *
 */
class CRM_Core_BAO_Preferences extends CRM_Core_DAO_Preferences {
  static private $_systemObject = NULL;

  static private $_userObject = NULL;

  static private $_mailingPref = NULL;

  static function systemObject() {
    if (!self::$_systemObject) {
      self::$_systemObject = new CRM_Core_DAO_Preferences();
      self::$_systemObject->domain_id = CRM_Core_Config::domainID();
      self::$_systemObject->is_domain = TRUE;
      self::$_systemObject->contact_id = NULL;
      self::$_systemObject->find(TRUE);
    }
    return self::$_systemObject;
  }

  static
  function mailingPreferences() {
    if (!self::$_mailingPref) {
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


  static
  function userObject($userID = NULL) {
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

  static
  function value($name, $system = TRUE, $userID = NULL) {
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

  static
  function addressSequence($format) {
    // also compute and store the address sequence
    $addressSequence = array('address_name',
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'city',
      'county',
      'state_province',
      'postal_code',
      'country',
    );

    // get the field sequence from the format
    $newSequence = array();
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

  static
  function valueOptions($name, $system = TRUE, $userID = NULL, $localize = FALSE,
    $returnField = 'name', $returnNameANDLabels = FALSE, $condition = NULL
  ) {
    if ($system) {
      $object = self::systemObject();
    }
    else {
      $object = self::userObject($userID);
    }

    $optionValue = $object->$name;
    require_once 'CRM/Core/OptionGroup.php';
    $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, $returnField);

    //enabled name => label require for new contact edit form, CRM-4605
    if ($returnNameANDLabels) {
      $names = $labels = $nameAndLabels = array();
      if ($returnField == 'name') {
        $names = $groupValues;
        $labels = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'label');
      }
      else {
        $labels = $groupValues;
        $names = CRM_Core_OptionGroup::values($name, FALSE, FALSE, $localize, $condition, 'name');
      }
    }

    $returnValues = array();
    foreach ($groupValues as $gn => $gv) {
      $returnValues[$gv] = 0;
    }

    if ($optionValue && !empty($groupValues)) {
      require_once 'CRM/Core/BAO/CustomOption.php';
      $dbValues = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
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

  static
  function setValue($name, $value, $system = TRUE, $userID = NULL, $keyField = 'name') {
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
      require_once 'CRM/Core/OptionGroup.php';
      $groupValues = CRM_Core_OptionGroup::values($name, FALSE, FALSE, FALSE, NULL, $keyField);

      $cbValues = array();
      foreach ($groupValues as $key => $val) {
        if (CRM_Utils_Array::value($val, $value)) {
          $cbValues[$key] = 1;
        }
      }

      if (!empty($cbValues)) {
        $object->$name = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
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

  static
  function fixAndStoreDirAndURL(&$params) {
    $sql = "
SELECT v.name as valueName, g.name as optionName
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  ( g.name = 'directory_preferences'
OR       g.name = 'url_preferences' )
AND    v.option_group_id = g.id
AND    v.is_active = 1
";

    $dirParams = array();
    $urlParams = array();
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

  static
  function storeDirectoryOrURLPreferences(&$params, $type = 'directory') {
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

    require_once 'CRM/Utils/File.php';
    foreach ($params as $name => $value) {
      // always try to store relative directory or url from CMS root
      if ($type == 'directory') {
        $value = CRM_Utils_File::relativeDirectory($value);
      }
      else {
        $value = CRM_Utils_System::relativeURL($value);
      }
      $sqlParams = array(1 => array($value, 'String'),
        2 => array($optionName, 'String'),
        3 => array($name, 'String'),
      );
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }

  static
  function retrieveDirectoryAndURLPreferences(&$params, $setInConfig = FALSE) {
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

    require_once 'CRM/Utils/File.php';

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

