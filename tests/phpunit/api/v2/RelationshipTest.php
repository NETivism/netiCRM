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



require_once 'api/v2/Relationship.php';
require_once 'api/v2/RelationshipType.php';
require_once 'api/v2/CustomGroup.php';
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class contains api test cases for "civicrm_relationship"
 *
 */
class api_v2_RelationshipTest extends CiviUnitTestCase {
  protected $_cId_a;
  protected $_cId_b;
  protected $_relTypeID;
  protected $_ids = array();
  protected $_customGroupId = NULL;
  protected $_customFieldId = NULL; function get_info() {
    return array(
      'name' => 'Relationship Create',
      'description' => 'Test all Relationship Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->_cId_a = $this->individualCreate();
    $this->_cId_b = $this->organizationCreate();

    //Create a relationship type
    $relTypeParams = array(
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $this->_relTypeID = $this->relationshipTypeCreate($relTypeParams);
  }

  function tearDown() {
    $this->relationshipTypeDelete($this->_relTypeID);
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_relationship',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  ///////////////// civicrm_relationship_create methods

  /**
   * check with empty array
   */
  function testRelationshipCreateEmpty() {
    $params = array();
    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input Parameters empty');
  }

  /**
   * check with No array
   */
  function testRelationshipCreateParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array');
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipCreateWithoutRequired() {
    $params = array(
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Required fields not found contact_id_a contact_id_b relationship_type_id');
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipCreateWithIncorrectData() {

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    );

    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);

    //contact id is not an integer
    $params = array(
      'contact_id_a' => 'invalid',
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );
    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);

    //contact id does not exists
    $params['contact_id_a'] = 999;
    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);

    //invalid date
    $params['contact_id_a'] = $this->_cId_a;
    $params['start_date'] = array('d' => '1', 'M' => '1');
    $result = &civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * check relationship creation with invalid Relationship
   */
  function testRelationshipCreatInvalidRelationship() {
    // both the contact of type Individual
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_a,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );

    $result = civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);

    // both the contact of type Organization
    $params = array(
      'contact_id_a' => $this->_cId_b,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );

    $result = civicrm_relationship_create($params);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * check relationship already exists
   */
  function testRelationshipCreateAlreadyExists() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => NULL,
      'is_active' => 1,
    );
    $relationship = civicrm_relationship_create($params);

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );
    $result = civicrm_relationship_create($params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Relationship already exists');

    $params['id'] = $relationship['result']['id'];
    $result = civicrm_relationship_delete($params);
  }

  /**
   * check relationship creation
   */
  function testRelationshipCreate() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
      'note' => 'note',
    );

    $result = civicrm_relationship_create($params);
    $this->assertNotNull($result['result']['id']);

    $relationParams = array(
      'id' => CRM_Utils_Array::value('id', $result['result']),
    );
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['result']['id'], $relationParams);

    $params['id'] = $result['result']['id'];
    $result = civicrm_relationship_delete($params);
  }

  /**
   * check relationship creation with custom data
   */
  function testRelationshipCreateWithCustomData() {
    $customGroup = $this->createCustomGroup();
    $this->_customGroupId = $customGroup['id'];
    $this->_ids = $this->createCustomField();
    //few custom Values for comparing
    $custom_params = array(
      "custom_{$this->_ids[0]}" => 'Hello! this is custom data for relationship',
      "custom_{$this->_ids[1]}" => 'Y',
      "custom_{$this->_ids[2]}" => '2009-07-11 00:00:00',
      "custom_{$this->_ids[3]}" => 'http://example.com',
    );

    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );
    $params = array_merge($params, $custom_params);
    $result = civicrm_relationship_create($params);

    $this->assertNotNull($result['result']['id']);
    $relationParams = array(
      'id' => CRM_Utils_Array::value('id', $result['result']),
    );
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Contact_DAO_Relationship', $result['result']['id'], $relationParams);

    $params['id'] = $result['result']['id'];
    $result = civicrm_relationship_delete($params);
  }

  function createCustomGroup() {
    $params = array(
      'title' => 'Test Custom Group',
      'extends' => array('Relationship'),
      'weight' => 5,
      'style' => 'Inline',
      'is_active' => 1,
      'max_multiple' => 0,
    );
    $customGroup = &civicrm_custom_group_create($params);
    return NULL;
  }

  function createCustomField() {
    $ids = array();
    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'label' => 'Enter text about relationship',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'xyz',
      'weight' => 1,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $customField = NULL;
    $ids[]       = $customField['result']['customFieldId'];

    $optionValue[] = array(
      'label' => 'Red',
      'value' => 'R',
      'weight' => 1,
      'is_active' => 1,
    );
    $optionValue[] = array(
      'label' => 'Yellow',
      'value' => 'Y',
      'weight' => 2,
      'is_active' => 1,
    );
    $optionValue[] = array(
      'label' => 'Green',
      'value' => 'G',
      'weight' => 3,
      'is_active' => 1,
    );

    $params = array(
      'label' => 'Pick Color',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 2,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $optionValue,
      'custom_group_id' => $this->_customGroupId,
    );

    $customField = &civicrm_custom_field_create($params);

    $ids[] = $customField['result']['customFieldId'];

    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'name' => 'test_date',
      'label' => 'test_date',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'default_value' => '20090711',
      'weight' => 3,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);

    $ids[] = $customField['result']['customFieldId'];
    $params = array(
      'custom_group_id' => $this->_customGroupId,
      'name' => 'test_link',
      'label' => 'test_link',
      'html_type' => 'Link',
      'data_type' => 'Link',
      'default_value' => 'http://civicrm.org',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $ids[] = $customField['result']['customFieldId'];
    return $ids;
  }

  ///////////////// civicrm_relationship_delete methods

  /**
   * check with empty array
   */
  function testRelationshipDeleteEmpty() {
    $params = array();
    $result = &civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'No input parameter present');
  }

  /**
   * check with No array
   */
  function testRelationshipDeleteParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = &civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array');
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipDeleteWithoutRequired() {
    $params = array(
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $result = &civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Missing required parameter');
  }

  /**
   * check with incorrect required fields
   */
  function testRelationshipDeleteWithIncorrectData() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => 'Breaking Relationship',
    );

    $result = &civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Missing required parameter');

    $params['id'] = "Invalid";
    $result = &civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Invalid value for relationship ID');
  }

  /**
   * check relationship creation
   */
  function testRelationshipDelete() {
    $params = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'is_active' => 1,
    );

    $result = civicrm_relationship_create($params);
    $this->assertNotNull($result['result']['id']);

    //Delete relationship
    $params = array();
    $params['id'] = $result['result']['id'];

    $result = civicrm_relationship_delete($params);
  }

  ///////////////// civicrm_relationship_update methods

  /**
   * check with empty array
   */
  function testRelationshipUpdateEmpty() {
    $params = array();
    $result = &civicrm_relationship_update($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Required fields contact_id_a,contact_id_b,relationship_type_id for CRM_Contact_DAO_Relationship are not found', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with No array
   */
  function testRelationshipUpdateParamsNotArray() {
    $params = 'relationship_type_id = 5';
    $result = &civicrm_relationship_update($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Input parameters is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check if required fields are not passed
   */
  function testRelationshipUpdateWithoutRequired() {
    $params = array(
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $result = &civicrm_relationship_update($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals('Required fields contact_id_a for CRM_Contact_DAO_Relationship are not found', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check relationship update
   */
  function testRelationshipUpdate() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $result = civicrm_relationship_create($relParams);
    $this->assertNotNull($result['result']['id'], 'In line ' . __LINE__);
    $this->_relationID = $result['result']['id'];

    $params = array(
      'relationship_id' => $this->_relationID,
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 0,
    );

    $result = civicrm_relationship_update($params);

    $this->assertEquals($result['is_error'], 1);
    //delete created relationship
    $params = array();
    $params['id'] = $this->_relationID;

    $result = civicrm_relationship_delete($params);
    $this->assertEquals($result['is_error'], 0);
  }


  ///////////////// civicrm_relationship_get methods

  /**
   * check with empty array
   */
  function testRelationshipGetEmptyParams() {
    //get relationship
    $params = array();
    $result = &civicrm_relationship_get($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Could not find contact_id in input parameters.');
  }

  /**
   * check with params Not Array.
   */
  function testRelationshipGetParamsNotArray() {
    $params = 'relationship';

    $result = &civicrm_relationship_get($params);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array');
  }

  /**
   * check with valid params array.
   */
  function testRelationshipsGet() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $result = civicrm_relationship_create($relParams);

    //get relationship
    $params = array('contact_id' => $this->_cId_b);
    $results = &civicrm_relationship_get($params);
    $this->assertEquals($result['is_error'], 0);
  }

  ///////////////// civicrm_relationship_type_add methods

  /**
   * check with invalid relationshipType Id
   */
  function testRelationshipTypeAddInvalidId() {
    $relTypeParams = array(
      'id' => 'invalid',
      'name_a_b' => 'Relation 1 for delete',
      'name_b_a' => 'Relation 2 for delete',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    );
    $result = &civicrm_relationship_type_add($relTypeParams);
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Invalid value for relationship type ID');
  }

  ///////////////// civicrm_get_relationships

  /**
   * check with invalid data
   */
  function testGetRelationshipInvalidData() {
    $contact_a = array('contact_id' => $this->_cId_a);
    $contact_b = array('contact_id' => $this->_cId_b);

    //no relationship has been created
    $result = &civicrm_get_relationships($contact_a, $contact_b, NULL, 'asc');
    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Invalid Data');
  }

  /**
   * check with valid data with contact_b
   */
  function testGetRelationshipWithContactB() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $relationship = civicrm_relationship_create($relParams);

    $contact_a = array('contact_id' => $this->_cId_a);
    $contact_b = array('contact_id' => $this->_cId_b);

    $result = &civicrm_get_relationships($contact_a, $contact_b, NULL, 'desc');
    $this->assertEquals($result['is_error'], 0);

    $params['id'] = $relationship['result']['id'];
    $result = civicrm_relationship_delete($params);
  }

  /**
   * check with valid data with relationshipTypes
   */
  function testGetRelationshipWithRelTypes() {
    $relParams = array(
      'contact_id_a' => $this->_cId_a,
      'contact_id_b' => $this->_cId_b,
      'relationship_type_id' => $this->_relTypeID,
      'start_date' => array('d' => '10', 'M' => '1', 'Y' => '2008'),
      'end_date' => array('d' => '10', 'M' => '1', 'Y' => '2009'),
      'is_active' => 1,
    );

    $relationship = civicrm_relationship_create($relParams);

    $contact_a = array('contact_id' => $this->_cId_a);
    $relationshipTypes = array('Relation 1 for delete');

    $result = &civicrm_get_relationships($contact_a, NULL, $relationshipTypes, 'desc');

    $this->assertEquals($result['is_error'], 0);

    $params['id'] = $relationship['result']['id'];
    $result = civicrm_relationship_delete($params);
  }
}



