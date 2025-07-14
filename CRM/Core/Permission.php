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

/**
 * This is the basic permission class wrapper
 */
class CRM_Core_Permission {

  /**
   * Static strings used to compose permissions
   *
   * @const
   * @var string
   */
  CONST EDIT_GROUPS = 'edit contacts in ', VIEW_GROUPS = 'view contacts in ';

  /**
   * The various type of permissions
   *
   * @var int
   */
  CONST EDIT = 1, VIEW = 2, DELETE = 3, CREATE = 4, SEARCH = 5, ALL = 6, ADMIN = 7;

  /**
   * get the current permission of this user
   *
   * @return string the permission of the user (edit or view or null)
   */
  public static function getPermission() {
    $config = CRM_Core_Config::singleton();
    $className = $config->userPermissionClass;
    return $className::getPermission( );
  }

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  static function check($str) {
    $config = CRM_Core_Config::singleton();
    return call_user_func([$config->userPermissionClass, 'check'], $str);
  }

  /**
   * Get the permissioned where clause for the user
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public static function whereClause($type, &$tables, &$whereTables, $context = 'contact') {
    $config = CRM_Core_Config::singleton();
    $className = $config->userPermissionClass;
    return $className::whereClause( $type, $tables, $whereTables, $context );
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType     type of group(Access/Mailing)
   * @param boolen $excludeHidden exclude hidden groups.
   *
   * @access public
   * @static
   *
   * @return array - array reference of all groups.
   *
   */
  public static function group($groupType, $excludeHidden = TRUE) {
    $config = CRM_Core_Config::singleton();
    $className = $config->userPermissionClass;
    return $className::group( $groupType, $excludeHidden );
  }

  public static function customGroup($type = CRM_Core_Permission::VIEW, $reset = FALSE) {
    $customGroups = CRM_Core_PseudoConstant::customGroup($reset);
    $defaultGroups = [];

    // check if user has all powerful permission
    // or administer civicrm permission (CRM-1905)
    if (self::check('access all custom data')) {
      $defaultGroups = array_keys($customGroups);
    }
    elseif (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
      if (self::check('administer Multiple Organizations')) {
        $defaultGroups = array_keys($customGroups);
      }
    }
    elseif (self::check('administer CiviCRM')) {
      $defaultGroups = array_keys($customGroups);
    }


    return CRM_ACL_API::group($type, NULL, 'civicrm_custom_group', $customGroups, $defaultGroups);
  }

