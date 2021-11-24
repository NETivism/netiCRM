<?php
/**
 * ContributionRecur Unit Test
 *
 * @docmaker_intro_start
 * This is a API document about recurring contribution.
 * @docmaker_intro_end
 */


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ContributionRecurTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $_individualId;
  protected $_entity = 'contribution_recur';
  public $_eNoticeCompliant = TRUE;

  function setUp() {
    parent::setUp();
    $this->_individualId = $this->individualCreate();
    $this->_params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_individualId,
      'amount' => '500.00',
      'currency' => 'TWD',
      'frequency_unit' => 'month',
      'frequency_interval' => '1',
      'installments' => '12',
      'start_date' => date('Y-m-25 H:i:s', strtotime('last month')),
      'create_date' => date('Y-m-01 H:i:s', strtotime('last month')),
      'cancel_date' => '',
      'end_date' => '',
      'processor_id' => '',
      'external_id' => '',
      'trxn_id' => CRM_Utils_String::createRandom(10),
      'invoice_id' => CRM_Utils_String::createRandom(32),
      'contribution_status_id' => 5, // processing
      'is_test' => 0,
      'cycle_day' => 5,
      'next_sched_contribution' => date('Y-m-25 H:i:s'),
      'failure_count' => 0,
      'failure_retry_date' => '',
      'auto_renew' => 0,
      'last_execute_date' => '',
    );
  }

  function tearDown() {
  }

  /**
   * Recurring Contribution Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Recurring Contribution
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution_recur&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution_recur&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testGetContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $getParams = array(
      'version' => $this->_apiversion,
      'id' => $result['id'],
    );
    $result = civicrm_api($this->_entity, 'get', $getParams);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

  /**
   * Recurring Contribution Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Recurring Contribution
   * @api_action Create
   * @http_method POST
   * @request_content_type application/x-www-form-urlencoded
   * @request_url <entrypoint>?entity=contribution_recur&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution_recur&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testCreateContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);

    $verifyParams = $this->_params;
    unset($verifyParams['version']);
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $result['id'], $verifyParams);
  }

  /**
   * Recurring Contribution Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Recurring Contribution
   * @api_action Update
   * @http_method POST
   * @request_content_type application/x-www-form-urlencoded
   * @request_url <entrypoint>?entity=contribution_recur&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution_recur&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testUpdateContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);

    $updateParams = array(
      'version' => $this->_apiversion,
      'id' => $result['id'],
      'contribution_status_id' => 1, // completed
      'next_sched_contribution' => '',
      'end_date' => date('Y-m-d H:i:s'),
    );
    $updated = civicrm_api($this->_entity, 'update', $updateParams);
    $this->assertAPISuccess($updated, 'In line ' . __LINE__);
    $this->assertNotNull($updated['values'][$updated['id']]['id'], 'In line ' . __LINE__);
    $verifyParams = array_merge($this->_params, $updateParams);
    $this->getAndCheck($verifyParams, $result['id'], $this->_entity);

    // database record as expect
    $verifyParams = array(
      'id' => $updated['id'],
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 1,
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $updated['id'], $verifyParams);

    // original value not touched when update
    $verifyParams = array(
      'id' => $updated['id'],
      'amount' => $this->_params['amount'],
      'currency' => $this->_params['currency'],
      'frequency_unit' => $this->_params['frequency_unit'],
      'frequency_interval' => $this->_params['frequency_interval'],
      'cycle_day' => $this->_params['cycle_day'],
    );
    $this->assertDBState('CRM_Contribute_DAO_ContributionRecur', $updated['id'], $verifyParams);
  }

  public function testDeleteContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $deleteParams = array(
      'version' => $this->_apiversion,
      'id' => $result['id'],
    );
    $deleted = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->assertAPISuccess($deleted, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => $this->_apiversion,
      'id' => $deleteParams['id'],
    ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
    $this->assertDBNull('CRM_Contribute_DAO_ContributionRecur', $result['id'], 'id', 'id', 'In line ' . __LINE__);
  }

  public function testGetFieldsContributionRecur() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => 3, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }
}

