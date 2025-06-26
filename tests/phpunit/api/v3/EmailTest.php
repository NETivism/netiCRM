<?php
/**
 * Email Unit Test
 *
 * @docmaker_intro_start
 * @api_title Email
 * This is a API Document about Email.
 * @docmaker_intro_end
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_EmailTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_entity;
  protected $_params;
  
  function setUp() {
    $this->_apiversion = 3;
    $this->_entity = 'Email';
    parent::setUp();

    $this->_contactID = $this->organizationCreate();
    $this->_locationType = $this->locationTypeCreate();
    $this->_locationType2 = $this->locationTypeCreate([
      'name' => 'New Location Type 2',
      'vcard_name' => 'New Location Type 2',
      'description' => 'Another Location Type',
      'is_active' => 1,
    ]);

    $this->_params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api.test@civicrm.test.org',
      'is_primary' => 1,
      'sequential' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    ];
  }

  function tearDown() {
    $this->contactDelete($this->_contactID);
    $this->locationTypeDelete($this->_locationType->id);
    $this->locationTypeDelete($this->_locationType2->id);
  }

  /**
   * Email Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Email
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Email&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testGetEmail() {
    $result = civicrm_api('email', 'create', $this->_params);
    $this->assertAPISuccess($result, 'create email in line ' . __LINE__);

    $params = $this->_params;
    unset($params['is_primary']);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $get = civicrm_api('email', 'get', $params);
    $this->docMakerResponse($get, __FILE__, __FUNCTION__);

    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);

    $delresult = civicrm_api('email', 'delete', ['id' => $result['id'], 'version' => 3]);
    $this->assertAPISuccess($delresult, 'In line ' . __LINE__);
  }


  /**
   * Email Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Email
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Email&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testCreateEmail() {
    $params = $this->_params;
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', [
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('email', 'create', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

    $this->assertApiSuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    foreach($reuslt['values'] as $value) {
      $this->assertNotNull($value, 'In line ' . __LINE__);
    }
  }

  /*
   * If is_primary is not set then it should become is_primary is no others exist
   */
  public function testCreateEmailPrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);

    $email1 = civicrm_api('email', 'create', $params);
    $this->assertApiSuccess($email1, 'In line ' . __LINE__);

    //now we check & make sure it has been set to primary
    $check = civicrm_api('email', 'getcount', [
      'version' => 3,
      'is_primary' => 1,
      'id' => $email1['id'],
    ]);
    $this->assertEquals(1, $check);
  }

  public function testCreateEmailPrimaryHandlingChangeExisting() {
    $email1 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email1, 'In line ' . __LINE__);

    $email2 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email2, 'In line ' . __LINE__);

    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1", [
      1 => [$this->_contactID, 'Integer'],
    ], 'In line '. __LINE__);
  }

  public function testCreateEmailWithoutEmail() {
    $result = civicrm_api('Email', 'Create', ['contact_id' => 4, 'version' => 3]);
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains('missing', $result['error_message'], 'In line ' . __LINE__);
    $this->assertContains('email', $result['error_message'], 'In line ' . __LINE__);
  }


  /**
   * Email Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Email
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Email&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testUpdateEmail() {
    $params = $this->_params;
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', [
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);

    // create first email
    $created = civicrm_api('email', 'create', $params);

    $this->assertApiSuccess($created, 'In line ' . __LINE__);
    $this->assertEquals(1, $created['count'], 'In line ' . __LINE__);
    $this->assertNotNull($created['id'], 'In line ' . __LINE__);
    foreach($created['values'] as $value) {
      $this->assertNotNull($value, 'In line ' . __LINE__);
    }

    // update email
    $params = $this->_params;
    $params['email'] = 'test.update@civicrm.test.org';
    $params['id'] = $created['id'];

    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('email', 'create', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    foreach($result['values'] as $value) {
      $this->assertNotNull($value, 'In line ' . __LINE__);
    }
  }

  /**
   * Email Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Email
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Email&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testDeleteEmail() {
    $params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api-test@civicrm.test.org',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    ];
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', [
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // create one
    $create = civicrm_api('email', 'create', $params);
    $this->assertEquals(0, $create['is_error'], 'In line ' . __LINE__);

    // delete one
    $params = ['id' => $create['id'], 'version' => 3];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('email', 'delete', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = civicrm_api('email', 'get', [
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }
}
