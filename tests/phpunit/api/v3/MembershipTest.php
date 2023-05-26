<?php
/**
 * MembershipTest Unit Test
 *
 * @docmaker_intro_start
 * @api_title Membership
 * This is a API Document about Membership.
 * @docmaker_intro_end
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MembershipTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_membershipTypeID;
  protected $_membershipStatusID;
  protected $__membershipID;
  protected $_entity;
  protected $_params;
  public $_eNoticeCompliant = TRUE;

  /**
   * @before
   */
  public function setUpTest() {
    //  Connect to the database
    parent::setUp();
    $this->_apiversion = 3;

    // ref #35445 13f, temporarily set membershipStatusID to 2 until check membershipStatusTest.
    $this->_membershipStatusID = '2';
    // $this->_membershipStatusID = $this->membershipStatusCreate('test status');
    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate($this->_contactID);


    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
    CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');

    $this->_entity = 'Membership';
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => 3,
    );
  }

  /**
   * @after
   */
  function tearDownTest() {
    // $this->membershipStatusDelete($this->_membershipStatusID);
    // $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    // $this->contactDelete($this->_contactID);
  }
  /**
   * Membership Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Membership
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Membership&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Membership&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testCreateMembership() {
    $result = civicrm_api('membership', 'create', $this->_params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  /**
   * Membership Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Membership
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Membership&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Membership&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testGetMembership() {
    $membership = civicrm_api('membership', 'create', $this->_params);
    $this->assertAPISuccess($membership, ' in line ' . __LINE__);
    $params = array(
      'id' => $membership['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('membership', 'get', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  /**
   * Membership Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Membership
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Membership&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Membership&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testUpdateMembership() {
    $membership = civicrm_api('membership', 'create', $this->_params);
    $this->assertAPISuccess($membership, ' in line ' . __LINE__);
    $params = array(
      'id' => $membership['id'],
      'membership_type_id' => $this->_membershipTypeID,
      'contact_id' => $this->_contactID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'update', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  /**
   * Membership Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Membership
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Membership&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Membership&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testDeleteMembership() {
    $membership = civicrm_api('membership', 'create', $this->_params);
    $this->assertAPISuccess($membership, ' in line ' . __LINE__);
    $params = array(
      'id' => $membership['id'],
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );
    $session = CRM_Core_Session::singleton();
    $session->set('userID', '1');
    $result = civicrm_api('membership', 'delete', $params);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, "In line " . __LINE__);
  }

  function testMembershipDeleteEmpty() {
    $params = array();
    $result = civicrm_api('membership', 'delete', $params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testMembershipDeleteInvalidID() {
    $params = array('version' => $this->_apiversion, 'id' => 'blah');
    $result = civicrm_api('membership', 'delete', $params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   *  Test civicrm_membership_delete() with invalid Membership Id
   */
  function testMembershipDeleteWithInvalidMembershipId() {
    $membershipId = 'membership';
    $result = civicrm_api('membership', 'delete', $membershipId);
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
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('membership', 'get', $params);
    $this->assertEquals(0, $result['is_error'], "In line " . __LINE__);
    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $this->_membershipID,
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithParamsContactId() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );
    $membership = civicrm_api('membership', 'get', $params);

    $result = $membership['values'][$this->_membershipID];
    civicrm_api('Membership', 'Delete', array(
      'id' => $this->_membershipID,
        'version' => $this->_apiversion,
      ));
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
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithParamsMemberShipTypeId() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $params = array(
      'membership_type_id' => $this->_membershipTypeID,
      'version' => $this->_apiversion,
    );
    $membership = civicrm_api('membership', 'get', $params);
    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $membership['id'],
        'version' => $this->_apiversion,
      ));
    $result = $membership['values'][$membership['id']];
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
    $this->assertEquals($result['id'], $membership['id']);
  }

  /**
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithNonExistantMemberShipTypeId() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'membership_type_id' => 465653,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('membership', 'get', $params);
    civicrm_api('Membership', 'Delete', array(
      'id' => $this->_membershipID,
        'version' => $this->_apiversion,
      ));

    $this->assertEquals($result['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals($result['count'], 0, "In line " . __LINE__);
    civicrm_api('Membership', 'Delete', array(
      'id' => $this->_membershipID,
        'version' => $this->_apiversion,
      ));
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testGetWithParamsMemberShipIdAndCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);

    $this->assertAPISuccess($result,  ' in line ' . __LINE__);
    $getParams = array('version' => 3, 'membership_type_id' => $params['membership_type_id']);
    $check = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $check, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  function testGet() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );

    $membership = civicrm_api('membership', 'get', $params);
    //$this->documentMe($params,$membership,__FUNCTION__,__FILE__);
    $result = $membership['values'][$membershipID];
    civicrm_api('Membership', 'Delete', array(
      'id' => $membership['id'],
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);

    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
  }


  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  function testGetWithId() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
      'id' => $this->__membershipID,
      'return' => 'id',
    );
    $result = civicrm_api('membership', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals($membershipID, $result['id']);
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
      'membership_id' => $this->__membershipID,
      'return' => 'membership_id',
    );
    $result = civicrm_api('membership', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals($membershipID, $result['id']);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Test civicrm_membership_get for only active.
   * Memberships expected.
   */
  function testGetOnlyActive() {
    $description          = "Demonstrates use of 'filter' active_only' param";
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $subfile             = 'filterIsCurrent';
    $params              = array(
      'contact_id' => $this->_contactID,
      'active_only' => 1,
      'version' => $this->_apiversion,
    );

    $membership = civicrm_api('membership', 'get', $params);
    $result = $membership['values'][$this->_membershipID];
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID, "In line " . __LINE__);
    $params = array(
      'contact_id' => $this->_contactID,
      'filters' => array(
        'is_current' => 1,
      ),
      'version' => $this->_apiversion,
    );

    $membership = civicrm_api('membership', 'get', $params);
    $this->documentMe($params, $membership, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $membership['values'][$this->_membershipID];
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID, "In line " . __LINE__);


    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $this->_membershipID,
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Test civicrm_membership_get for non exist contact.
   * empty Memberships.
   */
  function testGetNoContactExists() {
    $params = array(
      'contact_id' => 55555,
      'version' => $this->_apiversion,
    );

    $membership = civicrm_api('membership', 'get', $params);
    $this->assertEquals($membership['count'], 0, "In line " . __LINE__);
  }

  /**
   * Test civicrm_membership_get with relationship.
   * get Memberships.
   */
  function testGetWithRelationship() {
    $membershipOrgId = $this->organizationCreate(NULL);
    $memberContactId = $this->individualCreate(NULL);

    $relTypeParams = array(
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Organization',
      'contact_type_b' => 'Individual',
      'is_reserved' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
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
      'version' => $this->_apiversion,
    );
    $memType = civicrm_api('membership_type', 'create', $params);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );
    $membershipID = $this->contactMembershipCreate($params);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'get', $params);

    $membership = $result['values'][$membershipID];
    $this->assertEquals($this->_membershipStatusID, $membership['status_id'],
      "In line " . __LINE__
    );
    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $membership['id'],
        'version' => $this->_apiversion,
      ));
    $this->membershipTypeDelete(array('id' => $memType['id']));
    $this->relationshipTypeDelete($relTypeID);
    $this->contactDelete($membershipOrgId);
    $this->contactDelete($memberContactId);
  }

  ///////////////// civicrm_membership_create methods

  /**
   * Test civicrm_contact_memberships_create with empty params.
   * Error expected.
   */
  function testCreateWithEmptyParams() {
    $params = array();
    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * Test civicrm_contact_memberships_create with params with wrong type.
   * Error expected.
   */
  function testCreateWithParamsString() {
    $params = 'a string';
    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }
  /*
   * If is_overide is passed in status must also be passed in
   */
  function testCreateOverrideNoStatus() {
    $params = $this->_params;
    unset($params['status_id']);
    $result = civicrm_api('membership', 'create', $params);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
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
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->getAndCheck($params, $result['id'], $this->_entity);
    $this->assertEquals($result['is_error'], 0);
    $this->assertNotNull($result['id']);
    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['contact_id'], " in line " . __LINE__);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id'], " in line " . __LINE__);
  }
  /*
      * Check for useful message if contact doesn't exist
      */
  function testMembershipCreateWithInvalidContact() {
    $params = array(
      'contact_id' => 999,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals('contact_id is not valid : 999', $result['error_message']);
  }
  function testMembershipCreateWithInvalidStatus() {
    $params = $this->_params;
    $params['status_id'] = 999;

    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals('status_id is not valid : 999', $result['error_message']);
  }

  function testMembershipCreateWithInvalidType() {
    $params = $this->_params;
    $params['membership_type_id'] = 999;

    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals('membership_type_id is not valid : 999', $result['error_message']);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);

    $check = civicrm_api($this->_entity, 'get', array('version' => 3, 'id' => $result['id'], 'contact_id' => $this->_contactID));
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateWithId() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($result['is_error'], 0, "in line " . __LINE__);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateUpdateWithIdNoContact() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'membership_type_id' => $this->_membershipTypeID,
      'contact_id' => $this->_contactID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($result['is_error'], 0, "in line " . __LINE__);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateUpdateWithIdNoDates() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($result['is_error'], 0, "in line " . __LINE__);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateUpdateWithIdNoDatesNoType() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'source' => 'not much here',
      'contact_id' => $this->_contactID,
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($result['is_error'], 0, "in line " . __LINE__);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateUpdateWithIDAndSource() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'source' => 'changed',
      'contact_id' => $this->_contactID,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
      'membership_type_id' => $this->_membershipTypeID,
      'skipStatusCal' => 1,
    );
    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals($result['is_error'], 0, "in line " . __LINE__);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
    civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
  }

  /**
   * change custom field using update
   */
  function testUpdateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = civicrm_api($this->_entity, 'create', $params);

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api($this->_entity, 'create', array('id' => $result['id'], 'version' => 3, 'custom_' . $ids['custom_field_id'] => "new custom"));
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $check = civicrm_api($this->_entity, 'get', array('version' => 3, 'id' => $result['id'], 'contact_id' => $this->_contactID));

    $this->assertEquals("new custom", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $delete = civicrm_api('Membership', 'Delete', array(
      'id' => $check['id'],
        'version' => $this->_apiversion,
      ));

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_contact_memberships_create Invalid membership data
   * Error expected.
   */
  function testMembershipCreateInvalidMemData() {
    //membership_contact_id as string
    $params = array(
      'membership_contact_id' => 'Invalid',
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "in line " . __LINE__);

    //membership_contact_id which is no in contact table
    $params['membership_contact_id'] = 999;
    $result = civicrm_api('membership', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "in line " . __LINE__);

    //invalid join date
    unset($params['membership_contact_id']);
    $params['join_date'] = "invalid";
    $result = civicrm_api('Membership', 'Create', $params);
    $this->assertEquals($result['is_error'], 1, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  function testMembershipCreateWithMemContact() {
    $params = array(
      'membership_contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership', 'create', $params);

    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api('Membership', 'Delete', array(
      'id' => $result['id'],
        'version' => $this->_apiversion,
      ));
  }

  ///////////////// civicrm_membership_delete methods
}

