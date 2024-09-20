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
 * Page for displaying Administer CiviCRM Control Panel
 */
class CRM_Admin_Page_Admin extends CRM_Core_Page {
  public $_showHide;
  function run() {
    $groups = array('Customize' => ts('Customize'),
      'Configure' => ts('Configure'),
      'Manage' => ts('Manage'),
      'Option Lists' => ts('Option Lists'),
    );

    $config = CRM_Core_Config::singleton();
    if (in_array("CiviContribute", $config->enableComponents)) {
      $groups['CiviContribute'] = ts('CiviContribute');
    }

    if (in_array("CiviMember", $config->enableComponents)) {
      $groups['CiviMember'] = ts('CiviMember');
    }

    if (in_array("CiviEvent", $config->enableComponents)) {
      $groups['CiviEvent'] = ts('CiviEvent');
    }

    if (in_array("CiviMail", $config->enableComponents)) {
      $groups['CiviMail'] = ts('CiviMail');
    }

    if (in_array("CiviCase", $config->enableComponents)) {
      $groups['CiviCase'] = ts('CiviCase');
    }

    if (in_array("CiviReport", $config->enableComponents)) {
      $groups['CiviReport'] = ts('CiviReport');
    }

    if (in_array("CiviCampaign", $config->enableComponents)) {
      $groups['CiviCampaign'] = ts('CiviCampaign');
    }


    $values = &CRM_Core_Menu::getAdminLinks();


    $this->_showHide = new CRM_Core_ShowHideBlocks();
    foreach ($groups as $group => $title) {
      $this->_showHide->addShow("id_{$group}_show");
      $this->_showHide->addHide("id_{$group}");
      $v = CRM_Core_ShowHideBlocks::links($this, $group, '', '', FALSE);
      $adminPanel[$group] = $values[$group];
      $adminPanel[$group]['show'] = $v['show'];
      $adminPanel[$group]['hide'] = $v['hide'];
      $adminPanel[$group]['title'] = $title;
    }

    $this->assign('adminPanel', $adminPanel);
    $this->_showHide->addToTemplate();
    return parent::run();
  }
}

