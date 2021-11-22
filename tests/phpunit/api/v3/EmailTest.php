<?php
// $Id$


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
    $this->_locationType2 = $this->locationTypeCreate(array(
      'name' => 'New Location Type 2',
      'vcard_name' => 'New Location Type 2',
      'description' => 'Another Location Type',
      'is_active' => 1,
    ));

    $this->_params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api.test@civicrm.test.org',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    );
  }

  function tearDown() {
    $this->contactDelete($this->_contactID);
    $this->locationTypeDelete($this->_locationType->id);
    $this->locationTypeDelete($this->_locationType2->id);
  }

  /**
   * @start_document
   * 
   * ## {ts}Create{/ts} / {ts}Update{/ts} {ts}Email{/ts} 
   * 
   * {ts}This is API for create or update Email{/ts} 
   * 
   * **HTTP {ts}methods{/ts}: POST**
   * 
   * **{ts}Path{/ts}**
   * 
   * ```
   * <entrypoint>?entity=Email&action=create&pretty=1&json=\{"contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}
   * ```
   * 
   * **API Explorer**
   * 
   * ```
   * https://<site-domain>/civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=create&pretty=1&json=\{"contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}
   * ```
   * 
   * **{ts}Request Samples{/ts}**
   * 
   * ```shell
   * curl -g --request POST '<entrypoint>?entity=Email&action=create&pretty=1&json=\{"contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}' \
   * {$API_KEY_HEADER} \
   * {$SITE_KEY_HEADER}
   * ```
   * 
   * {$result}
   * 
   * @end_document
   */
  public function testCreateEmail() {
    $params = $this->_params;
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);

    $result = civicrm_api('email', 'create', $params);
    $this->docMakerTemplate($result, __FILE__, __FUNCTION__);

    $this->assertApiSuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $delresult = civicrm_api('email', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertEquals(0, $delresult['is_error'], 'In line ' . __LINE__);
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
    $check = civicrm_api('email', 'getcount', array(
      'version' => 3,
      'is_primary' => 1,
      'id' => $email1['id'],
    ));
    $this->assertEquals(1, $check);
  }

  public function testCreateEmailPrimaryHandlingChangeExisting() {
    $email1 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email1, 'In line ' . __LINE__);

    $email2 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email2, 'In line ' . __LINE__);

    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_email WHERE contact_id = %1 AND is_primary = 1", array(
      1 => array($this->_contactID, 'Integer'),
    ), 'In line '. __LINE__);
  }

  public function testCreateEmailWithoutEmail() {
    $result = civicrm_api('Email', 'Create', array('contact_id' => 4, 'version' => 3));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains('missing', $result['error_message'], 'In line ' . __LINE__);
    $this->assertContains('email', $result['error_message'], 'In line ' . __LINE__);
  }

  
  /**
   * @start_document
   * 
   * ## {ts}Get{/ts} {ts}Email{/ts} 
   * 
   * {ts}This is tests for get Email{/ts} 
   * 
   * **HTTP {ts}methods{/ts}: GET**
   * 
   * **{ts}Path{/ts}**
   * 
   * ```
   * <entrypoint>?entity=Email&action=get&pretty=1&json=\{"contact_id":"{$value.contact_id}"\}
   * ```
   * 
   * **API Explorer**
   * 
   * ```
   * https://<site-domain>/civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=get&pretty=1&json=\{"contact_id":"{$value.contact_id}"\}
   * ```
   * 
   * **{ts}Request Samples{/ts}**
   * 
   * ```shell
   * curl -g --request GET '<entrypoint>?entity=Email&action=get&pretty=1&json=\{"contact_id":"{$value.contact_id}"\}' \
   * {$API_KEY_HEADER} \
   * {$SITE_KEY_HEADER}
   * ```
   * 
   * {$result}
   * 
   * @end_document
   */
  public function testGetEmail() {
    $result = civicrm_api('email', 'create', $this->_params);
    $this->assertAPISuccess($result, 'create email in line ' . __LINE__);
    $get = civicrm_api('email', 'get', $this->_params);
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    $this->docMakerTemplate($get, __FILE__, __FUNCTION__);
    /*
    // Todo: Create API should skip when same contact and is_primary data is duplicated.
    $get = civicrm_api('email', 'create', $this->_params + array('debug' => 1));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    $get = civicrm_api('email', 'create', $this->_params + array('debug' => 1, 'action' => 'get'));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    */
    $delresult = civicrm_api('email', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertAPISuccess($delresult, 'In line ' . __LINE__);
  }

  
  /**
   * @start_document
   * 
   * ## {ts}Delete{/ts} {ts}Email{/ts} 
   * 
   * {ts}This is tests for deleting Email{/ts} 
   * 
   * **HTTP {ts}methods{/ts}: POST**
   * 
   * **{ts}Path{/ts}**
   * 
   * ```
   * <entrypoint>?entity=Email&action=delete&pretty=1&json=\{"id":"1"\}
   * ```
   * 
   * **API Explorer**
   * 
   * ```
   * https://<site-domain>/civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=delete&pretty=1&json=\{"id":"1"\}
   * ```
   * **{ts}Request Samples{/ts}**
   * 
   * ```bash
   * curl -g --request POST '<entrypoint>?entity=Email&action=delete&pretty=1&json=\{"id":"1"\}' \
   * {$API_KEY_HEADER} \
   * {$SITE_KEY_HEADER}
   * ```
   * 
   * {$result}
   * 
   * @end_document
   */
  public function testDeleteEmail() {
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api-test@civicrm.test.org',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    );
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // create one
    $create = civicrm_api('email', 'create', $params);
    $this->assertEquals(0, $create['is_error'], 'In line ' . __LINE__);

    // delete one
    $result = civicrm_api('email', 'delete', array('id' => $create['id'], 'version' => 3));
    $this->docMakerTemplate($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
      'location_type_id' => $this->_locationType->id,
      'contact_id' => $this->_contactID,
    ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  
  /**
   * @start_document
   * 
   * ## {ts}Update{/ts} {ts}Email{/ts} 
   * 
   * {ts}This is tests for updating Email{/ts} 
   * 
   * **HTTP {ts}methods{/ts}: POST**
   * 
   * **{ts}Path{/ts}**
   * 
   * ```
   * <entrypoint>?entity=Email&action=create&pretty=1&json=\{"id":"{$value.id}","contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}
   * ```
   * 
   * **API Explorer**
   * 
   * ```
   * https://<site-domain>/civicrm/apibrowser#/civicrm/ajax/rest?entity=Email&action=create&pretty=1&json=\{"id":"{$value.id}","contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}
   * ```
   * 
   * **{ts}Request Samples{/ts}**
   * 
   * ```
   * curl -g --request POST '<entrypoint>?entity=Email&action=create&pretty=1&json=\{"id":"{$value.id}","contact_id":"{$value.contact_id}","location_type_id":"{$value.location_type_id}","is_primary":"{$value.is_primary}","email":"{$value.email}"\}' \
   * {$API_KEY_HEADER} \
   * {$SITE_KEY_HEADER}
   * ```
   * 
   * {$result}
   * 
   * @end_document
   */
}

