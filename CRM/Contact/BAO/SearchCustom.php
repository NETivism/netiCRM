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
class CRM_Contact_BAO_SearchCustom {

  static function details($csID, $ssID = NULL, $gID = NULL) {
    $error = [NULL, NULL, NULL];

    if (!$csID &&
      !$ssID &&
      !$gID
    ) {
      return $error;
    }

    $customSearchID = $csID;
    $formValues = [];
    if ($ssID || $gID) {
      if ($gID) {
        $ssID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $gID, 'saved_search_id');
      }

      $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
      $customSearchID = CRM_Utils_Array::value('customSearchID',
        $formValues
      );
    }

    if (!$customSearchID) {
      return $error;
    }

    // check that the csid exists in the db along with the right file
    // and implements the right interface

    $allCustom = CRM_Core_OptionGroup::values('custom_search', FALSE, FALSE, FALSE, NULL, 'name');
    if (!empty($allCustom[$customSearchID])) {
      $customSearchClass = $allCustom[$customSearchID];
    }
    else {
      return $error;
    }


    $ext = new CRM_Core_Extensions();

    if (!$ext->isExtensionKey($customSearchClass)) {
      $customSearchFile = str_replace('_',
        DIRECTORY_SEPARATOR,
        $customSearchClass
      ) . '.php';
    }
    else {
      $customSearchFile = $ext->keyToPath($customSearchClass);
      $customSearchClass = $ext->keyToClass($customSearchClass);
    }

    if(!class_exists($customSearchClass)){
      $error = include_once ($customSearchFile);
      if ($error == FALSE) {
        CRM_Core_Error::fatal('Custom search file: ' . $customSearchFile . ' does not exist. Please verify your custom search settings in CiviCRM administrative panel.');
      }
    }

    return [$customSearchID, $customSearchClass, $formValues];
  }

  static function customClass($csID, $ssID) {
    list($customSearchID, $customSearchClass, $formValues) = self::details($csID, $ssID);

    if (!$customSearchID) {
      CRM_Core_Error::fatal('Could not resolve custom search ID');
    }

    // instantiate the new class
    $customClass = new $customSearchClass( $formValues );
    $customClass->_ssID = $ssID;

    return $customClass;
  }

  static function contactIDSQL($csID, $ssID) {
    $customClass = self::customClass($csID, $ssID);
    return $customClass->contactIDs();
  }

  static function &buildFormValues($args) {
    $args = trim($args);

    $values = explode("\n", $args);
    $formValues = [];
    foreach ($values as $value) {
      list($n, $v) = CRM_Utils_System::explode('=', $value, 2);
      if (!empty($v)) {
        $formValues[$n] = $v;
      }
    }
    return $formValues;
  }

  static function fromWhereEmail($csID, $ssID) {
    $customClass = self::customClass($csID, $ssID);

    $from = $customClass->from();
    $where = $customClass->where();


    return [$from, $where];
  }
}

