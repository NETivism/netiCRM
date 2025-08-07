<?php
/**
 * Phone Unit Test
 *
 * @docmaker_intro_start
 * @api_title Participant
 * This is a API Document about Participant.
 * @docmaker_intro_end
 */


// require_once 'api/v3/DeprecatedUtils.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ParticipantTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  public $_participantID2;
  /**
   * @var int
   */
  public $_participantID3;
  public $_contributionTypeId;
  protected $_apiversion;
  protected $_entity;
  protected $_contactID;
  protected $_contactID2;
  protected $_createdParticipants;
  protected $_participantID;
  protected $_eventID;
  protected $_individualId;
  protected $_params; function get_info() {
    return [
      'name' => 'Participant Create',
      'description' => 'Test all Participant Create API methods.',
      'group' => 'CiviCRM API Tests',
    ];
  }

  /**
   * @before
   */
  function setUpTest() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->_entity  = 'participant';
    $event          = $this->eventCreate(NULL);
    $this->_eventID = $event['id'];

    $this->_contactID = $this->individualCreate(NULL);

    $this->_createdParticipants = [];
    $this->_individualId = $this->individualCreate(NULL);

    $this->_participantID = $this->participantCreate(['contactID' => $this->_contactID, 'eventID' => $this->_eventID]);
    $this->_contactID2 = $this->individualCreate(NULL);
    $this->_participantID2 = $this->participantCreate(['contactID' => $this->_contactID2, 'eventID' => $this->_eventID, 'version' => $this->_apiversion]);
    $this->_participantID3 = $this->participantCreate(['contactID' => $this->_contactID2, 'eventID' => $this->_eventID, 'version' => $this->_apiversion]);
    $this->_params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',
      'version' => $this->_apiversion,
    ];
  }

  /**
   * @after
   */
  function tearDownTest() {
    // $this->eventDelete($this->_eventID);
    // $tablesToTruncate = array(
    //   'civicrm_custom_group', 'civicrm_custom_field', 'civicrm_contact', 'civicrm_participant'
    // );
    // true tells quickCleanup to drop any tables that might have been created in the test
    // $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Participant Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Participant
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Participant&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Participant&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testCreateParticipant() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);

    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertNotEquals($result['is_error'], 1, $result['error_message'] . ' in line ' . __LINE__);
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
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);

    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertNotEquals($result['is_error'], 1, $result['error_message'] . ' in line ' . __LINE__);

    $check = civicrm_api($this->_entity, 'get', ['version' => 3, 'id' => $result['id']]);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_participant_get with custom params
   */
  /**
   * Participant Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Participant
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Participant&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Participant&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = civicrm_api($this->_entity, 'create', $params);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $get_params = [
      'id' => $result['id'],
      'version' => $this->_apiversion,
      'return' => 'id,participant_register_date,event_id',
    ];
    $get = civicrm_api($this->_entity, 'get', $get_params);
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->docMakerResponse($get, __FILE__, __FUNCTION__);
  }

  ///////////////// civicrm_participant_get methods

  /**
   * check with wrong params type
   */
  function testGetWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('participant', 'get', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_get with empty params
   */
  function testGetEmptyParams() {
    $params = [];
    $result = civicrm_api('participant', 'get', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with participant_id
   */
  function testGetParticipantIdOnly() {
    $params = [
      'participant_id' => $this->_participantID,
      'version' => $this->_apiversion,
      'return' => [
        'participant_id',
        'event_id',
        'participant_register_date',
        'participant_source',
      ]
    ];
    $result = civicrm_api('participant', 'get', $params);
    $this->assertAPISuccess($result, " in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID, "in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00', "in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon', "in line " . __LINE__);
      $params = [
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
      'return' => 'id,participant_register_date,event_id',

    ];
    $result = civicrm_api('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');

  }

  /**
   * check with params id
   */
  function testGetParamsAsIdOnly() {
    $params = [
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('participant', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($result['id'], $result['values'][$this->_participantID]['id']);
  }

  /**
   * check with params id
   */
  function testGetNestedEventGet() {
    //create a second event & add participant to it.
    $event = $this->eventCreate(NULL);
    civicrm_api('participant', 'create', ['version' => 3, 'event_id' => $event['id'], 'contact_id' => $this->_contactID]);


    $description = "use nested get to get an event";
    $subfile     = "NestedEventGet";
    $params      = [
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
      'api.event.get' => 1,
    ];
    $result = civicrm_api('participant', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($this->_eventID, $result['values'][$this->_participantID]['api.event.get']['id']);
  }
  /*
     * Check Participant Get respects return properties
     */
  function testGetWithReturnProperties() {
    $params = [
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
      'return.status_id' => 1,
      'return.participant_status_id' => 1,
      'options' => ['limit' => 1]
    ];
    $result = civicrm_api('participant', 'get', $params);
    $this->assertArrayHasKey('participant_status_id', $result['values'][$result['id']]);
  }

  /**
   * check with contact_id
   */
  function testGetContactIdOnly() {
    $params = [
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($this->_participantID, $participant['id'],
      "In line " . __LINE__
    );
    $this->assertEquals($this->_eventID, $participant['values'][$participant['id']]['event_id'],
      "In line " . __LINE__
    );
    $this->assertEquals('2007-02-19 00:00:00', $participant['values'][$participant['id']]['participant_register_date'],
      "In line " . __LINE__
    );
    $this->assertEquals('Wimbeldon', $participant['values'][$participant['id']]['participant_source'],
      "In line " . __LINE__
    );
    $this->assertEquals($participant['id'], $participant['values'][$participant['id']]['id'],
      "In line " . __LINE__
    );
  }

  /**
   * check with event_id
   * fetch first record
   */
  function testGetMultiMatchReturnFirst() {
    $params = [
      'event_id' => $this->_eventID,
      'rowCount' => 1,
      'version' => $this->_apiversion,
    ];

    $participant = civicrm_api('participant', 'get', $params);
    $this->assertNotNull($participant['id']);
  }

  /**
   * check with event_id
   * in v3 this should return all participants
   */
  function testGetMultiMatchNoReturnFirst() {
    $params = [
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);
    $this->assertEquals($participant['is_error'], 0);
    $this->assertNotNull($participant['count'], 3);
  }

  ///////////////// civicrm_participant_get methods

  /**
   * Test civicrm_participant_get with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('participant', 'get', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_get with empty params
   * In this case all the participant records are returned.
   */
  function testSearchEmptyParams() {
    $params = ['version' => $this->_apiversion];
    $result = civicrm_api('participant', 'get', $params);

    // expecting 3 participant records
    $this->assertEquals($result['count'], 3);
  }

  /**
   * check with participant_id
   */
  function testSearchParticipantIdOnly() {
    $params = [
      'participant_id' => $this->_participantID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);
    $this->assertEquals($participant['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($participant['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
  }

  /**
   * check with contact_id
   */
  function testSearchContactIdOnly() {
    // Should get 2 participant records for this contact.
    $params = [
      'contact_id' => $this->_contactID2,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2);
  }

  /**
   * check with event_id
   */
  function testSearchByEvent() {
    // Should get >= 3 participant records for this event. Also testing that last_name and event_title are returned.
    $params = [
      'event_id' => $this->_eventID,
      'return.last_name' => 1,
      'return.event_title' => 1,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);
    if ($participant['count'] < 3) {
      $this->fail("Event search returned less than expected miniumum of 3 records.");
    }

    $this->assertEquals($participant['values'][$this->_participantID]['last_name'], 'Anderson');
    $this->assertEquals($participant['values'][$this->_participantID]['event_title'], 'Annual CiviCRM meet');
  }

  /**
   * check with event_id
   * fetch with limit
   */
  function testSearchByEventWithLimit() {
    // Should 2 participant records since we're passing rowCount = 2.
    $params = [
      'event_id' => $this->_eventID,
      'rowCount' => 2,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2, 'in line ' . __LINE__);
  }

  ///////////////// civicrm_participant_create methods

  /**
   * Test civicrm_participant_create with wrong params type
   */
  function testCreateWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('participant', 'create', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_create with empty params
   */
  function testCreateEmptyParams() {
    $params = [];
    $result = civicrm_api('participant', 'create', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with event_id
   */
  function testCreateMissingContactID() {
    $params = [
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'create', $params);
    if (CRM_Utils_Array::value('id', $participant)) {
      $this->_createdParticipants[] = $participant['id'];
    }
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  /**
   * check with contact_id
   * without event_id
   */
  function testCreateMissingEventID() {
    $params = [
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'create', $params);
    if (CRM_Utils_Array::value('id', $participant)) {
      $this->_createdParticipants[] = $participant['id'];
    }
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  /**
   * check with contact_id & event_id
   */
  function testCreateEventIdOnly() {
    $params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertNotEquals($participant['is_error'], 1);
    $this->_participantID = $participant['id'];

    if (!$participant['is_error']) {
      // assertDBState compares expected values in $match to actual values in the DB
      unset($params['version']);
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $params);
    }
  }

  /**
   * check with complete array
   */
  function testCreateAllParams() {
    $params = $this->_params;

    $participant = civicrm_api('participant', 'create', $params);
    $this->assertNotEquals($participant['is_error'], 1, 'in line ' . __LINE__);
    $this->_participantID = $participant['id'];
    if (!$participant['is_error']) {
      // assertDBState compares expected values in $match to actual values in the DB
      unset($params['version']);
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $params);
    }
  }
  /*
     * Test to check if receive date is being changed per CRM-9763
     */
  function testCreateUpdateReceiveDate() {
    $participant = civicrm_api('participant', 'create', $this->_params);
    $update = [
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    civicrm_api('participant', 'create', $update);
    $this->getAndCheck(array_merge($this->_params, $update), $participant['id'], 'participant');
  }
  /*
     * Test to check if participant fee level is being changed per CRM-9781
     */
  function testCreateUpdateParticipantFeeLevel() {
    $myParams = $this->_params + ['participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . "fee" . CRM_Core_DAO::VALUE_SEPARATOR];
    $participant = civicrm_api('participant', 'create', $myParams);
    $this->assertAPISuccess($participant);
    $update = [
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    civicrm_api('participant', 'create', $update);
    $this->assertEquals($participant['values'][$participant['id']]['participant_fee_level'],
      $update['values'][$participant['id']]['participant_fee_level']
    );

    civicrm_api('participant', 'delete', ['version' => 3, 'id' => $participant['id']]);
  }
  /*
     * Test to check if participant fee level is being changed per CRM-9781
     * Try again  without a custom separater to check that one isn't added
     * (get & check won't accept an array)
     */
  function testUpdateCreateParticipantFeeLevelNoSeparator() {

    $myParams = $this->_params + ['participant_fee_level' => "fee"];
    $participant = civicrm_api('participant', 'create', $myParams);
    $this->assertAPISuccess($participant);
    $update = [
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    civicrm_api('participant', 'create', $update);
    $this->assertEquals($participant['values'][$participant['id']]['participant_fee_level'],
      $update['values'][$participant['id']]['participant_fee_level']
    );
    $this->getAndCheck($update, $participant['id'], 'participant');
  }
  ///////////////// civicrm_participant_update methods

  /**
   * Test civicrm_participant_update with wrong params type
   */
  function testUpdateWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('participant', 'create', $params);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testUpdateEmptyParams() {
    $params = ['version' => $this->_apiversion];
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id, contact_id');
  }

  /**
   * check without event_id
   */
  function testUpdateWithoutEventId() {
    $participantId = $this->participantCreate(['contactID' => $this->_individualId, 'eventID' => $this->_eventID, 'version' => $this->_apiversion]);
    $params = [
      'contact_id' => $this->_individualId,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id');
    // Cleanup created participant records.
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with Invalid participantId
   */
  function testUpdateWithWrongParticipantId() {
    $params = [
      'id' => 1234,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('Participant', 'update', $params);
    $this->assertEquals($participant['is_error'], 1);
  }

  /**
   * check with Invalid ContactId
   */
  function testUpdateWithWrongContactId() {
    $participantId = $this->participantCreate([
      'contactID' => $this->_individualId,
        'eventID' => $this->_eventID,
      ], $this->_apiversion);
    $params = [
      'id' => $participantId,
      'contact_id' => 12345,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertEquals($participant['is_error'], 1);
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with complete array
   */
  /**
   * Phone Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Participant
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Participant&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Participant&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testUpdateParticipant() {
    $participantId = $this->participantCreate(['contactID' => $this->_individualId, 'eventID' => $this->_eventID, $this->_apiversion]);
    $params = [
      'id' => $participantId,
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $participant = civicrm_api('participant', 'create', $params);
    $this->docMakerResponse($participant, __FILE__, __FUNCTION__);
    $this->assertNotEquals($participant['is_error'], 1);


    if (!$participant['is_error']) {
      $params['id'] = CRM_Utils_Array::value('id', $participant);

      // Create $match array with DAO Field Names and expected values
      $match = [
        'id' => CRM_Utils_Array::value('id', $participant),
      ];
      // assertDBState compares expected values in $match to actual values in the DB
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $match);
    }
    // Cleanup created participant records.
    // $result = $this->participantDelete($params['id']);
  }



  ///////////////// civicrm_participant_delete methods

  /**
   * Test civicrm_participant_delete with wrong params type
   */
  function testDeleteWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('participant', 'delete', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_delete with empty params
   */
  function testDeleteEmptyParams() {
    $params = [];
    $result = civicrm_api('participant', 'delete', $params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with participant_id
   */
  /**
   * Participant Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Participant
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Participant&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Participant&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testDeleteParticipant() {
    $params = [
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
    ];
    //create one
    $create = civicrm_api('participant', 'create', $params);
    $this->assertAPISuccess($create, 'In line ' . __LINE__);

    $session = CRM_Core_Session::singleton();
    $session->set('userID', '1');
    $result = civicrm_api('participant', 'delete', $params);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertNotEquals($result['is_error'], 1);
    $this->assertDBState('CRM_Event_DAO_Participant', $this->_participantID, NULL, TRUE);
  }

  /**
   * check without participant_id
   * and with event_id
   * This should return an error because required param is missing..
   */
  function testParticipantDeleteMissingID() {
    $params = [
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    ];
    $participant = civicrm_api('participant', 'delete', $params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }
  /*
    * delete with a get - a 'criteria delete'
    */
  function testNestedDelete() {
    $description  = "Criteria delete by nesting a GET & a DELETE";
    $subfile      = "NestedDelete";
    $participants = civicrm_api('Participant', 'Get', ['version' => 3]);
    $this->assertEquals($participants['count'], 3);
    $params = ['version' => 3, 'contact_id' => $this->_contactID2, 'api.participant.delete' => 1];
    $participants = civicrm_api('Participant', 'Get', $params);
    $this->documentMe($params, $participants, __FUNCTION__, __FILE__, $description, $subfile, 'Get');
    $participants = civicrm_api('Participant', 'Get', ['version' => 3]);
    $this->assertEquals(1, $participants['count'], "only one participant should be left. line " . __LINE__);
  }
  /*
     * Test creation of a participant with an associated contribution
     */
  function testCreateParticipantWithPayment() {
    $this->_contributionTypeId = $this->contributionTypeCreate();
    $description = "single function to create contact w partipation & contribution. Note that in the
      case of 'contribution' the 'create' is implied (api.contribution.create)";
    $subfile = "CreateParticipantPayment";
    $params = [
      'contact_type' => 'Individual',
      'display_name' => 'dlobo',
      'version' => $this->_apiversion,
      'api.participant' => [
        'event_id' => $this->_eventID,
        'status_id' => 1,
        'role_id' => 1,
        'format.only_id' => 1,
      ],
      'api.contribution.create' => [
        'contribution_type_id' => $this->_contributionTypeId,
        'total_amount' => 100,
        'format.only_id' => 1,
      ],
      'api.participant_payment.create' => [
        'contribution_id' => '$value.api.contribution.create',
        'participant_id' => '$value.api.participant',
      ],
    ];

    $result = civicrm_api('contact', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['values'][$result['id']]['api.participant_payment.create']['count']);
    civicrm_api('contact', 'delete', ['id' => $result['id'], 'version' => $this->_apiversion]);
  }

  function testParticipantFormattedwithDuplicateParams() {
    $participantContact = $this->individualCreate(NULL);
    $params = [
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
      'version' => 3,
    ];
    require_once 'CRM/Event/Import/Parser.php';
    $onDuplicate = CRM_Event_Import_Parser::DUPLICATE_NOCHECK;
    $participant = _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  /**
   * Test civicrm_participant_formatted with wrong $onDuplicate
   */
  function testParticipantFormattedwithWrongDuplicateConstant() {
    $participantContact = $this->individualCreate(NULL);
    $params = [
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
      'version' => 3,
    ];
    $onDuplicate = 11;
    $participant = _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  function testParticipantcheckWithParams() {
    $participantContact = $this->individualCreate(NULL);
    $params = [
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
    ];
    require_once 'CRM/Event/Import/Parser.php';
    $participant = _civicrm_api3_deprecated_participant_check_params($params);
    $this->assertEquals($participant, TRUE, 'Check the returned True');
  }

  /**
   * check get with role id - create 2 registrations with different roles.
   * Test that get without role var returns 2 & with returns one
   TEST COMMENteD OUT AS HAVE GIVIEN UP ON using filters on get
   function testGetParamsRole()
   {
   require_once 'CRM/Event/PseudoConstant.php';
   CRM_Event_PseudoConstant::flush('participantRole');
   $participantRole2 = civicrm_api('Participant', 'Create', array('version' => 3, 'id' => $this->_participantID2, 'participant_role_id' => 2));

   $params = array(

   'version' => $this->_apiversion,

   );
   $result = civicrm_api('participant','get', $params);
   $this->assertEquals($result['is_error'], 0);
   $this->assertEquals($result['count'], 3);

   $params['participant_role_id'] =2;
   $result =  civicrm_api('participant','get', $params);

   $this->assertEquals($result['is_error'], 0,  "in line " . __LINE__);
   $this->assertEquals(2,$result['count'], "in line " . __LINE__);
   $this->documentMe($params,$result ,__FUNCTION__,__FILE__);
   }
   */
}

