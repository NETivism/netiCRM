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
 * Utility methods for report generation including CSV export and chart rendering
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Report_Utils_Report {

  /**
   * Returns the report template value (report_id) from the URL or from a saved instance.
   * When $instanceID is provided, reads report_id from the database for that instance.
   * Otherwise parses the current URL path, stripping the leading 'civicrm/report' segments.
   *
   * @param int|null $instanceID The report instance ID, or NULL to read from current URL.
   *
   * @return string The option value string identifying the report template (e.g. 'contact/summary').
   */
  public static function getValueFromUrl($instanceID = NULL) {
    if ($instanceID) {
      $optionVal = CRM_Core_DAO::getFieldValue(
        'CRM_Report_DAO_Instance',
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

  /**
   * Returns the option value row ID and option value string for the current report template URL.
   *
   * @param int|null $instanceID The report instance ID, or NULL to read from current URL.
   *
   * @return array|false Two-element array: [0] => option value row ID, [1] => option value string.
   *   Returns FALSE if no matching option value is found.
   */
  public static function getValueIDFromUrl($instanceID = NULL) {
    $optionVal = self::getValueFromUrl($instanceID);

    if ($optionVal) {

      $templateInfo = CRM_Core_OptionGroup::getRowValues('report_template', "{$optionVal}", 'value');
      return [$templateInfo['id'], $optionVal];
    }

    return FALSE;
  }

  /**
   * Returns the highest report instance ID for a given report template value (report_id).
   * Results are cached in a static variable for the duration of the request.
   *
   * @param string $optionVal The report template option value (e.g. 'contact/summary').
   *
   * @return string|null The instance ID as a string, or NULL if none exists.
   */
  public static function getInstanceIDForValue($optionVal) {
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

  /**
   * Returns the highest report instance ID whose concatenated report_id/name matches
   * the current URL path. Reads the path from the URL via getInstancePath().
   * Results are cached in a static variable for the duration of the request.
   *
   * @param string|null $path Unused; the path is always resolved from the current URL.
   *
   * @return string|null The instance ID as a string, or NULL if none exists.
   */
  public static function getInstanceIDForPath($path = NULL) {
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

  /**
   * Returns the URL to navigate to a report template or instance.
   * When $instanceID is truthy, resolves the highest instance ID for $urlValue and
   * returns its instance URL; returns FALSE if no instance is found.
   * Otherwise returns the direct template URL.
   *
   * @param string $urlValue The report template option value (e.g. 'contact/summary').
   * @param string $query Query string to append (default 'reset=1').
   * @param bool $absolute Whether to return an absolute URL (default FALSE).
   * @param int|null $instanceID Pass a truthy value to force instance URL resolution.
   *
   * @return string|false The report URL, or FALSE if instance resolution fails.
   */
  public static function getNextUrl($urlValue, $query = 'reset=1', $absolute = FALSE, $instanceID = NULL) {
    if ($instanceID) {
      $instanceID = self::getInstanceIDForValue($urlValue);

      if ($instanceID) {
        return CRM_Utils_System::url(
          "civicrm/report/instance/{$instanceID}",
          "{$query}",
          $absolute
        );
      }
      else {
        return FALSE;
      }
    }
    else {
      return CRM_Utils_System::url(
        "civicrm/report/" . trim($urlValue, '/'),
        $query,
        $absolute
      );
    }
  }

  /**
   * Returns the number of report instances created from a given report template.
   *
   * @param string $optionVal The report template option value (e.g. 'contact/summary').
   *
   * @return string|int The instance count (returned as a string from singleValueQuery).
   */
  public static function getInstanceCount($optionVal) {
    $sql = "
SELECT count(inst.id)
FROM   civicrm_report_instance inst
WHERE  inst.report_id = %1";

    $params = [1 => [$optionVal, 'String']];
    $count = CRM_Core_DAO::singleValueQuery($sql, $params);
    return $count;
  }

  /**
   * Emails a rendered report to the addresses configured on the report instance.
   * Prepends the report URL to the content and uses the domain's From address.
   *
   * @param string $fileContent The rendered HTML or text content of the report.
   * @param int|null $instanceID The report instance ID. Returns FALSE if not provided.
   * @param string $outputMode Output format; currently only 'html' is used (default 'html').
   *
   * @return bool Result of CRM_Utils_Mail::send(); FALSE if $instanceID is empty.
   */
  public static function mailReport($fileContent, $instanceID = NULL, $outputMode = 'html') {
    if (!$instanceID) {
      return FALSE;
    }

    $url = CRM_Utils_System::url(
      "civicrm/report/instance/{$instanceID}",
      "reset=1",
      TRUE
    );
    $url = "Report Url: {$url} ";
    $fileContent = $url . $fileContent;

    list($domainEmailName,
      $domainEmailAddress
    ) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = ['id' => $instanceID];
    $instanceInfo = [];
    CRM_Core_DAO::commonRetrieve(
      'CRM_Report_DAO_Instance',
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

  /**
   * Exports the report rows as an Excel (.xlsx) file and triggers a browser download.
   * Uses the form's _columnHeaders to build the header row and processes date/money fields.
   * Calls CRM_Utils_System::civiExit() after writing the file.
   *
   * @param CRM_Report_Form &$form The report form instance, providing _columnHeaders.
   * @param array &$rows Array of data rows keyed by column header names.
   * @param string|null $fileName Optional file name; defaults to 'report_{timestamp}.xlsx'.
   *
   * @return void
   */
  public static function export2xls(&$form, &$rows, $fileName = NULL) {

    $config = CRM_Core_Config::singleton();

    //Output headers if this is the first row.
    $columnHeaders = array_keys($form->_columnHeaders);

    // Replace internal header names with friendly ones, where available.
    foreach ($columnHeaders as $header) {
      if (isset($form->_columnHeaders[$header])) {
        if (CRM_Utils_Array::value('type', $form->_columnHeaders[$header]) == 1024) {
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

  /**
   * Adds contacts returned by the report's current query to a CiviCRM group.
   * When the report uses GROUP BY or HAVING clauses, wraps the query in a subquery
   * to correctly resolve HAVING conditions before collecting distinct contact IDs.
   *
   * @param CRM_Report_Form &$form The report form instance; must have an alias for
   *   'civicrm_contact' in _aliases, and may have _groupBy, _having, _select, _from, _where.
   * @param int $groupID The ID of the CiviCRM group to add contacts to.
   *
   * @return void
   */
  public static function add2group(&$form, $groupID) {

    if (is_numeric($groupID) && isset($form->_aliases['civicrm_contact'])) {

      if (!empty($form->_groupBy) || !empty($form->_having)) {
        // Build SELECT clause - include contact_id and any aggregate fields needed for HAVING
        $selectFields = ["{$form->_aliases['civicrm_contact']}.id AS contact_id"];

        if (!empty($form->_having) && !empty($form->_select)) {
          // Extract field names from HAVING clause
          if (preg_match_all('/\b([a-z_]+_(?:sum|count|avg))\b/i', $form->_having, $havingFields)) {
            foreach ($havingFields[1] as $fieldAlias) {
              // Get select aggregate field (sum/count/avg) in havingFields
              if (preg_match('/(\w+\([^)]+\))\s+as\s+' . preg_quote($fieldAlias, '/') . '\b/i', $form->_select, $aggMatch)) {
                $selectFields[] = $aggMatch[0];
              }
            }
          }
        }

        $innerSql = "SELECT " . implode(', ', $selectFields) . " {$form->_from} {$form->_where} ";

        if (!empty($form->_groupBy)) {
          $innerSql .= " {$form->_groupBy} ";
        }

        if (!empty($form->_having)) {
          $innerSql .= " {$form->_having} ";
        }

        $sql = "SELECT DISTINCT contact_id FROM ({$innerSql}) as subquery";
      }
      else {
        // Original behavior when no GROUP BY or HAVING
        $sql = "SELECT DISTINCT {$form->_aliases['civicrm_contact']}.id AS contact_id {$form->_from} {$form->_where} ";
      }

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
  /**
   * Extracts the report instance ID from the current URL path.
   * Expects a URL of the form 'civicrm/report/instance/{id}'.
   *
   * @return int|null The positive integer instance ID, or NULL if the URL does not match.
   */
  public static function getInstanceID() {

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

  /**
   * Extracts the path suffix after 'civicrm/report/instance/' from the current URL.
   * The returned path can be matched against civicrm_report_instance.name for lookup.
   *
   * @return string|null The sanitized path string, or NULL if the URL does not match.
   */
  public static function getInstancePath() {
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

  /**
   * Checks whether the current user has permission to access a report instance.
   * Returns TRUE when no instance is given, or when the instance has no permission
   * set, or when the user has either the instance permission or 'administer Reports'.
   *
   * @param int|null $instanceId The report instance ID to check, or NULL/0 to skip.
   *
   * @return bool TRUE if access is allowed, FALSE otherwise.
   */
  public static function isInstancePermissioned($instanceId) {
    if (!$instanceId) {
      return TRUE;
    }

    $instanceValues = [];
    $params = ['id' => $instanceId];
    CRM_Core_DAO::commonRetrieve(
      'CRM_Report_DAO_Instance',
      $params,
      $instanceValues
    );

    if (!empty($instanceValues['permission']) &&
      (!(
        CRM_Core_Permission::check($instanceValues['permission']) ||
          CRM_Core_Permission::check('administer Reports')
      ))
    ) {
      return FALSE;
    }

    return TRUE;
  }
}
