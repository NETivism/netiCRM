<?php

function neti_run_install(){
  neti_enable_custom_modules();
  neti_translate_report();
}

function neti_enable_custom_modules(){
  module_rebuild_cache( );
  // now enable civicrm related module.
  module_enable( array('civicrm_ckeditor','civicrm_imce','civicrm_twaddress','civicrm_gw','civicrm_newweb') );
  // clear block and page cache, to make sure civicrm link is present in navigation block
  cache_clear_all();
}

function neti_translate_report(){
  require_once "CRM/Report/DAO/Instance.php";
  $sql = "SELECT id FROM civicrm_report_instance WHERE 1";
  $rows = CRM_Core_DAO::executeQuery($sql);
  while($rows->fetch()){
    $report = new CRM_Report_DAO_Instance();
    $report->id = $rows->id;
    if($report->find(true)){
      $report->title = ts($report->title);
      $report->description = ts($report->description);
      $form_values = unserialize($report->form_values);
      $form_values['description'] = ts($form_values['description']);
      $report->form_values = serialize($form_values);
      $report->save();
    }
  }
}
