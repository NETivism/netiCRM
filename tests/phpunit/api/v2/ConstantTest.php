<?php
// vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the TestConstant class
 *
 *  (PHP 5)
 *
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: ConstantTest.php 40328 2012-05-11 23:06:13Z allen $
 *   @package CiviCRM_APIv2
 *   @subpackage API_Constant
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'api/v2/Constant.php';
require_once 'CRM/Core/I18n.php';
require_once 'CRM/Utils/Cache.php';

/**
 *  Test APIv2 civicrm_activity_* functions
 *
 *  @package CiviCRM_APIv2
 *  @subpackage API_Constant
 */
class api_v2_ConstantTest extends CiviUnitTestCase {

  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    parent::__construct();
  }

  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-option.xml'
      )
    );
  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_option_group'));
  }

  /**
   *  Test civicrm_constant_get( ) for unknown constant
   */
  public function testUnknownConstant() {
    $result = civicrm_constant_get('thisTypeDoesNotExist');
    $this->assertEquals(1, $result['is_error'], "In line " . __LINE__);
  }

  /**
   *  Test civicrm_constant_get( 'activityStatus' )
   */
  public function testActivityStatus() {
    //  Insert a row in civicrm_option_group creating
    //  an activity_status option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert rows in civicrm_option_value defining activity status
    //  values of 'Scheduled', 'Completed', 'Cancelled'
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    $result = civicrm_constant_get('activityStatus');
    $this->assertEquals(3, count($result), "In line " . __LINE__);
    $this->assertContains('Scheduled', $result, "In line " . __LINE__);
    $this->assertContains('Completed', $result, "In line " . __LINE__);
    $this->assertContains('Canceled', $result, "In line " . __LINE__);

    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_constant_get( 'activityType' )
   */
  public function testActivityType() {
    //  Insert 'activity_type' option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/dataset/option_group_activity.xml'
      )
    );

    //  Insert some activity type values
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/option_value_activity.xml'
      )
    );

    $parameters = array(TRUE, FALSE, TRUE);
    $result = civicrm_constant_get('activityType', $parameters);

    $this->assertEquals(2, count($result), "In line " . __LINE__);
    $this->assertContains('Test activity type', $result, "In line " . __LINE__);
    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_constant_get( 'locationType' )
   */
  public function testLocationType() {
    // needed to get rid of cached values from previous tests
    CRM_Core_Pseudoconstant::flush('locationType');

    $dataset = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/location_type_data.xml'
    );

    //  We don't want default set, we want our own, so clean up first
    $tr = new PHPUnit_Extensions_Database_Operation_Truncate();
    $tr->execute($this->_dbconn, $dataset);

    //  Insert default location type values
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn, $dataset);

    $result = civicrm_constant_get('locationType');

    $this->assertEquals(4, count($result), "In line " . __LINE__);
    $this->assertContains('Home', $result, "In line " . __LINE__);
    $this->assertContains('Work', $result, "In line " . __LINE__);
    $this->assertContains('Main', $result, "In line " . __LINE__);
    $this->assertContains('Billing', $result, "In line " . __LINE__);
    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_constant_get( 'locationType' ) with all=true
   */
  public function testLocationTypeAll() {
    // needed to get rid of cached values from previous tests
    CRM_Core_Pseudoconstant::flush('locationType');

    //  Testing on default set provided by civicrm_data.mysql

    $result = civicrm_constant_get('locationType', array('all' => TRUE));

    $this->assertEquals(5, count($result), "In line " . __LINE__);
    $this->assertContains('Home', $result, "In line " . __LINE__);
    $this->assertContains('Work', $result, "In line " . __LINE__);
    $this->assertContains('Main', $result, "In line " . __LINE__);
    $this->assertContains('Other', $result, "In line " . __LINE__);
    $this->assertContains('Billing', $result, "In line " . __LINE__);
    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_constant_get( 'phoneType' )
   */
  public function testPhoneType() {
    //  Insert 'phone_type' option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(dirname(__FILE__) . '/dataset/option_group_phone_type.xml'
      )
    );

    //  Insert some phone type option values
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(dirname(__FILE__) . '/dataset/option_value_phone_type.xml'
      )
    );

    $parameters = array(TRUE, FALSE, TRUE);
    $result = civicrm_constant_get('phoneType', $parameters);

    $this->assertEquals(5, count($result), "In line " . __LINE__);
    $this->assertContains('Phone', $result, "In line " . __LINE__);
    $this->assertContains('Mobile', $result, "In line " . __LINE__);
    $this->assertContains('Fax', $result, "In line " . __LINE__);
    $this->assertContains('Pager', $result, "In line " . __LINE__);
    $this->assertContains('Voicemail', $result, "In line " . __LINE__);

    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_constant_get( 'mailProtocol' )
   */
  public function testmailProtocol() {
    //  Insert 'mail_protocol' option group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(dirname(__FILE__) . '/dataset/option_group_mail_protocol.xml'
      )
    );

    //  Insert some mail protocol option values
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(dirname(__FILE__) . '/dataset/option_value_mail_protocol.xml'
      )
    );

    $parameters = array(TRUE, FALSE, TRUE);
    $result = civicrm_constant_get('mailProtocol', $parameters);

    $this->assertEquals(4, count($result), "In line " . __LINE__);
    $this->assertContains('IMAP', $result, "In line " . __LINE__);
    $this->assertContains('Maildir', $result, "In line " . __LINE__);
    $this->assertContains('POP3', $result, "In line " . __LINE__);
    $this->assertContains('Localdir', $result, "In line " . __LINE__);
    $this->assertTrue(empty($result['is_error']),
      "In line " . __LINE__
    );
  }
}
// class api_v2_ConstantTest

// -- set Emacs parameters --
// Local variables:
// mode: php;
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

