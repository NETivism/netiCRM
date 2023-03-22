<?php
/**
 * Group Unit Test
 *
 * @docmaker_intro_start
 * @api_title Group
 * This is a API Document about Group.
 * @docmaker_intro_end
 */

// require_once 'api/v3/Group.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_GroupTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_groupID; function get_info() {
    return array(
      'name' => 'Group Get',
      'description' => 'Test all Group Get API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;

    parent::setUp();
    $this->_groupID = $this->groupCreate(NULL, 3);

  }

  function tearDown() {

    $this->groupDelete($this->_groupID);
  }

  /**
   * Group Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Group
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Group&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Group&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testCreateGroup() {
    $params_create = array(
      'name' => 'Test Group 1',
      'domain_id' => 1,
      'title' => 'New Test Group Created',
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'version' => $this->_apiversion,
      'group_type' => '1',
    );
    $result_create = civicrm_api('group', 'create', $params_create);
    $this->_groupID = $result_create->id;
    $this->docMakerRequest($params_create, __FILE__, __FUNCTION__);
    $result = civicrm_api('group', 'create', $params_create);
    $this->assertAPISuccess($result_create, 'In line ' . __LINE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }
  function testgroupCreateEmptyParams() {
    $params = array();
    $group = civicrm_api('group', 'create', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, title');
  }

  function testgroupCreateNoTitle() {
    $params = array(
      'name' => 'Test Group No title ',
      'domain_id' => 1,
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => array(
        '1' => 1,
        '2' => 1,
      ),
    );

    $group = civicrm_api('group', 'create', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, title');
  }

  function testGetGroupEmptyParams() {
    $params = '';
    $group = civicrm_api('group', 'get', $params);

    $this->assertEquals($group['error_message'], 'Input variable `params` is not an array');
  }

  function testGetGroupWithEmptyParams() {
    $params = array('version' => $this->_apiversion);

    $group = civicrm_api('group', 'get', $params);

    $group = $group["values"];
    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], "Test Group 1_{$this->_groupID}");
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  function testGetGroupParamsWithGroupId() {
    $params       = array('version' => $this->_apiversion);
    $params['id'] = $this->_groupID;
    $group        = civicrm_api('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['name'], "Test Group 1_{$this->_groupID}");
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithGroupName() {
    $params         = array('version' => $this->_apiversion);
    $params['name'] = "Test Group 1_{$this->_groupID}";
    $group          = civicrm_api('group', 'get', $params);
    $this->documentMe($params, $group, __FUNCTION__, __FILE__);
    $group = $group['values'];

    foreach ($group as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithReturnName() {
    $params = array('version' => $this->_apiversion);
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = civicrm_api('group', 'get', $params);
    $this->assertEquals($group['values'][$this->_groupID]['name'],
      "Test Group 1_{$this->_groupID}"
    );
  }

  function testGetGroupParamsWithGroupTitle() {
    $params          = array('version' => $this->_apiversion);
    $params['title'] = 'New Test Group Created';
    $group           = civicrm_api('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['name'], "Test Group 1_{$this->_groupID}");
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetNonExistingGroup() {
    $params          = array('version' => $this->_apiversion);
    $params['title'] = 'No such group Exist';
    $group           = civicrm_api('group', 'get', $params);
    $this->assertEquals(0, $group['is_error']);
  }

  function testgroupdeleteNonArrayParams() {
    $params = 'TestNotArray';
    $group = civicrm_api('group', 'delete', $params);
    $this->assertEquals($group['error_message'], 'Input variable `params` is not an array');
  }

  function testgroupdeleteParamsnoId() {
    $params = array();
    $group = civicrm_api('group', 'delete', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  function testgetfields() {
    $description = "demonstrate use of getfields to interogate api";
    $params      = array('version' => 3, 'action' => 'create');
    $result      = civicrm_api('group', 'getfields', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'getfields', 'getfields');
    $this->assertEquals(1, $result['values']['is_active']['api.default']);
  }
}

