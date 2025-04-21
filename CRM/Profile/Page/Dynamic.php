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
 * Create a page for displaying CiviCRM Profile Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Profile_Page_Dynamic extends CRM_Core_Page {

  /**
   * The contact id of the person we are viewing
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * the profile group are are interested in
   *
   * @var int
   * @access protected
   */
  protected $_gid;

  /**
   * The profile types we restrict this page to display
   *
   * @var string
   * @access protected
   */
  protected $_restrict;

  /**
   * Should we bypass permissions
   *
   * @var boolean
   * @access prootected
   */
  protected $_skipPermission;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
   */
  protected $_profileIds = array();

  /**
   * Set title on page
   */
  protected $_setTitle;

  /**
   * class constructor
   *
   * @param int $id  the contact id
   * @param int $gid the group id
   *
   * @return void
   * @access public
   */
  function __construct($id, $gid, $restrict, $skipPermission = FALSE, $profileIds = NULL, $setTitle = TRUE) {
    $this->_id = $id;
    $this->_gid = $gid;
    $this->_restrict = $restrict;
    $this->_skipPermission = $skipPermission;
    $this->_setTitle = $setTitle;
    if ($profileIds) {
      $this->_profileIds = $profileIds;
    }
    else {
      $this->_profileIds = array($gid);
    }
    parent::__construct();
  }

  /**
   * Get the action links for this page.
   *
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    return NULL;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $template = CRM_Core_Smarty::singleton();
    if ($this->_id && $this->_gid) {

      // first check that id is part of the limit group id, CRM-4822
      $limitListingsGroupsID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
        $this->_gid,
        'limit_listings_group_id'
      );
      $config = CRM_Core_Config::singleton();
      if ($limitListingsGroupsID) {


        if (!CRM_Contact_BAO_GroupContact::isContactInGroup($this->_id,
            $limitListingsGroupsID
          )) {
          CRM_Utils_System::setTitle(ts('Profile View - Permission Denied'));
          return CRM_Core_Session::setStatus(ts('You do not have permission to view this contact record. Contact the site administrator if you need assistance.'));
        }
      }


      $values = array();
      $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileIds, FALSE, CRM_Core_Action::VIEW,
        NULL, NULL, FALSE, $this->_restrict,
        $this->_skipPermission, NULL,
        CRM_Core_Permission::VIEW
      );




      // make sure we dont expose all fields based on permission
      $admin = FALSE;
      $session = CRM_Core_Session::singleton();
      if ((!$config->userFrameworkFrontend &&
          (CRM_Core_Permission::check('administer users') ||
            CRM_Core_Permission::check('view all contacts') ||
            CRM_Contact_BAO_Contact_Permission::allow($this->_id, CRM_Core_Permission::VIEW)
          )
        ) ||
        $this->_id == $session->get('userID')
      ) {
        $admin = TRUE;
      }

      if (!$admin) {
        foreach ($fields as $name => $field) {
          // make sure that there is enough permission to expose this field
          if ($field['visibility'] == 'User and User Admin Only') {
            unset($fields[$name]);
          }
        }
      }
      CRM_Core_BAO_UFGroup::getValues($this->_id, $fields, $values, TRUE, NULL, CRM_Core_BAO_UFGroup::MASK_NONE);

      // $profileFields array can be used for customized display of field labels and values in Profile/View.tpl
      $profileFields = array();
      $labels = array();
      $idx = 0;
      foreach ($fields as $name => $field) {
        $idx++;
        $fieldName = preg_replace('/\s+|\W+/', '_', $name);
        if (empty($field['title'])) {
          $labels[$idx] = $fieldName;
        }
        else {
          $labels[$field['title']] = $fieldName;
        }
      }

      $idx = 0;
      foreach ($values as $title => $value) {
        $idx++;
        $key = !empty($title) ? $title : $idx;
        $profileFields[$key] = array(
          'label' => $title,
          'value' => $value,
        );
      }

      $template->assign_by_ref('row', $values);
      $template->assign_by_ref('profileFields', $profileFields);
    }

    $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');

    if (strtolower($name) == 'summary_overlay') {
      $template->assign('overlayProfile', TRUE);
    }

    if ($this->_setTitle) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'title');
      $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_id, 'display_name');
      if ($displayName) {
        $title .= ' - ' . $displayName;
      }
      CRM_Utils_System::setTitle($title);
    }

    // invoke the pagRun hook, CRM-3906

    CRM_Utils_Hook::pageRun($this);

    return trim($template->fetch($this->getHookedTemplateFileName()));
  }

  function getTemplateFileName() {
    if ($this->_gid) {
      $templateFile = "CRM/Profile/Page/{$this->_gid}/Dynamic.tpl";
      $template = &CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Page/{$ufGroupName}/Dynamic.tpl";
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return parent::getTemplateFileName();
  }
}

