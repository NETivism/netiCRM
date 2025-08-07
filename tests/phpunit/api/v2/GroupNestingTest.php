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
require_once 'api/v2/GroupNesting.php';

/**
 * Test class for GroupNesting API - civicrm_group_nesting_*
 *
 *  @package   CiviCRM
 */
class api_v2_GroupNestingTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/group_admins.xml'
      )
    );

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/group_subscribers.xml'
      )
    );

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/group_nesting.xml'
      )
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {
    // truncate a few tables
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_group',
      'civicrm_group_nesting',
      'civicrm_group_contact',
      'civicrm_subscription_history',
    ];

    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_group_nesting_get methods

  /**
   * Test civicrm_group_nesting_get.
   */
  public function testGet() {
    $params = [
      'parent_group_id' => 1,
      'child_group_id' => 2,
    ];

    $result = &civicrm_group_nesting_get($params);

    // expected data loaded in setUp
    $expected = [
      1 => ['id' => 1,
        'child_group_id' => 2,
        'parent_group_id' => 1,
      ]];
    $expected['is_error'] = 0;
    $this->assertEquals($expected, $result);
  }

  /**
   * Test civicrm_group_nesting_get with just one
   * param (child_group_id).
   */
  public function testGetWithChildGroupId() {
    $params = ['child_group_id' => 4];

    $result = &civicrm_group_nesting_get($params);

    // expected data loaded in setUp
    $expected = [
      3 => ['id' => 3,
        'child_group_id' => 4,
        'parent_group_id' => 1,
      ],
      4 => [
        'id' => 4,
        'child_group_id' => 4,
        'parent_group_id' => 2,
      ],
    ];
    $expected['is_error'] = 0;
    $this->assertEquals($expected, $result);
  }

  /**
   * Test civicrm_group_nesting_get with just one
   * param (parent_group_id).
   */
  public function testGetWithParentGroupId() {
    $params = ['parent_group_id' => 1];

    $result = &civicrm_group_nesting_get($params);

    // expected data loaded in setUp
    $expected = [
      1 => ['id' => 1,
        'child_group_id' => 2,
        'parent_group_id' => 1,
      ],
      2 => [
        'id' => 2,
        'child_group_id' => 3,
        'parent_group_id' => 1,
      ],
      3 => [
        'id' => 3,
        'child_group_id' => 4,
        'parent_group_id' => 1,
      ],
    ];
    $expected['is_error'] = 0;
    $this->assertEquals($expected, $result);
  }

  /**
   * Test civicrm_group_nesting_get for no records results.
   * Error expected.
   */
  public function testGetEmptyResults() {
    // no such record in the db
    $params = [
      'parent_group_id' => 1,
      'child_group_id' => 700,
    ];

    $result = &civicrm_group_nesting_get($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_group_nesting_get with empty params.
   * Error expected.
   */
  public function testGetWithEmptyParams() {
    $params = [];

    $result = &civicrm_group_nesting_get($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_group_nesting_get with wrong parameters type.
   * Error expected.
   */
  public function testGetWithWrongParamsType() {
    $params = 'a string';

    $result = &civicrm_group_nesting_get($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  ///////////////// civicrm_group_nesting_create methods

  /**
   * Test civicrm_group_nesting_create.
   */
  public function testCreate() {
    // groups id=1 and id=2 loaded in setUp
    $params = [
      'parent_group_id' => 1,
      'child_group_id' => 3,
    ];

    $result = civicrm_group_nesting_create($params);
    $this->assertEquals($result['is_error'], 0);

    // we have 4 group nesting records in the example
    // data, expecting next number to be the id for newly created
    $id = 5;
    $this->assertDBState('CRM_Contact_DAO_GroupNesting', $id, $params);
  }

  /**
   * Test civicrm_group_nesting_create with empty parameter array.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $params = [];

    $result = &civicrm_group_nesting_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_group_nesting_create with wrong parameter type.
   * Error expected.
   */
  public function testCreateWithWrongParamsType() {
    $params = 'a string';

    $result = &civicrm_group_nesting_create($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  ///////////////// civicrm_group_nesting_remove methods

  /**
   * Test civicrm_group_nesting_remove.
   */
  public function testRemove() {
    // groups id=1 and id=2 loaded in setUp
    $params = [
      'parent_group_id' => 1,
      'child_group_id' => 2,
    ];

    $result = &civicrm_group_nesting_remove($params);
    $this->assertEquals($result['is_error'], 0);

    // group nesting record with above combo of params
    // has id = 1, asserting it's gone
    $id = 1;
    $this->assertDBState('CRM_Contact_DAO_GroupNesting', $id, $params, TRUE);
  }

  /**
   * Test civicrm_group_nesting_remove with empty parameter array.
   * Error expected.
   */
  public function testRemoveWithEmptyParams() {
    $params = [];

    $result = &civicrm_group_nesting_remove($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_group_nesting_remove with wrong parameter type.
   * Error expected.
   */
  public function testRemoveWithWrongParamsType() {
    $params = 'a string';

    $result = &civicrm_group_nesting_remove($params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }
}


