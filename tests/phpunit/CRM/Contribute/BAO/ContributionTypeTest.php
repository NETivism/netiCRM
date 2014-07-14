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
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Contribute/BAO/ContributionType.php';

class CRM_Contribute_BAO_ContributionTypeTest extends CiviUnitTestCase 
{
    
    function get_info( ) 
    {
        return array(
                     'name'        => 'ContributionType BAOs',
                     'description' => 'Test all Contribute_BAO_Contribution methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     );
    }
    
    function setUp( ) 
    {
        parent::setUp();
    }
    
 
    /**
     * check method add()
     */
    function testAdd( )
    {
        $params = array( 'name'          => 'Donations',
                         'is_deductible' => 0,
                         'is_active'     => 1
                         );
        $ids = array();
        $contributionType = CRM_Contribute_BAO_ContributionType::add( $params, $ids );

        $result = $this->assertDBNotNull( 'CRM_Contribute_BAO_ContributionType', $contributionType->id ,
                                          'name', 'id',
                                          'Database check on updated contribution type record.' );
        
        $this->assertEquals( $result, 'Donations', 'Verify contribution type name.');
    }

    /**
     * check method retrive()
     */
    function testRetrieve( ) 
    {
        $params = array( 'name'          => 'Donations',
                         'is_deductible' => 0,
                         'is_active'     => 1
                         );
        $ids = array();
        $contributionType = CRM_Contribute_BAO_ContributionType::add( $params, $ids );

        $defaults = array();
        $result = CRM_Contribute_BAO_ContributionType::retrieve( $params, $defaults );

        $this->assertEquals( $result->name, 'Donations', 'Verify contribution type name.');
    }

    /**
     * check method setIsActive()
     */
    function testSetIsActive(  ) 
    {
        $params = array( 'name'          => 'testDonations',
                         'is_deductible' => 0,
                         'is_active'     => 1
                         );
        $ids = array();
        $contributionType = CRM_Contribute_BAO_ContributionType::add( $params, $ids );
        $result = CRM_Contribute_BAO_ContributionType::setIsActive( $contributionType->id, 0 );
        $this->assertEquals( $result, true , 'Verify contribution type record updation for is_active.');
        
        $isActive = $this->assertDBNotNull( 'CRM_Contribute_BAO_ContributionType', $contributionType->id ,
                                            'is_active', 'id',
                                            'Database check on updated for contribution type is_active.' );
        $this->assertEquals( $isActive, 0, 'Verify contribution types is_active.');
    }

    /**
     * check method del()
     */
    function testdel(  ) 
    {
        $params = array( 'name'          => 'checkDonations',
                         'is_deductible' => 0,
                         'is_active'     => 1
                         );
        $ids = array();
        $contributionType = CRM_Contribute_BAO_ContributionType::add( $params, $ids );
        
        CRM_Contribute_BAO_ContributionType::del( $contributionType->id );
        $params = array('id' => $contributionType->id );
        $result = CRM_Contribute_BAO_ContributionType::retrieve( $params, $defaults );
        $this->assertEquals( empty($result), true, 'Verify contribution types record deletion.');
        
    }

}
?>