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
    $allData['mailing_count'] = self::getDataFromSql("SELECT COUNT(DISTINCT mailing_id) count FROM civicrm_mailing_job WHERE is_test = 0");
    $allData['sended'] = self::getDataFromSql("SELECT COUNT(DISTINCT meq.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id WHERE mj.is_test = 0");
    $allData['received'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['opened'] = self::getDataFromSql("SELECT COUNT(DISTINCT meo.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_opened meo ON meo.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['clicked'] = self::getDataFromSql("SELECT COUNT(DISTINCT met.id) count FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id WHERE mj.is_test = 0");

    return $allData;
  }

  static function getActivitiesData(){
    $allData = array();
    $allData['events'] = self::getDataFromSql("SELECT count(id) count FROM civicrm_event");
    $allData['online_participants'] = self::getDataFromSql("SELECT count(id) count,COUNT(DISTINCT contact_id) people  FROM civicrm_participant WHERE source LIKE '線上活動報名%' AND is_test = 0");
    $allData['offline_participants'] = self::getDataFromSql("SELECT count(id) count,COUNT(DISTINCT contact_id) people  FROM civicrm_participant WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND is_test = 0");
    $allData['participants'] = self::getDataFromSql("SELECT count(id) count,COUNT(DISTINCT contact_id) people  FROM civicrm_participant WHERE is_test = 0");
    return $allData;
  }

  static function getContributionData(){
    $allData = array();
    $allData['online_offline'] = array();
    $allData['online_offline']['online'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE source LIKE '線上捐款%' AND is_test = 0;");
    $allData['online_offline']['offline'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE ((source NOT LIKE '線上活動報名%' AND source NOT LIKE '線上捐款%') OR source IS NULL) AND is_test = 0");
    
    // contribution_type
    $allData['contribution_type'] = array();
    $sql = "SELECT id,name FROM civicrm_contribution_type";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->name;
      $id = $dao->id;
      $allData['contribution_type'][$name] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND contribution_type_id = $id AND is_test = 0");
    }

    // instruments
    $allData['instruments'] = array();
    $gid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name LIKE 'payment_instrument'");

    $sql = "SELECT value,label FROM civicrm_option_value WHERE option_group_id = $gid";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $name = $dao->label;
      $value = $dao->value;
      $allData['instruments'][$name] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND payment_instrument_id = $value AND is_test = 0");
    }

    $allData['recur'] = array();
    $allData['recur']['recur'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND contribution_recur_id IS NOT NULL AND is_test = 0");
    $allData['recur']['non_recur'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND contribution_recur_id IS NULL AND is_test = 0");

    $allData['times'] = array();
    $allData['times']['1'] = self::getDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(total_amount) sum, COUNT(contact_id) count,contact_id FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL)  AND is_test = 0 GROUP BY contact_id) a WHERE a.count=1");
    $allData['times']['over_2'] = self::getDataFromSql("SELECT SUM(sum) sum, count(DISTINCT contact_id) people FROM (SELECT SUM(total_amount) sum, COUNT(contact_id) count,contact_id FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL)  AND is_test = 0 GROUP BY contact_id) a WHERE a.count>=2");

    $allData['total_contribute'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE (source NOT LIKE '線上活動報名%' OR source IS NULL) AND is_test = 0");
    $allData['total_application_fee'] = self::getDataFromSql("SELECT SUM(total_amount) sum, COUNT(id) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE source LIKE '線上活動報名%' AND is_test = 0");
    $allData['total_amount'] = self::getDataFromSql("SELECT SUM( total_amount ) sum, COUNT( id ) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE is_test =0");
    return $allData;
  }

  static function getActToConData(){
    $allData = array();
    $allData['has_applied'] = self::getDataFromSql("SELECT COUNT( id ) count,COUNT(DISTINCT contact_id) people FROM civicrm_participant WHERE (source LIKE '線上活動報名%' ) AND is_test = 0");
    $allData['has_contributed'] = self::getDataFromSql("SELECT COUNT( id ) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE source LIKE '線上捐款%' AND is_test =0");
    
    $allData['apply_after_contributed'] = self::getDataFromSql('SELECT count(c.contact_id) people, p.register_date FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c

INNER JOIN 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
  ON p.contact_id = c.contact_id

WHERE 
  c.receive_date < p.register_date');

    
    return $allData;
  }

  static function getConToActData(){
    $allData = array();
    $allData['has_applied'] = self::getDataFromSql("SELECT COUNT( id ) count,COUNT(DISTINCT contact_id) people FROM civicrm_participant WHERE (source LIKE '線上活動報名%' ) AND is_test = 0");
    $allData['has_contributed'] = self::getDataFromSql("SELECT COUNT( id ) count,COUNT(DISTINCT contact_id) people FROM civicrm_contribution WHERE source LIKE '線上捐款%' AND is_test =0");
    
    $allData['contribute_after_applied'] = self::getDataFromSql('SELECT count(c.contact_id) people, p.register_date FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c
INNER JOIN 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
  ON p.contact_id = c.contact_id

WHERE
  c.receive_date > p.register_date');

    return $allData;
  }

  static function getMailToActData(){
    $allData = array();
    $allData['received'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['clicked'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['received_application_url'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^https://jrf.neticrm.tw/civicrm/event/'");
    $allData['clicked_application_url'] = self::getDataFromSql("SELECT COUNT(DISTINCT met.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open met ON met.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = met.trackable_url_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^https://jrf.neticrm.tw/civicrm/event/'");
    $allData['apply_after_click_url'] = self::getDataFromSql('SELECT count(p.contact_id) people,m.time_stamp FROM 
  (SELECT pp.* FROM (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp GROUP BY pp.contact_id) p 
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm GROUP BY contact_id) m
  ON m.contact_id = p.contact_id 
WHERE
  p.register_date > m.time_stamp
');
    $allData['apply_after_click_url_in_1_hr'] = self::getDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT pp.*,mm.time_stamp FROM 
  (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id 
  INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id WHERE mj.is_test = 0 AND mtu.url REGEXP \'^https://jrf.neticrm.tw/civicrm/event/\'
  ) mm
  ON mm.contact_id = pp.contact_id
WHERE pp.register_date > mm.time_stamp AND pp.register_date < DATE_ADD(mm.time_stamp, INTERVAL 1 hour) GROUP BY contact_id) pm;');

    return $allData;
  }

  static function getMailToConData(){
    $allData = array();
    $allData['received'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['clicked'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id WHERE mj.is_test = 0");
    $allData['received_contribute_url'] = self::getDataFromSql("SELECT COUNT(DISTINCT meq.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.mailing_id = mj.mailing_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^https://jrf.neticrm.tw/civicrm/contribute/transact?'");
    $allData['clicked_contribute_url'] = self::getDataFromSql("SELECT COUNT(DISTINCT med.id) count,COUNT(DISTINCT meq.contact_id) people FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id 
      INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  '^https://jrf.neticrm.tw/civicrm/contribute/transact?'");
    $allData['contribute_after_click_url'] = self::getDataFromSql('SELECT count(c.contact_id) people, m.time_stamp FROM 
  (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c
INNER JOIN 
  (SELECT mm.* FROM (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm GROUP BY contact_id) m
  ON m.contact_id = c.contact_id
WHERE
  c.receive_date > m.time_stamp
');
    $allData['contribute_after_click_url_in_1_hr'] = self::getDataFromSql('SELECT COUNT(DISTINCT contact_id) people FROM (SELECT cc.*,mm.time_stamp FROM 
  (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_job mj 
      LEFT JOIN civicrm_mailing_event_queue meq ON meq.job_id = mj.id 
      INNER JOIN civicrm_mailing_event_trackable_url_open med ON med.event_queue_id = meq.id  
  INNER JOIN civicrm_mailing_trackable_url mtu ON mtu.id = med.trackable_url_id
      WHERE mj.is_test = 0 AND mtu.url REGEXP  \'^https://jrf.neticrm.tw/civicrm/contribute/transact?\') mm
  ON mm.contact_id = cc.contact_id
WHERE cc.receive_date > mm.time_stamp AND cc.receive_date < DATE_ADD(mm.time_stamp, INTERVAL 1 hour) GROUP BY contact_id) cm;');
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

  static function getContactSourceIsActCon(){
    $allData = array();
    $allData['by_contribute'] = self::getDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT cc.* FROM (SELECT ccc.contact_id, ccc.created_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc GROUP BY cc.contact_id) c INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON c.contact_id = cl.entity_id WHERE created_date < DATE_ADD(modified_date, INTERVAL 10 second)");
    $allData['by_apply'] = self::getDataFromSql("SELECT COUNT(contact_id) people FROM (SELECT contact_id, register_date FROM civicrm_participant WHERE 1 GROUP BY contact_id) p INNER JOIN (SELECT entity_id,modified_date FROM civicrm_log WHERE entity_table = 'civicrm_contact' GROUP BY entity_id) cl ON p.contact_id = cl.entity_id WHERE register_date < DATE_ADD(modified_date, INTERVAL 10 second)");

    return $allData;
  }




  private static function getDataFromSql($sql){
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

  private static function getActAfterMailFromSql($hour){
    $sql = 'SELECT COUNT(DISTINCT contact_id) people FROM (SELECT pp.*,mm.time_stamp FROM 
  (SELECT contact_id, register_date FROM civicrm_participant ORDER BY register_date) pp 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm
  ON mm.contact_id = pp.contact_id
WHERE pp.register_date > mm.time_stamp AND pp.register_date < DATE_ADD(mm.time_stamp, INTERVAL '.$hour.' hour) GROUP BY contact_id) pm;';
    return self::getDataFromSql($sql);
  }

  private static function getConAfterMailFromSql($hour){
    $sql = 'SELECT COUNT(DISTINCT contact_id) people FROM (SELECT cc.*,mm.time_stamp FROM 
  (SELECT ccc.contact_id, ccc.receive_date FROM civicrm_contribution ccc LEFT JOIN civicrm_participant_payment cpp ON cpp.contribution_id = ccc.id WHERE cpp.contribution_id IS NULL AND ccc.receive_date IS NOT NULL ORDER BY ccc.receive_date) cc 
LEFT JOIN 
  (SELECT med.time_stamp,meq.contact_id FROM civicrm_mailing_event_opened med LEFT JOIN civicrm_mailing_event_queue meq ON med.event_queue_id = meq.id) mm
  ON mm.contact_id = cc.contact_id
WHERE cc.receive_date > mm.time_stamp AND cc.receive_date < DATE_ADD(mm.time_stamp, INTERVAL '.$hour.' hour) GROUP BY contact_id) cm;';
    return self::getDataFromSql($sql);
  }

  private static function getMailDataFromSql($sql){
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
