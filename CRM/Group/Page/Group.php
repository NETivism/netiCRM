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

require_once 'CRM/Core/Page/Basic.php';
class CRM_Group_Page_Group extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  static $_links = NULL;

  protected $_pager = NULL;

  protected $_sortByCharacter;

  /**
   * The action links that we need to display for saved search items
   *
   * @var array
   */
  static $_savedSearchLinks = NULL; function getBAOName() {
    return 'CRM_Contact_BAO_Group';
  }

  /**
   * Function to define action links
   *
   * @return array self::$_links array of action links
   * @access public
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('Contacts'),
          'url' => 'civicrm/group/search',
          'qs' => 'reset=1&force=1&context=smog&gid=%%id%%',
          'title' => ts('Group Contacts'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Settings'),
          'url' => 'civicrm/group',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Edit Group'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contact_BAO_Group' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Group'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contact_BAO_Group' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Group'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/group',
          'qs' => 'reset=1&action=delete&id=%%id%%',
          'title' => ts('Delete Group'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Function to define action links for saved search
   *
   * @return array self::$_savedSearchLinks array of action links
   * @access public
   */
  function &savedSearchLinks() {
    if (!self::$_savedSearchLinks) {
      $deleteExtra = ts('Do you really want to remove this Smart Group?');
      self::$_savedSearchLinks = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('Show Group Members'),
          'url' => 'civicrm/contact/search/advanced',
          'qs' => 'reset=1&force=1&ssID=%%ssid%%',
          'title' => ts('Search'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/group',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Edit Group'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/search/saved',
          'qs' => 'action=delete&id=%%ssid%%',
          'extra' => 'onclick="return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_savedSearchLinks;
  }

  /**
   * return class name of edit form
   *
   * @return string
   * @access public
   */
  function editForm() {
    return 'CRM_Group_Form_Edit';
  }

  /**
   * return name of edit form
   *
   * @return string
   * @access public
   */
  function editName() {
    return 'Edit Group';
  }

  /**
   * return class name of delete form
   *
   * @return string
   * @access public
   */
  function deleteForm() {
    return 'CRM_Group_Form_Delete';
  }

  /**
   * return name of delete form
   *
   * @return string
   * @access public
   */
  function deleteName() {
    return 'Delete Group';
  }

  /**
   * return user context uri to return to
   *
   * @return string
   * @access public
   */
  function userContext($mode = NULL) {
    return 'civicrm/group';
  }

  /**
   * return user context uri params
   *
   * @return string
   * @access public
   */
  function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * make sure that the user has permission to access this group
   *
   * @param int $id   the id of the object
   * @param int $name the name or title of the object
   *
   * @return string   the permission that the user has (or null)
   * @access public
   */
  function checkPermission($id, $title) {
    return CRM_Contact_BAO_Group::checkPermission($id, $title);
  }

  /**
   * We need to do slightly different things for groups vs saved search groups, hence we
   * reimplement browse from Page_Basic
   *
   * @param int $action
   *
   * @return void
   * @access public
   */
  function browse($action = NULL) {
    require_once 'CRM/Contact/BAO/GroupNesting.php';
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    if ($this->_sortByCharacter == 1 ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }
    $customSearchList = CRM_Core_OptionGroup::values('custom_search', FALSE, FALSE, FALSE, NULL, 'name');
    $customGroupSearchId = array_search('CRM_Contact_Form_Search_Custom_Group', $customSearchList);
    if ($customGroupSearchId) {
      $this->assign('customGroupSearchId', $customGroupSearchId);
    }

    $query = " SELECT COUNT(*) FROM civicrm_group";
    $groupExists = CRM_Core_DAO::singleValueQuery($query);
    $this->assign('groupExists', $groupExists);

    $this->search();

    $config = CRM_Core_Config::singleton();

    $params = array();
    $whereClause = $this->whereClause($params, FALSE);
    $this->pagerAToZ($whereClause, $params);

    $params = array();
    $whereClause = $this->whereClause($params, TRUE);
    $this->pager($whereClause, $params);

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();
    $select = $from = $where = "";
    if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE &&
      CRM_Core_Permission::check('administer Multiple Organizations')
    ) {
      $select = ", contact.display_name as orgName, contact.id as orgID";
      $from = " LEFT JOIN civicrm_group_organization gOrg
                               ON gOrg.group_id = groups.id 
                        LEFT JOIN civicrm_contact contact
                               ON contact.id = gOrg.organization_id ";

      //get the Organization ID
      $orgID = CRM_Utils_Request::retrieve('oid', 'Positive', CRM_Core_DAO::$_nullObject);
      if ($orgID) {
        $where = " AND gOrg.organization_id = {$orgID}";
      }
      $this->assign('groupOrg', TRUE);
    }
    $query = "
        SELECT groups.* {$select}
        FROM  civicrm_group groups 
              {$from}
        WHERE $whereClause {$where}
        ORDER BY groups.id DESC 
        LIMIT $offset, $rowCount";

    $object = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contact_DAO_Group');

    $groupPermission = CRM_Core_Permission::check('edit groups') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('groupPermission', $groupPermission);

    //FIXME CRM-4418, now we are handling delete separately
    //if we introduce 'delete for group' make sure to handle here.
    $groupPermissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit groups')) {
      $groupPermissions[] = CRM_Core_Permission::EDIT;
      $groupPermissions[] = CRM_Core_Permission::DELETE;
    }

    require_once 'CRM/Core/OptionGroup.php';
    $links = &$this->links();
    $allTypes = CRM_Core_OptionGroup::values('group_type');
    $smartMarketingTypes = array();
    foreach($allTypes as $typeId => $typeName) {
      if (strstr($typeName, 'Smart Marketing')) {
        $smartMarketingTypes[$typeId] = $typeName;
      }
    }

    $values = array();

    while ($object->fetch()) {
      $permission = $this->checkPermission($object->id, $object->title);
      if ($permission) {
        $newLinks = $links;
        $values[$object->id] = array();
        CRM_Core_DAO::storeValues($object, $values[$object->id]);
        $values[$object->id]['mode'] = ts('Normal');
        if ($object->saved_search_id) {
          $values[$object->id]['mode'] = ts('Smart');
          $customSearchID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $object->saved_search_id, 'search_custom_id');
          if ($customSearchID) {
            $newLinks[CRM_Core_Action::ADVANCED] = $newLinks[CRM_Core_Action::VIEW];
            $newLinks[CRM_Core_Action::VIEW]['url'] = 'civicrm/contact/search/custom';
            $newLinks[CRM_Core_Action::VIEW]['qs'] = "reset=1&force=1&ssID={$object->saved_search_id}&gid={$object->id}";
            $newLinks[CRM_Core_Action::VIEW]['name'] = ts('Search Results');
            $newLinks[CRM_Core_Action::VIEW]['title'] = ts('Custom Searches');
          }
        }
        $action = array_sum(array_keys($newLinks));
        if (property_exists($object, 'is_active')) {
          if ($object->is_active) {
            $action -= CRM_Core_Action::ENABLE;
          }
          else {
            $action -= CRM_Core_Action::VIEW;
            $action -= CRM_Core_Action::DISABLE;
          }
        }
        if (!empty($smartMarketingTypes)) {
          foreach($smartMarketingTypes as $typeId => $typeName) {
            if (strstr($object->group_type, CRM_Core_DAO::VALUE_SEPARATOR.$typeId.CRM_Core_DAO::VALUE_SEPARATOR)) {
              $action -= CRM_Core_Action::DELETE;
            }
          }
        }

        $action = $action & CRM_Core_Action::mask($groupPermissions);

        $values[$object->id]['visibility'] = CRM_Contact_DAO_Group::tsEnum('visibility',
          $values[$object->id]['visibility']
        );
        if (isset($values[$object->id]['group_type'])) {
          $groupTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            substr($values[$object->id]['group_type'], 1, -1)
          );
          $types = $tsTypes = array();
          foreach ($groupTypes as $type) {
            $types[] = $allTypes[$type];
            $tsTypes[] = ts($allTypes[$type]);
          }
          $values[$object->id]['group_type'] = CRM_Utils_Array::implode(', ', $tsTypes);
        }
        $values[$object->id]['action'] = CRM_Core_Action::formLink($newLinks,
          $action,
          array('id' => $object->id,
            'ssid' => $object->saved_search_id,
          )
        );
        if (property_exists($object, 'orgName')) {
          if ($object->orgName) {
            $values[$object->id]['org_name'] = $object->orgName;
            $values[$object->id]['org_id'] = $object->orgID;
          }
        }
        if (!empty($object->children)) {
          $values[$object->id]['is_parent'] = 1;
          $children = explode(',', $object->children);
          $values[$object->id]['children'] = $children;
        }
        if (!empty($object->parents)) {
          $values[$object->id]['is_children'] = 1;
          $parents = explode(',', $object->parents);
          $values[$object->id]['parents'] = $parents;
        }
      }
    }

    if (isset($values)) {
      $this->assign('rows', $values);
    }
  }

  function search() {
    if ($this->_action &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Group_Form_Search', ts('Search Groups'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  function whereClause(&$params, $sortBy = TRUE, $excludeHidden = TRUE) {
    $values = array();

    $clauses = array();
    $title = $this->get('title');
    if ($title) {
      $clauses[] = "groups.title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    $groupType = $this->get('group_type');

    if ($groupType) {
      $types = array_keys($groupType);
      if (!empty($types)) {
        $clauses[] = 'groups.group_type LIKE %2';
        $typeString = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $types) . CRM_Core_DAO::VALUE_SEPARATOR;
        $params[2] = array($typeString, 'String', TRUE);
      }
    }

    $visibility = $this->get('visibility');
    if ($visibility) {
      $clauses[] = 'groups.visibility = %3';
      $params[3] = array($visibility, 'String');
    }

    $active_status = $this->get('active_status');
    $inactive_status = $this->get('inactive_status');
    if ($active_status && !$inactive_status) {
      $clauses[] = 'groups.is_active = 1';
      $params[4] = array($active_status, 'Boolean');
    }


    if ($inactive_status && !$active_status) {
      $clauses[] = 'groups.is_active = 0';
      $params[5] = array($inactive_status, 'Boolean');
    }

    if ($inactive_status && $active_status) {
      $clauses[] = '(groups.is_active = 0 OR groups.is_active = 1 )';
    }

    $group_mode = $this->get('group_mode');
    if (isset($group_mode) && is_array($group_mode) && count($group_mode) == 1) {
      $key = key($group_mode);
      if (empty($key)) {
        $clauses[] = '(groups.saved_search_id IS NULL)';
      }
      else {
        $clauses[] = '(groups.saved_search_id IS NOT NULL)';
      }
    }

    if ($sortBy &&
      $this->_sortByCharacter
    ) {
      $clauses[] = 'groups.title LIKE %6';
      $params[6] = array($this->_sortByCharacter . '%', 'String');
    }

    // dont do a the below assignement when doing a
    // AtoZ pager clause
    if ($sortBy) {
      if (count($clauses) > 1) {
        $this->assign('isSearch', 1);
      }
      else {
        $this->assign('isSearch', 0);
      }
    }

    if (empty($clauses)) {
      $clauses[] = 'groups.is_active = 1';
    }

    if ($excludeHidden) {
      $clauses[] = 'groups.is_hidden = 0';
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function pager($whereClause, $whereParams) {
    require_once 'CRM/Utils/Pager.php';

    $params['status'] = ts('Group %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
        SELECT groups.id, groups.title
            FROM  civicrm_group groups
            WHERE $whereClause";

    $object = CRM_Core_DAO::executeQuery($query, $whereParams);
    $total = 0;
    while ($object->fetch()) {
      if ($this->checkPermission($object->id, $object->title)) {
        $total++;
      }
    }

    $params['total'] = $total;

    $this->_pager = new CRM_Utils_Pager($params);


    $this->assign_by_ref('pager', $this->_pager);
  }

  function pagerAtoZ($whereClause, $whereParams) {
    require_once 'CRM/Utils/PagerAToZ.php';

    $query = "
        SELECT DISTINCT UPPER(LEFT(groups.title, 1)) as sort_name
        FROM  civicrm_group groups
        WHERE $whereClause
        ORDER BY LEFT(groups.title, 1)
            ";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }
}

