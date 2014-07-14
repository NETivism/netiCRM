<?php
// $Id$

/**
 *  Include class definitions
 */
require_once 'tests/phpunit/CiviTest/CiviUnitTestCase.php';
require_once 'api/v3/CustomGroup.php';

/**
 *  Test APIv3 civicrm_create_custom_group
 *
 *  @package   CiviCRM
 */
class api_v3_CustomGroupTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_entity;
  protected $_params;
  public $DBResetRequired = TRUE; function get_info() {
    return array(
      'name' => 'Custom Group Create',
      'description' => 'Test all Custom Group Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;
    $this->_entity     = 'CustomGroup';
    $this->_params     = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => 'Individual',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    parent::setUp();
  }

  function tearDown() {
    $tablesToTruncate = array('civicrm_custom_group', 'civicrm_custom_field');
    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  ///////////////// civicrm_custom_group_create methods

  /**
   * check with empty array
   */
  function testCustomGroupCreateNoParam() {
    $params = array(
      'version' => $this->_apiversion,
    );
    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($customGroup['error_message'],
      'Mandatory key(s) missing from params array: title, extends', 'In line ' . __LINE__
    );
  }

  /**
   * check with empty array
   */
  function testCustomGroupCreateNoExtends() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: extends', 'In line ' . __LINE__);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testCustomGroupCreateInvalidExtends() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'extends' => array(),
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: extends', 'In line ' . __LINE__);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * check with a string instead of array for extends
   */
  function testCustomGroupCreateExtendsString() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'extends' => 'Individual',
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
  }

  /**
   * check with create fields
   */
  function testCustomGroupCreateWithFields() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual'),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('value1', 'value2'),
      'option_name' => array('name_1', 'name_2'),
      'option_weight' => array(1, 2),
      'label' => 'Country',
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['values'][$customGroup['id']]['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Individual', 'In line ' . __LINE__);
  }

  /**
   * check with valid array
   */
  function testCustomGroupCreate() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual'),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('custom_group', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['is_error'], 0, 'In line ' . __LINE__ . ': ' . $result['error_message']);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['extends'], 'Individual', 'In line ' . __LINE__);
  }

  /**
   * check with extends array length greater than 1
   */
  function testCustomGroupExtendsMultipleCreate() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual', 'Household'),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'implode(): Invalid arguments passed', 'In line ' . __LINE__);
  }

  /**
   * check with style missing from params array
   */
  function testCustomGroupCreateNoStyle() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual'),
      'weight' => 4,
      'collapse_display' => 1,
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['style'], 'Inline', 'In line ' . __LINE__);
  }

  /**
   * check with not array
   */
  function testCustomGroupCreateNotArray() {
    $params = NULL;
    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($customGroup['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  /**
   * check without title
   */
  function testCustomGroupCreateNoTitle() {
    $params = array('extends' => array('Contact'),
      'weight' => 5,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 2',
      'help_post' => 'This is Post Help For Test Group 2',
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: title', 'In line ' . __LINE__);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * check for household without weight
   */
  function testCustomGroupCreateHouseholdNoWeight() {
    $params = array(
      'title' => 'Test_Group_3',
      'name' => 'test_group_3',
      'extends' => array('Household'),
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 3',
      'help_post' => 'This is Post Help For Test Group 3',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Household', 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['style'], 'Tab', 'In line ' . __LINE__);
  }

  /**
   * check for Contribution Donation
   */
  function testCustomGroupCreateContributionDonation() {
    $params = array(
      'title' => 'Test_Group_6',
      'name' => 'test_group_6',
      'extends' => array('Contribution', array(1)),
      'weight' => 6,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 6',
      'help_post' => 'This is Post Help For Test Group 6',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Contribution', 'In line ' . __LINE__);
  }

  /**
   * check with valid array
   */
  function testCustomGroupCreateGroup() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_8',
      'name' => 'test_group_8',
      'extends' => array('Group'),
      'weight' => 7,
      'collapse_display' => 1,
      'is_active' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 8',
      'help_post' => 'This is Post Help For Test Group 8',
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Group', 'In line ' . __LINE__);
  }

  /**
   * check with Activity - Meeting Type
   */
  function testCustomGroupCreateActivityMeeting() {
    $params = array(
      'title' => 'Test_Group_10',
      'name' => 'test_group_10',
      'extends' => array('Activity', array(1)),
      'weight' => 8,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 10',
      'help_post' => 'This is Post Help For Test Group 10',
      'version' => $this->_apiversion,
    );

    $customGroup = civicrm_api('custom_group', 'create', $params);
    $this->assertEquals($customGroup['is_error'], 0, 'In line ' . __LINE__);
    $this->assertNotNull($customGroup['id'], 'In line ' . __LINE__);
    $this->assertEquals($customGroup['values'][$customGroup['id']]['extends'], 'Activity', 'In line ' . __LINE__);
  }

  ///////////////// civicrm_custom_group_delete methods

  /**
   * check without GroupID
   */
  function testCustomGroupDeleteWithoutGroupID() {
    $params = array(
      'version' => $this->_apiversion,
    );
    $customGroup = civicrm_api('custom_group', 'delete', $params);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($customGroup['error_message'], 'Mandatory key(s) missing from params array: id', 'In line ' . __LINE__);
  }

  /**
   * check with no array
   */
  function testCustomGroupDeleteNoArray() {
    $params = NULL;
    $customGroup = civicrm_api('custom_group', 'delete', $params);
    $this->assertEquals($customGroup['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($customGroup['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  /**
   * check with valid custom group id
   */
  function testCustomGroupDelete() {
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $params = array(
      'id' => $customGroup['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('custom_group', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['is_error'], 0, 'In line ' . __LINE__);
  }
  /*
     * main success get function
     */



  public function testGetCustomGroupSuccess() {

    civicrm_api($this->_entity, 'create', $this->_params);
    $params = array('version' => 3);
    $result = civicrm_api($this->_entity, 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['is_error'], 0, 'in line' . __LINE__);
    $values = $result['values'][$result['id']];
    foreach ($this->_params as $key => $value) {
      if ($key == 'version' || $key == 'weight') {
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
  }
}

