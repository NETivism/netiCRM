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
 * Given an argument list, invoke the appropriate CRM function
 * Serves as a wrapper between the UserFrameWork and Core CRM
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */
class CRM_Core_Invoke {

  /**
   * This is the main function that is called on every click action and based on the argument
   * respective functions are called
   *
   * @param $args array this array contains the arguments of the url
   *
   * @static
   * @access public
   */
  static function invoke($args) {
    if ($args[0] !== 'civicrm') {
      return;
    }

    if (isset($args[1]) and $args[1] == 'menu' and
      isset($args[2]) and $args[2] == 'rebuild'
    ) {
      // ensure that the user has a good privilege level
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        CRM_Core_Menu::store();
        CRM_Core_Session::setStatus(ts('Menu has been rebuilt'));

        // also reset navigation
        CRM_Core_BAO_Navigation::resetNavigation();

        return CRM_Utils_System::redirect();
      }
      else {
        CRM_Core_Error::fatal('You do not have permission to execute this url');
      }
    }

    $config = CRM_Core_Config::singleton();
    if ($config->debug) {
      $sessionReset = CRM_Utils_Request::retrieve('sessionReset', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET');
      if ($sessionReset) {
        $config->sessionReset();
      }
    }

    // first fire up IDS and check for bad stuff
    if ($config->useIDS) {
      $ids = new CRM_Core_IDS();
      $ids->check($args);
    }

    // also initialize the i18n framework
    $i18n = CRM_Core_I18n::singleton();

    if ($config->userFramework == 'Standalone') {
      $session = CRM_Core_Session::singleton();
      if ($session->get('new_install') !== TRUE) {
        CRM_Core_Standalone::sidebarLeft();
      }
      elseif ($args[1] == 'standalone' && $args[2] == 'register') {
        CRM_Core_Menu::store();
      }
    }

    // get the menu items
    $path = implode('/', $args);
    $item = CRM_Core_Menu::get($path);

    // we should try to compute menus, if item is empty and stay on the same page,
    // rather than compute and redirect to dashboard.
    if (!$item) {
      CRM_Core_Menu::store(FALSE);
      $item = &CRM_Core_Menu::get($path);
    }

    if ($config->userFramework == 'Joomla' && $item) {
      $config->userFrameworkURLVar = 'task';

      // joomla 1.5RC1 seems to push this in the POST variable, which messes
      // QF and checkboxes
      unset($_POST['option']);
      CRM_Core_Joomla::sidebarLeft();
    }

    // set active Component
    $template = CRM_Core_Smarty::singleton();
    $template->assign('activeComponent', 'CiviCRM');
    $template->assign('formTpl', 'default');

    if ($item) {
      if (!array_key_exists('page_callback', $item)) {
        CRM_Core_Error::debug('Bad item', $item);
        CRM_Core_Error::fatal(ts('Bad menu record in database'));
      }

      // check that we are permissioned to access this page
      if (!CRM_Core_Permission::checkMenuItem($item)) {
        CRM_Utils_System::permissionDenied();
        return;
      }

      // check if ssl is set
      if (CRM_Utils_Array::value('is_ssl', $item)) {
        CRM_Utils_System::redirectToSSL();
      }

      if (isset($item['title'])) {
        CRM_Utils_System::setTitle($item['title']);
      }

      if (isset($item['breadcrumb']) && !isset($item['is_public'])) {
        foreach($item['breadcrumb'] as $k => $b){
          $item['breadcrumb'][$k]['url'] = CRM_Utils_System::url($b['url']);
        }
        CRM_Utils_System::appendBreadCrumb($item['breadcrumb']);
      }

      $pageArgs = NULL;
      if (CRM_Utils_Array::value('page_arguments', $item)) {
        $pageArgs = CRM_Core_Menu::getArrayForPathArgs($item['page_arguments']);
      }

      $template = CRM_Core_Smarty::singleton();
      if (isset($item['is_public']) && $item['is_public']) {
        $template->assign('urlIsPublic', TRUE);
      }
      else {
        $template->assign('urlIsPublic', FALSE);
      }

      if (isset($item['return_url'])) {
        $session = CRM_Core_Session::singleton();
        $args = CRM_Utils_Array::value('return_url_args', $item, 'reset=1');
        $session->pushUserContext(CRM_Utils_System::url($item['return_url'], $args));
      }
      $template->assign('callbackPath', $item['page_callback']);

      $result = NULL;
      if (is_array($item['page_callback'])) {
        $newArgs = explode('/', $_GET[$config->userFrameworkURLVar]);
        $result = call_user_func($item['page_callback'], $newArgs);
      }
      elseif (strstr($item['page_callback'], '_Form')) {
        $wrapper = new CRM_Utils_Wrapper();
        $result = $wrapper->run(
          CRM_Utils_Array::value('page_callback', $item),
          CRM_Utils_Array::value('title', $item),
          isset($pageArgs) ? $pageArgs : NULL
        );
      }
      else {
        $newArgs = explode('/', $_GET[$config->userFrameworkURLVar]);
        $mode = 'null';
        if (isset($pageArgs['mode'])) {
          $mode = $pageArgs['mode'];
          unset($pageArgs['mode']);
        }
        $title = CRM_Utils_Array::value('title', $item);
        $callback = $item['page_callback'];
        if (strstr($callback, '_Page')) {
          $object = new $callback($title, $mode);
        }
        elseif (strstr($callback, '_Controller')) {
          $addSequence = 'false';
          if (isset($pageArgs['addSequence'])) {
            $addSequence = $pageArgs['addSequence'];
            $addSequence = $addSequence ? 'true' : 'false';
            unset($pageArgs['addSequence']);
          }
          $object = new $callback($title, TRUE, $mode, NULL, $addSequence);
        }
        else {
          CRM_Core_Error::fatal();
        }
        if ($config->debug) {
          if (method_exists($object, 'editForm')) {
            $template->assign('callbackArgs', $object->editForm());
          }
        }
        $result = $object->run($newArgs, $pageArgs);
      }

      CRM_Core_Session::storeSessionObjects();
      return $result;
    }

    CRM_Core_Menu::store();
    CRM_Core_Session::setStatus(ts('Menu has been rebuilt'));
    return CRM_Utils_System::redirect();
  }

