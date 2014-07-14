<?php  // vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *  
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: MembershipTest.php 30743 2010-11-15 09:45:11Z shot $
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

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Member/Form/Membership.php';

/**
 *  Test APIv2 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class CRM_Member_Form_MembershipTest extends CiviUnitTestCase
{
    /**
     *  Test setup for every test
     *
     *  Connect to the database, truncate the tables that will be used
     *  and redirect stdin to a temporary file
     */
    public function setUp()
    {
        //  Connect to the database
        parent::setUp();

        //  Truncate the tables
        $op = new PHPUnit_Extensions_Database_Operation_Truncate( );
        $op->execute( $this->_dbconn,
                      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
                             dirname(__FILE__) . '/../../../CiviTest/truncate.xml') );
        //  Insert test data
        $op = new PHPUnit_Extensions_Database_Operation_Insert( );
        $op->execute( $this->_dbconn,
                      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
                             dirname(__FILE__)
                             . '/dataset/data.xml') );
    }

    /**
     *  Test CRM_Member_Form_Membership::buildQuickForm()
     */
    //function testCRMMemberFormMembershipBuildQuickForm()
    //{
    //    throw new PHPUnit_Framework_IncompleteTestError( "not implemented" );
    //}

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an empty contact_select_id value
     */
    function testFormRuleEmptyContact()
    {
        $params = array( 'contact_select_id' => 0,
                         'membership_type_id' => array( ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'membership_type_id', $rc ),
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'join_date', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an start date before the join date and a rolling
     *  membership type
     */
    function testFormRuleRollingEarlyStart()
    {
        $unixNow = time();
        $ymdNow = date( 'm/d/Y', $unixNow );
        $unixYesterday = $unixNow - ( 24 * 60 * 60 );
        $ymdYesterday= date( 'm/d/Y', $unixYesterday );
        $params = array( 'join_date'          => $ymdNow,
                         'start_date'         => $ymdYesterday,
                         'end_date'           => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = call_user_func(array('CRM_Member_Form_Membership','formRule'),
                              $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'start_date', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an end date before the start date and a rolling
     *  membership type
     */
    function testFormRuleRollingEarlyEnd()
    {
        $unixNow = time();
        $ymdNow = date( 'm/d/Y', $unixNow );
        $unixYesterday = $unixNow - ( 24 * 60 * 60 );
        $ymdYesterday= date( 'm/d/Y', $unixYesterday );
        $params = array( 'join_date'  => $ymdNow,
                         'start_date' => $ymdNow,
                         'end_date'   => $ymdYesterday,
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'end_date', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an end date but no start date and a rolling
     *  membership type
     */
    function testFormRuleRollingEndNoStart()
    {
        $unixNow = time();
        $ymdNow = date( 'm/d/Y', $unixNow );
        $unixYearFromNow = $unixNow + ( 365 * 24 * 60 * 60 );
        $ymdYearFromNow = date( 'm/d/Y', $unixYearFromNow );
        $params = array( 'join_date'          => $ymdNow,
                         'start_date'         => '',
                         'end_date'           => $ymdYearFromNow,
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'start_date', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an end date and a lifetime membership type
     */
    function testFormRuleRollingLifetimeEnd()
    {
        $unixNow = time();
        $unixYearFromNow = $unixNow + ( 365 * 24 * 60 * 60 );
        $params = array( 'join_date'          => date( 'm/d/Y', $unixNow ),
                         'start_date'         => date( 'm/d/Y', $unixNow ),
                         'end_date'           => date( 'm/d/Y',
                                                       $unixYearFromNow ),
                         'membership_type_id' => array( '23', '13' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'end_date', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a parameter
     *  that has an override and no status 
     */
    function testFormRuleOverrideNoStatus()
    {
        $unixNow = time();
        $unixYearFromNow = $unixNow + ( 365 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unixNow ),
                         'membership_type_id' => array( '23', '13' ),
                         'is_override' => true );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( 'status_id', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of one month from now and a rolling membership type
     */
    function testFormRuleRollingJoin1MonthFromNow()
    {
        $unixNow = time();
        $unix1MFmNow = $unixNow + ( 31 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix1MFmNow ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found no valid membership status
        $this->assertType( 'array', $rc,
                           'In line ' . __LINE__  );
        $this->assertTrue( array_key_exists( '_qf_default', $rc ),
                           'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of today and a rolling membership type
     */
    function testFormRuleRollingJoinToday()
    {
        $unixNow = time();
        $params = array( 'join_date'  => date( 'm/d/Y', $unixNow ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found New membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of one month ago and a rolling membership type
     */
    function testFormRuleRollingJoin1MonthAgo()
    {
        $unixNow = time();
        $unix1MAgo = $unixNow - ( 31 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix1MAgo ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found New membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of six months ago and a rolling membership type
     */
    function testFormRuleRollingJoin6MonthsAgo()
    {
        $unixNow = time();
        $unix6MAgo = $unixNow - ( 180 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix6MAgo ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found Current membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of one year+ ago and a rolling membership type
     */
    function testFormRuleRollingJoin1YearAgo()
    {
        $unixNow = time();
        $unix1YAgo = $unixNow - ( 370 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix1YAgo ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found Grace membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of two years ago and a rolling membership type
     */
    function testFormRuleRollingJoin2YearsAgo()
    {
        $unixNow = time();
        $unix2YAgo = $unixNow - ( 2 * 365 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix2YAgo ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '3' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found Expired membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::formRule() with a join date
     *  of six months ago and a fixed membership type
     */
    function testFormRuleFixedJoin6MonthsAgo()
    {
        $unixNow = time();
        $unix6MAgo = $unixNow - ( 180 * 24 * 60 * 60 );
        $params = array( 'join_date'  => date( 'm/d/Y', $unix6MAgo ),
                         'start_date' => '',
                         'end_date'   => '',
                         'membership_type_id' => array( '23', '7' ) );
        $files  = array();
        $obj    = new CRM_Member_Form_Membership;
        $rc = $obj->formRule( $params, $files, $obj );

        //  Should have found Current membership status
        $this->assertTrue( $rc, 'In line ' . __LINE__  );
    }

    /**
     *  Test CRM_Member_Form_Membership::postProcess()
     */
    //function testCRMMemberFormMembershipPostProcess()
    //{
    //    throw new PHPUnit_Framework_IncompleteTestError( "not implemented" );
    //}

    /**
     *  Test CRM_Member_Form_Membership::preProcess()
     */
    //function testCRMMemberFormMembershipPreProcess()
    //{
    //    throw new PHPUnit_Framework_IncompleteTestError( "not implemented" );
    //}

    /**
     *  Test CRM_Member_Form_Membership::setDefaultValues()
     */
    //function testCRMMemberFormMembershipSetDefaultValues()
    //{
    //    throw new PHPUnit_Framework_IncompleteTestError( "not implemented" );
    //}
    

} // class CRM_Member_Form_MembershipTest

// -- set Emacs parameters --
// Local variables:
// mode: php;
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
