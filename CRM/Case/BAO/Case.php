<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Case/DAO/Case.php';
require_once 'CRM/Case/PseudoConstant.php';

/**
 * This class contains the funtions for Case Management
 *
 */
class CRM_Case_BAO_Case extends CRM_Case_DAO_Case
{
  
    /**
     * static field for all the case information that we can potentially export
     *
     * @var array
     * @static
     */
    static $_exportableFields = null;

    /**  
     * value seletor for multi-select
     **/ 
   
    const VALUE_SEPERATOR = "";
    
    function __construct()
    {
        parent::__construct();
    }

    /**
     * takes an associative array and creates a case object
     *
     * the function extract all the params it needs to initialize the create a
     * case object. the params array could contain additional unused name/value
     * pairs
     *
     * @param array  $params (reference ) an assoc array of name/value pairs
     * @param array $ids    the array that holds all the db ids
     *
     * @return object CRM_Case_BAO_Case object
     * @access public
     * @static
     */
    static function add( &$params ) 
    {
        $caseDAO =& new CRM_Case_DAO_Case();
        $caseDAO->copyValues($params);
        return $caseDAO->save();
    }

    /**
     * Given the list of params in the params array, fetch the object
     * and store the values in the values array
     *
     * @param array $params input parameters to find object
     * @param array $values output values of the object
     * @param array $ids    the array that holds all the db ids
     *
     * @return CRM_Case_BAO_Case|null the found object or null
     * @access public
     * @static
     */
    static function &getValues( &$params, &$values, &$ids ) 
    {
        $case =& new CRM_Case_BAO_Case( );

        $case->copyValues( $params );
        
        if ( $case->find(true) ) {
            $ids['case']    = $case->id;
            CRM_Core_DAO::storeValues( $case, $values );
            return $case;
        }
        return null;
    }

    /**
     * takes an associative array and creates a case object
     *
     * @param array $params (reference ) an assoc array of name/value pairs
     * @param array $ids    the array that holds all the db ids
     *
     * @return object CRM_Case_BAO_Case object 
     * @access public
     * @static
     */
    static function &create( &$params ) 
    {
        require_once 'CRM/Core/Transaction.php';
        $transaction = new CRM_Core_Transaction( ); 
        
        $case = self::add( $params );

        if ( is_a( $case, 'CRM_Core_Error') ) {
            $transaction->rollback( );
            return $case;
        }
        $transaction->commit( );
                
        //we are not creating log for case
        //since case log can be tracked using log for activity.
        return $case;
    }

    /**
     * Create case contact record
     *
     * @param array    case_id, contact_id
     *
     * @return object
     * @access public
     */
    function addCaseToContact( $params ) 
    {
        require_once 'CRM/Case/DAO/CaseContact.php';
        $caseContact =& new CRM_Case_DAO_CaseContact();
        $caseContact->case_id = $params['case_id'];
        $caseContact->contact_id = $params['contact_id'];
        $caseContact->find(true);
        $caseContact->save();

        // add to recently viewed    
        require_once 'CRM/Utils/Recent.php';
        require_once 'CRM/Case/PseudoConstant.php';
        require_once 'CRM/Contact/BAO/Contact.php';
        $caseType = CRM_Case_PseudoConstant::caseTypeName( $caseContact->case_id, 'label' );
        $url = CRM_Utils_System::url( 'civicrm/contact/view/case', 
                                      "action=view&reset=1&id={$caseContact->case_id}&cid={$caseContact->contact_id}&context=home" );
        
        $title = CRM_Contact_BAO_Contact::displayName( $caseContact->contact_id ) . ' - ' . $caseType['name'];
        
        // add the recently created case
        CRM_Utils_Recent::add( $title,
                               $url,
                               $caseContact->case_id,
                               'Case',
                               $params['contact_id'],
                               null
                               );
        
        return $caseContact;
    }

    /**
     * Delet case contact record
     *
     * @param int    case_id
     *
     * @return Void
     * @access public
     */
    function deleteCaseContact( $caseID ) 
    {
        require_once 'CRM/Case/DAO/CaseContact.php';
        $caseContact =& new CRM_Case_DAO_CaseContact();
        $caseContact->case_id = $caseID;
        $caseContact->delete();
        
        // delete the recently created Case
        require_once 'CRM/Utils/Recent.php';
        $caseRecent = array(
                            'id'   => $caseID,
                            'type' => 'Case'
                            );
        CRM_Utils_Recent::del( $caseRecent );
    }

