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
    $allData['Sended Count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['Successful Deliveries'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");
    $allData['Opened Count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_opened meo ON meo.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");
    $allData['Click Count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id {JOIN} INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id WHERE mj.is_test = 0 {AND}");
    $allData = self::convertArrayToChartUse($allData);
    $allData['Mailing'] = self::parseDataFromSql("SELECT COUNT(DISTINCT mj.mailing_id) count FROM civicrm_mailing_job mj WHERE mj.is_test = 0");

    return $allData;
  }

  static function getParitcipantData(){
    $allData = array();
    $allData['Event Total'] = self::parseDataFromSql("SELECT count(e.id) count FROM civicrm_event e");
    $allData['online_offline'] = array();
    $allData['online_offline']["Online Registration"] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE p.source LIKE '".ts("Online Event Registration")."%' AND p.is_test = 0 {AND}");
    $allData['online_offline']['Non-online Registration'] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE (p.source NOT LIKE '".ts("Online Event Registration")."%' OR p.source IS NULL) AND p.is_test = 0 {AND}");
    $allData['Participants Count'] = self::parseDataFromSql("SELECT count(p.id) count,COUNT(DISTINCT p.contact_id) people  FROM civicrm_participant p {JOIN} WHERE p.is_test = 0 {AND}");
    $allData = self::convertArrayToChartUse($allData);

    return $allData;
  }

  static function getContributionData(){
    $allData = array();
    $allData['online_offline'] = array();
    $allData['online_offline']['Online Contribution'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '".ts("Online Contribution")."%' AND c.is_test = 0 {AND};");
    $allData['online_offline']['Non-online Contribution'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE ((c.source NOT LIKE '".ts("Online Event Registration")."%' AND c.source NOT LIKE '".ts("Online Contribution")."%') OR c.source IS NULL) AND c.is_test = 0 {AND}");
    // contribution_type
    $allData['contribution_type'] = array();
    $sql = "SELECT id,name FROM civicrm_contribution_type";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->name;
      $id = $dao->id;
      $allData['contribution_type'][$name] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.contribution_type_id = $id AND c.is_test = 0 {AND}");
    }

    // instruments
    $allData['instruments'] = array();
    $gid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

    $sql = "SELECT value,label FROM civicrm_option_value WHERE option_group_id = $gid";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->label;
      $value = $dao->value;
      $allData['instruments'][$name] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.payment_instrument_id = $value AND c.is_test = 0 {AND}");
    }

    $allData['recur'] = array();
    $allData['recur']['Recurring Contribution'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.contribution_recur_id IS NOT NULL AND c.is_test = 0 {AND} {AND}");
    $allData['recur']["Non-recurring Contribution"] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.contribution_recur_id IS NULL AND c.is_test = 0 {AND}");

    $allData['times'] = array();
    $allData['times']['First by Contributor'] = self::parseDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(c.total_amount) sum, COUNT(c.contact_id) count,contact_id FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.is_test = 0 {AND} GROUP BY c.contact_id) a  WHERE a.count=1 ");
    $allData['times']['Second or Later by Contributor'] = self::parseDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(c.total_amount) sum, COUNT(c.contact_id) count,c.contact_id FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.is_test = 0 {AND} GROUP BY c.contact_id) a  WHERE a.count>=2 ");

    $allData['total_contribute'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NULL AND c.is_test = 0 {AND}");
    $allData['total_application_fee'] = self::parseDataFromSql("SELECT SUM(c.total_amount) sum, COUNT(c.id) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} LEFT JOIN civicrm_participant_payment pp ON c.id = pp.contribution_id WHERE pp.participant_id IS NOT NULL AND c.is_test = 0 {AND}");
    $allData['total_amount'] = self::parseDataFromSql("SELECT SUM( c.total_amount ) sum, COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.is_test =0 {AND}");
    $allData = self::convertArrayToChartUse($allData);
    $allData['contribution_type_table'] = self::convertArrayToTableUse($allData['contribution_type']);
    $allData['recur_table'] = self::convertArrayToTableUse($allData['recur']);
    return $allData;
  }

  static function getPartToConData(){
    $allData = array();
    $allData['Event Registration'] = self::parseDataFromSql("SELECT COUNT( p.id ) count,COUNT(DISTINCT p.contact_id) people FROM civicrm_participant p {JOIN} WHERE (p.source LIKE '".ts("Online Event Registration")."%' ) AND p.is_test = 0 {AND}");
    $allData['Contribution'] = self::parseDataFromSql("SELECT COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '".ts("Online Contribution")."%' AND c.is_test =0 {AND}");
    
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

  static function getConToPartData(){
    $allData = array();
    $allData['Event Registration'] = self::parseDataFromSql("SELECT COUNT( p.id ) count,COUNT(DISTINCT p.contact_id) people FROM civicrm_participant p {JOIN} WHERE (p.source LIKE '".ts("Online Event Registration")."%' ) AND p.is_test = 0 {AND}");
    $allData['Contribution'] = self::parseDataFromSql("SELECT COUNT( c.id ) count,COUNT(DISTINCT c.contact_id) people FROM civicrm_contribution c {JOIN} WHERE c.source LIKE '".ts("Online Contribution")."%' AND c.is_test =0 {AND}");
    
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

  static function getMailToPartData(){
    $allData = array();
    $allData['Successful Deliveries'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0");
    $allData['Click Count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0");
    $allData['Received Application Url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id 
      {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/event/'");
    $allData['Clicked Application Url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT met.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = met.trackable_url_id
      {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/event/'");
    $allData['Apply After Click Url'] = self::parseDataFromSql('SELECT count(p.contact_id) people,m.time_stamp FROM 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id {JOIN} {WHERE}) mm GROUP BY contact_id) m
  ON m.contact_id = p.contact_id 
WHERE
  p.register_date > m.time_stamp
');
    $allData['Apply After Click Url In 1 hr'] = self::parseDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT p.*,mm.time_stamp FROM 
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
    $allData['Successful Deliveries'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['Click Count'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id {JOIN} WHERE mj.is_test = 0 {AND}");
    $allData['Received Contribute Url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT meq.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id {JOIN} 
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/contribute/transact?' {AND}");
    $allData['Clicked Contribute Url'] = self::parseDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id
      {JOIN}
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^{baseurl}/civicrm/contribute/transact?' {AND}");
    $allData['Contribute After Click Url'] = self::parseDataFromSql('SELECT count(c.contact_id) people, m.time_stamp FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm GROUP BY contact_id) m
  ON m.contact_id = c.contact_id
  {JOIN}
WHERE
  c.receive_date > m.time_stamp {AND}
');
    $allData['Contribute After Click Url In 1 hr'] = self::parseDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT c.*,mm.time_stamp FROM 
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

  static function getPartAfterMailData(){
    $allData = array();
    $allData['Apply After Open Mail In 1 hr'] = self::getPartAfterMailFromSql(1);
    $allData['Apply After Open Mail In 1 Day'] = self::getPartAfterMailFromSql(24);
    $allData['Apply After Open Mail In 3 Day'] = self::getPartAfterMailFromSql(72);
    $allData['Apply After Open Mail In 7 Day'] = self::getPartAfterMailFromSql(168);
    

    return $allData;
  }

  static function getConAfterMailData(){
    $allData = array();
    $allData['Contribute After Open Mail In 1 hr'] = self::getConAfterMailFromSql(1);
    $allData['Contribute After Open Mail In 1 Day'] = self::getConAfterMailFromSql(24);
    $allData['Contribute After Open Mail In 3 Day'] = self::getConAfterMailFromSql(72);
    $allData['Contribute After Open Mail In 7 Day'] = self::getConAfterMailFromSql(168);
    

    return $allData;
  }

  static function getContactSource(){
    $allData = array();
    $all = self::parseDataFromSql("SELECT COUNT(id) people FROM civicrm_contact");
    $all = $all['people'];
    // $allData['all'] = self::parseDataFromSql("SELECT COUNT(id) people FROM civicrm_contact");
    $allData['Contributions'] = self::parseDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.created_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ccc.id WHERE pp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON c.contact_id = cl.entity_id WHERE created_date < DATE_ADD(modified_date, INTERVAL 10 second)");
    $allData['Event Registration'] = self::parseDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT contact_id, register_date FROM civicrm_participant WHERE 1 GROUP BY contact_id) p INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON p.contact_id = cl.entity_id WHERE register_date < DATE_ADD(modified_date, INTERVAL 10 second)");
    $allData['Other']['people'] = $all - $allData['Contributions']['people'] - $allData['Event Registration']['people'];

    $allData = array(
      'all' => $all,
      'filtered' => self::convertArrayToChartUse($allData),
    );

    return $allData;
  }


  private static function getPartAfterMailFromSql($hour){
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
          $returnArray['label'][] = ts($key);
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

  private static function convertArrayToTableUse($origArray){
    $returnArray = array();
    $sum_sum = array_sum($origArray['sum']);
    $sum_count = array_sum($origArray['count']);
    // $sum_people = array_sum($origArray['people']);
    foreach ($origArray['label'] as $key => $value) {
      $row = array();
      $row[0] = $origArray['label'][$key];
      $v_sum = $origArray['sum'][$key];
      $row[1] = CRM_Utils_Money::format( $v_sum );
      $row[2] = round( 100 * $v_sum / $sum_sum ) . '%';
      $v_count = $origArray['count'][$key];
      $row[3] = CRM_Utils_Money::format( $v_count == 0 ? 0 : $v_sum / $v_count );
      $row[4] = $origArray['count'][$key];
      $row[5] = $origArray['people'][$key];
      $returnArray[] = $row;
    }
    $returnArray[] = array(ts('Total'),CRM_Utils_Money::format($sum_sum),'100%', CRM_Utils_Money::format($sum_sum /$sum_count ) ,$sum_count, '');
    return $returnArray;
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
