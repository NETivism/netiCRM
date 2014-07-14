<?php


require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_OptionValueTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  public function testGetOptionValueByID() {
    $result = civicrm_api('option_value', 'get', array('id' => 1, 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  public function testGetOptionValueByValue() {
    $result = civicrm_api('option_value', 'get', array('option_group_id' => 1, 'value' => '1', 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  /**
   *  Test limit param
   */
  function testGetOptionValueLimit() {
    $params = array(
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('option_value', 'getcount', $params);
    $this->assertGreaterThan(1, $result, "Check more than one exists In line " . __LINE__);
    $params['options']['limit'] = 1;
    $result = civicrm_api('option_value', 'getcount', $params);
    $this->assertEquals(1, $result, "Check only 1 retrieved " . __LINE__);
  }

  /**
   *  Test offset param
   */
  function testGetOptionValueOffSet() {

    $result = civicrm_api('option_value', 'getcount', array(
      'option_group_id' => 1,
        'value' => '1',
        'version' => $this->_apiversion,
      ));
    $result2 = civicrm_api('option_value', 'getcount', array(
      'option_group_id' => 1,
        'value' => '1',
        'version' => $this->_apiversion,
        'options' => array('offset' => 1),
      ));
    $this->assertGreaterThan($result2, $result);
  }

  /**
   *  Test offset param
   */
  function testGetSingleValueOptionValueSort() {
    $description = "demonstrates use of Sort param (available in many api functions). Also, getsingle";
    $subfile     = 'SortOption';
    $result      = civicrm_api('option_value', 'getsingle', array(
      'option_group_id' => 1,
        'version' => $this->_apiversion,
        'options' => array(
          'sort' => 'label ASC',
          'limit' => 1,
        ),
      ));
    $params = array(
      'option_group_id' => 1,
      'version' => $this->_apiversion,
      'options' => array(
        'sort' => 'label DESC',
        'limit' => 1,
      ),
    );
    $result2 = civicrm_api('option_value', 'getsingle', $params);
    $this->documentMe($params, $result2, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertGreaterThan($result['label'], $result2['label']);
  }

  /**
   * Try to emulate a pagination: fetch the first page of 10 options, then fetch the second page with an offset of 9 (instead of 10) and check the start of the second page is the end of the 1st one.
   */
  function testGetValueOptionPagination() {
    $pageSize = 10;
    $page1 = civicrm_api('option_value', 'get', array('options' => array('limit' => $pageSize),
        'version' => $this->_apiversion,
      ));
    $page2 = civicrm_api('option_value', 'get', array(
      'options' => array('limit' => $pageSize,
          // if you use it for pagination, option.offset=pageSize*pageNumber
          'offset' => $pageSize - 1,
        ),
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($pageSize, $page1['count'], "Check only 10 retrieved in the 1st page " . __LINE__);
    $this->assertEquals($pageSize, $page2['count'], "Check only 10 retrieved in the 2nd page " . __LINE__);

    $last = array_pop($page1['values']);
    $first = array_shift($page2['values']);

    $this->assertEquals($first, $last, "the first item of the second page should be the last of the 1st page" . __LINE__);
  }

  public function testGetOptionGroup() {
    $params = array('option_group_id' => 1, 'version' => $this->_apiversion);
    $result = civicrm_api('option_value', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertGreaterThan(1, $result['count'], 'In line ' . __LINE__);
  }
  /*
     * test that using option_group_name returns more than 1 & less than all
     */



  public function testGetOptionGroupByName() {
    $activityTypesParams = array('option_group_name' => 'activity_type', 'version' => $this->_apiversion, 'option.limit' => 100);
    $params              = array('version' => $this->_apiversion, 'option.limit' => 100);
    $activityTypes       = civicrm_api('option_value', 'get', $activityTypesParams);
    $result              = civicrm_api('option_value', 'get', $params);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertGreaterThan(1, $activityTypes['count'], 'In line ' . __LINE__);
    $this->assertGreaterThan($activityTypes['count'], $result['count'], 'In line ' . __LINE__);
  }
  public function testGetOptionDoesNotExist() {
    $result = civicrm_api('option_value', 'get', array('label' => 'FSIGUBSFGOMUUBSFGMOOUUBSFGMOOBUFSGMOOIIB', 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }
}

