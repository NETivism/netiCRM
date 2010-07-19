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

class CRM_Activity_BAO_Query 
{


    /** 
     * build select for Case 
     * 
     * @return void  
     * @access public  
     */
    static function select( &$query ) 
    {
        $query->_select['activity_tag1_id'] = "civicrm_meeting.activity_tag1_id as Activity type OR  civicrm_phonecall.activity_tag1_id as Activity type OR civicrm_activity.activity_tag1_id as Activity type";
        $query->_element['activity_tag1_id'] = 1;
 
        $query->_select['activity_tag2_id'] = "civicrm_meeting.activity_tag2_id as Communication Medium OR  civicrm_phonecall.activity_tag2_id as Communication Medium OR civicrm_activity.activity_tag2_id as Communication Medium";
        $query->_element['activity_tag2_id'] = 1;

        $query->_select['activity_tag3_id'] = "civicrm_meeting.activity_tag3_id as Violation OR civicrm_phonecall.activity_tag3_id as Violation OR civicrm_activity.activity_tag3_id as Violation";
        $query->_element['activity_tag3_id'] = 1;

        $query->_select['subject'] = "civicrm_meeting.subject as Subject OR  civicrm_phonecall.subject as Subject OR civicrm_activity.subject as Subject";
        $query->_element['subject'] = 1;
    }

     /** 
     * Given a list of conditions in query generate the required
     * where clause
     * 
     * @return void 
     * @access public 
     */ 
    static function where( &$query ) 
    {
        $isTest = false;
        $grouping = null;
        foreach ( array_keys( $query->_params ) as $id ) {
            if ( substr( $query->_params[$id][0], 0, 9) == 'activity_' ) {
                $grouping = $query->_params[$id][3];
                self::whereClauseSingle( $query->_params[$id], $query );
                if ( $query->_params[$id][0] == 'activity_test' ) {
                    $isTest = true;
                }
            }
        }
        
        if ( $grouping !== null &&
             !$isTest ) {
            $values = array( 'activity_test', '=', 0, $grouping, 0 );
            self::whereClauseSingle( $values, $query );
        }
    }
    
    /** 
     * where clause for a single field
     * 
     * @return void 
     * @access public 
     */ 
    static function whereClauseSingle( &$values, &$query ) 
    {
        list( $name, $op, $value, $grouping, $wildcard ) = $values;
        
        $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

        switch( $name ) {
            
        case 'activity_activitytag1_id':
            $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));
            $query->_where[$grouping][] = "civicrm_activity.activity_tag1_id $op {$value}";

            require_once 'CRM/Core/OptionGroup.php' ;
            $activityType = CRM_Core_OptionGroup::values('case_activity_type');
            $value = $activityType[$value];

            $query->_qill[$grouping ][]          = ts( 'Case Activity %2 %1', array( 1 => $value, 2 => $op) );
            $query->_tables['civicrm_activity']  = $query->_whereTables['civicrm_activity'] = 1;
            return;

