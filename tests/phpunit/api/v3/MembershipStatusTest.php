<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
class api_v3_MembershipStatusTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_contributionTypeID;
  protected $_membershipTypeID;
  protected $_membershipStatusID;
  public $_eNoticeCompliant = TRUE;
  protected $_apiversion; 
  
  function get_info() {
    return [
      'name' => 'MembershipStatus Calc',
      'description' => 'Test all MembershipStatus Calc API methods.',
      'group' => 'CiviCRM API Tests',
    ];
  }

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate($this->_contactID);
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');

    CRM_Member_PseudoConstant::membershipType($this->_membershipTypeID, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
  }

  function tearDown() {
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(['id' => $this->_membershipTypeID]);
    $this->contactDelete($this->_contactID);
  }

  ///////////////// civicrm_membership_status_get methods

  /**
   *  Test civicrm_membership_status_get with wrong params type
   */
  function testGetWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('membership_status', 'get', $params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_membership_status_get with empty params
   */
  function testGetEmptyParams() {
    $params = ['version' => 3];
    $result = civicrm_api('membership_status', 'get', $params);
    // It should be 8 statuses, 7 default from mysql_data
    // plus one test status added in setUp
    $this->assertEquals(8, $result['count'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_membership_status_get. Success expected.
   */
  function testGet() {
    $params = [
      'name' => 'test status',
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('membership_status', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$this->_membershipStatusID]['name'], "test status", "In line " . __LINE__);
  }

  /**
   *  Test civicrm_membership_status_get. Success expected.
   */
  function testGetLimit() {
    $params = [
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('membership_status', 'getcount', $params);
    $this->assertGreaterThan(1, $result, "Check more than one exists In line " . __LINE__);
    $params['option.limit'] = 1;
    $result = civicrm_api('membership_status', 'getcount', $params);
    $this->assertEquals(1, $result, "Check only 1 retrieved " . __LINE__);
  }

  function testMembershipStatusesGet() {
    $params = 'wrong type';
    $result = civicrm_api('membership_status', 'get', $params);
    $this->assertEquals(1, $result['is_error'],
      "In line " . __LINE__
    );
  }

  ///////////////// civicrm_membership_status_create methods
  function testCreateWithEmptyParams() {
    $params = [];
    $result = civicrm_api('membership_status', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testCreateWithWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('membership_status', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testCreateWithMissingRequired() {
    $params = ['title' => 'Does not make sense'];
    $result = civicrm_api('membership_status', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testCreate() {
    $params = [
      'name' => 'test membership status',
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('membership_status', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);

    $this->assertEquals($result['is_error'], 0);
    $this->assertNotNull($result['id']);
    $this->membershipStatusDelete($result['id']);
  }

  ///////////////// civicrm_membership_status_update methods
  //removed as none actually tested functionality - all just tested same stuff
  //generic tests test.



  ///////////////// civicrm_membership_status_calc methods
  /*pending it being re-enabled

    
    function testCalculateStatusWithNoMembershipID( )
    {
        $calcParams = array( 'title' => 'Does not make sense' );
        
        $result = civicrm_api3_membership_status_calc( $calcParams );
        $this->assertEquals( $result['is_error'], 1,"In line " . __LINE__ );
    }
    
    function testCalculateStatus( )
    {

        $join_date  = new DateTime();
        $start_date = new DateTime();
        $end_date   = new DateTime();
        $join_date->modify("-5 months");
        $start_date->modify("-5 months");
        $end_date->modify("+7 months");

        $params = array(
           'contact_id'         => $this->_contactID, 
                         'membership_type_id' => $this->_membershipTypeID,
                         'membership_status_id' => $this->_membershipStatusID,
                         'join_date'   => $join_date->format('Y-m-d'),
                         'start_date'  => $start_date->format('Y-m-d'),
                         'end_date'    => $end_date->format('Y-m-d') );
                         
        $membershipID       = $this->contactMembershipCreate( $params );
        $membershipStatusID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',$membershipID,'status_id');
        $calcParams         = array( 'membership_id' => $membershipID );
        $result             = _civicrm_api3_membership_status_calc( $calcParams );
        $this->assertEquals( $result['is_error'], 0 );
        $this->assertEquals( $membershipStatusID,$result['id'] );
        $this->assertNotNull( $result['id'] );
        
        $this->membershipDelete( $membershipID );
    }
*/



  ///////////////// civicrm_membership_status_delete methods
  function testDeleteEmptyParams() {
    $params = [];
    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testDeleteWrongParamsType() {
    $params = 'incorrect value';
    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testDeleteWithMissingRequired() {
    $params = ['title' => 'Does not make sense'];
    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  function testDelete() {
    $membershipID = $this->membershipStatusCreate();
    $params = [
      'id' => $membershipID,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 0);
  }
  /*
     * Test that trying to delete membership status while membership still exists creates error
     */
  function testDeleteWithMembershipError() {
    $membershipStatusID = $this->membershipStatusCreate();
    $this->_contactID = $this->individualCreate();
    $this->_entity = 'membership';
    $params = [
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $membershipStatusID,
      'version' => 3,
    ];

    $result = civicrm_api('membership', 'create', $params);
    $membershipID = $result['id'];

    $params = [
      'id' => $membershipStatusID,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);

    civicrm_api('Membership', 'Delete', [
      'id' => $membershipID,
        'version' => $this->_apiversion,
      ]);

    $result = civicrm_api('membership_status', 'delete', $params);
    $this->assertEquals($result['is_error'], 0, 'In line ' . __LINE__);
  }
}

