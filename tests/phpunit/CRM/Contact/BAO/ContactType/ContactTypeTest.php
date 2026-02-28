<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Contact/BAO/ContactType.php';

class CRM_Contact_BAO_ContactType_ContactTypeTest extends CiviUnitTestCase {

  public function get_info() {
    return [
                 'name'        => 'Contact Subtype',
                 'description' => 'Test Contact for subtype.',
                 'group'       => 'CiviCRM BAO Tests',
                 ];
  }

  public function setUp() {
    parent::setUp();

    $params = [ 'label'     => 'sub1_individual',
                     'name'      => 'sub1_individual',
                     'parent_id' => 1,//Individual
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesIndividual[] = $params['name'];

    $params = [ 'label'     => 'sub2_individual',
                     'name'      => 'sub2_individual',
                     'parent_id' => 1,//Individual
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesIndividual[] = $params['name'];

    $params = [ 'label'     => 'sub_organization',
                     'name'      => 'sub_organization',
                     'parent_id' => 3,//Organization
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesOrganization[] = $params['name'];

    $params = [ 'label'     => 'sub_household',
                     'name'      => 'sub_household',
                     'parent_id' => 2,//Household
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesHousehold[] = $params['name'];
  }

  /*
   * test contactTypes() and subTypes() methods with valid data
   * success expected
   */
  public function testGetMethods() {

    // check all contact types
    $contactTypes = [ 'Individual', 'Organization' , 'Household' ];
    $result       = CRM_Contact_BAO_ContactType::contactTypes('Individual');
    foreach ($contactTypes as $type) {
      $this->assertEquals(in_array($type, $result), TRUE, 'In line '. __LINE__);
    }

    // check for type:Individual
    $result = CRM_Contact_BAO_ContactType::subTypes('Individual');
    foreach ($result as $subtype) {
      $subTypeName = in_array($subtype, $this->subTypesIndividual);
      if (!empty($subTypeName)) {
        $this->assertEquals($subTypeName, TRUE, 'In line '. __LINE__);
      }
      $this->assertEquals(in_array($subtype, $this->subTypesOrganization), FALSE, 'In line '. __LINE__);
      $this->assertEquals(in_array($subtype, $this->subTypesHousehold), FALSE, 'In line '. __LINE__);
    }

    // check for type:Organization
    $result = CRM_Contact_BAO_ContactType::subTypes('Organization');
    foreach ($result as $subtype) {
      $this->assertEquals(in_array($subtype, $this->subTypesIndividual), FALSE, 'In line '. __LINE__);
      $subTypeName = in_array($subtype, $this->subTypesOrganization);
      if (!empty($subTypeName)) {
        $this->assertEquals($subTypeName, TRUE, 'In line '. __LINE__);
      }
      $subTypeName = in_array($subTypeName, $this->subTypesHousehold);
      if (empty($subTypeName)) {
        $this->assertEquals($subTypeName, FALSE, 'In line '. __LINE__);
      }
    }

    // check for type:Household
    $result = CRM_Contact_BAO_ContactType::subTypes('Household');
    foreach ($result as $subtype) {
      $this->assertEquals(in_array($subtype, $this->subTypesIndividual), FALSE, 'In line '. __LINE__);
      $this->assertEquals(in_array($subtype, $this->subTypesOrganization), FALSE, 'In line '. __LINE__);
      $this->assertEquals(in_array($subtype, $this->subTypesHousehold), TRUE, 'In line '. __LINE__);
    }

    // check for all conatct types
    $result = CRM_Contact_BAO_ContactType::subTypes();
    foreach ($this->subTypesIndividual as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE, 'In line '. __LINE__);
    }
    foreach ($this->subTypesOrganization as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE, 'In line '. __LINE__);
    }
    foreach ($this->subTypesHousehold as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE, 'In line '. __LINE__);
    }

  }

  /*
   * test subTypes() methods with invalid data
   */
  public function testGetMethodsInvalid() {

    $params = 'invalid';
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE, 'In line '. __LINE__);

    $params = [ 'invalid' ];
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE, 'In line '. __LINE__);
  }

  /*
   * test add() methods with valid data
   * success expected
   */
  public function testAdd() {

    $params = [ 'label'     => 'indiviSubType',
                     'name'      => 'indiviSubType',
                     'parent_id' => 1,
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label'], 'In line '. __LINE__);
    $this->assertEquals($result->name, $params['name'], 'In line '. __LINE__);
    $this->assertEquals($result->parent_id, $params['parent_id'], 'In line '. __LINE__);
    $this->assertEquals($result->is_active, $params['is_active'], 'In line '. __LINE__);
    CRM_Contact_BAO_ContactType::del($result->id);

    $params = [ 'label'     => 'householdSubType',
                     'name'      => 'householdSubType',
                     'parent_id' => 2,
                     'is_active' => 0
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label'], 'In line '. __LINE__);
    $this->assertEquals($result->name, $params['name'], 'In line '. __LINE__);
    $this->assertEquals($result->parent_id, $params['parent_id'], 'In line '. __LINE__);
    $this->assertEquals($result->is_active, $params['is_active'], 'In line '. __LINE__);
    CRM_Contact_BAO_ContactType::del($result->id);
  }

  /*
   * test add() with invalid data
   */
  public function testAddInvalid1() {

    // parent id does not exist in db
    $params = [ 'label'     => 'subType',
                     'name'      => 'subType',
                     'parent_id' => 100, //non existant
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL, 'In line '. __LINE__);

  }

  public function testAddInvalid2() {

    // params does not have name and label keys
    $params = [ 'parent_id' => 1,
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL, 'In line '. __LINE__);
  }

  public function testAddInvalid3() {

    // params does not have parent_id
    $params = [ 'label'     => 'subType',
                     'name'      => 'subType',
                     'is_active' => 1
                     ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL, 'In line'. __LINE__);
  }

  /*
   * test del() with valid data
   * success expected
   */
  public function testDel() {

    $params = [ 'label'     => 'indiviSubType',
                     'name'      => 'indiviSubType',
                     'parent_id' => 1,
                     'is_active' => 1
                     ];
    $subtype = CRM_Contact_BAO_ContactType::add($params);

    $del    = CRM_Contact_BAO_ContactType::del($subtype->id);
    $result = CRM_Contact_BAO_ContactType::subTypes();
    $this->assertEquals($del, TRUE, 'In line '. __LINE__);
    $this->assertEquals(in_array($subtype->name, $result), TRUE, 'In line '. __LINE__);

  }

  /*
   * test del() with invalid data
   */
  public function testDelInvalid() {

    $del    = CRM_Contact_BAO_ContactType::del(NULL);
    $this->assertEquals($del, FALSE, 'In line '. __LINE__);

  }
}
