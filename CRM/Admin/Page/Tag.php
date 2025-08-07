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
 * Page for displaying list of categories
 */
class CRM_Admin_Page_Tag extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_Tag';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/tag',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Tag'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/tag',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Tag'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_Tag';
  }

  /**
   * Get form name for edit form
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Tag';
  }

  /**
   * Get form name for delete form
   *
   * @return string name of this page.
   */
  function deleteName() {
    return 'Tag';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/tag';
  }

  /**
   * Get name of delete form
   *
   * @return string Classname of delete form.
   */
  function deleteForm() {
    return 'CRM_Admin_Form_Tag';
  }

  /**
   * override function browse()
   */
  function browse($action = NULL, $sort = NULL) {
    $adminTagSet = FALSE;
    if (CRM_Core_Permission::check('administer Tagsets')) {
      $adminTagSet = TRUE;
    }
    $this->assign('adminTagSet', $adminTagSet);


    $usedFor = CRM_Core_OptionGroup::values('tag_used_for');

    $query = "SELECT t1.name, t1.id, t2.name as parent, t1.description, t1.used_for, t1.is_tagset,
                        t1.is_reserved, t1.parent_id, t1.used_for
                 FROM civicrm_tag t1 LEFT JOIN civicrm_tag t2 ON t1.parent_id = t2.id
                 GROUP BY t1.parent_id, t1.id";

    $tag = CRM_Core_DAO::executeQuery($query);
    $values = [];

    $action = CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE;
    $permission = CRM_Core_Permission::EDIT;

    while ($tag->fetch()) {
      $values[$tag->id] = (array) $tag;

      $used = [];
      if ($values[$tag->id]['used_for']) {
        $usedArray = explode(",", $values[$tag->id]['used_for']);
        foreach ($usedArray as $key => $value) {
          $used[$key] = $usedFor[$value];
        }
      }

      if (!empty($used)) {
        $values[$tag->id]['used_for'] = CRM_Utils_Array::implode(", ", $used);
      }

      $newAction = $action;
      if ($values[$tag->id]['is_reserved']) {
        $newAction = CRM_Core_Action::UPDATE;
      }

      if ($values[$tag->id]['is_tagset'] && !CRM_Core_Permission::check('administer Tagsets')) {
        $newAction = 0;
      }

      // populate action links
      if ($newAction) {
        $this->action($tag, $newAction, $values[$tag->id], self::links(), $permission, TRUE);
      }
      else {
        $values[$tag->id]['action'] = '';
      }
    }

    $this->assign('rows', $values);

    $customSearchList = CRM_Core_OptionGroup::values('custom_search', FALSE, FALSE, FALSE, NULL, 'name');
    $customGroupSearchId = array_search('CRM_Contact_Form_Search_Custom_Group', $customSearchList);
    if ($customGroupSearchId) {
      $this->assign('customGroupSearchId', $customGroupSearchId);
    }
  }
}

