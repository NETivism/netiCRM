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
    $people_by_condition = array(
      'gender' => CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::GENDER),
      'age' => CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::AGE),
      'province' => CRM_Report_BAO_Summary::getStaWithCondition(CRM_Report_BAO_Summary::PROVINCE),
      );

    $template = CRM_Core_Smarty::singleton();
    $template->assign('contribute_total', CRM_Utils_Money::format($contribute['total_contribute']['sum']));
    $template->assign('contribute_online', CRM_Utils_Money::format($contribute['online_offline']['sum'][0]));
    $template->assign('participant_total',$participant['Participants Count']['count']);
    $template->assign('participant_online',$participant['online_offline']['count'][0]);
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
      'labels' => json_encode($contacts['filtered']['label']),
      'series' => json_encode($contacts['filtered']['people']),
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

    /**
     * Mailing Funnel
     */

    $chartMailingFunnel = array(
      'id' => 'chart-bar-mailing-funnel',
      'classes' => array('ct-chart-bar'),
      'selector' => '#chart-bar-mailing-funnel',
      'type' => 'Bar',
      'labels' => json_encode(array_slice($mailing['label'],1)),
      'series' => json_encode($mailing['funnel']['count']),
      'withToolTip' => true
    );
    $this->assign('chartMailingFunnel', $chartMailingFunnel);

    $this->assign('static_label',array(ts("Total Amount"), ts("Percentage"),ts("Avg Amount"),ts("Count"),ts("People")));
    $this->assign('contribution_type_table',$contribute['contribution_type_table']);

    $this->assign('recur_table',$contribute['recur_table']);



    $chartPeopleGender = array(
      'id' => 'chart-pie-with-legend-people-by-gender',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-people-by-gender',
      'type' => 'Pie',
      'labels' => json_encode($people_by_condition['gender']['label']),
      'series' => json_encode($people_by_condition['gender']['people']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartPeopleGender', $chartPeopleGender);

    $chartPeopleAge = array(
      'id' => 'chart-pie-with-legend-people-by-age',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-people-by-age',
      'type' => 'Pie',
      'labels' => json_encode($people_by_condition['age']['label']),
      'series' => json_encode($people_by_condition['age']['people']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartPeopleAge', $chartPeopleAge);

    $chartPeopleProvince = array(
      'id' => 'chart-pie-with-legend-people-by-province',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-people-by-province',
      'type' => 'Pie',
      'labels' => json_encode($people_by_condition['province']['label']),
      'series' => json_encode($people_by_condition['province']['people']),
      'labelType' => 'percent',
      'withLegend' => true,
      'withToolTip' => true
    );

    $template->assign('chartPeopleProvince', $chartPeopleProvince);



    // $template->assign('chartInsSum', $chartInsSum);
    // $template->assign('chartTypeSum', $chartTypeSum);
    $template->assign('hasChart', TRUE);

    if($_GET['showhidden'] == 1){
      $template->assign('showhidden', TRUE);
      $data['contacts'] = $contacts;
      $data['contribute'] = $contribute;
      $data['participant'] = $participant;
      $data['mailing'] = $mailing;
      $this->showhiddenall($data);
    }

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

  private function showhiddenall($data){
    $contacts = $data['contacts'];
    $contribute = $data['contribute'];
    $participant =  $data['participant'];
    $mailing =  $data['mailing'];

    $participant_to_contributor = CRM_Report_BAO_Summary::getPartToConData();
    $contributor_to_participant = CRM_Report_BAO_Summary::getConToPartData();
    $participant_after_mailing = CRM_Report_BAO_Summary::getPartAfterMailData();
    $contribute_after_mailing = CRM_Report_BAO_Summary::getConAfterMailData();

    $return_array['Count and people by participants online-offline'] = $this->showhidden(
      'part_online_offline',
      self::arrayRemoveKey($participant['online_offline']),
      $participant['online_offline']['label']
    );

    $return_array['Count and people by recur contribution.'] = $this->showhidden(
      'contrib_recur',
      self::arrayRemoveKey($contribute['recur']),
      $contribute['recur']['label']
    );
    $return_array['Contribution sum amount by recur'] = $this->showhidden(
      'contrib_recur_sum',
      self::arrayRemoveKey($contribute['recur'], array('sum')),
      $contribute['recur']['label']
    );

    $array = array(
      'total_contribute' => $contribute['total_contribute'],
      'total_application_fee' => $contribute['total_application_fee'],
      'total_amount' => $contribute['total_amount'],
      );

    $return_array['Count and people by contribution and application fee.'] = $this->showhidden(
      'contrib_applicate',
      self::dataTransferShowHidden($array),
      array_keys($array)
    );
    $return_array['Sum amount by contribution and application fee.'] = $this->showhidden(
      'contrib_applicate_sum',
      self::dataTransferShowHidden($array,array('sum')),
      array_keys($array)
    );

    $return_array['Count and people by contribution times.'] = $this->showhidden(
      'contrib_times',
      self::arrayRemoveKey($contribute['times']),
      $contribute['times']['label']
    );
    $return_array['Sum amount by contribution times.'] = $this->showhidden(
      'contrib_times_sum',
      self::arrayRemoveKey($contribute['times'], array('sum')),
      $contribute['times']['label']
    );

    $return_array['Count and people from participant to contributor.'] = $this->showhidden(
      'participant_to_contributor',
      self::dataTransferShowHidden($participant_to_contributor),
      array_keys($participant_to_contributor)
    );

    $return_array['Count and people from contributor to participant.'] = $this->showhidden(
      'contributor_to_participant',
      self::dataTransferShowHidden($contributor_to_participant),
      array_keys($contributor_to_participant)
    );

    $return_array['People participant after receiving mail.'] = $this->showhidden(
      'participant_after_mailing',
      self::dataTransferShowHidden($participant_after_mailing),
      array_keys($participant_after_mailing)
    );

    $return_array['People contributor after receiving mail.'] = $this->showhidden(
      'contribute_after_mailing',
      self::dataTransferShowHidden($contribute_after_mailing),
      array_keys($contribute_after_mailing)
    );

    $this->assign('showhiddenChart', $return_array);
  }

  private function showhidden($name, $data, $labels){
    $chart = array(
      'name' => $name,
      'id' => 'chart-bar-'.$name,
      'selector' => '#chart-bar-'.$name,
      'type' => 'Bar',
      'labels' => json_encode($labels),
      'series' => json_encode($data),
      'withToolTip' => true,
    );
    // $this->assign('chart'.$name, $chart);
    return $chart;
  }

  static private function arrayRemoveKey($arr, $types = array('count','people')){
    $return = array();
    if(!is_array($arr))return $arr;
    foreach ($types as $type) {
      foreach ($arr as $key => $value) {
        if($key == $type){
          $set = array();
          foreach ($value as $key => $value2) {
            $set[] = self::arrayRemoveKey($value2);
          }
          $return[] = $set;
        }
      }
    }
    return $return;
  }

  static private function dataTransferShowHidden($arr, $types = array('count','people')){
    $return = array();
    foreach ($types as $type) {
      $set = array();
      foreach ($arr as $value) {
        $set[] = $value[$type];
      }
      $return[] = $set;
    }
    return $return;
  }

}

