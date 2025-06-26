<?php
// $Id$

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_PriceFieldTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $priceSetID = 0;
  protected $_entity = 'price_field';
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = TRUE;

  public function setUp() {
    parent::setUp();
    // put stuff here that should happen before all tests in this unit
    $priceSetparams = [
      'version' => 3,
      #     [domain_id] =>
      'name' => 'default_goat_priceset',
      'title' => 'Goat accomodation',
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'contribution_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];

    $price_set = civicrm_api('price_set', 'create',$priceSetparams);
    $this->priceSetID = $price_set['id'];

    $this->_params = [
      'version' => $this->_apiversion,
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
  }

  function tearDown() {
    $tablesToTruncate = [
        'civicrm_contact',
        'civicrm_contribution',
    ];
    $this->quickCleanup($tablesToTruncate);

    $delete = civicrm_api('PriceSet','delete', [
      'version' => 3,
      'id' => $this->priceSetID,
    ]);

    $this->assertAPISuccess($delete);
  }

  public function testCreatePriceField() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->id = $result['id'];
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  public function testGetBasicPriceField() {
    $createResult = civicrm_api($this->_entity, 'create', $this->_params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = [
      'version' => $this->_apiversion,
      'name' => 'contribution_amount',
    ];
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
    civicrm_api('price_field','delete', ['version' => 3, 'id' => $createResult['id']]);
  }

  public function testDeletePriceField() {
    $startCount = civicrm_api($this->_entity, 'getcount', [
      'version' => $this->_apiversion,
      ]);
    $createResult = civicrm_api($this->_entity, 'create', $this->_params);
    $deleteParams = ['version' => $this->_apiversion, 'id' => $createResult['id']];
    $deleteResult = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $deleteResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult, 'In line ' . __LINE__);
    $endCount = civicrm_api($this->_entity, 'getcount', [
      'version' => $this->_apiversion,
      ]);
    $this->assertEquals($startCount, $endCount, 'In line ' . __LINE__);
  }

  public function testGetFieldsPriceField() {
    $result = civicrm_api($this->_entity, 'getfields', ['version' => $this->_apiversion, 'action' => 'create']);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values']['options_per_line']['type']);
  }

}

