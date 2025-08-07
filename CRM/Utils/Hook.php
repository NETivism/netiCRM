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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: $
 *
 */
class CRM_Utils_Hook {

  // Allowed values for dashboard hook content placement
  // Default - place content below activity list
  CONST DASHBOARD_BELOW = 1;
  // Place content above activity list
  CONST DASHBOARD_ABOVE = 2;
  // Don't display activity list at all
  CONST DASHBOARD_REPLACE = 3;

  // by default - place content below existing content
  CONST SUMMARY_BELOW = 1;
  // pace hook content above
  CONST SUMMARY_ABOVE = 2;
  // create your own summarys
  CONST SUMMARY_REPLACE = 3;

  /**
   * This will return implemented module of hook 
   *
   * @param string $hook
   *   hook name with civicrm to search
   */
  static function availableHooks($hook) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::availableHooks($hook);
  }

  /**
   * This hook is called when dao->find triggered
   *
   * @param bool $fetch Wheather the dao->find want to fetch first result
   * @param object $dao The fetched object which can be alter
   * @param int $num Number of rows after dao->find
   * @return void
   */
  static function get($fetch, &$dao, $numRows) {
    $className = CRM_Core_Config::singleton()->userHookClass;
    $null = &CRM_Core_DAO::$_nullObject;
    return $className::invoke( 3, $fetch, $dao, $numRows, $null, $null, 'civicrm_get' );
  }

  /**
   * This hook is called before a db write on some core objects.
   * This hook does not allow the abort of the operation
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param object $id         the object id if available
   * @param array  $params     the parameters used for object creation / editing
   *
   * @return null the return value is ignored
   * @access public
   */
  static function pre($op, $objectName, $id, &$params) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke( 4, $op, $objectName, $id, $params, $op, 'civicrm_pre' );
  }

  /**
   * This hook is called after a db write on some core objects.
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param int    $objectId   the unique identifier for the object
   * @param object $objectRef  the reference to the object if available
   *
   * @return mixed             based on op. pre-hooks return a boolean or
   *                           an error message which aborts the operation
   * @access public
   */
  static function post($op, $objectName, $objectId, &$objectRef) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke( 4, $op, $objectName, $objectId, $objectRef, $op, 'civicrm_post' );
  }

  /**
   * This hook retrieves links from other modules and injects it into
   * the view contact tabs
   *
   * @param string $op         the type of operation being performed
   * @param string $objectName the name of the object
   * @param int    $objectId   the unique identifier for the object
   * @params array $links      (optional ) the links array (introduced in v3.2)
   *
   * @return array|null        an array of arrays, each element is a tuple consisting of id, url, img, title, weight
   *
   * @access public
   */
  static function links($op, $objectName, &$objectId, &$links) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke( 4, $op, $objectName, $objectId, $links, $op, 'civicrm_links' );
  }

  /**
   * This hook is invoked *before* building a CiviCRM form
   * 
   * After form preprocessed, form already prepared to be build
   * Use this hook to add more preparation before adding element.
   * After this, we will invoke buildQuickForm in Core/Form
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function preProcess($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    $null = &CRM_Core_DAO::$_nullObject;
    return $className::invoke( 2, $formName, $form, $null, $null, $null, 'civicrm_preProcess' );
  }


  /**
   * This hook is invoked *after* building a CiviCRM form. This hook should also
   * be used to set the default values of a form element
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function buildForm($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke( 2, $formName, $form, $formName, $formName, $formName, 'civicrm_buildForm' );
  }

  /**
   * This hook is invoked when a CiviCRM form is before submit. If the module has injected
   * any form elements, this hook should save the values in the database
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function preSave($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  2, $formName, $form, $formName, $formName, $formName, 'civicrm_preSave' );
  }

  /**
   * This hook is invoked when a CiviCRM form is submitted. If the module has injected
   * any form elements, this hook should save the values in the database
   *
   * @param string $formName the name of the form
   * @param object $form     reference to the form object
   *
   * @return null the return value is ignored
   */
  static function postProcess($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  2, $formName, $form, $formName, $formName, $formName, 'civicrm_postProcess' );
  }

  /**
   * This hook is invoked during all CiviCRM form validation. An array of errors
   * detected is returned. Else we assume validation succeeded.
   *
   * @param string $formName the name of the form
   * @param array  &$fields   the POST parameters as filtered by QF
   * @param array  &$files    the FILES parameters as sent in by POST
   * @param array  &$form     the form object
   * @param array  $
   *
   * @return mixed             formRule hooks return a boolean or
   *                           an array of error messages which display a QF Error
   * @access public
   */
  static function validate($formName, &$fields, &$files, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  4, $formName, $fields, $files, $form, $formName, 'civicrm_validate' );
  }

  /**
   * This hook is called before a db write on a custom table
   *
   * @param string $op         the type of operation being performed
   * @param string $groupID    the custom group ID
   * @param object $entityID   the entityID of the row in the custom table
   * @param array  $params     the parameters that were sent into the calling function
   *
   * @return null the return value is ignored
   * @access public
   */
  static function custom($op, $groupID, $entityID, &$params) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  4, $op, $groupID, $entityID, $params, $op, 'civicrm_custom' );
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int $type the type of permission needed
   * @param array $tables (reference ) add the tables that are needed for the select clause
   * @param array $whereTables (reference ) add the tables that are needed for the where clause
   * @param int    $contactID the contactID for whom the check is made
   * @param string $where the currrent where clause
   *
   * @return null the return value is ignored
   * @access public
   */
  static function aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  5, $type, $tables, $whereTables, $contactID, $where, 'civicrm_aclWhereClause' );
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int    $type          the type of permission needed
   * @param int    $contactID     the contactID for whom the check is made
   * @param string $tableName     the tableName which is being permissioned
   * @param array  $allGroups     the set of all the objects for the above table
   * @param array  $currentGroups the set of objects that are currently permissioned for this contact
   *
   * @return null the return value is ignored
   * @access public
   */
  static function aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  5, $type, $contactID, $tableName, $allGroups, $currentGroups, 'civicrm_aclGroup' );
  }

  /**
   * This hook is called when building the menu table
   *
   * @param array $files The current set of files to process
   *
   * @return null the return value is ignored
   * @access public
   */
  static function xmlMenu(&$files) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $files, $null, $null, $null, $null, 'civicrm_xmlMenu' );
  }

  /**
   * Change menu item xml
   *
   * @param $items
   *   Contribution id
   */
  static function menuItems(&$items) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    return call_user_func_array([$config->userHookClass, 'invoke'], [
      1, &$items, &$null, &$null, &$null, &$null, 'civicrm_menuItems'
    ]);
  }

  /**
   * This hook is called when rendering the dashboard (q=civicrm/dashboard)
   *
   * @param int $contactID - the contactID for whom the dashboard is being rendered
   * @param int $contentPlacement - (output parameter) where should the hook content be displayed relative to the activity list
   *
   * @return string the html snippet to include in the dashboard
   * @access public
   */
  static function dashboard($contactID, &$contentPlacement = self::DASHBOARD_BELOW) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    $retval = $className::invoke( 2, $contactID, $contentPlacement, $null, $null, $null, 'civicrm_dashboard' );

    /* Note we need this seemingly unnecessary code because in the event that the implentation of the hook
		 * declares the second parameter but doesn't set it, then it comes back unset even
		 * though we have a default value in this function's declaration above. 
		 */

    if (!isset($contentPlacement)) {
      $contentPlacement = self::DASHBOARD_BELOW;
    }

    return $retval;
  }

  /**
   * This hook is called before storing recently viewed items.
   *
   * @param array $recentArray - an array of recently viewed or processed items, for in place modification
   *
   * @return array
   * @access public
   */
  static function recent(&$recentArray) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $null, $null, $null, $null, $null, 'civicrm_recent' );
  }

  /**
   * This hook is called when building the amount structure for a Contribution or Event Page
   *
   * @param int    $pageType - is this a contribution or event page
   * @param object $form     - reference to the form object
   * @param array  $amount   - the amount structure to be displayed
   *
   * @return null
   * @access public
   */
  static function buildAmount($pageType, &$form, &$amount) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $pageType, $form, $amount, $null, $null, 'civicrm_buildAmount' );
  }

  /**
   * This hook is called when rendering the tabs for a contact (q=civicrm/contact/view)c
   *
   * @param array $tabs      - the array of tabs that will be displayed
   * @param int   $contactID - the contactID for whom the dashboard is being rendered
   *
   * @return null
   * @access public
   */
  static function tabs(&$tabs, $contactID) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $tabs, $contactID, $null, $null, $null, 'civicrm_tabs' );
  }

  /**
   * This hook is called when sending an email / printing labels
   *
   * @param array $tokens    - the list of tokens that can be used for the contact
   *
   * @return null
   * @access public
   */
  static function tokens(&$tokens) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $tokens, $null, $null, $null, $null, 'civicrm_tokens' );
  }

  /**
   * This hook is called when sending an email / printing labels to get the values for all the
   * tokens returned by the 'tokens' hook
   *
   * @param array   $details    - the array to store the token values indexed by contactIDs (unless it a single)
   * @param array   $contactIDs - an array of contactIDs.
   * @param integer $job        - job id from mailing
   * @param array   $tokens     - token generated from hook::tokens
   * @param string  $context    - context when call this hook
   *
   * @return null
   * @access public
   */
  static function tokenValues(&$details, &$contactIDs, $job = NULL, $tokens = [], $context = NULL) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  5, $details, $contactIDs, $job, $tokens, $context, 'civicrm_tokenValues' );
  }

  /**
   * This hook is called before a CiviCRM Page is rendered. You can use this hook to insert smarty variables
   * in a  template
   *
   * @param object $page - the page that will be rendered
   *
   * @return null
   * @access public
   */
  static function pageRun(&$page) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $page, $null, $null, $null, $null, 'civicrm_pageRun' );
  }

  /**
   * This hook is called after a copy of an object has been made. The current objects are
   * Event, Contribution Page and UFGroup
   *
   * @param string $objectName - name of the object
   * @param object $object     - reference to the copy
   *
   * @return null
   * @access public
   */
  static function copy($objectName, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $objectName, $object, $null, $null, $null, 'civicrm_copy' );
  }

  static function invoke($numParams, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, $fnSuffix, $fnPrefix = '') {
    static $included = FALSE;

    $result = [];
    $fnName = "{$fnPrefix}_{$fnSuffix}";
    if (!function_exists($fnName)) {
      if ($included) {
        return;
      }

      // include external file
      $included = TRUE;

      $config = &CRM_Core_Config::singleton();
      if (!empty($config->customPHPPathDir) &&
        file_exists("{$config->customPHPPathDir}/civicrmHooks.php")
      ) {
        @include_once ("civicrmHooks.php");
      }

      if (!function_exists($fnName)) {
        return;
      }
    }

    if ($numParams == 1) {
      $result = $fnName($arg1);
    }
    elseif ($numParams == 2) {
      $result = $fnName($arg1, $arg2);
    }
    elseif ($numParams == 3) {
      $result = $fnName($arg1, $arg2, $arg3);
    }
    elseif ($numParams == 4) {
      $result = $fnName($arg1, $arg2, $arg3, $arg4);
    }
    elseif ($numParams == 5) {
      $result = $fnName($arg1, $arg2, $arg3, $arg4, $arg5);
    }

    return empty($result) ? TRUE : $result;
  }

  static function customFieldOptions($customFieldID, &$options, $detailedFormat = FALSE) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $customFieldID, $options, $detailedFormat, $null, $null, 'civicrm_customFieldOptions' );
  }

  static function searchTasks($objectType, &$tasks) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $objectType, $tasks, $null, $null, $null, 'civicrm_searchTasks' );
  }

  static function eventDiscount(&$form, &$params) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $form, $params, $null, $null, $null, 'civicrm_eventDiscount' );
  }

  static function mailingGroups(&$form, &$groups, &$mailings) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $form, $groups, $mailings, $null, $null, 'civicrm_mailingGroups' );
  }

  static function membershipTypeValues(&$form, &$membershipTypes) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $form, $membershipTypes, $null, $null, $null, 'civicrm_membershipTypeValues' );
  }

  /**
   * This hook is called when rendering the contact summary
   *
   * @param int $contactID - the contactID for whom the summary is being rendered
   * @param int $contentPlacement - (output parameter) where should the hook content be displayed relative to the existing content
   *
   * @return string the html snippet to include in the contact summary
   * @access public
   */
  static function summary($contactID, &$content, &$contentPlacement = self::SUMMARY_BELOW) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $contactID, $content, $contentPlacement, $null, $null, 'civicrm_summary' );
  }

  static function contactListQuery(&$query, $name, $context, $id) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  4, $query, $name, $context, $id, $null, 'civicrm_contactListQuery' );
  }

  /**
   * Hook definition for altering payment parameters before talking to a payment processor back end.
   *
   * Definition will look like this:
   *
   *   function hook_civicrm_alterPaymentProcessorParams($paymentObj,
   *                                                     &$rawParams, &$cookedParams);
   *
   * @param string $paymentObj
   *    instance of payment class of the payment processor invoked (e.g., 'CRM_Core_Payment_Dummy')
   * @param array &$rawParams
   *    array of params as passed to to the processor
   * @params array  &$cookedParams
   *     params after the processor code has translated them into its own key/value pairs
   *
   * @return void
   */

  static function alterPaymentProcessorParams($paymentObj,
    &$rawParams,
    &$cookedParams
  ) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $paymentObj, $rawParams, $cookedParams, $null, $null, 'civicrm_alterPaymentProcessorParams' );
  }

  static function alterMailParams(&$params) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $params, $null, $null, $null, $null, 'civicrm_alterMailParams' );
  }

  /**
   * This hook is called when rendering the Manage Case screen
   *
   * @param int $caseID - the case ID
   *
   * @return array of data to be displayed, where the key is a unique id to be used for styling (div id's) and the value is an array with keys 'label' and 'value' specifying label/value pairs
   * @access public
   */
  static function caseSummary($caseID) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $caseID, $null, $null, $null, $null, 'civicrm_caseSummary' );
  }

  static function config(&$config) {
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $config, $null, $null, $null, $null, 'civicrm_config' );
  }

  static function enableDisable($recordBAO, $recordID, $isActive) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $recordBAO, $recordID, $isActive, $null, $null, 'civicrm_enableDisable' );
  }

  /**
   * This hooks allows to change option values
   *
   * @param $options associated array of option values / id
   * @param $name    option group name
   *
   * @access public
   */
  static function optionValues(&$options, $name) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  2, $options, $name, $null, $null, $null, 'civicrm_optionValues' );
  }

  /**
   * This hook allows modification of the navigation menu.
   *
   * @param $params associated array of navigation menu entry to Modify/Add
   * @access public
   */
  static function navigationMenu(&$params) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $params, $null, $null, $null, $null, 'civicrm_navigationMenu' );
  }

  /**
   * This hook allows modification of the data used to perform merging of duplicates.
   *
   * @param string $type the type of data being passed (cidRefs|eidRefs|relTables|sqls)
   * @param array $data  the data, as described in $type
   * @param int $mainId  contact_id of the contact that survives the merge
   * @param int $otherId contact_id of the contact that will be absorbed and deleted
   * @param array $tables when $type is "sqls", an array of tables as it may have been handed to the calling function
   *
   * @access public
   */
  static function merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
    $config = &CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(  5, $type, $data, $mainId, $otherId, $tables , 'civicrm_merge' );
  }

  /**
   * This hook provides a way to override the default privacy behavior for notes.
   *
   * @param array $note (reference) Associative array of values for this note
   *
   * @access public
   */
  static function notePrivacy(&$noteValues) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  1, $noteValues, $null, $null, $null, $null, 'civicrm_notePrivacy' );
  }

  /**
   * This hook is called before record is exported as CSV
   *
   * @param string $exportTempTable - name of the temporary export table used during export
   * @param array  $headerRows      - header rows for output
   * @param array  $sqlColumns      - SQL columns
   * @param int    $exportMode      - export mode ( contact, contribution, etc...)
   *
   * @return void
   * @access public
   */
  static function export(&$exportTempTable, &$headerRows, &$sqlColumns, &$exportMode, $mappingId) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  5, $exportTempTable, $headerRows, $sqlColumns, $exportMode, $mappingId, 'civicrm_export' );
  }

  /**
   * This hook allows modification of the queries constructed from dupe rules.
   *
   * @param string $obj object of rulegroup class
   * @param string $type type of queries e.g table / threshold
   * @param array  $query set of queries
   *
   * @access public
   */
  static function dupeQuery($obj, $type, &$query) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  3, $obj, $type, $query, $null, $null , 'civicrm_dupeQuery' );
  }

  /**
   * This hook is called after a row has been processed and the
   * record (and associated records imported
   *
   * @param string  $object     - object being imported (for now Contact only, later Contribution, Activity, Participant and Member)
   * @param string  $usage      - hook usage/location (for now process only, later mapping and others)
   * @param string  $objectRef  - import record object
   * @param array   $params     - array with various key values: currently
   *                  contactID       - contact id
   *                  importID        - row id in temp table
   *                  importTempTable - name of tempTable
   *                  fieldHeaders    - field headers
   *                  fields          - import fields
   *
   * @return void
   * @access public
   */
  static function import($object, $usage, &$objectRef, &$params) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(  4, $object, $usage, $objectRef, $params, $null, 'civicrm_import' );
  }

  /**
   * This hook is called when API permissions are checked (cf. civicrm_api3_api_check_permission()
   * in api/v3/utils.php and _civicrm_api3_permissions() in CRM/Core/DAO/.permissions.php).
   *
   * @param string $entity       the API entity (like contact)
   * @param string $action       the API action (like get)
   * @param array &$params       the API parameters
   * @param array &$permisisons  the associative permissions array (probably to be altered by this hook)
   */
  static function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $entity, $action, $params, $permissions, $null, 'civicrm_alterAPIPermissions' );
  }

  /**
   * This hook is called from CRM_Core_Selector_Controller through which all searches in civicrm go.
   * This enables us hook implementors to modify both the headers and the rows
   *
   * The BIGGEST drawback with this hook is that you may need to modify the result template to include your
   * fields. The result files are CRM/{Contact,Contribute,Member,Event...}/Form/Selector.tpl
   *
   * However, if you use the same number of columns, you can overwrite the existing columns with the values that
   * you want displayed. This is a hackish, but avoids template modification.
   *
   * @param string $objectName the component name that we are doing the search
   *                           activity, campaign, case, contact, contribution, event, grant, membership, and pledge
   * @param array  &$headers   the list of column headers, an associative array with keys: ( name, sort, order )
   * @param array  &$rows      the list of values, an associate array with fields that are displayed for that component
   * @param array  &$seletor   the selector object. Allows you access to the context of the search
   *
   * @return void  modify the header and values object to pass the data u need
   */
  static function searchColumns($objectName, &$headers, &$rows, &$selector) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $objectName, $headers, $rows, $selector, $null, 'civicrm_searchColumns' );
  }

  /**
   * Alter receipt id prefix
   */
  static function alterReceiptId(&$prefix, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 2, $prefix, $object, $null, $null, $null, 'civicrm_alterReceiptId' );
  }

  /**
   * BaseIPN pre hook
   */
  static function ipnPre($type, &$objects, &$input = NULL, &$ids = NULL, &$values = NULL) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 5, $type, $objects, $input, $ids, $values, 'civicrm_ipnPre' );
  }
  /**
   * BaseIPN post hook
   */
  static function ipnPost($type, &$objects, &$input = NULL, &$ids = NULL, &$values = NULL) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 5, $type, $objects, $input, $ids, $values, 'civicrm_ipnPost' );
  }

  /**
   * This hooks allows alteration of generated page content.
   *
   * @param $content
   *   Previously generated content.
   * @param $context
   *   Context of content - page or form.
   * @param $tplName
   *   The file name of the tpl.
   * @param $object
   *   A reference to the page or form object.
   *
   * @return mixed
   */
  static function alterContent(&$content, $context, $tplName, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $content, $context, $tplName, $object, $null, 'civicrm_alterContent' );
  }

  /**
   * This hooks allows alteration of the tpl file used to generate content. It differs from the
   * altercontent hook as the content has already been rendered through the tpl at that point
   *
   * @param $formName
   *   Previously generated content.
   * @param $form
   *   Reference to the form object.
   * @param $context
   *   Context of content - page or form.
   * @param $tplName
   *   Reference the file name of the tpl.
   *
   * @return mixed
   */
  static function alterTemplateFile($formName, &$form, $context, &$tplName) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $formName, $form, $context, $tplName, $null, 'civicrm_alterTemplateFile' );
  }

  /**
   * This hooks allows alteration of the template variables before render content.
   * It differs from the alterContent hook as the content has already been rendered
   * through the tpl at that point
   *
   * @param $resourceName
   *   Previously generated content.
   * @param $vars
   *   Variables before render template.
   *
   * @return mixed
   */
  static function alterTemplateVars(&$resourceName, &$vars) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 2, $resourceName, $vars, $null, $null, $null, 'civicrm_alterTemplateVars' );
  }

  /**
   * This hooks allows other module prepare variable to pass into invoice
   *
   * @param $contribution_id
   *   Contribution id
   * @param $tplParams
   *   Variable to save variables 
   *
   * @return mixed
   */
  static function prepareInvoice($contribution_id, &$tplParams, $message) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 3, $contribution_id, $tplParams, $message, $null, $null, 'civicrm_prepareInvoice' );
  }

  /**
   * This hooks allows other module invoke tax receipt info
   *
   * @param $contributionId
   *   Contribution id
   * @param $tplParams
   *   Prepare template printing element
   * @param $taxReceipt
   *   TaxReceipt object return from contribution
   * @param $object
   *   Variable to save variables
   *
   * @return mixed
   */
  static function prepareTaxReceipt($contributionId, &$tplParams, &$taxReceipt, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $contributionId, $tplParams, $taxReceipt, $object, $null, 'civicrm_prepareTaxReceipt' );
  }

  /**
   * validate tax receipt info
   * 
   * @param $contributionId
   *   Contribution id
   * @param $receipt
   *   Receipt object stored here.
   */
  static function validateTaxReceipt($contributionId, &$receipt) {
    $config = CRM_Core_Config::singleton();
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      2, &$contributionId, &$receipt, &$null, &$null, &$null, 'civicrm_validateTaxReceipt'
    ]);
  }

  /**
   * Add registration validation here
   *
   * @param $contributionId
   */
  static function checkRegistration($contactID, $fields, $self, $isAdditional, &$result){
    $config = CRM_Core_Config::singleton();
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      5, &$contactID, &$fields, &$self, &$isAdditional, &$result, 'civicrm_checkRegistration'
    ]);
  }

  /**
   * Modify params when save payment result data
   *
   * @param $contributionId
   */
  static function alterTapPayResponse($response, &$object, $provider = 'TapPay', $apiType = ''){
    $config = CRM_Core_Config::singleton();
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      4, &$response, &$object, &$provider, &$apiType, &$null, 'civicrm_alterTapPayResponse'
    ]);
  }

  /**
   * Change validation result of coupon
   *
   * @param array   $coupon coupon array from CRM_Coupon_BAO::getCoupon
   * @param boolean $valid  will pass previous validation result. Can be modify by hook.
   * @param string  $phase  validation phase. For example, event have 2 phase. first is code, second is available events
   */
  static function validateCoupon(&$coupon, &$valid, $phase){
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 3, $coupon, $valid, $phase, $null, $null, 'civicrm_validateCoupon' );
  }

  /**
   * Modifies the API response content before it is returned.
   * 
   * @param string $entity  The entity being called by this api.
   * @param string $action  The action being performed (e.g. GET, CREATE, DELETE).
   * @param array  $params  The query parameters sent with the API request.
   * @param array  $result The API response data, passed by reference for modification.
   */
  static function alterAPIResult($entity, $action, $params, &$result) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $entity, $action, $params, $result, $null, 'civicrm_alterAPIResult' );
  }

  /**
   * Alter query of CRM_Contact_BAO_Query
   *
   * @param int Mode of query component, check constant of CRM_Contact_BAO_Query::MODE_
   * @param string $part Query component to alter in lower case. Available: select, from, where, having
   * @param object $object Query object of CRM_Contact_BAO_Query
   * @param array $additional Additional parameter for alter query
   *   - part == 'from', use $additional['tables'] to alter query
   */
  static function alterQuery($mode, $part, &$object, &$additional) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke( 4, $mode, $part, $object, $additional, $null, 'civicrm_alterQuery' );
  }
}

