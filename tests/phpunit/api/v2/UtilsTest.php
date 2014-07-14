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
require_once 'CRM/Core/Permission.php';
require_once 'CRM/Core/Permission/UnitTests.php';
require_once 'api/v2/utils.php';

/**
 * Test class for API utils
 *
 *  @package   CiviCRM
 */
class api_v2_UtilsTest extends CiviUnitTestCase {

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  function testAddFormattedParam() {
    $values = array('contact_type' => 'Individual');
    $params = array('something' => 1);
    $result = _civicrm_add_formatted_param($values, $params);
    $this->assertTrue($result);
  }

  function testCheckPermissionReturn() {
    $check = array('check_permissions' => TRUE);

    CRM_Core_Permission_UnitTests::$permissions = array();
    $this->assertFalse(civicrm_api_check_permission('civicrm_contact_create', $check), 'empty permissions should not be enough');
    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM');
    $this->assertFalse(civicrm_api_check_permission('civicrm_contact_create', $check), 'lacking permissions should not be enough');
    CRM_Core_Permission_UnitTests::$permissions = array('add contacts');
    $this->assertFalse(civicrm_api_check_permission('civicrm_contact_create', $check), 'lacking permissions should not be enough');

    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM', 'add contacts');
    $this->assertTrue(civicrm_api_check_permission('civicrm_contact_create', $check), 'exact permissions should be enough');

    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $this->assertTrue(civicrm_api_check_permission('civicrm_contact_create', $check), 'overfluous permissions should be enough');
  }

  function testCheckPermissionThrow() {
    $check = array('check_permissions' => TRUE);

    try {
      CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM');
      civicrm_api_check_permission('civicrm_contact_create', $check, TRUE);
    }
    catch(Exception$e) {
      $message = $e->getMessage();
    }
    $this->assertEquals($message, 'API permission check failed for civicrm_contact_create call; missing permission: add contacts.', 'lacking permissions should throw an exception');

    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $this->assertTrue(civicrm_api_check_permission('civicrm_contact_create', $check, TRUE), 'overfluous permissions should return true');
  }

  function testCheckPermissionSkip() {
    CRM_Core_Permission_UnitTests::$permissions = array('access CiviCRM');
    $this->assertFalse(civicrm_api_check_permission('civicrm_contact_create', array('check_permissions' => TRUE)), 'lacking permissions should not be enough');
    $this->assertTrue(civicrm_api_check_permission('civicrm_contact_create', array('check_permissions' => FALSE)), 'permission check should be skippable');
  }
}

