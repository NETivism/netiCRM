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
 * Class to retrieve information about a contribution page
 */

require_once 'CRM/Contribute/DAO/Widget.php';
class CRM_Contribute_BAO_Widget extends CRM_Contribute_DAO_Widget {

  /**
   * Gets all campaign related data and returns it as a std class.
   *
   * @param int $contributionPageID
   * @param string $widgetID
   *
   * @return stdClass
   */
  public function getContributionPageData($contributionPageID, $widgetID) {
    $config = CRM_Core_Config::singleton();

    $data = array();
    $data['currencySymbol'] = $config->defaultCurrencySymbol;

    if (empty($contributionPageID) ||
      CRM_Utils_Type::validate($contributionPageID, 'Integer') == NULL
    ) {
      $data['is_error'] = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not set");
      return $data;
    }

    require_once 'CRM/Contribute/DAO/Widget.php';
    $widget = new CRM_Contribute_DAO_Widget();
    $widget->contribution_page_id = $contributionPageID;
    if (!$widget->find(TRUE)) {
      $data['is_error'] = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not found");
      return $data;
    }

    $data['is_error'] = FALSE;
    if (!$widget->is_active) {
      $data['is_active'] = FALSE;
    }

    $data['is_active'] = TRUE;
    $data['title'] = $widget->title;
    $data['logo'] = $widget->url_logo;
    $data['button_title'] = $widget->button_title;
    $data['about'] = $widget->about;
    $data['num_donors'] = $data['money_raised'] = 0;

    // prepare all contribution page variable
    $page = array();
    CRM_Contribute_BAO_ContributionPage::setValues($contributionPageId, $page);

    // total donors
    $query = "SELECT count( id ) as count FROM   civicrm_contribution  WHERE is_test = 0 AND contribution_status_id = 1 AND contribution_page_id = %1";
    $data['num_donors'] = CRM_Core_DAO::singleValueQuery($query, array(1 => array($contributionPageID, 'Integer')));

    // goal
    $achievement = CRM_Contribute_BAO_ContributionPage::goalAchieved($contributionPageID);

    $data['campaign_start'] = '';
    $startDate = NULL;
    if ($page['id']) {
      $data['money_target'] = $achievement['goal'];

      // conditions that needs to be handled
      // 1. Campaign is not active - no text
      // 2. Campaign start date greater than today - show start date
      // 3. Campaign end date is set and greater than today - show end date
      // 4. If no start and end date or no end date and start date greater than today, then it's ongoing
      if ($page['is_active']) {
        $data['campaign_start'] = ts('Campaign is ongoing');

        // check for time being between start and end date
        $now = time();
        if ($page['start_date']) {
          $startDate = CRM_Utils_Date::unixTime($page['start_date']);
          if ($startDate && $startDate >= $now) {
            $data['is_active'] = FALSE;
            $data['campaign_start'] = ts('Campaign starts on %1', array(1 => CRM_Utils_Date::customFormat($page['start_date'], $config->dateformatFull)));
          }
        }

        if ($page['end_date']) {
          $endDate = CRM_Utils_Date::unixTime($page['end_date']);
          if ($endDate && $endDate < $now) {
            $data['is_active'] = FALSE;
          }
          elseif ($startDate >= $now) {
            $data['campaign_start'] = ts('Campaign starts on %1', array(1 => CRM_Utils_Date::customFormat($page['start_date'], $config->dateformatFull)));
          }
          else {
            $data['campaign_start'] = ts('Campaign ends on %1', array(1 => CRM_Utils_Date::customFormat($page['end_date'], $config->dateformatFull)));
          }
        }
      }
      else {
        $data['is_active'] = FALSE;
      }
    }
    else {
      $data['is_active'] = FALSE;
    }

    require_once 'CRM/Utils/Money.php';
    $data['money_raised_percentage'] = 0;
    if ($achievement['goal'] > 0) {
      $data['goal_type'] = $achievement['type'];
      $data['money_target'] = $achievement['goal'];
      $data['money_raised_percentage'] = $achievement['percent']."%";
      if ($achievement['type'] == 'amount') {
        $data['money_target_display'] = ts('Goal Amount') . ': ' . CRM_Utils_Money::format($achievement['goal']);
        $data['money_raised'] = ts('%1 achieved', array(1 => CRM_Utils_Money::format($achievement['current'])));
      }
      elseif ($achievement['type'] == 'recurring') {
        $data['money_target_display'] = ts('Goal Subscription') . ': ' . $achievement['goal'] ." ". ts("People");
        $data['money_raised'] = ts('%1 achieved', array(1 => $achievement['current'])) . " ". ts("People");
      }
    }
    else {
      $data['money_raised'] = ts('Raised %1', array(1 => CRM_Utils_Money::format($data['money_raised'])));
    }

    $data['money_low'] = 0;
    $data['num_donors'] = ts("Donation Count") . ": " .$data['num_donors'];
    $data['num_donors_include_pending'] = $data['num_donors_include_pending'] . " " . ts('Donors');
    $data['home_url'] = "<a href='{$config->userFrameworkBaseURL}' class='crm-home-url' style='color:" . $widget->color_homepage_link . "'>" . ts('Learn more.') . "</a>";

    // if is_active is false, show this link and hide the contribute button
    $data['homepage_link'] = $widget->url_homepage;

    $data['colors'] = array();

    $data['colors']["title"] = $widget->color_title;
    $data['colors']["button"] = $widget->color_button;
    $data['colors']["bar"] = $widget->color_bar;
    $data['colors']["main_text"] = $widget->color_main_text;
    $data['colors']["main"] = $widget->color_main;
    $data['colors']["main_bg"] = $widget->color_main_bg;
    $data['colors']["bg"] = $widget->color_bg;
    $data['colors']["about_link"] = $widget->color_about_link;

    require_once 'CRM/Core/Error.php';
    return $data;
  }
}

