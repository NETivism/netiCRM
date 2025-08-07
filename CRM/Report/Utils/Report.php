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
class CRM_Report_Utils_Report {

  static function getValueFromUrl($instanceID = NULL) {
    if ($instanceID) {
      $optionVal = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_Instance',
        $instanceID,
        'report_id'
      );
    }
    else {
      $config = CRM_Core_Config::singleton();
      $args = explode('/', $_GET[$config->userFrameworkURLVar]);

      // remove 'civicrm/report' from args
      array_shift($args);
      array_shift($args);

      // put rest of arguement back in the form of url, which is how value
      // is stored in option value table
      $optionVal = CRM_Utils_Array::implode('/', $args);
    }
    return $optionVal;
  }

  static function getValueIDFromUrl($instanceID = NULL) {
    $optionVal = self::getValueFromUrl($instanceID);

    if ($optionVal) {

      $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value');
      return [$templateInfo['id'], $optionVal];
    }

    return FALSE;
  }

  static function getInstanceIDForValue($optionVal) {
    static $valId = [];

    if (!CRM_Utils_Array::arrayKeyExists($optionVal, $valId)) {
      $sql = "
SELECT MAX(id) FROM civicrm_report_instance
WHERE  report_id = %1";

      $params = [1 => [$optionVal, 'String']];
      $valId[$optionVal] = CRM_Core_DAO::singleValueQuery($sql, $params);
    }
    return $valId[$optionVal];
  }

  static function getInstanceIDForPath($path = NULL) {
    static $valId = [];

    // if $path is null, try to get it from url
    $path = self::getInstancePath();

    if ($path && !CRM_Utils_Array::arrayKeyExists($path, $valId)) {
      $sql = "
SELECT MAX(id) FROM civicrm_report_instance
WHERE  TRIM(BOTH '/' FROM CONCAT(report_id, '/', name)) = %1";

      $params = [1 => [$path, 'String']];
      $valId[$path] = CRM_Core_DAO::singleValueQuery($sql, $params);
    }
    return $valId[$path];
  }

  static function getNextUrl($urlValue, $query = 'reset=1', $absolute = FALSE, $instanceID = NULL) {
    if ($instanceID) {
      $instanceID = self::getInstanceIDForValue($urlValue);

      if ($instanceID) {
        return CRM_Utils_System::url("civicrm/report/instance/{$instanceID}",
          "{$query}", $absolute
        );
      }
      else {
        return FALSE;
      }
    }
    else {
      return CRM_Utils_System::url("civicrm/report/" . trim($urlValue, '/'),
        $query, $absolute
      );
    }
  }

  // get instance count for a template
  static function getInstanceCount($optionVal) {
    $sql = "
SELECT count(inst.id)
FROM   civicrm_report_instance inst
WHERE  inst.report_id = %1";

    $params = [1 => [$optionVal, 'String']];
    $count = CRM_Core_DAO::singleValueQuery($sql, $params);
    return $count;
  }

  static function mailReport($fileContent, $instanceID = NULL, $outputMode = 'html') {
    if (!$instanceID) {
      return FALSE;
    }

    $url = CRM_Utils_System::url("civicrm/report/instance/{$instanceID}",
      "reset=1", TRUE
    );
    $url = "Report Url: {$url} ";
    $fileContent = $url . $fileContent;


    list($domainEmailName,
      $domainEmailAddress
    ) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = ['id' => $instanceID];
    $instanceInfo = [];
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_Instance',
      $params,
      $instanceInfo
    );

    $params = [];
    $params['groupName'] = 'Report Email Sender';
    $params['from'] = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';
    //$domainEmailName;
    $params['toName'] = "";
    $params['toEmail'] = CRM_Utils_Array::value('email_to', $instanceInfo);
    $params['cc'] = CRM_Utils_Array::value('email_cc', $instanceInfo);
    $params['subject'] = CRM_Utils_Array::value('email_subject', $instanceInfo);
    $params['attachments'] = CRM_Utils_Array::value('attachments', $instanceInfo);
    $params['text'] = '';
    $params['html'] = $fileContent;


    $params['mailerType'] = array_search('Transaction Notification', CRM_Core_BAO_MailSettings::$_usedFor);
    return CRM_Utils_Mail::send($params);
  }

  static function export2xls(&$form, &$rows, $fileName = NULL) {

    $config = CRM_Core_Config::singleton();

    //Output headers if this is the first row.
    $columnHeaders = array_keys($form->_columnHeaders);

    // Replace internal header names with friendly ones, where available.
    foreach ($columnHeaders as $header) {
      if (isset($form->_columnHeaders[$header])) {
        if(CRM_Utils_Array::value('type', $form->_columnHeaders[$header]) == 1024){
          $headers[] = $form->_columnHeaders[$header]['title'] . ':' . ts('Currency');
        }
        $headers[] = html_entity_decode(strip_tags($form->_columnHeaders[$header]['title']));
      }
    }

    $displayRows = [];
    $value = NULL;
    foreach ($rows as $i => $row) {
      foreach ($columnHeaders as $k => $v) {
        if ($value = CRM_Utils_Array::value($v, $row)) {
          // Remove HTML, unencode entities, and escape quotation marks.
          $value = str_replace('"', '""', html_entity_decode(strip_tags($value)));

          if (CRM_Utils_Array::value('type', $form->_columnHeaders[$v]) & 4) {
            if (CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'MONTH' ||
              CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'QUARTER'
            ) {
              $value = CRM_Utils_Date::customFormat($value, $config->dateformatPartial);
            }
            elseif (CRM_Utils_Array::value('group_by', $form->_columnHeaders[$v]) == 'YEAR') {
              $value = CRM_Utils_Date::customFormat($value, $config->dateformatYear);
            }
            else {
              $value = CRM_Utils_Date::customFormat($value, '%Y%m%d');
            }
          }
          elseif (CRM_Utils_Array::value('type', $form->_columnHeaders[$v]) == 1024) {
            $currency = $config->defaultCurrency;
            $displayRows[$i][] = $currency;
          }
          // check numeric value
          $value = CRM_Utils_String::toNumber($value);
          $displayRows[$i][] = $value;
        }
        else {
          if (CRM_Utils_Array::value('type', $form->_columnHeaders[$v]) == 1024) {
            $displayRows[$i][] = '';
          }
          $displayRows[$i][] = '';
        }
      }
    }
    $config = CRM_Core_Config::singleton();
    if (empty($fileName)) {
      $fileName = 'report_' . CRM_REQUEST_TIME . '.xlsx';
    }
    CRM_Core_Report_Excel::writeExcelFile($fileName, $headers, $displayRows, $download = TRUE);
    CRM_Utils_System::civiExit();
  }

  static function add2group(&$form, $groupID) {

    if (is_numeric($groupID) && isset($form->_aliases['civicrm_contact'])) {


      $sql = "SELECT DISTINCT {$form->_aliases['civicrm_contact']}.id AS contact_id {$form->_from} {$form->_where} ";
      $dao = CRM_Core_DAO::executeQuery($sql);

      $contact_ids = [];
      // Add resulting contacts to group
      while ($dao->fetch()) {
        $contact_ids[] = $dao->contact_id;
      }

      CRM_Contact_BAO_GroupContact::addContactsToGroup($contact_ids, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."));
    }
  }
  static function getInstanceID() {

    $config = CRM_Core_Config::singleton();
    $arg = explode('/', $_GET[$config->userFrameworkURLVar]);


    if ($arg[1] == 'report' &&
      CRM_Utils_Array::value(2, $arg) == 'instance'
    ) {
      if (CRM_Utils_Rule::positiveInteger($arg[3])) {
        return $arg[3];
      }
    }
  }

  static function getInstancePath() {
    $config = CRM_Core_Config::singleton();
    $arg = explode('/', $_GET[$config->userFrameworkURLVar]);

    if ($arg[1] == 'report' &&
      CRM_Utils_Array::value(2, $arg) == 'instance'
    ) {
      unset($arg[0], $arg[1], $arg[2]);
      $path = trim(CRM_Utils_Type::escape(CRM_Utils_Array::implode('/', $arg), 'String'), '/');
      return $path;
    }
  }

  static function isInstancePermissioned($instanceId) {
    if (!$instanceId) {
      return TRUE;
    }

    $instanceValues = [];
    $params = ['id' => $instanceId];
    CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_Instance',
      $params,
      $instanceValues
    );

    if (!empty($instanceValues['permission']) &&
      (!(CRM_Core_Permission::check($instanceValues['permission']) ||
          CRM_Core_Permission::check('administer Reports')
        ))
    ) {
      return FALSE;
    }

    return TRUE;
  }
}

