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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Event.php';

class CRM_Core_BAO_LocationTest extends CiviUnitTestCase 
{
    
    public $_contactId;
    function get_info( ) 
    {
        return [
                     'name'        => 'Location BAOs',
                     'description' => 'Test all Core_BAO_Location methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    function setUp( ) 
    {
        parent::setUp();
    }
    
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    function tearDown()
    {
        $tablesToTruncate = [ 'civicrm_contact',
                                   'civicrm_openid',
                                   'civicrm_loc_block' ];
        $this->quickCleanup( $tablesToTruncate );
    }

    function testCreateWithMissingParams( )
    {
        $contactId = Contact::createIndividual( );
        $params = [ 'contact_id'       => $contactId,
                         'street_address' => 'Saint Helier St' ];
        
        require_once 'CRM/Core/BAO/Location.php';
        CRM_Core_BAO_Location::create( $params );
        
        //Now check DB for Address
        $this->assertDBNull( 'CRM_Core_DAO_Address', 'Saint Helier St', 'id', 'street_address', 
                             'Database check, Address created successfully.' );
        
        //cleanup DB by deleting the contact
        Contact::delete( $contactId );
    }
    
    /**
     * create() method
     * create various elements of location block
     * without civicrm_loc_block entry
     */
    
    function testCreateWithoutLocBlock( )
    {
        $contactId = Contact::createIndividual( );
        
        //create various element of location block 
        //like address, phone, email, openid, im.
        $params =  [ 
                          'address' =>   [ 
                                               '1' => [ 'street_address'            => 'Saint Helier St',
                                                             'supplemental_address_1'    => 'Hallmark Ct',
                                                             'supplemental_address_2'    => 'Jersey Village',
                                                             'city'                      => 'Newark',
                                                             'postal_code'               => '01903',
                                                             'country_id'                => 1228,
                                                             'state_province_id'         => 1029,
                                                             'geo_code_1'                => '18.219023',
                                                             'geo_code_2'                => '-105.00973',
                                                             'is_primary'                => 1,
                                                             'location_type_id'          => 1,
                                                             ],

                                                ],
                          'email'   =>  [ 
                                              '1' =>  [ 'email'            => 'john.smith@example.org',
                                                             'is_primary'       => 1,
                                                             'location_type_id' => 1,
                                                             ], 
                                               ],
                          'phone'   =>  [
                                              '1' =>  [
                                                            'phone_type_id' => 1,
                                                            'phone'         => '303443689',
                                                            'is_primary'                => 1,
                                                            'location_type_id'          => 1,
                                                            ],
                                              '2' =>  [
                                                            'phone_type_id' => 2,
                                                            'phone'         => '9833910234',
                                                            'location_type_id'          => 1,
                                                            ],
                                              ],
                          'openid'  =>  [
                                              '1' =>  [ 'openid'      => 'http://civicrm.org/',
                                                             'location_type_id'          => 1,
                                                             'is_primary'              => 1,
                                                             ],
                                              ],
                          'im'      =>  [
                                              '1' =>  [ 'name'        => 'jane.doe',
                                                             'provider_id' => 1,
                                                             'location_type_id'          => 1,
                                                             'is_primary'              => 1,
                                                             ],
                                              ],
                          ];
        
        $params['contact_id'] = $contactId;
        
        require_once 'CRM/Core/BAO/Location.php';
        $location   = CRM_Core_BAO_Location::create( $params );

        $locBlockId = CRM_Utils_Array::value( 'id', $location ); 
        
        //Now check DB for contact
        $searchParams = [ 'contact_id'              => $contactId, 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1 ];
        $compareParams = [ 'street_address'         => 'Saint Helier St',
                                'supplemental_address_1' => 'Hallmark Ct',
                                'supplemental_address_2' => 'Jersey Village',
                                'city'                   => 'Newark',
                                'postal_code'            => '01903',
                                'country_id'             => 1228,
                                'state_province_id'      => 1029,
                                'geo_code_1'             => '18.219023',
                                'geo_code_2'             => '-105.00973' ];
        $this->assertDBCompareValues( 'CRM_Core_DAO_Address', $searchParams, $compareParams );
        
        $compareParams = [ 'email'                  => 'john.smith@example.org' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams );
        
        $compareParams =  [ 'openid'                => 'http://civicrm.org/' ];
        $this->assertDBCompareValues('CRM_Core_DAO_OpenID', $searchParams, $compareParams );
        
        $compareParams = [ 'name'                   => 'jane.doe',
                                'provider_id'            => 1 ];
        $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams );
        
        $searchParams = [ 'contact_id'              => $contactId, 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1,
                               'phone_type_id'           => 1 ];
        $compareParams = [ 'phone'                  => '303443689' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams );
        
        $searchParams = [ 'contact_id'              => $contactId, 
                               'location_type_id'        => 1, 
                               'phone_type_id'              => 2 ];
        $compareParams = [ 'phone'                  => '9833910234' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams );
        
        //delete the location block
        CRM_Core_BAO_Location::deleteLocBlock( $locBlockId );
        
        //cleanup DB by deleting the contact
        Contact::delete( $contactId );
    }
    
    /**
     * create() method
     * create various elements of location block
     * with civicrm_loc_block
     */
    function testCreateWithLocBlock( )
    {
        $this->_contactId = Contact::createIndividual( );
        //create test event record.
        $eventId = Event::create( );
        $params =  [ 
                          'address' =>   [ 
                                               '1' => [ 'street_address'            => 'Saint Helier St',
                                                             'supplemental_address_1'    => 'Hallmark Ct',
                                                             'supplemental_address_2'    => 'Jersey Village',
                                                             'city'                      => 'Newark',
                                                             'postal_code'               => '01903',
                                                             'country_id'                => 1228,
                                                             'state_province_id'         => 1029,
                                                             'geo_code_1'                => '18.219023',
                                                             'geo_code_2'                => '-105.00973',
                                                             'is_primary'                => 1,
                                                             'location_type_id'          => 1,
                                                             ],

                                                ],
                          'email'   =>  [ 
                                              '1' =>  [ 'email'            => 'john.smith@example.org',
                                                             'is_primary'       => 1,
                                                             'location_type_id' => 1,
                                                             ], 
                                               ],
                          'phone'   =>  [
                                              '1' =>  [
                                                            'phone_type_id' => 1,
                                                            'phone'         => '303443689',
                                                            'is_primary'                => 1,
                                                            'location_type_id'          => 1,
                                                            ],
                                              '2' =>  [
                                                            'phone_type_id' => 2,
                                                            'phone'         => '9833910234',
                                                            'location_type_id'          => 1,
                                                            ],
                                              ],
                          'openid'  =>  [
                                              '1' =>  [ 'openid'      => 'http://civicrm.org/',
                                                             'location_type_id'          => 1,
                                                             'is_primary'                => 1,
                                                             ],
                                              ],
                          'im'      =>  [
                                              '1' =>  [ 'name'        => 'jane.doe',
                                                             'provider_id' => 1,
                                                             'location_type_id'          => 1,
                                                             'is_primary'                => 1,
                                                             ],
                                              ],
                          ];
        
        $params['entity_id']    = $eventId;
        $params['entity_table'] = 'civicrm_event';
        
        //create location block.
        //with various element of location block 
        //like address, phone, email, im.
        require_once 'CRM/Core/BAO/Location.php';
        $location   = CRM_Core_BAO_Location::create( $params, null, true );
        $locBlockId = CRM_Utils_Array::value( 'id', $location );

        //update event record with location block id
        require_once 'CRM/Event/BAO/Event.php';
        $eventParams = [ 'id'           => $eventId,
                              'loc_block_id' => $locBlockId ];
        
        CRM_Event_BAO_Event::add( $eventParams );
        
        //Now check DB for location block
        
        $this->assertDBCompareValue('CRM_Event_DAO_Event', 
                                    $eventId,
                                    'loc_block_id',
                                    'id',
                                    $locBlockId,
                                    'Checking database for the record.'
                                    );
        $locElementIds = [ ];
        CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_LocBlock',
                                      $locParams = [ 'id' => $locBlockId ], 
                                      $locElementIds );

        //Now check DB for location elements.
        $searchParams = [ 'id'                      => CRM_Utils_Array::value( 'address_id', $locElementIds ), 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1 ];
        $compareParams = [ 'street_address'         => 'Saint Helier St',
                                'supplemental_address_1' => 'Hallmark Ct',
                                'supplemental_address_2' => 'Jersey Village',
                                'city'                   => 'Newark',
                                'postal_code'            => '01903',
                                'country_id'             => 1228,
                                'state_province_id'      => 1029,
                                'geo_code_1'             => '18.219023',
                                'geo_code_2'             => '-105.00973' ];
        $this->assertDBCompareValues( 'CRM_Core_DAO_Address', $searchParams, $compareParams );
        
        $searchParams = [ 'id'                      => CRM_Utils_Array::value( 'email_id', $locElementIds ), 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1 ];
        $compareParams = [ 'email'                  => 'john.smith@example.org' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams );
        
        
        $searchParams = [ 'id'                      => CRM_Utils_Array::value( 'phone_id', $locElementIds ), 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1,
                               'phone_type_id'           => 1 ];
        $compareParams = [ 'phone'                  => '303443689' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams );
        
        $searchParams = [ 'id'                      => CRM_Utils_Array::value( 'phone_2_id', $locElementIds ), 
                               'location_type_id'        => 1, 
                               'phone_type_id'           => 2 ];
        $compareParams = [ 'phone'                  => '9833910234' ];
        $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams );
        
