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

class CRM_Contact_Form_Merge extends CRM_Core_Form {
  // the id of the contact that tere's a duplicate for; this one will
  // possibly inherit some of $_oid's properties and remain in the system
  var $_cid = NULL;

  // the id of the other contact - the duplicate one that will get deleted
  var $_oid = NULL;

  var $_contactType = NULL;

  // variable to keep all location block ids.
  protected $_locBlockIds = array();

  // FIXME: QuickForm can't create advcheckboxes with value set to 0 or '0' :(
  // see HTML_QuickForm_advcheckbox::setValues() - but patching that doesn't
  // help, as QF doesn't put the 0-value elements in exportValues() anyway...
  // to side-step this, we use the below UUID as a (re)placeholder
  var $_qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';

  function preProcess() {
    $this->_hasError = FALSE;
    if (!CRM_Core_Permission::check('merge duplicate contacts')) {
      return CRM_Core_Error::statusBounce(ts('You do not have access to this page'));
    }

    $rows = array();
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $oid = CRM_Utils_Request::retrieve('oid', 'Positive', $this, TRUE);
    $flip = CRM_Utils_Request::retrieve('flip', 'Positive', $this, FALSE);

    $this->_rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE);
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE);
    $this->_mergeId = CRM_Utils_Request::retrieve('mergeId', 'Positive', $this, FALSE);

    if (!self::validateContacts($cid, $oid)) {
      return CRM_Core_Error::statusBounce(ts('The selected pair of contacts are marked as non duplicates. If these records should be merged, you can remove this exception on the <a href="%1">Dedupe Exceptions</a> page.', array(1 => CRM_Utils_System::url('civicrm/dedupe/exception', 'reset=1'))));
      $this->_hasError = TRUE;
    }

    if (self::checkContactIsAdmin($cid, $oid)) {
      return CRM_Core_Error::statusBounce(ts('Cannot merge with the administrator, please recheck the selected contacts.'));
      $this->_hasError = TRUE;
    }

    // Block access if user does not have EDIT permissions for both contacts.
    if (!(CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT)
        && CRM_Contact_BAO_Contact_Permission::allow($oid, CRM_Core_Permission::EDIT)
      )) {
      CRM_Utils_System::permissionDenied();
    }

    // get user info of main contact.
    $config = CRM_Core_Config::singleton();
    $config->doNotResetCache = 1;
    $viewUser = CRM_Core_Permission::check('access user profiles');
    $mainUfId = CRM_Core_BAO_UFMatch::getUFId($cid);
    if ($mainUfId) {
      if ($config->userFramework == 'Drupal') {
        $mainUserName = CRM_Core_Config::$_userSystem->getBestUFName($mainUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $mainUser = JFactory::getUser($mainUfId);
      }
    }

    $this->assign('mainUfId', $mainUfId);
    $this->assign('mainUfName', $mainUserName);

    $flipUrl = CRM_Utils_system::url('civicrm/contact/merge',
      "reset=1&action=update&cid={$oid}&oid={$cid}&rgid={$rgid}&gid={$gid}"
    );
    if (!$flip) {
      $flipUrl .= '&flip=1';
    }
    $this->assign('flip', $flipUrl);

    // get user info of other contact.
    $otherUfId = CRM_Core_BAO_UFMatch::getUFId($oid);
    if ($otherUfId) {
      if ($config->userFramework == 'Drupal') {
        $otherUserName = CRM_Core_Config::$_userSystem->getBestUFName($otherUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $otherUser = JFactory::getUser($otherUfId);
      }
    }

    $this->assign('otherUfId', $otherUfId);
    $this->assign('otherUfName', $otherUserName);

    $cmsUser = ($mainUfId && $otherUfId) ? TRUE : FALSE;
    $this->assign('user', $cmsUser);

    $session = CRM_Core_Session::singleton();

    // context fixed.
    if ($rgid) {
      $urlParam = "reset=1&action=browse&rgid={$rgid}";
      if ($gid) {
        $urlParam .= "&gid={$gid}";
      }
      $session->pushUserContext(CRM_Utils_system::url('civicrm/contact/dedupefind', $urlParam));
    }

    // ensure that oid is not the current user, if so refuse to do the merge
    if ($session->get('userID') == $oid) {
      $display_name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $oid, 'display_name');
      $message = ts('The contact record which is linked to the currently logged in user account - \'%1\' - cannot be deleted.',
        array(1 => $display_name)
      );
      return CRM_Core_Error::statusBounce($message);
      $this->_hasError = TRUE;
    }

    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($cid, $oid);
    $main                = &$rowsElementsAndInfo['main_details'];
    $other               = &$rowsElementsAndInfo['other_details'];

    if ($main['contact_id'] != $cid) {
      return CRM_Core_Error::statusBounce(ts('The main contact record does not exist')." (".ts("Contact ID").":{$cid}) ");
      $this->_hasError = TRUE;
    }

    if ($other['contact_id'] != $oid) {
      return CRM_Core_Error::statusBounce(ts('The other contact record does not exist')." (".ts("Contact ID").":{$oid}) ");
      $this->_hasError = TRUE;
    }

    $subtypes = CRM_Contact_BAO_ContactType::subTypePairs(NULL, TRUE, '');
    $this->assign('contact_type', $main['contact_type']);
    if (isset($main['contact_sub_type'])) {
      $this->assign('main_contact_subtype',
        CRM_Utils_Array::value('contact_sub_type', $subtypes[$main['contact_sub_type'][0]])
      );
    }
    if (isset($other['contact_sub_type'])) {
      $this->assign('other_contact_subtype',
        CRM_Utils_Array::value('contact_sub_type', $subtypes[$other['contact_sub_type'][0]])
      );
    }
    $this->assign('main_name', $main['display_name']);
    $this->assign('other_name', $other['display_name']);
    $this->assign('main_cid', $main['contact_id']);
    $this->assign('other_cid', $other['contact_id']);
    $this->_cid         = $cid;
    $this->_oid         = $oid;
    $this->_rgid        = $rgid;
    $this->_contactType = $main['contact_type'];
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('onclick' => "return toggleCheckboxVals('move_',this);"));

    $this->assign('mainLocBlock', json_encode($rowsElementsAndInfo['main_loc_block']));
    $this->assign('rows', $rowsElementsAndInfo['rows']);

    $this->_locBlockIds = array(
      'main' => $rowsElementsAndInfo['main_details']['loc_block_ids'],
      'other' => $rowsElementsAndInfo['other_details']['loc_block_ids']
    );

    // add elements
    foreach ($rowsElementsAndInfo['elements'] as $element) {
      // special case for communication method
      $this->addElement($element[0],
        $element[1],
        CRM_Utils_Array::arrayKeyExists('2', $element) ? $element[2] : NULL,
        CRM_Utils_Array::arrayKeyExists('3', $element) ? $element[3] : NULL,
        CRM_Utils_Array::arrayKeyExists('4', $element) ? $element[4] : NULL,
        CRM_Utils_Array::arrayKeyExists('5', $element) ? $element[5] : NULL
      );
    }

    // add related table elements
    foreach ($rowsElementsAndInfo['rel_table_elements'] as $relTableElement) {
      $element = $this->addElement($relTableElement[0], $relTableElement[1]);
      $element->setChecked(TRUE);
    }

    $this->assign('rel_tables', $rowsElementsAndInfo['rel_tables']);
    $this->assign('userContextURL', $session->readUserContext());

    // add other location type id
    $allLocationTypes = CRM_Core_PseudoConstant::locationType(TRUE, 'name');
    $otherLocationTypeId = array_search('Other', $allLocationTypes);
    if ($otherLocationTypeId) {
      $this->assign('otherLocationTypeId', $otherLocationTypeId);
    }
    else {
      $this->assign('otherLocationTypeId', 0);
    }
  }

  function setDefaultValues() {
    return array('deleteOther' => 1);
  }

  function addRules() {}

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Merge Contacts'));
    if (!$this->_hasError) {
      $this->addButtons(array(
        array('type' => 'next', 'name' => ts('Merge'), 'isDefault' => TRUE),
        array('type' => 'cancel', 'name' => ts('Cancel')),
      ));
    }
  }

  public function postProcess() {
    $formValues = $this->exportValues();

    // reset all selected contact ids from session
    // when we came from search context, CRM-3526
    $session = CRM_Core_Session::singleton();
    if ($session->get('selectedSearchContactIds')) {
      $session->resetScope('selectedSearchContactIds');
    }

    $formValues['main_details'] = $formValues['other_details'] = array();
    $formValues['main_details']['contact_type'] = $this->_contactType;
    $formValues['main_details']['loc_block_ids'] = $this->_locBlockIds['main'];
    $formValues['other_details']['loc_block_ids'] = $this->_locBlockIds['other'];

    CRM_Dedupe_Merger::moveAllBelongings($this->_cid, $this->_oid, $formValues);

    CRM_Core_Session::setStatus(ts('Contact id %1 has been updated and contact id %2 has been deleted.', array(1 => $this->_cid, 2 => $this->_oid)), ts('Contacts Merged'), 'success');
    $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_cid}");
    CRM_Utils_System::redirect($url);
  }

  function validateContacts($cid, $oid) {
    if (!$cid || !$oid) {
      return;
    }

    $exception = new CRM_Dedupe_DAO_Exception();
    $exception->contact_id1 = $cid;
    $exception->contact_id2 = $oid;
    //make sure contact2 > contact1.
    if ($cid > $oid) {
      $exception->contact_id1 = $oid;
      $exception->contact_id2 = $cid;
    }
    if ($exception->find(TRUE)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  function checkContactIsAdmin($cid, $oid) {
    // Check contact is admin or not
    $cidSql = "SELECT uf_id FROM `civicrm_uf_match` where contact_id = %1";
    $cidParams = array( 1 => array($cid, 'Integer'));
    $cidUid = CRM_Core_DAO::singleValueQuery($cidSql, $cidParams);

    $oidSql = "SELECT uf_id FROM `civicrm_uf_match` where contact_id = %1";
    $oidParams = array( 1 => array($oid, 'Integer'));
    $oidUid = CRM_Core_DAO::singleValueQuery($oidSql, $oidParams);

    if ($cidUid == 1 || $oidUid == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}

