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

require_once 'CRM/Core/Page.php';

/**
 * Page for displaying list of Payment-Instrument
 */
class CRM_Contribute_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviContribute'));

    $status = array('Valid', 'Cancelled');
    $prefixes = array('start', 'month', 'year');
    $startDate = NULL;
    $startToDate = $monthToDate = $yearToDate = array();

    //get contribution dates.
    require_once 'CRM/Contribute/BAO/Contribution.php';
    $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
    foreach (array('now', 'yearDate', 'monthDate') as $date) {
      $$date = $dates[$date];
    }
    $yearNow = $yearDate + 10000;
    foreach ($prefixes as $prefix) {
      $aName = $prefix . 'ToDate';
      $dName = $prefix . 'Date';

      if ($prefix == 'year') {
        $now = $yearNow;
      }

      foreach ($status as $s) {
        ${$aName}[$s] = CRM_Contribute_BAO_Contribution::getTotalAmountAndCount($s, $$dName, $now);
        ${$aName}[$s]['url'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&status=1&start={$$dName}&end=$now&test=0"
        );
      }

      $this->assign($aName, $$aName);
    }

    //for contribution tabular View
    $buildTabularView = CRM_Utils_Array::value('showtable', $_GET, FALSE);
    $this->assign('buildTabularView', $buildTabularView);
    if ($buildTabularView) {
      return;
    }

    // Check for admin permission to see if we should include the Manage Contribution Pages action link
    $isAdmin = 0;
    require_once 'CRM/Core/Permission.php';
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $isAdmin = 1;
    }
    $this->assign('isAdmin', $isAdmin);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    // block contribution
    $this->preProcess();

    // refs #22871 add chart data
    $summary_contrib = array();
    if($_GET['start_date']){
      $cc_filter['start_date'] = $_GET['start_date'];
    }
    if($_GET['end_date']){
      $cc_filter['end_date'] = $_GET['end_date'];
    }
    $filter_time = !empty($cc_filter) ? $cc_filter : array('start_date' => date('Y').'-01-01', 'end_date' => date('Y-m-d'));

    $filter_recur = array('contribution_recur_id' => TRUE);
    $filter_not_recur = array('contribution_recur_id' => FALSE);
    $summary_contrib['ContribThisYear']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'MONTH'), array('contribution' => $filter_time+$filter_recur));
    $summary_contrib['ContribThisYear']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'MONTH'), array('contribution' => $filter_time+$filter_not_recur));

    $filter_time = array('start_date' => date('Y-m-d', strtotime('-30day')), 'end_date' => date('Y-m-d'));
    $summary_contrib['Last30DaysContrib']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'DAY'), array('contribution' => $filter_time+$filter_recur));
    $summary_contrib['Last30DaysContrib']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,array('interval' => 'DAY'), array('contribution' => $filter_time+$filter_not_recur));

    $summary_contrib['Last30DaysProvince']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE, array('contribution' => 1, 'seperate_other' => 1), array('contribution' => $filter_time+$filter_recur));
    $summary_contrib['Last30DaysProvince']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE,array('contribution' => 1, 'seperate_other' => 1), array('contribution' => $filter_time+$filter_not_recur));
    if($_GET['debug']){
      dpm($summary_contrib);
    }

    // block recur
    $template =& CRM_Core_Smarty::singleton();
    $components = CRM_Core_Component::getEnabledComponents();
    $path = get_class($this);
    $summary = CRM_Core_BAO_Cache::getItem('Contribution Chart', $path.'_currentRunningSummary', $components['CiviContribute']->componentID);
    $summaryTime = CRM_Core_BAO_Cache::getItem('Contribution Chart', $path.'_currentRunningSummary_time', $components['CiviContribute']->componentID);
    if(empty($summary) || time() - $summaryTime > 86400 || $_GET['update']) {
      $summary = CRM_Contribute_BAO_ContributionRecur::currentRunningSummary();
      CRM_Core_BAO_Cache::setItem($summary, 'Contribution Chart', $path.'_currentRunningSummary', $components['CiviContribute']->componentID);
      $summaryTime = CRM_REQUEST_TIME;
      CRM_Core_BAO_Cache::setItem($summaryTime, 'Contribution Chart', $path.'_currentRunningSummary_time', $components['CiviContribute']->componentID);
      if ($_GET['update']) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute', 'reset=1'));
      }
    }
    if(!empty($summary)){
      $template->assign('summaryRecur', $summary);
      $template->assign('summaryTime', date('n/j H:i', $summaryTime));
      $template->assign('frequencyUnit', 'month');
      $chart = CRM_Contribute_BAO_ContributionRecur::chartEstimateMonthly(12);
      $chart['withToolTip'] = true;
      $chart['seriesUnitPosition'] = 'prefix';
      $chart['seriesUnit'] = '$';
      $template->assign('chartRecur', $chart);
    }

    // block last contribution
    $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_Search',
      ts('Contributions'), NULL
    );
    $controller->setEmbedded(TRUE);

    $controller->set('limit', 10);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    $controller->process();
    $controller->run();
    $chartForm = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionCharts',
      ts('Contributions Charts'), NULL
    );

    $chartForm->setEmbedded(TRUE);
    $chartForm->process();
    $chartForm->run();

    return parent::run();
  }
}

