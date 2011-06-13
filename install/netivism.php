<?php

function neticrm_run_install(){
  civicrm_initialize( );
  neticrm_domain_set_default();
  neticrm_enable_custom_modules();
  neticrm_disable_civicrm_blocks();
  neticrm_sql_update_1000();
}

function neticrm_enable_custom_modules(){
  module_rebuild_cache( );
  // now enable civicrm related module.
  module_enable(_neticrm_custom_modules());
  // clear block and page cache, to make sure civicrm link is present in navigation block
  cache_clear_all();
}
function _neticrm_custom_modules(){
  return array('civicrm_ckeditor','civicrm_imce','civicrm_twaddress','civicrm_alter_translation', 'civicrm_legalid', 'civicrm_dmenu');
}
function neticrm_disable_civicrm_blocks(){
  install_include(array('block'));
  install_set_block('civicrm', 2, 'neticrm', 'left', -10, 0, "civicrm\ncivicrm/dashboard\nadmin\nadmin/*");

  install_disable_block('civicrm', 1, 'neticrm');
  install_disable_block('civicrm', 3, 'neticrm');
  install_disable_block('civicrm', 4, 'neticrm');
  install_disable_block('civicrm', 5, 'neticrm');
}

function neticrm_domain_set_default(){
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
    'customTranslateFunction' => 'civicrm_alter_translation',
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

  // trick to update correct userFrameworkResourceURL
  if(function_exists('d')){
    $site = basename(d()->site_path);
    $path = 'http://'.$site.'/sites/all/modules/civicrm';
    require_once "CRM/Report/DAO/Instance.php";
    $sql = "UPDATE civicrm_option_value SET value = '$path' WHERE name = 'userFrameworkResourceURL'";
    CRM_Core_DAO::executeQuery($sql);
  }
}

function neticrm_source($fileName, $lineMode = false ) {
  global $crmPath, $sqlPath;
  if($sqlPath){
    $fileName = $sqlPath.'/'.$fileName;
    require_once "CRM/Report/DAO/Instance.php";
    require_once "packages/DB.php";

    if ( ! $lineMode ) {
      $string = file_get_contents( $fileName );
      // change \r\n to fix windows issues
      $string = str_replace("\r\n", "\n", $string );
      //get rid of comments starting with # and --
      $string = preg_replace("/^#[^\n]*$/m",   "\n", $string );
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string );

      $queries  = preg_split('/;$/m', $string);
      foreach ( $queries as $query ) {
        $query = trim( $query );
        if ( ! empty( $query ) ) {
          $res =& CRM_Core_DAO::executeQuery($query);
          if ( PEAR::isError( $res ) ) {
            die( "Cannot execute $query: " . $res->getMessage( ) );
          }
        }
      }
    }
    else {
      $fd = fopen( $fileName, "r" );
      while ( $string = fgets( $fd ) ) {
        $string = preg_replace("/^#[^\n]*$/m",   "\n", $string );
        $string = preg_replace("/^(--[^-]).*/m", "\n", $string );

        $string = trim( $string );
        if ( ! empty( $string ) ) {
          $res =& $db->query( $string );
          $res =& CRM_Core_DAO::executeQuery($string);
          if ( PEAR::isError( $res ) ) {
            die( "Cannot execute $string: " . $res->getMessage( ) );
          }
        }
      }
    }
  }
}

function neticrm_sql_update_1000(){
  neticrm_source('neticrm_1000.mysql');
}
