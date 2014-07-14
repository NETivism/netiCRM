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



require_once 'api/v2/Participant.php';

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_ParticipantTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_createdParticipants;
  protected $_participantID;
  protected $_eventID;
  protected $_individualId;
  protected $_contactID2; function get_info() {
    return array(
      'name' => 'Participant Create',
      'description' => 'Test all Participant Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $event = $this->eventCreate();
    $this->_eventID = $event['id'];

    $this->_contactID = $this->individualCreate();
    $this->_createdParticipants = array();
    $this->_individualId = $this->individualCreate();

    $this->_participantID = $this->participantCreate(array('contactID' => $this->_contactID, 'eventID' => $this->_eventID));
    $this->_contactID2 = $this->individualCreate();

    $this->_participantID2 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID));
    $this->_participantID3 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID));
  }

  function tearDown() {
    $this->eventDelete($this->_eventID);
    $this->contactDelete($this->_contactID);
    $this->contactDelete($this->_individualId);
    $this->contactDelete($this->_contactID2);
  }

  ///////////////// civicrm_participant_get methods

  /**
   * check with wrong params type
   */
  function testGetWrongParamsType() {
    $params = 'a string';
    $result = civicrm_participant_get($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_get with empty params
   */
  function testGetEmptyParams() {
    $params = array();
    $result = &civicrm_participant_get($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with participant_id
   */
  function testGetParticipantIdOnly() {
    $params = array(
      'participant_id' => $this->_participantID,
    );
    $participant = &civicrm_participant_get($params);
    $this->assertEquals($participant['event_id'], $this->_eventID);
    $this->assertEquals($participant['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant['participant_source'], 'Wimbeldon');
  }

  /**
   * check with params id
   */
  function testGetParamsAsIdOnly() {
    $params = array(
      'id' => $this->_participantID,
    );
    $participant = &civicrm_participant_get($params);
    $this->assertEquals($participant['event_id'], $this->_eventID);
    $this->assertEquals($participant['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant['participant_source'], 'Wimbeldon');
  }

  /**
   * check with contact_id
   */
  function testGetContactIdOnly() {
    $params = array(
      'contact_id' => $this->_contactID,
    );
    $participant = &civicrm_participant_get($params);

    $this->assertEquals($this->_participantID, $participant['participant_id'],
      "In line " . __LINE__
    );
    $this->assertEquals($this->_eventID, $participant['event_id'],
      "In line " . __LINE__
    );
    $this->assertEquals('2007-02-19 00:00:00', $participant['participant_register_date'],
      "In line " . __LINE__
    );
    $this->assertEquals('Wimbeldon', $participant['participant_source'],
      "In line " . __LINE__
    );
  }

  /**
   * check with event_id
   * fetch first record
   */
  function testGetMultiMatchReturnFirst() {
    $params = array(
      'event_id' => $this->_eventID,
      'returnFirst' => 1,
    );

    $participant = &civicrm_participant_get($params);

    $this->assertNotNull($participant['participant_id']);
  }

  /**
   * check with event_id
   * This should return an error because there will be at least 2 participants.
   */
  function testGetMultiMatchNoReturnFirst() {
    $params = array(
      'event_id' => $this->_eventID,
    );
    $participant = &civicrm_participant_get($params);

    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  ///////////////// civicrm_participant_search methods

  /**
   * Test civicrm_participant_search with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_search($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_search with empty params
   * In this case all the participant records are returned.
   */
  function testSearchEmptyParams() {
    $params = array();
    $result = &civicrm_participant_search($params);

    // expecting 3 participant records
    $this->assertEquals(count($result), 3);
  }

  /**
   * check with participant_id
   */
  function testSearchParticipantIdOnly() {
    $params = array(
      'participant_id' => $this->_participantID,
    );
    $participant = &civicrm_participant_search($params);
    $this->assertEquals($participant[$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($participant[$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant[$this->_participantID]['participant_source'], 'Wimbeldon');
  }

  /**
   * check with contact_id
   */
  function testSearchContactIdOnly() {
    // Should get 2 participant records for this contact.
    $params = array(
      'contact_id' => $this->_contactID2,
    );
    $participant = &civicrm_participant_search($params);

    $this->assertEquals(count($participant), 2);
  }

  /**
   * check with event_id
   */
  function testSearchByEvent() {
    // Should get >= 3 participant records for this event. Also testing that last_name and event_title are returned.
    $params = array(
      'event_id' => $this->_eventID,
      'return.last_name' => 1,
      'return.event_title' => 1,
    );
    $participant = &civicrm_participant_search($params);
    if (count($participant) < 3) {
      $this->fail("Event search returned less than expected miniumum of 3 records.");
    }

    $this->assertEquals($participant[$this->_participantID]['last_name'], 'Anderson');
    $this->assertEquals($participant[$this->_participantID]['event_title'], 'Annual CiviCRM meet');
  }

  /**
   * check with event_id
   * fetch with limit
   */
  function testSearchByEventWithLimit() {
    // Should 2 participant records since we're passing rowCount = 2.
    $params = array(
      'event_id' => $this->_eventID,
      'rowCount' => 3,
    );
    $participant = &civicrm_participant_search($params);

    $this->assertEquals(count($participant), 3);
  }

  ///////////////// civicrm_participant_create methods

  /**
   * Test civicrm_participant_create with wrong params type
   */
  function testCreateWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_create($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_create with empty params
   */
  function testCreateEmptyParams() {
    $params = array();
    $result = &civicrm_participant_create($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with event_id
   */
  function testCreateMissingContactID() {
    $params = array(
      'event_id' => $this->_eventID,
    );
    $participant = &civicrm_participant_create($params);
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
    $params = array(
      'contact_id' => $this->_contactID,
    );
    $participant = &civicrm_participant_create($params);
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
    $params = array(
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
    );
    $participant = &civicrm_participant_create($params);
    $this->assertNotEquals($participant['is_error'], 1);
    $this->_participantID = $participant['result'];

    if (!$participant['is_error']) {
      $this->_createdParticipants[] = CRM_Utils_Array::value('result', $participant);
      // Create $match array with DAO Field Names and expected values
      $match = array(
        'id' => CRM_Utils_Array::value('result', $participant),
      );
      // assertDBState compares expected values in $match to actual values in the DB
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['result'], $match);
    }
  }

  /**
   * check with complete array
   */
  function testCreateAllParams() {
    $params = array(
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      'register_date' => '2007-07-21',
      'source' => 'Online Event Registration: API Testing',
      'event_level' => 'Tenor',
    );

    $participant = &civicrm_participant_create($params);
    $this->assertNotEquals($participant['is_error'], 1);
    $this->_participantID = $participant['result'];
    if (!$participant['is_error']) {
      $this->_createdParticipants[] = CRM_Utils_Array::value('result', $participant);

      // Create $match array with DAO Field Names and expected values
      $match = array(
        'id' => CRM_Utils_Array::value('result', $participant),
      );
      // assertDBState compares expected values in $match to actual values in the DB
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['result'], $match);
    }
  }

  ///////////////// civicrm_participant_update methods

  /**
   * Test civicrm_participant_update with wrong params type
   */
  function testUpdateWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_update($params);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals('Parameters is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testUpdateEmptyParams() {
    $params = array();
    $participant = &civicrm_participant_update($params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Required parameter missing');
  }

  /**
   * check without event_id
   */
  function testUpdateWithoutEventId() {
    $participantId = $this->participantCreate(array('contactID' => $this->_individualId, 'eventID' => $this->_eventID));
    $params = array(
      'contact_id' => $this->_individualId,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    );
    $participant = &civicrm_participant_create($params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Required parameter missing');
    // Cleanup created participant records.
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with Invalid participantId
   */
  function testUpdateWithWrongParticipantId() {
    $params = array(
      'id' => 1234,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    );
    $participant = &civicrm_participant_update($params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Participant  id is not valid');
  }

  /**
   * check with Invalid ContactId
   */
  function testUpdateWithWrongContactId() {
    $participantId = $this->participantCreate(array(
      'contactID' => $this->_individualId,
        'eventID' => $this->_eventID,
      ));
    $params = array(
      'id' => $participantId,
      'contact_id' => 12345,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    );
    $participant = &civicrm_participant_update($params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertEquals($participant['error_message'], 'Contact id is not valid');
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with complete array
   */
  function testUpdate() {
    $participantId = $this->participantCreate(array('contactID' => $this->_individualId, 'eventID' => $this->_eventID));
    $params = array(
      'id' => $participantId,
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    );
    $participant = &civicrm_participant_update($params);
    $this->assertNotEquals($participant['is_error'], 1);


    if (!$participant['is_error']) {
      $params['id'] = CRM_Utils_Array::value('id', $participant);

      // Create $match array with DAO Field Names and expected values
      $match = array(
        'id' => CRM_Utils_Array::value('id', $participant),
      );
      // assertDBState compares expected values in $match to actual values in the DB
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $match);
    }
    // Cleanup created participant records.
    $result = $this->participantDelete($params['id']);
  }



  ///////////////// civicrm_participant_delete methods

  /**
   * Test civicrm_participant_delete with wrong params type
   */
  function testDeleteWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_delete($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_delete with empty params
   */
  function testDeleteEmptyParams() {
    $params = array();
    $result = &civicrm_participant_delete($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with participant_id
   */
  function testParticipantDelete() {
    $params = array(
      'id' => $this->_participantID,
    );
    $participant = &civicrm_participant_delete($params);
    $this->assertNotEquals($participant['is_error'], 1);
    $this->assertDBState('CRM_Event_DAO_Participant', $this->_participantID, NULL, TRUE);
  }

  /**
   * check without participant_id
   * and with event_id
   * This should return an error because required param is missing..
   */
  function testParticipantDeleteMissingID() {
    $params = array(
      'event_id' => $this->_eventID,
    );
    $participant = &civicrm_participant_delete($params);
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  ///////////////// civicrm_create_participant_formatted methods

  /**
   * Test civicrm_participant_formatted Empty  params type
   */
  function testParticipantFormattedEmptyParams() {
    $params      = array();
    $onDuplicate = array();
    $participant = &civicrm_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['error_message'], 'Input Parameters empty');
  }

  function testParticipantFormattedwithDuplicateParams() {
    $participantContact = $this->individualCreate();
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
    );
    require_once 'CRM/Event/Import/Parser.php';
    $onDuplicate = CRM_Event_Import_Parser::DUPLICATE_NOCHECK;
    $participant = &civicrm_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  /**
   * Test civicrm_participant_formatted with wrong $onDuplicate
   */
  function testParticipantFormattedwithWrongDuplicateConstant() {
    $participantContact = $this->individualCreate();
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
    );
    $onDuplicate = 11;
    $participant = &civicrm_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  function testParticipantcheckWithParams() {
    $participantContact = $this->individualCreate();
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
    );
    require_once 'CRM/Event/Import/Parser.php';
    $participant = &civicrm_participant_check_params($params);
    $this->assertEquals($participant, TRUE, 'Check the returned True');
  }

  ///////////////// civicrm_participant_payment_create methods

  /**
   * Test civicrm_participant_payment_create with wrong params type
   */
  function testPaymentCreateWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_payment_create($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_participant_payment_create with empty params
   */
  function testPaymentCreateEmptyParams() {
    $params = array();
    $result = &civicrm_participant_payment_create($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check without participant_id
   */
  function testPaymentCreateMissingParticipantId() {
    $contributionTypeID = $this->contributionTypeCreate();

    //Create Contribution & get entity ID
    $contributionID = $this->contributionCreate($this->_contactID, $contributionTypeID);

    //WithoutParticipantId
    $params = array(
      'contribution_id' => $contributionID,
    );
    $participantPayment = &civicrm_participant_payment_create($params);
    $this->assertEquals($participantPayment['is_error'], 1);

    //delete created contribution
    $this->contributionDelete($contributionID);

    // delete created contribution type
    $this->contributionTypeDelete();
  }

  /**
   * check without contribution_id
   */
  function testPaymentCreateMissingContributionId() {
    //Without Payment EntityID
    $params = array(
      'participant_id' => $this->_participantID,
    );
    $participantPayment = &civicrm_participant_payment_create($params);
    $this->assertEquals($participantPayment['is_error'], 1);
  }

  /**
   * check with valid array
   */
  function testPaymentCreate() {

    $contributionTypeID = $this->contributionTypeCreate();

    //Create Contribution & get contribution ID
    $contributionID = $this->contributionCreate($this->_contactID, $contributionTypeID);

    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    );
    $participantPayment = &civicrm_participant_payment_create($params);
    $this->assertEquals($participantPayment['is_error'], 0);
    $this->assertTrue(array_key_exists('id', $participantPayment));

    //delete created contribution
    $this->contributionDelete($contributionID);

    // delete created contribution type
    $this->contributionTypeDelete();
  }


  ///////////////// civicrm_participant_payment_update methods

  /**
   * Test civicrm_participant_payment_update with wrong params type
   */
  function testPaymentUpdateWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_payment_update($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals('Params is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testPaymentUpdateEmpty() {
    $params = array();
    $participantPayment = &civicrm_participant_payment_update($params);
    $this->assertEquals($participantPayment['is_error'], 1);
  }

  /**
   * check with missing participant_id
   */
  function testPaymentUpdateMissingParticipantId() {
    //WithoutParticipantId
    $params = array(
      'contribution_id' => '3',
    );
    $participantPayment = &civicrm_participant_payment_update($params);
    $this->assertEquals($participantPayment['is_error'], 1);
  }

  /**
   * check with missing contribution_id
   */
  function testPaymentUpdateMissingContributionId() {
    $params = array(
      'participant_id' => $this->_participantID,
    );
    $participantPayment = &civicrm_participant_payment_update($params);
    $this->assertEquals($participantPayment['is_error'], 1);
  }

  /**
   * check with complete array
   */
  function testPaymentUpdate() {
    $contributionTypeID = $this->contributionTypeCreate();

    // create contribution
    $contributionID = $this->contributionCreate($this->_contactID, $contributionTypeID);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);
    $params = array(
      'id' => $this->_participantPaymentID,
      'participant_id' => $this->_participantID,
      'contribution_id' => $contributionID,
    );

    // Update Payment
    $participantPayment = &civicrm_participant_payment_update($params);
    $this->assertEquals($participantPayment['id'], $this->_participantPaymentID);
    $this->assertTrue(array_key_exists('id', $participantPayment));

    $this->participantPaymentDelete($this->_participantPaymentID);
    $this->contributionTypeDelete();
  }

  ///////////////// civicrm_participant_payment_delete methods

  /**
   * Test civicrm_participant_payment_delete with wrong params type
   */
  function testPaymentDeleteWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_participant_payment_delete($params);

    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testPaymentDeleteWithEmptyParams() {
    $params = array();
    $deletePayment = &civicrm_participant_payment_delete($params);
    $this->assertEquals($deletePayment['is_error'], 1);
    $this->assertEquals($deletePayment['error_message'], 'Invalid or no value for Participant payment ID');
  }

  /**
   * check with wrong id
   */
  function testPaymentDeleteWithWrongID() {
    $params = array('id' => 0);
    $deletePayment = &civicrm_participant_payment_delete($params);
    $this->assertEquals($deletePayment['is_error'], 1);
    $this->assertEquals($deletePayment['error_message'], 'Invalid or no value for Participant payment ID');
  }

  /**
   * check with valid array
   */
  function testPaymentDelete() {
    $contributionTypeID = $this->contributionTypeCreate();

    // create contribution
    $contributionID = $this->contributionCreate($this->_contactID, $contributionTypeID);

    $this->_participantPaymentID = $this->participantPaymentCreate($this->_participantID, $contributionID);

    $params = array('id' => $this->_participantPaymentID);
    $deletePayment = &civicrm_participant_payment_delete($params);
    $this->assertEquals($deletePayment['is_error'], 0);

    $this->contributionTypeDelete();
  }
}

