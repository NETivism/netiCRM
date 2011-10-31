<?php
define( 'CIVICRM_UF'               , 'Drupal'        );
global $db_url;
if(is_array($db_url)){
  $dsn = str_replace("mysqli", "mysql", $db_url['default']);
}
else{
  $dsn = str_replace("mysqli", "mysql", $db_url);
}
define( 'CIVICRM_UF_DSN'           , $dsn."?new_link=true" );
define( 'CIVICRM_DSN'          , $dsn."?new_link=true" );
define('CIVICRM_LOGGING_DSN', CIVICRM_DSN);

define( 'CIVICRM_TEMPLATE_COMPILEDIR', dirname ( __FILE__ ).'/files/civicrm/templates_c/' );
define( 'CIVICRM_UF_BASEURL'      , 'http://'.$_SERVER['HTTP_HOST']);
define( 'CIVICRM_SITE_KEY', sprintf("%u", crc32(CIVICRM_UF_BASEURL)) );
define( 'CIVICRM_IDS_ENABLE', 1);
define( 'CIVICRM_DOMAIN_ID'      , 1 );
define( 'CIVICRM_DOMAIN_GROUP_ID', null );
define( 'CIVICRM_DOMAIN_ORG_ID'  , null );
define( 'CIVICRM_EVENT_PRICE_SET_DOMAIN_ID', 0 );
define( 'CIVICRM_ACTIVITY_ASSIGNEE_MAIL' , 1 ); 
define( 'CIVICRM_CONTACT_AJAX_CHECK_SIMILAR' , 1 ); 
define( 'CIVICRM_PROFILE_DOUBLE_OPTIN', 1 );
define('CIVICRM_TRACK_CIVIMAIL_REPLIES', false);

// define( 'CIVICRM_MAIL_LOG', '/var/aegir/platforms/neticrm-6.20-3.3.1-devel/sites/demo.civicrm.tw/files/civicrm/templates_c//mail.log' );
define('CIVICRM_TAG_UNCONFIRMED', 'Unconfirmed');
define('CIVICRM_PETITION_CONTACTS','Petition Contacts');

global $civicrm_root;
if(!$civicrm_root){
  $civicrm_root = getcwd()."/sites/all/modules/civicrm";
}
$include_path = '.'        . PATH_SEPARATOR .
                $civicrm_root . PATH_SEPARATOR . 
                $civicrm_root . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR .
                get_include_path( );
set_include_path( $include_path );
if ( function_exists( 'variable_get' ) && variable_get('clean_url', '0') != '0' ) {
    define( 'CIVICRM_CLEANURL', 1 );
} else {
    define( 'CIVICRM_CLEANURL', 0 );
}

// force PHP to auto-detect Mac line endings
ini_set('auto_detect_line_endings', '1');

// make sure the memory_limit is at least 64 MB
$memLimitString = trim(ini_get('memory_limit'));
$memLimitUnit   = strtolower(substr($memLimitString, -1));
$memLimit       = (int) $memLimitString;
switch ($memLimitUnit) {
    case 'g': $memLimit *= 1024;
    case 'm': $memLimit *= 1024;
    case 'k': $memLimit *= 1024;
}
if ($memLimit >= 0 and $memLimit < 67108864) {
    ini_set('memory_limit', '64M');
}

if(class_exists('Memcache')){
  define('CIVICRM_USE_MEMCACHE', 1);
  define('MEMCACHE_PREFIX', 'civi_'.CIVICRM_SITE_KEY);
}
