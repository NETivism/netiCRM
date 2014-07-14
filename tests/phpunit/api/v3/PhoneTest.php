<?php
// $Id$

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


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_PhoneTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params; function setUp() {
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

  function tearDown() {
    $this->locationTypeDelete($this->_locationType);
    $this->contactDelete($this->_contactID);
  }

  public function testCreatePhone() {

    $result = civicrm_api('phone', 'create', $this->_params);

    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);

    // $this->assertEquals( 1, $result['id'], 'In line ' . __LINE__ );

    $delresult = civicrm_api('phone', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertEquals(0, $delresult['is_error'], 'In line ' . __LINE__);
  }

  public function testDeletePhone() {
    //create one
    $create = civicrm_api('phone', 'create', $this->_params);

    $this->assertAPISuccess($create, 'In line ' . __LINE__);

    $result = civicrm_api('phone', 'delete', array('id' => $create['id'], 'version' => 3));
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
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
   * Test civicrm_address_get - success expected.
   */
  public function testGet() {
    $phone = civicrm_api('phone', 'create', $this->_params);
    $this->assertAPISuccess($phone, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $phone['id'],
      'phone' => $phone['values'][$phone['id']]['phone'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Phone', 'Get', ($params));
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['location_type_id'], $result['values'][$phone['id']]['location_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone_type_id'], $result['values'][$phone['id']]['phone_type_id'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['is_primary'], $result['values'][$phone['id']]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals($phone['values'][$phone['id']]['phone'], $result['values'][$phone['id']]['phone'], 'In line ' . __LINE__);
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
}

