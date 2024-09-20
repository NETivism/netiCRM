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

/**
 * Contribution Unit Test
 *
 * @docmaker_intro_start
 * @api_title Contribution
 * This is a API Document about contribution.
 * @docmaker_intro_end
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ContributionTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId;
  protected $_contribution;
  protected $_contributionTypeId;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  public $_eNoticeCompliant = FALSE;

  function setUp() {
    parent::setUp();
    $types = CRM_Contribute_PseudoConstant::contributionType();
    $this->_apiversion = 3;
    $this->_contributionTypeId = key($types);
    $this->_individualId = $this->individualCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Y-m-d H:i:s'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'source' => 'Contribution Unit Test',
      'contribution_status_id' => 1,
      'sequential' => 1,
      'version' => $this->_apiversion,
    );
  }

  function tearDown() {

  }

  /**
   * Contribution Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Contribution
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Contribution&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Contribution&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testGetContribution() {
    $p = $this->_params;
    $p['trxn_id'] = CRM_Utils_String::createRandom(10);
    $p['invoice_id'] = CRM_Utils_String::createRandom(10);
    $this->_contribution = civicrm_api('contribution', 'create', $p);
    $this->assertEquals($this->_contribution['is_error'], 0, 'In line ' . __LINE__);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
      'version' => $this->_apiversion,
    );

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $contribution = civicrm_api('contribution', 'get', $params);
    $this->docMakerResponse($contribution, __FILE__, __FUNCTION__);

    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1,$contribution['count']);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_type_id'], $this->_contributionTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 50.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 90.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], $p['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], $p['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'Contribution Unit Test', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1, 'In line ' . __LINE__);

    //create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id)
    $p['trxn_id'] = CRM_Utils_String::createRandom(10);
    $p['invoice_id'] =  CRM_Utils_String::createRandom(32);

    $contribution2 = civicrm_api('contribution', 'create', $p);
    $this->assertAPISuccess($contribution2, 'In line ' . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
    );
    // now we have 2 - test getcount
    $contribution = civicrm_api('contribution', 'getcount', array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_individualId,
    ));
    $this->assertEquals(2, $contribution);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array(
      'version' => $this->_apiversion,
      'id' => $this->_contribution['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution,true) . " in line " . __LINE__);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array
      ('version' => $this->_apiversion,
        'id' => $contribution2['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($contribution2['id'], $contribution);
    $contribution = civicrm_api('contribution', 'get', array(
      'version' => $this->_apiversion,
        'id' => $this->_contribution['id'],
      ));
    //test id as field
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1, $contribution['count'], 'In line ' . __LINE__);
    // $this->assertEquals($this->_contribution['id'], $contribution['id'] )  ;
    //test get by contact id works
    $contribution = civicrm_api('contribution', 'get', array('version' => $this->_apiversion, 'contact_id' => $this->_individualId));
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__ . "get with contact_id" . print_r(array('version' => $this->_apiversion, 'contact_id' => $this->_individualId), TRUE));

    $this->assertEquals(2, $contribution['count'], 'In line ' . __LINE__);
    civicrm_api('Contribution', 'Delete', array(
      'id' => $this->_contribution['id'],
        'version' => $this->_apiversion,
      ));
    civicrm_api('Contribution', 'Delete', array(
      'id' => $contribution2['id'],
        'version' => $this->_apiversion,
      ));
  }

  /**
   * Contribution Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Contribution
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Contribution&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Contribution&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testCreateContribution() {
    $params = $this->_params;
    $params['trxn_id'] = CRM_Utils_String::createRandom(10);
    $params['invoice_id'] = CRM_Utils_String::createRandom(10);
    $params['payment_instrument_id'] = 1;

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->docMakerResponse($contribution, __FILE__, __FUNCTION__);
    $value = reset($contribution['values']);

    $this->assertEquals($value['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($value['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($value['contribution_type_id'], $this->_contributionTypeId, 'In line ' . __LINE__);
    $this->assertEquals($value['payment_instrument_id'], 1, 'In line ' . __LINE__);
    $this->assertEquals($value['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($value['fee_amount'], 50.00, 'In line ' . __LINE__);
    $this->assertEquals($value['net_amount'], 90.00, 'In line ' . __LINE__);
    $this->assertEquals($value['trxn_id'], $params['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($value['invoice_id'], $params['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($value['source'], 'Contribution Unit Test', 'In line ' . __LINE__);
    $this->assertEquals($value['contribution_status_id'], 1, 'In line ' . __LINE__);

    $contrib = civicrm_api('Contribution', 'Get', array(
      'id' => $contribution['id'],
      'version' => $this->_apiversion,
    ));

    $this->assertEquals($contrib['is_error'], 0, 'In line ' . __LINE__);
    $value = reset($contrib['values']);
    $params['receive_date'] = date('Y-m-d H:i:s', strtotime($params['receive_date']));

    // this is not returned in id format
    unset($params['payment_instrument_id']);
    $params['contribution_source'] = $params['source'];
    unset($params['source']);
    foreach ($params as $key => $val) {
      if ($key == 'version' || $key === 'sequential') {
        continue;
      }
      $this->assertEquals($val, $value[$key], $key . " value: $val doesn't match " . print_r($value, TRUE) . 'in line' . __LINE__);
    }
  }


  function testCreateContributionEmptyID() {
    $params = array(
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'version' => 3,
      'check_permissions' => FALSE,
      'contribution_type_id' => 1,
    );
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['is_error'], 0, 'In line ' . __LINE__);
  }
  ///////////////// civicrm_contribution_
  function testCreateEmptyParamsContribution() {
    $params = array('version' => $this->_apiversion);
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($contribution['error_message'], 'Mandatory key(s) missing from params array: total_amount, contact_id', 'In line ' . __LINE__);
  }

  function testCreateContributionParamsNotArray() {

    $params = 'contact_id= 1';
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testCreateContributionWithoutRequiredKeys() {
    $params = array('version' => 3);
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Mandatory key(s) missing from params array: total_amount, contact_id');
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateContributionWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    $value = reset($result['values']);
    $this->assertEquals($result['id'], $value['id']);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $check = civicrm_api($this->_entity, 'get', array(
        'return.custom_' . $ids['custom_field_id'] => 1,
        'version' => 3,
        'id' => $result['id'],
      )
    );
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateGetFieldsWithCustom() {
    $ids        = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $idsContact = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTest.php');
    $result     = civicrm_api('Contribution', 'getfields', array('version' => 3));
    $this->assertArrayHasKey('custom_' . $ids['custom_field_id'], $result['values']);
    $this->assertArrayNotHasKey('custom_' . $idsContact['custom_field_id'], $result['values']);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customFieldDelete($idsContact['custom_field_id']);
    $this->customGroupDelete($idsContact['custom_group_id']);
  }

  function testCreateContributionInvalidContact() {

    $params = array(
      'contact_id' => 99999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => CRM_Utils_String::createRandom(10),
      'invoice_id' => CRM_Utils_String::createRandom(32),
      'contribution_source' => 'Contribution Unit Test',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'contact_id is not valid : 99999', 'In line ' . __LINE__);
  }

  function testCreateContributionWithNote() {
    $params = $this->_params;
    $description = "Demonstrates creating contribution with Note Entity";
    $params['note'] = $description;

    $contribution = civicrm_api('contribution', 'create', $params);
    $result = civicrm_api('note', 'get', array('version' => 3, 'entity_table' => 'civicrm_contribution', 'entity_id' => $contribution['id'], 'sequential' => 1));
    $this->assertAPISuccess($result);
    $this->assertEquals($description, $result['values'][0]['note']);
  }

  function testCreateContributionWithSoftCredt() {
    $contact2 = $this->individualCreate();

    $params = $this->_params;
    $params['soft_credit_to'] = $contact2;

    $contribution = civicrm_api('contribution', 'create', $params);
    $query = "SELECT count(*) FROM civicrm_contribution_soft WHERE contact_id = %1";
    $count = CRM_Core_DAO::singleValueQuery($query, array(1 => array($contact2, 'Integer')));
    $this->assertEquals(1, $count);
  }

  /**
   * Contribution Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Contribution
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Contribution&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Contribution&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testUpdateContribution() {
    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId, CRM_Utils_String::createRandom(32), CRM_Utils_String::createRandom(10));
    $old_params = array(
      'contribution_id' => $contributionID,
      'version' => $this->_apiversion,
    );
    $original = civicrm_api('contribution', 'get', $old_params);
    //Make sure it came back
    $this->assertTrue(empty($original['is_error']), 'In line ' . __LINE__);
    $this->assertEquals($original['id'], $contributionID, 'In line ' . __LINE__);
    //set up list of old params, verify

    //This should not be required on update:
    $old_contact_id = $original['values'][$contributionID]['contact_id'];
    $old_payment_instrument = $original['values'][$contributionID]['instrument_id'];
    $old_fee_amount = $original['values'][$contributionID]['fee_amount'];
    $old_source = $original['values'][$contributionID]['contribution_source'];
    $old_trxn_id = $original['values'][$contributionID]['trxn_id'];
    $old_invoice_id = $original['values'][$contributionID]['invoice_id'];

    //check against values in CiviUnitTestCase::createContribution()
    $this->assertEquals($old_contact_id, $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($old_fee_amount, 50.00, 'In line ' . __LINE__);
    $this->assertEquals($old_source, 'Contribution Unit Test', 'In line ' . __LINE__);

    $cancelDate = date('YmdHis');
    $params = array(
      'id' => $contributionID,
      'total_amount' => 9999.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'net_amount' => 100.00,
      'contribution_status_id' => 3,
      'cancel_date' => $cancelDate,
      'cancel_reason' => 'The reason to cancel',
      'version' => $this->_apiversion,
    );

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->docMakerResponse($contribution, __FILE__, __FUNCTION__);

    $new_params = array(
      'contribution_id' => $contribution['id'],
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'get', $new_params);

    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->assertEquals($contribution['values'][$contributionID]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['total_amount'], 9999.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_type_id'], $this->_contributionTypeId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['instrument_id'], $old_payment_instrument, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['fee_amount'], $old_fee_amount, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['net_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['trxn_id'], $old_trxn_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['invoice_id'], $old_invoice_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_source'], $old_source, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_status'], $statuses[3], 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_status'], $statuses[3], 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['cancel_reason'], 'The reason to cancel', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['cancel_date'], date('Y-m-d H:i:s', strtotime($cancelDate)), 'In line ' . __LINE__);

  }

  /**
   * Contribution Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Contribution
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Contribution&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Contribution&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testDeleteContribution() {
    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId, CRM_Utils_String::createRandom(32), CRM_Utils_String::createRandom(10));
    $params = array(
      'id' => $contributionID,
      'version' => $this->_apiversion,
    );

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('contribution', 'delete', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

    $this->assertEquals($result['is_error'], 0, 'In line ' . __LINE__);
  }

  function testDeleteContributionEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $contribution = civicrm_api('contribution', 'delete', $params);
    $this->assertEquals($contribution['is_error'], 1);
  }

  function testDeleteContributionParamsNotArray() {
    $params = 'contribution_id= 1';
    $contribution = civicrm_api('contribution', 'delete', $params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testDeleteContributionWrongParam() {
    $params = array(
      'contribution_source' => 'Contribution Unit Test',
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'delete', $params);
    $this->assertEquals($contribution['is_error'], 1);
  }

  /**
   *  Test civicrm_contribution_search. Success expected.
   */
  function testSearch() {
    $p1 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution1 = civicrm_api('contribution', 'create', $p1);

    $p2 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 200.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 20.00,
      'trxn_id' => CRM_Utils_String::createRandom(10),
      'invoice_id' => CRM_Utils_String::createRandom(32),
      'fee_amount' => 50.00,
      'net_amount' => 60.00,
      'contribution_status_id' => 2,
      'version' => $this->_apiversion,
    );
    $contribution2 = civicrm_api('contribution', 'create', $p2);

    $params = array(
      'contribution_id' => $contribution2['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution', 'get', $params);
    $res = $result['values'][$contribution2['id']];

    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->assertEquals($p2['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['contribution_type_id'], $res['contribution_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($statuses[2], $res['contribution_status'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_search with empty params.
   *  All available contributions expected.
   */
  function testSearchEmptyParams() {
    $p = $this->_params;
    $contribution = civicrm_api('contribution', 'create', $p);

    $params = array(
      'version' => $this->_apiversion,
      'options' => array('sort' => 'contribution_id DESC'),
    );
    $result = civicrm_api('contribution', 'get', $params);
    // We're taking the first element.
    $res = $result['values'][$contribution['id']];

    $this->assertEquals($p['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['contribution_type_id'], $res['contribution_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['source'], $res['contribution_source'], 'In line ' . __LINE__);
    $this->assertEquals(1, $res['contribution_status_id'], 'In line ' . __LINE__);
  }


  function testFormatParams() {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'contribution_status_id' => 1,
      'contribution_type' => NULL,
      'note' => 'note',
      'contribution_source' => 'test',
    );

    $values = array();
    // add api call to include api/v3/Contribution.php
    civicrm_api('contribution', 'get', array('id' => 1));

    $result = _civicrm_api3_contribute_format_params($params, $values, TRUE);
    $this->assertEquals($values['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($values['contribution_status_id'], 1, 'In line ' . __LINE__);
  }

  /*
     * This function does a GET & compares the result against the $params
     * Use as a double check on Creates
     */
  function contributionGetnCheck($params, $id, $delete = 1) {

  }

  /*
   * Test checks that passing in line items suppresses the create mechanism
   */
  /* disbale this because fairly low use rate
  function testCreateContributionChainedLineItems() {
    $params = array(
        'contact_id' => $this->_individualId,
        'receive_date' => '20120511',
        'total_amount' => 100.00,
        'contribution_type_id' => $this->_contributionTypeId,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => CRM_Utils_String::createRandom(10),
        'invoice_id' => CRM_Utils_String::createRandom(32),
        'source' => 'Contribution Unit Test',
        'contribution_status_id' => 1,
        'version' => $this->_apiversion,
        'use_default_price_set' => 0,
        'api.line_item.create' => array(
            array(
              'price_field_id' => 1,
              'qty' => 2,
              'line_total' => '20',
              'unit_price' => '10',
            ),
            array(
                'price_field_id' => 1,
                'qty' => 1,
                'line_total' => '80',
                'unit_price' => '80',
            ),
          ),

    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $description = "Create Contribution with Nested Line Items";
    $subfile = "CreateWithNestedLineItems";
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $lineItems = civicrm_api('line_item','get',array(
        'version' => $this->_apiversion,
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'sequential' => 1,
    ));
    $this->assertEquals(2, $lineItems['count']);
  }
  */

  /**
   * Function tests that line items are updated
   */
  /* disbale this because not implement contribution use line item
  function testCreateUpdateContributionChangeTotal(){
    $contribution = civicrm_api('contribution', 'create', $this->_params);
    $lineItems = civicrm_api('line_item','getvalue',array(
        'version' => $this->_apiversion,
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'sequential' => 1,
        'return' => 'line_total',
    ));
    $this->assertEquals('100.00', $lineItems);

    $newParams = array_merge($this->_params, array('total_amount' => '777'));
    $contribution = civicrm_api('contribution', 'create', $newParams);
    $lineItems = civicrm_api('line_item','getvalue',array(
        'version' => $this->_apiversion,
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'sequential' => 1,
        'return' => 'line_total',
    ));
    $this->assertEquals('777.00', $lineItems);
  }
  */
}

