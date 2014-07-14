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


require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'api/v2/Domain.php';

/**
 * Test class for Domain API - civicrm_domain_*
 *
 *  @package   CiviCRM
 */
class api_v2_DomainTest extends CiviUnitTestCase {

  /* This test case doesn't require DB reset - apart from 
       where cleanDB() is called. */

  public $DBResetRequired = FALSE;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  ///////////////// civicrm_domain_get methods

  /**
   * Test civicrm_domain_get. Takes no params.
   * Testing mainly for format.
   */
  public function testGet() {
    $this->cleanDB();

    $result = civicrm_domain_get();

    $this->assertType('array', $result, 'In line' . __LINE__);

    foreach ($result as $domain) {
      $this->assertEquals('info@FIXME.ORG', $domain['from_email'], 'In line' . __LINE__);
      $this->assertEquals('FIXME', $domain['from_name'], 'In line' . __LINE__);

      // checking other important parts of domain information
      // test will fail if backward incompatible changes happen
      $this->assertArrayHasKey('id', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_name', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_email', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_phone', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_address', $domain, 'In line' . __LINE__);
    }
  }

  ///////////////// civicrm_domain_create methods

  /**
   * Test civicrm_domain_create.
   */
  public function testCreate() {
    $params = array(
      'name' => 'New Domain',
      'description' => 'Description of a new domain',
    );

    $result = &civicrm_domain_create($params);
    $this->assertType('array', $result);
    $this->assertDBState('CRM_Core_DAO_Domain', $result['id'], $params);
  }

  /**
   * Test civicrm_domain_create with empty params.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $params = array();
    $result = &civicrm_domain_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_domain_create with wrong parameter type.
   */
  public function testCreateWithWrongParams() {
    $params = 1;
    $result = &civicrm_domain_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }
}


