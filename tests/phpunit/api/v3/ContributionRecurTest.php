<?php

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ContributionRecurTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'contribution_recur';
  public $_eNoticeCompliant = TRUE;

  function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
      'version' => 3,
      'contact_id' => $this->ids['contact'][0],
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

  public function testGetContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $getParams = array(
      'version' => $this->_apiversion,
      'id' => $result['id'],
    );
    $result = civicrm_api($this->_entity, 'get', $getParams);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

  public function testCreateContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testDeleteContributionRecur() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $deleteParams = array(
      'version' => 3,
      'id' => $result['id'],
    );
    $deleted = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->assertAPISuccess($deleted, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => 3,
      'id' => $deleteParams['id'],
    ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  public function testGetFieldsContributionRecur() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => 3, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }
}

