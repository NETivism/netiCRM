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
 * CMS User Dashboard
 * This class is used to build User Dashboard
 *
 */
class CRM_Contact_Page_View_UserDashBoard extends CRM_Core_Page {
  public $_userOptions;
  public $_contactId = NULL;

  /*
     * always show public groups
     */

  public $_onlyPublicGroups = TRUE;

  public $_edit = TRUE;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;
  
  function __construct() {
    parent::__construct();

    $check = CRM_Core_Permission::check('access Contact Dashboard');

    if (!$check) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/dashboard', 'reset=1'));
      return;
    }

    $this->_contactId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if (!$this->_contactId) {
      $this->_contactId = $userID;
    }
    elseif ($this->_contactId != $userID) {

      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::VIEW)) {
         return CRM_Core_Error::statusBounce(ts('You do not have permission to view this contact'));
      }
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
        $this->_edit = FALSE;
      }
    }
  }

  /*
     * Heart of the viewing process. The runner gets all the meta data for
     * the contact and calls the appropriate type of page to view.
     *
     * @return void
     * @access public
     *
     */
  function preProcess() {
    if (!$this->_contactId) {
       return CRM_Core_Error::statusBounce(ts('You must be logged in to view this page.'));
    }

    list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_contactId);

    $this->set('displayName', $displayName);
    $this->set('contactImage', $contactImage);

    CRM_Utils_System::setTitle(ts('Dashboard - %1', [1 => $displayName]));

    $this->assign('recentlyViewed', FALSE);
  }

  /**
   * Function to build user dashboard
   *
   * @return none
   * @access public
   */
  function buildUserDashBoard() {
    //build component selectors
    $dashboardElements = [];
    $config = CRM_Core_Config::singleton();


    $this->_userOptions = CRM_Core_BAO_Preferences::valueOptions('user_dashboard_options');

    $components = CRM_Core_Component::getEnabledComponents();

    foreach ($components as $name => $component) {
      $elem = $component->getUserDashboardElement();
      if (!$elem) {
        continue;
      }

      if (CRM_Utils_Array::value($name, $this->_userOptions) &&
        (CRM_Core_Permission::access($component->name) ||
          CRM_Core_Permission::check($elem['perm'][0])
        )
      ) {

        $userDashboard = $component->getUserDashboardObject();
        $dashboardElements[] = ['templatePath' => $userDashboard->getTemplateFileName(),
          'sectionTitle' => $elem['title'],
          'weight' => $elem['weight'],
        ];
        $userDashboard->run();
      }
    }

    $sectionName = 'Permissioned Orgs';
    if ($this->_userOptions[$sectionName]) {
      $dashboardElements[] = ['templatePath' => 'CRM/Contact/Page/View/Relationship.tpl',
        'sectionTitle' => ts('Your Contacts / Organizations'),
        'weight' => 40,
      ];

      $links = &self::links();
      $currentRelationships = CRM_Contact_BAO_Relationship::getRelationship($this->_contactId,
        CRM_Contact_BAO_Relationship::CURRENT,
        0, 0, 0,
        $links, NULL, TRUE
      );
      $this->assign('currentRelationships', $currentRelationships);
    }

    if ($this->_userOptions['PCP']) {

      $dashboardElements[] = ['templatePath' => 'CRM/Contribute/Page/PcpUserDashboard.tpl',
        'sectionTitle' => ts('Personal Campaign Pages'),
        'weight' => 40,
      ];
      list($pcpBlock, $pcpInfo) = CRM_Contribute_BAO_PCP::getPcpDashboardInfo($this->_contactId);
      $this->assign('pcpBlock', $pcpBlock);
      $this->assign('pcpInfo', $pcpInfo);
    }


    usort($dashboardElements, ['CRM_Utils_Sort', 'cmpFunc']);
    $this->assign('dashboardElements', $dashboardElements);

    if ($this->_userOptions['Groups']) {
      $this->assign('showGroup', TRUE);
      //build group selector

      $gContact = new CRM_Contact_Page_View_UserDashBoard_GroupContact();
      $gContact->run();
    }
    else {
      $this->assign('showGroup', FALSE);
    }
  }

  /**
   * perform actions and display for user dashboard
   *
   * @return none
   *
   * @access public
   */
  function run() {
    $this->preProcess();
    $this->buildUserDashBoard();
    return parent::run();
  }

  /**
   * Get action links
   *
   * @return array (reference) of action links
   * @static
   */
  static function &links() {
    if (!(self::$_links)) {
      $deleteExtra = ts('Are you sure you want to delete this relationship?');
      $disableExtra = ts('Are you sure you want to disable this relationship?');
      $enableExtra = ts('Are you sure you want to re-enable this relationship?');

      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Contact Information'),
          'url' => 'civicrm/contact/relatedcontact',
          'qs' => 'action=update&reset=1&cid=%%cbid%%&rcid=%%cid%%',
          'title' => ts('Edit Relationship'),
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('Dashboard'),
          'url' => 'civicrm/user',
          'qs' => 'reset=1&id=%%cbid%%',
          'title' => ts('View Relationship'),
        ],
      ];


      if (CRM_Core_Permission::check('access CiviCRM')) {
        self::$_links = array_merge(self::$_links, [CRM_Core_Action::DISABLE => [
              'name' => ts('Disable'),
              'url' => 'civicrm/contact/view/rel',
              'qs' => 'action=disable&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%&selectedChild=rel%%&context=dashboard',
              'extra' => 'onclick = "return confirm(\'' . $disableExtra . '\');"',
              'title' => ts('Disable Relationship'),
            ],
          ]);
      }
    }

    // call the hook so we can modify it

    CRM_Utils_Hook::links('view.contact.userDashBoard', 'Contact',
      CRM_Core_DAO::$_nullObject, self::$_links
    );
    return self::$_links;
  }
}

