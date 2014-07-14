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


require_once 'api/v2/MembershipContributionLink.php';
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'api/v2/MembershipType.php';
require_once 'api/v2/MembershipStatus.php';
require_once 'api/v2/Membership.php';
require_once 'CRM/Member/BAO/MembershipType.php';
require_once 'CRM/Member/BAO/Membership.php';
class api_v2_MembershipContributionLinkTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_membershipTypeID;
  protected $_membershipStatusID; function get_info() {
    return array(
      'name' => 'Membership Contribution Link',
      'description' => 'Test all Membership API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_contactID = $this->organizationCreate();
    $this->_contributionTypeID = $this->contributionTypeCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate($this->_contactID);
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
  }

  function tearDown() {
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->contributionTypeDelete();
    $this->contactDelete($this->_contactID);
  }

  ///////////////// civicrm_membershipcontributionlink_create methods

  /**
   * Test civicrm_membershipcontributionlink_create with wrong params type.
   */
  public function testCreateWrongParamsType() {
    $params = 'eeee';
    $CreateWrongParamsType = civicrm_membershipcontributionlink_create($params);
    $this->assertEquals($CreateWrongParamsType['error_message'], 'Input parameters is not an array');
  }

  /**
   * Test civicrm_membershipcontributionlink_create with empty params.
   */
  public function testCreateEmptyParams() {
    $params = array();
    $CreateEmptyParams = civicrm_membershipcontributionlink_create($params);
    $this->assertEquals($CreateEmptyParams['error_message'], 'No input parameters present');
  }

  /**
   * Test civicrm_membershipcontributionlink_create - success expected.
   */
  public function testCreate() {
    $contactId = Contact::createIndividual();
    $params = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'contribution_type_id' => $this->_contributionTypeID,
      'contribution_status_id' => 1,
      'contribution_page_id' => NULL,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'total_amount' => 200.00,
      'trxn_id' => '22ereerwww322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da70',
      'thankyou_date' => '20080522',
    );

    require_once 'CRM/Contribute/BAO/Contribution.php';
    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);

    $params = array(
      'contribution_id' => $contribution->id,
      'membership_id' => $membership->id,
    );
    $Create = civicrm_membershipcontributionlink_create($params);
    $this->assertEquals($Create['membership_id'], $membership->id, 'Check Membership Id');
    $this->assertEquals($Create['contribution_id'], $contribution->id, 'Check Contribution Id');

    $this->membershipDelete($membership->id);
    $this->contactDelete($contactId);
  }

  ///////////////// civicrm_membershipcontributionlink_get methods

  /**
   * Test civicrm_membershipcontributionlink_get with wrong params type.
   */
  public function testGetWrongParamsType() {
    $params = 'eeee';
    $GetWrongParamsType = civicrm_membershipcontributionlink_get($params);
    $this->assertEquals($GetWrongParamsType['error_message'], 'Input parameters is not an array');
  }

  /**
   * Test civicrm_membershipcontributionlink_get with empty params.
   */
  public function testGetEmptyParams() {
    $params = array();
    $GetEmptyParams = civicrm_membershipcontributionlink_get($params);
    $this->assertEquals($GetEmptyParams['error_message'], 'No input parameters present');
  }

  /**
   * Test civicrm_membershipcontributionlink_get - success expected.
   */
  public function testGet() {
    $contactId = Contact::createIndividual();
    $params = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'contribution_type_id' => $this->_contributionTypeID,
      'contribution_status_id' => 1,
      'contribution_page_id' => NULL,
      'payment_instrument_id' => 1,
      'id' => NULL,
      'total_amount' => 200.00,
    );

    require_once 'CRM/Contribute/BAO/Contribution.php';
    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);

    $params = array(
      'contribution_id' => $contribution->id,
      'membership_id' => $membership->id,
    );
    $Create = civicrm_membershipcontributionlink_create($params);

    $GetParams = civicrm_membershipcontributionlink_get($params);

    $this->assertEquals($GetParams[$Create['id']]['membership_id'], $membership->id, 'Check Membership Id');
    $this->assertEquals($GetParams[$Create['id']]['contribution_id'], $contribution->id, 'Check Contribution Id');

    $this->membershipDelete($membership->id);
    $this->contactDelete($contactId);
  }
}

