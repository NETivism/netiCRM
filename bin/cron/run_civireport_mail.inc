<?php
function run_civireport_mail(){
  date_default_timezone_set("Asia/Taipei");

  set_time_limit(1800);

  //log the execution of script
  CRM_Core_Error::debug_log_message('Execute CiviReportMail.php');

  // if there are named sets of settings, use them - otherwise use the default (null)
  CiviReportMail::processReport();
}

/**
 * A PHP cron script to mail the result set of specified report to the  
 * recipients mentioned for that report  
 */
class CiviReportMail { 
  static public function processReport( ) {
        $sendmail     = CRM_Utils_Request::retrieve( 'sendmail', 'Boolean', 
                                                     CRM_Core_DAO::$_nullObject, true, null, 'REQUEST' );
        $instanceId   = CRM_Utils_Request::retrieve( 'instanceId', 'Positive', 
                                                     CRM_Core_DAO::$_nullObject, true, null, 'REQUEST' );
        $resetVal     = CRM_Utils_Request::retrieve( 'reset', 'Positive',
                                                     CRM_Core_DAO::$_nullObject, true, null, 'REQUEST' );
        $optionVal    = CRM_Report_Utils_Report::getValueFromUrl( $instanceId );
        
        echo "Report Mail Triggered...<br />";
        require_once 'CRM/Core/OptionGroup.php';
        $templateInfo = CRM_Core_OptionGroup::getRowValues( 'report_template', $optionVal, 'value' );
        $obj = new CRM_Report_Page_Instance();            
        if ( strstr($templateInfo['name'], '_Form') ) {
            $instanceInfo = array( );
            CRM_Report_BAO_Instance::retrieve( array('id' => $instanceId), $instanceInfo );
            
            if ( ! empty($instanceInfo['title']) ) {
                $obj->assign( 'reportTitle', $instanceInfo['title'] );
            } else {
                $obj->assign( 'reportTitle', $templateInfo['label'] );
            }
            
            $wrapper = new CRM_Utils_Wrapper( );
            $arguments['urlToSession'] = array( array( 'urlVar'     => 'instanceId',
                                                       'type'       => 'Positive',
                                                       'sessionVar' => 'instanceId',
                                                       'default'    => 'null' ) );
            return $wrapper->run( $templateInfo['name'], null, $arguments );
        }
    }
}