        $searchParams = [ 'id'                      => CRM_Utils_Array::value( 'im_id', $locElementIds ), 
                               'location_type_id'        => 1, 
                               'is_primary'              => 1 ];
        $compareParams = [ 'name'                   => 'jane.doe',
                                'provider_id'            => 1 ];
        $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams );
        
        //delete the location block
        CRM_Core_BAO_Location::deleteLocBlock( $locBlockId );
        
        //cleanup DB by deleting the record.
        Event::delete ( $eventId );
        Contact::delete( $this->_contactId );
    }
    
    /**
     * deleteLocBlock() method
     * delete the location block
     * created with various elements.
     * 
     */
    
    function testDeleteLocBlock( )
    {
        $this->_contactId = Contact::createIndividual( );
        //create test event record.
        $eventId = Event::create( );
        $params['location'][1] =  [ 'location_type_id'                               => 1,
                                         'is_primary'                                     => 1,
                                         'address' =>   [ 
                                                              'street_address'            => 'Saint Helier St',
                                                              'supplemental_address_1'    => 'Hallmark Ct',
                                                              'supplemental_address_2'    => 'Jersey Village',
                                                              'city'                      => 'Newark',
                                                              'postal_code'               => '01903',
                                                              'country_id'                => 1228,
                                                              'state_province_id'         => 1029,
                                                              'geo_code_1'                => '18.219023',
                                                              'geo_code_2'                => '-105.00973',
                                                              ],
                                         'email'   =>  [ 
                                                             '1' =>  [ 'email'       => 'john.smith@example.org' ], 
                                                             ],
                                         'phone'   =>  [
                                                             '1' =>  [
                                                                           'phone_type_id' => 1,
                                                                           'phone'         => '303443689',
                                                                           ],
                                                             '2' =>  [
                                                                           'phone_type_id' => 2,
                                                                           'phone'         => '9833910234',
                                                                           ],
                                                             ],
                                         'im'      =>  [
                                                             '1' =>  [ 'name'        => 'jane.doe',
                                                                            'provider_id' => 1
                                                                            ],
                                                             ],
                                         ];
        $params['entity_id']    = $eventId;
        $params['entity_table'] = 'civicrm_event';
        
        //create location block.
        //with various elements
        //like address, phone, email, im.
        require_once 'CRM/Core/BAO/Location.php';
        $location   = CRM_Core_BAO_Location::create( $params, null, true );
        $locBlockId = CRM_Utils_Array::value( 'id', $location );
        //update event record with location block id
        require_once 'CRM/Event/BAO/Event.php';
        $eventParams = [ 'id'           => $eventId,
                              'loc_block_id' => $locBlockId ];
        CRM_Event_BAO_Event::add( $eventParams );
        
        //delete the location block
        CRM_Core_BAO_Location::deleteLocBlock( $locBlockId );
        
        //Now check DB for location elements.
        //Now check DB for Address
        $this->assertDBNull( 'CRM_Core_DAO_Address', 'Saint Helier St', 'id', 'street_address', 
                             'Database check, Address deleted successfully.' );
        //Now check DB for Email
        $this->assertDBNull( 'CRM_Core_DAO_Email', 'john.smith@example.org', 'id', 'email', 
                             'Database check, Email deleted successfully.' );
        //Now check DB for Phone
        $this->assertDBNull( 'CRM_Core_DAO_Phone', '303443689', 'id', 'phone', 
                             'Database check, Phone deleted successfully.' );
        //Now check DB for Mobile
        $this->assertDBNull( 'CRM_Core_DAO_Phone', '9833910234', 'id', 'phone', 
                             'Database check, Mobile deleted successfully.' );
        //Now check DB for IM
        $this->assertDBNull( 'CRM_Core_DAO_IM', 'jane.doe', 'id', 'name', 
                             'Database check, IM deleted successfully.' );
	
        //cleanup DB by deleting the record.
        Event::delete ( $eventId );
        Contact::delete( $this->_contactId );
	
        //Now check DB for Event
        $this->assertDBNull( 'CRM_Event_DAO_Event', $eventId, 'id', 'id', 
                             'Database check, Event deleted successfully.' );
    }

    /**
     * getValues() method
     * get the values of various location elements 
     */
    function testLocBlockgetValues( )
    {
        $contactId = Contact::createIndividual( );
        
        //create various element of location block 
        //like address, phone, email, openid, im.
        $params =  [ 
                          'address' =>   [ 
                                               '1' => [ 'street_address'            => 'Saint Helier St',
                                                             'supplemental_address_1'    => 'Hallmark Ct',
                                                             'supplemental_address_2'    => 'Jersey Village',
                                                             'city'                      => 'Newark',
                                                             'postal_code'               => '01903',
                                                             'country_id'                => 1228,
                                                             'state_province_id'         => 1029,
                                                             'geo_code_1'                => '18.219023',
                                                             'geo_code_2'                => '-105.00973',
                                                             'is_primary'                => 1,
                                                             'location_type_id'          => 1,
                                                             ],

                                                ],
                          'email'   =>  [ 
                                              '1' =>  [ 'email'            => 'john.smith@example.org',
                                                             'is_primary'       => 1,
                                                             'location_type_id' => 1,
                                                             ], 
                                               ],
                          'phone'   =>  [
                                              '1' =>  [
                                                            'phone_type_id' => 1,
                                                            'phone'         => '303443689',
                                                            'is_primary'                => 1,
                                                            'location_type_id'          => 1,
                                                            ],
                                              '2' =>  [
                                                            'phone_type_id' => 2,
                                                            'phone'         => '9833910234',
                                                            'location_type_id'          => 1,
                                                            ],
                                              ],
                          'openid'  =>  [
                                              '1' =>  [ 'openid'      => 'http://civicrm.org/',
                                                             'location_type_id'          => 1,
                                                             'is_primary'              => 1,
                                                             ],
                                              ],
                          'im'      =>  [
                                              '1' =>  [ 'name'        => 'jane.doe',
                                                             'provider_id' => 1,
                                                             'location_type_id'          => 1,
                                                             'is_primary'              => 1,
                                                             ],
                                              ],
                          ];
        
        $params['contact_id'] = $contactId;
                
        //create location elements.
        require_once 'CRM/Core/BAO/Location.php';
        CRM_Core_BAO_Location::create( $params );
                
        //get the values from DB
        $values = CRM_Core_BAO_Location::getValues( $params );
                        
        //Now check values of address
        $this->assertAttributesEquals( CRM_Utils_Array::value( '1', $params['address'] ),
                                       CRM_Utils_Array::value( '1', $values['address'] ) );
        
        //Now check values of email
        $this->assertAttributesEquals( CRM_Utils_Array::value( '1', $params['email'] ),
                                       CRM_Utils_Array::value( '1', $values['email'] ) );
        
        //Now check values of phone
        $this->assertAttributesEquals( CRM_Utils_Array::value( '1', $params['phone'] ),
                                       CRM_Utils_Array::value( '1', $values['phone'] ) );
        
        //Now check values of mobile
        $this->assertAttributesEquals( CRM_Utils_Array::value( '2', $params['phone'] ),
                                       CRM_Utils_Array::value( '2', $values['phone'] ) ); 
        
        //Now check values of openid
        $this->assertAttributesEquals( CRM_Utils_Array::value( '1', $params['openid'] ),
                                       CRM_Utils_Array::value( '1', $values['openid'] ) );
        
        //Now check values of im
        $this->assertAttributesEquals( CRM_Utils_Array::value( '1', $params['im'] ),
                                       CRM_Utils_Array::value( '1', $values['im'] ) );
        
        //cleanup DB by deleting the contact
        Contact::delete( $contactId );
    }
    
}
