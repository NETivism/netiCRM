<?php
require_once __DIR__.'/extern.inc';
CRM_Core_Config::singleton();

// to keep backward compatibility for URLs generated
// by CiviCRM < 1.7, we check for the q variable as well
if (isset($_GET['qid'])) {
  $queue_id = CRM_Utils_Array::value( 'qid', $_GET );
}
else {
  $queue_id = CRM_Utils_Array::value( 'q', $_GET );
}
$url_id = CRM_Utils_Array::value( 'u', $_GET );

if ( ! $queue_id || ! $url_id ) {
  http_response_code(400);
  exit;
}
if (!CRM_Utils_Rule::positiveInteger($queue_id)) {
  http_response_code(400);
  exit;
}
if (!CRM_Utils_Rule::positiveInteger($url_id)) {
  http_response_code(400);
  exit;
}

$url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($queue_id, $url_id);
$url_parsed = CRM_Utils_String::parseUrl($url);
if ($url_parsed['host'] == 'neti.cc') {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $content = curl_exec($ch);
  curl_close($ch);
  preg_match('/<a href="([^"]*)"/i', $content, $match);
  $url = $match[1];
  $url_parsed = CRM_Utils_String::parseUrl($url);
}
// CRM-7103
// looking for additional query variables and append them when redirecting
$query_param = $_GET;
unset($query_param['q'], $query_param['qid'], $query_param['u']);

if ($url_parsed['host'] === $_SERVER['HTTP_HOST']) {
  $query_param['civimail_x_q'] = $queue_id;
  $query_param['civimail_x_u'] = $url_id;
}

if (!empty($query_param)) {
  $query_string = http_build_query($query_param);
  if (empty($url_parsed['query'])) {
    $url_parsed['query'] = $query_string;
  }
  else {
    $url_parsed['query'] = rtrim($url_parsed['query'], '&') . '&' . $query_string;
  }

  $url = CRM_Utils_String::buildUrl($url_parsed);
}

CRM_Utils_System::redirect($url);
exit;
