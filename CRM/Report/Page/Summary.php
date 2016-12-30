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

    /*
    $allData = array();
    $allData['電子報統計'] = CRM_Report_BAO_Summary::getMailingData();
    $allData['參加者統計'] = CRM_Report_BAO_Summary::getParitcipantData();
    $allData['捐款統計'] = CRM_Report_BAO_Summary::getContributionData();
    // $allData['參加者轉捐款統計'] = CRM_Report_BAO_Summary::getActToConData();
    // $allData['捐款轉參加者統計'] = CRM_Report_BAO_Summary::getConToActData();
    // $allData['電子報轉參加者統計'] = CRM_Report_BAO_Summary::getMailToActData();
    // $allData['電子報轉捐款統計'] = CRM_Report_BAO_Summary::getMailToConData();
    // $allData['開信後多久報名活動'] = CRM_Report_BAO_Summary::getActAfterMailData();
    // $allData['開信後多久捐款'] = CRM_Report_BAO_Summary::getConAfterMailData();
    $allData['成為聯絡人是透過報名還是捐款'] = CRM_Report_BAO_Summary::getContactSource();
    dpm($allData);
    */

    $contacts = CRM_Report_BAO_Summary::getContactSource();

    $contribute = CRM_Report_BAO_Summary::getContributionData();

    $participants = CRM_Report_BAO_Summary::getParitcipantData();

    $mailing = CRM_Report_BAO_Summary::getMailingData();


    $template = CRM_Core_Smarty::singleton();
    $template->assign('contribute_total', $contribute['total_contribute']['sum']);
    $template->assign('participant_total',$participants['participants']['count']);
    $template->assign('contact_total',$contacts['all']['people']);
    $template->assign('mailing',$mailing['sended']['count']);

    $instrumentsSum = self::getArrayLabelAndValue($contribute['instruments'], 'sum');
    /**
     * Contribute
     */

    $chartContact = array(
      'id' => 'chart-pie-with-legend-contact-source',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contact-source',
      'type' => 'Pie',
      'labels' => json_encode($contacts['label']),
      'series' => json_encode($contacts['people']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartContact', $chartContact);


    /**
     * Contribute
     */

    $chartInsSum = array(
      'id' => 'chart-pie-with-legend-contribute-instrument',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contribute-instrument',
      'type' => 'Pie',
      'labels' => json_encode($contribute['instruments']['label']),
      'series' => json_encode($contribute['instruments']['sum']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartInsSum', $chartInsSum);

    /**
     * Contribute Times
     */

    $chartContribTimes = array(
      'id' => 'chart-pie-with-legend-contribute-times',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contribute-times',
      'type' => 'Pie',
      'labels' => json_encode($contribute['times']['label']),
      'series' => json_encode($contribute['times']['sum']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartContribTimes', $chartContribTimes);

    /**
     * Mailing
     */

    $chartMailing = array(
      'id' => 'chart-bar-mailing',
      'classes' => array('ct-chart-bar'),
      'selector' => '#chart-bar-mailing',
      'type' => 'Bar',
      'labels' => json_encode($mailing['label']),
      'series' => json_encode($mailing['count']),
      'withToolTip' => true
    );
    $this->assign('chartMailing', $chartMailing);


    // $template->assign('chartInsSum', $chartInsSum);
    // $template->assign('chartTypeSum', $chartTypeSum);
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

