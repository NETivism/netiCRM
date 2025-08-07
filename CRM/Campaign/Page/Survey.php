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
 * Page for displaying Surveys
 */
class CRM_Campaign_Page_Survey extends CRM_Core_Page {

  private static $_actionLinks; function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {

      self::$_actionLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/survey/add',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Update Survey'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Campaign_BAO_Survey' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Survey'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Campaign_BAO_Survey' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Survey'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/survey/add',
          'qs' => 'action=delete&id=%%id%%&reset=1',
          'title' => ts('Delete Survey'),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  function browse() {


    $surveys = CRM_Campaign_BAO_Survey::getSurvey(TRUE);

    if (!empty($surveys)) {


      $surveyType = CRM_Campaign_BAO_Survey::getSurveyActivityType();
      $campaigns = CRM_Campaign_BAO_Campaign::getAllCampaign();
      $activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, FALSE, 'name');
      foreach ($surveys as $sid => $survey) {
        $surveys[$sid]['campaign_id'] = $campaigns[$survey['campaign_id']];
        $surveys[$sid]['activity_type_id'] = $surveyType[$survey['activity_type_id']];
        $surveys[$sid]['release_frequency'] = $survey['release_frequency_interval'] . ' ' . $survey['release_frequency_unit'];

        $action = array_sum(array_keys($this->actionLinks()));
        if ($survey['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        $surveys[$sid]['action'] = CRM_Core_Action::formLink($this->actionLinks(), $action, ['id' => $sid]);
      }
    }

    $this->assign('surveys', $surveys);
    $this->assign('addSurveyUrl', CRM_Utils_System::url('civicrm/survey/add', 'reset=1&action=add'));
  }

  function run() {
    if (!CRM_Core_Permission::check('administer CiviCampaign')) {
      CRM_Utils_System::permissionDenied();
    }

    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );
    $this->assign('action', $action);
    $this->browse();

    parent::run();
  }
}

