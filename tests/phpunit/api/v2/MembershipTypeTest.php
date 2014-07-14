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


require_once 'api/v2/Membership.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID; function get_info() {
    return array(
      'name' => 'MembershipType Create',
      'description' => 'Test all Membership Type Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_contactID = $this->organizationCreate();
  }

  function tearDown() {
    $this->contactDelete($this->_contactID);
  }

  ///////////////// civicrm_membership_type_get methods
  function testGetWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testGetWithEmptyParams() {
    $params = array();
    $membershiptype = &civicrm_membership_type_get($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'No input parameters present');
  }

  function testGetWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'contribution_type_id' => 1,
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_get($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Exact match not found');
  }

  function testGet() {
    $id             = $this->membershipTypeCreate($this->_contactID);
    $params         = array('id' => $id);
    $membershiptype = &civicrm_membership_type_get($params);

    $this->assertEquals($membershiptype[$id]['name'], 'General', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype[$id]['member_of_contact_id'], $this->_contactID);
    $this->assertEquals($membershiptype[$id]['contribution_type_id'], 1);
    $this->assertEquals($membershiptype[$id]['duration_unit'], 'year');
    $this->assertEquals($membershiptype[$id]['duration_interval'], '1');
    $this->assertEquals($membershiptype[$id]['period_type'], 'rolling');
    $this->membershipTypeDelete($params);
  }

  ///////////////// civicrm_membership_type_create methods
  function testCreateWithEmptyParams() {
    $params = array();
    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'No input parameters present');
  }

  function testCreateWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testCreateWithoutMemberOfContactId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'contribution_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Required fields member_of_contact_id for CRM_Member_DAO_MembershipType are not found');
  }

  function testCreateWithoutContributionTypeId() {
    $params = array(
      'name' => '70+ Membership',
      'description' => 'people above 70 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );
    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Required fields contribution_type_id for CRM_Member_DAO_MembershipType are not found');
  }

  function testCreateWithoutDurationUnit() {

    $params = array(
      'name' => '80+ Membership',
      'description' => 'people above 80 are given health instructions', 'member_of_contact_id' => $this->_contactID,
      'contribution_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Missing require fileds ( name, duration unit,duration interval)');
  }

  function testCreateWithoutDurationInterval() {
    $params = array(
      'name' => '70+ Membership',
      'description' => 'people above 70 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );
    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Missing require fileds ( name, duration unit,duration interval)');
  }

  function testCreateWithoutName() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'contribution_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_create($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Missing require fileds ( name, duration unit,duration interval)');
  }

  function testCreate() {
    $params = array(
      'name' => '40+ Membership',
      'description' => 'people above 40 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'contribution_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_create($params);

    $this->assertEquals($membershiptype['is_error'], 0);
    $this->assertNotNull($membershiptype['id']);
    $this->membershipTypeDelete($membershiptype);
  }

  ///////////////// civicrm_membership_type_update methods
  function testUpdateWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = &civicrm_membership_type_update($params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testUpdateWithEmptyParams() {
    $params = array();
    $membershiptype = &civicrm_membership_type_update($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'No input parameters present');
  }

  function testUpdateWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions', 'member_of_contact_id' => $this->_contactID,
      'contribution_type_id' => 1,
      'minimum_fee' => '1200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = &civicrm_membership_type_update($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Required parameter missing');
  }

  function testUpdate() {
    $id = $this->membershipTypeCreate($this->_contactID);
    $newMembOrgParams = array(
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
    );
    // create a new contact to update this membership type to
    $newMembOrgID = $this->organizationCreate($newMembOrgParams);

    $params = array(
      'id' => $id,
      'name' => 'Updated General',
      'member_of_contact_id' => $newMembOrgID,
      'contribution_type_id' => '1',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'fixed',
    );
    $membershiptype = &civicrm_membership_type_update($params);
    $this->assertEquals($membershiptype['name'], 'Updated General');
    $this->assertEquals($membershiptype['member_of_contact_id'], $newMembOrgID);
    $this->assertEquals($membershiptype['contribution_type_id'], '1');
    $this->assertEquals($membershiptype['duration_unit'], 'month');
    $this->assertEquals($membershiptype['duration_interval'], '10');
    $this->assertEquals($membershiptype['period_type'], 'fixed');

    $this->membershipTypeDelete($params);
    $this->contactDelete($newMembOrgID);
  }

  ///////////////// civicrm_membership_type_delete methods
  function testDeleteWithWrongParamsType() {

    $params = 'a string';
    $membershiptype = &civicrm_membership_type_delete($params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testDeleteWithEmptyParams() {
    $params = array();
    $membershiptype = civicrm_membership_type_delete($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'No input parameters present');
  }

  function testDeleteNotExists() {
    $params = array('id' => 'doesNotExist');
    $membershiptype = civicrm_membership_type_delete($params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Error while deleting membership type');
  }

  function testDelete() {
    $orgID            = $this->organizationCreate();
    $membershipTypeID = $this->membershipTypeCreate($orgID);
    $params['id']     = $membershipTypeID;
    $membershiptype   = civicrm_membership_type_delete($params);
    $this->assertEquals($membershiptype['is_error'], 0);
    $this->contactDelete($orgID);
  }
}