    /**
     * This function is used to convert associative array names to values
     * and vice-versa.
     *
     * This function is used by both the web form layer and the api. Note that
     * the api needs the name => value conversion, also the view layer typically
     * requires value => name conversion
     */
    static function lookupValue(&$defaults, $property, &$lookup, $reverse)
    {
        $id = $property . '_id';

        $src = $reverse ? $property : $id;
        $dst = $reverse ? $id       : $property;

        if (!array_key_exists($src, $defaults)) {
            return false;
        }

        $look = $reverse ? array_flip($lookup) : $lookup;
        
        if(is_array($look)) {
            if (!array_key_exists($defaults[$src], $look)) {
                return false;
            }
        }
        $defaults[$dst] = $look[$defaults[$src]];
        return true;
    }

    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. We'll tweak this function to be more
     * full featured over a period of time. This is the inverse function of
     * create.  It also stores all the retrieved values in the default array
     *
     * @param array $params   (reference ) an assoc array of name/value pairs
     * @param array $defaults (reference ) an assoc array to hold the name / value pairs
     *                        in a hierarchical manner
     * @param array $ids      (reference) the array that holds all the db ids
     *
     * @return object CRM_Case_BAO_Case object
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults, &$ids ) 
    {
        $case = CRM_Case_BAO_Case::getValues( $params, $defaults, $ids );
        return $case;
    }

    /**
     * Function to process case activity add/delete
     * takes an associative array and
     *
     * @param array $params (reference ) an assoc array of name/value pairs
     *
     * @access public
     * @static
     */
    static function processCaseActivity( &$params ) 
    {
        require_once 'CRM/Case/DAO/CaseActivity.php';
        $caseActivityDAO =& new CRM_Case_DAO_CaseActivity();
        $caseActivityDAO->activity_id = $params['activity_id'];
        $caseActivityDAO->case_id = $params['case_id'];

        $caseActivityDAO->find( true );
        $caseActivityDAO->save();
    } 

    /**
     * Function to get the case subject for Activity
     *
     * @param int $activityId  activity id
     * @return  case subject or null
     * @access public
     * @static
     */
    static function getCaseSubject ( $activityId )
    {
        require_once 'CRM/Case/DAO/CaseActivity.php';
        $caseActivity =  new CRM_Case_DAO_CaseActivity();
        $caseActivity->activity_id = $activityId;
        if ( $caseActivity->find(true) ) {
            return CRM_Core_DAO::getFieldValue('CRM_Case_BAO_Case', $caseActivity->case_id,'subject' );
        }
        return null;
    }

    /**                                                           
     * Delete the record that are associated with this case 
     * record are deleted from case 
     * @param  int  $caseId id of the case to delete
     * 
     * @return void
     * @access public 
     * @static 
     */ 
    static function deleteCase( $caseId , $moveToTrash = false ) 
    {
        //delete activities
        $activities = self::getCaseActivityDates( $caseId );
        if ( $activities ) {
            require_once"CRM/Activity/BAO/Activity.php";
            foreach( $activities as $value ) {
                CRM_Activity_BAO_Activity::deleteActivity( $value, $moveToTrash );
            }
        }  
        
        if ( ! $moveToTrash ) {
            require_once 'CRM/Core/Transaction.php';
            $transaction = new CRM_Core_Transaction( );
        }
        require_once 'CRM/Case/DAO/Case.php';
        $case     = & new CRM_Case_DAO_Case( );
        $case->id = $caseId; 
        if ( ! $moveToTrash ) {  
            $result = $case->delete( );
            $transaction->commit( );
        } else {
            $result = $case->is_deleted = 1;
            $case->save( );
        }
        
        if ( $result ) {
            // remove case from recent items.
            $caseRecent = array(
                                'id'   => $caseId,
                                'type' => 'Case'
                        );
            require_once 'CRM/Utils/Recent.php';
            CRM_Utils_Recent::del( $caseRecent );
            return true;
        }
        
        return false;
    }

    /**                                                           
     * Delete the activities related to case
     * @param  int  $activityId id of the activity
     * 
     * @return void
     * @access public 
     * @static 
     */ 
    static function deleteCaseActivity( $activityId ) 
    {
        require_once 'CRM/Case/DAO/CaseActivity.php';
        $case              = & new CRM_Case_DAO_CaseActivity( );
        $case->activity_id = $activityId; 
        $case->delete( );
    }

    /** 
     * Retrieve contact_id by case_id
     *
     * @param int    $caseId  ID of the case
     * 
     * @return array
     * @access public
     * 
     */
    function retrieveContactIdsByCaseId( $caseId , $contactID = null ) 
    {
         require_once 'CRM/Case/DAO/CaseContact.php';
         $caseContact =   & new CRM_Case_DAO_CaseContact( );
         $caseContact->case_id = $caseId;
         $caseContact->find();
         $contactArray = array();
         $count = 1;
         while ( $caseContact->fetch( ) ) {
             if ( $contactID != $caseContact->contact_id ) {
                 $contactArray[$count] = $caseContact->contact_id;
                 $count++;
             }
         }
         
         return $contactArray;
     }
    
    /**
     * Retrieve contact names by caseId
     *
     * @param int    $caseId  ID of the case
     * 
     * @return array
     * 
     * @access public
     * 
     */
     static function getcontactNames( $caseId ) 
    {
        $queryParam = array();
        $query = "
                  SELECT contact_a.sort_name name, contact_a.display_name as display_name, contact_a.id cid, ce.email as email, cp.phone as phone
                  FROM civicrm_contact contact_a 
                  LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
                  LEFT JOIN civicrm_email ce ON ( ce.contact_id = contact_a.id AND ce.is_primary = 1)
                  LEFT JOIN civicrm_phone cp ON ( cp.contact_id = contact_a.id AND cp.is_primary = 1)
                  WHERE civicrm_case_contact.case_id = {$caseId}";

            $dao = CRM_Core_DAO::executeQuery($query,$queryParam);
            $contactNames = array();
            while ( $dao->fetch() ) {
                $contactNames['contact_id']   =  $dao->cid;
                $contactNames['sort_name']    =  $dao->name;
                $contactNames['display_name'] =  $dao->display_name;
                $contactNames['email']        =  $dao->email;
                $contactNames['phone']        =  $dao->phone;
                $contactNames['role']         =  ts('Client');
            }
            return $contactNames;
    }
     
    /** 
     * Retrieve case_id by contact_id
     *
     * @param int     $contactId      ID of the contact
     * @param boolean $includeDeleted include the deleted cases in result
     * @return array
     * 
     * @access public
     * 
     */
     function retrieveCaseIdsByContactId( $contactID, $includeDeleted = false ) 
     {
         $query = "
SELECT ca.id as id
FROM civicrm_case_contact cc
INNER JOIN civicrm_case ca ON cc.case_id = ca.id
WHERE cc.contact_id = %1
";
         if (!$includeDeleted) {
             $query .= " AND ca.is_deleted = 0";
         }
         
         $params = array( 1 => array( $contactID, 'Integer' ) );
         $dao = CRM_Core_DAO::executeQuery( $query, $params ); 

         $caseArray = array( );
         while ( $dao->fetch( ) ) {
             $caseArray[] = $dao->id;
         }
         
         $dao->free( );
         return $caseArray;
     }

    function getCaseActivityQuery( $type = 'upcoming', $userID = null, $condition = null, $isDeleted = 0 ) 
    {
        if ( !$userID ) {
            $session =& CRM_Core_Session::singleton( );
            $userID = $session->get( 'userID' );
        }
        
        $actStatus         = array_flip( CRM_Core_PseudoConstant::activityStatus('name') );
        $scheduledStatusId = $actStatus['Scheduled'];
        
        $query = "SELECT
                  civicrm_case.id as case_id,
                  civicrm_contact.id as contact_id,
                  civicrm_contact.sort_name as sort_name,
                  civicrm_phone.phone as phone,
                  civicrm_contact.contact_type as contact_type,
                  civicrm_activity.activity_type_id,
                  cov_type.label as case_type,
                  cov_type.name as case_type_name,
                  cov_status.label as case_status,
                  cov_status.label as case_status_name,
                  civicrm_activity.status_id,
                  case_relation_type.label_b_a as case_role, ";
        if ( $type == 'upcoming' ) {
            $query .=  " civicrm_activity.activity_date_time as case_scheduled_activity_date,
                         civicrm_activity.id as case_scheduled_activity_id,
                         aov.name as case_scheduled_activity_type_name,
                         aov.label as case_scheduled_activity_type ";       
        } else if ( $type == 'recent' ) {
            $query .=  " civicrm_activity.activity_date_time as case_recent_activity_date,
                         civicrm_activity.id as case_recent_activity_id,
                         aov.name as case_recent_activity_type_name,
                         aov.label as case_recent_activity_type ";
        } 
        
        $query .= 
            " FROM civicrm_case
                  INNER JOIN civicrm_case_activity
                        ON civicrm_case_activity.case_id = civicrm_case.id  
            
                  LEFT JOIN civicrm_case_contact ON civicrm_case.id = civicrm_case_contact.case_id
                  LEFT JOIN civicrm_contact ON civicrm_case_contact.contact_id = civicrm_contact.id
                  LEFT JOIN civicrm_phone ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary=1) ";

        if ( $type == 'upcoming' ) {
            $query .= " LEFT JOIN civicrm_activity
                             ON ( civicrm_case_activity.activity_id = civicrm_activity.id
                                  AND civicrm_activity.is_current_revision = 1
                                  AND civicrm_activity.status_id = $scheduledStatusId
                                  AND civicrm_activity.activity_date_time <= DATE_ADD( NOW(), INTERVAL 14 DAY ) ) ";
        } else if ( $type == 'recent' ) {
            $query .= " LEFT JOIN civicrm_activity
                             ON ( civicrm_case_activity.activity_id = civicrm_activity.id
                                  AND civicrm_activity.is_current_revision = 1
                                  AND civicrm_activity.status_id != $scheduledStatusId
                                  AND civicrm_activity.activity_date_time <= NOW() 
                                  AND civicrm_activity.activity_date_time >= DATE_SUB( NOW(), INTERVAL 14 DAY ) ) ";
        }
               
        $query .= "
                  LEFT JOIN civicrm_option_group aog  ON aog.name = 'activity_type'
                  LEFT JOIN civicrm_option_value aov
                        ON ( civicrm_activity.activity_type_id = aov.value
                             AND aog.id = aov.option_group_id )         

                  LEFT  JOIN  civicrm_relationship case_relationship 
                        ON ( case_relationship.contact_id_a = civicrm_case_contact.contact_id 
                             AND case_relationship.contact_id_b = {$userID}  
                             AND case_relationship.case_id = civicrm_case.id )
     
                  LEFT  JOIN civicrm_relationship_type case_relation_type 
                        ON ( case_relation_type.id = case_relationship.relationship_type_id 
                             AND case_relation_type.id = case_relationship.relationship_type_id )

                  LEFT JOIN civicrm_option_group cog_type ON cog_type.name = 'case_type'
                  LEFT JOIN civicrm_option_value cov_type
                        ON ( civicrm_case.case_type_id = cov_type.value
                             AND cog_type.id = cov_type.option_group_id )

                  LEFT JOIN civicrm_option_group cog_status ON cog_status.name = 'case_status'
                  LEFT JOIN civicrm_option_value cov_status 
                       ON ( civicrm_case.status_id = cov_status.value
                            AND cog_status.id = cov_status.option_group_id ) ";

        $query .= "
                  LEFT JOIN civicrm_activity ca2
                             ON ( ca2.id IN ( SELECT cca.activity_id FROM civicrm_case_activity cca 
                                              WHERE cca.case_id = civicrm_case.id )
                                  AND ca2.is_current_revision = 1 
                                  AND ca2.is_deleted = $isDeleted ";
        
        if ( $type == 'upcoming' ) {
            $query .= "AND ca2.status_id = $scheduledStatusId
                       AND ca2.activity_date_time <= DATE_ADD( NOW(), INTERVAL 14 DAY ) 
                       AND civicrm_activity.activity_date_time > ca2.activity_date_time )";
        } else if ( $type == 'recent' ) {
            $query .= "AND ca2.status_id != $scheduledStatusId
                       AND ca2.activity_date_time <= NOW() 
                       AND ca2.activity_date_time >= DATE_SUB( NOW(), INTERVAL 14 DAY )
                       AND civicrm_activity.activity_date_time < ca2.activity_date_time )";
        }
        
        $query .= " WHERE ca2.id IS NULL";

        if ( $condition ) {
            $query .= $condition;
        }

        if ( $type == 'upcoming' ) {
            $query .=" ORDER BY case_scheduled_activity_date ASC ";
        } else if ( $type == 'recent' ) {
            $query .= " ORDER BY case_recent_activity_date ASC ";
        }

        return $query;
    }

    /**
     * Retrieve cases related to particular contact or whole contact
     * used in Dashboad and Tab
     *
     * @param boolean    $allCases  
     * 
     * @param int        $userID 
     *
     * @param String     $type /upcoming,recent,all/ 
     *
     * @return array     Array of Cases
     * 
     * @access public
     * 
     */
    function getCases( $allCases = true, $userID = null, $type = 'upcoming' )
    {
        $condition = null;
       
        if ( !$allCases ) {
            $condition = " AND case_relationship.contact_id_b = {$userID}";
        }

        $condition .= " 
AND civicrm_activity.is_deleted = 0
AND civicrm_case.is_deleted     = 0";
        
        if ( $type == 'upcoming' ) {
            $closedId    = CRM_Core_OptionGroup::getValue( 'case_status', 'Closed', 'name' );
            $condition .= "
AND civicrm_case.status_id != $closedId";
        }
        
        $query = self::getCaseActivityQuery( $type, $userID, $condition );
 
        $queryParams = array();
        $result = CRM_Core_DAO::executeQuery( $query,$queryParams );

        require_once 'CRM/Core/OptionGroup.php';
        $caseStatus = CRM_Core_OptionGroup::values( 'case_status', false, false, false, " AND v.name = 'Urgent' " );

        $resultFields = array( 'contact_id',
                               'contact_type',
                               'sort_name',
                               'phone',
                               'case_id',
                               'case_type',
                               'case_type_name',
                               'status_id',
                               'case_status',
                               'case_status_name',
                               'activity_type_id',
                               'case_role', 
                               );

        if ( $type == 'upcoming' ) {
            $resultFields[] = 'case_scheduled_activity_date';
            $resultFields[] = 'case_scheduled_activity_type_name';
            $resultFields[] = 'case_scheduled_activity_type';
            $resultFields[] = 'case_scheduled_activity_id';
        } else if ( $type == 'recent' ) {
            $resultFields[] = 'case_recent_activity_date';
            $resultFields[] = 'case_recent_activity_type_name';
            $resultFields[] = 'case_recent_activity_type';
            $resultFields[] = 'case_recent_activity_id';
        }

        // we're going to use the usual actions, so doesn't make sense to duplicate definitions
        require_once( 'CRM/Case/Selector/Search.php');
        $actions = CRM_Case_Selector_Search::links();

        require_once "CRM/Contact/BAO/Contact/Utils.php";
        $casesList = array( );
        // check is the user has view/edit signer permission
        $permissions = array( CRM_Core_Permission::VIEW );
        if ( CRM_Core_Permission::check( 'edit cases' ) ) {
            $permissions[] = CRM_Core_Permission::EDIT;
        }
        if ( CRM_Core_Permission::check( 'delete in CiviCase' ) ) {
            $permissions[] = CRM_Core_Permission::DELETE;
        }
        $mask = CRM_Core_Action::mask( $permissions );

        while ( $result->fetch() ) {
            foreach( $resultFields as $donCare => $field ) {
                $casesList[$result->case_id][$field] = $result->$field;
                if( $field == 'contact_type' ) {
                    $casesList[$result->case_id]['contact_type_icon'] 
                        = CRM_Contact_BAO_Contact_Utils::getImage( $result->contact_sub_type ? 
                                                                   $result->contact_sub_type : $result->contact_type );
                    $casesList[$result->case_id]['action'] 
                        = CRM_Core_Action::formLink( $actions, $mask,
                                                     array( 'id'  => $result->case_id,
                                                            'cid' => $result->contact_id,
                                                            'cxt' => 'dashboard' ) );
                } elseif ( $field == 'case_status' ) {  
                    if ( in_array($result->$field, $caseStatus) ) {
                        $casesList[$result->case_id]['class'] = "status-urgent";
                    }else {
                        $casesList[$result->case_id]['class'] = "status-normal";
                    }
                }
            }
            //CRM-4510.
            $caseManagerContact = self::getCaseManagerContact( $result->case_type_name, $result->case_id );
            if ( !empty($caseManagerContact) ) {
                $casesList[$result->case_id]['casemanager_id'] = CRM_Utils_Array::value('casemanager_id', $caseManagerContact );
                $casesList[$result->case_id]['casemanager'   ] = CRM_Utils_Array::value('casemanager'   , $caseManagerContact );              
            } 
        }
        
        return $casesList;        
    }

    /**
     * Function to get the summary of cases counts by type and status.
     */
    function getCasesSummary( $allCases = true, $userID )
    {
        require_once 'CRM/Core/OptionGroup.php';
        $caseStatuses = CRM_Core_OptionGroup::values( 'case_status' );
        $caseTypes    = CRM_Core_OptionGroup::values( 'case_type' );
        $caseTypes    = array_flip( $caseTypes );  
     
        // get statuses as headers for the table
         $url =  CRM_Utils_System::url( 'civicrm/case/search',"reset=1&force=1&all=1&status=" ) ;
         foreach( $caseStatuses as $key => $name ) {
             $caseSummary['headers'][$key]['status'] = $name; 
             $caseSummary['headers'][$key]['url']    = $url.$key; 
         }
               
        // build rows with actual data
        $rows = array();
        $myGroupByClause = $mySelectClause = $myCaseFromClause = $myCaseWhereClause = '';
        
        if( $allCases ) {
            $userID = 'null';
            $all = 1;
        } else {
            $all = 0;
            $myCaseWhereClause = " AND case_relationship.contact_id_b = {$userID}";
            $myGroupByClause   = " GROUP BY CONCAT(case_relationship.case_id,'-',case_relationship.contact_id_b)";
        }
        
        $seperator = self::VALUE_SEPERATOR;
   
        $query = "
SELECT case_status.label AS case_status, status_id, case_type.label AS case_type, 
REPLACE(case_type_id,'{$seperator}','') AS case_type_id, case_relationship.contact_id_b
FROM civicrm_case
LEFT JOIN civicrm_option_group option_group_case_type ON ( option_group_case_type.name = 'case_type' )
LEFT JOIN civicrm_option_value case_type ON ( civicrm_case.case_type_id = case_type.value
AND option_group_case_type.id = case_type.option_group_id )
LEFT JOIN civicrm_option_group option_group_case_status ON ( option_group_case_status.name = 'case_status' )
LEFT JOIN civicrm_option_value case_status ON ( civicrm_case.status_id = case_status.value
AND option_group_case_status.id = case_status.option_group_id )
LEFT JOIN civicrm_relationship case_relationship ON ( case_relationship.case_id  = civicrm_case.id 
AND case_relationship.contact_id_b = {$userID})
WHERE is_deleted =0 
{$myCaseWhereClause} {$myGroupByClause}";
        
        $res = CRM_Core_DAO::executeQuery( $query, CRM_Core_DAO::$_nullArray );
        while( $res->fetch() ) {
            if ( CRM_Utils_Array::value($res->case_type, $rows) &&  CRM_Utils_Array::value($res->case_status, $rows[$res->case_type]) ) {
                $rows[$res->case_type][$res->case_status]['count'] = $rows[$res->case_type][$res->case_status]['count'] + 1;
            } else {
                $rows[$res->case_type][$res->case_status] = array( 'count' => 1,
                                                                   'url'   => CRM_Utils_System::url( 'civicrm/case/search',
                                                                                                     "reset=1&force=1&status={$res->status_id}&type={$res->case_type_id}&all={$all}" ) 
                                                                   );
            }
        }
        $caseSummary['rows'] = array_merge( $caseTypes, $rows );
        
        return $caseSummary;
    }

    /**
     * Function to get Case roles
     *
     * @param int $contactID contact id
     * @param int $caseID case id
     *
     * @return returns case role / relationships
     *
     * @static
     */
    static function getCaseRoles( $contactID, $caseID, $relationshipID = null )
    {
        $query = '
SELECT civicrm_relationship.id as civicrm_relationship_id, civicrm_contact.sort_name as sort_name, civicrm_email.email as email, civicrm_phone.phone as phone, civicrm_relationship.contact_id_b as civicrm_contact_id, civicrm_relationship_type.label_b_a as relation, civicrm_relationship_type.id as relation_type 
FROM civicrm_relationship, civicrm_relationship_type, civicrm_contact 
LEFT OUTER JOIN civicrm_phone ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary = 1) 
LEFT JOIN civicrm_email ON (civicrm_email.contact_id = civicrm_contact.id ) 
WHERE civicrm_relationship.relationship_type_id = civicrm_relationship_type.id AND civicrm_relationship.contact_id_a = %1 AND civicrm_relationship.contact_id_b = civicrm_contact.id AND civicrm_relationship.case_id = %2
';

        $params = array( 1 => array( $contactID, 'Integer' ),
                         2 => array( $caseID, 'Integer' )
                         );

		if ( $relationshipID ) {
			$query .= ' AND civicrm_relationship.id = %3 ';
			$params[3] = array( $relationshipID, 'Integer' );
		}
        
        $dao =& CRM_Core_DAO::executeQuery( $query, $params );

        $values = array( );
        while ( $dao->fetch( ) ) {
            $rid = $dao->civicrm_relationship_id;
            $values[$rid]['cid']           = $dao->civicrm_contact_id;
            $values[$rid]['relation']      = $dao->relation;
            $values[$rid]['name']          = $dao->sort_name;
            $values[$rid]['email']         = $dao->email;
            $values[$rid]['phone']         = $dao->phone;
            $values[$rid]['relation_type'] = $dao->relation_type;
        }
        
        $dao->free( );
        return $values;
    }

    /**
     * Function to get Case Activities
     *
     * @param int    $caseID case id
     * @param array  $params posted params 
     * @param int    $contactID contact id
     *
     * @return returns case activities
     *
     * @static
     */
    static function getCaseActivity( $caseID, &$params, $contactID )
    {
        $values = array( );
        
        $select = 'SELECT count(ca.id) as ismultiple, ca.id as id, 
                          ca.activity_type_id as type, 
                          cc.sort_name as reporter,
                          cc.id as reporter_id,
                          acc.sort_name AS assignee,
                          acc.id AS assignee_id,
                          IF(ca.activity_date_time < NOW() AND ca.status_id=ov.value,
                            ca.activity_date_time,
                            DATE_ADD(NOW(), INTERVAL 1 YEAR)
                          ) as overdue_date,
                          ca.activity_date_time as display_date,
                          ca.status_id as status, 
                          ca.subject as subject, 
                          ca.is_deleted as deleted,
                          ca.priority_id as priority ';

        $from  = 'FROM civicrm_case_activity cca 
                  INNER JOIN civicrm_activity ca ON ca.id = cca.activity_id
                  INNER JOIN civicrm_contact cc ON cc.id = ca.source_contact_id
                  LEFT OUTER JOIN civicrm_option_group og ON og.name="activity_status"
                  LEFT OUTER JOIN civicrm_option_value ov ON ov.option_group_id=og.id AND ov.name="Scheduled"
                  LEFT JOIN civicrm_activity_assignment caa 
                                ON caa.activity_id = ca.id 
                               LEFT JOIN civicrm_contact acc ON acc.id = caa.assignee_contact_id  '; 

        $where = 'WHERE cca.case_id= %1 
                    AND ca.is_current_revision = 1';

		if ( CRM_Utils_Array::value( 'reporter_id', $params ) ) {
            $where .= " AND ca.source_contact_id = ".CRM_Utils_Type::escape( $params['reporter_id'], 'Integer' );
        }

		if ( CRM_Utils_Array::value( 'status_id', $params ) ) {
            $where .= " AND ca.status_id = ".CRM_Utils_Type::escape( $params['status_id'], 'Integer' );
        }

		if ( CRM_Utils_Array::value( 'activity_deleted', $params ) ) {
            $where .= " AND ca.is_deleted = 1";
        } else {
            $where .= " AND ca.is_deleted = 0";
        }


		if ( CRM_Utils_Array::value( 'activity_type_id', $params ) ) {
            $where .= " AND ca.activity_type_id = ".CRM_Utils_Type::escape( $params['activity_type_id'], 'Integer' );
        }

		if ( CRM_Utils_Array::value( 'activity_date_low', $params ) ) {
            $fromActivityDate = CRM_Utils_Type::escape( CRM_Utils_Date::processDate( $params['activity_date_low'] ), 'Date' );
        }
		if ( CRM_Utils_Array::value( 'activity_date_high', $params ) ) {
            $toActivityDate   = CRM_Utils_Type::escape( CRM_Utils_Date::processDate( $params['activity_date_high'] ), 'Date' );
            $toActivityDate   = $toActivityDate ? $toActivityDate + 235959 : null;
        }
        
        if ( !empty( $fromActivityDate ) ) {
            $where .= " AND ca.activity_date_time >= '{$fromActivityDate}'";
        }
            
        if ( !empty( $toActivityDate ) ) {
            $where .= " AND ca.activity_date_time <= '{$toActivityDate}'";
        }
            
        // hack to handle to allow initial sorting to be done by query
        if ( CRM_Utils_Array::value( 'sortname', $params ) == 'undefined' ) {
            $params['sortname'] = null;
        }

        if ( CRM_Utils_Array::value( 'sortorder', $params ) == 'undefined' ) {
            $params['sortorder'] = null;
        }

        $sortname  = CRM_Utils_Array::value( 'sortname', $params );
        $sortorder = CRM_Utils_Array::value( 'sortorder', $params );
        
        $groupBy = " GROUP BY ca.id ";
        
        if ( !$sortname AND !$sortorder ) {
            $orderBy = " ORDER BY overdue_date ASC, display_date DESC";
        } else {
            $orderBy = " ORDER BY {$sortname} {$sortorder}, display_date DESC";
        }
        
        $page = CRM_Utils_Array::value( 'page', $params );
        $rp   = CRM_Utils_Array::value( 'rp', $params );
        
        if (!$page) $page = 1;
        if (!$rp) $rp = 10;

        $start = (($page-1) * $rp);
        
        $query  = $select . $from . $where . $groupBy . $orderBy;
				    
        $params = array( 1 => array( $caseID, 'Integer' ) );
        $dao    =& CRM_Core_DAO::executeQuery( $query, $params );
        $params['total'] = $dao->N;

        //FIXME: need to optimize/cache these queries
        $limit  = " LIMIT $start, $rp";
        $query .= $limit;
        $dao    =& CRM_Core_DAO::executeQuery( $query, $params );
       
        $activityTypes  = CRM_Case_PseudoConstant::activityType( false, true );

        require_once "CRM/Utils/Date.php";
        require_once "CRM/Core/PseudoConstant.php";
        $activityStatus   = CRM_Core_PseudoConstant::activityStatus( );
        $activityPriority = CRM_Core_PseudoConstant::priority( );

        $url = CRM_Utils_System::url( "civicrm/case/activity",
                                      "reset=1&cid={$contactID}&caseid={$caseID}", false, null, false ); 
        
        $editUrl    = "{$url}&action=update";
        $deleteUrl  = "{$url}&action=delete";
        $restoreUrl = "{$url}&action=renew";
        $viewTitle  = ts('View this activity.');

        require_once 'CRM/Core/OptionGroup.php';
        $emailActivityTypeIDs = array('Email' => CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                               'Email', 
                                                               'name' ),
                                      'Inbound Email' => CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                               'Inbound Email', 
                                                               'name' ),
                                     );
       
        $activityCondition = " AND v.name IN ('Open Case', 'Change Case Type', 'Change Case Status', 'Change Case Start Date')";
        $caseAttributeActivities = CRM_Core_OptionGroup::values( 'activity_type', false, false, false, $activityCondition );
                   
		require_once 'CRM/Core/OptionGroup.php'; 
        $emailActivityTypeIDs = array('Email' => CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                               'Email', 
                                                               'name' ),
                                      'Inbound Email' => CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                               'Inbound Email', 
                                                               'name' ),
                                     );
                                     
        require_once 'CRM/Case/BAO/Case.php';
        $caseDeleted = CRM_Core_DAO::getFieldValue( 'CRM_Case_DAO_Case', $caseID, 'is_deleted' );
        
        //check for delete activities CRM-4418
        require_once 'CRM/Core/Permission.php'; 
        $allowToDeleteActivities = CRM_Core_Permission::check( 'delete activities' );
        
        // define statuses which are handled like Completed status (others are assumed to be handled like Scheduled status)
        $compStatusValues = array();
        $compStatusNames = array('Completed', 'Left Message', 'Cancelled', 'Unreachable', 'Not Required');
        foreach($compStatusNames as $name) {
            $compStatusValues[] = CRM_Core_OptionGroup::getValue( 'activity_status', $name, 'name' );
        }
        
        $contactViewUrl = CRM_Utils_System::url( "civicrm/contact/view",
                                                 "reset=1&cid=", false, null, false );
        while ( $dao->fetch( ) ) {                 
            $values[$dao->id]['id']           = $dao->id;
            $values[$dao->id]['type']         = $activityTypes[$dao->type]['label'];
            $values[$dao->id]['reporter']     = "<a href='{$contactViewUrl}{$dao->reporter_id}'>$dao->reporter</a>";
            $values[$dao->id]['display_date'] = CRM_Utils_Date::customFormat( $dao->display_date );
            $values[$dao->id]['status']       = $activityStatus[$dao->status];
            $values[$dao->id]['subject']      = "<a href='javascript:viewActivity( {$dao->id}, {$contactID} );' title='{$viewTitle}'>{$dao->subject}</a>";
           
            // add activity assignee to activity selector. CRM-4485.
            if ( isset($dao->assignee) ) {
                if( $dao->ismultiple == 1 ) {
                    $values[$dao->id]['reporter'] .= ' / '."<a href='{$contactViewUrl}{$dao->assignee_id}'>$dao->assignee</a>";
                    $values[$dao->id]['assignee']  = $dao->assignee;
                } else {
                    $values[$dao->id]['reporter'] .= ' / ' .ts('(multiple)');
                } 
            }

            $url = "";
            $additionalUrl = "&id={$dao->id}";
            if ( !$dao->deleted ) {
                //hide edit link of activity type email.CRM-4530.
                if ( ! in_array($dao->type, $emailActivityTypeIDs) ) {
                    $url = "<a href='" .$editUrl.$additionalUrl."'>". ts('Edit') . "</a> ";
                }
                              
                //block deleting activities which affects
                //case attributes.CRM-4543
                if ( !array_key_exists($dao->type, $caseAttributeActivities) && $allowToDeleteActivities ) {
                    if ( !empty($url) ) {
                        $url .= " | ";   
                    }
                    $url .= "<a href='" .$deleteUrl.$additionalUrl."'>". ts('Delete') . "</a>";
                }
            } else if ( !$caseDeleted ) {
                $url = "<a href='" .$restoreUrl.$additionalUrl."'>". ts('Restore') . "</a>";
                $values[$dao->id]['status']  = $values[$dao->id]['status'].'<br /> (deleted)'; 
            } 
            
            $values[$dao->id]['links'] = $url;
            $values[$dao->id]['class'] = "";

            if ( !empty($dao->priority) ) {
                if ( $dao->priority == CRM_Core_OptionGroup::getValue( 'priority', 'Urgent', 'name' ) ) {
                    $values[$dao->id]['class'] = $values[$dao->id]['class']."priority-urgent ";
                } elseif ( $dao->priority == CRM_Core_OptionGroup::getValue( 'priority', 'Low', 'name' ) ) {
                    $values[$dao->id]['class'] = $values[$dao->id]['class']."priority-low ";
                }
            }
            
            if ( CRM_Utils_Array::crmInArray( $dao->status, $compStatusValues ) ) {
                $values[$dao->id]['class'] = $values[$dao->id]['class']." status-completed";
            } else {
                if ( CRM_Utils_Date::overdue( $dao->display_date ) ) {
                    $values[$dao->id]['class'] = $values[$dao->id]['class']." status-overdue";  
                } else {
                    $values[$dao->id]['class'] = $values[$dao->id]['class']." status-scheduled";    
                } 
            }
        }

        $dao->free( );
        return $values;
    }
    
    /**
     * Function to get Case Related Contacts
     *
     * @param int     $caseID case id
     * @param boolean $skipDetails if true include details of contacts  
     *
     * @return returns $searchRows array of returnproperties
     *
     * @static
     */
    static function getRelatedContacts( $caseID, $skipDetails = false )
    {
        $values = array( );
        $query = 'SELECT cc.display_name as name, cc.sort_name as sort_name, cc.id, crt.label_b_a as role, ce.email 
FROM civicrm_relationship cr 
LEFT JOIN civicrm_relationship_type crt ON crt.id = cr.relationship_type_id 
LEFT JOIN civicrm_contact cc ON cc.id = cr.contact_id_b 
LEFT JOIN civicrm_email   ce ON ce.contact_id = cc.id
WHERE cr.case_id =  %1 AND ce.is_primary= 1';
        
        $params = array( 1 => array( $caseID, 'Integer' ) );
        $dao    =& CRM_Core_DAO::executeQuery( $query, $params );

        while ( $dao->fetch( ) ) {
            if ( $skipDetails ) {
                $values[$dao->id] = 1;
                
            } else {
                $values[] = array( 'contact_id'   => $dao->id,
                                   'display_name' => $dao->name,
                                   'sort_name'    => $dao->sort_name,
                                   'role'         => $dao->role,
                                   'email'        => $dao->email
                                   );
            }
        }
        $dao->free( );

        return $values;
    }

    /**
     * Function that sends e-mail copy of activity
     * 
     * @param int     $activityId activity Id
     * @param array   $contacts array of related contact
     *
     * @return void
     * @access public
     */
    static function sendActivityCopy( $clientId, $activityId, $contacts, $attachments = null, $caseId )
    {   
        if ( !$activityId ) {
            return;
        }

        require_once 'CRM/Utils/Mail.php';
        require_once 'CRM/Contact/BAO/Contact/Location.php';        
        $tplParams = array();

        $activityInfo   = array( );
        //if its a case activity
        if ( $caseId ) {
            $anyActivity = false; 
            $tplParams['isCaseActivity'] = 1;
        } else {
            $anyActivity = true;
        }
        
        require_once 'CRM/Case/XMLProcessor/Report.php';
        $xmlProcessor = new CRM_Case_XMLProcessor_Report( );
        $activityInfo = $xmlProcessor->getActivityInfo($clientId, $activityId, $anyActivity );
        if ( $caseId ) { 
		$activityInfo['fields'][] = array( 'label' => 'Case ID', 'type' => 'String', 'value' => $caseId ); 
	}
        $tplParams['activity'] = $activityInfo;

        $activitySubject = CRM_Core_DAO::getFieldValue( 'CRM_Activity_DAO_Activity', $activityId, 'subject' );
        $session =& CRM_Core_Session::singleton( );
        
        //also create activities simultaneously of this copy.
        require_once "CRM/Activity/BAO/Activity.php";
        $activityParams = array( );
        
        $activityParams['source_record_id']   = $activityId; 
        $activityParams['source_contact_id']  = $session->get( 'userID' ); 
        $activityParams['activity_type_id']   = CRM_Core_OptionGroup::getValue( 'activity_type', 'Email', 'name' );
        $activityParams['activity_date_time'] = date('YmdHis');
        $activityParams['status_id']          = CRM_Core_OptionGroup::getValue( 'activity_status', 'Completed', 'name' );
        $activityParams['medium_id']          = CRM_Core_OptionGroup::getValue( 'encounter_medium', 'email', 'name' );
        $activityParams['case_id']            = $caseId;
        $activityParams['is_auto']            = 0;
        
        $tplParams['activitySubject'] = $activitySubject;

        $result = array();
        list ($name, $address) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $session->get( 'userID' ) );
        
        $receiptFrom = "$name <$address>";   
        
        foreach ( $contacts as $mail => $info ) {
            $tplParams['contact'] = $info;
            
            if ( !CRM_Utils_Array::value('sort_name', $info) ) {
                $info['sort_name'] = $info['display_name'];   
            }
            
            $displayName = $info['sort_name'];

            require_once 'CRM/Core/BAO/MessageTemplates.php';
            list ($result[$info['contact_id']], $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
                array(
                    'groupName'   => 'msg_tpl_workflow_case',
                    'valueName'   => 'case_activity',
                    'contactId'   => $info['contact_id'],
                    'tplParams'   => $tplParams,
                    'from'        => $receiptFrom,
                    'toName'      => $displayName,
                    'toEmail'     => $mail,
                    'attachments' => $attachments,
                )
            );

            $activityParams['subject']           = $activitySubject.' - copy sent to '.$displayName;
            $activityParams['details']           = $message;
            $activityParams['target_contact_id'] = $info['contact_id'];
            
            if ($result[$info['contact_id']]) {
                $activity = CRM_Activity_BAO_Activity::create( $activityParams );
                
                //create case_activity record if its case activity.
                if ( $caseId ) {
                    $caseParams = array( 'activity_id' => $activity->id,
                                         'case_id'     => $caseId );
                    self::processCaseActivity( $caseParams );
                }
            } else {
                unset($result[$info['contact_id']]);  
            }
        }
        return $result;
    }
    
    /**
     * Retrieve count of activities having a particular type, and
     * associated with a particular case.
     *
     * @param int    $caseId          ID of the case
     * @param int    $activityTypeId  ID of the activity type
     * 
     * @return array
     * 
     * @access public
     * 
     */
    static function getCaseActivityCount( $caseId, $activityTypeId ) 
    {
        $queryParam = array( 1 => array( $caseId, 'Integer' ),
                             2 => array( $activityTypeId, 'Integer' ) );
        $query = "SELECT count(ca.id) as countact 
FROM       civicrm_activity ca
INNER JOIN civicrm_case_activity cca ON ca.id = cca.activity_id 
WHERE      ca.activity_type_id = %2 
AND       cca.case_id = %1
AND        ca.is_deleted = 0"            
;
        
        $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
        if ( $dao->fetch() ) {
            return $dao->countact;
        }
        
        return false;
    }
    
    /**
     * Create an activity for a case via email
     * 
     * @param int    $file   email sent       
     *       
     * @return $activity object of newly creted activity via email
     * 
     * @access public
     * 
     */
    static function recordActivityViaEmail( $file ) 
    {
        if ( ! file_exists( $file ) ||
             ! is_readable( $file ) ) {
            return CRM_Core_Error::fatal( ts( 'File %1 does not exist or is not readable',
                                              array( 1 => $file ) ) );
        }
        
        require_once 'CRM/Utils/Mail/Incoming.php';
        $result = CRM_Utils_Mail_Incoming::parse( $file );
        if ( $result['is_error'] ) {
            return $result;
        }

        foreach( $result['to'] as $to ) {
            $caseId = null;

            $emailPattern = '/^([A-Z0-9._%+-]+)\+([\d]+)@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
            $replacement  = preg_replace ($emailPattern, '$2', $to['email']); 

            if ( $replacement !== $to['email'] ) {
                $caseId = $replacement;
                //if caseId is invalid, return as error file
                if( !CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseId, 'id') ) {
                    return CRM_Core_Error::createAPIError( ts( 'Invalid case ID ( %1 ) in TO: field.',
                                                               array( 1 => $caseId ) ) );  
                }
            } else {
                continue;
            }

// TODO: May want to replace this with a call to getRelatedAndGlobalContacts() when this feature is revisited.
// (Or for efficiency call the global one outside the loop and then union with this each time.)            
            $contactDetails = self::getRelatedContacts( $caseId, true );

            if ( CRM_Utils_Array::value( $result['from']['id'], $contactDetails ) ) {
                $params = array( );
                $params['subject']            = $result['subject'];
                $params['activity_date_time'] = $result['date'];
                $params['details']            = $result['body'];
                $params['source_contact_id']  = $result['from']['id'];
                $params['status_id']          = CRM_Core_OptionGroup::getValue('activity_status',
                                                                               'Completed',
                                                                               'name' );
            
                $details = CRM_Case_PseudoConstant::activityType( );
                $matches = array( );
                preg_match( '/^\W+([a-zA-Z0-9_ ]+)(\W+)?\n/i',
                            $result['body'], $matches );

                if ( !empty($matches) && isset($matches[1]) ) {
                    $activityType = trim($matches[1]);
                    if ( isset($details[$activityType]) ) {
                        $params['activity_type_id'] = $details[$activityType]['id'];
                    }
                }
                if ( ! isset($params['activity_type_id']) ) {
                    $params['activity_type_id'] = 
                        CRM_Core_OptionGroup::getValue( 'activity_type', 'Inbound Email', 'name' );
                }

                // create activity
                require_once "CRM/Activity/BAO/Activity.php";
                $activity = CRM_Activity_BAO_Activity::create( $params );

                $caseParams = array( 'activity_id' => $activity->id,
                                     'case_id'     => $caseId   );
                self::processCaseActivity( $caseParams );
            } else {
                return CRM_Core_Error::createAPIError( ts( 'FROM email contact %1 doesn\'t have a relationship to the referenced case.',
                                                           array( 1 => $result['from']['email'] ) ) );   
            }
        } 
    }

    /**
     * Function to retrive the scheduled activity type and date
     * 
     * @param  array  $cases  Array of contact and case id        
     *       
     * @return array  $activityInfo Array of scheduled activity type and date
     * 
     * @access public
     *
     * @static
     */
    static function getNextScheduledActivity( $cases, $type = 'upcoming' ) 
    {
        $session   =& CRM_Core_Session::singleton( );
        $userID    = $session->get( 'userID' );

        $caseID    = implode ( ',', $cases['case_id']);
        $contactID = implode ( ',', $cases['contact_id'] );

        $condition = "
AND civicrm_case_contact.contact_id IN( {$contactID} ) 
AND civicrm_case.id IN( {$caseID})
AND civicrm_activity.is_deleted = {$cases['case_deleted']}
AND civicrm_case.is_deleted     = {$cases['case_deleted']}";

        $query = self::getCaseActivityQuery( $type, $userID, $condition, $cases['case_deleted'] );

        $res   = CRM_Core_DAO::executeQuery( $query, CRM_Core_DAO::$_nullArray );

        $activityInfo = array();
        while( $res->fetch() ) {
            if ( $type == 'upcoming' ) {
                $activityInfo[$res->case_id]['date'] = $res->case_scheduled_activity_date;
                $activityInfo[$res->case_id]['type'] = $res->case_scheduled_activity_type;
            } else {
                $activityInfo[$res->case_id]['date'] = $res->case_recent_activity_date;
                $activityInfo[$res->case_id]['type'] = $res->case_recent_activity_type;
            }
        } 

        return $activityInfo;
    }

    /**
     * combine all the exportable fields from the lower levels object
     *     
     * @return array array of exportable Fields
     * @access public
     */
    function &exportableFields( ) 
    {
        if ( ! self::$_exportableFields ) {
            if ( ! self::$_exportableFields ) {
                self::$_exportableFields = array();
            }
            require_once 'CRM/Case/DAO/Case.php';
            
            $fields = CRM_Case_DAO_Case::import( );
            $fields['case_role'] = array( 'title' => ts('Role in Case') );
            
            self::$_exportableFields = $fields;
        }
        return self::$_exportableFields;
    }

    /**                                                           
     * Restore the record that are associated with this case 
     * 
     * @param  int  $caseId id of the case to restore
     * 
     * @return true if success.
     * @access public 
     * @static 
     */ 
    static function restoreCase( $caseId ) 
    {
        //restore activities
        $activities = self::getCaseActivityDates( $caseId );
        if ( $activities ) {
            require_once"CRM/Activity/BAO/Activity.php";
            foreach( $activities as $value ) {
                CRM_Activity_BAO_Activity::restoreActivity( $value );
            }
        }  
        //restore case
        require_once 'CRM/Case/DAO/Case.php';
        $case     = & new CRM_Case_DAO_Case( );
        $case->id = $caseId; 
        $case->is_deleted = 0;
        $case->save( );
        return true;
    }
    
    static function getGlobalContacts( &$groupInfo )
    {
    	$globalContacts = array();
    	
   		require_once 'CRM/Case/XMLProcessor/Settings.php';
   		require_once 'CRM/Contact/BAO/Group.php';
   		require_once 'api/v2/Contact.php';
   		$settingsProcessor = new CRM_Case_XMLProcessor_Settings();
   		$settings = $settingsProcessor->run();
   		if (! empty($settings)) {
   			$groupInfo['name'] = $settings['groupname'];
   			if ($groupInfo['name']) {
				$searchParams = array('name' => $groupInfo['name']);   				
				$results = array();
   				CRM_Contact_BAO_Group::retrieve($searchParams, $results);
				if ($results) {
					$groupInfo['id'] = $results['id'];
					$groupInfo['title'] = $results['title'];
					$searchParams = array( 'group' => array($groupInfo['id'] => 1),
                                           'return.sort_name'     => 1,
                                           'return.display_name'  => 1,
                                           'return.email'         => 1,
                                           'return.phone'         => 1
                                           );
        
					$globalContacts = civicrm_contact_search( $searchParams );
				}

   			}
   		}
   		return $globalContacts;
    }

	/* 
	 * Convenience function to get both case contacts and global in one array
	 */
	static function getRelatedAndGlobalContacts( $caseId )
	{
		$relatedContacts = self::getRelatedContacts( $caseId );
            
		$groupInfo = array();
		$globalContacts = self::getGlobalContacts( $groupInfo );
             
        //unset values which are not required.
        foreach( $globalContacts as $k => &$v ) {
             unset($v['email_id']);
             unset($v['group_contact_id']); 
             unset($v['status']);
             unset($v['phone']);
             $v['role'] = $groupInfo['title'];
        }
        //include multiple listings for the same contact/different roles.
        $relatedGlobalContacts = array_merge( $relatedContacts, $globalContacts );
        return $relatedGlobalContacts;
	}   

    /**
     * Function to get Case ActivitiesDueDates with given criteria. 
     *
     * @param int      $caseID case id
     * @param array    $criteriaParams given criteria
     * @param boolean  $latestDate if set newest or oldest date is selceted.
     *
     * @return returns case activities due dates
     *
     * @static
     */
    static function getCaseActivityDates( $caseID, $criteriaParams = array( ), $latestDate = false )
    {
        $values     = array( );
        $selectDate = " ca.activity_date_time";
        $where      = $groupBy = ' ';
        
        if ( !$caseID ) {
            return;
        }
        
        if ( $latestDate ) {
            if ( CRM_Utils_Array::value( 'activity_type_id', $criteriaParams ) ) {
                $where   .= " AND ca.activity_type_id    = ".CRM_Utils_Type::escape( $criteriaParams['activity_type_id'], 'Integer' );
                $where   .= " AND ca.is_current_revision = 1";
                $groupBy .= " GROUP BY ca.activity_type_id";
            }
            
            if ( CRM_Utils_Array::value( 'newest', $criteriaParams ) ) {
                $selectDate = " max(ca.activity_date_time) "; 
            } else {
                $selectDate = " min(ca.activity_date_time) "; 
            }
        }
        
        $query = "SELECT ca.id, {$selectDate} as activity_date
                  FROM civicrm_activity ca 
                  LEFT JOIN civicrm_case_activity cca ON cca.activity_id = ca.id LEFT JOIN civicrm_case cc ON cc.id = cca.case_id 
                  WHERE cc.id = %1 {$where} {$groupBy}";
        
        $params = array( 1 => array( $caseID, 'Integer' ) );
        $dao    =& CRM_Core_DAO::executeQuery( $query, $params );
        
        while ( $dao->fetch( ) ) {
            $values[$dao->id]['id']            = $dao->id;
            $values[$dao->id]['activity_date'] = $dao->activity_date;
        }
        $dao->free( );
        return $values;
    }
    
    /**
     * Function to create activities when Case or Other roles assigned/modified/deleted. 
     *
     * @param int      $caseID case id
     * @param int      $relationshipId relationship id
     * @param int      $relContactId case role assigne contactId.
     *
     * @return void on success creates activity and case activity 
     *
     * @static
     */
    static function createCaseRoleActivity( $caseId, $relationshipId, $relContactId = null, $contactId = null )
    {
        if ( !$caseId || !$relationshipId || empty($relationshipId) ) {
            return;    
        }
        
        $queryParam = array( );
        if ( is_array($relationshipId) ) {
            $relationshipId     = implode( ',', $relationshipId );
            $relationshipClause = " civicrm_relationship.id IN ($relationshipId)";
        } else {
            $relationshipClause = " civicrm_relationship.id = %1";
            $queryParam[1] = array( $relationshipId, 'Integer' );
        }

        $query = "
                  SELECT civicrm_relationship.contact_id_b as rel_contact_id, civicrm_relationship.contact_id_a as assign_contact_id, 
                  civicrm_relationship_type.label_b_a as relation, civicrm_relationship.case_id as caseId,
                  cc.display_name as clientName, cca.display_name as  assigneeContactName  
                  FROM civicrm_relationship_type,  civicrm_relationship 
                  LEFT JOIN civicrm_contact cc  ON cc.id  = civicrm_relationship.contact_id_b  
                  LEFT JOIN civicrm_contact cca ON cca.id = civicrm_relationship.contact_id_a
                  WHERE civicrm_relationship.relationship_type_id = civicrm_relationship_type.id AND {$relationshipClause}";
        
              
        $dao = CRM_Core_DAO::executeQuery( $query,$queryParam );
              
        while ( $dao->fetch() ) {
            $caseRelationship  = $dao->relation;
            //to get valid assignee contact(s).
             if ( isset($dao->caseId) || $dao->rel_contact_id != $contactId ) { 
                 $assigneContactIds[$dao->rel_contact_id]  = $dao->rel_contact_id;
                 $assigneContactName = $dao->clientName;
             } else {
                 $assigneContactIds[$dao->assign_contact_id]  = $dao->assign_contact_id; 
                 $assigneContactName = $dao->assigneeContactName;
             }
        }

        require_once 'CRM/Core/OptionGroup.php';
        $session = & CRM_Core_Session::singleton();
        $activityParams = array('source_contact_id'    => $session->get( 'userID' ),
                                'subject'              => $caseRelationship.' : '. $assigneContactName,
                                'activity_date_time'   => date('YmdHis'),
                                'status_id'            => CRM_Core_OptionGroup::getValue( 'activity_status', 'Completed', 'name' )
                                );

        //if $relContactId is passed, role is added or modified.
        if ( !empty($relContactId) ) {
            $activityParams['assignee_contact_id'] = $assigneContactIds;

            $activityTypeID = CRM_Core_OptionGroup::getValue( 'activity_type',
                                                              'Assign Case Role',
                                                              'name' );
        } else {
            $activityTypeID = CRM_Core_OptionGroup::getValue( 'activity_type',
                                                              'Remove Case Role',
                                                              'name' );
        }
        
        $activityParams['activity_type_id']    = $activityTypeID;
        
        require_once "CRM/Activity/BAO/Activity.php";
        $activity = CRM_Activity_BAO_Activity::create( $activityParams );
        
        //create case_activity record.
        $caseParams = array( 'activity_id' => $activity->id,
                             'case_id'     => $caseId );
        
        require_once "CRM/Activity/BAO/Activity.php";
        CRM_Case_BAO_Case::processCaseActivity( $caseParams );
    }

    /**
     * Function to get case manger 
     * contact which is assigned a case role of case manager. 
     *
     * @param int    $caseType case type
     * @param int    $caseId   case id
     *
     * @return array $caseManagerContact array of contact on success otherwise empty 
     *
     * @static
     */
    static function getCaseManagerContact( $caseType, $caseId )
    {
        if ( !$caseType || !$caseId ) {
            return;
        }
        
        $caseManagerContact = array( );
        require_once 'CRM/Case/XMLProcessor/Process.php';
        $xmlProcessor  = new CRM_Case_XMLProcessor_Process( );
        
        $managerRoleId = $xmlProcessor->getCaseManagerRoleId( $caseType );
        
        if ( !empty($managerRoleId) ) {
            $managerRoleQuery = "
SELECT civicrm_contact.id as casemanager_id, 
       civicrm_contact.sort_name as casemanager
FROM civicrm_contact 
LEFT JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = civicrm_contact.id AND civicrm_relationship.relationship_type_id = %1)
LEFT JOIN civicrm_case ON civicrm_case.id = civicrm_relationship.case_id
WHERE civicrm_case.id = %2";
            
            $managerRoleParams = array( 1 => array( $managerRoleId  , 'Integer' ),
                                        2 => array( $caseId         , 'Integer' ) );

            $dao = CRM_Core_DAO::executeQuery( $managerRoleQuery, $managerRoleParams );
            if ( $dao->fetch() ) {
                    $caseManagerContact['casemanager_id'] = $dao->casemanager_id;
                    $caseManagerContact['casemanager'   ] = $dao->casemanager;
            }
        }
        
        return $caseManagerContact; 
    }

    /**
     * Get all cases with no end dates
     * 
     * @return array of case and related data keyed on case id
     */
    static function getUnclosedCases()
    {
    	$dao    =& CRM_Core_DAO::executeQuery( "SELECT c.id as contact_id, c.display_name, ca.id, ov.label as case_type
FROM civicrm_case ca INNER JOIN civicrm_case_contact cc ON ca.id=cc.case_id
INNER JOIN civicrm_contact c ON cc.contact_id=c.id
INNER JOIN civicrm_option_group og ON og.name='case_type'
INNER JOIN civicrm_option_value ov ON (ca.case_type_id=ov.value AND ov.option_group_id=og.id)
WHERE ca.end_date is null ORDER BY c.display_name
");
        $values = array();
        while ( $dao->fetch() ) {
            $values[$dao->id] = array(
				'display_name' => $dao->display_name,
				'case_type' => $dao->case_type,
				'contact_id' => $dao->contact_id,
			);
        }
        $dao->free( );
        return $values;
    }
    
    function caseCount( $contactId = null, $excludeDeleted = true )
    {
        $whereConditions = array( );
        if ( $excludeDeleted ) {
            $whereConditions[] = "( civicrm_case.is_deleted = 0 OR civicrm_case.is_deleted IS NULL )";
        }
        if ( $contactId ) {
            $whereConditions[] = "civicrm_case_contact.contact_id = {$contactId}";
        }
        
        $whereClause = '';
        if ( !empty( $whereConditions ) ) {
            $whereClause = "WHERE " . implode( ' AND ', $whereConditions );
        }
        
        $query = "       
   SELECT  count( civicrm_case.id )
     FROM  civicrm_case
LEFT JOIN  civicrm_case_contact ON ( civicrm_case.id = civicrm_case_contact.case_id )
           {$whereClause}";
        
        return CRM_Core_DAO::singleValueQuery( $query ); 
    }
}

