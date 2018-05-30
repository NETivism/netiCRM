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

    $template =& CRM_Core_Smarty::singleton();
    $one_year_label = $year_month_label = array();
    for ($month=1; $month <= 12 ; $month++) {
      $one_year_label[] = $month.'月';
      $year_month = date('Y').'-'.sprintf('%02d',$month);
      $year_month_label[] = $year_month;
    }

    $recur_year_sum = self::getDateForChart($year_month_label, $summary_contrib['ContribThisYear']['recur']);
    $not_recur_year_sum = self::getDateForChart($year_month_label, $summary_contrib['ContribThisYear']['not_recur']);


    $chart = array(
      'id' => 'chart-one-year',
      'selector' => '#chart-one-year',
      'type' => 'Line',
      'labels' => json_encode($one_year_label),
      'series' => json_encode(array($recur_year_sum, $not_recur_year_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackLines' => true
    );
    $template->assign('chart_this_year', $chart);

    $last_30_label = array();
    for ($i=30; $i > 0 ; $i--) {
      $date = date('Y-m-d', strtotime('-'.$i.'day'));
      $last_30_label[] = $date;
      $recur_index = array_search($date, $summary_contrib['Last30DaysContrib']['recur']['label']);
    }
    $recur_30_sum = self::getDateForChart($last_30_label, $summary_contrib['Last30DaysContrib']['recur']);
    $not_recur_30_sum = self::getDateForChart($last_30_label, $summary_contrib['Last30DaysContrib']['not_recur']);

    $chart = array(
      'id' => 'chart-30-sum',
      'selector' => '#chart-30-sum',
      'type' => 'Line',
      'labels' => json_encode($last_30_label),
      'series' => json_encode(array($recur_30_sum, $not_recur_30_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
    );
    $template->assign('chart_last_30_sum', $chart);

    $last_30_province_label =  array_unique(array_merge($summary_contrib['Last30DaysProvince']['recur']['label'], $summary_contrib['Last30DaysProvince']['not_recur']['label']));
    $last_30_province_recur_sum = self::getDateForChart($last_30_province_label, $summary_contrib['Last30DaysProvince']['recur']);
    $last_30_province_not_recur_sum = self::getDateForChart($last_30_province_label, $summary_contrib['Last30DaysProvince']['not_recur']);

    $chart = array(
      'id' => 'chart-30-province-sum',
      'selector' => '#chart-30-province-sum',
      'type' => 'Bar',
      'labels' => json_encode($last_30_province_label),
      'series' => json_encode(array($last_30_province_recur_sum, $last_30_province_not_recur_sum)),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackBars' => true
    );
    $template->assign('chart_last_30_province_sum', $chart);

    // First contribtion contact in last 30 days
    $last30day = date('Y-m-d', strtotime('-30day'));
    $last60day = date('Y-m-d', strtotime('-60day'));
    $sql = "  SELECT COUNT(c.id) ct, cc30.id, SUM(cc30.total_amount) sum FROM civicrm_contact c
      INNER JOIN ( SELECT id, contact_id, total_amount FROM civicrm_contribution WHERE receive_date >= '$last30day' AND is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) cc30 ON c.id = cc30.contact_id
      INNER JOIN ( SELECT id, contact_id FROM civicrm_contribution WHERE is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) cc_all ON c.id = cc_all.contact_id WHERE cc30.id = cc_all.id;";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $last_30_count = $dao->ct;
      $last_30_sum = $dao->sum;
    }
    $sql = "  SELECT COUNT(c.id) ct, cc60.id, SUM(cc60.total_amount) FROM civicrm_contact c
      INNER JOIN ( SELECT id, contact_id, total_amount FROM civicrm_contribution WHERE receive_date < '$last30day' AND receive_date >= '$last60day' AND is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) cc60 ON c.id = cc60.contact_id
      INNER JOIN ( SELECT id, contact_id FROM civicrm_contribution WHERE is_test = 0 AND contribution_status_id = 1 GROUP BY contact_id ) cc_all ON c.id = cc_all.contact_id WHERE cc60.id = cc_all.id;";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $last_60_count = $dao->ct;
    }

    $template->assign('last_30_count', $last_30_count);
    if($last_60_count > 0){
      $last_30_count_growth = ( $last_30_count / $last_60_count ) -1;
      $template->assign('last_30_count_growth', abs($last_30_count_growth) * 100);
      $template->assign('last_30_count_is_changed', $last_30_count_growth != 0);
      $template->assign('last_30_count_is_growth', $last_30_count_growth > 0);
    }

    $sql = "SELECT * FROM civicrm_contribution cc INNER JOIN civicrm_contact c ON cc.contact_id = c.id WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= '$last30day' ORDER BY cc.total_amount DESC LIMIT 1;";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $template->assign('last_30_max_amount', $dao->total_amount);
      $template->assign('last_30_max_id', $dao->id);
      $template->assign('last_30_max_contact_id', $dao->contact_id);
      $template->assign('last_30_max_display_name', $dao->display_name);
      $template->assign('last_30_max_receive_date', $dao->receive_date);
      // $template->assign('last_30_max_receive_date', $dao->receive_date);
    }

    $sql = "SELECT SUM(total_amount) FROM civicrm_contribution cc WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= '$last30day' ;";
    $last_30_sum = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "SELECT SUM(total_amount) FROM civicrm_contribution cc WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date < '$last30day' AND receive_date >= '$last60day'  ;";
    $last_60_sum = CRM_Core_DAO::singleValueQuery($sql);

    $template->assign('last_30_sum', $last_30_sum);
    if($last_60_sum > 0){
      $last_30_sum_growth = ( $last_30_sum / $last_60_sum ) -1;
      $template->assign('last_30_sum_growth', abs($last_30_sum_growth) * 100);
      $template->assign('last_30_sum_is_changed', $last_30_sum_growth != 0);
      $template->assign('last_30_sum_is_growth', $last_30_sum_growth > 0);
    }

    // block recur
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

  private static function getDateForChart($label_array, $summary_array, $type='sum'){
    $return_array = array();
    foreach ($label_array as $label) {
      $recur_index = array_search($label, $summary_array['label']);
      if((!empty($recur_index) || $recur_index === 0 ) && !empty($summary_array[$type][$recur_index])){
        $return_array[] = floatval($summary_array[$type][$recur_index]);
      }else{
        $return_array[] = 0;
      }
    }

    return $return_array;
  }
}

