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
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
 */
class CRM_Contact_Form_Task_RemoveFromGroup extends CRM_Contact_Form_Task {

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    // add select for groups
    $group = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::group();
    $groupElement = $this->add('select', 'group_id', ts('Select Group'), $group, TRUE);

    CRM_Utils_System::setTitle(ts('Remove Contacts from Group'));
    $this->addDefaultButtons(ts('Remove from Group'));

    if ($this->get('context') === 'smog' && !empty($this->get('gid'))) {
      $smartMarketing = CRM_Mailing_External_SmartMarketing::getProviderByGroup($this->get('gid'));
      if (!empty($smartMarketing)) {
        $this->assign('smart_marketing_hint', TRUE);
        $groupElement->freeze();
      }
    }
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function &setDefaultValues() {
    $defaults = [];

    if ($this->get('context') === 'smog') {
      $defaults['group_id'] = $this->get('gid');
    }
    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $groupId = $this->controller->exportValue('RemoveFromGroup', 'group_id');
    $group = &CRM_Core_PseudoConstant::group();

    list($total, $removed, $notRemoved) = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($this->_contactIds, $groupId);
    $status = [
      ts('Removed Contact(s) from %1', [1 => $group[$groupId]]),
      ts('Total Selected Contact(s): %1', [1 => $total]),
    ];
    if ($removed) {
      $status[] = ts('Total Contact(s) removed from group: %1', [1 => $removed]);
    }
    if ($notRemoved) {
      $status[] = ts('Total Contact(s) not in group: %1', [1 => $notRemoved]);
      $status[] = ts('Total Contact(s) with negative membership in group: %1', [1 => $notRemoved]);
    }
    CRM_Core_Session::setStatus($status);
  }
  //end of function
}

