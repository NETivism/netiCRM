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




require_once 'api/v3/EntityTag.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_EntityTagTest extends CiviUnitTestCase {

  protected $_individualID;
  protected $_householdID;
  protected $_organizationID;
  protected $_tagID;
  protected $_apiversion;
  protected $_tag; function setUp() {
    parent::setUp();
    $this->_apiversion = 3;

    $this->quickCleanup(['civicrm_tag', 'civicrm_entity_tag']);

    $this->_individualID = $this->individualCreate(NULL);
    $this->_tag = $this->tagCreate(NULL);
    $this->_tagID = $this->_tag['id'];
    $this->_householdID = $this->houseHoldCreate(NULL);
    $this->_organizationID = $this->organizationCreate(NULL);
  }

  function tearDown() {}

  function testAddEmptyParams() {
    $params = ['version' => $this->_apiversion];
    $individualEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'contact_id is a required field');
  }

  function testAddWithoutTagID() {
    $params = [
      'contact_id' => $this->_individualID,
      'version' => $this->_apiversion,
    ];
    $individualEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'tag_id is a required field');
  }

  function testAddWithoutContactID() {
    $params = [
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $individualEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'contact_id is a required field');
  }

  function testContactEntityTagCreate() {
    $params = [
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);

    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['added'], 1);
  }

  function testAddDouble() {
    $individualId   = $this->_individualID;
    $organizationId = $this->_organizationID;
    $tagID          = $this->_tagID;
    $params         = [
      'contact_id' => $individualId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'create', $params);

    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['added'], 1);

    $params = [
      'contact_id_i' => $individualId,
      'contact_id_o' => $organizationId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['not_added'], 1);
  }

  ///////////////// civicrm_entity_tag_get methods
  function testGetWrongParamsType() {
    $ContactId = $this->_individualID;
    $tagID     = $this->_tagID;
    $params    = [
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $individualEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['is_error'], 0);
    $this->assertEquals($individualEntity['added'], 1);

    $paramsEntity = "wrong params";
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);

    $this->assertEquals($entity['is_error'], 1,
      "In line " . __LINE__
    );
    $this->assertEquals($entity['error_message'], 'Input variable `params` is not an array');
  }

  function testIndividualEntityTagGetWithoutContactID() {
    $paramsEntity = ['version' => $this->_apiversion];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
    $this->assertEquals($entity['is_error'], 1);
    $this->assertNotNull($entity['error_message']);
    $this->assertEquals($entity['error_message'], 'Mandatory key(s) missing from params array: entity_id');
  }

  function testIndividualEntityTagGet() {
    $contactId = $this->_individualID;
    $tagID     = $this->_tagID;
    $params    = [
      'contact_id' => $contactId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $individualEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['is_error'], 0);
    $this->assertEquals($individualEntity['added'], 1);

    $paramsEntity = [
      'contact_id' => $contactId,
      'version' => $this->_apiversion,
    ];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
  }

  function testHouseholdEntityGetWithoutContactID() {
    $paramsEntity = ['version' => $this->_apiversion];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
    $this->assertEquals($entity['is_error'], 1);
    $this->assertNotNull($entity['error_message']);
  }

  function testHouseholdEntityGet() {
    $ContactId = $this->_householdID;
    $tagID     = $this->_tagID;
    $params    = [
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $householdEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($householdEntity['is_error'], 0);
    $this->assertEquals($householdEntity['added'], 1);

    $paramsEntity = ['contact_id' => $ContactId];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
  }

  function testOrganizationEntityGetWithoutContactID() {
    $paramsEntity = ['version' => $this->_apiversion];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
    $this->assertEquals($entity['is_error'], 1);
    $this->assertNotNull($entity['error_message']);
  }

  function testOrganizationEntityGet() {
    $ContactId = $this->_organizationID;
    $tagID     = $this->_tagID;
    $params    = [
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
      'version' => $this->_apiversion,
    ];

    $organizationEntity = civicrm_api('entity_tag', 'create', $params);
    $this->assertEquals($organizationEntity['is_error'], 0);
    $this->assertEquals($organizationEntity['added'], 1);

    $paramsEntity = ['contact_id' => $ContactId];
    $entity = civicrm_api('entity_tag', 'get', $paramsEntity);
  }

  ///////////////// civicrm_entity_tag_remove methods
  function testEntityTagRemoveNoTagId() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'delete', $params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'tag_id is a required field');
  }

  function testEntityTagRemoveINDHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'delete', $params);

    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['removed'], 2);
  }

  function testEntityTagDeleteHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['removed'], 1);
  }

  function testEntityTagRemoveHHORG() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }

  ///////////////// civicrm_entity_tag_display methods
  function testEntityTagDisplayWithContactId() {
    $entityTagParams = [
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id' => $this->_individualID,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api3_entity_tag_display($params);
    $this->assertEquals($this->_tag['values'][$this->_tag['id']]['name'], $result);
  }

  ///////////////// civicrm_tag_entities_get methods



  ///////////////// civicrm_entity_tag_common methods
  function testCommonAddEmptyParams() {
    $params = [
      'version' => $this->_apiversion,
    ];
    $individualEntity = _civicrm_api3_entity_tag_common($params, 'add');
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'contact_id is a required field');
  }

  function testCommonAddWithoutTagID() {
    $params = [
      'contact_id' => $this->_individualID,
      'version' => $this->_apiversion,
    ];
    $individualEntity = _civicrm_api3_entity_tag_common($params, 'add');
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'tag_id is a required field');
  }

  function testCommonAddWithoutContactID() {
    $params = [
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $individualEntity = _civicrm_api3_entity_tag_common($params, 'add');
    $this->assertEquals($individualEntity['is_error'], 1);
    $this->assertEquals($individualEntity['error_message'], 'contact_id is a required field');
  }

  function testCommonContactEntityTagAdd() {
    $params = [
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $individualEntity = _civicrm_api3_entity_tag_common($params, 'add');
    $this->assertEquals($individualEntity['is_error'], 0);
    $this->assertEquals($individualEntity['added'], 1);
  }

  function testEntityTagCommonRemoveNoContactId() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = _civicrm_api3_entity_tag_common($params, 'remove');
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'contact_id is a required field');
  }

  function testEntityTagCommonRemoveNoTagId() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'version' => $this->_apiversion,
    ];

    $result = _civicrm_api3_entity_tag_common($params, 'remove');
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'tag_id is a required field');
  }

  function testEntityTagCommonRemoveINDHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = _civicrm_api3_entity_tag_common($params, 'remove');

    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['removed'], 2);
  }

  function testEntityTagCommonRemoveHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = _civicrm_api3_entity_tag_common($params, 'remove');
    $this->assertEquals($result['removed'], 1);
  }

  function testEntityTagCommonRemoveHHORG() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
      'version' => $this->_apiversion,
    ];

    $result = _civicrm_api3_entity_tag_common($params, 'remove');
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }
}

