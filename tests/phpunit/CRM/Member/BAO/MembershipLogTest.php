<?php

/**
 *  File for the TestActivityType class
 *
 *  (PHP 5)
 *  
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Member/BAO/MembershipLog.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Membership.php';
require_once 'CRM/Member/BAO/Membership.php';
require_once 'CRM/Member/BAO/MembershipType.php';

/**
 *  Test CRM/Member/BAO Membership Log add , delete functions
 *
 *  @package   CiviCRM
 */
class CRM_Member_BAO_MembershipLogTest extends CiviUnitTestCase 
{    
    function get_info( ) 
    {
        return [
                     'name'        => 'MembershipLog Test',
                     'description' => 'Test all Membership Log methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    function setUp( ) 
    {
        parent::setUp();
        
        $params = [ 'contact_type_a' => 'Individual',
                         'contact_type_b' => 'Organization',
                         'name_a_b'       => 'Test Employee of',
                         'name_b_a'       => 'Test Employer of'
                         ];
        $this->_relationshipTypeId  = $this->relationshipTypeCreate( $params ); 
        $this->_orgContactID        = $this->organizationCreate( ) ;
        $this->_contributionTypeId  = $this->contributionTypeCreate();
        
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name'                 => 'test type',
                         'description'          => null,
                         'minimum_fee'          => 10,
                         'duration_unit'        => 'year',
                         'period_type'          => 'fixed',
                         'duration_interval'    => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility'           => 'Public',
                         'is_active'            => 1,
                         ];
        $membershipType = CRM_Member_BAO_MembershipType::add( $params, $ids );
        $this->_membershipTypeID    = $membershipType->id;
        $this->_mebershipStatusID  = $this->membershipStatusCreate( 'test status' );           
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     */
    function tearDown( )
    {
        $this->relationshipTypeDelete( $this->_relationshipTypeId );
        $this->membershipTypeDelete( [ 'id' => $this->_membershipTypeID ] );
        $this->membershipStatusDelete( $this->_mebershipStatusID );
        $this->contributionTypeDelete( null );
        $this->contactDelete( $this->_orgContactID );
    }

    /**
     *  Test add()
     */
    function testadd()
    {  
        $contactId = Contact::createIndividual( );
        
        $params = [
                        'contact_id'         => $contactId,  
                        'membership_type_id' => $this->_membershipTypeID,
                        'join_date'          => '2007-01-21',
                        'start_date'         => '2007-01-21',
                        'end_date'           => '2007-12-21',
                        'source'             => 'Payment',
                        'is_override'        => 1,
                        'status_id'          => $this->_mebershipStatusID
                        ];
        $ids = [];
        $membership = CRM_Member_BAO_Membership::create( $params, $ids );      
        $this->assertDBNotNull( 'CRM_Member_BAO_MembershipLog',$membership->id ,
                                'membership_id', 'id',
                                'Database checked on membershiplog record.' );

        $this->membershipDelete( $membership->id );
        $this->contactDelete( $contactId );
    }
    
    /**
     *  Test del()
     */
    function testdel()
    {  
        $contactId = Contact::createIndividual( );
        
        $params = [
                        'contact_id'         => $contactId,  
                        'membership_type_id' => $this->_membershipTypeID,
                        'join_date'          => '2008-01-21',
                        'start_date'         => '2008-01-21',
                        'end_date'           => '2008-12-21',
                        'source'             => 'Payment',
                        'is_override'        => 1,
                        'status_id'          => $this->_mebershipStatusID
                        ];
        $ids = [ 
                     'userId'   => $contactId
                      ];
        $membership = CRM_Member_BAO_Membership::create( $params, $ids );
        $membershipDelete =  CRM_Member_BAO_MembershipLog::del( $membership->id );
        $this->assertDBNull( 'CRM_Member_BAO_MembershipLog',$membership->id, 'membership_id', 
                             'id', 'Database check for deleted membership log.' );

        $this->membershipDelete( $membership->id );
        $this->contactDelete( $contactId );
    }
    
    /**
     *  Test resetmodified()
     */
    function testresetmodifiedId()
    {  
        $contactId = Contact::createIndividual( );
        
        $params = [
                        'contact_id'         => $contactId,  
                        'membership_type_id' => $this->_membershipTypeID,
                        'join_date'          => '2009-01-21',
                        'start_date'         => '2009-01-21',
                        'end_date'           => '2009-12-21',
                        'source'             => 'Payment',
                        'is_override'        => 1,
                        'status_id'          => $this->_mebershipStatusID
                        ];
        $ids = [ 
                     'userId'   => $contactId
                     ];
        $membership = CRM_Member_BAO_Membership::create( $params, $ids );
        $resetModifiedId =  CRM_Member_BAO_MembershipLog::resetModifiedID( $contactId );
        $this->assertDBNull( 'CRM_Member_BAO_MembershipLog',$contactId, 'modified_id', 
                             'modified_id', 'Database check for NULL modified id.' ); 

        $this->membershipDelete( $membership->id );
        $this->contactDelete( $contactId );
    }
}
