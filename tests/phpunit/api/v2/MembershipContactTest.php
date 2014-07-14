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


require_once 'api/v2/MembershipContact.php';
require_once 'api/v2/Membership.php';
require_once 'api/v2/MembershipType.php';
require_once 'api/v2/MembershipStatus.php';
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for MembershipContact API - civicrm_membership_contact_*
 *
 *  @package   CiviCRM
 */
class api_v2_MembershipContactTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_orgContact;
  protected $_membershipTypeID;
  protected $_membershipStatusID;
  protected $_membershipID; function get_info() {
    return array(
      'name' => 'Membership Contact',
      'description' => 'Test all Membership API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   */
  function setUp() {
    parent::setUp();

    $this->_contactID = $this->individualCreate();
    $this->_orgContact = $this->organizationCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate($this->_orgContact);

    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);

    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);

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

    $this->_membershipID = $this->contactMembershipCreate($params);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   */
  function tearDown() {
    $this->membershipDelete($this->_membershipID);
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->contactDelete($this->_orgContact);
    $this->contactDelete($this->_contactID);
  }

  ///////////////// civicrm_contact_memberships_get methods

  /**
   * Test civicrm_contact_memberships_get with empty params.
   * Error expected.
   */
  function testGetWithEmptyParams() {
    $params = array();
    $result = &civicrm_contact_memberships_get($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_contact_memberships_get with params with wrong type.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_contact_memberships_get($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_contact_memberships_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithParamsContactId() {
    $membership = &civicrm_contact_memberships_get($this->_contactID);

    $result = $membership[$this->_contactID][$this->_membershipID];

    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_get with proper params.
   * Memberships expected.
   */
  function testGet() {
    $params = array('contact_id' => $this->_contactID);

    $membership = &civicrm_contact_memberships_get($params);

    $result = $membership[$this->_contactID][$this->_membershipID];

    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_get for only active.
   * Memberships expected.
   */
  function testGetOnlyActive() {
    $params = array(
      'contact_id' => $this->_contactID,
      'active_only' => 1,
    );

    $membership = &civicrm_contact_memberships_get($params);
    $result = $membership[$this->_contactID][$this->_membershipID];

    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_get for non exist contact.
   * empty Memberships.
   */
  function testGetNoContactExists() {
    $params = array('contact_id' => 'NoContact');

    $membership = &civicrm_contact_memberships_get($params);
    $this->assertEquals($membership['record_count'], 0, "In line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_get with relationship.
   * get Memberships.
   */
  function testGetWithRelationship() {

    $membershipOrgId = $this->organizationCreate();
    $memberContactId = $this->individualCreate();

    $relTypeParams = array(
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Organization',
      'contact_type_b' => 'Individual',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relTypeID = $this->relationshipTypeCreate($relTypeParams);

    $params = array(
      'name' => 'test General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $membershipOrgId,
      'domain_id' => 1,
      'contribution_type_id' => 1,
      'relationship_type_id' => $relTypeID,
      'relationship_direction' => 'b_a',
      'is_active' => 1,
    );
    $memType = civicrm_membership_type_create($params);
    // in order to reload static caching -
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $membershipID = $this->contactMembershipCreate($params);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
    );

    $result = &civicrm_membership_contact_get($params);

    $this->assertArrayHasKey($memberContactId, $result,
      "In line " . __LINE__
    );

    // extra one for the record county key
    $this->assertEquals(2, count($result),
      "In line " . __LINE__
    );

    $membership = $result[$memberContactId][$membershipID];
    $this->assertEquals($this->_membershipStatusID, $membership['status_id'],
      "In line " . __LINE__
    );

    $this->membershipDelete($membershipID);
    $this->membershipTypeDelete($memType);
    $this->relationshipTypeDelete($relTypeID);
    $this->contactDelete($memberContactId);
    $this->contactDelete($membershipOrgId);
  }

  ///////////////// civicrm_membership_contact_create methods

  /**
   * Test civicrm_contact_memberships_create with empty params.
   * Error expected.
   */
  function testCreateWithEmptyParams() {
    $params = array();
    $result = civicrm_membership_contact_create($params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * Test civicrm_contact_memberships_create with params with wrong type.
   * Error expected.
   */
  function testCreateWithParamsString() {
    $params = 'a string';
    $result = &civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testMembershipCreateMissingRequired() {
    $params = array(
      'membership_type_id' => '1',
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'status_id' => '2',
    );

    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testMembershipCreate() {
    $params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 0);
    $this->assertNotNull($result['id']);
    $this->membershipDelete($result['id']);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateWithId() {
    $params = array(
      'id' => $this->_membershipID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['id'], $this->_membershipID);
  }

  /**
   * Test civicrm_contact_memberships_create Invalid membership data
   * Error expected.
   */
  function testMembershipCreateInvalidMemData() {
    //membership_contact_id as string
    $params = array(
      'membership_contact_id' => 'Invalid',
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1);

    //membership_contact_id which is no in contact table
    $params['membership_contact_id'] = 999;
    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1);

    //invalid join date
    unset($params['membership_contact_id']);
    $params['join_date'] = "invalid";
    $result = civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  function testMembershipCreateWithMemContact() {

    $params = array(
      'membership_contact_id' => $this->_contactID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = civicrm_contact_membership_create($params);

    $this->assertEquals($result['is_error'], 0);
    $this->membershipDelete($result['id']);
  }

  ///////////////// civicrm_membership_delete methods

  /**
   * Test civicrm_contact_memberships_delete with params with wrong type.
   * Error expected.
   */
  function testDeleteWithParamsString() {
    $params = 'a string';
    $result = &civicrm_contact_membership_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testMembershipDeleteEmpty() {
    $params = array();
    $result = civicrm_membership_delete($params);
    $this->assertEquals($result['is_error'], 1);
  }

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
    $this->assertEquals($result['is_error'], 0);
  }

  ///////////////// _civicrm_membership_format_params with $create
  function testMemebershipFormatParamsWithCreate() {

    $params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'membership_start_date' => '2006-01-21',
      'membership_end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $values = array();
    _civicrm_membership_format_params($params, $values, TRUE);

    $this->assertEquals($values['start_date'], '20060121');
    $this->assertEquals($values['end_date'], '20061221');
  }
}

