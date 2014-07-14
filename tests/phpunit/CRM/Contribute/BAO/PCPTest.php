<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 | at info'AT'civicrm'DOT'org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for CRM_Contribute_BAO_PCPTest BAO
 *
 *  @package   CiviCRM
 */
class CRM_Contribute_BAO_PCPTest extends CiviUnitTestCase 
{

    function get_info( ) 
    {
        return array(
                     'name'        => 'PCP BAOs',
                     'description' => 'Test all Contribute_BAO_PCP methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     );
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
    }

    function testAddWithPCPBlockTrue()
    {
        
        $params = $this->pcpBlockParams();
        require_once 'CRM/Contribute/BAO/PCP.php';
        $pcpBlock = CRM_Contribute_BAO_PCP::add($params, true);

        $this->assertType( 'CRM_Contribute_DAO_PCPBlock', $pcpBlock, 'Check for created object' );
        $this->assertEquals( $params['entity_table'], $pcpBlock->entity_table, 'Check for entity table.' );
        $this->assertEquals( $params['entity_id'], $pcpBlock->entity_id, 'Check for entity id.' );
        $this->assertEquals( $params['supporter_profile_id'], $pcpBlock->supporter_profile_id, 'Check for profile id .' );
        $this->assertEquals( $params['is_approval_needed'], $pcpBlock->is_approval_needed, 'Check for approval needed .' );
        $this->assertEquals( $params['is_tellfriend_enabled'], $pcpBlock->is_tellfriend_enabled, 'Check for tell friend on.' );
        $this->assertEquals( $params['tellfriend_limit'], $pcpBlock->tellfriend_limit, 'Check for tell friend limit .' );
        $this->assertEquals( $params['link_text'], $pcpBlock->link_text, 'Check for link text.' );
        $this->assertEquals( $params['is_active'], $pcpBlock->is_active, 'Check for is_active.' );
        // Delete our test object
        require_once 'CRM/Core/DAO.php';
        $delParams = array( 'id' => $pcpBlock->id );
        // FIXME: Currently this delete fails with an FK constraint error: DELETE FROM civicrm_contribution_type  WHERE (  civicrm_contribution_type.id = 5 )
        // CRM_Core_DAO::deleteTestObjects( 'CRM_Contribute_DAO_PCPBlock', $delParams );
        
    }

    function testAddWithPCPBlockFalse()
    {
        $params = $this->pcpParams();

        require_once 'CRM/Contribute/BAO/PCP.php';
        $pcp = CRM_Contribute_BAO_PCP::add($params, false);

        $this->assertType( 'CRM_Contribute_DAO_PCP', $pcp, 'Check for created object' );
        $this->assertEquals( $params['contact_id'], $pcp->contact_id, 'Check for entity table.' );
        $this->assertEquals( $params['status_id'], $pcp->status_id, 'Check for status.' );
        $this->assertEquals( $params['title'], $pcp->title, 'Check for title.' );
        $this->assertEquals( $params['intro_text'], $pcp->intro_text, 'Check for intro_text.' );
        $this->assertEquals( $params['page_text'], $pcp->page_text, 'Check for page_text.' );
        $this->assertEquals( $params['donate_link_text'], $pcp->donate_link_text, 'Check for donate_link_text.' );
        $this->assertEquals( $params['contribution_page_id'], $pcp->contribution_page_id, 'Check for contribution_page_id.' );
        $this->assertEquals( $params['is_thermometer'], $pcp->is_thermometer, 'Check for is_thermometer.' );
        $this->assertEquals( $params['is_honor_roll'], $pcp->is_honor_roll, 'Check for is_honor_roll.' );
        $this->assertEquals( $params['goal_amount'], $pcp->goal_amount, 'Check for goal_amount.' );
        $this->assertEquals( $params['referer'], $pcp->referer, 'Check for referer.' );
        $this->assertEquals( $params['is_active'], $pcp->is_active, 'Check for is_active.' );
        
        // Delete our test object
        require_once 'CRM/Core/DAO.php';
        $delParams = array( 'id' => $pcp->id );
        // FIXME: Currently this delete fails with an FK constraint error: DELETE FROM civicrm_contribution_type  WHERE (  civicrm_contribution_type.id = 5 )
        // CRM_Core_DAO::deleteTestObjects( 'CRM_Contribute_DAO_PCP', $delParams );
        
    }

    function testAddWithPCPBlockFalseNoStatus()
    {
        $params = $this->pcpParams();
        unset($params['status_id']);

        require_once 'CRM/Contribute/BAO/PCP.php';
        $pcp = CRM_Contribute_BAO_PCP::add($params, false);

        $this->assertType( 'CRM_Contribute_DAO_PCP', $pcp, 'Check for created object' );
        $this->assertEquals( $params['contact_id'], $pcp->contact_id, 'Check for entity table.' );
        $this->assertEquals( 0, $pcp->status_id, 'Check for zero status when no status_id passed.' );
        $this->assertEquals( $params['title'], $pcp->title, 'Check for title.' );
        $this->assertEquals( $params['intro_text'], $pcp->intro_text, 'Check for intro_text.' );
        $this->assertEquals( $params['page_text'], $pcp->page_text, 'Check for page_text.' );
        $this->assertEquals( $params['donate_link_text'], $pcp->donate_link_text, 'Check for donate_link_text.' );
        $this->assertEquals( $params['contribution_page_id'], $pcp->contribution_page_id, 'Check for contribution_page_id.' );
        $this->assertEquals( $params['is_thermometer'], $pcp->is_thermometer, 'Check for is_thermometer.' );
        $this->assertEquals( $params['is_honor_roll'], $pcp->is_honor_roll, 'Check for is_honor_roll.' );
        $this->assertEquals( $params['goal_amount'], $pcp->goal_amount, 'Check for goal_amount.' );
        $this->assertEquals( $params['referer'], $pcp->referer, 'Check for referer.' );
        $this->assertEquals( $params['is_active'], $pcp->is_active, 'Check for is_active.' );

        // Delete our test object
        require_once 'CRM/Core/DAO.php';
        $delParams = array( 'id' => $pcp->id );
        // FIXME: Currently this delete fails with an FK constraint error: DELETE FROM civicrm_contribution_type  WHERE (  civicrm_contribution_type.id = 5 )
        // CRM_Core_DAO::deleteTestObjects( 'CRM_Contribute_DAO_PCP', $delParams );
    }

    function testDeletePCP()
    {
        require_once 'CRM/Core/DAO.php';
        require_once 'CRM/Contribute/BAO/PCP.php';

        $pcp = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_PCP');
        $pcpId = $pcp->id;
        $del = CRM_Contribute_BAO_PCP::delete( $pcpId);
        $this->assertDBRowNotExist( 'CRM_Contribute_DAO_PCP', $pcpId,
                                    'Database check PCP deleted successfully.' );
        
    }

    /**
     * function to build params
     *
     */
    private function pcpBlockParams( ) 
    {
        require_once 'CRM/Core/DAO.php';
        $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
        $contribPageId = $contribPage->id;
        $supporterProfile = CRM_Core_DAO::createTestObject('CRM_Core_DAO_UFGroup');
        $supporterProfileId = $supporterProfile->id;

        $params = array(
            'entity_table' => 'civicrm_contribution_page',
            'entity_id' => $contribPageId,
            'supporter_profile_id' => $supporterProfileId,
            'is_approval_needed' => 1,
            'is_tellfriend_enabled' => 1,
            'tellfriend_limit' => 1,
            'link_text' => 'Create your own PCP',
            'is_active' => 1
            );
        
        return $params;
    }

    /**
     * function to build params
     *
     */
    private function pcpParams( ) 
    {
        require_once 'CRM/Core/DAO.php';
        $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');
        $contactId = $contact->id;
        $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
        $contribPageId = $contribPage->id;

        $params = array(
            'contact_id' => $contactId,
            'status_id' => '1',
            'title' => 'My PCP',
            'intro_text' => 'Hey you, contribute now!', 
            'page_text' => 'You better give more.', 
            'donate_link_text' => 'Donate Now',
            'contribution_page_id' => $contribPageId,
            'is_thermometer' => 1,
            'is_honor_roll' => 1,
            'goal_amount' => 10000.00,
            'referer' => 'referrer value',
            'is_active' => 1,
            );
        
        return $params;
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }



}