  /**
   * This function contains the default action
   *
   * @param $action
   *
   * @static
   * @access public
   */
  static function form($action, $contact_type, $contact_sub_type) {
    CRM_Utils_System::setUserContext(array('civicrm/contact/search/basic', 'civicrm/contact/view'));
    $wrapper = new CRM_Utils_Wrapper();

    require_once 'CRM/Core/Component.php';
    $properties = &CRM_Core_Component::contactSubTypeProperties($contact_sub_type, 'Edit');
    if ($properties) {
      $wrapper->run($properties['class'], ts('New %1', array(1 => $contact_sub_type)), $action, TRUE);
    }
    else {
      $wrapper->run('CRM_Contact_Form_Contact', ts('New Contact'), $action, TRUE);
    }
  }

  /**
   * This function contains the actions for profile arguments
   *
   * @param $args array this array contains the arguments of the url
   *
   * @static
   * @access public
   */
  static function profile($args) {
    if ($args[1] !== 'profile') {
      return;
    }

    $secondArg = CRM_Utils_Array::value(2, $args, '');

    if ($secondArg == 'map') {
      $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Task_Map',
        ts('Map Contact'),
        NULL, FALSE, FALSE, TRUE
      );

      $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

      if (count($gids) > 1) {
        foreach ($gids as $pfId) {
          $profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
        $controller->set('gid', $profileIds[0]);
        $profileGID = $profileIds[0];
      }
      else {
        $profileGID = CRM_Utils_Request::retrieve('gid', 'Integer',
          $controller,
          TRUE
        );
      }

      $profileView = CRM_Utils_Request::retrieve('pv', 'Integer',
        $controller,
        FALSE
      );
      // set the userContext stack
      $session = CRM_Core_Session::singleton();
      if ($profileView) {
        $session->pushUserContext(CRM_Utils_System::url('civicrm/profile/view'));
      }
      else {
        $session->pushUserContext(CRM_Utils_System::url('civicrm/profile', 'force=1'));
      }

      $controller->set('profileGID', $profileGID);
      $controller->process();
      return $controller->run();
    }

    if ($secondArg == 'edit' || $secondArg == 'create') {
      // set the userContext stack
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/profile', 'reset=1'));

      $buttonType = CRM_Utils_Array::value('_qf_Edit_cancel', $_POST);
      // CRM-5849: we should actually check the button *type*, but we get the *value*, potentially translated;
      // we should keep both English and translated checks just to make sure we also handle untranslated Cancels
      if ($buttonType == 'Cancel' or $buttonType == ts('Cancel')) {
        $cancelURL = CRM_Utils_Request::retrieve('cancelURL',
          'String',
          CRM_Core_DAO::$_nullObject,
          FALSE,
          NULL,
          $_POST
        );
        if ($cancelURL) {
          CRM_Utils_System::redirect($cancelURL);
        }
      }

      if ($secondArg == 'edit') {
        $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Edit',
          ts('Create Profile'),
          CRM_Core_Action::UPDATE,
          FALSE, FALSE, TRUE
        );
        $controller->set('edit', 1);
        $controller->process();
        return $controller->run();
      }
      else {
        $embed = CRM_Utils_Request::retrieve('embed', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE);
        if ($embed) {
          // #28162, check if Same Origin
          setcookie('hasCookiePermission', 1, 0, '/');
          $sameOrigin = FALSE;
          if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = parse_url($_SERVER['HTTP_REFERER']);
            if ($url['host'] == $_SERVER['HTTP_HOST']) {
              $sameOrigin = TRUE;
            }
          }
          $profile = CRM_UF_Page_Group::profile();
          $template = CRM_Core_Smarty::singleton();
          $template->assign('sameOrigin', $sameOrigin);
          $template->assign('embedBody', $profile);
          $content = $template->fetch('CRM/common/Embed.tpl');
          echo $content; 
          return;
        }
        else {
          $wrapper = new CRM_Utils_Wrapper();
          return $wrapper->run('CRM_Profile_Form_Edit',
            ts('Create Profile'),
            array('mode' => CRM_Core_Action::ADD,
              'ignoreKey' => TRUE,
            )
          );
        }
      }
    }

    require_once 'CRM/Profile/Page/Listings.php';
    $page = new CRM_Profile_Page_Listings();
    return $page->run();
  }
}

