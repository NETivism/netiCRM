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
 * Create a page for displaying UF Groups.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_UF_Page_Group extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks = NULL;

  /**
   * Get the action links for this page.
   *
   * @param
   *
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!self::$_actionLinks) {
      // helper variable for nicer formatting
      $copyExtra = ts('Are you sure you want to make a copy of this Profile?');
      self::$_actionLinks = [
        CRM_Core_Action::BROWSE => [
          'name' => ts('Fields'),
          'url' => 'civicrm/admin/uf/group/field',
          'qs' => 'reset=1&action=browse&gid=%%id%%',
          'title' => ts('View and Edit Fields'),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/uf/group/update',
          'qs' => 'action=update&id=%%id%%&context=group',
          'title' => ts('Edit CiviCRM Profile Group'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=preview&id=%%id%%&field=0&context=group',
          'title' => ts('Edit CiviCRM Profile Group'),
        ],
        CRM_Core_Action::ADD => [
          'name' => ts('Publish Online Profile'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=profile&gid=%%id%%',
          'title' => ts('HTML Form Snippet for this Profile'),
        ],
        /*
        CRM_Core_Action::VIEW => array(
          'name' => ts('Public Pages'),
          'url' => 'civicrm/profile',
          'qs' => 'reset=1&gid=%%id%%',
          'title' => ts('Search in public pages when enabled in profile settings'),
        ),
        */
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_UFGroup' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable CiviCRM Profile Group'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_UFGroup' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable CiviCRM Profile Group'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete CiviCRM Profile Group'),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy Profile'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=copy&gid=%%id%%&key=%%key%%',
          'title' => ts('Make a Copy of CiviCRM Profile Group'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ],
        CRM_Core_Action::REOPEN => [
          'name' => ts('Traffic Source'),
          'title' => ts('Traffic Source'),
          'url' => 'civicrm/track/report',
          'qs' => 'reset=1&ptype=civicrm_uf_group&pid=%%id%%',
          'uniqueName' => 'traffic_source',
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @param
   *
   * @return void
   * @access public
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE,
      // default to 'browse'
      'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE | CRM_Core_Action::DISABLE)) {
      $this->edit($id, $action);
    }
    else {
      // if action is enable or disable do the needful.
      if ($action & CRM_Core_Action::ENABLE) {

        CRM_Core_BAO_UFGroup::setIsActive($id, 1);

        // update cms integration with registration / my account

        CRM_Utils_System::updateCategories();
      }
      elseif ($action & CRM_Core_Action::PROFILE) {
        $this->profileCode();
      }
      elseif ($action & CRM_Core_Action::PREVIEW) {
        $this->preview($id, $action);
      }
      elseif ($action & CRM_Core_Action::COPY) {
        $this->copy();
      }
      // finally browse the uf groups
      $this->browse();
    }
    // parent run
    parent::run();
  }

  /**
   * This function is to make a copy of a profile, including
   * all the fields in the profile
   *
   * @return void
   * @access public
   */
  function copy() {
    $key = CRM_Utils_Request::retrieve('key', 'String',
      CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
    );

    $name = get_class($this);
    if (!CRM_Core_Key::validate($key, $name)) {
      return CRM_Core_Error::statusBounce(ts('Sorry, we cannot process this request for security reasons. The request may have expired or is invalid. Please return to the profile list and try again.'));
    }

    $gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, TRUE, 0, 'GET'
    );


    $copy = CRM_Core_BAO_UFGroup::copy($gid);

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/uf/group/update', 'reset=1&action=update&id=' . $copy->id));
  }

  /**
   * This function is for profile mode (standalone html form ) for uf group
   *
   * @return void
   * @access public
   */
  function profileCode() {
    $template = CRM_Core_Smarty::singleton();
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET');
    if ($gid) {
      $this->assign('gid', $gid);
      $iframeSrc = CRM_Utils_System::url('civicrm/profile/create', 'reset=1&embed=1&gid='.$gid, TRUE, NULL, FALSE);
      $this->assign('iframeSrc', $iframeSrc);
      $this->assign('iframeWidth', '100%');
      $iframeCode = trim($template->fetch('CRM/common/iframe.tpl'));
      $this->assign('profile', htmlentities($iframeCode, ENT_NOQUOTES, 'UTF-8'));

      $shorten = CRM_Core_OptionGroup::getValue('shorten_url', 'civicrm_uf_group.'.$gid, 'name', 'String', 'value');
      if ($shorten) {
        $this->assign('shorten', $shorten);
      }
      
      //get the title of uf group
      $title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gid, 'title');
      $title = $title . ' - ' . ts('Publish Online Profile');
      CRM_Utils_System::setTitle($title);
    }
    else {
      $title = 'Profile Form';
      CRM_Utils_System::setTitle(ts('%1 - HTML Form Snippet', [1 => $this->_title]));
    }

    $this->assign('title', $title);
    $this->assign('action', CRM_Core_Action::PROFILE);
    $this->assign('isForm', 0);
  }

  /**
   * edit uf group
   *
   * @param int $id uf group id
   * @param string $action the action to be invoked
   *
   * @return void
   * @access public
   */
  function edit($id, $action) {
    // create a simple controller for editing uf data
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Group', ts('CiviCRM Profile Group'), $action);
    $this->setContext($id, $action);
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Browse all uf data groups.
   *
   * @param
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    $ufGroup = [];
    $allUFGroups = [];

    $allUFGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup();
    if (empty($allUFGroups)) {
      return;
    }

    require_once 'CRM/Utils/Hook.php';
    // Add key for action validation
    $name = get_class($this);
    $key = CRM_Core_Key::get($name);
    $this->assign('key', $key);

    $ufGroups = CRM_Core_PseudoConstant::ufGroup();
    CRM_Utils_Hook::aclGroup(CRM_Core_Permission::ADMIN, NULL, 'civicrm_uf_group', $ufGroups, $allUFGroups);
    $restrictType = [
      'Contribution',
      'Membership',
      'Activity',
      'Participant',
    ];

    foreach ($allUFGroups as $id => $value) {
      $ufGroup[$id] = [];
      $ufGroup[$id]['id'] = $id;
      $ufGroup[$id]['title'] = $value['title'];
      $ufGroup[$id]['is_active'] = $value['is_active'];
      $ufGroup[$id]['group_type'] = $value['group_type'];
      $ufGroup[$id]['is_reserved'] = $value['is_reserved'];

      // form all action links
      $action = array_sum(array_keys($this->actionLinks()));

      // update enable/disable links depending on uf_group properties.
      if ($value['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      // drop certain actions if the profile is reserved
      if ($value['is_reserved']) {
        $action -= CRM_Core_Action::UPDATE;
        $action -= CRM_Core_Action::DISABLE;
        $action -= CRM_Core_Action::DELETE;
      }
      $groupTypes = self::extractGroupTypes($value['group_type']);
      $groupComponents = ['Contribution', 'Membership', 'Activity', 'Participant'];

      $groupTypesString = '';
      if (!empty($groupTypes)) {
        $groupTypesStrings = [];
        foreach ($groupTypes as $groupType => $typeValues) {
          if (is_array($typeValues)) {
            if ($groupType == 'Participant') {
              foreach ($typeValues as $subType => $subTypeValues) {
                $groupTypesStrings[] = $subType . '::' . CRM_Utils_Array::implode(': ', $subTypeValues);
              }
            }
            else {
              $groupTypesStrings[] = ts($groupType) . '::' . CRM_Utils_Array::implode(': ', current($typeValues));
            }
          }
          else {
            $groupTypesStrings[] = ts($groupType);
          }
        }
        $groupTypesString = CRM_Utils_Array::implode(', ', $groupTypesStrings);
      }
      $ufGroup[$id]['group_type'] = $groupTypesString;

      // remove traffic source and embed profile when modules doesn't have profile
      // drop Create, Edit and View mode links if profile group_type is Contribution, Membership, Activities or Participant
      $ufJoinRecords = CRM_Core_BAO_UFGroup::getUFJoinRecord($id);
      $profileTypes = explode(',', $value['group_type']);
      if (!in_array('Profile', $ufJoinRecords) || array_intersect($restrictType, $profileTypes) || $value['is_reserved']) {
        $action -= CRM_Core_Action::ADD;
        $action -= CRM_Core_Action::REOPEN;
      }

      $ufGroup[$id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        [
          'id' => $id,
          'key' => $key
        ]
      );


      // Create module list, prevent duplicate string
      //get the "Used For" from uf_join
      $modules = CRM_Core_BAO_UFGroup::getUFJoinRecord($id, TRUE);
      foreach ($modules as $k => $v) {
        $modules[$k] = ts(str_replace("_", " ", $v));
      }
      $modules = array_unique($modules);
      $ufGroup[$id]['module'] = CRM_Utils_Array::implode(',<br />', $modules);
    }

    $this->assign('rows', $ufGroup);
  }

  /**
   * this function is for preview mode for ufoup
   *
   * @param int $id uf group id
   *
   * @return void
   * @access public
   */
  function preview($id, $action) {
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Preview', ts('CiviCRM Profile Group Preview'), NULL);
    $this->setContext($id, $action);
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  function setContext($id, $action) {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);

    //we need to differentiate context for update and preview profile.
    if (!$context && !($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::PREVIEW))) {
      $context = 'group';
    }

    switch ($context) {
      case 'group':
        $url = CRM_Utils_System::url('civicrm/admin/uf/group', 'reset=1&action=browse');

        // as there is no argument after group in the url, and the context is different,
        // breadcrumb doesn't get set. And therefore setting it here -
        $breadCrumb = [['title' => ts('CiviCRM Profile'),
            'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'),
          ]];
        CRM_Utils_System::appendBreadCrumb($breadCrumb);
        break;

      case 'field':
        $url = CRM_Utils_System::url('civicrm/admin/uf/group/field',
          "reset=1&action=browse&gid={$id}"
        );
        break;
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  static function extractGroupTypes($groupType) {
    $returnGroupTypes = [];
    if (!$groupType) {
      return $returnGroupTypes;
    }

    $groupTypeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $groupType);
    foreach (explode(',', $groupTypeParts[0]) as $type) {
      $returnGroupTypes[$type] = $type;
    }

    if (CRM_Utils_Array::value(1, $groupTypeParts)) {
      foreach (explode(',', $groupTypeParts[1]) as $typeValue) {
        $groupTypeValues = $valueLabels = [];
        $valueParts = explode(':', $typeValue);
        $typeName = NULL;
        switch ($valueParts[0]) {
          case 'ContributionType':
            $typeName = 'Contribution';
            $valueLabels = CRM_Contribute_PseudoConstant::contributionType();
            break;

          case 'ParticipantRole':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::participantRole();
            break;

          case 'ParticipantEventName':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::event();
            break;

          case 'ParticipantEventType':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::eventType();
            break;

          case 'MembershipType':
            $typeName = 'Membership';
            $valueLabels = CRM_Member_PseudoConstant::membershipType();
            break;

          case 'ActivityType':
            $typeName = 'Activity';
            $valueLabels = CRM_Core_PseudoConstant::ActivityType(TRUE, TRUE, FALSE, 'label', TRUE);
            break;
        }

        foreach ($valueParts as $val) {
          if (CRM_Utils_Rule::integer($val)) {
            $groupTypeValues[$val] = CRM_Utils_Array::value($val, $valueLabels);
          }
        }

        if (!is_array($returnGroupTypes[$typeName])) {
          $returnGroupTypes[$typeName] = [];
        }
        $returnGroupTypes[$typeName][$valueParts[0]] = $groupTypeValues;
      }
    }
    return $returnGroupTypes;
  }

  public static function profile($gid = NULL) {
    $config = CRM_Core_Config::singleton();

    // reassign resource base to be the full url, CRM-4660
    $config->resourceBase = $config->userFrameworkResourceURL;

    if (empty($gid)) {
      $gid = CRM_Utils_Request::retrieve('gid', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET');
    }
    $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Edit', ts('Create'), CRM_Core_Action::ADD, FALSE, FALSE, TRUE);
    $controller->reset();
    $controller->process();
    $controller->set('gid', $gid);
    $controller->setEmbedded(TRUE);
    $controller->run();
    $template = CRM_Core_Smarty::singleton();
    $template->assign('gid', $gid);
    $template->assign('tplFile', $controller->getTemplateFileName());
    $profile = trim($template->fetch('CRM/Form/default.tpl'));
    // not sure how to circumvent our own navigation system to generate the right form url
    $form_url = CRM_Utils_System::url('civicrm/profile/create', 'gid=' . $gid . '&reset=1', FALSE);
    $form_url = str_replace($config->userFrameworkBaseURL, '', $form_url);
    $profile = str_replace('civicrm/admin/uf/group', $form_url, $profile);

    // add jquery files
    $profile = CRM_Utils_String::addJqueryFiles($profile);
    return $profile;
  }
}

