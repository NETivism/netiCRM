<?php
/* This file is auto generated. from templates civicrm.settings.php.tpl */
define('CIVICRM_UF', 'Drupal');
$db_url = $GLOBALS['db_url'];
if(is_array($db_url)){
  $dsn = str_replace("mysqli", "mysql", $db_url['default']);
}
else{
  $dsn = str_replace("mysqli", "mysql", $db_url);
}
define('CIVICRM_UF_DSN' , $dsn."?new_link=true");
define('CIVICRM_DSN' , $dsn."?new_link=true");
define('CIVICRM_LOGGING_DSN', CIVICRM_DSN);

define('CIVICRM_TEMPLATE_COMPILEDIR', dirname ( __FILE__ ).'/files/civicrm/templates_c/');
define('CIVICRM_SITE_KEY', '%%siteKey%%');
define('CIVICRM_IDS_ENABLE', 1);
define('CIVICRM_DOMAIN_ID', 1 );
define('CIVICRM_DOMAIN_GROUP_ID', null );
define('CIVICRM_DOMAIN_ORG_ID'  , null );
define('CIVICRM_EVENT_PRICE_SET_DOMAIN_ID', 0 );
define('CIVICRM_ACTIVITY_ASSIGNEE_MAIL' , 1 ); 
define('CIVICRM_CONTACT_AJAX_CHECK_SIMILAR' , 1 ); 
define('CIVICRM_PROFILE_DOUBLE_OPTIN', 1 );
define('CIVICRM_TRACK_CIVIMAIL_REPLIES', false);

// define( 'CIVICRM_MAIL_LOG', '/tmp/mail.log' );
define('CIVICRM_TAG_UNCONFIRMED', 'Unconfirmed');
define('CIVICRM_PETITION_CONTACTS','Petition Contacts');

// Support cli / drush installation
global $base_url;
if($base_url){
  define('CIVICRM_UF_BASEURL', $base_url);
}
else{
  define('CIVICRM_UF_BASEURL', 'http://'.$_SERVER['HTTP_HOST']);
}

$civi_root = !empty($GLOBALS['civicrm_root']) ? $GLOBALS['civicrm_root'] : '';
global $civicrm_root;
if(!$civi_root){
  $civicrm_root = getcwd()."/sites/all/modules/civicrm";
}
else{
  $civicrm_root = $civi_root;
}
$include_path = '.' . PATH_SEPARATOR .
                $civicrm_root . PATH_SEPARATOR . 
                $civicrm_root . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR .
                get_include_path( );
set_include_path( $include_path );

if ( function_exists( 'variable_get' ) && variable_get('clean_url', '0') != '0' ) {
  define('CIVICRM_CLEANURL', 1);
}
else {
  define('CIVICRM_CLEANURL', 0);
}

date_default_timezone_set('Asia/Taipei');
