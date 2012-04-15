<?php

/**
 * Provide recurring administration page.
 */
function civicrm_neweb_recurring_page(){
  // drupal_get_form();
  // table list of recurring page
  // pager
  civicrm_initialize();
  if($_GET['action'] == 'stop' && is_numeric(arg(3))){
    $recur_id = arg(3);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_recur SET cancel_date = '".date('Y-m-d H:i:s')."' WHERE id = $recur_id");
    drupal_set_message('已成功停止定期定額捐款 #'.arg(3));
    drupal_goto('civicrm/contribute/recurring_nw');
  }
  $rows = array();
  $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution_recur r LEFT JOIN civicrm_contribution_neweb_recur nr ON r.id = nr.recur_id WHERE r.is_test = 0 ORDER BY r.create_date DESC");
  $header = array('#', '聯絡人', '金額', '頻率', '開始', '結束', '取消', '扣款日', '上傳藍新', '異動回覆', '最新交易', '操作');
  while($dao->fetch()){
    $group++;
    $class = $group % 2 ? 'even' : 'odd';
    $contact_name = CRM_Core_DAO::singleValueQuery("SELECT display_name FROM civicrm_contact WHERE id = $dao->contact_id");
    $row = array(
      array('data'=>$dao->id, 'rowspan' => 2, 'class' => 'recurring-id'),
      l($contact_name, "civicrm/contact/view", array("query" => "reset=1&cid=$dao->contact_id")),
      substr($dao->amount, 0, strpos($dao->amount, '.')),
      $dao->installments.ts($dao->frequency_unit),
      substr($dao->start_date, 0, strpos($dao->start_date, ' ')),
      substr($dao->end_date, 0, strpos($dao->end_date, ' ')),
      substr($dao->cancel_date, 0, strpos($dao->cancel_date, ' ')),
      $dao->cycle_day,
      format_date($dao->upload, 'custom', 'Y-m-d H:i'),
      format_date($dao->response, 'custom', 'Y-m-d H:i'),
      $dao->contributions ? format_date($dao->contributions, 'custom', 'Y-m-d H:i') : 'none',
      $dao->cancel_date ? '' : l('停止', 'civicrm/contribute/recurring_nw/'.$dao->id, array('query' => "action=stop")),
    );
    $rows[] = $row;
    $dao2 = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_contribution WHERE contribution_recur_id = $dao->id ORDER BY id ASC");
    $contributions = array();
    while($dao2->fetch()){
      $contributions[$dao2->id] = array(
        $dao2->trxn_id,
        $dao2->receive_date,
        $dao2->contribution_status_id,
        $dao2->source,
      );
    }
    $row = array(
    0 => array(
      'data' => drupal_get_form('civicrm_neweb_recurring_fieldset', $contributions),
      'colspan' => 11,
      'class' => 'recurring-detail',
    ));
    $rows[] = $row;
  }

  $output = theme('table', $header, $rows, array('id' => 'recurring-nw'));
  return $output;
}
function civicrm_neweb_recurring_fieldset($form_state, $contrib){
  $form['contributions'] = array(
    '#type' => 'fieldset',
    '#title' => ts('Contribution'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $header = array(
    '交易編號',
    '收到日期',
    '狀態',
    '來源',
  );
  $form['contributions']['contrib'] = array(
    '#value' => theme('table', $header, $contrib, array('class'=>'recurring-table')),
  );
  return $form;
}
