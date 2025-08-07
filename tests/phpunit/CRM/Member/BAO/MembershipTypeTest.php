<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Member/BAO/MembershipType.php';

class CRM_Member_BAO_MembershipTypeTest extends CiviUnitTestCase
{
    function get_info( ) 
    {
        return [
                     'name'        => 'MembershipType BAOs',
                     'description' => 'Test all Member_BAO_MembershipType methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    function setUp( ) 
    { 
        parent::setUp();
        
        //create relationship
        $params = [
                           'name_a_b'       => 'Relation 1',
                           'name_b_a'       => 'Relation 2',
                           'contact_type_a' => 'Individual',
                           'contact_type_b' => 'Organization',
                           'is_reserved'    => 1,
                           'is_active'      => 1
                               ];
        $this->_relationshipTypeId  = $this->relationshipTypeCreate( $params ); 
        $this->_orgContactID        = $this->organizationCreate( ) ;
        $this->_indiviContactID     = $this->individualCreate( ) ;
        $this->_contributionTypeId  = $this->contributionTypeCreate();
        $this->_membershipStatusID  = $this->membershipStatusCreate( 'test status' );

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     */
    function tearDown( )
    {
        $this->relationshipTypeDelete( $this->_relationshipTypeId );
        $this->membershipStatusDelete( $this->_membershipStatusID );
        $this->contributionTypeDelete( );
        $this->contactDelete( $this->_orgContactID );
        $this->contactDelete( $this->_indiviContactID );
    }

    /* check function add()
     *
     */
    function testAdd( ) 
    {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'test type',
                         'description' => null,
                         'minimum_fee' => 10,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public'
                         ];
        
        $membershipType = CRM_Member_BAO_MembershipType::add( $params, $ids );
        
        $membership = $this->assertDBNotNull( 'CRM_Member_BAO_MembershipType', $this->_orgContactID,
                                              'name', 'member_of_contact_id',
                                              'Database check on updated membership record.' );
        
        $this->assertEquals( $membership, 'test type', 'Verify membership type name.');
        $this->membershipTypeDelete( [ 'id' => $membershipType->id ] );
    }

    /* check function retrive()
     *
     */
    function testRetrieve( ) 
    {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public'
                         ];
        $membershipType = CRM_Member_BAO_MembershipType::add( $params, $ids );

        $params  = ['name' => 'General'];
        $default = [ ];  
        $result = CRM_Member_BAO_MembershipType::retrieve( $params ,$default);
        $this->assertEquals( $result->name , 'General', 'Verify membership type name.');
        $this->membershipTypeDelete( [ 'id' => $membershipType->id ] );
    }

    /* check function isActive()
     *
     */
    function testSetIsActive( ) 
    {        
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );
        
        CRM_Member_BAO_MembershipType::setIsActive( $membership->id , 0 ) ;

        $isActive = $this->assertDBNotNull( 'CRM_Member_BAO_MembershipType', $membership->id,
                                                    'is_active', 'id',
                                                    'Database check on membership type status.' );

        $this->assertEquals( $isActive, 0, 'Verify membership type status.');
        $this->membershipTypeDelete( [ 'id' => $membership->id ] );
    }
    
    /* check function del()
     *
     */
    function testdel( ) 
    {        
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );
        
        $result = CRM_Member_BAO_MembershipType::del($membership->id) ;
        
        $this->assertEquals( $result, true , 'Verify membership deleted.');
        
    }
    
    /* check function convertDayFormat( )
     *
     */
    function testConvertDayFormat( ) 
    {        
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'fixed_period_start_day' => 1213,
                         'fixed_period_rollover_day' => 1214,
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );
        $membershipType[$membership->id] = $params;
        
        CRM_Member_BAO_MembershipType::convertDayFormat( $membershipType);
        
        $this->assertEquals( $membershipType[$membership->id]['fixed_period_rollover_day'], 'Dec 14' , 'Verify memberFixed Period Rollover Day.');
        $this->membershipTypeDelete( [ 'id' => $membership->id ] );
        
    }

    /* check function getMembershipTypes( )
     *
     */
    function testGetMembershipTypes( ) {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );
        $result = CRM_Member_BAO_MembershipType::getMembershipTypes();
        $this->assertEquals( $result[$membership->id], 'General' , 'Verify membership types.');
        $this->membershipTypeDelete( [ 'id' => $membership->id ] );
    }

    /* check function getMembershipTypeDetails( )
     *
     */
    function testGetMembershipTypeDetails( ) {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'fixed',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );
        $result = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membership->id);

        $this->assertEquals( $result['name'], 'General' , 'Verify membership type details.');
        $this->assertEquals( $result['duration_unit'], 'year' , 'Verify membership types details.');
        $this->membershipTypeDelete( [ 'id' => $membership->id ] );
    }

    /* check function getDatesForMembershipType( )
     *
     */
    function testGetDatesForMembershipType( ) {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'rolling',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membership = CRM_Member_BAO_MembershipType::add( $params, $ids );

        $membershipDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membership->id);
        $this->assertEquals( $membershipDates['start_date'], date('Ymd') , 'Verify membership types details.');
        $this->membershipTypeDelete( [ 'id' => $membership->id ] );
    }
    
    /* check function getRenewalDatesForMembershipType( )
     *
     */
    function testGetRenewalDatesForMembershipType( ) {
        require_once 'CRM/Member/BAO/Membership.php';
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'rolling',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membershipType = CRM_Member_BAO_MembershipType::add( $params, $ids );

        $params = [
                        'contact_id'         => $this->_indiviContactID,  
                        'membership_type_id' => $membershipType->id,
                        'join_date'          => '20060121000000',
                        'start_date'         => '20060121000000',
                        'end_date'           => '20061221000000',
                        'source'             => 'Payment',
                        'is_override'        => 1,
                        'status_id'          => $this->_membershipStatusID
                        ];
        $ids = [];
        $membership = CRM_Member_BAO_Membership::create( $params, $ids );
        
        $membershipRenewDates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id);

        $this->assertEquals( $membershipRenewDates['start_date'], '20060121' , 'Verify membership renewal start date.' );
        $this->assertEquals( $membershipRenewDates['end_date'], '20071221' , 'Verify membership renewal end date.' );

        $this->membershipDelete( $membership->id );
        $this->membershipTypeDelete( [ 'id' => $membershipType->id ] );
    }

    /* check function getMembershipTypesByOrg( )
     *
     */
    function testGetMembershipTypesByOrg( ) {
        $ids    = [ 'memberOfContact' => $this->_orgContactID ];
        $params = [ 'name' => 'General',
                         'description' => null,
                         'minimum_fee' => 100,
                         'duration_unit' => 'year',
                         'period_type' => 'rolling',
                         'duration_interval' => 1,
                         'contribution_type_id' => $this->_contributionTypeId,
                         'relationship_type_id' => $this->_relationshipTypeId,
                         'visibility' => 'Public',
                         'is_active'  => 1
                         ];
        $membershipType = CRM_Member_BAO_MembershipType::add( $params, $ids );

        $result = CRM_Member_BAO_MembershipType::getMembershipTypesByOrg( $this->_orgContactID);
        $this->assertEquals( empty($result), false , 'Verify membership types for organization.' );
        
        $result = CRM_Member_BAO_MembershipType::getMembershipTypesByOrg( 501 );
        $this->assertEquals( empty($result), true , 'Verify membership types for organization.' );

        $this->membershipTypeDelete( [ 'id' => $membershipType->id ] );
    }
}
