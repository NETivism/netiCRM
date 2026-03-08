<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 *
 */

/**
 * Helper class to build navigation links for contribution page tabs.
 */
class CRM_Contribute_Form_ContributionPage_TabHeader {

  /**
   * Build the tab header for the contribution page.
   *
   * This method retrieves existing tabs from the form state or generates them,
   * assigns them to the template, and determines the selected tab.
   *
   * @param CRM_Core_Form $form the form object to which the tabs belong
   *
   * @return array the array of tab definitions
   */
  public static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || !CRM_Utils_Array::value('reset', $_GET)) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    $form->assign_by_ref('selectedTab', self::getCurrentTab($tabs));
    return $tabs;
  }

  /**
   * Process and define the tabs for the contribution page.
   *
   * This method initializes the list of available tabs (Settings, Amounts,
   * Memberships, etc.), calculates their links, and determines which one is active.
   *
   * @param CRM_Core_Form $form the form object
   *
   * @return array|null the array of processed tabs, or null if no contribution page ID is found
   */
  public static function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $tabs = [
      'settings' => ['title' => ts('Title'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'amount' => ['title' => ts('Amounts'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'membership' => ['title' => ts('Memberships'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'thankyou' => ['title' => ts('Receipt'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'friend' => ['title' => ts('Tell a Friend'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'custom' => ['title' => ts('Profiles'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'premium' => ['title' => ts('Premiums'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'widget' => ['title' => ts('Widgets'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'pcp' => ['title' => ts('Personal Campaigns'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
    ];

    $contribPageId = $form->getVar('_id');
    $fullName = $form->getVar('_name');
    $className = CRM_Utils_String::getClassName($fullName);

    // Hack for special cases.
    switch ($className) {
      case 'Contribute':
        $attributes = $form->getVar('_attributes');
        $class = strtolower(basename(CRM_Utils_Array::value('action', $attributes)));
        break;

      case 'MembershipBlock':
        $class = 'membership';
        break;

      default:
        $class = strtolower($className);
        break;
    }

    $qfKey = $form->get('qfKey');
    $form->assign('qfKey', $qfKey);

    if (CRM_Utils_Array::arrayKeyExists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
    }

    if ($contribPageId) {
      $reset = CRM_Utils_Array::value('reset', $_GET) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        $tabs[$key]['link'] = CRM_Utils_System::url(
          "civicrm/admin/contribute/{$key}",
          "{$reset}action=update&snippet=4&id={$contribPageId}&qfKey={$qfKey}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }
      //get all section info.
      $contriPageInfo = CRM_Contribute_BAO_ContributionPage::getSectionInfo([$contribPageId]);

      foreach ($contriPageInfo[$contribPageId] as $section => $info) {
        if (!$info) {
          $tabs[$section]['valid'] = FALSE;
        }
      }
    }
    return $tabs;
  }

  /**
   * Reset the tab header configuration.
   *
   * Recalculates the tab definitions and updates the form state.
   *
   * @param CRM_Core_Form $form the form object
   *
   * @return void
   */
  public static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

  /**
   * Get the name of the currently selected tab.
   *
   * Iterates through the provided tabs to find the one marked as 'current'.
   *
   * @param array $tabs the array of tab definitions
   *
   * @return string the machine name of the current tab (e.g., 'settings')
   */
  public static function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ? $current : 'settings';
    return $current;
  }
}
