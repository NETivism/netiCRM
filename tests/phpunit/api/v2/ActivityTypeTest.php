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
require_once 'api/v2/ActivityType.php';

/**
 *  Test APIv2 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class api_v2_ActivityTypeTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Activity Type',
      'description' => 'Test all ActivityType Get/Create/Delete methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDown() {}

  /**
   *  Test civicrm_activity_type_get()
   */
  function testActivityTypeCheckValues() {
    $activitytypes = &civicrm_activity_type_get();
    $this->assertEquals($activitytypes['1'], 'Meeting', 'In line ' . __LINE__);
    $this->assertEquals($activitytypes['13'], 'Open Case', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_type_create with no label()
   */
  function testActivityTypeCreate() {

    $params = array(
      'weight' => '2',
    );
    $activitycreate = &civicrm_activity_type_create($params);
    $this->assertEquals($activitycreate['is_error'], 1);
    $this->assertEquals($activitycreate['error_message'], 'Required parameter "label / weight" not found');
  }

  /**
   *  Test civicrm_activity_type_create - check id
   */
  function testActivityTypeCreateCheckId() {

    $params = array(
      'label' => 'type_create',
      'weight' => '2',
    );
    $activityTypeCreate = &civicrm_activity_type_create($params);
    $activityTypeID = $activityTypeCreate['id'];

    $this->assertNotContains('is_error', $activityTypeCreate);
    $this->assertArrayHasKey('id', $activityTypeCreate);
    $this->assertArrayHasKey('option_group_id', $activityTypeCreate);
    $this->activityTypeDelete($activityTypeID);
  }

  /**
   *  Test civicrm_activity_type_delete()
   */
  function testActivityTypeDelete() {

    $params = array(
      'label' => 'type_create_delete',
      'weight' => '2',
    );
    $activityTypeCreate = $this->activityTypeCreate($params);
    $params             = array('activity_type_id' => $activityTypeCreate['id']);
    $activityTypeDelete = civicrm_activity_type_delete($params);
    $this->assertEquals($activityTypeDelete, 1, 'In line ' . __LINE__);
  }
}