        case 'activity_activitytag2_id':
            $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));
            $query->_where[$grouping][] = "civicrm_activity.activity_tag2_id $op {$value}";

            require_once 'CRM/Core/OptionGroup.php' ;
            $communicationMedium = CRM_Core_OptionGroup::values('communication_medium');
            $value = $communicationMedium[$value];

            $query->_qill[$grouping ][] = ts( 'Communication Medium %2 %1', array( 1 => $value, 2 => $op) );
            $query->_tables['civicrm_activity']  = $query->_whereTables['civicrm_activity'] = 1;
            return;

        case 'activity_activitytag3_id':
            require_once 'CRM/Core/OptionGroup.php' ;
            $violation = CRM_Core_OptionGroup::values('f1_case_violation');
            $actualValue = array();
            foreach ( $value as $id => $val ) {
                 $actualValue[] = $violation[$val];
            }
            $op = 'LIKE';
            
            require_once 'CRM/Case/BAO/Case.php';
            $value = CRM_Case_BAO_Case::VALUE_SEPERATOR . 
                implode( CRM_Case_BAO_Case::VALUE_SEPERATOR . "%' OR civicrm_activity.activity_tag3_id LIKE '%" .
                         CRM_Case_BAO_Case::VALUE_SEPERATOR, $value) . 
                CRM_Case_BAO_Case::VALUE_SEPERATOR;
            $query->_where[$grouping][] = "(civicrm_activity.activity_tag3_id $op '%{$value}%')";

            $query->_qill[$grouping ][] = ts( 'Violation Type %1', array( 1 => $op) ).  ' ' .implode( ' ' . ts('or') . ' ', $actualValue );
            $query->_tables['civicrm_activity']  = $query->_whereTables['civicrm_activity'] = 1;
            return;

        case 'activity_details':
            $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));
            $op = 'LIKE';
            $query->_where[$grouping][] = "civicrm_activity.details $op '%{$value}%'";

            $query->_qill[$grouping ][] = ts( 'Activity Content %2 %1', array( 1 => $value, 2 => $op) );
            $query->_tables['civicrm_activity']  = $query->_whereTables['civicrm_activity'] = 1;
            return;

        case 'activity_start_date_low':
        case 'activity_start_date_high':
            
            $query->dateQueryBuilder( $values,
                                      'civicrm_meeting', 'activity_start_date', 'scheduled_date_time', 'Start Date' );
            return;
            
        case 'activity_test':
            $query->_where[$grouping][] = " civicrm_activity.is_test $op '$value'";
            if ( $value ) {
                $query->_qill[$grouping][]  = "Find Test Activities";
            }
            $query->_tables['civicrm_activity'] = $query->_whereTables['civicrm_activity'] = 1;
            
            return;
        }
    }
    
    /*
    static function from( $name, $mode, $side ) 
    {
        $from = null;
 
        return $from;
        
    }
    */
    
    /**
     * getter for the qill object
     *
     * @return string
     * @access public
     */
    function qill( ) {
        return (isset($this->_qill)) ? $this->_qill : "";
    }
    
    
    /**
     * add all the elements shared between case activity search  and advanaced search
     *
     * @access public 
     * @return void
     * @static
     */  
    static function buildSearchForm( &$form ) 
    {
       
        require_once 'CRM/Core/OptionGroup.php';
        $caseActivityType = CRM_Core_OptionGroup::values('case_activity_type');
        $form->add('select', 'activity_activitytag1_id',  ts( 'Activity Type' ),  
                   array( '' => ts( '- select -' ) ) + $caseActivityType );
        
        $comunicationMedium = CRM_Core_OptionGroup::values('communication_medium'); 
        $form->add('select', 'activity_activitytag2_id',  ts( 'Activity Medium' ),  
                   array( '' => ts( '- select -' ) ) + $comunicationMedium );
        
        $caseViolation = CRM_Core_OptionGroup::values('f1_case_violation');
        $form->addElement('select', 'activity_activitytag3_id',  ts( 'Violation Type'  ),  
                          array( '' => ts( '- select -' ) ) + $caseViolation);

        $form->addElement( 'text', 'activity_subject', ts( 'Subject' ) );
        $form->addElement( 'text', 'activity_details', ts( 'Content' ) );
    
        $form->addElement('date', 'activity_start_date_low', ts('Start Date - From'), CRM_Core_SelectValues::date('relative')); 
        $form->addRule('activity_start_date_low', ts('Select a valid date.'), 'qfDate'); 
        
        $form->addElement('date', 'activity_start_date_high', ts('To'), CRM_Core_SelectValues::date('relative')); 
        $form->addRule('activity_start_date_high', ts('Select a valid date.'), 'qfDate'); 

        $form->assign( 'validCaseActivity', true );
    }

    static function addShowHide( &$showHide ) 
    {
        $showHide->addHide( 'caseActivityForm' );
        $showHide->addShow( 'caseActivityForm_show' );
    }

}


