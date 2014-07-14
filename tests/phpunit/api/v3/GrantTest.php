<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_GrantTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'Grant';
  public $DBResetRequired = FALSE; function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
      'version' => 3,
      'contact_id' => $this->ids['contact'][0],
      'application_received_date' => 'now',
      'decision_date' => 'next Monday',
      'amount_total' => '500',
      'status_id' => 1,
      'rationale' => 'Just Because',
      'currency' => 'USD',
      'grant_type_id' => 1,
    );
  }

  function tearDown() {
    foreach ($this->ids as $entity => $entities) {
      foreach ($entities as $id) {
        civicrm_api($entity, 'delete', array('version' => $this->_apiversion, 'id' => $id));
      }
    }
  }

  public function testCreateGrant() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetGrant() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->ids['grant'][0] = $result['id'];
    $result = civicrm_api($this->_entity, 'get', array('version' => $this->_apiversion, 'rationale' => 'Just Because'));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
  }

  public function testDeleteGrant() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $result = civicrm_api($this->_entity, 'delete', array('version' => 3, 'id' => $result['id']));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }
  /*
 * This is a test to check if setting fields one at a time alters other fields
 * Issues Hit so far =
 * 1) Currency keeps getting reset to USD -  BUT this may be the only enabled currency
 *  - in which case it is valid
 * 2)
 */

  public function testCreateAutoGrant() {
    $entityName = $this->_entity;
    $baoString  = 'CRM_Grant_BAO_Grant';
    $fields     = civicrm_api($entityName, 'getfields', array(
        'version' => 3,
      )
    );

    $fields = $fields['values'];
    $return = array_keys($fields);
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject($baoString, array('currency' => 'USD'), 2, 0);
    $getentities = civicrm_api($entityName, 'get', array(
        'version' => 3,
        'sequential' => 1,
        'return' => $return,
      ));

    // lets use first rather than assume only one exists
    $entity = $getentities['values'][0];
    $entity2 = $getentities['values'][1];
    foreach ($fields as $field => $specs) {
      if ($field == 'currency' || $field == 'id') {
        continue;
      }
      switch ($specs['type']) {
        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIMESTAMP:
          $entity[$field] = '2012-05-20';
          break;

        case CRM_Utils_Type::T_STRING:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_EMAIL:
          $entity[$field] = 'New String';
          break;

        case CRM_Utils_Type::T_INT:
          // probably created with a 1
          $entity[$field] = 111;
          if (CRM_Utils_Array::value('FKClassName', $specs)) {
            $entity[$field] = empty($entity2[$field]) ? $entity2[$specs]['uniqueName'] : $entity2[$field];
          }
          break;

        case CRM_Utils_Type::T_BOOL:
        case CRM_Utils_Type::T_BOOLEAN:
          // probably created with a 1
          $entity[$field] = 0;
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_MONEY:
          $entity[$field] = 222;
          break;

        case CRM_Utils_Type::T_URL:
          $entity[$field] = 'warm.beer.com';
      }
      $updateParams = array(
        'version' => 3,
        'id' => $entity['id'],
        $field => $entity[$field],
      );
      $update = civicrm_api($entityName, 'create', $updateParams);

      $this->assertAPISuccess($update, 'in line ' . __LINE__);
      $checkParams = array(
        'id' => $entity['id'],
        'version' => 3,
        'sequential' => 1,
      );
      $checkEntity = civicrm_api($entityName, 'getsingle', $checkParams);
      $this->assertEquals($entity, $checkEntity, "changing field $field");
    }
    $baoObj->deleteTestObjects($baoString);
    $baoObj->free();
  }
}

