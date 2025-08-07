<?php
/**
 * Get Options Unit Test
 *
 * @docmaker_intro_start
 * @api_title Get Options
 * This is a API document about getoptions action.
 * @docmaker_intro_end
 */


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_GetOptionsTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;

  /**
   * @before
   */
  function setUpTest() {
    parent::setUp();

    global $tsLocale;
    $tsLocale = 'zh_TW';
    $config =& CRM_Core_Config::singleton();
    $config->countryLimit = [1208, 1228];
    $config->provinceLimit = [1208]; // Taiwan
    $config->defaultContactCountry = 1208; // Taiwan
    $config->defaultCurrency = 'TWD';
    $config->lcMessages = 'zh_TW';
    $this->_params = [
      'version' => $this->_apiversion,
      'sequential' => true,
    ];
  }

  /**
   * @after
   */
  function tearDownTest() {
  }

  /**
   * @docmaker_start
   *
   * @api_entity <field_name>
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=<entity>&action=getoptions&json={"field":"<field_name>"}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testExample() {
    $params = $this->_params += [
      'field' => 'contact_type',
    ];
    $result = civicrm_api('Contact', 'getoptions', $params);
    foreach($result['values'] as $k => &$v) {
      $a = $k+1;
      $v['label'] = 'example label '.$a;
      $v['value'] = $a+1000;
    }
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity contact_type
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testContactType() {
    $params = $this->_params += [
      'field' => 'contact_type',
    ];
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity contact_sub_type
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testContactSubType() {
    // create contact sub type
    CRM_Core_DAO::executeQuery("INSERT IGNORE INTO `civicrm_contact_type` (`name`, `label`, `parent_id`, `is_active`, `is_reserved`) VALUES ('NPO', 'NPO', 3, 1, NULL)");
    $params = $this->_params += [
      'field' => 'contact_sub_type',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity prefix_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testPrefixId() {
    $params = $this->_params += [
      'field' => 'prefix_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity suffix_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  function testSuffixId() {
    $params = $this->_params += [
      'field' => 'suffix_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity gender_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testGenderId() {
    $params = $this->_params += [
      'field' => 'gender_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity location_type_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testLocationTypeId() {
    $params = $this->_params += [
      'field' => 'location_type_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity worldregion_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testWorldregionId() {
    $params = $this->_params += [
      'field' => 'worldregion_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }
  /**
   * @docmaker_start
   *
   * @api_entity country_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testCountryId() {
    $params = $this->_params += [
      'field' => 'country_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity state_province_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testStateProvinceId() {
    $config = CRM_Core_Config::singleton();
    $params = [
      'version' => $this->_apiversion,
      'field' => 'state_province_id',
    ];
    $params = $this->_params += [
      'field' => 'state_province_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }


  /**
   * @docmaker_start
   *
   * @api_entity phone_type_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testPhoneTypeId() {
    $params = $this->_params += [
      'field' => 'phone_type_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity provider_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contact&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contact&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testProviderId() {
    $params = $this->_params += [
      'field' => 'provider_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contact', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity contribution_type_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testContributionTypeId() {
    $params = $this->_params += [
      'field' => 'contribution_type_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contribution', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity contribution_page_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testContributionPageId() {
    $params = $this->_params += [
      'field' => 'contribution_page_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contribution', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity contribution_status_id
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testContributionStatusId() {
    $params = $this->_params += [
      'field' => 'contribution_status_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contribution', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity currency
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testCurrency() {
    $params = $this->_params += [
      'field' => 'currency',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contribution', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }

  /**
   * @docmaker_start
   *
   * @api_entity payment_instrument_id 
   * @api_action options
   * @http_method GET
   * @request_url <entrypoint>?entity=contribution&action=getoptions&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=contribution&action=getoptions&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   */
  public function testPaymentInstrumentId() {
    $params = $this->_params += [
      'field' => 'payment_instrument_id',
    ];
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $result = civicrm_api('Contribution', 'getoptions', $params);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertNotEmpty($result['count'], "In line " . __LINE__);
  }
}
