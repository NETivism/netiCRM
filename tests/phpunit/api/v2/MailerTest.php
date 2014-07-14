<?php

/**
 *  File for the TestMailer class
 *
 *  (PHP 5)
 *
 *   @package   CiviCRM
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

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'api/v2/Mailer.php';

/**
 *  Test APIv2 civicrm_mailer_* functions
 *
 *  @package   CiviCRM
 */
class api_v2_MailerTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email; function get_info() {
    return array(
      'name' => 'Mailer',
      'description' => 'Test all Mailer methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
  }

  function tearDown() {
    $this->groupDelete($this->_groupID);
  }

  //---------- civicrm_mailer_event_subscribe methods ---------

  /**
   * Test civicrm_mailer_event_subscribe with wrong params type.
   */
  public function testMailerSubscribeWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_subscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_subscribe with empty params.
   */
  public function testMailerSubscribeEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_subscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_subscribe with wrong params.
   */
  public function testMailerSubscribeWrongParams() {
    $params = array(
      'email' => $this->_email,
      'group_id' => 'Wrong Group ID',
    );
    $result = &civicrm_mailer_event_subscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    if ($result['error_message'] != 'Subscription failed') {
      $this->assertEquals($result['error_message'], 'Invalid Group id', 'In line ' . __LINE__);
    }
    else {
      $this->assertEquals($result['error_message'], 'Subscription failed', 'In line ' . __LINE__);
    }
  }

  /**
   * Test civicrm_mailer_event_subscribe with given contact ID.
   */
  public function testMailerSubscribeGivenContactId() {
    $params = array(
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $this->_email,
      'contact_type' => 'Individual',
    );
    $contactID = $this->individualCreate($params);

    $params = array(
      'email' => $this->_email,
      'group_id' => $this->_groupID,
      'contact_id' => $contactID,
    );
    $result = &civicrm_mailer_event_subscribe($params);
    $this->assertEquals($result['is_error'], 0);
    $this->assertEquals($result['contact_id'], $contactID);

    $this->contactDelete($contactID);
  }

  //----------- civicrm_mailer_event_confirm methods -----------

  /**
   * Test civicrm_mailer_event_confirm with wrong params type.
   */
  public function testMailerConfirmWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_confirm($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_confirm with empty params.
   */
  public function testMailerConfirmEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_confirm($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_confirm with wrong params.
   */
  public function testMailerConfirmWrongParams() {
    $params = array(
      'contact_id' => 'Wrong ID',
      'subscribe_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
    );
    $result = &civicrm_mailer_event_confirm($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Confirmation failed', 'In line ' . __LINE__);
  }


  //------------ civicrm_mailer_event_bounce methods------------

  /**
   * Test civicrm_mailer_event_bounce with wrong params type.
   */
  public function testMailerBounceWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_bounce($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_bounce with empty params.
   */
  public function testMailerBounceEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_bounce($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_bounce with wrong params.
   */
  public function testMailerBounceWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'body' => 'Body...',
    );
    $result = &civicrm_mailer_event_bounce($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }




  //-------- civicrm_mailer_event_unsubscribe methods-----------

  /**
   * Test civicrm_mailer_event_unsubscribe with wrong params type.
   */
  public function testMailerUnsubscribeWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_unsubscribe with empty params.
   */
  public function testMailerUnsubscribeEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_unsubscribe with wrong params.
   */
  public function testMailerUnsubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
    );
    $result = &civicrm_mailer_event_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }



  //--------- civicrm_mailer_event_domain_unsubscribe methods -------

  /**
   * Test civicrm_mailer_event_domain_unsubscribe with wrong params type.
   */
  public function testMailerDomainUnsubscribeWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_domain_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_domain_unsubscribe with empty params.
   */
  public function testMailerDomainUnsubscribeEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_domain_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_domain_unsubscribe with wrong params.
   */
  public function testMailerDomainUnsubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
    );
    $result = &civicrm_mailer_event_domain_unsubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


  //----------- civicrm_mailer_event_resubscribe methods--------

  /**
   * Test civicrm_mailer_event_resubscribe with wrong params type.
   */
  public function testMailerResubscribeWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_resubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_resubscribe with empty params.
   */
  public function testMailerResubscribeEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_resubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_resubscribe with wrong params.
   */
  public function testMailerResubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
    );
    $result = &civicrm_mailer_event_resubscribe($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


  //---------- civicrm_mailer_event_reply methods -----------

  /**
   * Test civicrm_mailer_event_reply with wrong params type.
   */
  public function testMailerReplyWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_reply($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_reply with empty params.
   */
  public function testMailerReplyEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_reply($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_reply with wrong params.
   */
  public function testMailerReplyWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'bodyTxt' => 'Body...',
      'replyTo' => $this->_email,
    );
    $result = &civicrm_mailer_event_reply($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


  //----------- civicrm_mailer_event_forward methods ----------

  /**
   * Test civicrm_mailer_event_forward with wrong params type.
   */
  public function testMailerForwardWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_forward($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_forward with empty params.
   */
  public function testMailerForwardEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_forward($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_forward with wrong params.
   */
  public function testMailerForwardWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'email' => $this->_email,
    );
    $result = &civicrm_mailer_event_forward($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


  //---------- civicrm_mailer_event_click methods------------

  /**
   * Test civicrm_mailer_event_click with wrong params type.
   */
  public function testMailerClickWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_click($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_click with empty params.
   */
  public function testMailerClickEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_click($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }


  //------------ civicrm_mailer_event_open methods -----------

  /**
   * Test civicrm_mailer_event_open with wrong params type.
   */
  public function testMailerOpenWrongParamsType() {
    $params = 'is_string';
    $result = &civicrm_mailer_event_open($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input parameter is not an array', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_mailer_event_open with empty params.
   */
  public function testMailerOpenEmptyParams() {
    $params = array();
    $result = &civicrm_mailer_event_open($params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input Parameters empty', 'In line ' . __LINE__);
  }


  //------------------------ success case ---------------------

  /**
   * Test civicrm_mailer_event_subscribe and civicrm_mailer_event_confirm functions - success expected.
   */
  public function testMailerProcess() {
    $params = array(
      'email' => $this->_email,
      'group_id' => $this->_groupID,
    );
    $result = &civicrm_mailer_event_subscribe($params);
    $this->assertEquals($result['is_error'], 0);

    $params = array(
      'contact_id' => $result['contact_id'],
      'subscribe_id' => $result['subscribe_id'],
      'hash' => $result['hash'],
    );
    $result = &civicrm_mailer_event_confirm($params);
    $this->assertEquals($result['is_error'], 0);

    $this->contactDelete($params['contact_id']);
  }
}

