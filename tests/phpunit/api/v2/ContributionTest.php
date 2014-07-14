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



require_once 'api/v2/Contribution.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_ContributionTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId;
  protected $_contribution;
  protected $_contributionTypeId; function setUp() {
    parent::setUp();

    $this->_contributionTypeId = $this->contributionTypeCreate();
    $this->_individualId = $this->individualCreate();
  }

  function tearDown() {
    $this->contributionTypeDelete();
    $this->contactDelete($this->_individualId);
  }

  ///////////////// civicrm_contribution_get methods
  function testGetEmptyParamsContribution() {

    $params = array();
    $contribution = &civicrm_contribution_get($params);

    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'No input parameters present');
  }

  function testGetParamsNotArrayContribution() {
    $params = 'contact_id= 1';
    $contribution = &civicrm_contribution_get($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input parameters is not an array');
  }

  function testGetContribution() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $this->_contribution = &civicrm_contribution_add($p);
    $params              = array('contribution_id' => $this->_contribution['id']);
    $contribution        = &civicrm_contribution_get($params);
    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['contribution_type_id'], $this->_contributionTypeId);
    $this->assertEquals($contribution['total_amount'], 100.00);
    $this->assertEquals($contribution['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['fee_amount'], 51.00);
    $this->assertEquals($contribution['net_amount'], 91.00);
    $this->assertEquals($contribution['trxn_id'], 23456);
    $this->assertEquals($contribution['invoice_id'], 78910);
    $this->assertEquals($contribution['contribution_source'], 'SSF');
    $this->assertEquals($contribution['contribution_status'], 'Completed');

    $params2 = array('contribution_id' => $this->_contribution['id']);

    $this->contributionDelete($this->_contribution['id']);
  }

  ///////////////// civicrm_contribution_add
  function testCreateEmptyParamsContribution() {
    $params = array();
    $contribution = civicrm_contribution_add($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input Parameters empty');
  }

  function testCreateParamsNotArrayContribution() {
    $params = 'contact_id= 1';
    $contribution = &civicrm_contribution_add($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Input parameters is not an array');
  }

  function testCreateParamsWithoutRequiredKeys() {
    $params = array('no_required' => 1);
    $contribution = &civicrm_contribution_add($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Required fields not found for contribution contact_id');
  }

  function testCreateContribution() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $contribution = &civicrm_contribution_add($params);

    $this->assertEquals($contribution['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['receive_date'], date('Ymd'), 'In line ' . __LINE__);
    $this->assertEquals($contribution['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['contribution_type_id'], $this->_contributionTypeId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['payment_instrument_id'], 1, 'In line ' . __LINE__);
    $this->assertEquals($contribution['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['fee_amount'], 50.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['net_amount'], 90.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['contribution_status_id'], 1, 'In line ' . __LINE__);
    $this->_contribution = $contribution;

    $contributionID = array('contribution_id' => $contribution['id']);
    $contribution = &civicrm_contribution_delete($contributionID);

    $this->assertEquals($contribution['is_error'], 0);
    $this->assertEquals($contribution['result'], 1);
  }


  //To Update Contribution
  //CHANGE: we require the API to do an incremental update
  function testCreateUpdateContribution() {
    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId);
    $old_params = array(
      'contribution_id' => $contributionID,
    );
    $original = &civicrm_contribution_get($old_params);
    //Make sure it came back
    $this->assertTrue(empty($original['is_error']), 'In line ' . __LINE__);
    $this->assertEquals($original['contribution_id'], $contributionID, 'In line ' . __LINE__);
    //set up list of old params, verify

    //This should not be required on update:
    $old_contact_id = $original['contact_id'];
    $old_payment_instrument = $original['instrument_id'];
    $old_fee_amount = $original['fee_amount'];
    $old_source = $original['contribution_source'];

    //note: current behavior is to return ISO.  Is this
    //documented behavior?  Is this correct
    $old_receive_date = date('Ymd', strtotime($original['receive_date']));

    $old_trxn_id = $original['trxn_id'];
    $old_invoice_id = $original['invoice_id'];

    //check against values in CiviUnitTestCase::createContribution()
    $this->assertEquals($old_contact_id, $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($old_fee_amount, 50.00, 'In line ' . __LINE__);
    $this->assertEquals($old_source, 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($old_trxn_id, 12345, 'In line ' . __LINE__);
    $this->assertEquals($old_invoice_id, 67890, 'In line ' . __LINE__);
    $params = array(
      'id' => $contributionID,
      'contact_id' => $this->_individualId,
      'total_amount' => 110.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'net_amount' => 100.00,
      'contribution_status_id' => 1,
      'note' => 'Donating for Nobel Cause',
    );

    $contribution = &civicrm_contribution_add($params);

    $new_params = array(
      'contribution_id' => $contribution['id'],
    );
    $contribution = &civicrm_contribution_get($new_params);

    $this->assertEquals($contribution['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['total_amount'], 110.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['contribution_type_id'], $this->_contributionTypeId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['instrument_id'], $old_payment_instrument, 'In line ' . __LINE__);
    $this->assertEquals($contribution['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['fee_amount'], $old_fee_amount, 'In line ' . __LINE__);
    $this->assertEquals($contribution['net_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['trxn_id'], $old_trxn_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['invoice_id'], $old_invoice_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['contribution_source'], $old_source, 'In line ' . __LINE__);
    $this->assertEquals($contribution['contribution_status'], 'Completed', 'In line ' . __LINE__);
    $contributionID = array('contribution_id' => $contribution['contribution_id']);
    $contribution = &civicrm_contribution_delete($contributionID);

    $this->assertEquals($contribution['is_error'], 0);
    $this->assertEquals($contribution['result'], 1);
  }

  ///////////////// civicrm_contribution_delete methods
  function testDeleteEmptyParamsContribution() {
    $params = array();
    $contribution = civicrm_contribution_delete($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Could not find contribution_id in input parameters');
  }

  function testDeleteParamsNotArrayContribution() {
    $params = 'contribution_id= 1';
    $contribution = civicrm_contribution_delete($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Could not find contribution_id in input parameters');
  }

  function testDeleteWrongParamContribution() {
    $params = array('contribution_source' => 'SSF');
    $contribution = &civicrm_contribution_delete($params);
    $this->assertEquals($contribution['is_error'], 1);
    $this->assertEquals($contribution['error_message'], 'Could not find contribution_id in input parameters');
  }

  function testDeleteContribution() {
    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId);
    $params         = array('contribution_id' => $contributionID);
    $contribution   = civicrm_contribution_delete($params);
    $this->assertEquals($contribution['is_error'], 0);
    $this->assertEquals($contribution['result'], 1);
  }

  ///////////////// civicrm_contribution_search methods

  /**
   *  Test civicrm_contribution_search with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = &civicrm_contribution_search($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameters is not an array', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_search with empty params.
   *  All available contributions expected.
   */
  function testSearchEmptyParams() {
    $params = array();

    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $contribution = &civicrm_contribution_add($p);
    $contributionId = $contribution['id'];

    $result = &civicrm_contribution_search($params);
    // We're taking the first element.
    $res = $result[$contributionId];

    $this->assertEquals($p['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['contribution_type_id'], $res['contribution_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['source'], $res['contribution_source'], 'In line ' . __LINE__);
    // contribution_status_id = 1 => Completed
    $this->assertEquals('Completed', $res['contribution_status'], 'In line ' . __LINE__);

    $this->contributionDelete($contribution['id']);
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
    );
    $contribution1 = &civicrm_contribution_add($p1);

    $p2 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 200.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 20.00,
      'trxn_id' => 5454565,
      'invoice_id' => 1212124,
      'fee_amount' => 50.00,
      'net_amount' => 60.00,
      'contribution_status_id' => 2,
    );
    $contribution2 = &civicrm_contribution_add($p2);

    $params = array('contribution_id' => $contribution2['id']);
    $result = &civicrm_contribution_search($params);
    $res    = $result[$contribution2['id']];

    $this->assertEquals($p2['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['contribution_type_id'], $res['contribution_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    // contribution_status_id = 2 => Pending
    $this->assertEquals('Pending', $res['contribution_status'], 'In line ' . __LINE__);

    $this->contributionDelete($contribution1['id']);
    $this->contributionDelete($contribution2['id']);
  }

  ///////////////// civicrm_contribution_format_create methods

  /**
   *  Test civicrm_contribution_format_creat with Empty params
   */
  function testFormatCreateEmptyParams() {
    $params = array();
    $result = &civicrm_contribution_format_create($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_format_creat with wrong params type
   */
  function testFormatCreateParamsType() {
    $params = 'a string';
    $result = &civicrm_contribution_format_create($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_format_creat with invalid data
   */
  function testFormatCreateInvalidData() {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $validParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'contribution_status_id' => 1,
    );
    $params = $validParams;
    $params['receive_date'] = 'invalid';
    $result = &civicrm_contribution_format_create($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);

    $params = $validParams;
    $params['total_amount'] = 'invalid';
    $result = &civicrm_contribution_format_create($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);

    $params             = $validParams;
    $params['currency'] = 'invalid';
    $result             = &civicrm_contribution_format_create($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);

    $params = $validParams;
    $params['contribution_contact_id'] = 'invalid';
    $result = &civicrm_contribution_format_create($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);

    $params = $validParams;
    $params['contribution_contact_id'] = 999;
    $result = &civicrm_contribution_format_create($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_format_creat success expected
   */
  function testFormatCreate() {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    require_once 'CRM/Contribute/PseudoConstant.php';

    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_contributionTypeId,
      'contribution_status_id' => 1,
      'contribution_type' => 'Prize',
      'note' => 'note',
    );

    $result = &civicrm_contribution_format_create($params);

    $this->assertEquals($result['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($result['contribution_status_id'], 1, 'In line ' . __LINE__);

    $params = array('contribution_id' => $result['id']);
    $contribution = civicrm_contribution_delete($params);
  }

  /////////////////  _civicrm_contribute_format_params for $create
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
    $result = _civicrm_contribute_format_params($params, $values, TRUE);
    $this->assertEquals($values['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($values['contribution_status_id'], 1, 'In line ' . __LINE__);
  }
}

