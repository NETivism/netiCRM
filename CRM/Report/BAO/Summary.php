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

class CRM_Report_BAO_Summary {

  /** 
   * 1. Summary
   * 2. Contact source
   * 3. Contribution by Instruments
   * 4. Contact by Contribute times
   * 5. Mailing Summary
   * 6. Contribution Summary ( Table )
   */

  static function getMailingData(){
    $allData = array();
    $allData['mailing_count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT mj.mailing_id) count FROM civicrm_mailing_job mj WHERE mj.is_test = 0");
    $allData['sended'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['received'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");
    $allData['opened'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meo.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_opened meo ON meo.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");
    $allData['clicked'] = self::parseDataFromSql("SELECT COUNT(DISTINCT met.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");

    return self::convertArrayToChartUse($allData);
  }

  static function getParitcipantData(){
    $allData = array();
    $allData['events'] = self::parseDataFromSql("SELECT count(e.id) count FROM civicrm_event e");
    $allData['online_offline'] = array();
    $allData['online_offline']['online_participants'] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE p.source LIKE '線上活動報名%' AND p.is_test = 0 {AND}");
    $allData['online_offline']['offline_participants'] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE (p.source NOT LIKE '線上活動報名%' OR p.source IS NULL) AND p.is_test = 0 {AND}");
    $allData['participants'] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE p.is_test = 0 {AND}");
    return self::convertArrayToChartUse($allData);
  }

  static function getContributionData(){
    $allData = array();
    $allData['online_offline'] = array();
    $allData['online_offline']['online'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '線上捐款%' AND c.is_test = 0 {AND};");
    // $allData['online_offline']['online'] = self::parseDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people {SELECT} FROM civicrm_contribution {JOIN} WHERE source LIKE '線上捐款%' AND is_test = 0 {WHERE};");
    // {JOIN} -> INNER JOIN civicrm_contact contact_a ;
    // {WHERE} -> AND contact_a.is_deleted = 0;
    $allData['online_offline']['offline'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE ((c.source NOT LIKE '線上活動報名%' AND c.source NOT LIKE '線上捐款%') OR c.source IS NULL) AND c.is_test = 0 {AND}");
    
    // contribution_type
    $allData['contribution_type'] = array();
    $sql = "SELECT id,name FROM civicrm_contribution_type";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->name;
      $id = $dao->id;
      $allData['contribution_type'][$name] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL) AND c.contribution_type_id = $id AND c.is_test = 0 {AND}");
    }

    // instruments
    $allData['instruments'] = array();
    $gid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

    $sql = "SELECT value,label FROM civicrm_option_value WHERE option_group_id = $gid";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->label;
      $value = $dao->value;
      $allData['instruments'][$name] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL) AND c.payment_instrument_id = $value AND c.is_test = 0 {AND}");
    }

    $allData['recur'] = array();
    $allData['recur']['recur'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL) AND c.contribution_recur_id IS NOT NULL AND c.is_test = 0 {AND} {AND}");
    $allData['recur']['non_recur'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL) AND c.contribution_recur_id IS NULL AND c.is_test = 0 {AND}");

    $allData['times'] = array();
    $allData['times']['1'] = self::parseDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(c.total_amount) sum, COUNT(c.contact_id) count,contact_id FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL)  AND c.is_test = 0 {AND} GROUP BY c.contact_id) a  WHERE a.count=1 ");
    $allData['times']['over_2'] = self::parseDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(c.total_amount) sum, COUNT(c.contact_id) count,c.contact_id FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL)  AND c.is_test = 0 {AND} GROUP BY c.contact_id) a  WHERE a.count>=2 ");

    $allData['total_contribute'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE (c.source NOT LIKE '線上活動報名%' OR c.source IS NULL) AND c.is_test = 0 {AND}");
    $allData['total_application_fee'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '線上活動報名%' AND c.is_test = 0 {AND}");
    $allData['total_amount'] = self::parseDataFromSql("SELECT SUM( c.total_amount ) sum, COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.is_test =0 {AND}");
    return self::convertArrayToChartUse($allData);
  }

  static function getActToConData(){
    $allData = array();
    $allData['has_applied'] = self::parseDataFromSql("SELECT COUNT( p.id ) count,COUNT(DISTINCT p.contact_id) people FROM civicrm_participant p {JOIN} WHERE (p.source LIKE '線上活動報名%' ) AND p.is_test = 0 {AND}");
    $allData['has_contributed'] = self::parseDataFromSql("SELECT COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '線上捐款%' AND c.is_test =0 {AND}");
    
    $allData['apply_after_contributed'] = self::parseDataFromSql('SELECT count(c.contact_id) people, p.register_date FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c

INNER JOIN 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
  ON p.contact_id = c.contact_id
{JOIN}
WHERE 
  c.receive_date < p.register_date {AND}');

    
    return $allData;
  }

  static function getConToActData(){
    $allData = array();
    $allData['has_applied'] = self::parseDataFromSql("SELECT COUNT( p.id ) count,COUNT(DISTINCT p.contact_id) people FROM civicrm_participant p {JOIN} WHERE (p.source LIKE '線上活動報名%' ) AND p.is_test = 0 {AND}");
    $allData['has_contributed'] = self::parseDataFromSql("SELECT COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '線上捐款%' AND c.is_test =0 {AND}");
    
    $allData['contribute_after_applied'] = self::parseDataFromSql('SELECT count(c.contact_id) people, p.register_date FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c
INNER JOIN 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
  ON p.contact_id = c.contact_id
{JOIN}
WHERE
  c.receive_date > p.register_date {AND}');

    return $allData;
  }

  static function getMailToActData(){
    $allData = array();
    $allData['received'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0");
    $allData['clicked'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0");
    $allData['received_application_url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id 
      {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/event/'");
    $allData['clicked_application_url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT met.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = met.trackable_url_id
      {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/event/'");
    $allData['apply_after_click_url'] = self::parseDataFromSql('SELECT count(p.contact_id) people,m.time_stamp FROM 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id {JOIN} {WHERE}) mm GROUP BY contact_id) m
  ON m.contact_id = p.contact_id 
WHERE
  p.register_date > m.time_stamp
');
    $allData['apply_after_click_url_in_1_hr'] = self::parseDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT p.*,mm.time_stamp FROM 
  (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) p 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id 
  INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id 
  {JOIN}
  WHERE mj.is_test = 0 AND mtu.url REGEXP \'^{baseurl}/civicrm/event/\' {AND}
  ) mm
  ON mm.contact_id = p.contact_id
WHERE p.register_date > mm.time_stamp AND p.register_date < DATE_ADD(mm.time_stamp, INTERVAL 1 hour) GROUP BY contact_id) pm;');

    return $allData;
  }

  static function getMailToConData(){
    $allData = array();
    $allData['received'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['clicked'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['received_contribute_url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/contribute/transact?' {AND}");
    $allData['clicked_contribute_url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id
      {JOIN}
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/contribute/transact?' {AND}");
    $allData['contribute_after_click_url'] = self::parseDataFromSql('SELECT count(c.contact_id) people, m.time_stamp FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm GROUP BY contact_id) m
  ON m.contact_id = c.contact_id
  {JOIN}
WHERE
  c.receive_date > m.time_stamp {AND}
');
    $allData['contribute_after_click_url_in_1_hr'] = self::parseDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT c.*,mm.time_stamp FROM 
  (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) c 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id  
  INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  \'^{baseurl}/civicrm/contribute/transact?\') mm
  ON mm.contact_id = c.contact_id
  {JOIN}
WHERE c.receive_date > mm.time_stamp AND c.receive_date < DATE_ADD(mm.time_stamp, INTERVAL 1 hour) {AND} GROUP BY contact_id) cm;');
    return $allData;
  }

  static function getActAfterMailData(){
    $allData = array();
    $allData['apply_after_open_mail_in_1_hr'] = self::getActAfterMailFromSql(1);
    // $allData['apply_after_open_mail_in_1_day'] = self::getActAfterMailFromSql(24);
    // $allData['apply_after_open_mail_in_3_day'] = self::getActAfterMailFromSql(72);
    // $allData['apply_after_open_mail_in_7_day'] = self::getActAfterMailFromSql(168);
    

    return $allData;
  }

  static function getConAfterMailData(){
    $allData = array();
    $allData['contribute_after_open_mail_in_1_hr'] = self::getConAfterMailFromSql(1);
    // $allData['contribute_after_open_mail_in_1_day'] = self::getConAfterMailFromSql(24);
    // $allData['contribute_after_open_mail_in_3_day'] = self::getConAfterMailFromSql(72);
    // $allData['contribute_after_open_mail_in_7_day'] = self::getConAfterMailFromSql(168);
    

    return $allData;
  }

  static function getContactSource(){
    $allData = array();
    $all = self::parseDataFromSql("SELECT COUNT(id) people FROM civicrm_contact");
    $all = $all['people'];
    // $allData['all'] = self::parseDataFromSql("SELECT COUNT(id) people FROM civicrm_contact");
    $allData['by_contribute'] = self::parseDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.created_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON c.contact_id = cl.entity_id WHERE created_date < DATE_ADD(modified_date, INTERVAL 10 second)");
    $allData['by_apply'] = self::parseDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT contact_id, register_date FROM civicrm_participant WHERE 1 GROUP BY contact_id) p INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON p.contact_id = cl.entity_id WHERE register_date < DATE_ADD(modified_date, INTERVAL 10 second)");
    $allData['other']['people'] = $all - $allData['by_contribute']['people'] - $allData['by_apply']['people'];

    $allData = array(
      'all' => $all,
      'filted' => self::convertArrayToChartUse($allData),
    );

    return $allData;
  }


  private static function getActAfterMailFromSql($hour){
    $sql = 'SELECT COUNT(DISTINCT contact_id) people FROM (SELECT pp.*,mm.time_stamp FROM 
  (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm
  ON mm.contact_id = pp.contact_id
WHERE pp.register_date > mm.time_stamp AND pp.register_date < DATE_ADD(mm.time_stamp, INTERVAL '.$hour.' hour) GROUP BY contact_id) pm;';
    return self::parseDataFromSql($sql);
  }

  private static function getConAfterMailFromSql($hour){
    $sql = 'SELECT COUNT(DISTINCT contact_id) people FROM (SELECT cc.*,mm.time_stamp FROM 
  (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm
  ON mm.contact_id = cc.contact_id
WHERE cc.receive_date > mm.time_stamp AND cc.receive_date < DATE_ADD(mm.time_stamp, INTERVAL '.$hour.' hour) GROUP BY contact_id) cm;';
    return self::parseDataFromSql($sql);
  }

  private static function convertArrayToChartUse($origArray){
    if(is_array($origArray)){
      if(self::isArrayDataFormat($origArray)){

        return $origArray;
      }

      $subArrayIsDataFormat = true;
      foreach ($origArray as $key => $subArray) {
        if(!self::isArrayDataFormat($subArray)){
          $subArrayIsDataFormat = false;
        }
      }

      if($subArrayIsDataFormat){
        $returnArray = array(
          'label' => array()
        );
        $valueType = array('count','people','sum');
        foreach ($valueType as $type) {
          $returnArray[$type] = array();
        }

        foreach ($origArray as $key => $value) {
          $returnArray['label'][] = $key;
          foreach ($valueType as $type) {
            $returnArray[$type][] = !empty($value[$type])?$value[$type]:0;
          }
        }


      }else{
        foreach ($origArray as $key => $subArray) {
          $returnArray[$key] = self::convertArrayToChartUse($subArray);
        }
      }
      return $returnArray;

    }
  }

  private static function isArrayDataFormat($origArray){
    $flag = true;
    if(is_array($origArray)){
      foreach ($origArray as $key => $value) {
        switch ($key) {
            case 'sum':
            case 'people':
            case 'count':
              # code...
              break;
            default:
              $flag = false;
              break;
          }
      }
    }else{
      $flag = false;
    }
    return $flag;
  }

  private static function parseDataFromSql($sql){
    if(strpos($sql,'{JOIN}')){
      if(strpos($sql,'civicrm_participant p')){
        $table_name = 'p';
      }elseif(strpos($sql,'civicrm_contribution c')){
        $table_name = 'c';
      }elseif(strpos($sql,'civicrm_mailing_event_queue meq')){
        $table_name = 'meq';
      }else{
        $table_name = false;
      }
      $table_name_point = !empty($table_name)?"{$table_name}.":"";
      $join = "INNER JOIN civicrm_contact contact ON {$table_name_point}contact_id = contact.id";
      $sql = str_replace('{JOIN}', $join, $sql);
      $sql = str_replace('{AND}', 'AND contact.is_deleted = 0', $sql);
      $sql = str_replace('{WHERE}', 'WHERE contact.is_deleted = 0', $sql);
    }

    global $base_url;
    $sql = str_replace('{baseurl}', $base_url, $sql);

    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $data = array();
      if(isset($dao->sum)){
        $data['sum'] = $dao->sum;
      }
      if(isset($dao->people)){
        $data['people'] = $dao->people;
      }
      if(isset($dao->count)){
        $data['count'] = $dao->count;
      }
      return $data;
    }
  }

  private static function parseMailDataFromSql($sql){
    $dao = CRM_Core_DAO::executeQuery($sql);
    $alldata = array();
    while($dao->fetch()){
      $data = array();
      $data['id'] = $dao->id;
      $data['title'] = $dao->title;
      $data['time'] = $dao->time;
      $data['delivered'] = $dao->delivered;
      $data['opened'] = $dao->opened;
      $data['clicked'] = $dao->clicked;
      $alldata[] = $data;
    }
    return $alldata;
  }
}
