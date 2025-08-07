<?php
require_once 'api/v2/Group.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_GroupTest extends CiviUnitTestCase {
  protected $_groupID; function get_info() {
    return [
      'name' => 'Group Get',
      'description' => 'Test all Group Get API methods.',
      'group' => 'CiviCRM API Tests',
    ];
  }

  function setUp() {
    parent::setUp();
    $this->_groupID = $this->groupCreate();
  }

  function tearDown() {
    $this->groupDelete($this->_groupID);
  }

  function testgroupAddEmptyParams() {
    $params = [];
    $group = &civicrm_group_add($params);
    $this->assertEquals($group['error_message'], 'Required parameter missing');
  }

  function testgroupAddNoTitle() {
    $params = [
      'name' => 'Test Group No title ',
      'domain_id' => 1,
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => [
        '1' => 1,
        '2' => 1,
      ],
    ];

    $group = &civicrm_group_add($params);
    $this->assertEquals($group['error_message'], 'Required parameter title missing');
  }

  function testGetGroupEmptyParams() {
    $params = '';
    $group = civicrm_group_get($params);

    $this->assertEquals($group['error_message'], 'Params should be array');
  }

  function testGetGroupWithEmptyParams() {
    $params = [];

    $group = civicrm_group_get($params);

    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], 'Test Group 1');
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  function testGetGroupParamsWithGroupId() {
    $params       = [];
    $params['id'] = $this->_groupID;
    $group        = &civicrm_group_get($params);

    foreach ($group as $v) {
      $this->assertEquals($v['name'], 'Test Group 1');
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithGroupName() {
    $params         = [];
    $params['name'] = 'Test Group 1';
    $group          = &civicrm_group_get($params);

    foreach ($group as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithReturnName() {
    $params = [];
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = &civicrm_group_get($params);
    $this->assertEquals($group[$this->_groupID]['name'], 'Test Group 1');
  }

  function testGetGroupParamsWithGroupTitle() {
    $params          = [];
    $params['title'] = 'New Test Group Created';
    $group           = &civicrm_group_get($params);

    foreach ($group as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['name'], 'Test Group 1');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetNonExistingGroup() {
    $params          = [];
    $params['title'] = 'No such group Exist';
    $group           = &civicrm_group_get($params);
    $this->assertEquals($group['error_message'], 'No such group exists');
  }

  function testgroupdeleteNonArrayParams() {
    $params = 'TestNotArray';
    $group = &civicrm_group_delete($params);
    $this->assertEquals($group['error_message'], 'Required parameter missing');
  }

  function testgroupdeleteParamsnoId() {
    $params = [];
    $group = &civicrm_group_delete($params);
    $this->assertEquals($group['error_message'], 'Required parameter missing');
  }
}

