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

class CRM_Contact_Form_Search_Criteria {

    static function basic( &$form ) {
        $form->addElement( 'hidden', 'hidden_basic', 1 );

        if ( $form->_searchOptions['contactType'] ) {
            // add checkboxes for contact type
            $contact_type = array( );
            require_once 'CRM/Contact/BAO/ContactType.php';
            $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements( );
            foreach ($contactTypes as $k => $v) {
                if ( ! empty( $k ) ) {
                    $contact_type[] = HTML_QuickForm::createElement('checkbox', $k, null, $v);
                }
            }
            $form->addGroup($contact_type, 'contact_type', ts('Contact Type(s)'), '<br />');
        }

        if ( $form->_searchOptions['groups'] ) {
            // checkboxes for groups
            foreach ($form->_group as $groupID => $group) {
                $form->_groupElement =& $form->addElement('checkbox', "group[$groupID]", null, $group);
            }
        }

        if ( $form->_searchOptions['tags'] ) {
            // checkboxes for categories
   	    require_once 'CRM/Core/BAO/Tag.php';
	    $tags = new CRM_Core_BAO_Tag ();
	    $tree =$tags->getTree();
            $form->assign       ( 'tree'  , $tags->getTree() );
            foreach ($form->_tag as $tagID => $tagName) {
                $form->_tagElement =& $form->addElement('checkbox', "tag[$tagID]", null, $tagName);
            }
        }

        // add text box for last name, first name, street name, city
        $form->addElement('text', 'sort_name', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name') );

        // add text box for last name, first name, street name, city
        $form->add('text', 'email', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name') );

        //added contact source
        $form->add('text', 'contact_source', ts('Contact Source'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'source') );

        // add checkbox for cms users only
        if (CIVICRM_UF != 'Standalone'){
          $form->addYesNo( 'uf_user', ts( 'CMS User?' ) );
        }
        // add search profiles
        require_once 'CRM/Core/BAO/UFGroup.php';

        // FIXME: This is probably a part of profiles - need to be
        // FIXME: eradicated from here when profiles are reworked.
        $types = array( 'Participant', 'Contribution', 'Membership' );

        // get component profiles
        $componentProfiles = array( );
        $componentProfiles = CRM_Core_BAO_UFGroup::getProfiles($types);

        $ufGroups           =& CRM_Core_BAO_UFGroup::getModuleUFGroup('Search Profile', 1);
        $accessibleUfGroups = CRM_Core_Permission::ufGroup( CRM_Core_Permission::VIEW );

        $searchProfiles = array ( );
        foreach ($ufGroups as $key => $var) {
            if ( ! array_key_exists($key, $componentProfiles) && in_array($key, $accessibleUfGroups) ) {
                $searchProfiles[$key] = $var['title'];
            }
        }
        
        $form->addElement('select', 'uf_group_id', ts('Search Views'),  array('0' => ts('- default view -')) + $searchProfiles);

        // checkboxes for DO NOT phone, email, mail
        // we take labels from SelectValues
        $t = CRM_Core_SelectValues::privacy();
        $t['do_not_toggle'] = ts( 'Include contacts who have these privacy option(s).' );
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_phone', null, $t['do_not_phone']);
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_email', null, $t['do_not_email']);
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_mail' , null, $t['do_not_mail']);
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_sms' ,  null, $t['do_not_sms']);
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_trade', null, $t['do_not_trade']);
        $privacy[] = HTML_QuickForm::createElement('advcheckbox', 'do_not_toggle', null, $t['do_not_toggle']);
        
        $form->addGroup($privacy, 'privacy', ts('Privacy'), array( '&nbsp;', '&nbsp;', '&nbsp;', '<br/>' ) );

        // preferred communication method 
        require_once 'CRM/Core/PseudoConstant.php';
        $comm = CRM_Core_PseudoConstant::pcm(); 
        
        $commPreff = array();
        foreach ( $comm as $k => $v ) {
            $commPreff[] = HTML_QuickForm::createElement('advcheckbox', $k , null, $v );
        }
        $form->addGroup($commPreff, 'preferred_communication_method', ts('Preferred Communication Method'));
        
    }

    static function location( &$form ) {
        $form->addElement( 'hidden', 'hidden_location', 1 );

        require_once 'CRM/Core/BAO/Preferences.php';
        $addressOptions = CRM_Core_BAO_Preferences::valueOptions( 'address_options', true, null, true );
        
        $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');
 
        $elements = array( 
                          'street_address'         => array( ts('Street Address')    ,  $attributes['street_address'], null, null ),
                          'city'                   => array( ts('City')              ,  $attributes['city'] , null, null ),
                          'postal_code'            => array( ts('Zip / Postal Code') ,  $attributes['postal_code'], null, null ),
                          'county'                 => array( ts('County')            ,  $attributes['county_id'], 'county', false ),
                          'state_province'         => array( ts('State / Province')  ,  $attributes['state_province_id'], 'stateProvince', true ),
                          'country'                => array( ts('Country')           ,  $attributes['country_id'], 'country', false ), 
                          'address_name'           => array( ts('Address Name')      ,  $attributes['address_name'], null, null ), 
                           );
 
        foreach ( $elements as $name => $v ) {
            list( $title, $attributes, $select, $multiSelect ) = $v;
            
            if ( ! $addressOptions[$name] ) {
                continue;
            }
 
            if ( ! $attributes ) {
                $attributes = $attributes[$name];
            }
            
            if ( $select ) {
                $selectElements = array( '' => ts('- select -') ) + CRM_Core_PseudoConstant::$select( );
                $element = $form->addElement('select', $name, $title, $selectElements );
                if ( $multiSelect ) {
                    $element->setMultiple( true );
                }
            } else {
                $form->addElement('text', $name, $title, $attributes );
            }
            
            if ( $addressOptions['postal_code'] ) { 
                $form->addElement('text', 'postal_code_low', ts('Range-From'),
                                  CRM_Utils_Array::value( 'postal_code', $attributes ) );
                $form->addElement('text', 'postal_code_high', ts('To'),
                                  CRM_Utils_Array::value( 'postal_code', $attributes ) );
            }
            
            // select for state province
            $stateProvince = array('' => ts('- any state/province -')) + CRM_Core_PseudoConstant::stateProvince( );
            
        }

        $worldRegions =  array('' => ts('- any region -')) + CRM_Core_PseudoConstant::worldRegion( );
        $form->addElement('select', 'world_region', ts('World Region'), $worldRegions);
        
        // checkboxes for location type
        $location_type = array();
        $locationType = CRM_Core_PseudoConstant::locationType( );
        foreach ($locationType as $locationTypeID => $locationTypeName) {
            $location_type[] = HTML_QuickForm::createElement('checkbox', $locationTypeID, null, $locationTypeName);
        }
        $form->addGroup($location_type, 'location_type', ts('Location Types'), '&nbsp;');
    }

    static function activity( &$form ) 
    {
        $form->add( 'hidden', 'hidden_activity', 1 );

        $activityOptions = CRM_Core_PseudoConstant::activityType( true, true );
        asort( $activityOptions );

        // textbox for Activity Type
        $form->_activityType =
            array( ''   => ' - select activity - ' ) + $activityOptions;
           
        
        $form->add('select', 'activity_type_id', ts('Activity Type'),
                   $form->_activityType,
                   false);

        $config =& CRM_Core_Config::singleton( );

        $form->addDate( 'activity_date_low', ts('Activity Dates - From'), false, array( 'formatType' => 'searchDate') );
        $form->addDate( 'activity_date_high', ts('To'), false, array( 'formatType' => 'searchDate') );
        
        $activityRoles  = array( ts('With'), ts('Created by'), ts('Assigned to') );
        $form->addRadio( 'activity_role', ts( 'Contact Role and Name' ), $activityRoles, null, '<br />');
        $form->setDefaults(array('activity_role' => 0));
        
        $form->addElement('text', 'activity_target_name', ts('Contact Name'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name') );
       
        $activityStatus = CRM_Core_PseudoConstant::activityStatus( );
        foreach ($activityStatus as $activityStatusID => $activityStatusName) {
            $activity_status[] = HTML_QuickForm::createElement('checkbox', $activityStatusID, null, $activityStatusName);
        }
        $form->addGroup($activity_status, 'activity_status', ts('Activity Status'));
        $form->setDefaults(array('activity_status[1]' => 1, 'activity_status[2]' => 1));

        $form->addElement('text', 'activity_subject', ts('Subject'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

        $form->addElement('checkbox', 'activity_test', ts('Find Test Activities?'));

        // add all the custom  searchable fields
        require_once 'CRM/Core/BAO/CustomGroup.php';
        $activity = array( 'Activity' );
        $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail( null, true, $activity );
        if ( $groupDetails ) {
            require_once 'CRM/Core/BAO/CustomField.php';
            $form->assign('activityGroupTree', $groupDetails);
            foreach ($groupDetails as $group) {
                foreach ($group['fields'] as $field) {
                    $fieldId = $field['id'];                
                    $elementName = 'custom_' . $fieldId;
                    CRM_Core_BAO_CustomField::addQuickFormElement( $form,
                                                                   $elementName,
                                                                   $fieldId,
                                                                   false, false, true );
                }
            }
        }
    }

    static function changeLog( &$form ) {
        $form->add( 'hidden', 'hidden_changeLog', 1 );

        // block for change log
        $form->addElement('text', 'changed_by', ts('Modified By'), null);

        $form->addDate( 'modified_date_low', ts('Modified Between'), false, array( 'formatType' => 'searchDate') );
        $form->addDate( 'modified_date_high', ts('and'), false, array( 'formatType' => 'searchDate') );
    }

    static function task( &$form ) {
        $form->add( 'hidden', 'hidden_task', 1 );

        if ( CRM_Core_Permission::access( 'Quest' ) ) {
            $form->assign( 'showTask', 1 );

            // add the task search stuff
            // we add 2 select boxes, one for the task from the task table
            $taskSelect       = array( '' => '- select -' ) + CRM_Core_PseudoConstant::tasks( );
            $form->addElement( 'select', 'task_id', ts( 'Task' ), $taskSelect );
            $form->addSelect( 'task_status', ts( 'Task Status' ) );
        }
    }

    static function relationship( &$form ) {
        $form->add( 'hidden', 'hidden_relationship', 1 );

        require_once 'CRM/Contact/BAO/Relationship.php';
        require_once 'CRM/Core/PseudoConstant.php';
        $allRelationshipType = array( );
        $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType( null, null, null, null, true );
        $form->addElement('select', 'relation_type_id', ts('Relationship Type'),  array('' => ts('- select -')) + $allRelationshipType);
        $form->addElement('text', 'relation_target_name', ts('Target Contact'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name') );
        $relStatusOption  = array( ts('Active '), ts('Inactive '), ts('All') );
        $form->addRadio( 'relation_status', ts( 'Relationship Status' ), $relStatusOption);
        $form->setDefaults(array('relation_status' => 0));
        
        // add all the custom  searchable fields
        require_once 'CRM/Core/BAO/CustomGroup.php';
        $relationship = array( 'Relationship' );
        $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail( null, true, $relationship );
        if ( $groupDetails ) {
            require_once 'CRM/Core/BAO/CustomField.php';
            $form->assign('relationshipGroupTree', $groupDetails);
            foreach ($groupDetails as $group) {
                foreach ($group['fields'] as $field) {
                    $fieldId = $field['id'];                
                    $elementName = 'custom_' . $fieldId;
                    CRM_Core_BAO_CustomField::addQuickFormElement( $form,
                                                                   $elementName,
                                                                   $fieldId,
                                                                   false, false, true );
                }
            }
        }
    }
    
    static function demographics( &$form ) {
        $form->add( 'hidden', 'hidden_demographics', 1 );
        // radio button for gender
        $genderOptions = array( );
        $gender =CRM_Core_PseudoConstant::gender();
        foreach ($gender as $key => $var) {
            $genderOptions[$key] = HTML_QuickForm::createElement('radio', null, ts('Gender'), $var, $key);
        }
        $form->addGroup($genderOptions, 'gender', ts('Gender'));
         
        $form->addDate( 'birth_date_low', ts('Birth Dates - From'), false, array( 'formatType' => 'searchDate') );
        $form->addDate( 'birth_date_high', ts('To'), false, array( 'formatType' => 'searchDate') );

        $form->addDate( 'deceased_date_low', ts('Deceased Dates - From'), false, array( 'formatType' => 'searchDate') );
        $form->addDate( 'deceased_date_high', ts('To'), false, array( 'formatType' => 'searchDate') );
    }
    
    static function notes( &$form ) {
        $form->add( 'hidden', 'hidden_notes', 1 );

        $form->addElement('text', 'note', ts('Note Text'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name') );
    }

    /**
     * Generate the custom Data Fields based
     * on the is_searchable
     *
     * @access private
     * @return void
     */
    static function custom( &$form ) {
        $form->add( 'hidden', 'hidden_custom', 1 ); 
        $extends      = array_merge( array( 'Contact', 'Individual', 'Household', 'Organization' ),
                                     CRM_Contact_BAO_ContactType::subTypes( ) );
        $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail( null, true,
                                                                  $extends );

        $form->assign('groupTree', $groupDetails);

        foreach ($groupDetails as $key => $group) {
            $_groupTitle[$key] = $group['name'];
            CRM_Core_ShowHideBlocks::links( $form, $group['name'], '', '');
            
            $groupId = $group['id'];
            foreach ($group['fields'] as $field) {
                $fieldId = $field['id'];                
                $elementName = 'custom_' . $fieldId;
                
                CRM_Core_BAO_CustomField::addQuickFormElement( $form,
                                                               $elementName,
                                                               $fieldId,
                                                               false, false, true );
            }
        }
    }

    static function CiviCase( &$form ) {
        //Looks like obsolete code, since CiviCase is a component, but might be used by HRD
        $form->add( 'hidden', 'hidden_CiviCase', 1 );
        require_once 'CRM/Case/BAO/Query.php';
        CRM_Case_BAO_Query::buildSearchForm( $form );
    }

}


