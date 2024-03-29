<?php

/**
 * Update membership status
 */
function run_membership_status_update(){
  date_default_timezone_set("Asia/Taipei");
  $obj = new CRM_UpdateMembershipRecord( );
  $obj->updateMembershipStatus( );
}
class CRM_UpdateMembershipRecord {
    
    function __construct() 
    {
        //log the execution time of script
        CRM_Core_Error::debug_log_message( 'UpdateMembershipRecord.php' );
    }


    public function updateMembershipStatus( )
    {
        require_once 'CRM/Member/BAO/MembershipLog.php';
        require_once 'CRM/Member/BAO/Membership.php';
        require_once 'CRM/Core/BAO/MessageTemplates.php';
        require_once 'CRM/Member/BAO/MembershipType.php';
        require_once 'CRM/Utils/Date.php';
        require_once 'CRM/Utils/System.php';
        require_once 'CRM/Member/PseudoConstant.php';
        require_once 'CRM/Contact/BAO/Contact.php';
        require_once 'CRM/Activity/BAO/Activity.php';

        //get all active statuses of membership, CRM-3984
        $allStatus    = CRM_Member_PseudoConstant::membershipStatus( );
        $statusLabels = CRM_Member_PseudoConstant::membershipStatus( null, null, 'label' );
        $allTypes     = CRM_Member_PseudoConstant::membershipType( );
        
        $query = "
SELECT civicrm_membership.id                 as membership_id,
       civicrm_membership.is_override        as is_override,
       civicrm_membership.reminder_date      as reminder_date,
       civicrm_membership.membership_type_id as membership_type_id,
       civicrm_membership.status_id          as status_id,
       civicrm_membership.join_date          as join_date,
       civicrm_membership.start_date         as start_date,
       civicrm_membership.end_date           as end_date,
       civicrm_membership.source             as source,
       civicrm_contact.id                    as contact_id,
       civicrm_contact.is_deceased           as is_deceased,
       civicrm_membership.owner_membership_id as owner_membership_id
FROM   civicrm_membership, civicrm_contact
WHERE  civicrm_membership.contact_id = civicrm_contact.id
AND    civicrm_membership.is_test = 0
";
        $params = array( );
        $dao =& CRM_Core_DAO::executeQuery( $query, $params );
        
        $today = date( "Y-m-d" );
        $count = 0;

        require_once 'CRM/Core/Smarty.php';
        $smarty =& CRM_Core_Smarty::singleton();
        
        require_once 'CRM/Core/BAO/Domain.php';
        $domainValues     = CRM_Core_BAO_Domain::getNameAndEmail( );
        $fromEmailAddress = "$domainValues[0] <$domainValues[1]>";

        while ( $dao->fetch( ) ) {
            echo ".";
            
            /**
            $count++;
            echo $dao->contact_id . ', '. CRM_Utils_System::memory( ) . "<p>\n";

            CRM_Core_Error::debug( 'fBegin', count( $GLOBALS['_DB_DATAOBJECT']['RESULTS'] ) );
            if ( $count > 2 ) {
                foreach ( $GLOBALS['_DB_DATAOBJECT']['RESULTS'] as $r ) {
                    CRM_Core_Error::debug( 'r', $r->query );
                }
                // CRM_Core_Error::debug( 'f', $GLOBALS['_DB_DATAOBJECT']['RESULTS'] );
                exit( );
            }
            **/

            // Put common parameters into array for easy access
            $memberParams = array( 'id'                 => $dao->membership_id,
                                   'status_id'          => $dao->status_id,
                                   'contact_id'         => $dao->contact_id,
                                   'membership_type_id' => $dao->membership_type_id,
                                   'membership_type'    => $allTypes[$dao->membership_type_id],
                                   'join_date'          => $dao->join_date,
                                   'start_date'         => $dao->start_date,
                                   'end_date'           => $dao->end_date,
                                   'reminder_date'      => $dao->reminder_date,
                                   'source'             => $dao->source,
                                   'skipStatusCal'      => true,
                                   'skipRecentView'     => true );
            
            $smarty->assign_by_ref('memberParams', $memberParams);

            //update membership record to Deceased if contact is deceased
            if ( $dao->is_deceased ) { 
                // check for 'Deceased' membership status, CRM-5636
                $deceaseStatusId = array_search( 'Deceased', $allStatus );
                if ( !$deceaseStatusId ) {
                    CRM_Core_Error::fatal( ts( "Deceased Membership status is missing or not active. <a href='%1'>Click here to check</a>.", array( 1 => CRM_Utils_System::url( 'civicrm/admin/member/membershipStatus', 'reset=1' ) ) ) );
                }
                
                //process only when status change.
                if ( $dao->status_id != $deceaseStatusId ) {
                    //since there is change in status.
                    $statusChange = array( 'status_id' => $deceaseStatusId );
                    $smarty->append_by_ref('memberParams', $statusChange, true );
                    
                    //process membership record.
                    //take all params that need to save.
                    $deceasedMembership =  $memberParams;
                    unset($deceasedMembership['membership_type']);
                    $deceasedMembership['version'] = 3;
                    $deceasedMembership['status_id'] = $deceaseStatusId; 
                    $deceasedMembership['createActivity'] = TRUE;

                    civicrm_api('membership', 'create', $deceasedMembership);
                }
                continue;
            }
            
            //we fetch related, since we need to check for deceased 
            //now further processing is handle w/ main membership record. 
            if ( $dao->owner_membership_id ) continue;
            
            //update membership records where status is NOT - Pending OR Cancelled.
            //as well as membership is not override.
            //skipping Expired membership records -> reduced extra processing( kiran ) 
            if ( !$dao->is_override &&
                 !in_array( $dao->status_id, array( array_search( 'Pending', $allStatus ),
                                                    array_search( 'Cancelled', $allStatus ),
                                                    array_search( 'Expired', $allStatus ) ) ) ) {
                
                //get the membership status as per id.
                $newStatus = civicrm_api('membership_status', 'calc',
                  array(
                    'version' => 3,
                    'membership_id' => $dao->membership_id,
                    'ignore_admin_only' => FALSE,
                  )
                );
                $statusId = $newStatus['id'] ? $newStatus['id'] : NULL;
                
                //process only when status change.
                if ( $statusId && 
                     $statusId != $dao->status_id ) {
                    //since there is change in status.
                    $statusChange = array( 'status_id' => $statusId );
                    $smarty->append_by_ref('memberParams', $statusChange, true );
                    
                    //process member record.
                    //take all params that need to save.
                    $memParams = $memberParams;
                    unset($memParams['membership_type']);
                    $memParams['version'] = 3;
                    $memParams['status_id'] = $statusId;
                    $memParams['createActivity'] = TRUE; 
                    civicrm_api('membership', 'create', $memParams);
                }
            }
            
            //convert date from string format to timestamp format
            $reminder_date = CRM_Utils_DATE::unixTime( $dao->reminder_date );
            $today_date    = CRM_Utils_DATE::unixTime( $today );
            
            //send reminder for membership renewal
            $current_member = CRM_Member_PseudoConstant::membershipStatus(NULL, 'is_current_member = 1');
            if ( $dao->reminder_date &&
                 $dao->reminder_date != '0000-00-00' &&
                 ( $reminder_date <= $today_date ) && 
                 !empty($current_member[$dao->status_id])
                ) {
                $memType = new CRM_Member_BAO_MembershipType( );
                
                $memType->id = $dao->membership_type_id;
                if ( $memType->find( true ) &&
                     $memType->renewal_msg_id ) {
                    $toEmail  = CRM_Contact_BAO_Contact::getPrimaryEmail( $dao->contact_id );
                    
                    if ( $toEmail ) {
                        $result = CRM_Core_BAO_MessageTemplates::sendReminder( $dao->contact_id,
                                                                               $toEmail,
                                                                               $memType->renewal_msg_id,
                                                                               $fromEmailAddress );
                        if ( ! $result ||
                             is_a( $result, 'PEAR_Error' ) ) {
                            // we could not send an email, for now we ignore
                            // CRM-3406
                            // at some point we might decide to do something
                        }
                        
                        //set membership reminder date to NULL since we've sent the reminder.
                        CRM_Core_DAO::setFieldValue( 'CRM_Member_DAO_Membership', $dao->membership_id, 'reminder_date', 'null');
                        
                        // insert the activity log record.
                        $activityParams = array( );
                        $activityParams['subject']            = $allTypes[$dao->membership_type_id] . 
                            ": Status - " . $statusLabels[$newStatus['id']] . 
                            ", End Date - " . CRM_Utils_Date::customFormat(CRM_Utils_Date::isoToMysql($dao->end_date), $config->dateformatFull);
                        $activityParams['source_record_id']   = $dao->membership_id; 
                        $activityParams['source_contact_id']  = $dao->contact_id; 
                        $activityParams['activity_date_time'] = date('YmdHis');

                        static $actRelIds = array( );
                        if ( ! isset($actRelIds['activity_type_id']) ) {
                            $actRelIds['activity_type_id']    = 
                                CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                                'Membership Renewal Reminder', 'name' );
                        }
                        $activityParams['activity_type_id']   = $actRelIds['activity_type_id'];
                        
                        if ( ! isset($actRelIds['activity_status_id']) ) {
                            $actRelIds['activity_status_id']  = 
                                CRM_Core_OptionGroup::getValue( 'activity_status', 'Completed', 'name' );
                        }
                        $activityParams['status_id']          = $actRelIds['activity_status_id'];
                        
                        static $msgTpl = array();
                        if ( ! isset($msgTpl[$memType->renewal_msg_id]) ) {
                            $msgTpl[$memType->renewal_msg_id] = array( );
                            
                            $messageTemplate = new CRM_Core_DAO_MessageTemplates( );
                            $messageTemplate->id = $memType->renewal_msg_id;
                            if ( $messageTemplate->find(true) ) {
                                $msgTpl[$memType->renewal_msg_id]['subject'] = $messageTemplate->msg_subject;
                                $msgTpl[$memType->renewal_msg_id]['details'] = $messageTemplate->msg_text;
                            }
                            $messageTemplate->free( );
                        }
                        $activityParams['details'] = "Subject: {$msgTpl[$memType->renewal_msg_id]['subject']}
Message: {$msgTpl[$memType->renewal_msg_id]['details']}
";
                        $activity = CRM_Activity_BAO_Activity::create( $activityParams );
                    }
                }
                $memType->free( );
                
            }
            // CRM_Core_Error::debug( 'fEnd', count( $GLOBALS['_DB_DATAOBJECT']['RESULTS'] ) );
        }
    }
}
