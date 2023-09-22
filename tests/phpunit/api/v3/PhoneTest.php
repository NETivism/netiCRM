<?php
/**
 * Phone Unit Test
 *
 * @docmaker_intro_start
 * @api_title Phone
 * This is a API Document about Phone.
 * @docmaker_intro_end
 */


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_PhoneTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params;
  protected static $initialized = FALSE;

  /**
   * @before
   */
  function setUpTest() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->_contactID    = $this->organizationCreate();
    $loc                 = $this->locationTypeCreate();
    $this->_locationType = $loc->id;
    CRM_Core_PseudoConstant::flush('locationType');
    $this->quickCleanup(array('civicrm_phone'));
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType,
      'phone' => '021 512 755',
      'is_primary' => 1,
      'version' => $this->_apiversion,
    );
  }

  /**
   * @after
   */
  function tearDownTest() {
    $this->locationTypeDelete($this->_locationType);
    // $this->contactDelete($this->_contactID);
  }

  /**
   * Phone Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Phone
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Phone&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Phone&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testCreatePhone() {

    $result = civicrm_api('phone', 'create', $this->_params);

    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);

    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);

    // $this->assertEquals( 1, $result['id'], 'In line ' . __LINE__ );

    $delresult = civicrm_api('phone', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertEquals(0, $delresult['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Phone Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Phone
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Phone&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Phone&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testDeletePhone() {
    //create one
    $create = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($create, 'In line ' . __LINE__);

    $result = civicrm_api('phone', 'delete', array('id' => $create['id'], 'version' => 3));
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);

    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = civicrm_api('phone', 'get', array(
      'version' => 3, 'id' => $create['id'],
        'location_type_id' => $this->_locationType,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Phone not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test civicrm_phone_get with wrong params type.
   */
  public function testGetWrongParamsType() {
    $params = 'is_string';
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_phone_get with empty params.
   */
  public function testGetEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_address_get with wrong params.
   */
  public function testGetWrongParams() {
    $params = array('contact_id' => 'abc', 'version' => $this->_apiversion);
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);

    $params = array('location_type_id' => 'abc', 'version' => $this->_apiversion);
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);

    $params = array('phone_type_id' => 'abc', 'version' => $this->_apiversion);
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }

  /**
   * Phone Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Phone
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Phone&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Phone&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testGetPhone() {
    $phone = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['values'][$phone['id']]['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'version' => $this->_apiversion,
    );
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Phone', 'Get', ($params));

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['location_type_id'], $result['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone_type_id'], $result['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['is_primary'], $result['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone'], $result['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
  }

  public function testGetPhoneIsPrimary() {
    $phone = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['values'][$phone['id']]['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'is_primary' => '1',
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Phone', 'Get', ($params));

    $this->documentMe($params, $result, __FUNCT1ION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['location_type_id'], $result['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone_type_id'], $result['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('1', $result['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone'], $result['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
  }

  public function testGetPhoneByLocationType() {
    $phone = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['values'][$phone['id']]['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'location_type_id' => $phone['values'][$phone['id']]['location_type_id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Phone', 'Get', ($params));

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['location_type_id'], $result['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone_type_id'], $result['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('1', $result['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone'], $result['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
  }

  public function testGetPhoneByPhoneType() {
    $paramsWithPhoneType = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType,
      'phone' => '021 512 755',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      'phone_type_id' => 1
    );

    $phone = civicrm_api('phone', 'create', $paramsWithPhoneType);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['values'][$phone['id']]['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'phone_type_id' => '1',
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Phone', 'Get', ($params));

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($paramsWithPhoneType['location_type_id'], $result['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($paramsWithPhoneType['phone_type_id'], $result['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('1', $result['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($paramsWithPhoneType['phone'], $result['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
  }

  public function testReplacePhoneByData() {
    $phone = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['values'][$phone['id']]['contact_id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Phone', 'Get', ($params));

    $replaceParams = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType,
      'phone' => '021 512 755',
      'is_primary' => 1,
      'phone_type_id' => 1,
      'version' => $this->_apiversion,
    );

    $replace = civicrm_api('phone', 'create', $replaceParams);
    $this->assertAPISuccess($replace, 'In line ' . __LINE__);

    $this->doWriteResult($replace, __FILE__, __FUNCTION__);

    $this->documentMe($replaceParams, $replace, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($replaceParams['location_type_id'], $replace['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($replaceParams['phone_type_id'], $replace['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($replaceParams['is_primary'], $replace['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($replaceParams['phone'], $replace['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
  }

  ///////////////// civicrm_phone_create methods

  /**
   * Test civicrm_phone_create with wrong params type.
   */
  function testCreateWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('Phone', 'Create', $params);
    $this->assertEquals(1, $result['is_error'], "In line " . __LINE__);
  }

  /*
   * If a new email is set to is_primary the prev should no longer be
   * 
   * If is_primary is not set then it should become is_primary is no others exist
   */



  public function testCreatePhonePrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);
    $phone1 = civicrm_api('phone', 'create', $params);
    $this->assertApiSuccess($phone1, 'In line ' . __LINE__);
    //now we check & make sure it has been set to primary
    $check = civicrm_api('phone', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'id' => $phone1['id'],
      ));
    $this->assertEquals(1, $check);
  }
  public function testCreatePhonePrimaryHandlingChangeExisting() {
    $phone1 = civicrm_api('phone', 'create', $this->_params);
    $this->assertApiSuccess($phone1, 'In line ' . __LINE__);
    $phone2 = civicrm_api('phone', 'create', $this->_params);
    $this->assertApiSuccess($phone2, 'In line ' . __LINE__);
    $check = civicrm_api('phone', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(1, $check);
  }

  /**
   * Phone Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Phone
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Phone&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Phone&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testUpdatePhone() {
    $params = $this->_params;

    // create first email
    $created = civicrm_api('phone', 'create', $params);

    $this->assertApiSuccess($created, 'In line ' . __LINE__);
    $this->assertEquals(1, $created['count'], 'In line ' . __LINE__);
    $this->assertNotNull($created['id'], 'In line ' . __LINE__);
    foreach($created['values'] as $value) {
      $this->assertNotNull($value, 'In line ' . __LINE__);
    }

    // update email
    $params = $this->_params;
    $params['phone'] = '000 512 755';
    $params['id'] = $created['id'];

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('phone', 'create', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    foreach($result['values'] as $value) {
      $this->assertNotNull($value, 'In line ' . __LINE__);
    }
  }
}

