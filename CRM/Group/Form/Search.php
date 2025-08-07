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


class CRM_Group_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    parent::preProcess();
  }

  function setDefaultValues() {
    $defaults = [];
    $defaults['active_status'] = 1;
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );


    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    foreach ($groupTypes as $g => $v) {
      $tsg = ts($g);
      $tsGroupTypes[$tsg] = $v;
    }
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Joomla') {
      unset($groupTypes['Access Control']);
    }

    $this->addCheckBox('group_type',
      ts('Type'),
      $tsGroupTypes,
      NULL, NULL, NULL, NULL, '<br>'
    );

    $this->addCheckBox('group_mode',
      ts('Mode'),
      [
        '0' => ts('Normal'),
        '1' => ts('Smart'),
      ], NULL, NULL, NULL, NULL, '<br>', TRUE
    );

    $this->add('select', 'visibility', ts('Visibility'),
      ['' => ts('- any visibility -')] + CRM_Core_SelectValues::ufVisibility(TRUE)
    );
    $this->addElement('checkbox', 'active_status', ts('Enabled'));
    $this->addElement('checkbox', 'inactive_status', ts('Disabled'));
    $this->addButtons([
        ['type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]);

    parent::buildQuickForm();
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = ['title', 'group_type', 'visibility', 'active_status', 'inactive_status', 'group_mode'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}

