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
require_once 'CRM/Report/Utils/Report.php';

/**
 * Page for invoking report templates
 */
class CRM_Report_Page_Summary extends CRM_Core_Page {

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    // $allData = array();
    // $allData['電子報統計'] = CRM_Report_BAO_Summary::getMailingData();
    // $allData['參加者統計'] = CRM_Report_BAO_Summary::getActivitiesData();
    // $allData['捐款統計'] = CRM_Report_BAO_Summary::getContributionData();
    // $allData['參加者轉捐款統計'] = CRM_Report_BAO_Summary::getActToConData();
    // $allData['捐款轉參加者統計'] = CRM_Report_BAO_Summary::getConToActData();
    // $allData['電子報轉參加者統計'] = CRM_Report_BAO_Summary::getMailToActData();
    // $allData['電子報轉捐款統計'] = CRM_Report_BAO_Summary::getMailToConData();
    // $allData['開信後多久報名活動'] = CRM_Report_BAO_Summary::getActAfterMailData();
    // $allData['開信後多久捐款'] = CRM_Report_BAO_Summary::getConAfterMailData();
    // $allData['成為聯絡人是透過報名還是捐款'] = CRM_Report_BAO_Summary::getContactSourceIsActCon();
    // dpm($allData);

    $contribute = CRM_Report_BAO_Summary::getContributionData();

    $instrumentsSum = self::getArrayLabelAndValue($contribute['instruments'], 'sum');

    $chartInsSum = array(
      'id' => 'chart-pie-with-legend-contribute-instrument',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contribute-instrument',
      'type' => 'Pie',
      'labels' => json_encode($instrumentsSum[0]),
      'series' => json_encode($instrumentsSum[1]),
      'labelType' => 'percent',
      'withLegend' => true
    );


    $typeSum = self::getArrayLabelAndValue($contribute['contribution_type'], 'sum');

    $chartTypeSum = array(
      'id' => 'chart-pie-with-legend-contribute-type',
      'classes' => array('cchart-pie-with-legend-contribute-type'),
      'selector' => '#chart-pie-with-legend-contribute-type',
      'type' => 'Pie',
      'labels' => json_encode($typeSum[0]),
      'series' => json_encode($typeSum[1]),
      'labelType' => 'percent',
      'withLegend' => true
    );

    $template = CRM_Core_Smarty::singleton();
    $template->assign('chartInsSum', $chartInsSum);
    $template->assign('chartTypeSum', $chartTypeSum);
    $template->assign('hasChart', TRUE);
    return parent::run();
  }

  static function getArrayLabelAndValue($array, $key){
    $returnLabel = Array();
    $returnValue = Array();
    foreach ($array as $label => $ele) {
      if(!empty($ele[$key])){
        $returnLabel[] = $label;
        $returnValue[] = $ele[$key];
      }
      else{
        $returnLabel[] = $label;
        $returnValue[] = 0;
      }
    }
    return array($returnLabel, $returnValue);
  }

}

