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



require_once 'api/v2/Contact.php';
require_once 'api/v2/Location.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v2_LocationTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_locationType; function get_info() {
    return array(
      'name' => 'Location Add',
      'description' => 'Test all Location Add API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_contactID = $this->organizationCreate();

    CRM_Core_Pseudoconstant::flush('locationType');
    $this->_locationType = $this->locationTypeCreate();
  }

  function tearDown() {
    $this->locationTypeDelete($this->_locationType->id);
    $this->contactDelete($this->_contactID);
  }

  ///////////////// civicrm_location_add methods
  function testAddWrongParamsType() {
    $params = 1;
    $location = &civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 1);
    $this->assertEquals('Params need to be of type array!', $location['error_message']);
  }

  function testAddWithEmptyParams() {
    $params = array();
    $location = &civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 1);
    $this->assertEquals($location['error_message'], 'Input Parameters empty');
  }

  function testAddWithoutContactid() {
    $params = array(
      'location_type' => 'Home',
      'is_primary' => 1,
      'name' => 'Ashbury Terrace',
    );
    $location = &civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 1);
    $this->assertEquals($location['error_message'], 'Required fields not found for location contact_id');
  }

  function testAddWithoutLocationid() {
    $params = array(
      'contact_id' => $this->_contactID,
      'is_primary' => 1,
      'name' => 'aaadadf',
    );

    $location = &civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 1);
    $this->assertEquals($location['error_message'], 'Please set atleast one location block. ( address or email or phone or im or website)');
  }

  function testAddOrganizationWithAddress() {
    $address = array(
      1 =>
      array(
        'name' => 'Saint Helier St',
        'location_type' => 'New Location Type',
        'is_primary' => 1,
        'county' => 'Marin',
        'country' => 'India',
        'state_province' => 'Michigan',
        'street_address' => 'B 103, Ram Maruti Road',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
      ),
    );

    $params = array(
      'contact_id' => $this->_contactID,
      'address' => $address,
    );

    $location = civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 0);
    $this->assertTrue(!empty($location['result']['address']));

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $this->_locationType->id,
    );
    $this->locationDelete($params);
  }

  function testAddWithAddressEmailPhoneIM() {
    $workPhone = array(
      'phone' => '91-20-276048',
      'phone_type_id' => 1,
      'is_primary' => 1,
    );

    $workFax = array(
      'phone' => '91-20-234-657686',
      'phone_type_id' => 3,
    );

    $phone = array($workPhone, $workFax);

    $workIMFirst = array(
      'name' => 'Hi',
      'provider_id' => '1',
      'is_primary' => 0,
    );

    $workIMSecond = array(
      'name' => 'Hola',
      'provider_id' => '3',
      'is_primary' => 0,
    );

    $workIMThird = array(
      'name' => 'Welcome',
      'provider_id' => '5',
      'is_primary' => 1,
    );

    $im = array($workIMFirst, $workIMSecond, $workIMThird);

    $workEmailFirst = array(
      'email' => 'abc@def.com',
      'on_hold' => 1,
    );

    $workEmailSecond = array(
      'email' => 'yash@hotmail.com',
      'is_bulkmail' => 1,
    );

    $workEmailThird = array('email' => 'yashi@yahoo.com');

    $email = array($workEmailFirst, $workEmailSecond, $workEmailThird);

    $address = array(
      1 => array(
        'city' => 'San Francisco',
        'state_province' => 'California',
        'country_id' => 1228,
        'street_address' => '123, FC Road',
        'supplemental_address_1' => 'Near Wenna Lake',
        'is_primary' => 1,
        'location_type' => 'New Location Type',
      ));

    $params = array(
      'contact_id' => $this->_contactID,
      'address' => $address,
      'phone' => $phone,
      'im' => $im,
      'email' => $email,
    );

    $location = &civicrm_location_add($params);

    $this->assertEquals($location['is_error'], 0);
    $this->assertEquals(count($location['result']['phone']), 2);
    $this->assertEquals(count($location['result']['email']), 3);
    $this->assertEquals(count($location['result']['im']), 3);
    $this->assertTrue(!empty($location['result']['address']));

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $this->_locationType->id,
    );
    $this->locationDelete($params);

    $locationTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', 'Home', 'id', 'name');
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $locationTypeId,
    );
    $this->locationDelete($params);
  }

  ///////////////// civicrm_location_delete methods
  function testDeleteWrongParamsType() {
    $location = 1;

    $locationDelete = &civicrm_location_delete($location);
    $this->assertEquals($locationDelete['is_error'], 1);
    $this->assertEquals($locationDelete['error_message'], 'Params need to be of type array!');
  }

  function testDeleteWithEmptyParams() {
    $location = array();
    $locationDelete = &civicrm_location_delete($location);
    $this->assertEquals($locationDelete['is_error'], 1);
    $this->assertEquals($locationDelete['error_message'], '$contact is not valid contact datatype');
  }

  function testDeleteWithMissingContactId() {
    $params = array('location_type' => 3);
    $locationDelete = &civicrm_location_delete($params);

    $this->assertEquals($locationDelete['is_error'], 1);
    $this->assertEquals($locationDelete['error_message'], '$contact is not valid contact datatype');
  }

  function testDeleteWithMissingLocationTypeId() {
    $params = array('contact_id' => $this->_contactID);
    $locationDelete = &civicrm_location_delete($params);

    $this->assertEquals($locationDelete['is_error'], 1);
    $this->assertEquals($locationDelete['error_message'], 'missing or invalid location');
  }

  function testDeleteWithNoMatch() {
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => 10,
    );
    $locationDelete = &civicrm_location_delete($params);

    $this->assertEquals($locationDelete['is_error'], 1);
    $this->assertEquals($locationDelete['error_message'], 'invalid location type');
  }

  function testDelete() {
    $location = $this->locationAdd($this->_contactID);
    $locationTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', 'New Location Type', 'id', 'name');

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $locationTypeId,
    );
    $locationDelete = &civicrm_location_delete($params);
    $this->assertNull($locationDelete);
    $this->assertDBNull('CRM_Core_DAO_Address',
      $location['result']['address'][0],
      'contact_id',
      'id',
      'Check DB for deleted Location.'
    );
  }

  ///////////////// civicrm_location_get methods
  function testGetWrongParamsType() {
    $params = 1;

    $result = &civicrm_location_get($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Params need to be of type array!', $result['error_message']);
  }

  function testGetWithEmptyParams() {
    // empty params
    $params = array();

    $result = &civicrm_location_get($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testGetWithoutContactId() {
    // no contact_id
    $params = array('location_type' => 'Main');

    $result = &civicrm_location_get($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testGetWithEmptyLocationType() {
    // location_type an empty array
    $params = array(
      'contact_id' => $this->_contactId,
      'location_type' => array(),
    );

    $result = &civicrm_location_get($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testGet() {
    $location = $this->locationAdd($this->_contactID);

    $proper = array(
      'country_id' => 1228,
      'county_id' => 3,
      'state_province_id' => 1021,
      'supplemental_address_1' => 'Hallmark Ct',
      'supplemental_address_2' => 'Jersey Village',
    );
    $result = civicrm_location_get(array('contact_id' => $this->_contactId));
    foreach ($result as $location) {
      if (CRM_Utils_Array::value('address', $location)) {
        foreach ($proper as $field => $value) {
          $this->assertEquals($location['address'][$field], $value);
        }
      }
    }

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $this->_locationType->id,
    );
    $this->locationDelete($params);
  }

  function testGetTwoSeriesCompliance() {
    $location = $this->locationAdd($this->_contactID);
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => 'Home',
      'name' => 'Saint Helie',
      'country' => 'United States',
      'state_province' => 'Michigan',
      'supplemental_address_1' => 'Hallmark ',
      'supplemental_address_2' => 'Jersey Village',
    );
    $locationHome = civicrm_location_add($params);
    $result = civicrm_location_get(array('contact_id' => $params['contact_id']));
    foreach ($result as $location) {
      if (CRM_Utils_Array::value('address', $location)) {
        $this->assertEquals($location['address']['contact_id'], $this->_contactID);
      }
    }

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $this->_locationType->id,
    );
    $this->locationDelete($params);

    $locationTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', 'Home', 'id', 'name');
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $locationTypeId,
    );
    $this->locationDelete($params);
  }

  ///////////////// civicrm_location_update methods
  function testUpdateWrongParamsType() {
    $location = 1;

    $locationUpdate = &civicrm_location_update($location);
    $this->assertEquals($locationUpdate['is_error'], 1);
    $this->assertEquals('Params need to be of type array!', $locationUpdate['error_message']);
  }

  function testLocationUpdateWithEmptyParams() {
    $params = array();

    $result = civicrm_location_update($params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testLocationUpdateWithMissingContactId() {
    $params = array('location_type' => 3);
    $locationUpdate = &civicrm_location_update($params);

    $this->assertEquals($locationUpdate['is_error'], 1);
    $this->assertEquals($locationUpdate['error_message'], '$contact is not valid contact datatype');
  }

  function testLocationUpdateWithMissingLocationTypeId() {
    $params = array('contact_id' => $this->_contactID);
    $locationUpdate = &civicrm_location_update($params);

    $this->assertEquals($locationUpdate['is_error'], 1);
    $this->assertEquals($locationUpdate['error_message'], 'missing or invalid location_type_id');
  }

  function testLocationUpdate() {
    $location = $this->locationAdd($this->_contactID);

    $workPhone = array(
      'phone' => '02327276048',
      'phone_type' => 'Phone',
      'location_type' => 'New Location Type',
    );

    $phones = array($workPhone);

    $workEmailFirst = array(
      'email' => 'xyz@indiatimes.com',
      'location_type' => 'New Location Type',
    );

    $workEmailSecond = array(
      'email' => 'abcdef@hotmail.com',
      'location_type' => 'New Location Type',
    );

    $emails = array($workEmailFirst, $workEmailSecond);

    $params = array(
      'phone' => $phones,
      'email' => $emails,
      'contact_id' => $this->_contactID,
    );

    $locationUpdate = &civicrm_location_update($params);

    $this->assertEquals($locationUpdate['is_error'], 0, 'In line ' . __LINE__);

    $params = array(
      'contact_id' => $this->_contactID,
      'location_type' => $this->_locationType->id,
    );
    $this->locationDelete($params);
  }


  ///////////////// helper methods
  function _checkResult(&$result, &$match) {
    if (CRM_Utils_Array::value('address', $match)) {
      $this->assertDBState('CRM_Core_DAO_Address', $result['address'][0], $match['address'][0]);
    }

    if (CRM_Utils_Array::value('phone', $match)) {
      for ($i = 0; $i < count($result['phone']); $i++) {
        $this->assertDBState('CRM_Core_DAO_Phone', $result['phone'][$i], $match['phone'][$i]);
      }
    }

    if (CRM_Utils_Array::value('email', $match)) {
      for ($i = 0; $i < count($result['email']); $i++) {
        $this->assertDBState('CRM_Core_DAO_Email', $result['email'][$i], $match['email'][$i]);
      }
    }

    if (CRM_Utils_Array::value('im', $match)) {
      for ($i = 0; $i < count($result['im']); $i++) {
        $this->assertDBState('CRM_Core_DAO_IM', $result['im'][$i], $match['im'][$i]);
      }
    }
  }
}

