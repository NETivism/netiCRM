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


require_once 'api/v2/Event.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_EventTest extends CiviUnitTestCase {
  protected $_params; function get_info() {
    return array(
      'name' => 'Event Create',
      'description' => 'Test all Event Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_params = array(
      'title' => 'Annual CiviCRM meet',
      'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
      'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20081021,
      'end_date' => 20081023,
      'is_online_registration' => 1,
      'registration_start_date' => 20080601,
      'registration_end_date' => 20081015,
      'max_participants' => 100,
      'event_full_text' => 'Sorry! We are already full',
      'is_monetory' => 0,
      'is_active' => 1,
      'is_show_location' => 0,
    );

    $params = array(
      'title' => 'Annual CiviCRM meet',
      'event_type_id' => 1,
      'start_date' => 20081021,
    );

    $this->_event = civicrm_event_create($params);
    $this->_eventId = $this->_event['event_id'];
  }

  function tearDown() {
    if ($this->_eventId) {
      $this->eventDelete($this->_eventId);
    }
    $this->eventDelete($this->_event['event_id']);
  }

  ///////////////// civicrm_event_get methods
  function testGetWrongParamsType() {
    $params = 'Annual CiviCRM meet';
    $result = civicrm_event_get($params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input parameters is not an array.');
  }

  function testGetEventEmptyParams() {
    $params = array();
    $result = civicrm_event_get($params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Params cannot be empty.');
  }

  function testGetEventById() {
    $params = array('id' => $this->_event['event_id']);
    $result = civicrm_event_get($params);
    $this->assertEquals($result['event_title'], 'Annual CiviCRM meet');
  }

  function testGetEventByEventTitle() {
    $params = array('title' => 'Annual CiviCRM meet');

    $result = civicrm_event_get($params);
    $this->assertEquals($result['id'], $this->_event['event_id']);
  }

  ///////////////// civicrm_event_create methods
  function testCreateEventParamsNotArray() {
    $params = NULL;
    $result = civicrm_event_create($params);
    $this->assertEquals(1, $result['is_error']);
    $this->assertEquals('Input parameters is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  function testCreateEventEmptyParams() {
    $params = array();
    $result = civicrm_event_create($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Mandatory param missing: start_date', $result['error_message'], 'In line ' . __LINE__);
  }

  function testCreateEventParamsWithoutTitle() {
    unset($this->_params['title']);
    $result = civicrm_event_create($this->_params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Mandatory param missing: title', $result['error_message'], 'In line ' . __LINE__);
  }

  function testCreateEventParamsWithoutEventTypeId() {
    unset($this->_params['event_type_id']);
    $result = civicrm_event_create($this->_params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Mandatory param missing: event_type_id', $result['error_message'], 'In line ' . __LINE__);
  }

  function testCreateEventParamsWithoutStartDate() {
    unset($this->_params['start_date']);
    $result = civicrm_event_create($this->_params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Mandatory param missing: start_date', $result['error_message'], 'In line ' . __LINE__);
  }

  function testCreateEvent() {
    $result = civicrm_event_create($this->_params);

    $this->assertEquals($result['is_error'], 0);
    $this->assertArrayHasKey('event_id', $result, 'In line ' . __LINE__);
  }

  ///////////////// civicrm_event_delete methods
  function testDeleteWrongParamsType() {
    $params = 'Annual CiviCRM meet';
    $result = &civicrm_event_delete($params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Invalid value for eventID');
  }

  function testDeleteEmptyParams() {
    $params = array();
    $result = &civicrm_event_delete($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testDelete() {
    $params = array('event_id' => $this->_eventId);
    $result = &civicrm_event_delete($params);
    $this->assertNotEquals($result['is_error'], 1);
  }

  function testDeleteWithWrongEventId() {
    $params = array('event_id' => $this->_eventId);
    $result = &civicrm_event_delete($params);
    // try to delete again - there's no such event anymore
    $params = array('event_id' => $this->_eventId);
    $result = &civicrm_event_delete($params);
    $this->assertEquals($result['is_error'], 1);
  }

  ///////////////// civicrm_event_search methods

  /**
   *  Test civicrm_event_search with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_event_search($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameters is not an array.', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_event_search with empty params
   */
  function testSearchEmptyParams() {
    $event = civicrm_event_create($this->_params);

    $params = array();
    $result = &civicrm_event_search($params);
    $res    = $result[$event['event_id']];

    $this->assertEquals($res['id'], $event['event_id'], 'In line ' . __LINE__);
    $this->assertEquals($res['title'], $this->_params['title'], 'In line ' . __LINE__);
    $this->assertEquals($res['event_type_id'], $this->_params['event_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($res['is_online_registration'], $this->_params['is_online_registration'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_event_search. Success expected.
   */
  function testSearch() {
    $params = array(
      'event_type_id' => 1,
      'return.title' => 1,
      'return.id' => 1,
      'return.start_date' => 1,
    );
    $result = &civicrm_event_search($params);

    $this->assertEquals($result[$this->_eventId]['id'], $this->_eventId, 'In line ' . __LINE__);
    $this->assertEquals($result[$this->_eventId]['title'], 'Annual CiviCRM meet', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_event_search. Success expected.
   *  return.offset and return.max_results test (CRM-5266)
   */
  function testSearchWithOffsetAndMaxResults() {
    $maxEvents = 5;
    $events = array();
    while ($maxEvents > 0) {
      $params = array(
        'title' => 'Test Event' . $maxEvents,
        'event_type_id' => 2,
        'start_date' => 20081021,
      );

      $events[$maxEvents] = civicrm_event_create($params);
      $maxEvents--;
    }
    $params = array(
      'event_type_id' => 2,
      'return.id' => 1,
      'return.title' => 1,
      'return.offset' => 2,
      'return.max_results' => 2,
    );
    $result = &civicrm_event_search($params);
    $this->assertEquals(count($result), 2, 'In line ' . __LINE__);
  }

  function testEventCreationPermissions() {
    require_once 'CRM/Core/Permission/UnitTests.php';
    $params = array('event_type_id' => 1, 'start_date' => '2010-10-03', 'title' => 'le cake is a tie', 'check_permissions' => TRUE);

    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM');
    $result = civicrm_event_create($params);
    $this->assertEquals(1, $result['is_error'], 'lacking permissions should not be enough to create an event');
    $this->assertEquals('API permission check failed for civicrm_event_create call; missing permission: access CiviEvent.', $result['error_message'], 'lacking permissions should not be enough to create an event');

    CRM_Core_Permission_UnitTests::$permissions = array('access CiviEvent', 'add contacts');
    $result = civicrm_event_create($params);
    $this->assertEquals(0, $result['is_error'], 'overfluous permissions should be enough to create an event');
  }
}

