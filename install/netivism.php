<?php

function neti_run_install(){
  civicrm_initialize( );
  neti_domain_set_default();
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

function neti_domain_set_default(){
  require_once "CRM/Core/Component.php";
  require_once "CRM/Core/BAO/Setting.php";
  $params = array (
    'verpSeparator' => '.',
    'mailerBatchLimit' => '300',
    'mailerJobSize' => '',
    'civiRelativeURL' => '/',
    'dateformatDatetime' => '%Y-%m-%d %H:%M',
    'dateformatFull' => '%Y-%m-%d',
    'dateformatPartial' => '%Y-%m',
    'dateformatYear' => '%Y',
    'dateformatTime' => '%H:%M',
    'dateInputFormat' => 'yy-mm-dd',
    'timeInputFormat' => '2',
    'fiscalYearStart' => 
      array (
        'M' => '1',
        'd' => '1',
      ),
    'contactUndelete' => '1',
    'logging' => '0',
    'versionCheck' => '0',
    'maxAttachments' => '3',
    'maxFileSize' => '2',
    'recaptchaPublicKey' => '',
    'recaptchaPrivateKey' => '',
    'dashboardCacheTimeout' => '1440',
    'includeWildCardInName' => '1',
    'includeEmailInName' => '1',
    'includeNickNameInName' => '1',
    'includeAlphabeticalPager' => '0',
    'includeOrderByClause' => '1',
    'smartGroupCacheTimeout' => '3',
    'defaultSearchProfileID' => '',
    'autocompleteContactSearch' => 
      array (
        1 => '1',
        2 => '1',
      ),
    'mapProvider' => 'Google',
    'mapAPIKey' => 'ABQIAAAAI0lzbp5MgOdeDMWG1cr9-BQZAY_qI7JFiArrU6Wsi_s48IuOrxSPDjSQeXkQ18oC3N608PKHjmNV-A',
    'enableComponents' => 
      array (
        0 => 'CiviContribute',
        1 => 'CiviMember',
        2 => 'CiviEvent',
        3 => 'CiviMail',
        4 => 'CiviReport',
      ),
    'enableComponentIDs' => 
      array (
        0 => '2',
        1 => '3',
        2 => '1',
        3 => '4',
        4 => '8',
      ),
    'lcMessages' => 'zh_TW',
    'inheritLocale' => '1',
    'monetaryThousandSeparator' => ',',
    'monetaryDecimalPoint' => '.',
    'moneyformat' => '%c %a',
    'moneyvalueformat' => '%!i',
    'countryLimit' => 
      array (
        0 => '1208',
      ),
    'provinceLimit' => 
      array (
        0 => '1208',
      ),
    'defaultContactCountry' => '1208',
    'defaultCurrency' => 'TWD',
    'legacyEncoding' => 'Big5',
    'customTranslateFunction' => '',
    'fieldSeparator' => ',',
    '_qf_default' => 'Localization:next',
    '_qf_Localization_next' => 'Save',
    'defaultCurrencySymbol' => 'NT$',
    'userFramework' => 'Drupal',
    'initialized' => 0,
    'DAOFactoryClass' => 'CRM_Contact_DAO_Factory',
    'inCiviCRM' => false,
    'debug' => 0,
    'backtrace' => 0,
    'resourceBase' => NULL,
    'currencySymbols' => '',
    'gettextCodeset' => 'utf-8',
    'gettextDomain' => 'civicrm',
    'userFrameworkVersion' => '6.20',
    'userFrameworkUsersTableName' => 'users',
    'userFrameworkFrontend' => false,
    'userFrameworkLogging' => false,
    'maxImportFileSize' => 1048576,
    'geocodeMethod' => '',
    'mapGeoCoding' => 1,
    'enableSSL' => false,
    'fatalErrorTemplate' => 'CRM/common/fatal.tpl',
    'fatalErrorHandler' => NULL,
    'maxLocationBlocks' => 2,
    'captchaFontPath' => '/usr/X11R6/lib/X11/fonts/',
    'captchaFont' => 'HelveticaBold.ttf',
    'doNotResetCache' => 0,
    'oldInputStyle' => 1,
    'formKeyDisable' => false,
    'mailerPeriod' => 180,
    'mailerSpoolLimit' => 0,
  ); 
  $params['componentRegistry'] = new CRM_Core_Component();

  // start saving
  $setting = new CRM_Core_BAO_Setting();
  $setting->add($params);
}
