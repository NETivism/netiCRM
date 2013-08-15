<?php
function civicrm_statistics_fetch($debug = false){
  $stat = array();
  $sitekey = $_SERVER['HTTP_HOST'];
  $tables = civicrm_statistics_table();
  civicrm_initialize();
  foreach($tables as $table => $t){
    list($table, $sub) = explode('.', $table);
    if($sub){
      switch($t){
        case 'cc.g':
          $rows = civicrm_statistics_query("SELECT gender_id idx, count(id) counting FROM civicrm_contact WHERE gender_id IS NOT NULL GROUP BY gender_id");
          break;
        case 'cc.b':
          $rows = civicrm_statistics_query("SELECT year(birth_date) idx, count(id) counting FROM civicrm_contact WHERE birth_date IS NOT NULL GROUP BY year(birth_date)");
          break;
        case 'ca.p':
          $rows = civicrm_statistics_query("SELECT state_province_id idx, count(id) counting FROM civicrm_address WHERE state_province_id IS NOT NULL group by state_province_id");
          break;
        case 'ccb.a':
          $rows = CRM_Core_DAO::singleValueQuery("SELECT SUM(total_amount) FROM civicrm_contribution WHERE contribution_status_id = 1");
          $rows = (int) $rows;
          break;
        case 'ccb.h':
          $rows = civicrm_statistics_query("SELECT HOUR(receive_date) idx, count(id) counting, round(sum(total_amount)) as summ, round(avg(total_amount)) as avgg FROM civicrm_contribution WHERE payment_instrument_id = 1 AND contribution_status_id = 1 AND receive_date > '2000-01-01 00:00:00' group by HOUR(receive_date)");
          break;
        case 'ccb.w':
          $rows = civicrm_statistics_query("SELECT WEEKDAY(receive_date) idx, count(id) counting, round(sum(total_amount)) as summ, round(avg(total_amount)) as avgg      FROM civicrm_contribution WHERE payment_instrument_id = 1 AND contribution_status_id = 1 AND receive_date > '2000-01-01 00:00:00' group by WEEKDAY(receive_date)");
          break;
        case 'ccb.r':
          $rows = civicrm_statistics_query("
          select t.idx, count(*) counting, round(sum(t.total_amount)) summ, round(avg(t.total_amount)) avgg
          from (
          select case
          when total_amount between 0 and 100 then 100
          when total_amount between 100 and 500 then 500
          when total_amount between 500 and 1000 then 1000
          when total_amount between 1000 and 2000 then 2000
          when total_amount between 2000 and 5000 then 5000
          when total_amount between 5000 and 10000 then 10000
          when total_amount between 10000 and 20000 then 20000
          else 20001
          end idx,
          total_amount
          from civicrm_contribution
          ) t 
          group by t.idx
          ");
          break;
        case 'cmeo.h':
          $rows = civicrm_statistics_query("SELECT HOUR(time_stamp) idx, COUNT(time_stamp) as counting FROM civicrm_mailing_event_opened group by HOUR(time_stamp)");
          break;
        case 'cmeo.w':
          $rows = civicrm_statistics_query("SELECT WEEKDAY(time_stamp) idx, COUNT(time_stamp) as counting FROM civicrm_mailing_event_opened group by WEEKDAY(time_stamp)");
          break;
        case 'cmeo.n':
          $rows = civicrm_statistics_query("SELECT cc.counting as idx, count(cc.counting) as counting FROM (SELECT event_queue_id, count(event_queue_id) as counting FROM civicrm_mailing_event_opened group by event_queue_id having count(event_queue_id) > 1) as cc GROUP BY cc.counting");
          break;
        case 'cmet.h':
          $rows = civicrm_statistics_query("SELECT HOUR(time_stamp) idx, COUNT(time_stamp) as counting FROM civicrm_mailing_event_trackable_url_open group by HOUR(time_stamp)");
          break;
        case 'cmet.w':
          $rows = civicrm_statistics_query("SELECT WEEKDAY(time_stamp) idx, COUNT(time_stamp) as counting FROM civicrm_mailing_event_trackable_url_open group by WEEKDAY(time_stamp)");
          break;
        case 'cmet.n':
          $rows = civicrm_statistics_query("SELECT cc.counting as idx, count(cc.counting) as counting FROM (SELECT event_queue_id, count(event_queue_id) as counting FROM civicrm_mailing_event_trackable_url_open group by event_queue_id having count(event_queue_id) > 1) as cc GROUP BY cc.counting");
          break;
      }
    }
    else{
      $rows = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM $table WHERE 1");
    }

    $url = civicrm_statistics_build_fetch_url($sitekey, $t, $rows);
    if($debug){
      $stat[$t] = $rows;
    }
    else{
      $response = drupal_http_request($url);
      if($response->code == 200 && $response->data){
        $stat[$t] = $response->data;
      }
    }
  }

  if(!empty($stat) && !$debug){
    variable_set('civicrm_statistics_run', $_SERVER['REQUEST_TIME']);
    variable_set('civicrm_statistics', $stat);
  }
  elseif($debug){
    print_r($stat);
  }
}

function civicrm_statistics_build_fetch_url($sitekey, $type, $stat){
  if(is_array($stat)){
    $stat = urlencode(json_encode($stat));
  }
  return NETICRM_DEFAULT_URL."/fetch?sitekey={$sitekey}&type={$type}&stat={$stat}";
}


function civicrm_statistics_table($flip = FALSE){
  $tables = array(
    'civicrm_contact' => 'cc',
    'civicrm_contact.gender' => 'cc.g',
    'civicrm_contact.birth_date' => 'cc.b',
    'civicrm_address.province' => 'ca.p',
    'civicrm_contribution' => 'ccb',
    'civicrm_contribution.amount' => 'ccb.a',
    'civicrm_contribution.hour' => 'ccb.h',
    'civicrm_contribution.week' => 'ccb.w',
    'civicrm_contribution.range' => 'ccb.r',
    'civicrm_mailing_event_delivered' => 'cmed',
    'civicrm_mailing_event_opened' => 'cmeo',
    'civicrm_mailing_event_opened.hour' => 'cmeo.h',
    'civicrm_mailing_event_opened.week' => 'cmeo.w',
    'civicrm_mailing_event_opened.num' => 'cmeo.n',
    'civicrm_mailing_event_trackable_url_open' => 'cmet',
    'civicrm_mailing_event_trackable_url_open.hour' => 'cmet.h',
    'civicrm_mailing_event_trackable_url_open.week' => 'cmet.w',
    'civicrm_mailing_event_trackable_url_open.num' => 'cmet.n',
    'civicrm_event' => 'ce',
    'civicrm_participant' => 'cp',
  );
  if($flip){
    return array_flip($tables);
  }
  return $tables;
}

function civicrm_statistics_query($query){
  $q = CRM_Core_DAO::executeQuery($query);
  $result = array();
  while($q->fetch()){
    if($q->summ){
      $result[$q->idx] = array(
        'count' => $q->counting,
        'sum' => $q->summ,
        'avg' => $q->avgg,
      );
    }
    else{
      $result[$q->idx] = $q->counting;
    }
  }
  return $result;
}
