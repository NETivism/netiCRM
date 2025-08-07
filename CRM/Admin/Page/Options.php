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
 * Page for displaying list of Gender
 */
class CRM_Admin_Page_Options extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * The option group name
   *
   * @var array
   * @static
   */
  static $_gName = NULL;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var array
   * @static
   */
  static $_GName = NULL;

  /**
   * The option group id
   *
   * @var array
   * @static
   */
  static $_gId = NULL;

  /**
   * Obtains the group name from url and sets the title.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    if (!self::$_gName) {
      self::$_gName = CRM_Utils_Request::retrieve('group', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');
    }
    if (self::$_gName) {
      $this->set('gName', self::$_gName);
    }
    else {
      self::$_gName = $this->get('gName');
    }
    if (self::$_gName) {
      self::$_gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', self::$_gName, 'id', 'name');
    }
    else {
      CRM_Core_Error::fatal('Do not specify group name.');
    }

    self::$_GName = ucwords(str_replace('_', ' ', self::$_gName));

    $this->assign('gName', self::$_gName);
    $this->assign('GName', ts(self::$_GName));

    if (self::$_gName == 'acl_role') {
      CRM_Utils_System::setTitle(ts('Manage ACL Roles'));
      // set breadcrumb to append to admin/access
      $breadCrumb = [['title' => ts('Access Control'),
          'url' => CRM_Utils_System::url('civicrm/admin/access',
            'reset=1'
          ),
        ]];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }
    else {
      CRM_Utils_System::setTitle(ts('%1 Options', [1 => ts(self::$_GName)]));
    }

    // #30318, use new form for DKIM / SPF verification
    if (self::$_gName == 'from_email_address') {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/from_email', 'reset=1'));
    }
    if (in_array(self::$_gName, ['email_greeting', 'postal_greeting', 'addressee', 'website_type'])) {
      $this->assign('showIsDefault', TRUE);
    }
    if (self::$_gName == 'participant_status') {
      $this->assign('showCounted', TRUE);
      $this->assign('showVisibility', TRUE);
    }

    if (self::$_gName == 'participant_role') {
      $this->assign('showCounted', TRUE);
    }

    $config = CRM_Core_Config::singleton();
    if (in_array("CiviCase", $config->enableComponents) && self::$_gName == 'activity_type') {
      $this->assign('showComponent', TRUE);
    }
  }

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_OptionValue';
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
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'group=' . self::$_gName . '&action=update&id=%%id%%&reset=1',
          'title' => ts('Edit %1', [1 => self::$_gName]),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionValue' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable %1', [1 => self::$_gName]),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionValue' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable %1', [1 => self::$_gName]),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/options/' . self::$_gName,
          'qs' => 'group=' . self::$_gName . '&action=delete&id=%%id%%',
          'title' => ts('Delete %1 Type', [1 => self::$_gName]),
        ],
      ];

      if (self::$_gName == 'custom_search') {
        $runLink = [CRM_Core_Action::FOLLOWUP => [
            'name' => ts('Run'),
            'url' => 'civicrm/contact/search/custom',
            'qs' => 'reset=1&csid=%%value%%',
            'title' => ts('Run %1', [1 => self::$_gName]),
          ]];
        self::$_links = $runLink + self::$_links;
      }
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   *
   * @return void
   */
  function run() {
    $this->preProcess();
    parent::run();
  }

  /**
   * Browse all options
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {


    $groupParams = ['name' => self::$_gName];
    $optionValue = CRM_Core_OptionValue::getRows($groupParams, $this->links(), 'component_id,weight');
    $gName = self::$_gName;
    $returnURL = CRM_Utils_System::url("civicrm/admin/options/$gName",
      "reset=1&group=$gName"
    );
    $filter = "option_group_id = " . self::$_gId;

    CRM_Utils_Weight::addOrder($optionValue, 'CRM_Core_DAO_OptionValue',
      'id', $returnURL, $filter
    );
    $this->assign('rows', $optionValue);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_Options';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return self::$_GName;
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/options/' . self::$_gName;
  }

  /**
   * function to get userContext params
   *
   * @param int $mode mode that we are in
   *
   * @return string
   * @access public
   */
  function userContextParams($mode = NULL) {
    return 'group=' . self::$_gName . '&reset=1&action=browse';
  }
}

