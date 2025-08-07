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




class CRM_UF_Form_AdvanceSetting extends CRM_UF_Form_Group {

  /**
   * Function to build the form for Advance Settings.
   *
   * @access public
   *
   * @return None
   */
  static function buildAdvanceSetting(&$form) {
    // should mapping be enabled for this group
    $form->addElement('checkbox', 'is_map', ts('Enable mapping for this profile?'));

    // should we allow updates on a exisitng contact
    $options = [
      0 => ts('Issue warning and do not save'),
      1 => ts('Update the matching contact'),
      2 => ts('Allow duplicate contact to be created'),
    ];
    $form->addRadio('is_update_dupe', ts('What to do upon duplicate match'), $options, NULL, '<br>');

    // we do not have any url checks to allow relative urls
    $form->addElement('text', 'post_URL', ts('Redirect URL after Submitted'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'post_URL'));
    $form->addElement('text', 'cancel_URL', ts('Back to Website URL'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'cancel_URL'));

    // add select for groups
    $group = ['' => ts('- select -')] + $form->_group;
    $form->_groupElement = &$form->addElement('select', 'group', ts('Limit listings to a specific Group?'), $group);

    // add CAPTCHA To this group ?
    $form->addElement('checkbox', 'add_captcha', ts('Include reCAPTCHA?'));

    // should we display an edit link
    $form->addElement('checkbox', 'is_edit_link', ts('Include profile edit links in search results?'));

    // should we display a link to the website profile
    $config = CRM_Core_Config::singleton();
    $form->addElement('checkbox', 'is_uf_link', ts('Include %1 user account information links in search results?', [1 => $config->userFramework]));

    // want to create cms user
    $session = CRM_Core_Session::singleton();
    $cmsId = FALSE;
    if ($form->_cId = $session->get('userID')) {
      $form->_cmsId = TRUE;
    }

    $options = [
      0 => ts('No account create option'),
      1 => ts('Give option, but not required'),
      2 => ts('Account creation required'),
    ];
    $form->addRadio('is_cms_user', ts('%1 user account registration option?'), $options, NULL, '<br>');

    // options for including Proximity Search in the profile search form
    $proxOptions = [
      0 => ts('None'),
      1 => ts('Optional'),
      2 => ts('Required'),
    ];
    $form->addRadio('is_proximity_search', ts('Proximity search'), $proxOptions, NULL, '<br>');

    // add is in other situation to this group
    $form->addElement('checkbox', 'is_in_other_situation', ts('Use in other situation'), ts('Used for Batch Update and Contact Search Profile.'));

    $ufJoinRecords = CRM_Core_BAO_UFGroup::getUFJoinRecord($form->_id);
    if (!empty($ufJoinRecords) && $form->_action & CRM_Core_Action::UPDATE) {
      $ele = $form->getElement('is_in_other_situation');
      $ele->freeze();
    }
  }
}

