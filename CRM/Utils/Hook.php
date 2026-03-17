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
  public const DASHBOARD_BELOW = 1;
  // Place content above activity list
  public const DASHBOARD_ABOVE = 2;
  // Don't display activity list at all
  public const DASHBOARD_REPLACE = 3;

  // by default - place content below existing content
  public const SUMMARY_BELOW = 1;
  // pace hook content above
  public const SUMMARY_ABOVE = 2;
  // create your own summarys
  public const SUMMARY_REPLACE = 3;

  /**
   * This will return implemented module of hook
   *
   * @param string $hook
   *   Hook name with civicrm to search (e.g., 'civicrm_pre').
   *
   * @return array
   *   Array of module names implementing the given hook.
   */
  public static function availableHooks($hook) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::availableHooks($hook);
  }

  /**
   * This hook is called when dao->find() is triggered.
   *
   * @param bool $fetch
   *   Whether dao->find() was called to fetch the first result.
   * @param CRM_Core_DAO &$dao
   *   The DAO object on which find() was called, passed by reference.
   * @param int $numRows
   *   Number of rows returned after dao->find().
   *
   * @return null
   */
  public static function get($fetch, &$dao, $numRows) {
    $className = CRM_Core_Config::singleton()->userHookClass;
    $null = &CRM_Core_DAO::$_nullObject;
    return $className::invoke(3, $fetch, $dao, $numRows, $null, $null, 'civicrm_get');
  }

  /**
   * This hook is called before a db write on some core objects.
   *
   * @param string $op
   *   The type of operation being performed. One of: 'create', 'edit', 'delete'.
   * @param string $objectName
   *   The name of the object (e.g., 'Contribution', 'Contact', 'Membership').
   * @param int|null $id
   *   The object ID if available. NULL for 'create' operations.
   * @param array &$params
   *   The parameters used for object creation or editing, passed by reference.
   *
   * @return null
   */
  public static function pre($op, $objectName, $id, &$params) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(4, $op, $objectName, $id, $params, $op, 'civicrm_pre');
  }

  /**
   * This hook is called after a db write on some core objects.
   *
   * @param string $op
   *   The type of operation that was performed. One of: 'create', 'edit', 'delete'.
   * @param string $objectName
   *   The name of the object (e.g., 'Contribution', 'Contact', 'Membership').
   * @param int $objectId
   *   The unique identifier for the object.
   * @param CRM_Core_DAO &$objectRef
   *   Reference to the DAO object, passed by reference.
   *
   * @return null
   */
  public static function post($op, $objectName, $objectId, &$objectRef) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(4, $op, $objectName, $objectId, $objectRef, $op, 'civicrm_post');
  }

  /**
   * This hook retrieves links from other modules and injects them into the view contact tabs.
   *
   * @param string $op
   *   A string describing the context (e.g., 'view.contact.activity', 'create.new.shortcuts').
   * @param string|null $objectName
   *   The name of the object (e.g., 'Contact'), or NULL if not applicable.
   * @param int|null &$objectId
   *   The unique identifier for the object, passed by reference. May be NULL or a
   *   CRM_Core_DAO null placeholder when not applicable.
   * @param array &$links
   *   The links array to be modified, passed by reference. Each element is an
   *   associative array with keys: 'id', 'url', 'img', 'title', 'weight'.
   *
   * @return null
   */
  public static function links($op, $objectName, &$objectId, &$links) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(4, $op, $objectName, $objectId, $links, $op, 'civicrm_links');
  }

  /**
   * This hook is invoked *before* building a CiviCRM form.
   *
   * Called after preProcess() on the form. Use this hook to add preparation
   * logic before form elements are added. buildQuickForm() is invoked after this.
   *
   * @param string $formName
   *   The fully qualified class name of the form (e.g., 'CRM_Contribute_Form_Contribution'),
   *   obtained via get_class($this).
   * @param CRM_Core_Form &$form
   *   Reference to the form object, passed by reference.
   *
   * @return null
   */
  public static function preProcess($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    $null = &CRM_Core_DAO::$_nullObject;
    return $className::invoke(2, $formName, $form, $null, $null, $null, 'civicrm_preProcess');
  }

  /**
   * This hook is invoked *after* building a CiviCRM form.
   *
   * Use this hook to add custom form elements or set their default values.
   *
   * @param string $formName
   *   The fully qualified class name of the form (e.g., 'CRM_Contribute_Form_Contribution'),
   *   obtained via get_class($this).
   * @param CRM_Core_Form &$form
   *   Reference to the form object, passed by reference.
   *
   * @return null
   */
  public static function buildForm($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(2, $formName, $form, $formName, $formName, $formName, 'civicrm_buildForm');
  }

  /**
   * This hook is invoked just before a CiviCRM form is submitted.
   *
   * If the module has injected custom form elements, use this hook to
   * persist those values to the database before the main form submission runs.
   *
   * @param string $formName
   *   The fully qualified class name of the form (e.g., 'CRM_Contribute_Form_Contribution'),
   *   obtained via get_class($this).
   * @param CRM_Core_Form &$form
   *   Reference to the form object, passed by reference.
   *
   * @return null
   */
  public static function preSave($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(2, $formName, $form, $formName, $formName, $formName, 'civicrm_preSave');
  }

  /**
   * This hook is invoked after a CiviCRM form has been submitted.
   *
   * If the module has injected custom form elements, use this hook to
   * persist those values to the database after the main form submission runs.
   *
   * @param string $formName
   *   The fully qualified class name of the form (e.g., 'CRM_Contribute_Form_Contribution'),
   *   obtained via get_class($this).
   * @param CRM_Core_Form &$form
   *   Reference to the form object, passed by reference.
   *
   * @return null
   */
  public static function postProcess($formName, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(2, $formName, $form, $formName, $formName, $formName, 'civicrm_postProcess');
  }

  /**
   * This hook is invoked during CiviCRM form validation.
   *
   * Return an array of error messages to report validation failures,
   * or an empty value to indicate that validation passed.
   *
   * @param string $formName
   *   The fully qualified class name of the form (e.g., 'CRM_Contribute_Form_Contribution'),
   *   obtained via get_class($this).
   * @param array &$fields
   *   The POST parameters as filtered by QuickForm ($this->_submitValues),
   *   passed by reference.
   * @param array &$files
   *   The FILES parameters as sent by POST ($this->_submitFiles), passed by reference.
   * @param CRM_Core_Form &$form
   *   Reference to the form object, passed by reference.
   *
   * @return null
   */
  public static function validate($formName, &$fields, &$files, &$form) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(4, $formName, $fields, $files, $form, $formName, 'civicrm_validate');
  }

  /**
   * This hook is called after a db write on a custom table.
   *
   * @param string $op
   *   The type of operation being performed. One of: 'create', 'edit', 'delete'.
   * @param int $groupID
   *   The custom group ID of the custom data being written.
   * @param int $entityID
   *   The entity ID of the row in the custom table (e.g., contact ID, contribution ID).
   * @param array &$params
   *   The parameters that were sent into the calling function, passed by reference.
   *
   * @return null
   */
  public static function custom($op, $groupID, $entityID, &$params) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(4, $op, $groupID, $entityID, $params, $op, 'civicrm_custom');
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
  public static function aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(5, $type, $tables, $whereTables, $contactID, $where, 'civicrm_aclWhereClause');
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
  public static function aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(5, $type, $contactID, $tableName, $allGroups, $currentGroups, 'civicrm_aclGroup');
  }

  /**
   * This hook is called when building the menu table
   *
   * @param array $files The current set of files to process
   *
   * @return null the return value is ignored
   * @access public
   */
  public static function xmlMenu(&$files) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $files, $null, $null, $null, $null, 'civicrm_xmlMenu');
  }

  /**
   * This hook is called when building the navigation menu items.
   *
   * Allows modules to add, modify, or remove items from the CiviCRM
   * navigation menu before it is rendered.
   *
   * @param array &$items
   *   Associative array of menu item definitions, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function menuItems(&$items) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
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
  public static function dashboard($contactID, &$contentPlacement = self::DASHBOARD_BELOW) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    $retval = $className::invoke(2, $contactID, $contentPlacement, $null, $null, $null, 'civicrm_dashboard');

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
  public static function recent(&$recentArray) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $recentArray, $null, $null, $null, $null, 'civicrm_recent');
  }

  /**
   * This hook is called when building the amount structure for a Contribution or Event Page
   *
   * @param string    $pageType - is this a contribution or event page
   * @param object $form     - reference to the form object
   * @param array  $amount   - the amount structure to be displayed
   *
   * @return null
   * @access public
   */
  public static function buildAmount($pageType, &$form, &$amount) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $pageType, $form, $amount, $null, $null, 'civicrm_buildAmount');
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
  public static function tabs(&$tabs, $contactID) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $tabs, $contactID, $null, $null, $null, 'civicrm_tabs');
  }

  /**
   * This hook is called when sending an email / printing labels
   *
   * @param array $tokens    - the list of tokens that can be used for the contact
   *
   * @return null
   * @access public
   */
  public static function tokens(&$tokens) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $tokens, $null, $null, $null, $null, 'civicrm_tokens');
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
  public static function tokenValues(&$details, &$contactIDs, $job = NULL, $tokens = [], $context = NULL) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(5, $details, $contactIDs, $job, $tokens, $context, 'civicrm_tokenValues');
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
  public static function pageRun(&$page) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $page, $null, $null, $null, $null, 'civicrm_pageRun');
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
  public static function copy($objectName, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $objectName, $object, $null, $null, $null, 'civicrm_copy');
  }

  /**
   * Dispatch a hook invocation to the registered hook function.
   *
   * This is the underlying dispatcher used by all specific hook methods.
   * It calls the hook function named "{$fnPrefix}_{$fnSuffix}" with up to
   * 5 parameters based on $numParams. Includes the custom PHP file from
   * $config->customPHPPathDir if the function is not yet defined.
   *
   * @param int $numParams
   *   Number of arguments to pass to the hook function (1–5).
   * @param mixed &$arg1
   *   First argument passed by reference to the hook function.
   * @param mixed &$arg2
   *   Second argument passed by reference to the hook function.
   * @param mixed &$arg3
   *   Third argument passed by reference to the hook function.
   * @param mixed &$arg4
   *   Fourth argument passed by reference to the hook function.
   * @param mixed &$arg5
   *   Fifth argument passed by reference to the hook function.
   * @param string $fnSuffix
   *   The hook name suffix (e.g., 'civicrm_pre'). Combined with $fnPrefix to form the function name.
   * @param string $fnPrefix
   *   Optional prefix prepended to $fnSuffix when building the function name.
   *
   * @return mixed
   *   TRUE if the hook function returns an empty result, otherwise the hook's return value.
   */
  public static function invoke($numParams, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, $fnSuffix, $fnPrefix = '') {
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
        @include_once("civicrmHooks.php");
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

  /**
   * This hook is called to alter the options for a custom field.
   *
   * Allows modules to add, remove, or modify the selectable options for a
   * custom field before they are displayed to the user.
   *
   * @param int $customFieldID
   *   The ID of the custom field whose options are being retrieved.
   * @param array &$options
   *   Associative array of option values passed by reference for modification.
   *   When $detailedFormat is FALSE, keys are option values and values are labels.
   *   When $detailedFormat is TRUE, each entry contains detailed metadata.
   * @param bool $detailedFormat
   *   If TRUE, $options contains detailed metadata per option (id, label, value, etc.).
   *   If FALSE (default), a simple value-to-label mapping is used.
   *
   * @return null the return value is ignored
   */
  public static function customFieldOptions($customFieldID, &$options, $detailedFormat = FALSE) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $customFieldID, $options, $detailedFormat, $null, $null, 'civicrm_customFieldOptions');
  }

  /**
   * This hook is called when building the list of bulk-action tasks for a search result set.
   *
   * Allows modules to add, remove, or modify the task options available in the
   * task dropdown for search results of a given entity type.
   *
   * @param string $objectType
   *   The type of entity being searched. One of: 'contact', 'contribution',
   *   'event', 'membership', 'activity', 'grant', 'case', 'pledge', 'campaign'.
   * @param array &$tasks
   *   Associative array of task definitions passed by reference, keyed by task
   *   constant. Each entry contains 'title' (string), 'class' (string or array),
   *   and optionally 'result' (bool).
   *
   * @return null the return value is ignored
   */
  public static function searchTasks($objectType, &$tasks) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $objectType, $tasks, $null, $null, $null, 'civicrm_searchTasks');
  }

  /**
   * This hook is called during event registration to allow applying custom discounts.
   *
   * Called from the event registration confirmation form, allowing modules to
   * apply custom discount logic and modify registration fee parameters.
   *
   * @param CRM_Core_Form &$form
   *   Reference to the event registration form object (typically
   *   CRM_Event_Form_Registration_Confirm), passed by reference.
   * @param array &$params
   *   Array of registration parameters (e.g., participant details, fees),
   *   passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function eventDiscount(&$form, &$params) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $form, $params, $null, $null, $null, 'civicrm_eventDiscount');
  }

  /**
   * This hook is called when building the recipient group selector for a mailing.
   *
   * Allows modules to add, remove, or modify the available groups and completed
   * mailings that can be selected as mailing recipients in the mailing form.
   *
   * @param CRM_Core_Form &$form
   *   Reference to the mailing group form object (CRM_Mailing_Form_Group),
   *   passed by reference.
   * @param array &$groups
   *   Associative array of available groups keyed by group ID with group label
   *   as value (e.g., from CRM_Core_PseudoConstant::group()), passed by reference.
   * @param array &$mailings
   *   Associative array of completed mailings keyed by mailing ID with mailing
   *   name as value, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function mailingGroups(&$form, &$groups, &$mailings) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $form, $groups, $mailings, $null, $null, 'civicrm_mailingGroups');
  }

  /**
   * This hook is called when building the list of available membership types for a form.
   *
   * Allows modules to alter the membership type data displayed on membership
   * sign-up and renewal forms, such as modifying fees or filtering types.
   *
   * @param CRM_Core_Form &$form
   *   Reference to the form object requesting the membership types, passed by reference.
   * @param array &$membershipTypes
   *   Associative array of membership type data keyed by type ID. Each entry
   *   contains keys such as 'id' (int), 'name' (string), 'minimum_fee' (float),
   *   'is_active' (bool), 'description' (string), and 'contribution_type_id' (int).
   *   Passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function membershipTypeValues(&$form, &$membershipTypes) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $form, $membershipTypes, $null, $null, $null, 'civicrm_membershipTypeValues');
  }

  /**
   * This hook is called when rendering the contact summary
   *
   * @param int $contactID
   *   The contactID for whom the summary is being rendered.
   * @param string &$content
   *   The HTML content to be displayed in the summary, passed by reference for modification.
   * @param int &$contentPlacement
   *   (output parameter) Where the hook content should be displayed relative to the
   *   existing content. Use self::SUMMARY_BELOW (default), self::SUMMARY_ABOVE,
   *   or self::SUMMARY_REPLACE.
   *
   * @return null the return value is ignored
   */
  public static function summary($contactID, &$content, &$contentPlacement = self::SUMMARY_BELOW) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $contactID, $content, $contentPlacement, $null, $null, 'civicrm_summary');
  }

  /**
   * This hook is called when building the SQL query for the contact list autocomplete lookup.
   *
   * Allows modules to modify the SQL SELECT query used for contact name searches,
   * enabling custom filtering, scoping, or result ordering.
   *
   * @param string &$query
   *   The SQL SELECT query string used to retrieve matching contacts, passed by
   *   reference for modification.
   * @param string $name
   *   The search term entered by the user to match against contact names.
   * @param string|null $context
   *   Optional context string describing where the lookup is being called from
   *   (e.g., from a $_GET or API parameter).
   * @param int|null $id
   *   Optional entity ID to filter or scope the contact list query.
   *
   * @return null the return value is ignored
   */
  public static function contactListQuery(&$query, $name, $context, $id) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $query, $name, $context, $id, $null, 'civicrm_contactListQuery');
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
   *   Instance of payment class of the payment processor invoked (e.g., 'CRM_Core_Payment_Dummy').
   * @param array &$rawParams
   *   Array of params as passed to the processor, passed by reference for modification.
   * @param array &$cookedParams
   *   Params after the processor code has translated them into its own key/value pairs,
   *   passed by reference for modification.
   *
   * @return null the return value is ignored
   */

  public static function alterPaymentProcessorParams(
    $paymentObj,
    &$rawParams,
    &$cookedParams
  ) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $paymentObj, $rawParams, $cookedParams, $null, $null, 'civicrm_alterPaymentProcessorParams');
  }

  /**
   * This hook is called before an outgoing email is sent, allowing modification of mail parameters.
   *
   * Implementations can inspect or alter any mail parameter (from, to, subject,
   * body, attachments, etc.) before the message is dispatched. The 'alterTag'
   * key in $params indicates the mail context: 'mail' for transactional system
   * mails, 'transactional' for transactional mailings, or 'civimail' for bulk
   * mailing. The 'alterTag' key is removed by the caller after this hook returns.
   *
   * @param array &$params
   *   Associative array of mail parameters passed by reference. Common keys include:
   *   'from', 'toEmail', 'toName', 'Subject', 'text', 'html', 'attachments',
   *   'images', 'alterTag', and 'returnPath'.
   *
   * @return null the return value is ignored
   */
  public static function alterMailParams(&$params) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $params, $null, $null, $null, $null, 'civicrm_alterMailParams');
  }

  /**
   * This hook is called when rendering the Manage Case screen
   *
   * @param int $caseID - the case ID
   *
   * @return array of data to be displayed, where the key is a unique id to be used for styling (div id's) and the value is an array with keys 'label' and 'value' specifying label/value pairs
   * @access public
   */
  public static function caseSummary($caseID) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $caseID, $null, $null, $null, $null, 'civicrm_caseSummary');
  }

  /**
   * This hook is called at the end of CRM_Core_Config initialization.
   *
   * Allows modules to inspect or modify the global CiviCRM configuration object
   * immediately after it has been fully initialized. Use this hook to override
   * configuration settings at runtime.
   *
   * @param CRM_Core_Config &$config
   *   Reference to the CiviCRM configuration singleton object, passed by reference
   *   so hook implementations can alter configuration properties.
   *
   * @return null the return value is ignored
   */
  public static function config(&$config) {
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $config, $null, $null, $null, $null, 'civicrm_config');
  }

  /**
   * This hook is called after a CiviCRM record has been enabled or disabled.
   *
   * Allows modules to react to is_active toggle events on CiviCRM records,
   * such as updating related data or triggering notifications. Typically
   * invoked via AJAX from the contact or admin pages.
   *
   * @param string $recordBAO
   *   The BAO class name of the record being toggled
   *   (e.g., 'CRM_Contribute_BAO_Contribution').
   * @param int $recordID
   *   The ID of the record being enabled or disabled.
   * @param bool $isActive
   *   TRUE if the record is being enabled, FALSE if being disabled.
   *
   * @return null the return value is ignored
   */
  public static function enableDisable($recordBAO, $recordID, $isActive) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $recordBAO, $recordID, $isActive, $null, $null, 'civicrm_enableDisable');
  }

  /**
   * This hooks allows to change option values
   *
   * @param array &$options
   *   Associative array of option values indexed by option value/ID,
   *   passed by reference for modification.
   * @param string $name
   *   The option group name (e.g., 'case_activity_type').
   *
   * @return null the return value is ignored
   */
  public static function optionValues(&$options, $name) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $options, $name, $null, $null, $null, 'civicrm_optionValues');
  }

  /**
   * This hook allows modification of the navigation menu.
   *
   * @param array &$params
   *   Associative array of navigation menu entries to modify or add to,
   *   passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function navigationMenu(&$params) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $params, $null, $null, $null, $null, 'civicrm_navigationMenu');
  }

  /**
   * This hook allows modification of the data used to perform merging of duplicates.
   *
   * @param string $type
   *   The type of data being passed (cidRefs|eidRefs|relTables|sqls).
   * @param array &$data
   *   The data, as described by $type, passed by reference for modification.
   * @param int|null $mainId
   *   contact_id of the contact that survives the merge.
   * @param int|null $otherId
   *   contact_id of the contact that will be absorbed and deleted.
   * @param array|null $tables
   *   When $type is "sqls", an array of tables as it may have been handed to the calling function.
   *
   * @return null the return value is ignored
   */
  public static function merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
    $config = &CRM_Core_Config::singleton();
    $className = $config->userHookClass;
    return $className::invoke(5, $type, $data, $mainId, $otherId, $tables, 'civicrm_merge');
  }

  /**
   * This hook provides a way to override the default privacy behavior for notes.
   *
   * @param array &$noteValues
   *   Associative array of values for this note, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function notePrivacy(&$noteValues) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(1, $noteValues, $null, $null, $null, $null, 'civicrm_notePrivacy');
  }

  /**
   * This hook is called before record is exported as CSV
   *
   * @param string &$exportTempTable
   *   Name of the temporary export table used during export.
   * @param array &$headerRows
   *   Header rows for the CSV output, passed by reference for modification.
   * @param array &$sqlColumns
   *   SQL column definitions for the export query, passed by reference for modification.
   * @param int &$exportMode
   *   Export mode constant (e.g., CRM_Export_Form_Select::CONTACT_EXPORT).
   * @param int|null $mappingId
   *   Optional mapping ID used for the export, or NULL if none.
   *
   * @return null the return value is ignored
   */
  public static function export(&$exportTempTable, &$headerRows, &$sqlColumns, &$exportMode, $mappingId) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(5, $exportTempTable, $headerRows, $sqlColumns, $exportMode, $mappingId, 'civicrm_export');
  }

  /**
   * This hook allows modification of the queries constructed from dupe rules.
   *
   * @param string $obj
   *   Object of rulegroup class.
   * @param string $type
   *   Type of queries, e.g., 'table' or 'threshold'.
   * @param array &$query
   *   Set of queries, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function dupeQuery($obj, $type, &$query) {
    $config = &CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $obj, $type, $query, $null, $null, 'civicrm_dupeQuery');
  }

  /**
   * This hook is called after a row has been processed and the
   * record (and associated records imported
   *
   * @param string  $entity - entity type being imported, case insensitive
   * @param string  $usage      - hook usage/location (for now process only, later mapping and others)
   * @param string  $objectRef  - import record object
   * @param array   $params     - array with various key values: currently
   *                  contactID       - contact id
   *                  importID        - row id in temp table
   *                  importTempTable - name of tempTable
   *                  fieldHeaders    - field headers
   *                  fields          - import fields
   *
   * @return null the return value is ignored
   */
  public static function import($entity, $usage, &$objectRef, &$params) {
    $config = CRM_Core_Config::singleton();
    $entity = strtolower($entity);
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $entity, $usage, $objectRef, $params, $null, 'civicrm_import');
  }

  /**
   * This hook is called when API permissions are checked (cf. civicrm_api3_api_check_permission()
   * in api/v3/utils.php and _civicrm_api3_permissions() in CRM/Core/DAO/.permissions.php).
   *
   * @param string $entity
   *   The API entity (e.g., 'contact').
   * @param string $action
   *   The API action (e.g., 'get').
   * @param array &$params
   *   The API parameters, passed by reference.
   * @param array &$permissions
   *   The associative permissions array, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $entity, $action, $params, $permissions, $null, 'civicrm_alterAPIPermissions');
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
   * @return null the return value is ignored
   */
  public static function searchColumns($objectName, &$headers, &$rows, &$selector) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $objectName, $headers, $rows, $selector, $null, 'civicrm_searchColumns');
  }

  /**
   * This hook allows modification of the receipt ID prefix before it is applied to a contribution.
   *
   * @param string &$prefix
   *   The receipt ID prefix string (e.g., a date-formatted string), passed by reference for modification.
   * @param CRM_Contribute_DAO_Contribution &$object
   *   The contribution object for which the receipt ID is being generated, passed by reference.
   *
   * @return null the return value is ignored
   */
  public static function alterReceiptId(&$prefix, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $prefix, $object, $null, $null, $null, 'civicrm_alterReceiptId');
  }

  /**
   * This hook is called before the BaseIPN processes a payment notification (IPN).
   *
   * @param string $type
   *   The type of payment notification being processed.
   *   One of: 'failed', 'pending', 'cancelled', 'complete'.
   * @param array &$objects
   *   Associative array of CiviCRM DAO objects involved in the IPN transaction
   *   (e.g., 'contribution', 'membership', 'participant', 'event', 'contact').
   * @param array|null &$input
   *   Payment input data passed from the payment processor, or NULL for status-only notifications.
   * @param array|null &$ids
   *   Associative array of entity IDs related to this transaction, or NULL.
   * @param array|null &$values
   *   Additional values array for the transaction, or NULL.
   *
   * @return null the return value is ignored
   */
  public static function ipnPre($type, &$objects, &$input = NULL, &$ids = NULL, &$values = NULL) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(5, $type, $objects, $input, $ids, $values, 'civicrm_ipnPre');
  }
  /**
   * This hook is called after the BaseIPN processes a payment notification (IPN).
   *
   * @param string $type
   *   The type of payment notification that was processed.
   *   One of: 'failed', 'pending', 'cancelled', 'complete'.
   * @param array &$objects
   *   Associative array of CiviCRM DAO objects involved in the IPN transaction
   *   (e.g., 'contribution', 'membership', 'participant', 'event', 'contact').
   * @param array|null &$input
   *   Payment input data passed from the payment processor, or NULL for status-only notifications.
   * @param array|null &$ids
   *   Associative array of entity IDs related to this transaction, or NULL.
   * @param array|null &$values
   *   Additional values array for the transaction, or NULL.
   *
   * @return null the return value is ignored
   */
  public static function ipnPost($type, &$objects, &$input = NULL, &$ids = NULL, &$values = NULL) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(5, $type, $objects, $input, $ids, $values, 'civicrm_ipnPost');
  }

  /**
   * This hooks allows alteration of generated page content.
   *
   * @param string &$content
   *   Previously generated HTML content, passed by reference for modification.
   * @param string $context
   *   Context of content — 'page' or 'form'.
   * @param string $tplName
   *   The file name of the Smarty template that generated the content.
   * @param CRM_Core_Page|CRM_Core_Form &$object
   *   Reference to the page or form object that rendered the content.
   *
   * @return null the return value is ignored
   */
  public static function alterContent(&$content, $context, $tplName, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $content, $context, $tplName, $object, $null, 'civicrm_alterContent');
  }

  /**
   * This hooks allows alteration of the tpl file used to generate content. It differs from the
   * altercontent hook as the content has already been rendered through the tpl at that point
   *
   * @param string $formName
   *   The name of the form or page class (e.g., 'CRM_Contact_Form_Contact').
   * @param CRM_Core_Form|CRM_Core_Page &$form
   *   Reference to the form or page object.
   * @param string $context
   *   Context of content — 'page' or 'form'.
   * @param string &$tplName
   *   The file name of the Smarty template, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function alterTemplateFile($formName, &$form, $context, &$tplName) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $formName, $form, $context, $tplName, $null, 'civicrm_alterTemplateFile');
  }

  /**
   * This hooks allows alteration of the template variables before render content.
   * It differs from the alterContent hook as the content has already been rendered
   * through the tpl at that point
   *
   * @param string &$resourceName
   *   The Smarty template resource name, passed by reference for modification.
   * @param array &$vars
   *   Associative array of Smarty template variables, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function alterTemplateVars(&$resourceName, &$vars) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(2, $resourceName, $vars, $null, $null, $null, 'civicrm_alterTemplateVars');
  }

  /**
   * This hooks allows other module prepare variable to pass into invoice
   *
   * @param int $contribution_id
   *   Contribution ID.
   * @param array &$tplParams
   *   Template variables array passed by reference, for adding or modifying
   *   variables used in the invoice template.
   * @param string $message
   *   Additional message or context string passed to the invoice hook.
   *
   * @return null the return value is ignored
   */
  public static function prepareInvoice($contribution_id, &$tplParams, $message) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $contribution_id, $tplParams, $message, $null, $null, 'civicrm_prepareInvoice');
  }

  /**
   * This hooks allows other module invoke tax receipt info
   *
   * @param int $contributionId
   *   Contribution ID.
   * @param array &$tplParams
   *   Template variables array passed by reference for the tax receipt template.
   * @param mixed &$taxReceipt
   *   TaxReceipt is null when prepared by contribution, passed by reference for further modification
   * @param mixed &$object
   *   Additional variable passed by reference for saving data.
   *
   * @return null the return value is ignored
   *
   */
  public static function prepareTaxReceipt($contributionId, &$tplParams, &$taxReceipt, &$object) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $contributionId, $tplParams, $taxReceipt, $object, $null, 'civicrm_prepareTaxReceipt');
  }

  /**
   * Validate tax receipt info before it is submitted.
   *
   * @param int $contributionId
   *   Contribution ID.
   * @param mixed &$receipt
   *   Receipt object or data array passed by reference. Hook implementations
   *   for further validation.
   *
   * @return null the return value is ignored
   */
  public static function validateTaxReceipt($contributionId, &$receipt) {
    $config = CRM_Core_Config::singleton();
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      2, &$contributionId, &$receipt, &$null, &$null, &$null, 'civicrm_validateTaxReceipt'
    ]);
  }

  /**
   * This hook is called during event registration to validate whether a contact may register.
   *
   * @param int|null $contactID
   *   The contact ID of the registrant, or NULL if not yet identified.
   * @param array|null $fields
   *   Array of form field values submitted during registration, or NULL.
   * @param CRM_Core_Form|CRM_Core_Page $form
   *   The form or page object handling the registration (e.g.,
   *   CRM_Event_Form_Registration_Register or CRM_Event_Page_EventInfo).
   * @param bool $isAdditional
   *   TRUE if this is an additional (non-primary) participant registration.
   * @param bool|null &$overrideCheck
   *   Output parameter passed by reference. Hook implementations can set this
   *   to TRUE to force-allowed registration, NULL to neutual and skill hook, FALSE force to block it.
   *
   * @return null the return value is ignored
   */
  public static function checkRegistration($contactID, $fields, $form, $isAdditional, &$overrideCheck) {
    $config = CRM_Core_Config::singleton();
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      5, &$contactID, &$fields, &$form, &$isAdditional, &$overrideCheck, 'civicrm_checkRegistration'
    ]);
  }

  /**
   * This hook is called after receiving a TapPay payment gateway API response.
   *
   * Allows modules to modify or react to the raw API response data before it
   * is processed and saved to the database.
   *
   * @param stdClass $response
   *   The JSON-decoded response object returned by the payment gateway API.
   * @param CRM_Contribute_DAO_TapPay &$object
   *   The TapPay DAO object being updated, passed by reference.
   * @param string $provider
   *   The payment provider name (default: 'TapPay').
   * @param string $apiType
   *   The type of API call that produced this response.
   *
   * @return null the return value is ignored
   */
  public static function alterTapPayResponse($response, &$object, $provider = 'TapPay', $apiType = '') {
    $config = CRM_Core_Config::singleton();
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $config->userHookClass) . '.php');
    $null = &CRM_Core_DAO::$_nullObject;

    return call_user_func_array([$config->userHookClass, 'invoke'], [
      4, &$response, &$object, &$provider, &$apiType, &$null, 'civicrm_alterTapPayResponse'
    ]);
  }

  /**
   * Change validation result of coupon
   *
   * @param array &$coupon
   *   Coupon array from CRM_Coupon_BAO::getCoupon, passed by reference for modification.
   * @param bool &$valid
   *   Previous validation result, passed by reference. Hook implementations can
   *   modify this to override the default validation outcome.
   * @param string $phase
   *   Validation phase. For example, event validation has two phases:
   *   first is 'code' (coupon code check), second is 'event' (available events check).
   *
   * @return null the return value is ignored
   */
  public static function validateCoupon(&$coupon, &$valid, $phase) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(3, $coupon, $valid, $phase, $null, $null, 'civicrm_validateCoupon');
  }

  /**
   * Modifies the API response content before it is returned.
   *
   * @param string $entity
   *   The entity being called by this API (e.g., 'Contact', 'Contribution').
   * @param string $action
   *   The action being performed (e.g., 'get', 'create', 'delete').
   * @param array $params
   *   The query parameters sent with the API request.
   * @param array &$result
   *   The API response data, passed by reference for modification.
   *
   * @return null the return value is ignored
   */
  public static function alterAPIResult($entity, $action, $params, &$result) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $entity, $action, $params, $result, $null, 'civicrm_alterAPIResult');
  }

  /**
   * Alter query of CRM_Contact_BAO_Query
   *
   * @param int $mode
   *   Mode of query component. Check constants of CRM_Contact_BAO_Query::MODE_*.
   * @param string $part
   *   Query component to alter in lower case. Available: 'select', 'from', 'where', 'having'.
   * @param CRM_Contact_BAO_Query &$object
   *   Query object of CRM_Contact_BAO_Query, passed by reference for modification.
   * @param array &$additional
   *   Additional parameters for alter query. When $part is 'from', use
   *   $additional['tables'] to alter the query, passed by reference.
   *
   * @return null the return value is ignored
   */
  public static function alterQuery($mode, $part, &$object, &$additional) {
    $config = CRM_Core_Config::singleton();
    $null = &CRM_Core_DAO::$_nullObject;
    $className = $config->userHookClass;
    return $className::invoke(4, $mode, $part, $object, $additional, $null, 'civicrm_alterQuery');
  }
}
