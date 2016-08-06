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
require_once 'CRM/Core/Form.php';
class CRM_Contribute_Form_ContributionCharts extends CRM_Core_Form {

  /**
   *  Year of chart
   *
   * @var int
   */
  protected $_year = NULL;

  /**
   *  The type of chart
   *
   * @var string
   */
  protected $_chartType = NULL;

  function preProcess() {
    $this->_year = CRM_Utils_Request::retrieve('year', 'Int', $this);
    $this->postProcess();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    //take available years from database to show in drop down
    $currentYear = date('Y');
    $years = array();
    if (!empty($this->_years)) {
      if (!array_key_exists($currentYear, $this->_years)) {
        $this->_years[$currentYear] = $currentYear;
        krsort($this->_years);
      }
      foreach ($this->_years as $k => $v) {
        $years[$k] = $k;
      }
    }

    $this->addElement('select', 'select_year', ts('Select Year (for monthly breakdown)'), $years, array('onchange' => "getChart();"));
    $this->setDefaults(array('select_year' => ($this->_year) ? $this->_year : $currentYear));
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_year) {
      $selectedYear = $this->_year;
    }
    else{
      $selectedYear = date('Y');
    }
    $group = 'Contribution Chart';
    $path = get_class($this);
    $components = CRM_Core_Component::getEnabledComponents();

    $chartYears = CRM_Core_BAO_Cache::getItem($group, $path.'_chartYearsList', $components['CiviContribute']->componentID);
    $chartData = CRM_Core_BAO_Cache::getItem($group, $path.'_chartData'.$selectedYear, $components['CiviContribute']->componentID);
    $chartTime = CRM_Core_BAO_Cache::getItem($group, $path.'_chartTime'.$selectedYear, $components['CiviContribute']->componentID);
    $abbrMonthNames = array();
    for ($i = 0; $i < 12; $i++) {
      $abbrMonthNames[$i] = strftime('%b', mktime(0, 0, 0, $i+1, 10, 1970));
    }

    if(empty($chartData) || time() - $chartTime > 86400) {
      $chartInfoYearly = CRM_Contribute_BAO_Contribution_Utils::contributionChartYearly();
      $this->_years = $chartInfoYearly['By Year'];

      //take contribution information monthly
      $chartInfoMonthly = CRM_Contribute_BAO_Contribution_Utils::contributionChartMonthly($selectedYear);
      $chartData = $abbrMonthNames = array();
      if (is_array($chartInfoMonthly)) {
        foreach ($chartInfoMonthly['By Month'] as $value) {
          $chartData[] = $value;
        }
      }
      $chartYears = CRM_Core_BAO_Cache::setItem($this->_years, $group, $path.'_chartYearsList', $components['CiviContribute']->componentID);
      $chartData = CRM_Core_BAO_Cache::setItem($chartData, $group, $path.'_chartData'.$selectedYear, $components['CiviContribute']->componentID);
      $chartTime = CRM_Core_BAO_Cache::setItem(time(), $group, $path.'_chartTime'.$selectedYear, $components['CiviContribute']->componentID);
    }
    else{
      $this->_years = $chartYears;
    }
    if(!empty($chartData)){
      $chart = array(
        'type' => 'bar',
        'labels' => json_encode($abbrMonthNames),
        'series' => json_encode(array(array_values($chartData))),
      );
      $this->assign('chart', $chart);
      $this->assign('hasChart', TRUE);
    }
  }
}

