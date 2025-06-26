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
    return [
      'name' => 'Group Get',
      'description' => 'Test all Group Get API methods.',
      'group' => 'CiviCRM API Tests',
    ];
  }

  /**
   * @before
   */
  function setUpTest() {
    $this->_apiversion = 3;

    parent::setUp();
    $this->_groupID = $this->groupCreate(NULL, 3);
  }

  /**
   * @after
   */
  function tearDownTest() {
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
   * @group CItesting
   */
  public function testCreateGroup() {
    $params_create = [
      'name' => 'Test Group 1 For Create',
      'domain_id' => 1,
      'title' => 'New Test Group For Create',
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'group_type' => '1,2',
      'visibility' => 'Public Pages',
      'version' => $this->_apiversion,
    ];
    $result_create = civicrm_api('group', 'create', $params_create);
    $this->docMakerRequest($params_create, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result_create, 'In line ' . __LINE__);
    $this->docMakerResponse($result_create, __FILE__, __FUNCTION__);
    $this->groupDelete($result_create['id']);
  }

  /**
   * Group Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Group
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Group&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Group&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testUpdateGroup() {
    $params_update = [
      'id' => $this->_groupID,
      'title' => 'New Update title for title',
      'description' => 'New Update title for description',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'version' => $this->_apiversion,
    ];
    $this->docMakerRequest($params_update, __FILE__, __FUNCTION__);
    $result = civicrm_api('group', 'create', $params_update);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][$this->_groupID]['title'], 'New Update title for title');
    $this->assertEquals($result['values'][$this->_groupID]['description'], 'New Update title for description');
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  /**
   * Group Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Group
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Group&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Group&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testDeleteGroup() {
    $params = [
      'id' => $this->_groupID,
      'version' => $this->_apiversion,
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('group', 'delete', $params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  function testgroupCreateEmptyParams() {
    $params = [];
    $group = civicrm_api('group', 'create', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, title');
  }

  function testgroupCreateNoTitle() {
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

    $group = civicrm_api('group', 'create', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, title');
  }

  function testGetGroupEmptyParams() {
    $params = '';
    $group = civicrm_api('group', 'get', $params);

    $this->assertEquals($group['error_message'], 'Input variable `params` is not an array');
  }

  function testGetGroupWithEmptyParams() {
    $params = ['version' => $this->_apiversion];

    $group = civicrm_api('group', 'get', $params);

    $group = $group["values"];
    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], "Test Group 1_{$this->_groupID}");
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  /**
   * Group Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Group
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Group&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Group&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testGetGroupParamsWithGroupId() {
    $params = ['version' => $this->_apiversion];
    $params['id'] = $this->_groupID;
    $group = civicrm_api('group', 'get', $params);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($group, __FILE__, __FUNCTION__);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['name'], "Test Group 1");
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
    $this->groupDelete($groupID);
  }

  function testGetGroupParamsWithGroupName() {
    $params         = ['version' => $this->_apiversion];
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
    $params = ['version' => $this->_apiversion];
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = civicrm_api('group', 'get', $params);
    $this->assertEquals($group['values'][$this->_groupID]['name'],
      "Test Group 1_{$this->_groupID}"
    );
  }

  function testGetGroupParamsWithGroupTitle() {
    $params          = ['version' => $this->_apiversion];
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
    $params          = ['version' => $this->_apiversion];
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
    $params = [];
    $group = civicrm_api('group', 'delete', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  function testgetfields() {
    $description = "demonstrate use of getfields to interogate api";
    $params      = ['version' => 3, 'action' => 'create'];
    $result      = civicrm_api('group', 'getfields', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'getfields', 'getfields');
    $this->assertEquals(1, $result['values']['is_active']['api.default']);
  }
}

