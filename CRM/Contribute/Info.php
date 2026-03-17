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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Contribute_Info extends CRM_Core_Component_Info {

  // docs inherited from interface
  protected $keyword = 'contribute';

  /**
   * Get component info
   *
   * @return array
   */
  public function getInfo() {
    return ['name' => 'CiviContribute',
      'translatedName' => ts('CiviContribute'),
      'title' => ts('CiviCRM Contribution Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * Get permissions for this component
   *
   * @return array
   */
  public function getPermissions() {
    return ['access CiviContribute',
      'edit contributions',
      'make online contributions',
      'delete in CiviContribute',
    ];
  }

  /**
   * Get user dashboard element for this component
   *
   * @return array
   */
  public function getUserDashboardElement() {
    return ['name' => ts('Contributions'),
      'title' => ts('Your Contribution(s)'),
      'perm' => ['make online contributions'],
      'weight' => 10,
    ];
  }

  /**
   * Register tab for this component
   *
   * @return array
   */
  public function registerTab() {
    return ['title' => ts('Contributions'),
      'url' => 'contribution',
      'weight' => 20,
    ];
  }

  /**
   * Register advanced search pane for this component
   *
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return ['title' => ts('Contributions'),
      'weight' => 20,
    ];
  }

  /**
   * Get activity types for this component
   *
   * @return array|null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * Add shortcut to Create New
   *
   * @param array $shortCuts
   *
   * @return void
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access CiviContribute') &&
      CRM_Core_Permission::check('edit contributions')
    ) {
      $shortCuts = array_merge($shortCuts, [['path' => 'civicrm/contribute/add',
            'query' => "reset=1&action=add&context=standalone",
            'ref' => 'new-contribution',
            'title' => ts('Contribution'),
          ]]);
    }
  }
}
