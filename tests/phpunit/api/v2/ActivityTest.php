<?php
// vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the TestActivity class
 *
 *  (PHP 5)
 *
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: ActivityTest.php 40328 2012-05-11 23:06:13Z allen $
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

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'api/v2/Activity.php';
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'Utils.php';

/**
 *  Test APIv2 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class api_v2_ActivityTest extends CiviUnitTestCase {

  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_contact creating contact 17
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating option group
    //  activity_type
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert a row in civicrm_option_value creating
    //  activity_type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_activity',
      'civicrm_option_group',
      'civicrm_option_value',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   *  Test civicrm_activities_get_contact()
   */
  function testActivitiesGetContact() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );

    //  Get activities associated with contact 17
    $params = array('contact_id' => 17);
    $result = civicrm_activity_get_contact($params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(2, count($result['result']),
      'In line ' . __LINE__
    );
    $this->assertEquals(2, count($result['result']),
      'In line ' . __LINE__
    );
    $this->assertEquals('Test activity type',
      $result['result'][4]['activity_name'],
      'In line ' . __LINE__
    );
    $this->assertEquals('Test activity type',
      $result['result'][13]['activity_name'],
      'In line ' . __LINE__
    );
  }

  /**
   * check with empty array
   */
  function testActivityCreateEmpty() {
    $params = array();
    $result = &civicrm_activity_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check if required fields are not passed
   */
  function testActivityCreateWithoutRequired() {
    $params = array(
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = &civicrm_activity_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with missing subject
   */
  function testActivityCreateMissingSubject() {
    $params = array(
      'source_contact_id' => 17,
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = civicrm_activity_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with mismatched activity_type_id
   *  and activity_name
   */
  function testActivityCreateMismatchNameType() {
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Test activity',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Fubar activity type',
      'activity_type_id' => 5,
      'scheduled_date_time' => date('Ymd'),
    );

    $result = civicrm_activity_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with missing source_contact_id
   */
  function testActivityCreateWithMissingContactId() {
    $params = array(
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $result = &civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with non-numeric source_contact_id
   */
  function testActivityCreateWithNonNumericContactId() {
    $params = array(
      'source_contact_id' => 'fubar',
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $result = &civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with non-numeric duration
   *  @todo Come back to this in later stages
   */
  /// we don't offer single parameter correctness checking at the moment
  //function testActivityCreateWithNonNumericDuration( )
  //{
  //    $params = array(
  //                    'source_contact_id'   => 17,
  //                    'subject'             => 'Discussion on Apis for v2',
  //                    'activity_date_time'  => date('Ymd'),
  //                    'duration'            => 'fubar',
  //                    'location'            => 'Pensulvania',
  //                    'details'             => 'a test activity',
  //                    'status_id'           => 1,
  //                    'activity_name'       => 'Test activity type'
  //                    );
  //
  //    $result = civicrm_activity_create($params);
  //
  //    $this->assertEquals( $result['is_error'], 1,
  //                         "In line " . __LINE__ );
  //}

  /**
   * check with incorrect required fields
   */
  function testActivityCreateWithNonNumericActivityTypeId() {
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Test activity type',
    );

    $result = civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityCreateWithUnknownActivityTypeId() {
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 6,
    );

    $result = &civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with valid parameters
   */
  function testActivityCreate() {
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $result = &civicrm_activity_create($params);
    $this->assertEquals($result['is_error'], 0,
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals($result['source_contact_id'], 17);
    $this->assertEquals($result['duration'], 120);
    $this->assertEquals($result['subject'], 'Discussion on Apis for v2');
    $this->assertEquals($result['activity_date_time'], date('Ymd') . '000000');
    $this->assertEquals($result['location'], 'Pensulvania');
    $this->assertEquals($result['details'], 'a test activity');
    $this->assertEquals($result['status_id'], 1);
  }

  /**
   *  Test civicrm_activity_create() with valid parameters
   *  and some custom data
   */
  function testActivityCreateCustom() {
    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating option group
    //  activity_type
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert a row in civicrm_option_value creating
    //  activity_type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    //  Insert rows in civicrm_custom_group and civicrm_custom_field
    //  creating Activity Custom to extend activity type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/custom_group_activity_type.xml'
      )
    );

    //  Drop and create table civicrm_value_activity_custom_9
    $query = 'DROP TABLE IF EXISTS civicrm_value_activity_custom_9';
    CRM_Core_DAO::executeQuery($query);
    $group              = new CRM_Core_DAO_CustomGroup();
    $group->extends     = "Activity";
    $group->table_name  = 'civicrm_value_activity_custom_9';
    $group->is_multiple = 0;
    $group->is_active   = 1;
    CRM_Core_BAO_CustomGroup::createTable($group);

    //  Add column activity_custom_11 to the custom table
    $customField = new CRM_Core_DAO_CustomField();
    $customField->column_name = 'activity_custom_11';
    $customField->custom_group_id = 9;
    $customField->is_required = 0;
    $customField->is_active = 1;
    $customField->data_type = 'String';
    $customField->text_length = 255;
    CRM_Core_BAO_CustomField::createField($customField, 'add');

    //  Create an activity with custom data
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'custom' => array(
        array(
          array(
            'value' => 'bite my test data',
            'type' => 'String',
            'custom_field_id' => 11,
            'custom_group_id' => 9,
            'table_name' => 'civicrm_value_activity_custom_9',
            'column_name' => 'activity_custom_11',
            'is_multiple' => 0,
            'file_id' => NULL,
          ),
        )),
    );
    $result = civicrm_activity_create($params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(1, $result['id'],
      'In line ' . __LINE__
    );

    //  Retrieve and check the activity created
    $params = array(
      'activity_id' => 1,
      'activity_type_id' => 1,
    );
    $result = civicrm_activity_get($params, TRUE);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(1, $result['result']['id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(17, $result['result']['source_contact_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(1, $result['result']['activity_type_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals('Discussion on Apis for v2',
      $result['result']['subject'],
      'In line ' . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with an invalid text status_id
   */
  function testActivityCreateBadTextStatus() {
    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating
    //  an activity_status option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert rows in civicrm_option_value defining activity status
    //  values of 'Scheduled', 'Completed', 'Cancelled'
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 'Invalid',
      'activity_name' => 'Test activity type',
    );

    $result = civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with valid parameters,
   *  using a text status_id
   */
  function testActivityCreateTextStatus() {
    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating
    //  an activity_status option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert rows in civicrm_option_value defining activity status
    //  values of 'Scheduled', 'Completed', 'Cancelled'
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 'Scheduled',
      'activity_name' => 'Test activity type',
    );

    $result = civicrm_activity_create($params);

    $this->assertEquals($result['is_error'], 0,
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals($result['source_contact_id'], 17);
    $this->assertEquals($result['duration'], 120);
    $this->assertEquals($result['subject'], 'Discussion on Apis for v2');
    $this->assertEquals($result['activity_date_time'], date('Ymd') . '000000');
    $this->assertEquals($result['location'], 'Pensulvania');
    $this->assertEquals($result['details'], 'a test activity');
    $this->assertEquals($result['status_id'], 'Scheduled');
  }

  /**
   *  Test civicrm_activity_get_contact()
   */
  function testActivityGetContact() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );

    //  Get activities associated with contact 17
    $params = array('contact_id' => 17);
    $result = civicrm_activity_get_contact($params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(2, count($result['result']),
      'In line ' . __LINE__
    );
    $this->assertEquals(2, count($result['result']),
      'In line ' . __LINE__
    );
    $this->assertEquals('Test activity type',
      $result['result'][4]['activity_name'],
      'In line ' . __LINE__
    );
    $this->assertEquals('Test activity type',
      $result['result'][13]['activity_name'],
      'In line ' . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_get() with no params
   */
  function testActivityGetEmpty() {
    $params = array();
    $result = civicrm_activity_get($params);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_get() with a non-numeric activity ID
   */
  function testActivityGetNonNumericID() {
    $params = array('activity_id' => 'fubar');
    $result = civicrm_activity_get($params);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_get() with a bad activity ID
   */
  function testActivityGetBadID() {
    $params = array('activity_id' => 42);
    $result = civicrm_activity_get($params);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_get() with a good activity ID
   */
  function testActivityGetGoodID() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );

    $params = array('activity_id' => 13);
    $result = civicrm_activity_get($params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(13, $result['result']['id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(17, $result['result']['source_contact_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(1, $result['result']['activity_type_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals("test activity type id",
      $result['result']['subject'],
      'In line ' . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_get() with a good activity ID which
   *  has associated custom data
   */
  function testActivityGetGoodIDCustom() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );

    //  Insert rows in civicrm_custom_group and civicrm_custom_field
    //  creating Activity Custom to extend activity type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/custom_group_activity_type.xml'
      )
    );

    //  Drop and create table civicrm_value_activity_custom_9
    $query = 'DROP TABLE IF EXISTS civicrm_value_activity_custom_9';
    CRM_Core_DAO::executeQuery($query);
    $group              = new CRM_Core_DAO_CustomGroup();
    $group->extends     = "Activity";
    $group->table_name  = 'civicrm_value_activity_custom_9';
    $group->is_multiple = 0;
    $group->is_active   = 1;
    CRM_Core_BAO_CustomGroup::createTable($group);

    //  Add column activity_custom_11 to the custom table
    $customField = new CRM_Core_DAO_CustomField();
    $customField->column_name = 'activity_custom_11';
    $customField->custom_group_id = 9;
    $customField->is_required = 0;
    $customField->is_active = 1;
    $customField->data_type = 'String';
    $customField->text_length = 255;
    CRM_Core_BAO_CustomField::createField($customField, 'add');

    //  Insert a test value into the new table
    //  Insert a test value into the new table
    $query = "INSERT INTO civicrm_value_activity_custom_9" . "( entity_id, activity_custom_11 )" . " VALUES ( 4,  'bite my test data' )";
    CRM_Core_DAO::executeQuery($query);

    //  Retrieve the test value
    $params = array(
      'activity_id' => 4,
      'activity_type_id' => 5,
    );
    $result = civicrm_activity_get($params, TRUE);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(4, $result['result']['id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(17, $result['result']['source_contact_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals(1, $result['result']['activity_type_id'],
      'In line ' . __LINE__
    );
    $this->assertEquals('test activity type id',
      $result['result']['subject'],
      'In line ' . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_get_types()
   */
  function testActivityGetTypes() {
    $result = civicrm_activity_get_types();
    $this->assertTrue(is_array($result),
      "In line " . __LINE__
    );
    $this->assertEquals('Test activity type', $result[1],
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion with empty params
   */
  function testDeleteActivityForEmptyParams() {
    $params = array();
    $result = &civicrm_activity_delete($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion without activity id
   */
  function testDeleteActivityWithoutId() {
    $params = array('activity_name' => 'Meeting');
    $result = &civicrm_activity_delete($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion without activity type
   */
  function testDeleteActivityWithoutActivityType() {
    $params = array('id' => 1);
    $result = &civicrm_activity_delete($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion with incorrect data
   */
  function testDeleteActivityWithIncorrectActivityType() {
    $params = array(
      'id' => 1,
      'activity_name' => 'Test Activity',
    );

    $result = &civicrm_activity_delete($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion with correct data
   */
  function testDeleteActivity() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );
    $params = array(
      'id' => 13,
      'activity_type_id' => 1,
    );

    $result = &civicrm_activity_delete($params);
    $this->assertEquals($result['is_error'], 0,
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
  }

  /**
   *  Test civicrm_activity_processmail() with non-existent file
   */
  function testActivityProcessEMailNoFile() {
    $result = civicrm_activity_process_email('no/such/file/nohow', 5);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_processmail() with unparseable file
   *  @todo Fix the bug that causes CiviCRM to crash and stop the test
   */
  //function testActivityProcessEMailUnparseableFile()
  //{
  //    $result = civicrm_activity_process_email(
  //        dirname( __FILE__ ) . '/dataset/activity_email_unparseable' , 5 );
  //    $this->assertEquals( $result['is_error'], 1,
  //                         "In line " . __LINE__ );
  //}

  /**
   *  Test civicrm_activity_processmail()
   */
  function testActivityProcessEMail() {
    //  Give contact 17 an email address
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/email_contact_17.xml'
      )
    );

    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating option group
    //  activity_type
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert rows in civicrm_option_value defining activity status
    //  values of 'Scheduled', 'Completed', 'Cancelled'
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    $result = civicrm_activity_process_email(
      dirname(__FILE__) . '/dataset/activity_email', 5
    );

    $this->assertEquals($result['is_error'], 0,
      "In line " . __LINE__
    );

    //  civicrm_activity should show the new activity
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/activity_1_emailed.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataSet(
      $this->_dbconn
    );
    $actual->addTable('civicrm_activity');

    $expected->assertEquals($actual);

    //  civicrm_activity_target should show the target of the new activity
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/activity_target_1_emailed.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataSet(
      $this->_dbconn
    );
    $actual->addTable('civicrm_activity_target');
    $expected->assertEquals($actual);
  }

  /**
   * check with empty array
   */
  function testActivityUpdateEmpty() {
    $params = array();
    $result = &civicrm_activity_update($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check if required fields are not passed
   */
  function testActivityUpdateWithoutRequired() {
    $params = array(
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = &civicrm_activity_update($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityUpdateWithIncorrectData() {
    $params = array(
      'activity_name' => 'Meeting',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = &civicrm_activity_update($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_activity_update() with non-numeric id
   */
  function testActivityUpdateWithNonNumericId() {
    $params = array(
      'id' => 'lets break it',
      'activity_name' => 'Meeting',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = &civicrm_activity_update($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityUpdateWithIncorrectContactActivityType() {
    $params = array(
      'id' => 1,
      'activity_name' => 'Test Activity',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = &civicrm_activity_update($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
    $this->assertEquals($result['error_message'], 'Invalid Activity Id');
  }

  /**
   *  Test civicrm_activity_update() to update an existing activity
   */
  function testActivityUpdate() {
    //  Insert rows in civicrm_activity creating activities 4 and 13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );

    $params = array(
      'id' => 4,
      'subject' => 'Update Discussion on Apis for v2',
      'activity_date_time' => '20091011123456',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'source_contact_id' => 17,
      'priority_id' => 1,
    );

    $result = &civicrm_activity_update($params);

    //  civicrm_activity should show new values
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/activity_4_13_updated.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataset(
      $this->_dbconn
    );
    $actual->addTable('civicrm_activity');
    $expected->assertEquals($actual);
  }

  /**
   *  Test civicrm_activity_update() with valid parameters
   *  and some custom data
   */
  function testActivityUpdateCustom() {
    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating option group
    //  activity_type
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert a row in civicrm_option_value creating
    //  activity_type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    //  Insert rows in civicrm_custom_group and civicrm_custom_field
    //  creating Activity Custom to extend activity type 5
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/custom_group_activity_type.xml'
      )
    );

    //  Drop and create table civicrm_value_activity_custom_9
    $query = 'DROP TABLE IF EXISTS civicrm_value_activity_custom_9';
    CRM_Core_DAO::executeQuery($query);
    $group              = new CRM_Core_DAO_CustomGroup();
    $group->extends     = "Activity";
    $group->table_name  = 'civicrm_value_activity_custom_9';
    $group->is_multiple = 0;
    $group->is_active   = 1;
    CRM_Core_BAO_CustomGroup::createTable($group);

    //  Add column activity_custom_11 to the custom table
    $customField = new CRM_Core_DAO_CustomField();
    $customField->column_name = 'activity_custom_11';
    $customField->custom_group_id = 9;
    $customField->is_required = 0;
    $customField->is_active = 1;
    $customField->data_type = 'String';
    $customField->text_length = 255;
    $customFiledId = CRM_Core_BAO_CustomField::createField($customField, 'add');

    //  Create an activity with custom data
    $params = array(
      'source_contact_id' => 17,
      'subject' => 'Discussion on Activity Apis for v2',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity to check the update api',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'custom' => array(
        array(
          array(
            'value' => 'bite my test data',
            'type' => 'String',
            'custom_field_id' => 11,
            'custom_group_id' => 9,
            'table_name' => 'civicrm_value_activity_custom_9',
            'column_name' => 'activity_custom_11',
            'is_multiple' => 0,
            'file_id' => NULL,
          ),
        )),
    );
    $result = civicrm_activity_create($params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(1, $result['id'],
      'In line ' . __LINE__
    );

    //  Retrieve and check the activity created
    $params = array(
      'activity_id' => 1,
      'activity_type_id' => 1,
    );
    $result = civicrm_activity_get($params, TRUE);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals(1, $result['result']['id'],
      'In line ' . __LINE__
    );
    $this->assertEquals('Discussion on Activity Apis for v2',
      $result['result']['subject'],
      'In line ' . __LINE__
    );

    //  Update the activity with custom data
    $params = array(
      'id' => $result['result']['id'],
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v2 - Activity Update',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'custom_11' => 'Updated my test data',
    );
    $result = civicrm_activity_update($params);

    //  Retrieve and check the activity created
    $params = array(
      'activity_id' => $result['id'],
      'activity_type_id' => 1,
    );
    $result = civicrm_activity_get($params, TRUE);

    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $this->assertEquals('Discussion on Apis for v2 - Activity Update',
      $result['result']['subject'],
      'In line ' . __LINE__
    );

    // Check that the custom field value is update
    $params = array(
      'entityID' => $result['result']['id'],
      'custom_11' => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    // check for updated custom value
    $this->assertEquals('Updated my test data',
      $values['custom_11'],
      'In line ' . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_update() where the DB has a date_time
   *  value and there is none in the update params.
   */
  function testActivityUpdateNotDate() {
    //  Insert rows in civicrm_activity creating activities 4 and 13
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_4_13.xml'
      )
    );
    //
    $params = array(
      'id' => 4,
      'subject' => 'Update Discussion on Apis for v2',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'source_contact_id' => 17,
      'priority_id' => 1,
    );

    $result = &civicrm_activity_update($params);

    //  civicrm_activity should show new values except date
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/activity_4_13_updated_not_date.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataset(
      $this->_dbconn
    );
    $actual->addTable('civicrm_activity');
    $expected->assertEquals($actual);
  }

  /**
   * check activity update with status
   */
  function testActivityUpdateWithStatus() {
    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );

    //  Insert a row in civicrm_option_group creating
    //  an activity_status option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert rows in civicrm_option_value defining activity status
    //  values of 'Scheduled', 'Completed', 'Cancelled'
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    //  Insert a row in civicrm_activity creating activity 1
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/activity_type_5.xml'
      )
    );
    $params = array(
      'id' => 4,
      'source_contact_id' => 17,
      'subject' => 'Hurry update works',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $result = &civicrm_activity_update($params);
    $this->assertNotContains('is_error', $result);
    $this->assertEquals($result['id'], 4,
      "In line " . __LINE__
    );
    $this->assertEquals($result['source_contact_id'], 17,
      "In line " . __LINE__
    );
    $this->assertEquals($result['subject'], 'Hurry update works',
      "In line " . __LINE__
    );
    $this->assertEquals($result['status_id'], 1,
      "In line " . __LINE__
    );
  }
}
// class api_v2_ActivityTest

// -- set Emacs parameters --
// Local variables:
// mode: php;
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

