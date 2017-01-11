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
    $contacts = CRM_Report_BAO_Summary::getContactSource();
    $contribute = CRM_Report_BAO_Summary::getContributionData();
    $participant = CRM_Report_BAO_Summary::getParitcipantData();
    $mailing = CRM_Report_BAO_Summary::getMailingData();

    $template = CRM_Core_Smarty::singleton();
    $template->assign('contribute_total', $contribute['total_contribute']['sum']);
    $template->assign('participant_total',$participant['Participants Count']['count']);
    $template->assign('contact_total',$contacts['all']);
    $template->assign('mailing',$mailing['count'][0]);

    /**
     * Online-offline contribution
     */
    $chartContact = array(
      'id' => 'chart-pie-with-legend-contribution-online-offline',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contribution-online-offline',
      'type' => 'Pie',
      'series' => self::getDonutData($contribute['online_offline']['sum']),
      'isFillDonut' => true,
    );
    $template->assign('chartConributeOnlineOffline', $chartContact);

    /**
     * Online-offline participant
     */
    $chartContact = array(
      'id' => 'chart-pie-with-legend-participant-online-offline',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-participant-online-offline',
      'type' => 'Pie',
      'series' => self::getDonutData($participant['online_offline']['count']),
      'isFillDonut' => true,
    );
    $template->assign('chartParticipantOnlineOffline', $chartContact);


    /**
     * Contribute
     */

    $chartContact = array(
      'id' => 'chart-pie-with-legend-contact-source',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-contact-source',
      'type' => 'Pie',
      'labels' => json_encode($contacts['filted']['label']),
      'series' => json_encode($contacts['filted']['people']),
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
      'series' => json_encode(array($mailing['count'])),
      'withToolTip' => true
    );
    $this->assign('chartMailing', $chartMailing);

    $this->assign('static_label',array(ts("Total Amount"),ts("Avg Amount"),ts("Count"),ts("People")));
    $this->assign('contribution_type_table',$contribute['contribution_type_table']);

    $this->assign('recur_table',$contribute['recur_table']);

    // $template->assign('chartInsSum', $chartInsSum);
    // $template->assign('chartTypeSum', $chartTypeSum);
    $template->assign('hasChart', TRUE);

    CRM_Utils_System::setTitle(ts('Report Summary'));

    return parent::run();
  }

  static private function getDonutData($data){
    $i = 0;
    $returnData = array();
    foreach ($data as $value) {
      if($i == 0){
        $returnData[0] = $value;
      }elseif($i == 1){
        $returnData[] = $returnData[0] + $value;
      }else{
        break;
      }
      $i ++;
    }
    return json_encode($returnData);
  }

}

