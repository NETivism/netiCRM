<?php
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
require_once 'api/v2/Membership.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_MembershipTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_membershipTypeID;
  protected $_membershipStatusID; function get_info() {
    return array(
      'name' => 'Membership',
      'description' => 'Test all Membership API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  public function setUp() {
    //  Connect to the database
    parent::setUp();

    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate($this->_contactID);
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');

    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
  }

  function tearDown() {
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->contactDelete($this->_contactID);
  }

  /**
   *  Test civicrm_membership_delete()
   */
  function testMembershipDelete() {
    $params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $membershipID = $this->contactMembershipCreate($params);

    $result = civicrm_membership_delete($membershipID);

    $this->assertEquals($result['is_error'], 0,
      "In line " . __LINE__
    );

    $this->assertEquals($result['result'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check civicrm_membership_delete() with empty parameter
   */
  function testMembershipDeleteEmpty() {
    $membershipId = NULL;
    $result = civicrm_membership_delete($membershipId);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_membership_delete() with invalid Membership Id
   */
  function testMembershipDeleteWithInvalidMembershipId() {
    $membershipId = 'membership';
    $result = civicrm_membership_delete($membershipId);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  All other methods calls MembershipType and MembershipContact
   *  api, but putting simple test methods to control existence of
   *  these methods for backwards compatibility, also verifying basic
   *  behaviour is the same as new methods.
   */
  function testContactMembershipsGet() {
    $this->assertTrue(function_exists('civicrm_contact_memberships_get'));
    $params = array();
    $result = civicrm_contact_memberships_get($params);
    $this->assertEquals(1, $result['is_error'],
      "In line " . __LINE__
    );
  }

  function testContactMembershipCreate() {
    $this->assertTrue(function_exists('civicrm_contact_membership_create'));
    $params = array();
    $result = civicrm_contact_membership_create($params);
    $this->assertEquals(1, $result['is_error'],
      "In line " . __LINE__
    );
  }

  function testContactMembershipGet() {
    $this->assertTrue(function_exists('civicrm_membership_types_get'));
    $params = array();
    $result = civicrm_membership_types_get($params);
    $this->assertEquals(1, $result['is_error'],
      "In line " . __LINE__
    );
  }

  function testMembershipStatusesGet() {
    $this->assertTrue(function_exists('civicrm_membership_statuses_get'));
    $params = 'wrong type';
    $result = civicrm_membership_statuses_get($params);
    $this->assertEquals(1, $result['is_error'],
      "In line " . __LINE__
    );
  }
}

