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

require_once 'CRM/Contact/Page/View/UserDashBoard.php';
class CRM_Contact_Page_View_UserDashBoard_GroupContact extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * This function is called when action is browse
   *
   * return null
   * @access public
   */
  function browse() {
    $count = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId,
      NULL,
      NULL, TRUE, TRUE,
      $this->_onlyPublicGroups
    );

    $in = &CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId,
      'Added',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups
    );
    $pending = &CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId,
      'Pending',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups
    );
    $out = &CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId,
      'Removed',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups
    );

    $this->assign('groupCount', $count);
    $this->assign_by_ref('groupIn', $in);
    $this->assign_by_ref('groupPending', $pending);
    $this->assign_by_ref('groupOut', $out);

    // Add key for deleting/rejoin group action validation.
    $name = get_class($this);
    $key = CRM_Core_Key::get($name);
    $this->assign('key', $key);
  }

  /**
   * This function is called when action is update
   *
   * @param int    $groupID group id
   *
   * return null
   * @access public
   */
  function edit($groupId = NULL) {
    $this->assign('edit', $this->_edit);
    if (!$this->_edit) {
      return;
    }

    $action = CRM_Utils_Request::retrieve('action', 'String',
      CRM_Core_DAO::$_nullObject,
      FALSE, 'browse'
    );

    if ($action == CRM_Core_Action::DELETE) {
      $key = CRM_Utils_Request::retrieve('key', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST');
      $name = get_class($this);
      if( !CRM_Core_Key::validate($key, $name) ) {
         return CRM_Core_Error::statusBounce(ts('We can\'t load the requested web page due to an incomplete link. This can be caused by using your browser\'s Back button or by using an incomplete or invalid link.'));
      }

      $groupContactId = CRM_Utils_Request::retrieve('gcid', 'Positive',
        CRM_Core_DAO::$_nullObject, TRUE
      );
      $status = CRM_Utils_Request::retrieve('st', 'String',
        CRM_Core_DAO::$_nullObject, TRUE
      );
      if (is_numeric($groupContactId) && $status) {
        require_once 'CRM/Contact/Page/View/GroupContact.php';
        CRM_Contact_Page_View_GroupContact::del($groupContactId, $status, $this->_contactId);
      }

      $url = CRM_Utils_System::url('civicrm/user',
        "reset=1&id={$this->_contactId}"
      );
      CRM_Utils_System::redirect($url);
    }

    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_GroupContact',
      ts("Contact's Groups"),
      CRM_Core_Action::ADD
    );
    $controller->setEmbedded(TRUE);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/user',
        "reset=1&id={$this->_contactId}"
      ),
      FALSE
    );

    $controller->reset();
    $controller->set('contactId', $this->_contactId);
    $controller->set('groupId', $groupId);
    $controller->set('context', 'user');
    $controller->set('onlyPublicGroups', $this->_onlyPublicGroups);
    $controller->process();
    $controller->run();
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->edit();
    $this->browse();
  }
}

