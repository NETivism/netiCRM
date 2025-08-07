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
 * Page for displaying list of Payment-Instrument
 */
class CRM_Contribute_Page_DashBoard extends CRM_Core_Page {

  public $is_custom_date;
  public $end_date;
  public $start_date;
  public $last_start_date;
  public $last_end_date;
  public $duration_array;
  public $days;
  public $params_duration;
  public $params_last_duration;
  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    $title = ts('CiviContribute Dashboard');
    CRM_Utils_System::setTitle($title);

    $this->getDate();

    if(empty($this->is_custom_date)){
      // Check for admin permission to see if we should include the Manage Contribution Pages action link
      $isAdmin = 0;

      if (CRM_Core_Permission::check('administer CiviCRM')) {
        $isAdmin = 1;
      }
      $this->assign('isAdmin', $isAdmin);
    }
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
    $this->processDashBoard();

    return parent::run();
  }

  function getDate($start_date = NULL, $end_date = NULL){
    if(!empty($_GET['end_date']) || !empty($_GET['start_date'])){
      $this->is_custom_date = TRUE;
    }
    $end_date = $this->end_date = $end_date ? $end_date : ($_GET['end_date'] ? $_GET['end_date'] : date('Y-m-d'));
    $start_date = $this->start_date = $start_date ? $start_date : ($_GET['start_date'] ? $_GET['start_date'] : date('Y-m-d', strtotime('-29day')));

    $end_timestamp = strtotime($end_date);
    $start_timestamp = strtotime($start_date);
    if(empty($end_timestamp)){
       return CRM_Core_Error::statusBounce(ts('End date is not valid date format.'));
    }
    if(empty($start_timestamp)){
       return CRM_Core_Error::statusBounce(ts('Start date is not valid date format.'));
    }
    if($start_timestamp > $end_timestamp){
       return CRM_Core_Error::statusBounce(ts('Start date should not be after End date.'));
    }

    list($last_start_date, $last_end_date) = self::getLastDurationTime($start_date, $end_date);
    $this->last_start_date =  $last_start_date;
    $this->last_end_date = $last_end_date;

    $duration_array = [];
    $count_date_stamp = strtotime($this->start_date);
    while($count_date_stamp <= strtotime($this->end_date)){
      $duration_array[] = date('Y-m-d', $count_date_stamp);
      $count_date_stamp+=86400;
    }
    $this->duration_array = $duration_array;

    $this->days = ceil(($end_timestamp - $start_timestamp) / 86400) + 1;

    $this->params_duration = [
      1 => [$start_date . ' 00:00:00', 'String'],
      2 => [$end_date . ' 23:59:59', 'String'],
    ];
    $this->params_last_duration = [
      1 => [$last_start_date . ' 00:00:00', 'String'],
      2 => [$last_end_date . ' 23:59:59', 'String'],
    ];

    $this->assign('days', $this->days);
    $this->assign('start_date', $this->start_date);
    $this->assign('end_date', $this->end_date);
    $this->assign('last_start_date', $this->last_start_date);
    $this->assign('last_end_date', $this->last_end_date);

  }

  function processDashBoard(){
    if($_GET['debug']){
      $this->assign('debug', 1);
    }

    // refs #22871 add chart data
    $filter_time = ['start_date' => $this->start_date, 'end_date' => $this->end_date];
    $filter_all_year = ['start_date' => date('Y').'-01-01', 'end_date' => date('Y-m-d')];

    $filter_recur = ['contribution_recur_id' => TRUE];
    $filter_not_recur = ['contribution_recur_id' => FALSE];


    $summary_contrib['LastDurationContrib']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,['interval' => 'DAY'], ['contribution' => $filter_time+$filter_recur]);
    $summary_contrib['LastDurationContrib']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,['interval' => 'DAY'], ['contribution' => $filter_time+$filter_not_recur]);

    $summary_contrib['LastDurationProvince']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE, ['contribution' => 1, 'seperate_other' => 1], ['contribution' => $filter_time+$filter_recur]);
    $summary_contrib['LastDurationProvince']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE,['contribution' => 1, 'seperate_other' => 1], ['contribution' => $filter_time+$filter_not_recur]);

    if (empty($this->is_custom_date)) {
      $summary_contrib['ContribThisYear']['recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,['interval' => 'MONTH'], ['contribution' => $filter_all_year+$filter_recur]);
      $summary_contrib['ContribThisYear']['not_recur'] = CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::CONTRIBUTION_RECEIVE_DATE,['interval' => 'MONTH'], ['contribution' => $filter_all_year+$filter_not_recur]);
      $one_year_label = $year_month_label = [];
      for ($month=1; $month <= 12 ; $month++) {
        $one_year_label[] = $month.'月';
        $year_month = date('Y').'-'.sprintf('%02d',$month);
        $year_month_label[] = $year_month;
      }

      $contrib_this_year = $summary_contrib['ContribThisYear'];
      $this->assign('this_year_sum_non_recur', $contrib_this_year['not_recur']['sum'] ? array_sum($contrib_this_year['not_recur']['sum']) : 0);
      $this->assign('this_year_sum_recur', $contrib_this_year['recur']['sum'] ? array_sum($contrib_this_year['recur']['sum']) : 0);
      $this->assign('this_year_count_non_recur', $contrib_this_year['not_recur']['count'] ? array_sum($contrib_this_year['not_recur']['count']) : 0);
      $this->assign('this_year_count_recur', $contrib_this_year['recur']['count'] ? array_sum($contrib_this_year['recur']['count']) : 0);
      $this->assign('this_year_people_non_recur', $contrib_this_year['not_recur']['people'] ? array_sum($contrib_this_year['not_recur']['people']) : 0);
      $this->assign('this_year_people_recur', $contrib_this_year['recur']['people'] ? array_sum($contrib_this_year['recur']['people']) : 0);
      $recur_year_sum = self::getDataForChart($year_month_label, $contrib_this_year['recur']);
      $not_recur_year_sum = self::getDataForChart($year_month_label, $contrib_this_year['not_recur']);
      for ($i=1; $i < 12; $i++) {
        if($i <= date('m')){
          $recur_year_sum[$i] += $recur_year_sum[$i-1];
          $not_recur_year_sum[$i] += $not_recur_year_sum[$i-1];
        }else{
          unset($recur_year_sum[$i]);
          unset($not_recur_year_sum[$i]);
        }
      }

      $chart = [
        'id' => 'chart-one-year',
        'selector' => '#chart-one-year',
        'type' => 'Line',
        'labels' => json_encode($one_year_label),
        'series' => json_encode([$recur_year_sum, $not_recur_year_sum]),
        'seriesUnit' => '$ ',
        'seriesUnitPosition'=> 'prefix',
        'withToolTip' => true,
        'withVerticalHint' => true,
        'legends' => json_encode([ts("Recurring Contribution"), ts("Non-Recurring Contribution")]),
        'stackLines' => true,
      ];
      $this->assign('chart_this_year', $chart);
    }

    $recur_duration_sum = self::getDataForChart($this->duration_array, $summary_contrib['LastDurationContrib']['recur']);
    $not_recur_duration_sum = self::getDataForChart($this->duration_array, $summary_contrib['LastDurationContrib']['not_recur']);

    $chart = [
      'id' => 'chart-duration-sum',
      'selector' => '#chart-duration-sum',
      'type' => 'Line',
      'labels' => json_encode($this->duration_array),
      'series' => json_encode([$recur_duration_sum, $not_recur_duration_sum]),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withVerticalHint' => true,
      'legends' => json_encode([ts("Recurring Contribution"), ts("Non-Recurring Contribution")]),
      'withToolTip' => true,
      'autoDateLabel' => true,
      'withLegend' => true,
    ];
    $this->assign('chart_duration_sum', $chart);


    $referrerTypes = CRM_Core_PseudoConstant::referrerTypes();
    $referrerTypesIdx = array_values($referrerTypes);
    $referrerTypesAvailable = [];
    $durationTrack = [];
    foreach($this->duration_array as $idx => $d) {
      foreach($referrerTypesIdx as $k => $type) {
        $durationTrack[$k][$idx] = 0;
       }
     }
    $params = [
      'pageType' => 'civicrm_contribution_page',
      'visitDateStart' => $this->params_duration[1][0],
      'visitDateEnd' => $this->params_duration[2][0],
    ];
    $selector = new CRM_Track_Selector_Track($params);
    $dao = $selector->getQuery("referrer_type, count(id) as `count`, DATE_FORMAT(visit_date,'%Y-%m-%d') visit_day", 'GROUP BY visit_day, referrer_type');
    while($dao->fetch()){
      $dateIdx = array_search($dao->visit_day, $this->duration_array);
      if(empty($dao->referrer_type)){
        continue;
      }
      $referrerTypeIdx = array_search($referrerTypes[$dao->referrer_type], $referrerTypesIdx);
      $referrerTypesAvailable[$referrerTypeIdx] = 1;
      $durationTrack[$referrerTypeIdx][$dateIdx] = (int)$dao->count;
    }
    $durationTrack = array_intersect_key($durationTrack, $referrerTypesAvailable);
    $referrerTypesIdx = array_intersect_key($referrerTypesIdx, $referrerTypesAvailable);
    $durationTrack = array_values($durationTrack);
    $referrerTypesIdx = array_values($referrerTypesIdx);

    $chart = [
      'id' => 'chart-duration-track',
      'selector' => '#chart-duration-track',
      'type' => 'Bar',
      'labels' => json_encode($this->duration_array),
      'series' => json_encode($durationTrack),
      'seriesUnit' => ts("People"),
      'withToolTip' => true,
      'withVerticalHint' => true,
      'legends' => json_encode(array_values($referrerTypesIdx)),
      'autoDateLabel' => true,
      'stackBars' => true,
      'withLegend' => true,
      'onlyIntegerY' => true,
    ];
    $this->assign('chart_duration_track', $chart);

    $duration_province_recur_label = empty($summary_contrib['LastDurationProvince']['recur']) ? [] : $summary_contrib['LastDurationProvince']['recur']['label'];
    $duration_province_not_recur_label = empty($summary_contrib['LastDurationProvince']['not_recur']) ? [] : $summary_contrib['LastDurationProvince']['not_recur']['label'];
    $duration_province_label =  array_unique(array_merge($duration_province_recur_label, $duration_province_not_recur_label));
    $duration_province_recur_sum = self::getDataForChart($duration_province_label, $summary_contrib['LastDurationProvince']['recur']);
    $duration_province_not_recur_sum = self::getDataForChart($duration_province_label, $summary_contrib['LastDurationProvince']['not_recur']);

    $chart = [
      'id' => 'chart-duration-province-sum',
      'selector' => '#chart-duration-province-sum',
      'type' => 'Bar',
      'legends' => json_encode([ts("Recurring Contribution"), ts("Non-Recurring Contribution")]),
      'labels' => json_encode(array_values($duration_province_label)), // array_values to let key to integer not string
      'series' => json_encode([$duration_province_recur_sum, $duration_province_not_recur_sum]),
      'seriesUnit' => '$ ',
      'seriesUnitPosition'=> 'prefix',
      'withToolTip' => true,
      'stackBars' => true,
      'withLegend' => true,
    ];
    $this->assign('chart_duration_province_sum', $chart);

    // First contribtion contact in last 30 days, Used sql by firstTimeDonor.php
    $sql = "
