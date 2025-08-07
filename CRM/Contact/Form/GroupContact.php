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
 * This class generates form components for groupContact
 *
 */
class CRM_Contact_Form_GroupContact extends CRM_Core_Form {

  public $_context;
  /**
   * The groupContact id, used when editing the groupContact
   *
   * @var int
   */
  protected $_groupContactId;

  /**
   * The contact id, used when add/edit groupContact
   *
   * @var int
   */
  protected $_contactId; function preProcess() {

    $this->_contactId = $this->get('contactId');
    $this->_groupContactId = $this->get('groupContactId');
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
  }

  /**
   * This function sets the default values for the form. GroupContact that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = [];
    $params = [];

    return $defaults;
  }

  /**
   * This function is used to add the rules for form.
   *
   * @return None
   * @access public
   */
  function addRules() {}

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    // get the list of all the groups
    if ($this->_context == 'user') {
      $onlyPublicGroups = CRM_Utils_Request::retrieve('onlyPublicGroups', 'Boolean', $this, FALSE);
      $allGroups = CRM_Core_PseudoConstant::staticGroup($onlyPublicGroups);
    }
    else {
      $allGroups = CRM_Core_PseudoConstant::group();
    }

    // get the list of groups for the contact
    $currentGroups = CRM_Contact_BAO_GroupContact::getGroupList($this->_contactId);

    if (is_array($currentGroups)) {
      $groupList = array_diff($allGroups, $currentGroups);
    }
    else {
      $groupList = $allGroups;
    }

    $groupList[''] = ts('- select group -');
    asort($groupList);

    if (count($groupList) > 1) {
      $session = CRM_Core_Session::singleton();
      // user dashboard
      if (strstr($session->readUserContext(), 'user')) {
        $msg = ts('Join a Group');
      }
      else {
        $msg = ts('Add to a group');
      }

      $this->add('select', 'group_id', $msg, $groupList, TRUE);

      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Add'),
            'isDefault' => TRUE,
          ],
        ]
      );
    }
  }

  /**
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $contactID = [$this->_contactId];
    $groupId = $this->controller->exportValue('GroupContact', 'group_id');
    $method = 'Admin';
    $method = ($this->_context == 'user') ? 'Web' : 'Admin';

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID == $this->_contactId) {
      $method = 'Web';
    }

    $groupContact = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactID, $groupId, $method);

    if ($groupContact && $this->_context != 'user') {
      CRM_Core_Session::setStatus(ts('Contact has been added to the selected group.'));
    }
  }
  //end of function
}

