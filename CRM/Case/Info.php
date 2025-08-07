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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */
class CRM_Case_Info extends CRM_Core_Component_Info {


  // docs inherited from interface
  protected $keyword = 'case';

  // docs inherited from interface
  public function getInfo() {
    return ['name' => 'CiviCase',
      'translatedName' => ts('CiviCase'),
      'title' => ts('CiviCase Engine'),
      'search' => 1,
      'showActivitiesInCore' => 0,
    ];
  }

  // docs inherited from interface
  public function getPermissions() {
    return ['delete in CiviCase',
      'administer CiviCase',
      'access my cases and activities',
      'access all cases and activities',
    ];
  }

  // docs inherited from interface
  public function getUserDashboardElement() {
    return [];
  }

  // docs inherited from interface
  public function registerTab() {
    return ['title' => ts('Cases'),
      'url' => 'case',
      'weight' => 50,
    ];
  }

  // docs inherited from interface
  public function registerAdvancedSearchPane() {
    return ['title' => ts('Cases'),
      'weight' => 50,
    ];
  }

  // docs inherited from interface
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access all cases and activities') &&
      CRM_Core_Permission::check('add contacts')
    ) {

      $atype = CRM_Core_OptionGroup::getValue('activity_type',
        'Open Case',
        'name'
      );
      if ($atype) {
        $shortCuts = array_merge($shortCuts, [['path' => 'civicrm/case/add',
              'query' => "reset=1&action=add&atype=$atype&context=standalone",
              'ref' => 'new-case',
              'title' => ts('Case'),
            ]]);
      }
    }
  }
}