SELECT COUNT(fst_donor.id) as ct, SUM(fst_donor.amount) as sum FROM
(
  SELECT
    contact.id as id,
    ROUND(c.total_amount,0) as amount,
    c.receive_date,
    c.is_test
  FROM civicrm_contribution c
  INNER JOIN (
    SELECT civicrm_contact.id, civicrm_contact.sort_name FROM civicrm_contact
      INNER JOIN civicrm_contribution cc ON cc.contact_id = civicrm_contact.id
      LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = cc.id
      LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = cc.id
    WHERE
      cc.is_test = 0 AND pp.id IS NULL AND mp.id IS NULL AND civicrm_contact.is_deleted = 0 AND cc.contribution_status_id = 1   AND cc.receive_date <= %2 AND cc.receive_date IS NOT NULL
    GROUP BY civicrm_contact.id
    HAVING count(civicrm_contact.id) = 1
  ) contact ON contact.id = c.contact_id
) fst_donor WHERE fst_donor.receive_date >= %1 AND fst_donor.receive_date <= %2 GROUP BY fst_donor.is_test";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    if($dao->fetch()){
      $duration_count = $dao->ct;
      $duration_sum = $dao->sum;
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_last_duration);
    if($dao->fetch()){
      $last_duration_count = $dao->ct;
    }

    $this->assign('duration_count', $duration_count);
    if($last_duration_count > 0){
      $duration_count_growth = ( $duration_count / $last_duration_count ) -1;
      $this->assign('duration_count_growth', number_format($duration_count_growth * 100, 2));
    }

    $sql = "SELECT cc.id id, cc.total_amount total_amount, c.id contact_id, c.display_name display_name, DATE_FORMAT(receive_date,'%Y-%m-%d') receive_date FROM civicrm_contribution cc INNER JOIN civicrm_contact c ON cc.contact_id = c.id WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= %1 AND receive_date <= %2 ORDER BY cc.total_amount DESC LIMIT 1;";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    if($dao->fetch()){
      $this->assign('duration_max_amount', $dao->total_amount);
      $this->assign('duration_max_id', $dao->id);
      $this->assign('duration_max_contact_id', $dao->contact_id);
      $this->assign('duration_max_display_name', $dao->display_name);
      $this->assign('duration_max_receive_date', $dao->receive_date);
      // $this->assign('duration_max_receive_date', $dao->receive_date);
    }

    $sql = "SELECT SUM(total_amount) FROM civicrm_contribution cc INNER JOIN civicrm_contact c ON cc.contact_id = c.id  WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND receive_date >= %1 AND receive_date <= %2 AND c.is_deleted = 0;";
    $duration_sum = CRM_Core_DAO::singleValueQuery($sql, $this->params_duration);

    $last_duration_sum = CRM_Core_DAO::singleValueQuery($sql, $this->params_last_duration);

    $this->assign('duration_sum', $duration_sum);
    if($last_duration_sum > 0){
      $duration_sum_growth = ( $duration_sum / $last_duration_sum ) -1;
      $this->assign('duration_sum_growth', number_format($duration_sum_growth * 100, 2));
    }

    if(empty($this->is_custom_date)){
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
        $this->assign('summaryRecur', $summary);
        $this->assign('summaryTime', date('n/j H:i', $summaryTime));
        $this->assign('frequencyUnit', 'month');
        $chart = CRM_Contribute_BAO_ContributionRecur::chartEstimateMonthly(12);
        $chart['withToolTip'] = true;
        $chart['seriesUnitPosition'] = 'prefix';
        $chart['seriesUnit'] = '$';
        $this->assign('chartRecur', $chart);
      }
    }

    // contribution_page status
    $sql = "SELECT cp.id id, title, goal_amount, SUM(c.total_amount) sum, COUNT(c.id) count FROM civicrm_contribution_page cp INNER JOIN civicrm_contribution c ON cp.id = c.contribution_page_id WHERE c.receive_date >= %1 AND receive_date <= %2 AND c.contribution_status_id = 1 AND c.is_test = 0 GROUP BY cp.id ORDER BY count DESC LIMIT 3";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $i = 0;
    while($dao->fetch()){
      $stat = self::getContributionPageStatistics($dao->id, $this->start_date, $this->end_date);
      if(!empty($stat)){
        $cp_stat[$dao->id] = $stat;
      }
      $i++;
    }
    $this->assign('contribution_page_stat', $cp_stat);
    if ($dao->N > 0) {
      $this->assign('page_col_n', (12 / $dao->N));
    }
    else {
      $this->assign('page_col_n', (12 / 3));
    }


    // last 30 days count
    $instrument_option_group_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

    $sql = "SELECT cc.id id, c.id contact_id, cc.receive_date receive_date, cc.total_amount amount, c.display_name name, cc.payment_instrument_id instrument_id FROM civicrm_contribution cc 
      INNER JOIN civicrm_contact c ON cc.contact_id = c.id
      WHERE cc.contribution_status_id = 1 AND cc.is_test = 0 AND cc.receive_date >= %1 AND cc.receive_date <= %2 AND cc.contribution_recur_id IS NULL ORDER BY receive_date DESC LIMIT 5 ";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $non_recur_contributions = [];
    while($dao->fetch()){
      $contribution = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'name' => $dao->name, 
        'date' => date('Y-m-d', strtotime($dao->receive_date)),
        'amount' => $dao->amount,
      ];
      if(!empty($dao->instrument_id)){
        $sql = "SELECT ov.label FROM civicrm_option_value ov WHERE ov.value = %1 AND ov.option_group_id = %2";
        $params_ov = [
          1 => [$dao->instrument_id, 'Integer'],
          2 => [$instrument_option_group_id, 'Integer'],
        ];
        $contribution['instrument'] = CRM_Core_DAO::singleValueQuery($sql, $params_ov);
      }
      $non_recur_contributions[] = $contribution;
    }
    $this->assign('non_recur_contributions', $non_recur_contributions);

    $sql = "SELECT cc.id id, c.id contact_id, cr.start_date start_date, cc.total_amount amount, c.display_name name, cr.installments installments FROM civicrm_contribution cc 
      INNER JOIN civicrm_contact c ON cc.contact_id = c.id
      INNER JOIN civicrm_contribution_recur cr ON cr.id = cc.contribution_recur_id
      WHERE cc.payment_processor_id IS NOT NULL AND cc.contribution_status_id = 1 AND cc.is_test = 0 AND start_date >= %1 AND start_date <= %2 AND cc.contribution_recur_id IS NOT NULL GROUP BY cr.id ORDER BY start_date DESC LIMIT 5 ";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params_duration);
    $recur_contributions = [];
    while($dao->fetch()){
      $recur = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'name' => $dao->name, 
        'date' => date('Y-m-d', strtotime($dao->start_date)),
        'amount' => $dao->amount,
      ];
      if($dao->installments){
        $recur['installments'] = $dao->installments;
      }else{
        $recur['installments'] = ts("no limit");
      }
      $recur_contributions[] = $recur;
    }
    $this->assign('recur_contributions', $recur_contributions);

    $params_last_month = [
      1 => [date('Y-m-d', strtotime("-1 month")), 'String'],
    ];
    $sql = "SELECT * FROM (SELECT c.id contact_id, c.display_name name, cr.amount amount, cr.end_date end_date, cr.id recur_id, c.id contribution_id, cr.installments installments, COUNT(cc.id) cc_total, MAX(cc.receive_date) last_receive_date
      FROM civicrm_contribution cc 
      INNER JOIN civicrm_contact c ON cc.contact_id = c.id 
      INNER JOIN civicrm_contribution_recur cr ON cc.contribution_recur_id = cr.id
      WHERE cr.contribution_status_id = 5
      GROUP BY cr.id ) cr WHERE (cc_total + 1) = installments AND last_receive_date > %1 
      ORDER BY last_receive_date ASC LIMIT 5";
    $dao = CRM_Core_DAO::executeQuery($sql, $params_last_month);
    $due_recur = [];
    while($dao->fetch()){
      $recur = [];
      $recur['contact_id'] = $dao->contact_id;
      $recur['name'] = $dao->name;
      $recur['amount'] = $dao->amount;
      $recur['recur_id'] = $dao->recur_id;
      $recur['end_date'] = empty($dao->end_date) ? '' : date('Y-m-d', strtotime($dao->end_date));
      $due_recur[] = $recur;
    }
    $this->assign('due_recur', $due_recur);

  }

  static function getLastDurationTime($start_date, $end_date) {
    $last_end_date = date('Y-m-d', strtotime($start_date) - 86400);
    $duration_stamp = strtotime($end_date) - strtotime($start_date);
    $last_start_date = date('Y-m-d', strtotime($last_end_date) - $duration_stamp);
    return [$last_start_date, $last_end_date];
  }

  public static function getContributionPageStatistics($pid, $start_date = NULL, $end_date = NULL) {
    $pid = (int)$pid;
    $page = $track = $achievement = $duration = [];

    // contribution page profile
    CRM_Contribute_BAO_ContributionPage::setValues($pid, $page);
    $sql = "SELECT COUNT(c.id) as `count`, SUM(c.total_amount) as `total_amount` FROM civicrm_contribution c WHERE c.contribution_page_id = %1 AND c.contribution_status_id = 1 AND c.is_test = 0";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$pid, 'Integer']]);
    if($dao->fetch()){
      $page['total_count'] = $dao->count;
      $page['total_amount'] = $dao->total_amount;
    }

    // track
    $track = CRM_Core_BAO_Track::referrerTypeByPage('civicrm_contribution_page', $pid, $start_date, $end_date);
    if (count($track) > 4) {
      $other = [
        'name' => 'other',
        'label' => ts('Other'),
        'count' => 0,
        'percent' => 0,
      ];
      $i = 0;
      foreach($track as $referType => $t) {
        if ($i > 3 && $referType != 'other') {
          $other['count'] += $t['count'];
          $other['percent'] += $t['percent'];
          $other['count_goal'] += $t['count_goal'];
          $other['percent_goal'] += $t['percent_goal'];
          unset($track[$referType]);
        }
        $i++;
      }
      $track['other'] = $other;
    }

    // achievement
    $achievement = CRM_Contribute_BAO_ContributionPage::goalAchieved($pid);

    // new contribution with last time interval
    if(!empty($start_date)){
      if(empty($end_date)){
        $end_date = date('Y-m-d');
      }
      $sql = "SELECT COUNT(id) FROM civicrm_contribution c WHERE contribution_page_id = %1 AND receive_date >= %2 AND receive_date <= %3 AND contribution_status_id = 1 AND c.is_test = 0 ";
      $params = [
        1 => [$pid, 'Integer'],
        2 => [$start_date . ' 00:00:00' , 'String'],
        3 => [$end_date . ' 23:59:59' , 'String'],
      ];
      $duration_count = CRM_Core_DAO::singleValueQuery($sql, $params);
      $duration['count'] = $duration_count;

      list($last_start_date, $last_end_date) = self::getLastDurationTime($start_date, $end_date);
      $sql = "SELECT COUNT(id) FROM civicrm_contribution c WHERE contribution_page_id = %1 AND receive_date >= %2 AND receive_date <= %3 AND contribution_status_id = 1 AND c.is_test = 0 ";
      $params = [
        1 => [$pid, 'Integer'],
        2 => [$last_start_date . ' 00:00:00' , 'String'],
        3 => [$last_end_date . ' 23:59:59' , 'String'],
      ];
      $last_duration_count = CRM_Core_DAO::singleValueQuery($sql, $params);

      if($last_duration_count > 1){
        $duration_count_growth = ( $duration_count / $last_duration_count ) - 1;
        $duration['growth'] = number_format($duration_count_growth * 100,2 );
      }
    }

    $return = [
      'page' => $page,
      'track' => $track,
      'achievement' => $achievement,
      'duration' => $duration,
    ];
    return $return;
  }

  private static function getDataForChart($label_array, $summary_array, $type='sum') {
    $return_array = [];
    if (!is_array($summary_array['label'])) {
      $summary_array['label'] = [];
    }
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