  static function customGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $reset = FALSE) {
    $groups = self::customGroup($type, $reset);
    if (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . CRM_Utils_Array::implode(',', $groups) . ' ) ';
    }
  }

  public static function ufGroupValid($gid, $type = CRM_Core_Permission::VIEW) {
    if (empty($gid)) {
      return TRUE;
    }

    $groups = self::ufGroup($type);
    return in_array($gid, $groups) ? TRUE : FALSE;
  }

  public static function ufGroup($type = CRM_Core_Permission::VIEW) {
    $ufGroups = CRM_Core_PseudoConstant::ufGroup();

    $allGroups = array_keys($ufGroups);

    // check if user has all powerful permission
    if (self::check('profile listings and forms')) {
      return $allGroups;
    }

    switch ($type) {
      case CRM_Core_Permission::VIEW:
        if (self::check('profile view') ||
          self::check('profile edit')
        ) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::CREATE:
        if (self::check('profile create')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::EDIT:
        if (self::check('profile edit')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::SEARCH:
        if (self::check('profile listings')) {
          return $allGroups;
        }
        break;
    }


    return CRM_ACL_API::group($type, NULL, 'civicrm_uf_group', $ufGroups);
  }

  static function ufGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $returnUFGroupIds = FALSE) {
    $groups = self::ufGroup($type);
    if ($returnUFGroupIds) {
      return $groups;
    }
    elseif (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . CRM_Utils_Array::implode(',', $groups) . ' ) ';
    }
  }

  public static function event($type = CRM_Core_Permission::VIEW, $eventID = NULL) {

    $events = CRM_Event_PseudoConstant::event(NULL, TRUE);
    $includeEvents = [];

    // check if user has all powerful permission
    if (self::check('register for events')) {
      $includeEvents = array_keys($events);
    }

    if ($type == CRM_Core_Permission::VIEW &&
      self::check('view event info')
    ) {
      $includeEvents = array_keys($events);
    }


    $permissionedEvents = CRM_ACL_API::group($type, NULL, 'civicrm_event', $events, $includeEvents);
    if (!$eventID) {
      return $permissionedEvents;
    }
    return array_search($eventID, $permissionedEvents) === FALSE ? NULL : $eventID;
  }

  static function eventClause($type = CRM_Core_Permission::VIEW, $prefix = NULL) {
    $events = self::event($type);
    if (empty($events)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . CRM_Utils_Array::implode(',', $events) . ' ) ';
    }
  }

  static function access($module, $checkPermission = TRUE) {
    $config = CRM_Core_Config::singleton();

    if (!in_array($module, $config->enableComponents)) {
      return FALSE;
    }

    if ($checkPermission) {
      if ($module == 'CiviCase') {

        return CRM_Case_BAO_Case::accessCiviCase();
      }
      else {
        return CRM_Core_Permission::check("access $module");
      }
    }

    return TRUE;
  }

  /**
   * check permissions for delete and edit actions
   *
   * @param string  $module component name.
   * @param $action action to be check across component
   *
   **/
  static function checkActionPermission($module, $action) {
    //check delete related permissions.
    if ($action & CRM_Core_Action::DELETE) {
      $permissionName = "delete in $module";
    }
    else {
      $editPermissions = ['CiviEvent' => 'edit event participants',
        'CiviMember' => 'edit memberships',
        'CiviPledge' => 'edit pledges',
        'CiviContribute' => 'edit contributions',
        'CiviGrant' => 'edit grants',
        'CiviMail' => 'access CiviMail',
        'CiviAuction' => 'add auction items',
      ];
      $permissionName = CRM_Utils_Array::value($module, $editPermissions);
    }

    if ($module == 'CiviCase' && !$permissionName) {

      return CRM_Case_BAO_Case::accessCiviCase();
    }
    else {
      //check for permission.
      return CRM_Core_Permission::check($permissionName);
    }
  }

  static function checkMenu(&$args, $op = 'and') {
    if (!is_array($args)) {
      return $args;
    }
    foreach ($args as $str) {
      $res = CRM_Core_Permission::check($str);
      if ($op == 'or' && $res) {
        return TRUE;
      }
      elseif ($op == 'and' && !$res) {
        return FALSE;
      }
    }
    return ($op == 'or') ? FALSE : TRUE;
  }

  static function checkMenuItem(&$item) {
    if (!CRM_Utils_Array::arrayKeyExists('access_callback', $item)) {
      CRM_Core_Error::backtrace();
      CRM_Core_Error::fatal();
    }

    // if component_id is present, ensure it is enabled
    if (isset($item['component_id']) &&
      $item['component_id']
    ) {
      $config = CRM_Core_Config::singleton();
      if (is_array($config->enableComponentIDs) &&
        in_array($item['component_id'],
          $config->enableComponentIDs
        )
      ) {
        // continue with process
      }
      else {
        return FALSE;
      }
    }

    // the following is imitating drupal 6 code in includes/menu.inc
    if (empty($item['access_callback']) ||
      is_numeric($item['access_callback'])
    ) {
      return (bool ) $item['access_callback'];
    }

    // check whether the following Ajax requests submitted the right key
    // FIXME: this should be integrated into ACLs proper
    if ($item['page_type'] == 3) {

      if (!CRM_Core_Key::validate($_REQUEST['key'], $item['path'])) {
        return FALSE;
      }
    }

    // check if callback is for checkMenu, if so optimize it
    if (is_array($item['access_callback']) &&
      $item['access_callback'][0] == 'CRM_Core_Permission' &&
      $item['access_callback'][1] == 'checkMenu'
    ) {
      $op = CRM_Utils_Array::value(1, $item['access_arguments'], 'and');
      return self::checkMenu($item['access_arguments'][0],
        $op
      );
    }
    else {
      return call_user_func_array($item['access_callback'],
        $item['access_arguments']
      );
    }
  }

  static function &basicPermissions($all = FALSE) {
    static $permissions = NULL;

    if (!$permissions) {
      $permissions = [
        'add contacts' => ts('add contacts'),
        'view all contacts' => ts('view all contacts'),
        'edit all contacts' => ts('edit all contacts'),
        'delete contacts' => ts('delete contacts'),
        'access deleted contacts' => ts('access deleted contacts'),
        'delete contacts permanantly' => ts('delete contacts permanantly'),
        'import contacts' => ts('import contacts'),
        'import SQL datasource' => ts('import SQL datasource'),
        'edit groups' => ts('edit groups'),
        'administer CiviCRM' => ts('administer CiviCRM'),
        'administer Reserved Option' => ts('administer Reserved Option'),
        'access uploaded files' => ts('access uploaded files'),
        'upload and post images' => ts('upload and post images'),
        'profile listings and forms' => ts('profile listings and forms'),
        'profile listings' => ts('profile listings'),
        'profile create' => ts('profile create'),
        'profile edit' => ts('profile edit'),
        'profile view' => ts('profile view'),
        'access all custom data' => ts('access all custom data'),
        'view all activities' => ts('view all activities'),
        'delete activities' => ts('delete activities'),
        'access CiviCRM' => ts('access CiviCRM'),
        'access Contact Dashboard' => ts('access Contact Dashboard'),
        'translate CiviCRM' => ts('translate CiviCRM'),
        'administer Tagsets' => ts('administer Tagsets'),
        'administer reserved tags' => ts('administer reserved tags'),
        'administer dedupe rules' => ts('administer dedupe rules'),
        'merge duplicate contacts' => ts('merge duplicate contacts'),
        'view all notes' => ts('view all notes'),
        'REST API create' => ts('REST API create'),
        'REST API update' => ts('REST API update'),
        'REST API delete' => ts('REST API delete'),
        'REST API search' => ts('REST API search'),
        'MCP query' => ts('MCP query'),
      ];

      if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
        $permissions['administer Multiple Organizations'] = ts('administer Multiple Organizations');
      }

      $config = CRM_Core_Config::singleton();


      if (!$all) {
        $components = CRM_Core_Component::getEnabledComponents();
      }
      else {
        $components = CRM_Core_Component::getComponents();
      }
      foreach($permissions as $p => $title) {
        if ($p != $title) {
          $permissions[$p] .= ' ('.$p.')';
        }
      }

      foreach ($components as $comp) {
        $perm = $comp->getPermissions();
        if ($perm) {
          sort($perm);
          foreach ($perm as $p) {
            $title = ts($p);
            if (ts($p) != $p) {
              $title .= ' ('.$p.')';
            }
            $permissions[$p] = $title;
          }
        }
      }
      asort($permissions);
    }

    return $permissions;
  }

  /**
   * Validate user permission across
   * edit or view or with supportable acls.
   *
   * return boolean true/false.
   **/
  static function giveMeAllACLs() {
    $hasPermission = FALSE;
    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      $hasPermission = TRUE;
    }

    //check for acl.
    if (!$hasPermission) {
      $aclPermission = self::getPermission();
      if (in_array($aclPermission, [CRM_Core_Permission::EDIT,
            CRM_Core_Permission::VIEW,
          ])) {
        $hasPermission = TRUE;
      }
    }

    return $hasPermission;
  }

  /**
   * Function to get component name from given permission.
   *
   * @param string  $permission
   *
   * return string $componentName the name of component.
   * @static
   */
  static function getComponentName($permission) {
    $componentName = NULL;
    $permission = trim($permission);
    if (empty($permission)) {
      return $componentName;
    }

    static $allCompPermissions;
    if (!is_array($allCompPermissions)) {

      $components = CRM_Core_Component::getComponents();
      foreach ($components as $name => $comp) {
        $allCompPermissions[$name] = $comp->getPermissions();
      }
    }

    if (is_array($allCompPermissions)) {
      foreach ($allCompPermissions as $name => $permissions) {
        if (in_array($permission, $permissions)) {
          $componentName = $name;
          break;
        }
      }
    }

    return $componentName;
  }
}

