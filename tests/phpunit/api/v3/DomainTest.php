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
require_once 'api/v3/Domain.php';

/**
 * Test class for Domain API - civicrm_domain_*
 *
 *  @package   CiviCRM
 */
class api_v3_DomainTest extends CiviUnitTestCase {

  /* This test case doesn't require DB reset - apart from
       where cleanDB() is called. */



  public $DBResetRequired = FALSE;

  protected $_apiversion = 3;
  protected $params;
  public $_eNoticeCompliant = TRUE;

  /**
   *  Constructor
   *
   *  Initialize configuration
   */ function __construct() {
    parent::__construct();
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();

    // taken from form code - couldn't find good method to use
    $params['entity_id'] = 1;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $domain = 1;    
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    $location = array();
    $params['address'][1]['location_type_id'] = $defaultLocationType->id;
    $params['phone'][1]['location_type_id'] = $defaultLocationType->id;
    $params['phone'][1]['phone_type_id'] = 1;
    $params['email'][1]['location_type_id'] = $defaultLocationType->id;
    $params['email'][1]['email'] = 'my@email.com';
    $params['phone'][1]['phone'] = '456-456';
    $params['address'][1]['street_address'] = '45 Penny Lane';
    $location = CRM_Core_BAO_Location::create($params, TRUE, 'domain');
    $domUpdate = civicrm_api('domain','create',array('id' => 1, 'loc_block_id' => $location['id'], 'version' => $this->_apiversion));
    $this->_apiversion = 3;
    $this->params = array(
      'name' => 'A-team domain',
      'description' => 'domain of chaos',
      'version' => $this->_apiversion,
      'domain_version' => '4.2',
      'loc_block_id' => $location['id'],
    );
 }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {

  }

  ///////////////// civicrm_domain_get methods

  /**
   * Test civicrm_domain_get. Takes no params.
   * Testing mainly for format.
   */
  public function testGet() {
    

    $params = array('version' => 3);
    $result = civicrm_api('domain', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);

    $this->assertType('array', $result, 'In line' . __LINE__);

    foreach ($result['values'] as $key => $domain) {
      if ($key == 'version') {
        continue;
      }

      $this->assertEquals("info@FIXME.ORG", $domain['from_email'], 'In line ' . __LINE__);
      $this->assertEquals("FIXME", $domain['from_name'], 'In line' . __LINE__);
     
      // checking other important parts of domain information
      // test will fail if backward incompatible changes happen
      $this->assertArrayHasKey('id', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('name', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_email', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_phone', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_address', $domain, 'In line' . __LINE__);
    }
  }

  public function testGetCurrentDomain() {
    $params = array('version' => 3, 'current_domain' => 1);
    $result = civicrm_api('domain', 'get', $params);

    $this->assertType('array', $result, 'In line' . __LINE__);

    foreach ($result['values'] as $key => $domain) {
      if ($key == 'version') {
        continue;
      }

      $this->assertEquals("info@FIXME.ORG", $domain['from_email'], 'In line ' . __LINE__);
      $this->assertEquals("FIXME", $domain['from_name'], 'In line' . __LINE__);

      // checking other important parts of domain information
      // test will fail if backward incompatible changes happen
      $this->assertArrayHasKey('id', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('name', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_email', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_phone', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_address', $domain, 'In line' . __LINE__);
      $this->assertEquals("my@email.com",$domain['domain_email']);
      $this->assertEquals("456-456",$domain['domain_phone']['phone']);
      $this->assertEquals("45 Penny Lane",$domain['domain_address']['street_address']);
    }
  }

  ///////////////// civicrm_domain_create methods
  /*
    * This test checks for a memory leak observed when doing 2 gets on current domain
    */



  public function testGetCurrentDomainTwice() {
    $domain = civicrm_api('domain', 'getvalue', array(
        'version' => 3,
        'current_domain' => 1,
        'return' => 'name',
      ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE) . 'in line ' . __LINE__);
    $domain = civicrm_api('domain', 'getvalue', array(
        'version' => 3,
        'current_domain' => 1,
        'return' => 'name',
      ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE) . 'in line ' . __LINE__);
  }

  /**
   * Test civicrm_domain_create.
   */
  public function testCreate() {
    $result = civicrm_api('domain', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertType('array', $result);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['count'], 1);

    $this->assertNotNull($result['id']);
    $this->assertEquals($result['values'][$result['id']]['name'], $this->params['name']);
  }

  /**
   * Test civicrm_domain_create with empty params.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $params = array();
    $result = civicrm_api('domain', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_domain_create with wrong parameter type.
   */
  public function testCreateWithWrongParams() {
    $params = 1;
    $result = civicrm_api('domain', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }
}

